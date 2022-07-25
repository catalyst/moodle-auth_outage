<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * outagedb class.
 *
 * The DB Context to manipulate Outages.
 * It will also commit changes to the calendar as you change outages.
 *
 * @package    auth_outage
 * @author     Daniel Thee Roperto <daniel.roperto@catalyst-au.net>
 * @copyright  2016 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace auth_outage\dml;

use auth_outage\calendar\calendar;
use auth_outage\event\outage_created;
use auth_outage\event\outage_deleted;
use auth_outage\event\outage_updated;
use auth_outage\local\outage;
use auth_outage\local\outagelib;
use coding_exception;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/calendar/lib.php');

/**
 * outagedb class.
 *
 * The DB Context to manipulate Outages.
 * It will also commit changes to the calendar as you change outages.
 *
 * @package    auth_outage
 * @author     Daniel Thee Roperto <daniel.roperto@catalyst-au.net>
 * @copyright  2016 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class outagedb {
    /**
     * Private constructor, use static methods instead.
     */
    private function __construct() {
    }

    /**
     * Gets all outage entries.
     */
    public static function get_all() {
        global $DB;

        $outages = [];

        $rs = $DB->get_recordset('auth_outage', null, 'starttime,stoptime,title');
        foreach ($rs as $r) {
            $outages[] = new outage($r);
        }
        $rs->close();

        return $outages;
    }

    /**
     * Gets an outage based on the given id.
     * @param int $id Outage id to get.
     * @return outage|null Returns the outage or null if not found.
     * @throws coding_exception
     */
    public static function get_by_id($id) {
        global $DB;

        if (!is_int($id) || ($id <= 0)) {
            throw new coding_exception('$id must be an positive int.', $id);
        }

        $outage = $DB->get_record('auth_outage', ['id' => $id]);
        if ($outage === false) {
            return null;
        }

        return new outage($outage);
    }

    /**
     * Saves an outage to the database.
     *
     * @param outage $outage Outage to save.
     * @return int Outage ID.
     */
    public static function save(outage $outage) {
        global $DB, $USER;

        // Do not change the original object.
        $outage = clone $outage;

        // Update control fields.
        $outage->modifiedby = $USER->id;
        $outage->lastmodified = time();

        if ($outage->id === null) {
            // If new outage, set its creator.
            $outage->createdby = $USER->id;
            // Then create it, log it and adjust its id.
            $outage->id = $DB->insert_record('auth_outage', $outage, true);

            $other = (array) $outage;
            $other['title'] = $outage->get_title();
            $event = outage_created::create([
                'objectid' => $outage->id,
                'other' => $other,
            ]);
            $event->add_record_snapshot('auth_outage', (object)(array) $outage);
            $event->trigger();

            // Create calendar entry.
            calendar::create($outage);
        } else {

            $other = (array) $outage;
            $other['title'] = $outage->get_title();
            $event = outage_updated::create([
                'objectid' => $outage->id,
                'other' => $other,
            ]);

            $event->add_record_snapshot('auth_outage', (object)(array) $outage);
            $event->trigger();

            // Remove the createdby field so it does not get updated.
            unset($outage->createdby);
            $DB->update_record('auth_outage', $outage);

            // Update calendar entry.
            calendar::update($outage);
        }

        // Trigger outages modified events.
        outagelib::prepare_next_outage(true);

        // All done, return the id.
        return $outage->id;
    }

    /**
     * Deletes an outage from the database.
     *
     * @param int $id Outage ID to delete
     * @throws coding_exception
     */
    public static function delete($id) {
        global $DB;

        if (!is_int($id) || ($id <= 0)) {
            throw new coding_exception('$id must be an int.', $id);
        }

        // Log it.
        $previous = $DB->get_record('auth_outage', ['id' => $id], '*', MUST_EXIST);

        $outage = new outage($previous);

        $other = (array) $outage;
        $other['title'] = $outage->get_title();
        $event = outage_deleted::create([
            'objectid' => $id,
            'other' => $other,
        ]);

        $event->add_record_snapshot('auth_outage', $previous);
        $event->trigger();

        // Delete it and remove from calendar.
        $DB->delete_records('auth_outage', ['id' => $id]);
        calendar::delete($id);

        // Trigger events.
        outagelib::prepare_next_outage();
    }

    /**
     * Gets the most important active outage, considering importance as:
     *  - Ongoing outages more important than outages in warning period.
     *  - Outages that start earlier are more important.
     *  - Outages that stop later are more important.
     * @param int|null $time Timestamp considered to check for outages, null for current date/time.
     * @return outage|null The outage or null if no active outages were found.
     * @throws coding_exception
     */
    public static function get_active($time = null) {
        global $DB;

        if ($time === null) {
            $time = time();
        }
        if (!is_int($time) || ($time <= 0)) {
            throw new coding_exception('$time must be null or a positive int.', $time);
        }

        // Get cached outage, or null.
        $outageinfo = get_config('moodle', 'auth_outage_active_outage');

        if (!$outageinfo) {
            return null;
        } else {
            $outagecache = new outage(json_decode($outageinfo));
        }

        if ($outagecache && $outagecache->warntime <= $time && $outagecache->stoptime >= $time
            && (!$outagecache->finished || $outagecache->finished >= $time)) {
            return  $outagecache;
        }
        return null;
    }

    /**
     * Gets all outages that have not ended yet.
     * @param int|null $time Timestamp considered to check for outages, null for current date/time.
     * @return outage[] An array of outages or an empty array if no unded outages were found.
     * @throws coding_exception
     */
    public static function get_all_unended($time = null) {
        global $DB;

        if ($time === null) {
            $time = time();
        }
        if (!is_int($time) || ($time <= 0)) {
            throw new coding_exception('$time must be null or a positive int.');
        }

        $outages = [];

        $rs = $DB->get_recordset_select(
            'auth_outage',
            ':datetime1 < stoptime AND (finished IS NULL OR :datetime2 < finished)',
            ['datetime1' => $time, 'datetime2' => $time],
            'starttime ASC, stoptime DESC, title ASC',
            '*');
        foreach ($rs as $r) {
            $outages[] = new outage($r);
        }
        $rs->close();

        return $outages;
    }

    /**
     * Gets all ended outages.
     * @param int|null $time Timestamp considered to check for outages, null for current date/time.
     * @return outage[] An array of outages or an empty array if no ended outages found.
     * @throws coding_exception
     */
    public static function get_all_ended($time = null) {
        global $DB;

        if ($time === null) {
            $time = time();
        }
        if (!is_int($time) || ($time <= 0)) {
            throw new coding_exception('$time must be null or a positive int.', $time);
        }

        $outages = [];

        $rs = $DB->get_recordset_select(
            'auth_outage',
            'NOT (:datetime1 < stoptime AND (finished IS NULL OR :datetime2 < finished))',
            ['datetime1' => $time, 'datetime2' => $time],
            'stoptime DESC, starttime DESC, title ASC',
            '*');
        foreach ($rs as $r) {
            $outages[] = new outage($r);
        }
        $rs->close();

        return $outages;
    }

    /**
     * Marks an outage as finished.
     * @param int $id Outage id.
     * @param int|null $time Timestamp to consider as finished date or null for current time.
     * @throws coding_exception
     */
    public static function finish($id, $time = null) {
        if (is_null($time)) {
            $time = time();
        }
        if (!is_int($time) || ($time <= 0)) {
            throw new coding_exception('$time must be null or a positive int.', $time);
        }

        $outage = self::get_by_id($id);
        if (is_null($outage)) {
            debugging('Cannot finish outage #'.$id.': outage not found.');
            return;
        }

        if (!$outage->is_ongoing($time)) {
            debugging('Cannot finish outage #'.$id.': outage not ongoing.');
            return;
        }

        $outage->finished = $time;
        self::save($outage);
    }

    /**
     * Gets the next outage which has not started yet.
     * @param null $time Timestamp reference for current time.
     * @return outage|null The outage or null if not found.
     * @throws coding_exception
     */
    public static function get_next_starting($time = null) {
        global $DB;

        if ($time === null) {
            $time = time();
        }
        if (!is_int($time) || ($time <= 0)) {
            throw new coding_exception('$time must be null or a positive int.', $time);
        }

        $data = $DB->get_records_select(
            'auth_outage',
            ':datetime <= starttime',
            ['datetime' => $time],
            'starttime ASC',
            '*',
            0,
            1
        );

        // Not using $DB->get_record_select instead because there is no 'limit' parameter.
        // Allowing multiple records still raises an internal error.
        return (count($data) == 0) ? null : new outage(array_shift($data));
    }

    /**
     * Gets the next outage which has not started yet and has the autostart flag set to true.
     * @param null $time Timestamp reference for current time.
     * @return outage|null The outage or null if not found.
     * @throws coding_exception
     */
    public static function get_next_autostarting($time = null) {
        global $DB;

        if ($time === null) {
            $time = time();
        }
        if (!is_int($time) || ($time <= 0)) {
            throw new coding_exception('$time must be null or a positive int.', $time);
        }

        $data = $DB->get_records_select(
            'auth_outage',
            '(:datetime <= starttime) AND (autostart = 1)',
            ['datetime' => $time],
            'starttime ASC',
            '*',
            0,
            1
        );

        // Not using $DB->get_record_select instead because there is no 'limit' parameter.
        // Allowing multiple records still raises an internal error.
        return (count($data) == 0) ? null : new outage(array_shift($data));
    }

    /**
     * Gets an ongoing outage (between start and stop time but not finished).
     * @param int|null $time Timestamp considered to check for outages, null for current date/time.
     * @return outage|null The outage or null if no active outages were found.
     * @throws coding_exception
     */
    public static function get_ongoing($time = null) {
        global $DB;

        if ($time === null) {
            $time = time();
        }
        if (!is_int($time) || ($time <= 0)) {
            throw new coding_exception('$time must be null or a positive int.', $time);
        }

        $data = $DB->get_records_select(
            'auth_outage',
            'starttime <= :datetime1 AND :datetime2 <= stoptime AND finished IS NULL',
            ['datetime1' => $time, 'datetime2' => $time, 'datetime3' => $time],
            'starttime ASC, stoptime DESC, title ASC',
            '*',
            0,
            1
        );

        // Not using $DB->get_record_select instead because there is no 'limit' parameter.
        // Allowing multiple records still raises an internal error.
        return (count($data) == 0) ? null : new outage(array_shift($data));
    }
}

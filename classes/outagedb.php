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

namespace auth_outage;

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.'); // It must be included from a Moodle page.
}

require_once($CFG->dirroot . '/calendar/lib.php');

use auth_outage\event\outage_created;
use auth_outage\event\outage_deleted;
use auth_outage\event\outage_updated;
use auth_outage\models\outage;
use calendar_event;
use InvalidArgumentException;

/**
 * The DB Context to manipulate Outages.
 * It will also commit changes to the calendar as you change outages.
 *
 * @package    auth_outage
 * @author     Daniel Thee Roperto <daniel.roperto@catalyst-au.net>
 * @copyright  Catalyst IT
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
     * @param $id int Outage id to get.
     * @return outage|null Returns the outage or null if not found.
     */
    public static function get_by_id($id) {
        global $DB;

        if (!is_int($id)) {
            throw new InvalidArgumentException('$id must be an int.');
        }
        if ($id <= 0) {
            throw new InvalidArgumentException('$id must be positive.');
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
            outage_created::create(
                ['objectid' => $outage->id, 'other' => (array)$outage]
            )->trigger();
            // Create calendar entry.
            self::calendar_create($outage);
        } else {
            // Remove the createdby field so it does not get updated.
            unset($outage->createdby);
            $DB->update_record('auth_outage', $outage);
            // Log it.
            outage_updated::create(
                ['objectid' => $outage->id, 'other' => (array)$outage]
            )->trigger();
            // Update calendar entry.
            self::calendar_update($outage);
        }

        // All done, return the id.
        return $outage->id;
    }

    /**
     * Deletes an outage from the database.
     *
     * @param int $id Outage ID to delete
     * @throws InvalidArgumentException If ID is not valid.
     */
    public static function delete($id) {
        global $DB;

        if (!is_int($id)) {
            throw new InvalidArgumentException('$id must be an int.');
        }
        if ($id <= 0) {
            throw new InvalidArgumentException('$id must be positive.');
        }

        // Log it.
        $previous = $DB->get_record('auth_outage', ['id' => $id], '*', MUST_EXIST);
        $event = outage_deleted::create(['objectid' => $id, 'other' => (array)$previous]);
        $event->add_record_snapshot('auth_outage', $previous);
        $event->trigger();

        // Delete it and remove from calendar.
        $DB->delete_records('auth_outage', ['id' => $id]);
        self::calendar_delete($id);
    }

    /**
     * Gets the most important active outage, considering importance as:
     *  - Ongoing outages more important than outages in warning period.
     *  - Outages that start earlier are more important.
     *  - Outages that stop later are more important.
     * @param int|null $time Timestamp considered to check for outages, null for current date/time.
     * @return outage|null The outage or null if no active outages were found.
     */
    public static function get_active($time = null) {
        global $DB;

        if ($time === null) {
            $time = time();
        }
        if (!is_int($time)) {
            throw new InvalidArgumentException('$time must be null or an int.');
        }

        $select = ':datetime2 <= stoptime AND (finished IS NULL OR :datetime3 <= finished)'; // End condition.
        $select = "(warntime <= :datetime1 AND (${select}))"; // Full select part.
        $data = $DB->get_records_select(
            'auth_outage',
            $select,
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

    /**
     * Gets all outages that have not ended yet.
     * @param int|null $time Timestamp considered to check for outages, null for current date/time.
     * @return array An array of outages or an empty array if no unded outages were found.
     */
    public static function get_all_unended($time = null) {
        global $DB;

        if ($time === null) {
            $time = time();
        }
        if (!is_int($time)) {
            throw new InvalidArgumentException('$time must be null or an int.');
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
     * @return array An array of outages or an empty array if no ended outages found.
     */
    public static function get_all_ended($time = null) {
        global $DB;

        if ($time === null) {
            $time = time();
        }
        if (!is_int($time)) {
            throw new InvalidArgumentException('$time must be null or an int.');
        }

        $outages = [];

        $rs = $DB->get_recordset_select(
            'auth_outage',
            ':datetime1 >= stoptime OR (finished IS NOT NULL AND :datetime2 >= finished)',
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
     */
    public static function finish($id, $time = null) {
        if (is_null($time)) {
            $time = time();
        }
        if (!is_int($time)) {
            throw new InvalidArgumentException('$time must be an int or null.');
        }

        $outage = self::get_by_id($id);
        if (is_null($outage)) {
            debugging('Cannot finish outage #' . $id . ': outage not found.');
            return;
        }

        if (!$outage->is_ongoing($time)) {
            debugging('Cannot finish outage #' . $id . ': outage not ongoing.');
            return;
        }

        $outage->finished = $time;
        self::save($outage);
    }

    /**
     * Create an event on the calendar for this outage.
     * @param outage $outage Outage to be added to the calendar.
     */
    private static function calendar_create(outage $outage) {
        calendar_event::create(self::calendar_data($outage));
    }

    /**
     * Updates an event on the calendar based on this outage.
     * @param outage $outage Outage to be updated in the calendar.
     */
    private static function calendar_update(outage $outage) {
        $event = self::calendar_load($outage->id);

        if (is_null($event)) {
            debugging('Cannot update calendar entry for outage #' . $outage->id . ', event not found. Creating it...');
            self::calendar_create($outage);
        } else {
            $event->update(self::calendar_data($outage));
        }
    }

    /**
     * Removes an event from the calendar related to this outage.
     * @param int $outageid Id of outage to be deleted from the calendar.
     */
    private static function calendar_delete($outageid) {
        $event = self::calendar_load($outageid);

        // If not found (was not created before) ignore it.
        if (is_null($event)) {
            debugging('Cannot delete calendar entry for outage #' . $outageid . ', event not found. Ignoring it...');
        } else {
            $event->delete();
        }
    }

    /**
     * Generates an array with the calendar event data based on an outage object.
     * @param outage $outage Outage to use as reference for the calendar event.
     * @return array Calendar event data.
     */
    private static function calendar_data(outage $outage) {
        return [
            'name' => $outage->get_title(),
            'description' => $outage->get_description(),
            'courseid' => 1,
            'groupid' => 0,
            'userid' => 0,
            'modulename' => '',
            'instance' => $outage->id,
            'eventtype' => 'auth_outage',
            'timestart' => $outage->starttime,
            'visible' => true,
            'timeduration' => $outage->get_duration(),
        ];
    }

    /**
     * Finds the calendar event for an specific outage.
     * @param int $outageid The outage id to find in the calendar.
     * @return calendar_event|null The calendar event or null if not found.
     */
    private static function calendar_load($outageid) {
        global $DB;

        $event = $DB->get_record_select(
            'event',
            "(eventtype = 'auth_outage' AND instance = :outageid)",
            ['outageid' => $outageid],
            'id',
            IGNORE_MISSING
        );

        return ($event === false) ? null : calendar_event::load($event->id);
    }
}

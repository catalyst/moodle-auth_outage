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
 * The DB Context to manipulate Outages. Singleton class.
 *
 * @package    auth_outage
 * @author     Daniel Thee Roperto <daniel.roperto@catalyst-au.net>
 * @copyright  Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace auth_outage;

use auth_outage\models\outage;

final class outagedb {
    /**
     * Private constructor, use static methods instead.
     */
    private function __construct() {
    }

    /**
     * Gets all outage entries.
     */
    public static function getall() {
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
    public static function getbyid($id) {
        global $DB;

        if (!is_int($id)) {
            throw new \InvalidArgumentException('$id must be an int.');
        }
        if ($id <= 0) {
            throw new \InvalidArgumentException('$id must be positive.');
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
            \auth_outage\event\outage_created::create(
                ['objectid' => $outage->id, 'other' => (array)$outage]
            )->trigger();
        } else {
            // Remove the createdby field so it does not get updated.
            unset($outage->createdby);
            $DB->update_record('auth_outage', $outage);
            // Log it.
            \auth_outage\event\outage_updated::create(
                ['objectid' => $outage->id, 'other' => (array)$outage]
            )->trigger();
        }

        // All done, return the id.
        return $outage->id;
    }

    /**
     * Deletes an outage from the database.
     *
     * @param $id outage Outage ID to delete
     * @throws InvalidArgumentException If ID is not valid.
     */
    public static function delete($id) {
        global $DB;

        if (!is_int($id)) {
            throw new \InvalidArgumentException('$id must be an int.');
        }
        if ($id <= 0) {
            throw new \InvalidArgumentException('$id must be positive.');
        }

        // Log it.
        $previous = $DB->get_record('auth_outage', ['id' => $id], '*', MUST_EXIST);
        $event = \auth_outage\event\outage_deleted::create(['objectid' => $id, 'other' => (array)$previous]);
        $event->add_record_snapshot('auth_outage', $previous);
        $event->trigger();

        $DB->delete_records('auth_outage', ['id' => $id]);
    }

    /**
     * Gets the most important active outage, considering importance as:
     *  - Ongoing outages more important than outages in warning period.
     *  - Outages that start earlier are more important.
     *  - Outages that stop later are more important.
     * @param int|null $time Timestamp considered to check for outages, null for current date/time.
     * @return outage|null The outage or null if no active outages were found.
     */
    public static function getactive($time = null) {
        global $DB;

        if ($time === null) {
            $time = time();
        }
        if (!is_int($time)) {
            throw new \InvalidArgumentException('$time must be null or an int.');
        }

        // TODO Is there a way to use Moodle API instead of writing SQL (conditions not equals)?
        // TODO Query not fully using indexes (starttime + 90)
        // Gets any active outage (already started or during warning period).
        // Gets only one record if available, the one that starts(ed) first and that stops last.
        $now = time();
        $data = $DB->get_record_sql('
                SELECT *
                FROM {auth_outage}
                WHERE (starttime - warningduration <= :now1 AND stoptime >= :now2) 
                ORDER BY starttime ASC, stoptime DESC, title ASC
                LIMIT 1
            ',
            ['now1' => $now, 'now2' => $now]
        );

        return ($data === false) ? null : new \auth_outage\models\outage($data);
    }
}

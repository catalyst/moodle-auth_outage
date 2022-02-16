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
 * calendar class.
 *
 * @package    auth_outage
 * @author     Daniel Thee Roperto <daniel.roperto@catalyst-au.net>
 * @copyright  2016 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace auth_outage\calendar;

use auth_outage\local\outage;
use calendar_event;

defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot.'/calendar/lib.php');

/**
 * calendar class.
 *
 * @package    auth_outage
 * @author     Daniel Thee Roperto <daniel.roperto@catalyst-au.net>
 * @copyright  2016 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class calendar {
    /**
     * Private constructor, use static methods instead.
     */
    private function __construct() {
    }

    /**
     * Create an event on the calendar for this outage.
     * @param outage $outage Outage to be added to the calendar.
     */
    public static function create(outage $outage) {
        calendar_event::create(self::create_data($outage));
    }

    /**
     * Updates an event on the calendar based on this outage.
     * @param outage $outage Outage to be updated in the calendar.
     */
    public static function update(outage $outage) {
        $event = self::load($outage->id);

        if (is_null($event)) {
            debugging('Cannot update calendar entry for outage #'.$outage->id.', event not found. Creating it...');
            self::create($outage);
        } else {
            $event->update(self::create_data($outage), false);
        }
    }

    /**
     * Removes an event from the calendar related to this outage.
     * @param int $outageid Id of outage to be deleted from the calendar.
     */
    public static function delete($outageid) {
        $event = self::load($outageid);

        // If not found (was not created before) ignore it.
        if (is_null($event)) {
            debugging('Cannot delete calendar entry for outage #'.$outageid.', event not found. Ignoring it...');
        } else {
            $event->delete();
        }
    }

    /**
     * Generates an array with the calendar event data based on an outage object.
     * @param outage $outage Outage to use as reference for the calendar event.
     * @return mixed[] Calendar event data.
     */
    private static function create_data(outage $outage) {
        return [
            'name' => $outage->get_title(),
            'description' => $outage->get_description(),
            'courseid' => 1,
            'groupid' => 0,
            'userid' => 0,
            'modulename' => '',
            'instance' => $outage->id,
            'eventtype' => 'site',
            'timestart' => $outage->starttime,
            'visible' => true,
            'timeduration' => $outage->get_duration_planned(),
        ];
    }

    /**
     * Finds the calendar event for an specific outage.
     * @param int $outageid The outage id to find in the calendar.
     * @return calendar_event|null The calendar event or null if not found.
     */
    public static function load($outageid) {
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

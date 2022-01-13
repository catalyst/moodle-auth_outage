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
 * events_test tests class.
 *
 * @package         auth_outage
 * @author          Daniel Thee Roperto <daniel.roperto@catalyst-au.net>
 * @copyright       Catalyst IT
 * @license         http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use auth_outage\dml\outagedb;
use auth_outage\local\outage;

/**
 * events_test tests class.
 *
 * We are using static variables instead of test dependencies as the
 * annotation 'depends' is not accepted in moodle checker.
 *
 * @package         auth_outage
 * @author          Daniel Thee Roperto <daniel.roperto@catalyst-au.net>
 * @copyright       Catalyst IT
 * @license         http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class auth_outage_events_test extends advanced_testcase {
    /**
     * @var outage|null Outage used in the tests.
     */
    private static $outage = null;

    /**
     * @var stdClass|null Data for the created event.
     */
    private static $event = null;

    /**
     * Saves an outage and check if the event was created.
     * @return array With the outage id and the event id.
     */
    public function test_save() {
        global $DB;
        self::setAdminUser();
        $this->resetAfterTest(true);

        // Save new outage.
        $now = time();
        $outage = new outage([
            'autostart' => false,
            'warntime' => $now - 60,
            'starttime' => 60,
            'stoptime' => 120,
            'title' => 'Title',
            'description' => 'Description',
        ]);
        $outage->id = outagedb::save($outage);
        self::$outage = $outage;

        // Check existance.
        self::$event = $DB->get_record_select(
            'event',
            "(eventtype = 'auth_outage' AND instance = :idoutage)",
            ['idoutage' => self::$outage->id],
            'id',
            IGNORE_MISSING
        );
        self::assertTrue(is_object(self::$event));
    }

    /**
     * Updates an outage and checks if the event was updated.
     */
    public function test_update() {
        global $DB;

        self::setAdminUser();
        $this->resetAfterTest(true);

        // Save new outage.
        $now = time();
        $outage = new outage([
            'autostart' => false,
            'warntime' => $now - 60,
            'starttime' => 60,
            'stoptime' => 120,
            'title' => 'Title',
            'description' => 'Description',
        ]);
        $outage->id = outagedb::save($outage);
        self::$outage = $outage;

        self::$outage->starttime += 10;
        outagedb::save(self::$outage);

        // Should still exist.
        $event = $DB->get_record_select(
            'event',
            "(eventtype = 'auth_outage' AND instance = :idoutage)",
            ['idoutage' => self::$outage->id],
            'id',
            IGNORE_MISSING
        );
        self::assertTrue(is_object($event));
        self::assertSame(self::$event->id, $event->id);
        self::$event = $event;
    }

    /**
     * Deletes an outage and checks if the event was deleted.
     */
    public function test_delete() {
        global $DB;

        self::setAdminUser();
        $this->resetAfterTest(true);

        // Save new outage.
        $now = time();
        $outage = new outage([
            'autostart' => false,
            'warntime' => $now - 60,
            'starttime' => 60,
            'stoptime' => 120,
            'title' => 'Title',
            'description' => 'Description',
        ]);
        $outage->id = outagedb::save($outage);
        self::$outage = $outage;

        outagedb::delete(self::$outage->id);

        // Should not exist.
        $event = $DB->get_record_select(
            'event',
            "(eventtype = 'auth_outage' AND instance = :idoutage) OR (id = :idevent)",
            ['idoutage' => self::$outage->id, 'idevent' => self::$event->id],
            'id',
            IGNORE_MISSING
        );
        self::assertFalse($event);
    }
}

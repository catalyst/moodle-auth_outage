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
 * calendar_test test class.
 *
 * @package         auth_outage
 * @author          Daniel Thee Roperto <daniel.roperto@catalyst-au.net>
 * @copyright       Catalyst IT
 * @license         http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use auth_outage\calendar\calendar;
use auth_outage\local\outage;

/**
 * calendar_test test class.
 *
 * We are using static variables instead of test dependencies as the
 * annotation 'depends' is not accepted in moodle checker.
 *
 * @package         auth_outage
 * @author          Daniel Thee Roperto <daniel.roperto@catalyst-au.net>
 * @copyright       Catalyst IT
 * @license         http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class auth_outage_calendar_test extends advanced_testcase {
    /**
     * @var outage|null The calendar entry owner.
     */
    private static $outage = null;

    /**
     * Creates an outage and checks if its in the calendar.
     */
    public function test_create() {
        $this->resetAfterTest(true);
        self::setAdminUser();

        $time = time();
        self::$outage = new outage([
            'id' => 1,
            'autostart' => false,
            'warntime' => $time - 100,
            'starttime' => $time,
            'stoptime' => $time + (2 * 60 * 60),
            'title' => 'Title',
            'description' => 'Description',
        ]);
        calendar::create(self::$outage);
        $this->check_calendar();
    }

    /**
     * Updates an outage and checks the calendar.
     */
    public function test_update() {
        $this->resetAfterTest(true);
        self::setAdminUser();

        $time = time();
        self::$outage = new outage([
            'id' => 1,
            'autostart' => false,
            'warntime' => $time - 100,
            'starttime' => $time,
            'stoptime' => $time + (2 * 60 * 60),
            'title' => 'Title',
            'description' => 'Description',
        ]);
        calendar::create(self::$outage);

        self::$outage->title = 'New Title';
        calendar::update(self::$outage);
        $this->check_calendar();
    }

    /**
     * Deletes an outage and checks the calendar.
     */
    public function test_delete() {
        $this->resetAfterTest(true);
        self::setAdminUser();

        $time = time();
        self::$outage = new outage([
            'id' => 1,
            'autostart' => false,
            'warntime' => $time - 100,
            'starttime' => $time,
            'stoptime' => $time + (2 * 60 * 60),
            'title' => 'Title',
            'description' => 'Description',
        ]);
        calendar::create(self::$outage);

        $this->check_calendar();

        calendar::delete(self::$outage->id);
        self::assertNull(calendar::load(self::$outage->id));
    }

    /**
     * Try to update a non existing outage.
     */
    public function test_update_notfound() {
        $this->resetAfterTest(true);
        self::setAdminUser();

        $time = time();
        $outage = new outage([
            'id' => 1,
            'autostart' => false,
            'warntime' => $time - 100,
            'starttime' => $time,
            'stoptime' => $time + (2 * 60 * 60),
            'title' => 'Title',
            'description' => 'Description',
        ]);

        calendar::update($outage);
        self::assertCount(1, $this->getDebuggingMessages());
        $this->resetDebugging();
    }

    /**
     * Try to delete a non existing outage.
     */
    public function test_delete_notfound() {
        $this->resetAfterTest(true);
        self::setAdminUser();

        calendar::delete(1);
        self::assertCount(1, $this->getDebuggingMessages());
        $this->resetDebugging();
    }

    /**
     * Check if there is a calendar entry for the given outage.
     */
    private function check_calendar() {
        $calendar = calendar::load(self::$outage->id);
        self::assertSame(self::$outage->title, $calendar->name);
        self::assertSame(self::$outage->description, $calendar->description);
        self::assertSame('auth_outage', $calendar->eventtype);
        self::assertSame('', $calendar->modulename);
        self::assertEquals(self::$outage->starttime, $calendar->timestart);
        self::assertEquals(self::$outage->get_duration_planned(), $calendar->timeduration);
    }
}

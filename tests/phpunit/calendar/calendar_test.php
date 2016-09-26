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

use auth_outage\calendar\calendar;
use auth_outage\local\outage;

defined('MOODLE_INTERNAL') || die();

/**
 * Tests performed on outage class.
 *
 * @package         auth_outage
 * @author          Daniel Thee Roperto <daniel.roperto@catalyst-au.net>
 * @copyright       Catalyst IT
 * @license         http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class calendar_test extends advanced_testcase {
    public function test_create() {
        self::setAdminUser();
        $this->resetAfterTest(false);

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
        calendar::create($outage);
        $this->check_calendar($outage);

        return $outage;
    }

    /**
     * @depends test_create
     */
    public function test_update(outage $outage) {
        self::setAdminUser();
        $this->resetAfterTest(false);

        $outage->title = 'New Title';
        calendar::update($outage);
        $this->check_calendar($outage);

        return $outage;
    }

    /**
     * @depends test_update
     */
    public function test_delete($outage) {
        self::setAdminUser();
        $this->resetAfterTest(true);

        calendar::delete($outage->id);
        self::assertNull(calendar::load($outage->id));
    }

    public function test_update_notfound() {
        self::setAdminUser();
        $this->resetAfterTest(true);

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
        self::assertCount(1, phpunit_util::get_debugging_messages());
        phpunit_util::reset_debugging();
    }

    public function test_delete_notfound() {
        self::setAdminUser();
        $this->resetAfterTest(true);
        calendar::delete(1);
        self::assertCount(1, phpunit_util::get_debugging_messages());
        phpunit_util::reset_debugging();
    }

    private function check_calendar(outage $outage) {
        $calendar = calendar::load($outage->id);
        self::assertSame($outage->title, $calendar->name);
        self::assertSame($outage->description, $calendar->description);
        self::assertSame('auth_outage', $calendar->eventtype);
        self::assertEquals($outage->starttime, $calendar->timestart);
        self::assertEquals($outage->get_duration_planned(), $calendar->timeduration);
    }
}

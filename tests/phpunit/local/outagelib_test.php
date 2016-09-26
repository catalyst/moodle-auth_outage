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

use auth_outage\dml\outagedb;
use auth_outage\local\outage;
use auth_outage\local\outagelib;

defined('MOODLE_INTERNAL') || die();

/**
 * Tests performed on outagelib class.
 *
 * @package     auth_outage
 * @author      Daniel Thee Roperto <daniel.roperto@catalyst-au.net>
 * @copyright   2016 Catalyst IT
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class outagelib_test extends advanced_testcase {
    public function test_maintenancemessage() {
        $this->resetAfterTest(true);
        static::setAdminUser();

        $now = time();
        $outage = new outage([
            'autostart' => true,
            'warntime' => $now,
            'starttime' => $now + 100,
            'stoptime' => $now + 200,
            'title' => 'Title',
            'description' => 'Description',
        ]);

        set_config('maintenance_message', 'A message.');
        outagedb::save($outage);
        self::assertFalse((bool)get_config('moodle', 'maintenance_message'));
        self::assertCount(2, phpunit_util::get_debugging_messages());
        phpunit_util::reset_debugging();
    }

    public function test_maintenancelater_nonext() {
        $this->resetAfterTest(true);
        set_config('maintenance_later', time() + (60 * 60 * 24 * 7)); // In 1 week.
        self::assertNotEmpty(get_config('moodle', 'maintenance_later'));
        outagelib::outages_modified();
        self::assertEmpty(get_config('moodle', 'maintenance_later'));
    }

    public function test_inject() {
        global $CFG;

        $this->resetAfterTest(true);
        self::setAdminUser();
        $now = time();
        $outage = new outage([
            'autostart' => true,
            'warntime' => $now,
            'starttime' => $now + 100,
            'stoptime' => $now + 200,
            'title' => 'Title',
            'description' => 'Description',
        ]);
        $outage->id = outagedb::save($outage);
        self::assertEmpty($CFG->additionalhtmltopofbody);

        outagelib::reinject();
        self::assertContains('<style>', $CFG->additionalhtmltopofbody);
        self::assertContains('<script>', $CFG->additionalhtmltopofbody);

        // Should not inject again.
        $size = strlen($CFG->additionalhtmltopofbody);
        outagelib::inject();
        self::assertSame($size, strlen($CFG->additionalhtmltopofbody));
    }

    public function test_inject_broken() {
        global $CFG;
        $_GET = ['auth_outage_break_code' => '1'];
        outagelib::reinject();
        self::assertCount(2, phpunit_util::get_debugging_messages());
        phpunit_util::reset_debugging();
    }

    public function test_inject_preview() {
        global $CFG;
        $this->resetAfterTest(true);
        self::setAdminUser();
        $now = time();
        $outage = new outage([
            'autostart' => true,
            'warntime' => $now,
            'starttime' => $now + 100,
            'stoptime' => $now + 200,
            'title' => 'Title',
            'description' => 'Description',
        ]);
        $outage->id = outagedb::save($outage);
        self::assertEmpty($CFG->additionalhtmltopofbody);
        $_GET = ['auth_outage_preview' => (string)$outage->id];

        outagelib::reinject();
        self::assertContains('<style>', $CFG->additionalhtmltopofbody);
        self::assertContains('<script>', $CFG->additionalhtmltopofbody);
    }

    public function test_inject_preview_notfound() {
        global $CFG;
        self::assertEmpty($CFG->additionalhtmltopofbody);
        $_GET = ['auth_outage_preview' => '1'];
        // Should not throw exception or halt anything, silently ignore it.
        outagelib::reinject();
        self::assertEmpty($CFG->additionalhtmltopofbody);
    }

    public function test_inject_preview_withdelta() {
        global $CFG;
        $this->resetAfterTest(true);
        self::setAdminUser();
        $now = time();
        $outage = new outage([
            'autostart' => true,
            'warntime' => $now,
            'starttime' => $now + 100,
            'stoptime' => $now + 200,
            'title' => 'Title',
            'description' => 'Description',
        ]);
        $outage->id = outagedb::save($outage);
        self::assertEmpty($CFG->additionalhtmltopofbody);
        $_GET = ['auth_outage_preview' => (string)$outage->id, 'auth_outage_delta' => '500'];
        outagelib::reinject();
        // Still empty, delta is too high (outage ended).
        self::assertEmpty($CFG->additionalhtmltopofbody);
    }

    public function test_inject_noactive() {
        outagelib::reinject();
    }
}


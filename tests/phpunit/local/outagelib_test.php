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
 * outagelib_test test class.
 *
 * @package     auth_outage
 * @author      Daniel Thee Roperto <daniel.roperto@catalyst-au.net>
 * @copyright   2016 Catalyst IT
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use auth_outage\dml\outagedb;
use auth_outage\local\outage;
use auth_outage\local\outagelib;

defined('MOODLE_INTERNAL') || die();

/**
 * outagelib_test test class.
 *
 * @package     auth_outage
 * @author      Daniel Thee Roperto <daniel.roperto@catalyst-au.net>
 * @copyright   2016 Catalyst IT
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @SuppressWarnings(public) Allow as many methods as needed.
 */
class outagelib_test extends advanced_testcase {
    /**
     * Check if maintenance message is disabled as needed.
     */
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

    /**
     * Check if maintenance later is removed if no outage set.
     */
    public function test_maintenancelater_nonext() {
        $this->resetAfterTest(true);
        set_config('maintenance_later', time() + (60 * 60 * 24 * 7)); // In 1 week.
        self::assertNotEmpty(get_config('moodle', 'maintenance_later'));
        outagelib::outages_modified();
        self::assertEmpty(get_config('moodle', 'maintenance_later'));
    }

    /**
     * Check outagelib::inject() works as expected.
     */
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

        // Should not inject more than once with the inject() function.
        $size = strlen($CFG->additionalhtmltopofbody);
        outagelib::inject();
        self::assertSame($size, strlen($CFG->additionalhtmltopofbody));
    }

    /**
     * Check outagelib::inject() will not break the page if something goes wrong.
     */
    public function test_inject_broken() {
        $_GET = ['auth_outage_break_code' => '1'];
        outagelib::reinject();
        self::assertCount(2, phpunit_util::get_debugging_messages());
        phpunit_util::reset_debugging();
    }

    /**
     * Check if injection works with preview.
     */
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

    /**
     * Check if injection works with invalid preview without stopping the page.
     */
    public function test_inject_preview_notfound() {
        global $CFG;
        self::assertEmpty($CFG->additionalhtmltopofbody);
        $_GET = ['auth_outage_preview' => '1'];
        // Should not throw exception or halt anything, silently ignore it.
        outagelib::reinject();
        self::assertEmpty($CFG->additionalhtmltopofbody);
    }

    /**
     * Test injection with preview and delta.
     */
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

    /**
     * Test injection without active outage.
     */
    public function test_inject_noactive() {
        outagelib::reinject();
    }

    /**
     * Check if get config works without getting defaults.
     */
    public function test_get_config() {
        $this->resetAfterTest(true);
        $keys = [
            'css',
            'default_autostart',
            'default_description',
            'default_duration',
            'default_title',
            'default_warning_duration',
        ];
        // Set config with values.
        foreach ($keys as $k) {
            set_config($k, $k.'_value', 'auth_outage');
        }
        // Ensure it is not using any defaults.
        $config = outagelib::get_config();
        foreach ($keys as $k) {
            self::assertSame($config->$k, $k.'_value', 'auth_outage');
        }
    }

    /**
     * Check if get config works getting defaults when needed.
     */
    public function test_get_config_invalid() {
        $this->resetAfterTest(true);
        // Set config with invalid values.
        set_config('css', " \n", 'auth_outage');
        set_config('default_autostart', " \n", 'auth_outage');
        set_config('default_description', " \n", 'auth_outage');
        set_config('default_duration', " \n", 'auth_outage');
        set_config('default_title', " \n", 'auth_outage');
        set_config('default_warning_duration', " \n", 'auth_outage');
        // Get defaults.
        $defaults = outagelib::get_config_defaults();
        $config = outagelib::get_config();
        // Ensure it is using all defailts.
        foreach ($defaults as $k => $v) {
            self::assertSame($v, $config->$k);
        }
    }

    /**
     * Check if outagelib::inject() does not inject on admin/settings.php
     *
     * See Issue #65.
     */
    public function test_inject_settings() {
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

        // Pretend we are there...
        $_SERVER['SCRIPT_FILENAME'] = $CFG->dirroot.'/admin/settings.php';
        outagelib::reinject();

        self::assertEmpty($CFG->additionalhtmltopofbody);
    }
}


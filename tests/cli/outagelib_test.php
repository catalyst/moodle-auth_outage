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

use auth_outage\models\outage;
use auth_outage\outagelib;

defined('MOODLE_INTERNAL') || die();

/**
 * Tests performed on outage class.
 *
 * @package    auth_outage
 * @author     Daniel Thee Roperto <daniel.roperto@catalyst-au.net>
 * @copyright  Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \auth_outage\outagelib
 */
class outagelib_test extends advanced_testcase {
    /**
     * Gets a temp file to use in the test. Deleted every time a test starts.
     * @return string A temporary file name.
     */
    public function get_file() {
        return sys_get_temp_dir() . '/phpunit_authoutage.tmp';
    }

    public function setUp() {
        if (file_exists($this->get_file())) {
            if (is_file($this->get_file())) {
                unlink($this->get_file());
            } else {
                self::fail('Invalid temp file: ' . $this->get_file());
            }
        }
    }

    public function test_staticpage() {
        $now = time();
        $outage = new outage([
            'id' => 1,
            'warntime' => $now - 100,
            'starttime' => $now + 100,
            'stoptime' => $now + 200,
            'title' => 'Title',
            'description' => 'Description',
        ]);
        outagelib::savestaticinfopage($outage, $this->get_file());
        self::assertFileExists($this->get_file());

        $id = outagelib::get_outageidfrominfopage(file_get_contents($this->get_file()));
        self::assertSame($outage->id, $id);

        unlink($this->get_file());
    }

    public function test_getdefaulttemplatefile() {
        $file = outagelib::get_defaulttemplatefile();
        self::assertTrue(is_string($file));
        self::assertContains('template', $file);
    }
}

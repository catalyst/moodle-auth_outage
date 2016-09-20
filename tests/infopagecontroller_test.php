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

use auth_outage\infopage_controller;
use auth_outage\models\outage;
use auth_outage\outagelib;

defined('MOODLE_INTERNAL') || die();

/**
 * Tests performed on infopage_controller class.
 *
 * @package    auth_outage
 * @author     Daniel Thee Roperto <daniel.roperto@catalyst-au.net>
 * @copyright  Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \auth_outage\infopage_controller
 */
class infopagecontroller_test extends advanced_testcase {
    public function setUp() {
        if (file_exists($this->get_file())) {
            if (is_file($this->get_file())) {
                unlink($this->get_file());
            } else {
                self::fail('Invalid temp file: ' . $this->get_file());
            }
        }
    }

    /**
     * Return a temporary file name to use for this test.
     * @return string Default file.
     */
    public function get_file() {
        return sys_get_temp_dir() . '/phpunit_authoutage.tmp';
    }

    public function test_staticpage_output() {
        global $PAGE;
        $this->resetAfterTest(true);

        $PAGE->set_context(context_system::instance());
        $renderer = outagelib::get_renderer();
        $now = time();
        $outage = new outage([
            'id' => 1,
            'starttime' => $now + (60 * 60),
            'warntime' => $now - (60 * 60),
            'stoptime' => $now + (2 * 60 * 60),
            'title' => 'Outage Title at {{start}}',
            'description' => 'This is an <b>important</b> outage, starting at {{start}}.',
        ]);
        $info = new infopage_controller(['static' => true, 'outage' => $outage]);
        $html = $info->get_output();
        self::assertContains('<!DOCTYPE html>', $html);
        self::assertContains('</html>', $html);
        self::assertContains($outage->get_title(), $html);
        self::assertContains($outage->get_description(), $html);
        self::assertSame($outage->id, infopage_controller::find_outageid_from_infopage($html));
    }

    public function test_staticpage_file() {
        $now = time();
        $outage = new outage([
            'id' => 1,
            'warntime' => $now - 100,
            'starttime' => $now + 100,
            'stoptime' => $now + 200,
            'title' => 'Title',
            'description' => 'Description',
        ]);
        infopage_controller::save_static_page($outage, $this->get_file());
        self::assertFileExists($this->get_file());

        $id = infopage_controller::find_outageid_from_infopage(file_get_contents($this->get_file()));
        self::assertSame($outage->id, $id);

        unlink($this->get_file());
    }

    public function test_getdefaulttemplatefile() {
        $file = infopage_controller::get_defaulttemplatefile();
        self::assertTrue(is_string($file));
        self::assertContains('template', $file);
    }
}

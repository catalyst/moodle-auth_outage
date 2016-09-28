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
use auth_outage\local\controllers\infopage;
use auth_outage\local\outage;
use auth_outage\task\update_static_page;

defined('MOODLE_INTERNAL') || die();

/**
 * Tests performed on infopage controller class and update_static_page task class.
 *
 * @package    auth_outage
 * @author     Daniel Thee Roperto <daniel.roperto@catalyst-au.net>
 * @copyright  2016 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @SuppressWarnings("public") Allow this test to have as many tests as necessary.
 */
class infopagecontroller_test extends advanced_testcase {
    public function setUp() {
        $file = infopage::get_defaulttemplatefile();
        if (file_exists($file)) {
            if (is_file($file)) {
                unlink($file);
            } else {
                self::fail('Invalid temp file: '.$file);
            }
        }
    }

    public function test_constructor() {
        new infopage();
    }

    public function test_constructor_withparams() {
        $_GET = ['id' => 1, 'static' => 'true'];
        new infopage();
    }

    /**
     * @expectedException coding_exception
     */
    public function test_constructor_idmismatch() {
        $outage = new outage([
            'id' => 1,
            'autostart' => false,
            'warntime' => time() - 60,
            'starttime' => time(),
            'stoptime' => time() + 60,
            'title' => 'Title',
            'description' => 'Description',
        ]);
        new infopage(['id' => 2, 'outage' => $outage]);
    }

    /**
     * @expectedException coding_exception
     */
    public function test_constructor_invalidoutage() {
        new infopage(['outage' => 'My outage']);
    }

    public function test_staticpage_output() {
        global $PAGE;
        $this->resetAfterTest(true);

        $PAGE->set_context(context_system::instance());
        $now = time();
        $outage = new outage([
            'id' => 1,
            'starttime' => $now + (60 * 60),
            'warntime' => $now - (60 * 60),
            'stoptime' => $now + (2 * 60 * 60),
            'title' => 'Outage Title at {{start}}',
            'description' => 'This is an <b>important</b> outage, starting at {{start}}.',
        ]);
        $info = new infopage(['static' => true, 'outage' => $outage]);
        $html = $info->get_output();
        // Must find...
        self::assertContains('<!DOCTYPE html>', $html);
        self::assertContains('<meta http-equiv="refresh" content="'.(60 * 60).'">', $html); // Issue #53.
        self::assertContains('</html>', $html);
        self::assertContains($outage->get_title(), $html);
        self::assertContains($outage->get_description(), $html);
        // Must not find...
        self::assertNotContains('<link ', $html);
        self::assertNotContains('<a ', $html);
        self::assertNotContains('<script ', $html);
        // Ensure it has the id encoded in it...
        self::assertSame($outage->id, infopage::find_outageid_from_infopage($html));
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
        $file = infopage::get_defaulttemplatefile();
        infopage::save_static_page($outage, $file);
        self::assertFileExists($file);

        $id = infopage::find_outageid_from_infopage(file_get_contents($file));
        self::assertSame($outage->id, $id);

        unlink($file);
    }

    public function test_getdefaulttemplatefile() {
        $file = infopage::get_defaulttemplatefile();
        self::assertTrue(is_string($file));
        self::assertContains('template', $file);
    }

    /**
     * @expectedException coding_exception
     */
    public function test_findoutageid_notstring() {
        infopage::find_outageid_from_infopage(new stdClass());
    }

    public function test_findoutageid_notfound() {
        self::assertNull(
            infopage::find_outageid_from_infopage(
                '<html><head><title>Hello world!</title></head><body>Done.</body></html>'
            )
        );
    }

    /**
     * @expectedException coding_exception
     */
    public function test_savestaticpage_filenotstring() {
        infopage::save_static_page(new outage(), 1);
    }

    /**
     * @expectedException file_exception
     */
    public function test_savestaticpage_fileinvalid() {
        global $CFG;
        $outage = new outage([
            'id' => 1,
            'warntime' => time() - 100,
            'starttime' => time() + 100,
            'stoptime' => time() + 200,
            'title' => 'Title',
            'description' => 'Description',
        ]);
        infopage::save_static_page($outage, $CFG->dataroot.'/someinvalidpath/someinvalidfile');
    }

    /**
     * @expectedException invalid_state_exception
     */
    public function test_sanity_notstring() {
        infopage::save_static_page_sanitycheck(404);
    }

    /**
     * @expectedException invalid_state_exception
     */
    public function test_sanity_empty() {
        infopage::save_static_page_sanitycheck('    ');
    }

    /**
     * @expectedException invalid_state_exception
     */
    public function test_sanity_clearhtml() {
        infopage::save_static_page_sanitycheck('<html><head></head><body><b>  <!-- Nothing -->  </b></body></html>');
    }

    public function test_updatestaticpage() {
        $this->resetAfterTest(true);
        self::setAdminUser();
        $file = infopage::get_defaulttemplatefile();
        $now = time();
        $outage = new outage([
            'autostart' => false,
            'warntime' => $now - 100,
            'starttime' => $now + 100,
            'stoptime' => $now + 200,
            'title' => 'Title',
            'description' => 'Description',
        ]);
        $id = outagedb::save($outage);
        // The method update_static_page should have been called by save().
        self::assertFileExists($file);
        $idfound = infopage::find_outageid_from_infopage(file_get_contents($file));
        self::assertSame($id, $idfound);
        unlink($file);
    }

    public function test_updatestaticpage_nooutage() {
        infopage::update_static_page();
    }

    public function test_updatestaticpage_hasfile() {
        $file = infopage::get_defaulttemplatefile();
        touch($file);
        self::assertFileExists($file);
        infopage::update_static_page();
        self::assertFileNotExists($file);
    }

    /**
     * @expectedException coding_exception
     */
    public function test_updatestaticpage_invalidfile() {
        infopage::update_static_page(123);
    }

    public function test_hasadminoptions() {
        $this->resetAfterTest(true);
        $static = new infopage(['static' => true]);
        $nonstatic = new infopage(['static' => false]);
        // Now I am guest.
        self::assertFalse(is_siteadmin());
        self::assertFalse($static->has_admin_options());
        self::assertFalse($nonstatic->has_admin_options());
        // Now I am admin.
        self::setAdminUser();
        self::assertTrue(is_siteadmin());
        self::assertFalse($static->has_admin_options());
        self::assertTrue($nonstatic->has_admin_options());
    }

    /**
     * @expectedException coding_exception
     */
    public function test_output_static_nooutage() {
        $info = new infopage(['static' => true]);
        $info->output();
    }

    /**
     * We should have an exception because CLI cannot redirect.
     * @expectedException moodle_exception
     */
    public function test_output_nonstatic_nooutage() {
        $info = new infopage(['static' => false]);
        $info->output();
    }

    public function test_output() {
        $now = time();
        $outage = new outage([
            'id' => 1,
            'autostart' => false,
            'warntime' => $now - 100,
            'starttime' => $now + 100,
            'stoptime' => $now + 200,
            'title' => 'Title',
            'description' => 'Description',
        ]);
        $info = new infopage(['outage' => $outage]);
        $output = $info->get_output();
        self::assertContains('auth_outage_info', $output);
    }

    public function test_tasks() {
        $task = new update_static_page();
        self::assertNotEmpty($task->get_name());
        $task->execute();
    }
}

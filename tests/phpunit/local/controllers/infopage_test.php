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
 * Tests performed on infopage controller class and update_static_page task class.
 *
 * @package    auth_outage
 * @author     Daniel Thee Roperto <daniel.roperto@catalyst-au.net>
 * @copyright  2016 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use auth_outage\dml\outagedb;
use auth_outage\local\controllers\infopage;
use auth_outage\local\outage;
use auth_outage\task\update_static_page;

defined('MOODLE_INTERNAL') || die();
require_once(__DIR__.'/../../base_testcase.php');

/**
 * Tests performed on infopage controller class and update_static_page task class.
 *
 * @package    auth_outage
 * @author     Daniel Thee Roperto <daniel.roperto@catalyst-au.net>
 * @copyright  2016 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class infopagecontroller_test extends auth_outage_base_testcase {
    /**
     * Ensures the template file does not exist when starting a test.
     */
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

    /**
     * Tests the constructor.
     */
    public function test_constructor() {
        new infopage();
    }

    /**
     * Tests the constructor with given parameters.
     */
    public function test_constructor_withparams() {
        $_GET = ['id' => 1, 'static' => 'true'];
        new infopage();
    }

    /**
     * Tests the constructor with different id and outage id.
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
        $this->set_expected_exception('coding_exception');
        new infopage(['id' => 2, 'outage' => $outage]);
    }

    /**
     * Tests the constructor with an invalid outage.
     */
    public function test_constructor_invalidoutage() {
        $this->set_expected_exception('coding_exception');
        new infopage(['outage' => 'My outage']);
    }

    /**
     * Tests the static page contents.
     */
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

    /**
     * Tests the static page file contents.
     */
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

    /**
     * Checks if the default template file is a valid string with the name templage.
     */
    public function test_getdefaulttemplatefile() {
        $file = infopage::get_defaulttemplatefile();
        self::assertTrue(is_string($file));
        self::assertContains('template', $file);
    }

    /**
     * Tests infopage::find_outageid_from_infopage() with an invalid parameter.
     */
    public function test_findoutageid_notstring() {
        $this->set_expected_exception('coding_exception');
        infopage::find_outageid_from_infopage(new stdClass());
    }

    /**
     * Tests infopage::find_outageid_from_infopage() when the id is not found.
     */
    public function test_findoutageid_notfound() {
        self::assertNull(
            infopage::find_outageid_from_infopage(
                '<html><head><title>Hello world!</title></head><body>Done.</body></html>'
            )
        );
    }

    /**
     * Tests infopage::save_static_page() with an invalid parameter.
     */
    public function test_savestaticpage_filenotstring() {
        $this->set_expected_exception('coding_exception');
        infopage::save_static_page(new outage(), 1);
    }

    /**
     * Tests infopage::save_static_page() with an invalid filename.
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

        $this->set_expected_exception('file_exception');
        infopage::save_static_page($outage, $CFG->dataroot.'/someinvalidpath/someinvalidfile');
    }

    /**
     * Tests infopage::test_sanity_notstring() with invalid contents: an int.
     */
    public function test_sanity_notstring() {
        $this->set_expected_exception('invalid_state_exception');
        infopage::save_static_page_sanitycheck(404);
    }

    /**
     * Tests infopage::test_sanity_notstring() with invalid contents: an empty string.
     */
    public function test_sanity_empty() {
        $this->set_expected_exception('invalid_state_exception');
        infopage::save_static_page_sanitycheck('    ');
    }

    /**
     * Tests infopage::test_sanity_notstring() with invalid contents: an empty HTML.
     */
    public function test_sanity_clearhtml() {
        $this->set_expected_exception('invalid_state_exception');
        infopage::save_static_page_sanitycheck('<html><head></head><body><b>  <!-- Nothing -->  </b></body></html>');
    }

    /**
     * Tests updating the static page.
     */
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

    /**
     * Tests updating the static page when there is no outage.
     */
    public function test_updatestaticpage_nooutage() {
        infopage::update_static_page();
    }

    /**
     * Tests updating the static page when there is no outage but the file existed before.
     */
    public function test_updatestaticpage_hasfile() {
        $file = infopage::get_defaulttemplatefile();
        touch($file);
        self::assertFileExists($file);
        infopage::update_static_page();
        self::assertFileNotExists($file);
    }

    /**
     * Tests updating the static page with an invalid filename.
     */
    public function test_updatestaticpage_invalidfile() {
        $this->set_expected_exception('coding_exception');
        infopage::update_static_page(123);
    }

    /**
     * Checks if infopage::has_admin_options() works as expected.
     */
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
     * Tries to output a static page without a defined outage.
     */
    public function test_output_static_nooutage() {
        $info = new infopage(['static' => true]);

        $this->set_expected_exception('coding_exception');
        $info->output();
    }

    /**
     * We should have an exception because CLI cannot redirect.
     */
    public function test_output_nonstatic_nooutage() {
        $info = new infopage(['static' => false]);
        $this->set_expected_exception('moodle_exception');
        $info->output();
    }

    /**
     * Checks the output of the info page.
     */
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

    /**
     * Checks if we can create and execute a task to update outage pages.
     */
    public function test_tasks() {
        $task = new update_static_page();
        self::assertNotEmpty($task->get_name());
        $task->execute();
    }
}

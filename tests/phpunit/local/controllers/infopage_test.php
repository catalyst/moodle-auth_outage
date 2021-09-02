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

use auth_outage\local\controllers\infopage;
use auth_outage\local\outage;

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
class auth_outage_infopagecontroller_test extends auth_outage_base_testcase {
    /**
     * Tests the constructor.
     */
    public function test_constructor() {
        $this->assertTrue(has_capability('auth/outage:viewinfo', context_system::instance()));

        new infopage();
    }

    /**
     * Tests the constructor with given parameters.
     */
    public function test_constructor_withparams() {
        $this->assertTrue(has_capability('auth/outage:viewinfo', context_system::instance()));

        $_GET = ['id' => 1, 'static' => 'true'];
        new infopage();
    }

    /**
     * Tests the constructor with different id and outage id.
     */
    public function test_constructor_idmismatch() {
        $this->assertTrue(has_capability('auth/outage:viewinfo', context_system::instance()));

        $outage = $this->get_dummy_outage();
        $this->set_expected_exception('coding_exception', 'Provided id and outage->id do not match. (2/1)');
        new infopage(['id' => 2, 'outage' => $outage]);
    }

    /**
     * Tests the constructor with an invalid outage.
     */
    public function test_constructor_invalidoutage() {
        $this->assertTrue(has_capability('auth/outage:viewinfo', context_system::instance()));

        $this->set_expected_exception('coding_exception', 'Provided outage is not a valid outage object. (My outage)');
        new infopage(['outage' => 'My outage']);
    }

    /**
     * Checks the output of the info page.
     */
    public function test_output() {
        $this->assertTrue(has_capability('auth/outage:viewinfo', context_system::instance()));

        $outage = $this->get_dummy_outage();

        $info = new infopage(['outage' => $outage]);
        $output = $info->get_output();
        self::assertStringContainsString('auth_outage_info', $output);
    }

    /**
     * Checks the output of the info page.
     */
    public function test_output_without_permission() {
        $this->revoke_info_page_permissions();
        $this->assertFalse(has_capability('auth/outage:viewinfo', context_system::instance()));

        $outage = $this->get_dummy_outage();
        $info = new infopage(['outage' => $outage]);

        $this->set_expected_exception('moodle_exception', 'Unsupported redirect detected, script execution terminated');
        $output = $info->get_output();
    }

    /**
     * Checks the output of the info page.
     */
    public function test_output_without_permission_but_static() {
        $this->revoke_info_page_permissions();
        $this->assertFalse(has_capability('auth/outage:viewinfo', context_system::instance()));

        $outage = $this->get_dummy_outage();
        $info = new infopage(['outage' => $outage, 'static' => true]);

        $output = $info->get_output();
        self::assertStringContainsString('auth_outage_info', $output);
    }

    /**
     * Checks the output of the info page.
     */
    public function test_output_with_forcelogin() {
        $this->assertTrue(has_capability('auth/outage:viewinfo', context_system::instance()));

        set_config('forcelogin', true);

        $outage = $this->get_dummy_outage();
        $info = new infopage(['outage' => $outage]);

        $this->set_expected_exception('moodle_exception', 'Unsupported redirect detected, script execution terminated');
        $info->get_output();
    }

    /**
     * Checks the output of the info page.
     */
    public function test_output_with_forcelogin_if_static() {
        $this->assertTrue(has_capability('auth/outage:viewinfo', context_system::instance()));

        set_config('forcelogin', true);

        $outage = $this->get_dummy_outage();

        $info = new infopage(['outage' => $outage, 'static' => true]);

        $output = $info->get_output();
        self::assertStringContainsString('auth_outage_info', $output);
    }

    /**
     * Tests the constructor enables SVG support.
     */
    public function test_svgicons_is_true() {
        global $CFG;

        $this->assertTrue(has_capability('auth/outage:viewinfo', context_system::instance()));

        $CFG->svgicons = false;
        new infopage();
        self::assertTrue($CFG->svgicons);
    }
}

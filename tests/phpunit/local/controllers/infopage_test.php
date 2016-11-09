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
 * @SuppressWarnings(public) Allow as many methods as needed.
 */
class infopagecontroller_test extends auth_outage_base_testcase {
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
}

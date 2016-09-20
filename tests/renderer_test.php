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
 * @covers     \auth_outage_renderer
 */
class renderer_test extends advanced_testcase {
    public function test_staticpage() {
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
        $html = $renderer->renderoutagepagestatic($outage);
        self::assertContains('<!DOCTYPE html>', $html);
        self::assertContains('</html>', $html);
        self::assertContains($outage->get_title(), $html);
        self::assertContains($outage->get_description(), $html);
        self::assertSame($outage->id, outagelib::get_outageidfrominfopage($html));
    }
}

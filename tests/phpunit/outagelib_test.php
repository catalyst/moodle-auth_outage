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

defined('MOODLE_INTERNAL') || die();

/**
 * Tests performed on outagelib class.
 *
 * @package    auth_outage
 * @author     Daniel Thee Roperto <daniel.roperto@catalyst-au.net>
 * @copyright  2016 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class outagelib_test extends advanced_testcase {
    public function test_maintenancemessage() {
        global $CFG;
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
    }
}

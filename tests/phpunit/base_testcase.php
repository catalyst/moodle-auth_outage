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
 * Base testcase for auth outage tests.
 *
 * We added this testcase to handle exceptions.
 * Moodle 29 uses PHPUnit 3.7.38 which does not implement the expectException().
 * Moodle 30 uses PHPUnit 4.8.21 which does not implement the expectException().
 * Moodle 31 uses PHPUnit 4.8.27 which does not implement the expectException().
 * Moodle 32 (as of now) uses PHPUnit 5.4.8 which deprecated setExpectedException().
 * In PHPUnit 6 the setExpectedException() will be removed.
 * We are not not using the annotation expectException as it is not accepted by Moodle Checker.
 *
 * @package    auth_outage
 * @author     Daniel Thee Roperto <daniel.roperto@catalyst-au.net>
 * @copyright  2016 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use auth_outage\dml\outagedb;
use auth_outage\local\outage;

/**
 * auth_outage_base_testcase class.
 *
 * @package    auth_outage
 * @author     Daniel Thee Roperto <daniel.roperto@catalyst-au.net>
 * @copyright  2016 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class auth_outage_base_testcase extends advanced_testcase {
    /**
     * Checks PHPUnit version and calls the functions accordingly.
     * @param string $exception Expected exception class.
     * @param string|null $message Expected exception message.
     * @param int|null $code Expected exception code.
     */
    public function set_expected_exception($exception, $message = null, $code = null) {
        global $CFG;
        if ($CFG->branch < 32) {
            $this->setExpectedException($exception, $message, $code);
        } else {
            $this->expectException($exception);
            if (!is_null($message) && ($message !== '')) {
                $this->expectExceptionMessage($message);
            }
            if (!is_null($code)) {
                $this->expectExceptionCode($code);
            }
        }
    }

    /**
     * Revoke permission to see info page.
     */
    protected function revoke_info_page_permissions() {
        global $DB;

        $guestrole = $DB->get_record('role', array('shortname' => 'guest'));
        role_change_permission($guestrole->id, context_system::instance(), 'auth/outage:viewinfo', CAP_PREVENT);

        $this->setGuestUser();
    }

    /**
     * Get an outage object.
     *
     * @return \auth_outage\local\outage
     */
    protected function get_dummy_outage() {
        $now = time();

        return new outage([
            'id' => 1,
            'autostart' => false,
            'warntime' => $now - 100,
            'starttime' => $now + 100,
            'stoptime' => $now + 200,
            'title' => 'Title',
            'description' => 'Description',
        ]);
    }

    /**
     * Setup testcase.
     */
    public function setUp(): void {
        global $CFG;

        parent::setUp();
        $this->resetAfterTest(true);
    }

    /**
     * Tear down to restore the original DB reference.
     */
    public function tearDown(): void {
        global $DB;

        foreach (outagedb::get_all() as $i => $outage) {
            $DB->delete_records('auth_outage', ['id' => $outage->id]);
        }
    }
}

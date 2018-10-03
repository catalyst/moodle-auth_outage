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

defined('MOODLE_INTERNAL') || die();

/**
 * auth_outage_base_testcase class.
 *
 * @package    auth_outage
 * @author     Daniel Thee Roperto <daniel.roperto@catalyst-au.net>
 * @copyright  2016 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @SuppressWarnings(public) Allow as many methods as needed.
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

    public function setUp() {
        global $CFG;

        parent::setUp();
        $this->resetAfterTest(true);
    }
}

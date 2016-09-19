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

use auth_outage\cli\cliexception;
use auth_outage\cli\create;

defined('MOODLE_INTERNAL') || die();
require_once('cli_testcase.php');

/**
 * Tests performed on CLI base and exception class.
 *
 * @package    auth_outage
 * @author     Daniel Thee Roperto <daniel.roperto@catalyst-au.net>
 * @copyright  Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \auth_outage\cli\clibase
 * @covers     \auth_outage\cli\cliexception
 * @SuppressWarnings("public")
 */
class cli_test extends cli_testcase {
    public function test_invalidargumentparam() {
        $this->set_parameters(['--aninvalidparameter']);
        $this->setExpectedException(cliexception::class);
        new create();
    }

    public function test_invalidargumentgiven() {
        $this->setExpectedException(cliexception::class);
        new create(['anotherinvalidparameter']);
    }

    public function test_setreferencetime() {
        $cli = new create(['start' => 0]);
        $cli->set_referencetime(1);
        $cli->set_referencetime(60 * 60 * 24 * 7);
    }

    public function test_setreferencetime_invalid() {
        $cli = new create(['start' => 0]);
        $this->setExpectedException(InvalidArgumentException::class);
        $cli->set_referencetime(-1);
    }

    public function test_help() {
        $this->set_parameters(['-h']);
        $cli = new create();
        $output = $this->execute($cli);
        self::assertContains('-h', $output);
        self::assertContains('--help', $output);
    }

    public function test_exception() {
        self::setExpectedException(cliexception::class, '*ERROR* An CLI exception.', 5);
        throw new cliexception('An CLI exception.', 5);
    }
}

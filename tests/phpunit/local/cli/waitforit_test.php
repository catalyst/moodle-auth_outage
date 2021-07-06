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
 * waitforit_test test class.
 *
 * @package     auth_outage
 * @author      Daniel Thee Roperto <daniel.roperto@catalyst-au.net>
 * @copyright   2016 Catalyst IT
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use auth_outage\dml\outagedb;
use auth_outage\local\cli\cli_exception;
use auth_outage\local\cli\waitforit;
use auth_outage\local\outage;

defined('MOODLE_INTERNAL') || die();
require_once(__DIR__.'/cli_testcase.php');

/**
 * waitforit_test test class.
 *
 * @package     auth_outage
 * @author      Daniel Thee Roperto <daniel.roperto@catalyst-au.net>
 * @copyright   2016 Catalyst IT
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class auth_outage_waitforit_test extends auth_outage_cli_testcase {
    /**
     * Tests the constructor.
     */
    public function test_constructor() {
        $cli = new waitforit();
        self::assertNotNull($cli);
    }

    /**
     * Tests the generated options.
     */
    public function test_generateoptions() {
        $cli = new waitforit();
        $options = $cli->generate_options();
        foreach (array_keys($options) as $k) {
            self::assertTrue(is_string($k));
        }
    }

    /**
     * Tests the generated shortcut options.
     */
    public function test_generateshortcuts() {
        $cli = new waitforit();
        $options = $cli->generate_options();
        $shorts = $cli->generate_shortcuts();
        foreach ($shorts as $s) {
            self::assertArrayHasKey($s, $options);
        }
    }

    /**
     * Tests if help works.
     */
    public function test_help() {
        $this->set_parameters(['--help']);
        $cli = new waitforit();
        $text = $this->execute($cli);
        self::assertStringContainsString('Waits', $text);
        self::assertStringContainsString('--help', $text);
    }

    /**
     * Checks if providing an outageid and active parameter.
     */
    public function test_bothparams() {
        $this->set_parameters(['--outageid=1', '--active']);
        $cli = new waitforit();
        $this->set_expected_cli_exception(cli_exception::ERROR_PARAMETER_INVALID);
        $cli->execute();
    }

    /**
     * Tests with an invalid outage id
     */
    public function test_invalidoutageid() {
        $this->set_parameters(['-id=-1']);
        $cli = new waitforit();
        $this->set_expected_cli_exception(cli_exception::ERROR_PARAMETER_INVALID);
        $this->execute($cli);
    }

    /**
     * Tests with an active outage when it does not exists.
     */
    public function test_outagenotfound() {
        $this->set_parameters(['-a']);
        $cli = new waitforit();
        $this->set_expected_cli_exception(cli_exception::ERROR_OUTAGE_NOT_FOUND);
        $this->execute($cli);
    }

    /**
     * Tests with an outage that already ended.
     */
    public function test_endedoutage() {
        self::setAdminUser();
        $now = time();
        $id = outagedb::save(new outage([
            'autostart' => false,
            'warntime' => $now - 200,
            'starttime' => $now - 100,
            'stoptime' => $now - 50,
            'title' => 'Title',
            'description' => 'Description',
        ]));
        $this->set_parameters(['-id='.$id]);
        $cli = new waitforit();
        $cli->set_referencetime($now);
        $this->set_expected_cli_exception(cli_exception::ERROR_OUTAGE_INVALID);
        $this->execute($cli);
    }

    /**
     * Tests waiting for an existing active outage, verbose mode.
     */
    public function test_activeverbose() {
        self::setAdminUser();
        $now = time();
        outagedb::save(new outage([
            'autostart' => false,
            'warntime' => $now - 10,
            'starttime' => $now + 1,
            'stoptime' => $now + 10,
            'title' => 'Title',
            'description' => 'Description',
        ]));
        $this->set_parameters(['-v', '--active']);
        $cli = new waitforit();
        $cli->set_referencetime($now);
        $output = $this->execute($cli);
        self::assertStringContainsString('Verbose mode', $output);
        self::assertStringContainsString('starting in 1 sec', $output);
        self::assertStringContainsString('started', $output);
    }

    /**
     * Tests the countdown.
     */
    public function test_countdown() {
        self::setAdminUser();
        $now = time();
        outagedb::save(new outage([
            'autostart' => false,
            'warntime' => $now,
            'starttime' => $now + 45,
            'stoptime' => $now + (60 * 60),
            'title' => 'Title',
            'description' => 'Description',
        ]));
        $this->set_parameters(['-v', '--active', '--sleep=30']);
        $cli = new waitforit();
        $cli->set_referencetime($now);
        $cli->set_sleepcallback(function ($sleep) use (&$now) {
            $now += $sleep;
            return $now;
        });
        $output = $this->execute($cli);
        self::assertStringContainsString("starting in 45", $output);
        self::assertStringContainsString("sleep 30 second", $output);
        self::assertStringContainsString("starting in 15", $output);
        self::assertStringContainsString("sleep 15 second", $output);
        self::assertStringContainsString("started!", $output);
    }

    /**
     * Tests if the outage changed while waiting.
     */
    public function test_outagechanged() {
        self::setAdminUser();
        $now = time();
        $id = outagedb::save(new outage([
            'autostart' => false,
            'warntime' => $now,
            'starttime' => $now + (2 * 60 * 60),
            'stoptime' => $now + (60 * 60),
            'title' => 'Title',
            'description' => 'Description',
        ]));
        $this->set_parameters(['-v', '--active', '--sleep=30']);
        $cli = new waitforit();
        $cli->set_referencetime($now);
        $cli->set_sleepcallback(function () use ($id) {
            // Change outage when not expected to.
            $outage = outagedb::get_by_id($id);
            $outage->title = 'New title!';
            outagedb::save($outage);
            // Pretend it is time to start, but it should get an error instead.
            return $outage->starttime;
        });
        $this->set_expected_cli_exception(cli_exception::ERROR_OUTAGE_CHANGED);
        $this->execute($cli);
    }
}

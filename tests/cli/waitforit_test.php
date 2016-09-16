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
use auth_outage\cli\waitforit;
use auth_outage\models\outage;
use auth_outage\outagedb;

defined('MOODLE_INTERNAL') || die();
require_once('cli_testcase.php');

/**
 * Tests performed on CLI waitforit class.
 *
 * @package    auth_outage
 * @author     Daniel Thee Roperto <daniel.roperto@catalyst-au.net>
 * @copyright  Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @SuppressWarnings("public")
 */
class waitforit_test extends cli_testcase {
    public function test_constructor() {
        $cli = new waitforit();
        self::assertNotNull($cli);
    }

    public function test_generateoptions() {
        $cli = new waitforit();
        $options = $cli->generateoptions();
        foreach (array_keys($options) as $k) {
            self::assertTrue(is_string($k));
        }
    }

    public function test_generateshortcuts() {
        $cli = new waitforit();
        $options = $cli->generateoptions();
        $shorts = $cli->generateshortcuts();
        foreach ($shorts as $s) {
            self::assertArrayHasKey($s, $options);
        }
    }

    public function test_help() {
        $this->set_parameters(['--help']);
        $cli = new waitforit();
        $text = $this->execute($cli);
        self::assertContains('Waits', $text);
        self::assertContains('--help', $text);
    }

    public function test_bothparams() {
        $this->set_parameters(['--outageid=1', '--active']);
        $cli = new waitforit();
        $this->setExpectedException(cliexception::class);
        $cli->execute();
    }

    public function test_invalidoutageid() {
        $this->set_parameters(['-id=-1']);
        $cli = new waitforit();
        $this->setExpectedException(cliexception::class);
        $this->execute($cli);
    }

    public function test_outagenotfound() {
        $this->set_parameters(['-a']);
        $cli = new waitforit();
        $this->setExpectedException(cliexception::class);
        $this->execute($cli);
    }

    public function test_endedoutage() {
        $this->setAdminUser();
        $now = time();
        $id = outagedb::save(new outage([
            'warntime' => $now - 200,
            'starttime' => $now - 100,
            'stoptime' => $now - 50,
            'title' => 'Title',
            'description' => 'Description',
        ]));
        $this->set_parameters(['-id=' . $id]);
        $cli = new waitforit();
        $cli->set_referencetime($now);
        $this->setExpectedException(cliexception::class);
        $this->execute($cli);
    }

    public function test_activeverbose() {
        $this->setAdminUser();
        $now = time();
        outagedb::save(new outage([
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
        self::assertContains('Verbose mode', $output);
        self::assertContains('starting in 1 sec', $output);
        self::assertContains('started', $output);
    }

    public function test_countdown() {
        $this->setAdminUser();
        $now = time();
        outagedb::save(new outage([
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
        self::assertContains("starting in 45", $output);
        self::assertContains("sleep 30 second", $output);
        self::assertContains("starting in 15", $output);
        self::assertContains("sleep 15 second", $output);
        self::assertContains("started!", $output);
    }

    public function test_outagechanged() {
        $this->setAdminUser();
        $now = time();
        $id = outagedb::save(new outage([
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
        $this->setExpectedException(cliexception::class);
        $this->execute($cli);
    }
}

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
use auth_outage\models\outage;
use auth_outage\outagedb;

defined('MOODLE_INTERNAL') || die();
require_once('cli_testcase.php');

/**
 * Tests performed on CLI create class.
 *
 * @package    auth_outage
 * @author     Daniel Thee Roperto <daniel.roperto@catalyst-au.net>
 * @copyright  Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @SuppressWarnings("public")
 */
class create_test extends cli_testcase {
    public function test_noarguments() {
        $cli = new create();
        $this->setExpectedException(cliexception::class);
        $this->execute($cli);
    }

    public function test_invalidargumentparam() {
        $this->set_parameters(['--aninvalidparameter']);
        $this->setExpectedException(cliexception::class);
        new create();
    }

    public function test_invalidargumentgiven() {
        $this->setExpectedException(cliexception::class);
        new create(['anotherinvalidparameter']);
    }

    public function test_invalidparam_notanumber() {
        $cli = new create(['start' => 'some day']);
        $cli->set_defaults([
            'warn' => 50,
            'start' => 200,
            'duration' => 300,
            'title' => 'Default Title',
            'description' => 'Default Description',
        ]);
        $this->setExpectedException(cliexception::class);
        $this->execute($cli);
    }

    public function test_invalidparam_negative() {
        $cli = new create(['start' => -1]);
        $cli->set_defaults([
            'warn' => 50,
            'start' => 200,
            'duration' => 300,
            'title' => 'Default Title',
            'description' => 'Default Description',
        ]);
        $this->setExpectedException(cliexception::class);
        $this->execute($cli);
    }

    public function test_invalidparam_emptystring() {
        $cli = new create(['start' => 0, 'title' => '']);
        $cli->set_defaults([
            'warn' => 50,
            'start' => 200,
            'duration' => 300,
            'title' => 'Default Title',
            'description' => 'Default Description',
        ]);
        $this->setExpectedException(cliexception::class);
        $this->execute($cli);
    }

    public function test_invalidparam_notastring() {
        $cli = new create(['start' => 0, 'title' => true]);
        $cli->set_defaults([
            'warn' => 50,
            'start' => 200,
            'duration' => 300,
            'title' => 'Default Title',
            'description' => 'Default Description',
        ]);
        $this->setExpectedException(cliexception::class);
        $this->execute($cli);
    }

    public function test_setreferencetime_invalid() {
        $cli = new create(['start' => 0]);
        $this->setExpectedException(InvalidArgumentException::class);
        $cli->set_referencetime(-1);
    }

    public function test_help() {
        $this->set_parameters(['--help']);
        $cli = new create();
        $output = $this->execute($cli);
        self::assertContains('Creates', $output);
        self::assertContains('--help', $output);
    }

    public function test_options() {
        $cli = new create();

        $options = $cli->generateoptions();
        foreach (array_keys($options) as $k) {
            self::assertTrue(is_string($k));
        }

        $shorts = $cli->generateshortcuts();
        foreach ($shorts as $s) {
            self::assertArrayHasKey($s, $options);
        }
    }

    public function test_create_withoptions() {
        $this->set_parameters([
            '--warn=10',
            '--start=0',
            '--duration=30',
            '--title=A Title',
            '--description=A Description',
        ]);
        $now = time();
        $cli = new create();
        $cli->set_referencetime($now);
        $text = $this->execute($cli);
        self::assertContains('created', $text);
        // Check creted outage.
        list(, $id) = explode(':', $text);
        $id = (int)$id;
        $outage = outagedb::get_by_id($id);
        self::assertSame($now, $outage->starttime);
        self::assertSame(10, $outage->get_warning_duration());
        self::assertSame(30, $outage->get_duration());
        self::assertNull($outage->finished);
        self::assertSame('A Title', $outage->title);
        self::assertSame('A Description', $outage->description);
    }

    public function test_create_onlyid() {
        $this->set_parameters([
            '--onlyid',
            '--warn=10',
            '--start=0',
            '--duration=30',
            '--title=Title',
            '--description=Description',
        ]);
        $now = time();
        $cli = new create();
        $cli->set_referencetime($now);
        $id = $this->execute($cli);
        // Check if the id contains is only a number (parameter onlyid).
        $id = trim($id);
        self::assertTrue(is_number($id));
        $id = (int)$id;
        // Check creted outage.
        $outage = outagedb::get_by_id($id);
        self::assertSame($now, $outage->starttime);
        self::assertSame($outage->starttime - 10, $outage->warntime);
        self::assertSame($outage->starttime + 30, $outage->stoptime);
        self::assertNull($outage->finished);
        self::assertSame('Title', $outage->title);
        self::assertSame('Description', $outage->description);
    }

    public function test_create_withdefaults() {
        $this->set_parameters([
            '--warn=100',
            '--start=50',
        ]);
        $now = time();
        $cli = new create();
        $cli->set_referencetime($now);
        $cli->set_defaults([
            'warn' => 50,
            'start' => 200,
            'duration' => 300,
            'title' => 'Default Title',
            'description' => 'Default Description',
        ]);
        $text = $this->execute($cli);
        self::assertContains('created', $text);
        // Check creted outage.
        list(, $id) = explode(':', $text);
        $id = (int)$id;
        $outage = outagedb::get_by_id($id);
        self::assertSame($now + 50, $outage->starttime, 'Wrong starttime.');
        self::assertSame($outage->starttime - 100, $outage->warntime, 'Wrong warntime.');
        self::assertSame($outage->starttime + 300, $outage->stoptime, 'Wrong stoptime.');
        self::assertNull($outage->finished);
        self::assertSame('Default Title', $outage->title);
        self::assertSame('Default Description', $outage->description);
    }

    public function test_create_withclone() {
        $this->setAdminUser();
        $now = time();
        // Create the outage to clone.
        $original = new outage([
            'warntime' => $now - 120,
            'starttime' => $now,
            'stoptime' => $now + 120,
            'title' => 'Title',
            'description' => 'Description',
        ]);
        $id = outagedb::save($original);
        // Clone it using CLI.
        $this->set_parameters([
            '--onlyid',
            '--start=60',
            '--clone=' . $id,
        ]);
        $cli = new create();
        $cli->set_referencetime($now);
        $id = trim($this->execute($cli));
        // Check cloned data.
        $cloned = outagedb::get_by_id((int)$id);
        self::assertSame($now + 60, $cloned->starttime);
        self::assertSame($original->get_warning_duration(), $cloned->get_warning_duration());
        self::assertSame($original->get_duration(), $cloned->get_duration());
        self::assertSame($original->title, $cloned->title);
        self::assertSame($original->description, $cloned->description);
    }

    public function test_create_withclone_invalid() {
        $this->setExpectedException(cliexception::class);
        $this->set_parameters([
            '--start=60',
            '--clone=-1',
        ]);
        $cli = new create();
        $this->execute($cli);
    }

    public function test_create_withblock() {
        // Not an extensive test in the blocking API, cliwaitforit tests should cover them deeper.
        $this->set_parameters([
            '--block',
            '--warn=60',
            '--start=0',
            '--duration=600',
            '--title=Title',
            '--description=Description',
        ]);
        $now = time();
        $cli = new create();
        $cli->set_referencetime($now);
        $text = $this->execute($cli);
        self::assertContains('created', $text);
        self::assertContains('started', $text);
    }
}

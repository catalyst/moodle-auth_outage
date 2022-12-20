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
 * create_test test class.
 *
 * @package     auth_outage
 * @author      Daniel Thee Roperto <daniel.roperto@catalyst-au.net>
 * @copyright   2016 Catalyst IT
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use auth_outage\dml\outagedb;
use auth_outage\local\cli\cli_exception;
use auth_outage\local\cli\create;
use auth_outage\local\outage;

defined('MOODLE_INTERNAL') || die();
require_once(__DIR__.'/cli_testcase.php');

/**
 * create_test test class.
 *
 * @package     auth_outage
 * @author      Daniel Thee Roperto <daniel.roperto@catalyst-au.net>
 * @copyright   2016 Catalyst IT
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class auth_outage_create_test extends auth_outage_cli_testcase {
    /**
     * Tests without any arguments.
     */
    public function test_noarguments() {
        $cli = new create();
        $this->set_expected_cli_exception(cli_exception::ERROR_PARAMETER_MISSING);
        $this->execute($cli);
    }

    /**
     * Tests when the start time is not a valid number.
     */
    public function test_invalidparam_notanumber() {
        $cli = new create(['start' => 'some day']);
        $cli->set_defaults([
            'warn' => 50,
            'start' => 200,
            'duration' => 300,
            'title' => 'Default Title',
            'description' => 'Default Description',
        ]);
        $this->set_expected_cli_exception(cli_exception::ERROR_PARAMETER_INVALID);
        $this->execute($cli);
    }

    /**
     * Tests when providing a negative start time.
     */
    public function test_invalidparam_negative() {
        $cli = new create(['start' => -1]);
        $cli->set_defaults([
            'warn' => 50,
            'start' => 200,
            'duration' => 300,
            'title' => 'Default Title',
            'description' => 'Default Description',
        ]);
        $this->set_expected_cli_exception(cli_exception::ERROR_PARAMETER_INVALID);
        $this->execute($cli);
    }

    /**
     * Tests providing an empty title.
     */
    public function test_invalidparam_emptystring() {
        $cli = new create(['start' => 0, 'title' => '']);
        $cli->set_defaults([
            'warn' => 50,
            'start' => 200,
            'duration' => 300,
            'title' => 'Default Title',
            'description' => 'Default Description',
        ]);
        $this->set_expected_cli_exception(cli_exception::ERROR_PARAMETER_INVALID);
        $this->execute($cli);
    }

    /**
     * Tests if not providing the title (it will be send as true).
     */
    public function test_invalidparam_notastring() {
        $cli = new create(['start' => 0, 'title' => true]);
        $cli->set_defaults([
            'warn' => 50,
            'start' => 200,
            'duration' => 300,
            'title' => 'Default Title',
            'description' => 'Default Description',
        ]);
        $this->set_expected_cli_exception(cli_exception::ERROR_PARAMETER_INVALID);
        $this->execute($cli);
    }

    /**
     * Tests the help.
     */
    public function test_help() {
        $this->set_parameters(['--help']);
        $cli = new create();
        $output = $this->execute($cli);
        self::assertStringContainsString('Creates', $output);
        self::assertStringContainsString('--help', $output);
    }

    /**
     * Tests the options and shortcuts.
     */
    public function test_options() {
        $cli = new create();

        $options = $cli->generate_options();
        foreach (array_keys($options) as $k) {
            self::assertTrue(is_string($k));
        }

        $shorts = $cli->generate_shortcuts();
        foreach ($shorts as $s) {
            self::assertArrayHasKey($s, $options);
        }
    }

    /**
     * Tests creating with all given options.
     */
    public function test_create_withoptions() {
        $this->set_parameters([
            '--autostart=true',
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
        self::assertStringContainsString('created', $text);
        // Check creted outage.
        $clioutput = explode(':', $text);
        $id = (int)end($clioutput);
        $outage = outagedb::get_by_id($id);
        self::assertSame($now, $outage->starttime);
        self::assertSame(10, $outage->get_warning_duration());
        self::assertSame(30, $outage->get_duration_planned());
        self::assertNull($outage->finished);
        self::assertSame('A Title', $outage->title);
        self::assertSame('A Description', $outage->description);
    }

    /**
     * Tests creating with the onlyid parameter.
     */
    public function test_create_onlyid() {
        $this->set_parameters([
            '--onlyid',
            '--autostart=N',
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

    /**
     * Tests creating using some default values.
     */
    public function test_create_withdefaults() {
        $this->set_parameters([
            '--warn=100',
            '--start=50',
        ]);
        $now = time();
        $cli = new create();
        $cli->set_referencetime($now);
        $cli->set_defaults([
            'autostart' => false,
            'warn' => 50,
            'start' => 200,
            'duration' => 300,
            'title' => 'Default Title',
            'description' => 'Default Description',
        ]);
        $text = $this->execute($cli);
        self::assertStringContainsString('created', $text);
        // Check creted outage.
        $clioutput = explode(':', $text);
        $id = (int)end($clioutput);
        $outage = outagedb::get_by_id($id);
        self::assertSame($now + 50, $outage->starttime, 'Wrong starttime.');
        self::assertSame($outage->starttime - 100, $outage->warntime, 'Wrong warntime.');
        self::assertSame($outage->starttime + 300, $outage->stoptime, 'Wrong stoptime.');
        self::assertNull($outage->finished);
        self::assertSame('Default Title', $outage->title);
        self::assertSame('Default Description', $outage->description);
    }

    /**
     * Tests creating with clone.
     */
    public function test_create_withclone() {
        self::setAdminUser();
        $now = time();
        // Create the outage to clone.
        $original = new outage([
            'autostart' => false,
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
            '--clone='.$id,
        ]);
        $cli = new create();
        $cli->set_referencetime($now);
        $id = trim($this->execute($cli));
        // Check cloned data.
        $cloned = outagedb::get_by_id((int)$id);
        self::assertSame($now + 60, $cloned->starttime);
        self::assertSame($original->get_warning_duration(), $cloned->get_warning_duration());
        self::assertSame($original->get_duration_planned(), $cloned->get_duration_planned());
        self::assertSame($original->title, $cloned->title);
        self::assertSame($original->description, $cloned->description);
    }

    /**
     * Tests creating with an invalid clone id.
     */
    public function test_create_withclone_invalid() {
        $this->set_parameters([
            '--start=60',
            '--clone=-1',
        ]);
        $cli = new create();
        $this->set_expected_cli_exception(cli_exception::ERROR_PARAMETER_INVALID);
        $this->execute($cli);
    }

    /**
     * Tests creating with the block flag.
     */
    public function test_create_withblock() {
        // Not an extensive test in the blocking API, cliwaitforit tests should cover them deeper.
        $this->set_parameters([
            '--autostart=N',
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
        self::assertStringContainsString('created', $text);
        self::assertStringContainsString('started', $text);
    }

    /**
     * Tests providing an invalid option as default.
     */
    public function test_setdefaults_extra() {
        $cli = new create([]);
        $this->set_expected_exception('coding_exception');
        $cli->set_defaults(['aninvalidparameter' => 'value']);
    }

    /**
     * Tests with an invalud autostart bool value.
     */
    public function test_invalid_bool() {
        $this->set_parameters([
            '--autostart=maybe',
            '--warn=60',
            '--start=0',
            '--duration=600',
            '--title=Title',
            '--description=Description',
        ]);
        $cli = new create();
        $this->set_expected_cli_exception(cli_exception::ERROR_PARAMETER_INVALID);
        $cli->execute();
    }
}

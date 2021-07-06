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
 * finish_test test class.
 *
 * @package     auth_outage
 * @author      Daniel Thee Roperto <daniel.roperto@catalyst-au.net>
 * @copyright   2016 Catalyst IT
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use auth_outage\dml\outagedb;
use auth_outage\local\cli\cli_exception;
use auth_outage\local\cli\finish;
use auth_outage\local\outage;

defined('MOODLE_INTERNAL') || die();
require_once(__DIR__.'/cli_testcase.php');

/**
 * finish_test test class.
 *
 * @package     auth_outage
 * @author      Daniel Thee Roperto <daniel.roperto@catalyst-au.net>
 * @copyright   2016 Catalyst IT
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class auth_outage_finish_test extends auth_outage_cli_testcase {
    /**
     * Tests the constructor.
     */
    public function test_constructor() {
        $cli = new finish();
        self::assertNotNull($cli);
    }

    /**
     * Tests the generated options and shortcuts.
     */
    public function test_options() {
        $cli = new finish();

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
     * Tests the help.
     */
    public function test_help() {
        $this->set_parameters(['--help']);
        $cli = new finish();
        $text = $this->execute($cli);
        self::assertStringContainsString('Finishes', $text);
        self::assertStringContainsString('--help', $text);
    }

    /**
     * Tests if running without arguments.
     */
    public function test_noarguments() {
        $cli = new finish();
        $this->set_expected_cli_exception(cli_exception::ERROR_PARAMETER_MISSING);
        $this->execute($cli);
    }

    /**
     * Tests finishing an already ended outage.
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
        $cli = new finish();
        $cli->set_referencetime($now);
        $this->set_expected_cli_exception(cli_exception::ERROR_OUTAGE_INVALID);
        $this->execute($cli);
    }

    /**
     * Tests finishing an outage.
     */
    public function test_finish() {
        self::setAdminUser();
        $now = time();
        $id = outagedb::save(new outage([
            'autostart' => false,
            'warntime' => $now - 200,
            'starttime' => $now - 100,
            'stoptime' => $now + 100,
            'title' => 'Title',
            'description' => 'Description',
        ]));
        $this->set_parameters(['-id='.$id]);
        $cli = new finish();
        $cli->set_referencetime($now);
        $this->execute($cli);
    }

    /**
     * Tests finishing an active outage when it does not exists.
     */
    public function test_activenotfound() {
        self::setAdminUser();
        $this->set_parameters(['-a']);
        $cli = new finish();
        $this->set_expected_cli_exception(cli_exception::ERROR_OUTAGE_NOT_FOUND);
        $this->execute($cli);
    }

    /**
     * Tests providing an invalid outage id.
     */
    public function test_invalidid() {
        self::setAdminUser();
        $this->set_parameters(['-id=theid']);
        $cli = new finish();
        $this->set_expected_cli_exception(cli_exception::ERROR_PARAMETER_INVALID);
        $this->execute($cli);
    }

    /**
     * Tests when the outage is not found.
     */
    public function test_idnotfound() {
        self::setAdminUser();
        $this->set_parameters(['-id=99999']);
        $cli = new finish();
        $this->set_expected_cli_exception(cli_exception::ERROR_OUTAGE_NOT_FOUND);
        $this->execute($cli);
    }
}

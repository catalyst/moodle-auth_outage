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
 * auth_outage_cli_testcase class.
 *
 * @package    auth_outage
 * @author     Daniel Thee Roperto <daniel.roperto@catalyst-au.net>
 * @copyright  2016 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use auth_outage\local\cli\clibase;

defined('MOODLE_INTERNAL') || die();
require_once(__DIR__.'/../../base_testcase.php');

/**
 * auth_outage_cli_testcase class.
 *
 * @package    auth_outage
 * @author     Daniel Thee Roperto <daniel.roperto@catalyst-au.net>
 * @copyright  2016 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class auth_outage_cli_testcase extends auth_outage_base_testcase {
    /**
     * Always enable the auth outage plugin, resets after test and set no parameters.
     */
    public function setUp(): void {
        global $CFG;

        // PHPUnit does not load config.php file.
        $CFG->auth_outage_bootstrap_loaded = true;

        // Enable auth plugins.
        set_config('auth', 'outage');
        \core\session\manager::gc(); // Remove stale sessions.
        core_plugin_manager::reset_caches();

        $this->set_parameters([]);
        parent::setUp();
    }

    /**
     * Mocks the command line parameters.
     *
     * @param string[] $options Options to use as parameters.
     */
    protected function set_parameters(array $options) {
        array_unshift($options, 'cli.php');
        $_SERVER['argv'] = $options;
        $_SERVER['argc'] = count($options);
    }

    /**
     * Executes the CLI.
     *
     * @param clibase $cli CLI to execute.
     *
     * @return string The output text.
     */
    protected function execute(clibase $cli) {
        ob_start();
        try {
            $cli->execute();
            $text = ob_get_contents();
            return $text;
        } finally {
            ob_end_clean();
        }
    }

    /**
     * Sets the expected exception as cli_exception with the given error code.
     *
     * @param int $errorcode Error code.
     */
    protected function set_expected_cli_exception($errorcode) {
        $this->set_expected_exception('\\auth_outage\\local\\cli\\cli_exception', null, $errorcode);
    }
}

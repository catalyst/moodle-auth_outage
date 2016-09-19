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

use auth_outage\cli\clibase;

defined('MOODLE_INTERNAL') || die();

/**
 * Tests performed on CLIs.
 *
 * @package    auth_outage
 * @author     Daniel Thee Roperto <daniel.roperto@catalyst-au.net>
 * @copyright  Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class cli_testcase extends advanced_testcase {
    public function setUp() {
        $this->resetAfterTest(true);
        $this->set_parameters([]);
        parent::setUp();
    }

    /**
     * Mocks the command line parameters.
     * @param array $options Options to use as parameters.
     */
    protected function set_parameters(array $options) {
        array_unshift($options, 'cli.php');
        $_SERVER['argv'] = $options;
        $_SERVER['argc'] = count($options);
    }

    /**
     * Executes the CLI.
     * @param clibase $cli CLI to execute.
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
}

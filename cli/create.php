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
 * CLI for creating outages.
 *
 * @package    auth_outage
 * @author     Daniel Thee Roperto <daniel.roperto@catalyst-au.net>
 * @copyright  Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use auth_outage\cli\cliexception;
use auth_outage\cli\create;

define('CLI_SCRIPT', true);
require('../../config.php');

$cli = new create();

$config = get_config('auth_outage');
$cli->set_defaults([
    'help' => false,
    'warn' => (int)($config->warning_duration),
    'start' => null,
    'duration' => (int)($config->default_duration),
    'title' => $config->warning_title,
    'description' => $config->warning_description,
]);

try {
    $cli->execute();
} catch (cliexception $e) {
    cli_error($e->getMessage());
}

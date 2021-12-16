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
 * cli_exception class.
 *
 * @package    auth_outage
 * @author     Daniel Thee Roperto <daniel.roperto@catalyst-au.net>
 * @copyright  2016 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace auth_outage\local\cli;

use Exception;

/**
 * cli_exception class.
 *
 * @package    auth_outage
 * @author     Daniel Thee Roperto <daniel.roperto@catalyst-au.net>
 * @copyright  2016 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class cli_exception extends Exception {
    /**
     * Undefined error.
     */
    const ERROR_UNDEFINED = 1;

    /**
     * Unknow parameter.
     */
    const ERROR_PARAMETER_UNKNOWN = 2;

    /**
     * Invalid parameter usage.
     */
    const ERROR_PARAMETER_INVALID = 3;

    /**
     * Missing required parameter.
     */
    const ERROR_PARAMETER_MISSING = 4;

    /**
     * The informed outage cannot be used for that purpose.
     */
    const ERROR_OUTAGE_INVALID = 5;

    /**
     * The informed outage was not found.
     */
    const ERROR_OUTAGE_NOT_FOUND = 6;

    /**
     * The outage has changed before the completion of the command.
     */
    const ERROR_OUTAGE_CHANGED = 7;

    /**
     * The outage plugin is not properly configured.
     */
    const ERROR_PLUGIN_CONFIGURATION = 8;

    /**
     * Moodle maintenance mode is enabled.
     */
    const ERROR_MAINTENANCE_MODE = 9;

    /**
     * cliexception constructor.
     * @param string $message An explanation of the exception.
     * @param int $code Exit code to be used.
     * @param Exception $previous Another exception as reference or null.
     */
    public function __construct($message, $code = 1, Exception $previous = null) {
        parent::__construct('*ERROR* '.$message, $code, $previous = null);
    }
}

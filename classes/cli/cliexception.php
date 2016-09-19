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

namespace auth_outage\cli;

use Exception;

/**
 * Exception executing CLI.
 *
 * @package    auth_outage
 * @author     Daniel Thee Roperto <daniel.roperto@catalyst-au.net>
 * @copyright  Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class cliexception extends Exception {
    /**
     * cliexception constructor.
     * @param string $message An explanation of the exception.
     * @param int $code Exit code to be used.
     * @param Exception|null $previous Another exception as reference.
     */
    public function __construct($message, $code = 1, Exception $previous = null) {
        parent::__construct('*ERROR* ' . $message, $code, $previous = null);
    }
}

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
 * Version information.
 *
 * @package     auth_outage
 * @author      Marcus Boon <marcus@catalyst-au.net>
 * @author      Brendan Heywood <brendan@catalyst-au.net>
 * @author      Daniel Thee Roperto <daniel.roperto@catalyst-au.net>
 * @copyright   2016 Catalyst IT
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$plugin->component = "auth_outage";
$plugin->version = 2019022200;                  // The current plugin version (Date: YYYYMMDDXX).
$plugin->release = '1.0.10';                     // Human-readable release information.
$plugin->requires = 2017051500; // Requires 3.3 and higher.
$plugin->maturity = MATURITY_STABLE;            // Suitable for PRODUCTION environments!

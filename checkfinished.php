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
 * Called async from warning bar to check if the outage has finished.
 *
 * @package    auth_outage
 * @author     Daniel Thee Roperto <daniel.roperto@catalyst-au.net>
 * @copyright  2016 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use auth_outage\dml\outagedb;

define('NO_MOODLE_COOKIES', true);
 // @codingStandardsIgnoreStart
header('Cache-Control: public, max-age=10,s-maxage=10');
 // @codingStandardsIgnoreEnd
define('NO_AUTH_OUTAGE', true);

require_once(__DIR__.'/../../config.php');

$active = outagedb::get_active();

echo $active ? 'ongoing' : 'finished';

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
 * Auth Outage plugin uninstall code.
 *
 * @package    auth_outage
 * @author     Daniel Thee Roperto <daniel.roperto@catalyst-au.net>
 * @copyright  2016 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

/**
 * Auth Outage plugin uninstall code.
 * @return bool result
 * @throws moodle_exception
 */

function xmldb_auth_outage_uninstall() {
    global $DB;

    // Delete all outage events.
    $DB->delete_records('event', ['eventtype' => 'auth_outage']);

    // Delete template file.
    $template = auth_outage\local\controllers\infopage::get_defaulttemplatefile();
    if (file_exists($template)) {
        if (!unlink($template)) {
            throw new moodle_exception('Cannot remote maintenance template file: '.$template);
        }
    }

    // Remove 'maintenance later' which could have been set for the next outage.
    unset_config('maintenance_later');

    return true;
}

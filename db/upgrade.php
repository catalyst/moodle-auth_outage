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
 * Outage plugin upgrade code
 *
 * @package    auth_outage
 * @author     Daniel Thee Roperto <daniel.roperto@catalyst-au.net>
 * @copyright  2016 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Outage plugin upgrade code
 *
 * @param int $oldversion the version we are upgrading from
 * @return bool result
 */
function xmldb_auth_outage_upgrade($oldversion) {
    global $DB;
    $dbman = $DB->get_manager();

    if ($oldversion < 2016092200) {
        // Define field autostart to be added to auth_outage.
        $table = new xmldb_table('auth_outage');
        $field = new xmldb_field('autostart', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'finished');

        // Conditionally launch add field autostart.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Outage savepoint reached.
        upgrade_plugin_savepoint(true, 2016092200, 'auth', 'outage');
    }

    return true;
}

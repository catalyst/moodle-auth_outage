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


use auth_outage\local\outage;

function xmldb_auth_outage_upgrade($oldversion) {
    global $DB;
    $dbman = $DB->get_manager();
    $auto_start_on = outage::AUTO_START_ON;
    $auto_start_off = outage::AUTO_START_OFF;
    $auto_start_forceoff = outage::AUTO_START_FORCEOFF;

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

    if ($oldversion < 2024081900) {

        // Define field accesskey to be added to auth_outage.
        $table = new xmldb_table('auth_outage');
        $field = new xmldb_field('accesskey', XMLDB_TYPE_CHAR, '16', null, null, null, null, 'finished');

        // Conditionally launch add field accesskey.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Outage savepoint reached.
        upgrade_plugin_savepoint(true, 2024081900, 'auth', 'outage');
    }

    if ($oldversion < 2024081901) {

       $current_config = get_config('auth_outage', 'default_autostart');

       if ($current_config === '0') {
            // Mapping to default off.
           set_config('default_autostart', $auto_start_off, 'auth_outage');

       } else if ($current_config === '1') {
           // Mapping to default on.
           set_config('default_autostart', $auto_start_on, 'auth_outage');  
       } else {
           // Mapping to force off.
           set_config('default_autostart', $auto_start_forceoff, 'auth_outage');
       }

       // Outage savepoint reached.
       upgrade_plugin_savepoint(true, 2024081901, 'auth', 'outage');

    }

    return true;
}

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
 * This file should run before config.php requires '/lib/setup.php'.
 *
 * Main purpose of this file:
 * 1) Create a hook allowing other scripts to run before Moodle loads, but after the $CFG is defined.
 * 2) Allow to 'pretend' maintenance mode for non-allowed IPs by calling 'climaintenance.php'.
 * 3) Set a flag that this file was loaded so we can warn users if this config is not working.
 *
 * @package    auth_outage
 * @author     Daniel Thee Roperto <daniel.roperto@catalyst-au.net>
 * @copyright  2016 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @var stdClass $CFG
 */

// This call is required by Moodle, but this script should have been called by config.php anyway.
require_once(__DIR__.'/../../config.php');

// We need the CFG->dataroot, if not set yet this script is called too early in config.php file.
if (!isset($CFG->dataroot)) {
    return;
}

// 1) Make sure we replace the configurations for behat as we have not ran 'lib/setup.php' yet.
if (!empty($CFG->behat_wwwroot) or !empty($CFG->behat_dataroot) or !empty($CFG->behat_prefix)) {
    require_once(__DIR__.'/../../lib/behat/lib.php');
    behat_update_vars_for_process();
    if (behat_is_test_site()) {
        $beforebehatcfg = $CFG;
        $CFG = clone($CFG);
        clearstatcache();
        behat_check_config_vars();
        behat_clean_init_config();
        $CFG->wwwroot = $CFG->behat_wwwroot;
        $CFG->dataroot = $CFG->behat_dataroot;
        // We should not access database in bootstrap.
        $CFG->dbtype = null;
        $CFG->dblibrary = null;
        $CFG->dbhost = null;
        $CFG->dbname = null;
        $CFG->dbuser = null;
        $CFG->dbpass = null;
        $CFG->prefix = null;
        $CFG->dboptions = null;
    }
}

// 2) Check and run the hook.
if (is_callable('auth_outage_bootstrap_callback')) {
    call_user_func('auth_outage_bootstrap_callback');
}

// 3) Check for allowed scripts or IPs during outages.
$allowed = !file_exists($CFG->dataroot.'/climaintenance.php') // Not in maintenance mode.
           || (defined('ABORT_AFTER_CONFIG') && ABORT_AFTER_CONFIG) // Only config requested.
           || (defined('CLI_SCRIPT') && CLI_SCRIPT); // Allow CLI scripts.
if (!$allowed) {
    // Call the climaintenance.php which will check for allowed IPs.
    $CFG->dirroot = dirname(dirname(dirname(__FILE__))); // It is not defined yet but the script below needs it.
    require($CFG->dataroot.'/climaintenance.php'); // This call may terminate the script here or not.
}

// 4) Set flag this file was loaded.
$CFG->auth_outage_bootstrap_loaded = true;

// 5) Restore behat config as needed (let setup.php execute which is more complex than our quick-check).
if (isset($beforebehatcfg)) {
    $CFG = $beforebehatcfg;
}

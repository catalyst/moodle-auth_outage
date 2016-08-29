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
 * This plugin allows for an outage window to be configured
 * and then optionally allows only a subset of IPs to connect,
 * it also shows an outage notification to users.
 *
 * @package     auth_outage
 * @author      Marcus Boon<marcus@catalyst-au.net>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU Public License
 */

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.'); // It must be included from a Moodle page.
}

require_once($CFG->libdir . '/authlib.php');

/**
 * Class auth_plugin_outage
 */
class auth_plugin_outage extends auth_plugin_base
{
    public function __construct() {
        $this->pluginconfig = 'auth_outage';
        $this->authtype = 'outage';
        $this->roleauth = 'auth_outage';
        $this->component = 'auth_outage';
        $this->errorlogtag = '[AUTH_OUTAGE]';
        $this->config = get_config('auth_outage');
    }

    /**
     * This is the primary method that is used by the authenticate_user_login()
     * function in moodlelib.php.
     *
     * This method should return a boolean indicating
     * whether or not the username and password authenticate successfully.
     *
     * Returns true if the username and password work and false if they are
     * wrong or don't exist.
     *
     * @param string $username The username (with system magic quotes)
     * @param string $password The password (with system magic quotes)
     *
     * @return bool Authentication success or failure.
     */
    public function user_login($username, $password) {
        // Do not authenticate users.
        return false;
    }
}

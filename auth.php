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
 * auth_plugin_outage class.
 *
 * This plugin allows for an outage window to be configured
 * and then optionally allows only a subset of IPs to connect,
 * it also shows an outage notification to users.
 *
 * @package     auth_outage
 * @author      Marcus Boon<marcus@catalyst-au.net>
 * @author      Daniel Thee Roperto <daniel.roperto@catalyst-au.net>
 * @copyright   2016 Catalyst IT
 * @license     http://www.gnu.org/copyleft/gpl.html GNU Public License
 */

use auth_outage\local\outagelib;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir.'/authlib.php');

/**
 * auth_plugin_outage class.
 *
 * This plugin allows for an outage window to be configured
 * and then optionally allows only a subset of IPs to connect,
 * it also shows an outage notification to users.
 *
 * @package     auth_outage
 * @author      Marcus Boon<marcus@catalyst-au.net>
 * @author      Daniel Thee Roperto <daniel.roperto@catalyst-au.net>
 * @copyright   2016 Catalyst IT
 * @license     http://www.gnu.org/copyleft/gpl.html GNU Public License
 */
class auth_plugin_outage extends auth_plugin_base {
    /**
     * Constructor.
     */
    public function __construct() {
        $this->authtype = 'outage';
    }

    /**
     * Always returns false (password wrong or user does not exist).
     *
     * @param string $username Not used in this plugin.
     * @param string $password Not used in this plugin.
     * @return bool False
     */
    public function user_login($username, $password) {
        return false;
    }
}

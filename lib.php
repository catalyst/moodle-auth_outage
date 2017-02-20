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
 * auth_outage plugin lib
 *
 * @package    auth_outage
 * @author     Daniel Thee Roperto <daniel.roperto@catalyst-au.net>
 * @copyright  2016 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use auth_outage\local\outagelib;

defined('MOODLE_INTERNAL') || die;

/**
 * Used in Moodle 30+ when a user is logged on.
 */
function auth_outage_extend_navigation_user_settings() {
    outagelib::inject();
}

/**
 * Used in Moodle 30+ on the frontpage.
 */
function auth_outage_extend_navigation_frontpage() {
    outagelib::inject();
}

/**
 * Used in Moodle 31+ when a user is logged on.
 */
function auth_outage_extend_navigation_user() {
    outagelib::inject();
}

/**
 * Used for adminlib::set_updatedcallback which requires a string that resolves to a function.
 *
 * Related to: MDL-57264 and MDL-32984
 */
function auth_outage_outagelib_prepare_next_outage() {
    outagelib::prepare_next_outage();
}

/**
 * Used by file.php to fetch a file from sitedata, protecting it from path traversal attacks.
 *
 * To keep it minimalist it was not added to the outagelib.php class.
 *
 * @param $file string Filename to fetch from sitedata
 * @return string|null Full path to the sitedata file or null if file is not valid.
 */
function auth_outage_get_climaintenance_resource_file($file) {
    global $CFG;

    // We are not using any external libraries or references in this file (we have not gully loaded config.php yet).
    // If you change the path below maybe you need to change maintenance_static_page::get_resources_folder() as well.
    $resourcedir = rtrim($CFG->dataroot, '/'); // In case the configuration has a trailing slash.
    $resourcedir = $resourcedir.'/auth_outage/climaintenance';

    // Protect against path traversal attacks.
    $basename = basename($file);
    if ($basename !== $file) {
        // @codingStandardsIgnoreStart
        if (!PHPUNIT_TEST) {
            error_log('Possible attempt for Path Traversal Attack (only filename expected): '.$file);
        }
        // @codingStandardsIgnoreEnd
        return null;
    }

    $realpath = realpath($resourcedir.'/'.$file);
    return ($realpath == false) ? null : $realpath;
}

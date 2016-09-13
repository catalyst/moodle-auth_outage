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

namespace auth_outage;

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.'); // It must be included from a Moodle page.
}

/**
 * Outage related functions.
 *
 * @package    auth_outage
 * @author     Daniel Thee Roperto <daniel.roperto@catalyst-au.net>
 * @copyright  Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class outagelib {
    private static $initialized = false;

    /**
     * Initializes admin pages for outage.
     *
     * @return \renderer_base
     */
    public static function pagesetup() {
        global $PAGE;
        admin_externalpage_setup('auth_outage_manage');
        $PAGE->set_url(new \moodle_url('/auth/outage/manage.php'));
        return self::get_renderer();
    }

    /**
     * Returns the outage renderer.
     * @return \renderer_base
     */
    public static function get_renderer() {
        global $PAGE;
        return $PAGE->get_renderer('auth_outage');
    }

    /**
     * Will check for ongoing or warning outages and will attach the message bar as required.
     */
    public static function inject() {
        global $CFG;

        // Many hooks can call it, execute only once.
        if (self::$initialized) {
            return;
        }
        self::$initialized = true;

        // Check for a previewing outage, then for an active outage.
        $previewid = optional_param('auth_outage_preview', null, PARAM_INT);
        $time = time();
        if (is_null($previewid)) {
            if (($active = outagedb::get_active()) == null) {
                return;
            }
        } else {
            if (($active = outagedb::get_by_id($previewid)) == null) {
                return;
            }
            $delta = optional_param('auth_outage_delta', null, PARAM_FLOAT);
            if ($delta) {
                // Delta is float in minutes, allowing to check the redirect in a few seconds.
                $time = $active->starttime + (int)($delta * 60);
            }
        }

        // There is a previewing or active outage.
        $CFG->additionalhtmltopofbody = self::get_renderer()->renderoutagebar($active, $time)
            . $CFG->additionalhtmltopofbody;
    }

    /**
     * Loads data from an object or array into another object. It ensures no new fields are created in the $obj.
     *
     * @param $data mixed An object or array.
     * @param $obj object Destination object to write the properties.
     */
    public static function data2object($data, $obj) {
        if (is_object($data)) {
            $data = get_object_vars($data);
        }
        if (!is_array($data)) {
            throw new \InvalidArgumentException('$data must be an array or an object.');
        }
        if (!is_object($obj)) {
            throw new \InvalidArgumentException('$obj must be an object.');
        }

        foreach ($data as $k => $v) {
            if (property_exists($obj, $k)) {
                if (method_exists($obj, $k)) {
                    throw new \InvalidArgumentException('$obj has a method called ' . $k);
                }
                $obj->$k = $v;
            }
        }
    }
}

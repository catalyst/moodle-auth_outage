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

use Horde\Socket\Client\Exception;

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
class outageutils
{
    /**
     * Initializes admin pages for outage.
     *
     * @return \renderer_base
     */
    public static function pagesetup() {
        global $PAGE;
        admin_externalpage_setup('auth_outage_manage');
        $PAGE->set_url(new \moodle_url('/auth/outage/list.php'));
        return $PAGE->get_renderer('auth_outage');
    }

    /**
     * Loads data from an object or array into another object.
     *
     * @param $data mixed An object or array.
     * @param $obj object Destination object to write the properties.
     * @param $strict bool All data fields must be used in the destination object or an exception will be thrown.
     */
    public static function data2object($data, $obj, $strict = false) {
        if (is_object($data)) {
            $data = get_object_vars($data);
        }
        if (!is_array($data)) {
            throw new \InvalidArgumentException('$data must be an array or an object.');
        }
        if (!is_object($obj)) {
            throw new \InvalidArgumentException('$obj must be an object.');
        }
        if (!is_bool($strict)) {
            throw new \InvalidArgumentException('$strict must be a bool.');
        }

        foreach ($data as $k => $v) {
            if (!property_exists($obj, $k)) {
                if ($strict) {
                    throw new \InvalidArgumentException('$obj does not have a property called ' . $k);
                }
            } else {
                if (method_exists($obj, $k)) {
                    throw new \InvalidArgumentException('$obj has a method called ' . $k);
                }
                $obj->$k = $v;
            }
        }
    }

    public static function parseformdata($data) {
        if ($data->description['format'] != '1') {
            throw new \InvalidArgumentException('Not implemented for format ' . $data->description['format']);
        }
        if ($data->id === 0) {
            $data->id = null;
        }
        $data->description = $data->description['text'];
        return $data;
    }
}
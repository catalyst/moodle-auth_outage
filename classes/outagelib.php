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

use auth_outage_renderer;
use moodle_url;

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
     * @return auth_outage_renderer The outage renderer for the page.
     */
    public static function pagesetup() {
        global $PAGE;
        admin_externalpage_setup('auth_outage_manage');
        $PAGE->set_url(new moodle_url('/auth/outage/manage.php'));
        return self::get_renderer();
    }

    /**
     * Returns the outage renderer.
     * @return auth_outage_renderer The outage renderer.
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
            if (!$active = outagedb::get_active()) {
                return;
            }
        } else {
            if (!$active = outagedb::get_by_id($previewid)) {
                return;
            }
            // Delta is in seconds, setting the time our warning bar will consider relative to the outage start time.
            $time = $active->starttime + optional_param('auth_outage_delta', 0, PARAM_INT);
            if (!$active->is_active($time)) {
                return;
            }
        }

        // There is a previewing or active outage.
        $CFG->additionalhtmltopofbody = self::get_renderer()->renderoutagebar($active, $time)
            . $CFG->additionalhtmltopofbody;
    }

    public static function get_config() {
        return (object)array_merge(self::get_config_defaults(), (array)get_config('auth_outage'));
    }

    public static function get_config_defaults() {
        global $CFG;

        return [
            'default_duration' => 60,
            'warning_duration' => 60,
            'warning_title' => get_string('defaultwarningtitlevalue', 'auth_outage'),
            'warning_description' => get_string('defaultwarningdescriptionvalue', 'auth_outage'),
            'css' => file_get_contents($CFG->dirroot . '/auth/outage/views/warningbar.css'),
        ];
    }
}

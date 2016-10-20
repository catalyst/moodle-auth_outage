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
 * outagelib class.
 *
 * @package    auth_outage
 * @author     Daniel Thee Roperto <daniel.roperto@catalyst-au.net>
 * @copyright  2016 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace auth_outage\local;

use auth_outage\dml\outagedb;
use auth_outage\local\controllers\infopage;
use auth_outage\output\renderer;
use coding_exception;
use Exception;
use stdClass;

defined('MOODLE_INTERNAL') || die();

/**
 * outagelib class.
 *
 * @package    auth_outage
 * @author     Daniel Thee Roperto <daniel.roperto@catalyst-au.net>
 * @copyright  2016 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class outagelib {
    /**
     * @var bool Flags in the injection function was already called.
     */
    private static $injectcalled = false;

    /**
     * Calls inject even if it was already called before.
     */
    public static function reinject() {
        self::$injectcalled = false;
        self::inject();
    }

    /**
     * Will check for ongoing or warning outages and will attach the message bar as required.
     */
    public static function inject() {
        global $CFG;

        // Ensure we do not kill the whole website in case of an error.
        try {
            // Check if we should inject the code.
            if (!self::injection_allowed()) {
                return;
            }

            // Used to test the try block in case of errors.
            if (PHPUNIT_TEST && optional_param('auth_outage_break_code', false, PARAM_INT)) {
                (new stdClass())->invalidfield;
            }

            // Check for a previewing outage, then for an active outage.
            $previewid = optional_param('auth_outage_preview', null, PARAM_INT);
            $time = time();
            if (is_null($previewid)) {
                if (!$active = outagedb::get_active($time)) {
                    return;
                }
                $preview = false;
            } else {
                if (!$active = outagedb::get_by_id($previewid)) {
                    return;
                }
                // Delta is in seconds, setting the time our warning bar will consider relative to the outage start time.
                $delta = optional_param('auth_outage_delta', 0, PARAM_INT);
                $time = $active->starttime + $delta;
                if (!$active->is_active($time)) {
                    return;
                }
                $preview = true;
            }

            // There is a previewing or active outage.
            $CFG->additionalhtmltopofbody = renderer::get()->render_warningbar($active, $time, false, $preview).
                                            $CFG->additionalhtmltopofbody;
        } catch (Exception $e) {
            debugging('Exception occured while injecting our code: '.$e->getMessage());
            debugging($e->getTraceAsString(), DEBUG_DEVELOPER);
        }
    }

    /**
     * Creates a configuration object ensuring all parameters are set,
     * loading defaults even if the plugin is not configured.
     * @return stdClass Configuration object with all parameters set.
     * @throws coding_exception
     */
    public static function get_config() {
        $config = (array)get_config('auth_outage');
        foreach ($config as $k => $v) {
            if (!is_string($v)) {
                throw new coding_exception('Config is expected to give string.');
            }
            if (trim($v) == '') {
                unset($config[$k]);
            }
        }

        return (object)array_merge(self::get_config_defaults(), $config);
    }

    /**
     * Creates the default configurations. If the plugin is not configured we should use those defaults.
     * @return mixed[] Default configuration.
     */
    public static function get_config_defaults() {
        global $CFG;

        return [
            'default_autostart' => '0',
            'default_duration' => (string)(60 * 60),
            'default_warning_duration' => (string)(60 * 60),
            'default_title' => get_string('defaulttitlevalue', 'auth_outage'),
            'default_description' => get_string('defaultdescriptionvalue', 'auth_outage'),
            'css' => file_get_contents($CFG->dirroot.'/auth/outage/views/warningbar/warningbar.css'),
        ];
    }

    /**
     * Executed when outages are modified (created, updated or deleted).
     */
    public static function outages_modified() {
        infopage::update_static_page();
        self::update_maintenance_later();
    }

    /**
     * Calls Moodle API - set_maintenance_later() to set when the next outage starts.
     */
    private static function update_maintenance_later() {
        $next = outagedb::get_next_autostarting();
        if (is_null($next)) {
            unset_config('maintenance_later');
        } else {
            $message = get_config('moodle', 'maintenance_message');
            if ($message) {
                debugging('Disabling $CFG->maintenance_message to allow our template page to take place.');
                debugging('Previous value: '.$message);
                // We cannot do much if forced config, but the logs will show the error.
                unset_config('maintenance_message');
            }
            set_config('maintenance_later', $next->starttime);
        }
    }

    /**
     * Checks if we should try to inject an warning bar.
     * @return bool
     */
    private static function injection_allowed() {
        global $CFG;

        // Injection should only be called once, if called more times by other hooks ignore it.
        if (self::$injectcalled) {
            return false;
        }
        self::$injectcalled = true;

        // Do not inject into admin/settings.php, see Issue #65.
        if ($_SERVER['SCRIPT_FILENAME'] === $CFG->dirroot.'/admin/settings.php') {
            return false;
        }

        // Nothing preventing the injection.
        return true;
    }
}

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
use file_exception;
use invalid_parameter_exception;
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
        return [
            'allowedips' => '',
            'css' => '',
            'default_autostart' => '0',
            'default_duration' => (string)(60 * 60),
            'default_warning_duration' => (string)(60 * 60),
            'default_title' => get_string('defaulttitlevalue', 'auth_outage'),
            'default_description' => get_string('defaultdescriptionvalue', 'auth_outage'),
        ];
    }

    /**
     * Executed when outages are modified (created, updated or deleted).
     */
    public static function prepare_next_outage() {
        // If there is an ongoing outage, prepare it instead.
        $outage = outagedb::get_ongoing();
        if (is_null($outage)) {
            $outage = outagedb::get_next_starting();
        }
        infopage::update_static_page($outage);
        self::update_climaintenance_code($outage);
        self::update_maintenance_later($outage);
    }

    /**
     * Calls Moodle API - set_maintenance_later() to set when the next outage starts.
     * @param outage|null $outage Outage or null if no scheduled outage.
     */
    private static function update_maintenance_later($outage) {
        if (is_null($outage) || !$outage->autostart) {
            unset_config('maintenance_later');
        } else {
            $message = get_config('moodle', 'maintenance_message');
            if ($message) {
                debugging('Disabling $CFG->maintenance_message to allow our template page to take place.');
                debugging('Previous value: '.$message);
                // We cannot do much if forced config, but the logs will show the error.
                unset_config('maintenance_message');
            }
            set_config('maintenance_later', $outage->starttime);
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

    /**
     * Generates the code to put in sitedata/climaintenance.php when needed.
     * @param int $starttime Outage start time.
     * @param int $stoptime Outage stop time.
     * @param string $allowedips List of IPs allowed.
     * @return string
     * @throws invalid_parameter_exception
     */
    public static function create_climaintenancephp_code($starttime, $stoptime, $allowedips) {
        if (!is_int($starttime) || !is_int($stoptime)) {
            throw new invalid_parameter_exception('Make sure $startime and $stoptime are integers.');
        }
        if (!is_string($allowedips) || (trim($allowedips) == '')) {
            throw new invalid_parameter_exception('$allowedips must be a valid string.');
        }
        // I know Moodle validation would clean up this field, but just in case, let's ensure no
        // single-quotes (and double for the sake of it) are present otherwise it would break the code.
        $allowedips = str_replace('\'"', '', $allowedips);

        $code = <<<'EOT'
<?php
if (time() >= {{STARTTIME}}) {
    if (!defined('CLI_SCRIPT') || !CLI_SCRIPT) {
        define('MOODLE_INTERNAL', true);
        require_once($CFG->dirroot.'/lib/moodlelib.php');
        if (!remoteip_in_list('{{ALLOWEDIPS}}')) {
            header($_SERVER['SERVER_PROTOCOL'] . ' 503 Moodle under maintenance');
            header('Status: 503 Moodle under maintenance');
            header('Retry-After: 300');
            header('Content-type: text/html; charset=utf-8');
            header('X-UA-Compatible: IE=edge');
            header('Cache-Control: no-store, no-cache, must-revalidate');
            header('Cache-Control: post-check=0, pre-check=0', false);
            header('Pragma: no-cache');
            header('Expires: Mon, 20 Aug 1969 09:23:00 GMT');
            header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
            header('Accept-Ranges: none');
            echo '<!-- Blocked by ip, your ip: '.getremoteaddr('n/a').' -->';
            if (file_exists($CFG->dataroot.'/climaintenance.template.html')) {
                require($CFG->dataroot.'/climaintenance.template.html');
                exit(0);
            }
            // The file above should always exist, but just in case...
            die('We are currently under maintentance, please try again later.');
        }
    }
}
$CFG->auth_outage_check = 1;
EOT;
        $search = ['{{STARTTIME}}', '{{ALLOWEDIPS}}', '{{YOURIP}}'];
        $replace = [$starttime, $allowedips, getremoteaddr('n/a')];
        return str_replace($search, $replace, $code);
    }

    /**
     * Updates the static info page by (re)creating or deleting it as needed.
     * @param outage|null $outage Outage or null if no scheduled outage.
     * @throws coding_exception
     * @throws file_exception
     */
    public static function update_climaintenance_code($outage) {
        global $CFG;
        $file = $CFG->dataroot.'/climaintenance.php';

        if (!is_null($outage) && !($outage instanceof outage)) {
            throw new coding_exception('$outage must be null or an outage object.');
        }

        $config = self::get_config();
        $allowedips = trim($config->allowedips);

        if (is_null($outage) || ($allowedips == '')) {
            if (file_exists($file)) {
                unlink($file);
            }
        } else {
            $code = self::create_climaintenancephp_code($outage->starttime, $outage->stoptime, $allowedips);

            $dir = dirname($file);
            if (!file_exists($dir) || !is_dir($dir)) {
                throw new file_exception('Directory must exists: '.$dir);
            }
            file_put_contents($file, $code);
        }
    }
}

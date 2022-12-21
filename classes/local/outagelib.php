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
use auth_outage\local\controllers\maintenance_static_page;
use auth_outage\output\renderer;
use coding_exception;
use curl;
use Exception;
use file_exception;
use invalid_parameter_exception;
use stdClass;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__.'/../../lib.php');

/**
 * outagelib class.
 *
 * @package    auth_outage
 * @author     Daniel Thee Roperto <daniel.roperto@catalyst-au.net>
 * @copyright  2016 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class outagelib {

    /** Outage start. */
    const OUTAGE_START = '<!-- OUTAGESTART -->';

    /** Outage end. */
    const OUTAGE_END = '<!-- OUTAGEEND -->';

    /**
     * @var bool Flags in the injection function was already called.
     */
    private static $injectcalled = false;

    /**
     * Fetches page.
     * @param string $file file to be fetched
     */
    public static function fetch_page($file) {
        global $CFG;
        require_once($CFG->libdir . '/filelib.php');

        $curl = new curl();
        $contents = $curl->get($file);
        $info = $curl->get_info();
        if (!empty($info['content_type'])) {
            $mime = $info['content_type'];
        } else {
            $mime = '';
        }
        return compact('contents', 'mime');
    }

    /**
     * Resets inject called to allow the code to be regenerated.
     */
    public static function reset_injectcalled() {
        self::$injectcalled = false;
    }

    /**
     * Given a time, usually now, when is the next outage window?
     * @param int $time time for next window
     */
    public static function get_next_window($time = null) {

        $config = self::get_config();

        if (!$time) {
            $time = time();
        }

        $default = $config->default_time;
        if ($default) {
            // First try natural language parsing.
            $time = strtotime($default, $time);
        }
        return $time;
    }


    /**
     * Will check for ongoing or warning outages and will return the message bar as required.
     *
     * @return string|void CSS and HTML for the warning bar if it should be displayed
     */
    public static function get_inject_code() {
        global $PAGE;
        // Ensure we do not kill the whole website in case of an error.
        try {
            // Check if we should inject the code.
            if (!self::injection_allowed()) {
                return;
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
            $renderer = $PAGE->get_renderer('auth_outage');
            return $renderer->render_warningbar($active, $time, false, $preview);
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

        $config = array_merge(self::get_config_defaults(), $config);
        // Combine allowed IPs config values together.
        if (isset($config['allowedips_forced'])) {
            $config['allowedips'] = trim($config['allowedips'] . "\n" . $config['allowedips_forced']);
        }
        return (object)$config;
    }

    /**
     * Creates the default configurations. If the plugin is not configured we should use those defaults.
     * @return mixed[] Default configuration.
     */
    public static function get_config_defaults() {
        return [
            'allowedips'               => '',
            'css'                      => '',
            'default_time'             => '',
            'default_autostart'        => '0',
            'default_duration'         => (string)(60 * 60),
            'default_warning_duration' => (string)(60 * 60),
            'default_title'            => get_string('defaulttitlevalue', 'auth_outage'),
            'default_description'      => get_string('defaultdescriptionvalue', 'auth_outage'),
            'remove_selectors'         => ".usermenu\n.logininfo\n.homelink",
        ];
    }

    /**
     * Executed when outages are modified (created, updated or deleted).
     *
     * @param bool $reenablemaint should we re-enable maintenance mode for ongoing outage
     * @throws coding_exception
     * @throws file_exception
     */
    public static function prepare_next_outage($reenablemaint = false) {
        // If there is an ongoing outage, prepare it instead.
        $outage = outagedb::get_ongoing();
        if (is_null($outage)) {
            $outage = outagedb::get_next_starting();
            $ongoingoutage = false;
        } else {
            $ongoingoutage = true;
        }

        // Set json formatted outage string to cache.
        set_config('auth_outage_active_outage', (string)$outage);

        maintenance_static_page::create_from_outage($outage)->generate();
        self::update_climaintenance_code($outage);
        if (!$ongoingoutage || $reenablemaint || is_null($outage)) {
            self::update_maintenance_later($outage);
        }
    }

    /**
     * Checks if wwwroot accessible.
     */
    private static function check_wwwroot_accessible() {
        global $CFG;
        $result = self::fetch_page($CFG->wwwroot);
        return (!empty($result['contents']));
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
            self::maintenance_config_log($outage);
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

        // Do not inject into admin/settings.php.
        if ($_SERVER['SCRIPT_NAME'] == '/'.$CFG->admin.'/settings.php') {
            if (optional_param('section', '', PARAM_RAW) === 'additionalhtml') {
                return false;
            }
        }

        // Check if warning bar should be hidden.
        if (optional_param('auth_outage_hide_warning', false, PARAM_BOOL)) {
            return false;
        }

        // Used to test the try block in case of errors.
        if (PHPUNIT_TEST && optional_param('auth_outage_break_code', false, PARAM_INT)) {
            (new stdClass())->invalidfield; // Triggers an exception.
        }

        // Nothing preventing the injection.
        return true;
    }

    /**
     * Generates the code to put in sitedata/climaintenance.php when needed.
     *
     * @param int    $starttime  Outage start time.
     * @param int    $stoptime   Outage stop time.
     * @param string $allowedips List of IPs allowed.
     *
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
        $allowedips = addslashes($allowedips);

        $code = <<<'EOT'
<?php
if ((time() >= {{STARTTIME}}) && (time() < {{STOPTIME}})) {
    define('MOODLE_INTERNAL', true);
    require_once($CFG->dirroot.'/lib/moodlelib.php');
    if (file_exists($CFG->dirroot.'/lib/classes/ip_utils.php')) {
        require_once($CFG->dirroot.'/lib/classes/ip_utils.php');
    }
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
        header('X-Moodle-Maintenance: manager');
        if ((defined('AJAX_SCRIPT') && AJAX_SCRIPT) || (defined('WS_SERVER') && WS_SERVER)) {
            exit(0);
        }
        echo '<!-- Blocked by ip, your ip: '.getremoteaddr('n/a').' -->';
        if (file_exists($CFG->dataroot.'/climaintenance.template.html')) {
            require($CFG->dataroot.'/climaintenance.template.html');
            exit(0);
        }
        // The file above should always exist, but just in case...
        die('We are currently under maintentance, please try again later.');
    }
}
EOT;
        $search = ['{{STARTTIME}}', '{{STOPTIME}}', '{{ALLOWEDIPS}}', '{{YOURIP}}'];
        $replace = [$starttime, $stoptime, $allowedips, getremoteaddr('n/a')];
        return str_replace($search, $replace, $code);
    }

    /**
     * Updates the static info page by (re)creating or deleting it as needed.
     *
     * @param outage|null $outage Outage or null if no scheduled outage.
     *
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

    /**
     * Generates a warning message in case the plugin is not active and configured.
     *
     * @return string
     */
    public static function generate_plugin_configuration_warning() {
        global $CFG, $OUTPUT, $PAGE;

        $message = [];

        if (trim(self::get_config()->allowedips) != ''
                && (!isset($CFG->auth_outage_bootstrap_loaded) || !$CFG->auth_outage_bootstrap_loaded)) {
            $message[] = get_string('configurationwarning', 'auth_outage');
        }

        if (!is_enabled_auth('outage')) {
            $message[] = get_string('configurationdisabled', 'auth_outage');
        }

        if ($PAGE->pagetype == "admin-setting-auth_outage" || $PAGE->pagetype == "admin-setting-authsettingoutage") {
            if (!self::check_wwwroot_accessible()) {
                $message[] = get_string('configurationinaccessiblewwwroot', 'auth_outage', ['wwwroot' => $CFG->wwwroot]);
            }
        }

        if (count($message) == 0) {
            return '';
        }

        if (CLI_SCRIPT) {
            $message = html_to_text(implode("; ", $message));
        } else {
            $message = $OUTPUT->notification(implode("<br />", $message), 'notifyerror');
        }

        return $message;
    }

    /**
     * Logging for maintenance mode configuration.
     *
     * @param outage|null $outage Outage or null if no scheduled outage.
     */
    private static function maintenance_config_log(outage $outage) {
        mtrace(get_string('logformaintmodeconfig', 'auth_outage'));
        $timezone = ' (Timezone ' . \core_date::get_server_timezone_object()->getName() . ')';
        mtrace('... updated at ' . date('H:i:s'));
        $time = date("Y-m-d H:i:s", $outage->starttime);
        mtrace("... enable maintenance mode at $time $timezone");
        mtrace(get_string('logformaintmodeconfigcomplete', 'auth_outage'));
    }
}

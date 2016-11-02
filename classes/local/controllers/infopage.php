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
 * infopage class.
 *
 * @package    auth_outage
 * @author     Daniel Thee Roperto <daniel.roperto@catalyst-au.net>
 * @copyright  2016 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace auth_outage\local\controllers;

use auth_outage\dml\outagedb;
use auth_outage\local\outage;
use auth_outage\local\outagelib;
use auth_outage\output\renderer;
use coding_exception;
use context_system;
use file_exception;
use invalid_state_exception;
use moodle_url;

defined('MOODLE_INTERNAL') || die();

/**
 * infopage class.
 *
 * @package    auth_outage
 * @author     Daniel Thee Roperto <daniel.roperto@catalyst-au.net>
 * @copyright  2016 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class infopage {
    /**
     * @var outage|null The outage to display or null if none found.
     */
    private $outage;

    /**
     * @var bool Flags if a static version of this page should be displayed (maintenance mode).
     */
    private $static;

    /**
     * infopage_controller constructor.
     * @param array $params Parameters to use or null to get from Moodle API (request).
     */
    public function __construct(array $params = null) {
        if (is_null($params)) {
            $params = [
                'id' => optional_param('id', null, PARAM_INT),
                'static' => optional_param('static', false, PARAM_BOOL),
                'outage' => null,
            ];
        } else {
            $defaults = [
                'id' => null,
                'outage' => null,
                'static' => false,
            ];
            $params = array_merge($defaults, $params);
        }

        $this->set_parameters($params);
    }

    /**
     * Given the HTML code for the static page, find the outage id for that page.
     * @param string $html Static info page HTML.
     * @return int|null Outage id or null if not found.
     * @throws coding_exception
     */
    public static function find_outageid_from_infopage($html) {
        if (!is_string($html)) {
            throw new coding_exception('$html must be a string.', gettype($html));
        }

        $output = [];
        if (preg_match('/data-outage-id="(?P<id>\d+)"/', $html, $output)) {
            return (int)$output['id'];
        }
        return null;
    }

    /**
     * Saves a static info page for the given outage.
     * @param outage $outage Outage to generate the info page.
     * @param string $file File to save the static info page.
     * @throws coding_exception
     * @throws file_exception
     * @throws invalid_state_exception
     */
    public static function save_static_page(outage $outage, $file) {
        if (!is_string($file)) {
            throw new coding_exception('$file is not a string.', $file);
        }

        $info = new infopage(['outage' => $outage, 'static' => true]);
        $html = $info->get_output();

        self::save_static_page_sanitycheck($html);

        $dir = dirname($file);
        if (!file_exists($dir) || !is_dir($dir)) {
            throw new file_exception('Directory must exists: '.$dir);
        }
        file_put_contents($file, $html);
    }

    /**
     * Updates the static info page by (re)creating or deleting it as needed.
     * @param outage|null $outage Outage or null if no scheduled outage.
     * @param string|null $file File to update. Null to use default.
     * @throws coding_exception
     * @throws file_exception
     */
    public static function update_static_page($outage, $file = null) {
        if (is_null($file)) {
            $file = self::get_defaulttemplatefile();
        }
        if (!is_string($file)) {
            throw new coding_exception('$file is not a string.', $file);
        }
        if (!is_null($outage) && !($outage instanceof outage)) {
            throw new coding_exception('$outage must be null or an outage object.');
        }

        if (is_null($outage)) {
            if (file_exists($file)) {
                unlink($file);
            }
        } else {
            self::save_static_page($outage, $file);
        }
    }

    /**
     * Gets the default template file to use for static info page.
     * @return string The default template file to use for static info page.
     */
    public static function get_defaulttemplatefile() {
        global $CFG;
        return $CFG->dataroot.'/climaintenance.template.html';
    }

    /**
     * Ensures the data to create the page is valid.
     * It should never be invalid, but if it is we will get a blank page while the maintenance is ongoing and the
     * system administrators may become frustrated to understand why it is not working, let's not provoke them!
     * @param string $html The HTML to check.
     * @throws invalid_state_exception
     */
    public static function save_static_page_sanitycheck($html) {
        if (!is_string($html) || (trim($html) == '') || (trim(html_to_text($html)) == '')) {
            throw new invalid_state_exception('Sanity check failed. Invalid contents on $html.');
        }
    }

    /**
     * Generates and returns the HTML for the info page.
     * @return string HTML for the info page.
     */
    public function get_output() {
        ob_start();
        $output = null;
        try {
            $this->output();
            $output = ob_get_contents();
        } finally {
            ob_end_clean();
        }
        return $output;
    }

    /**
     * Checks if this page should have admin options, taking in consideration it should happen if generating a static page.
     * @return bool True if it should display admin options.
     */
    public function has_admin_options() {
        return (!$this->static && is_siteadmin());
    }

    /**
     * Generates and outputs the HTML for the info page.
     * @uses    redirect
     */
    public function output() {
        global $PAGE, $CFG, $OUTPUT;

        if (is_null($this->outage)) {
            if ($this->static) {
                throw new coding_exception('Cannot render a static info page without an outage.');
            } else {
                redirect(new moodle_url('/'));
            }
        }

        $viewbag = [
            'admin' => is_siteadmin(),
            'outage' => $this->outage,
        ];

        $PAGE->set_context(context_system::instance());
        if ($this->static) {
            $viewbag['admin'] = false;
            renderer::get()->output_view('info/static.php', $viewbag);
        } else {
            $PAGE->set_title($this->outage->get_title());
            $PAGE->set_heading($this->outage->get_title());
            $PAGE->set_url(new moodle_url('/auth/outage/info.php'));

            // No hooks injecting into this page, do it manually.
            outagelib::inject();

            echo $OUTPUT->header();
            require($CFG->dirroot.'/auth/outage/views/info/content.php');

            // Moodle 2.7 did not check for CLI mode, which was fixed later.
            if (!($CFG->branch == '27' && CLI_SCRIPT)) {
                echo $OUTPUT->footer();
            }
        }
    }

    /**
     * Adjusts the fields according to the given parameters.
     * @param mixed[] $params
     * @throws coding_exception
     */
    private function set_parameters(array $params) {
        if (!is_null($params['outage']) && !($params['outage'] instanceof outage)) {
            throw new coding_exception('Provided outage is not a valid outage object.', $params['outage']);
        }

        if (!is_null($params['id']) && !is_null($params['outage']) && ($params['id'] !== $params['outage']->id)) {
            throw new coding_exception('Provided id and outage->id do not match.', $params['id'].'/'.$params['outage']->id);
        }

        if (is_null($params['id']) && is_null($params['outage'])) {
            $params['outage'] = outagedb::get_active();
        } else if (is_null($params['outage'])) {
            $params['outage'] = outagedb::get_by_id($params['id']);
        }

        $this->outage = $params['outage'];
        $this->static = (bool)$params['static'];
    }
}

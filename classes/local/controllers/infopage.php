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
use coding_exception;
use context_system;
use moodle_url;

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
     * @var bool|null Defines if the page is generated for a static outage page.
     */
    private $static;

    /**
     * infopage_controller constructor.
     * @param array $params Parameters to use or null to get from Moodle API (request).
     */
    public function __construct(array $params = null) {
        global $CFG;
        // Enable SVG support here to make sure all SVG files
        // used in the current theme are served properly.
        $CFG->svgicons = true;

        if (is_null($params)) {
            $params = [
                'id' => optional_param('id', null, PARAM_INT),
                'outage' => null,
                'static' => optional_param('static', false, PARAM_BOOL),
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
     * Generates and outputs the HTML for the info page.
     * @uses    redirect
     */
    public function output() {
        global $PAGE, $CFG, $OUTPUT;

        if (is_null($this->outage)) {
            redirect(new moodle_url('/'));
        }

        // If it's not static outage page, then check access, then redirect if not allowed.
        if (!$this->static && !has_capability('auth/outage:viewinfo', context_system::instance())) {
            redirect(new moodle_url('/'));
        }
        $PAGE->set_context(context_system::instance());
        $PAGE->set_title($this->outage->get_title());
        $PAGE->set_heading($this->outage->get_title());
        $PAGE->set_url(new moodle_url('/auth/outage/info.php'));

        // No hooks injecting into this page, do it manually.
        echo outagelib::get_inject_code();

        echo $OUTPUT->header();
        $viewbag = [
            'admin' => is_siteadmin(),
            'outage' => $this->outage,
        ];
        require($CFG->dirroot.'/auth/outage/views/info/content.php');

        // Moodle 2.7 did not check for CLI mode, which was fixed later.
        if (!($CFG->branch == '27' && CLI_SCRIPT)) {
            echo $OUTPUT->footer();
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
        $this->static = $params['static'];
    }
}

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
 * maintenance_static_page class.
 *
 * @package    auth_outage
 * @author     Daniel Thee Roperto <daniel.roperto@catalyst-au.net>
 * @copyright  2016 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace auth_outage\local\controllers;

use auth_outage\local\outage;
use coding_exception;
use DOMDocument;

/**
 * maintenance_static_page class.
 *
 * @package    auth_outage
 * @author     Daniel Thee Roperto <daniel.roperto@catalyst-au.net>
 * @copyright  2016 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class maintenance_static_page {
    /**
     * Creates a page based on the given outage.
     *
     * @param outage|null $outage
     *
     * @return maintenance_static_page
     * @throws coding_exception
     */
    public static function create_from_outage($outage) {
        global $CFG;

        if (!is_null($outage) && !($outage instanceof outage)) {
            throw new coding_exception('$outage must be null or an outage object.');
        }

        if (is_null($outage)) {
            $html = null;
        } else if (PHPUNIT_TEST || defined('BEHAT_SITE_RUNNING')) {
            $html = '<html></html>';
        } else {
            $data = maintenance_static_page_io::file_get_data(
                $CFG->wwwroot.'/auth/outage/info.php?auth_outage_hide_warning=1&static=1&id='.$outage->id);
            $html = $data['contents'];
        }

        $page = self::create_from_html($html);
        if (!is_null($outage)) {
            $page->set_max_refresh_time($outage->get_duration_planned());
        }
        return $page;
    }

    /**
     * Creates a page based on the given HTML.
     *
     * @param string|null $html
     *
     * @return maintenance_static_page
     * @throws coding_exception
     */
    public static function create_from_html($html) {
        if (!is_null($html) && !is_string($html)) {
            throw new coding_exception('$html is not valid.');
        }

        if (is_null($html)) {
            $dom = null;
        } else {
            $dom = new DOMDocument();

            // Let's assume we have no parsing errors as we cannot rely on a badly-formed page anyway.
            libxml_use_internal_errors(true);
            $dom->loadHTML($html);
            libxml_clear_errors();
        }

        return new maintenance_static_page($dom);
    }

    /** @var maintenance_static_page_generator */
    protected $generator;

    /**
     * maintenance_static_page constructor.
     *
     * @param DOMDocument|null $dom
     *
     * @throws coding_exception
     */
    protected function __construct($dom) {
        $io = new maintenance_static_page_io();
        $this->generator = new maintenance_static_page_generator($dom, $io);
    }

    /**
     * Requests to generate the static page.
     */
    public function generate() {
        $this->generator->generate();
    }

    /**
     * Gets generator io.
     * @return maintenance_static_page_io
     */
    public function get_io() {
        return $this->generator->get_io();
    }

    /**
     * Sets the maximum amount of seconds to auto refresh the static page.
     * @param int $maxsecs
     */
    public function set_max_refresh_time($maxsecs) {
        $current = $this->generator->get_refresh_time();
        if ($maxsecs < $current) {
            $this->generator->set_refresh_time($maxsecs);
        }
    }
}

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
use DOMElement;
use invalid_parameter_exception;
use moodle_url;

defined('MOODLE_INTERNAL') || die();

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
     * @param outage $outage
     * @return maintenance_static_page
     */
    public static function create_from_outage(outage $outage) {
        global $CFG;
        $html = file_get_contents($CFG->wwwroot.'/auth/outage/info.php?auth_outage_hide_warning=1&id='.$outage->id);
        return self::create_from_html($html);
    }

    /**
     * Creates a page based on the given HTML.
     * @param string $html
     * @return maintenance_static_page
     * @throws coding_exception
     */
    public static function create_from_html($html) {
        if (!is_string($html)) {
            throw new coding_exception('$html is not valid.');
        }

        $dom = new DOMDocument();

        // Let's assume we have no parsing errors as we cannot rely on a badly-formed page anyway.
        libxml_use_internal_errors(true);
        $dom->loadHTML($html);
        libxml_clear_errors();

        return new maintenance_static_page($dom);
    }

    /** @var DOMDocument */
    protected $dom;

    /** @var bool */
    protected $preview = false;

    /**
     * maintenance_static_page constructor.
     * @param DOMDocument $dom
     */
    public function __construct(DOMDocument $dom) {
        $this->dom = $dom;
    }

    /**
     * Gets the cli maintenance template file location.
     * @return string
     */
    public function get_template_file() {
        global $CFG;
        if ($this->preview) {
            return $this->get_resources_folder().'/climaintenance.html';
        } else {
            return $CFG->dataroot.'/climaintenance.template.html';
        }
    }

    /**
     * Gets the resources folder in dataroot.
     *
     * Warning: this folder will be deleted every time the page is regenerated.
     *
     * @return string
     */
    public function get_resources_folder() {
        global $CFG;
        // If you change the path, also change file auth/outage/maintenance.php as it does not use this reference.
        if ($this->preview) {
            return $CFG->dataroot.'/auth_outage/climaintenance/preview';
        } else {
            return $CFG->dataroot.'/auth_outage/climaintenance';
        }
    }

    /**
     * Generates the page.
     */
    public function generate() {
        self::prepare_dataroot();
        self::remove_script_tags();
        self::update_link_stylesheet();
        self::update_link_favicon();
        self::update_images();
        file_put_contents(self::get_template_file(), $this->dom->saveHTML());
    }

    /**
     * @param boolean $preview
     * @return maintenance_static_page
     */
    public function set_preview($preview) {
        $this->preview = $preview;
        return $this;
    }

    /**
     * Remove script tags from DOM.
     */
    private function remove_script_tags() {
        $scripts = $this->dom->getElementsByTagName('script');
        // List items to remove without changing the DOM.
        $remove = [];
        foreach ($scripts as $node) {
            $remove[] = $node;
        }
        // All listed, now remove them.
        foreach ($remove as $node) {
            $node->parentNode->removeChild($node);
        }
    }

    /**
     * Clean up the dataroot as needed.
     */
    private function prepare_dataroot() {
        $dir = self::get_resources_folder();
        if (is_dir($dir)) {
            self::delete_directory_recursively($dir);
        }
        mkdir($dir, 0775, true);
    }

    /**
     * Deletes the given directory with all its files and subdirectories.
     * @param string $dir Directory to delete.
     * @throws coding_exception
     * @throws invalid_parameter_exception
     */
    private function delete_directory_recursively($dir) {
        // It should never come from user, but protect against possible attacks anyway.
        $dir = realpath($dir);
        $safedir = self::get_resources_folder();
        if (substr($dir, 0, strlen($safedir)) !== $safedir) {
            throw new invalid_parameter_exception('Unsafe to delete: '.$dir);
        }

        if (!is_dir($dir)) {
            throw new coding_exception('Not a directory: '.$dir);
        }
        $files = scandir($dir);
        foreach ($files as $file) {
            if (($file == '.') || ($file == '..')) {
                continue;
            }
            $file = $dir.'/'.$file;
            if (is_file($file)) {
                unlink($file);
                continue;
            }
            if (is_dir($file)) {
                self::delete_directory_recursively($file);
                continue;
            }
            throw new coding_exception('Not a file or directory: '.$file);
        }
        rmdir($dir);
    }

    /**
     * Fetch and fixes all link rel="stylesheet" tags.
     */
    private function update_link_stylesheet() {
        $links = $this->dom->getElementsByTagName('link');

        foreach ($links as $link) {
            $rel = $link->getAttribute("rel");
            $href = $link->getAttribute("href");
            if (($rel != 'stylesheet') || ($href == '')) {
                continue;
            }
            $link->setAttribute('href', self::prepare_url($href, 'css'));
        }
    }

    /**
     * Fetch and fixes the favicon link tag.
     */
    private function update_link_favicon() {
        $links = $this->dom->getElementsByTagName('link');

        foreach ($links as $link) {
            $rel = $link->getAttribute("rel");
            $href = $link->getAttribute("href");
            if (($rel != 'shortcut icon') || ($href == '')) {
                continue;
            }
            $link->setAttribute('href', self::prepare_url($href, 'png')); // Works for most image formats.
        }
    }

    /**
     * Fetch and fixes all img tags.
     */
    private function update_images() {
        $links = $this->dom->getElementsByTagName('img');

        foreach ($links as $link) {
            $src = $link->getAttribute("src");
            if ($src == '') {
                continue;
            }
            $link->setAttribute('src', self::prepare_url($src, 'png')); // Works for most image formats.
        }
    }

    /**
     * Saves the content of the URL into a file, returning the new URL.
     * @param string $url Input URL.
     * @param string $type Type of file.
     * @return string Output URL.
     */
    private function prepare_url($url, $type) {
        global $CFG;

        if (!preg_match('#^http(s)?://#', $url)) {
            debugging('Found a relative url ('.$url.') -- is it using moodle_url()?');
            return $url; // Leave hardcoded URLs as it is.
        }

        if (substr($url, 0, strlen($CFG->wwwroot)) !== $CFG->wwwroot) {
            return $url; // External URL, leave it.
        }

        // PHPUnit will use www.example.com as wwwroot and we don't to copy the file.
        if (PHPUNIT_TEST) {
            $contents = '';
        } else {
            $contents = file_get_contents($url);
        }
        $filename = sha1($contents).'.'.$type;
        $filepath = self::get_resources_folder().'/'.$filename;
        file_put_contents($filepath, $contents);

        if ($this->preview) {
            $filename = 'preview/'.$filename;
        }
        $url = (string)new moodle_url('/auth/outage/maintenance.php', ['file' => $filename]);
        return $url;
    }
}

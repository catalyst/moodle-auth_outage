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
use invalid_parameter_exception;
use invalid_state_exception;
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
     * @param outage|null $outage
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
        } else if (PHPUNIT_TEST) {
            $html = '<html></html>';
        } else {
            $html = file_get_contents($CFG->wwwroot.'/auth/outage/info.php?auth_outage_hide_warning=1&id='.$outage->id);
        }

        return self::create_from_html($html);
    }

    /**
     * Creates a page based on the given HTML.
     * @param string|null $html
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

    /** @var DOMDocument */
    protected $dom;

    /** @var bool */
    protected $preview = false;

    /**
     * maintenance_static_page constructor.
     * @param DOMDocument|null $dom
     * @throws coding_exception
     */
    public function __construct($dom) {
        if (!is_null($dom) && !($dom instanceof DOMDocument)) {
            throw new coding_exception('$dom must be null or an DOMDocument object.');
        }
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

        // If you change the path, also change file auth/outage/bootstrap.php as it does not use this reference.
        $dir = $CFG->dataroot.'/auth_outage/climaintenance';

        if ($this->preview) {
            $dir = $dir.'/preview';
        }
        return $dir;
    }

    /**
     * Generates the page.
     */
    public function generate() {
        $this->cleanup();

        if (!is_null($this->dom)) {
            $this->remove_script_tags();
            $this->update_link_stylesheet();
            $this->update_link_favicon();
            $this->update_images();

            $html = $this->dom->saveHTML();
            if (trim($html) == '') {
                // Should never happen, but just in case...
                throw new invalid_state_exception('Sanity check failed, $html is empty.');
            }
            file_put_contents($this->get_template_file(), $html);
        }
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
    private function cleanup() {
        $resources = $this->get_resources_folder();
        if (is_dir($resources)) {
            $this->delete_directory_recursively($resources);
        }

        $template = $this->get_template_file();
        if (is_file($template)) {
            unlink($template);
        }

        if (!is_null($this->dom)) {
            mkdir($resources, 0775, true);
        }
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
        $safedir = $this->get_resources_folder();
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
                $this->delete_directory_recursively($file);
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
            $filename = $this->save_url_file($href, 'css');
            if (is_null($filename)) {
                $url = $href; // Skipped, use original URL.
            } else {
                $url = $this->get_url_for_file($filename);
            }
            $link->setAttribute('href', $url);
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
            $link->setAttribute('href', $this->generate_file_url($href, 'png')); // Works for most image formats.
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
            $link->setAttribute('src', $this->generate_file_url($src, 'png')); // Works for most image formats.
        }
    }

    /**
     * Saves the content of the URL into a file, returning the new URL.
     * @param string $url Input URL.
     * @param string $type Type of file.
     * @return string Output URL.
     */
    private function generate_file_url($url, $type) {
        $filename = $this->save_url_file($url, $type);
        if (is_null($filename)) {
            return $url; // Skipped, use original URL.
        }
        return $this->get_url_for_file($filename);
    }

    /**
     * Creates a URL for a resource file.
     * @param string $filename
     * @return string
     */
    private function get_url_for_file($filename) {
        return (string)new moodle_url('/auth/outage/file.php', ['file' => $filename]);
    }

    /**
     * Saves the content of the URL into a file, returning the local filename.
     * @param string $url Input URL.
     * @param string $type Type of file.
     * @return string|null Output filename or null if skipped.
     */
    private function save_url_file($url, $type) {
        global $CFG;

        if (!preg_match('#^http(s)?://#', $url)) {
            debugging('Found a relative url ('.$url.') -- is it using moodle_url()?');
            return null; // Leave hardcoded URLs as it is.
        }

        if (substr($url, 0, strlen($CFG->wwwroot)) !== $CFG->wwwroot) {
            return null; // External URL, leave it.
        }

        // PHPUnit will use www.example.com as wwwroot and we don't to copy the file.
        if (PHPUNIT_TEST) {
            $contents = '';
        } else {
            $contents = file_get_contents($url);
        }
        $filename = sha1($contents).'.'.$type;
        $filepath = $this->get_resources_folder().'/'.$filename;
        file_put_contents($filepath, $contents);

        if ($this->preview) {
            $filename = 'preview/'.$filename;
        }
        return $filename;
    }
}

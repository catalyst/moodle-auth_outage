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
 * maintenance_static_page_io class.
 *
 * @package    auth_outage
 * @author     Daniel Thee Roperto <daniel.roperto@catalyst-au.net>
 * @copyright  2016 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace auth_outage\local\controllers;

use auth_outage\local\outagelib;
use coding_exception;
use finfo;
use invalid_parameter_exception;
use moodle_url;

/**
 * maintenance_static_page_io class.
 *
 * @package    auth_outage
 * @author     Daniel Thee Roperto <daniel.roperto@catalyst-au.net>
 * @copyright  2016 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class maintenance_static_page_io {
    /**
     * Checks if the given string starts with "http://" or "https://".
     * Also checks for "//" at the start of image, which setting_file_url still uses.
     *
     * @param string $url url string for check
     * @return bool
     */
    public static function is_url($url) {
        return ((bool) preg_match('#^http(s)?://#', $url) || (bool) preg_match('#^//#', $url));
    }

    /**
     * Tries to get the contents of the file or URL.
     * @param string $file File to get.
     * @return string Contents of $file or an empty string if failed.
     * @throws coding_exception
     */
    public static function file_get_data($file) {
        if (!is_string($file)) {
            throw new coding_exception('$file is not a string.');
        }

        if (self::is_url($file)) {
            $result = outagelib::fetch_page($file);
        } else {
            $result = ['contents' => @file_get_contents($file)];
            $result['mime'] = (new finfo(FILEINFO_MIME_TYPE))->buffer($result['contents']); // Try guessing it.
        }

        if ($result['contents'] === false) {
            debugging('Cannot fetch: '.$file);
            $result = ['contents' => '', 'mime' => 'unknown'];
        }
        return $result;
    }

    /** @var bool */
    protected $preview = false;

    /**
     * Sets preview
     * @param boolean $preview
     */
    public function set_preview($preview) {
        $this->preview = $preview;
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
     * Clean up the dataroot as needed.
     */
    public function cleanup() {
        $resources = $this->get_resources_folder();
        if (is_dir($resources)) {
            $this->delete_directory_recursively($resources);
        }

        $template = $this->get_template_file();
        if (is_file($template)) {
            unlink($template);
        }
    }

    /**
     * Clean up the dataroot as needed.
     */
    public function create_resources_path() {
        mkdir($this->get_resources_folder(), 0775, true);
    }

    /**
     * Saves the template file with the given string.
     * @param string $data
     * @throws coding_exception
     */
    public function save_template_file($data) {
        if (!is_string($data) || ($data === '')) {
            throw new coding_exception('$data is not a valid string.');
        }
        file_put_contents($this->get_template_file(), $data);
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
     * Saves the content of the URL into a file, returning the new URL.
     * @param string $url Input URL.
     * @return string Output URL.
     */
    public function generate_file_url($url) {
        $saved = $this->save_url_file($url);
        if (is_null($saved)) {
            return $url; // Skipped, use original URL.
        }
        return $this->get_url_for_file($saved['url']);
    }

    /**
     * Creates a URL for a resource file.
     * @param string $filename
     * @return string
     */
    public function get_url_for_file($filename) {
        return (string)new moodle_url('/auth/outage/file.php', ['file' => $filename]);
    }

    /**
     * Saves the content of the URL into a file, returning the local filename.
     * @param string $url Input URL.
     * @return array|null Output an array with the filename and url or null if skipped.
     */
    public function save_url_file($url) {
        global $CFG;

        if (!self::is_url($url)) {
            debugging('Found a relative url ('.$url.') -- is it using moodle_url()?');
            return null; // Leave hardcoded URLs as it is.
        }

        if (substr($url, 0, strlen($CFG->wwwroot)) !== $CFG->wwwroot) {
            return null; // External URL, leave it.
        }

        // PHPUnit does not expose a web interface to fetch, point to local file instead.
        if (PHPUNIT_TEST) {
            $url = str_replace($CFG->wwwroot, $CFG->dirroot, $url);
        }

        $data = self::file_get_data($url);

        $mime = trim(base64_encode($data['mime']), '=');
        $url = sha1($data['contents']).'.'.$mime;
        $filepath = $this->get_resources_folder().'/'.$url;
        file_put_contents($filepath, $data['contents']);

        if ($this->preview) {
            $url = 'preview/'.$url;
        }

        return ['file' => $filepath, 'url' => $url];
    }
}

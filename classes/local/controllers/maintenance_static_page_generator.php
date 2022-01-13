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
 * maintenance_static_page_generator class.
 *
 * @package    auth_outage
 * @author     Daniel Thee Roperto <daniel.roperto@catalyst-au.net>
 * @copyright  2016 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace auth_outage\local\controllers;

use auth_outage\local\outagelib;
use coding_exception;
use core_php_time_limit;
use DOMDocument;
use DOMElement;
use invalid_state_exception;
use moodle_url;

/**
 * maintenance_static_page_generator class.
 *
 * @package    auth_outage
 * @author     Daniel Thee Roperto <daniel.roperto@catalyst-au.net>
 * @copyright  2016 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class maintenance_static_page_generator {
    /** PATTERN
     * The pattern should match the attribute values that
     * go as 'url(xxxxx)', but make sure 'url(data:xxxxx)' is not
     * rewritten. Must be case insensitive to match 'URL(xxxxx)'.
     * It should be possible to specify other background attributes as
     * 'background: color url(xxxxx) no-repeat'.
     */
    protected const PATTERN = '/url\s*\(\s*[\'"]?(?![\'"]?data:)([^\s\'"]+)[\'"]?\s*\)/i';

    /** @var DOMDocument */
    protected $dom;

    /** @var maintenance_static_page_io */
    protected $io;

    /** @var int */
    protected $refreshtime = 300;

    /**
     * maintenance_static_page_generator constructor.
     *
     * @param DOMDocument|null           $dom
     * @param maintenance_static_page_io $io
     *
     * @throws coding_exception
     */
    public function __construct($dom, maintenance_static_page_io $io) {
        if (!is_null($dom) && !($dom instanceof DOMDocument)) {
            throw new coding_exception('$dom must be null or an DOMDocument object.');
        }
        $this->dom = $dom;
        $this->io = $io;
    }

    /**
     * Generates the page.
     */
    public function generate() {
        $this->io->cleanup();

        if (!is_null($this->dom)) {

            // This can take a while to process using repeated curls.
            core_php_time_limit::raise();

            $this->io->create_resources_path();

            $this->remove_script_tags();
            $this->add_meta_refresh();
            $this->update_link_stylesheet();
            $this->update_link_favicon();
            $this->update_images();
            $this->remove_configured_css_selectors();
            $this->update_inline_background_images();

            $html = $this->dom->saveHTML();
            if (trim($html) == '') {
                // Should never happen, but just in case...
                throw new invalid_state_exception('Sanity check failed, $html is empty.');
            }

            $this->io->save_template_file($html);
        }
    }

    /**
     * Gets maintenance_static_page_io.
     * @return maintenance_static_page_io
     */
    public function get_io() {
        return $this->io;
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
        $this->remove_nodes_from_dom($remove);
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
            $saved = $this->io->save_url_file($href);
            if (empty($saved['url'])) {
                $url = $href; // Skipped, use original URL.
            } else {
                $this->update_link_stylesheet_parse($saved['file'], dirname($href));
                $url = $this->io->get_url_for_file($saved['url']);
            }
            $link->setAttribute('href', $url);
        }
    }

    /**
     * Retrieves all URLs from file content using regular expressions.
     *
     * @param string $contents Content of the file
     * @return array Array of all matches in multi-dimensional array
     */
    public function get_urls_from_stylesheet($contents) {
        preg_match_all('#url\([\'"]?(?!data:)([^\'"\)]+)#', $contents, $matches);
        return $matches;
    }

    /**
     * Retrieves a URL from inline style using regular expressions.
     *
     * @param string $style Content of the style attribute
     * @return array Array containing match
     */
    public function get_url_from_inline_style($style) {
        preg_match(self::PATTERN, $style, $match);
        return $match;
    }


    /**
     * Checks for urls inside filename.
     *
     * @param string $filename
     * @param string $baseref
     */
    private function update_link_stylesheet_parse($filename, $baseref) {
        global $CFG;

        $contents = file_get_contents($filename);
        $matches = $this->get_urls_from_stylesheet($contents);

        foreach ($matches[1] as $originalurl) {
            // Allow incomplete URLs in CSS, assume it is from moodle root.
            if (maintenance_static_page_io::is_url($originalurl)) {
                $fullurl = $originalurl;
            } else if ($originalurl[0] == '/') {
                $rooturl = parse_url($CFG->wwwroot);
                $fullurl = $rooturl['scheme'].'://'.$rooturl['host'].$originalurl;
            } else {
                $fullurl = $baseref.'/'.$originalurl;
            }

            $saved = $this->io->save_url_file($fullurl);
            if (!is_null($saved)) {
                $finalurl = $this->io->get_url_for_file($saved['url']);
                $contents = str_replace($originalurl, $finalurl, $contents);
            }
        }

        file_put_contents($filename, $contents);
    }

    /**
     * Fetch and fixes the favicon link tag.
     */
    private function update_link_favicon() {
        $links = $this->dom->getElementsByTagName('link');

        foreach ($links as $link) {
            $rel = $link->getAttribute("rel");
            $href = $link->getAttribute("href");
            if (($rel == 'shortcut icon') && ($href != '')) {
                if (!maintenance_static_page_io::is_url($href)) {
                    $href = (string) new moodle_url($href);
                }
                $link->setAttribute('href', $this->io->generate_file_url($href)); // Works for most image formats.
            }
        }
    }

    /**
     * Fetch and fixes all img tags.
     */
    private function update_images() {
        $links = $this->dom->getElementsByTagName('img');

        foreach ($links as $link) {
            $src = $link->getAttribute("src");
            if ($src != '') {
                if (!maintenance_static_page_io::is_url($src)) {
                    $src = (string) new moodle_url($src);
                }
                $link->setAttribute('src', $this->io->generate_file_url($src)); // Works for most image formats.
            }
        }
    }

    /**
     * Fetch and fixes all inline background images.
     */
    private function update_inline_background_images() {
        global $CFG;
        $xpath = new \DOMXPath($this->dom);
        $elements = $xpath->query("//*[contains(@style,'background')]");

        foreach ($elements as $element) {
            $style = $element->getAttribute("style");
            $matches = $this->get_url_from_inline_style($style);
            if (isset($matches[1])) {
                // Allow incomplete URLs in style, assume it is from moodle root.
                if (maintenance_static_page_io::is_url($matches[1])) {
                    $fullurl = $matches[1];
                } else {
                    $fullurl = (string) new moodle_url($matches[1]);
                }
                $newurl = $this->io->generate_file_url($fullurl);
                $updated = preg_replace(self::PATTERN, ' url('.$newurl.') ', $style);
                $element->setAttribute('style', $updated);
            }
        }
    }

    /**
     * Remove from DOM the CSS selectores defined in the plugin settings.
     */
    private function remove_configured_css_selectors() {
        $selectors = explode("\n", outagelib::get_config()->remove_selectors);

        $remove = [];

        foreach ($selectors as $selector) {
            // We only support a simple .class or #id -- if support for full selectors must be added
            // then I suggest checking http://code.google.com/p/phpquery/ on how to implement it.
            $selector = trim($selector);
            if ($selector == '') {
                continue;
            }
            $remove = array_merge($remove, $this->fetch_elements_by_selector($selector));
        }

        $this->remove_nodes_from_dom($remove);
    }

    /**
     * Removes the nodes from the DOM.
     *
     * @param DOMElement[] $nodes
     */
    private function remove_nodes_from_dom(array $nodes) {
        foreach ($nodes as $node) {
            $node->parentNode->removeChild($node);
        }
    }

    /**
     * Fetches all elements based on the given selector.
     *
     * @param string $selector element selector
     *
     * @return DOMElement[]
     */
    private function fetch_elements_by_selector($selector) {
        $type = $selector[0];
        $selector = substr($selector, 1); // Remove '.' or '#'.
        if ($type == '#') {
            $element = $this->dom->getElementById($selector);
            return is_null($element) ? [] : [$element];
        } else {
            return $this->fetch_elements_by_class($selector);
        }
    }

    /**
     * Fetch all elements which contains the given class.
     *
     * @param string $class element class
     *
     * @return DOMElement[]
     */
    private function fetch_elements_by_class($class) {
        $matches = [];
        $elements = $this->dom->getElementsByTagName('*');
        foreach ($elements as $element) {
            $elementclasses = explode(' ', $element->getAttribute('class'));
            if (in_array($class, $elementclasses)) {
                $matches[] = $element;
            }
        }
        return $matches;
    }

    /**
     * Adds meta refresh to head element.
     */
    private function add_meta_refresh() {
        $meta = $this->dom->createElement('meta');
        $meta->setAttribute('http-equiv', 'refresh');
        $meta->setAttribute('content', $this->refreshtime);

        $head = $this->dom->getElementsByTagName('head')->item(0);
        if ($head) {
            $head->appendChild($meta);
        }
    }

    /**
     * Gets refresh time.
     * @return int
     */
    public function get_refresh_time() {
        return $this->refreshtime;
    }

    /**
     * Sets refresh time.
     * @param int $refreshtime
     */
    public function set_refresh_time($refreshtime) {
        $this->refreshtime = $refreshtime;
    }
}

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
 * maintenance_static_page_test task class.
 *
 * @package    auth_outage
 * @author     Daniel Thee Roperto <daniel.roperto@catalyst-au.net>
 * @copyright  2016 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use auth_outage\local\controllers\maintenance_static_page;

defined('MOODLE_INTERNAL') || die();
require_once(__DIR__.'/../../base_testcase.php');

/**
 * maintenance_static_page_test class.
 *
 * @package    auth_outage
 * @author     Daniel Thee Roperto <daniel.roperto@catalyst-au.net>
 * @copyright  2016 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @SuppressWarnings(public) Allow as many methods as needed.
 */
class maintenance_static_page_test extends auth_outage_base_testcase {
    /**
     * Ensures the template file does not exist when starting a test.
     */
    public function setUp() {
        $file = maintenance_static_page::get_template_file();
        if (file_exists($file)) {
            if (is_file($file)) {
                unlink($file);
            } else {
                self::fail('Invalid temp file: '.$file);
            }
        }
    }

    public function test_templatefile() {
        global $CFG;
        self::assertSame($CFG->dataroot.'/climaintenance.template.html', maintenance_static_page::get_template_file());
    }

    public function test_createfromoutage() {
        // How to fetch a page from PHPUnit environment?
    }

    public function test_createfromhtml() {
        $html = "<!DOCTYPE html>\n<html><head><title>Title</title></head><body>Content</body></html>";
        maintenance_static_page::create_from_html($html);
        $generated = trim(file_get_contents(maintenance_static_page::get_template_file()));
        self::assertSame($html, $generated);
    }

    public function test_removescripttags() {
        $html = "<!DOCTYPE html>\n".
                '<html><head><script type="text/javascript" src="http://xyz"></script><title>Title</title></head>'.
                '<body>Content<script> a < 5; x > 3</script></body></html>';
        maintenance_static_page::create_from_html($html);

        $generated = file_get_contents(maintenance_static_page::get_template_file());
        self::assertNotContains('<script', $generated);
    }

    public function test_updatelinkstylesheet() {
        $link1 = (string)new moodle_url('/example.css');
        $link2 = (string)new moodle_url('/auth/outage/stylesheet');
        $link3 = 'http://google.com/coolstyle.css';
        $html = "<!DOCTYPE html>\n".
                '<html><head><link href="'.$link1.'" rel="stylesheet" /><title>Title</title></head>'.
                '<body><link rel="stylesheet" href="'.$link2.'">Content<link rel="stylesheet" href="'.$link3.'"></body></html>';
        maintenance_static_page::create_from_html($html);
        $generated = file_get_contents(maintenance_static_page::get_template_file());

        self::assertContains('http://www.example.com/moodle/auth/outage/maintenance.php/', $generated);
        self::assertNotContains($link1, $generated);
        self::assertNotContains($link2, $generated);
        self::assertContains($link3, $generated);
    }

    public function test_updateimages() {
        $link1 = (string)new moodle_url('/example.png');
        $link2 = (string)new moodle_url('/auth/outage/imagefile');
        $link3 = 'http://google.com/coolstyle.css';
        $html = "<!DOCTYPE html>\n".
                '<html><head><img src="'.$link1.'" alt="an image" /><title>Title</title></head>'.
                '<body><img src="'.$link2.'">Content<img src="'.$link3.'" /></body></html>';
        maintenance_static_page::create_from_html($html);
        $generated = file_get_contents(maintenance_static_page::get_template_file());

        self::assertContains('http://www.example.com/moodle/auth/outage/maintenance.php/', $generated);
        self::assertNotContains($link1, $generated);
        self::assertNotContains($link2, $generated);
        self::assertContains($link3, $generated);
    }

    public function test_updatelinkfavicon() {
        $link = (string)new moodle_url('/favicon.jpg');
        $html = "<!DOCTYPE html>\n".
                '<html><head><title>Title</title><link rel="shortcut icon" href="'.$link.'""></head>'.
                '<body>Content</body></html>';
        maintenance_static_page::create_from_html($html);
        $generated = file_get_contents(maintenance_static_page::get_template_file());

        self::assertNotContains($link, $generated);
        self::assertContains('http://www.example.com/moodle/auth/outage/maintenance.php/', $generated);
    }
}

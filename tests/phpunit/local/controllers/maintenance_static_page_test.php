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
use auth_outage\local\controllers\maintenance_static_page_io;
use auth_outage\task\update_static_page;

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
 * @SuppressWarnings(methods) Allow as many methods as needed.
 */
class maintenance_static_page_test extends auth_outage_base_testcase {
    public function test_templatefile() {
        global $CFG;
        $page = maintenance_static_page::create_from_html('<html></html>');
        self::assertSame($CFG->dataroot.'/climaintenance.template.html', $page->get_io()->get_template_file());
        $page->get_io()->set_preview(true);
        self::assertSame($CFG->dataroot.'/auth_outage/climaintenance/preview/climaintenance.html',
            $page->get_io()->get_template_file());
    }

    public function test_resourcesfolder() {
        global $CFG;
        $page = maintenance_static_page::create_from_html('<html></html>');
        self::assertSame($CFG->dataroot.'/auth_outage/climaintenance', $page->get_io()->get_resources_folder());
        $page->get_io()->set_preview(true);
        self::assertSame($CFG->dataroot.'/auth_outage/climaintenance/preview', $page->get_io()->get_resources_folder());
    }

    public function test_createfromoutage() {
        // How to fetch a page from PHPUnit environment?
    }

    public function test_createfromhtml() {
        $html = "<!DOCTYPE html>\n<html><head><title>Title</title></head><body>Content</body></html>";
        $expected = "<!DOCTYPE html>\n<html><head><title>Title</title><meta http-equiv=\"refresh\" content=\"300\">".
                    "</head><body>Content</body></html>";
        self::assertSame($expected, $this->generated_page_html($html));
    }

    public function test_removescripttags() {
        $html = "<!DOCTYPE html>\n".
                '<html><head><script type="text/javascript" src="http://xyz"></script><title>Title</title></head>'.
                '<body>Content<script> a < 5; x > 3</script></body></html>';
        maintenance_static_page::create_from_html($html)->generate();

        $generated = $this->generated_page_html($html);
        self::assertNotContains('<script', $generated);
    }

    public function test_updatelinkstylesheet() {
        $localcsslink = $this->get_fixture_path('simple.css');
        $externalcsslink = 'http://google.com/coolstuff.css';
        $html = "<!DOCTYPE html>\n".
                '<html><head><link href="'.$localcsslink.'" rel="stylesheet" /><title>Title</title></head>'.
                '<body>Content<link rel="stylesheet" href="'.$externalcsslink.'"></body></html>';
        $generated = $this->generated_page_html($html);

        self::assertContains('www.example.com/moodle/auth/outage/file.php?file=', $generated);
        self::assertNotContains($localcsslink, $generated);
        self::assertContains($externalcsslink, $generated);
    }

    public function test_updatelinkstylesheet_urls() {
        $localcsslink = $this->get_fixture_path('withurls.css');
        $html = "<!DOCTYPE html>\n".
                '<html><head><link href="'.$localcsslink.'" rel="stylesheet" /><title>Title</title></head>'.
                '<body>Content</body></html>';
        $page = maintenance_static_page::create_from_html($html);
        $page->generate();

        // Check for css file.
        self::assertFileExists($page->get_io()->get_resources_folder().'/622ef6e83acfcb274cdf37bdb3bffa0923f9a7ad.dGV4dC9wbGFpbg');

        // Check for catalyst.png file referenced in url(..) of css.
        self::assertFileExists($page->get_io()->get_resources_folder().'/ff7f7f87a26a908fc72930eaefb6b57306361d16.aW1hZ2UvcG5n');
    }

    public function test_updatelinkstylesheet_urls_quoted() {
        $localcsslink = $this->get_fixture_path('withurls-quoted.css');
        $html = "<!DOCTYPE html>\n".
                '<html><head><link href="'.$localcsslink.'" rel="stylesheet" /><title>Title</title></head>'.
                '<body>Content</body></html>';
        $page = maintenance_static_page::create_from_html($html);
        $page->generate();

        // Check for css file.
        self::assertFileExists($page->get_io()->get_resources_folder().'/1d84b6d321fef780237f84834b7316c079221a31.dGV4dC9wbGFpbg');

        // Check for catalyst.png file referenced in url(..) of css.
        self::assertFileExists($page->get_io()->get_resources_folder().'/ff7f7f87a26a908fc72930eaefb6b57306361d16.aW1hZ2UvcG5n');
    }

    public function test_updatelinkstylesheet_urls_subdir() {
        $localcsslink = $this->get_fixture_path('subdir/withurls-subdir.css');
        $html = "<!DOCTYPE html>\n".
                '<html><head><link href="'.$localcsslink.'" rel="stylesheet" /><title>Title</title></head>'.
                '<body>Content</body></html>';
        $page = maintenance_static_page::create_from_html($html);
        $page->generate();

        // Check for css file.
        self::assertFileExists($page->get_io()->get_resources_folder().'/beb44281e23b9d872056bf0230cea34535e8cdea.dGV4dC9wbGFpbg');

        // Check for file referenced in url(..) of css.
        self::assertFileExists($page->get_io()->get_resources_folder().'/a02a8a442fa82d5205ffb24722d9df7f35161f56.dGV4dC9wbGFpbg');
    }

    public function test_updateimages() {
        $localimglink = $this->get_fixture_path('catalyst.png');
        $externalimglink = 'http://google.com/coolstyle.css';
        $html = "<!DOCTYPE html>\n".
                '<html><head><title>Title</title></head>'.
                '<body><img src="'.$localimglink.'">Content<img src="'.$externalimglink.'" /></body></html>';
        $generated = $this->generated_page_html($html);

        self::assertContains('www.example.com/moodle/auth/outage/file.php?file=', $generated);
        self::assertNotContains($localimglink, $generated);
        self::assertContains($externalimglink, $generated);
    }

    public function test_updatelinkfavicon() {
        $link = $this->get_fixture_path('catalyst.png');
        $html = "<!DOCTYPE html>\n".
                '<html><head><title>Title</title><link rel="shortcut icon" href="'.$link.'""></head>'.
                '<body>Content</body></html>';
        $generated = $this->generated_page_html($html);

        self::assertNotContains($link, $generated);
        self::assertContains('www.example.com/moodle/auth/outage/file.php?file=', $generated);
    }

    public function test_previewpath() {
        $link = $this->get_fixture_path('catalyst.png');
        $html = "<!DOCTYPE html>\n".
                '<html><head><title>Title</title><link rel="shortcut icon" href="'.$link.'""></head>'.
                '<body>Content</body></html>';
        $page = maintenance_static_page::create_from_html($html);
        $page->get_io()->set_preview(true);
        $page->generate();
        $generated = trim(file_get_contents($page->get_io()->get_template_file()));

        self::assertNotContains($link, $generated);
        self::assertContains('www.example.com/moodle/auth/outage/file.php?file=preview%2F', $generated);
    }

    /**
     * Generates the maintenance page (not using preview mode).
     *
     * @param string $html Input HTML.
     *
     * @return string Output HTML.
     */
    private function generated_page_html($html) {
        $page = maintenance_static_page::create_from_html($html);
        $page->generate();
        $generated = trim(file_get_contents($page->get_io()->get_template_file()));
        return $generated;
    }

    /**
     * Checks if we can create and execute a task to update outage pages.
     */
    public function test_tasks() {
        $this->resetAfterTest(true);
        $task = new update_static_page();
        self::assertNotEmpty($task->get_name());
        $task->execute();
    }

    /**
     * Tests updating the static page when there is no outage but the file existed before.
     */
    public function test_updatestaticpage_hasfile() {
        global $CFG;
        $file = $CFG->dataroot.'/climaintenance.template.html';
        touch($file);
        self::assertFileExists($file);
        maintenance_static_page::create_from_outage(null)->generate();
        self::assertFileNotExists($file);
    }

    public function test_createdfile() {
        global $CFG;

        $link = $this->get_fixture_path('catalyst.png');
        $html = "<!DOCTYPE html>\n".
                '<html><head><title>Title</title></head>'.
                '<body>Content<img src="'.$link.'" /></body></html>';
        $page = maintenance_static_page::create_from_html($html);
        $page->generate();

        // This checks if content is correct and mime type is correct from the encoded name.
        $file = $page->get_io()->get_resources_folder().'/ff7f7f87a26a908fc72930eaefb6b57306361d16.aW1hZ2UvcG5n';
        self::assertFileExists($file);

        // We can still assert the contents really match, not just the hash.
        $found = file_get_contents($file);
        $expected = file_get_contents($CFG->dirroot.'/auth/outage/tests/phpunit/local/controllers/fixtures/catalyst.png');
        self::assertSame($found, $expected);
    }

    /**
     * Gets a fixture file for this test case.
     *
     * @param $file
     *
     * @return string
     */
    private function get_fixture_path($file) {
        return (string)new moodle_url('/auth/outage/tests/phpunit/local/controllers/fixtures/'.$file);
    }

    public function test_invalid_string_saving_template_empty() {
        $io = new maintenance_static_page_io();
        $this->set_expected_exception('coding_exception');
        $io->save_template_file('');
    }

    public function test_invalid_string_saving_template_nostring() {
        $io = new maintenance_static_page_io();
        $this->set_expected_exception('coding_exception');
        $io->save_template_file(50);
    }

    public function test_get_url_for_file() {
        $io = new maintenance_static_page_io();
        self::assertContains('www.example.com/moodle/auth/outage/file.php?file=img.png', $io->get_url_for_file('img.png'));
    }

    public function test_is_url() {
        self::assertTrue(maintenance_static_page_io::is_url('http://catalyst.net.nz'));
        self::assertTrue(maintenance_static_page_io::is_url('https://www.catalyst-au.net/'));
        self::assertFalse(maintenance_static_page_io::is_url('/homepage'));
        self::assertFalse(maintenance_static_page_io::is_url('file://homepage'));
    }

    public function test_file_get_data() {
        $file = __DIR__.'/fixtures/catalyst.png';
        $found = maintenance_static_page_io::file_get_data($file);
        self::assertSame(file_get_contents($file), $found['contents']);
        self::assertSame('image/png', $found['mime']);
    }

    public function test_file_get_data_invalidfile() {
        $found = maintenance_static_page_io::file_get_data(__DIR__.'/fixtures/invalidfile');
        self::assertSame('', $found['contents']);
        self::assertSame('unknown', $found['mime']);
        self::assertCount(1, phpunit_util::get_debugging_messages());
        phpunit_util::reset_debugging();
    }

    public function test_file_get_data_invalidfilename() {
        $this->set_expected_exception('coding_exception');
        maintenance_static_page_io::file_get_data(200);
    }

    public function test_remove_css_selector() {
        $this->resetAfterTest(true);
        $html = "<!DOCTYPE html>\n".
                '<html><head><title>Title</title></head>'.
                '<body>Content<b class="removeme">Goodbye cruel world.</b></body></html>';
        set_config('remove_selectors', '.removeme', 'auth_outage');
        $generated = $this->generated_page_html($html);

        self::assertNotContains('removeme', $generated);
        self::assertNotContains('Goodbye cruel world', $generated);
    }

    public function test_remove_css_selector_id() {
        $this->resetAfterTest(true);
        $html = "<!DOCTYPE html>\n".
                '<html><head><title>Title</title></head>'.
                '<body>Content<b id="removeme">Goodbye cruel world.</b></body></html>';
        set_config('remove_selectors', '#removeme', 'auth_outage');
        $generated = $this->generated_page_html($html);

        self::assertNotContains('removeme', $generated);
        self::assertNotContains('Goodbye cruel world', $generated);
    }

    public function test_remove_css_selector_with_multiline() {
        $this->resetAfterTest(true);
        $html = "<!DOCTYPE html>\n".
                '<html><head><title>Title</title></head>'.
                '<body>'.
                '<b class="deleteme">Goodbye cruel world.</b>'.
                '<b class="removeme">Goodbye cruel world.</b>'.
                '</body></html>';
        set_config('remove_selectors', ".removeme\n.deleteme", 'auth_outage');
        $generated = $this->generated_page_html($html);

        self::assertNotContains('removeme', $generated);
        self::assertNotContains('deleteme', $generated);
        self::assertNotContains('Goodbye cruel world', $generated);
    }

    public function test_remove_css_selector_needing_trim() {
        $this->resetAfterTest(true);
        $html = "<!DOCTYPE html>\n".
                '<html><head><title>Title</title></head>'.
                '<body>'.
                '<b class="deleteme">Goodbye cruel world.</b>'.
                '<b class="removeme">Goodbye cruel world.</b>'.
                '</body></html>';
        set_config('remove_selectors', " .removeme     \n    .deleteme   ", 'auth_outage');
        $generated = $this->generated_page_html($html);

        self::assertNotContains('removeme', $generated);
        self::assertNotContains('deleteme', $generated);
        self::assertNotContains('Goodbye cruel world', $generated);
    }

    public function test_remove_css_selector_with_empty_line() {
        $this->resetAfterTest(true);
        $html = "<!DOCTYPE html>\n".
                '<html><head><title>Title</title></head>'.
                '<body>'.
                '<b class="deleteme">Goodbye cruel world.</b>'.
                '<b class="removeme">Goodbye cruel world.</b>'.
                '</body></html>';
        set_config('remove_selectors', "\n\n.removeme\n\n\n\n.deleteme\n\n", 'auth_outage');
        $generated = $this->generated_page_html($html);

        self::assertNotContains('removeme', $generated);
        self::assertNotContains('deleteme', $generated);
        self::assertNotContains('Goodbye cruel world', $generated);
    }

    public function test_remove_css_selector_with_invalid_id() {
        $this->resetAfterTest(true);
        $html = "<!DOCTYPE html>\n".
                '<html><head><title>Title</title></head>'.
                '<body>Content<b id="removeme">Goodbye cruel world.</b></body></html>';
        set_config('remove_selectors', '#invalidid', 'auth_outage');
        $generated = $this->generated_page_html($html);

        self::assertContains('removeme', $generated);
        self::assertContains('Goodbye cruel world', $generated);
    }

    public function test_meta_refresh_5minutes() {
        $this->resetAfterTest(true);
        $html = "<!DOCTYPE html>\n".
                '<html><head><title>Title</title></head>'.
                '<body>Content<b id="removeme">Goodbye cruel world.</b></body></html>';
        set_config('remove_selectors', '#invalidid', 'auth_outage');
        $generated = $this->generated_page_html($html);

        self::assertContains('<meta http-equiv="refresh" content="300">', $generated);
    }

    public function test_meta_refresh_maximum_5seconds() {
        $this->resetAfterTest(true);
        $html = "<!DOCTYPE html>\n".
                '<html><head><title>Title</title></head>'.
                '<body>Content<b id="removeme">Goodbye cruel world.</b></body></html>';
        set_config('remove_selectors', '#invalidid', 'auth_outage');
        $page = maintenance_static_page::create_from_html($html);
        $page->set_max_refresh_time(5);
        $page->generate();
        $generated = trim(file_get_contents($page->get_io()->get_template_file()));
        return $generated;

        self::assertContains('<meta http-equiv="refresh" content="5">', $generated);
    }
}

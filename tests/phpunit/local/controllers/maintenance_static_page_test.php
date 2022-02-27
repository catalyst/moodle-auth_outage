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
use auth_outage\local\controllers\maintenance_static_page_generator;
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
 */
class auth_outage_maintenance_static_page_test extends auth_outage_base_testcase {
    /**
     * Test template file.
     */
    public function test_templatefile() {
        global $CFG;
        $page = maintenance_static_page::create_from_html('<html></html>');
        self::assertSame($CFG->dataroot.'/climaintenance.template.html', $page->get_io()->get_template_file());
        $page->get_io()->set_preview(true);
        self::assertSame($CFG->dataroot.'/auth_outage/climaintenance/preview/climaintenance.html',
            $page->get_io()->get_template_file());
    }

    /**
     * Test resources folder.
     */
    public function test_resourcesfolder() {
        global $CFG;
        $page = maintenance_static_page::create_from_html('<html></html>');
        self::assertSame($CFG->dataroot.'/auth_outage/climaintenance', $page->get_io()->get_resources_folder());
        $page->get_io()->set_preview(true);
        self::assertSame($CFG->dataroot.'/auth_outage/climaintenance/preview', $page->get_io()->get_resources_folder());
    }

    /**
     * Test create from outage.
     */
    public function test_createfromoutage() {
        // How to fetch a page from PHPUnit environment?
    }

    /**
     * Test create from HTML.
     */
    public function test_createfromhtml() {
        $html = "<!DOCTYPE html>\n<html><head><title>Title</title></head><body>Content</body></html>";
        $expected = "<!DOCTYPE html>\n<html><head><title>Title</title><meta http-equiv=\"refresh\" content=\"300\">".
                    "</head><body>Content</body></html>";
        self::assertSame($expected, $this->generated_page_html($html));
    }

    /**
     * Test remove script tags.
     */
    public function test_removescripttags() {
        $html = "<!DOCTYPE html>\n".
                '<html><head><script type="text/javascript" src="http://xyz"></script><title>Title</title></head>'.
                '<body>Content<script> a < 5; x > 3</script></body></html>';
        maintenance_static_page::create_from_html($html)->generate();

        $generated = $this->generated_page_html($html);
        self::assertStringNotContainsString('<script', $generated);
    }

    /**
     * Test remove script tags.
     */
    public function test_updatelinkstylesheet() {
        $localcsslink = $this->get_fixture_path('simple.css');
        $externalcsslink = 'http://google.com/coolstuff.css';
        $html = "<!DOCTYPE html>\n".
                '<html><head><link href="'.$localcsslink.'" rel="stylesheet" /><title>Title</title></head>'.
                '<body>Content<link rel="stylesheet" href="'.$externalcsslink.'"></body></html>';
        $generated = $this->generated_page_html($html);

        self::assertStringContainsString('www.example.com/moodle/auth/outage/file.php?file=', $generated);
        self::assertStringNotContainsString($localcsslink, $generated);
        self::assertStringContainsString($externalcsslink, $generated);
    }

    /**
     * Test update link style sheet urls.
     */
    public function test_updatelinkstylesheet_urls() {
        $localcsslink = $this->get_fixture_path('withurls.css');
        $html = "<!DOCTYPE html>\n".
                '<html><head><link href="'.$localcsslink.'" rel="stylesheet" /><title>Title</title></head>'.
                '<body>Content</body></html>';
        $page = maintenance_static_page::create_from_html($html);
        $page->generate();

        // Check for css file.
        self::assertFileExists($page->get_io()->get_resources_folder().'/d8643101d96b093e642b15544e4d1f7815b5ba55.dGV4dC9wbGFpbg');

        // Check for catalyst.png file referenced in url(..) of css.
        self::assertFileExists($page->get_io()->get_resources_folder().'/ff7f7f87a26a908fc72930eaefb6b57306361d16.aW1hZ2UvcG5n');
    }

    /**
     * Test update link style sheet urls quoted.
     */
    public function test_updatelinkstylesheet_urls_quoted() {
        $localcsslink = $this->get_fixture_path('withurls-quoted.css');
        $html = "<!DOCTYPE html>\n".
                '<html><head><link href="'.$localcsslink.'" rel="stylesheet" /><title>Title</title></head>'.
                '<body>Content</body></html>';
        $page = maintenance_static_page::create_from_html($html);
        $page->generate();

        // Check for css file.
        self::assertFileExists($page->get_io()->get_resources_folder().'/9fe2374b03953e1949d54ab750be2d8706891c03.dGV4dC9wbGFpbg');

        // Check for catalyst.png file referenced in url(..) of css.
        self::assertFileExists($page->get_io()->get_resources_folder().'/ff7f7f87a26a908fc72930eaefb6b57306361d16.aW1hZ2UvcG5n');
    }

    /**
     * Test update link style sheet urls with sub dir.
     */
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

     /**
      * Test update images to file.php style link.
      */
    public function test_updateimages() {
        $localimglink = $this->get_fixture_path('catalyst.png');
        $externalimglink = 'http://google.com/coolstyle.css';
        $html = "<!DOCTYPE html>\n".
                '<html><head><title>Title</title></head>'.
                '<body><img src="'.$localimglink.'">Content<img src="'.$externalimglink.'" /></body></html>';
        $generated = $this->generated_page_html($html);

        self::assertStringContainsString('www.example.com/moodle/auth/outage/file.php?file=', $generated);
        self::assertStringNotContainsString($localimglink, $generated);
        self::assertStringContainsString($externalimglink, $generated);
    }

     /**
      * Test update favicon to file.php style link.
      */
    public function test_updatelinkfavicon() {
        $link = $this->get_fixture_path('catalyst.png');
        $html = "<!DOCTYPE html>\n".
                '<html><head><title>Title</title><link rel="shortcut icon" href="'.$link.'""></head>'.
                '<body>Content</body></html>';
        $generated = $this->generated_page_html($html);

        self::assertStringNotContainsString($link, $generated);
        self::assertStringContainsString('www.example.com/moodle/auth/outage/file.php?file=', $generated);
    }


    /**
     * Data provider for test_update_inline_background_images
     * @return array
     */
    public function test_update_inline_background_images_provider() {
        return [
            // Empty string.
            ["", false],
            // URLs that should be retrieved.
            ["color: #FF00FF; background: lightblue url(/pluginfile.php/1/theme_custom/banner/251298630/0001.png) no-repeat", true],
            ["background: lightblue url(https://www.example.com/moodle/pluginfile.php/1/theme_custom/banner/251298630/0001.png) no-repeat", true],
            ["background:url('https://www.example.com/moodle/pluginfile.php/1/theme_custom/banner/251298630/0001.png')", true],
            ["background-image : url( /pix/help.png);", true],
            ["background-image: url ('/pix/help.png')", true],
            // URLs that should not be retrieved.
            ["background-image:url(data:image/gif;base64,R0lGODlhYADIAP=)", false],
            ["background-image:url('data:image/gif;base64,R0lGODlhYADIAP=')", false]
        ];
    }

    /**
     * Tests update_inline_background_images() method to update the background images.
     *
     * @dataProvider test_update_inline_background_images_provider
     * @param string $stylecontent Content of the style to test
     * @param bool $rewrite Flag if URL should be rewritten
     * @throws coding_exception
     */
    public function test_update_inline_background_images($stylecontent, $rewrite) {
        global $CFG;
        $this->resetAfterTest(true);
        $generator = new maintenance_static_page_generator(new DOMDocument(), new maintenance_static_page_io());

        $html = '<!DOCTYPE html>\n'.
            '<html><head><title>Title</title></head>'.
            '<body><div style="'.$stylecontent.'">Content</div></body></html>';

        // Temporarily disable debugging to prevent errors because file does not exist
        $debuglevel = $CFG->debug;
        $CFG->debug = '';
        $generated = $this->generated_page_html($html);
        // Restore debugging level
        $CFG->debug = $debuglevel;
        $matches = $generator->get_url_from_inline_style($stylecontent);
        if ($rewrite) {
            self::assertStringNotContainsString($matches[1], $generated);
            self::assertStringContainsString('www.example.com/moodle/auth/outage/file.php?file=', $generated);
            self::assertIsArray($matches);
        } else {
            self::assertStringContainsString($stylecontent, $generated);
        }
    }

     /**
      * Test update preview path to file.php style link.
      */
    public function test_previewpath() {
        $link = $this->get_fixture_path('catalyst.png');
        $html = "<!DOCTYPE html>\n".
                '<html><head><title>Title</title><link rel="shortcut icon" href="'.$link.'""></head>'.
                '<body>Content</body></html>';
        $page = maintenance_static_page::create_from_html($html);
        $page->get_io()->set_preview(true);
        $page->generate();
        $generated = trim(file_get_contents($page->get_io()->get_template_file()));

        self::assertStringNotContainsString($link, $generated);
        self::assertStringContainsString('www.example.com/moodle/auth/outage/file.php?file=preview%2F', $generated);
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
        // Backwards compatibility with older PHPUnit - use old assertFile method.
        if (method_exists($this, 'assertFileDoesNotExist')) {
            self::assertFileDoesNotExist($file);
        } else {
            self::assertFileNotExists($file);
        }
    }

    /**
     * Tests created file.
     */
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
     * @param string $file file name
     *
     * @return string
     */
    private function get_fixture_path($file) {
        return (string)new moodle_url('/auth/outage/tests/phpunit/local/controllers/fixtures/'.$file);
    }

    /**
     * Test saving empty string for template file.
     */
    public function test_invalid_string_saving_template_empty() {
        $io = new maintenance_static_page_io();
        $this->set_expected_exception('coding_exception');
        $io->save_template_file('');
    }

    /**
     * Test saving non string for template file.
     */
    public function test_invalid_string_saving_template_nostring() {
        $io = new maintenance_static_page_io();
        $this->set_expected_exception('coding_exception');
        $io->save_template_file(50);
    }

    /**
     * Test get url for file.
     */
    public function test_get_url_for_file() {
        $io = new maintenance_static_page_io();
        self::assertStringContainsString('www.example.com/moodle/auth/outage/file.php?file=img.png', $io->get_url_for_file('img.png'));
    }

    /**
     * Return array of url data provider and true or false.
     */
    public function is_url_dataprovider() {
        return [
            [true, 'http://catalyst.net.nz'],
            [true, 'https://www.catalyst-au.net/'],
            [false, '/homepage'],
            [false, 'file://homepage'],
            [true, '//catalyst-au.net/img/test.jpg'],
            [false, '://www.catalyst-au.net/img/test.jpg']
        ];
    }

    /**
     * Test if it is url
     * @dataProvider is_url_dataprovider
     * @param string $result expected result
     * @param string $url url to be checked
     */
    public function test_is_url($result, $url) {
        self::assertEquals($result, maintenance_static_page_io::is_url($url));
    }

    /**
     * Test file get_data.
     */
    public function test_file_get_data() {
        $file = __DIR__.'/fixtures/catalyst.png';
        $found = maintenance_static_page_io::file_get_data($file);
        self::assertSame(file_get_contents($file), $found['contents']);
        self::assertSame('image/png', $found['mime']);
    }

    /**
     * Test invalid file get_data.
     */
    public function test_file_get_data_invalidfile() {
        $found = maintenance_static_page_io::file_get_data(__DIR__.'/fixtures/invalidfile');
        self::assertSame('', $found['contents']);
        self::assertSame('unknown', $found['mime']);
        self::assertCount(1, $this->getDebuggingMessages());
        $this->resetDebugging();
    }

    /**
     * Test invalid file get_data.
     */
    public function test_file_get_data_invalidfilename() {
        $this->set_expected_exception('coding_exception');
        maintenance_static_page_io::file_get_data(200);
    }

    /**
     * Test remove css selector.
     */
    public function test_remove_css_selector() {
        $this->resetAfterTest(true);
        $html = "<!DOCTYPE html>\n".
                '<html><head><title>Title</title></head>'.
                '<body>Content<b class="removeme">Goodbye cruel world.</b></body></html>';
        set_config('remove_selectors', '.removeme', 'auth_outage');
        $generated = $this->generated_page_html($html);

        self::assertStringNotContainsString('removeme', $generated);
        self::assertStringNotContainsString('Goodbye cruel world', $generated);
    }

    /**
     * Test remove css selector id.
     */
    public function test_remove_css_selector_id() {
        $this->resetAfterTest(true);
        $html = "<!DOCTYPE html>\n".
                '<html><head><title>Title</title></head>'.
                '<body>Content<b id="removeme">Goodbye cruel world.</b></body></html>';
        set_config('remove_selectors', '#removeme', 'auth_outage');
        $generated = $this->generated_page_html($html);

        self::assertStringNotContainsString('removeme', $generated);
        self::assertStringNotContainsString('Goodbye cruel world', $generated);
    }

    /**
     * Test remove css selector with multi lines.
     */
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

        self::assertStringNotContainsString('removeme', $generated);
        self::assertStringNotContainsString('deleteme', $generated);
        self::assertStringNotContainsString('Goodbye cruel world', $generated);
    }

    /**
     * Test remove css selector needs trim.
     */
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

        self::assertStringNotContainsString('removeme', $generated);
        self::assertStringNotContainsString('deleteme', $generated);
        self::assertStringNotContainsString('Goodbye cruel world', $generated);
    }

    /**
     * Test remove css selector with empty line.
     */
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

        self::assertStringNotContainsString('removeme', $generated);
        self::assertStringNotContainsString('deleteme', $generated);
        self::assertStringNotContainsString('Goodbye cruel world', $generated);
    }

    /**
     * Test remove css selector with invalid id.
     */
    public function test_remove_css_selector_with_invalid_id() {
        $this->resetAfterTest(true);
        $html = "<!DOCTYPE html>\n".
                '<html><head><title>Title</title></head>'.
                '<body>Content<b id="removeme">Goodbye cruel world.</b></body></html>';
        set_config('remove_selectors', '#invalidid', 'auth_outage');
        $generated = $this->generated_page_html($html);

        self::assertStringContainsString('removeme', $generated);
        self::assertStringContainsString('Goodbye cruel world', $generated);
    }

    /**
     * Test meta refresh 5 minutes.
     */
    public function test_meta_refresh_5minutes() {
        $this->resetAfterTest(true);
        $html = "<!DOCTYPE html>\n".
                '<html><head><title>Title</title></head>'.
                '<body>Content<b id="removeme">Goodbye cruel world.</b></body></html>';
        set_config('remove_selectors', '#invalidid', 'auth_outage');
        $generated = $this->generated_page_html($html);

        self::assertStringContainsString('<meta http-equiv="refresh" content="300">', $generated);
    }

    /**
     * Test meta refresh maximum 5 minutes.
     */
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

        self::assertStringContainsString('<meta http-equiv="refresh" content="5">', $generated);
    }

    /**
     * Data provider for test_get_urls_from_stylesheet
     * @return array
     */
    public function test_get_urls_from_stylesheet_provider() {
        return [
            // Empty string.
            ["", 0],
            // URLs that should be retrieved.
            ["background:url(/theme/image.php/_s/boost/core/1581292565/t/expanded)", 1],
            ["background:url('/theme/image.php/_s/boost/core/1581292565/t/expanded')", 1],
            ["src:url(\"/theme/font.php/boost/core/1581292565/fontawesome-webfont.eot?#iefix&v=4.7.0\")", 1],
            ["background-image:url(pix/vline-rtl.gif)", 1],
            // URLs that should not be retrieved.
            ["background-image:url(data:image/gif;base64,R0lGODlhYADIAP=)", 0],
            ["background-image:url('data:image/gif;base64,R0lGODlhYADIAP=')", 0],
            ["background-image:url(\"data:image/svg+xml;charset=utf8,%3Csvg xmlns=\'http://www.w3.org/2000/svg\'\")", 0],
            // Combination of URLs used above.
            ["background-image:url(pix/vline-rtl.gif) background:url(/theme/image.php/_s/boost/core/158/t/expanded)", 2],
            ["background-image:url(data:image/gif;base64,R0lG=)src:url(\"/theme/font.php/fontawesome-webfont.eot\")", 1],
        ];
    }

    /**
     * Tests get_urls_from_stylesheet() method to get all appropriate URLS from the file.
     *
     * @dataProvider test_get_urls_from_stylesheet_provider
     * @param string $filecontent Content of the file
     * @param int $count Expected quantity of found URLs
     * @throws coding_exception
     */
    public function test_get_urls_from_stylesheet($filecontent, $count) {
        $this->resetAfterTest(true);
        $generator = new maintenance_static_page_generator(new DOMDocument(), new maintenance_static_page_io());
        $matches = $generator->get_urls_from_stylesheet($filecontent);

        self::assertIsArray($matches);
        self::assertCount(2, $matches);
        self::assertCount($count, $matches[1]);
    }
}

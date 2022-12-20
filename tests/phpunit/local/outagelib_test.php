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
 * outagelib_test test class.
 *
 * @package     auth_outage
 * @author      Daniel Thee Roperto <daniel.roperto@catalyst-au.net>
 * @copyright   2016 Catalyst IT
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use auth_outage\dml\outagedb;
use auth_outage\local\outage;
use auth_outage\local\outagelib;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir.'/adminlib.php');
require_once(__DIR__.'/../base_testcase.php');

/**
 * outagelib_test test class.
 *
 * @package     auth_outage
 * @author      Daniel Thee Roperto <daniel.roperto@catalyst-au.net>
 * @copyright   2016 Catalyst IT
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class auth_outage_outagelib_test extends auth_outage_base_testcase {
    /**
     * Check if maintenance message is disabled as needed.
     */
    public function test_maintenancemessage() {
        $this->resetAfterTest(true);
        static::setAdminUser();

        $now = time();
        $outage = new outage([
            'autostart' => true,
            'warntime' => $now,
            'starttime' => $now + 100,
            'stoptime' => $now + 200,
            'title' => 'Title',
            'description' => 'Description',
        ]);

        set_config('maintenance_message', 'A message.');
        ob_start();
        outagedb::save($outage);
        $text = trim(ob_get_contents());
        ob_end_clean();
        self::assertStringContainsString('Update maintenance mode configuration', $text);
        self::assertFalse((bool)get_config('moodle', 'maintenance_message'));
        self::assertCount(2, $this->getDebuggingMessages());
        $this->resetDebugging();
    }

    /**
     * Check if maintenance later is removed if no outage set.
     */
    public function test_maintenancelater_nonext() {
        $this->resetAfterTest(true);
        set_config('maintenance_later', time() + (60 * 60 * 24 * 7)); // In 1 week.
        self::assertNotEmpty(get_config('moodle', 'maintenance_later'));
        outagelib::prepare_next_outage();
        self::assertEmpty(get_config('moodle', 'maintenance_later'));
    }

    /**
     * Check outagelib::inject() works as expected.
     */
    public function test_inject() {
        global $OUTPUT;

        $this->resetAfterTest(true);
        self::setAdminUser();
        $now = time();
        $outage = new outage([
            'autostart' => true,
            'warntime' => $now,
            'starttime' => $now + 100,
            'stoptime' => $now + 200,
            'title' => 'Title',
            'description' => 'Description',
        ]);
        ob_start();
        $outage->id = outagedb::save($outage);
        $text = trim(ob_get_contents());
        ob_end_clean();
        self::assertStringContainsString('Update maintenance mode configuration', $text);

        outagelib::reset_injectcalled();
        // Get full header to avoid interactions with other single inject plugins.
        $header1 = $OUTPUT->standard_top_of_body_html();
        self::assertStringContainsString('<style>', $header1);
        self::assertStringContainsString('<script>', $header1);

        // Should not inject more than once.
        $size = strlen($OUTPUT->standard_top_of_body_html());
        self::assertSame($size, strlen($OUTPUT->standard_top_of_body_html()));
        // Check styles aren't reinjected.
        self::assertStringNotContainsString('<style>', $OUTPUT->standard_top_of_body_html());
    }

    /**
     * Check outagelib::inject() will not break the page if something goes wrong.
     */
    public function test_inject_broken() {
        $_GET = ['auth_outage_break_code' => '1'];
        outagelib::reset_injectcalled();
        $header = outagelib::get_inject_code();
        self::assertCount(2, $this->getDebuggingMessages());
        $this->resetDebugging();
    }

    /**
     * Check if injection works with preview.
     */
    public function test_inject_preview() {
        global $CFG;
        $this->resetAfterTest(true);
        self::setAdminUser();
        $now = time();
        $outage = new outage([
            'autostart' => true,
            'warntime' => $now,
            'starttime' => $now + 100,
            'stoptime' => $now + 200,
            'title' => 'Title',
            'description' => 'Description',
        ]);
        ob_start();
        $outage->id = outagedb::save($outage);
        $text = trim(ob_get_contents());
        ob_end_clean();
        self::assertStringContainsString('Update maintenance mode configuration', $text);

        $_GET = ['auth_outage_preview' => (string)$outage->id];

        outagelib::reset_injectcalled();
        $header = outagelib::get_inject_code();
        self::assertStringContainsString('<style>', $header);
        self::assertStringContainsString('<script>', $header);
    }

    /**
     * Check if injection works with invalid preview without stopping the page.
     */
    public function test_inject_preview_notfound() {
        global $CFG;

        $_GET = ['auth_outage_preview' => '1'];
        // Should not throw exception or halt anything, silently ignore it.
        outagelib::reset_injectcalled();
        $header = outagelib::get_inject_code();
        self::assertEmpty($header);
    }

    /**
     * Test injection with preview and delta.
     */
    public function test_inject_preview_withdelta() {
        global $CFG;
        $this->resetAfterTest(true);
        self::setAdminUser();
        $now = time();
        $outage = new outage([
            'autostart' => true,
            'warntime' => $now,
            'starttime' => $now + 100,
            'stoptime' => $now + 200,
            'title' => 'Title',
            'description' => 'Description',
        ]);
        ob_start();
        $outage->id = outagedb::save($outage);
        $text = trim(ob_get_contents());
        ob_end_clean();
        self::assertStringContainsString('Update maintenance mode configuration', $text);
        $_GET = ['auth_outage_preview' => (string)$outage->id, 'auth_outage_delta' => '500'];
        outagelib::reset_injectcalled();
        $header = outagelib::get_inject_code();
        // Still empty, delta is too high (outage ended).
        self::assertEmpty($header);
    }

    /**
     * Test injection without active outage.
     */
    public function test_inject_noactive() {
        outagelib::reset_injectcalled();
        outagelib::get_inject_code();
    }

    /**
     * Check if get config works without getting defaults.
     */
    public function test_get_config() {
        $this->resetAfterTest(true);
        $keys = [
            'css',
            'default_autostart',
            'default_description',
            'default_duration',
            'default_title',
            'default_warning_duration',
            'allowedips',
            'remove_selectors',
        ];
        // Set config with values.
        foreach ($keys as $k) {
            set_config($k, $k.'_value', 'auth_outage');
        }
        // Ensure it is not using any defaults.
        $config = outagelib::get_config();
        foreach ($keys as $k) {
            self::assertSame($config->$k, $k.'_value', 'auth_outage');
        }

        set_config('allowedips_forced', 'allowedips_forced_value', 'auth_outage');
        $config = outagelib::get_config();
        self::assertSame($config->allowedips, "allowedips_value\nallowedips_forced_value", 'auth_outage');
    }

    /**
     * Check that config has key.
     */
    public function test_config_keys() {
        $this->resetAfterTest(true);
        $keys = [
            'allowedips',
            'css',
            'default_autostart',
            'default_description',
            'default_duration',
            'default_title',
            'default_warning_duration',
            'remove_selectors',
        ];
        $defaults = outagelib::get_config_defaults();
        foreach ($keys as $k) {
            self::assertArrayHasKey($k, $defaults);
        }
    }

    /**
     * Check if get config works getting defaults when needed.
     */
    public function test_get_config_invalid() {
        $this->resetAfterTest(true);
        // Set config with invalid values.
        set_config('allowedips', " \n", 'auth_outage');
        set_config('css', " \n", 'auth_outage');
        set_config('default_autostart', " \n", 'auth_outage');
        set_config('default_description', " \n", 'auth_outage');
        set_config('default_duration', " \n", 'auth_outage');
        set_config('default_title', " \n", 'auth_outage');
        set_config('default_warning_duration', " \n", 'auth_outage');
        // Get defaults.
        $defaults = outagelib::get_config_defaults();
        $config = outagelib::get_config();
        // Ensure it is using all defaults.
        foreach ($defaults as $k => $v) {
            self::assertSame($v, $config->$k);
        }
    }

    /**
     * Check if outagelib::inject() does not inject on admin/settings.php?section=additionalhtml
     */
    public function test_inject_settings() {
        global $CFG;

        $this->resetAfterTest(true);
        self::setAdminUser();
        $now = time();
        $outage = new outage([
            'autostart' => true,
            'warntime' => $now,
            'starttime' => $now + 100,
            'stoptime' => $now + 200,
            'title' => 'Title',
            'description' => 'Description',
        ]);
        ob_start();
        $outage->id = outagedb::save($outage);
        $text = trim(ob_get_contents());
        ob_end_clean();
        self::assertStringContainsString('Update maintenance mode configuration', $text);

        // Pretend we are there...
        $_SERVER['SCRIPT_FILENAME'] = '/var/www/alternativepath/admin/settings.php'; // Issue #88 regression test.
        $_SERVER['SCRIPT_NAME'] = '/admin/settings.php';
        $_GET['section'] = 'additionalhtml';
        outagelib::reset_injectcalled();
        $header = outagelib::get_inject_code();

        self::assertEmpty($header);
    }

    /**
     * Test create maintenance php code
     */
    public function test_createmaintenancephpcode() {
        $expected = <<<'EOT'
<?php
if ((time() >= 123) && (time() < 456)) {
    define('MOODLE_INTERNAL', true);
    require_once($CFG->dirroot.'/lib/moodlelib.php');
    if (file_exists($CFG->dirroot.'/lib/classes/ip_utils.php')) {
        require_once($CFG->dirroot.'/lib/classes/ip_utils.php');
    }
    if (!remoteip_in_list('hey\'\"you
a.b.c.d
e.e.e.e/20')) {
        header($_SERVER['SERVER_PROTOCOL'] . ' 503 Moodle under maintenance');
        header('Status: 503 Moodle under maintenance');
        header('Retry-After: 300');
        header('Content-type: text/html; charset=utf-8');
        header('X-UA-Compatible: IE=edge');
        header('Cache-Control: no-store, no-cache, must-revalidate');
        header('Cache-Control: post-check=0, pre-check=0', false);
        header('Pragma: no-cache');
        header('Expires: Mon, 20 Aug 1969 09:23:00 GMT');
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
        header('Accept-Ranges: none');
        header('X-Moodle-Maintenance: manager');
        if ((defined('AJAX_SCRIPT') && AJAX_SCRIPT) || (defined('WS_SERVER') && WS_SERVER)) {
            exit(0);
        }
        echo '<!-- Blocked by ip, your ip: '.getremoteaddr('n/a').' -->';
        if (file_exists($CFG->dataroot.'/climaintenance.template.html')) {
            require($CFG->dataroot.'/climaintenance.template.html');
            exit(0);
        }
        // The file above should always exist, but just in case...
        die('We are currently under maintentance, please try again later.');
    }
}
EOT;
        $found = outagelib::create_climaintenancephp_code(123, 456, "hey'\"you\na.b.c.d\ne.e.e.e/20");
        self::assertSame($expected, $found);
    }

    /**
     * Test create maintenance php code without age
     *
     * @param string $configkey The key of the config.
     * @dataProvider test_createmaintenancephpcode_withoutage_provider
     */
    public function test_createmaintenancephpcode_withoutage($configkey) {
        global $CFG;
        $this->resetAfterTest(true);

        $expected = <<<'EOT'
<?php
if ((time() >= 123) && (time() < 456)) {
    define('MOODLE_INTERNAL', true);
    require_once($CFG->dirroot.'/lib/moodlelib.php');
    if (file_exists($CFG->dirroot.'/lib/classes/ip_utils.php')) {
        require_once($CFG->dirroot.'/lib/classes/ip_utils.php');
    }
    if (!remoteip_in_list('127.0.0.1')) {
        header($_SERVER['SERVER_PROTOCOL'] . ' 503 Moodle under maintenance');
        header('Status: 503 Moodle under maintenance');
        header('Retry-After: 300');
        header('Content-type: text/html; charset=utf-8');
        header('X-UA-Compatible: IE=edge');
        header('Cache-Control: no-store, no-cache, must-revalidate');
        header('Cache-Control: post-check=0, pre-check=0', false);
        header('Pragma: no-cache');
        header('Expires: Mon, 20 Aug 1969 09:23:00 GMT');
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
        header('Accept-Ranges: none');
        header('X-Moodle-Maintenance: manager');
        if ((defined('AJAX_SCRIPT') && AJAX_SCRIPT) || (defined('WS_SERVER') && WS_SERVER)) {
            exit(0);
        }
        echo '<!-- Blocked by ip, your ip: '.getremoteaddr('n/a').' -->';
        if (file_exists($CFG->dataroot.'/climaintenance.template.html')) {
            require($CFG->dataroot.'/climaintenance.template.html');
            exit(0);
        }
        // The file above should always exist, but just in case...
        die('We are currently under maintentance, please try again later.');
    }
}
EOT;
        $outage = new outage([
            'starttime' => 123,
            'stoptime' => 456,
        ]);
        $file = $CFG->dataroot.'/climaintenance.php';
        set_config($configkey, '127.0.0.1', 'auth_outage');

        outagelib::update_climaintenance_code($outage);
        self::assertFileExists($file);
        $found = file_get_contents($file);
        self::assertSame($found, $expected);
    }

    public function test_createmaintenancephpcode_withoutage_provider(): array {
        return [['allowedips'], ['allowedips_forced']];
    }

    /**
     * Test create maintenance php code without IPs
     */
    public function test_createmaintenancephpcode_withoutips() {
        global $CFG;
        $this->resetAfterTest(true);

        $outage = new outage([
            'starttime' => 123,
            'stoptime' => 456,
        ]);
        $file = $CFG->dataroot.'/climaintenance.php';
        set_config('allowedips', '', 'auth_outage');
        set_config('allowedips_forced', '', 'auth_outage');

        touch($file);
        outagelib::update_climaintenance_code($outage);
        // Backwards compatibility with older PHPUnit - use old assertFile method.
        if (method_exists($this, 'assertFileDoesNotExist')) {
            self::assertFileDoesNotExist($file);
        } else {
            self::assertFileNotExists($file);
        }
    }

    /**
     * Test create maintenance php code without outage
     */
    public function test_createmaintenancephpcode_withoutoutage() {
        global $CFG;
        $file = $CFG->dataroot.'/climaintenance.php';

        touch($file);
        outagelib::update_climaintenance_code(null);
        // Backwards compatibility with older PHPUnit - use old assertFile method.
        if (method_exists($this, 'assertFileDoesNotExist')) {
            self::assertFileDoesNotExist($file);
        } else {
            self::assertFileNotExists($file);
        }
    }

    /**
     * Related to Issue #70: Creating ongoing outage does not trigger maintenance file creation.
     */
    public function test_preparenextoutage_notautostart() {
        global $CFG;

        $this->create_outage();

        // The method outagelib::prepare_next_outage() should have been called by save().
        foreach ([$CFG->dataroot.'/climaintenance.template.html', $CFG->dataroot.'/climaintenance.php'] as $file) {
            self::assertFileExists($file);
            unlink($file);
        }
    }

    /**
     * Regression Test - Issue #82: When changing the IP address list it should recreate the maintenance files.
     */
    public function test_when_we_change_allowed_ips_in_settings_it_updates_the_templates() {
        global $CFG;

        $this->create_outage();

        // Change settings.
        set_config('s_auth_outage_allowedips', '127', 'auth_outage');

        // The method outagelib::prepare_next_outage() should have been called from admin_write_settings().
        foreach ([$CFG->dataroot.'/climaintenance.template.html', $CFG->dataroot.'/climaintenance.php'] as $file) {
            self::assertFileExists($file);
            unlink($file);
        }
    }

    /**
     * Problem detected while solving Issue #82.
     */
    public function test_when_we_change_remove_selectors_in_settings_it_updates_the_templates() {
        global $CFG;

        $this->create_outage();

        // Change settings.
        set_config('s_auth_outage_remove_selectors', '.something', 'auth_outage');

        // The method outagelib::prepare_next_outage() should have been called from admin_write_settings().
        foreach ([$CFG->dataroot.'/climaintenance.template.html', $CFG->dataroot.'/climaintenance.php'] as $file) {
            self::assertFileExists($file);
            unlink($file);
        }
    }

    /**
     * Related to Issue #72: IP Block still triggers cli maintenance mode even without autostart.
     */
    public function test_preparenextoutage_noautostarttrigger() {
        global $CFG;

        $this->resetAfterTest(true);
        self::setAdminUser();
        $now = time();
        $outage = new outage([
            'autostart' => false,
            'warntime' => $now - 200,
            'starttime' => $now - 100,
            'stoptime' => $now + 200,
            'title' => 'Title',
            'description' => 'Description',
        ]);
        outagedb::save($outage);

        // The method outagelib::prepare_next_outage() should have been called by save().
        self::assertFalse(get_config('moodle', 'maintenance_later'));
        // This file should not exist even if the statement above fails as Moodle does not create it immediately but test anyway.
        // Backwards compatibility with older PHPUnit - use old assertFile method.
        if (method_exists($this, 'assertFileDoesNotExist')) {
            self::assertFileDoesNotExist($CFG->dataroot.'/climaintenance.html');
        } else {
            self::assertFileNotExists($CFG->dataroot.'/climaintenance.html');
        }
    }

    /**
     * Regression test for issue #85.
     */
    public function test_it_can_inject_in_settings_if_not_additional_html() {
        global $CFG;

        $this->resetAfterTest(true);
        self::setAdminUser();
        $now = time();
        $outage = new outage([
            'autostart' => true,
            'warntime' => $now,
            'starttime' => $now + 100,
            'stoptime' => $now + 200,
            'title' => 'Title',
            'description' => 'Description',
        ]);
        ob_start();
        $outage->id = outagedb::save($outage);
        $text = trim(ob_get_contents());
        ob_end_clean();
        self::assertStringContainsString('Update maintenance mode configuration', $text);
        // Pretend we are there...
        $_SERVER['SCRIPT_FILENAME'] = '/var/www/alternativepath/admin/settings.php'; // Issue #88 regression test.
        $_SERVER['SCRIPT_NAME'] = '/admin/settings.php';
        $_GET['section'] = 'notadditionalhtml';
        outagelib::reset_injectcalled();

        $header = outagelib::get_inject_code();
        self::assertNotEmpty($header);
    }

    /**
     * Creates outage for tests.
     */
    private function create_outage() {
        $this->resetAfterTest(true);
        self::setAdminUser();
        $now = time();
        $outage = new outage([
            'autostart'   => false,
            'warntime'    => $now - 200,
            'starttime'   => $now - 100,
            'stoptime'    => $now + 200,
            'title'       => 'Title',
            'description' => 'Description',
        ]);
        set_config('allowedips', '127.0.0.1', 'auth_outage');
        outagedb::save($outage);

        // Enable outage plugin so settings can be changed.
        set_config('auth', 'outage');
        \core\session\manager::gc(); // Remove stale sessions.
        core_plugin_manager::reset_caches();
    }
}

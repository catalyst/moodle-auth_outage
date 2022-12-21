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
 * installation_test test class.
 *
 * Tests for installs, updates and uninstalls as needed.
 *
 * @package    auth_outage
 * @author     Daniel Thee Roperto <daniel.roperto@catalyst-au.net>
 * @copyright  2016 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use auth_outage\local\outage;

defined('MOODLE_INTERNAL') || die();
require_once(__DIR__.'/base_testcase.php');

/**
 * installation_test test class.
 *
 * Tests for installs, updates and uninstalls as needed.
 *
 * @package    auth_outage
 * @author     Daniel Thee Roperto <daniel.roperto@catalyst-au.net>
 * @copyright  2016 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class auth_outage_installation_test extends auth_outage_base_testcase {
    /**
     * Checks if plugin cleans up data after uninstall.
     *
     * See Issue #57.
     */
    public function test_uninstall() {
        global $CFG, $DB;

        $this->resetAfterTest();
        static::setAdminUser();
        $dbman = $DB->get_manager();

        // Create a future outage with autostart.
        $now = time();
        $outage = new outage([
            'autostart' => true,
            'starttime' => $now + (1 * 60 * 60),
            'stoptime' => $now + (2 * 60 * 60),
            'warntime' => $now - (2 * 60 * 60),
            'title' => 'Title',
            'description' => 'Description',
        ]);
        ob_start();
        \auth_outage\dml\outagedb::save($outage);
        $text = trim(ob_get_contents());
        ob_end_clean();
        self::assertStringContainsString('Update maintenance mode configuration', $text);
        self::assertSame(1, $DB->count_records_select('event', "eventtype = 'auth_outage'", null));

        // Uninstall plugin.
        require_once($CFG->libdir.'/adminlib.php');
        $progress = new progress_trace_buffer(new text_progress_trace(), false);
        core_plugin_manager::instance()->uninstall_plugin('auth_outage', $progress);
        $progress->finished();
        self::assertStringContainsString('++ Success ++', $progress->get_buffer());

        // Check ...
        self::assertSame(0, $DB->count_records_select('event', "eventtype = 'auth_outage'", null),
            'The outage events were not removed.');
        self::assertFalse(file_exists($CFG->dataroot.'/climaintenance.php'),
            'The maintenance template file was not deleted.');
        self::assertFalse(get_config('moodle', 'maintenance_later'),
            'Maintenance later must not be set.'); // Issue #57.
        self::assertFalse($dbman->table_exists('auth_outage'),
            'Table "auth_outage" was not dropped.');

        // Create tables back so tests do not fail with MySQL ...
        require_once($CFG->libdir.'/upgradelib.php');
        $DB->get_manager()->install_from_xmldb_file($CFG->dirroot.'/auth/outage/db/install.xml');
    }
}

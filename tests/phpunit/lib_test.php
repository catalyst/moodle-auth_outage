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
 * tests for lib.php
 *
 * @package    auth_outage
 * @author     Daniel Thee Roperto <daniel.roperto@catalyst-au.net>
 * @copyright  2017 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
require_once(__DIR__.'/base_testcase.php');
require_once(__DIR__.'/../../lib.php');

/**
 * tests for lib.php
 *
 * @package    auth_outage
 * @author     Daniel Thee Roperto <daniel.roperto@catalyst-au.net>
 * @copyright  2017 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @SuppressWarnings(public) Allow as many methods as needed.
 */
class lib_test extends auth_outage_base_testcase {
    public function test_auth_outage_get_climaintenance_resource_file_resolves_a_file() {
        global $CFG;
        $dir = $CFG->dataroot.'/auth_outage/climaintenance';
        mkdir($dir, 0777, true);

        // Create a file.
        $expected = $dir.'/example.txt';
        file_put_contents($expected, 'Outage Unit Test Message');

        // Get that file.
        $actual = auth_outage_get_climaintenance_resource_file('example.txt');

        // Clean up.
        unlink($expected);
        rmdir($dir);

        self::assertSame($expected, $actual);
    }

    /**
     * Regression test for issue #104.
     */
    public function test_auth_outage_get_climaintenance_resource_file_resolves_a_file_with_symlink() {
        global $CFG;

        // Create a file.
        $realdir = $CFG->dataroot.'/auth_outage/climaintenance_real';
        mkdir($realdir, 0777, true);
        $realfile = $realdir.'/example.txt';
        file_put_contents($realfile, 'Outage Unit Test Message');

        // Create a symlink
        $symdir = $CFG->dataroot.'/auth_outage/climaintenance';
        if (!symlink($realdir, $symdir)) {
            unlink($realfile);
            rmdir($realdir);
            $this->markTestSkipped('Canont create symlinks, maybe OS does not support.');
            return;
        }

        // Get that file.
        $actual = auth_outage_get_climaintenance_resource_file('example.txt');

        // Clean up.
        unlink($symdir);
        unlink($realfile);
        rmdir($realdir);

        self::assertSame($realfile, $actual);
    }

    public function test_auth_outage_get_climaintenance_resource_file_prevent_path_traversal() {
        global $CFG;

        $dir = $CFG->dataroot.'/auth_outage/climaintenance';
        mkdir($dir, 0777, true);

        // Create a file.
        $expected = $dir.'/example.txt';
        file_put_contents($expected, 'Outage Unit Test Message');

        // Create a sensitive file.
        $sensitivefile = $CFG->dataroot.'/auth_outage/nuclear_silo_passwords.txt';
        file_put_contents($sensitivefile, 'The password to launch the ICBM: 123456');

        // Path Traversal Attack.
        $actual = auth_outage_get_climaintenance_resource_file('../n\\uclear_silo_passwords.txt');

        // Clean up.
        unlink($expected);
        rmdir($dir);

        self::assertNull($actual);
    }
}

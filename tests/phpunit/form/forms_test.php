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
 * forms_test test class.
 *
 * @package     auth_outage
 * @author      Daniel Thee Roperto <daniel.roperto@catalyst-au.net>
 * @copyright   2016 Catalyst IT
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use auth_outage\form\outage\delete;
use auth_outage\form\outage\edit;
use auth_outage\form\outage\finish;
use auth_outage\local\outage;

defined('MOODLE_INTERNAL') || die();
require_once(__DIR__.'/../base_testcase.php');

/**
 * forms_test test class.
 *
 * @package     auth_outage
 * @author      Daniel Thee Roperto <daniel.roperto@catalyst-au.net>
 * @copyright   2016 Catalyst IT
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class auth_outage_forms_test extends auth_outage_base_testcase {
    /**
     * Create a delete form.
     */
    public function test_delete() {
        new delete();
    }

    /**
     * Create a finish form.
     */
    public function test_finish() {
        new finish();
    }

    /**
     * Mock some data and check values.
     */
    public function test_edit_valid() {
        if ($this->skip_because_moodle_is_below_30('Moodle POST mocking was fixed in Moodle 30.')) {
            return;
        }

        $this->mock_edit_post();
        $edit = new edit();
        self::assertFalse($edit->is_cancelled());
        $outage = $edit->get_data();
        self::assertInstanceOf('\\auth_outage\\local\\outage', $outage);
        self::assertSame(false, $outage->autostart);
        self::assertSame(60, $outage->get_warning_duration());
        self::assertSame(mktime(14, 15, 0, 2, 1, 2013), $outage->starttime);
        self::assertSame(2 * 60 * 60, $outage->get_duration_planned());
        self::assertSame('The title.', $outage->title);
        self::assertSame('The <b>description</b>.', $outage->description);
    }

    /**
     * Check invalid warning duration.
     */
    public function test_edit_invalid_warning() {
        if ($this->skip_because_moodle_is_below_30('Moodle POST mocking was fixed in Moodle 30.')) {
            return;
        }

        $this->mock_edit_post();
        $_POST['warningduration'] = ['number' => '-1', 'timeunit' => '60'];
        $edit = new edit();
        $outage = $edit->get_data();
        self::assertNull($outage);
    }

    /**
     * Check invalid outage duration.
     */
    public function test_edit_invalid_duration() {
        if ($this->skip_because_moodle_is_below_30('Moodle POST mocking was fixed in Moodle 30.')) {
            return;
        }

        $this->mock_edit_post();
        $_POST['outageduration'] = ['number' => '-2', 'timeunit' => '3600'];
        $edit = new edit();
        self::assertNull($edit->get_data());
    }

    /**
     * Check invalid title (empty).
     */
    public function test_edit_invalid_title() {
        if ($this->skip_because_moodle_is_below_30('Moodle POST mocking was fixed in Moodle 30.')) {
            return;
        }

        $this->mock_edit_post();
        $_POST['title'] = '';
        $edit = new edit();
        self::assertNull($edit->get_data());
    }

    /**
     * Check invalid title (too long).
     */
    public function test_edit_invalid_title_toolong() {
        if ($this->skip_because_moodle_is_below_30('Moodle POST mocking was fixed in Moodle 30.')) {
            return;
        }

        $this->mock_edit_post();
        $_POST['title'] = 'This is a very long time, it is so long that at some point it should not be valid. '.
                          'With a very long title used in this place we should get a form validation error. '.
                          'Do you think this title is long enough?';
        $edit = new edit();
        self::assertNull($edit->get_data());
    }

    /**
     * Check invalid format for description.
     */
    public function test_edit_description_invalid_format() {
        if ($this->skip_because_moodle_is_below_30('Moodle POST mocking was fixed in Moodle 30.')) {
            return;
        }

        $this->mock_edit_post();
        $_POST['description'] = ['text' => 'The <b>description</b>.', 'format' => '2'];
        $edit = new edit();
        self::assertNull($edit->get_data());
        self::assertCount(1, $this->getDebuggingMessages());
        $this->resetDebugging();
    }

    /**
     * Check if set data works properly.
     */
    public function test_setdata() {
        $outage = new outage([
            'autostart'   => false,
            'warntime'    => time() - 60,
            'starttime'   => time(),
            'stoptime'    => time() + 60,
            'title'       => 'Title',
            'description' => 'Description',
        ]);
        $edit = new edit();
        $edit->set_data($outage);
    }

    /**
     * Check edit::set_data() with invalid parameter.
     */
    public function test_setdata_invalid() {
        $edit = new edit();
        $this->set_expected_exception('coding_exception');
        $edit->set_data(null);
    }

    /**
     * Mock a post, see MDL-56233.
     */
    private function mock_edit_post() {
        $_POST = [
            'id'                                => '1',
            'sesskey'                           => sesskey(),
            '_qf__auth_outage_form_outage_edit' => '1',
            'warningduration'                   => ['number' => '1', 'timeunit' => '60'],
            'starttime'                         => [
                'day'    => '1',
                'month'  => '2',
                'year'   => '2013',
                'hour'   => '14',
                'minute' => '15',
            ],
            'outageduration'                    => ['number' => '2', 'timeunit' => '3600'],
            'title'                             => 'The title.',
            'description'                       => ['text' => 'The <b>description</b>.', 'format' => '1'],
            'submitbutton'                      => 'Save changes',
        ];
    }

    /**
     * Skip tests for moodle below 30.
     * @param string $reason reason to be filled
     */
    private function skip_because_moodle_is_below_30($reason = '') {
        global $CFG;

        // The bugfix MDL-56250 in only applies to Moodle 30+.
        // Before that the form validation test is meaningless (results are cached), so skip it.
        if ($CFG->branch < 30) {
            $this->markTestSkipped('Some tests can only run in Moodle 30+. '.$reason);
            return true;
        }

        return false;
    }
}

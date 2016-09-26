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

use auth_outage\form\outage\delete;
use auth_outage\form\outage\edit;
use auth_outage\form\outage\finish;
use auth_outage\local\outage;

defined('MOODLE_INTERNAL') || die();

/**
 * Tests performed on forms classes.
 *
 * @package     auth_outage
 * @author      Daniel Thee Roperto <daniel.roperto@catalyst-au.net>
 * @copyright   2016 Catalyst IT
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class forms_test extends advanced_testcase {
    public function test_delete() {
        new delete();
    }

    public function test_finish() {
        new finish();
    }

    public function test_edit_valid() {
        $this->mock_edit_post();
        $edit = new edit();
        self::assertFalse($edit->is_cancelled());
        $outage = $edit->get_data();
        self::assertInstanceOf(outage::class, $outage);
        self::assertSame(false, $outage->autostart);
        self::assertSame(60, $outage->get_warning_duration());
        self::assertSame(mktime(14, 15, 0, 2, 1, 2013), $outage->starttime);
        self::assertSame(2 * 60 * 60, $outage->get_duration_planned());
        self::assertSame('The title.', $outage->title);
        self::assertSame('The <b>description</b>.', $outage->description);
    }

    public function test_edit_invalid_warning() {
        $this->mock_edit_post();
        $_POST['warningduration'] = ['number' => '-1', 'timeunit' => '60'];
        $edit = new edit();
        $outage = $edit->get_data();
        self::assertNull($outage);
    }

    public function test_edit_invalid_duration() {
        $this->mock_edit_post();
        $_POST['outageduration'] = ['number' => '-2', 'timeunit' => '3600'];
        $edit = new edit();
        self::assertNull($edit->get_data());
    }

    public function test_edit_invalid_title() {
        $this->mock_edit_post();
        $_POST['title'] = '';
        $edit = new edit();
        self::assertNull($edit->get_data());
    }

    public function test_edit_invalid_title_toolong() {
        $this->mock_edit_post();
        $_POST['title'] = 'This is a very long time, it is so long that at some point it should not be valid. '.
                          'With a very long title used in this place we should get a form validation error. '.
                          'Do you think this title is long enough?';
        $edit = new edit();
        self::assertNull($edit->get_data());
    }

    public function test_edit_description_invalid_format() {
        $this->mock_edit_post();
        $_POST['description'] = ['text' => 'The <b>description</b>.', 'format' => '2'];
        $edit = new edit();
        self::assertNull($edit->get_data());
        self::assertCount(1, phpunit_util::get_debugging_messages());
        phpunit_util::reset_debugging();
    }

    public function test_setdata() {
        $outage = new outage([
            'autostart' => false,
            'warntime' => time() - 60,
            'starttime' => time(),
            'stoptime' => time() + 60,
            'title' => 'Title',
            'description' => 'Description',
        ]);
        $edit = new edit();
        $edit->set_data($outage);
    }

    /**
     * @expectedException coding_exception
     */
    public function test_setdata_invalid() {
        $edit = new edit();
        $edit->set_data(null);
    }

    private function mock_edit_post() {
        // There is a bug in moodleform::mock_submit so we make our own version.
        $_POST = [
            'id' => '1',
            'sesskey' => sesskey(),
            '_qf__auth_outage_form_outage_edit' => '1',
            'warningduration' => ['number' => '1', 'timeunit' => '60'],
            'starttime' => ['day' => '1', 'month' => '2', 'year' => '2013', 'hour' => '14', 'minute' => '15'],
            'outageduration' => ['number' => '2', 'timeunit' => '3600'],
            'title' => 'The title.',
            'description' => ['text' => 'The <b>description</b>.', 'format' => '1'],
            'submitbutton' => 'Save changes',
        ];
    }
}

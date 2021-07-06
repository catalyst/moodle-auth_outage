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
 * outage_test test class.
 *
 * @package    auth_outage
 * @author     Daniel Thee Roperto <daniel.roperto@catalyst-au.net>
 * @copyright  2016 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use auth_outage\local\outage;

defined('MOODLE_INTERNAL') || die();
require_once(__DIR__.'/../base_testcase.php');

/**
 * outage_test test class.
 *
 * @package    auth_outage
 * @author     Daniel Thee Roperto <daniel.roperto@catalyst-au.net>
 * @copyright  2016 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class auth_outage_outage_test extends auth_outage_base_testcase {
    /**
     * Tests the constructor.
     */
    public function test_constructor() {
        $outage = new outage();
        // Very important, this should never change.
        self::assertNull($outage->id, 'New empty outage can never have an id set.');
        // Ensure all other fields are also null.
        foreach ($outage as $v) {
            self::assertNull($v);
        }
    }

    /**
     * Tests the constructor, giving data as an object.
     */
    public function test_constructor_object() {
        $obj = new stdClass();
        $obj->id = 1;
        $obj->autostart = true;
        $obj->warntime = 2;
        $obj->starttime = 3;
        $obj->finished = 4;
        $obj->stoptime = 5;
        $obj->title = 'Title';
        $obj->description = 'Description';
        $outage = new outage($obj);
        self::assertSame($obj->id, $outage->id);
        self::assertSame($obj->autostart, $outage->autostart);
        self::assertSame($obj->warntime, $outage->warntime);
        self::assertSame($obj->starttime, $outage->starttime);
        self::assertSame($obj->finished, $outage->finished);
        self::assertSame($obj->stoptime, $outage->stoptime);
        self::assertSame($obj->title, $outage->title);
        self::assertSame($obj->description, $outage->description);
    }

    /**
     * Tests the constructor with invalid data.
     */
    public function test_constructor_invalid() {
        $this->set_expected_exception('coding_exception');
        new outage('My outage');
    }

    /**
     * Tests getting the stage considering the current time (now).
     */
    public function test_getstage_now() {
        $now = time();
        // Make sure it is in the past.
        $outage = new outage([
            'starttime' => $now - (3 * 60 * 60),
            'stoptime' => $now - (2 * 60 * 60),
            'warntime' => $now - (2 * 60 * 60),
            'title' => '',
            'description' => '',
        ]);
        self::assertSame(outage::STAGE_STOPPED, $outage->get_stage());
    }

    /**
     * Tests getting the stage providing an invalid time reference.
     */
    public function test_getstage_invalidtime() {
        $outage = new outage();
        $this->set_expected_exception('coding_exception');
        $outage->get_stage(-1);
    }

    /**
     * Tests is_ongoing() with different outage stages.
     */
    public function test_isongoing() {
        $now = time();

        // In the past.
        $outage = new outage([
            'starttime' => $now - (3 * 60 * 60),
            'stoptime' => $now - (2 * 60 * 60),
            'warntime' => $now - (2 * 60 * 60),
            'title' => '',
            'description' => '',
        ]);
        self::assertFalse($outage->is_ongoing($now));

        // In the present (ongoing).
        $outage = new outage([
            'starttime' => $now - (1 * 60 * 60),
            'stoptime' => $now + (1 * 60 * 60),
            'warntime' => $now - (2 * 60 * 60),
            'title' => '',
            'description' => '',
        ]);
        self::assertTrue($outage->is_ongoing($now));

        // In the future.
        $outage = new outage([
            'starttime' => $now + (1 * 60 * 60),
            'stoptime' => $now + (2 * 60 * 60),
            'warntime' => $now - (2 * 60 * 60),
            'title' => '',
            'description' => '',
        ]);
        self::assertFalse($outage->is_ongoing($now));
    }

    /**
     * Tests is_active() with different outage stages.
     */
    public function test_isactive() {
        $now = time();

        // In the past.
        $outage = new outage([
            'starttime' => $now - (3 * 60 * 60),
            'stoptime' => $now - (2 * 60 * 60),
            'warntime' => $now - (2 * 60 * 60),
            'title' => '',
            'description' => '',
        ]);
        self::assertFalse($outage->is_active($now));

        // In the present (ongoing).
        $outage = new outage([
            'starttime' => $now - (1 * 60 * 60),
            'stoptime' => $now + (1 * 60 * 60),
            'warntime' => $now - (2 * 60 * 60),
            'title' => '',
            'description' => '',
        ]);
        self::assertTrue($outage->is_active($now));

        // In the future (warning).
        $outage = new outage([
            'starttime' => $now + (1 * 60 * 60),
            'stoptime' => $now + (2 * 60 * 60),
            'warntime' => $now - (2 * 60 * 60),
            'title' => '',
            'description' => '',
        ]);
        self::assertTrue($outage->is_active($now));

        // In the future (not warning).
        $outage = new outage([
            'starttime' => $now + (2 * 60 * 60),
            'stoptime' => $now + (3 * 60 * 60),
            'warntime' => $now + (1 * 60 * 60),
            'title' => '',
            'description' => '',
        ]);
        self::assertFalse($outage->is_active($now));
    }

    /**
     * Tests different outage stages.
     */
    public function test_stages() {
        $now = time();

        $outage = new outage([
            'warntime' => $now + 10,
            'starttime' => $now + 20,
            'stoptime' => $now + 30,
            'title' => 'Outage Waiting',
        ]);
        self::assertSame(outage::STAGE_WAITING, $outage->get_stage($now));
        self::assertFalse($outage->is_active($now));
        self::assertFalse($outage->is_ongoing($now));
        self::assertFalse($outage->has_ended());

        $outage = new outage([
            'warntime' => $now - 10,
            'starttime' => $now + 20,
            'stoptime' => $now + 30,
            'title' => 'Outage Warning',
        ]);
        self::assertSame(outage::STAGE_WARNING, $outage->get_stage($now));
        self::assertTrue($outage->is_active($now));
        self::assertFalse($outage->is_ongoing($now));
        self::assertFalse($outage->has_ended());

        $outage = new outage([
            'warntime' => $now - 20,
            'starttime' => $now - 10,
            'stoptime' => $now + 30,
            'title' => 'Outage Ongoing',
        ]);
        self::assertSame(outage::STAGE_ONGOING, $outage->get_stage($now));
        self::assertTrue($outage->is_active($now));
        self::assertTrue($outage->is_ongoing($now));
        self::assertFalse($outage->has_ended());

        $outage = new outage([
            'warntime' => $now - 50,
            'starttime' => $now - 40,
            'stoptime' => $now - 30,
            'title' => 'Outage Stopped',
        ]);
        self::assertSame(outage::STAGE_STOPPED, $outage->get_stage($now));
        self::assertFalse($outage->is_active($now));
        self::assertFalse($outage->is_ongoing($now));
        self::assertTrue($outage->has_ended());

        $outage = new outage([
            'warntime' => $now - 50,
            'starttime' => $now - 40,
            'finished' => $now - 30,
            'stoptime' => $now - 20,
            'title' => 'Outage Finished before Stop',
        ]);
        self::assertSame(outage::STAGE_FINISHED, $outage->get_stage($now));
        self::assertFalse($outage->is_active($now));
        self::assertFalse($outage->is_ongoing($now));
        self::assertTrue($outage->has_ended());

        $outage = new outage([
            'warntime' => $now - 50,
            'starttime' => $now - 40,
            'stoptime' => $now - 30,
            'finished' => $now - 20,
            'title' => 'Outage Finished after Stop',
        ]);
        self::assertSame(outage::STAGE_FINISHED, $outage->get_stage($now));
        self::assertFalse($outage->is_active($now));
        self::assertFalse($outage->is_ongoing($now));
        self::assertTrue($outage->has_ended());
    }

    /**
     * Tests if getting title and description replaces the placeholders.
     */
    public function test_gettitle_getdescription() {
        $now = time();
        $outage = new outage([
            'warntime' => $now - 50,
            'starttime' => $now - 40,
            'stoptime' => $now - 30,
            'finished' => $now - 20,
            'title' => 'Title {{start}} {{stop}} {{duration}}',
            'description' => 'Description {{start}} {{stop}} {{duration}}',
        ]);
        $title = $outage->get_title();
        self::assertStringNotContainsString('{', $title);
        self::assertStringNotContainsString('}', $title);
        $description = $outage->get_description();
        self::assertStringNotContainsString('{', $description);
        self::assertStringNotContainsString('}', $description);
    }

    /**
     * Tests getting the durations.
     */
    public function test_getdurations() {
        $outage = new outage(['starttime' => 1000]);
        self::assertNull($outage->get_duration_actual());

        $outage->finished = 3000;
        self::assertSame(2000, $outage->get_duration_actual());

        $outage->stoptime = 3050;
        self::assertEquals(2050, $outage->get_duration_planned());

        $outage->warntime = 600;
        self::assertEquals(400, $outage->get_warning_duration());
    }
}

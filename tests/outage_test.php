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

use auth_outage\models\outage;

defined('MOODLE_INTERNAL') || die();

/**
 * Tests performed on outage class.
 *
 * @package    auth_outage
 * @author     Daniel Thee Roperto <daniel.roperto@catalyst-au.net>
 * @copyright  Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class outage_test extends basic_testcase {
    public function test_constructor() {
        $outage = new outage();
        // Very important, this should never change.
        self::assertNull($outage->id, 'New empty outage can never have an id set.');
        // Ensure all other fields are also null.
        foreach ($outage as $v) {
            self::assertNull($v);
        }
    }

    public function test_isongoing() {
        $now = time();

        // In the past.
        $outage = new outage([
            'starttime' => $now - (3 * 60 * 60),
            'stoptime' => $now - (2 * 60 * 60),
            'warntime' => $now - (2 * 60 * 60),
            'title' => '',
            'description' => ''
        ]);
        self::assertFalse($outage->is_ongoing($now));

        // In the present (ongoing).
        $outage = new outage([
            'starttime' => $now - (1 * 60 * 60),
            'stoptime' => $now + (1 * 60 * 60),
            'warntime' => $now - (2 * 60 * 60),
            'title' => '',
            'description' => ''
        ]);
        self::assertTrue($outage->is_ongoing($now));

        // In the future.
        $outage = new outage([
            'starttime' => $now + (1 * 60 * 60),
            'stoptime' => $now + (2 * 60 * 60),
            'warntime' => $now - (2 * 60 * 60),
            'title' => '',
            'description' => ''
        ]);
        self::assertFalse($outage->is_ongoing($now));
    }

    public function test_isactive() {
        $now = time();

        // In the past.
        $outage = new outage([
            'starttime' => $now - (3 * 60 * 60),
            'stoptime' => $now - (2 * 60 * 60),
            'warntime' => $now - (2 * 60 * 60),
            'title' => '',
            'description' => ''
        ]);
        self::assertFalse($outage->is_active($now));

        // In the present (ongoing).
        $outage = new outage([
            'starttime' => $now - (1 * 60 * 60),
            'stoptime' => $now + (1 * 60 * 60),
            'warntime' => $now - (2 * 60 * 60),
            'title' => '',
            'description' => ''
        ]);
        self::assertTrue($outage->is_active($now));

        // In the future (warning).
        $outage = new outage([
            'starttime' => $now + (1 * 60 * 60),
            'stoptime' => $now + (2 * 60 * 60),
            'warntime' => $now - (2 * 60 * 60),
            'title' => '',
            'description' => ''
        ]);
        self::assertTrue($outage->is_active($now));

        // In the future (not warning).
        $outage = new outage([
            'starttime' => $now + (2 * 60 * 60),
            'stoptime' => $now + (3 * 60 * 60),
            'warntime' => $now + (1 * 60 * 60),
            'title' => '',
            'description' => ''
        ]);
        self::assertFalse($outage->is_active($now));
    }

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

        $outage = new outage([
            'warntime' => $now - 10,
            'starttime' => $now + 20,
            'stoptime' => $now + 30,
            'title' => 'Outage Warning',
        ]);
        self::assertSame(outage::STAGE_WARNING, $outage->get_stage($now));
        self::assertTrue($outage->is_active($now));
        self::assertFalse($outage->is_ongoing($now));

        $outage = new outage([
            'warntime' => $now - 20,
            'starttime' => $now - 10,
            'stoptime' => $now + 30,
            'title' => 'Outage Ongoing',
        ]);
        self::assertSame(outage::STAGE_ONGOING, $outage->get_stage($now));
        self::assertTrue($outage->is_active($now));
        self::assertTrue($outage->is_ongoing($now));

        $outage = new outage([
            'warntime' => $now - 50,
            'starttime' => $now - 40,
            'stoptime' => $now - 30,
            'title' => 'Outage Stopped',
        ]);
        self::assertSame(outage::STAGE_STOPPED, $outage->get_stage($now));
        self::assertFalse($outage->is_active($now));
        self::assertFalse($outage->is_ongoing($now));

        $outage = new outage([
            'warntime' => $now - 50,
            'starttime' => $now - 40,
            'finishtime' => $now - 30,
            'stoptime' => $now - 20,
            'title' => 'Outage Finished before Stop',
        ]);
        self::assertSame(outage::STAGE_FINISHED, $outage->get_stage($now));
        self::assertFalse($outage->is_active($now));
        self::assertFalse($outage->is_ongoing($now));

        $outage = new outage([
            'warntime' => $now - 50,
            'starttime' => $now - 40,
            'stoptime' => $now - 30,
            'finishtime' => $now - 20,
            'title' => 'Outage Finished after Stop',
        ]);
        self::assertSame(outage::STAGE_FINISHED, $outage->get_stage($now));
        self::assertFalse($outage->is_active($now));
        self::assertFalse($outage->is_ongoing($now));
    }
}

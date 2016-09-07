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
 * Tests performed on outage class.
 *
 * @package    auth_outage
 * @author     Daniel Thee Roperto <daniel.roperto@catalyst-au.net>
 * @copyright  Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use auth_outage\models\outage;
use auth_outage\outagedb;

defined('MOODLE_INTERNAL') || die();


class outagedb_test extends advanced_testcase {
    /**
     * Make sure we can save and update.
     */
    public function test_save() {
        $this->resetAfterTest(true);
        // Save new outage.
        $id = outagedb::save($this->createoutage(1));
        // Update it.
        $outage = $this->createoutage(2);
        $outage->id = $id;
        outagedb::save($outage);
    }

    /**
     * Make sure we can get existing entries and null if not found.
     */
    public function test_getbyid() {
        $this->resetAfterTest(true);
        // Create something.
        $id = outagedb::save($this->createoutage(1));
        // Get should work.
        $outage = outagedb::get_by_id($id);
        self::assertNotNull($outage);
        // Delete it.
        outagedb::delete($id);
        // Get should be null.
        $outage = outagedb::get_by_id($id);
        self::assertNull($outage);
    }

    /**
     * Make sure we can delete stuff.
     */
    public function test_delete() {
        $this->resetAfterTest(true);
        // Create something.
        $id = outagedb::save($this->createoutage(1));
        // Delete it.
        outagedb::delete($id);
        // Should not exist anymore.
        self::assertNull(outagedb::get_by_id($id));
    }

    /**
     * Make sure getall brings all entries.
     */
    public function test_getall() {
        $this->resetAfterTest(true);
        $amount = 10;
        // Should start empty.
        $outages = outagedb::get_all();
        self::assertSame([], $outages);
        // Create some stuff outages.
        for ($i = 0; $i < $amount; $i++) {
            outagedb::save($this->createoutage($i));
        }
        // Count entries created.
        self::assertSame($amount, count(outagedb::get_all()));
    }

    /**
     * Perform some tests on the data itself, checking values after inserted and updated.
     */
    public function test_basiccrud() {
        $this->resetAfterTest(true);

        // Create some outages.
        $outages = [];
        for ($i = 1; $i <= 10; $i++) {
            $outage = $this->createoutage($i);
            $id = outagedb::save($outage);
            $outages[$id] = $outage;
        }

        // With all created outages.
        foreach ($outages as $id => $outage) {
            // Get it.
            $inserted = outagedb::get_by_id($id);
            self::assertNotNull($inserted);
            // Check its data.
            foreach (['starttime', 'stoptime', 'warningduration', 'title', 'description'] as $field) {
                self::assertSame($outage->$field, $inserted->$field, 'Field ' . $field . ' does not match.');
            }
            // Check generated data.
            self::assertGreaterThan(0, $inserted->id);
            self::assertGreaterThan(0, $inserted->lastmodified);
            self::assertNotNull($inserted->createdby);
            self::assertNotNull($inserted->modifiedby);
            // Change it.
            $inserted->title = 'Title ID' . $id;
            outagedb::save($inserted);
            // Get it again and check data.
            $updated = outagedb::get_by_id($id);
            self::assertSame('Title ID' . $id, $updated->title);
            self::assertSame($inserted->description, $updated->description);
            // Delete it.
            outagedb::delete($id);
            $deleted = outagedb::get_by_id($id);
            self::assertNull($deleted);
        }
    }

    public function test_getactive() {
        $this->resetAfterTest(true);

        // Have a consistent time for now (no seconds variation), helps debugging.
        $now = time();

        // Should never fail.
        self::assertEquals([], outagedb::get_all(), 'Ensure there are no other outages that can affect the test.');
        self::assertNull(outagedb::get_active($now), 'There should be no active outage at this point.');

        // An outage that starts in the future and is not in warning period.
        self::saveoutage($now, 2, 3, 1);
        self::assertNull(outagedb::get_active($now), 'No active outages yet.');

        // An outage that is already in the past.
        self::saveoutage($now, -3, -2, 1);
        self::assertNull(outagedb::get_active($now), 'No active outages yet.');

        // An outage in warning period.
        $activeid = self::saveoutage($now, 1, 2, 2);
        self::assertSame($activeid, outagedb::get_active($now)->id, 'Wrong active outage picked.');

        // Another outage in warning period, but ignored as it starts after the previous one.
        self::saveoutage($now, 2, 3, 3);
        self::assertSame($activeid, outagedb::get_active($now)->id, 'Wrong active outage picked.');

        // An ongoing outage.
        $activeid = self::saveoutage($now, -2, 2, 1);
        self::assertSame($activeid, outagedb::get_active($now)->id, 'Wrong active outage picked.');

        // Another ongoing outage but ignored because it started after the previous one.
        self::saveoutage($now, -1, 2, 1);
        self::assertSame($activeid, outagedb::get_active($now)->id, 'Wrong active outage picked.');

        // Another ongoing outage starting at the same time, but ignored as it stops before the previous one.
        self::saveoutage($now, -2, 1, 1);
        self::assertSame($activeid, outagedb::get_active($now)->id, 'Wrong active outage picked.');
    }

    /**
     * Helper function to create an outage then save it to the database.
     *
     * @param $now int Timestamp for now, such as time().
     * @param $start int In how many hours this outage starts. Can be negative.
     * @param $stop int In how many hours this outage finishes. Can be negative.
     * @param $warning int Warning duration in hours.
     * @return int Id the of created outage.
     */
    private static function saveoutage($now, $start, $stop, $warning) {
        return outagedb::save(new outage([
            'starttime' => $now + ($start * 60 * 60),
            'stoptime' => $now + ($stop * 60 * 60),
            'warningduration' => ($warning * 60 * 60),
            'title' => 'Test Outage',
            'description' => 'Test Outage Description.'
        ]));
    }

    /**
     * Helper function to create an outage for tests.
     *
     * @param $i int Used to populate the information.
     * @return outage The created outage.
     */
    private function createoutage($i) {
        return new outage([
            'starttime' => $i * 100,
            'stoptime' => $i * 100 + 50,
            'warningduration' => $i * 60,
            'title' => 'The Title ' . $i,
            'description' => 'A <b>description</b> in HTML.'
        ]);
    }
}

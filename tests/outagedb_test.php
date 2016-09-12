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
     * Creates an array of ids in from the given outages array.
     * @param $outages
     */
    private static function createidarray(array $outages) {
        $ids = [];
        foreach ($outages as $outage) {
            $ids[] = $outage->id;
        }
        return $ids;
    }

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
            foreach (['starttime', 'stoptime', 'warntime', 'title', 'description'] as $field) {
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

        self::assertEquals([], outagedb::get_all(), 'Ensure there are no other outages that can affect the test.');
        self::assertNull(outagedb::get_active($now), 'There should be no active outage at this point.');

        self::saveoutage($now, 1, 2, 3,
            'An outage that starts in the future and is not in warning period.');
        self::assertNull(outagedb::get_active($now), 'No active outages yet.');

        self::saveoutage($now, -3, -2, -1,
            'An outage that is already in the past.');
        self::assertNull(outagedb::get_active($now), 'No active outages yet.');

        $activeid = self::saveoutage($now, -2, 1, 2,
            'An outage in warning period.');
        self::assertSame($activeid, outagedb::get_active($now)->id, 'Wrong active outage picked.');

        self::saveoutage($now, -1, 2, 3,
            'Another outage in warning period, but ignored as it starts after the previous one.');
        self::assertSame($activeid, outagedb::get_active($now)->id, 'Wrong active outage picked.');

        $activeid = self::saveoutage($now, -3, -2, 2,
            'An ongoing outage.');
        self::assertSame($activeid, outagedb::get_active($now)->id, 'Wrong active outage picked.');

        self::saveoutage($now, -3, -1, 1,
            'Another ongoing outage but ignored because it started after the previous one.');
        self::assertSame($activeid, outagedb::get_active($now)->id, 'Wrong active outage picked.');

        self::saveoutage($now, -3, -2, 1,
            'Another ongoing outage starting at the same time, but ignored as it stops before the previous one.');
        self::assertSame($activeid, outagedb::get_active($now)->id, 'Wrong active outage picked.');
    }

    public function test_getallactive() {
        $this->resetAfterTest(true);

        // Have a consistent time for now (no seconds variation), helps debugging.
        $now = time();

        self::assertEquals([], outagedb::get_all(), 'Ensure there are no other outages that can affect the test.');
        self::assertEquals([], outagedb::get_all_active($now), 'There should be no active outages at this point.');

        self::saveoutage($now, 1, 2, 3, 'An outage that starts in the future and is not in warning period.');
        self::assertEquals([], outagedb::get_all_active($now), 'No active outages yet.');

        self::saveoutage($now, -3, -2, -1, 'An outage that is already in the past.');
        self::assertEquals([], outagedb::get_all_active($now), 'No active outages yet.');

        $id1 = self::saveoutage($now, -2, 1, 2, 'An outage in warning period.');
        self::assertEquals([$id1],
            self::createidarray(outagedb::get_all_active($now)), 'Wrong actives data.');

        $id2 = self::saveoutage($now, -1, 2, 3, 'Another outage in warning period.');
        self::assertEquals([$id1, $id2],
            self::createidarray(outagedb::get_all_active($now)), 'Wrong actives data.');

        $id3 = self::saveoutage($now, -3, -2, 2, 'An ongoing outage.');
        self::assertEquals([$id3, $id1, $id2],
            self::createidarray(outagedb::get_all_active($now)), 'Wrong actives data.');

        $id4 = self::saveoutage($now, -3, -1, 1, 'Another ongoing outage.');
        self::assertEquals([$id3, $id4, $id1, $id2],
            self::createidarray(outagedb::get_all_active($now)), 'Wrong actives data.');

        $id5 = self::saveoutage($now, -3, -2, 1, 'Yet another ongoing outage.');
        self::assertEquals([$id3, $id5, $id4, $id1, $id2],
            self::createidarray(outagedb::get_all_active($now)), 'Wrong actives data.');
    }

    public function test_getallfuture() {
        $this->resetAfterTest(true);

        // Have a consistent time for now (no seconds variation), helps debugging.
        $now = time();

        self::assertEquals([], outagedb::get_all(), 'Ensure there are no other outages that can affect the test.');
        self::assertEquals([], outagedb::get_all_future($now), 'There should be no future outages at this point.');

        self::saveoutage($now, -3, -2, -1, 'A past outage.');
        self::assertEquals([], outagedb::get_all_future($now), 'No future outages yet.');

        self::saveoutage($now, -2, 1, 2, 'An outage in warning period.');
        self::assertEquals([], outagedb::get_all_future($now), 'No future outages yet.');

        self::saveoutage($now, -3, -2, 2, 'An ongoing outage.');
        self::assertEquals([], outagedb::get_all_future($now), 'No future outages yet.');

        $id1 = self::saveoutage($now, 2, 3, 4, 'A future outage.');
        self::assertEquals([$id1],
            self::createidarray(outagedb::get_all_future($now)), 'Wrong future data.');

        $id2 = self::saveoutage($now, 1, 4, 5, 'Another future outage.');
        self::assertEquals([$id1, $id2],
            self::createidarray(outagedb::get_all_future($now)), 'Wrong future data.');

        $id3 = self::saveoutage($now, 1, 3, 5, 'Yet another future outage.');
        self::assertEquals([$id3, $id1, $id2],
            self::createidarray(outagedb::get_all_future($now)), 'Wrong future data.');
    }

    public function test_getallpast() {
        $this->resetAfterTest(true);

        // Have a consistent time for now (no seconds variation), helps debugging.
        $now = time();

        self::assertEquals([], outagedb::get_all(), 'Ensure there are no other outages that can affect the test.');
        self::assertEquals([], outagedb::get_all_past($now), 'There should be no future outages at this point.');

        self::saveoutage($now, -2, 1, 2, 'An outage in warning period.');
        self::assertEquals([], outagedb::get_all_past($now), 'No past outages yet.');

        self::saveoutage($now, -3, -2, 2, 'An ongoing outage.');
        self::assertEquals([], outagedb::get_all_past($now), 'No past outages yet.');

        self::saveoutage($now, 2, 3, 4, 'A future outage.');
        self::assertEquals([], outagedb::get_all_past($now), 'No past outages yet.');

        $id1 = self::saveoutage($now, -8, -6, -4, 'A past outage.');
        self::assertEquals([$id1],
            self::createidarray(outagedb::get_all_past($now)), 'Wrong past data.');

        $id2 = self::saveoutage($now, -8, -7, -5, 'Another past outage.');
        self::assertEquals([$id1, $id2],
            self::createidarray(outagedb::get_all_past($now)), 'Wrong past data.');

        $id3 = self::saveoutage($now, -8, -5, -3, 'Yet another past outage.');
        self::assertEquals([$id3, $id1, $id2],
            self::createidarray(outagedb::get_all_past($now)), 'Wrong past data.');
    }

    /**
     * Helper function to create an outage then save it to the database.
     *
     * @param $now int Timestamp for now, such as time().
     * @param $warning int In how many hours the warning starts. Can be negative.
     * @param $start int In how many hours this outage starts. Can be negative.
     * @param $stop int In how many hours this outage finishes. Can be negative.
     * @param $title string Title for the outage.
     * @return int Id the of created outage.
     */
    private static function saveoutage($now, $warning, $start, $stop, $title) {
        return outagedb::save(new outage([
            'starttime' => $now + ($start * 60 * 60),
            'stoptime' => $now + ($stop * 60 * 60),
            'warntime' => $now + ($warning * 60 * 60),
            'title' => $title,
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
            'warntime' => $i * 60,
            'title' => 'The Title ' . $i,
            'description' => 'A <b>description</b> in HTML.'
        ]);
    }
}

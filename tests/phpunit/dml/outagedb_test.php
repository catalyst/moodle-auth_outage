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
 * outagedb_test tests class.
 *
 * @package     auth_outage
 * @author      Daniel Thee Roperto <daniel.roperto@catalyst-au.net>
 * @copyright   2016 Catalyst IT
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use auth_outage\dml\outagedb;
use auth_outage\local\outage;

defined('MOODLE_INTERNAL') || die();
require_once(__DIR__.'/../base_testcase.php');

/**
 * outagedb_test tests class.
 *
 * @package     auth_outage
 * @author      Daniel Thee Roperto <daniel.roperto@catalyst-au.net>
 * @copyright   2016 Catalyst IT
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class auth_outage_outagedb_test extends auth_outage_base_testcase {
    /**
     * Creates an array of ids in from the given outages array.
     * @param outage[] $outages An array of outages.
     * @return int[] An array with the keys of the outages as values.
     */
    private static function createidarray(array $outages) {
        $ids = [];
        foreach ($outages as $outage) {
            $ids[] = $outage->id;
        }
        return $ids;
    }

    /**
     * Helper function to create an outage then save it to the database.
     *
     * @param bool $autostart If outage should automatically start.
     * @param int $now Timestamp for now, such as time().
     * @param int $warning In how many hours the warning starts. Can be negative.
     * @param int $start In how many hours this outage starts. Can be negative.
     * @param int $stop In how many hours this outage finishes. Can be negative.
     * @param string $title Title for the outage.
     * @param int|null $finished In how many hours this outage is marked as finished. Can be negative or null.
     * @return int Id the of created outage.
     */
    private static function saveoutage($autostart, $now, $warning, $start, $stop, $title, $finished = null) {
        return outagedb::save(new outage([
            'autostart' => $autostart,
            'warntime' => $now + ($warning * 60 * 60),
            'starttime' => $now + ($start * 60 * 60),
            'stoptime' => $now + ($stop * 60 * 60),
            'finished' => is_null($finished) ? null : ($now + ($finished * 60 * 60)),
            'title' => $title,
            'description' => 'Test Outage Description.',
        ]));
    }

    /**
     * Ensure DB tests run as admin.
     */
    public function setUp(): void {
        parent::setUp();
        $this->setAdminUser();
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
     * Create a few outages, fetch them and check if fields match.
     */
    public function test_saved_fields() {
        $this->resetAfterTest(true);
        for ($i = 0; $i < 4; $i++) {
            $expected = $this->createoutage($i);
            $expected->id = outagedb::save($expected);
            $actual = outagedb::get_by_id($expected->id);
            // Ignore the following fields.
            $expected->lastmodified = $actual->lastmodified;
            $expected->createdby = $actual->createdby;
            $expected->modifiedby = $actual->modifiedby;
            // Check if fields are the same.
            self::assertEquals($expected, $actual, 'Failed for $i='.$i);
        }
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
     * Make sure we can finish outages.
     */
    public function test_finish() {
        $now = time();
        $this->resetAfterTest(true);
        // Create it.
        $id = self::saveoutage(false, $now, -3, -2, 2, 'An ongoing outage.');
        $outage = outagedb::get_by_id($id);
        self::assertTrue($outage->is_active($now));
        self::assertTrue($outage->is_ongoing($now));
        self::assertSame(null, $outage->finished);
        // Finish it.
        outagedb::finish($id, $now);
        $outage = outagedb::get_by_id($id);
        self::assertSame($now, $outage->finished);
        self::assertFalse($outage->is_active($now));
        self::assertFalse($outage->is_ongoing($now));
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
                self::assertSame($outage->$field, $inserted->$field, 'Field '.$field.' does not match.');
            }
            // Check generated data.
            self::assertGreaterThan(0, $inserted->id);
            self::assertGreaterThan(0, $inserted->lastmodified);
            self::assertNotNull($inserted->createdby);
            self::assertNotNull($inserted->modifiedby);
            // Change it.
            $inserted->title = 'Title ID'.$id;
            outagedb::save($inserted);
            // Get it again and check data.
            $updated = outagedb::get_by_id($id);
            self::assertSame('Title ID'.$id, $updated->title);
            self::assertSame($inserted->description, $updated->description);
            // Delete it.
            outagedb::delete($id);
            $deleted = outagedb::get_by_id($id);
            self::assertNull($deleted);
        }
    }

    /**
     * Tests the outagedb::get_active() method.
     */
    public function test_getactive() {
        $this->resetAfterTest(true);

        // Have a consistent time for now (no seconds variation), helps debugging.
        $now = time();

        self::assertEquals([], outagedb::get_all(), 'Ensure there are no other outages that can affect the test.');
        self::assertNull(outagedb::get_active($now), 'There should be no active outage at this point.');

        self::saveoutage(false, $now, 1, 2, 3, 'An outage that starts in the future and is not in warning period.');
        self::assertNull(outagedb::get_active($now), 'No active outages yet.');

        self::saveoutage(false, $now, -3, -2, -1, 'An outage that is already in the past.');
        self::assertNull(outagedb::get_active($now), 'No active outages yet.');

        $activeid = self::saveoutage(false, $now, -2, 1, 2, 'An outage in warning period.');
        self::assertSame($activeid, outagedb::get_active($now)->id, 'Wrong active outage picked.');

        $activeid = self::saveoutage(false, $now, -2, 0, 2, 'An outage starts now.');
        self::assertSame($activeid, outagedb::get_active($now)->id, 'Wrong active outage picked.');

        self::saveoutage(false, $now, -2, 0, -1, 'Invalid outage.');
        self::assertSame($activeid, outagedb::get_active($now)->id, 'Wrong active outage picked.');

        self::saveoutage(false, $now, -2, 0, 0, 'Invalid outage.');
        self::assertSame($activeid, outagedb::get_active($now)->id, 'Wrong active outage picked.');

        self::saveoutage(false, $now, -1, 2, 3,
            'Another outage in warning period, but ignored as it starts after the previous one.');
        self::assertSame($activeid, outagedb::get_active($now)->id, 'Wrong active outage picked.');

        self::saveoutage(false, $now, -3, -2, 2, 'An finished outage.', -1);
        self::assertSame($activeid, outagedb::get_active($now)->id, 'Wrong active outage picked.');

        $activeid = self::saveoutage(false, $now, -3, -2, 2, 'An ongoing outage.');
        self::assertSame($activeid, outagedb::get_active($now)->id, 'Wrong active outage picked.');

        self::saveoutage(false, $now, -3, -1, 1, 'Another ongoing outage but ignored because it started after the previous one.');
        self::assertSame($activeid, outagedb::get_active($now)->id, 'Wrong active outage picked.');

        self::saveoutage(false, $now, -3, -2, 1,
            'Another ongoing outage starting at the same time, but ignored as it stops before the previous one.');
        self::assertSame($activeid, outagedb::get_active($now)->id, 'Wrong active outage picked.');
    }

    /**
     * Tests the outagedb::get_all_unended() method.
     */
    public function test_getallunended() {
        $this->resetAfterTest(true);

        // Have a consistent time for now (no seconds variation), helps debugging.
        $now = time();

        self::assertEquals([], outagedb::get_all(), 'Ensure there are no other outages that can affect the test.');
        self::assertEquals([], outagedb::get_all_unended($now), 'There should be no future outages at this point.');

        self::saveoutage(false, $now, -3, -2, -1, 'A past outage.');
        self::assertEquals([], outagedb::get_all_unended($now), 'No future outages yet.');

        self::saveoutage(false, $now, -3, -2, 2, 'A finished outage.', -1);
        self::assertEquals([], outagedb::get_all_unended($now), 'No future outages yet.');

        $id1 = self::saveoutage(false, $now, 2, 3, 4, 'A future outage.');
        self::assertEquals([$id1],
            self::createidarray(outagedb::get_all_unended($now)), 'Wrong future data.');

        $id2 = self::saveoutage(false, $now, 1, 4, 5, 'Another future outage.');
        self::assertEquals([$id1, $id2],
            self::createidarray(outagedb::get_all_unended($now)), 'Wrong future data.');

        $id3 = self::saveoutage(false, $now, 1, 3, 5, 'Yet another future outage.');
        self::assertEquals([$id3, $id1, $id2],
            self::createidarray(outagedb::get_all_unended($now)), 'Wrong future data.');

        $id4 = self::saveoutage(false, $now, -2, 1, 2, 'An outage in warning period.');
        self::assertEquals([$id4, $id3, $id1, $id2],
            self::createidarray(outagedb::get_all_unended($now)), 'Wrong future data.');

        $id5 = self::saveoutage(false, $now, -1, 2, 3, 'Another outage in warning period.');
        self::assertEquals([$id4, $id5, $id3, $id1, $id2],
            self::createidarray(outagedb::get_all_unended($now)), 'Wrong future data.');

        $id6 = self::saveoutage(false, $now, -3, -2, 2, 'An ongoing outage.');
        self::assertEquals([$id6, $id4, $id5, $id3, $id1, $id2],
            self::createidarray(outagedb::get_all_unended($now)), 'Wrong future data.');

        $id7 = self::saveoutage(false, $now, -3, -1, 1, 'Another ongoing outage.');
        self::assertEquals([$id6, $id7, $id4, $id5, $id3, $id1, $id2],
            self::createidarray(outagedb::get_all_unended($now)), 'Wrong future data.');

        $id8 = self::saveoutage(false, $now, -3, -2, 1, 'Yet another ongoing outage.');
        self::assertEquals([$id6, $id8, $id7, $id4, $id5, $id3, $id1, $id2],
            self::createidarray(outagedb::get_all_unended($now)), 'Wrong future data.');
    }

    /**
     * Tests the outagedb::get_all_ended() method.
     */
    public function test_getallended() {
        $this->resetAfterTest(true);

        // Have a consistent time for now (no seconds variation), helps debugging.
        $now = time();

        self::assertEquals([], outagedb::get_all(), 'Ensure there are no other outages that can affect the test.');
        self::assertEquals([], outagedb::get_all_ended($now), 'There should be no future outages at this point.');

        self::saveoutage(false, $now, -2, 1, 2, 'An outage in warning period.');
        self::assertEquals([], outagedb::get_all_ended($now), 'No past outages yet.');

        self::saveoutage(false, $now, -3, -2, 2, 'An ongoing outage.');
        self::assertEquals([], outagedb::get_all_ended($now), 'No past outages yet.');

        self::saveoutage(false, $now, 2, 3, 4, 'A future outage.');
        self::assertEquals([], outagedb::get_all_ended($now), 'No past outages yet.');

        $id1 = self::saveoutage(false, $now, -8, -6, -4, 'A past outage.');
        self::assertEquals([$id1],
            self::createidarray(outagedb::get_all_ended($now)), 'Wrong past data.');

        $id2 = self::saveoutage(false, $now, -8, -7, -5, 'Another past outage.');
        self::assertEquals([$id1, $id2],
            self::createidarray(outagedb::get_all_ended($now)), 'Wrong past data.');

        $id3 = self::saveoutage(false, $now, -8, -5, -3, 'Yet another past outage.');
        self::assertEquals([$id3, $id1, $id2],
            self::createidarray(outagedb::get_all_ended($now)), 'Wrong past data.');

        $id4 = self::saveoutage(false, $now, -3, -2, 2, 'A finished outage.', -1);
        self::assertEquals([$id4, $id3, $id1, $id2],
            self::createidarray(outagedb::get_all_ended($now)), 'Wrong past data.');
    }

    /**
     * Tests the outagedb::get_by_id() with an invalid parameter.
     */
    public function test_getbyid_invalid() {
        $this->resetAfterTest(true);
        $this->set_expected_exception('coding_exception');
        outagedb::get_by_id(-1);
    }

    /**
     * Tests the outagedb::delete() with an invalid parameter.
     */
    public function test_delete_invalid() {
        $this->resetAfterTest(true);
        $this->set_expected_exception('coding_exception');
        outagedb::delete(-1);
    }

    /**
     * Tests the outagedb::get_active() with an invalid parameter.
     */
    public function test_getactive_invalid() {
        $this->resetAfterTest(true);
        $this->set_expected_exception('coding_exception');
        outagedb::get_active(-1);
    }

    /**
     * Tests the outagedb::get_all_unended() with an invalid parameter.
     */
    public function test_getallunended_invalid() {
        $this->resetAfterTest(true);
        $this->set_expected_exception('coding_exception');
        outagedb::get_all_unended(-1);
    }

    /**
     * Checks we can execute outagedb::get_all_unended() without parameters (now).
     */
    public function test_getallunended_now() {
        $this->resetAfterTest(true);
        self::assertEmpty(outagedb::get_all_unended());
    }

    /**
     * Tests the outagedb::get_all_ended() with an invalid parameter.
     */
    public function test_getallended_invalid() {
        $this->resetAfterTest(true);
        $this->set_expected_exception('coding_exception');
        outagedb::get_all_ended(-1);
    }

    /**
     * Checks we can execute outagedb::test_getallended_now() without parameters (now).
     */
    public function test_getallended_now() {
        $this->resetAfterTest(true);
        self::assertEmpty(outagedb::get_all_ended());
    }

    /**
     * Tests the outagedb::finish() with an invalid parameter.
     */
    public function test_finish_invalid() {
        $this->resetAfterTest(true);
        $this->set_expected_exception('coding_exception');
        outagedb::finish(1, -1);
    }

    /**
     * Tests the outagedb::finish() with a non existing outage.
     */
    public function test_finish_now_notfound() {
        $this->resetAfterTest(true);
        outagedb::finish(1);
        self::assertCount(1, $this->getDebuggingMessages());
        $this->resetDebugging();
    }

    /**
     * Try to finish not ongoing outages.
     */
    public function test_finish_notongoing() {
        $this->resetAfterTest(true);
        $time = time();
        $outage = new outage([
            'autostart' => false,
            'warntime' => $time + (60 * 60 * 24 * 1),
            'starttime' => $time + (60 * 60 * 24 * 2),
            'stoptime' => $time + (60 * 60 * 24 * 3),
            'title' => 'Title',
            'description' => 'Description',
        ]);
        $id = outagedb::save($outage);
        self::assertTrue(!$outage->is_ongoing($time));

        outagedb::finish($id, $time);
        self::assertCount(1, $this->getDebuggingMessages());
        $this->resetDebugging();
    }

    /**
     * Tests the outagedb::get_next_starting() with an invalid parameter.
     */
    public function test_getnextstartinginvalid() {
        $this->resetAfterTest(true);
        $this->set_expected_exception('coding_exception');
        outagedb::get_next_starting(-1);
    }

    /**
     * Tests the outagedb::get_next_autostarting() with an invalid parameter.
     */
    public function test_getnextautostartinginvalid() {
        $this->resetAfterTest(true);
        $this->set_expected_exception('coding_exception');
        outagedb::get_next_autostarting(-1);
    }

    /**
     * Helper function to create an outage for tests.
     * @param int $i Used to populate the information.
     * @return outage The created outage.
     */
    private function createoutage($i) {
        return new outage([
            'autostart' => ($i % 2 == 0),
            'starttime' => $i * 100,
            'stoptime' => $i * 100 + 50,
            'warntime' => $i * 60,
            'title' => 'The Title '.$i,
            'description' => 'A <b>description</b> in HTML.',
        ]);
    }
}

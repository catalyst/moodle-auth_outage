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


class outagedb_test extends advanced_testcase
{
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
        $outage = outagedb::getbyid($id);
        self::assertNotNull($outage);
        // Delete it.
        outagedb::delete($id);
        // Get should be null.
        $outage = outagedb::getbyid($id);
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
        self::assertNull(outagedb::getbyid($id));
    }

    /**
     * Make sure getall brings all entries.
     */
    public function test_getall() {
        $this->resetAfterTest(true);
        $amount = 10;
        // Should start empty.
        $outages = outagedb::getall();
        self::assertSame([], $outages);
        // Create some stuff outages.
        for ($i = 0; $i < $amount; $i++) {
            outagedb::save($this->createoutage($i));
        }
        // Count entries created.
        self::assertSame($amount, count(outagedb::getall()));
    }

    /**
     * Perform some tests on the data itself, checking values after inserted and updated.
     */
    public function test_basiccrud() {
        return;
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
            $inserted = outagedb::getbyid($id);
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
            $updated = outagedb::getbyid($id);
            self::assertSame('Title ID' . $id, $updated->title);
            self::assertSame($inserted->description, $updated->description);
            // Delete it.
            outagedb::delete($id);
            $deleted = outagedb::getbyid($id);
            self::assertNull($deleted);
        }
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

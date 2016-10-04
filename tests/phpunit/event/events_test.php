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
 * events_test tests class.
 *
 * @package         auth_outage
 * @author          Daniel Thee Roperto <daniel.roperto@catalyst-au.net>
 * @copyright       Catalyst IT
 * @license         http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use auth_outage\dml\outagedb;
use auth_outage\local\outage;

defined('MOODLE_INTERNAL') || die();

/**
 * events_test tests class.
 *
 * @package         auth_outage
 * @author          Daniel Thee Roperto <daniel.roperto@catalyst-au.net>
 * @copyright       Catalyst IT
 * @license         http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class events_test extends advanced_testcase {
    /**
     * Saves an outage and check if the event was created.
     * @return array With the outage id and the event id.
     */
    public function test_save() {
        global $DB;
        self::setAdminUser();
        $this->resetAfterTest(false);

        // Save new outage.
        $now = time();
        $id = outagedb::save(new outage([
            'autostart' => false,
            'warntime' => $now - 60,
            'starttime' => 60,
            'stoptime' => 120,
            'title' => 'Title',
            'description' => 'Description',
        ]));

        // Check existance.
        $event = $DB->get_record_select(
            'event',
            "(eventtype = 'auth_outage' AND instance = :outageid)",
            ['outageid' => $id],
            'id',
            IGNORE_MISSING
        );
        self::assertTrue(is_object($event));

        // Another test will use it.
        return [$id, $event->id];
    }

    /**
     * Updates an outage and checks if the event was updated.
     * @param int[] $ids Outage id and event id.
     * @return int[] same as input parameter.
     * @depends test_save
     */
    public function test_update(array $ids) {
        global $DB;

        self::setAdminUser();
        $this->resetAfterTest(false);

        list($idoutage, $idevent) = $ids;
        $outage = outagedb::get_by_id($idoutage);
        $outage->starttime += 10;
        outagedb::save($outage);

        // Should still exist.
        $event = $DB->get_record_select(
            'event',
            "(eventtype = 'auth_outage' AND instance = :idoutage)",
            ['idoutage' => $idoutage],
            'id',
            IGNORE_MISSING
        );
        self::assertTrue(is_object($event));
        self::assertSame($idevent, $event->id);

        return $ids;
    }

    /**
     * Deletes an outage and checks if the event was deleted.
     * @param int[] $ids Outage id and event id.
     * @depends test_update
     */
    public function test_delete($ids) {
        global $DB;

        self::setAdminUser();
        $this->resetAfterTest(true);
        list($idoutage, $idevent) = $ids;

        outagedb::delete($idoutage);

        // Should not exist.
        $event = $DB->get_record_select(
            'event',
            "(eventtype = 'auth_outage' AND instance = :idoutage) OR (id = :idevent)",
            ['idoutage' => $idoutage, 'idevent' => $idevent],
            'id',
            IGNORE_MISSING
        );
        self::assertFalse($event);
    }
}

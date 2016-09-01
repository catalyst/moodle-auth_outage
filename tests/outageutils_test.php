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
 * Tests performed on outageutils class.
 *
 * @package    auth_outage
 * @author     Daniel Thee Roperto <daniel.roperto@catalyst-au.net>
 * @copyright  Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use \auth_outage\outageutils;

defined('MOODLE_INTERNAL') || die();


class outageutils_test extends basic_testcase
{
    public function test_data2object() {
        // Using object data, no new fields, not strict.
        $obj = new stdClass();
        $obj->foo = 'bar';
        $obj->number = 42;
        $data = new stdClass();
        $data->foo = 'not bar';
        outageutils::data2object($data, $obj, false);
        self::assertEquals(get_object_vars($obj), ['foo' => 'not bar', 'number' => 42], 'Invalid result.');
        self::assertEquals(get_object_vars($data), ['foo' => 'not bar'], 'Data should not change.');

        // Using array data, with new fields, not strict.
        $obj = new stdClass();
        $obj->foo = 'bar';
        $obj->number = 42;
        $data = ['foo' => 'foobar', 'flag' => false];
        outageutils::data2object($data, $obj, false);
        self::assertEquals(get_object_vars($obj), ['foo' => 'foobar', 'number' => 42], 'Invalid result.');

        // Using object data, no new fields, strict.
        $obj = new stdClass();
        $obj->foo = 'bar';
        $obj->number = 42;
        $data = new stdClass();
        $data->foo = 'not bar';
        outageutils::data2object($data, $obj, true);
        self::assertEquals(get_object_vars($obj), ['foo' => 'not bar', 'number' => 42], 'Invalid result.');
        self::assertEquals(get_object_vars($data), ['foo' => 'not bar'], 'Data should not change.');

        // Using array data, with new fields, strict.
        $obj = new stdClass();
        $obj->foo = 'bar';
        $obj->number = 42;
        $data = ['foo' => 'foobar', 'flag' => false];
        try {
            outageutils::data2object($data, $obj, true);
            $this->fail('Exception was expected.');
        }
        catch (InvalidArgumentException $e){
        }
    }
}

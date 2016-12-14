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
 * history_table class.
 *
 * @package    auth_outage
 * @author     Daniel Thee Roperto <danielroperto@catalyst-au.net>
 * @copyright  2016 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace auth_outage\output\manage;

use auth_outage\local\outage;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir.'/tablelib.php');

/**
 * history_table class.
 *
 * @package    auth_outage
 * @author     Daniel Thee Roperto <danielroperto@catalyst-au.net>
 * @copyright  2016 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class history_table extends base_table {
    /**
     * Constructor
     */
    public function __construct() {
        parent::__construct();

        $this->define_columns(['warning', 'starts', 'durationplanned', 'durationactual', 'title', 'actions']);

        $this->define_headers([
                get_string('tableheaderwarnbefore', 'auth_outage'),
                get_string('tableheaderstartedtime', 'auth_outage'),
                get_string('tableheaderdurationplanned', 'auth_outage'),
                get_string('tableheaderdurationactual', 'auth_outage'),
                get_string('tableheadertitle', 'auth_outage'),
                get_string('actions'),
            ]
        );

        $this->setup();
    }

    /**
     * Sets the data of the table.
     * @param outage[] $outages An array with outage objects.
     */
    public function show_data(array $outages) {
        foreach ($outages as $outage) {
            $finished = $outage->get_duration_actual();
            $finished = is_null($finished) ? '-' : format_time($finished);
            $this->add_data([
                format_time($outage->get_warning_duration()),
                self::create_starttime_string($outage->starttime),
                format_time($outage->get_duration_planned()),
                $finished,
                $outage->get_title(),
                $this->create_data_buttons($outage, false),
            ]);
        }
    }
}

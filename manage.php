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
 * List outages
 *
 * @package    auth_outage
 * @author     Daniel Thee Roperto <daniel.roperto@catalyst-au.net>
 * @copyright  2016 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use auth_outage\dml\outagedb;
use auth_outage\output\renderer;
use auth_outage\local\outagelib;

require_once(__DIR__.'/../../config.php');
require_once($CFG->libdir.'/adminlib.php');

admin_externalpage_setup('auth_outage_manage');
$PAGE->set_url(new moodle_url('/auth/outage/manage.php'));
$output = $PAGE->get_renderer('auth_outage');

echo $output->header();

// Give it a consistent time so all outages are listed. Useful when debugging.
$now = time();

$output->output_view('manage.php', [
    'unended' => outagedb::get_all_unended($now),
    'ended'   => outagedb::get_all_ended($now),
    'warning' => outagelib::generate_plugin_configuration_warning(),
]);

echo $output->footer();

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
 * @copyright  Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use auth_outage\outagedb;
use auth_outage\outagelib;

require_once('../../config.php');

$id = optional_param('id', null, PARAM_INT);
$outage = is_null($id) ? outagedb::get_active() : outagedb::get_by_id($id);
if (is_null($outage)) {
    redirect(new moodle_url('/'));
}

if (optional_param('static', false, PARAM_BOOL)) {
    echo outagelib::get_renderer()->renderoutagepagestatic($outage);
} else {
    $PAGE->set_title($outage->get_title());
    $PAGE->set_heading($outage->get_title());
    $PAGE->set_url(new \moodle_url('/auth/outage/info.php'));

    // No hooks injecting into this page, do it manually.
    outagelib::inject();

    echo $OUTPUT->header();

    echo outagelib::get_renderer()->renderoutagepage($outage);

    echo $OUTPUT->footer();
}

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
 * Mark an outage as finished.
 *
 * @package    auth_outage
 * @author     Daniel Thee Roperto <daniel.roperto@catalyst-au.net>
 * @copyright  2016 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use auth_outage\dml\outagedb;
use auth_outage\form\outage\finish;
use auth_outage\output\renderer;

require_once(__DIR__.'/../../config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->libdir.'/formslib.php');

admin_externalpage_setup('auth_outage_manage');
$PAGE->set_url(new moodle_url('/auth/outage/manage.php'));

$mform = new finish();
if ($mform->is_cancelled()) {
    redirect($CFG->wwwroot.'/auth/outage/manage.php');
} else if ($fromform = $mform->get_data()) {
    outagedb::finish($fromform->id);
    redirect($CFG->wwwroot.'/auth/outage/manage.php');
}

$id = required_param('id', PARAM_INT);
$outage = outagedb::get_by_id($id);
if ($outage == null) {
    throw new invalid_parameter_exception('Outage #'.$id.' not found.');
}

$dataid = new stdClass();
$dataid->id = $outage->id;
$mform->set_data($dataid);

echo $OUTPUT->header();

echo renderer::get()->renderfinishconfirmation($outage);

$mform->display();

echo $OUTPUT->footer();

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
 * Edit an outage.
 *
 * @package    auth_outage
 * @author     Daniel Thee Roperto <daniel.roperto@catalyst-au.net>
 * @copyright  2016 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use auth_outage\dml\outagedb;
use auth_outage\form\outage\edit;
use auth_outage\local\outage;
use auth_outage\local\outagelib;
use auth_outage\output\renderer;

require_once(__DIR__.'/../../config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->libdir.'/formslib.php');

admin_externalpage_setup('auth_outage_manage');
$output = $PAGE->get_renderer('auth_outage');
$PAGE->set_url(new moodle_url('/auth/outage/manage.php'));

$mform = new edit();

if ($mform->is_cancelled()) {
    redirect(new moodle_url('/auth/outage/manage.php'));
} else if ($outage = $mform->get_data()) {
    $id = outagedb::save($outage);
    redirect(new moodle_url('/auth/outage/manage.php'));
}

$clone = optional_param('clone', 0, PARAM_INT);
$edit = optional_param('edit', 0, PARAM_INT);
$time = optional_param('starttime', 0, PARAM_INT);
if ($clone && $edit) {
    throw new invalid_parameter_exception('Cannot provide both clone and edit ids.');
}
if ($clone) {
    // Remove outage id to force creating a new one.
    $outage = outagedb::get_by_id($clone);
    $outage->id = null;
    $action = 'outageclone';
} else if ($edit) {
    $outage = outagedb::get_by_id($edit);
    $action = 'outageedit';
} else {
    $config = outagelib::get_config();
    if (empty($time)) {
        $time = outagelib::get_next_window();
    }

    $outage = new outage([
        'autostart' => $config->default_autostart,
        'starttime' => $time,
        'stoptime' => $time + $config->default_duration,
        'warntime' => $time - $config->default_warning_duration,
        'title' => $config->default_title,
        'description' => $config->default_description,
    ]);
    $action = 'outagecreate';
}

if ($outage == null) {
    throw new invalid_parameter_exception('Outage not found.');
}

$mform->set_data($outage);

$PAGE->navbar->add(get_string($action.'crumb', 'auth_outage'));
echo $output->header();
echo $output->rendersubtitle($action);
$mform->display();
echo $output->footer();

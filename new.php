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
 * Create new outage.
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

require_once(__DIR__.'/../../config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->libdir.'/formslib.php');

outagelib::page_setup();

$mform = new edit();
if ($mform->is_cancelled()) {
    redirect('/auth/outage/manage.php');
} else if ($outage = $mform->get_data()) {
    $id = outagedb::save($outage);
    redirect('/auth/outage/manage.php#auth_outage_id_'.$id);
}

$config = outagelib::get_config();
$defaults = new outage([
    'autostart' => $config->default_autostart,
    'starttime' => time(),
    'stoptime' => time() + $config->default_duration,
    'warntime' => time() - $config->default_warning_duration,
    'title' => $config->default_title,
    'description' => $config->default_description,
]);
$mform->set_data($defaults);

$PAGE->navbar->add(get_string('outagecreate', 'auth_outage'));
echo $OUTPUT->header();

$mform->display();

echo $OUTPUT->footer();

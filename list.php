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

use \auth_outage\outage;

require_once('../../config.php');
require_once($CFG->libdir . '/adminlib.php');

// TODO Check parameters.
//$triggerid = optional_param('id', 0, PARAM_INT);
// $datatype = required_param('datatype', PARAM_ALPHANUM);
// if (empty($datatype)) { throw new coding_exception('required_param() requires $parname and $type to be specified (parameter: datatype)'); }

// Page API: https://docs.moodle.org/dev/Page_API#.24PAGE_The_Moodle_page_global .
admin_externalpage_setup('auth_outage_list'); // Does require_login and set_context inside.
$PAGE->set_url(new moodle_url('/auth/outage/list.php'));
$PAGE->set_title('Outage List');
$PAGE->set_heading('List of registered outages.');
//$PAGE->requires->css('/local/extension/styles.css');

$renderer = $PAGE->get_renderer('auth_outage');

$outage_list = [];
for ($i = 1; $i <= 10; $i++) {
    $outage_list[$i] = new outage();
    $outage_list[$i]->id = $i;
    $outage_list[$i]->start_time = time();
    $outage_list[$i]->stop_time = time() + 60 * 60 * 4; // 4 hours.
    $outage_list[$i]->warning_minutes = 10 * $i;
    $outage_list[$i]->title = 'Outage #' . $i;
    $outage_list[$i]->description = 'This is the Outage #' . $i . ', backup creation.';
    $outage_list[$i]->created_by = 1;
    $outage_list[$i]->modified_by = 1;
    $outage_list[$i]->last_modified = time() - 60 * 60 * 10; // -10 hours.
};

echo $OUTPUT->header();

echo $renderer->render_outage_list($outage_list);

//$form = new \auth_outage\listform();


/*
$data = null;
$editordata = array(
    'template_notify' => array('text' => get_string('template_notify_content', 'local_extension')),
    'template_user' => array('text' => get_string('template_user_content', 'local_extension')),
);

if (!empty($triggerid) && confirm_sesskey()) {
    $data = \local_extension\rule::from_id($triggerid);

    // Set the saved serialised data as object properties, which will be loaded as default form values.
    // If and only if the form elements have the same name, and they have been saved to the data variable.
    if (!empty($data->data)) {
        foreach ($data->data as $key => $value) {

            if (strpos($key, 'template') === 0) {
                if (!empty($value)) {
                    $editordata[$key] = array('text' => $value);
                }
            }

            $data->$key = $value;
        }
    }
}

$rules = \local_extension\rule::load_all($datatype);
$sorted = \local_extension\utility::rule_tree($rules);

$params = array(
    'ruleid' => $triggerid,
    'rules' => $sorted,
    'datatype' => $datatype,
    'editordata' => $editordata,
);

//// MFORM


$mform->set_data($data);

if ($mform->is_cancelled()) {

    $url = new moodle_url('/local/extension/manage.php');
    redirect($url);
    die;

} else if ($form = $mform->get_data()) {

    $rule = new \local_extension\rule();

    // Also saves template_ form items to the custom data variable.
    $rule->load_from_form($form);

    if (!empty($rule->id)) {
        $DB->update_record('local_extension_triggers', $rule);
    } else {
        $DB->insert_record('local_extension_triggers', $rule);
    }

    $url = new moodle_url('/local/extension/manage.php');
    redirect($url);
    die;

}
*/
//echo $mform->display();
echo $OUTPUT->footer();

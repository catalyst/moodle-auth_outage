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

namespace auth_outage\forms\outage;

use \auth_outage\models\outage;

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.'); // It must be included from a Moodle page.
}

require_once($CFG->libdir . '/formslib.php');

/**
 * Outage form.
 *
 * @package    auth_outage
 * @author     Daniel Thee Roperto <daniel.roperto@catalyst-au.net>
 * @copyright  Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class edit extends \moodleform {
    const TITLE_MAX_CHARS = 100;

    /**
     * {@inheritDoc}
     * @see moodleform::definition()
     */
    public function definition() {
        $mform = $this->_form;

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        $mform->addElement('date_time_selector', 'starttime', get_string('starttime', 'auth_outage'));

        $mform->addElement('duration', 'outageduration', get_string('outageduration', 'auth_outage'));

        $mform->addElement('duration', 'warningduration', get_string('warningduration', 'auth_outage'));

        $mform->addElement(
            'text',
            'title',
            get_string('title', 'auth_outage'),
            'maxlength="' . self::TITLE_MAX_CHARS . '"'
        );
        $mform->setType('title', PARAM_TEXT);

        $mform->addElement('editor', 'description', get_string('description', 'auth_outage'));

        $mform->addElement('static', 'usagehints', '', get_string('textplaceholdershint', 'auth_outage'));

        $this->add_action_buttons();
    }

    /**
     * Validate the parts of the request form for this module
     *
     * @param array $data An array of form data
     * @param array $files An array of form files
     * @return array of error messages
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        if ($data['outageduration'] <= 0) {
            $errors['outageduration'] = get_string('outagedurationerrorinvalid', 'auth_outage');
        }
        if ($data['warningduration'] <= 0) {
            $errors['warningduration'] = get_string('warningdurationerrorinvalid', 'auth_outage');
        }

        $titlelen = strlen(trim($data['title']));
        if ($titlelen == 0) {
            $errors['title'] = get_string('titleerrorinvalid', 'auth_outage');
        }
        if ($titlelen > self::TITLE_MAX_CHARS) {
            $errors['title'] = get_string('titleerrortoolong', 'auth_outage', self::TITLE_MAX_CHARS);
        }

        return $errors;
    }

    /**
     * Return submitted data if properly submitted or returns NULL if validation fails.
     * @return outage submitted data; NULL if not valid or not submitted or cancelled
     */
    public function get_data() {
        // Fetch data and check if description is the correct format.
        $data = parent::get_data();
        if (is_null($data)) {
            return null;
        }
        if ($data->description['format'] != '1') {
            debugging('Not implemented for format ' . $data->description['format'], DEBUG_DEVELOPER);
            return null;
        }
        // Return an outage.
        return new outage([
            'id' => ($data->id === 0) ? null : $data->id,
            'starttime' => $data->starttime,
            'stoptime' => $data->starttime + $data->outageduration,
            'warntime' => $data->starttime - $data->warningduration,
            'title' => $data->title,
            'description' => $data->description['text']
        ]);
    }

    /**
     * Load in existing outage as form defaults.
     *
     * @param outage $outage outage object with default values
     */
    public function set_data(outage $outage) {
        $this->_form->setDefaults([
            'id' => $outage->id,
            'starttime' => $outage->starttime,
            'outageduration' => $outage->stoptime - $outage->starttime,
            'warningduration' => $outage->starttime - $outage->warntime,
            'title' => $outage->title,
            'description' => ['text' => $outage->description, 'format' => '1']
        ]);
    }
}

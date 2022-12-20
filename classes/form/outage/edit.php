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
 * edit class.
 *
 * @package    auth_outage
 * @author     Daniel Thee Roperto <daniel.roperto@catalyst-au.net>
 * @copyright  2016 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace auth_outage\form\outage;

use auth_outage\local\outage;
use coding_exception;
use moodleform;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir.'/formslib.php');

/**
 * edit class.
 *
 * @package    auth_outage
 * @author     Daniel Thee Roperto <daniel.roperto@catalyst-au.net>
 * @copyright  2016 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class edit extends moodleform {
    /**
     * @var int Maximum number of characters for a title.
     */
    const TITLE_MAX_CHARS = 100;

    /**
     * {@inheritDoc}
     * @see moodleform::definition()
     */
    public function definition() {
        $mform = $this->_form;

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        $mform->addElement('checkbox', 'autostart', get_string('autostart', 'auth_outage'));
        $mform->addHelpButton('autostart', 'autostart', 'auth_outage');

        $mform->addElement('duration', 'warningduration', get_string('warningduration', 'auth_outage'));
        $mform->addHelpButton('warningduration', 'warningduration', 'auth_outage');

        $mform->addElement('date_time_selector', 'starttime', get_string('starttime', 'auth_outage'));
        $mform->addHelpButton('starttime', 'starttime', 'auth_outage');

        $mform->addElement('duration', 'outageduration', get_string('outageduration', 'auth_outage'));
        $mform->addHelpButton('outageduration', 'outageduration', 'auth_outage');

        $mform->addElement(
            'text',
            'title',
            get_string('title', 'auth_outage'),
            'maxlength="'.self::TITLE_MAX_CHARS.'" size="60"'
        );
        $mform->setType('title', PARAM_TEXT);
        $mform->addHelpButton('title', 'title', 'auth_outage');

        $mform->addElement('editor', 'description', get_string('description', 'auth_outage'));
        $mform->addHelpButton('description', 'description', 'auth_outage');

        $mform->addElement('static', 'usagehints', '', get_string('textplaceholdershint', 'auth_outage'));
        $mform->addElement('static', 'warningreenablemaintenancemode', '');

        $this->add_action_buttons();
    }

    /**
     * Validate the parts of the request form for this module
     *
     * @param mixed[] $data An array of form data
     * @param string[] $files An array of form files
     * @return string[] of error messages
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
            debugging('Not implemented for format '.$data->description['format'], DEBUG_DEVELOPER);
            return null;
        }
        $outagedata = [
            'id' => ($data->id === 0) ? null : $data->id,
            'autostart' => (isset($data->autostart) && ($data->autostart == 1)),
            'starttime' => $data->starttime,
            'stoptime' => $data->starttime + $data->outageduration,
            'warntime' => $data->starttime - $data->warningduration,
            'title' => $data->title,
            'description' => $data->description['text'],
        ];
        return new outage($outagedata);
    }

    /**
     * Load in existing outage as form defaults.
     * @param outage $outage outage object with default values
     * @throws coding_exception
     */
    public function set_data($outage) {
        global $OUTPUT, $CFG;
        $mform = $this->_form;

        // Cannot change method signature, check type.
        if ($outage instanceof outage) {
            $this->_form->setDefaults([
                'id' => $outage->id,
                'autostart' => $outage->autostart,
                'starttime' => $outage->starttime,
                'outageduration' => $outage->get_duration_planned(),
                'warningduration' => $outage->get_warning_duration(),
                'title' => $outage->title,
                'description' => ['text' => $outage->description, 'format' => '1'],
            ]);

            // If the default_autostart is configured in config, then force autostart to be the default value.
            if (array_key_exists('auth_outage', $CFG->forced_plugin_settings)
                && array_key_exists('default_autostart', $CFG->forced_plugin_settings['auth_outage'])) {
                $this->_form->setDefaults([
                    'autostart' => $CFG->forced_plugin_settings['auth_outage']['default_autostart']
                ]);
                $mform->freeze('autostart');
            }

            if (!empty($outage->id) && $outage->autostart && $outage->starttime < time() && $outage->stoptime > time()) {
                $warning = $mform->getElement('warningreenablemaintenancemode');
                $warning->setValue($OUTPUT->notification(get_string('warningreenablemaintenancemode', 'auth_outage'),
                    'notifywarning'));
            }
        } else {
            throw new coding_exception('$outage must be an outage object.', $outage);
        }
    }
}

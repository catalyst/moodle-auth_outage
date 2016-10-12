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
 * baseform class.
 *
 * @package    auth_outage
 * @author     Daniel Thee Roperto <daniel.roperto@catalyst-au.net>
 * @copyright  2016 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace auth_outage\form;

use moodleform;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir.'/formslib.php');

/**
 * baseform class.
 *
 * @package    auth_outage
 * @author     Daniel Thee Roperto <daniel.roperto@catalyst-au.net>
 * @copyright  2016 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class baseform extends moodleform {
    /**
     * Validate the form. See MDL-56250.
     *
     * @param bool $validateonnosubmit optional, defaults to false.  The default behaviour
     *             is NOT to validate the form when a no submit button has been pressed.
     *             pass true here to override this behaviour
     *
     * @return bool true if form data valid
     */
    public function validate_defined_fields($validateonnosubmit = false) {
        // One validation NOT is enough (if mocking). See parent method.
        $mform =& $this->_form;
        if ($this->no_submit_button_pressed() && empty($validateonnosubmit)) {
            return false;
        }
        $internalval = $mform->validate();

        $files = [];
        $fileval = $this->_validate_files($files);
        // Check draft files for validation and flag them if required files are not in draft area.
        $draftfilevalue = $this->validate_draft_files();

        if ($fileval !== true && $draftfilevalue !== true) {
            $fileval = array_merge($fileval, $draftfilevalue);
        } else if ($draftfilevalue !== true) {
            $fileval = $draftfilevalue;
        } //default is file_val, so no need to assign.

        if ($fileval !== true) {
            if (!empty($fileval)) {
                foreach ($fileval as $element => $msg) {
                    $mform->setElementError($element, $msg);
                }
            }
            $fileval = false;
        }

        $data = $mform->exportValues();
        $moodleval = $this->validation($data, $files);
        if ((is_array($moodleval) && count($moodleval) !== 0)) {
            // Non-empty array means errors.
            foreach ($moodleval as $element => $msg) {
                $mform->setElementError($element, $msg);
            }
            $moodleval = false;
        } else {
            // Anything else means validation ok.
            $moodleval = true;
        }

        $validated = ($internalval and $moodleval and $fileval);
        return $validated;
    }
}

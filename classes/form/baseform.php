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

namespace auth_outage\form;

use moodleform;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir.'/formslib.php');

/**
 * Outage base for forms, extends Moodle form to fix a but in the validation method.
 *
 * @package    auth_outage
 * @author     Daniel Thee Roperto <daniel.roperto@catalyst-au.net>
 * @copyright  2016 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class baseform extends moodleform {
    /**
     * Validate the form.
     *
     * You almost always want to call {@link is_validated} instead of this
     * because it calls {@link definition_after_data} first, before validating the form,
     * which is what you want in 99% of cases.
     *
     * This is provided as a separate function for those special cases where
     * you want the form validated before definition_after_data is called
     * for example, to selectively add new elements depending on a no_submit_button press,
     * but only when the form is valid when the no_submit_button is pressed,
     *
     * @param bool $validateonnosubmit optional, defaults to false.  The default behaviour
     *             is NOT to validate the form when a no submit button has been pressed.
     *             pass true here to override this behaviour
     *
     * @return bool true if form data valid
     * @SuppressWarnings(PHPMD) It is better to not refactor this method as it is linked to its parent functionality.
     * @codeCoverageIgnore
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

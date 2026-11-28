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

namespace auth_outage\admin;

/**
 * Admin setting holding a regular expression (without delimiters).
 *
 * @package    auth_outage
 * @copyright  2026 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class setting_regex extends \admin_setting_configtext {
    /**
     * Builds a full pattern from a delimiter-less regex.
     *
     * @param string $regex Regex without delimiters.
     * @return string Pattern ready for preg_* functions.
     */
    public static function to_pattern(string $regex): string {
        return '~' . str_replace('~', '\~', $regex) . '~u';
    }

    /**
     * Validates the value is empty or a valid regular expression.
     *
     * @param string $data Value to validate.
     * @return true|string True if valid, error string otherwise.
     */
    public function validate($data) {
        $data = trim($data);
        if ($data === '' || @preg_match(self::to_pattern($data), '') !== false) {
            return true;
        }
        return get_string('metadataregexinvalid', 'auth_outage');
    }
}

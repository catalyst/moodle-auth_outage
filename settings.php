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
 * auth_outage plugin settings
 *
 * @package    auth_outage
 * @author     Daniel Thee Roperto <daniel.roperto@catalyst-au.net>
 * @copyright  Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die;

// FIXME If plugin not installed, it is still generating the category Outage under Auth Plugins.

if ($hassiteconfig) {
    // Configure default settings page.
    $settings->visiblename = "Defaults";
    $settings->add(
        new admin_setting_configtext('auth_outage_warning_period',
            "Default Warning Time",
            "Default warning time (in minutes) for outages.",
            120, PARAM_INT));
    $settings->add(
        new admin_setting_configtextarea('auth_outage_warning_text',
            'Default Warning Message',
            'Default warning message for outages. Use [from] and [until] placeholders as required.',
            'There is an scheduled maintenance from [from] to [until] and our system will not be available during that time.',
            PARAM_TEXT)
    );
    // Create category for Outage.
    $ADMIN->add('authsettings', new admin_category('auth_outage', 'Outage'));
    // Add settings page toconfigure defaults.
    $ADMIN->add('auth_outage', $settings);
    // Clear '$settings' to prevent adding again outsite category.
    $settings = null;
    // Add options.
    $ADMIN->add('auth_outage',
        new admin_externalpage('auth_outage_manage', 'Manage',
            new moodle_url($CFG->wwwroot . '/auth/outage/list.php')
        ));
}
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
    $settings->visiblename = get_string('menudefaults', 'auth_outage');
    $settings->add(
        new admin_setting_configtext('auth_outage_warning_period',
            get_string('defaultwarningtime', 'auth_outage'),
            get_string('defaultwarningtimedescription', 'auth_outage'),
            120, PARAM_INT));
    $settings->add(
        new admin_setting_configtextarea('auth_outage_warning_text',
            get_string('defaultwarningmessage', 'auth_outage'),
            get_string('defaultwarningmessagedescription', 'auth_outage'),
            get_string('defaultwarningmessagevalue', 'auth_outage'),
            PARAM_TEXT)
    );
    // Create category for Outage.
    $ADMIN->add('authsettings', new admin_category('auth_outage', get_string('pluginname', 'auth_outage')));
    // Add settings page toconfigure defaults.
    $ADMIN->add('auth_outage', $settings);
    // Clear '$settings' to prevent adding again outsite category.
    $settings = null;
    // Add options.
    $ADMIN->add('auth_outage',
        new admin_externalpage(
            'auth_outage_manage',
            get_string('menumanage', 'auth_outage'),
            new moodle_url($CFG->wwwroot . '/auth/outage/list.php')
        )
    );
}
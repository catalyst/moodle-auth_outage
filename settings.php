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

if ($hassiteconfig && is_enabled_auth('outage')) {
    // Configure default settings page.
    $settings->visiblename = get_string('menudefaults', 'auth_outage');
    $settings->add(
        new admin_setting_configtext('auth_outage/default_duration',
            get_string('defaultoutageduration', 'auth_outage'),
            get_string('defaultoutagedurationdescription', 'auth_outage'),
            60, PARAM_INT));
    $settings->add(
        new admin_setting_configtext('auth_outage/warning_duration',
            get_string('defaultwarningduration', 'auth_outage'),
            get_string('defaultwarningdurationdescription', 'auth_outage'),
            60, PARAM_INT));
    $settings->add(
        new admin_setting_configtext('auth_outage/warning_title',
            get_string('defaultwarningtitle', 'auth_outage'),
            get_string('defaultwarningtitledescription', 'auth_outage'),
            get_string('defaultwarningtitlevalue', 'auth_outage'),
            PARAM_TEXT)
    );
    $settings->add(
        new admin_setting_configtextarea('auth_outage/warning_description',
            get_string('defaultwarningdescription', 'auth_outage'),
            get_string('defaultwarningdescriptiondescription', 'auth_outage'),
            get_string('defaultwarningdescriptionvalue', 'auth_outage'),
            PARAM_TEXT)
    );
    $settings->add(
        new admin_setting_configtextarea('auth_outage/css',
            get_string('defaultlayoutcss', 'auth_outage'),
            get_string('defaultlayoutcssdescription', 'auth_outage'),
            file_get_contents($CFG->dirroot . '/auth/outage/views/warningbar.css'),
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
            new moodle_url($CFG->wwwroot . '/auth/outage/manage.php')
        )
    );
}

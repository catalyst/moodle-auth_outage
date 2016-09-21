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
 * @copyright  2016 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
use auth_outage\local\outagelib;

defined('MOODLE_INTERNAL') || die;

if ($hassiteconfig && is_enabled_auth('outage')) {
    $defaults = outagelib::get_config_defaults();
    // Configure default settings page.
    $settings->visiblename = get_string('menudefaults', 'auth_outage');
    $settings->add(new admin_setting_configtext(
        'auth_outage/default_duration',
        get_string('defaultoutageduration', 'auth_outage'),
        get_string('defaultoutagedurationdescription', 'auth_outage'),
        $defaults['default_duration'],
        PARAM_INT
    ));
    $settings->add(new admin_setting_configtext(
        'auth_outage/warning_duration',
        get_string('defaultwarningduration', 'auth_outage'),
        get_string('defaultwarningdurationdescription', 'auth_outage'),
        $defaults['warning_duration'],
        PARAM_INT
    ));
    $settings->add(new admin_setting_configtext(
        'auth_outage/warning_title',
        get_string('defaultwarningtitle', 'auth_outage'),
        get_string('defaultwarningtitledescription', 'auth_outage'),
        $defaults['warning_title'],
        PARAM_TEXT
    ));
    $settings->add(new admin_setting_configtextarea(
        'auth_outage/warning_description',
        get_string('defaultwarningdescription', 'auth_outage'),
        get_string('defaultwarningdescriptiondescription', 'auth_outage'),
        $defaults['warning_description'],
        PARAM_TEXT
    ));
    $settings->add(new admin_setting_configtextarea(
        'auth_outage/css',
        get_string('defaultlayoutcss', 'auth_outage'),
        get_string('defaultlayoutcssdescription', 'auth_outage'),
        $defaults['css'],
        PARAM_TEXT
    ));
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
            new moodle_url($CFG->wwwroot.'/auth/outage/manage.php')
        )
    );
}

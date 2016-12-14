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
 *
 * @var stdClass $CFG
 * @var admin_settingpage $settings
 * @var bootstrap_renderer $OUTPUT
 * @var admin_root $ADMIN
 */
use auth_outage\local\outagelib;

defined('MOODLE_INTERNAL') || die;

if ($hassiteconfig && is_enabled_auth('outage')) {
    $defaults = outagelib::get_config_defaults();
    $settings->visiblename = get_string('menusettings', 'auth_outage');

    $settings->add(new admin_setting_heading(
        'defaults',
        get_string('settingssectiondefaults', 'auth_outage'),
        get_string('settingssectiondefaultsdescription', 'auth_outage')));
    $settings->add(new admin_setting_configcheckbox(
        'auth_outage/default_autostart',
        get_string('defaultoutageautostart', 'auth_outage'),
        get_string('defaultoutageautostartdescription', 'auth_outage'),
        $defaults['default_autostart']
    ));
    $settings->add(new admin_setting_configduration(
        'auth_outage/default_warning_duration',
        get_string('defaultwarningduration', 'auth_outage'),
        get_string('defaultwarningdurationdescription', 'auth_outage'),
        $defaults['default_warning_duration'],
        60
    ));
    $settings->add(new admin_setting_configduration(
        'auth_outage/default_duration',
        get_string('defaultoutageduration', 'auth_outage'),
        get_string('defaultoutagedurationdescription', 'auth_outage'),
        $defaults['default_duration'],
        60
    ));
    $settings->add(new admin_setting_configtext(
        'auth_outage/default_title',
        get_string('defaulttitle', 'auth_outage'),
        get_string('defaulttitledescription', 'auth_outage'),
        $defaults['default_title'],
        PARAM_TEXT
    ));
    $settings->add(new admin_setting_configtextarea(
        'auth_outage/default_description',
        get_string('defaultdescription', 'auth_outage'),
        get_string('defaultdescriptiondescription', 'auth_outage'),
        $defaults['default_description'],
        PARAM_RAW
    ));

    $settings->add(new admin_setting_heading(
        'plugin',
        get_string('settingssectionplugin', 'auth_outage'),
        get_string('settingssectionplugindescription', 'auth_outage')));

    $settings->add(new admin_setting_configtextarea(
        'auth_outage/css',
        get_string('defaultlayoutcss', 'auth_outage'),
        get_string('defaultlayoutcssdescription', 'auth_outage'),
        $defaults['css'],
        PARAM_RAW
    ));

    // Create 'Allowed IPs' settings.
    $allowedips = outagelib::get_config()->allowedips;
    $description = outagelib::generate_plugin_configuration_warning();
    if (trim($allowedips) == '') {
        $message = 'allowedipsempty';
        $type = 'notifymessage';
    } else if (remoteip_in_list($allowedips)) {
        $message = 'allowedipshasmyip';
        $type = 'notifysuccess';
    } else {
        $message = 'allowedipshasntmyip';
        $type = 'notifyerror';
    };
    $description .= $OUTPUT->notification(get_string($message, 'auth_outage', ['ip' => getremoteaddr()]), $type);

    $description .= '<p>'.get_string('ipblockersyntax', 'admin').'</p>';
    $iplist = new admin_setting_configiplist(
        'auth_outage/allowedips',
        get_string('allowediplist', 'admin'),
        $description,
        $defaults['allowedips']
    );
    $iplist->set_updatedcallback('auth_outage_outagelib_prepare_next_outage');
    $settings->add($iplist);

    // Create 'Static Page - Elements to Remove' settings.
    $toremove = new admin_setting_configtextarea(
        'auth_outage/remove_selectors',
        get_string('removeselectors', 'auth_outage'),
        get_string('removeselectorsdescription', 'auth_outage'),
        $defaults['remove_selectors']
    );
    $toremove->set_updatedcallback('auth_outage_outagelib_prepare_next_outage');
    $settings->add($toremove);

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

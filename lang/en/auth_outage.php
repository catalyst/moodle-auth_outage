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
 * Strings for component 'auth_outage', language 'en'.
 *
 * @package   auth_outage
 * @author    Daniel Thee Roperto <daniel.roperto@catalyst-au.net>
 * @copyright Catalyst IT
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['auth_outagedescription'] = 'Auxiliary plugin that warns users about a future outage and prevents them from logging in once the outage starts.';
$string['clone'] = 'Clone';
$string['datetimeformat'] = '%d %h %Y at %I:%M%P';
$string['defaultlayoutcss'] = 'Layout CSS';
$string['defaultlayoutcssdescription'] = 'This CSS code will be used to display the Outage Warning Bar.';
$string['defaultoutageduration'] = 'Outage Duration';
$string['defaultoutagedurationdescription'] = 'Default duration (in minutes) of an outage.';
$string['defaultwarningduration'] = 'Warning Duration';
$string['defaultwarningdurationdescription'] = 'Default warning time (in minutes) for outages.';
$string['defaultwarningtitle'] = 'Title';
$string['defaultwarningtitledescription'] = 'Default title for outages. Use {{start}} and {{stop}} placeholders as required.';
$string['defaultwarningtitlevalue'] = 'System down from {{start}} for {{duration}}.';
$string['defaultwarningdescription'] = 'Description';
$string['defaultwarningdescriptiondescription'] = 'Default warning message for outages. Use {{start}} and {{stop}} placeholders as required.';
$string['defaultwarningdescriptionvalue'] = 'There is an scheduled maintenance from {{start}} to {{stop}} and our system will not be available during that time.';
$string['description'] = 'Public Description';
$string['description_help'] = 'A full description of the outage, publicly visible by all users.';
$string['info1minutebefore'] = '1 minute before';
$string['infoendofoutage'] = 'end of outage';
$string['infofrom'] = 'From:';
$string['infountil'] = 'Until:';
$string['infostart'] = 'start';
$string['infostartofwarning'] = 'start of warning';
$string['menudefaults'] = 'Default Settings';
$string['menumanage'] = 'Manage';
$string['messageoutageongoing'] = 'Our system will be under maintenance until {$a->stop}.';
$string['messageoutagewarning'] = 'Shutting down in {{countdown}} ...';
$string['notfound'] = 'No outages found.';
$string['outageedit'] = 'Edit Outage';
$string['outageclone'] = 'Clone Outage';
$string['outagecreate'] = 'Create Outage';
$string['outagedelete'] = 'Delete Outage';
$string['outagedeletewarning'] = 'You are about to permanently delete the outage below. This cannot be undone.';
$string['outageduration'] = 'Outage Duration';
$string['outagedurationerrorinvalid'] = 'Outage duration must be positive.';
$string['outageduration_help'] = 'How long the outage lasts after it starts.';
$string['outageslistfuture'] = 'Planned outages';
$string['outageslistpast'] = 'Outage history';
$string['pluginname'] = 'Outage manager';
$string['readmore'] = 'Read More';
$string['starttime'] = 'Start date and time';
$string['starttime_help'] = 'At which date and time the outage starts, preventing general access to the system.';
$string['tableheaderstarttime'] = 'Starts on';
$string['tableheaderstopsafter'] = 'Stops after';
$string['tableheaderwarnbefore'] = 'Warns before';
$string['tableheadertitle'] = 'Title';
$string['textplaceholdershint'] = 'You can use {{start}}, {{stop}} and {{duration}} as placeholders on the title and description.';
$string['titleerrorinvalid'] = 'Title cannot be left blank.';
$string['titleerrortoolong'] = 'Title cannot have more than {$a} characters.';
$string['title'] = 'Title';
$string['title_help'] = 'A short title to for this outage. It will be displayed on the warning bar and on the calendar.';
$string['warningdurationerrorinvalid'] = 'Warning duration must be positive.';
$string['warningduration'] = 'Warning duration';
$string['warningduration_help'] = 'How long before the start of the outage should the warning be displayed.';

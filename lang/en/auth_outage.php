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
$string['defaultlayoutcss'] = 'Layout CSS';
$string['defaultlayoutcssdescription'] = 'This CSS code will be used to display the Outage Warning Bar.';
$string['defaultoutageduration'] = 'Outage Duration';
$string['defaultoutagedurationdescription'] = 'Default duration (in minutes) of an outage.';
$string['defaultwarningduration'] = 'Warning Duration';
$string['defaultwarningdurationdescription'] = 'Default warning time (in minutes) for outages.';
$string['defaultwarningtitle'] = 'Title';
$string['defaultwarningtitledescription'] = 'Default title for outages. Use {{start}} and {{stop}} placeholders as required.';
$string['defaultwarningtitlevalue'] = 'System down from {{start}} to {{stop}}.';
$string['defaultwarningdescription'] = 'Description';
$string['defaultwarningdescriptiondescription'] = 'Default warning message for outages. Use {{start}} and {{stop}} placeholders as required.';
$string['defaultwarningdescriptionvalue'] = 'There is an scheduled maintenance from {{start}} to {{stop}} and our system will not be available during that time.';
$string['description'] = 'Public Description';
$string['menudefaults'] = 'Default Settings';
$string['menumanage'] = 'Manage';
$string['messageoutageongoing'] = 'Our system will be under maintenance until {$a->stop}.';
$string['messageoutagewarning'] = 'There is an scheduled downtime from {$a->start} until {$a->stop}.';
$string['outageedit'] = 'Edit Outage';
$string['outagecreate'] = 'Create Outage';
$string['outagedelete'] = 'Delete Outage';
$string['outagedeletewarning'] = 'You are about to permanently delete the outage below. This cannot be undone.';
$string['outageduration'] = 'Outage Duration';
$string['outageslist'] = 'Outages List';
$string['pluginname'] = 'Outage manager';
$string['starttime'] = 'Start date and time';
$string['stoptimeerrornotafterstart'] = 'Stop time must be after start time.';
$string['textplaceholdershint'] = 'You can use {{start}} and {{stop}} as placeholders on the title/description for the actual start/stop time.';
$string['titleerrorinvalid'] = 'Title cannot be left blank.';
$string['titleerrortoolong'] = 'Title cannot have more than {$a} characters.';
$string['title'] = 'Title';
$string['warningdurationerrorinvalid'] = 'Warning duration cannot be zero.';
$string['warningduration'] = 'Warning duration';

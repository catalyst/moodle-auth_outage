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
$string['clicreatehelp'] = 'Creates a new outage.';
$string['clicreateparamautostart'] = 'must be Y or N, sets if the outage automatically triggers maintenance mode.';
$string['clicreateparamblock'] = 'blocks until outage starts.';
$string['clicreateparamclone'] = 'clone another outage except for the start time.';
$string['clicreateparamdescription'] = 'the description of the outage.';
$string['clicreateparamduration'] = 'how many seconds should the outage last.';
$string['clicreateparamhelp'] = 'shows parameters help.';
$string['clicreateparamonlyid'] = 'only outputs the new outage id, useful for scripts.';
$string['clicreateparamstart'] = 'in how many seconds should this outage start. Required.';
$string['clicreateparamtitle'] = 'the title of the outage.';
$string['clicreateparamwarn'] = 'how many seconds before it starts to display a warning.';
$string['clifinishhelp'] = 'Finishes an ongoing outage.';
$string['clifinishnotongoing'] = 'Outage is not ongoing.';
$string['clifinishparamhelp'] = 'shows parameters help.';
$string['clifinishparamactive'] = 'finishes the currently active outage.';
$string['clifinishparamoutageid'] = 'the id of the outage to finish.';
$string['cliwaitforiterroridxoractive'] = 'You must use --outageid=# or --active parameter but not both.';
$string['cliwaitforithelp'] = 'Waits until an outage starts.';
$string['cliwaitforitoutagestarted'] = 'Outage started!';
$string['cliwaitforitoutagestartingin'] = 'Outage starting in {$a->countdown}.';
$string['cliwaitforitparamactive'] = 'wait for the currently active outage.';
$string['cliwaitforitparamhelp'] = 'shows parameters help.';
$string['cliwaitforitparamoutageid'] = 'the id of the outage to wait until it starts.';
$string['cliwaitforitparamsleep'] = 'maximum amount of seconds before status output.';
$string['cliwaitforitparamverbose'] = 'enable verbose mode.';
$string['clierrorinvalidvalue'] = 'Invalid value for parameter: {$a->param}';
$string['clierrormissingparamaters'] = 'You must specify the start time, use --help for more information.';
$string['clierroroutagechanged'] = 'Outage was changed while waiting.';
$string['clierroroutageended'] = 'Outage has already ended.';
$string['clierroroutagenotfound'] = 'Outage not found.';
$string['clioutagecreated'] = 'Outage created, id: {$a->id}';
$string['clone'] = 'Clone';
$string['datetimeformat'] = '%a %d %h %Y at %I:%M%P %Z';
$string['defaultlayoutcss'] = 'Layout CSS';
$string['defaultlayoutcssdescription'] = 'This CSS code will be used to display the Outage Warning Bar.';
$string['defaultoutageautostart'] = 'Outage Auto Start';
$string['defaultoutageautostartdescription'] = 'If the outage should automatically trigger maintenance mode once it starts, locking down the whole site.';
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
$string['finish'] = 'Finish';
$string['info15secondsbefore'] = '15 seconds before';
$string['infoendofoutage'] = 'end of outage';
$string['infofrom'] = 'From:';
$string['infountil'] = 'Until:';
$string['infostart'] = 'start';
$string['infostartofwarning'] = 'start of warning';
$string['infopagestaticgenerated'] = 'This warning was generated on {$a->time}.';
$string['menudefaults'] = 'Default Settings';
$string['menumanage'] = 'Manage';
$string['messageoutagebackonline'] = 'We are back online!';
$string['messageoutagebackonlinedescription'] = 'You may resume browsing safely.';
$string['messageoutageongoing'] = 'Back online at {$a->stop}.';
$string['messageoutagewarning'] = 'Shutting down in {{countdown}}';
$string['na'] = 'n/a';
$string['notfound'] = 'No outages found.';
$string['outageedit'] = 'Edit Outage';
$string['outageclone'] = 'Clone Outage';
$string['outagecreate'] = 'Create Outage';
$string['outagedelete'] = 'Delete Outage';
$string['outagedeletewarning'] = 'You are about to permanently delete the outage below. This cannot be undone.';
$string['outageduration'] = 'Outage Duration';
$string['outagedurationerrorinvalid'] = 'Outage duration must be positive.';
$string['outageduration_help'] = 'How long the outage lasts after it starts.';
$string['outagefinish'] = 'Finish Outage';
$string['outagefinishwarning'] = 'You are about to mark this outage as finished. The system will be immediately back online.';
$string['outageslistfuture'] = 'Planned outages';
$string['outageslistpast'] = 'Outage history';
$string['pluginname'] = 'Outage manager';
$string['starttime'] = 'Start date and time';
$string['starttime_help'] = 'At which date and time the outage starts, preventing general access to the system.';
$string['tableheaderduration'] = 'Duration';
$string['tableheaderdurationplanned'] = 'Planned Duration';
$string['tableheaderdurationactual'] = 'Actual Duration';
$string['tableheaderstarttime'] = 'Starts on';
$string['tableheaderwarnbefore'] = 'Warns before';
$string['tableheadertitle'] = 'Title';
$string['taskupdatestaticpage'] = 'Update static outage page';
$string['textplaceholdershint'] = 'You can use {{start}}, {{stop}} and {{duration}} as placeholders on the title and description.';
$string['titleerrorinvalid'] = 'Title cannot be left blank.';
$string['titleerrortoolong'] = 'Title cannot have more than {$a} characters.';
$string['title'] = 'Title';
$string['title_help'] = 'A short title to for this outage. It will be displayed on the warning bar and on the calendar.';
$string['warningdurationerrorinvalid'] = 'Warning duration must be positive.';
$string['warningduration'] = 'Warning duration';
$string['warningduration_help'] = 'How long before the start of the outage should the warning be displayed.';

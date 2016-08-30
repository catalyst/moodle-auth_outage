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
 * Update outages (create, update, delete).
 *
 * @package    auth_outage
 * @author     Daniel Thee Roperto <daniel.roperto@catalyst-au.net>
 * @copyright  Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use \auth_outage\outage;

require_once('../../config.php');
require_once($CFG->libdir . '/adminlib.php');

// Check parameters.
require_sesskey();
$action = required_param('action', PARAM_ALPHA);
switch ($action) {
    case 'add':
        $title = 'Add new Outage';
        break;
    default:
        print_error('auth_outage_invalidaction1');
}

admin_externalpage_setup('auth_outage_manage');
$PAGE->set_title($title);
$PAGE->set_heading($title);
$PAGE->set_url(new moodle_url('/auth/outage/update.php'));
$renderer = $PAGE->get_renderer('auth_outage');

echo $OUTPUT->header();

switch ($action) {
    case 'add':
        $outage = new outage();
        break;
    default:
        print_error('auth_outage_invalidaction2');
}

echo $OUTPUT->footer();

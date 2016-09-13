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
 * View included by the renderer to output the outage information page.
 *
 * @package    auth_outage
 * @author     Daniel Thee Roperto <daniel.roperto@catalyst-au.net>
 * @copyright  Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.'); // It must be included from a Moodle page.
}
?>

<div class="auth_outage_info">
    <div>
        <b><?php echo get_string('infofrom', 'auth_outage'); ?></b>
        <?php echo userdate($outage->starttime, get_string('datetimeformat', 'auth_outage')); ?>
    </div>
    <div>
        <b><?php echo get_string('infountil', 'auth_outage'); ?></b>
        <?php echo userdate($outage->stoptime, get_string('datetimeformat', 'auth_outage')); ?>
    </div>
    <div class="auth_outage_info_description"><?php echo $outage->get_description(); ?></div>

    <?php if (is_siteadmin()): ?>
        <div class="auth_outage_info_adminlinks">
            <b><?php echo get_string('preview'); ?>:</b>
            <?php echo implode(' | ', $adminlinks); ?><br />
            <?php echo $admineditlink; ?>
        </div>
    <?php endif; ?>

</div>

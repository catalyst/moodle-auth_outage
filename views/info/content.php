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

defined('MOODLE_INTERNAL') || die();

if ($this->has_admin_options()) {
    $adminlinks = [];
    foreach ([
                 'startofwarning' => -$this->outage->get_warning_duration(),
                 '15secondsbefore' => -15,
                 'start' => 0,
                 'endofoutage' => $this->outage->get_duration_planned(),
             ] as $title => $delta) {
        $adminlinks[] = html_writer::link(
            new moodle_url(
                '/auth/outage/info.php',
                [
                    'id' => $this->outage->id,
                    'auth_outage_preview' => $this->outage->id,
                    'auth_outage_delta' => $delta,
                ]
            ),
            get_string('info'.$title, 'auth_outage')
        );
    }

    $admineditlink = html_writer::link(
        new moodle_url('/auth/outage/edit.php', ['id' => $this->outage->id]),
        get_string('outageedit', 'auth_outage')
    );
}
?>

<div class="auth_outage_info">

    <div>
        <b><?php echo get_string('infofrom', 'auth_outage'); ?></b>
        <?php echo userdate($this->outage->starttime, get_string('datetimeformat', 'auth_outage')); ?>
    </div>
    <div>
        <b><?php echo get_string('infountil', 'auth_outage'); ?></b>
        <?php echo userdate($this->outage->stoptime, get_string('datetimeformat', 'auth_outage')); ?>
    </div>
    <div class="auth_outage_info_description"><?php echo $this->outage->get_description(); ?></div>

    <?php if ($this->has_admin_options()): ?>
        <div class="auth_outage_info_adminlinks">
            <b><?php echo get_string('preview'); ?>:</b>
            <?php echo implode(' | ', $adminlinks); ?><br/>
            <?php echo $admineditlink; ?>
        </div>
    <?php endif; ?>

</div>

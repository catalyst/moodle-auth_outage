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
 * @copyright  2016 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @var array $viewbag
 */

defined('MOODLE_INTERNAL') || die();
?>

<div class="auth_outage_info">

    <div>
        <b><?php echo get_string('infofrom', 'auth_outage'); ?></b>
        <?php echo userdate($viewbag['outage']->starttime, get_string('datetimeformat', 'auth_outage')); ?>
    </div>
    <div>
        <b><?php echo get_string('infountil', 'auth_outage'); ?></b>
        <?php echo userdate($viewbag['outage']->stoptime, get_string('datetimeformat', 'auth_outage')); ?>
    </div>
    <div class="auth_outage_info_description"><?php echo $viewbag['outage']->get_description(); ?></div>

    <?php if ($viewbag['admin']): ?>
        <?php
        $adminlinks = [];
        foreach ([
            'startofwarning' => -$viewbag['outage']->get_warning_duration(),
            '15secondsbefore' => -15,
            'start' => 0,
            'endofoutage' => $viewbag['outage']->get_duration_planned() - 1,
        ] as $title => $delta) {
            $adminlinks[] = html_writer::link(
                new moodle_url(
                    '/auth/outage/info.php',
                    [
                        'id' => $viewbag['outage']->id,
                        'auth_outage_preview' => $viewbag['outage']->id,
                        'auth_outage_delta' => $delta,
                    ]
                ),
                get_string('info'.$title, 'auth_outage')
            );
        }
        $adminlinks[] = html_writer::link(
            new moodle_url(
                '/auth/outage/info.php',
                [
                    'id' => $viewbag['outage']->id,
                    'auth_outage_preview' => $viewbag['outage']->id,
                    'auth_outage_delta' => 0,
                    'auth_outage_hide_warning' => 1,
                ]
            ),
            get_string('infohidewarning', 'auth_outage')
        );
        $adminlinks[] = html_writer::link(
            new moodle_url('/auth/outage/preview.php', ['id' => $viewbag['outage']->id]),
            get_string('infostaticpage', 'auth_outage')
        );

        $admineditlink = html_writer::link(
            new moodle_url('/auth/outage/edit.php', ['edit' => $viewbag['outage']->id]),
            get_string('outageedit', 'auth_outage')
        );
        ?>
        <div class="auth_outage_info_adminlinks">
            <b><?php echo get_string('preview'); ?>:</b>
            <?php echo implode(' | ', $adminlinks); ?><br/>
            <?php echo $admineditlink; ?>
        </div>
    <?php endif; ?>

</div>

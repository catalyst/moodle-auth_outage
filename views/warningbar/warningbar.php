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
 * View included by the renderer to output the outage warning bar.
 *
 * @package    auth_outage
 * @author     Daniel Thee Roperto <daniel.roperto@catalyst-au.net>
 * @copyright  2016 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use auth_outage\local\outagelib;

defined('MOODLE_INTERNAL') || die();

global $OUTPUT;

$start = userdate($viewbag['outage']->starttime, get_string('datetimeformat', 'auth_outage'));
$stop = userdate($viewbag['outage']->stoptime, get_string('datetimeformat', 'auth_outage'));

$countdown = get_string('messageoutagewarning', 'auth_outage', ['start' => $start, 'stop' => $stop]);
$ongoing = get_string('messageoutageongoing', 'auth_outage', ['start' => $start, 'stop' => $stop]);
$message = $viewbag['outage']->is_ongoing($viewbag['time']) ? $ongoing : '';

$infolink = new moodle_url('/auth/outage/info.php', ['id' => $viewbag['outage']->id]);

$title = $viewbag['outage']->get_title();
if (!$viewbag['static']) {
    $title = html_writer::link(
        $infolink,
        $title,
        ['target' => '_blank', 'class' => 'auth_outage_warningbar_box_title']
    );
    if (is_siteadmin()) {
        $url = new moodle_url('/auth/outage/finish.php', ['id' => $viewbag['outage']->id]);
        $text = html_writer::empty_tag('img', [
                'src' => $OUTPUT->image_url('t/check'),
                'alt' => get_string('finish', 'auth_outage'),
                'class' => 'iconsmall',
            ]).' '.get_string('finish', 'auth_outage');
        $attr = [
            'title' => get_string('finish', 'auth_outage'),
            'class' => 'auth_outage_warningbar_box_finish',
        ];
        $title .= ' '.html_writer::span(html_writer::link($url, $text, $attr), '', ['id' => 'auth_outage_warningbar_button']);
    }
}
?>
    <style>
        <?php
            readfile($CFG->dirroot.'/auth/outage/views/warningbar/warningbar.css');
            echo outagelib::get_config()->css;
        ?>
    </style>

    <div id="auth_outage_warningbar_box">
        <div class="auth_outage_warningbar_center">
            <div id="auth_outage_warningbar_message"><?php echo $message; ?></div>
            <div id="auth_outage_warningbar_title"><?php echo $title; ?></div>
        </div>
    </div>

<?php if (!$viewbag['static']): ?>
    <script>
        document.body.className += ' auth_outage';
        <?php
        require(__DIR__.'/warningbar.js');
        $json = json_encode([
            'countdown' => $countdown,
            'ongoing' => $ongoing,
            'backonline' => get_string('messageoutagebackonline', 'auth_outage'),
            'backonlinedescription' => get_string('messageoutagebackonlinedescription', 'auth_outage'),
            'servertime' => $viewbag['time'],
            'starts' => $viewbag['outage']->starttime,
            'stops' => $viewbag['outage']->stoptime,
            'preview' => $viewbag['preview'],
            'checkfinishedurl' => (string)(new moodle_url('/auth/outage/checkfinished.php')),
        ]);
        echo 'authOutageWarningBar.init('.$json.');';
        ?>
    </script>
<?php endif;

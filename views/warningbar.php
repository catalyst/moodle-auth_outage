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
 * @copyright  Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use auth_outage\outagelib;

defined('MOODLE_INTERNAL') || die();

global $OUTPUT;

if (!isset($static)) {
    $static = true;
}

if ($static) {
    $start = userdate($outage->starttime, get_string('datetimeformat', 'auth_outage'));
    $stop = userdate($outage->stoptime, get_string('datetimeformat', 'auth_outage'));
    $countdown = get_string('messageoutageongoing', 'auth_outage', ['start' => $start, 'stop' => $stop]);
} else {
    $infolink = new moodle_url('/auth/outage/info.php', ['id' => $outage->id]);
}

echo html_writer::tag('style', outagelib::get_config()->css);
?>

<div id="auth_outage_warningbar_box">
    <div class="auth_outage_warningbar_center">
        <div id="auth_outage_warningbar_countdown"><?php echo $countdown; ?></div>
        <div>
            <?php
            if ($static) {
                echo $outage->get_title();
            } else {
                echo html_writer::link(
                    $infolink,
                    $outage->get_title(),
                    ['target' => '_blank', 'class' => 'auth_outage_warningbar_box_title']
                );
            }

            if (!$static && is_siteadmin() && $outage->is_ongoing()) {
                $url = new moodle_url('/auth/outage/finish.php', ['id' => $outage->id]);
                $text = html_writer::empty_tag('img', [
                        'src' => $OUTPUT->pix_url('t/check'),
                        'alt' => get_string('finish', 'auth_outage'),
                        'class' => 'iconsmall'
                    ]) . ' ' . get_string('finish', 'auth_outage');
                $attr = [
                    'title' => get_string('finish', 'auth_outage'),
                    'class' => 'auth_outage_warningbar_box_finish'
                ];
                echo html_writer::link($url, $text, $attr);
            }
            ?>
        </div>
    </div>
</div>

<?php if (!$static && !$outage->is_ongoing($time)): ?>
    <script>
        <?php require($CFG->dirroot . '/auth/outage/views/warningbar.js'); ?>
        auth_outage_countdown.init(
            <?php echo($outage->starttime - $time); ?>,
            <?php echo(is_siteadmin() ? 'true' : 'false'); ?>,
            '<?php echo $infolink; ?>'
        );
    </script>
<?php endif; ?>

<div class="auth_outage_warningbar_spacer">&nbsp;</div>

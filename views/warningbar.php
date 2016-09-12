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

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.'); // It must be included from a Moodle page.
}

// If debugging include directly from file, otherwise use plugin settings.
echo html_writer::tag('style',
    debugging() ? file_get_contents($CFG->dirroot . '/auth/outage/views/warningbar.css') : get_config('auth_outage', 'css')
);

?>

<div class="auth_outage_warningbar">
    <div class="auth_outage_warningbar_box">
        <div class="auth_outage_warningbar_box_countdown" id="auth_outage_warningbar_countdown"><?php echo $countdown; ?></div>
        <div class="auth_outage_warningbar_box_message">
            <?php echo $outage->get_title(); ?>
            <small>
                [<?php echo html_writer::link(
                    new moodle_url('/auth/outage/info.php'),
                    get_string('readmore', 'auth_outage'),
                    ['target' => 'outage']
                ); ?>]
            </small>
        </div>
    </div>
</div>

<script>
    // Internet Explorer 8 fix.
    if (!Date.now) {
        Date.now = function () {
            return new Date().getTime();
        }
    }

    // Define outage object.
    var auth_outage_countdown = {
        timer: null
        , countdown: <?php echo($outage->starttime - time()); ?>
        , clienttime: Date.now()
        , init: function () {
            this.span = document.getElementById('auth_outage_warningbar_countdown');
            this.text = this.span.innerHTML;
            this.tick();
            var $this = this;
            this.timer = setInterval(function () {
                $this.tick();
            }, 1000);
        }
        , tick: function () {
            var elapsed = Math.round((Date.now() - this.clienttime) / 1000);
            var missing = this.countdown - elapsed;
            if (missing <= 0) {
                clearInterval(this.timer);
                missing = 0;
                <?php
                if (!is_siteadmin()) {
                    echo 'location.href = "' . (new \moodle_url('/auth/outage/info.php')) . '";';
                }
                ?>
            }
            else {
                this.span.innerHTML = this.text.replace('{{countdown}}', this.seconds2hms(missing));
            }
        }
        , seconds2hms: function (seconds) {
            var minutes = Math.floor(seconds / 60);
            var hours = Math.floor(minutes / 60);
            seconds %= 60;
            minutes %= 60;
            // Cross-browser simple solution for padding zeroes.
            if (minutes < 10) {
                minutes = "0" + minutes;
            }
            if (seconds < 10) {
                seconds = "0" + seconds;
            }
            return hours + ':' + minutes + ':' + seconds;
        }
    };
    auth_outage_countdown.init();
</script>

<div class="auth_outage_warningbar_spacer">&nbsp;</div>

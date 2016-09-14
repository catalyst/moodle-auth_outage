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

$infolink = new moodle_url('/auth/outage/info.php', ['id' => $outage->id]);

echo html_writer::tag('style', get_config('auth_outage', 'css'));
?>

<div id="auth_outage_warningbar_box">
    <div class="auth_outage_warningbar_center">
        <div id="auth_outage_warningbar_countdown"><?php echo $countdown; ?></div>
        <div class="auth_outage_warningbar_box_message">
            <?php echo html_writer::link($infolink, $outage->get_title(), ['target' => '_blank']); ?>
        </div>
    </div>
</div>

<?php if (!$outage->is_ongoing($time)): ?>
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

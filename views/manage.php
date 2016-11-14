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
 * View to manage outages.
 *
 * @package    auth_outage
 * @author     Daniel Thee Roperto <daniel.roperto@catalyst-au.net>
 * @copyright  2016 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use auth_outage\output\manage\history_table;
use auth_outage\output\manage\planned_table;
use auth_outage\output\renderer;

defined('MOODLE_INTERNAL') || die();

$urlnew = new moodle_url('/auth/outage/edit.php');

echo $viewbag['warning'];
?>

<section id="section_planned_outages">
    <?php echo renderer::get()->rendersubtitle('outageslistfuture'); ?>
    <?php if (empty($viewbag['unended'])): ?>
        <p>
            <small><?php echo get_string('notfound', 'auth_outage'); ?></small>
        </p>
    <?php else: ?>
        <?php
        $table = new planned_table();
        $table->show_data($viewbag['unended']);
        $table->finish_output();
        ?>
    <?php endif; ?>
    <input type="button" class="form-submit"
           value="<?php echo get_string('outagecreate', 'auth_outage'); ?>"
           onclick="location.href='<?php echo $urlnew; ?>';"/>
</section>

<section id="section_outage_history">
    <?php echo renderer::get()->rendersubtitle('outageslistpast'); ?>
    <?php if (empty($viewbag['ended'])): ?>
        <p>
            <small><?php echo get_string('notfound', 'auth_outage'); ?></small>
        </p>
    <?php else: ?>
        <?php
        $table = new history_table();
        $table->show_data($viewbag['ended']);
        $table->finish_output();
        ?>
    <?php endif; ?>
</section>

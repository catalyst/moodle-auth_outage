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

namespace auth_outage\output;

use auth_outage\local\outage;
use auth_outage\output\manage\history_table;
use auth_outage\output\manage\planned_table;
use coding_exception;
use html_writer;
use moodle_url;
use plugin_renderer_base;

defined('MOODLE_INTERNAL') || die();

/**
 * auth_outage auth_outage_renderer
 *
 * @package    auth_outage
 * @author     Daniel Thee Roperto <daniel.roperto@catalyst-au.net>
 * @copyright  2016 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class renderer extends plugin_renderer_base {
    /**
     * Returns the outage renderer.
     * @return renderer The outage renderer.
     */
    public static function get() {
        global $PAGE;
        return $PAGE->get_renderer('auth_outage');
    }

    /**
     * Outputs the view in a separate scope to avoid conflicts with variable names.
     * @param string $view View PHP file.
     * @param mixed[] $viewbag Values to be used in the view.
     * @throws coding_exception
     */
    public function output_view($view, $viewbag = []) {
        global $CFG;

        $viewbag['viewfile'] = $view;
        unset($view);

        require($CFG->dirroot.'/auth/outage/views/'.$viewbag['viewfile']);
    }

    /**
     * Renders the view in a separate scope to avoid conflicts with variable names.
     * @param string $view View PHP file.
     * @param mixed[] $viewbag Values to be used in the view.
     * @return string The rendered view code.
     */
    public function render_view($view, $viewbag = []) {
        ob_start();
        $this->output_view($view, $viewbag);
        $html = ob_get_contents();
        ob_end_clean();
        return $html;
    }

    /**
     * Renders the subtitle of the page.
     * @param string $subtitlekey Key to be used and localized.
     * @return string HTML for the subtitle.
     * @throws coding_exception
     */
    public function rendersubtitle($subtitlekey) {
        if (!is_string($subtitlekey)) {
            throw new coding_exception('$subtitlekeym is not a string.', $subtitlekey);
        }
        return html_writer::tag('h2', get_string($subtitlekey, 'auth_outage'));
    }

    /**
     * Renders a confirmation to delete an outage.
     * @param outage $outage Outage to be deleted.
     * @return string HTML for the page.
     */
    public function renderdeleteconfirmation(outage $outage) {
        return $this->rendersubtitle('outagedelete').
               html_writer::tag('p', get_string('outagedeletewarning', 'auth_outage')).
               $this->renderoutage($outage, false);
    }

    /**
     * Renders a confirmation to finish an outage.
     * @param outage $outage Outage to be finished.
     * @return string HTML for the page.
     */
    public function renderfinishconfirmation(outage $outage) {
        return $this->rendersubtitle('outagefinish').
               html_writer::tag('p', get_string('outagefinishwarning', 'auth_outage')).
               $this->renderoutage($outage, false);
    }

    /**
     * Outputs the HTML data listing all given outages.
     * @param outage[] $future Outages to list as planned.
     * @param outage[] $past Outages to list as history.
     */
    public function renderoutagelist(array $future, array $past) {
        global $OUTPUT;

        echo html_writer::start_tag('section', ['id' => 'section_planned_outages']);
        echo $this->rendersubtitle('outageslistfuture');
        if (empty($future)) {
            echo html_writer::tag('p', html_writer::tag('small', get_string('notfound', 'auth_outage')));
        } else {
            $table = new planned_table();
            $table->set_data($future);
            $table->finish_output();
        }
        $url = new moodle_url('/auth/outage/new.php');
        echo html_writer::empty_tag('input', [
            'class' => 'form-submit',
            'type' => 'button',
            'value' => get_string('outagecreate', 'auth_outage'),
            'onclick' => "location.href='${url}';"
        ]);
        echo html_writer::end_tag('section');

        echo html_writer::start_tag('section', ['id' => 'section_outage_history']);
        echo $this->rendersubtitle('outageslistpast');
        if (empty($past)) {
            echo html_writer::tag('p', html_writer::tag('small', get_string('notfound', 'auth_outage')));
        } else {
            $table = new history_table();
            $table->set_data($past);
            $table->finish_output();
        }
        echo html_writer::end_tag('section');
    }

    /**
     * Renders the warning bar.
     * @param outage $outage The outage to show in the warning bar.
     * @param int $time Timestamp to send to the outage bar in order to render the outage.
     * @param bool $static If the warning bar is rendering in a static page.
     * @param bool $preview If in preview mode the warning bar will not check if we are back online.
     * @return string HTML of the warning bar.
     * @throws coding_exception
     * @SuppressWarnings("unused") because $viewbag is used inside require()
     */
    public function render_warningbar(outage $outage, $time, $static, $preview) {
        global $CFG;
        if (!is_int($time) || ($time <= 0)) {
            throw new coding_exception('$time is not an positive int or null.', $time);
        }
        if (!is_bool($static)) {
            throw new coding_exception('$static is not a bool.');
        }
        if (!is_bool($preview)) {
            throw new coding_exception('$preview is not a bool.');
        }

        $viewbag = [
            'time' => $time,
            'outage' => $outage,
            'static' => $static,
            'preview' => $preview,
        ];

        return $this->render_view('warningbar/warningbar.php', $viewbag);
    }

    /**
     * Returns the HTML for displaying and outage information.
     * @param outage $outage Outage to display.
     * @param bool $buttons If should display management buttons (edit, delete, etc).
     * @return string The formatted HTML.
     */
    private function renderoutage(outage $outage, $buttons) {
        global $OUTPUT;

        if ($outage->createdby == 0) {
            $created = get_string('na', 'auth_outage');
        } else {
            $created = core_user::get_user($outage->createdby, 'firstname,lastname', MUST_EXIST);
            $created = html_writer::link(
                new moodle_url('/user/profile.php', ['id' => $outage->createdby]),
                trim($created->firstname.' '.$created->lastname)
            );
        }

        if ($outage->modifiedby == 0) {
            $modified = get_string('na', 'auth_outage');
        } else {
            $modified = core_user::get_user($outage->modifiedby, 'firstname,lastname', MUST_EXIST);
            $modified = html_writer::link(
                new moodle_url('/user/profile.php', ['id' => $outage->modifiedby]),
                trim($modified->firstname.' '.$modified->lastname)
            );
        }

        $url = new moodle_url('/auth/outage/edit.php', ['id' => $outage->id]);
        $img = html_writer::empty_tag(
            'img',
            ['src' => $OUTPUT->pix_url('t/edit'), 'alt' => get_string('edit'), 'class' => 'iconsmall']
        );
        $linkedit = html_writer::link($url, $img, ['title' => get_string('edit')]);

        $url = new moodle_url('/auth/outage/delete.php', ['id' => $outage->id]);
        $img = html_writer::empty_tag(
            'img',
            ['src' => $OUTPUT->pix_url('t/delete'), 'alt' => get_string('delete'), 'class' => 'iconsmall']
        );
        $linkdelete = html_writer::link($url, $img, ['title' => get_string('delete')]);

        $finished = $outage->finished;
        if (is_null($finished)) {
            $finished = get_string('na', 'auth_outage');
        } else {
            $finished = userdate($finished, get_string('datetimeformat', 'auth_outage'));
        }

        return html_writer::div(
            html_writer::tag('blockquote',
                             html_writer::div(html_writer::tag('b', $outage->get_title(), ['data-id' => $outage->id])).
                             html_writer::div(html_writer::tag('i', $outage->get_description())).
                             html_writer::div(
                                 html_writer::tag('b', get_string('tableheaderwarnbefore', 'auth_outage').': ').
                                 format_time($outage->get_warning_duration())
                             ).
                             html_writer::div(
                                 html_writer::tag('b', get_string('tableheaderstarttime', 'auth_outage').': ').
                                 userdate($outage->starttime, get_string('datetimeformat', 'auth_outage'))
                             ).
                             html_writer::div(
                                 html_writer::tag('b', get_string('tableheaderdurationplanned', 'auth_outage').': ').
                                 format_time($outage->get_duration_planned())
                             ).
                             html_writer::div(
                                 html_writer::tag('b', get_string('tableheaderdurationactual', 'auth_outage').': ').
                                 $finished
                             ).
                             html_writer::div(
                                 html_writer::tag('small',
                                                  'Created by '.$created.
                                                  ', modified by '.$modified.' on '.
                                                  userdate($outage->lastmodified, get_string('datetimeformat', 'auth_outage'))
                                 )
                             ).
                             ($buttons ? html_writer::div($linkedit.$linkdelete) : '')
            )
        );
    }
}

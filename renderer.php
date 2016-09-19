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

use auth_outage\models\outage;
use auth_outage\tables\manage\planned;

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.'); // It must be included from a Moodle page.
}

/**
 * auth_outage auth_outage_renderer
 *
 * @package    auth_outage
 * @author     Daniel Thee Roperto <daniel.roperto@catalyst-au.net>
 * @copyright  Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class auth_outage_renderer extends plugin_renderer_base {
    /**
     * Renders the subtitle of the page.
     * @param string $subtitlekey Key to be used and localized.
     * @return string HTML for the subtitle.
     */
    public function rendersubtitle($subtitlekey) {
        if (!is_string($subtitlekey)) {
            throw new \InvalidArgumentException('$subtitle is not a string.');
        }
        return html_writer::tag('h2', get_string($subtitlekey, 'auth_outage'));
    }

    /**
     * Renders a confirmation to delete an outage.
     * @param outage $outage Outage to be deleted.
     * @return string HTML for the page.
     */
    public function renderdeleteconfirmation(outage $outage) {
        return $this->rendersubtitle('outagedelete')
        . html_writer::tag('p', get_string('outagedeletewarning', 'auth_outage'))
        . $this->renderoutage($outage, false);
    }

    /**
     * Renders a confirmation to finish an outage.
     * @param outage $outage Outage to be finished.
     * @return string HTML for the page.
     */
    public function renderfinishconfirmation(outage $outage) {
        return $this->rendersubtitle('outagefinish')
        . html_writer::tag('p', get_string('outagefinishwarning', 'auth_outage'))
        . $this->renderoutage($outage, false);
    }

    /**
     * Outputs the HTML data listing all given outages.
     * @param array $future Outages to list as planned.
     * @param array $past Outages to list as history.
     */
    public function renderoutagelist(array $future, array $past) {
        global $OUTPUT;

        // Add 'add' button.
        $url = new moodle_url('/auth/outage/new.php');
        $img = html_writer::empty_tag('img',
            ['src' => $OUTPUT->pix_url('t/add'), 'alt' => get_string('create'), 'class' => 'iconsmall']);
        echo html_writer::tag('p',
            html_writer::link(
                $url,
                $img . ' ' . get_string('outagecreate', 'auth_outage'),
                ['title' => get_string('delete')]
            )
        );

        echo $this->rendersubtitle('outageslistfuture');
        if (empty($future)) {
            echo html_writer::tag('p', html_writer::tag('small', get_string('notfound', 'auth_outage')));
        } else {
            $table = new planned();
            $table->set_data($future);
            $table->finish_output();
        }

        echo $this->rendersubtitle('outageslistpast');
        if (empty($past)) {
            echo html_writer::tag('p', html_writer::tag('small', get_string('notfound', 'auth_outage')));
        } else {
            $table = new \auth_outage\tables\manage\history();
            $table->set_data($past);
            $table->finish_output();
        }
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
                trim($created->firstname . ' ' . $created->lastname)
            );
        }

        if ($outage->modifiedby == 0) {
            $modified = get_string('na', 'auth_outage');
        } else {
            $modified = core_user::get_user($outage->modifiedby, 'firstname,lastname', MUST_EXIST);
            $modified = html_writer::link(
                new moodle_url('/user/profile.php', ['id' => $outage->modifiedby]),
                trim($modified->firstname . ' ' . $modified->lastname)
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
                html_writer::div(html_writer::tag('b', $outage->get_title(), ['data-id' => $outage->id]))
                . html_writer::div(html_writer::tag('i', $outage->get_description()))
                . html_writer::div(
                    html_writer::tag('b', get_string('tableheaderwarnbefore', 'auth_outage') . ': ')
                    . format_time($outage->get_warning_duration())
                )
                . html_writer::div(
                    html_writer::tag('b', get_string('tableheaderstarttime', 'auth_outage') . ': ')
                    . userdate($outage->starttime, get_string('datetimeformat', 'auth_outage'))
                )
                . html_writer::div(
                    html_writer::tag('b', get_string('tableheaderdurationplanned', 'auth_outage') . ': ')
                    . format_time($outage->get_duration_planned())
                )
                . html_writer::div(
                    html_writer::tag('b', get_string('tableheaderdurationactual', 'auth_outage') . ': ')
                    . $finished
                )
                . html_writer::div(
                    html_writer::tag('small',
                        'Created by ' . $created
                        . ', modified by ' . $modified . ' on '
                        . userdate($outage->lastmodified, get_string('datetimeformat', 'auth_outage'))
                    )
                )
                . ($buttons ? html_writer::div($linkedit . $linkdelete) : '')
            )
        );
    }

    /**
     * @param outage $outage
     * @param null $time
     * @return string
     * @SuppressWarnings("unused") because $admineditlink is used inside require(...)
     */
    public function renderoutagepage(outage $outage, $time = null) {
        global $CFG;

        if (is_null($time)) {
            $time = time();
        }
        if (!is_int($time)) {
            throw new \InvalidArgumentException('$time is not an int or null.');
        }

        $adminlinks = [];
        foreach ([
                     'startofwarning' => -$outage->get_warning_duration(),
                     '15secondsbefore' => -15,
                     'start' => 0,
                     'endofoutage' => $outage->get_duration_planned(),
                 ] as $title => $delta) {
            $adminlinks[] = html_writer::link(
                new moodle_url(
                    '/auth/outage/info.php',
                    [
                        'id' => $outage->id,
                        'auth_outage_preview' => $outage->id,
                        'auth_outage_delta' => $delta,
                    ]
                ),
                get_string('info' . $title, 'auth_outage')
            );
        }

        $admineditlink = html_writer::link(
            new moodle_url('/auth/outage/edit.php', ['id' => $outage->id]),
            get_string('outageedit', 'auth_outage')
        );

        ob_start();
        require($CFG->dirroot . '/auth/outage/views/infopage.php');
        $html = ob_get_contents();
        ob_end_clean();
        return $html;
    }

    /**
     * Renders the warning bar.
     * @param outage $outage The outage to show in the warning bar.
     * @param int|null $time Timestamp to send to the outage bar in order to render the outage. Null for current time.
     * @return string HTML of the warning bar.
     * @SuppressWarnings("unused") because $countdown is used inside require(...)
     */
    public function renderoutagebar(outage $outage, $time = null) {
        global $CFG;

        if (is_null($time)) {
            $time = time();
        }
        if (!is_int($time)) {
            throw new \InvalidArgumentException('$time is not an int or null.');
        }

        $start = userdate($outage->starttime, get_string('datetimeformat', 'auth_outage'));
        $stop = userdate($outage->stoptime, get_string('datetimeformat', 'auth_outage'));

        $countdown = get_string(
            $outage->is_ongoing($time) ? 'messageoutageongoing' : 'messageoutagewarning',
            'auth_outage',
            ['start' => $start, 'stop' => $stop]
        );

        ob_start();
        require($CFG->dirroot . '/auth/outage/views/warningbar.php');
        $html = ob_get_contents();
        ob_end_clean();
        return $html;
    }
}

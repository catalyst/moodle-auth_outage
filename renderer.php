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

use \auth_outage\outage;

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
class auth_outage_renderer extends plugin_renderer_base
{
    public function renderoutagelist(array $outages) {
        global $OUTPUT;

        $html = html_writer::tag('h1', 'Outage List');

        // Generate list of outages.
        foreach ($outages as $outage) {
            $html .= $this->renderoutagelistentry($outage);
        }

        // Add 'add' button.
        $url = new moodle_url('/auth/outage/update.php', ['action' => 'add', 'sesskey' => sesskey()]);
        $img = html_writer::empty_tag('img',
            ['src' => $OUTPUT->pix_url('t/add'), 'alt' => get_string('add'), 'class' => 'iconsmall']);
        $html .= html_writer::empty_tag('br')
            . html_writer::link($url, $img . ' Create new Outage', ['title' => get_string('delete')])
            . html_writer::empty_tag('br');

        return $html;
    }

    private function renderoutagelistentry(outage $outage) {
        global $OUTPUT;

        $created = core_user::get_user($outage->get_createdby(), 'firstname,lastname', MUST_EXIST);
        $created = html_writer::link(
            new moodle_url('/user/profile.php', ['id' => $outage->get_createdby()]),
            trim($created->firstname . ' ' . $created->lastname)
        );

        $modified = core_user::get_user($outage->get_modifiedby(), 'firstname,lastname', MUST_EXIST);
        $modified = html_writer::link(
            new moodle_url('/user/profile.php', ['id' => $outage->get_modifiedby()]),
            trim($modified->firstname . ' ' . $modified->lastname)
        );

        $url = new moodle_url('/auth/outage/update.php', ['id' => $outage->get_id(), 'sesskey' => sesskey()]);

        $url->param('action', 'edit');
        $img = html_writer::empty_tag('img',
            ['src' => $OUTPUT->pix_url('t/edit'), 'alt' => get_string('edit'), 'class' => 'iconsmall']);
        $linkedit = html_writer::link($url, $img, ['title' => get_string('edit')]);

        $url->param('action', 'delete');
        $img = html_writer::empty_tag('img',
            ['src' => $OUTPUT->pix_url('t/delete'), 'alt' => get_string('delete'), 'class' => 'iconsmall']);
        $linkdelete = html_writer::link($url, $img, ['title' => get_string('delete')]);

        return html_writer::div(
            html_writer::span(
                html_writer::tag('b', $outage->get_title(), ['data-id' => $outage->get_id()])
                . html_writer::empty_tag('br')
                . html_writer::tag('i', $outage->get_description())
                . html_writer::empty_tag('br')
                . html_writer::tag('b', 'Warning: ')
                . userdate($outage->get_starttime() - ($outage->get_warningminutes() * 60))
                . html_writer::empty_tag('br')
                . html_writer::tag('b', 'Starts: ')
                . userdate($outage->get_starttime(), '%d %h %Y %l:%M%P')
                . html_writer::empty_tag('br')
                . html_writer::tag('b', 'Stops: ')
                . userdate($outage->get_stoptime(), '%d %h %Y %l:%M%P')
                . html_writer::empty_tag('br')
                . html_writer::tag('small',
                    'Created by ' . $created
                    . ', modified by ' . $modified . ' on '
                    . userdate($outage->get_lastmodified(), '%d %h %Y %l:%M%P')
                )
                . html_writer::empty_tag('br')
                . $linkedit . $linkdelete
                . html_writer::empty_tag('br')
                . html_writer::empty_tag('br')
            )
        );
    }
}

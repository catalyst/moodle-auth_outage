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
 * This page is used to regenerate and preview a maintenance mode static page.
 *
 * @package    auth_outage
 * @author     Daniel Thee Roperto <daniel.roperto@catalyst-au.net>
 * @copyright  2016 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @var stdClass $CFG
 */

use auth_outage\dml\outagedb;
use auth_outage\local\controllers\maintenance_static_page;

// @codingStandardsIgnoreStart
require_once(__DIR__.'/../../config.php');
// @codingStandardsIgnoreEnd
$id = optional_param('id', null, PARAM_INT);
$outage = is_null($id) ? outagedb::get_next_starting() : outagedb::get_by_id($id);
if (is_null($outage)) {
    throw new invalid_parameter_exception('Outage not found.');
}

$page = maintenance_static_page::create_from_outage($outage);
$page->get_io()->set_preview(true);
$page->generate();
readfile($page->get_io()->get_template_file());

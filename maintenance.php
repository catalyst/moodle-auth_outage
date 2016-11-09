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
 */

use auth_outage\dml\outagedb;
use auth_outage\local\controllers\maintenance_static_page;

if (isset($_GET['file'])) {
    define('NO_DEBUG_DISPLAY', true);
    define('ABORT_AFTER_CONFIG', true);
    require_once(__DIR__.'/../../config.php');

    // We are not using any external libraries or references in this file (cli maintenance is active).
    // If you change the path below maybe you need to change maintenance_static_page::get_resources_folder() as well.
    $resourcedir = $CFG->dataroot.'/auth_outage/climaintenance';

    // Protect against path traversal attacks.
    $file = $resourcedir.'/'.$_GET['file'];
    if (realpath($file) !== $file) {
        // @codingStandardsIgnoreStart
        error_log('Invalid file: '.$_GET['file']);
        // @codingStandardsIgnoreEnd
        http_response_code(404);
        die('Not found.');
    }

    // Detect type, we only support css or PNG images.
    header('Content-Type: '.(substr($file, -3) == 'css' ? 'text/css' : 'image/png'));

    // Use cache.
    $lifetime = 60 * 60 * 24; // 1 day.
    header('Expires: '. gmdate('D, d M Y H:i:s', time() + $lifetime) .' GMT');
    header('Pragma: ');
    header('Cache-Control: public, max-age='.$lifetime);
    header('Accept-Ranges: none');

    readfile($file);
    return;
}

if (isset($_GET['debug'])) {
    // Use auth/outage/maintenance.php?debug to preview how it will render without triggering maintenance mode.

    require_once(__DIR__.'/../../config.php');
    $id = optional_param('id', null, PARAM_INT);
    $outage = is_null($id) ? outagedb::get_next_starting() : outagedb::get_by_id($id);
    if (is_null($outage)) {
        throw new invalid_parameter_exception('Outage not found.');
    }

    $page = maintenance_static_page::create_from_outage($outage);
    $page->set_preview(true);
    $page->generate();
    readfile($page->get_template_file());
    return;
}
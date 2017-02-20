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
 * This page is used to fetch files while in maintenance mode.
 *
 * It should avoid as much as possible using code Moodle API.
 *
 * @package    auth_outage
 * @author     Daniel Thee Roperto <daniel.roperto@catalyst-au.net>
 * @copyright  2016 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @var stdClass $CFG
 */

// This file does not use Moodle initialization as a requirement. Supress Warning.
define('MOODLE_INTERNAL', true);
defined('MOODLE_INTERNAL') || die();

// File should have at least 3 characters as we will check the extension below.
if (!isset($_GET['file'])) {
    http_response_code(400);
    die('Missing file parameter.');
}

$parts = explode('.', $_GET['file']);
if (count($parts) != 2) {
    http_response_code(400);
    die('Invalid file requested.');
}
$mime = base64_decode($parts[1]);

// Detect type, we only support css or PNG images.
header('Content-Type: '.$mime);

// Use cache.
$lifetime = 60 * 60 * 24; // 1 day.
header('Expires: '.gmdate('D, d M Y H:i:s', time() + $lifetime).' GMT');
header('Pragma: ');
header('Cache-Control: public, max-age='.$lifetime);
header('Accept-Ranges: none');


/**
 * Callback used in bootstrap.
 * @SupressWarnings(PHPMD)
 */
function auth_outage_bootstrap_callback() {
    // Not using classes as classloader has not been initialized yet. Keep it minimalist.
    require_once(__DIR__.'/lib.php');
    $file = auth_outage_get_climaintenance_resource_file($_GET['file']);
    if (is_null($file)) {
        // @codingStandardsIgnoreStart
        error_log('Invalid file: '.$_GET['file']);
        // @codingStandardsIgnoreEnd
        http_response_code(404);
        die('File not found.');
    }
    readfile($file);
    exit(0);
}

require_once(__DIR__.'/../../config.php');

// We should never reach here if config.php and auth/outage/bootstrap.php intercepted it correctly.
// If config.php did not execute the callback function we can use the debugging function here.
debugging('Your config.php is not properly configured for auth/outage plugin. '.
          'Please check the plugin settings for information.');

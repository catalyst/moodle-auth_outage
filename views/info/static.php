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
 * View included by the renderer to output the static outage information page.
 *
 * @package    auth_outage
 * @author     Daniel Thee Roperto <daniel.roperto@catalyst-au.net>
 * @copyright  2016 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use auth_outage\output\renderer;

defined('MOODLE_INTERNAL') || die();

global $SITE;

// The Meta Refresh will ensure the page keeps refreshing every 5 minutes until outage is over.
?>
<!DOCTYPE html>
<html data-outage-id="<?php echo $viewbag['outage']->id; ?>">
<head>
    <title><?php echo strip_tags($SITE->fullname); ?></title>
    <meta http-equiv="refresh" content="<?php echo (5 * 60); ?>">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body {
            font-family: sans-serif;
        }
    </style>
</head>
<body>

<?php
// Static page is rendered as if outage started. It is never rendered as admin or preview mode.
echo renderer::get()->render_warningbar($viewbag['outage'], $viewbag['outage']->starttime, false, false);
?>

<header>
    <h1><?php echo strip_tags($SITE->fullname); ?></h1>
</header>

<section>
    <h2><?php echo $viewbag['outage']->get_title(); ?></h2>
    <?php echo renderer::get()->render_view('info/content.php', $viewbag); ?>
</section>

<!-- <?php echo
get_string(
    'infopagestaticgenerated',
    'auth_outage',
    ['time' => userdate(time(), get_string('datetimeformat', 'auth_outage'))]
);
?> -->

</body>
</html>

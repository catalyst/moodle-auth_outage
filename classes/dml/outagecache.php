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

namespace auth_outage\dml;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/calendar/lib.php');

/**
 * outagecache class.
 *
 * To manipulate outage cache.
 *
 * @package    auth_outage
 * @author     Qihui Chan <qihuichan@catalyst-au.net>
 * @copyright  2022 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class outagecache {
    /**
     * Private constructor, use static methods instead.
     */
    private function __construct() {
    }

    /**
     * Set active outage cache.
     *
     * @param outage|null $nextoutage Next outage to save, null for not setting outage.
     */
    public static function set_active_outage_cache($nextoutage = null) {
        $cache = \cache::make('auth_outage', 'cache_active_outage_data');
        $cache->set('cache_active_outage_data', $nextoutage);
    }

    /**
     * Get active outage cache.
     *
     * @return outage|null Return the outage or null if cache not setted.
     */
    public static function get_active_outage_cache() {
        $cache = \cache::make('auth_outage', 'cache_active_outage_data');
        return $cache->get('cache_active_outage_data');
    }

    /**
     * Set onging outage cache.
     *
     * @param outage|null $ongingoutage Onging outage to save, null for not setting outage.
     */
    public static function set_ongoing_outage_cache($ongingoutage = null) {
        $cache = \cache::make('auth_outage', 'cache_ongoing_outage_data');
        $cache->set('cache_ongoing_outage_data', $ongingoutage);
    }

    /**
     * Get onging outage cache.
     *
     * @return outage|null Return the outage or null if cache not setted.
     */
    public static function get_ongoing_outage_cache() {
        $cache = \cache::make('auth_outage', 'cache_ongoing_outage_data');
        return $cache->get('cache_ongoing_outage_data');
    }

    /**
     * Delete onging outage cache.
     */
    public static function delete_ongoing_outage_cache() {
        $cache = \cache::make('auth_outage', 'cache_ongoing_outage_data');
        $cache->delete('cache_ongoing_outage_data');
    }
}

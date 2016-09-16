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

namespace auth_outage\cli;

use auth_outage\models\outage;
use auth_outage\outagedb;

defined('MOODLE_INTERNAL') || die();

/**
 * Outage CLI to create outage.
 *
 * @package    auth_outage
 * @author     Daniel Thee Roperto <daniel.roperto@catalyst-au.net>
 * @copyright  Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class create extends clibase {
    /**
     * @var array Defaults to use if given option is null.
     */
    private $defaults;

    /**
     * Generates all options (parameters) available for the CLI command.
     * @return array Options.
     */
    public function generateoptions() {
        // Do not provide some defaults, if cloning an outage we need to know which parameters were provided.
        $options = [
            'help' => false,
            'clone' => null,
            'warn' => null,
            'start' => null,
            'duration' => null,
            'title' => null,
            'description' => null,
            'onlyid' => false,
            'block' => false,
        ];
        return $options;
    }

    /**
     * Generate all short forms for the available options.
     * @return array Short form options.
     */
    public function generateshortcuts() {
        return [
            'b' => 'block',
            'c' => 'clone',
            'd' => 'duration',
            'e' => 'description',
            'h' => 'help',
            's' => 'start',
            't' => 'title',
            'w' => 'warn',
        ];
    }

    /**
     * Sets the default values for options.
     * @param array $defaults Defaults.
     */
    public function set_defaults(array $defaults) {
        $this->defaults = $defaults;
    }

    /**
     * Executes the CLI.
     */
    public function execute() {
        // Help always overrides any other parameter.
        if ($this->options['help']) {
            $this->showhelp('create');
            return;
        }

        // If not help mode, 'start' is required and cannot use default.
        if (is_null($this->options['start'])) {
            throw new cliexception(get_string('clierrormissingparamaters', 'auth_outage'));
        }

        // If cloning, set defaults to outage being cloned.
        if (!is_null($this->options['clone'])) {
            $this->clonedefaults();
        }

        // Merge provided parameters with defaults then create outage.
        $options = $this->mergeoptions();
        $id = $this->createoutage($options);

        if ($options['block']) {
            $block = new waitforit(['outageid' => $id]);
            $block->execute();
        }
    }

    /**
     * Merges provided options with defaults, checking and converting types as needed.
     * @return array Parameters to use.
     * @throws cliexception
     */
    private function mergeoptions() {
        $options = $this->options;
        // Merge with defaults.
        if (!is_null($this->defaults)) {
            foreach ($options as $k => $v) {
                if (is_null($v) && array_key_exists($k, $this->defaults)) {
                    $options[$k] = $this->defaults[$k];
                }
            }
        }

        return $this->mergeoptions_checkparameters($options);
    }

    /**
     * Creates an outages based on the provided options.
     * @param array $options Options used to create the outage.
     * @return int Id of the new outage.
     */
    private function createoutage(array $options) {
        // We need to become an admin to avoid permission problems.
        $this->becomeadmin();

        // Create the outage.
        $start = $this->time + ($options['start'] * 60);
        $outage = new outage([
            'warntime' => $start - ($options['warn'] * 60),
            'starttime' => $start,
            'stoptime' => $start + ($options['duration'] * 60),
            'title' => $options['title'],
            'description' => $options['description'],
        ]);
        $id = outagedb::save($outage);

        // All done!
        if ($options['onlyid']) {
            printf("%d\n", $id);
        } else {
            printf("%s\n", get_string('clioutagecreated', 'auth_outage', ['id' => $id]));
        }

        return $id;
    }

    private function clonedefaults() {
        $id = $this->options['clone'];
        if (!is_number($id) || ($id <= 0)) {
            throw new cliexception(get_string('clierrorinvalidvalue', 'auth_outage', ['param' => 'clone']));
        }

        $outage = outagedb::get_by_id((int)$id);
        $this->set_defaults([
            'warn' => (int)($outage->get_warning_duration() / 60),
            'duration' => (int)($outage->get_duration() / 60),
            'title' => $outage->title,
            'description' => $outage->description,
        ]);
    }

    /**
     * Check parameters converting their type as needed.
     * @param array $options Input options.
     * @return array Output options.
     * @throws cliexception
     */
    private function mergeoptions_checkparameters(array $options) {
        // Check parameters that must be a non-negative int while converting their type to int.
        foreach (['start', 'warn', 'duration'] as $param) {
            if (!is_number($options[$param])) {
                throw new cliexception(get_string('clierrorinvalidvalue', 'auth_outage', ['param' => $param]));
            }
            $options[$param] = (int)$options[$param];
            if ($options[$param] < 0) {
                throw new cliexception(get_string('clierrorinvalidvalue', 'auth_outage', ['param' => $param]));
            }
        }

        // Check parameters that must be a non empty string.
        foreach (['title', 'description'] as $param) {
            if (!is_string($options[$param])) {
                throw new cliexception(get_string('clierrorinvalidvalue', 'auth_outage', ['param' => $param]));
            }
            $options[$param] = trim($options[$param]);
            if (strlen($options[$param]) == 0) {
                throw new cliexception(get_string('clierrorinvalidvalue', 'auth_outage', ['param' => $param]));
            }
        }

        return $options;
    }
}

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
 * waitforit class.
 *
 * @package    auth_outage
 * @author     Daniel Thee Roperto <daniel.roperto@catalyst-au.net>
 * @copyright  2016 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace auth_outage\local\cli;

use auth_outage\dml\outagedb;
use auth_outage\local\outage;

/**
 * waitforit class.
 *
 * @package    auth_outage
 * @author     Daniel Thee Roperto <daniel.roperto@catalyst-au.net>
 * @copyright  2016 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class waitforit extends clibase {
    /**
     * Default value if --sleep no provided.
     */
    const DEFAULT_SLEEP_SECONDS = 300;

    /**
     * @var callable Alternative callback for sleeping thread, must return the new reference timestamp.
     */
    private $sleepcallback = null;

    /**
     * Generates all options (parameters) available for the CLI command.
     * @return mixed[] Options.
     */
    public function generate_options() {
        // Do not provide some defaults, if cloning an outage we need to know which parameters were provided.
        $options = [
            'help' => false,
            'outageid' => null,
            'active' => false,
            'verbose' => false,
            'sleep' => self::DEFAULT_SLEEP_SECONDS,
        ];
        return $options;
    }

    /**
     * Generate all short forms for the available options.
     * @return string[] Short form options.
     */
    public function generate_shortcuts() {
        return [
            'h' => 'help',
            'id' => 'outageid',
            'a' => 'active',
            'v' => 'verbose',
            's' => 'sleep',
        ];
    }

    /**
     * Sets a callback to be used instead of the sleep method.
     * @param callable $callback Callback function.
     */
    public function set_sleepcallback(callable $callback) {
        $this->sleepcallback = $callback;
    }

    /**
     * Executes the CLI.
     */
    public function execute() {
        // Help always overrides any other parameter.
        if ($this->options['help']) {
            $this->show_help('waitforit');
            return;
        }

        // Requires outageid or active but not both at the same time.
        $byid = !is_null($this->options['outageid']);
        $byactive = $this->options['active'];
        if ($byid == $byactive) {
            throw new cli_exception(get_string('cliwaitforiterroridxoractive', 'auth_outage'),
                cli_exception::ERROR_PARAMETER_INVALID);
        }

        $this->verbose('Verbose mode activated.');

        $outage = $this->get_outage();

        while ($sleep = $this->wait_for_outage_to_start($outage)) {
            if (is_null($this->sleepcallback)) {
                $this->verbose('Sleeping for '.$sleep.' second(s).');
                sleep($sleep);
                $this->time = time();
            } else {
                $this->verbose('Calling callback to sleep '.$sleep.' second(s).');
                $callback = $this->sleepcallback;
                $this->time = $callback($sleep);
            }
        }
    }

    /**
     * Shows a message if in verbose mode.
     * @param string $message Message.
     */
    private function verbose($message) {
        if (!$this->options['verbose']) {
            return;
        }

        $time = strftime('%F %T %Z');
        printf("[%s] %s\n", $time, $message);
    }

    /**
     * Gets the outage to wait for.
     * @return outage|null The outage to wait for.
     * @throws cli_exception
     */
    private function get_outage() {
        if ($this->options['active']) {
            $this->verbose('Querying database for active outage...');
            $outage = outagedb::get_active();
        } else {
            $id = $this->options['outageid'];
            if (!is_number($id) || ($id <= 0)) {
                throw new cli_exception(get_string('clierrorinvalidvalue', 'auth_outage', ['param' => 'outageid']),
                    cli_exception::ERROR_PARAMETER_INVALID);
            }
            $this->verbose('Querying database for outage #'.$id.'...');
            $outage = outagedb::get_by_id((int)$id);
        }

        if (is_null($outage)) {
            throw new cli_exception(get_string('clierroroutagenotfound', 'auth_outage'), cli_exception::ERROR_OUTAGE_NOT_FOUND);
        }

        $this->verbose('Found outage #'.$outage->id.': '.$outage->get_title());
        return $outage;
    }

    /**
     * Calculate how many seconds to wait for the outage to start.
     * @param outage $outage Outage to consider.
     * @return int Seconds until it stars.
     * @throws cli_exception
     */
    private function wait_for_outage_to_start(outage $outage) {
        $this->verbose('Checking outage status...');
        // Outage should not change while waiting to start.
        if (outagedb::get_by_id($outage->id) != $outage) {
            throw new cli_exception(get_string('clierroroutagechanged', 'auth_outage'), cli_exception::ERROR_OUTAGE_CHANGED);
        }
        // Outage cannot have already ended.
        if ($outage->has_ended($this->time)) {
            throw new cli_exception(get_string('clierroroutageended', 'auth_outage'), cli_exception::ERROR_OUTAGE_INVALID);
        }
        // If outage has started, do not wait.
        if ($outage->is_ongoing($this->time)) {
            printf("%s\n", get_string('cliwaitforitoutagestarted', 'auth_outage'));
            return 0;
        }
        // Outage nas not started yet.
        $countdown = $outage->starttime - $this->time;
        printf("%s\n", get_string(
            'cliwaitforitoutagestartingin',
            'auth_outage',
            ['countdown' => format_time($countdown)]
        ));
        return min($countdown, $this->options['sleep']);
    }
}

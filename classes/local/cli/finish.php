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
 * finish class.
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
 * finish class.
 *
 * @package    auth_outage
 * @author     Daniel Thee Roperto <daniel.roperto@catalyst-au.net>
 * @copyright  2016 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class finish extends clibase {
    /**
     * Generates all options (parameters) available for the CLI command.
     * @return mixed[] Options.
     */
    public function generate_options() {
        // Do not provide some defaults, if cloning an outage we need to know which parameters were provided.
        $options = [
            'help'     => false,
            'outageid' => null,
            'active'   => false,
        ];
        return $options;
    }

    /**
     * Generate all short forms for the available options.
     * @return string[] Short form options.
     */
    public function generate_shortcuts() {
        return [
            'h'  => 'help',
            'id' => 'outageid',
            'a'  => 'active',
        ];
    }

    /**
     * Executes the CLI.
     */
    public function execute() {
        // Help always overrides any other parameter.
        if ($this->options['help']) {
            $this->show_help('finish');
            return;
        }

        // Cannot run during CLI_MAINTENANCE mode.
        if (CLI_MAINTENANCE) {
            throw new cli_exception(get_string('cliinmaintenancemode', 'auth_outage'),
                cli_exception::ERROR_MAINTENANCE_MODE);
        }

        // Requires outageid or active but not both at the same time.
        $byid = !is_null($this->options['outageid']);
        $byactive = $this->options['active'];
        if ($byid == $byactive) {
            throw new cli_exception(get_string('cliwaitforiterroridxoractive', 'auth_outage'),
                cli_exception::ERROR_PARAMETER_MISSING);
        }

        $outage = $this->get_outage();
        if (!$outage->is_ongoing()) {
            throw new cli_exception(get_string('clifinishnotongoing', 'auth_outage'), cli_exception::ERROR_OUTAGE_INVALID);
        }

        outagedb::finish($outage->id, $this->time);
    }

    /**
     * Gets the outage to finish.
     * @return outage|null The outage to wait for.
     * @throws cli_exception
     */
    private function get_outage() {
        if ($this->options['active']) {
            $outage = outagedb::get_active();
        } else {
            $id = $this->options['outageid'];
            if (!is_number($id) || ($id <= 0)) {
                throw new cli_exception(get_string('clierrorinvalidvalue', 'auth_outage', ['param' => 'outageid']),
                    cli_exception::ERROR_PARAMETER_INVALID);
            }
            $outage = outagedb::get_by_id((int)$id);
        }

        if (is_null($outage)) {
            throw new cli_exception(get_string('clierroroutagenotfound', 'auth_outage'), cli_exception::ERROR_OUTAGE_NOT_FOUND);
        }

        return $outage;
    }
}

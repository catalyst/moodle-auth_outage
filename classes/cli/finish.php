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

/**
 * Outage CLI to finish an outage.
 *
 * @package    auth_outage
 * @author     Daniel Thee Roperto <daniel.roperto@catalyst-au.net>
 * @copyright  Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class finish extends clibase {
    /**
     * Generates all options (parameters) available for the CLI command.
     * @return array Options.
     */
    public function generateoptions() {
        // Do not provide some defaults, if cloning an outage we need to know which parameters were provided.
        $options = [
            'help' => false,
            'outageid' => null,
            'active' => false,
        ];
        return $options;
    }

    /**
     * Generate all short forms for the available options.
     * @return array Short form options.
     */
    public function generateshortcuts() {
        return [
            'h' => 'help',
            'id' => 'outageid',
            'a' => 'active',
        ];
    }

    /**
     * Executes the CLI.
     */
    public function execute() {
        // Help always overrides any other parameter.
        if ($this->options['help']) {
            $this->showhelp('finish');
            return;
        }

        // Requires outageid or active but not both at the same time.
        $byid = !is_null($this->options['outageid']);
        $byactive = $this->options['active'];
        if ($byid == $byactive) {
            throw new cliexception(get_string('cliwaitforiterroridxoractive', 'auth_outage'));
        }

        $outage = $this->get_outage();
        if (!$outage->is_ongoing()) {
            throw new cliexception(get_string('clifinishnotongoing', 'auth_outage'));
        }

        outagedb::finish($outage->id, $this->time);
    }

    /**
     * Gets the outage to finish.
     * @return outage|null The outage to wait for.
     * @throws cliexception
     */
    private function get_outage() {
        if ($this->options['active']) {
            $outage = outagedb::get_active();
        } else {
            $id = $this->options['outageid'];
            if (!is_number($id) || ($id <= 0)) {
                throw new cliexception(get_string('clierrorinvalidvalue', 'auth_outage', ['param' => 'outageid']));
            }
            $outage = outagedb::get_by_id((int)$id);
        }

        if (is_null($outage)) {
            throw new cliexception(get_string('clierroroutagenotfound', 'auth_outage'));
        }

        return $outage;
    }
}

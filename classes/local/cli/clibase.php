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
 * clibase class.
 *
 * @package    auth_outage
 * @author     Daniel Thee Roperto <daniel.roperto@catalyst-au.net>
 * @copyright  2016 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace auth_outage\local\cli;

use auth_outage\local\outagelib;
use coding_exception;
use core\session\manager;

/**
 * clibase class.
 *
 * @package    auth_outage
 * @author     Daniel Thee Roperto <daniel.roperto@catalyst-au.net>
 * @copyright  2016 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class clibase {
    /**
     * @var mixed[] Options passed as parameters to the CLI.
     */
    protected $options;

    /**
     * @var int The reference time to use when creating an outage.
     */
    protected $time;

    /**
     * clibase constructor.
     * @param array $options The parameters to use.
     * @throws cli_exception
     */
    public function __construct(array $options = null) {
        global $CFG;
        require_once($CFG->libdir.'/clilib.php');

        $warning = outagelib::generate_plugin_configuration_warning();
        if ($warning) {
            throw new cli_exception($warning, cli_exception::ERROR_PLUGIN_CONFIGURATION);
        }

        $this->become_admin_user();

        if (is_null($options)) {
            // Using Moodle CLI API to read the parameters.
            list($options, $unrecognized) = cli_get_params($this->generate_options(), $this->generate_shortcuts());
            if ($unrecognized) {
                $unrecognized = implode("\n  ", $unrecognized);
                throw new cli_exception(get_string('cliunknowoption', 'admin', $unrecognized),
                    cli_exception::ERROR_PARAMETER_UNKNOWN);
            }
        } else {
            // If not using Moodle CLI API to read parameters, ensure all keys exist.
            $default = $this->generate_options();
            foreach ($options as $k => $v) {
                if (!array_key_exists($k, $default)) {
                    throw new cli_exception(get_string('cliunknowoption', 'admin', $k), cli_exception::ERROR_PARAMETER_UNKNOWN);
                }
                $default[$k] = $v;
            }
            $options = $default;
        }

        $this->options = $options;
        $this->time = time();
    }

    /**
     * Sets the reference time for creating outages.
     * @param int $time Timestamp for the reference time.
     * @throws coding_exception
     */
    public function set_referencetime($time) {
        if (!is_int($time) || ($time <= 0)) {
            throw new coding_exception('$time must be a positive int.', $time);
        }
        $this->time = $time;
    }

    /**
     * Generates all options (parameters) available for the CLI command.
     * @return mixed[] Options.
     */
    abstract public function generate_options();

    /**
     * Generate all short forms for the available options.
     * @return string[] Short form options.
     */
    abstract public function generate_shortcuts();

    /**
     * Executes the CLI script.
     */
    abstract public function execute();

    /**
     * Change session to admin user.
     */
    protected function become_admin_user() {
        global $DB;
        $user = get_admin();
        unset($user->description);
        unset($user->access);
        unset($user->preference);
        manager::init_empty_session();
        manager::set_user($user);
    }

    /**
     * Outputs a help message.
     * @param string $cliname Name of CLI used in the language file.
     */
    protected function show_help($cliname) {
        $options = $this->generate_options();
        $shorts = array_flip($this->generate_shortcuts());

        printf("%s\n\n", get_string('cli'.$cliname.'help', 'auth_outage'));
        foreach (array_keys($options) as $long) {
            $text = get_string('cli'.$cliname.'param'.$long, 'auth_outage');
            $short = isset($shorts[$long]) ? ('-'.$shorts[$long].',') : '';
            $long = '--'.$long;
            printf("  %-4s %-20s %s\n", $short, $long, $text);
        }
        printf("\n%s\n\n", get_string('cli'.$cliname.'examples', 'auth_outage'));
    }
}

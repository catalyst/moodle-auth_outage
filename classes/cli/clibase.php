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

use core\session\manager;
use InvalidArgumentException;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/clilib.php');

/**
 * Outage CLI base class.
 *
 * @package    auth_outage
 * @author     Daniel Thee Roperto <daniel.roperto@catalyst-au.net>
 * @copyright  Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class clibase {
    /**
     * @var array Options passed as parameters to the CLI.
     */
    protected $options;

    /**
     * @var int The reference time to use when creating an outage.
     */
    protected $time;

    /**
     * clibase constructor.
     * @param array|null $options The parameters to use or null to read from the command line.
     * @throws cliexception
     */
    public function __construct(array $options = null) {
        $this->becomeadmin();

        if (is_null($options)) {
            // Using Moodle CLI API to read the parameters.
            list($options, $unrecognized) = cli_get_params($this->generateoptions(), $this->generateshortcuts());
            if ($unrecognized) {
                $unrecognized = implode("\n  ", $unrecognized);
                throw new cliexception(get_string('cliunknowoption', 'admin', $unrecognized));
            }
        } else {
            // If not using Moodle CLI API to read parameters, ensure all keys exist.
            $default = $this->generateoptions();
            foreach ($options as $k => $v) {
                if (!array_key_exists($k, $default)) {
                    throw new cliexception(get_string('cliunknowoption', 'admin', $k));
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
     */
    public function set_referencetime($time) {
        if (!is_int($time) || ($time <= 0)) {
            throw new InvalidArgumentException('$time must be a positive int.');
        }
        $this->time = $time;
    }

    /**
     * Generates all options (parameters) available for the CLI command.
     * @return array Options.
     */
    public abstract function generateoptions();

    /**
     * Generate all short forms for the available options.
     * @return array Short form options.
     */
    public abstract function generateshortcuts();

    /**
     * Executes the CLI script.
     */
    public abstract function execute();

    /**
     * Change session to admin user.
     */
    protected function becomeadmin() {
        global $DB;
        $user = $DB->get_record('user', array('id' => 2));
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
    protected function showhelp($cliname) {
        $options = $this->generateoptions();
        $shorts = array_flip($this->generateshortcuts());

        printf("%s\n\n", get_string('cli' . $cliname . 'help', 'auth_outage'));
        foreach (array_keys($options) as $long) {
            $text = get_string('cli' . $cliname . 'param' . $long, 'auth_outage');
            $short = isset($shorts[$long]) ? ('-' . $shorts[$long] . ',') : '';
            $long = '--' . $long;
            printf("  %-4s %-20s %s\n", $short, $long, $text);
        }
    }
}

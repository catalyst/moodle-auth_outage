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
 * Steps definitions related to auth_outage.
 *
 * @package   auth_outage
 * @author    Daniel Thee Roperto <daniel.roperto@catalyst-au.net>
 * @copyright 2016 Catalyst IT
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// NOTE: no MOODLE_INTERNAL test here, this file may be required by behat before including /config.php.

use Behat\Behat\Tester\Exception\PendingException;
use Behat\Gherkin\Node\TableNode;
use Behat\Mink\Exception\ExpectationException;

require_once(__DIR__.'/../../../../lib/behat/behat_base.php');

class behat_auth_outage extends behat_base {
    /**
     * @Given the authentication plugin :name is enabled
     */
    public function the_authentication_plugin_is_enabled($name) {
        set_config('auth', $name);
        \core\session\manager::gc(); // Remove stale sessions.
        core_plugin_manager::reset_caches();
    }

    /**
     * @Given /^I am on Outage Management Page$/
     */
    public function i_am_on_outage_management_page() {
        $this->getSession()->visit($this->locate_path('/auth/outage/manage.php'));
    }

    /**
     * @Given /^I am an administrator$/
     */
    public function i_am_an_administrator() {
        // Visit login page.
        $this->getSession()->visit($this->locate_path('login/index.php'));

        // Enter username and password.
        $this->execute('behat_forms::i_set_the_field_to', array('Username', $this->escape('admin')));
        $this->execute('behat_forms::i_set_the_field_to', array('Password', $this->escape('admin')));

        // Press log in button, no need to check for exceptions as it will checked after this step execution.
        $this->execute('behat_forms::press_button', get_string('login'));
    }

    /**
     * @Given /^I visit the Create Outage Page$/
     */
    public function i_visit_the_create_outage_page() {
        $this->getSession()->visit($this->locate_path('/auth/outage/new.php'));
    }
}

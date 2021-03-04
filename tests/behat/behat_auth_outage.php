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
 * @package     auth_outage
 * @author      Daniel Thee Roperto <daniel.roperto@catalyst-au.net>
 * @copyright   2016 Catalyst IT
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// NOTE: no MOODLE_INTERNAL test here, this file may be required by behat before including /config.php.

use auth_outage\dml\outagedb;
use auth_outage\local\outage;
use Behat\Gherkin\Node\TableNode;
use Behat\Mink\Exception\ExpectationException;

require_once(__DIR__.'/../../../../lib/behat/behat_base.php');

/**
 * Steps definitions related to auth_outage.
 *
 * @package     auth_outage
 * @author      Daniel Thee Roperto <daniel.roperto@catalyst-au.net>
 * @copyright   2016 Catalyst IT
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class behat_auth_outage extends behat_base {
    /**
     * @var outage Which outage are we checking.
     */
    private $outage = null;

    /**
     * Checks if a authentication plugin is enabled
     * @Given /^the authentication plugin "([^"]*)" is enabled$/
     * @param string $name Name of authentication plugin to check.
     */
    public function the_authentication_plugin_is_enabled($name) {
        set_config('auth', $name);
        \core\session\manager::gc(); // Remove stale sessions.
        core_plugin_manager::reset_caches();
    }

    /**
     * Opens the Outage Management page.
     * @Given /^I am on Outage Management Page$/
     */
    public function i_am_on_outage_management_page() {
        $this->getSession()->visit($this->locate_path('/auth/outage/manage.php'));
    }

    /**
     * Logs into the system.
     * @Given /^I am an administrator$/
     */
    public function i_am_an_administrator() {
        $this->execute('behat_auth::i_log_in_as', ['admin']);
    }

    /**
     * Opens the create outage page.
     * @Given /^I visit the Create Outage Page$/
     */
    public function i_visit_the_create_outage_page() {
        $this->getSession()->visit($this->locate_path('/auth/outage/edit.php'));
    }

    /**
     * Creates an outage of the given type.
     * @Given /^there is a "([^"]*)" outage$/
     * @param string $type Type (stage) of outage to create.
     */
    public function there_is_a_outage($type) {
        $data = [
            'autostart' => false,
            'finished' => null,
            'title' => 'Example of '.$type.' outage',
            'description' => 'An outage: '.$type,
        ];
        switch ($type) {
            case 'waiting':
                $data['starttime'] = time() + (60 * 60 * 24 * 7); // Starts in 1 week.
                $data['warntime'] = $data['starttime'] - 60;
                $data['stoptime'] = $data['starttime'] + 120;
                break;
            case 'warning':
                $data['starttime'] = time() + (60 * 60); // Starts in 1 hour.
                $data['warntime'] = $data['starttime'] - (60 * 60 * 2); // Warns before 2 hours.
                $data['stoptime'] = $data['starttime'] + (60 * 60 * 24 * 7); // Ends after 1 week.
                break;
            case 'ongoing':
                $data['starttime'] = time() - (60 * 60); // Started 1 hour ago.
                $data['warntime'] = $data['starttime'] - 60;
                $data['stoptime'] = $data['starttime'] + (60 * 60 * 24 * 7); // Ends after 1 week.
                break;
            case 'finished':
                $data['starttime'] = time() - (60 * 60); // Started 1 hour ago.
                $data['warntime'] = $data['starttime'] - 60;
                $data['finished'] = time() - 60; // Finished 1 minute ago.
                $data['stoptime'] = $data['starttime'] + (60 * 60 * 24 * 7); // Ends after 1 week.
                break;
            case 'stopped':
                $data['starttime'] = time() - (60 * 60 * 2); // Started 2 hour ago.
                $data['warntime'] = $data['starttime'] - 60;
                $data['stoptime'] = time() - (60 * 60 * 2); // Stopped 1 hour ago.
                break;
            default:
                throw new InvalidArgumentException('$type='.$type.' is not valid.');
        }
        outagedb::save(new outage($data));
    }

    /**
     * Check if there is an action button with the given name.
     * @Then /^I should see the action "([^"]*)"$/
     * @param string $action Action to check.
     * @throws ExpectationException
     */
    public function i_should_see_the_action($action) {
        $expected = ($action == 'Edit') ? 2 : 1; // Edit is an action through the title or button.
        $found = $this->how_many_times_can_i_see_action($action);
        if ($found != $expected) {
            throw new ExpectationException('"'.$action.'" action not found, expected '.$expected.
                                           ' but found '.$found.'.', $this->getSession());
        }
    }

    /**
     * Check if an specific action is not visible.
     * @Then /^I should not see the action "([^"]*)"$/
     * @param string $action Action to check.
     * @throws ExpectationException
     */
    public function i_should_not_see_the_action($action) {
        if ($this->how_many_times_can_i_see_action($action) != 0) {
            throw new ExpectationException('"'.$action.'" action was found', $this->getSession());
        }
    }

    /**
     * Check if an specific action is not visible.
     * @Then /^I should see an empty settings text area "([^"]*)"$/
     * @param string $name
     */
    public function i_should_see_an_empty_settings_text_area($name) {
        $this->assertSession()->fieldValueEquals('s_auth_outage_'.$name, '');
    }

    /**
     * Check if an specific action is not visible.
     * @When /^I go to the "Outage Settings" page$/
     */
    public function i_go_to_the_outage_settings_page() {
        $this->getSession()->visit($this->locate_path('/admin/settings.php?section=authsettingoutage'));
    }

    /**
     * Counts how many times an specific action is visible.
     * @param string $action Action to check.
     * @return int Number of times it is shown.
     */
    private function how_many_times_can_i_see_action($action) {
        $selector = 'css';
        $locator = "div[role='main'] a[title='".$action."']";
        $items = $this->getSession()->getPage()->findAll($selector, $locator);
        return count($items);
    }

    /**
     * Click the given action button.
     * @Then /^I click on the "([^"]*)" action button$/
     * @param string $action Action button to click.
     */
    public function i_click_on_the_action_button($action) {
        $node = $this->get_selected_node('css_element', "div[role='main'] table nobr a[title='".$action."']");
        $this->ensure_node_is_visible($node);
        $node->click();
    }

    /**
     * Check if a new window was opened.
     * @Given I should be in a new window
     * @throws ExpectationException
     */
    public function i_should_be_in_a_new_window() {
        if (! $this->is_behat_3()) {
            return;
        }

        $count = count($this->getSession()->getWindowNames());
        if ($count != 2) {
            throw new ExpectationException('Number of windows: '.$count, $this->getSession());
        }
    }

    /**
     * Checks if the warning bar is visible.
     * @Then /^I should see "([^"]*)" in the warning bar$/
     * @param string $text Text that should be in the warning bar.
     * @throws ExpectationException
     */
    public function i_should_see_in_the_warning_bar($text) {
        $element = '#auth_outage_warningbar_box';

        $container = $this->getSession()->getPage()->findAll('css', $element);
        if (count($container) == 0) {
            throw new ExpectationException('"'.$element.'" element not found', $this->getSession());
        }
        $container = $container[0];

        $xpathliteral = $this->getSession()->getSelectorsHandler()->xpathLiteral($text);
        $xpath = "/descendant-or-self::*[contains(., $xpathliteral)]".
                 "[count(descendant::*[contains(., $xpathliteral)]) = 0]";

        $found = $this->find_all('xpath', $xpath, false, $container);
        if (count($found) == 0) {
            throw new ExpectationException('"'.$text.'" text was not found in the "'.$element.'" element', $this->getSession());
        }

        foreach ($found as $node) {
            if ($node->isVisible()) {
                return;
            }
        }
        throw new ExpectationException('"'.$text.'" text was found in the "'.$element.'" element but was not visible',
            $this->getSession());
    }

    /**
     * Checks if the warning bar is not visible.
     * @Then I should not see the warning bar
     * @throws ExpectationException
     */
    public function i_should_not_see_the_warning_bar() {
        $selector = 'css';
        $locator = "#auth_outage_warningbar_box";
        $items = $this->getSession()->getPage()->findAll($selector, $locator);
        if (count($items) > 0) {
            throw new ExpectationException($locator.' found, not expected.', $this->getSession());
        }
    }

    /**
     * Creates an outage for the given data.
     * @Given there is the following outage:
     * @param TableNode $data Outage data.
     * @throws Exception
     */
    public function there_is_the_following_outage(TableNode $data) {
        $time = time();
        $row = $data->getHash()[0];

        // Set defaults.
        $row = array_merge(
            [
                'autostart' => 'no',
                'warnbefore' => 60,
                'startsin' => 0,
                'stopsafter' => 60,
                'finished' => null,
                'title' => 'Outage Title',
                'description' => 'Outage Description.',
            ],
            $row
        );
        if (($row['autostart'] != 'yes') && ($row['autostart'] != 'no')) {
            throw new Exception('autostart must be yes or no, found: '.$row['autostart']);
        }
        if ($row['finished'] == '') {
            $row['finished'] = null;
        }

        $starttime = $time + $row['startsin'];
        $this->outage = new outage([
            'autostart' => ($row['autostart'] == 'yes'),
            'warntime' => $starttime - $row['warnbefore'],
            'starttime' => $starttime,
            'stoptime' => $starttime + $row['stopsafter'],
            'finished' => $row['finished'],
            'title' => $row['title'],
            'description' => $row['description'],
        ]);
        outagedb::save($this->outage);
    }

    /**
     * Waits (sleep) until outage reaches a stage.
     * @When /^I wait until the outage (?P<what>warns|starts|stops)$/
     * @param string $what What to wait for.
     * @throws Exception
     */
    public function i_wait_until_outage($what) {
        switch ($what) {
            case 'warns':
                $seconds = $this->outage->warntime - time();
                break;
            case 'starts':
                $seconds = $this->outage->starttime - time();
                break;
            case 'stops':
                $seconds = $this->outage->stoptime - time();
                $seconds += 5; // Give it some extra time to pool the server.
                break;
            default:
                throw new Exception('Invalid $what='.$what);
        }
        if ($seconds >= 0) {
            $seconds++; // Give one extra second for things to happen.
            $this->getSession()->wait($seconds * 1000);
        }
    }

    /**
     * Checks if behat3+ is running.
     * @return bool If behat is 3+
     */
    private function is_behat_3() {
        global $version;
        list($behat) = explode('.', $version);
        return ($behat >= 3);
    }
}

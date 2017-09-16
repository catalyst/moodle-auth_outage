@dev @auth @auth_outage @javascript
Feature: IP Blocker
  In order to allow admins to access the system during an outage
  As an admin
  I need to be able to login into Moodle

  Terminology:
  - An ongoing outage does not block Moodle execution, although it can trigger maintenance mode.
  - Maintenance mode completely blocks Moodle and can only be deactivated using the CLI.


  Background:
    Given the authentication plugin "outage" is enabled


  Scenario: Default IP Whitelist Settings
    Given I am an administrator
    And I am on homepage
    When I navigate to "Settings" node in "Site administration > Plugins > Authentication > Outage manager"
    Then I should see "Allowed IP list"
    And I should see an empty settings text area "allowedips"


  Scenario: Ensure we are not redirected to another login page during an outage if IP blocker is on
    Given I know my IP address
    And the IP whitelist is set to my current IP
    And there is a "ongoing" outage
    And the alternate login URL is set to the fake auth outage page
    When I go to the login page
    Then I should see "Acceptance test site"
    Then I should see "Log in"


  Scenario: Redirect to SAML or another authentication during an outage if IP blocker is off
    Given there is a "ongoing" outage
    And the IP whitelist is empty
    And the alternate login URL is set to the fake auth outage page
    When I go to the login page
    Then I should see "Welcome to the fake outage auth page!"

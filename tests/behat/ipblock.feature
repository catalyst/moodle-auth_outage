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
    When I navigate to "Plugins > Authentication > Outage manager > Settings" in site administration
    Then I should see "Allowed IP list"
    And I should see an empty settings text area "allowedips"

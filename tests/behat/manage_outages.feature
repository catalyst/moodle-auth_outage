@auth @auth_outage @javascript
Feature: Test the outage management functionality.
  In order to check if I can manage outages
  As an admin
  I need to create, edit, delete, clone and finish an outage.

  Background: Always login as admin, enable the auth_outage plugin and go to the outage management page.
    Given The authentication plugin "outage" is enabled
    And I log in as "admin"

  Scenario: Check if I can navigate to management page.
    Given I am on homepage
    When I navigate to "Manage" node in "Site administration > Plugins > Authentication > Outage manager"
    Then I should see "Create Outage"
    Then I should see "Planned outages"
    Then I should see "Outage history"

  Scenario: Check if creating outages uses the configured defaults.
    Given I am on Outage Management Page
    When I click on "([^"]|\"*)" "<string>"

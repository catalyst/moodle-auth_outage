@auth @auth_outage @javascript
Feature: Test outage plugin works as expected.
  In order to check if this plugin works
  As an admin
  I need to go through all configuration to ensure they can be changed and are consistent.

  Background:
    Given I log in as "admin"

#  Scenario: Check if I can navigate to the default settings.
#    Given I navigate to "Defaults" node in "Site administration > Plugins > Authentication > Outage"
#    Then I should see "Outage Defaults"

  Scenario: Check if I can add a new outage.
    Given I navigate to "Manage" node in "Site administration > Plugins > Authentication > Outage"
    And I should see "Outages List"
    When I click on "Create Outage" "link"
    And I set the following fields to these values:
      | starttime[day]            | 2                      |
      | starttime[month]          | January                |
      | starttime[year]           | 2016                   |
      | starttime[hour]           | 22                     |
      | starttime[minute]         | 00                     |
      | stoptime[day]             | 2                      |
      | stoptime[month]           | January                |
      | stoptime[year]            | 2016                   |
      | stoptime[hour]            | 23                     |
      | stoptime[minute]          | 30                     |
      | warningduration[number]   | 90                     |
      | warningduration[timeunit] | minutes                |
      | title                     | My New Outage          |
      | description[text]         | This is a test outage. |
    And I press "Save changes"
    Then I should see "My New Outage"
    And I should see "This is a test outage."

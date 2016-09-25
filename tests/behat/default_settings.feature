@dev @auth @auth_outage @javascript
Feature: Test changing the default settings.
  In order to easily create outages
  As an admin
  I need to configure the outage defaults

  Rules:
  - Times should be expressed in minutes.

  Reminder:
  - If one setting is not valid, but another setting is valid and modified, Moodle will display 'Settings Saved'.

  Background:
    Given the authentication plugin "outage" is enabled
    And I am an administrator


  Scenario Outline: Check if I can save the default settings.
    When I navigate to "Default Settings" node in "Site administration > Plugins > Authentication > Outage manager"
    And I set the following fields to these values:
      | s_auth_outage_default_autostart        | <autostart>   |
      | s_auth_outage_default_warning_duration | <warning>     |
      | s_auth_outage_default_duration         | <duration>    |
      | s_auth_outage_default_title            | <title>       |
      | s_auth_outage_default_description      | <description> |
      | s_auth_outage_css                      | <css>         |
    And I wait "600" seconds
    And I press "Save changes"
    Then I should see "Changes saved"
    When I visit the Create Outage Page
    Then the following fields match these values:
      | autostart                 | <autostart>   |
      | warningduration[number]   | <warning>     |
      | warningduration[timeunit] | 60            |
      | outageduration[number]    | <duration>    |
      | outageduration[timeunit]  | 60            |
      | title                     | <title>       |
      | description[text]         | <description> |

    Examples:
      | autostart | warning | duration | title                   | description                 | css             |
#      | 1         | 15      | 30       | An Outage               | My outage until {stop}.     | /* Some CSS. */ |
      | 0         | 30      | 45       | My Behat Outage {start} | My outage with <b>HTML</b>. | /* More CSS. */ |


##  Scenario Outline: Check if I can save invalid values for default settings.
##    When I navigate to "Default Settings" node in "Site administration > Plugins > Authentication > Outage manager"
##    And I set the following fields to these values:
##      | s_auth_outage_default_autostart        | 1               |
##      | s_auth_outage_default_warning_duration | <warning>       |
##      | s_auth_outage_default_duration         | <duration>      |
##      | s_auth_outage_default_title            | <title>         |
##      | s_auth_outage_default_description      | <description>   |
##      | s_auth_outage_css                      | /* Some CSS. */ |
##    And I press "Save changes"
##    Then I should <seeornot> "Changes saved"
##
##    Examples:
##      | warning | duration | title    | description    | seeornot |
##      | 15      | 30       | My Title | My Description | see      |
##      | -1      | 30       | My Title | My Description | not see  |
##      | 15      | -1       | My Title | My Description | not see  |
##      | 15      | 30       |          | My Description | not see  |
##      | 15      | 30       | My Title |                | not see  |
#

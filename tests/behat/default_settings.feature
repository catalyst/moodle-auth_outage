@auth @auth_outage @javascript
Feature: Change the default settings
  In order to easily create outages
  As an admin
  I need to configure the outage defaults

  Reminder:
  - Event if one setting is not valid Moodle will display 'Changes Saved' if another setting was saved.

  Background:
    Given the authentication plugin "outage" is enabled
    And I am an administrator

  Scenario Outline: Check if I can save the default settings.
    When I navigate to "Plugins > Authentication > Outage manager > Settings" in site administration
    And I set the following fields to these values:
      | s_auth_outage_default_autostart           | <autostart>   |
      | s_auth_outage_default_warning_duration[v] | <warning>     |
      | s_auth_outage_default_warning_duration[u] | 60            |
      | s_auth_outage_default_duration[v]         | <duration>    |
      | s_auth_outage_default_duration[u]         | 60            |
      | s_auth_outage_default_title               | <title>       |
      | s_auth_outage_default_description         | <description> |
      | s_auth_outage_css                         | <css>         |
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
      | 1         | 15      | 30       | An Outage               | My outage until {stop}.     | /* Some CSS. */ |
      | 0         | 30      | 45       | My Behat Outage {start} | My outage with <b>HTML</b>. | /* More CSS. */ |

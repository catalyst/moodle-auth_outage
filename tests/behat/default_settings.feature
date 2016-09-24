@dev @auth @auth_outage @javascript
Feature: Test changing the default settings.
  In order to easily create outages
  As an admin
  I need to configure the outage defaults

  Rules:
  - Times should be expressed in minutes.

  Background:
    Given the authentication plugin "outage" is enabled
    And I am an administrator

  Scenario: Check if I can save the default settings.
    When I navigate to "Default Settings" node in "Site administration > Plugins > Authentication > Outage manager"
    And I set the following fields to these values:
      | s_auth_outage_default_autostart        | 1               |
      | s_auth_outage_default_warning_duration | 15              |
      | s_auth_outage_default_duration         | 30              |
#      | s_auth_outage_default_Title            | My Behat Outage {start}  |
#      | s_auth_outage_default_description      | My outage <b>{stop}</b>. |
      | s_auth_outage_css                      | /* Some CSS. */ |
    And I press "Save changes"
    Then I should see "Changes saved"
    When I visit the Create Outage Page
    Then the following fields match these values:
#      | autostart                 | 1                        |
      | warningduration[number]   | 15 |
      | warningduration[timeunit] | 60 |
      | outageduration[number]    | 30 |
      | outageduration[timeunit]  | 60 |
#      | title                     | My Behat Outage {start}  |
#      | description[text]         | My outage <b>{stop}</b>. |

  Scenario Outline: Check if I can save invalid values for default settings.
    When I navigate to "Default Settings" node in "Site administration > Plugins > Authentication > Outage manager"
    And I set the following fields to these values:
      | s_auth_outage_default_autostart        | 1               |
      | s_auth_outage_default_warning_duration | <warning>       |
      | s_auth_outage_default_duration         | <duration>      |
#      | s_auth_outage_default_Title            | <title>  |
#      | s_auth_outage_default_description      | <description> |
      | s_auth_outage_css                      | /* Some CSS. */ |
    And I press "Save changes"
    Then I should <seeornot> "Changes saved"

    Examples:
      | warning | duration | title    | description    | seeornot |
      | 15      | 30       | My Title | My Description | see      |
      | -1      | 30       | My Title | My Description | not see  |
      | 15      | -1       | My Title | My Description | not see  |
      | 15      | 30       |          | My Description | not see  |
      | 15      | 30       | My Title |                | not see  |


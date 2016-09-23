@auth @auth_outage @javascript
Feature: Test changing the default settings.
  In order to check if I can set the default settings
  As an admin
  I need to go set the default values and check if they are using for new outages

  Background: Always login as admin and enable the auth_outage plugin.
    Given The authentication plugin "outage" is enabled
    And I log in as "admin"

  Scenario: Check if I can navigate to the default settings.
    Given I navigate to "Default Settings" node in "Site administration > Plugins > Authentication > Outage manager"
    When I set the following fields to these values:
      | s_auth_outage_default_autostart           | bool                       |
      | s_auth_outage_default_duration            | 123                        |
      | s_auth_outage_default_warning_duration    | 456                        |
      | s_auth_outage_default_warning_title       | My Behat Outage {{start}}  |
      | s_auth_outage_default_warning_description | My outage <b>{{stop}}</b>. |
      | s_auth_outage_css                         | /* Some CSS. */            |
    And I press "Save changes"
    And I should see "Changes Saved"
#    And I wait "600" seconds
#    And I should see "This is a test outage."

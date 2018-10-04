@auth @auth_outage @javascript
Feature: Manage outages
  In order to manage outages
  As an admin
  I need to view, create, edit, delete, clone and finish an outage

  Outage stage terminology:
  - waiting is an outage in the future, not yet in the warning period.
  - warning is an outage in the future but already in the warning period.
  - ongoing is an outage that has started, but not yet reached the stop time nor is marked as finished.
  - finished is an outage that has explicitly been marked as finished.
  - stopped is an outage that has already ended but not explicitly marked as finished.

  Background: Always login as admin, enable the auth_outage plugin and go to the outage management page.
    Given the authentication plugin "outage" is enabled
    And I log in as "admin"
    And I wait "1" seconds

  Scenario: Check if I can navigate to management page.
    Given I am on homepage
    When I navigate to "Plugins > Authentication > Outage manager > Manage outages" in site administration
    Then I should see "Planned outages"
    And I should see "No outages found." in the "#section_planned_outages" "css_element"
    And I should see "Outage history"
    And I should see "No outages found." in the "#section_outage_history" "css_element"

  Scenario Outline: Planned outages should include all outages not finished or stopped.
    Given there is a "<type>" outage
    When I am on Outage Management Page
    Then I should see "Example of <type> outage" in the "#section_<section>" "css_element"

    Examples:
      | type     | section         |
      | waiting  | planned_outages |
      | warning  | planned_outages |
      | ongoing  | planned_outages |
      | finished | outage_history  |
      | stopped  | outage_history  |

  Scenario Outline: Planned and history outages have different actions.
    Given there is a "<type>" outage
    When I am on Outage Management Page
    Then I should see "Example of <type> outage"
    And I should <view> the action "View"
    And I should <clone> the action "Clone"
    And I should <edit> the action "Edit"
    And I should <delete> the action "Delete"
    And I should <finish> the action "Finish"

    Examples:
      | type     | view | clone | edit    | delete  | finish  |
      | waiting  | see  | see   | see     | see     | not see |
      | warning  | see  | see   | see     | see     | not see |
      | ongoing  | see  | see   | see     | see     | see     |
      | finished | see  | see   | not see | not see | not see |
      | stopped  | see  | see   | not see | not see | not see |

  Scenario: Create an outage using defaults.
    Given I am on Outage Management Page
    When I press "Create outage"
    And I press "Save changes"
    And I should not see "No outages found." in the "#section_planned_outages" "css_element"
    And I should see "No outages found." in the "#section_outage_history" "css_element"

  Scenario: View an outage which should open in a new window or tab.
    Given there is a "waiting" outage
    And I am on Outage Management Page
    When I click on the "View" action button
    Then I should be in a new window
    And I should see "Example of waiting outage"

  Scenario: Clone an outage.
    Given there is a "waiting" outage
    And I am on Outage Management Page
    When I click on the "Clone" action button
    Then I should see "Clone outage"
    And I set the field "title" to "My cloned outage"
    And I press "Save changes"
    Then I should see "Example of waiting outage"
    And I should see "My cloned outage"

  Scenario: Edit an outage.
    Given there is a "warning" outage
    And I am on Outage Management Page
    And I should see "Example of warning outage"
    When I click on the "Edit" action button
    Then I should see "Edit outage"
    And I set the field "title" to "My previous warning outage"
    And I press "Save changes"
    Then I should not see "Example of warning outage"
    And I should see "My previous warning outage"

  Scenario: Delete an outage
    Given there is a "warning" outage
    And I am on Outage Management Page
    And I should see "Example of warning outage"
    When I click on the "Delete" action button
    Then I should see "Delete outage"
    And I should see "Example of warning outage"
    Then I press "Delete"
    And I should not see "Example of warning outage"

  Scenario: Finish an outage
    Given there is a "ongoing" outage
    And I am on Outage Management Page
    And I should see "Example of ongoing outage" in the "#section_planned_outages" "css_element"
    When I click on the "Finish" action button
    Then I should see "Finish outage"
    And I should see "Example of ongoing outage"
    Then I click on "input[value='Finish']" "css_element"
    And I should not see "Example of ongoing outage" in the "#section_planned_outages" "css_element"
    But I should see "Example of ongoing outage" in the "#section_outage_history" "css_element"

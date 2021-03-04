@auth @auth_outage @javascript @_file_upload @_switch_iframe
Feature: Disable warning bar in embedded layout
  In order alert users about an outage
  As any user
  I need to view the warning bar, but not in embedded layout

  Background:
    Given the authentication plugin "outage" is enabled
    And the following "courses" exist:
      | fullname | shortname |
      | Course 1 | C1 |
    And the following "activities" exist:
      | activity | name       | intro      | introformat | course | content  | contentformat | idnumber |
      | page     | PageName1  | PageDesc1  | 1           | C1     | H5Ptest  | 1             | 1        |
    And the "displayh5p" filter is "on"

  Scenario: Disable warning bar in embedded h5p in book activity
    Given I log in as "admin"
    And I am on "Course 1" course homepage with editing mode on
    And I add a "File" to section "1"
    And I set the following fields to these values:
      | Name | ipsumFile |
    And I upload "h5p/tests/fixtures/ipsums.h5p" file to "Select files" filemanager
    And I press "Save and return to course"
    And I follow "PageName1"
    And I navigate to "Edit settings" in current page administration
    And I click on "Insert H5P" "button" in the "#fitem_id_page" "css_element"
    And I click on "Browse repositories..." "button" in the "Insert H5P" "dialogue"
    And I click on "Server files" "link" in the ".fp-repo-area" "css_element"
    And I click on "ipsumFile (File)" "link"
    And I click on "ipsums.h5p" "link"
    And I click on "Select this file" "button"
    And I click on "Insert H5P" "button" in the "Insert H5P" "dialogue"
    And I wait until the page is ready
    And I click on "Save and display" "button"
    When there is the following outage:
      | warnbefore | startsin |
      | 0          | 0        |
    And I reload the page
    Then I should see "Back online at" in the warning bar
    And I should not see "Lorum ipsum"
#   Switch to iframe created by filter
    And I switch to "h5p-iframe" class iframe
    And I should not see the warning bar
#   Switch to iframe created by embed.php page
    And I switch to "h5p-iframe" class iframe
    And I should see "Lorum ipsum"
    And I should not see the warning bar

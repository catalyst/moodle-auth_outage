@auth @auth_outage @javascript
Feature: Warning bar
  In order alert users about an outage
  As any user
  I need to view the warning bar

  Outage stage terminology:
  - waiting is an outage in the future, not yet in the warning period.
  - warning is an outage in the future but already in the warning period.
  - ongoing is an outage that has started, but not yet reached the stop time nor is marked as finished.
  - finished is an outage that has explicitly been marked as finished.
  - stopped is an outage that has already ended but not explicitly marked as finished.

  Background:
    Given the authentication plugin "outage" is enabled

  Scenario: This is how an outage should happen without maintenance mode and manual finish.
    Given there is the following outage:
      | warnbefore | startsin | stopsafter |
      | 10         | 20       | 10         |
    When I am on homepage
    Then I should not see the warning bar
    When I wait until the outage warns
    And I reload the page
    Then I should see "Shutting down in" in the warning bar
    When I wait until the outage starts
    And I reload the page
    Then I should see "Back online at" in the warning bar
    When I wait until the outage stops
    Then I should see "We are back online!" in the warning bar
    When I reload the page
    Then I should not see the warning bar
#
#
#  Scenario Outline: Some stages should show its own warning message.
#    Given there is a "<type>" outage
#    When I am on homepage
#    Then I should see "<see>" in the warning bar
#
#    Examples:
#      | type    | see              |
#      | warning | Shutting down in |
#      | ongoing | Back online at   |
#
#
#  Scenario Outline: Some stages should not have a warning bar.
#    Given there is a "<type>" outage
#    When I am on homepage
#    Then I should not see the warning bar
#
#    Examples:
#      | type     |
#      | waiting  |
#      | finished |
#      | stopped  |

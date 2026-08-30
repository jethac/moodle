@core @core_admin @core_admin_roles
Feature: Filter the capability tables on the roles pages
  In order to find a capability quickly
  As an admin
  I need to filter the capability table without the filter leaking onto another page

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email           |
      | user1    | User      | One      | one@example.com |
    And the following "courses" exist:
      | shortname | fullname |
      | C1        | Course 1 |
    And the following "course enrolments" exist:
      | user  | course | role    |
      | user1 | C1     | student |

  @javascript
  Scenario: The check permissions filter is not carried over to the permissions page
    Given I am on the "C1" "permissions" page logged in as "admin"
    And I set the field "Participants tertiary navigation" to "Check permissions"
    And I set the field "reportuser" to "User One (one@example.com)"
    And I press "Show this user's permissions"
    When I set the field "Filter" to "assign:addinstance"
    Then I should see "mod/assign:addinstance"
    And I should not see "mod/forum:addinstance"
    When I set the field "Participants tertiary navigation" to "Permissions"
    Then the field "Filter" matches value ""
    And I should see "mod/assign:addinstance"
    And I should see "mod/forum:addinstance"

  @javascript
  Scenario: The permissions filter is remembered when returning to the permissions page
    Given I am on the "C1" "permissions" page logged in as "admin"
    When I set the field "Filter" to "assign:addinstance"
    Then I should see "mod/assign:addinstance"
    And I should not see "mod/forum:addinstance"
    When I am on the "C1" "permissions" page
    Then the field "Filter" matches value "assign:addinstance"
    And I should see "mod/assign:addinstance"
    And I should not see "mod/forum:addinstance"

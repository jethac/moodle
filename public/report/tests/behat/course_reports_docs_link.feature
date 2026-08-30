@core @core_report
Feature: Documentation link in the course reports page
  In order to find help about the course reports
  As a teacher
  I need the documentation link of the course reports page to point to the reports documentation

  Background:
    Given the following "courses" exist:
      | fullname | shortname |
      | Course 1 | C1        |
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | Teacher   | 1        | teacher1@example.com |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
    And the following config values are set as admin:
      | docroot | https://docs.moodle.org |

  Scenario: The documentation link in the course reports page points to the reports documentation
    Given I am on the "C1" "Course" page logged in as "teacher1"
    When I navigate to "Reports" in current page administration
    Then "//footer//a[contains(@href, '/report/view')]" "xpath_element" should exist
    And "//footer//a[contains(@href, '/course/view')]" "xpath_element" should not exist

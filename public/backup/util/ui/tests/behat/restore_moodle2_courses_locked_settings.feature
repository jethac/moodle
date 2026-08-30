@core @core_backup
Feature: Restore Moodle 2 course backups with locked restore settings
  In order to enforce restore settings for everybody
  As an admin
  I need locked settings and the settings depending on them to be applied to the restored course

  Background:
    Given the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "core_badges > Badge" exists:
      | name        | Testing course badge         |
      | type        | 2                            |
      | course      | C1                           |
      | description | Testing course badge         |
      | image       | badges/tests/behat/badge.png |
    And the following config values are set as admin:
      | enableasyncbackup | 0 |
    And I log in as "admin"
    And I backup "Course 1" course using this options:
      | Confirmation | Filename | test_backup.mbz |

  @javascript
  Scenario: Restore a course with badges when the activities restore setting is locked
    Given the following config values are set as admin:
      | restore_general_activities_locked | 1 | restore |
    When I restore "test_backup.mbz" backup into a new course using this options:
    And I navigate to "Badges" in current page administration
    Then I should see "Testing course badge"

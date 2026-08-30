@report @report_security
Feature: XSS trusted users security check
  In order to audit which users may post content which is not cleaned
  As an admin
  I need the XSS trusted users check to list only the users who really hold a capability with an XSS risk

  Background:
    Given the following "users" exist:
      | username  | firstname | lastname |
      | trusted   | Trusted   | User     |
      | untrusted | Untrusted | User     |
    And the following "courses" exist:
      | fullname | shortname |
      | Course 1 | C1        |
      | Course 2 | C2        |
    And the following "roles" exist:
      | shortname | name     |
      | xssrisk   | XSS risk |
    And the following "role capabilities" exist:
      | role    | moodle/site:trustcontent |
      | xssrisk | allow                    |
    And the following "role assigns" exist:
      | user      | role    | contextlevel | reference |
      | trusted   | xssrisk | Course       | C1        |
      | untrusted | xssrisk | Course       | C2        |
    And I log in as "admin"

  Scenario: Users who hold a capability with an XSS risk are reported
    When I visit "/report/security/index.php?detail=core_riskxss"
    Then I should see "Trusted User"
    And I should see "Untrusted User"

  Scenario: Users whose capability is taken away by an override are not reported
    Given the following "permission overrides" exist:
      | capability               | permission | role    | contextlevel | reference |
      | moodle/site:trustcontent | Prevent    | xssrisk | Course       | C2        |
    When I visit "/report/security/index.php?detail=core_riskxss"
    Then I should see "Trusted User"
    And I should not see "Untrusted User"

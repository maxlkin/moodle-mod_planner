@mod @mod_planner @javascript
Feature: Test adding, editing, deleting, and searching for templates

  Scenario: Test adding template
    Given I log in as "admin"
    And I navigate to "Plugins > Activity modules > Planner > Manage templates" in site administration
    And I press "Add new template"
    And I set the field "Template name" to "Template 1"
    When I set the field "Step 1 time allocation" to "0"
    And I press "Submit"
    Then I should see "Total time allocated for all steps should equal 100"
    When I set the field "Step 2 time allocation" to "10"
    And I press "Submit"
    Then I should see "Required"
    When I set the field "Step 1 time allocation" to "5"
    And I press "Submit"
    Then I should see "Total time allocated for all steps should equal 100"
    When I set the field "Step 2 time allocation" to "5"
    And I press "Add 1 step to the form"
    And I set the field "Disclaimer" to "Test disclaimer"
    And I press "Submit"
    Then I should see "Template 1"
    And I should see "Enabled"
    When I click on "Edit" "link"
    Then I should not see "Step 7 name"

  Scenario: Test enabling/disabling template
    Given I log in as "admin"
    And I navigate to "Plugins > Activity modules > Planner > Manage templates" in site administration
    And I press "Add new template"
    And I set the field "Template name" to "Template 1"
    And I press "Submit"
    Then I should see "Enabled"
    When I click on "Disable this template" "link"
    Then I should see "Disabled"
    When I click on "Enable this template" "link"
    Then I should see "Enabled"

  Scenario: Test deleting template
    Given I log in as "admin"
    And I navigate to "Plugins > Activity modules > Planner > Manage templates" in site administration
    And I press "Add new template"
    And I set the field "Template name" to "Template 1"
    And I press "Submit"
    Then I should see "Template 1"
    When I click on "Delete" "link"
    And I press "Delete"
    Then I should not see "Template 1"

  Scenario: Test searching templates
    Given the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | Darrell   | Teacher1 | teacher1@example.com |
    And the following "courses" exist:
      | fullname | shortname | category | enablecompletion | showcompletionconditions |
      | Course 1 | C1        | 0        | 1                | 1                        |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
    And I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    And I navigate to "Manage templates" in current page administration
    And I press "Add new template"
    And I set the field "Template name" to "Template teacher1"
    And I press "Submit"
    And I log in as "admin"
    And I am on "Course 1" course homepage with editing mode on
    And I navigate to "Manage templates" in current page administration
    And I press "Add new template"
    And I set the field "Template name" to "Template 1"
    And I press "Submit"
    And I press "Add new template"
    And I set the field "Template name" to "Personal"
    And I press "Submit"
    When I set the field "Search" to "per"
    And I press "Submit"
    Then I should see "Personal"
    And I should not see "Template 1"
    When I set the field "Search" to "Template 1"
    And I press "Submit"
    Then I should see "Template 1"
    And I should not see "Personal"
    When I set the field "Search" to "Teacher1"
    And I press "Submit"
    Then I should see "Template teacher1" in the "alltemplates" "table"
    And "mytemplates" "table" should not exist

  Scenario: Test that the my templates table only displays templates created by the user
    Given the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | Darrell   | Teacher1 | teacher1@example.com |
    And the following "courses" exist:
      | fullname | shortname | category | enablecompletion | showcompletionconditions |
      | Course 1 | C1        | 0        | 1                | 1                        |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
    And I log in as "admin"
    And I navigate to "Plugins > Activity modules > Planner > Manage templates" in site administration
    And I press "Add new template"
    And I set the field "Template name" to "Template 1"
    And I press "Submit"
    And I log in as "teacher1"
    And I am on "Course 1" course homepage
    When I navigate to "Manage templates" in current page administration
    And I press "Add new template"
    And I set the field "Template name" to "Template teacher1"
    And I press "Submit"
    Then I should see "Template teacher1" in the "mytemplates" "table"
    And I should see "Template teacher1" in the "alltemplates" "table"
    And I should not see "Template 1" in the "mytemplates" "table"
    And I should see "Template 1" in the "alltemplates" "table"

  Scenario: Test that a non-admin user can only edit/delete their own templates
    Given the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | Darrell   | Teacher1 | teacher1@example.com |
    And the following "courses" exist:
      | fullname | shortname | category | enablecompletion | showcompletionconditions |
      | Course 1 | C1        | 0        | 1                | 1                        |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
    When I log in as "admin"
    And I navigate to "Plugins > Activity modules > Planner > Manage templates" in site administration
    And I press "Add new template"
    And I set the field "Template name" to "Template 1"
    And I press "Submit"
    Then "View" "link" should be visible
    And "Disable this template" "link" should be visible
    And "Edit" "link" should be visible
    And "Delete" "link" should be visible
    When I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I navigate to "Manage templates" in current page administration
    Then "View" "link" should be visible
    And "Disable this template" "link" should not be visible
    And "Edit" "link" should not be visible
    And "Delete" "link" should not be visible

  Scenario: Test the view template modal
    Given I log in as "admin"
    And I navigate to "Plugins > Activity modules > Planner > Manage templates" in site administration
    And I press "Add new template"
    And I set the field "Template name" to "Template 1"
    And I press "Submit"
    When I click on "View" "link"
    Then I should see "Template 1"
    And I should see "Step 1 name: Understanding your assignment"
    And I should see "Step 1 time allocation: 5"
    And I should see "Step 1 description:"

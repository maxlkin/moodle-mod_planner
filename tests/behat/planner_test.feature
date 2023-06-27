@mod @mod_planner @javascript
Feature: Test the main planner page

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email                |
      | student1 | Vinnie    | Student1 | student1@example.com |
      | teacher1 | Darrell   | Teacher1 | teacher1@example.com |
    And the following "courses" exist:
      | fullname | shortname | category | enablecompletion | showcompletionconditions |
      | Course 1 | C1        | 0        | 1                | 1                        |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | student1 | C1     | student        |
      | teacher1 | C1     | editingteacher |
    And the following "activity" exists:
      | activity                            | assign               |
      | course                              | C1                   |
      | section                             | 1                    |
      | name                                | Test assignment name |
      | completion                          | 1                    |
      | allowsubmissionsfromdate            | 1424908800           |
      | duedate                             | 1903909056           |
      | assignsubmission_onlinetext_enabled | 1                    |
    And I log in as "admin"
    And I navigate to "Plugins > Activity modules > Planner > Manage templates" in site administration
    And I press "Add new template"
    And I set the field "Template name" to "Template 1"
    And I press "Submit"
    And I am on "Course 1" course homepage with editing mode on
    And I add a "Planner" to section "1"
    And I set the field "Name" to "Test planner name"
    And I set the field "Description" to "Test planner description"
    And I select "Task number, title and due date" from the "Information on course page" singleselect
    And I select "Test assignment name" from the "Select activity" singleselect
    And I select "Template 1" from the "Template" singleselect
    And I set the field "Step 1 description" to "Test step 1 description"
    And I set the field "Step 2 description" to "Test step 2 description"
    And I set the field "Step 3 description" to "Test step 3 description"
    And I set the field "Step 4 description" to "Test step 4 description"
    And I set the field "Step 5 description" to "Test step 5 description"
    And I set the field "Step 6 description" to "Test step 6 description"
    And I press "Save and return to course"

  Scenario: Test the print page
    Given I am on the "Test planner name" "planner activity" page
    When I click on "Printer-friendly version" "link"
    Then I should see "Site: Acceptance test site"
    And I should see "Course: Course 1 (C1)"
    And I should see "Planner: Test planner name"
    And I should see "Planner default starting on : 26/02/15"
    And I should see "Planner default ending on : 2/05/30"
    And I should see "According to the dates you have entered, you have"
    And I should see "Step 1 - Understanding your assignment"
    And I should see "Test step 1 description"
    And I should see "Print"

  Scenario: Test the report page
    Given I am on the "Test planner name" "planner activity" page
    And I press "Calculate student steps"
    When I navigate to "Report" in current page administration
    Then I should see "Test planner name Report"
    And I should see "Vinnie Student1"
    And I should see "student1@example.com"
    And I should see "Pending"

  Scenario: Test the report page with completed activity
    Given I am on the "Test planner name" "planner activity" page
    And I press "Calculate student steps"
    And I log in as "student1"
    And I am on "Course 1" course homepage
    And I click on "Test assignment name" "link"
    And I press "Add submission"
    And I set the field "Online text" to "Test submission"
    And I press "Save changes"
    And I press "Submit assignment"
    And I press "Continue"
    When I am on the "Test planner name" "planner activity" page
    And I click on "stepid" "checkbox"
    And I press "Submit"
    And I log in as "admin"
    And I am on the "Test planner name" "planner activity" page
    And I navigate to "Report" in current page administration
    Then I should see "Test planner name Report"
    And I should see "Vinnie Student1"
    And I should see "Completed"
    And I should see "Pending"

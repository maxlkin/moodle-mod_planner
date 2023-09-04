@mod @mod_planner @javascript
Feature: Test adding, deleting, and editing planner activities

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
      | activity                 | assign               |
      | course                   | C1                   |
      | section                  | 1                    |
      | name                     | Test assignment name |
      | completion               | 1                    |
      | allowsubmissionsfromdate | 1424908800           |
      | duedate                  | 1424908800           |
    And I log in as "admin"
    And I navigate to "Plugins > Activity modules > Planner > Manage planner templates" in site administration
    And I press "Add new template"
    And I set the field "Template name" to "Template 1"
    And I press "Submit"

  Scenario: Test adding/deleting planner
    Given I am on "Course 1" course homepage with editing mode on
    And I add a "Planner" to section "1"
    And I set the field "Name" to "Test planner name"
    And I set the field "Description" to "Test planner description"
    And I select "Task number, title and due date" from the "Information on course page" singleselect
    And I select "Test assignment name" from the "Select activity" singleselect
    And I select "Template 1" from the "Template" singleselect
    And I press "Save and return to course"
    Then I should see "Test planner name"
    When I delete "Test planner name" activity
    Then I should not see "Test planner name"

  Scenario: Test editing planner
    Given I am on "Course 1" course homepage with editing mode on
    And I add a "Planner" to section "1"
    And I set the field "Name" to "Test planner name"
    And I set the field "Description" to "Test planner description"
    And I select "Task number, title and due date" from the "Information on course page" singleselect
    And I select "Test assignment name" from the "Select activity" singleselect
    And I select "Template 1" from the "Template" singleselect
    And I press "Save and return to course"
    When I am on the "Test planner name" "planner activity" page
    And I click on "Settings" "link"
    Then I should not see "Template 1"
    When I set the field "Step 1 description" to "Test step 1 description"
    And I set the field "Step 2 description" to "Test step 2 description"
    And I set the field "Step 3 description" to "Test step 3 description"
    And I set the field "Step 4 description" to "Test step 4 description"
    And I set the field "Step 5 description" to "Test step 5 description"
    And I set the field "Step 6 description" to "Test step 6 description"
    And I press "Save and display"
    Then I should see "Test step 1 description"
    When I press "Calculate student steps"
    Then I should see "Student steps updated"
    When I click on "Settings" "link"
    Then I should not see "Template 1"
    And I should not see "Select activity"

  Scenario: Test the save as new template button
    Given I am on "Course 1" course homepage with editing mode on
    And I add a "Planner" to section "1"
    And I set the field "Name" to "Test planner name"
    And I set the field "Description" to "Test planner description"
    And I select "Task number, title and due date" from the "Information on course page" singleselect
    And I select "Test assignment name" from the "Select activity" singleselect
    And I select "Template 1" from the "Template" singleselect
    When I set the field "Step 1 description" to "Test step 1 description new"
    And I set the field "Step 1 name" to "Test step 1 name new"
    And I press "Save as new template"
    And I set the field "Template name" to "Template 2"
    And I click on "Save as new template" "button" in the "Save as new template" "dialogue"
    And I navigate to "Plugins > Activity modules > Planner > Manage planner templates" in site administration
    Then I should see "Template 1"
    And I should see "Template 2"
    When I click on "View" "link" in the "Template 2" "table_row"
    Then I should see "Test step 1 description new"
    And I should see "Test step 1 name new"

  Scenario: Test that the save as new template button does not save if the name isn't unique
    Given I navigate to "Plugins > Activity modules > Planner > Manage planner templates" in site administration
    And I press "Add new template"
    And I set the field "Template name" to "Template 1"
    And I press "Submit"
    And I am on "Course 1" course homepage with editing mode on
    And I add a "Planner" to section "1"
    And I set the field "Name" to "Test planner name"
    And I set the field "Description" to "Test planner description"
    And I select "Task number, title and due date" from the "Information on course page" singleselect
    And I select "Test assignment name" from the "Select activity" singleselect
    When I select "Template 1" from the "Template" singleselect
    And I press "Save as new template"
    And I set the field "Template name" to "Template 1"
    And I click on "Save as new template" "button" in the "Save as new template" "dialogue"
    Then I should see "The template name must be unique"

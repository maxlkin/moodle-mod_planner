<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Strings for component 'planner', language 'en'
 *
 * @copyright 2021 Brickfield Education Labs, www.brickfield.ie
 * @package mod_planner
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['planner:addinstance'] = 'Add a new planner activity';
$string['planner:manageplanner'] = 'Manage planner activities';
$string['planner:managetemplates'] = 'Manage planner templates';
$string['planner:view'] = 'View planner';
$string['plannertext'] = 'Planner text';
$string['modulename'] = 'Planner';
$string['modulename_help'] = 'The planner activity can be attached to either a quiz or an assignment for steps.';
$string['modulename_link'] = 'mod/planner/view';
$string['modulenameplural'] = 'Planner';
$string['privacy:metadata:planner_userstep'] = 'Information about the user\'s steps for a given planner activity';
$string['privacy:metadata:planner_userstep:stepid'] = 'The ID of a particular step for a planner activity.';
$string['privacy:metadata:planner_userstep:userid'] = 'The ID of a user for a planner activity.';
$string['privacy:metadata:planner_userstep:timestart'] = 'The timestamp indicating when the step was started by the user.';
$string['privacy:metadata:planner_userstep:duedate'] = 'The timestamp indicating when the planner step is going to be due.';
$string['privacy:metadata:planner_userstep:completionstatus'] = 'The ID of the completionstatus that the user selected.';
$string['privacy:metadata:planner_userstep:timemodified'] = 'The timestamp indicating when the step was modified by the user.';

$string['privacy:metadata:planner_template'] = 'Information about the templates for the planner activity';
$string['privacy:metadata:planner_template:userid'] = 'The ID of the user which created a template for the planner activity.';
$string['privacy:metadata:planner_template:name'] = 'The name of the template for the planner activity.';
$string['privacy:metadata:planner_template:disclaimer'] = 'The disclaimer of the template.';
$string['privacy:metadata:planner_template:personal'] = 'The switch indicating if the planner is global or personal.';
$string['privacy:metadata:planner_template:status'] = 'The status indicating if a template is enabled or disabled.';
$string['privacy:metadata:planner_template:copied'] = 'The count indicating how many times the template has been copied.';
$string['privacy:metadata:planner_template:timecreated'] = 'The timestamp indicating when the template was created by the user';
$string['privacy:metadata:planner_template:timemodified'] = 'The timestamp indicating when the template was modified by the user';

$string['pluginadministration'] = 'Planner administration';
$string['pluginname'] = 'Planner';
$string['search:activity'] = 'Planner';

$string['upcomingemailtemplate'] = 'Upcoming email template';
$string['upcomingemailtemplate_help'] = 'Email template for reminder emails of upcoming step';
$string['upcomingemailtemplatedefault'] = 'Hi {$a->firstname},

You have upcoming Step \'{$a->stepname}\' for {$a->activityname} on {$a->stepdate}.

Review your step at {$a->link}.

If you need help, please contact the site administrator,
{$a->admin}';
$string['missedemailtemplate'] = 'Missed due date email template';
$string['missedemailtemplate_help'] = 'Email template for reminder email of missed due date';
$string['missedemailtemplatedefault'] = 'Hi {$a->firstname},

You missed the Step \'{$a->stepname}\' for {$a->activityname} on {$a->duedate}.

You can complete your step at {$a->link}.

If you need help, please contact the site administrator,
{$a->admin}';
$string['frequencyemail'] = 'Frequency of reminders';
$string['frequencyemailhelp'] = 'Frequency of reminders (X days)';

$string['linkedactivityemailtemplate'] = 'Deleted linked activity teacher email template';
$string['linkedactivityemailtemplate_help'] = 'Email template of teacher for a deleted linked activity from planner';
$string['linkedactivityemailtemplatedefault'] = 'Hi {$a->firstname},

Activity which is linked to {$a->plannername} has been deleted.

We request that you remove this planner from this course {$a->coursename} - {$a->courselink}.

If you need help, please contact the site administrator,
{$a->admin}';

$string['linkedactivitystudentemailtemplate'] = 'Deleted linked activity student email template';
$string['linkedactivitystudentemailtemplate_help'] = 'Email template of student for a deleted linked activity from planner';
$string['linkedactivitystudentemailtemplatedefault'] = 'Hi {$a->firstname},

We request that you to contact your lecturer as activity from \'{$a->plannername}\' has been deleted from the system.

If you need help, please contact the site administrator,
{$a->admin}';

$string['changedateemailtemplate'] = 'Changed date email template';
$string['changedateemailtemplate_help'] = 'Email template for change in date of linked activity';
$string['changedateemailtemplatedefault'] = 'Hi {$a->firstname},

Date has been changed for \'{$a->activityname}\' which is linked to {$a->plannername} - {$a->link}.

If you need help, please contact the site administrator,
{$a->admin}';
$string['totaltimeallocated'] = 'Total time allocated for all steps should equal 100';
$string['enternumber'] = 'Enter number between 0-100';
$string['steptimeallocationsettings'] = 'Step {$a} time allocation';
$string['stepnamesettings'] = 'Step {$a} name';
$string['stepdescriptionsettings'] = 'Step {$a} description';
$string['step1default'] = 'Understanding your assignment';
$string['step1defaultallocatin'] = '5';
$string['step2default'] = 'Get organised – focus your topic';
$string['step2defaultallocatin'] = '5';
$string['step3default'] = 'Research – find, review & evaluate information';
$string['step3defaultallocatin'] = '25';
$string['step4default'] = 'Active reading & taking notes';
$string['step4defaultallocatin'] = '30';
$string['step5default'] = 'Create a plan / overall structure';
$string['step5defaultallocatin'] = '5';
$string['step6default'] = 'Write, reference, revise and proofread';
$string['step6defaultallocatin'] = '30';
$string['planner:manage_templates'] = 'Manage templates';
$string['manage_templates'] = 'Manage templates';
$string['addtemplate'] = 'Add new template';
$string['deletetemplate'] = 'Delete template';
$string['deletetemplatecheck'] = 'Are you absolutely sure you want to completely delete the template {$a}?';
$string['deletednottemplate'] = 'Could not delete {$a} !';
$string['stepname'] = 'Step {no} name';
$string['steptimeallocation'] = 'Step {no} time allocation';
$string['stepdescription'] = 'Step {no} description';
$string['messageprovider:planner_notification'] = 'Planner notifications';
$string['crontask'] = 'Email reminder to student for upcoming and missed step for planner module';
$string['crontaskdeleted'] = 'Email reminder to teacher and student for deleted linked activity';
$string['crontaskdatechange'] = 'Email reminder to teacher for date change in activity';
$string['alltaskscompleted'] = 'All tasks completed';
$string['duein'] = 'Due in {$a} days';
$string['missedbefore'] = 'Overdue by {$a} days';
$string['invalidtemplate'] = 'Invalid template';
$string['edittemplate'] = 'Edit template';
$string['newtemplate'] = 'New template';
$string['successfullyupdated'] = 'Template updated successfully';
$string['successfullyadded'] = 'Template added successfully';
$string['step'] = 'Step';
$string['assignment'] = 'Assignment';
$string['quiz'] = 'Quiz';
$string['selectactivity'] = 'Select activity';
$string['selecttemplate'] = 'Select template';
$string['template'] = 'Template';
$string['disclaimer'] = 'Disclaimer';
$string['invalidid'] = 'Invalid ID';
$string['print'] = 'Print';
$string['plannerdefaultstartingon'] = 'Planner default starting on';
$string['plannerdefaultendingon'] = 'Planner default ending on';
$string['startingon'] = 'Starting on';
$string['endingon'] = 'Ending on';
$string['daysinstruction'] = 'According to the dates you have entered, you have {$a} days to finish.';
$string['step'] = 'Step';
$string['startdate'] = 'Start date';
$string['enddate'] = 'End date';
$string['report'] = 'Report';
$string['reportheading'] = '{$a} Report';
$string['studentname'] = 'Student name';
$string['email'] = 'Email';
$string['stepnumber'] = 'Step {$a}';
$string['completed'] = 'Completed';
$string['pending'] = 'Pending';
$string['downloadcsv'] = 'Download CSV';
$string['name'] = 'Name';
$string['templateowner'] = 'Template owner';
$string['templatetype'] = 'Template type';
$string['copy'] = 'Copy';
$string['status'] = 'Status';
$string['action'] = 'Action';
$string['global'] = 'Global';
$string['personal'] = 'Personal';
$string['enabled'] = 'Enabled';
$string['disabled'] = 'Disabled';
$string['templatename'] = 'Template name';
$string['recalculateschedule'] = 'Re-calculate schedule!';
$string['startdatewarning1'] = 'Start date should not be less than default start date';
$string['startdatewarning2'] = 'Start date should not be greater than default end date';
$string['studentstepupdated'] = 'Student steps updated';
$string['recalculatedstudentsteps'] = 'Re-calculated student steps';
$string['studentstepmarkcompleted'] = 'Step marked as completed';
$string['studentstepmarkpending'] = 'Step marked as pending';
$string['stepcompleted'] = 'Step completed';
$string['steppending'] = 'Step pending';
$string['stepupdated'] = 'Start date updated for steps';
$string['missedemailsubject'] = 'Missed step notification';
$string['upcomingemailsubject'] = 'Upcoming step notification';
$string['markstepascomplete'] = 'Mark this step as completed';
$string['markstepaspending'] = 'Mark this step as pending';
$string['submit'] = 'Submit';
$string['differentdates'] = 'Want to try a different date?';
$string['stepsyettobe'] = 'Steps have yet to be configured';
$string['calculatestudentsteps'] = 'Calculate student steps';
$string['recalculatestudentsteps'] = 'Re-calculate student steps';
$string['actionnotassociated'] = 'Activity not associated';
$string['invalidplanner'] = 'Invalid planner';
$string['relatedactivitynotexistdelete'] = 'Associated activity does not exist, kindly remove this planner.';
$string['disabletemplate'] = 'Disable this template';
$string['enabletemplate'] = 'Enable this template';
$string['description'] = 'Description';
$string['helpinstruction'] = 'Step';
$string['helpinstruction_help'] = 'If you want to remove this step, save it with no name.';
$string['informationcoursepage'] = 'Information on course page';
$string['shownothing'] = 'Show nothing';
$string['showitem3'] = 'Task number, title and due date';
$string['showallitems'] = 'Show all (task number, title, due date and step description)';
$string['deletedactivityemailsubject'] = 'Linked activity has been deleted email notification';
$string['deletedactivitystudentsubject'] = 'Linked activity has been deleted email notification';
$string['changedateemailsubject'] = 'Activity date change email notification for planner';
$string['addstepstoform'] = 'Add {no} step to the form';
$string['savenewtemplate'] = 'Save as new template';
$string['enternewname'] = 'Enter a new name for the template';
$string['alltemplates'] = 'All templates';
$string['mytemplates'] = 'My templates';
$string['templatenameunique'] = 'The template name must be unique';
$string['activityname'] = 'Activity name';
$string['activityname_param'] = 'Activity name: {$a}';

$string['activitiesenabled'] = 'selecting a activity';
$string['activitiesenabled_help'] = '* Select an assignment or quiz activity for the planner.';

$string['activitiesdisabled'] = 'selecting a activity';
$string['activitiesdisabled_help'] = '* An assignment or quiz activity must exist in the course to select.';

$string['templatesenabled'] = 'selecting a template';
$string['templatesenabled_help'] = '* Select a template for the planner.';

$string['templatesdisabled'] = 'selecting a template';
$string['templatesdisabled_help'] = '* A template must exist to select.
* To add a template, go to the administration block and click Manage Templates.';

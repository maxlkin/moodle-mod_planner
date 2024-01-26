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
 * A scheduled task for planner cron.
 *
 * @copyright 2021 Brickfield Education Labs, www.brickfield.ie
 * @package    mod_planner
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_planner\task;

defined('MOODLE_INTERNAL') || die();

/**
 * The main scheduled task for the planner.
 *
 * @package   mod_planner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class cron_task_deletedactivity extends \core\task\scheduled_task {

    /**
     * Get a descriptive name for this task (shown to admins).
     *
     * @return string
     */
    public function get_name() {
        return get_string('crontaskdeleted', 'mod_planner');
    }

    /**
     * Run planner cron.
     */
    public function execute() {
        global $CFG, $DB;

        mtrace(' processing delete activity planner cron ...');

        $plannerid = $DB->get_record('modules', ['name' => 'planner', 'visible' => '1']);
        if ($plannerid) {
            $sql = 'SELECT p.*, cm.instance, cm.id AS cmid
                      FROM {planner} p
                      JOIN {course_modules} cm ON (cm.instance = p.id AND cm.module = :plannerid)
                     WHERE  cm.visible = 1';
            $allplanners = $DB->get_records_sql($sql, ['plannerid' => $plannerid->id]);

            if ($allplanners) {
                $studentroleids = $DB->get_records('role', ['archetype' => 'student']);
                $teacherroleids = $DB->get_records('role', ['archetype' => 'editingteacher']);
                $supportuser = \core_user::get_support_user();
                $deletedactivityemailsubject = get_string('deletedactivityemailsubject', 'mod_planner');
                $deletedactivitystudentsubject = get_string('deletedactivitystudentsubject', 'mod_planner');
                $deletedactivityemailtemplate = get_config('planner', 'deletedactivityemail');
                $deletedactivitystudentemailtemplate = get_config('planner', 'deletedactivitystudentemail');

                // Logic to detect last cron run, then only find activities deleted, via logs, since then.
                // This is to avoid spam emailing users indefinately when activity isn't found.
                $task = \core\task\manager::get_scheduled_task('mod_planner\task\cron_task_deletedactivity');
                $lastruntime = $task->get_last_run_time();
                mtrace('Last run time = ' . $lastruntime);
                // Get all possible log tables in use.
                $logtables = (get_log_manager())->get_readers('\core\log\sql_internal_table_reader');

                foreach ($allplanners as $planner) {
                    // Look through all of the site log systems, per planner.
                    $selectwhere = 'target = ? AND action = ? AND objectid = ? AND timecreated > ?';
                    $inparams = ['course_module', 'deleted', $planner->activitycmid, $lastruntime];
                    foreach ($logtables as $log => $logdetails) {
                        // Get all matching events, using the log API.
                        // SHOULD only be one instance.
                        $events = $logdetails->get_events_select($selectwhere, $inparams, 'id DESC', 0, 0);
                    }

                    // If activity deletion event detected since last cron run, then process.
                    if ($events) {
                        $courseid = $planner->course;
                        $course = $DB->get_record('course', ['id' => $courseid]);
                        $coursecontext = \context_course::instance($courseid);
                        $teachers = [];
                        foreach ($teacherroleids as $teacherroleid) {
                            $teachers[] = get_role_users($teacherroleid->id, $coursecontext);
                        }
                        $teachers = reset($teachers);
                        if ($teachers) {
                            if ($deletedactivityemailtemplate) {
                                $subject = $deletedactivityemailsubject;
                                foreach ($teachers as $teacher) {
                                    // Ensure email templates are renewed from original for each user.
                                    $deletedactivityemail = $deletedactivityemailtemplate;
                                    $tmpteacher = \core_user::get_user($teacher->id);
                                    $deletedactivityemail = str_replace(
                                        '{$a->firstname}',
                                        $tmpteacher->firstname,
                                        $deletedactivityemail
                                    );
                                    $deletedactivityemail = str_replace(
                                        '{$a->plannername}',
                                        format_string($planner->name),
                                        $deletedactivityemail
                                    );
                                    $deletedactivityemail = str_replace(
                                        '{$a->coursename}',
                                        format_string($course->fullname),
                                        $deletedactivityemail
                                    );
                                    $deletedactivityemail = str_replace(
                                        '{$a->courselink}',
                                        $CFG->wwwroot.'/course/view.php?id='.$courseid,
                                        $deletedactivityemail
                                    );
                                    $deletedactivityemail = str_replace(
                                        '{$a->admin}',
                                        fullname($supportuser),
                                        $deletedactivityemail
                                    );
                                    $message = $deletedactivityemail;
                                    $messagehtml = format_text($message, FORMAT_MOODLE);
                                    $messagetext = html_to_text($messagehtml);

                                    $eventdata = new \core\message\message();
                                    $eventdata->courseid          = $courseid;
                                    $eventdata->modulename        = 'planner';
                                    $eventdata->userfrom          = $supportuser;
                                    $eventdata->userto            = $tmpteacher;
                                    $eventdata->subject           = $subject;
                                    $eventdata->fullmessage       = $messagetext;
                                    $eventdata->fullmessageformat = FORMAT_PLAIN;
                                    $eventdata->fullmessagehtml   = $messagehtml;
                                    $eventdata->smallmessage      = $subject;
                                    $eventdata->name              = 'planner_notification';
                                    $eventdata->component         = 'mod_planner';
                                    $eventdata->notification      = 1;
                                    $customdata = [
                                        'cmid' => $planner->cmid,
                                        'instance' => $planner->instance
                                    ];
                                    // Check if the userfrom is real and visible.
                                    $eventdata->customdata = $customdata;
                                    message_send($eventdata);
                                }
                            }
                        }

                        $students = [];
                        foreach ($studentroleids as $studentroleid) {
                            $students[] = get_role_users($studentroleid->id, $coursecontext);
                        }
                        $students = reset($students);
                        if ($students) {
                            if ($deletedactivitystudentemailtemplate) {
                                $subject = $deletedactivitystudentsubject;
                                foreach ($students as $student) {
                                    // Ensure email templates are renewed from original for each user.
                                    $deletedactivitystudentemail = $deletedactivitystudentemailtemplate;
                                    $tmpstudent = \core_user::get_user($student->id);
                                    $deletedactivitystudentemail = str_replace(
                                        '{$a->firstname}',
                                        $tmpstudent->firstname,
                                        $deletedactivitystudentemail
                                    );
                                    $deletedactivitystudentemail = str_replace(
                                        '{$a->plannername}',
                                        format_string($planner->name),
                                        $deletedactivitystudentemail
                                    );
                                    $deletedactivitystudentemail = str_replace(
                                        '{$a->admin}',
                                        fullname($supportuser),
                                        $deletedactivitystudentemail
                                    );
                                    $message = $deletedactivitystudentemail;
                                    $messagehtml = format_text($message, FORMAT_MOODLE);
                                    $messagetext = html_to_text($messagehtml);

                                    $eventdata = new \core\message\message();
                                    $eventdata->courseid          = $courseid;
                                    $eventdata->modulename        = 'planner';
                                    $eventdata->userfrom          = $supportuser;
                                    $eventdata->userto            = $tmpstudent;
                                    $eventdata->subject           = $subject;
                                    $eventdata->fullmessage       = $messagetext;
                                    $eventdata->fullmessageformat = FORMAT_PLAIN;
                                    $eventdata->fullmessagehtml   = $messagehtml;
                                    $eventdata->smallmessage      = $subject;
                                    $eventdata->name              = 'planner_notification';
                                    $eventdata->component         = 'mod_planner';
                                    $eventdata->notification      = 1;
                                    $customdata = [
                                        'cmid' => $planner->cmid,
                                        'instance' => $planner->instance
                                    ];
                                    // Check if the userfrom is real and visible.
                                    $eventdata->customdata = $customdata;
                                    message_send($eventdata);
                                }
                            }
                        }
                    }
                }
            }
        }
        mtrace('done');
    }
}

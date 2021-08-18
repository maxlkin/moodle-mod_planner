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

        $plannerid = $DB->get_record('modules', array('name' => 'planner', 'visible' => '1'));
        if ($plannerid) {
            $allplanners = $DB->get_records_sql("SELECT p.*, cm.instance, cm.id as cmid
                    FROM {planner} p
                    JOIN {course_modules} cm ON (cm.instance = p.id AND cm.module = ".$plannerid->id.")
                    WHERE  cm.visible = 1");

            if ($allplanners) {
                $studentroleid = $DB->get_record('role', array('shortname' => 'student'));
                $teacherroleid = $DB->get_record('role', array('shortname' => 'editingteacher'));
                $supportuser = core_user::get_support_user();
                $deletedactivityemailsubject = get_string('deletedactivityemailsubject', 'mod_planner');
                $deletedactivitystudentsubject = get_string('deletedactivitystudentsubject', 'mod_planner');
                $deletedactivityemail = get_config('planner', 'deletedactivityemail');
                $deletedactivitystudentemail = get_config('planner', 'deletedactivitystudentemail');

                foreach ($allplanners as $planner) {
                    $cminfo = $DB->get_record('course_modules', array('id' => $planner->activitycmid));
                    if ((!$cminfo) OR ($cminfo->deletioninprogress == '1')) {

                        $courseid = $planner->course;
                        $course = $DB->get_record('course', array('id' => $courseid));
                        $coursecontext = context_course::instance($courseid);
                        $teachers = get_role_users($teacherroleid->id, $coursecontext);

                        if ($teachers) {
                            if ($deletedactivityemail) {
                                $subject = $deletedactivityemailsubject;
                                foreach ($teachers as $teacher) {
                                    $deletedactivityemail = str_replace('{$a->firstname}',
                                    $teacher->firstname, $deletedactivityemail);
                                    $deletedactivityemail = str_replace('{$a->plannername}',
                                    format_string($planner->name), $deletedactivityemail);
                                    $deletedactivityemail = str_replace('{$a->coursename}',
                                    format_string($course->fullname), $deletedactivityemail);
                                    $deletedactivityemail = str_replace('{$a->courselink}',
                                    $CFG->wwwroot.'/course/view.php?id='.$courseid, $deletedactivityemail);
                                    $deletedactivityemail = str_replace('{$a->admin}',
                                    fullname($supportuser), $deletedactivityemail);
                                    $message = $deletedactivityemail;
                                    $messagetext = html_to_text($messagehtml);
                                    $messagehtml = format_text($message, FORMAT_MOODLE);

                                    $eventdata = new \core\message\message();
                                    $eventdata->courseid         = $courseid;
                                    $eventdata->modulename       = 'planner';
                                    $eventdata->userfrom         = $supportuser;
                                    $eventdata->userto           = $teacher;
                                    $eventdata->subject          = $subject;
                                    $eventdata->fullmessage      = $messagetext;
                                    $eventdata->fullmessageformat = FORMAT_PLAIN;
                                    $eventdata->fullmessagehtml  = $messagehtml;
                                    $eventdata->smallmessage     = $subject;
                                    $eventdata->name            = 'planner_notification';
                                    $eventdata->component       = 'mod_planner';
                                    $eventdata->notification    = 1;
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

                        $students = get_role_users($studentroleid->id, $coursecontext);
                        if ($students) {
                            if ($deletedactivitystudentemail) {
                                $subject = $deletedactivitystudentsubject;
                                foreach ($students as $student) {
                                    $deletedactivitystudentemail = str_replace('{$a->firstname}',
                                    $student->firstname, $deletedactivitystudentemail);
                                    $deletedactivitystudentemail = str_replace('{$a->plannername}',
                                    format_string($planner->name), $deletedactivitystudentemail);
                                    $deletedactivitystudentemail = str_replace('{$a->admin}',
                                    fullname($supportuser), $deletedactivitystudentemail);
                                    $message = $deletedactivitystudentemail;
                                    $messagehtml = format_text($message, FORMAT_MOODLE);
                                    $messagetext = html_to_text($messagehtml);

                                    $eventdata = new \core\message\message();
                                    $eventdata->courseid         = $courseid;
                                    $eventdata->modulename       = 'planner';
                                    $eventdata->userfrom         = $supportuser;
                                    $eventdata->userto           = $student;
                                    $eventdata->subject          = $subject;
                                    $eventdata->fullmessage      = $messagetext;
                                    $eventdata->fullmessageformat = FORMAT_PLAIN;
                                    $eventdata->fullmessagehtml  = $messagehtml;
                                    $eventdata->smallmessage     = $subject;
                                    $eventdata->name            = 'planner_notification';
                                    $eventdata->component       = 'mod_planner';
                                    $eventdata->notification    = 1;
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

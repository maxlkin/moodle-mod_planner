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
                $deletedactivityemail = get_config('planner', 'deletedactivityemail');
                $deletedactivitystudentemail = get_config('planner', 'deletedactivitystudentemail');

                foreach ($allplanners as $planner) {
                    $cminfo = $DB->get_record('course_modules', ['id' => $planner->activitycmid]);
                    if ((!$cminfo) || ($cminfo->deletioninprogress == '1')) {

                        $courseid = $planner->course;
                        $course = $DB->get_record('course', ['id' => $courseid]);
                        $coursecontext = \context_course::instance($courseid);
                        $teachers = [];
                        foreach ($teacherroleids as $teacherroleid) {
                            $teachers[] = get_role_users($teacherroleid->id, $coursecontext);
                        }
                        $teachers = reset($teachers);
                        if ($teachers) {
                            if ($deletedactivityemail) {
                                $subject = $deletedactivityemailsubject;
                                foreach ($teachers as $teacher) {
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
                            if ($deletedactivitystudentemail) {
                                $subject = $deletedactivitystudentsubject;
                                foreach ($students as $student) {
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

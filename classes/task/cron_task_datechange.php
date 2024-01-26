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
class cron_task_datechange extends \core\task\scheduled_task {

    /**
     * Get a descriptive name for this task (shown to admins).
     *
     * @return string
     */
    public function get_name() {
        return get_string('crontaskdatechange', 'mod_planner');
    }

    /**
     * Run planner cron.
     */
    public function execute() {
        global $CFG, $DB;

        mtrace(' processing date change planner cron...');

        $plannerid = $DB->get_record('modules', ['name' => 'planner', 'visible' => '1']);
        if ($plannerid) {
            $sql = 'SELECT p.*, cm.instance, cm.id AS cmid
                      FROM {planner} p
                      JOIN {course_modules} cm ON (cm.instance = p.id AND cm.module = :plannerid)
                     WHERE  cm.visible = 1';
            $allplanners = $DB->get_records_sql($sql, ['plannerid' => $plannerid->id]);

            if ($allplanners) {
                $teacherroleids = $DB->get_records('role', ['archetype' => 'editingteacher']);
                $supportuser = \core_user::get_support_user();
                $changedateemailsubject = get_string('changedateemailsubject', 'mod_planner');
                $changedateemailtemplate = get_config('planner', 'changedateemailtemplate');

                $task = \core\task\manager::get_scheduled_task('mod_planner\task\cron_task_datechange');
                $lastruntime = $task->get_last_run_time();

                foreach ($allplanners as $planner) {
                    // Reinitialise $teachers array for each planner, needs to be course specific.
                    $teachers = [];
                    $sql = 'SELECT cm.id,cm.instance,cm.module,m.name
                              FROM {course_modules} cm
                              JOIN {modules} m ON (m.id = cm.module)
                             WHERE cm.id = :cmid';
                    $cminfoactivity = $DB->get_record_sql($sql, ['cmid' => $planner->activitycmid]);
                    if ($cminfoactivity) {
                        $modulename = $DB->get_record($cminfoactivity->name, ['id' => $cminfoactivity->instance]);
                        if ($cminfoactivity->name == 'assign') {
                            $starttime = $modulename->allowsubmissionsfromdate;
                            $endtime = $modulename->duedate;
                        } else if ($cminfoactivity->name == 'quiz') {
                            $starttime = $modulename->timeopen;
                            $endtime = $modulename->timeclose;
                        }
                        $timemodified = $modulename->timemodified;

                        if (($timemodified >= $lastruntime)
                        && (($starttime != $planner->timeopen) || ($endtime != $planner->timeclose))) {
                            $courseid = $planner->course;
                            $coursecontext = \context_course::instance($courseid);
                            foreach ($teacherroleids as $teacherroleid) {
                                $newteachers = get_role_users($teacherroleid->id, $coursecontext);
                                foreach ($newteachers as $newteacher) {
                                    $teachers[] = $newteacher;
                                }
                            }
                            if ($teachers) {
                                if ($changedateemailtemplate) {
                                    $subject = $changedateemailsubject;
                                    foreach ($teachers as $teacher) {
                                        // Ensure email template is renewed from original for each teacher.
                                        $changedateemail = $changedateemailtemplate;
                                        $tmpteacher = \core_user::get_user($teacher->id);
                                        $changedateemail = str_replace(
                                            '{$a->firstname}',
                                            $tmpteacher->firstname,
                                            $changedateemail
                                        );
                                        $changedateemail = str_replace(
                                            '{$a->activityname}',
                                            format_string($modulename->name),
                                            $changedateemail
                                        );
                                        $changedateemail = str_replace(
                                            '{$a->plannername}',
                                            format_string($planner->name),
                                            $changedateemail
                                        );
                                        $changedateemail = str_replace(
                                            '{$a->link}',
                                            $CFG->wwwroot . '/mod/planner/view.php?id=' . $planner->cmid,
                                            $changedateemail
                                        );
                                        $changedateemail = str_replace(
                                            '{$a->admin}',
                                            fullname($supportuser),
                                            $changedateemail
                                        );
                                        $message = $changedateemail;
                                        $messagehtml = format_text($message, FORMAT_MOODLE);
                                        $messagetext = html_to_text($messagehtml);

                                        $eventdata = new \core\message\message();
                                        $eventdata->courseid = $courseid;
                                        $eventdata->modulename = 'planner';
                                        $eventdata->userfrom = $supportuser;
                                        $eventdata->userto = $tmpteacher;
                                        $eventdata->subject = $subject;
                                        $eventdata->fullmessage = $messagetext;
                                        $eventdata->fullmessageformat = FORMAT_PLAIN;
                                        $eventdata->fullmessagehtml = $messagehtml;
                                        $eventdata->smallmessage = $subject;
                                        $eventdata->name = 'planner_notification';
                                        $eventdata->component = 'mod_planner';
                                        $eventdata->notification = 1;
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
        }
        mtrace('done');
    }
}

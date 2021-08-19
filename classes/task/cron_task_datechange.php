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

        $now = time();

        mtrace(' processing date change planner cron...');

        $plannerid = $DB->get_record('modules', array('name' => 'planner', 'visible' => '1'));
        if ($plannerid) {
            $allplanners = $DB->get_records_sql("SELECT p.*, cm.instance, cm.id as cmid
                    FROM {planner} p
                    JOIN {course_modules} cm ON (cm.instance = p.id AND cm.module = ".$plannerid->id.")
                    WHERE  cm.visible = 1");

            if ($allplanners) {
                $teacherroleid = $DB->get_record('role', array('shortname' => 'editingteacher'));
                $supportuser = core_user::get_support_user();
                $changedateemailsubject = get_string('changedateemailsubject', 'mod_planner');
                $changedateemail = get_config('planner', 'changedateemailtemplate');
                foreach ($allplanners as $planner) {
                    $cminfoactivity = $DB->get_record_sql("SELECT cm.id,cm.instance,cm.module,m.name FROM {course_modules} cm
                     JOIN {modules} m ON (m.id = cm.module) WHERE cm.id = '".$planner->activitycmid."'");
                    if ($cminfoactivity) {
                        $modulename = $DB->get_record($cminfoactivity->name, array('id' => $cminfoactivity->instance));
                        if ($cminfoactivity->name == 'assign') {
                            $starttime = $modulename->allowsubmissionsfromdate;
                            $endtime = $modulename->duedate;
                        } else if ($cminfoactivity->name == 'quiz') {
                            $starttime = $modulename->timeopen;
                            $endtime = $modulename->timeclose;
                        }

                        if (($starttime != $planner->timeopen) OR ($endtime != $planner->timeclose)) {
                            $courseid = $planner->course;
                            $course = $DB->get_record('course', array('id' => $courseid));
                            $coursecontext = context_course::instance($courseid);
                            $teachers = get_role_users($teacherroleid->id, $coursecontext);

                            if ($teachers) {
                                if ($changedateemail) {
                                    $subject = $changedateemailsubject;
                                    foreach ($teachers as $teacher) {
                                        $changedateemail = str_replace('{$a->firstname}',
                                        $teacher->firstname, $changedateemail);
                                        $changedateemail = str_replace('{$a->activityname}',
                                        format_string($modulename->name), $changedateemail);
                                        $changedateemail = str_replace('{$a->plannername}',
                                        format_string($planner->name), $changedateemail);
                                        $changedateemail = str_replace('{$a->link}',
                                        $CFG->wwwroot.'/mod/planner/view.php?id='.$planner->cmid, $changedateemail);
                                        $changedateemail = str_replace('{$a->admin}',
                                        fullname($supportuser), $changedateemail);
                                        $message = $changedateemail;
                                        $messagehtml = format_text($message, FORMAT_MOODLE);
                                        $messagetext = html_to_text($messagehtml);

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
                        }
                    }
                }
            }
        }
        mtrace('done');
    }
}

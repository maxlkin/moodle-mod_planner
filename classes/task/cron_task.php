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
class cron_task extends \core\task\scheduled_task {

    /**
     * Get a descriptive name for this task (shown to admins).
     *
     * @return string
     */
    public function get_name() {
        return get_string('crontask', 'mod_planner');
    }

    /**
     * Run planner cron.
     */
    public function execute() {
        global $CFG, $DB;

        $now = time();

        mtrace(' processing planner ...');

        // Now when the scheduled allocator had a chance to do its job.
        // Check if there are some planners to switch into the assessment phase.
        $currentime = time();
        $upcomingemail = get_config('planner', 'upcomingemail');
        $missedemail = get_config('planner', 'dueemail');
        $frequencyemail = get_config('planner', 'frequencyemail');
        $nextdate = strtotime("+".$frequencyemail." days", $currentime);
        $previousdate = strtotime("-".$frequencyemail." days", $currentime);

        $plannerid = $DB->get_record('modules', array('name' => 'planner', 'visible' => '1'));
        if ($plannerid) {

            $allplanner = $DB->get_records_sql("SELECT pus.id,pus.stepid,pus.userid,pus.duedate,ps.name,p.course,cm.instance,
			p.activitycmid,cm.id AS cmid FROM {planner_userstep} pus JOIN {planner_step} ps ON (ps.id = pus.stepid)
			JOIN {planner} p ON (p.id = ps.plannerid) JOIN {course_modules} cm ON (cm.instance = p.id AND
			cm.module = ".$plannerid->id.")  WHERE pus.duedate BETWEEN ".$previousdate." AND ".$nextdate." AND
			pus.completionstatus = 0 AND cm.visible = 1");
            if ($allplanner) {
                $supportuser = core_user::get_support_user();
                $missedemailsubject = get_string('missedemailsubject', 'mod_planner');
                $upcomingemailsubject = get_string('upcomingemailsubject', 'mod_planner');
                foreach ($allplanner as $plannerdata) {
                    $user = $DB->get_record('user', array('id' => $plannerdata->userid));
                    $associatemodule = $DB->get_record_sql("SELECT cm.id,m.name AS modulename,cm.instance
					FROM {course_modules} cm JOIN {modules} m ON (m.id = cm.module) WHERE cm.id = '".$plannerdata->activitycmid."'");
                    if ($associatemodule->modulename == 'assign') {
                        $modulename = $DB->get_record('assign', array('id' => $associatemodule->instance));
                    } else if ($associatemodule->modulename == 'quiz') {
                        $modulename = $DB->get_record('quiz', array('id' => $associatemodule->instance));
                    }
                    if ($plannerdata->duedate > $currentime ) {
                        // Upcoming notification.
                        $subject = $upcomingemailsubject;
                        $upcomingemail = str_replace('{$a->firstname}', $user->firstname, $upcomingemail);
                        $upcomingemail = str_replace('{$a->stepname}', $plannerdata->name, $upcomingemail);
                        $upcomingemail = str_replace('{$a->activityname}', format_string($planner->name), $upcomingemail);
                        $upcomingemail = str_replace('{$a->stepdate}', userdate($plannerdata->duedate,
                        get_string('strftimedatefullshort')), $upcomingemail);
                        $upcomingemail = str_replace('{$a->link}',
                        $CFG->wwwroot.'/mod/planner/view.php?id='.$plannerdata->cmid, $upcomingemail);
                        $upcomingemail = str_replace('{$a->admin}', fullname($supportuser), $upcomingemail);
                        $message = $upcomingemail;
                    } else if ($plannerdata->duedate < $currentime ) {
                        // Missed notification.
                        $subject = $missedemailsubject;
                        $missedemail = str_replace('{$a->firstname}', $user->firstname, $missedemail);
                        $missedemail = str_replace('{$a->stepname}', $plannerdata->name, $missedemail);
                        $missedemail = str_replace('{$a->activityname}', format_string($planner->name), $missedemail);
                        $missedemail = str_replace('{$a->duedate}', userdate($plannerdata->duedate,
                        get_string('strftimedatefullshort')), $missedemail);
                        $missedemail = str_replace('{$a->link}',
                        $CFG->wwwroot.'/mod/planner/view.php?id='.$plannerdata->cmid, $missedemail);
                        $missedemail = str_replace('{$a->admin}', fullname($supportuser), $missedemail);
                        $message = $missedemail;
                    }
                    $messagehtml = format_text($message, FORMAT_MOODLE);
                    $messagetext = html_to_text($messagehtml);

                    $eventdata = new \core\message\message();
                    $eventdata->courseid         = $plannerdata->course;
                    $eventdata->modulename       = 'planner';
                    $eventdata->userfrom         = $supportuser;
                    $eventdata->userto           = $user;
                    $eventdata->subject          = $subject;
                    $eventdata->fullmessage      = $messagetext;
                    $eventdata->fullmessageformat = FORMAT_PLAIN;
                    $eventdata->fullmessagehtml  = $messagehtml;
                    $eventdata->smallmessage     = $subject;
                    $eventdata->name            = 'planner_notification';
                    $eventdata->component       = 'mod_planner';
                    $eventdata->notification    = 1;
                    $customdata = [
                        'cmid' => $plannerdata->cmid,
                        'instance' => $associatemodule->instance
                    ];
                    // Check if the userfrom is real and visible.
                    $eventdata->customdata = $customdata;
                    message_send($eventdata);
                }
                 mtrace('done');
            }
        }
    }
}

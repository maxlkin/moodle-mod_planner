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
 * Event observers used in planner.
 *
 * @package    mod_planner
 * @copyright  2013 Rajesh Taneja <rajesh@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_planner;

use mod_planner\planner;

/**
 * Event observer for mod_forum.
 */
class observer {

    /**
     * Triggered via user_override_created event.
     *
     * @param \mod_assign\event\user_override_created $event
     */
    public static function assign_user_override_created(\mod_assign\event\user_override_created $event) {
        global $DB;
        $context = \context_course::instance($event->courseid);
        $userid = $event->relateduserid;
        if ( $planner = $DB->get_record("planner", array("activitycmid" => $event->contextinstanceid))) {
            $cm = get_coursemodule_from_id('assign', $event->contextinstanceid);
            if ($cm) {
                $assignment = $DB->get_record("assign", array("id" => $cm->instance));
                $defaultstartdate = $assignment->allowsubmissionsfromdate;
                $defaultenddate = $assignment->duedate;
                $studentroleid = $DB->get_record('role', array('shortname' => 'student'));
                if (user_has_role_assignment($userid, $studentroleid->id, $context->id)) {
                    $assignoverrides = $DB->get_record("assign_overrides", array("id" => $event->objectid));
                    if ($assignoverrides) {
                        if ($assignoverrides->allowsubmissionsfromdate) {
                            $starttime = $assignoverrides->allowsubmissionsfromdate;
                        } else {
                            $starttime = $defaultstartdate;
                        }
                        if ($assignoverrides->duedate) {
                            $endtime = $assignoverrides->duedate;
                        } else {
                            $endtime = $defaultenddate;
                        }
                        if ($endtime > time()) {
                            $planner = planner::create_planner_by_id($planner->id);
                            $planner->planner_user_step($userid, $starttime, $endtime);
                        }
                    }
                }
            }
        }
    }

    /**
     * Triggered via user_override_updated event.
     *
     * @param \mod_assign\event\user_override_updated $event
     */
    public static function assign_user_override_updated(\mod_assign\event\user_override_updated $event) {
        global $DB;
        $context = \context_course::instance($event->courseid);
        $userid = $event->relateduserid;
        if ( $planner = $DB->get_record("planner", array("activitycmid" => $event->contextinstanceid))) {
            $cm = get_coursemodule_from_id('assign', $event->contextinstanceid);
            if ($cm) {
                $assignment = $DB->get_record("assign", array("id" => $cm->instance));
                $defaultstartdate = $assignment->allowsubmissionsfromdate;
                $defaultenddate = $assignment->duedate;
                $studentroleid = $DB->get_record('role', array('shortname' => 'student'));
                if (user_has_role_assignment($userid, $studentroleid->id, $context->id)) {
                    $assignoverrides = $DB->get_record("assign_overrides", array("id" => $event->objectid));
                    if ($assignoverrides) {
                        if ($assignoverrides->allowsubmissionsfromdate) {
                            $starttime = $assignoverrides->allowsubmissionsfromdate;
                        } else {
                            $starttime = $defaultstartdate;
                        }
                        if ($assignoverrides->duedate) {
                            $endtime = $assignoverrides->duedate;
                        } else {
                            $endtime = $defaultenddate;
                        }
                        if ($endtime > time()) {
                            $planner = planner::create_planner_by_id($planner->id);
                            $planner->planner_user_step($userid, $starttime, $endtime);
                        }
                    }
                }
            }
        }
    }

    /**
     * Triggered via user_override_deleted event.
     *
     * @param \mod_assign\event\user_override_deleted $event
     */
    public static function assign_user_override_deleted(\mod_assign\event\user_override_deleted $event) {
        global $DB;
        $context = \context_course::instance($event->courseid);
        $userid = $event->relateduserid;
        if ( $planner = $DB->get_record("planner", array("activitycmid" => $event->contextinstanceid))) {
            $cm = get_coursemodule_from_id('assign', $event->contextinstanceid);
            if ($cm) {
                $studentroleid = $DB->get_record('role', array('shortname' => 'student'));
                if (user_has_role_assignment($userid, $studentroleid->id, $context->id)) {
                    $assignment = $DB->get_record("assign", array("id" => $cm->instance));
                    $starttime = $assignment->allowsubmissionsfromdate;
                    $endtime = $assignment->duedate;
                    $userid = $event->relateduserid;
                    if ($endtime > time()) {
                        $planner = planner::create_planner_by_id($planner->id);
                        $planner->planner_user_step_delete($userid, $starttime, $endtime);
                    }
                }
            }
        }
    }
    /**
     * Triggered via group_override_created event.
     *
     * @param \mod_assign\event\group_override_created $event
     */
    public static function assign_group_override_created(\mod_assign\event\group_override_created $event) {
        global $DB;
        $context = \context_course::instance($event->courseid);
        if ( $planner = $DB->get_record("planner", array("activitycmid" => $event->contextinstanceid))) {
            $cm = get_coursemodule_from_id('assign', $event->contextinstanceid);
            if ($cm) {
                $assignment = $DB->get_record("assign", array("id" => $cm->instance));
                $defaultstartdate = $assignment->allowsubmissionsfromdate;
                $defaultenddate = $assignment->duedate;
                $groupmembers = groups_get_members($event->other['groupid'], 'u.id');
                if ($groupmembers) {
                    $assignoverrides = $DB->get_record("assign_overrides", array("id" => $event->objectid));
                    $studentroleid = $DB->get_record('role', array('shortname' => 'student'));
                    if ($assignoverrides) {
                        if ($assignoverrides->allowsubmissionsfromdate) {
                            $starttime = $assignoverrides->allowsubmissionsfromdate;
                        } else {
                            $starttime = $defaultstartdate;
                        }
                        if ($assignoverrides->duedate) {
                            $endtime = $assignoverrides->duedate;
                        } else {
                            $endtime = $defaultenddate;
                        }
                        if ($endtime > time()) {
                            foreach ($groupmembers as $groupkey => $user) {
                                $userid = $user->id;
                                if (user_has_role_assignment($userid, $studentroleid->id, $context->id)) {
                                    $$planner = planner::create_planner_by_id($planner->id);
                                    $planner->planner_user_step($userid, $starttime, $endtime);
                                }
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * Triggered via group_override_updated event.
     *
     * @param \mod_assign\event\group_override_updated $event
     */
    public static function assign_group_override_updated(\mod_assign\event\group_override_updated $event) {
        global $DB;
        $context = \context_course::instance($event->courseid);
        if ( $planner = $DB->get_record("planner", array("activitycmid" => $event->contextinstanceid))) {
            $cm = get_coursemodule_from_id('assign', $event->contextinstanceid);
            if ($cm) {
                $assignment = $DB->get_record("assign", array("id" => $cm->instance));
                $defaultstartdate = $assignment->allowsubmissionsfromdate;
                $defaultenddate = $assignment->duedate;
                $groupmembers = groups_get_members($event->other['groupid'], 'u.id');
                if ($groupmembers) {
                    $assignoverrides = $DB->get_record("assign_overrides", array("id" => $event->objectid));
                    $studentroleid = $DB->get_record('role', array('shortname' => 'student'));
                    if ($assignoverrides) {
                        if ($assignoverrides->allowsubmissionsfromdate) {
                            $starttime = $assignoverrides->allowsubmissionsfromdate;
                        } else {
                            $starttime = $defaultstartdate;
                        }
                        if ($assignoverrides->duedate) {
                            $endtime = $assignoverrides->duedate;
                        } else {
                            $endtime = $defaultenddate;
                        }
                        if ($endtime > time()) {
                            foreach ($groupmembers as $groupkey => $user) {
                                $userid = $user->id;
                                if (user_has_role_assignment($userid, $studentroleid->id, $context->id)) {
                                    $planner = planner::create_planner_by_id($planner->id);
                                    $planner->planner_user_step($userid, $starttime, $endtime);
                                }
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * Triggered via group_override_deleted event.
     *
     * @param \mod_assign\event\group_override_deleted $event
     */
    public static function assign_group_override_deleted(\mod_assign\event\group_override_deleted $event) {
        global $DB;
        $context = \context_course::instance($event->courseid);
        if ( $planner = $DB->get_record("planner", array("activitycmid" => $event->contextinstanceid))) {
            $cm = get_coursemodule_from_id('assign', $event->contextinstanceid);
            if ($cm) {
                $assignment = $DB->get_record("assign", array("id" => $cm->instance));
                $starttime = $assignment->allowsubmissionsfromdate;
                $endtime = $assignment->duedate;
                $groupmembers = groups_get_members($event->other['groupid'], 'u.id');
                if ($groupmembers) {
                    $studentroleid = $DB->get_record('role', array('shortname' => 'student'));
                    if ($endtime > time()) {
                        foreach ($groupmembers as $groupkey => $user) {
                            $userid = $user->id;
                            if (user_has_role_assignment($userid, $studentroleid->id, $context->id)) {
                                $planner = planner::create_planner_by_id($planner->id);
                                $planner->planner_user_step_delete($userid, $starttime, $endtime);
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * Triggered via user_override_created event.
     *
     * @param \mod_quiz\event\user_override_created $event
     */
    public static function quiz_user_override_created(\mod_quiz\event\user_override_created $event) {
        global $DB;
        $context = \context_course::instance($event->courseid);
        $userid = $event->relateduserid;
        if ( $planner = $DB->get_record("planner", array("activitycmid" => $event->contextinstanceid))) {
            $cm = get_coursemodule_from_id('quiz', $event->contextinstanceid);
            if ($cm) {
                $quiz = $DB->get_record("quiz", array("id" => $cm->instance));
                $defaultstartdate = $quiz->timeopen;
                $defaultenddate = $quiz->timeclose;
                $studentroleid = $DB->get_record('role', array('shortname' => 'student'));
                if (user_has_role_assignment($userid, $studentroleid->id, $context->id)) {
                    $quizoverrides = $DB->get_record("quiz_overrides", array("id" => $event->objectid));
                    if ($quizoverrides) {
                        if ($quizoverrides->timeopen) {
                            $starttime = $quizoverrides->timeopen;
                        } else {
                            $starttime = $defaultstartdate;
                        }
                        if ($quizoverrides->timeclose) {
                            $endtime = $quizoverrides->timeclose;
                        } else {
                            $endtime = $defaultenddate;
                        }
                        if ($endtime > time()) {
                            $planner = planner::create_planner_by_id($planner->id);
                            $planner->planner_user_step($userid, $starttime, $endtime);
                        }
                    }
                }
            }
        }
    }

    /**
     * Triggered via user_override_updated event.
     *
     * @param \mod_quiz\event\user_override_updated $event
     */
    public static function quiz_user_override_updated(\mod_quiz\event\user_override_updated $event) {
        global $DB;
        $context = \context_course::instance($event->courseid);
        $userid = $event->relateduserid;
        if ( $planner = $DB->get_record("planner", array("activitycmid" => $event->contextinstanceid))) {
            $cm = get_coursemodule_from_id('quiz', $event->contextinstanceid);
            if ($cm) {
                $quiz = $DB->get_record("quiz", array("id" => $cm->instance));
                $defaultstartdate = $quiz->timeopen;
                $defaultenddate = $quiz->timeclose;
                $studentroleid = $DB->get_record('role', array('shortname' => 'student'));
                if (user_has_role_assignment($userid, $studentroleid->id, $context->id)) {
                    $quizoverrides = $DB->get_record("quiz_overrides", array("id" => $event->objectid));
                    if ($quizoverrides) {
                        if ($quizoverrides->timeopen) {
                            $starttime = $quizoverrides->timeopen;
                        } else {
                            $starttime = $defaultstartdate;
                        }
                        if ($quizoverrides->timeclose) {
                            $endtime = $quizoverrides->timeclose;
                        } else {
                            $endtime = $defaultenddate;
                        }
                        if ($endtime > time()) {
                            $planner = planner::create_planner_by_id($planner->id);
                            $planner->planner_user_step($userid, $starttime, $endtime);
                        }
                    }
                }
            }
        }
    }

    /**
     * Triggered via user_override_deleted event.
     *
     * @param \mod_quiz\event\user_override_deleted $event
     */
    public static function quiz_user_override_deleted(\mod_quiz\event\user_override_deleted $event) {
        global $DB;
        $context = \context_course::instance($event->courseid);
        $userid = $event->relateduserid;
        if ( $planner = $DB->get_record("planner", array("activitycmid" => $event->contextinstanceid))) {
            $cm = get_coursemodule_from_id('quiz', $event->contextinstanceid);
            if ($cm) {
                $studentroleid = $DB->get_record('role', array('shortname' => 'student'));
                if (user_has_role_assignment($userid, $studentroleid->id, $context->id)) {
                    $quiz = $DB->get_record("quiz", array("id" => $cm->instance));
                    $starttime = $quiz->timeopen;
                    $endtime = $quiz->timeclose;
                    $userid = $event->relateduserid;
                    if ($endtime > time()) {
                            $planner = planner::create_planner_by_id($planner->id);
                            $planner->planner_user_step_delete($userid, $starttime, $endtime);
                    }
                }
            }
        }
    }

    /**
     * Triggered via group_override_created event.
     *
     * @param \mod_quiz\event\group_override_created $event
     */
    public static function quiz_group_override_created(\mod_quiz\event\group_override_created $event) {
        global $DB;
        $context = \context_course::instance($event->courseid);
        if ( $planner = $DB->get_record("planner", array("activitycmid" => $event->contextinstanceid))) {
            $cm = get_coursemodule_from_id('quiz', $event->contextinstanceid);
            if ($cm) {
                $quiz = $DB->get_record("quiz", array("id" => $cm->instance));
                $defaultstartdate = $quiz->timeopen;
                $defaultenddate = $quiz->timeclose;
                $groupmembers = groups_get_members($event->other['groupid'], 'u.id');
                if ($groupmembers) {
                    $quizoverrides = $DB->get_record("quiz_overrides", array("id" => $event->objectid));
                    $studentroleid = $DB->get_record('role', array('shortname' => 'student'));
                    if ($quizoverrides) {
                        if ($quizoverrides->timeopen) {
                            $starttime = $quizoverrides->timeopen;
                        } else {
                            $starttime = $defaultstartdate;
                        }
                        if ($quizoverrides->timeclose) {
                            $endtime = $quizoverrides->timeclose;
                        } else {
                            $endtime = $defaultenddate;
                        }
                        if ($endtime > time()) {
                            foreach ($groupmembers as $groupkey => $user) {
                                $userid = $user->id;
                                if (user_has_role_assignment($userid, $studentroleid->id, $context->id)) {
                                    $planner = planner::create_planner_by_id($planner->id);
                                    $planner->planner_user_step($userid, $starttime, $endtime);
                                }
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * Triggered via group_override_updated event.
     *
     * @param \mod_quiz\event\group_override_updated $event
     */
    public static function quiz_group_override_updated(\mod_quiz\event\group_override_updated $event) {
        global $DB;
        $context = \context_course::instance($event->courseid);
        if ( $planner = $DB->get_record("planner", array("activitycmid" => $event->contextinstanceid))) {
            $cm = get_coursemodule_from_id('quiz', $event->contextinstanceid);
            if ($cm) {
                $quiz = $DB->get_record("quiz", array("id" => $cm->instance));
                $defaultstartdate = $quiz->timeopen;
                $defaultenddate = $quiz->timeclose;
                $groupmembers = groups_get_members($event->other['groupid'], 'u.id');
                if ($groupmembers) {
                    $quizoverrides = $DB->get_record("quiz_overrides", array("id" => $event->objectid));
                    $studentroleid = $DB->get_record('role', array('shortname' => 'student'));
                    if ($quizoverrides) {
                        if ($quizoverrides->timeopen) {
                            $starttime = $quizoverrides->timeopen;
                        } else {
                            $starttime = $defaultstartdate;
                        }
                        if ($quizoverrides->timeclose) {
                            $endtime = $quizoverrides->timeclose;
                        } else {
                            $endtime = $defaultenddate;
                        }
                        if ($endtime > time()) {
                            foreach ($groupmembers as $groupkey => $user) {
                                $userid = $user->id;
                                if (user_has_role_assignment($userid, $studentroleid->id, $context->id)) {
                                    $planner = planner::create_planner_by_id($planner->id);
                                    $planner->planner_user_step($userid, $starttime, $endtime);
                                }
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * Triggered via group_override_deleted event.
     *
     * @param \mod_quiz\event\group_override_deleted $event
     */
    public static function quiz_group_override_deleted(\mod_quiz\event\group_override_deleted $event) {
        global $DB;
        $context = \context_course::instance($event->courseid);
        if ( $planner = $DB->get_record("planner", array("activitycmid" => $event->contextinstanceid))) {
            $cm = get_coursemodule_from_id('quiz', $event->contextinstanceid);
            if ($cm) {
                $quiz = $DB->get_record("quiz", array("id" => $cm->instance));
                $starttime = $quiz->timeopen;
                $endtime = $quiz->timeclose;
                $groupmembers = groups_get_members($event->other['groupid'], 'u.id');
                if ($groupmembers) {
                    $studentroleid = $DB->get_record('role', array('shortname' => 'student'));
                    if ($endtime > time()) {
                        foreach ($groupmembers as $groupkey => $user) {
                            $userid = $user->id;
                            if (user_has_role_assignment($userid, $studentroleid->id, $context->id)) {
                                $planner = planner::create_planner_by_id($planner->id);
                                $planner->planner_user_step_delete($userid, $starttime, $endtime);
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * Observer for role_assigned event.
     *
     * @param \core\event\role_assigned $event
     * @return void
     */
    public static function role_assigned(\core\event\role_assigned $event) {
        global $DB, $CFG;
        $context = \context_course::instance($event->courseid);
        $userid = $event->relateduserid;
        $studentroleid = $DB->get_record('role', array('shortname' => 'student'));
        if ($event->objectid == $studentroleid->id) {
            if ( $planners = $DB->get_records("planner", array("course" => $event->courseid))) {
                foreach ($planners as $planner) {
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
                        if ($endtime > time()) {
                            require_once($CFG->dirroot . '/mod/planner/lib.php');
                            require_once($CFG->dirroot.'/calendar/lib.php');
                            $templatestepdata = $DB->get_records_sql("SELECT * FROM {planner_step} WHERE
                            plannerid = '".$planner->id."' ORDER BY id ASC");
                            $templateuserstepdata = $DB->get_records_sql("SELECT pu.*,ps.name,ps.description
                            FROM {planner_userstep} pu JOIN {planner_step} ps ON (ps.id = pu.stepid)
                            WHERE ps.plannerid = '".$planner->id."' AND pu.userid = '".$userid."' ORDER BY pu.id ASC ");
                            $totaltime = $endtime - $starttime;
                            $exsitingsteptime = $starttime;
                            $stepsdata = array();
                            foreach ($templatestepdata as $stepkey => $stepval) {
                                $existingsteptemp = ($totaltime * $stepval->timeallocation) / 100;
                                $exsitingsteptime = $existingsteptemp + $exsitingsteptime;
                                $stepsdata[$stepkey]['name'] = $stepval->name;
                                $stepsdata[$stepkey]['timedue'] = $exsitingsteptime;
                            }
                            if (!$templateuserstepdata) {
                                foreach ($stepsdata as $stepid => $stepdata) {
                                    $insertstep = new \stdClass();
                                    $insertstep->stepid = $stepid;
                                    $insertstep->userid = $userid;
                                    $insertstep->duedate = $stepdata['timedue'];
                                    $insertstep->completionstatus = 0;
                                    $insertstep->timemodified = 0;
                                    $DB->insert_record('planner_userstep', $insertstep);
                                }
                                $planner = planner::create_planner_by_id($planner->id);
                                $planner->planner_update_events($userid, $stepsdata, false);
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * Observer for role_unassigned event.
     *
     * @param \core\event\role_unassigned $event
     * @return void
     */
    public static function role_unassigned(\core\event\role_unassigned $event) {
        global $DB, $CFG;
        $context = \context_course::instance($event->courseid);
        $userid = $event->relateduserid;
        $studentroleid = $DB->get_record('role', array('shortname' => 'student'));
        if ($event->objectid == $studentroleid->id) {
            if ( $planners = $DB->get_records("planner", array("course" => $event->courseid))) {
                foreach ($planners as $planner) {
                    $templateuserstepdata = $DB->get_records_sql("SELECT pu.* FROM {planner_userstep} pu
                        JOIN {planner_step} ps ON (ps.id = pu.stepid)
                        WHERE ps.plannerid = '".$planner->id."' AND pu.userid = '".$userid."' ORDER BY pu.id ASC ");

                    if ($templateuserstepdata) {
                        foreach ($templateuserstepdata as $step) {
                            $DB->delete_records('planner_userstep', array('id' => $step->id));
                        }
                    }
                    $DB->delete_records('event', array('instance' => $planner->id, 'modulename' => 'planner',
                    'eventtype' => 'user', 'userid' => $userid));
                }
            }
        }
    }
}

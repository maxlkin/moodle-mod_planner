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

namespace mod_planner;

defined('MOODLE_INTERNAL') || die();

use stdClass;

require_once("{$CFG->libdir}/csvlib.class.php");

/**
 * Helper class for utility functions.
 *
 * @package    mod_planner
 * @copyright  2020 onward Brickfield Education Labs Ltd, https://www.brickfield.ie
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class planner {

    /** @var int The id of the planner */
    public $id;
    /** @var int The id of the course the planner belongs to */
    public $courseid;
    /** @var string The intro text for the planner */
    public $intro;
    /** @var int The format of the intro text */
    public $introformat;
    /** @var string The name of the planner */
    public $name;
    /** @var string The disclaimer text for the planner */
    public $disclaimer;
    /** @var int The id of the activity the planner is associated with */
    public $activitycmid;
    /** @var int The step view setting for the planner */
    public $stepview;
    /** @var int The time the planner opens */
    public $timeopen;
    /** @var int The time the planner closes */
    public $timeclose;

    /**
     * Constructor
     *
     * @param int $id
     * @param int $courseid
     * @param string $intro
     * @param string $name
     * @param string $disclaimer
     * @param int $activitycmid
     * @param int $timeopen
     * @param int $timeclose
     * @param int $stepview
     * @param int $introformat
     */
    public function __construct(int $id, int $courseid, string $intro, string $name,
                                string $disclaimer, int $activitycmid, int $timeopen,
                                int $timeclose, int $stepview = 0, int $introformat = 1) {
        $this->id = $id;
        $this->courseid = $courseid;
        $this->intro = $intro;
        $this->introformat = $introformat;
        $this->name = $name;
        $this->disclaimer = $disclaimer;
        $this->activitycmid = $activitycmid;
        $this->stepview = $stepview;
        $this->timeopen = $timeopen;
        $this->timeclose = $timeclose;
    }

    /**
     * Creates a planner object by id
     *
     * @param int $id
     * @return object|null
     */
    public static function create_planner_by_id(int $id): ?object {
        global $DB;
        $record = $DB->get_record('planner', ['id' => $id]);
        if ($record) {
            $planner = new planner(
                $record->id,
                $record->course,
                $record->intro,
                $record->name,
                $record->disclaimer,
                $record->activitycmid,
                $record->timeopen,
                $record->timeclose,
                $record->stepview,
                $record->introformat
            );
        } else {
            $planner = null;
        }
        return $planner;
    }

    /**
     * Returns the Planner name
     *
     * @return string
     */
    public function get_planner_name(): string {
        $name = get_string('modulename', 'planner');
        return $name;
    }

    /**
     * Creates user steps for planner
     *
     * @param int $userid
     * @param int $starttime
     * @param int $endtime
     * @return void
     */
    public function create_user_step(int $userid, int $starttime, int $endtime): void {
        global $DB;

        $templatestepdata = $this->get_all_steps($this->id);
        $templateuserstepdata = $this->get_all_usersteps($this->id, $userid);
        $totaltime = $endtime - $starttime;
        $exsitingsteptime = $starttime;
        $stepsdata = [];
        foreach ($templatestepdata as $stepkey => $stepval) {
            $existingsteptemp = ($totaltime * $stepval->timeallocation) / 100;
            $exsitingsteptime = $existingsteptemp + $exsitingsteptime;
            $stepsdata[$stepkey]['name'] = $stepval->name;
            $stepsdata[$stepkey]['timedue'] = $exsitingsteptime;
        }
        if ($templateuserstepdata) {
            $i = 0;
            foreach ($templateuserstepdata as $stepid => $stepdata) {
                $updatestep = new stdClass();
                $updatestep->id = $stepdata->id;
                $updatestep->duedate = $stepsdata[$stepdata->stepid]['timedue'];
                if ($i == 0) {
                    $updatestep->timestart = $starttime;
                }
                $updatestep->completionstatus = 0;
                $updatestep->timemodified = 0;
                $DB->update_record('planner_userstep', $updatestep);
                $i++;
            }
        } else {
            $i = 0;
            foreach ($stepsdata as $stepid => $stepdata) {
                $insertstep = new stdClass();
                $insertstep->stepid = $stepid;
                $insertstep->userid = $userid;
                $insertstep->duedate = $stepdata['timedue'];
                if ($i == 0) {
                    $insertstep->timestart = $starttime;
                }
                $insertstep->completionstatus = 0;
                $insertstep->timemodified = 0;
                $DB->insert_record('planner_userstep', $insertstep);
                $i++;
            }
        }
        $this->update_events($userid, [], $stepsdata, false);
    }

    /**
     * Updates the user steps for a planner
     *
     * @param int $userid
     * @param int $starttime
     * @param int $endtime
     * @return void
     */
    public function update_user_step(int $userid, int $starttime, int $endtime): void {
        global $DB;

        $templatestepdata = $this->get_all_steps($this->id);
        $templateuserstepdata = $this->get_all_usersteps($this->id, $userid);
        $totaltime = $endtime - $starttime;
        $exsitingsteptime = $starttime;
        $stepsdata = [];
        foreach ($templatestepdata as $stepkey => $stepval) {
            $existingsteptemp = ($totaltime * $stepval->timeallocation) / 100;
            $exsitingsteptime = $existingsteptemp + $exsitingsteptime;
            $stepsdata[$stepkey]['name'] = $stepval->name;
            $stepsdata[$stepkey]['timedue'] = $exsitingsteptime;
        }
        if ($templateuserstepdata) {
            $i = 0;
            foreach ($templateuserstepdata as $stepid => $stepdata) {
                $updatestep = new stdClass();
                $updatestep->id = $stepdata->id;
                $updatestep->duedate = $stepsdata[$stepdata->stepid]['timedue'];
                if ($i == 0) {
                    $updatestep->timestart = null;
                }
                $updatestep->completionstatus = 0;
                $updatestep->timemodified = 0;
                $DB->update_record('planner_userstep', $updatestep);
            }
        }
        $this->update_events($userid, [], $stepsdata, false);
    }

    /**
     * Updates events for Planner activity
     *
     * @param int|null $userid
     * @param array $students
     * @param array $stepsdata
     * @param bool $alluser
     * @return void
     */
    public function update_events(?int $userid, array $students, array $stepsdata, bool $alluser = true): void {
        global $DB;

        if ($alluser) {
            $DB->delete_records('event', ['instance' => $this->id, 'modulename' => 'planner', 'eventtype' => 'due']);

            foreach ($students as $studentkey => $studentdata) {
                $i = 1;
                foreach ($stepsdata as $stepid => $stepval) {
                    $event = new stdClass();
                    $event->name = format_string($this->name);
                    $event->description = get_string('step', 'planner') . ' ' . $i . ' : ' . $stepval['name'];
                    $event->format = FORMAT_HTML;
                    $event->userid = $studentkey;
                    $event->modulename = 'planner';
                    $event->instance = $this->id;
                    $event->type = CALENDAR_EVENT_TYPE_ACTION;
                    $event->eventtype = 'due';
                    $event->timestart = round($stepval['timedue']);
                    $event->timesort = round($stepval['timedue']);
                    \calendar_event::create($event, false);
                    $i++;
                }
            }
        } else {
            $DB->delete_records('event', [
                'instance' => $this->id,
                'modulename' => 'planner',
                'eventtype' => 'due',
                'userid' => $userid
            ]);
            $i = 1;
            foreach ($stepsdata as $stepid => $stepval) {
                $event = new stdClass();
                $event->name = format_string($this->name);
                $event->description = get_string('step', 'planner') . ' ' . $i . ' : ' . $stepval['name'];
                $event->format = FORMAT_HTML;
                $event->userid = $userid;
                $event->modulename = 'planner';
                $event->instance = $this->id;
                $event->type = CALENDAR_EVENT_TYPE_ACTION;
                $event->eventtype = 'due';
                $event->timestart = round($stepval['timedue']);
                $event->timesort = round($stepval['timedue']);
                \calendar_event::create($event, false);
                $i++;
            }
        }
    }

    /**
     * Update or delete a template based on input.
     *
     * @param string|null $action
     * @param int|null $id
     * @param string $confirm
     * @param string $pageurl
     * @param int $cid
     * @return void
     */
    public static function template_crud_handler(?string $action, ?int $id, string $confirm, string $pageurl, int $cid): void {
        global $DB, $PAGE;

        $plannertemplatedata = $DB->get_record('plannertemplate', ['id' => $id]);
        $renderer = $PAGE->get_renderer('mod_planner');
        if (($action == 'delete') && confirm_sesskey()) {
            if ($plannertemplatedata) {
                if ($confirm != md5($id)) {
                    $renderer->display_template_delete_page($plannertemplatedata, $pageurl, $id, $cid);
                } else if (data_submitted()) {
                    $DB->delete_records('plannertemplate_step', ['plannerid' => $id]);
                    if ($DB->delete_records('plannertemplate', ['id' => $id])) {
                        redirect($pageurl);
                    } else {
                        redirect(
                            $pageurl,
                            get_string('deletednottemplate', 'planner', $plannertemplatedata->name),
                            null,
                            \core\output\notification::NOTIFY_ERROR
                        );
                    }
                }
            }
        } else if (($action == 'enable') && confirm_sesskey()) {
            if ($plannertemplatedata) {
                $updatestatus = new stdClass();
                $updatestatus->id = $id;
                $updatestatus->status = 1;
                $DB->update_record('plannertemplate', $updatestatus);
            }
        } else if (($action == 'disable') && confirm_sesskey()) {
            if ($plannertemplatedata) {
                $updatestatus = new stdClass();
                $updatestatus->id = $id;
                $updatestatus->status = 0;
                $DB->update_record('plannertemplate', $updatestatus);
            }
        }
    }

    /**
     * Create a the search form.
     *
     * @param int $cid
     * @return array
     */
    public static function create_template_search_form(int $cid): array {
        $mform = new \mod_planner\form\search(null, ['cid' => $cid]);

        /** @var cache_session $cache */
        $cache = \cache::make_from_params(\cache_store::MODE_SESSION, 'mod_planner', 'search');
        if ($cachedata = $cache->get('data')) {
            $mform->set_data($cachedata);
        }

        $searchclauses = '';

        // Check if we have a form submission, or a cached submission.
        $data = ($mform->is_submitted() ? $mform->get_data() : fullclone($cachedata));
        if ($data instanceof stdClass) {
            if (!empty($data->planner_searchgroup['setting'])) {
                $searchclauses = $data->planner_searchgroup['setting'];
            }
            // Cache form submission so that it is preserved while paging through the report.
            unset($data->submitbutton);
            $cache->set('data', $data);
        }
        return ['searchclauses' => $searchclauses, 'mform' => $mform];
    }

    /**
     * Updates records for templates and template steps.
     *
     * @param object $templatedata
     * @return void
     */
    public static function update_planner_template_step(object $templatedata): void {
        global $DB;

        // Update case.
        $updatetemplate = new stdClass();
        $updatetemplate->id = $templatedata->id;
        $updatetemplate->name = $templatedata->name;
        $disclaimer = $templatedata->disclaimer;
        $updatetemplate->disclaimer = $disclaimer['text'];
        $updatetemplate->personal = $templatedata->personal;
        $updatetemplate->timemodified = time();
        if ($DB->update_record('plannertemplate', $updatetemplate)) {
            $DB->delete_records('plannertemplate_step', ['plannerid' => $templatedata->id]);
            $stepnames = $templatedata->stepname;
            $stepstepallocations = $templatedata->stepallocation;
            $stepstepdescriptions = $templatedata->stepdescription;
            if ($templatedata->option_repeats > 0) {
                for ($i = 0; $i < $templatedata->option_repeats; $i++) {
                    if ($stepnames[$i]) {
                        $insertrecord = new stdClass();
                        $insertrecord->plannerid = $templatedata->id;
                        $insertrecord->name = $stepnames[$i];
                        $insertrecord->timeallocation = $stepstepallocations[$i];
                        $description = $stepstepdescriptions[$i];
                        $insertrecord->description = $description['text'];
                        $DB->insert_record('plannertemplate_step', $insertrecord);
                    }
                }
            }
        }
    }

    /**
     * Inserts records for templates and template steps
     *
     * @param object $templatedata
     * @return void
     */
    public static function insert_planner_template_step(object $templatedata): void {
        global $DB, $USER;

        // Insert case.
        $inserttemplate = new stdClass();
        $inserttemplate->userid = $USER->id;
        $inserttemplate->name = $templatedata->name;
        $disclaimer = $templatedata->disclaimer;
        $inserttemplate->disclaimer = $disclaimer['text'];
        $inserttemplate->status = 1;
        $inserttemplate->personal = $templatedata->personal;
        $inserttemplate->timecreated = time();
        if ($insertedtemplateid = $DB->insert_record('plannertemplate', $inserttemplate)) {
            $stepnames = $templatedata->stepname;
            $stepstepallocations = $templatedata->stepallocation;
            $stepstepdescriptions = $templatedata->stepdescription;
            if ($templatedata->option_repeats > 0) {
                for ($i = 0; $i < $templatedata->option_repeats; $i++) {
                    if ($stepnames[$i]) {
                        $insertrecord = new stdClass();
                        $insertrecord->plannerid = $insertedtemplateid;
                        $insertrecord->name = $stepnames[$i];
                        $insertrecord->timeallocation = $stepstepallocations[$i];
                        $description = $stepstepdescriptions[$i];
                        $insertrecord->description = $description['text'];
                        $DB->insert_record('plannertemplate_step', $insertrecord);
                    }
                }
            }
        }
    }

    /**
     * Get a template and template steps.
     *
     * @param int $id
     * @return array
     */
    public static function get_planner_template_step(int $id): array {
        global $DB;

        $plannertemplate = $DB->get_record('plannertemplate', ['id' => $id]);
        $plannertemplatesteps = $DB->get_records('plannertemplate_step', ['plannerid' => $id], 'id ASC');
        return ['plannertemplate' => $plannertemplate, 'plannertemplatesteps' => $plannertemplatesteps];
    }

    /**
     * Returns the students and groups for a course.
     *
     * @param string $group
     * @param object $course
     * @param object $context
     * @param object $coursecontext
     * @param int $groupuserid
     *
     * @return array
     */
    public function get_students_and_groups(
        string $group,
        object $course,
        object $context,
        object $coursecontext,
        int $groupuserid
    ): array {
        global $DB;

        // Apply group restrictions.
        $params = [];
        $groupidnums = [];

        if (has_capability('moodle/site:accessallgroups', $context)) {
            $groupuserid = 0;
        }
        $groupjoin = '';
        if ((substr($group, 0, 6) == 'group-') && ($groupid = intval(substr($group, 6)))) {
            $groupjoin = 'JOIN {groups_members} g ON (g.groupid = :groupselected AND g.userid = u.id)';
            $params['groupselected'] = $groupid;
        } else if ((substr($group, 0, 9) == 'grouping-') && ($groupingid = intval(substr($group, 9)))) {
            $groupjoin = 'JOIN {groups_members} g ON ' .
                '(g.groupid IN (SELECT DISTINCT groupid FROM {groupings_groups} WHERE groupingid = :groupingselected) ' .
                'AND g.userid = u.id)';
            $params['groupingselected'] = $groupingid;
        } else if ($groupuserid != 0 && !empty($groupidnums)) {
            $groupjoin = 'JOIN {groups_members} g ON (g.groupid IN (' . implode(',', $groupidnums) . ') AND g.userid = u.id)';
        }

        // Get the list of students enrolled in the course.
        $sql = "SELECT u.*
                  FROM {user} u
                  JOIN {role_assignments} a ON (a.contextid = :contextid AND a.userid = u.id)
                  JOIN {role} r ON (r.id = a.roleid AND r.archetype = :archetype)
                $groupjoin";
        $params['contextid'] = $coursecontext->id;
        $params['courseid'] = $course->id;
        $params['archetype'] = 'student';

        $userrecords = $DB->get_records_sql($sql, $params);
        $students = array_values($userrecords);
        return $students;
    }

    /**
     * Downloads the report as a CSV file.
     *
     * @param int $plannersteps
     * @param array $students
     * @param object $course
     * @return void
     */
    public function export_report_to_csv(int $plannersteps, array $students, object $course): void {
        $modinfo = get_fast_modinfo($course);
        foreach ($modinfo->instances as $modulename => $modinstances) {
            foreach ($modinstances as $cm) {
                if ($cm->id == $this->activitycmid) {
                    $cmname = $cm->name;
                }
            }
        }

        $export = new \csv_export_writer();
        $export->set_filename(format_string($this->name) . "_" . $cmname);
        $row = [];
        $row[] = get_string('studentname', 'planner');
        $row[] = get_string('email', 'planner');
        for ($i = 1; $i <= $plannersteps; $i++) {
            $row[] = get_string('stepnumber', 'planner', $i);
        }
        $export->add_data($row);
        if ($students) {
            foreach ($students as $studentdata) {
                $usersteps = $this->get_all_usersteps($this->id, $studentdata->id);
                $row = [];
                if ($studentdata->idnumber) {
                    $row[] = fullname($studentdata) . ' (' . $studentdata->idnumber . ')';
                } else {
                    $row[] = fullname($studentdata);
                }
                $row[] = $studentdata->email;
                foreach ($usersteps as $step) {
                    if ($step->completionstatus == '1') {
                        $row[] = get_string('completed', 'planner');
                    } else {
                        $row[] = get_string('pending', 'planner');
                    }
                }
                $export->add_data($row);
            }
        }
        $export->download_file();
    }

    /**
     * Returns the planner times.
     *
     * @param object $cm
     * @return object
     */
    public function get_planner_times(object $cm): object {
        global $DB, $USER;
        $sql = 'SELECT cm.id,cm.instance,cm.module,m.name
                  FROM {course_modules} cm
                  JOIN {modules} m ON (m.id = cm.module)
                 WHERE cm.id = :cmid';
        $cminfoactivity = $DB->get_record_sql($sql, ['cmid' => $this->activitycmid]);

        if (!$cminfoactivity) {
            throw new \moodle_exception(
                'relatedactivitynotexistdelete',
                'planner',
                new \moodle_url("/course/view.php?id=$this->courseid")
            );
        }

        $modinfo = get_fast_modinfo($this->courseid);
        foreach ($modinfo->instances as $modname => $modinstances) {
            foreach ($modinstances as $cmnew) {
                if ($cmnew->deletioninprogress == 0 && $cmnew->id == $this->activitycmid) {
                    $modulename = $DB->get_record($cminfoactivity->name, ['id' => $cminfoactivity->instance]);
                } else if ($cmnew->deletioninprogress == 1 && $cmnew->id == $this->activitycmid) {
                    throw new \moodle_exception(
                        'relatedactivitynotexistdelete',
                        'planner',
                        new \moodle_url("/course/view.php?id=$this->courseid")
                    );
                }
            }
        }

        $time = new stdClass();
        if ($cminfoactivity->name == 'assign') {
            // Check if the user has overrides for the assignment.
            if ($overrides = $DB->get_record('assign_overrides', ['assignid' => $modulename->id, 'userid' => $USER->id])) {
                if ($overrides->allowsubmissionsfromdate) {
                    $time->defaultstarttime = $overrides->allowsubmissionsfromdate;
                } else {
                    $time->defaultstarttime = $modulename->allowsubmissionsfromdate;
                }
                if ($overrides->duedate) {
                    $time->endtime = $overrides->duedate;
                } else {
                    $time->endtime = $modulename->duedate;
                }
            } else {
                $time->defaultstarttime = $modulename->allowsubmissionsfromdate;
                $time->endtime = $modulename->duedate;
            }
            $time->starttime = $modulename->allowsubmissionsfromdate;
            $time->defaultendtime = $modulename->duedate;
        } else if ($cminfoactivity->name == 'quiz') {
            // Check if the user has overrides for the quiz.
            if ($overrides = $DB->get_record('quiz_overrides', ['quiz' => $modulename->id, 'userid' => $USER->id])) {
                if ($overrides->timeopen) {
                    $time->defaultstarttime = $overrides->timeopen;
                } else {
                    $time->defaultstarttime = $modulename->timeopen;
                }
                if ($overrides->timeclose) {
                    $time->endtime = $overrides->timeclose;
                } else {
                    $time->endtime = $modulename->timeclose;
                }
            } else {
                $time->defaultstarttime = $modulename->timeopen;
                $time->endtime = $modulename->timeclose;
            }
            $time->starttime = $modulename->timeopen;
            $time->defaultendtime = $modulename->timeclose;
        }

        $sql = 'SELECT pu.*
                  FROM {planner_userstep} pu
                  JOIN {planner_step} ps ON (ps.id = pu.stepid)
                 WHERE ps.plannerid = :plannerid AND pu.userid = :userid ORDER BY pu.id ';
        $time->userstartdate = $DB->get_record_sql($sql . 'ASC LIMIT 1', ['plannerid' => $cm->instance, 'userid' => $USER->id]);
        $time->userenddate = $DB->get_record_sql($sql . 'DESC LIMIT 1', ['plannerid' => $cm->instance, 'userid' => $USER->id]);

        if ($time->userstartdate) {
            if ($time->userstartdate->timestart) {
                $time->starttime = $time->userstartdate->timestart;
            }
        }

        $datediff = $time->endtime - $time->starttime;
        $time->days = round($datediff / (60 * 60 * 24));
        return $time;
    }

    /**
     * Handles the CRUD actions for userstep table.
     *
     * @param string|null $action
     * @param string $redirecturl
     * @param object $context
     * @param object $cm
     * @return object
     */
    public function crud_handler(?string $action, string $redirecturl, object $context, object $cm): object {
        global $DB, $USER;

        $templatestepdata = $this->get_all_steps($this->id);
        $templateuserstepdata = $this->get_all_usersteps($cm->instance, $USER->id);
        $time = $this->get_planner_times($cm);
        if (($action == "studentsteps") || ($action == "recalculatesteps")) {
            if ($action == "recalculatesteps") {
                $updaterecord = new stdClass();
                $updaterecord->id = $this->id;
                $updaterecord->timeopen = $time->starttime;
                $updaterecord->timeclose = $time->endtime;
                $DB->update_record('planner', $updaterecord);
            }

            $totaltime = $time->endtime - $time->starttime;
            $exsitingsteptime = $time->starttime;
            $stepsdata = [];
            foreach ($templatestepdata as $stepkey => $stepval) {
                $existingsteptemp = ($totaltime * $stepval->timeallocation) / 100;
                $exsitingsteptime = $existingsteptemp + $exsitingsteptime;
                $stepsdata[$stepkey]['name'] = $stepval->name;
                $stepsdata[$stepkey]['timedue'] = $exsitingsteptime;
            }

            $coursecontext = \context_course::instance($this->courseid);
            $studentroleids = $DB->get_records('role', ['archetype' => 'student']);
            $students = [];
            foreach ($studentroleids as $studentroleid) {
                $students[] = get_role_users($studentroleid->id, $coursecontext);
            }
            $students = reset($students);
            if ($students) {
                foreach ($students as $studentkey => $studentdata) {
                    foreach ($stepsdata as $stepid => $stepval) {
                        $DB->delete_records('planner_userstep', ['stepid' => $stepid, 'userid' => $studentkey]);

                        $insertstudentstep = new stdClass();
                        $insertstudentstep->stepid = $stepid;
                        $insertstudentstep->duedate = round($stepval['timedue']);
                        $insertstudentstep->userid = $studentkey;
                        $insertstudentstep->completionstatus = 0;
                        $DB->insert_record('planner_userstep', $insertstudentstep);
                    }
                }
                $this->update_events(null, $students, $stepsdata, true);
                if ($action == "recalculatesteps") {
                    redirect(
                        $redirecturl,
                        get_string('recalculatedstudentsteps', 'planner'),
                        null,
                        \core\output\notification::NOTIFY_SUCCESS
                    );
                } else {
                    redirect($redirecturl, get_string('studentstepupdated', 'planner'), null,
                        \core\output\notification::NOTIFY_SUCCESS);
                }
            }
        }
        if ($action == "stepsubmit") {
            $stepid = required_param('stepid', PARAM_INT);
            $uncheckstep = optional_param('uncheckstep', 0, PARAM_INT);

            $checkexistingstep = $DB->get_record('planner_userstep', ['id' => $stepid, 'userid' => $USER->id]);

            if ($checkexistingstep) {
                $updateuserstep = new stdClass();
                $updateuserstep->id = $checkexistingstep->id;
                $updateuserstep->userid = $USER->id;
                if ($uncheckstep == 1) {
                    $updateuserstep->completionstatus = 0;
                } else {
                    $updateuserstep->completionstatus = 1;
                }
                $updateuserstep->timemodified = time();
                $DB->update_record('planner_userstep', $updateuserstep);

                $params = [
                    'objectid' => $this->id,
                    'relateduserid' => $USER->id,
                    'courseid' => $this->courseid,
                    'context' => $context,
                    'other' => [
                        'plannerid' => $this->id,
                        'stepid' => $checkexistingstep->stepid,
                        'stepname' => $templatestepdata[$checkexistingstep->stepid]->name
                    ]
                ];
                if ($uncheckstep == 1) {
                    $event = \mod_planner\event\step_pending::create($params);
                    $event->trigger();
                    redirect(
                        $redirecturl,
                        get_string('studentstepmarkpending', 'planner'),
                        null,
                        \core\output\notification::NOTIFY_SUCCESS
                    );
                } else {
                    $event = \mod_planner\event\step_completed::create($params);
                    $event->trigger();
                    redirect(
                        $redirecturl,
                        get_string('studentstepmarkcompleted', 'planner'),
                        null,
                        \core\output\notification::NOTIFY_SUCCESS
                    );
                }
            }
        }

        $data = new stdClass();
        $data->templateuserstepdata = $templateuserstepdata;
        $data->templatestepdata = $templatestepdata;

        return $data;
    }

    /**
     * Create the planner user form.
     *
     * @param object $data
     * @param int $id
     * @param object $cm
     * @param object $course
     * @param object $context
     * @param string $redirecturl
     * @return object
     */
    public function create_planner_user_form($data, $id, $cm, $course, $context, $redirecturl) {
        global $DB, $USER;

        $time = $this->get_planner_times($cm);
        $templateform = new \mod_planner\form\user_form(
            'view.php',
            [
                'id' => $id,
                'startdate' => $time->defaultstarttime,
                'studentstartime' => $time->starttime,
                'enddate' => $time->endtime
            ]
        );

        if ($templatedata = $templateform->get_data()) {
            $plannerid = $templatedata->id;
            $starttime = $templatedata->userstartdate;
            $totaltime = $time->endtime - $starttime;
            $exsitingsteptime = $starttime;
            $stepsdata = [];
            foreach ($data->templatestepdata as $stepkey => $stepval) {
                $existingsteptemp = ($totaltime * $stepval->timeallocation) / 100;
                $exsitingsteptime = $existingsteptemp + $exsitingsteptime;
                $stepsdata[$stepkey]['name'] = $stepval->name;
                $stepsdata[$stepkey]['timedue'] = $exsitingsteptime;
            }
            if ($data->templateuserstepdata) {
                $i = 0;
                foreach ($data->templateuserstepdata as $stepid => $stepdata) {
                    $updatestep = new stdClass();
                    $updatestep->id = $stepdata->id;
                    $updatestep->duedate = $stepsdata[$stepdata->stepid]['timedue'];
                    if ($i == 0) {
                        $updatestep->timestart = $starttime;
                    }
                    $updatestep->completionstatus = 0;
                    $updatestep->timemodified = 0;
                    $DB->update_record('planner_userstep', $updatestep);
                    $i++;
                }
                $this->update_events($USER->id, [], $stepsdata, false);

                $params = [
                    'objectid' => $this->id,
                    'relateduserid' => $USER->id,
                    'courseid' => $course->id,
                    'context' => $context,
                    'other' => [
                        'plannerid' => $this->id,
                    ]
                ];
                $event = \mod_planner\event\step_updated::create($params);
                $event->trigger();

                redirect($redirecturl, get_string('studentstepupdated', 'planner'), null,
                    \core\output\notification::NOTIFY_SUCCESS);
            }
        }
        return $templateform;
    }

    /**
     * Returns a list of created templates.
     *
     * @param object $table
     * @param string $searchclauses
     * @param int $perpage
     * @param bool $mytemplates
     * @return object
     */
    public static function get_templatelist(object $table, string $searchclauses, int $perpage, bool $mytemplates = false): object {
        global $USER, $DB;

        $isadmin = has_capability('moodle/site:config', \context_system::instance());
        $params = ['userid' => $USER->id];
        $wheres = [];
        $wheresearch = "";
        $whereteacher = "";
        $where = "";
        $select = 'SELECT pt.*,u.firstname,u.lastname,u.middlename,u.firstnamephonetic,u.lastnamephonetic,u.alternatename';
        $from = "FROM {plannertemplate} pt LEFT JOIN {user} u ON (u.id = pt.userid)";
        if (!$isadmin) {
            $whereteacher = "((pt.userid = :userid AND pt.personal = 1) OR (pt.personal = 0))";
        }
        if ($searchclauses) {
            $wheres[] = $DB->sql_like('name', ':search1', false, false);
            $wheres[] = $DB->sql_like('firstname', ':search2', false, false);
            $wheres[] = $DB->sql_like('lastname', ':search3', false, false);
            $params['search1'] = "%$searchclauses%";
            $params['search2'] = "%$searchclauses%";
            $params['search3'] = "%$searchclauses%";
            $wheresearch = implode(" OR ", $wheres);
        }
        if ($mytemplates && $wheresearch) {
            $where = "WHERE (pt.userid = :userid) AND (" . $wheresearch . ")";
        } else if ($mytemplates) {
            $where = "WHERE pt.userid = :userid";
        } else if ($whereteacher && $wheresearch) {
            $where = "WHERE " . $whereteacher . " AND " . $wheresearch;
        } else if ($wheresearch) {
            $where = "WHERE " . $wheresearch;
        } else if ($whereteacher) {
            $where = "WHERE " . $whereteacher;
        }
        if ($table->get_sql_sort()) {
            $sort = ' ORDER BY ' . $table->get_sql_sort();
        } else {
            $sort = '';
        }
        $matchcount = $DB->count_records_sql("SELECT COUNT(pt.id) $from $where", $params);

        $table->pagesize($perpage, $matchcount);

        $templatelist = $DB->get_recordset_sql(
            "$select $from $where $sort",
            $params,
            $table->get_page_start(),
            $table->get_page_size()
        );
        return $templatelist;
    }

    /**
     * Validate the form data.
     *
     * @param array $data
     * @return array
     */
    public static function validation(array $data): array {
        global $DB;
        $errors = [];
        if (isset($data['submitbutton'])) {
            if (isset($data['stepname'])) {
                $stepname = $data['stepname'][0];
                $stepallocation = $data['stepallocation'][0];
                $totalsteps = count($data['stepallocation']);
                $totaltimeallocation = 0;
                for ($i = 0; $i <= $totalsteps; $i++) {
                    if (isset($data['stepname'][$i]) && (!empty($data['stepname'][$i]))) {
                        if (isset($data['stepallocation'][$i])) {
                            $totaltimeallocation = $totaltimeallocation + $data['stepallocation'][$i];
                        }
                    }
                }
                if (!$stepname) {
                    $errors['stepname[0]'] = get_string('required');
                }
                $name = $data['name'];
                $nameunique = $DB->get_records('plannertemplate', ['name' => $name]);
                if ($nameunique) {
                    $errors['name'] = get_string('templatenameunique', 'planner');
                }
                if (!$stepallocation) {
                    $errors['stepallocation[0]'] = get_string('required');
                }
                if ($totaltimeallocation != '100') {
                    for ($i = 0; $i <= $totalsteps; $i++) {
                        if (isset($data['stepname'][$i]) && (!empty($data['stepname'][$i]))) {
                            if (isset($data['stepallocation'][$i])) {
                                $errors['stepallocation['.$i.']'] = get_string('totaltimeallocated', 'planner');
                            }
                        }
                    }
                }
            }
        }
        return $errors;
    }

    /**
     * Get all planner steps.
     *
     * @param int $plannerid
     * @return array
     */
    public static function get_all_steps(int $plannerid): array {
        global $DB;
        return $DB->get_records('planner_step', ['plannerid' => $plannerid], 'id ASC');
    }

    /**
     * Get all planner steps.
     *
     * @param int $plannerid
     * @param int $userid
     * @return array
     */
    public static function get_all_usersteps(int $plannerid, int $userid): array {
        global $DB;
        $sql = 'SELECT pu.*,ps.name,ps.description
                  FROM {planner_userstep} pu
                  JOIN {planner_step} ps ON (ps.id = pu.stepid)
                 WHERE ps.plannerid = :plannerid AND pu.userid = :userid
              ORDER BY pu.id ASC';
        $params = [
            'plannerid' => $plannerid,
            'userid' => $userid,
        ];
        return $DB->get_records_sql($sql, $params);
    }

    /**
     * Checks if a user is assigned to a role of a specified archetype.
     *
     * @param int $userid
     * @param string $archetype
     * @param int $contextid
     * @return bool
     */
    public static function user_has_archetype(int $userid, string $archetype, int $contextid): bool {
        $students = get_archetype_roles($archetype);
        $hasrole = false;
        foreach ($students as $student) {
            if (user_has_role_assignment($userid, $student->id, $contextid)) {
                $hasrole = true;
            }
        }
        return $hasrole;
    }
}

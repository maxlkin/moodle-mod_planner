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
 * Library of functions and constants for module planner
 *
 * @copyright 2021 Brickfield Education Labs, www.brickfield.ie
 * @package mod_planner
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;
require_once($CFG->dirroot.'/calendar/lib.php');

/**
 * Returns the Planner name
 *
 * @param object $planner
 * @return string
 */
function get_planner_name($planner) {
    $name = get_string('modulename', 'planner');
    return $name;
}
/**
 * Given an object containing all the necessary data,
 * (defined by the form in mod_form.php) this function
 * will create a new instance and return the id number
 * of the new instance.
 *
 * @param object $planner
 * @return bool|int
 */
function planner_add_instance($planner) {
    global $DB, $CFG, $SESSION;

    if ((!isset($planner->submitbutton2)) AND (!isset($planner->submitbutton))) {
        $url = $CFG->wwwroot.'/course/modedit.php?add=planner&type=';
        $url .= '&course='.$planner->course.'&section='.$planner->section.'&return='.$planner->return.'&sr='.$planner->sr;
        if (isset($planner->activitycmid)) {
            $url .= '&activitycmid='.$planner->activitycmid;
        }
        if (isset($planner->templateid)) {
            $url .= '&templateid='.$planner->templateid;
        }
        if (isset($planner->name)) {
            $url .= '&name='.$planner->name;
        }
        if (isset($planner->introformat)) {
            $url .= '&introformat='.$planner->introformat;
        }
        if (isset($planner->introformat)) {
            $url .= '&introformat='.$planner->introformat;
        }
        if (isset($planner->stepview)) {
            $url .= '&stepview='.$planner->stepview;
        }
        redirect($url);
    }
    if ($planner->activitycmid == 0) {
        throw new moodle_exception('actionnotassociated', 'planner');
    }
    if ($planner->disclaimer) {
        $planner->disclaimer = $planner->disclaimer['text'];
    }
    $planner->timemodified = time();

    $cminfoactivity = $DB->get_record_sql("SELECT cm.id,cm.instance,cm.module,m.name FROM {course_modules} cm
    JOIN {modules} m ON (m.id = cm.module) WHERE cm.id = '".$planner->activitycmid."'");
    if ($cminfoactivity) {
        $modulename = $DB->get_record($cminfoactivity->name, array('id' => $cminfoactivity->instance));
    } else {
        throw new moodle_exception('relatedactivitynotexistdelete', 'planner',
            new moodle_url("/course/view.php?id=$planner->course"));
    }
    if ($cminfoactivity->name == 'assign') {
        $planner->timeopen = $modulename->allowsubmissionsfromdate;
        $planner->timeclose = $modulename->duedate;
    } else if ($cminfoactivity->name == 'quiz') {
        $planner->timeopen = $modulename->timeopen;
        $planner->timeclose = $modulename->timeclose;
    }

    $id = $DB->insert_record("planner", $planner);
    if ($id) {
        // Increase template counter.
        $template = $DB->get_record("plannertemplate", array('id' => $planner->templateid));
        $updatetemplate = new stdClass();
        $updatetemplate->id = $template->id;
        if (!isset($template->copied)) {
            $template->copied = 0;
        }
        $updatetemplate->copied = $template->copied + 1;
        $DB->update_record("plannertemplate", $updatetemplate);

        // Insert steps in DB.
        $stepnames = $planner->stepname;
        $stepstepallocations = $planner->stepallocation;
        $stepstepdescriptions = $planner->stepdescription;
        if ($planner->option_repeats > 0) {
            for ($i = 0; $i < $planner->option_repeats; $i++) {
                if ($stepnames[$i]) {
                    $insertrecord = new stdClass();
                    $insertrecord->plannerid = $id;
                    $insertrecord->name = $stepnames[$i];
                    $insertrecord->timeallocation = $stepstepallocations[$i];
                    $description = $stepstepdescriptions[$i];
                    $insertrecord->description = $description['text'];
                    $DB->insert_record('planner_step', $insertrecord);
                }
            }
        }
    }

    $completiontimeexpected = !empty($planner->completionexpected) ? $planner->completionexpected : null;
    \core_completion\api::update_completion_date_event($planner->coursemodule, 'planner', $id, $completiontimeexpected);
    return $id;
}

/**
 * Given an object containing all the necessary data,
 * (defined by the form in mod_form.php) this function
 * will update an existing instance with new data.
 *
 * @param object $planner
 * @return bool
 */
function planner_update_instance($planner) {
    global $DB;

    $planner->disclaimer = $planner->disclaimer['text'];
    $planner->timemodified = time();
    $planner->id = $planner->instance;

    $oldplannersteps = $DB->get_records('planner_step', array('plannerid' => $planner->id));
    $createnewsteps = false;
    if ($oldplannersteps) {
        $oldtimeallocations = array_values($oldplannersteps);
        for ($i = 0; $i < $planner->option_repeats; $i++) {
            $stepnames = $planner->stepname;
            $stepstepallocations = $planner->stepallocation;
            if (($stepnames[$i]) && (!empty($stepnames[$i]))) {
                if (isset($oldtimeallocations[$i]->timeallocation)) {
                    if ($stepstepallocations[$i] != $oldtimeallocations[$i]->timeallocation) {
                        $createnewsteps = true;
                    }
                } else {
                    $createnewsteps = true;
                }
            }
        }
    } else {
        $createnewsteps = true;
    }
    $completiontimeexpected = !empty($planner->completionexpected) ? $planner->completionexpected : null;
    \core_completion\api::update_completion_date_event($planner->coursemodule, 'planner', $planner->id, $completiontimeexpected);

    if ($createnewsteps) {
        if ($stepsdata = $DB->get_records("planner_step", array("plannerid" => $planner->id))) {
            foreach ($stepsdata as $step) {
                $DB->delete_records('planner_userstep', array('stepid' => $step->id));
            }
            $DB->delete_records('planner_step', array('plannerid' => $planner->id));
            $DB->delete_records('event', array('instance' => $planner->id, 'modulename' => 'planner', 'eventtype' => 'user'));
        }

        for ($i = 0; $i < $planner->option_repeats; $i++) {
            $insertrecord = new stdClass();
            $insertrecord->plannerid = $planner->id;
            $stepnames = $planner->stepname;
            $stepstepallocations = $planner->stepallocation;
            $stepstepdescriptions = $planner->stepdescription;
            if (($stepnames[$i]) && (!empty($stepnames[$i]))) {
                $insertrecord->name = $stepnames[$i];
                $insertrecord->timeallocation = $stepstepallocations[$i];
                $description = $stepstepdescriptions[$i];
                $insertrecord->description = $description['text'];
                $DB->insert_record('planner_step', $insertrecord);
            }
        }
    } else {
        for ($i = 0; $i < $planner->option_repeats; $i++) {
            $updaterecord = new stdClass();
            $updaterecord->id = $oldtimeallocations[$i]->id;
            $updaterecord->plannerid = $planner->id;
            $stepnames = $planner->stepname;
            $stepstepallocations = $planner->stepallocation;
            $stepstepdescriptions = $planner->stepdescription;
            if (($stepnames[$i]) && (!empty($stepnames[$i]))) {
                $updaterecord->name = $stepnames[$i];
                $description = $stepstepdescriptions[$i];
                $updaterecord->description = $description['text'];
                $DB->update_record('planner_step', $updaterecord);
            }
        }
    }
    return $DB->update_record("planner", $planner);
}

/**
 * Given an ID of an instance of this module,
 * this function will permanently delete the instance
 * and any data that depends on it.
 *
 * @param int $id
 * @return bool
 */
function planner_delete_instance($id) {
    global $DB;

    if (! $planner = $DB->get_record("planner", array("id" => $id))) {
        return false;
    }

    $result = true;

    if ($stepsdata = $DB->get_records("planner_step", array("plannerid" => $planner->id))) {
        foreach ($stepsdata as $step) {
            $DB->delete_records('planner_userstep', array('stepid' => $step->id));
        }
    }

    $cm = get_coursemodule_from_instance('planner', $id);
    \core_completion\api::update_completion_date_event($cm->id, 'planner', $planner->id, null);

    if (! $DB->delete_records("planner_step", array("plannerid" => $planner->id))) {
        $result = false;
    }

    $DB->delete_records('event', array('instance' => $planner->id, 'modulename' => 'planner', 'eventtype' => 'user'));

    if (! $DB->delete_records("planner", array("id" => $planner->id))) {
        $result = false;
    }
    return $result;
}

/**
 * extend an assigment navigation settings
 *
 * @param settings_navigation $settings
 * @param navigation_node $navref
 * @return void
 */
function planner_extend_settings_navigation(settings_navigation $settings, navigation_node $navref) {
    global $PAGE, $DB;

    // We want to add these new nodes after the Edit settings node, and before the
    // Locally assigned roles node. Of course, both of those are controlled by capabilities.
    $keys = $navref->get_children_key_list();
    $beforekey = null;
    $i = array_search('modedit', $keys);
    if ($i === false and array_key_exists(0, $keys)) {
        $beforekey = $keys[0];
    } else if (array_key_exists($i + 1, $keys)) {
        $beforekey = $keys[$i + 1];
    }

    $cm = $PAGE->cm;
    if (!$cm) {
        return;
    }

    $context = $cm->context;
    $course = $PAGE->course;

    if (!$course) {
        return;
    }

    if (has_capability('mod/planner:manageplanner', $cm->context)) {
        $link = new moodle_url('/mod/planner/report.php', array('id' => $cm->id));
        $linkname = get_string('report', 'planner');
        $node = $navref->add($linkname, $link, navigation_node::TYPE_SETTING);
    }
}

/**
 * This function extends the navigation with the report items
 *
 * @param navigation_node $navigation The navigation node to extend
 * @param stdClass $course The course to object for the report
 * @param stdClass $context The context of the course
 */
function mod_planner_extend_navigation_course($navigation, $course, $context) {
    global $CFG;

    if (has_capability('mod/planner:managetemplates', $context)) {
        $url = new moodle_url('/mod/planner/template.php', array('cid' => $course->id));
        $navigation->add(get_string('manage_templates', 'planner'), $url,
            navigation_node::TYPE_CUSTOM, get_string('manage_templates', 'planner'));
    }
}
/**
 * Given a course_module object, this function returns any
 * "extra" information that may be needed when printing
 * this activity in a course listing.
 * See get_array_of_activities() in course/lib.php
 *
 * @param object $coursemodule
 * @return cached_cm_info|null
 */
function planner_get_coursemodule_info($coursemodule) {
    global $DB;

    $dbparams = ['id' => $coursemodule->instance];
    $fields = 'id, activitycmid, name, intro, introformat';
    if (!$planner = $DB->get_record('planner', $dbparams, $fields)) {
        return false;
    }

    $result = new cached_cm_info();
    $result->name = $planner->name;

    if ($coursemodule->showdescription) {
        // Convert intro to html. Do not filter cached version, filters run at display time.
        $result->content = format_module_intro('planner', $planner, $coursemodule->id, false);
    }
    return $result;
}

/**
 * Check if the current planner being viewed has cm_info
 *
 * @param cm_info $cm
 * @return void
 */
function planner_cm_info_view(cm_info $cm) {
    global $DB, $USER;

    $dbparams = ['id' => $cm->instance];
    $fields = 'id, activitycmid, name, intro, introformat, stepview';
    if (!$planner = $DB->get_record('planner', $dbparams, $fields)) {
        return false;
    }
    if (($planner->stepview == '1') OR ($planner->stepview == '2')) {
        $templatestepdata = $DB->get_records_sql("SELECT pu.*,ps.name,ps.description FROM {planner_userstep} pu
        JOIN {planner_step} ps ON (ps.id = pu.stepid) WHERE ps.plannerid = '".$cm->instance."'
        AND pu.userid = '".$USER->id."' ORDER BY pu.id ASC ");

        if ($templatestepdata) {
            $i = 0;
            $duetime = 0;
            $taskname = '';
            $taskdescription = '';
            $completed = true;
            foreach ($templatestepdata as $step => $stepdata) {
                $i++;
                if ($stepdata->completionstatus == '0') {
                    $duetime = $stepdata->duedate;
                    $taskname = $stepdata->name;
                    $taskdescription = $stepdata->description;
                    $completed = false;
                    break;
                }
            }
            if ($completed) {
                $userhtml = '<br/>'.get_string('alltaskscompleted', 'planner');
            } else {
                $userhtml = '<br/>'.get_string('step', 'planner').' '.$i.' - ';
                $currenttime = time();
                if ($duetime >= $currenttime) {
                    $datediff = $duetime - $currenttime;
                    $days = round($datediff / (60 * 60 * 24));
                    $userhtml .= get_string('duein', 'planner', $days);
                } else {
                    $datediff = $currenttime - $duetime;
                    $days = round($datediff / (60 * 60 * 24));
                    $userhtml .= get_string('missedbefore', 'planner', $days);
                }
                $userhtml .= '<br/>'.$taskname;
                if ($planner->stepview == '2') {
                    $userhtml .= '<br/>'.$taskdescription;
                }
            }
            $cm->set_after_link($userhtml);
        }
    }
}

/**
 * This function is used by the reset_course_userdata function in moodlelib.
 *
 * @param object $data the data submitted from the reset course.
 * @return array status array
 */
function planner_reset_userdata($data) {

    // Any changes to the list of dates that needs to be rolled should be same during course restore and course reset.
    // See MDL-9367.

    return array();
}

/**
 * Creates user steps for planner
 *
 * @param object $planner
 * @param int $userid
 * @param int $starttime
 * @param int $endtime
 * @return void
 */
function planner_user_step($planner, $userid, $starttime, $endtime) {
    global $DB;

    $templatestepdata = $DB->get_records_sql("SELECT * FROM {planner_step} WHERE plannerid = '".$planner->id."' ORDER BY id ASC");
    $templateuserstepdata = $DB->get_records_sql("SELECT pu.*,ps.name,ps.description FROM {planner_userstep} pu
JOIN {planner_step} ps ON (ps.id = pu.stepid)
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
    planner_update_events($planner, '', $userid, $stepsdata, false);
}

/**
 * Deleting a user step for a planner
 *
 * @param object $planner
 * @param int $userid
 * @param int $starttime
 * @param int $endtime
 * @return void
 */
function planner_user_step_delete ($planner, $userid, $starttime, $endtime) {
    global $DB;

    $templatestepdata = $DB->get_records_sql("SELECT * FROM {planner_step} WHERE plannerid = '".$planner->id."' ORDER BY id ASC");
    $templateuserstepdata = $DB->get_records_sql("SELECT pu.*,ps.name,ps.description FROM {planner_userstep} pu
JOIN {planner_step} ps ON (ps.id = pu.stepid)
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
    planner_update_events($planner, '', $userid, $stepsdata, false);
}

/**
 * Updates events for Planner activity
 *
 * @param object $planner
 * @param null $override
 * @param object $students
 * @param object $stepsdata
 * @param boolean $alluser
 * @return void
 */
function planner_update_events($planner, $override = null, $students, $stepsdata, $alluser = true) {
    global $DB;

    if ($alluser) {
        $DB->delete_records('event', array('instance' => $planner->id, 'modulename' => 'planner', 'eventtype' => 'due'));

        foreach ($students as $studentkey => $studentdata) {
            $i = 1;
            foreach ($stepsdata as $stepid => $stepval) {
                $event = new stdClass();
                $event->name = format_string($planner->name);
                $event->description = get_string('step', 'planner').' '.$i.' : '.$stepval['name'];
                $event->format = FORMAT_HTML;
                $event->userid = $studentkey;
                $event->modulename = 'planner';
                $event->instance = $planner->id;
                $event->type = CALENDAR_EVENT_TYPE_ACTION;
                $event->eventtype = 'due';
                $event->timestart = $stepval['timedue'];
                $event->timesort = $stepval['timedue'];
                calendar_event::create($event, false);
                $i++;
            }
        }
    } else {
        $DB->delete_records('event', array('instance' => $planner->id, 'modulename' => 'planner',
        'eventtype' => 'due', 'userid' => $students));
        $i = 1;
        foreach ($stepsdata as $stepid => $stepval) {
            $event = new stdClass();
            $event->name = format_string($planner->name);
            $event->description = get_string('step', 'planner').' '.$i.' : '.$stepval['name'];
            $event->format = FORMAT_HTML;
            $event->userid = $students;
            $event->modulename = 'planner';
            $event->instance = $planner->id;
            $event->type = CALENDAR_EVENT_TYPE_ACTION;
            $event->eventtype = 'due';
            $event->timestart = $stepval['timedue'];
            $event->timesort = $stepval['timedue'];
            calendar_event::create($event, false);
            $i++;
        }
    }
}

/**
 * Checks the features the Planner currently supports
 *
 * @uses FEATURE_IDNUMBER
 * @uses FEATURE_GROUPS
 * @uses FEATURE_GROUPINGS
 * @uses FEATURE_MOD_INTRO
 * @uses FEATURE_COMPLETION_TRACKS_VIEWS
 * @uses FEATURE_GRADE_HAS_GRADE
 * @uses FEATURE_GRADE_OUTCOMES
 * @param string $feature FEATURE_xx constant for requested feature
 * @return bool|null True if module supports feature, false if not, null if doesn't know
 */
function planner_supports($feature) {
    switch($feature) {
        case FEATURE_IDNUMBER:
            return true;
        case FEATURE_GROUPS:
            return true;
        case FEATURE_GROUPINGS:
            return true;
        case FEATURE_MOD_INTRO:
            return true;
        case FEATURE_COMPLETION_TRACKS_VIEWS:
            return true;
        case FEATURE_COMPLETION_HAS_RULES:
            return true;
        case FEATURE_GRADE_HAS_GRADE:
            return true;
        case FEATURE_GRADE_OUTCOMES:
            return true;
        case FEATURE_BACKUP_MOODLE2:
            return true;
        case FEATURE_SHOW_DESCRIPTION:
            return true;
        case FEATURE_CONTROLS_GRADE_VISIBILITY:
            return true;

        default:
            return null;
    }
}

/**
 * Check if the module has any update that affects the current user since a given time.
 *
 * @param  cm_info $cm course module data
 * @param  int $from the time to check updates from
 * @param  array $filter  if we need to check only specific updates
 * @return stdClass an object with the different type of areas indicating if they were updated or not
 * @since Moodle 3.2
 */
function planner_check_updates_since(cm_info $cm, $from, $filter = array()) {
    $updates = course_check_module_updates_since($cm, $from, array(), $filter);
    return $updates;
}

/**
 * This function receives a calendar event and returns the action associated with it, or null if there is none.
 *
 * This is used by block_myoverview in order to display the event appropriately. If null is returned then the event
 * is not displayed on the block.
 *
 * @param calendar_event $event
 * @param \core_calendar\action_factory $factory
 * @param int $userid User id to use for all capability checks, etc. Set to 0 for current user (default).
 * @return \core_calendar\local\event\entities\action_interface|null
 */
function mod_planner_core_calendar_provide_event_action(calendar_event $event,
                                                      \core_calendar\action_factory $factory,
                                                      int $userid = 0) {
    $cm = get_fast_modinfo($event->courseid, $userid)->instances['planner'][$event->instance];

    if (!$cm->uservisible) {
        // The module is not visible to the user for any reason.
        return null;
    }

    $completion = new \completion_info($cm->get_course());

    $completiondata = $completion->get_data($cm, false, $userid);

    if ($completiondata->completionstate != COMPLETION_INCOMPLETE) {
        return null;
    }

    return $factory->create_instance(
        get_string('view'),
        new \moodle_url('/mod/planner/view.php', ['id' => $cm->id]),
        1,
        true
    );
}

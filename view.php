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
 * planner module
 *
 * @copyright 2021 Brickfield Education Labs, www.brickfield.ie
 * @package mod_planner
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once("../../config.php");

$id = optional_param('id', 0, PARAM_INT);    // Course Module ID, or.
$l = optional_param('l', 0, PARAM_INT);     // Planner ID.
$action = optional_param('action', '', PARAM_RAW);

if ($id) {
    $PAGE->set_url('/mod/planner/view.php', array('id' => $id));
    if (! $cm = get_coursemodule_from_id('planner', $id)) {
        throw new moodle_exception('invalidcoursemodule');
    }

    if (! $course = $DB->get_record("course", array("id" => $cm->course))) {
        throw new moodle_exception('coursemisconf');
    }

    if (! $planner = $DB->get_record("planner", array("id" => $cm->instance))) {
        throw new moodle_exception('invalidplanner', 'planner');
    }

    if (! $planner->activitycmid) {
        throw new moodle_exception('actionnotassociated', 'planner');
    }

} else {
    $PAGE->set_url('/mod/planner/view.php', array('l' => $l));
    if (! $planner = $DB->get_record("planner", array("id" => $l))) {
        throw new moodle_exception('invalidcoursemodule');
    }
    if (! $course = $DB->get_record("course", array("id" => $planner->course))) {
        throw new moodle_exception('coursemisconf');
    }
    if (! $cm = get_coursemodule_from_instance("planner", $planner->id, $course->id)) {
        throw new moodle_exception('invalidcoursemodule');
    }
}

$cminfoactivity = $DB->get_record_sql("SELECT cm.id,cm.instance,cm.module,m.name FROM {course_modules} cm
JOIN {modules} m ON (m.id = cm.module) WHERE cm.id = '".$planner->activitycmid."'");

$modinfo = get_fast_modinfo($course);
foreach ($modinfo->instances as $modname => $modinstances) {
    foreach ($modinstances as $cmnew) {
        if($cmnew->deletioninprogress == 0 && $cmnew->id == $planner->activitycmid) {
            $modulename = $DB->get_record($cminfoactivity->name, array('id' => $cminfoactivity->instance));
        } else if($cmnew->deletioninprogress == 1 && $cmnew->id == $planner->activitycmid){
            throw new moodle_exception('relatedactivitynotexistdelete', 'planner', new moodle_url("/course/view.php?id=$planner->course"));
        }
    }
}



require_login($course, true, $cm);
$context = context_module::instance($cm->id);
$redirecturl = new moodle_url("/mod/planner/view.php?id=$id");
$templatestepdata = $DB->get_records_sql("SELECT * FROM {planner_step} WHERE plannerid = '".$planner->id."' ORDER BY id ASC");

if (($action == "studentsteps") OR ($action == "recalculatesteps")) {
    if ($cminfoactivity->name == 'assign') {
        $starttime = $modulename->allowsubmissionsfromdate;
        $endtime = $modulename->duedate;
    } else if ($cminfoactivity->name == 'quiz') {
        $starttime = $modulename->timeopen;
        $endtime = $modulename->timeclose;
    }
    if ($action == "recalculatesteps") {
        $updaterecord = new stdClass();
        $updaterecord->id = $planner->id;
        $updaterecord->timeopen = $starttime;
        $updaterecord->timeclose = $endtime;
        $DB->update_record('planner', $updaterecord);
    }

    $totaltime = $endtime - $starttime;
    $exsitingsteptime = $starttime;
    $stepsdata = array();
    foreach ($templatestepdata as $stepkey => $stepval) {
        $existingsteptemp = ($totaltime * $stepval->timeallocation) / 100;
        $exsitingsteptime = $existingsteptemp + $exsitingsteptime;
        $stepsdata[$stepkey]['name'] = $stepval->name;
        $stepsdata[$stepkey]['timedue'] = $exsitingsteptime;
    }

    $coursecontext = context_course::instance($course->id);
    $studentroleid = $DB->get_record('role', array('shortname' => 'student'));
    $students = get_role_users($studentroleid->id, $coursecontext);
    if ($students) {
        foreach ($students as $studentkey => $studentdata) {
            foreach ($stepsdata as $stepid => $stepval) {
                $DB->delete_records('planner_userstep', array('stepid' => $stepid, 'userid' => $studentkey));

                $insertstudentstep = new stdClass();
                $insertstudentstep->stepid = $stepid;
                $insertstudentstep->duedate = $stepval['timedue'];
                $insertstudentstep->userid = $studentkey;
                $insertstudentstep->completionstatus = 0;
                $DB->insert_record('planner_userstep', $insertstudentstep);
            }
        }
        planner_update_events($planner, '', $students, $stepsdata, true);
        if ($action == "recalculatesteps") {
            redirect($redirecturl, get_string('recalculatedstudentsteps', 'planner'),
            null, \core\output\notification::NOTIFY_SUCCESS);
        } else {
            redirect($redirecturl, get_string('studentstepupdated', 'planner'), null, \core\output\notification::NOTIFY_SUCCESS);
        }
    }
}
if ($action == "stepsubmit") {
    $stepid = required_param('stepid', PARAM_INT);
    $uncheckstep = optional_param('uncheckstep', 0, PARAM_INT);

    $checkexistingstep = $DB->get_record_sql("SELECT * from {planner_userstep}
    WHERE id = '".$stepid."' AND userid = '".$USER->id."'");

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

        $params = array(
            'objectid' => $planner->id,
            'relateduserid' => $USER->id,
            'courseid' => $course->id,
            'context' => $context,
            'other' => array(
                'plannerid' => $planner->id,
                'stepid' => $checkexistingstep->stepid,
                'stepname' => $templatestepdata[$checkexistingstep->stepid]->name
            )
        );
        if ($uncheckstep == 1) {
            $event = \mod_planner\event\step_pending::create($params);
            $event->trigger();
            redirect($redirecturl, get_string('studentstepmarkpending', 'planner'),
            null, \core\output\notification::NOTIFY_SUCCESS);
        } else {
            $event = \mod_planner\event\step_completed::create($params);
            $event->trigger();
            redirect($redirecturl, get_string('studentstepmarkcompleted', 'planner'),
            null, \core\output\notification::NOTIFY_SUCCESS);
        }
    }
}

if ($cminfoactivity->name == 'assign') {
    $starttime = $modulename->allowsubmissionsfromdate;
    $endtime = $modulename->duedate;
    $defaultstarttime = $modulename->allowsubmissionsfromdate;
    $defaultendtime = $modulename->duedate;
} else if ($cminfoactivity->name == 'quiz') {
    $starttime = $modulename->timeopen;
    $endtime = $modulename->timeclose;
    $defaultstarttime = $modulename->timeopen;
    $defaultendtime = $modulename->timeclose;
}
$userstartdate = $DB->get_record_sql("SELECT pu.* FROM {planner_userstep} pu JOIN {planner_step} ps ON (ps.id = pu.stepid)
 WHERE ps.plannerid = '".$cm->instance."' AND pu.userid = '".$USER->id."' ORDER BY pu.id ASC LIMIT 1");

if ($userstartdate) {
    if ($userstartdate->timestart) {
        $starttime = $userstartdate->timestart;
    }
}
$userenddate = $DB->get_record_sql("SELECT pu.* FROM {planner_userstep} pu JOIN {planner_step} ps ON (ps.id = pu.stepid)
 WHERE ps.plannerid = '".$cm->instance."' AND pu.userid = '".$USER->id."' ORDER BY pu.id DESC LIMIT 1");

$datediff = $endtime - $starttime;
$days = round($datediff / (60 * 60 * 24));
$templateuserstepdata = $DB->get_records_sql("SELECT pu.*,ps.name,ps.description FROM {planner_userstep} pu
 JOIN {planner_step} ps ON (ps.id = pu.stepid)
 WHERE ps.plannerid = '".$cm->instance."' AND pu.userid = '".$USER->id."' ORDER BY pu.id ASC ");


require_once(__DIR__.'/user_form.php');
$templateform = new user_form('view.php',
    array('id' => $id, 'startdate' => $defaultstarttime,
    'studentstartime' => $starttime, 'enddate' => $defaultendtime));

if ($templatedata = $templateform->get_data()) {
    if ($cminfoactivity->name == 'assign') {
        $endtime = $modulename->duedate;
    } else if ($cminfoactivity->name == 'quiz') {
        $endtime = $modulename->timeclose;
    }
    $plannerid = $templatedata->id;
    $starttime = $templatedata->userstartdate;
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
        planner_update_events($planner, '', $USER->id, $stepsdata, false);

        $params = array(
            'objectid' => $planner->id,
            'relateduserid' => $USER->id,
            'courseid' => $course->id,
            'context' => $context ,
            'other' => array(
                'plannerid' => $planner->id,
            )
        );
        $event = \mod_planner\event\step_updated::create($params);
        $event->trigger();

        redirect($redirecturl, get_string('studentstepupdated', 'planner'), null, \core\output\notification::NOTIFY_SUCCESS);
    }
}

$PAGE->set_context($context);
$PAGE->set_title($course->shortname.":".format_string($planner->name));
$PAGE->set_heading($course->fullname);


$PAGE->requires->jquery_plugin('ui-css');

echo $OUTPUT->header();
echo $OUTPUT->heading(format_string($planner->name));

if (!html_is_blank($planner->intro)) {
    echo $OUTPUT->box(format_module_intro('planner', $planner, $cm->id), 'generalbox', 'intro');
}

$params = array('id' => $cm->id);
$printurl = new moodle_url('/mod/planner/print.php', $params);
$printtitle = get_string('printerfriendly', 'glossary');
$printattributes = array(
            'class' => 'printicon',
            'title' => $printtitle
);
echo '<div style="text-align: right">';
echo html_writer::link($printurl, $printtitle, $printattributes);
echo '</div>';

$j = 0;
if ($templateuserstepdata) {
    foreach ($templateuserstepdata as $step => $stepdata) {
        if ($stepdata->completionstatus == '0') {
            break;
        }
        $j++;
    }
}

$PAGE->requires->js_call_amd('mod_planner/planner', 'initialise', [$j]);

echo '<h3>'.get_string('plannerdefaultstartingon', 'planner').' : '
.userdate($defaultstarttime, get_string('strftimedatefullshort')).'</h3>';
echo '<h3>'.get_string('plannerdefaultendingon', 'planner').' : '.userdate($endtime, get_string('strftimedatefullshort')).'</h3>';
if (isset($userstartdate->timestart)) {
    echo '<h3>'.get_string('startingon', 'planner').' : '
    .userdate($starttime, get_string('strftimedatefullshort')).'</h3>';
    if (isset($userenddate->duedate)) {
        echo '<h3>'.get_string('endingon', 'planner').' : '
        .userdate($userenddate->duedate, get_string('strftimedatefullshort')).'</h3>';
    }
}

echo '<br/>';
echo '<p><b>'.get_string('daysinstruction', 'planner', $days).'</b></p>';

if (has_capability('mod/planner:manageplanner', $context)) {

    $html = '<div id="accordion">';
    $totaltime = $endtime - $starttime;
    $exsitingsteptime = $starttime;
    $stepsdata = array();
    foreach ($templatestepdata as $stepkey => $stepval) {
        $existingsteptemp = ($totaltime * $stepval->timeallocation) / 100;
        $exsitingsteptime = $existingsteptemp + $exsitingsteptime;
        $stepsdata[$stepkey]['timedue'] = $exsitingsteptime;
    }
    $i = 1;
    foreach ($templatestepdata as $stepdata) {
        $html .= '<h3 class="step-header">'. '<div class="stepname">' . get_string('step', 'planner').' '.$i.' - '.$stepdata->name.'</div>'.'
        <div class="stepdate">'.get_string('enddate', 'planner').' : '
        .userdate($stepsdata[$stepdata->id]['timedue'], get_string('strftimedatefullshort')).' ';
        $html .= '</div></h3>';
        $html .= '<div class="">'.$stepdata->description;
        $html .= '</div>';
        $i++;
    }
    echo $html;
    echo '</div>';
} else {
    if ($templateuserstepdata) {
        $html = '<div class="row"><div class="col-md-7">';
        $html .= '<div id="accordion">';
        $i = 1;
        foreach ($templateuserstepdata as $stepdata) {
            $html .= '<h3 class="step-header">'. '<div class="stepname">' . get_string('step', 'planner').' '.$i.' - '.$stepdata->name.'</div>'.'
        <div class="stepdate">'.get_string('enddate', 'planner').' : '
        .userdate($stepdata->duedate, get_string('strftimedatefullshort')).' ';
            if ($stepdata->completionstatus == '1') {
                $html .= $OUTPUT->pix_icon('i/checked', 'Completed');
            }
            $html .= '</div></h3>';
            $html .= '<div class="">'.$stepdata->description;
            if ($i == $j + 1) {
                $html .= '<br/><form action="view.php" method="post" >
                <input type="hidden" name="id" value="'.$id.'">
                <input type="hidden" name="action" value="stepsubmit">
                <input type="checkbox" class="" required name="stepid" value="'.$stepdata->id.'"> '
                .get_string('markstepascomplete', 'planner').'
                <input type="submit" class="btn btn-primary" value="'.get_string('submit', 'planner').'"></form>';
            }
            if ($stepdata->completionstatus == '1') {
                $html .= '<br/><form action="view.php" method="post" >
                <input type="hidden" name="id" value="'.$id.'">
                <input type="hidden" name="action" value="stepsubmit">
                <input type="hidden" name="uncheckstep" value="1">
                <input type="hidden" name="stepid" value="'.$stepdata->id.'">
                <input type="checkbox" class="" checked  value="'.$stepdata->id.'">'
                .get_string('markstepaspending', 'planner').'
                <input type="submit" class="btn btn-primary" value="'.get_string('submit', 'planner').'"></form>';
            }
            $html .= '</div>';
            $i++;
        }
        echo $html;
        echo '<br/>';
        echo '</div></div><div class="col-md-5"><h2>'.get_string('differentdates', 'planner').'</h2>';
        echo $templateform->display();
        echo '</div>';
        echo '</div>';
    } else {
        echo '<h3 style="text-align:center;">'.get_string('stepsyettobe', 'planner').'</h3>';
    }
}
if ($planner->disclaimer) {
    echo '<h3>'.get_string('disclaimer', 'planner').'</h3>';
    echo $planner->disclaimer;
}
if (has_capability('mod/planner:manageplanner', $context)) {
    if (($starttime != $planner->timeopen) OR ($endtime != $planner->timeclose)) {
        echo '<br/>';
        echo '<div style="text-align:center">';
        echo $OUTPUT->single_button(new moodle_url('view.php', array('id' => $id, 'action' => 'recalculatesteps')),
        get_string('recalculatestudentsteps', 'planner'));
        echo '</div>';
    } else {
        $checkalreadycompleted = $DB->count_records_sql("SELECT count(pu.id) FROM {planner_userstep} pu
        JOIN {planner_step} ps ON (ps.id = pu.stepid) WHERE ps.plannerid = '".$planner->id."'");
        if ($checkalreadycompleted == 0) {
            echo '<br/>';
            echo '<div style="text-align:center">';
            echo $OUTPUT->single_button(new moodle_url('view.php', array('id' => $id, 'action' => 'studentsteps')),
            get_string('calculatestudentsteps', 'planner'));
            echo '</div>';
        }
    }
}
echo $OUTPUT->footer();

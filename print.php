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

require_once("../../config.php");
require_once("lib.php");

$id = required_param('id', PARAM_INT);  // Course Module ID.
$url = new moodle_url('/mod/print/print.php', array('id' => $id));

$PAGE->set_url($url);
if (! $cm = get_coursemodule_from_id('planner', $id)) {
    throw new moodle_exception('invalidcoursemodule');
}
if (! $course = $DB->get_record("course", array("id" => $cm->course))) {
    throw new moodle_exception('coursemisconf');
}
if (! $planner = $DB->get_record("planner", array("id" => $cm->instance))) {
    throw new moodle_exception('invalidid', 'planner');
}

$cminfoactivity = $DB->get_record_sql("SELECT cm.id,cm.instance,cm.module,m.name FROM {course_modules} cm
 JOIN {modules} m ON (m.id = cm.module) WHERE cm.id = '".$planner->activitycmid."'");
if ($cminfoactivity) {
    $modulename = $DB->get_record($cminfoactivity->name, array('id' => $cminfoactivity->instance));
} else {
    throw new moodle_exception('relatedactivitynotexistdelete', 'planner', new moodle_url("/course/view.php?id=$planner->course"));
}

$templatestepdata = $DB->get_records_sql("SELECT * FROM {planner_step} WHERE plannerid = '".$planner->id."' ORDER BY id ASC");

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

$templateuserstepdata = $DB->get_records_sql("SELECT pu.*,ps.name,ps.description FROM {planner_userstep} pu JOIN {planner_step}
 ps ON (ps.id = pu.stepid) WHERE ps.plannerid = '".$cm->instance."' AND pu.userid = '".$USER->id."' ORDER BY pu.id ASC ");

$j = 0;
if ($templateuserstepdata) {
    foreach ($templateuserstepdata as $step => $stepdata) {
        if ($stepdata->completionstatus == '0') {
            break;
        }
        $j++;
    }
}

require_course_login($course, true, $cm);
$context = context_module::instance($cm->id);

// Prepare format_string/text options.
$fmtoptions = array('context' => $context);
$PAGE->set_pagelayout('print');
$PAGE->set_title($course->shortname.":".format_string($planner->name));
$PAGE->set_heading($course->fullname);
echo $OUTPUT->header();

$site = $DB->get_record("course", array("id" => 1));

$printtext = get_string('print', 'planner');
$printlinkatt = array('onclick' => 'window.print();return false;', 'class' => 'planner_no_print printicon');
$printiconlink = html_writer::link('#', $printtext, $printlinkatt);
echo  html_writer::tag('div', $printiconlink, array('class' => 'displayprinticon'));

echo html_writer::tag('div', userdate(time()), array('class' => 'displaydate'));

$sitename = get_string("site") . ': <span class="strong">' . format_string($site->fullname) . '</span>';
echo html_writer::tag('div', $sitename, array('class' => 'sitename'));

$coursename = get_string("course") . ': <span class="strong">' . format_string($course->fullname)
. ' ('. format_string($course->shortname) . ')</span>';
echo html_writer::tag('div', $coursename, array('class' => 'coursename'));

$modname = get_string("modulename", "planner") . ': <span class="strong">' . format_string($planner->name, true) . '</span>';
echo html_writer::tag('div', $modname, array('class' => 'modname'));

if (!html_is_blank($planner->intro)) {
    $plannerdescription = get_string("description", "planner") . ':
    <span class="strong">' . format_string(format_module_intro('planner', $planner, $cm->id), true) . '</span>';
    echo html_writer::tag('div', $plannerdescription, array('class' => 'modname'));
}
$plannerstart = get_string('plannerdefaultstartingon', 'planner').' : <span class="strong">'
.userdate($defaultstarttime, get_string('strftimedatefullshort')).'</span>';
echo html_writer::tag('div', $plannerstart);

$plannerend = get_string('plannerdefaultendingon', 'planner').' : <span class="strong">'
.userdate($endtime, get_string('strftimedatefullshort')).'</span>';
echo html_writer::tag('div', $plannerend);

if (isset($userstartdate->timestart)) {
    $planneruserstart = get_string('startingon', 'planner').' : <span class="strong">'
    .userdate($starttime, get_string('strftimedatefullshort')).'</span>';
    echo html_writer::tag('div', $planneruserstart);

    if (isset($userenddate->duedate)) {
        $plannerend = get_string('endingon', 'planner').' : <span class="strong">'
        .userdate($userenddate->duedate, get_string('strftimedatefullshort')).'</span>';
        echo html_writer::tag('div', $plannerend);
    }
}



$plannerday = '<p><b>'.get_string('daysinstruction', 'planner', $days).'</b></p>';
echo html_writer::tag('div', $plannerday);

if (has_capability('mod/planner:manageplanner', $context)) {

    $adminprint = '';
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
        $adminprint .= '<h4 class="step-header">'. '<div class="stepname">' . get_string('step', 'planner').' '.$i.' - '.$stepdata->name.'</div>'.'
        <div class="stepdate">'.get_string('enddate', 'planner').' : '
        .userdate($stepsdata[$stepdata->id]['timedue'], get_string('strftimedatefullshort')).' ';
        $adminprint .= '</div></h4>';

        $adminprint .= $stepdata->description;
        $i++;
    }
    echo html_writer::tag('div', $adminprint);
} else {
    if ($templateuserstepdata) {
        $i = 1;
        $userprint = '';
        foreach ($templateuserstepdata as $stepdata) {
            $userprint .= '<h4 class="step-header">'. '<div class="stepname">' . get_string('step', 'planner').' '.$i.' - '.$stepdata->name.'</div>'.'
            <div class="stepdate">'.get_string('enddate', 'planner').' : '
            .userdate($stepdata->duedate, get_string('strftimedatefullshort')).' ';
            if ($stepdata->completionstatus == '1') {
                $userprint .= $OUTPUT->pix_icon('i/checked', get_string('completed', 'planner'));
            }
            $userprint .= '</div></h4>';
            $userprint .= $stepdata->description;
            $i++;
        }
        echo html_writer::tag('div', $userprint);
    }
}

if ($planner->disclaimer) {
    $disclaimer = '<h4>'.get_string('disclaimer', 'planner').'</h4>';
    $disclaimer .= $planner->disclaimer;
    echo html_writer::tag('div', $disclaimer);
}
echo $OUTPUT->footer();

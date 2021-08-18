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
 * @package mod_planner
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__.'/../../config.php');
require_once(__DIR__.'/template_form.php');
require_once($CFG->libdir . '/adminlib.php');
$id = optional_param('id', '0', PARAM_INT);   // Templated id.
$totalsteps = optional_param('totalsteps', '0', PARAM_INT);   // Total steps.
$cid = optional_param('cid', 0, PARAM_INT);

if ($cid) {
    if (! $course = $DB->get_record("course", array("id" => $cid))) {
        throw new moodle_exception('coursemisconf');
    }
    require_login($course);
    $context = context_course::instance($course->id);
    navigation_node::override_active_url(new moodle_url('/mod/planner/template.php', array('cid' => $cid)));
    $PAGE->set_heading($course->fullname);
    $PAGE->set_url(new moodle_url('/mod/planner/template.php', array('cid' => $cid)));
    $PAGE->set_context($context);
} else {
    require_login(0, false);
    $context = context_system::instance();
    $PAGE->set_context($context);
    admin_externalpage_setup('planner/template');
}

$redirecturl = new moodle_url("/mod/planner/template.php", array('cid' => $cid));

$PAGE->set_url('/mod/planner/managetemplate.php', array('id' => $id, 'cid' => $cid));

$templatedata = array();
$templatestepdata = array();
if ($id) {
    if (!$templatedata = $DB->get_record('plannertemplate', array('id' => $id))) {
        throw new moodle_exception('invalidtemplate', 'planner');
    }
    $templatestepdata = $DB->get_records_sql("SELECT * FROM {plannertemplate_step} WHERE plannerid = '".$id."' ORDER BY id ASC");
}

if ($id) {
    $PAGE->set_title("{$SITE->shortname}");
    $PAGE->set_heading(get_string('edittemplate', 'planner'));
} else {
    $PAGE->set_title("{$SITE->shortname}: ".get_string('addtemplate', 'planner'));
    $PAGE->set_heading("{$SITE->shortname}");
}

$templateform = new template_form('managetemplate.php', array('id' => $id, 'cid' => $cid,
'templatedata' => $templatedata, 'templatestepdata' => $templatestepdata));

if ($templateform->is_cancelled()) {
    redirect($redirecturl);
} else if ($templatedata = $templateform->get_data()) {
    if ($templatedata->id) {
        // Update case.
        $updatetemplate = new stdClass();
        $updatetemplate->id = $templatedata->id;
        $updatetemplate->name = $templatedata->name;
        $disclaimer = $templatedata->disclaimer;
        $updatetemplate->disclaimer = $disclaimer['text'];
        $updatetemplate->personal = $templatedata->personal;
        $updatetemplate->timemodified = time();
        if ($DB->update_record('plannertemplate', $updatetemplate)) {
            $DB->delete_records('plannertemplate_step', array('plannerid' => $templatedata->id));
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
        redirect($redirecturl, get_string('successfullyupdated', 'planner'), null, \core\output\notification::NOTIFY_SUCCESS);
    } else {
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
        redirect($redirecturl, get_string('successfullyadded', 'planner'), null, \core\output\notification::NOTIFY_SUCCESS);
    }
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('newtemplate', 'planner'));

$templateform->display();

echo $OUTPUT->footer();

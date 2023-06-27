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

use mod_planner\planner;

require(__DIR__.'/../../config.php');
require_once($CFG->libdir . '/adminlib.php');
$id = optional_param('id', '0', PARAM_INT);   // Templated id.
$totalsteps = optional_param('totalsteps', '0', PARAM_INT);   // Total steps.
$cid = optional_param('cid', 0, PARAM_INT);

if ($cid) {
    if (! $course = $DB->get_record("course", ["id" => $cid])) {
        throw new moodle_exception('coursemisconf');
    }
    require_login($course);
    $context = context_course::instance($course->id);
    navigation_node::override_active_url(new moodle_url('/mod/planner/template.php', ['cid' => $cid]));
    $PAGE->set_heading($course->fullname);
    $PAGE->set_url(new moodle_url('/mod/planner/template.php', ['cid' => $cid]));
    $PAGE->set_context($context);
} else {
    require_login(0, false);
    $context = context_system::instance();
    $PAGE->set_context($context);
    admin_externalpage_setup('planner/template');
}

$redirecturl = new moodle_url("/mod/planner/template.php", ['cid' => $cid]);

$PAGE->set_url('/mod/planner/managetemplate.php', ['id' => $id, 'cid' => $cid]);

$templatedata = [];
$templatestepdata = [];
if ($id) {
    if (!$templatedata = $DB->get_record('plannertemplate', ['id' => $id])) {
        throw new moodle_exception('invalidtemplate', 'planner');
    }
    $templatestepdata = $DB->get_records('plannertemplate_step', ['plannerid' => $id], 'id ASC');
}

if ($id) {
    $PAGE->set_title("{$SITE->shortname}");
    $PAGE->set_heading(get_string('edittemplate', 'planner'));
} else {
    $PAGE->set_title("{$SITE->shortname}: ".get_string('addtemplate', 'planner'));
    $PAGE->set_heading("{$SITE->shortname}");
}

$templateform = new mod_planner\form\template_form(
    'managetemplate.php',
    ['id' => $id, 'cid' => $cid, 'templatedata' => $templatedata, 'templatestepdata' => $templatestepdata]
);

if ($templateform->is_cancelled()) {
    redirect($redirecturl);
} else if ($templatedata = $templateform->get_data()) {
    if ($templatedata->id) {
        planner::update_planner_template_step($templatedata);
        redirect($redirecturl, get_string('successfullyupdated', 'planner'), null, \core\output\notification::NOTIFY_SUCCESS);
    } else {
        planner::insert_planner_template_step($templatedata);
        redirect($redirecturl, get_string('successfullyadded', 'planner'), null, \core\output\notification::NOTIFY_SUCCESS);
    }
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('newtemplate', 'planner'));

$templateform->display();

echo $OUTPUT->footer();

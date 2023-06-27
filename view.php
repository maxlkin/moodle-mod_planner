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

use mod_planner\planner;

$id = optional_param('id', 0, PARAM_INT);    // Course Module ID, or.
$plannerid = optional_param('l', 0, PARAM_INT);     // Planner ID.
$action = optional_param('action', '', PARAM_RAW);

if ($id) {
    $PAGE->set_url('/mod/planner/view.php', ['id' => $id]);
    if (! $cm = get_coursemodule_from_id('planner', $id)) {
        throw new moodle_exception('invalidcoursemodule');
    }

    if (! $course = $DB->get_record("course", ["id" => $cm->course])) {
        throw new moodle_exception('coursemisconf');
    }

    if (! $planner = planner::create_planner_by_id($cm->instance)) {
        throw new moodle_exception('invalidplanner', 'planner');
    }

    if (! $planner->activitycmid) {
        throw new moodle_exception('actionnotassociated', 'planner');
    }

} else {
    $PAGE->set_url('/mod/planner/view.php', ['l' => $plannerid]);
    if (! $planner = planner::create_planner_by_id($cm->instance)) {
        throw new moodle_exception('invalidcoursemodule');
    }
    if (! $course = $DB->get_record("course", ["id" => $planner->courseid])) {
        throw new moodle_exception('coursemisconf');
    }
    if (! $cm = get_coursemodule_from_instance("planner", $planner->id, $course->id)) {
        throw new moodle_exception('invalidcoursemodule');
    }
}

require_login($course, true, $cm);
$context = context_module::instance($cm->id);
$redirecturl = new moodle_url("/mod/planner/view.php?id=$id");

$data = $planner->crud_handler($action, $redirecturl, $context, $cm);
$form = $planner->create_planner_user_form($data, $id, $cm, $course, $context, $redirecturl);

$PAGE->set_context($context);
$PAGE->set_title($course->shortname.":".format_string($planner->name));
$PAGE->set_heading($course->fullname);
$PAGE->requires->jquery_plugin('ui-css');

$renderer = $PAGE->get_renderer('mod_planner');

$output = $renderer->display_planner($planner, $cm, $data, $context, $form, $id);
echo $output;

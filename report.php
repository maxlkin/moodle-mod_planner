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
require_once($CFG->libdir . '/tablelib.php');
$id     = optional_param('id', '0', PARAM_INT);   // Templated id.
$format = optional_param('format', '', PARAM_RAW);
$group  = optional_param('group', 0, PARAM_ALPHANUMEXT); // Group selected.

if (! $cm = get_coursemodule_from_id('planner', $id)) {
    throw new moodle_exception('invalidcoursemodule');
}
if (! $course = $DB->get_record("course", ["id" => $cm->course])) {
    throw new moodle_exception('coursemisconf');
}
if (! $planner = planner::create_planner_by_id($cm->instance)) {
    throw new moodle_exception('invalidcoursemodule');
}

require_login($course, true, $cm);

$context = context_module::instance($cm->id);

if (!has_capability('mod/planner:manageplanner', $context)) {
    throw new moodle_exception('invalidpermission');
}

$PAGE->set_context($context);

$title = get_string('report', 'planner');
$PAGE->set_title($course->shortname.":".format_string($planner->name).":".$title);
$PAGE->set_heading($title);
$PAGE->set_pagelayout('report');

$pageurl = new moodle_url("/mod/planner/report.php");

$PAGE->set_url('/mod/planner/report.php', ['id' => $id]);

$plannersteps = $DB->count_records('planner_step', ['plannerid' => $planner->id]);
$coursecontext = \context_course::instance($course->id);

$groupuserid = $USER->id;
$students = $planner->get_students_and_groups($group, $course, $context, $coursecontext, $groupuserid);

if ($format) {
    $planner->export_report_to_csv($plannersteps, $students, $course);
}

$renderer = $PAGE->get_renderer('mod_planner');
$renderer->display_report_group_dropdown($course, $groupuserid, $group, $planner);
$output = $renderer->display_report_table($plannersteps, $planner, $pageurl, $students, $course, $id, $group);
echo $output;

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
 * @copyright  2021 Brickfield Education Labs, www.brickfield.ie
 * @package    mod_planner
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once("../../config.php");
require_once("lib.php");

use mod_planner\planner;

$id = required_param('id', PARAM_INT);  // Course Module ID.
$url = new moodle_url('/mod/print/print.php', array('id' => $id));

$PAGE->set_url($url);
if (! $cm = get_coursemodule_from_id('planner', $id)) {
    throw new moodle_exception('invalidcoursemodule');
}
if (! $course = $DB->get_record("course", array("id" => $cm->course))) {
    throw new moodle_exception('coursemisconf');
}
if (! $planner = planner::create_planner_by_id($cm->instance)) {
    throw new moodle_exception('invalidid', 'planner');
}

require_course_login($course, true, $cm);
$context = context_module::instance($cm->id);

// Prepare format_string/text options.
$fmtoptions = array('context' => $context);
$PAGE->set_pagelayout('print');
$PAGE->set_title($course->shortname.":".format_string($planner->name));
$PAGE->set_heading($course->fullname);

$renderer = $PAGE->get_renderer('mod_planner');
$output = $renderer->display_print_page($course, $planner, $cm);
echo $output;

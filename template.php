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
 * Page to manipulate templates.
 *
 * @copyright 2021 Brickfield Education Labs, www.brickfield.ie
 * @package    mod_planner
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
use mod_planner\planner;

require(__DIR__.'/../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->libdir . '/tablelib.php');
define('DEFAULT_PAGE_SIZE', 10);
$perpage = optional_param('perpage', DEFAULT_PAGE_SIZE, PARAM_INT);
$spage = optional_param('spage', 0, PARAM_INT);
$confirm      = optional_param('confirm', '', PARAM_ALPHANUM);
$action       = optional_param('action', null, PARAM_ALPHANUMEXT);
$id           = optional_param('id', null, PARAM_INT);
$cid           = optional_param('cid', 0, PARAM_INT);

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
    admin_externalpage_setup('planner/template');
}
$renderer = $PAGE->get_renderer('mod_planner');

$searchform = planner::create_template_search_form($cid);
$searchclauses = $searchform['searchclauses'];
$mform = $searchform['mform'];

$pageurl = new moodle_url('/mod/planner/template.php', array(
    'spage' => $spage,
    'cid' => $cid,
    'setting' => $searchclauses));

planner::template_crud_handler($action, $id, $confirm, $pageurl, $cid);

$PAGE->set_url($pageurl);
$PAGE->set_title("{$SITE->shortname}: " . get_string('manage_templates', 'planner'));
$PAGE->requires->js_call_amd('mod_planner/viewtemplate', 'init');

$output = $renderer->setup_template($cid);
echo $output;
$renderer->display_template_table($cid, $mform, $pageurl, $searchclauses, $perpage, true);
$renderer->display_template_table($cid, $mform, $pageurl, $searchclauses, $perpage);

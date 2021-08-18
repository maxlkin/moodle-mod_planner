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
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->libdir . '/tablelib.php');
$id     = optional_param('id', '0', PARAM_INT);   // Templated id.
$format = optional_param('format', '', PARAM_RAW);
$group  = optional_param('group', 0, PARAM_ALPHANUMEXT); // Group selected.

if (! $cm = get_coursemodule_from_id('planner', $id)) {
    throw new moodle_exception('invalidcoursemodule');
}
if (! $course = $DB->get_record("course", array("id" => $cm->course))) {
    throw new moodle_exception('coursemisconf');
}
if (! $planner = $DB->get_record("planner", array("id" => $cm->instance))) {
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

$PAGE->set_url('/mod/planner/report.php', array('id' => $id));

$sql = "SELECT DISTINCT r.id, r.name, r.archetype
          FROM {role} r, {role_assignments} a
         WHERE a.contextid = :contextid
           AND r.id = a.roleid
           AND r.archetype = :archetype";
$params = array('contextid' => $context->id, 'archetype' => 'student');
$studentrole = $DB->get_record_sql($sql, $params);
if ($studentrole) {
    $studentroleid = $studentrole->id;
} else {
    $studentroleid = 0;
}

$roleselected = optional_param('role', $studentroleid, PARAM_INT);

$cminfoactivity = $DB->get_record_sql("SELECT cm.id,cm.instance,cm.module,m.name FROM {course_modules} cm
JOIN {modules} m ON (m.id = cm.module) WHERE cm.id = '".$planner->activitycmid."'");
if ($cminfoactivity) {
    $modulename = $DB->get_record($cminfoactivity->name, array('id' => $cminfoactivity->instance));
}

$plannersteps = $DB->count_records('planner_step', array('plannerid' => $planner->id));

$coursecontext = context_course::instance($course->id);

// Apply group restrictions.
$params = array();
$groupids = array();
$groupidnums = array();
$groupingids = array();
$groupselected = 0;
$groupuserid = $USER->id;
if (has_capability('moodle/site:accessallgroups', $context)) {
    $groupuserid = 0;
}
$groupjoin = '';
if ((substr($group, 0, 6) == 'group-') && ($groupid = intval(substr($group, 6)))) {
    $groupjoin = 'JOIN {groups_members} g ON (g.groupid = :groupselected AND g.userid = u.id)';
    $params['groupselected'] = $groupid;
} else if ((substr($group, 0, 9) == 'grouping-') && ($groupingid = intval(substr($group, 9)))) {
    $groupjoin = 'JOIN {groups_members} g ON '.
                 '(g.groupid IN (SELECT DISTINCT groupid FROM {groupings_groups} WHERE groupingid = :groupingselected) '.
                 'AND g.userid = u.id)';
    $params['groupingselected'] = $groupingid;
} else if ($groupuserid != 0 && !empty($groupidnums)) {
    $groupjoin = 'JOIN {groups_members} g ON (g.groupid IN ('.implode(',', $groupidnums).') AND g.userid = u.id)';
}


// Get the list of users enrolled in the course.

$sql = "SELECT u.*
          FROM {user} u
          JOIN {role_assignments} a ON (a.contextid = :contextid AND a.userid = u.id AND a.roleid = '5')
          $groupjoin
     ";
$params['contextid'] = $coursecontext->id;
$params['courseid'] = $course->id;

$userrecords = $DB->get_records_sql($sql, $params);

$students = array_values($userrecords);

if ($format) {
    require_once("{$CFG->libdir}/csvlib.class.php");

    $export = new csv_export_writer();
    $export->set_filename(format_string($planner->name));
    $row = array();
    $row[] = get_string('studentname', 'planner');
    $row[] = get_string('email', 'planner');
    for ($i = 1; $i <= $plannersteps; $i++) {
        $row[] = get_string('stepnumber', 'planner', $i);
    }
    $export->add_data($row);
    if ($students) {
        foreach ($students as $studentdata) {
            $getusersteps = $DB->get_records_sql("SELECT pus.*  FROM {planner_userstep} pus
            JOIN {planner_step} ps ON (ps.id = pus.stepid) WHERE ps.plannerid = '".$planner->id."'
            AND pus.userid = '".$studentdata->id."' ORDER BY pus.stepid ASC");
            $row = array();
            if ($studentdata->idnumber) {
                $row[] = fullname($studentdata).' ('.$studentdata->idnumber.')';
            } else {
                $row[] = fullname($studentdata);
            }
            $row[] = $studentdata->email;
            foreach ($getusersteps as $step) {
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
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('report', 'planner'));
echo $OUTPUT->container_start('progressoverviewmenus');

$groups = groups_get_all_groups($course->id, $groupuserid);
$groupings = ($groupuserid == 0 ? groups_get_all_groupings($course->id) : []);
if (!empty($groups) || !empty($groupings)) {
    $groupstodisplay = ($groupuserid == 0 ? array(0 => get_string('allparticipants')) : []);
    foreach ($groups as $groupidnum => $groupobject) {
        $groupid = 'group-'.$groupidnum;
        $groupstodisplay[$groupid] = format_string($groupobject->name);
        $groupids[] = $groupid;
        $groupidnums[] = $groupidnum;
    }
    foreach ($groupings as $groupingidnum => $groupingobject) {
        $groupingid = 'grouping-'.$groupingidnum;
        $groupstodisplay[$groupingid] = format_string($groupingobject->name);
        $groupids[] = $groupingid;
    }
    if (!in_array($group, $groupids)) {
        $group = '0';
        $PAGE->url->param('group', $group);
    }
    echo get_string('groupsvisible') . '&nbsp;';
    echo $OUTPUT->single_select($PAGE->url, 'group', $groupstodisplay, $group);
}
echo $OUTPUT->container_end();

if ($plannersteps) {
    echo $OUTPUT->box_start('generalbox');
    $tablecolumns = array();
    $tableheaders = array();
    $tablecolumns[] = 'name';
    $tablecolumns[] = 'email';
    for ($i = 1; $i <= $plannersteps; $i++) {
        $tablecolumns[] = 'step'.$i;
    }
    $tableheaders[] = get_string('studentname', 'planner');
    $tableheaders[] = get_string('email', 'planner');
    for ($i = 1; $i <= $plannersteps; $i++) {
        $tableheaders[] = get_string('stepnumber', 'planner', $i);
    }
    $table = new flexible_table('planner-report');
    $table->define_columns($tablecolumns);
    $table->define_headers($tableheaders);
    $table->column_style('name', 'text-align', 'left');
    $table->column_style('email', 'text-align', 'left');
    for ($i = 1; $i <= $plannersteps; $i++) {
        $table->column_style('step'.$i, 'text-align', 'left');
    }
    $table->define_baseurl($pageurl);
    $table->set_attribute('cellspacing', '0');
    $table->set_attribute('id', 'dashboard');
    $table->set_attribute('class', 'generaltable generalbox');

    $table->setup();
    if ($students) {
        foreach ($students as $studentdata) {
            $getusersteps = $DB->get_records_sql("SELECT pus.*  FROM {planner_userstep} pus
            JOIN {planner_step} ps ON (ps.id = pus.stepid) WHERE ps.plannerid = '".$planner->id."'
            AND pus.userid = '".$studentdata->id."' ORDER BY pus.stepid ASC");
            $data = array();
            $params = array('id' => $studentdata->id);
            $params['course'] = $course->id;
            if ($studentdata->idnumber) {
                $data[] = fullname($studentdata).'<br/>('.$studentdata->idnumber.')';
            } else {
                $data[] = \html_writer::link(new \moodle_url("/user/view.php", $params), fullname($studentdata));
            }
            $data[] = $studentdata->email;
            foreach ($getusersteps as $step) {
                if ($step->completionstatus == '1') {
                    $data[] = get_string('completed', 'planner');
                } else {
                    $data[] = get_string('pending', 'planner');
                }
            }
            $table->add_data($data);
        }
    }
    $table->print_html();
}
echo $OUTPUT->box_end();

if (count($students) > 0) {
    echo '<div style="text-align:center">';
    echo $OUTPUT->single_button(new moodle_url('report.php', array('id' => $id, 'format' => 'csv', 'group' => $group)),
    get_string('downloadcsv', 'planner'));
    echo '</div>';
}

echo $OUTPUT->footer();

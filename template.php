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
 * @package    mod_planner
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
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
$mform = new \mod_planner\form\search(null, array('cid' => $cid));

/** @var cache_session $cache */
$cache = cache::make_from_params(cache_store::MODE_SESSION, 'mod_planner', 'search');
if ($cachedata = $cache->get('data')) {
    $mform->set_data($cachedata);
}

$searchclauses = '';

// Check if we have a form submission, or a cached submission.
$data = ($mform->is_submitted() ? $mform->get_data() : fullclone($cachedata));
if ($data instanceof stdClass) {
    if (!empty($data->searchgroup['setting'])) {
        $searchclauses = $data->searchgroup['setting'];
    }
    // Cache form submission so that it is preserved while paging through the report.
    unset($data->submitbutton);
    $cache->set('data', $data);
}

$pageurl = new moodle_url('/mod/planner/template.php', array(
    'spage' => $spage,
    'cid' => $cid,
    'setting' => $searchclauses));

if (($action == 'delete') and confirm_sesskey()) {
    $plannertemplatedata = $DB->get_record('plannertemplate', array('id' => $id));
    if ($plannertemplatedata) {
        if ($confirm != md5($id)) {
            echo $OUTPUT->header();
            echo $OUTPUT->heading(get_string('deletetemplate', 'planner'));
            $optionsyes = array('id' => $id, 'confirm' => md5($id), 'sesskey' => sesskey(), 'action' => 'delete', 'cid' => $cid);
            $deleteurl = new moodle_url($pageurl, $optionsyes);
            $deletebutton = new single_button($deleteurl, get_string('delete'), 'post');

            echo $OUTPUT->confirm(get_string('deletetemplatecheck', 'planner', "'$plannertemplatedata->name'"),
            $deletebutton, $pageurl);
            echo $OUTPUT->footer();
            die;
        } else if (data_submitted()) {
            $DB->delete_records('plannertemplate_step', array('plannerid' => $id));
            if ($DB->delete_records('plannertemplate', array('id' => $id))) {
                redirect($pageurl);
            } else {
                redirect($pageurl, get_string('deletednottemplate', 'planner', $plannertemplatedata->name),
                null, \core\output\notification::NOTIFY_ERROR);
            }
        }
    }
} else if (($action == 'enable') and confirm_sesskey()) {
    $plannertemplatedata = $DB->get_record('plannertemplate', array('id' => $id));
    if ($plannertemplatedata) {
        $updatestatus = new stdClass();
        $updatestatus->id = $id;
        $updatestatus->status = 1;
        $DB->update_record('plannertemplate', $updatestatus);
    }
} else if (($action == 'disable') and confirm_sesskey()) {
    $plannertemplatedata = $DB->get_record('plannertemplate', array('id' => $id));
    if ($plannertemplatedata) {
        $updatestatus = new stdClass();
        $updatestatus->id = $id;
        $updatestatus->status = 0;
        $DB->update_record('plannertemplate', $updatestatus);
    }
}
$PAGE->set_url($pageurl);

$admins = get_admins();
$isadmin = false;
foreach ($admins as $admin) {
    if ($USER->id == $admin->id) {
        $isadmin = true;
        break;
    }
}

$PAGE->set_title("{$SITE->shortname}: " . get_string('manage_templates', 'planner'));

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('manage_templates', 'planner'));
echo $OUTPUT->box_start('generalbox');

echo "<div style=display:inline-flex; class=plannerformlayout>";
echo $OUTPUT->single_button(new moodle_url('/mod/planner/managetemplate.php',
 array('cid' => $cid)), get_string('addtemplate', 'planner'));

$mform->display();
echo "</div>";
$tablecolumns = array();
$tableheaders = array();
$tablecolumns[] = 'name';
$tablecolumns[] = 'fullname';
$tablecolumns[] = 'personal';
$tablecolumns[] = 'copied';
$tablecolumns[] = 'status';
$tablecolumns[] = 'action';

$tableheaders[] = get_string('name', 'planner');
$tableheaders[] = get_string('templateowner', 'planner');
$tableheaders[] = get_string('templatetype', 'planner');
$tableheaders[] = get_string('copy', 'planner');
$tableheaders[] = get_string('status', 'planner');
$tableheaders[] = get_string('action', 'planner');

$table = new flexible_table('planner-template');
$table->define_columns($tablecolumns);
$table->define_headers($tableheaders);
$table->column_style('name', 'text-align', 'left');
$table->column_style('fullname', 'text-align', 'left');
$table->column_style('personal', 'text-align', 'left');
$table->column_style('copied', 'text-align', 'left');
$table->column_style('status', 'text-align', 'left');
$table->column_style('action', 'text-align', 'left');


$table->define_baseurl($pageurl);

$table->no_sorting('action');


$table->set_attribute('cellspacing', '0');
$table->set_attribute('id', 'dashboard');
$table->set_attribute('class', 'generaltable generalbox');

$table->set_control_variables(array(
    TABLE_VAR_SORT => 'ssort',
    TABLE_VAR_HIDE => 'shide',
    TABLE_VAR_SHOW => 'sshow',
    TABLE_VAR_IFIRST => 'sifirst',
    TABLE_VAR_ILAST => 'silast',
    TABLE_VAR_PAGE => 'spage'
));

$table->initialbars(false);



$table->setup();

$select = "SELECT pt.*,u.firstname,u.lastname,u.middlename,u.firstnamephonetic,u.lastnamephonetic,u.alternatename";
$from = "FROM {plannertemplate} pt LEFT JOIN {user} u ON (u.id = pt.userid)";
$where = "";
$params = array();
$wheres = array();
$wheresearch = "";
$whereteacher = "";
if (!$isadmin) {
    $whereteacher = "((pt.userid = '".$USER->id."' AND pt.personal = 1) OR (pt.personal = 0))";
}
if ($searchclauses) {
    $wheres[] = $DB->sql_like('name', ':search1', false, false);
    $wheres[] = $DB->sql_like('firstname', ':search2', false, false);
    $wheres[] = $DB->sql_like('lastname', ':search3', false, false);
    $params['search1'] = "%$searchclauses%";
    $params['search2'] = "%$searchclauses%";
    $params['search3'] = "%$searchclauses%";
    $wheresearch = implode(" OR ", $wheres);
}
if ($whereteacher && $wheresearch) {
    $where = "WHERE " .$whereteacher. " AND ".$wheresearch;
} else if ($wheresearch) {
    $where = "WHERE " .$wheresearch;
} else if ($whereteacher) {
    $where = "WHERE " .$whereteacher;
}
if ($table->get_sql_sort()) {
    $sort = ' ORDER BY ' . $table->get_sql_sort();
} else {
    $sort = '';
}
$matchcount = $DB->count_records_sql("SELECT COUNT(pt.id) $from $where", $params);

$table->pagesize($perpage, $matchcount);

$templatelist = $DB->get_recordset_sql("$select $from $where $sort", $params, $table->get_page_start(), $table->get_page_size());

foreach ($templatelist as $template) {
    $data = array();
    $data[] = $template->name;
    $data[] = fullname($template);
    if ($template->personal == '0') {
        $data[] = get_string('global', 'planner');
    } else {
        $data[] = get_string('personal', 'planner');
    }
    $data[] = $template->copied;
    if ($template->status == '1') {
        $data[] = get_string('enabled', 'planner');
        $statuslink = $OUTPUT->action_icon($pageurl.'&id='.$template->id.'&action=disable&sesskey='.sesskey(),
        new pix_icon('t/hide', get_string('disabletemplate', 'planner')));
    } else {
        $data[] = get_string('disabled', 'planner');
        $statuslink = $OUTPUT->action_icon($pageurl.'&id='.$template->id.'&action=enable&sesskey='.sesskey(),
        new pix_icon('t/show', get_string('enabletemplate', 'planner')));
    }
    $editlink = $OUTPUT->action_icon(new moodle_url('/mod/planner/managetemplate.php', array('id' => $template->id,
    'cid' => $cid)), new pix_icon('t/edit', get_string('edit')));
    $deletelink = $OUTPUT->action_icon(new moodle_url('/mod/planner/template.php', array('id' => $template->id,
    'action' => 'delete', 'cid' => $cid, 'sesskey' => sesskey())), new pix_icon('t/delete', get_string('delete')));
    $data[] = $statuslink.' '.$editlink.''.$deletelink;
    $table->add_data($data);
}
$table->print_html();

echo $OUTPUT->box_end();
echo $OUTPUT->footer();

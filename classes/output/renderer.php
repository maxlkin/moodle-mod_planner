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

namespace mod_planner\output;

use mod_planner\planner;

/**
 * mod_planner renderer
 *
 * @package    mod_planner
 * @copyright  2020 onward: Brickfield Education Labs, https://www.brickfield.ie
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class renderer extends \plugin_renderer_base {

    /**
     * Display the main planner activity page.
     *
     * @param object $planner
     * @param object $cm
     * @param object $data
     * @param object $context
     * @param object $templateform
     * @param int $id
     * @return string
     */
    public function display_planner(
        object $planner,
        object $cm,
        object $data,
        object $context,
        object $templateform,
        int $id
    ): string {
        global $DB;
        $out = '';
        $out .= $this->output->header();
        $out .= $this->output->heading($planner->name);

        if ($activity = get_coursemodule_from_id('assign', $planner->activitycmid)) {
            $out .= '<h3>' . get_string('activityname_param', 'planner', $activity->name) . '</h3>';
        } else if ($activity = get_coursemodule_from_id('quiz', $planner->activitycmid)) {
            $out .= '<h3>' . get_string('activityname_param', 'planner', $activity->name) . '</h3>';
        }

        // Get necessary data.
        $time = $planner->get_planner_times($cm);

        if (!html_is_blank($planner->intro)) {
            $out .= $this->output->box(format_module_intro('planner', $planner, $cm->id), 'generalbox', 'intro');
        }

        $params = ['id' => $cm->id];
        $printurl = new \moodle_url('/mod/planner/print.php', $params);
        $printtitle = get_string('printerfriendly', 'glossary');
        $printattributes = [
            'class' => 'printicon',
            'title' => $printtitle
        ];
        $out .= '<div style="text-align: right">';
        $out .= \html_writer::link($printurl, $printtitle, $printattributes);
        $out .= '</div>';

        $j = 0;
        if ($data->templateuserstepdata) {
            foreach ($data->templateuserstepdata as $step => $stepdata) {
                if ($stepdata->completionstatus == '0') {
                    break;
                }
                $j++;
            }
        }

        $this->page->requires->js_call_amd('mod_planner/planner', 'initialise', [$j]);

        $out .= '<h3>'.get_string('plannerdefaultstartingon', 'planner').' : '
        .userdate($time->defaultstarttime, get_string('strftimedatefullshort')).'</h3>';
        $out .= '<h3>'.get_string('plannerdefaultendingon', 'planner').' : '.
                userdate($time->defaultendtime, get_string('strftimedatefullshort')).'</h3>';
        if (isset($time->userstartdate->timestart)) {
            $out .= '<h3>'.get_string('startingon', 'planner').' : '
            .userdate($time->starttime, get_string('strftimedatefullshort')).'</h3>';
            if (isset($time->userenddate->duedate)) {
                $out .= '<h3>'.get_string('endingon', 'planner').' : '
                .userdate($time->endtime, get_string('strftimedatefullshort')).'</h3>';
            }
        }

        $out .= '<br/>';
        $out .= '<p><b>'.get_string('daysinstruction', 'planner', $time->days).'</b></p>';

        if (has_capability('mod/planner:manageplanner', $context)) {

            $html = '<div id="accordion">';
            $totaltime = $time->endtime - $time->starttime;
            $exsitingsteptime = $time->starttime;
            $stepsdata = [];
            foreach ($data->templatestepdata as $stepkey => $stepval) {
                $existingsteptemp = ($totaltime * $stepval->timeallocation) / 100;
                $exsitingsteptime = $existingsteptemp + $exsitingsteptime;
                $stepsdata[$stepkey]['timedue'] = $exsitingsteptime;
            }
            $i = 1;
            foreach ($data->templatestepdata as $stepdata) {
                $html .= '<h3 class="step-header">'. '<div class="stepname">' .
                get_string('step', 'planner').' '.$i.' - '.$stepdata->name.'</div>'.'
                <div class="stepdate">'.get_string('enddate', 'planner').' : '
                .userdate($stepsdata[$stepdata->id]['timedue'], get_string('strftimedatefullshort')).' ';
                $html .= '</div></h3>';
                $html .= '<div class="">'.$stepdata->description;
                $html .= '</div>';
                $i++;
            }
            $out .= $html;
            $out .= '</div>';
        } else {
            if ($data->templateuserstepdata) {
                $html = '<div class="row"><div class="col-md-7">';
                $html .= '<div id="accordion">';
                $i = 1;
                foreach ($data->templateuserstepdata as $stepdata) {
                    $html .= '<h3 class="step-header">'. '<div class="stepname">' .
                    get_string('step', 'planner').' '.$i.' - '.$stepdata->name.'</div>'.'
                    <div class="stepdate">'.get_string('enddate', 'planner').' : '
                    .userdate($stepdata->duedate, get_string('strftimedatefullshort')).' ';
                    if ($stepdata->completionstatus == '1') {
                        $html .= $this->output->pix_icon('i/checked', 'Completed');
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
                $out .= $html;
                $out .= '<br/>';
                $out .= '</div></div><div class="col-md-5"><h2>'.get_string('differentdates', 'planner').'</h2>';
                $out .= $templateform->render();
                $out .= '</div>';
                $out .= '</div>';
            } else {
                $out .= '<h3 style="text-align:center;">'.get_string('stepsyettobe', 'planner').'</h3>';
            }
        }
        if ($planner->disclaimer) {
            $out .= '<h3>'.get_string('disclaimer', 'planner').'</h3>';
            $out .= $planner->disclaimer;
        }
        if (has_capability('mod/planner:manageplanner', $context)) {
            if (($time->starttime != $planner->timeopen) || ($time->endtime != $planner->timeclose)) {
                $out .= '<br/>';
                $out .= '<div style="text-align:center">';
                $out .= $this->output->single_button(
                    new \moodle_url('view.php', ['id' => $id, 'action' => 'recalculatesteps']),
                    get_string('recalculatestudentsteps', 'planner')
                );
                $out .= '</div>';
            } else {
                $sql = 'SELECT count(pu.id)
                          FROM {planner_userstep} pu
                          JOIN {planner_step} ps ON (ps.id = pu.stepid)
                         WHERE ps.plannerid = :plannerid';
                $checkalreadycompleted = $DB->count_records_sql($sql, ['plannerid' => $planner->id]);
                if ($checkalreadycompleted == 0) {
                    $out .= '<br/>';
                    $out .= '<div style="text-align:center">';
                    $out .= $this->output->single_button(
                        new \moodle_url('view.php', ['id' => $id, 'action' => 'studentsteps']),
                        get_string('calculatestudentsteps', 'planner')
                    );
                    $out .= '</div>';
                }
            }
        }
        $out .= $this->output->footer();
        return $out;
    }

    /**
     * Setup the template page.
     *
     * @param int $cid
     * @return string
     */
    public function setup_template(int $cid): string {
        $out = '';
        $out .= $this->output->header();
        $out .= $this->output->heading(get_string('manage_templates', 'planner'));
        $out .= $this->output->box_start('generalbox');

        return $out;
    }

    /**
     * Display the template table.
     *
     * @param int $cid
     * @param object $mform
     * @param string $pageurl
     * @param string $searchclauses
     * @param int $perpage
     * @param bool $mytemplates
     * @return void
     */
    public function display_template_table(
        int $cid,
        object $mform,
        string $pageurl,
        string $searchclauses,
        int $perpage,
        bool $mytemplates = false
    ): void {
        global $USER;

        if ($mytemplates) {
            $mform->display();
            $out = "<div style=display:inline-flex; class=plannerformlayout>";
            $out .= $this->output->single_button(
                new \moodle_url('/mod/planner/managetemplate.php', ['cid' => $cid]), get_string('addtemplate', 'planner')
            );
            $out .= "</div>";
            $out .= '<h3>'.get_string('mytemplates', 'planner').'</h3>';
            echo $out;
            $table = new \flexible_table('my-planner-templates');
            $table->set_control_variables([
                TABLE_VAR_SORT => 'sort',
                TABLE_VAR_HIDE => 'hide',
                TABLE_VAR_SHOW => 'show',
                TABLE_VAR_IFIRST => 'ifirst',
                TABLE_VAR_ILAST => 'ilast',
                TABLE_VAR_PAGE => 'page'
            ]);
        } else {
            echo '<h3>'.get_string('alltemplates', 'planner').'</h3>';
            $table = new \flexible_table('all-planner-templates');
            $table->set_control_variables([
                TABLE_VAR_SORT => 'ssort',
                TABLE_VAR_HIDE => 'shide',
                TABLE_VAR_SHOW => 'sshow',
                TABLE_VAR_IFIRST => 'sifirst',
                TABLE_VAR_ILAST => 'silast',
                TABLE_VAR_PAGE => 'spage'
            ]);
        }
        $tablecolumns = [];
        $tableheaders = [];
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
        $table->sortable(true, 'name', SORT_ASC);

        $table->set_attribute('cellspacing', '0');
        $table->set_attribute('id', 'dashboard');
        if ($mytemplates) {
            $table->set_attribute('class', 'generaltable generalbox mytemplates');
        } else {
            $table->set_attribute('class', 'generaltable generalbox alltemplates');
        }

        $table->initialbars(false);

        $table->setup();

        // Have to set width after setup() as it unsets the width attribute.
        $table->column_style('name', 'width', '25%');
        $table->column_style('fullname', 'width', '25%');
        $table->column_style('personal', 'width', '15%');
        $table->column_style('copied', 'width', '10%');
        $table->column_style('status', 'width', '10%');
        $table->column_style('action', 'width', '15%');

        if ($mytemplates) {
            $templatelist = planner::get_templatelist($table, $searchclauses, $perpage, $mytemplates);
        } else {
            $templatelist = planner::get_templatelist($table, $searchclauses, $perpage);
        }

        $admins = get_admins();
        $isadmin = false;
        foreach ($admins as $admin) {
            if ($USER->id == $admin->id) {
                $isadmin = true;
                break;
            }
        }

        if ($templatelist->valid()) {
            foreach ($templatelist as $template) {
                $data = [];
                $data[] = $template->name;
                $data[] = fullname($template);
                $statuslink = '';
                if ($template->personal == '0') {
                    $data[] = get_string('global', 'planner');
                } else {
                    $data[] = get_string('personal', 'planner');
                }
                $data[] = $template->copied;
                if ($template->status == '1') {
                    $data[] = get_string('enabled', 'planner');
                } else {
                    $data[] = get_string('disabled', 'planner');
                }
                $viewlink = \html_writer::link(
                    '#',
                    $this->output->pix_icon('t/viewdetails', get_string('view')),
                    [
                        'data-action' => 'viewtemplate',
                        'data-templateid' => $template->id,
                        'id' => 'toggle-template-modal-' . $template->id
                    ]
                );
                if ($template->userid == $USER->id || $isadmin) {
                    if ($template->status == '1') {
                        $statuslink = $this->output->action_icon(
                            $pageurl . '&id=' . $template->id . '&action=disable&sesskey=' . sesskey(),
                            new \pix_icon('t/hide', get_string('disabletemplate', 'planner'))
                        );
                    } else {
                        $statuslink = $this->output->action_icon(
                            $pageurl . '&id=' . $template->id . '&action=enable&sesskey=' . sesskey(),
                            new \pix_icon('t/show', get_string('enabletemplate', 'planner'))
                        );
                    }
                    $editlink = $this->output->action_icon(
                        new \moodle_url('/mod/planner/managetemplate.php', ['id' => $template->id, 'cid' => $cid]),
                        new \pix_icon('t/edit', get_string('edit'))
                    );
                    $deletelink = $this->output->action_icon(
                        new \moodle_url(
                            '/mod/planner/template.php',
                            ['id' => $template->id, 'action' => 'delete', 'cid' => $cid, 'sesskey' => sesskey()]
                        ),
                        new \pix_icon('t/delete', get_string('delete'))
                    );
                    $data[] = $viewlink . $statuslink . ' ' . $editlink . '' . $deletelink;
                } else {
                    $data[] = $viewlink . $statuslink;
                }
                $table->add_data($data);
            }
            $table->finish_output();
            if (!$mytemplates) {
                echo $this->output->box_end();
                echo $this->output->footer();
            }
        } else {
            echo '<h4>' . get_string('nothingtodisplay') . '</h4>';
            if (!$mytemplates) {
                echo $this->output->box_end();
                echo $this->output->footer();
            }
        }
    }

    /**
     * Display the template delete page.
     *
     * @param object $plannertemplatedata
     * @param string $pageurl
     * @param int $id
     * @param int $cid
     * @return string
     */
    public function display_template_delete_page(object $plannertemplatedata, string $pageurl, int $id, int $cid): string {
        $out = '';
        $out .= $this->output->header();
        $out .= $this->output->heading(get_string('deletetemplate', 'planner'));
        $optionsyes = ['id' => $id, 'confirm' => md5($id), 'sesskey' => sesskey(), 'action' => 'delete', 'cid' => $cid];
        $deleteurl = new \moodle_url($pageurl, $optionsyes);
        $deletebutton = new \single_button($deleteurl, get_string('delete'), 'post');

        $out .= $this->output->confirm(
            get_string('deletetemplatecheck', 'planner', "'$plannertemplatedata->name'"),
            $deletebutton,
            $pageurl
        );
        $out .= $this->output->footer();
        echo $out;
        die;
    }

    /**
     * Display the print page
     *
     * @param object $course
     * @param object $planner
     * @param object $cm
     * @return string
     */
    public function display_print_page(object $course, object $planner, object $cm): string {
        global $DB, $USER;

        // Get necessary data.
        $time = $planner->get_planner_times($cm);
        $templatestepdata = planner::get_all_steps($planner->id);
        $sql = 'SELECT pu.*
                  FROM {planner_userstep} pu
                  JOIN {planner_step} ps ON (ps.id = pu.stepid)
                 WHERE ps.plannerid = :plannerid AND pu.userid = :userid ORDER BY pu.id ';
        $userstartdate = $DB->get_record_sql($sql . 'ASC', ['plannerid' => $cm->instance, 'userid' => $USER->id]);
        $userenddate = $DB->get_record_sql($sql . 'DESC', ['plannerid' => $cm->instance, 'userid' => $USER->id]);

        $datediff = $time->endtime - $time->starttime;
        $days = round($datediff / (60 * 60 * 24));
        $templateuserstepdata = planner::get_all_usersteps($cm->instance, $USER->id);
        if ($userstartdate) {
            if ($userstartdate->timestart) {
                $time->starttime = $userstartdate->timestart;
            }
        }

        // Start output.
        $out = '';
        $out .= $this->output->header();
        $site = $DB->get_record("course", ["id" => 1]);
        $context = \context_module::instance($cm->id);

        $printtext = get_string('print', 'planner');
        $printlinkatt = ['onclick' => 'window.print();return false;', 'class' => 'planner_no_print printicon'];
        $printiconlink = \html_writer::link('#', $printtext, $printlinkatt);
        $out .= \html_writer::tag('div', $printiconlink, ['class' => 'displayprinticon']);

        $out .= \html_writer::tag('div', userdate(time()), ['class' => 'displaydate']);

        $sitename = get_string("site") . ': <span class="strong">' . format_string($site->fullname) . '</span>';
        $out .= \html_writer::tag('div', $sitename, ['class' => 'sitename']);

        $coursename = get_string("course") . ': <span class="strong">' . format_string($course->fullname)
        . ' ('. format_string($course->shortname) . ')</span>';
        $out .= \html_writer::tag('div', $coursename, ['class' => 'coursename']);

        $modname = get_string("modulename", "planner") . ': <span class="strong">' .
                   format_string($planner->name, true) . '</span>';
        $out .= \html_writer::tag('div', $modname, ['class' => 'modname']);

        if (!html_is_blank($planner->intro)) {
            $plannerdescription = get_string("description", "planner") . ':
            <span class="strong">' . format_string(format_module_intro('planner', $planner, $cm->id), true) . '</span>';
            $out .= \html_writer::tag('div', $plannerdescription, ['class' => 'modname']);
        }
        $plannerstart = get_string('plannerdefaultstartingon', 'planner').' : <span class="strong">'
        .userdate($time->defaultstarttime, get_string('strftimedatefullshort')).'</span>';
        $out .= \html_writer::tag('div', $plannerstart);

        $plannerend = get_string('plannerdefaultendingon', 'planner').' : <span class="strong">'
        .userdate($time->endtime, get_string('strftimedatefullshort')).'</span>';
        $out .= \html_writer::tag('div', $plannerend);

        if (isset($userstartdate->timestart)) {
            $planneruserstart = get_string('startingon', 'planner').' : <span class="strong">'
            .userdate($time->starttime, get_string('strftimedatefullshort')).'</span>';
            $out .= \html_writer::tag('div', $planneruserstart);

            if (isset($userenddate->duedate)) {
                $plannerend = get_string('endingon', 'planner').' : <span class="strong">'
                .userdate($userenddate->duedate, get_string('strftimedatefullshort')).'</span>';
                $out .= \html_writer::tag('div', $plannerend);
            }
        }

        $plannerday = '<p><b>'.get_string('daysinstruction', 'planner', $days).'</b></p>';
        $out .= \html_writer::tag('div', $plannerday);

        // Display the steps.
        if (has_capability('mod/planner:manageplanner', $context)) {
            $adminprint = '';
            $totaltime = $time->endtime - $time->starttime;
            $exsitingsteptime = $time->starttime;
            $stepsdata = [];
            foreach ($templatestepdata as $stepkey => $stepval) {
                $existingsteptemp = ($totaltime * $stepval->timeallocation) / 100;
                $exsitingsteptime = $existingsteptemp + $exsitingsteptime;
                $stepsdata[$stepkey]['timedue'] = $exsitingsteptime;
            }

            $i = 1;
            foreach ($templatestepdata as $stepdata) {
                $adminprint .= '<h4 class="step-header">'. '<div class="stepname">' .
                get_string('step', 'planner').' '.$i.' - '.$stepdata->name.'</div>'.'
                <div class="stepdate">'.get_string('enddate', 'planner').' : '
                .userdate($stepsdata[$stepdata->id]['timedue'], get_string('strftimedatefullshort')).' ';
                $adminprint .= '</div></h4>';

                $adminprint .= $stepdata->description;
                $i++;
            }
            $out .= \html_writer::tag('div', $adminprint);
        } else {
            if ($templateuserstepdata) {
                $i = 1;
                $userprint = '';
                foreach ($templateuserstepdata as $stepdata) {
                    $userprint .= '<h4 class="step-header">'. '<div class="stepname">' .
                    get_string('step', 'planner').' '.$i.' - '.$stepdata->name.'</div>'.'
                    <div class="stepdate">'.get_string('enddate', 'planner').' : '
                    .userdate($stepdata->duedate, get_string('strftimedatefullshort')).' ';
                    if ($stepdata->completionstatus == '1') {
                        $userprint .= $this->output->pix_icon('i/checked', get_string('completed', 'planner'));
                    }
                    $userprint .= '</div></h4>';
                    $userprint .= $stepdata->description;
                    $i++;
                }
                $out .= \html_writer::tag('div', $userprint);
            }
        }

        if ($planner->disclaimer) {
            $disclaimer = '<h4>'.get_string('disclaimer', 'planner').'</h4>';
            $disclaimer .= $planner->disclaimer;
            $out .= \html_writer::tag('div', $disclaimer);
        }

        $out .= $this->output->footer();
        return $out;
    }

    /**
     * Display the report group dropdown.
     *
     * @param object $course
     * @param int $groupuserid
     * @param int $group
     * @param object $planner
     * @return void
     */
    public function display_report_group_dropdown(object $course, int $groupuserid, int $group, object $planner): void {
        $out = '';
        $out .= $this->output->header();
        $out .= $this->output->heading(get_string('reportheading', 'planner', $planner->name));
        $out .= $this->output->container_start('progressoverviewmenus');

        $groupids = [];
        $groupidnums = [];
        $groups = groups_get_all_groups($course->id, $groupuserid);
        $groupings = ($groupuserid == 0 ? groups_get_all_groupings($course->id) : []);
        if (!empty($groups) || !empty($groupings)) {
            $groupstodisplay = ($groupuserid == 0 ? [0 => get_string('allparticipants')] : []);
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
                $this->page->url->param('group', $group);
            }
            $out .= get_string('groupsvisible') . '&nbsp;';
            $out .= $this->output->single_select($this->page->url, 'group', $groupstodisplay, $group);
        }
        $out .= $this->output->container_end();

        echo $out;
    }

    /**
     * Display the report table.`
     *
     * @param int $plannersteps
     * @param object $planner
     * @param string $pageurl
     * @param array $students
     * @param object $course
     * @param int $id
     * @param int $group
     * @return string
     */
    public function display_report_table(
        int $plannersteps,
        object $planner,
        string $pageurl,
        array $students,
        object $course,
        int $id,
        int $group
    ): string {
        $out = '';

        if ($plannersteps) {
            $out .= $this->output->box_start('generalbox');
            $tablecolumns = [];
            $tableheaders = [];
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
            $table = new \flexible_table('planner-report');
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
                    $usersteps = planner::get_all_usersteps($planner->id, $studentdata->id);
                    $data = [];
                    $params = ['id' => $studentdata->id];
                    $params['course'] = $course->id;
                    if ($studentdata->idnumber) {
                        $data[] = fullname($studentdata).'<br/>('.$studentdata->idnumber.')';
                    } else {
                        $data[] = \html_writer::link(new \moodle_url("/user/view.php", $params), fullname($studentdata));
                    }
                    $data[] = $studentdata->email;
                    foreach ($usersteps as $step) {
                        if ($step->completionstatus == '1') {
                            $data[] = get_string('completed', 'planner');
                        } else {
                            $data[] = get_string('pending', 'planner');
                        }
                    }
                    $table->add_data($data);
                }
            }
            $table->finish_html();
        }
        $out .= $this->output->box_end();

        if (count($students) > 0) {
            $out .= '<div style="text-align:center">';
            $out .= $this->output->single_button(new \moodle_url('report.php',
                ['id' => $id, 'format' => 'csv', 'group' => $group]),
            get_string('downloadcsv', 'planner'));
            $out .= '</div>';
        }
        $out .= $this->output->footer();
        return $out;
    }
}

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

defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot.'/course/moodleform_mod.php');

/**
 * Add planner form
 *
 * @package    mod_planner
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_planner_mod_form extends moodleform_mod {

    /**
     * Function definition for moodle form
     */
    public function definition() {
        global $PAGE, $DB, $USER, $course, $CFG;

        $PAGE->force_settings_menu();
        $mform = $this->_form;
        $activitycmid = optional_param('activitycmid', '', PARAM_INT);
        $stepview = optional_param('stepview', 0, PARAM_INT);
        $templateid = optional_param('templateid', '', PARAM_INT);
        $activitytitle = optional_param('name', '', PARAM_TEXT);
        $introformat = optional_param('introformat', '', PARAM_INT);
        $strrequired = get_string('required');

        $mform->addElement('header', 'generalhdr', get_string('general'));
        // Name.
        $mform->addElement('text', 'name', get_string('name'), ['size' => '64']);
        if (!empty($CFG->formatstringstriptags)) {
            $mform->setType('name', PARAM_TEXT);
        } else {
            $mform->setType('name', PARAM_CLEANHTML);
        }
        if (!empty($activitytitle)) {
            $mform->setDefault('name', $activitytitle);
        }

        $mform->addRule('name', null, 'required', null, 'server');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'server');

        // Introduction.
        $this->standard_intro_elements(get_string('description', 'planner'));
        $mform->setDefault('showdescription', $introformat);
        $informationitems = [];
        $informationitems[0] = get_string('shownothing', 'planner');
        $informationitems[1] = get_string('showitem3', 'planner');
        $informationitems[2] = get_string('showallitems', 'planner');
        $mform->addElement('select', 'stepview', get_string('informationcoursepage', 'planner'), $informationitems);
        if (!empty($stepview)) {
            $mform->setDefault('stepview', $stepview);
        }

        if ($this->_cm) {
            $cm = $this->_cm;
            if ($cm) {
                $templateid = $cm->instance;
                $planner = $DB->get_record('planner', ['id' => $templateid]);
                $sql = 'SELECT cm.id,cm.instance,cm.module,m.name
                        FROM {course_modules} cm
                        JOIN {modules} m ON (m.id = cm.module)
                        WHERE cm.id = :cmid';
                $cminfoactivity = $DB->get_record_sql($sql, ['cmid' => $planner->activitycmid]);

                if (!$cminfoactivity) {
                    throw new moodle_exception(
                        'relatedactivitynotexistdelete',
                        'planner',
                        new moodle_url("/course/view.php?id=$planner->course")
                    );
                }
                $templatestepdata = mod_planner\planner::get_all_steps($templateid);
                $mform->setDefault('disclaimer', ['text' => $planner->disclaimer]);
            }
        }

        $sql = 'SELECT cm.id,a.name
                  FROM {assign} a
                  JOIN {course_modules} cm
                    ON (cm.instance = a.id AND cm.module = (SELECT id FROM {modules} WHERE name = :assignname))
                 WHERE a.course = :courseid AND cm.visible = 1 AND (a.allowsubmissionsfromdate != 0 AND a.duedate != 0)';
        $params = ['assignname' => 'assign', 'courseid' => $course->id];
        $assignments = $DB->get_records_sql($sql, $params);
        $activitynames = [];
        $activitynames[0] = '';
        if ($assignments) {
            foreach ($assignments as $id => $assignment) {
                $activitynames[$id] = get_string('assignment', 'planner').' - '.$assignment->name;
            }
        }

        $sql = 'SELECT cm.id,q.name
                  FROM {quiz} q
                  JOIN {course_modules} cm ON (cm.instance = q.id AND cm.module = (SELECT id FROM {modules} WHERE name = :quizname))
                 WHERE q.course = :courseid AND cm.visible = 1 AND (q.timeopen != 0 AND q.timeclose != 0)';
        $params = ['quizname' => 'quiz', 'courseid' => $course->id];
        $quizzes = $DB->get_records_sql($sql, $params);
        if ($quizzes) {
            foreach ($quizzes as $id => $quiz) {
                $activitynames[$id] = get_string('quiz', 'planner').' - '.$quiz->name;
            }
        }

        if ($this->_cm) {
            $whereplanner = "id != ?";
            $params = [$cm->instance];
            $sql = 'SELECT count(pu.id)
                      FROM {planner_userstep} pu
                      JOIN {planner_step} ps ON (ps.id = pu.stepid)
                     WHERE ps.plannerid = :plannerid';
            $checkalreadycompleted = $DB->count_records_sql($sql, ['plannerid' => $planner->id]);
        } else {
            $checkalreadycompleted = null;
            $whereplanner = '';
            $params = [];
        }

        $newplanner = $DB->get_records_select('planner', $whereplanner, $params, '', 'id,activitycmid,name');
        $modinfo = get_fast_modinfo($course);

        if ($newplanner) {
            foreach ($newplanner as $cmid) {
                foreach ($modinfo->instances as $modulename => $modinstances) {
                    foreach ($modinstances as $cm) {
                        if (($cm->modname == 'planner' && $cm->deletioninprogress == 0)
                        && (array_key_exists($cmid->activitycmid, $activitynames) && $cm->name == $cmid->name)
                        || ($cm->id == $cmid->activitycmid && $cm->deletioninprogress == 1) ) {
                            unset($activitynames[$cmid->activitycmid]);
                        }
                    }
                }
            }
        }

        $enabledoptions = [
            'multiple' => false,
            'noselectionstring' => get_string('selectactivity', 'planner')
        ];
        $disabledoptions = [
            'disabled' => 'disabled',
            'style' => 'width:200px; background:none;'
        ];
        if ($checkalreadycompleted == 0 || $checkalreadycompleted == null) {
            if (count($activitynames) > 1) {
                $mform->addElement('autocomplete', 'activitycmid',
                get_string('selectactivity', 'planner'), $activitynames, $enabledoptions);
                $mform->addHelpButton('activitycmid', 'activitiesenabled', 'mod_planner');
            } else {
                $activitynames[0] = "No activities";
                $mform->addElement('select', 'activitycmid',
                get_string('selectactivity', 'planner'), $activitynames, $disabledoptions);
                $mform->addHelpButton('activitycmid', 'activitiesdisabled', 'mod_planner');
            }
            $mform->addRule('activitycmid', $strrequired, 'required', null, 'server');
            if ($activitycmid) {
                $mform->setDefault('activitycmid', $activitycmid);
            }
        } else {
            if ($activity = get_coursemodule_from_id('assign', $cminfoactivity->id)) {
                $mform->addElement('static', 'activityname', get_string('activityname', 'planner'), $activity->name);
            } else if ($activity = get_coursemodule_from_id('quiz', $cminfoactivity->id)) {
                $mform->addElement('static', 'activityname', get_string('activityname', 'planner'), $activity->name);
            }
        }

        $alltemplates = '';
        if (!$this->_cm) {
            $admins = get_admins();
            $isadmin = false;
            foreach ($admins as $admin) {
                if ($USER->id == $admin->id) {
                    $isadmin = true;
                    break;
                }
            }

            $sql = 'SELECT id, name, disclaimer, personal FROM {plannertemplate}';
            $whereteacher = " WHERE status = 1 ";
            if (!$isadmin) {
                $whereteacher .= 'AND ((userid = :userid AND personal = 1) OR (personal = 0))';
            }
            $templates = [];
            $templates[0] = '';
            $sql .= $whereteacher;
            $sql .= 'ORDER BY name ASC';
            $alltemplates = $DB->get_records_sql($sql, ['userid' => $USER->id]);
            if ($alltemplates) {
                foreach ($alltemplates as $id => $template) {
                    $templates[$id] = $template->name;
                }
            }

            $enabledoptions = [
                'multiple' => false,
                'noselectionstring' => get_string('selecttemplate', 'planner'),
                'onchange' => 'this.form.submit()'
            ];
            $disabledoptions = [
                'disabled' => 'disabled',
                'style' => 'width:200px; background:none;'
            ];
            $mform->disable_form_change_checker();
            if (count($templates) > 1) {
                $mform->addElement('autocomplete', 'templateid', get_string('template', 'planner'), $templates, $enabledoptions);
                $mform->addHelpButton('templateid', 'templatesenabled', 'mod_planner');
            } else {
                $templates[0] = "No templates";
                $mform->addElement('select', 'templateid', get_string('template', 'planner'), $templates, $disabledoptions);
                $mform->addHelpButton('templateid', 'templatesdisabled', 'mod_planner');
            }
            $mform->addRule('templateid', $strrequired, 'required', null, 'server');
            if ($templateid) {
                $mform->setDefault('templateid', $templateid);
                $templatestepdata = $DB->get_records('plannertemplate_step', ['plannerid' => $templateid], 'id ASC');
                $mform->setDefault('disclaimer', ['text' => $alltemplates[$templateid]->disclaimer]);
            }
        }
        if ($templateid) {
            $repeatno = count($templatestepdata);
            if ($repeatno > 0) {
                $repeatarray = [];
                $repeatarray[] = $mform->createElement(
                    'text',
                    'stepname',
                    get_string('stepname', 'planner'),
                    ['size' => "50", 'selector' => 'planner_stepname']
                );
                $repeatarray[] = $mform->createElement(
                    'text',
                    'stepallocation',
                    get_string('steptimeallocation', 'planner'),
                    ['size' => "3", 'selector' => 'planner_stepallocation']
                );
                $repeatarray[] = $mform->createElement(
                    'editor',
                    'stepdescription',
                    get_string('stepdescription', 'planner'),
                    ['selector' => 'planner_stepdescription']
                );
                $repeatno = count($templatestepdata);
                $repeateloptions = [];
                $repeateloptions['stepname']['type'] = PARAM_RAW;
                $repeateloptions['stepname']['helpbutton'] = ['helpinstruction', 'planner'];
                $repeateloptions['stepallocation']['type'] = PARAM_INT;
                $repeateloptions['stepdescription']['type'] = PARAM_RAW;
                $this->repeat_elements(
                    $repeatarray,
                    $repeatno,
                    $repeateloptions,
                    'option_repeats',
                    'option_add_fields',
                    1,
                    get_string('addstepstoform', 'planner'),
                    true
                );
                $i = 0;
                foreach ($templatestepdata as $templatestep) {
                    $mform->setDefault('stepname['.$i.']', $templatestep->name);
                    $mform->setDefault('stepallocation['.$i.']', $templatestep->timeallocation);
                    $mform->setDefault('stepdescription['.$i.']', ['text' => $templatestep->description]);
                    $i++;
                }
                $mform->addElement('editor', 'disclaimer', get_string('disclaimer', 'planner'));
                $mform->settype('disclaimer', PARAM_RAW);
            }
            $mform->addElement('button', 'savenewtemplate', get_string('savenewtemplate', 'planner'));
            if ($alltemplates) {
                $personal = $alltemplates[$templateid]->personal;
            } else {
                $personal = 0;
            }
            $PAGE->requires->js_call_amd('mod_planner/savenewtemplate', 'init', [$personal, $course->id]);
        }
        $this->standard_coursemodule_elements();
        $this->add_action_buttons();
    }
    /**
     * Moodle form validation
     *
     * @param array $data
     * @param array $files
     * @return array $errors
     */
    public function validation ($data, $files) {
        $errors = parent::validation($data, $files);
        if ((isset($data['submitbutton2'])) || (isset($data['submitbutton']))) {
            if ($data['update'] == 0) {
                $activitycmid = isset($data['activitycmid']) ? $data['activitycmid'] : 0;
                $templateid = isset($data['templateid']) ? $data['templateid'] : 0;
                if ($activitycmid == '0') {
                    $errors['activitycmid'] = get_string('required');
                }
                if ($templateid == '0') {
                    $errors['templateid'] = get_string('required');
                }
            }
            if (isset($data['stepname'])) {
                $stepname = $data['stepname'][0];
                $stepallocation = $data['stepallocation'][0];
                $totalsteps = count($data['stepallocation']);
                $totaltimeallocation = 0;
                for ($i = 0; $i <= $totalsteps; $i++) {
                    if (isset($data['stepname'][$i]) && (!empty($data['stepname'][$i]))) {
                        if (isset($data['stepallocation'][$i])) {
                            $totaltimeallocation = $totaltimeallocation + $data['stepallocation'][$i];
                        }
                    }
                }
                if (!$stepname) {
                    $errors['stepname[0]'] = get_string('required');
                }
                if (!$stepallocation) {
                    $errors['stepallocation[0]'] = get_string('required');
                }
                if ($totaltimeallocation != '100') {
                    for ($i = 0; $i <= $totalsteps; $i++) {
                        if (isset($data['stepname'][$i]) && (!empty($data['stepname'][$i]))) {
                            if (isset($data['stepallocation'][$i])) {
                                $errors['stepallocation['.$i.']'] = get_string('totaltimeallocated', 'planner');
                            }
                        }
                    }
                }
            }
        } else {
            $activitycmid = isset($data['activitycmid']) ? $data['activitycmid'] : 0;
            if ($activitycmid == '0') {
                $errors['activitycmid'] = get_string('required');
            }
        }
        return $errors;
    }
}

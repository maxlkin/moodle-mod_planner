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
if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');    // It must be included from a Moodle page.
}

require_once($CFG->dirroot.'/lib/formslib.php');
/**
 * Templates form class for planner module
 *
 * @copyright 2021 Brickfield Education Labs, www.brickfield.ie
 * @package mod_planner
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class template_form extends moodleform {

    /**
     * Define the form.
     */
    public function definition() {
        global $CFG;

        $mform = $this->_form;
        $strrequired = get_string('required');
        $id = $this->_customdata['id'];
        $cid = $this->_customdata['cid'];
        $templatedata = $this->_customdata['templatedata'];
        $templatestepdata = $this->_customdata['templatestepdata'];

        $mform->addElement('text', 'name', get_string('templatename', 'planner'));
        $mform->addRule('name', $strrequired, 'required', null, 'server');
        $mform->settype('name', PARAM_RAW);

        if ($templatedata) {
            $mform->setDefault('name', $templatedata->name);
            $mform->setDefault('personal', $templatedata->personal);
            $mform->setDefault('disclaimer', array('text' => $templatedata->disclaimer));
        } else {
            $mform->setDefault('personal', 0);
        }

        $templatetypes = array();
        $templatetypes[0] = get_string('global', 'planner');
        $templatetypes[1] = get_string('personal', 'planner');

        $mform->addElement('select', 'personal', get_string('templatetype', 'planner'), $templatetypes);

        if ($templatestepdata) {
            $totalsteps = count($templatestepdata);
        } else {
            $totalsteps = 6;
        }

        $repeatno = $totalsteps;
        if ($repeatno > 0) {
            $repeatarray = array();
            $repeatarray[] = $mform->createElement('text', 'stepname', get_string('stepname', 'planner'), 'size="50" ');
            $repeatarray[] = $mform->createElement('text', 'stepallocation', get_string('steptimeallocation', 'planner'),
            'size="3" ');
            $repeatarray[] = $mform->createElement('editor', 'stepdescription', get_string('stepdescription', 'planner'));
            $repeateloptions = array();
            $repeateloptions['stepname']['type'] = PARAM_RAW;
            $repeateloptions['stepname']['helpbutton'] = array('helpinstruction', 'planner');
            $repeateloptions['stepallocation']['type'] = PARAM_INT;
            $repeateloptions['stepdescription']['type'] = PARAM_RAW;
            $this->repeat_elements($repeatarray, $repeatno,
                        $repeateloptions, 'option_repeats', 'option_add_fields', 1, get_string('addstepstoform', 'planner'), true);
            $i = 0;
            if ($templatestepdata) {
                foreach ($templatestepdata as $templatestep) {
                    $mform->setDefault('stepname['.$i.']', $templatestep->name);
                    $mform->setDefault('stepallocation['.$i.']', $templatestep->timeallocation);
                    $mform->setDefault('stepdescription['.$i.']', array('text' => $templatestep->description));
                    $i++;
                }
            } else {
                $j = 1;
                for ($i = 0; $i < 6; $i++) {
                    $mform->setDefault('stepname['.$i.']', get_config('planner', 'step'.$j.'name'));
                    $mform->setDefault('stepallocation['.$i.']', get_config('planner', 'step'.$j.'timeallocation'));
                    $mform->setDefault('stepdescription['.$i.']', array('text' => get_config('planner', 'step'.$j.'description')));
                    $j++;
                }
            }
            $mform->addElement('editor', 'disclaimer', get_string('disclaimer', 'planner'));
            $mform->settype('disclaimer', PARAM_RAW);
        }

        $mform->addElement('hidden', 'id', $id);
        $mform->settype('id', PARAM_INT);

        $mform->addElement('hidden', 'cid', $cid);
        $mform->settype('cid', PARAM_INT);

        $mform->addElement('hidden', 'totalsteps', $totalsteps);
        $mform->settype('totalsteps', PARAM_INT);

        $btnstring = get_string('submit');

        $this->add_action_buttons(true, $btnstring);
    }

    /**
     * Extend the form definition after data has been parsed.
     */
    public function definition_after_data() {
        global $USER, $CFG, $DB, $OUTPUT;
        $mform = $this->_form;
    }

    /**
     * Validate the form data.
     * @param array $data
     * @param array $files
     * @return array|bool
     */
    public function validation($data, $files) {
        global $CFG, $DB;
        $errors = parent::validation($data, $files);
        if (isset($data['submitbutton'])) {
            if (isset($data['stepname'])) {
                $stepname = $data['stepname'][0];
                $stepallocation = $data['stepallocation'][0];
                $totalsteps = count($data['stepallocation']);
                $totaltimeallocation = 0;
                for ($i = 0; $i <= $totalsteps; $i++) {
                    if (isset($data['stepname'][$i]) AND (!empty($data['stepname'][$i]))) {
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
                        if (isset($data['stepname'][$i]) AND (!empty($data['stepname'][$i]))) {
                            if (isset($data['stepallocation'][$i])) {
                                $errors['stepallocation['.$i.']'] = get_string('totaltimeallocated', 'planner');
                            }
                        }
                    }
                }
            }
        }
        return $errors;
    }
}

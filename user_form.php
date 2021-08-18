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
 * Planner module version info
 * @package mod_planner
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');    // It must be included from a Moodle page.
}

require_once($CFG->dirroot.'/lib/formslib.php');
class user_form extends moodleform  {

    /**
     * Define the form.
     */
    public function definition() {
        global $CFG;

        $mform = $this->_form;
        $strrequired = get_string('required');
        $id = $this->_customdata['id'];
        $studentstartime = $this->_customdata['studentstartime'];
        $starttime = $this->_customdata['startdate'];
        $endtime = $this->_customdata['enddate'];
        $mform->addElement('date_selector', 'userstartdate', get_string('startdate', 'planner'),
        array('startyear' => userdate($starttime, '%Y'), 'stopyear' => userdate($endtime, '%Y')));
        if ($studentstartime) {
            $mform->setDefault('userstartdate', $studentstartime);
        } else {
            $mform->setDefault('userstartdate', $starttime);
        }
        $mform->addElement('hidden', 'id', $id);
        $mform->settype('id', PARAM_INT);

        $btnstring = get_string('recalculateschedule', 'planner');

        $this->add_action_buttons(false, $btnstring);

        $this->set_data($id);
    }

    /**
     * Validate the form data.
     * @param array $data
     * @param array $files
     * @return array|bool
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        $starttime = $this->_customdata['startdate'];
        $endtime = $this->_customdata['enddate'];
        if ($data['userstartdate'] < $starttime) {
            $errors['userstartdate'] = get_string('startdatewarning1', 'planner');
        } else if ($data['userstartdate'] > $endtime) {
            $errors['userstartdate'] = get_string('startdatewarning2', 'planner');
        }

        return $errors;
    }
}

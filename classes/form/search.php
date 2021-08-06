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
 * Report search form class.
 *
 * @package    mod_planner
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_planner\form;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

/**
 * Report search form class.
 *
 * @package    report_configlog
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class search extends \moodleform {

    /**
     * Form definition
     *
     * @return void
     */
    public function definition() {
        $mform = $this->_form;
        $cid = $this->_customdata['cid'];

        // By default just show the 'setting' field.
        $mform->addElement('text', 'setting', get_string('search'));
        $mform->setType('setting', PARAM_TEXT);

        $mform->addElement('hidden', 'cid', $cid);
        $mform->settype('cid', PARAM_INT);

        $this->add_action_buttons(false, get_string('search'));
    }
}
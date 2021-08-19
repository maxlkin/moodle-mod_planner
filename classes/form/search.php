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

        $mform->addElement('hidden', 'cid', $cid);
        $mform->setType('cid', PARAM_INT);

        // By default just show the 'setting' field.
        $group[] =& $mform->createElement('text', 'setting', get_string('search'));
        $group[] =& $mform->createElement('submit', get_string('search'), 'Submit');
        $mform->addGroup($group, 'searchgroup', get_string('search'));
        $mform->setType('searchgroup[setting]', PARAM_TEXT);
    }
}

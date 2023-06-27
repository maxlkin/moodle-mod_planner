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

defined('MOODLE_INTERNAL') || die();

/**
 * Unit tests for mod_planner planner.php.
 *
 * @package    mod_planner
 * @copyright  2020 onward: Brickfield Education Labs, www.brickfield.ie
 * @author     Jay Churchward (jay@brickfieldlabs.ie)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_planner_generator extends testing_module_generator {

    /**
     * Create a new instance of the module.
     *
     * @param object $record
     * @param array $options
     * @return stdClass
     */
    public function create_instance($record = null, array $options = null) {
        global $CFG;
        require_once($CFG->dirroot . '/lib/resourcelib.php');

        $record = (object)(array)$record;

        if (!isset($record->content)) {
            $record->content = 'Test page content';
        }
        if (!isset($record->submitbutton)) {
            $record->submitbutton = 'submitbutton';
        }
        if (!isset($record->submitbutton2)) {
            $record->submitbutton2 = 'submitbutton2';
        }
        if (!isset($record->contentformat)) {
            $record->contentformat = FORMAT_MOODLE;
        }
        if (!isset($record->display)) {
            $record->display = RESOURCELIB_DISPLAY_AUTO;
        }
        if (!isset($record->printintro)) {
            $record->printintro = 0;
        }
        if (!isset($record->printlastmodified)) {
            $record->printlastmodified = 1;
        }
        if (!isset($record->activitycmid)) {
            $record->activitycmid = 1;
        }
        if (!isset($record->disclaimer)) {
            $record->disclaimer = false;
        }
        if (!isset($record->stepname)) {
            $record->stepname = ['Step 1', 'Step 2', 'Step 3'];
        }
        if (!isset($record->stepallocation)) {
            $record->stepallocation = [1, 2, 3];
        }
        if (!isset($record->stepdescription)) {
            $record->stepdescription = [['text' => 'Step 1 description'], ['text' => 'Step 2 description'],
                ['text' => 'Step 3 description']];
        }
        if (!isset($record->option_repeats)) {
            $record->option_repeats = 3;
        }

        return parent::create_instance($record, (array)$options);
    }

    /**
     * Creates and inserts a template record.
     *
     * @return int
     */
    public function create_template() {
        global $DB;

        $record = new stdClass();
        $record->userid = 2;
        $record->name = 'Test planner template';
        $record->personal = 0;
        $record->status = 1;
        $record->copied = 0;

        $id = $DB->insert_record('plannertemplate', $record);
        return $id;
    }
}

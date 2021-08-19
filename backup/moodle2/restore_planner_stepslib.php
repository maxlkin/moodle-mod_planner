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
 * Define all the restore steps that will be used by the restore_planner_activity_task
 *
 * @package    mod_planner
 * @subpackage backup-moodle2
 * @copyright 2010 onwards Eloy Lafuente (stronk7) {@link http://stronk7.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class restore_planner_activity_structure_step extends restore_activity_structure_step {

    /**
     * Structure step to restore one planner activity
     *
     * @return void
     */
    protected function define_structure() {

        $paths = array();
        $userinfo = $this->get_setting_value('userinfo');

        $paths[] = new restore_path_element('planner', '/activity/planner');
        $paths[] = new restore_path_element('planner_step', '/activity/planner/plannersteps/plannerstep');
        if ($userinfo) {
            $paths[] = new restore_path_element('planner_userstep',
            '/activity/planner/plannersteps/plannerstep/plannerusersteps/planneruserstep');
        }

        // Return the paths wrapped into standard activity structure.
        return $this->prepare_activity_structure($paths);
    }

    /**
     * Process planner table for restore
     *
     * @param array $data
     * @return void
     */
    protected function process_planner($data) {
        global $DB;

        $data = (object)$data;

        /*if (!restore_structure_step::get_mapping('course_module', $data->activitycmid)) {
            throw new restore_step_exception('planner_linked_activity_not_included');
        }*/
        $oldid = $data->id;
        $data->course = $this->get_courseid();
        $newcmactivity = restore_structure_step::get_mapping('course_module', $data->activitycmid);
        $data->activitycmid = $newcmactivity->newitemid;

        // Insert the planner record.
        $newitemid = $DB->insert_record('planner', $data);
        // Immediately after inserting "activity" record, call this.
        $this->apply_activity_instance($newitemid);
    }

    /**
     * Process planner_step table for restore
     *
     * @param array $data
     * @return void
     */
    protected function process_planner_step($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        $data->plannerid = $this->get_new_parentid('planner');

        $newitemid = $DB->insert_record('planner_step', $data);
        $this->set_mapping('planner_step', $oldid, $newitemid);
    }

    /**
     * Process planner_step table for restore
     *
     * @param array $data
     * @return void
     */
    protected function process_planner_userstep($data) {
        global $DB;

        $data = (object)$data;

        $data->stepid = $this->get_new_parentid('planner_step');
        $data->userid = $this->get_mappingid('user', $data->userid);

        $newitemid = $DB->insert_record('planner_userstep', $data);
        // No need to save this mapping as far as nothing depend on it.
        // (child paths, file areas nor links decoder).
    }

    /**
     * Add planner related files, no need to match by itemname (just internally handled context)
     *
     * @return void
     */
    protected function after_execute() {
        $this->add_related_files('mod_planner', 'intro', null);
    }
}

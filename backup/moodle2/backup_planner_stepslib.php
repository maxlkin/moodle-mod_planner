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
 * @package    mod_planner
 * @subpackage backup-moodle2
 * @copyright 2010 onwards Eloy Lafuente (stronk7) {@link http://stronk7.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Define all the backup steps that will be used by the backup_planner_activity_task
 */

/**
 * Define the complete planner structure for backup, with file and id annotations
 */
class backup_planner_activity_structure_step extends backup_activity_structure_step {

    protected function define_structure() {
        global $DB;

        /*if (isset($_SERVER['HTTP_USER_AGENT'])) {
            $exisingplanner = $DB->get_record('planner', array ('id' => $this->task->get_activityid()));

            $cminfoactivity = $DB->get_record_sql("SELECT cm.id,cm.instance,cm.module,m.name FROM {course_modules} cm
        JOIN {modules} m ON (m.id = cm.module) WHERE cm.id = '".$exisingplanner->activitycmid."'");
            if ($cminfoactivity) {
                if (!$this->get_setting_value($cminfoactivity->name.'_'.$cminfoactivity->id.'_included')) {
                    throw new backup_step_exception('planner_linked_activity_not_included');
                }
            }
        }*/

        // To know if we are including userinfo.
        $userinfo = $this->get_setting_value('userinfo');

        // Define each element separated.
        $planner = new backup_nested_element('planner', array('id'), array(
            'name', 'intro', 'introformat', 'disclaimer',
            'activitycmid', 'stepview', 'timeopen', 'timeclose', 'timemodified'));

        $plannersteps = new backup_nested_element('plannersteps');

        $plannerstep = new backup_nested_element('plannerstep', array('id'), array(
            'name', 'timeallocation', 'description'));

        $plannerusersteps = new backup_nested_element('plannerusersteps');

        $planneruserstep = new backup_nested_element('planneruserstep', array('id'), array(
            'userid', 'timestart', 'duedate', 'completionstatus', 'timemodified'));

        // Build the tree.
        $planner->add_child($plannersteps);
        $plannersteps->add_child($plannerstep);

        $plannerstep->add_child($plannerusersteps);
        $plannerusersteps->add_child($planneruserstep);

        // Define sources.
        $planner->set_source_table('planner', array('id' => backup::VAR_ACTIVITYID));

        $plannerstep->set_source_table('planner_step', array('plannerid' => backup::VAR_PARENTID), 'id ASC');

        // All the rest of elements only happen if we are including user info.
        if ($userinfo) {
            $planneruserstep->set_source_table('planner_userstep', array('stepid' => backup::VAR_PARENTID));
        }

        // Define id annotations.
        $planneruserstep->annotate_ids('user', 'userid');

        // Define file annotations.
        $planner->annotate_files('mod_planner', 'intro', null); // This file area hasn't itemid.

        // Return the root element (planner), wrapped into standard activity structure.
        return $this->prepare_activity_structure($planner);
    }
}

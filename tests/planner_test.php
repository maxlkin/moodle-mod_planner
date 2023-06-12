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

namespace mod_planner;

/**
 * Unit tests for mod_planner planner.php.
 *
 * @package    mod_planner
 * @group      mod_planner
 * @copyright  2020 onward: Brickfield Education Labs, www.brickfield.ie
 * @author     Jay Churchward (jay@brickfieldlabs.ie)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class planner_test extends \advanced_testcase {

    /**
     * Test the get_planner_name function.
     */
    public function test_get_planner_name() {
        $this->resetAfterTest();
        $data = $this->setup_test_data();
        $object = $data->planner;

        $output = $object->get_planner_name();
        $this->assertEquals('Planner', $output);
    }

    /**
     * Test the create_template_search_form function.
     */
    public function test_create_template_search_form() {
        $this->resetAfterTest();
        $data = $this->setup_test_data();
        $object = $data->planner;

        $output = $object->create_template_search_form(1);
        $this->assertIsArray($output);
        $this->assertEquals($output['searchclauses'], '');
    }

    /**
     * Test the get_students_and_groups function.
     */
    public function test_get_students_and_groups() {
        $this->resetAfterTest();
        $data = $this->setup_test_data();

        // The group paramater is always passed as group-<groupid>.
        // Test with a group that doesn't follow that naming convention.
        $object = $data->planner;
        $output = $object->get_students_and_groups($data->group->name, $data->course, $data->context,
            $data->coursecontext, $data->student1->id);
        $this->assertIsArray($output);
        $this->assertEquals($output[0]->id, $data->student1->id);
        $this->assertEquals($output[1]->id, $data->student2->id);

        // Test with a group that follows the naming convention, but with an id that doesn't exist.
        $output = $object->get_students_and_groups('group-10320320', $data->course, $data->context,
            $data->coursecontext, $data->student1->id);
        $this->assertIsArray($output);
        $this->assertEmpty($output);

        // Test with a group that follows the naming convention, but with an id that does exist.
        $output = $object->get_students_and_groups('group-' . $data->group->id, $data->course, $data->context,
            $data->coursecontext, $data->student1->id);
        $this->assertIsArray($output);
        $this->assertEquals($output[0]->id, $data->student1->id);
        $this->assertEquals($output[1]->id, $data->student2->id);
    }

    /**
     * Test the get_planner_times function.
     */
    public function test_get_planner_times() {
        $this->resetAfterTest();
        $data = $this->setup_test_data();
        $object = $data->planner;
        $output = $object->get_planner_times($data->cm);

        $this->assertIsObject($output);
    }

    /**
     * Test the planner_crud_handler function.
     */
    public function test_planner_crud_handler() {
        $this->resetAfterTest();
        $data = $this->setup_test_data();
        $redirecturl = new \moodle_url('/mod/planner/view.php', array('id' => $data->cm->id));
        $object = $data->planner;

        // Test with empty action.
        $output = $object->planner_crud_handler('', $redirecturl, $data->context, $data->cm);
        $output->templatestepdata = array_values($output->templatestepdata);
        $this->assertIsObject($output);
        $this->assertEquals($output->templatestepdata[0]->name, 'Step 1');
        $this->assertEquals($output->templatestepdata[1]->name, 'Step 2');
        $this->assertEquals($output->templatestepdata[2]->name, 'Step 3');

        // Test with 'recalculatesteps' action.
        try {
            $output = $object->planner_crud_handler('recalculatesteps', $redirecturl, $data->context, $data->cm);
        } catch (\exception $e) {
            // Redirect will be called so we will encounter an unsupported redirect error' moodle_exception.
            $this->assertInstanceOf(\moodle_exception::class, $e);
        } finally {
            $output->templatestepdata = array_values($output->templatestepdata);
            // Get the user step data as it isn't retrieved due to the redirect.
            $output->templateuserstepdata = $this->get_user_step_data($data->cm, $data->student1);
            $output->templateuserstepdata = array_values($output->templateuserstepdata);

            $this->assertIsObject($output);
            $this->assertEquals($output->templatestepdata[0]->name, 'Step 1');
            $this->assertEquals($output->templatestepdata[1]->name, 'Step 2');
            $this->assertEquals($output->templatestepdata[2]->name, 'Step 3');
            $this->assertEquals($output->templateuserstepdata[0]->name, 'Step 1');
            $this->assertEquals($output->templateuserstepdata[1]->name, 'Step 2');
            $this->assertEquals($output->templateuserstepdata[2]->name, 'Step 3');
        }

        // Test with 'studentsteps' action.
        try {
            $output = $object->planner_crud_handler('studentsteps', $redirecturl, $data->context, $data->cm);
        } catch (\exception $e) {
            // Redirect will be called so we will encounter an unsupported redirect error' moodle_exception.
            $this->assertInstanceOf(\moodle_exception::class, $e);
        } finally {
            $output->templatestepdata = array_values($output->templatestepdata);
            // Get the user step data as it isn't retrieved due to the redirect.
            $output->templateuserstepdata = $this->get_user_step_data($data->cm, $data->student1);
            $output->templateuserstepdata = array_values($output->templateuserstepdata);

            $this->assertIsObject($output);
            $this->assertEquals($output->templatestepdata[0]->name, 'Step 1');
            $this->assertEquals($output->templatestepdata[1]->name, 'Step 2');
            $this->assertEquals($output->templatestepdata[2]->name, 'Step 3');
            $this->assertEquals($output->templateuserstepdata[0]->name, 'Step 1');
            $this->assertEquals($output->templateuserstepdata[1]->name, 'Step 2');
            $this->assertEquals($output->templateuserstepdata[2]->name, 'Step 3');
        }

        // Test with 'stepsubmit' action.
        try {
             $output = $object->planner_crud_handler('stepsubmit', $redirecturl, $data->context, $data->cm);
        } catch (\exception $e) {
            // Redirect will be called so we will encounter an unsupported redirect error' moodle_exception.
            $this->assertInstanceOf(\moodle_exception::class, $e);
        } finally {
            $output->templatestepdata = array_values($output->templatestepdata);
            // Get the user step data as it isn't retrieved due to the redirect.
            $output->templateuserstepdata = $this->get_user_step_data($data->cm, $data->student1);
            $output->templateuserstepdata = array_values($output->templateuserstepdata);

            $this->assertIsObject($output);
            $this->assertEquals($output->templatestepdata[0]->name, 'Step 1');
            $this->assertEquals($output->templatestepdata[1]->name, 'Step 2');
            $this->assertEquals($output->templatestepdata[2]->name, 'Step 3');
            $this->assertEquals($output->templateuserstepdata[0]->name, 'Step 1');
            $this->assertEquals($output->templateuserstepdata[1]->name, 'Step 2');
            $this->assertEquals($output->templateuserstepdata[2]->name, 'Step 3');
        }
    }

    /**
     * Test the create_planner_user_form function.
     */
    public function test_create_planner_user_form() {
        $this->resetAfterTest();
        $testdata = $this->setup_test_data();
        $redirecturl = new \moodle_url('/mod/planner/view.php', array('id' => $testdata->cm->id));
        $object = $testdata->planner;
        $data = $object->planner_crud_handler('', $redirecturl, $testdata->context, $testdata->cm);
        try {
            $data = $object->planner_crud_handler('recalculatesteps', $redirecturl, $testdata->context, $testdata->cm);
        } catch (\exception $e) {
            // Redirect will be called so we will encounter an unsupported redirect error' moodle_exception.
            $this->assertInstanceOf(\moodle_exception::class, $e);
        } finally {
            $data->templatestepdata = array_values($data->templatestepdata);
            // Get the user step data as it isn't retrieved due to the redirect.
            $data->templateuserstepdata = $this->get_user_step_data($testdata->cm, $testdata->student1);
            $data->templateuserstepdata = array_values($data->templateuserstepdata);
        }

        $output = $object->create_planner_user_form($data, $testdata->cm->id, $testdata->cm, $testdata->course,
            $testdata->context, $redirecturl);
        $this->assertIsObject($output);
        $this->assertInstanceOf(\moodleform::class, $output);
    }

    /**
     * Test the get_templatelist function.
     */
    public function test_get_templatelist() {
        $this->resetAfterTest();
        $data = $this->setup_test_data();
        $table = $this->create_test_table();
        $object = $data->planner;

        $output = $object->get_templatelist($table, '', 10);
        $template = '';
        foreach ($output as $temp) {
            $template = $temp;
        }
        $this->assertIsObject($template);
        $this->assertEquals($template->name, 'Test planner template');

        $output = $object->get_templatelist($table, 'Test', 10);
        $template = '';
        foreach ($output as $temp) {
            $template = $temp;
        }
        $this->assertIsObject($template);
        $this->assertEquals($template->name, 'Test planner template');

        $output = $object->get_templatelist($table, 'template', 10);
        $template = '';
        foreach ($output as $temp) {
            $template = $temp;
        }
        $this->assertIsObject($template);
        $this->assertEquals($template->name, 'Test planner template');

        $output = $object->get_templatelist($table, 'planner', 10);
        $template = '';
        foreach ($output as $temp) {
            $template = $temp;
        }
        $this->assertIsObject($template);
        $this->assertEquals($template->name, 'Test planner template');

        $output = $object->get_templatelist($table, 'Test planner template', 10);
        $template = '';
        foreach ($output as $temp) {
            $template = $temp;
        }
        $this->assertIsObject($template);
        $this->assertEquals($template->name, 'Test planner template');

        $output = $object->get_templatelist($table, 'sdfsdfsdf', 10);
        $template = '';
        foreach ($output as $temp) {
            $template = $temp;
        }
        $this->assertEmpty($template);
    }

    /**
     * Creates and returns all the needed test data.
     *
     * @return \stdClass
     */
    private function setup_test_data() {
        // Create a course, quiz, template, and planner.
        $course = $this->getDataGenerator()->create_course(array('enablecompletion' => 1));
        $quiz = $this->getDataGenerator()->create_module('quiz', ['course' => $course->id,
            'grade' => 100.0, 'sumgrades' => 2, 'layout' => '1,2,0,3,4,0,5,6,0', 'navmethod' => 'free']);
        $templateid = $this->getDataGenerator()->get_plugin_generator('mod_planner')->create_template();
        $planner = $this->getDataGenerator()->create_module('planner',
            array('course' => $course->id, 'activitycmid' => $quiz->cmid, 'templateid' => $templateid));
        $planner = planner::create_planner_by_id($planner->id);
        $cm = get_coursemodule_from_instance('planner', $planner->id, $course->id, false, MUST_EXIST);
        $context = \context_module::instance($cm->id);
        $coursecontext = \context_course::instance($course->id);

        // Create 2 students, enrol them in the course and add them to a group.
        $student1 = $this->getDataGenerator()->create_user();
        $student2 = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($student1->id, $course->id, 'student');
        $this->getDataGenerator()->enrol_user($student2->id, $course->id, 'student');
        $group = $this->getDataGenerator()->create_group(array('courseid' => $course->id, 'name' => 'Test Group'));
        $this->getDataGenerator()->create_group_member(array('groupid' => $group->id, 'userid' => $student1->id));
        $this->getDataGenerator()->create_group_member(array('groupid' => $group->id, 'userid' => $student2->id));

        $data = new \stdClass();
        $data->course = $course;
        $data->quiz = $quiz;
        $data->templateid = $templateid;
        $data->planner = $planner;
        $data->cm = $cm;
        $data->context = $context;
        $data->coursecontext = $coursecontext;
        $data->student1 = $student1;
        $data->student2 = $student2;
        $data->group = $group;
        return $data;
    }

    /**
     * Returns the user step data for the given course module.
     *
     * @param object $cm
     * @param object $student1
     * @return array
     */
    private function get_user_step_data($cm, $student1) {
        global $DB;
        $templateuserstepdata = $DB->get_records_sql("SELECT pu.*,ps.name,ps.description FROM {planner_userstep} pu
         JOIN {planner_step} ps ON (ps.id = pu.stepid)
         WHERE ps.plannerid = '" . $cm->instance . "' AND pu.userid = '" .  $student1->id . "' ORDER BY pu.id ASC ");

        return $templateuserstepdata;
    }

    /**
     * Creates a test table.
     *
     * @return \flexible_table
     */
    private function create_test_table() {
        $pageurl = new \moodle_url('/mod/planner/template.php', array(
            'spage' => 0,
            'cid' => 0,
            'setting' => ''));
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

        $table = new \flexible_table('planner-template');
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
        return $table;
    }
}

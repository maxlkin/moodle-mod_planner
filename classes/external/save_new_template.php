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

namespace mod_planner\external;

use external_api;
use external_function_parameters;
use external_multiple_structure;
use external_value;
use mod_planner\planner;

/**
 * External service definitions for mod_planner.
 *
 * @package     mod_planner
 * @author      Jay Churchward <jay@brickfieldlabs.ie>
 * @copyright   2020 Brickfield Education Labs <jay@brickfieldlabs.ie>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class save_new_template extends external_api {
    /**
     * Describes the save_new_template parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'name' => new external_value(PARAM_TEXT, 'The name of the template.', VALUE_REQUIRED),
            'disclaimer' => new external_value(PARAM_RAW, 'The disclaimer of the template.', VALUE_REQUIRED),
            'personal' => new external_value(
                PARAM_INT,
                'A value representing if the template is personal or global.',
                VALUE_REQUIRED
            ),
            'stepname' => new external_multiple_structure(
                new external_value(PARAM_TEXT, 'The name of the step.'),
            ),
            'stepallocation' => new external_multiple_structure(
                new external_value(PARAM_INT, 'The value allocated to the step.'),
            ),
            'stepdescription' => new external_multiple_structure(
                new external_value(PARAM_RAW, 'The description of the step.'),
            ),
            'optionrepeats' => new external_value(PARAM_INT, 'The number of steps in the template.'),
            'courseid' => new external_value(PARAM_INT, 'The id of the course.', VALUE_REQUIRED),
        ]);
    }

    /**
     * Web service to insert a new template record.
     *
     * @param string $name
     * @param string $disclaimer
     * @param int $personal
     * @param array $stepname
     * @param array $stepallocation
     * @param array $stepdescription
     * @param int $optionrepeats
     * @param int $courseid
     * @return string
     */
    public static function execute(
        string $name,
        string $disclaimer,
        int $personal,
        array $stepname,
        array $stepallocation,
        array $stepdescription,
        int $optionrepeats,
        int $courseid
    ): string {
        $params = self::validate_parameters(
            self::execute_parameters(), [
                'name' => $name,
                'disclaimer' => $disclaimer,
                'personal' => $personal,
                'stepname' => $stepname,
                'stepallocation' => $stepallocation,
                'stepdescription' => $stepdescription,
                'optionrepeats' => $optionrepeats,
                'courseid' => $courseid,
            ]
        );

        // Check capability and context.
        $context = \context_course::instance($params['courseid']);
        static::validate_context($context);
        require_capability('mod/planner:managetemplates', $context);

        // Create the data array to be passed to the validation function.
        $data = [
            'name' => $params['name'],
            'disclaimer' => $params['disclaimer'],
            'personal' => $params['personal'],
            'stepname' => $params['stepname'],
            'stepallocation' => $params['stepallocation'],
            'stepdescription' => $params['stepdescription'],
            'optionrepeats' => $params['optionrepeats'],
            'submitbutton' => true,
        ];
        $errors = planner::validation($data);

        if (!empty($errors['name'])) {
            return $errors['name'];
        } else {
            // Create the template object to be passed to the insert function.
            $templatedata = new \stdClass();
            $templatedata->name = $params['name'];
            $templatedata->disclaimer['text'] = $params['disclaimer'];
            $templatedata->personal = $params['personal'];
            $templatedata->stepname = $params['stepname'];
            $templatedata->stepallocation = $params['stepallocation'];
            foreach ($params['stepdescription'] as $value) {
                $templatedata->stepdescription[]['text'] = $value;
            }
            $templatedata->option_repeats = $params['optionrepeats'];

            planner::insert_planner_template_step($templatedata);
            return '';
        }
    }

    /**
     * Describes the return structure of the service.
     *
     * @return external_value
     */
    public static function execute_returns(): external_value {
        return new external_value(PARAM_TEXT, 'An error message if there is one.');
    }
}

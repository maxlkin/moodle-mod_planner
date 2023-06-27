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
use external_single_structure;
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
class fetch_template_data extends external_api {
    /**
     * Describes the fetch_template_data parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'templateid' => new external_value(PARAM_INT, 'The id of the template.', VALUE_REQUIRED),
            'courseid' => new external_value(PARAM_INT, 'The id of the course.', VALUE_REQUIRED)
        ]);
    }

    /**
     * Web service to fetch a template record.
     *
     * @param int $templateid
     * @param int $courseid
     * @return array
     */
    public static function execute(int $templateid, int $courseid): array {
        $params = self::validate_parameters(
            self::execute_parameters(), [
                'templateid' => $templateid,
                'courseid' => $courseid
            ]
        );
        // Check capability and context.
        if ($params['courseid'] == 0) {
            $context = \context_system::instance();
            static::validate_context($context);
            require_capability('mod/planner:managetemplates', $context);
        } else {
            $context = \context_course::instance($params['courseid']);
            static::validate_context($context);
            require_capability('mod/planner:managetemplates', $context);
        }

        $data = planner::get_planner_template_step($params['templateid']);
        $data['plannertemplate'] = (array) $data['plannertemplate'];
        $i = 1;
        foreach ($data['plannertemplatesteps'] as $val) {
            $val = (array) $val;
            $val['stepnumber'] = $i;
            $steps[] = $val;
            $i++;
        }
        $data['plannertemplatesteps'] = $steps;
        return $data;
    }

    /**
     * Describes the return structure of the service.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'plannertemplate' => new external_single_structure(
               [
                   'id' => new external_value(PARAM_INT, 'The id of the step.'),
                   'userid' => new external_value(PARAM_INT, 'The id of the user.'),
                   'name' => new external_value(PARAM_TEXT, 'The name of the step.'),
                   'disclaimer' => new external_value(PARAM_RAW, 'The disclaimer of the template.'),
                   'personal' => new external_value(PARAM_INT, 'A value representing if the template is personal or global.'),
                   'status' => new external_value(PARAM_INT, 'The status of the step.'),
                   'copied' => new external_value(PARAM_INT, 'A value representing if the step has been copied.'),
                   'timecreated' => new external_value(PARAM_INT, 'The time the step was created.'),
                   'timemodified' => new external_value(PARAM_INT, 'The time the step was modified.'),
               ]
            ),
           'plannertemplatesteps' => new external_multiple_structure(
               new external_single_structure([
                   'id' => new external_value(PARAM_INT, 'The id of the step.'),
                   'plannerid' => new external_value(PARAM_INT, 'The id of the planner.'),
                   'name' => new external_value(PARAM_TEXT, 'The name of the step.'),
                   'timeallocation' => new external_value(PARAM_INT, 'The time allocation of the step.'),
                   'description' => new external_value(PARAM_RAW, 'The description of the step.'),
                   'stepnumber' => new external_value(PARAM_INT, 'The number of the step.'),
                ])
            )
        ]);
    }
}

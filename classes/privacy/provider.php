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

namespace mod_planner\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\deletion_criteria;
use core_privacy\local\request\helper;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;

/**
 * Privacy Subsystem implementation for mod_planner.
 *
 * @copyright 2021 Brickfield Education Labs, www.brickfield.ie
 * @package    mod_planner
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements
        // This plugin stores personal data.
        \core_privacy\local\metadata\provider,

        // This plugin is a core_user_data_provider.
        \core_privacy\local\request\plugin\provider,

        // This plugin is capable of determining which users have data within it.
        \core_privacy\local\request\core_userlist_provider {
    /**
     * Return the fields which contain personal data.
     *
     * @param collection $collection a reference to the collection to use to store the metadata.
     * @return collection the updated collection of metadata items.
     */
    public static function get_metadata(collection $collection) : collection {
            $planneruserstep = [
                'stepid'            => 'privacy:metadata:planner_userstep:stepid',
                'userid'            => 'privacy:metadata:planner_userstep:userid',
                'timestart'         => 'privacy:metadata:planner_userstep:timestart',
                'duedate'           => 'privacy:metadata:planner_userstep:duedate',
                'completionstatus'  => 'privacy:metadata:planner_userstep:completionstatus',
                'timemodified'      => 'privacy:metadata:planner_userstep:timemodified',
            ];
            $plannertemplate = [
                'userid'            => 'privacy:metadata:planner_template:userid',
                'name'              => 'privacy:metadata:planner_template:name',
                'disclaimer'        => 'privacy:metadata:planner_template:disclaimer',
                'personal'          => 'privacy:metadata:planner_template:personal',
                'status'            => 'privacy:metadata:planner_template:status',
                'copied'            => 'privacy:metadata:planner_template:copied',
                'timecreated'       => 'privacy:metadata:planner_template:timecreated',
                'timemodified'      => 'privacy:metadata:planner_template:timemodified',
            ];
            $collection->add_database_table('planner_userstep', $planneruserstep, 'privacy:metadata:planner_userstep');
            $collection->add_database_table('plannertemplate', $plannertemplate, 'privacy:metadata:planner_template');
            return $collection;
    }

    /**
     * Get the list of contexts that contain user information for the specified user.
     *
     * @param int $userid the userid.
     * @return contextlist the list of contexts containing user info for the user.
     */
    public static function get_contexts_for_userid(int $userid) : contextlist {
        // Fetch all planner steps.
        $sql = "SELECT c.id
                  FROM {context} c
            INNER JOIN {course_modules} cm ON cm.id = c.instanceid AND c.contextlevel = :contextlevel
            INNER JOIN {modules} m ON m.id = cm.module AND m.name = :modname
            INNER JOIN {planner} p ON p.id = cm.instance
            INNER JOIN {planner_step} ps ON ps.plannerid = p.id
            INNER JOIN {planner_userstep} pu ON pu.stepid = ps.id
                 WHERE pu.userid = :userid";

        $params = [
            'modname'       => 'planner',
            'contextlevel'  => CONTEXT_MODULE,
            'userid'        => $userid,
        ];
        $contextlist = new contextlist();
        $contextlist->add_from_sql($sql, $params);

        return $contextlist;
    }

    /**
     * Get the list of users who have data within a context.
     *
     * @param   userlist    $userlist   The userlist containing the list of users who have data in this context/plugin combination.
     */
    public static function get_users_in_context(userlist $userlist) {
        $context = $userlist->get_context();

        if (!$context instanceof \context_module) {
            return;
        }

        // Fetch all planner users.
        $sql = "SELECT pu.userid
                  FROM {course_modules} cm
                  JOIN {modules} m ON m.id = cm.module AND m.name = :modname
                  INNER JOIN {planner} p ON p.id = cm.instance
                  INNER JOIN {planner_step} ps ps.plannerid = p.id
                  INNER JOIN {planner_userstep} pu ON pu.stepid = ps.id
                 WHERE cm.id = :cmid";

        $params = [
            'cmid'      => $context->instanceid,
            'modname'   => 'planner',
        ];

        $userlist->add_from_sql('userid', $sql, $params);
    }

    /**
     * Export personal data for the given approved_contextlist. User and context information is contained within the contextlist.
     *
     * @param approved_contextlist $contextlist a list of contexts approved for export.
     */
    public static function export_user_data(approved_contextlist $contextlist) {
        global $DB;

        if (empty($contextlist->count())) {
            return;
        }

        $user = $contextlist->get_user();

        list($contextsql, $contextparams) = $DB->get_in_or_equal($contextlist->get_contextids(), SQL_PARAMS_NAMED);

        $sql = "SELECT cm.id AS cmid,
                       ps.name as stepname,
                       pu.timestart,
                       pu.duedate,
                       pu.completionstatus,
                       pu.timemodified
                  FROM {context} c
            INNER JOIN {course_modules} cm ON cm.id = c.instanceid AND c.contextlevel = :contextlevel
            INNER JOIN {modules} m ON m.id = cm.module AND m.name = :modname
            INNER JOIN {planner} p ON p.id = cm.instance
            INNER JOIN {planner_step} ps ps.plannerid = p.id
            INNER JOIN {planner_userstep} pu ON pu.stepid = ps.id
                 WHERE c.id {$contextsql}
                       AND pu.userid = :userid
              ORDER BY cm.id";

        $params = ['modname' => 'planner', 'contextlevel' => CONTEXT_MODULE, 'userid' => $user->id] + $contextparams;
        $lastcmid = null;

        $stepsdata = $DB->get_recordset_sql($sql, $params);
        foreach ($stepsdata as $step) {
            // If we've moved to a new choice, then write the last choice data and reinit the choice data array.
            if ($lastcmid != $step->cmid) {
                if (!empty($stepdata)) {
                    $context = \context_module::instance($lastcmid);
                    self::export_planner_data_for_user($stepdata, $context, $user);
                }

                if ($step->completionstatus == 1) {
                    $completionvalue = get_string('completed', 'planner');
                } else {
                    $completionvalue = get_string('pending', 'planner');
                }
                $stepdata = [
                    'stepname' => [],
                    'timestart' => \core_privacy\local\request\transform::datetime($step->timestart),
                    'duedate' => \core_privacy\local\request\transform::datetime($step->duedate),
                    'completionstatus' => $completionvalue,
                    'timemodified' => \core_privacy\local\request\transform::datetime($step->timemodified),
                ];
            }
            $stepdata['stepname'][] = $step->stepname;
            $lastcmid = $step->cmid;
        }
        $stepsdata->close();

        // The data for the last activity won't have been written yet, so make sure to write it now!
        if (!empty($stepdata)) {
            $context = \context_module::instance($lastcmid);
            self::export_planner_data_for_user($stepdata, $context, $user);
        }
    }

    /**
     * Export the supplied personal data for a planner activity, along with any generic data or area files.
     *
     * @param array $plannerdata the personal data to export for the planner.
     * @param \context_module $context the context of the planner.
     * @param \stdClass $user the user record
     */
    protected static function export_planner_data_for_user(array $plannerdata, \context_module $context, \stdClass $user) {
        // Fetch the generic module data for the planner.
        $contextdata = helper::get_context_data($context, $user);

        // Merge with planner data and write it.
        $contextdata = (object)array_merge((array)$contextdata, $plannerdata);
        writer::with_context($context)->export_data([], $contextdata);
    }

    /**
     * Delete all data for all users in the specified context.
     *
     * @param \context $context the context to delete in.
     */
    public static function delete_data_for_all_users_in_context(\context $context) {
        global $DB;

        if (!$context instanceof \context_module) {
            return;
        }

        if ($cm = get_coursemodule_from_id('planner', $context->instanceid)) {
            if ($stepsdata = $DB->get_records("planner_step", array("plannerid" => $cm->instance))) {
                foreach ($stepsdata as $step) {
                    $DB->delete_records('planner_userstep', array('stepid' => $step->id));
                }
            }
        }
    }

    /**
     * Delete all user data for the specified user, in the specified contexts.
     *
     * @param approved_contextlist $contextlist a list of contexts approved for deletion.
     */
    public static function delete_data_for_user(approved_contextlist $contextlist) {
        global $DB;

        if (empty($contextlist->count())) {
            return;
        }

        $userid = $contextlist->get_user()->id;
        foreach ($contextlist->get_contexts() as $context) {

            if (!$context instanceof \context_module) {
                continue;
            }
            $instanceid = $DB->get_field('course_modules', 'instance', ['id' => $context->instanceid]);
            if (!$instanceid) {
                continue;
            }
            if ($stepsdata = $DB->get_records("planner_step", array("plannerid" => $instanceid))) {
                foreach ($stepsdata as $step) {
                    $DB->delete_records('planner_userstep', array('stepid' => $step->id, 'userid' => $userid));
                }
            }
        }
    }

    /**
     * Delete multiple users within a single context.
     *
     * @param   approved_userlist       $userlist The approved context and user information to delete information for.
     */
    public static function delete_data_for_users(approved_userlist $userlist) {
        global $DB;

        $context = $userlist->get_context();

        if (!$context instanceof \context_module) {
            return;
        }

        $cm = get_coursemodule_from_id('planner', $context->instanceid);

        if (!$cm) {
            // Only planner module will be handled.
            return;
        }

        $userids = $userlist->get_userids();
        list($usersql, $userparams) = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED);

        if ($stepsdata = $DB->get_records("planner_step", array("plannerid" => $cm->instance))) {
            foreach ($stepsdata as $step) {
                $select = "stepid = :stepid AND userid $usersql";
                $params = ['stepid' => $step->id] + $userparams;
                $DB->delete_records_select('planner_userstep', $select, $params);
            }
        }
    }
}

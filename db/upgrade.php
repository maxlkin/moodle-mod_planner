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
 * Planner module upgrade
 *
 * @copyright 2021 Brickfield Education Labs, www.brickfield.ie
 * @package mod_planner
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// This file keeps track of upgrades to
// the planner module
//
// Sometimes, changes between versions involve
// alterations to database structures and other
// major things that may break installations.
//
// The upgrade function in this file will attempt
// to perform all the necessary actions to upgrade
// your older installation to the current version.
//
// If there's something it cannot do itself, it
// will tell you what you need to do.
//
// The commands in here will all be database-neutral,
// using the methods of database_manager class
//
// Please do not forget to use upgrade_set_timeout()
// before any action that may take longer time to finish.

/**
 * Upgrades planner
 *
 * @param int $oldversion
 * @return void
 */
function xmldb_planner_upgrade($oldversion) {
    global $DB;

    // Check for duplicate template names and append a number to the end of the name if there are duplicates.
    if ($oldversion < 2023041701.06) {
        $records = $DB->get_records('plannertemplate');
        $namecount = array_count_values(array_column($records, 'name'));

        foreach ($records as $record) {
            $names = array_column($records, 'name');
            $name = $record->name;
            $count = $namecount[$name];

            // If count is greater than 1, than the name is not unique.
            if ($count > 1) {
                $updatedname = $name . ' (' . $count . ')';

                // Check if the updated name is unique.
                $unique = false;
                while (!$unique) {
                    if (!in_array($updatedname, $names)) {
                        $unique = true;
                    } else {
                        $updatedname = $name . ' (' . ++$count . ')';
                    }
                }
                $record->name = $updatedname;
                $namecount[$name]--;
                $DB->set_field('plannertemplate', 'name', $updatedname, ['id' => $record->id]);
            }
        }
        upgrade_plugin_savepoint(true, 2023041701.06, 'mod', 'planner');
    }

    return true;
}

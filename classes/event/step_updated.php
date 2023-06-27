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

namespace mod_planner\event;

/**
 * The mod_quiz attempt reviewed event.
 *
 * @copyright 2021 Brickfield Education Labs, www.brickfield.ie
 * @package    mod_planner
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class step_updated extends \core\event\base {

    /**
     * Init method.
     */
    protected function init() {
        $this->data['objecttable'] = 'planner_userstep';
        $this->data['crud'] = 'r';
        $this->data['edulevel'] = self::LEVEL_PARTICIPATING;
    }

    /**
     * Returns localised general event name.
     *
     * @return string
     */
    public static function get_name(): string {
        return get_string('stepupdated', 'mod_planner');
    }

    /**
     * Returns description of what happened.
     *
     * @return string
     */
    public function get_description(): string {
        $params = new \stdClass();
        $params->userid = $this->userid;
        $params->relateduserid = $this->relateduserid;
        $params->plannerid = $this->other['plannerid'];
        $params->cmid = $this->contextinstanceid;
        return get_string('event:stepupdated', 'mod_planner', $params);
    }

    /**
     * Returns relevant URL.
     *
     * @return \moodle_url
     */
    public function get_url(): \moodle_url {
        return new \moodle_url('/mod/planner/view.php', ['id' => $this->contextinstanceid]);
    }

    /**
     * Return the legacy event log data.
     *
     * @return array
     */
    protected function get_legacy_logdata(): array {
        return [
            $this->courseid,
            'planner',
            'view',
            'view.php?id=' . $this->objectid,
            $this->other['plannerid'],
            $this->contextinstanceid
        ];
    }

    /**
     * Custom validation.
     *
     * @throws \coding_exception
     * @return void
     */
    protected function validate_data(): void {
        parent::validate_data();

        if (!isset($this->relateduserid)) {
            throw new \coding_exception(get_string('event:userexception', 'mod_planner'));
        }

        if (!isset($this->other['plannerid'])) {
            throw new \coding_exception(get_string('event:plannerexception', 'mod_planner'));
        }
    }
}

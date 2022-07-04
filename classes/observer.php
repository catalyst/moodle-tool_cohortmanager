<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

namespace tool_cohortmanager;

use core\event\base;

/**
 * Event observer class.
 *
 * @package     tool_cohortmanager
 * @copyright   2022 Catalyst IT
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class observer {

    /**
     * Process event based rules.
     *
     * @param base $event The event.
     */
    public static function process_event(base $event): void {
        foreach (helper::get_conditions_with_event($event) as $condition) {
            foreach (helper::get_rules_with_condition($condition) as $rule) {
                helper::process_rule($rule, self::get_userid_from_event($event));
            }
        }
    }

    /**
     * Gets user id from the event.
     *
     * @param \core\event\base $event Triggered event.
     * @return int
     */
    protected static function get_userid_from_event(base $event): int {
        $data = $event->get_data();

        if (array_key_exists('relateduserid', $data)) {
            $userid = $data['relateduserid'];
        } else {
            $userid = $data['userid'];
        }

        return $userid;
    }

}

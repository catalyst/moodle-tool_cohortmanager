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

/**
 * Event observer class.
 *
 * @package     tool_cohortmanager
 * @category    event
 * @copyright   2022 Catalyst IT
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class observer {

    /**
     * Triggered via $event.
     *
     * @param \core\event\user_loggedin $event The event.
     * @return bool True on success.
     */
    public static function user_loggedin($event) {

        // For more information about the Events API please visit {@link https://docs.moodle.org/dev/Events_API}.

        return true;
    }

    /**
     * Triggered via $event.
     *
     * @param \core\event\user_loggedinas $event The event.
     * @return bool True on success.
     */
    public static function user_loggedinas($event) {

        // For more information about the Events API please visit {@link https://docs.moodle.org/dev/Events_API}.

        return true;
    }

    /**
     * Triggered via $event.
     *
     * @param \core\event\user_created $event The event.
     * @return bool True on success.
     */
    public static function user_created($event) {

        // For more information about the Events API please visit {@link https://docs.moodle.org/dev/Events_API}.

        return true;
    }

    /**
     * Triggered via $event.
     *
     * @param \core\event\user_updated $event The event.
     * @return bool True on success.
     */
    public static function user_updated($event) {

        // For more information about the Events API please visit {@link https://docs.moodle.org/dev/Events_API}.

        return true;
    }
}

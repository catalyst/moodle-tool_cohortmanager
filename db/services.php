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
 * List of Web Services for the plugin.
 *
 * @package     tool_cohortmanager
 * @copyright   2022 Catalyst IT
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$functions = [
    'tool_cohortmanager_submit_condition_form' => [
        'classname'       => 'tool_cohortmanager\external\condition_form',
        'methodname'      => 'submit',
        'description'     => 'Submits condition form',
        'type'            => 'read',
        'capabilities'    => 'tool/cohortmanager:managerules',
        'ajax'            => true,
    ],
    'tool_cohortmanager_get_total_matching_users_for_rule' => [
        'classname'       => 'tool_cohortmanager\external\matching_users',
        'methodname'      => 'get_total',
        'description'     => 'Returns a number of matching users for provided rule ',
        'type'            => 'read',
        'capabilities'    => 'tool/cohortmanager:managerules',
        'ajax'            => true,
    ],
];

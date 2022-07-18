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

namespace tool_cohortmanager\external;

use context_system;
use external_api;
use external_function_parameters;
use external_value;
use tool_cohortmanager\helper;
use tool_cohortmanager\rule;
use invalid_parameter_exception;

/**
 * Matching users external APIs.
 *
 * @package    tool_cohortmanager
 * @author     Dmitrii Metelkin <dmitriim@catalyst-au.net>
 * @copyright  2022 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class matching_users extends external_api {

    /**
     * Describes the parameters for validate_form webservice.
     * @return external_function_parameters
     */
    public static function get_total_parameters(): external_function_parameters {
        return new external_function_parameters([
            'ruleid' => new external_value(PARAM_INT, 'The condition class being submitted')
        ]);
    }

    /**
     * Gets a total number of matching users for provided rule.
     *
     * @param int $ruleid Rule Id number.
     * @return int
     */
    public static function get_total(int $ruleid): int {
        $params = self::validate_parameters(self::get_total_parameters(), ['ruleid' => $ruleid]);

        self::validate_context(context_system::instance());
        require_capability('tool/cohortmanager:managerules', context_system::instance());

        $rule = rule::get_record(['id' => $params['ruleid']]);

        if (empty($rule)) {
            throw new invalid_parameter_exception('Rule does not exist');
        }

        return count(helper::get_matching_users($rule));
    }

    /**
     * Returns description of method result value.
     *
     * @return external_value
     */
    public static function get_total_returns(): external_value {
        return new external_value(PARAM_INT, 'Total number of matching users for provided rule');
    }
}

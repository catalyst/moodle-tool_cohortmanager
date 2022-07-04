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

namespace tool_cohortmanager\task;

use \core\task\adhoc_task;
use moodle_exception;
use tool_cohortmanager\helper;
use tool_cohortmanager\rule;

/**
 * Processing a single rules.
 *
 * @package    tool_cohortmanager
 * @author     Dmitrii Metelkin <dmitriim@catalyst-au.net>
 * @copyright  2022 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class process_rule extends adhoc_task {

    /**
     * Task execution
     */
    public function execute() {
        $ruleid = $this->get_custom_data();

        try {
            $rule = rule::get_record(['id' => $ruleid]);
        } catch (moodle_exception $e) {
            mtrace("Processing cohort manager rules: rule with ID  {$ruleid} is not found.");
            return;
        }

        mtrace("Processing cohort manager rules: processing rule with id  {$ruleid}");
        helper::process_rule($rule);
    }
}

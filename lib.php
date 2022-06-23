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
 * Callbacks.
 *
 * @package    tool_cohortmanager
 * @author     Dmitrii Metelkin <dmitriim@catalyst-au.net>
 * @copyright  2022 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use core\output\inplace_editable;
use tool_cohortmanager\rule;
use tool_cohortmanager\output\inplace_editable_rule_name;
use tool_cohortmanager\output\inplace_editable_rule_description;
use tool_cohortmanager\output\inplace_editable_rule_enabled;

/**
 * Manage inplace editable saves.
 *
 * @param string $itemtype The type of item.
 * @param int $itemid The ID of the item.
 * @param mixed $newvalue The new value
 *
 * @return ? inplace_editable
 */
function tool_cohortmanager_inplace_editable(string $itemtype, int $itemid, $newvalue): ?inplace_editable {
    $context = \context_system::instance();
    external_api::validate_context($context);

    switch ($itemtype) {
        case 'rulename':
            $rule = rule::get_record(['id' => $itemid]);
            $newvalue = clean_param($newvalue, PARAM_TEXT);
            if (!empty($newvalue)) {
                $rule->set('name', $newvalue);
                $rule->save();
            }
            return new inplace_editable_rule_name($rule);
        case 'ruleenabled':
            $rule = rule::get_record(['id' => $itemid]);
            $rule->set('enabled', $newvalue);
            $rule->save();
            return new inplace_editable_rule_enabled($rule);
        case 'ruledescription':
            $rule = rule::get_record(['id' => $itemid]);
            $rule->set('description', $newvalue);
            $rule->save();
            return new inplace_editable_rule_description($rule);
    }

    return null;
}
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
use tool_cohortmanager\event\rule_updated;
use tool_cohortmanager\output\inplace_editable_rule_name;
use tool_cohortmanager\output\inplace_editable_rule_description;
use tool_cohortmanager\output\inplace_editable_rule_enabled;
use tool_cohortmanager\condition_form;

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
                rule_updated::create(['other' => ['ruleid' => $rule->get('id')]])->trigger();
            }
            return new inplace_editable_rule_name($rule);
        case 'ruleenabled':
            $rule = rule::get_record(['id' => $itemid]);
            if (!$rule->is_broken()) {
                $rule->set('enabled', $newvalue);
                $rule->save();
                rule_updated::create(['other' => ['ruleid' => $rule->get('id')]])->trigger();
            }
            return new inplace_editable_rule_enabled($rule);
        case 'ruledescription':
            $rule = rule::get_record(['id' => $itemid]);
            $rule->set('description', $newvalue);
            $rule->save();
            rule_updated::create(['other' => ['ruleid' => $rule->get('id')]])->trigger();
            return new inplace_editable_rule_description($rule);
    }

    return null;
}

/**
 * Serve the new condition form as a fragment.
 *
 * @param array $args List of named arguments for the fragment loader.
 * @return string
 */
function tool_cohortmanager_output_fragment_condition_form(array $args): string {
    $args = (object) $args;

    $classname = clean_param($args->classname, PARAM_RAW);

    $ajaxdata = [];
    if (!empty($args->jsonformdata)) {
        $serialiseddata = json_decode($args->jsonformdata);
        parse_str($serialiseddata, $ajaxdata);
    }

    $mform = new condition_form(null, ['classname' => $classname], 'post', '', null, true, $ajaxdata);

    unset($ajaxdata['classname']);

    if (!empty($args->defaults)) {
        $data = json_decode($args->defaults, true);
        if (!empty($data)) {
            $confifdata = json_decode($data['configdata']);
            $data = $data + (array)$confifdata;
            $mform->set_data($data);
        }
    }

    if (!empty($ajaxdata)) {
        $mform->is_validated();
    }

    return $mform->render();
}


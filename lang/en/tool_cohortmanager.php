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

/**
 * Plugin strings are defined here.
 *
 * @package     tool_cohortmanager
 * @category    string
 * @copyright   2022 Catalyst IT
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['addrule'] = 'Add a new rule';
$string['addcondition'] = 'Add a condition';
$string['add_breadcrumb'] = 'Add new rule';
$string['broken'] = 'Broken';
$string['brokenruleswarning'] = 'There are some broken rules require your attention.  <br /> To fix a broken rule you should remove all broken conditions. <br />Sometimes a rule becomes broken when matching users SQL failed. In this case all condition are ok, but the rule is marked as broken. You should check Moodle logs for "Matching users failed" event and related SQL errors. <br />Please note, that in any case you have to re-save the rule to mark it as unbroken.';
$string['cachedef_rules'] = 'Cohort manager rules cache.';
$string['cohortmanager:managerules'] = 'Manage rules';
$string['cohortid'] = 'Cohort';
$string['cohortid_help'] = 'A cohort to manage as part of this rule. Only cohorts that are not managed by other plugins are displayed in this list.';
$string['conditions'] = 'Conditions';
$string['conditionformtitle'] = 'Rule condition';
$string['conditionchnagesnotapplied'] = 'Condition changes are not applied until you save the rule form';
$string['condition_user_profile']  = 'User standard profile field';
$string['condition_user_custom_profile'] = 'User custom profile field';
$string['delete_breadcrumb'] = 'Delete rule';
$string['delete_confirm'] = 'Are you sure you want to delete rule {$a}?';
$string['description'] = 'Description';
$string['description_help'] = 'As short description of this rule';
$string['edit_breadcrumb'] = 'Edit rule';
$string['enabled'] = 'Rule enabled';
$string['enabled_help'] = 'Only enabled rules will be processed';
$string['event:conditioncreated'] = 'Condition created';
$string['event:conditiondeleted'] = 'Condition deleted';
$string['event:conditionupdated'] = 'Condition updated';
$string['event:matchingfailed'] = 'Matching users failed';
$string['event:rulecreated'] = 'Rule created';
$string['event:ruleupdated'] = 'Rule updated';
$string['event:ruledeleted'] = 'Rule deleted';
$string['invalidfieldvalue'] = 'Invalid field value';
$string['includingmissingdatadesc'] = '(including users with missing data)';
$string['includeusersmissingdata'] = 'include users with missing data';
$string['include_missing_data_help'] = 'Some users may not have a custom field data set yet. This option includes those user in the final result.';
$string['isnotempty'] = 'is not empty';
$string['managerules'] = 'Manage dynamic cohort rules';
$string['managecohorts'] = 'Manage cohorts';
$string['matchingusers'] = 'Matching users';
$string['name'] = 'Rule name';
$string['name_help'] = 'A human readable name of this rule.';
$string['pleaseselectfield'] = 'Please select a field';
$string['pluginname'] = 'Cohort manager';
$string['privacy:metadata:tool_cohortmanager'] = 'Information about cohort rules created or updated by a user';
$string['privacy:metadata:tool_cohortmanager:name'] = 'Rule name';
$string['privacy:metadata:tool_cohortmanager:usermodified'] = 'The ID of the user who created or updated a rule';
$string['privacy:metadata:tool_cohortmanager_cond'] = 'Information cohort rule conditions created or updated by a user';
$string['privacy:metadata:tool_cohortmanager_cond:ruleid'] = 'ID of the rule name';
$string['privacy:metadata:tool_cohortmanager_cond:usermodified'] = 'The ID of the user who created or updated a condition';
$string['profilefield'] = 'Profile field';
$string['processrulestask'] = 'Process all rules';
$string['ruledisabledpleasereview'] = 'Newly created or updated rules are disabled by default. Please review the rule below and enable it when ready.';
$string['usersforrule'] = 'Users match rule "{$a}"';

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

namespace tool_cohortmanager;

use core\event\base;
use core_component;
use moodle_exception;
use moodle_url;
use tool_cohortmanager\event\condition_created;
use tool_cohortmanager\event\condition_deleted;
use tool_cohortmanager\event\condition_updated;
use tool_cohortmanager\event\matching_failed;
use tool_cohortmanager\event\rule_created;
use tool_cohortmanager\event\rule_deleted;
use tool_cohortmanager\event\rule_updated;
use tool_cohortmanager\output\renderer;
use cache;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/cohort/lib.php');

/**
 * Helper class.
 *
 * @package    tool_cohortmanager
 * @author     Dmitrii Metelkin <dmitriim@catalyst-au.net>
 * @copyright  2022 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class helper {

    /**
     * Cohort component to be set in {cohort} table.
     */
    const COHORT_COMPONENT = 'tool_cohortmanager';

    /**
     * Get a list of all conditions.
     *
     * @return condition_base[]
     */
    public static function get_all_conditions(): array {
        $instances = [];
        $classes = core_component::get_component_classes_in_namespace(null, '\\tool_cohortmanager\\condition');

        foreach (array_keys($classes) as $class) {
            $reflectionclass = new \ReflectionClass($class);
            if (!$reflectionclass->isAbstract()) {
                $instance = $class::get_instance();
                if (!$instance->is_broken()) {
                    $instances[$class] = $class::get_instance();
                }
            }
        }

        // Sort conditions by name.
        uasort($instances, function(condition_base $a, condition_base $b) {
            return ($a->get_name() > $b->get_name());
        });

        return $instances;
    }

    /**
     * A helper method for processing rule form data.
     *
     * @param \stdClass $formdata Data received from rule_form.
     * @return rule Rule instance.
     */
    public static function process_rule_form(\stdClass $formdata): rule {
        global $DB;

        $formdata->enabled = 0;
        self::validate_rule_data($formdata);

        $ruledata = (object) [
            'name' => $formdata->name,
            'enabled' => $formdata->enabled,
            'cohortid' => $formdata->cohortid,
            'description' => $formdata->description,
        ];

        $oldcohortid = 0;

        $transaction = $DB->start_delegated_transaction();

        try {
            if (empty($formdata->id)) {
                $rule = new rule(0, $ruledata);
                $rule->create();
                rule_created::create(['other' => ['ruleid' => $rule->get('id')]])->trigger();
            } else {
                $rule = new rule($formdata->id);
                $oldcohortid = $rule->get('cohortid');
                $rule->from_record($ruledata);
                $rule->update();
                rule_updated::create(['other' => ['ruleid' => $rule->get('id')]])->trigger();
            }

            self::unmanage_cohort($oldcohortid);
            self::manage_cohort($formdata->cohortid);
            self::process_rule_conditions($rule, $formdata);

            if ($rule->is_broken(true)) {
                $rule->mark_broken();
            } else {
                $rule->mark_unbroken();
            }

            $transaction->allow_commit();
            return $rule;
        } catch (\Exception $exception) {
            $transaction->rollback($exception);
            throw new $exception;
        }
    }

    /**
     * Validate submitted rule data.
     *
     * @param \stdClass $formdata Data received from rule_form.
     */
    protected static function validate_rule_data(\stdClass $formdata): void {
        // Go through all rule persistent fields excluding system fields to make sure
        // we get only required fields to check form data against.
        $requiredfields = array_diff(
            array_keys(rule::properties_definition()),
            ['id', 'broken', 'usermodified', 'timecreated', 'timemodified']
        );

        foreach ($requiredfields as $field) {
            if (!isset($formdata->{$field})) {
                throw new moodle_exception('Invalid rule data. Missing field: ' . $field);
            }
        }

        if (!key_exists($formdata->cohortid, self::get_available_cohorts())) {
            throw new moodle_exception('Invalid rule data. Cohort is invalid: ' . $formdata->cohortid);
        }

        if (!isset($formdata->conditionjson)) {
            throw new moodle_exception('Invalid rule data. Missing condition data.');
        }
    }

    /**
     * Process conditions for submitted rule.
     *
     * @param rule $rule Rule instance/
     * @param \stdClass $formdata Data received from rule_form.
     */
    protected static function process_rule_conditions(rule $rule, \stdClass $formdata): void {
        if (!empty($formdata->isconditionschanged)) {
            $submittedconditions = self::process_condition_json($formdata->conditionjson);
            $oldconditions = $rule->get_condition_records();

            $toupdate = [];
            foreach ($submittedconditions as $condition) {
                if (empty($condition->get('id'))) {
                    $condition->set('ruleid', $rule->get('id'));
                    $condition->create();
                    self::trigger_condition_event(condition_created::class, $condition, []);
                } else {
                    $toupdate[$condition->get('id')] = $condition;
                }
            }

            $todelete = array_diff_key($oldconditions, $toupdate);

            foreach ($todelete as $conditiontodelete) {
                $conditiontodelete->delete();
                self::trigger_condition_event(condition_deleted::class, $conditiontodelete, []);
            }

            foreach ($toupdate as $conditiontoupdate) {
                $olddescription = $conditiontoupdate->get('configdata');
                $instance = condition_base::get_instance(0, $conditiontoupdate->to_record());
                if ($instance && !$instance->is_broken()) {
                    $olddescription = $instance->get_config_description();
                }

                $conditiontoupdate->save();

                self::trigger_condition_event(condition_updated::class, $conditiontoupdate, [
                    'other' => [
                        'olddescription' => $olddescription,
                    ],
                ]);
            }
        }
    }

    /**
     * Take JSON from the form and return a list of condition persistents.
     * @param string $formjson Conditions JSON string from the rule form.
     *
     * @return condition[]
     */
    protected static function process_condition_json(string $formjson): array {
        // Get only required fields for condition persistent.
        $requiredconditionfield = array_diff(
            array_keys(condition::properties_definition()),
            ['ruleid', 'usermodified', 'timecreated', 'timemodified']
        );

        $formjson = json_decode($formjson, true);
        $submittedrecords = [];

        if (is_array($formjson)) {
            // Filter out submitted conditions data to only fields required for condition persistent.
            $submittedrecords = array_map(function (array $record) use ($requiredconditionfield): array {
                return array_intersect_key($record, array_flip($requiredconditionfield));
            }, $formjson);
        }

        $conditions = [];
        foreach ($submittedrecords as $submittedrecord) {
            $conditions[] = new condition($submittedrecord['id'], (object)$submittedrecord);
        }

        return $conditions;
    }

    /**
     * Delete rule.
     *
     * @param \tool_cohortmanager\rule $rule
     */
    public static function delete_rule(rule $rule) {
        $oldruleid = $rule->get('id');
        $conditions = $rule->get_condition_records();

        if ($rule->delete()) {
            rule_deleted::create(['other' => ['ruleid' => $oldruleid]])->trigger();

            // Delete related condition in a loop to be able to trigger events.
            foreach ($conditions as $condition) {
                $condition->delete();
                self::trigger_condition_event(condition_deleted::class, $condition, [
                    'other' => [
                        'ruleid' => $oldruleid
                    ],
                ]);
            }

            self::unmanage_cohort($rule->get('cohortid'));
        }
    }

    /**
     * Trigger condition related event.
     *
     * @param string $eventclass Full event class name, e.g. \tool_cohortmanager\event\condition_updated.
     * @param condition $condition Related condition object.
     * @param array $data Event related data.
     */
    protected static function trigger_condition_event(string $eventclass, condition $condition, array $data): void {
        $instance = condition_base::get_instance(0, $condition->to_record());

        if (!isset($data['other']['ruleid'])) {
            $data['other']['ruleid'] = $condition->get('ruleid');
        }

        // In case that the class related to that condition is not found,
        // we use data that we know about that condition such as class name and raw config.
        if (!$instance) {
            $name = $condition->get('classname');
            $description = $condition->get('configdata');
        } else {
            $name = $instance->get_name();
            $description = $instance->is_broken() ? $condition->get('configdata') : $instance->get_config_description();
        }

        $data['other']['name'] = $name;
        $data['other']['description'] = $description;

        $eventclass::create($data)->trigger();
    }

    /**
     * Unset cohort from being managed by tool_cohortmanager.
     *
     * @param int $cohortid Cohort ID.
     */
    public static function unmanage_cohort(int $cohortid): void {
        $cohorts = self::get_available_cohorts();

        if (!empty($cohorts[$cohortid])) {
            $cohort = $cohorts[$cohortid];
            $cohort->component = '';
            cohort_update_cohort($cohort);
        }
    }

    /**
     * Set cohort to be managed by tool_cohortmanager.
     *
     * @param int $cohortid Cohort ID.
     */
    public static function manage_cohort(int $cohortid): void {
        $cohorts = self::get_available_cohorts();
        if (!empty($cohorts[$cohortid])) {
            $cohort = $cohorts[$cohortid];

            if ($cohort->component === self::COHORT_COMPONENT) {
                throw new moodle_exception('Cohort ' . $cohortid . ' is already managed by tool_cohortmanager');
            }

            $cohort->component = 'tool_cohortmanager';
            cohort_update_cohort($cohort);
        }
    }

    /**
     * Get a list of all cohort names in the system keyed by cohort ID.
     *
     * @param bool $excludemanaged Exclude cohorts managed by us.
     * @return array
     */
    public static function get_available_cohorts(bool $excludemanaged = false): array {
        $cohorts = [];
        foreach (\cohort_get_all_cohorts(0, 0)['cohorts'] as $cohort) {
            if (empty($cohort->component) || (!$excludemanaged && $cohort->component === self::COHORT_COMPONENT)) {
                $cohorts[$cohort->id] = $cohort;
            }
        }

        return $cohorts;
    }

    /**
     * Builds rule edit URL.
     *
     * @param rule $rule Rule instance.
     * @return moodle_url
     */
    public static function build_rule_edit_url(rule $rule): moodle_url {
        return new moodle_url('/admin/tool/cohortmanager/edit.php', ['ruleid' => $rule->get('id')]);
    }

    /**
     * Builds rule delete URL.
     *
     * @param rule $rule Rule instance.
     * @return moodle_url
     */
    public static function build_rule_delete_url(rule $rule): moodle_url {
        return new \moodle_url('/admin/tool/cohortmanager/delete.php', [
            'ruleid' => $rule->get('id'),
            'sesskey' => sesskey()
        ]);
    }

    /**
     * Builds URL to see matching users.
     *
     * @param rule $rule Rule instance.
     * @return moodle_url
     */
    public static function build_users_url(rule $rule): moodle_url {
        return new moodle_url('/admin/tool/cohortmanager/users.php', ['ruleid' => $rule->get('id')]);
    }

    /**
     * Returns plugin render.
     *
     * @return renderer
     */
    public static function get_renderer(): renderer {
        global $PAGE;

        return $PAGE->get_renderer('tool_cohortmanager');
    }

    /**
     * Build data for setting into a rule form as default values.
     *
     * @param rule $rule Rule to build a data for.
     * @return array
     */
    public static function build_rule_data_for_form(rule $rule): array {
        $data = (array)$rule->to_record();
        $data['conditionjson'] = '';

        $conditions = [];

        foreach ($rule->get_condition_records() as $condition) {
            $instance = condition_base::get_instance(0, $condition->to_record());

            if (!$instance) {
                $name = $condition->get('classname');
                $description = $condition->get('configdata');
            } else {
                $name = $instance->get_name();
                $description = $instance->is_broken() ? $condition->get('configdata') : $instance->get_config_description();
            }

            $conditions[] = (array)$condition->to_record() +
                ['description' => $description] +
                ['name' => $name];
        }

        if (!empty($conditions)) {
            $data['conditionjson'] = json_encode($conditions);
        }

        return $data;
    }

    /**
     * Generate an alias for prepending parameters in SQL.
     *
     * @return string
     */
    public static function generate_param_alias(): string {
        static $cnt = 0;
        return 'tcmp' . ($cnt++);
    }

    /**
     * Generating an alias for tables in SQLs.
     *
     * @return string
     */
    public static function generate_table_alias(): string {
        static $cnt = 0;
        return 'tcmf' . ($cnt++);
    }

    /**
     * Returns a list of all matching users for provided rule.
     *
     * @param rule $rule A rule to get a list of users.
     * @param ?int $userid Optional user ID if we need to check just one user.
     *
     * @return array
     */
    public static function get_matching_users(rule $rule, ?int $userid = null): array {
        global $DB;

        $conditions = $rule->get_condition_records();

        if (empty($conditions)) {
            return [];
        }

        $where = ' u.deleted = 0 ';
        $join = '';
        $params = [];

        $sql = "SELECT DISTINCT u.id FROM {user} u";

        foreach ($conditions as $condition) {
            try {
                $instance = condition_base::get_instance(0, $condition->to_record());

                if (!$instance || $instance->is_broken()) {
                    return [];
                }

                $sqldata = $instance->get_sql_data();

                if (!empty($sqldata->get_join())) {
                    $join .= ' ' . $sqldata->get_join();
                }

                if (!empty($sqldata->get_where())) {
                    $where .= ' AND (' . $sqldata->get_where() . ')';
                }

                if (!empty($sqldata->get_params())) {
                    $params += $sqldata->get_params();
                }
            } catch (\Exception $exception ) {
                matching_failed::create([
                    'other' => [
                        'ruleid' => $rule->get('id'),
                        'error' => $exception->getMessage()
                    ]
                ])->trigger();

                $rule->mark_broken();
                return [];
            }
        }

        if ($userid) {
            $userparam = self::generate_param_alias();
            $where .= " AND u.id = :{$userparam} ";
            $params += [$userparam => $userid];
        }

        try {
            return $DB->get_records_sql($sql . $join . ' WHERE ' . $where, $params);
        } catch (\Exception $exception) {
            matching_failed::create([
                'other' => [
                    'ruleid' => $rule->get('id'),
                    'error' => $exception->getMessage()
                ]
            ])->trigger();

            $rule->mark_broken();
            return  [];
        }
    }

    /**
     * Process a given rule.
     *
     * @param rule $rule A rule to process.
     * @param ?int $userid Optional user ID for processing a rule just for a single user.
     */
    public static function process_rule(rule $rule, ?int $userid = null): void {
        global $DB;

        if (!$rule->is_enabled() || $rule->is_broken()) {
            return;
        }

        if ($rule->is_broken(true)) {
            $rule->mark_broken();
            return;
        }

        if (!$DB->record_exists('cohort', ['id' => $rule->get('cohortid')])) {
            $rule->mark_broken();
            return;
        }

        $conditions = $rule->get_condition_records();

        if (empty($conditions)) {
            return;
        }

        $users = self::get_matching_users($rule, $userid);

        $cohortmembersparams = ['cohortid' => $rule->get('cohortid')];
        if (!empty($userid)) {
            $cohortmembersparams['userid'] = $userid;
        }

        $cohortmembers = $DB->get_records('cohort_members', $cohortmembersparams, '', 'userid');

        $userstoadd = array_diff_key($users, $cohortmembers);
        $userstodelete = array_diff_key($cohortmembers, $users);

        foreach ($userstoadd as $user) {
            cohort_add_member($rule->get('cohortid'), $user->id);
        }

        foreach ($userstodelete as $user) {
            cohort_remove_member($rule->get('cohortid'), $user->userid);
        }
    }

    /**
     * Gets a list of conditions subscribed for the given event.
     *
     * @param \core\event\base $event Event.
     * @return array
     */
    public static function get_conditions_with_event(base $event): array {
        $conditionswithevent = [];

        foreach (self::get_all_conditions() as $condition) {
            $class = get_class($event);
            foreach ($condition->get_events() as $eventclass) {
                if (ltrim($class, '\\') === ltrim($eventclass, '\\')) {
                    $conditionswithevent[] = $condition;
                    break;
                }
            }
        }

        return $conditionswithevent;
    }

    /**
     * Returns a list of rules with provided condition.
     *
     * @param \tool_cohortmanager\condition_base $condition Condition to check.
     * @return rule[]
     */
    public static function get_rules_with_condition(condition_base $condition): array {
        global $DB;

        $classname = get_class($condition);

        $cache = cache::make('tool_cohortmanager', 'rules');
        $key = 'rules-conditions-' . $classname;

        $rules = $cache->get($key);

        if ($rules === false) {
            $rules = [];

            $sql = 'SELECT DISTINCT r.id
                      FROM {tool_cohortmanager} r
                      JOIN {tool_cohortmanager_cond} c ON c.ruleid = r.id
                     WHERE c.classname = ?
                       AND r.enabled = 1 ORDER BY r.id';

            $records = $DB->get_records_sql($sql, [$classname]);

            foreach ($records as $record) {
                $rules[$record->id] = new rule($record->id);
            }

            $cache->set($key, $rules);
        }

        return $rules;
    }

}

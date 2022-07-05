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

use core_component;
use moodle_exception;
use moodle_url;
use tool_cohortmanager\output\renderer;

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
                $instances[$class] = $class::get_instance();
            }
        }

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
            } else {
                $rule = new rule($formdata->id);
                $oldcohortid = $rule->get('cohortid');
                $rule->from_record($ruledata);
                $rule->update();
            }

            self::unmanage_cohort($oldcohortid);
            self::manage_cohort($formdata->cohortid);
            self::process_rule_conditions($rule, $formdata);

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
            ['id', 'usermodified', 'timecreated', 'timemodified']
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
                } else {
                    $toupdate[$condition->get('id')] = $condition;
                }
            }

            $todelete = array_diff_key($oldconditions, $toupdate);

            foreach ($todelete as $conditiontodelete) {
                $conditiontodelete->delete();
            }

            foreach ($toupdate as $conditiontoupdate) {
                $conditiontoupdate->save();
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

        // Filter out submitted conditions data to only fields required for condition persistent.
        $submittedrecords = array_map(function (array $record) use ($requiredconditionfield): array {
            return array_intersect_key($record, array_flip($requiredconditionfield));
        }, json_decode($formjson, true));

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
        global $DB;

        $oldid = $rule->get('id');

        if ($rule->delete()) {
            $DB->delete_records(condition::TABLE, ['ruleid' => $oldid]);
            $DB->delete_records(match::TABLE, ['ruleid' => $oldid]);
            self::unmanage_cohort($rule->get('cohortid'));
        }
    }

    /**
     * Unset cohort from being managed by tool_cohortmanager.
     *
     * @param int $cohortid Cohort ID.
     */
    public static function unmanage_cohort(int $cohortid): void {
        $cohorts = self::get_available_cohorts();

        if (!empty($cohorts[$cohortid]) && !rule::record_exists_select('cohortid = ?', [$cohortid])) {
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
            $cohort->component = 'tool_cohortmanager';
            cohort_update_cohort($cohort);
        }
    }

    /**
     * Get a list of all cohort names in the system keyed by cohort ID.
     *
     * @return array
     */
    public static function get_available_cohorts(): array {
        $cohorts = [];
        foreach (\cohort_get_all_cohorts(0, 0)['cohorts'] as $cohort) {
            if (empty($cohort->component) || $cohort->component == self::COHORT_COMPONENT) {
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
        foreach ($rule->get_condition_records() as $persistent) {
            $condition = condition_base::get_instance($persistent->get('id'));
            $conditions[] = (array)$persistent->to_record() +
                ['description' => $condition->get_config_description()] +
                ['name' => $condition->get_name()];
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
}

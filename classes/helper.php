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
     * @return array
     */
    public static function get_all_conditions(): array {
        $instances = [];
        $classes = \core_component::get_component_classes_in_namespace(null, '\\tool_cohortmanager\\condition');

        foreach (array_keys($classes) as $class) {
            $reflectionclass = new \ReflectionClass($class);
            if (!$reflectionclass->isAbstract()) {
                $instances[] = $class::get_instance();
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
        self::validate_rule_data($formdata);

        $ruledata = (object) [
            'name' => $formdata->name,
            'enabled' => $formdata->enabled,
            'cohortid' => $formdata->cohortid,
            'description' => $formdata->description,
        ];

        $oldcohortid = 0;

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

        return $rule;
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

}

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
     * Get a list of all conditions.
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
     * @return int Rule record ID.
     */
    public static function process_rule_form(\stdClass $formdata): int {

        if (!self::is_valid_rule_data($formdata)) {
            throw new moodle_exception('Invalid rule data');
        }

        $ruledata = (object) [
            'name' => $formdata->name,
            'enabled' => $formdata->enabled,
            'cohortid' => $formdata->cohortid,
            'description' => $formdata->description,
        ];

        if (empty($formdata->id)) {
            $rule = new rule(0, $ruledata);
            $rule->create();
        } else {
            $rule = new rule($formdata->id);
            $rule->from_record($ruledata);
            $rule->update();
        }

        return $rule->get('id');
    }

    /**
     * Validate submitted rule data.
     *
     * @param \stdClass $formdata Data received from rule_form.
     * @return bool
     */
    protected static function is_valid_rule_data(\stdClass $formdata): bool {
        // Go through all rule persistent fields excluding system fields to make sure
        // we get only required fields to check form data against.
        $requiredfields = array_diff(
            array_keys(rule::properties_definition()),
            ['id', 'usermodified', 'timecreated', 'timemodified']
        );

        foreach ($requiredfields as $field) {
            if (!isset($formdata->{$field})) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get a list of all cohort names in the system keyed by cohort ID.
     * @return array
     */
    public static function get_all_cohorts(): array {
        $cohorts = [];
        foreach (\cohort_get_all_cohorts(0, 0)['cohorts'] as $cohort) {
            $cohorts[$cohort->id] = $cohort->name;
        }

        return $cohorts;
    }

    /**
     * Builds rule edit URL.
     *
     * @param \tool_cohortmanager\rule $rule Rule instance.
     * @return \moodle_url
     */
    public static function build_rule_edit_url(rule $rule): moodle_url {
        return new moodle_url('/admin/tool/cohortmanager/edit.php', ['ruleid' => $rule->get('id')]);
    }

    /**
     * Builds rule delete URL.
     *
     * @param \tool_cohortmanager\rule $rule Rule instance.
     * @return \moodle_url
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
     * @return \tool_cohortmanager\output\renderer
     */
    public static function get_renderer(): renderer {
        global $PAGE;

        return $PAGE->get_renderer('tool_cohortmanager');
    }

}

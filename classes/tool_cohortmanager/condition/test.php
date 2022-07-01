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

namespace tool_cohortmanager\tool_cohortmanager\condition;

use tool_cohortmanager\condition_base;
use tool_cohortmanager\sql_data;

/**
 * Testing condition.
 *
 * @package    tool_cohortmanager
 * @author     Dmitrii Metelkin <dmitriim@catalyst-au.net>
 * @copyright  2022 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class test extends condition_base {

    /**
     * Condition name.
     * @return string
     */
    public function get_name(): string {
        return 'Test Condition';
    }

    /**
     * Add config form elements.
     * @param \MoodleQuickForm $mform
     */
    public function config_form_add(\MoodleQuickForm $mform) {
        $mform->addElement('text', 'test', 'Test field');
        $mform->setType('test', PARAM_TEXT);
    }

    /**
     * Validate config form elements.
     * @param array $data
     *
     * @return array
     */
    public function config_form_validate(array $data): array {
        $errors = [];

        if (empty($data['test'])) {
            $errors['test'] = 'Test field can not be empty';
        }

        return $errors;
    }

    /**
     * Human readable description of the configured condition.
     *
     * @return string
     */
    public function get_config_description(): string {
        $description = '';
        $configdata = $this->get_configdata();

        if (!empty($configdata['test'])) {
            $description = 'Test field set as "' . $configdata['test'] . '"';
        }

        return $description;
    }

    /**
     * Returns elements to extend SQL for searching users.
     *
     * @return sql_data
     */
    public function get_sql_data(): sql_data {
        return new sql_data('', '', []);
    }
}

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

/**
 * Unit tests for helper class.
 *
 * @covers     \tool_cohortmanager\helper
 *
 * @package    tool_cohortmanager
 * @author     Dmitrii Metelkin <dmitriim@catalyst-au.net>
 * @copyright  2022 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class helper_test extends \advanced_testcase {

    /**
     * Test all conditions.
     */
    public function test_get_all_conditions() {
        $conditions = helper::get_all_conditions();
        $this->assertIsArray($conditions);

        foreach ($conditions as $condition) {
            $this->assertFalse(is_null($condition));
            $this->assertTrue(is_subclass_of($condition, condition_base::class));
        }
    }

    /**
     * Data provider for testing test_process_rule_form_with_invalid_data.
     *
     * @return array
     */
    public function process_rule_form_with_invalid_data_provider(): array {
        return [
            [[]],
            [['name' => 'Test']],
            [['enabled' => 1]],
            [['cohortid' => 1]],
            [['description' => '']],
            [['enabled' => 1, 'cohortid' => 1, 'description' => '']],
            [['name' => 'Test', 'cohortid' => 1, 'description' => '']],
            [['name' => 'Test', 'enabled' => 1, 'description' => '']],
            [['name' => 'Test', 'enabled' => 1, 'cohortid' => 1]],
        ];
    }

    /**
     * Test processing rules with invalid data.
     *
     * @dataProvider process_rule_form_with_invalid_data_provider
     * @param array $formdata Broken form data
     */
    public function test_process_rule_form_with_invalid_data(array $formdata) {
        $this->expectException(\moodle_exception::class);
        $this->expectExceptionMessage('Invalid rule data');

        helper::process_rule_form((object)$formdata);
    }

    /**
     * Test new rules are created when processing form data.
     */
    public function test_process_rule_form_new_rule() {
        global $DB;

        $this->resetAfterTest();
        $this->assertEquals(0, $DB->count_records(rule::TABLE));

        $formdata = ['name' => 'Test', 'enabled' => 1, 'cohortid' => 1, 'description' => ''];

        $id = helper::process_rule_form((object)$formdata);
        $this->assertEquals(1, $DB->count_records(rule::TABLE));

        $rule = rule::get_record(['id' => $id]);
        foreach ($formdata as $field => $value) {
            $this->assertEquals($value, $rule->get($field));
        }

        $formdata = ['name' => 'Test', 'enabled' => 1, 'cohortid' => 1, 'description' => ''];
        $id = helper::process_rule_form((object)$formdata);
        $this->assertEquals(2, $DB->count_records(rule::TABLE));

        $rule = rule::get_record(['id' => $id]);
        foreach ($formdata as $field => $value) {
            $this->assertEquals($value, $rule->get($field));
        }

        $formdata = ['name' => 'Test1', 'enabled' => 1, 'cohortid' => 2, 'description' => ''];
        $id = helper::process_rule_form((object)$formdata);
        $this->assertEquals(3, $DB->count_records(rule::TABLE));

        $rule = rule::get_record(['id' => $id]);
        foreach ($formdata as $field => $value) {
            $this->assertEquals($value, $rule->get($field));
        }

    }

    /**
     * Test existing rules are updated when processing form data.
     */
    public function test_process_rule_form_existing_rule() {
        global $DB;

        $this->resetAfterTest();
        $this->assertEquals(0, $DB->count_records(rule::TABLE));

        $formdata = ['name' => 'Test', 'enabled' => 1, 'cohortid' => 1, 'description' => ''];
        $rule = new rule(0, (object)$formdata);
        $rule->create();

        $this->assertEquals(1, $DB->count_records(rule::TABLE));
        foreach ($formdata as $field => $value) {
            $this->assertEquals($value, $rule->get($field));
        }

        $formdata = ['id' => $rule->get('id'), 'name' => 'Test1', 'enabled' => 0, 'cohortid' => 2, 'description' => 'Desc'];
        $id = helper::process_rule_form((object)$formdata);
        $this->assertEquals(1, $DB->count_records(rule::TABLE));

        $rule = rule::get_record(['id' => $id]);
        foreach ($formdata as $field => $value) {
            $this->assertEquals($value, $rule->get($field));
        }
    }

}

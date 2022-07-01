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

use advanced_testcase;
use moodle_url;
use moodle_exception;

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
class helper_test extends advanced_testcase {

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
            [['conditionjson' => '']],
            [['enabled' => 1, 'cohortid' => 1, 'description' => '', 'conditionjson' => '']],
            [['name' => 'Test', 'cohortid' => 1, 'description' => '', 'conditionjson' => '']],
            [['name' => 'Test', 'enabled' => 1, 'description' => '', 'conditionjson' => '']],
            [['name' => 'Test', 'enabled' => 1, 'cohortid' => 1, 'conditionjson' => '']],
            [['name' => 'Test', 'enabled' => 1, 'cohortid' => 1, 'description' => '']],

        ];
    }

    /**
     * Test processing rules with invalid data.
     *
     * @dataProvider process_rule_form_with_invalid_data_provider
     * @param array $formdata Broken form data
     */
    public function test_process_rule_form_with_invalid_data(array $formdata) {
        $this->expectException(moodle_exception::class);
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

        $cohort = $this->getDataGenerator()->create_cohort();

        $formdata = ['name' => 'Test', 'enabled' => 1, 'cohortid' => $cohort->id, 'description' => '', 'conditionjson' => ''];

        $rule = helper::process_rule_form((object)$formdata);
        $this->assertEquals(1, $DB->count_records(rule::TABLE));

        $rule = rule::get_record(['id' => $rule->get('id')]);
        unset($formdata['conditionjson']);
        foreach ($formdata as $field => $value) {
            $this->assertEquals($value, $rule->get($field));
        }

        $formdata = ['name' => 'Test', 'enabled' => 1, 'cohortid' => $cohort->id, 'description' => '', 'conditionjson' => ''];
        $rule = helper::process_rule_form((object)$formdata);
        $this->assertEquals(2, $DB->count_records(rule::TABLE));

        $rule = rule::get_record(['id' => $rule->get('id')]);
        unset($formdata['conditionjson']);
        foreach ($formdata as $field => $value) {
            $this->assertEquals($value, $rule->get($field));
        }

        $cohort = $this->getDataGenerator()->create_cohort();
        $formdata = ['name' => 'Test1', 'enabled' => 1, 'cohortid' => $cohort->id, 'description' => '', 'conditionjson' => ''];
        $rule = helper::process_rule_form((object)$formdata);
        $this->assertEquals(3, $DB->count_records(rule::TABLE));

        $rule = rule::get_record(['id' => $rule->get('id')]);
        unset($formdata['conditionjson']);
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

        $cohort = $this->getDataGenerator()->create_cohort();
        $formdata = ['name' => 'Test', 'enabled' => 1, 'cohortid' => $cohort->id, 'description' => ''];
        $rule = new rule(0, (object)$formdata);
        $rule->create();

        $this->assertEquals(1, $DB->count_records(rule::TABLE));
        unset($formdata['conditionjson']);
        foreach ($formdata as $field => $value) {
            $this->assertEquals($value, $rule->get($field));
        }

        $cohort = $this->getDataGenerator()->create_cohort();
        $formdata = ['id' => $rule->get('id'), 'name' => 'Test1', 'enabled' => 0, 'cohortid' => $cohort->id,
            'description' => 'D', 'conditionjson' => ''];
        $rule = helper::process_rule_form((object)$formdata);
        $this->assertEquals(1, $DB->count_records(rule::TABLE));

        $rule = rule::get_record(['id' => $rule->get('id')]);
        unset($formdata['conditionjson']);
        foreach ($formdata as $field => $value) {
            $this->assertEquals($value, $rule->get($field));
        }
    }

    /**
     * Test trying to submit form data and sending not existing cohort.
     */
    public function test_process_rule_form_with_not_existing_cohort() {
        $this->expectException(moodle_exception::class);
        $this->expectExceptionMessage('Invalid rule data. Cohort is invalid: 999');

        $formdata = ['name' => 'Test', 'enabled' => 1, 'cohortid' => 999, 'description' => '', 'conditionjson' => ''];
        helper::process_rule_form((object)$formdata);
    }

    /**
     * Test trying to submit form data and sending a cohort taken by other component.
     */
    public function test_process_rule_form_with_cohort_managed_by_other_component() {
        $this->resetAfterTest();

        $cohort = $this->getDataGenerator()->create_cohort(['component' => 'mod_assign']);
        $this->expectException(moodle_exception::class);
        $this->expectExceptionMessage('Invalid rule data. Cohort is invalid: ' . $cohort->id);

        $formdata = ['name' => 'Test', 'enabled' => 1, 'cohortid' => $cohort->id, 'description' => '', 'conditionjson' => ''];
        helper::process_rule_form((object)$formdata);
    }

    /**
     * Test trying to submit form data and sending a cohort taken by other component.
     */
    public function test_process_rule_form_without_condition_data() {
        $this->resetAfterTest();

        $cohort = $this->getDataGenerator()->create_cohort(['component' => 'tool_cohortmanager']);
        $this->expectException(moodle_exception::class);
        $this->expectExceptionMessage('Invalid rule data. Missing condition data.');

        $formdata = ['name' => 'Test', 'enabled' => 1, 'cohortid' => $cohort->id, 'description' => ''];
        helper::process_rule_form((object)$formdata);
    }

    /**
     * Test component string for cohorts.
     */
    public function test_cohort_component() {
        $this->assertSame('tool_cohortmanager', helper::COHORT_COMPONENT);
    }

    /**
     * Test getting all available cohorts.
     */
    public function test_get_available_cohorts() {
        $this->resetAfterTest();

        $this->assertEmpty(helper::get_available_cohorts());

        $cohort1 = $this->getDataGenerator()->create_cohort(['component' => helper::COHORT_COMPONENT]);
        $cohort2 = $this->getDataGenerator()->create_cohort();
        $cohort3 = $this->getDataGenerator()->create_cohort();
        $cohort4 = $this->getDataGenerator()->create_cohort(['component' => 'mod_assign']);

        $allcohorts = helper::get_available_cohorts();

        $this->assertCount(3, $allcohorts);

        $this->assertArrayNotHasKey($cohort4->id, $allcohorts);
        $this->assertEquals($cohort1, $allcohorts[$cohort1->id]);
        $this->assertEquals($cohort2, $allcohorts[$cohort2->id]);
        $this->assertEquals($cohort3, $allcohorts[$cohort3->id]);
    }

    /**
     * Test edit URL.
     */
    public function test_build_rule_edit_url() {
        $this->resetAfterTest();

        $data = ['name' => 'Test', 'enabled' => 1, 'cohortid' => 2, 'description' => ''];
        $rule = new rule(0, (object)$data);
        $rule->save();

        $actual = helper::build_rule_edit_url($rule);
        $expected = new moodle_url('/admin/tool/cohortmanager/edit.php', ['ruleid' => $rule->get('id')]);
        $this->assertEquals($expected->out(), $actual->out());
    }

    /**
     * Test delete URL.
     */
    public function test_build_rule_delete_url() {
        $this->resetAfterTest();

        $data = ['name' => 'Test', 'enabled' => 1, 'cohortid' => 2, 'description' => ''];
        $rule = new rule(0, (object)$data);
        $rule->save();

        $actual = helper::build_rule_delete_url($rule);
        $expected = new moodle_url('/admin/tool/cohortmanager/delete.php', [
            'ruleid' => $rule->get('id'),
            'sesskey' => sesskey()
        ]);

        $this->assertEquals($expected->out(), $actual->out());
    }

    /**
     * Test managing cohort.
     */
    public function test_manage_cohort() {
        global $DB;

        $this->resetAfterTest();

        $cohort = $this->getDataGenerator()->create_cohort();
        $this->assertEquals('', $DB->get_field('cohort', 'component', ['id' => $cohort->id]));

        helper::manage_cohort($cohort->id);
        $this->assertEquals('tool_cohortmanager', $DB->get_field('cohort', 'component', ['id' => $cohort->id]));
    }

    /**
     * Test unmanaging cohort.
     */
    public function test_unmanage_cohort() {
        global $DB;

        $this->resetAfterTest();

        $cohort = $this->getDataGenerator()->create_cohort(['component' => 'tool_cohortmanager']);
        $this->assertEquals('tool_cohortmanager', $DB->get_field('cohort', 'component', ['id' => $cohort->id]));

        helper::unmanage_cohort($cohort->id);
        $this->assertEquals('', $DB->get_field('cohort', 'component', ['id' => $cohort->id]));
    }

    /**
     * Test that we reserve selected cohort on rule creation.
     */
    public function test_creating_rule_reserves_related_cohort() {
        global $DB;

        $this->resetAfterTest();
        $cohort = $this->getDataGenerator()->create_cohort();
        $this->assertEquals('', $DB->get_field('cohort', 'component', ['id' => $cohort->id]));

        $formdata = ['name' => 'Test1', 'enabled' => 0, 'cohortid' => $cohort->id, 'description' => 'D', 'conditionjson' => ''];
        helper::process_rule_form((object)$formdata);
        $this->assertEquals('tool_cohortmanager', $DB->get_field('cohort', 'component', ['id' => $cohort->id]));
    }

    /**
     * Test updating cohort releases old cohort and reserves a new cohort.
     */
    public function test_updating_rule_reserves_new_cohort_and_releases_old_cohort() {
        global $DB;

        $this->resetAfterTest();
        $cohort1 = $this->getDataGenerator()->create_cohort();
        $cohort2 = $this->getDataGenerator()->create_cohort();

        $this->assertEquals('', $DB->get_field('cohort', 'component', ['id' => $cohort1->id]));
        $this->assertEquals('', $DB->get_field('cohort', 'component', ['id' => $cohort2->id]));

        $formdata = ['name' => 'Test1', 'enabled' => 0, 'cohortid' => $cohort1->id, 'description' => 'D', 'conditionjson' => ''];
        $rule1 = helper::process_rule_form((object)$formdata);
        $this->assertEquals('tool_cohortmanager', $DB->get_field('cohort', 'component', ['id' => $cohort1->id]));
        $this->assertEquals('', $DB->get_field('cohort', 'component', ['id' => $cohort2->id]));

        $formdata = ['name' => 'Test2', 'enabled' => 0, 'cohortid' => $cohort1->id, 'description' => 'D', 'conditionjson' => ''];
        $rule2 = helper::process_rule_form((object)$formdata);
        $this->assertEquals('tool_cohortmanager', $DB->get_field('cohort', 'component', ['id' => $cohort1->id]));
        $this->assertEquals('', $DB->get_field('cohort', 'component', ['id' => $cohort2->id]));

        $formdata = ['id' => $rule1->get('id'), 'name' => 'Test1',
            'enabled' => 0, 'cohortid' => $cohort2->id, 'description' => 'D', 'conditionjson' => ''];
        helper::process_rule_form((object)$formdata);

        $this->assertEquals('tool_cohortmanager', $DB->get_field('cohort', 'component', ['id' => $cohort1->id]));
        $this->assertEquals('tool_cohortmanager', $DB->get_field('cohort', 'component', ['id' => $cohort2->id]));

        $formdata = ['id' => $rule2->get('id'), 'name' => 'Test2',
            'enabled' => 0, 'cohortid' => $cohort2->id, 'description' => 'D', 'conditionjson' => ''];
        helper::process_rule_form((object)$formdata);

        $this->assertEquals('', $DB->get_field('cohort', 'component', ['id' => $cohort1->id]));
        $this->assertEquals('tool_cohortmanager', $DB->get_field('cohort', 'component', ['id' => $cohort2->id]));
    }

    /**
     * Test rule deleting clear all related tables.
     */
    public function test_deleting_rule_deletes_all_related_records() {
        global $DB;

        $this->resetAfterTest();

        $this->assertSame(0, $DB->count_records(rule::TABLE));
        $this->assertSame(0, $DB->count_records(condition::TABLE));
        $this->assertSame(0, $DB->count_records(match::TABLE));

        $cohort = $this->getDataGenerator()->create_cohort();

        $rule = new rule(0, (object)['name' => 'Test rule', 'cohortid' => $cohort->id]);
        $rule->save();

        $condition = new condition(0, (object)['ruleid' => $rule->get('id'), 'classname' => 'test', 'position' => 0]);
        $condition->save();

        $match = new match(0, (object)['ruleid' => $rule->get('id'), 'userid' => 2, 'matchedtime' => time(), 'status' => 1]);
        $match->save();

        $this->assertSame(1, $DB->count_records(rule::TABLE));
        $this->assertSame(1, $DB->count_records(condition::TABLE));
        $this->assertSame(1, $DB->count_records(match::TABLE));

        helper::delete_rule($rule);
        $this->assertSame(0, $DB->count_records(rule::TABLE));
        $this->assertSame(0, $DB->count_records(condition::TABLE));
        $this->assertSame(0, $DB->count_records(match::TABLE));
    }

    /**
     * Test cohorts are getting released after related rules are deleted.
     */
    public function test_deleting_rule_releases_cohorts() {
        global $DB;

        $this->resetAfterTest();

        $cohort = $this->getDataGenerator()->create_cohort();
        $this->assertEquals('', $DB->get_field('cohort', 'component', ['id' => $cohort->id]));

        $rule1 = new rule(0, (object)['name' => 'Test rule', 'cohortid' => $cohort->id]);
        $rule1->save();
        helper::manage_cohort($cohort->id);

        $this->assertEquals('tool_cohortmanager', $DB->get_field('cohort', 'component', ['id' => $cohort->id]));

        $rule2 = new rule(0, (object)['name' => 'Test rule 2', 'cohortid' => $cohort->id]);
        $rule2->save();
        helper::manage_cohort($cohort->id);
        $this->assertEquals('tool_cohortmanager', $DB->get_field('cohort', 'component', ['id' => $cohort->id]));

        helper::delete_rule($rule1);
        $this->assertEquals('tool_cohortmanager', $DB->get_field('cohort', 'component', ['id' => $cohort->id]));

        helper::delete_rule($rule2);
        $this->assertEquals('', $DB->get_field('cohort', 'component', ['id' => $cohort->id]));
    }

    /**
     * Test building rule data for form.
     */
    public function test_build_rule_data_for_form() {
        $rule = new rule(0, (object)['name' => 'Test rule', 'cohortid' => 0, 'description' => 'Test description']);

        $expected = [
            'name' => 'Test rule',
            'enabled' => 0,
            'cohortid' => 0,
            'description' => 'Test description',
            'id' => 0,
            'timecreated' => 0,
            'timemodified' => 0,
            'usermodified' => 0,
            'conditionjson' => '',
        ];

        $this->assertEquals($expected, helper::build_rule_data_for_form($rule));
    }

    /**
     * Test conditions created when processing rule form data.
     */
    public function test_process_rule_form_with_conditions() {
        global $DB;

        $this->resetAfterTest();
        $cohort = $this->getDataGenerator()->create_cohort();

        $this->assertEquals(0, $DB->count_records(rule::TABLE));

        // Creating rule without conditions.
        $formdata = ['name' => 'Test', 'enabled' => 1, 'cohortid' => $cohort->id, 'description' => '', 'conditionjson' => ''];
        $rule = helper::process_rule_form((object)$formdata);
        $this->assertEquals(1, $DB->count_records(rule::TABLE));
        $this->assertCount(0, $rule->get_condition_records());

        // Updating the rule with 3 new conditions, but flag isconditionschanged is not set.
        $conditionjson = json_encode([
            ['id' => 0, 'classname' => 'class1', 'position' => 0, 'configdata' => ''],
            ['id' => 0, 'classname' => 'class2', 'position' => 1, 'configdata' => ''],
            ['id' => 0, 'classname' => 'class3', 'position' => 2, 'configdata' => ''],
        ]);

        $formdata = ['id' => $rule->get('id'), 'name' => 'Test', 'enabled' => 1, 'cohortid' => $cohort->id,
            'description' => '', 'conditionjson' => $conditionjson];
        $rule = helper::process_rule_form((object)$formdata);
        $this->assertEquals(1, $DB->count_records(rule::TABLE));
        $this->assertCount(0, $rule->get_condition_records());

        // Updating the rule with 3 new conditions. Expecting 3 new conditions to be created.
        $formdata = ['id' => $rule->get('id'), 'name' => 'Test', 'enabled' => 1, 'cohortid' => $cohort->id,
            'description' => '', 'conditionjson' => $conditionjson, 'isconditionschanged' => true];
        $rule = helper::process_rule_form((object)$formdata);
        $this->assertEquals(1, $DB->count_records(rule::TABLE));
        $this->assertCount(3, $rule->get_condition_records());

        $this->assertTrue(condition::record_exists_select('classname = ? AND ruleid = ?', ['class1', $rule->get('id')]));
        $this->assertTrue(condition::record_exists_select('classname = ? AND ruleid = ?', ['class2', $rule->get('id')]));
        $this->assertTrue(condition::record_exists_select('classname = ? AND ruleid = ?', ['class3', $rule->get('id')]));

        // Updating the rule with 1 new condition, 1 deleted condition (position 1) and
        // two updated conditions (position added to a class name). Expecting 1 new condition, 2 updated and 1 deleted.
        $conditions = $rule->get_condition_records();
        $conditionjson = [];

        foreach ($conditions as $condition) {
            if ($condition->get('position') != 1) {
                $conditionjson[] = [
                    'id' => $condition->get('id'),
                    'classname' => $condition->get('classname') . $condition->get('position'),
                    'position' => $condition->get('position'),
                    'configdata' => $condition->get('configdata'),
                ];
            }
        }

        $conditionjson[] = ['id' => 0, 'classname' => 'class4', 'position' => 2, 'configdata' => ''];
        $conditionjson = json_encode($conditionjson);

        $formdata = ['id' => $rule->get('id'), 'name' => 'Test', 'enabled' => 1, 'cohortid' => $cohort->id,
            'description' => '', 'conditionjson' => $conditionjson, 'isconditionschanged' => true];
        $rule = helper::process_rule_form((object)$formdata);
        $this->assertEquals(1, $DB->count_records(rule::TABLE));
        $this->assertCount(3, $rule->get_condition_records());

        $this->assertTrue(condition::record_exists_select('classname = ? AND ruleid = ?', ['class10', $rule->get('id')]));
        $this->assertFalse(condition::record_exists_select('classname = ? AND ruleid = ?', ['class2', $rule->get('id')]));
        $this->assertTrue(condition::record_exists_select('classname = ? AND ruleid = ?', ['class32', $rule->get('id')]));
        $this->assertTrue(condition::record_exists_select('classname = ? AND ruleid = ?', ['class4', $rule->get('id')]));
    }

}

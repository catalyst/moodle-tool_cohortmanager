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

        $formdata = ['name' => 'Test', 'enabled' => 1, 'cohortid' => $cohort->id, 'description' => ''];

        $rule = helper::process_rule_form((object)$formdata);
        $this->assertEquals(1, $DB->count_records(rule::TABLE));

        $rule = rule::get_record(['id' => $rule->get('id')]);
        foreach ($formdata as $field => $value) {
            $this->assertEquals($value, $rule->get($field));
        }

        $formdata = ['name' => 'Test', 'enabled' => 1, 'cohortid' => $cohort->id, 'description' => ''];
        $rule = helper::process_rule_form((object)$formdata);
        $this->assertEquals(2, $DB->count_records(rule::TABLE));

        $rule = rule::get_record(['id' => $rule->get('id')]);
        foreach ($formdata as $field => $value) {
            $this->assertEquals($value, $rule->get($field));
        }

        $cohort = $this->getDataGenerator()->create_cohort();
        $formdata = ['name' => 'Test1', 'enabled' => 1, 'cohortid' => $cohort->id, 'description' => ''];
        $rule = helper::process_rule_form((object)$formdata);
        $this->assertEquals(3, $DB->count_records(rule::TABLE));

        $rule = rule::get_record(['id' => $rule->get('id')]);
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
        foreach ($formdata as $field => $value) {
            $this->assertEquals($value, $rule->get($field));
        }

        $cohort = $this->getDataGenerator()->create_cohort();
        $formdata = ['id' => $rule->get('id'), 'name' => 'Test1', 'enabled' => 0, 'cohortid' => $cohort->id, 'description' => 'D'];
        $rule = helper::process_rule_form((object)$formdata);
        $this->assertEquals(1, $DB->count_records(rule::TABLE));

        $rule = rule::get_record(['id' => $rule->get('id')]);
        foreach ($formdata as $field => $value) {
            $this->assertEquals($value, $rule->get($field));
        }
    }

    /**
     * Test getting all cohorts.
     */
    public function test_get_all_cohorts() {
        $this->resetAfterTest();

        $this->assertEmpty(helper::get_all_cohorts());

        $cohort1 = $this->getDataGenerator()->create_cohort();
        $cohort2 = $this->getDataGenerator()->create_cohort();
        $cohort3 = $this->getDataGenerator()->create_cohort();

        $allcohorts = helper::get_all_cohorts();

        $this->assertCount(3, $allcohorts);

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
     * Test reserving cohort.
     */
    public function test_reserve_cohort() {
        global $DB;

        $this->resetAfterTest();

        $cohort = $this->getDataGenerator()->create_cohort();
        $this->assertEquals('', $DB->get_field('cohort', 'component', ['id' => $cohort->id]));

        helper::reserve_cohort($cohort->id);
        $this->assertEquals('tool_cohortmanager', $DB->get_field('cohort', 'component', ['id' => $cohort->id]));
    }

    /**
     * Test releasing cohort.
     */
    public function test_release_cohort() {
        global $DB;

        $this->resetAfterTest();

        $cohort = $this->getDataGenerator()->create_cohort(['component' => 'tool_cohortmanager']);
        $this->assertEquals('tool_cohortmanager', $DB->get_field('cohort', 'component', ['id' => $cohort->id]));

        helper::release_cohort($cohort->id);
        $this->assertEquals('', $DB->get_field('cohort', 'component', ['id' => $cohort->id]));
    }

    /**
     * Test that we reserve selected cohort on rule creation.
     */
    public function test_creating_reserves_related_cohort() {
        global $DB;

        $this->resetAfterTest();
        $cohort = $this->getDataGenerator()->create_cohort();
        $this->assertEquals('', $DB->get_field('cohort', 'component', ['id' => $cohort->id]));

        $formdata = ['name' => 'Test1', 'enabled' => 0, 'cohortid' => $cohort->id, 'description' => 'D'];
        helper::process_rule_form((object)$formdata);
        $this->assertEquals('tool_cohortmanager', $DB->get_field('cohort', 'component', ['id' => $cohort->id]));
    }

    /**
     * Test updating cohort releases old cohort and reserves a new cohort.
     */
    public function test_updating_reserves_new_cohort_and_releases_old_cohort() {
        global $DB;

        $this->resetAfterTest();
        $cohort1 = $this->getDataGenerator()->create_cohort();
        $cohort2 = $this->getDataGenerator()->create_cohort();

        $this->assertEquals('', $DB->get_field('cohort', 'component', ['id' => $cohort1->id]));
        $this->assertEquals('', $DB->get_field('cohort', 'component', ['id' => $cohort2->id]));

        $formdata = ['name' => 'Test1', 'enabled' => 0, 'cohortid' => $cohort1->id, 'description' => 'D'];
        $rule1 = helper::process_rule_form((object)$formdata);
        $this->assertEquals('tool_cohortmanager', $DB->get_field('cohort', 'component', ['id' => $cohort1->id]));
        $this->assertEquals('', $DB->get_field('cohort', 'component', ['id' => $cohort2->id]));

        $formdata = ['name' => 'Test2', 'enabled' => 0, 'cohortid' => $cohort1->id, 'description' => 'D'];
        $rule2 = helper::process_rule_form((object)$formdata);
        $this->assertEquals('tool_cohortmanager', $DB->get_field('cohort', 'component', ['id' => $cohort1->id]));
        $this->assertEquals('', $DB->get_field('cohort', 'component', ['id' => $cohort2->id]));

        $formdata = ['id' => $rule1->get('id'), 'name' => 'Test1',
            'enabled' => 0, 'cohortid' => $cohort2->id, 'description' => 'D'];
        helper::process_rule_form((object)$formdata);

        $this->assertEquals('tool_cohortmanager', $DB->get_field('cohort', 'component', ['id' => $cohort1->id]));
        $this->assertEquals('tool_cohortmanager', $DB->get_field('cohort', 'component', ['id' => $cohort2->id]));

        $formdata = ['id' => $rule2->get('id'), 'name' => 'Test2',
            'enabled' => 0, 'cohortid' => $cohort2->id, 'description' => 'D'];
        helper::process_rule_form((object)$formdata);

        $this->assertEquals('', $DB->get_field('cohort', 'component', ['id' => $cohort1->id]));
        $this->assertEquals('tool_cohortmanager', $DB->get_field('cohort', 'component', ['id' => $cohort2->id]));
    }

    /**
     * Test rule deleting clear all related tables.
     */
    public function test_deleting_deletes_all_related_records() {
        global $DB;

        $this->resetAfterTest();

        $this->assertSame(0, $DB->count_records(rule::TABLE));
        $this->assertSame(0, $DB->count_records(condition::TABLE));
        $this->assertSame(0, $DB->count_records(match::TABLE));

        $cohort = $this->getDataGenerator()->create_cohort();

        $rule = new rule(0, (object)['name' => 'Test rule', 'cohortid' => $cohort->id]);
        $rule->save();

        $condition = new condition(0, (object)['ruleid' => $rule->get('id'), 'classname' => 'test']);
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
    public function test_deleting_releases_cohorts() {
        global $DB;

        $this->resetAfterTest();

        $cohort = $this->getDataGenerator()->create_cohort();
        $this->assertEquals('', $DB->get_field('cohort', 'component', ['id' => $cohort->id]));

        $rule1 = new rule(0, (object)['name' => 'Test rule', 'cohortid' => $cohort->id]);
        $rule1->save();
        helper::reserve_cohort($cohort->id);

        $this->assertEquals('tool_cohortmanager', $DB->get_field('cohort', 'component', ['id' => $cohort->id]));

        $rule2 = new rule(0, (object)['name' => 'Test rule 2', 'cohortid' => $cohort->id]);
        $rule2->save();
        helper::reserve_cohort($cohort->id);
        $this->assertEquals('tool_cohortmanager', $DB->get_field('cohort', 'component', ['id' => $cohort->id]));

        helper::delete_rule($rule1);
        $this->assertEquals('tool_cohortmanager', $DB->get_field('cohort', 'component', ['id' => $cohort->id]));

        helper::delete_rule($rule2);
        $this->assertEquals('', $DB->get_field('cohort', 'component', ['id' => $cohort->id]));
    }

}

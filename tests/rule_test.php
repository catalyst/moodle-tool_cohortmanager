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
 * Unit tests for rule persistent class.
 *
 * @covers     \tool_cohortmanager\rule
 *
 * @package    tool_cohortmanager
 * @author     Dmitrii Metelkin <dmitriim@catalyst-au.net>
 * @copyright  2022 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class rule_test extends \advanced_testcase {

    /**
     * Test is_enabled.
     */
    public function test_is_enabled() {
        $this->resetAfterTest();

        $rule = new rule(0, (object)['name' => 'Test rule 1']);
        $this->assertFalse($rule->is_enabled());

        $rule = new rule(0, (object)['name' => 'Test rule 2', 'enabled' => 1]);
        $this->assertTrue($rule->is_enabled());
    }

    /**
     * Test is_broken.
     */
    public function test_is_broken() {
        $this->resetAfterTest();

        $rule = new rule(0, (object)['name' => 'Test rule 1']);
        $this->assertFalse($rule->is_broken());

        $rule = new rule(0, (object)['name' => 'Test rule 2', 'broken' => 1]);
        $this->assertTrue($rule->is_broken());
    }

    /**
     * Test is_broken when checking conditions.
     */
    public function test_is_broken_check_conditions() {
        $this->resetAfterTest();

        $rule = new rule(0, (object)['name' => 'Test rule 1']);
        $rule->save();

        $condition = new condition(0, (object)['ruleid' => $rule->get('id'), 'classname' => 'test', 'position' => 0]);
        $condition->save();

        $this->assertFalse($rule->is_broken());
        $this->assertTrue($rule->is_broken(true));
    }

    /**
     * Test getting a list of related condition records.
     */
    public function test_get_condition_records() {
        $this->resetAfterTest();

        $rule = new rule(0, (object)['name' => 'Test rule 1']);
        $rule->save();

        $this->assertEmpty($rule->get_condition_records());

        $condition1 = new condition(0, (object)['ruleid' => $rule->get('id'), 'classname' => 'test', 'position' => 0]);
        $condition1->save();
        $condition2 = new condition(0, (object)['ruleid' => $rule->get('id'), 'classname' => 'test', 'position' => 1]);
        $condition2->save();

        $actual = $rule->get_condition_records();
        $this->assertCount(2, $actual);

        $this->assertEquals($actual[$condition1->get('id')]->to_record(), $condition1->to_record());
        $this->assertEquals($actual[$condition2->get('id')]->to_record(), $condition2->to_record());
    }

    /**
     * Test marking a rule broken and unbroken.
     */
    public function test_mark_broken_and_unbroken() {
        $this->resetAfterTest();

        $rule = new rule(0, (object)['name' => 'Test rule 2', 'broken' => 0, 'enabled' => 1]);
        $this->assertFalse($rule->is_broken());
        $this->assertTrue($rule->is_enabled());

        $rule->mark_broken();
        $this->assertTrue($rule->is_broken());
        $this->assertFalse($rule->is_enabled());

        $rule->mark_unbroken();
        $this->assertFalse($rule->is_broken());
        $this->assertFalse($rule->is_enabled());
    }

}

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

use cache;

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
     * Test cache for condition records.
     */
    public function test_condition_records_get_cached() {
        $this->resetAfterTest();

        $cache = cache::make('tool_cohortmanager', 'rules');

        $rule = new rule(0, (object)['name' => 'Test rule']);
        $rule->save();
        $key = 'condition-records-' . $rule->get('id');

        $this->assertFalse($cache->get($key));

        $this->assertEmpty($rule->get_condition_records());
        $this->assertIsArray($cache->get($key));
        $this->assertEquals([], $cache->get($key));

        // Saving rule should purge the cache.
        $rule->save();
        $this->assertFalse($cache->get($key));

        $condition1 = new condition(0, (object)['ruleid' => $rule->get('id'), 'classname' => 'test', 'position' => 0]);
        $condition1->save();
        $condition2 = new condition(0, (object)['ruleid' => $rule->get('id'), 'classname' => 'test', 'position' => 1]);
        $condition2->save();

        // Saving conditions should purge the cache.
        $this->assertFalse($cache->get($key));

        $expected = $rule->get_condition_records();

        $this->assertCount(2, $expected);
        $this->assertEquals($expected[$condition1->get('id')]->to_record(), $condition1->to_record());
        $this->assertEquals($expected[$condition2->get('id')]->to_record(), $condition2->to_record());

        $this->assertSame($expected, $cache->get($key));
    }

}

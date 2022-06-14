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
     * Test rule deleting clear all related tables.
     */
    public function test_deleting_deletes_all_related_records() {
        global $DB;

        $this->resetAfterTest();

        $this->assertSame(0, $DB->count_records(rule::TABLE));
        $this->assertSame(0, $DB->count_records(condition::TABLE));
        $this->assertSame(0, $DB->count_records(match::TABLE));

        $rule = new rule(0, (object)['name' => 'Test rule']);
        $rule->save();

        $condition = new condition(0, (object)['ruleid' => $rule->get('id'), 'classname' => 'test']);
        $condition->save();

        $match = new match(0, (object)['ruleid' => $rule->get('id'), 'userid' => 2, 'matchedtime' => time(), 'status' => 1]);
        $match->save();

        $this->assertSame(1, $DB->count_records(rule::TABLE));
        $this->assertSame(1, $DB->count_records(condition::TABLE));
        $this->assertSame(1, $DB->count_records(match::TABLE));

        $rule->delete();
        $this->assertSame(0, $DB->count_records(rule::TABLE));
        $this->assertSame(0, $DB->count_records(condition::TABLE));
        $this->assertSame(0, $DB->count_records(match::TABLE));
    }

}

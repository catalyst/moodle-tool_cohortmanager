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

namespace tool_cohortmanager\external;

use externallib_advanced_testcase;
use tool_cohortmanager\rule;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/lib/externallib.php');
require_once($CFG->dirroot . '/webservice/tests/helpers.php');

/**
 * Tests for matching users external APIs .
 *
 * @package    tool_cohortmanager
 * @author     Dmitrii Metelkin <dmitriim@catalyst-au.net>
 * @copyright  2022 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class matching_users_test extends externallib_advanced_testcase {

    /**
     * Test exception if rule is not exist.
     */
    public function test_get_total_throws_exception_on_invalid_rule() {
        $this->resetAfterTest();

        $this->setAdminUser();
        $this->expectException(\invalid_parameter_exception::class);
        $this->expectExceptionMessage('Rule does not exist');

        matching_users::get_total(777);
    }

    /**
     * Test required permissions.
     */
    public function test_get_total_permissions() {
        $this->resetAfterTest();
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        $this->expectException(\required_capability_exception::class);
        $this->expectExceptionMessage('Sorry, but you do not currently have permissions to do that (Manage rules).');

        matching_users::get_total(777);
    }

    /**
     * Test can get total.
     */
    public function test_get_total() {
        $this->resetAfterTest();
        $this->setAdminUser();

        $rule = new rule(0, (object)['name' => 'Test rule 1']);
        $rule->save();

        $this->assertSame(0, matching_users::get_total($rule->get('id')));
    }
}

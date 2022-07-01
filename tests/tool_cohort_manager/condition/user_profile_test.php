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

/**
 * Unit tests for user profile condition class.
 *
 * @covers     \tool_cohortmanager\tool_cohortmanager\condition\user_profile
 * @package    tool_cohortmanager
 * @author     Dmitrii Metelkin <dmitriim@catalyst-au.net>
 * @copyright  2022 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class user_profile_test extends \advanced_testcase {

    /**
     * Get instance of user_profile for testing.
     *
     * @return condition_base
     */
    protected function get_condition(): condition_base {
        return condition_base::get_instance(0, (object)[
            'classname' => '\tool_cohortmanager\tool_cohortmanager\condition\user_profile'
        ]);
    }

    /**
     * A helper function to create a custom profile field.
     *
     * @param string $shortname Short name of the field.
     * @param string $datatype Type of the field, e.g. text, checkbox, datetime, menu and etc.
     * @param array $extras A list of extra fields for the field (e.g. forceunique, param1 and etc)
     *
     * @return \stdClass
     */
    protected function add_user_profile_field(string $shortname, string $datatype, array $extras = []): \stdClass {
        global $DB;

        $data = new \stdClass();
        $data->shortname = $shortname;
        $data->datatype = $datatype;
        $data->name = 'Test ' . $shortname;
        $data->description = 'This is a test field';
        $data->required = false;
        $data->locked = false;
        $data->forceunique = false;
        $data->signup = false;
        $data->visible = '0';
        $data->categoryid = '0';

        foreach ($extras as $name => $value) {
            $data->{$name} = $value;
        }

        $DB->insert_record('user_info_field', $data);

        return $data;
    }

    /**
     * Test retrieving of config data.
     */
    public function test_retrieving_configdata() {
        $formdata = (object)[
            'id' => 1,
            'profilefield' => 'firstname',
            'firstname_operator' => 3,
            'firstname_value' => 123,
            'invalid_firstname' => 'invalid',
            'ruleid' => 1,
            'position' => 0,
        ];

        $actual = $this->get_condition()::retrieve_config_data($formdata);
        $expected = [
            'profilefield' => 'firstname',
            'firstname_operator' => 3,
            'firstname_value' => 123,
        ];
        $this->assertEquals($expected, $actual);
    }

    /**
     * Test setting and getting config data.
     */
    public function test_set_and_get_configdata() {
        $configdata = [
            'profilefield' => 'firstname',
            'firstname_operator' => 3,
            'firstname_value' => 123,
        ];

        $instance = $this->get_condition();
        $instance->set_configdata($configdata);

        $this->assertEquals(
            ['profilefield' => 'firstname',  'firstname_operator' => 3,  'firstname_value' => 123],
            $instance->get_configdata()
        );
    }

    /**
     * Data provider for testing config description.
     *
     * @return array[]
     */
    public function config_description_data_provider(): array {
        return [
            [user_profile::TEXT_CONTAINS, 'First name contains 123'],
            [user_profile::TEXT_DOES_NOT_CONTAIN, 'First name doesn\'t contain 123'],
            [user_profile::TEXT_IS_EQUAL_TO, 'First name is equal to 123'],
            [user_profile::TEXT_IS_NOT_EQUAL_TO, 'First name isn\'t equal to 123'],
            [user_profile::TEXT_STARTS_WITH, 'First name starts with 123'],
            [user_profile::TEXT_ENDS_WITH, 'First name ends with 123'],
            [user_profile::TEXT_IS_EMPTY, 'First name is empty'],
            [user_profile::TEXT_IS_NOT_EMPTY, 'First name is not empty'],
        ];
    }

    /**
     * Test getting config description.
     *
     * @dataProvider config_description_data_provider
     * @param int $operator
     * @param string $expected
     */
    public function test_config_description(int $operator, string $expected) {
        $configdata = [
            'profilefield' => 'firstname',
            'firstname_operator' => $operator,
            'firstname_value' => '123',
        ];

        $instance = $this->get_condition();
        $instance->set_configdata($configdata);

        $this->assertSame($expected, $instance->get_config_description());
    }

    /**
     * Test setting and getting config data.
     */
    public function test_get_sql_data() {
        global $DB;

        $this->resetAfterTest();

        $field1 = $this->add_user_profile_field('field1', 'text');
        $field2 = $this->add_user_profile_field('field2', 'text', ['param1' => "Opt 1\nOpt 2\nOpt 3"]);

        $user1 = $this->getDataGenerator()->create_user(['username' => 'user1']);
        profile_save_data((object)['id' => $user1->id, 'profile_field_' . $field1->shortname => 'User 1 Field 1']);
        profile_save_data((object)['id' => $user1->id, 'profile_field_' . $field2->shortname => 'Opt 1']);

        $user2 = $this->getDataGenerator()->create_user(['username' => 'user2']);
        profile_save_data((object)['id' => $user2->id, 'profile_field_' . $field1->shortname => 'User 2 Field 1']);
        profile_save_data((object)['id' => $user2->id, 'profile_field_' . $field2->shortname => 'Opt 2']);

        $condition = $this->get_condition();
        $condition->set_configdata([
            'profilefield' => 'username',
            'username_operator' => user_profile::TEXT_IS_EQUAL_TO,
            'username_value' => 'user1',
        ]);

        $result = $condition->get_sql_data();
        $sql = "SELECT u.id FROM {user} u {$result->get_join()} WHERE {$result->get_where()}";
        $this->assertCount(1, $DB->get_records_sql($sql, $result->get_params()));

        $condition->set_configdata([
            'profilefield' => 'username',
            'username_operator' => user_profile::TEXT_STARTS_WITH,
            'username_value' => 'user',
        ]);

        $result = $condition->get_sql_data();
        $sql = "SELECT u.id FROM {user} u {$result->get_join()} WHERE {$result->get_where()}";
        $this->assertCount(2, $DB->get_records_sql($sql, $result->get_params()));

        $fieldname = 'profile_field_' . $field1->shortname;
        $condition->set_configdata([
            'profilefield' => $fieldname,
            $fieldname . '_operator' => user_profile::TEXT_ENDS_WITH,
            $fieldname . '_value' => 'Field 1',
        ]);

        $result = $condition->get_sql_data();
        $sql = "SELECT u.id FROM {user} u {$result->get_join()} WHERE {$result->get_where()}";
        $this->assertCount(2, $DB->get_records_sql($sql, $result->get_params()));

        $fieldname = 'profile_field_' . $field2->shortname;
        $condition->set_configdata([
            'profilefield' => $fieldname,
            $fieldname . '_operator' => user_profile::TEXT_IS_NOT_EQUAL_TO,
            $fieldname . '_value' => 'Opt 1',
        ]);

        $result = $condition->get_sql_data();
        $sql = "SELECT u.id FROM {user} u {$result->get_join()} WHERE {$result->get_where()}";
        $this->assertCount(1, $DB->get_records_sql($sql, $result->get_params()));
    }

}

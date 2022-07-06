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
use tool_cohortmanager\helper;
use tool_cohortmanager\sql_data;
use core_user;
use core_plugin_manager;

/**
 * Condition using user profile data.
 *
 * @package    tool_cohortmanager
 * @author     Dmitrii Metelkin <dmitriim@catalyst-au.net>
 * @copyright  2022 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class user_profile extends condition_base {

    public const FIELD_NAME = 'profilefield';
    public const FIELD_DATA_TYPE_TEXT = 'text';
    public const FIELD_DATA_TYPE_MENU = 'menu';

    public const TEXT_CONTAINS = 1;
    public const TEXT_DOES_NOT_CONTAIN = 2;
    public const TEXT_IS_EQUAL_TO = 3;
    public const TEXT_STARTS_WITH = 4;
    public const TEXT_ENDS_WITH = 5;
    public const TEXT_IS_EMPTY = 6;
    public const TEXT_IS_NOT_EMPTY = 7;
    public const TEXT_IS_NOT_EQUAL_TO = 8;

    /**
     * A list of supported default fields.
     */
    private const SUPPORTED_STANDARD_FIELDS = ['auth', 'firstname', 'lastname', 'username', 'email',  'idnumber',
        'city', 'country', 'institution', 'department'];

    /**
     * A list of supported custom profile fields.
     */
    private const SUPPORTED_CUSTOM_FIELDS = ['text', 'menu'];

    /**
     * Condition name.
     *
     * @return string
     */
    public function get_name(): string {
        return get_string('condition_user_profile', 'tool_cohortmanager');
    }

    /**
     * Add config form elements.
     *
     * @param \MoodleQuickForm $mform
     */
    public function config_form_add(\MoodleQuickForm $mform) {
        $options = [0 => get_string('select')];

        $fields = $this->get_fields_info();
        foreach ($fields as $shortname => $field) {
            $options[$shortname] = $field->name;
        }

        $group = [];
        $group[] = $mform->createElement('select', self::FIELD_NAME, '', $options);

        foreach ($fields as $shortname => $field) {
            switch ($field->datatype) {
                case self::FIELD_DATA_TYPE_TEXT:
                    $this->add_text_field($mform, $group, $field, $shortname);
                    break;
                case  self::FIELD_DATA_TYPE_MENU:
                    $this->add_menu_field($mform, $group, $field, $shortname);
                    break;
            }
        }

        $mform->addGroup($group, 'profilefieldgroup', get_string('profilefield', 'tool_cohortmanager'), '', false);
    }

    /**
     * Validate config form elements.
     *
     * @param array $data Data to validate.
     * @return array
     */
    public function config_form_validate(array $data): array {
        $errors = [];

        $fields = $this->get_fields_info();
        if (empty($data[self::FIELD_NAME]) || !isset($data[self::FIELD_NAME]) || !isset($fields[$data[self::FIELD_NAME]])) {
            $errors['profilefieldgroup'] = get_string('pleaseselectfield', 'tool_cohortmanager');
        }

        $fieldvalue = $data[self::FIELD_NAME] . '_value';
        $operator = $data[self::FIELD_NAME] . '_operator';
        $datatype = $fields[$data[self::FIELD_NAME]]->datatype ?? '';

        if (empty($data[$fieldvalue])) {
            if ($datatype == 'text' && !in_array($data[$operator], [self::TEXT_IS_EMPTY, self::TEXT_IS_NOT_EMPTY])) {
                $errors['profilefieldgroup'] = get_string('invalidfieldvalue', 'tool_cohortmanager');
            }
        }

        return $errors;
    }

    /**
     * Gets required config data from submitted condition form data.
     *
     * @param \stdClass $formdata
     * @return array
     */
    public static function retrieve_config_data(\stdClass $formdata): array {
        $configdata = parent::retrieve_config_data($formdata);
        $fieldname = $configdata[self::FIELD_NAME];

        $data = [];

        // Get field name itself.
        $data[self::FIELD_NAME] = $fieldname;

        // Only get values related to the selected field name, e.g firstname_operator, firstname_value.
        foreach ($configdata as $key => $value) {
            if (strpos($key, $fieldname . '_') === 0) {
                $data[$key] = $value;
            }
        }

        return $data;
    }

    /**
     * Gets an list of comparison operators for text fields.
     *
     * @return array A list of operators.
     */
    private function get_text_operators() : array {
        return [
            self::TEXT_CONTAINS => get_string('contains', 'filters'),
            self::TEXT_DOES_NOT_CONTAIN => get_string('doesnotcontain', 'filters'),
            self::TEXT_IS_EQUAL_TO => get_string('isequalto', 'filters'),
            self::TEXT_IS_NOT_EQUAL_TO => get_string('isnotequalto', 'filters'),
            self::TEXT_STARTS_WITH => get_string('startswith', 'filters'),
            self::TEXT_ENDS_WITH => get_string('endswith', 'filters'),
            self::TEXT_IS_EMPTY => get_string('isempty', 'filters'),
            self::TEXT_IS_NOT_EMPTY => get_string('isnotempty', 'tool_cohortmanager')
        ];
    }

    /**
     * Gets an list of comparison operators for menu fields.
     *
     * @return array A list of operators.
     */
    private function get_menu_operators() : array {
        return [
            self::TEXT_IS_EQUAL_TO => get_string('isequalto', 'filters'),
            self::TEXT_IS_NOT_EQUAL_TO => get_string('isnotequalto', 'filters'),
        ];
    }

    /**
     * Returns a list of all fields with extra data (shortname, name, datatype, param1 and type).
     *
     * @return \stdClass[]
     */
    private function get_fields_info(): array {
        global $CFG;

        require_once($CFG->dirroot.'/user/profile/lib.php');

        $fields = [];

        foreach (self::SUPPORTED_STANDARD_FIELDS as $field) {
            $fields[$field] = new \stdClass();
            $fields[$field]->shortname = $field;

            switch ($field) {
                case 'auth':
                    $options = [];
                    foreach (core_plugin_manager::instance()->get_plugins_of_type('auth') as $plugin) {
                        $options[$plugin->name] = $plugin->displayname;
                    }
                    $fields[$field]->name = get_string('type_auth', 'plugin');
                    $fields[$field]->datatype = self::FIELD_DATA_TYPE_MENU;
                    $fields[$field]->param1 = $options;
                    break;
                default:
                    $fields[$field]->name = get_string($field);
                    $fields[$field]->datatype = self::FIELD_DATA_TYPE_TEXT;
                    $fields[$field]->paramtype = core_user::get_property_type($field);
                    break;
            }
        }

        foreach (profile_get_user_fields_with_data(0) as $customfield) {
            if (!in_array($customfield->field->datatype, self::SUPPORTED_CUSTOM_FIELDS)) {
                continue;
            }

            $field = (object)array_intersect_key((array)$customfield->field,
                ['shortname' => 1, 'name' => 1, 'datatype' => 1, 'param1' => 1]);

            if ($field->datatype == 'menu') {
                $options = explode("\n", $field->param1);
                $field->param1 = array_combine($options, $options);
            } else if ($field->datatype == 'text') {
                $field->paramtype = PARAM_TEXT;
            }

            $shortname = 'profile_field_' . $field->shortname;
            $fields[$shortname] = $field;
        }

        return $fields;
    }

    /**
     * Adds a text field to the form.
     *
     * @param \MoodleQuickForm $mform Form to add the field to.
     * @param array $group A group to add the field to.
     * @param \stdClass $field Field info.
     * @param string $shortname A field shortname.
     */
    private function add_text_field(\MoodleQuickForm $mform, array &$group, \stdClass $field, string $shortname): void {
        $elements = [];
        $elements[] = $mform->createElement('select', $shortname . '_operator', null, $this->get_text_operators());
        $elements[] = $mform->createElement('text', $shortname . '_value', null);

        $mform->setType($shortname . '_value', $field->paramtype ?? PARAM_TEXT);
        $mform->hideIf($shortname . '_value', $shortname . '_operator', 'in', self::TEXT_IS_EMPTY . '|' . self::TEXT_IS_NOT_EMPTY);

        $group[] = $mform->createElement('group', $shortname, '', $elements, '', false);
        $mform->hideIf($shortname, self::FIELD_NAME, 'neq', $shortname);
    }

    /**
     * Adds a menu field to the form.
     *
     * @param \MoodleQuickForm $mform Form to add the field to.
     * @param array $group A group to add the field to.
     * @param \stdClass $field Field info.
     * @param string $shortname A field shortname.
     */
    private function add_menu_field(\MoodleQuickForm $mform, array &$group, \stdClass $field, string $shortname): void {
        $options = (array) $field->param1;
        $elements = [];
        $elements[] = $mform->createElement('select', $shortname . '_operator', null, $this->get_menu_operators());

        $elements[] = $mform->createElement('select', $shortname . '_value', $field->name, $options);
        $mform->hideIf($shortname . '_value', $shortname . '_operator', 'in', self::TEXT_IS_EMPTY . '|' . self::TEXT_IS_NOT_EMPTY);

        $group[] = $mform->createElement('group', $shortname, '', $elements, '', false);
        $mform->hideIf($shortname, self::FIELD_NAME, 'neq', $shortname);
    }

    /**
     * Returns a field name for the configured field.
     *
     * @return string
     */
    private function get_field_name(): string {
        return $this->get_configdata()[self::FIELD_NAME];
    }

    /**
     * Returns a value of the configured field.
     *
     * @return string|null
     */
    private function get_field_value(): ?string {
        $fieldvalue = null;
        $field = $this->get_field_name();
        $configdata = $this->get_configdata();

        if (!empty($field) && isset($configdata[$field . '_value'])) {
            $fieldvalue = $configdata[$field . '_value'];
            if ($field == 'auth') {
                $authplugins = core_plugin_manager::instance()->get_plugins_of_type('auth');
                $fieldvalue = $authplugins[$fieldvalue]->displayname;
            }
        }

        return $fieldvalue;
    }

    /**
     * Return the field name as a text.
     *
     * @return string
     */
    private function get_field_text(): string {
        return $this->get_fields_info()[$this->get_field_name()]->name ?? '-';
    }


    /**
     * Returns a value for the configured operator.
     *
     * @return int
     */
    private function get_operator_value(): int {
        return $this->get_configdata()[$this->get_field_name() . '_operator'] ?? self::TEXT_IS_EQUAL_TO;
    }

    /**
     *  Returns a text for the configured operator based on a field data type.
     *
     * @param string $fielddatatype Field data type.
     * @return string
     */
    private function get_operator_text(string $fielddatatype): string {
        if ($fielddatatype == self::FIELD_DATA_TYPE_TEXT) {
            return $this->get_text_operators()[$this->get_operator_value()];
        }

        if ($fielddatatype == self::FIELD_DATA_TYPE_MENU) {
            return $this->get_menu_operators()[$this->get_operator_value()];
        }

        return $this->get_text_operators()[$this->get_operator_value()];
    }

    /**
     * Human readable description of the configured condition.
     *
     * @return string
     */
    public function get_config_description(): string {
        $fieldname = $this->get_field_name();

        if (empty($fieldname)) {
            return '';
        }

        $datatype = $this->get_fields_info()[$fieldname]->datatype;

        if (in_array($this->get_operator_value(), [self::TEXT_IS_EMPTY, self::TEXT_IS_NOT_EMPTY])) {
            return $this->get_field_text() . ' ' . $this->get_operator_text($datatype);
        } else {
            return $this->get_field_text() . ' '. $this->get_operator_text($datatype) . ' ' . $this->get_field_value();
        }
    }

    /**
     * Gets SQL data for building SQL.
     *
     * @return \tool_cohortmanager\sql_data
     */
    public function get_sql_data(): sql_data {
        $result = new sql_data('', '1=0', []);

        $configuredfield = $this->get_field_name();
        $datatype = $this->get_fields_info()[$configuredfield]->datatype;

        if (in_array($configuredfield, self::SUPPORTED_STANDARD_FIELDS)) {
            $iscustomfield = false;
            $dbfieldname = $configuredfield;
            $ud = 'u';
        } else {
            $iscustomfield = true;
            $dbfieldname = 'data';
            $ud = helper::generate_table_alias();
        }

        if ($datatype == self::FIELD_DATA_TYPE_TEXT) {
            $result = $this->get_text_sql_data($ud, $dbfieldname);
        } else if ($datatype == self::FIELD_DATA_TYPE_MENU) {
            $result = $this->get_menu_sql_data($ud, $dbfieldname);
        }

        // If custom profile field we need to JOIN on extra tables as data is stored in user_info_data
        // and fields information is in user_info_field.
        if ($iscustomfield && !empty($result->get_params())) {
            $userinfofield = helper::generate_table_alias();
            $userinfodata = helper::generate_table_alias();

            $shortnameparam = helper::generate_param_alias();
            $extrafields = "{$userinfodata}.data, {$userinfodata}.userid";

            $join = "LEFT JOIN (SELECT $extrafields
                                 FROM {user_info_data} $userinfodata
                                 JOIN {user_info_field} $userinfofield
                                   ON ({$userinfofield}.id = {$userinfodata}.fieldid
                                      AND {$userinfofield}.shortname = :{$shortnameparam})) $ud
                           ON ({$ud}.userid = u.id)";

            $params = $result->get_params();
            $params[$shortnameparam] = str_replace('profile_field_', '', $configuredfield);

            $result = new sql_data($join, $result->get_where(), $params);
        }

        return  $result;
    }

    /**
     * Get SQl data for text type fields.
     *
     * @param string $tablealias Alias for a table.
     * @param string $fieldname Field name.
     * @return \tool_cohortmanager\sql_data
     */
    private function get_text_sql_data(string $tablealias, string $fieldname): sql_data {
        global $DB;

        $fieldvalue = $this->get_field_value();
        $operatorvalue = $this->get_operator_value();

        if ($this->is_broken()) {
            return new sql_data('', '', []);
        }

        $param = helper::generate_param_alias();

        switch ($operatorvalue) {
            case self::TEXT_CONTAINS:
                $where = $DB->sql_like("$tablealias.$fieldname", ":$param", false, false);
                $value = $DB->sql_like_escape($fieldvalue);
                $params[$param] = "%$value%";
                break;
            case self::TEXT_DOES_NOT_CONTAIN:
                $where = $DB->sql_like("$tablealias.$fieldname", ":$param", false, false, true);
                $fieldvalue = $DB->sql_like_escape($fieldvalue);
                $params[$param] = "%$fieldvalue%";
                break;
            case self::TEXT_IS_EQUAL_TO:
                $where = $DB->sql_equal($DB->sql_compare_text("{$tablealias}.{$fieldname}"), ":$param", false, false);
                $params[$param] = $fieldvalue;
                break;
            case self::TEXT_IS_NOT_EQUAL_TO:
                $where = $DB->sql_equal($DB->sql_compare_text("{$tablealias}.{$fieldname}"), ":$param", false, false, true);
                $params[$param] = $fieldvalue;
                break;
            case self::TEXT_STARTS_WITH:
                $where = $DB->sql_like("$tablealias.$fieldname", ":$param", false, false);
                $fieldvalue = $DB->sql_like_escape($fieldvalue);
                $params[$param] = "$fieldvalue%";
                break;
            case self::TEXT_ENDS_WITH:
                $where = $DB->sql_like("$tablealias.$fieldname", ":$param", false, false);
                $fieldvalue = $DB->sql_like_escape($fieldvalue);
                $params[$param] = "%$fieldvalue";
                break;
            case self::TEXT_IS_EMPTY:
                $where = $DB->sql_compare_text("$tablealias.$fieldname") . " = " . $DB->sql_compare_text(":$param");
                $params[$param] = '';
                break;
            case self::TEXT_IS_NOT_EMPTY:
                $where = $DB->sql_compare_text("$tablealias.$fieldname") . " != " . $DB->sql_compare_text(":$param");
                $params[$param] = '';
                break;
            default:
                return new sql_data('', '', []);
        }

        return new sql_data('', $where, $params);
    }

    /**
     * Get SQL data for menu type fields.
     *
     * @param string $tablealias Alias for a table.
     * @param string $fieldname Field name.
     * @return \tool_cohortmanager\sql_data
     */
    private function get_menu_sql_data(string $tablealias, string $fieldname): sql_data {
        global $DB;

        $fieldvalue = $this->get_field_value();
        $operatorvalue = $this->get_operator_value();

        if ($this->is_broken()) {
            return new sql_data('', '', []);
        }

        $param = helper::generate_param_alias();

        switch ($operatorvalue) {
            case self::TEXT_IS_EQUAL_TO:
                $where = $DB->sql_equal($DB->sql_compare_text("{$tablealias}.{$fieldname}"), ":$param", false, false);
                $params[$param] = $fieldvalue;
                break;
            case self::TEXT_IS_NOT_EQUAL_TO:
                $where = $DB->sql_equal($DB->sql_compare_text("{$tablealias}.{$fieldname}"), ":$param", false, false, true);
                $params[$param] = $fieldvalue;
                break;
            default:
                return new sql_data('', '', []);
        }

        return new sql_data('', $where, $params);
    }

    /**
     * A list of events the condition is listening to.
     *
     * @return string[]
     */
    public function get_events(): array {
        return [
            '\core\event\user_created',
            '\core\event\user_updated',
        ];
    }

    /**
     * Is condition broken.
     *
     * @return bool
     */
    public function is_broken(): bool {
        if ($this->get_configdata()) {
            $configuredfield = $this->get_field_name();
            $fieldvalue = $this->get_field_value();
            $operatorvalue = $this->get_operator_value();

            if ($fieldvalue === '' && $operatorvalue != self::TEXT_IS_EMPTY && $operatorvalue != self::TEXT_IS_NOT_EMPTY) {
                return true;
            }

            return !key_exists($configuredfield, $this->get_fields_info());
        }

        return false;
    }

}

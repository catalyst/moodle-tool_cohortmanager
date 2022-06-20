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
 * Abstract class that all conditions should extend.
 *
 * @package    tool_cohortmanager
 * @author     Dmitrii Metelkin <dmitriim@catalyst-au.net>
 * @copyright  2022 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class condition_base {

    /**
     * Condition persistent object.
     * @var $condition
     */
    protected $condition;

    /**
     * Protected constructor.
     */
    protected function __construct() {
    }

    /**
     * Return an instance of condition object.
     *
     * @param int $id
     * @param \stdClass|null $record
     *
     * @return \tool_cohortmanager\condition_base|null
     */
    final public static function get_instance(int $id = 0, ?\stdClass $record = null):? condition_base {
        $condition = new condition($id, $record);

        // In case we are getting the instance without underlying persistent data.
        if (!$classname = $condition->get('classname')) {
            $classname = get_called_class();
            $condition->set('classname', $classname);
        }

        if (!class_exists($classname) || !is_subclass_of($classname, self::class)) {
            return null;
        }

        $instance = new $classname();
        $instance->condition = $condition;
        return $instance;
    }

    /**
     * Returns the name of the condition
     *
     * @return string
     */
    abstract public function get_name(): string;


    /**
     * Add condition config form elements.
     *
     * @param \MoodleQuickForm $mform The form to add elements to
     */
    abstract public function config_form_add(\MoodleQuickForm $mform);

    /**
     * Validates conditions form elements.
     *
     * @param array $data Form data.
     * @return array Errors array.
     */
    abstract public function config_form_validate(array $data): array;

}

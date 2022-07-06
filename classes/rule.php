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

use core\persistent;
use cache_helper;
use cache;

/**
 * Rules persistent class.
 *
 * @package    tool_cohortmanager
 * @author     Dmitrii Metelkin <dmitriim@catalyst-au.net>
 * @copyright  2022 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class rule extends persistent {

    /** @var string table. */
    const TABLE = 'tool_cohortmanager';

    /**
     * Return the definition of the properties of this model.
     *
     * @return array
     */
    protected static function define_properties() {
        return [
            'name' => [
                'type' => PARAM_TEXT,
            ],
            'enabled' => [
                'type' => PARAM_INT,
                'default' => 0,
            ],
            'cohortid' => [
                'type' => PARAM_INT,
                'default' => 0,
            ],
            'description' => [
                'type' => PARAM_TEXT,
                'default' => null,
                'null' => NULL_ALLOWED,
            ],
        ];
    }

    /**
     * Return if the rule is enabled.
     *
     * @return bool
     */
    public function is_enabled() : bool {
        return (bool)$this->get('enabled');
    }

    /**
     * Get a list of condition records for that rule.
     *
     * @return condition[]
     */
    public function get_condition_records(): array {
        $cache = cache::make('tool_cohortmanager', 'rules');
        $key = 'condition-records-' . $this->get('id');
        $conditions = $cache->get($key);

        if ($conditions === false) {
            $conditions = [];
            foreach (condition::get_records(['ruleid' => $this->get('id')], 'position') as $condition) {
                $conditions[$condition->get('id')] = $condition;
            }
            $cache->set($key, $conditions);
        }

        return $conditions;
    }

    /**
     * Hook after a condition is deleted.
     *
     * @param bool $result Whether or not the delete was successful.
     * @return void
     */
    protected function after_delete($result): void {
        if ($result) {
            cache_helper::purge_by_event('ruleschanged');
        }
    }

    /**
     * Hook after created a rule.
     */
    protected function after_create() {
        cache_helper::purge_by_event('ruleschanged');
    }

    /**
     * Hook after updating a rule.
     *
     * @param bool $result
     */
    protected function after_update($result) {
        cache_helper::purge_by_event('ruleschanged');
    }
}

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
            'broken' => [
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
     * Return if the rule is broken.
     *
     * @param bool $checkconditions If false, only DB state will be checked, otherwise conditions state will be checked.
     * @return bool
     */
    public function is_broken(bool $checkconditions = false): bool {
        if ($checkconditions) {
            $broken = false;

            foreach ($this->get_condition_records() as $condition) {
                $instance = condition_base::get_instance(0, $condition->to_record());
                if (!$instance || $instance->is_broken()) {
                    $broken = true;
                    break;
                }
            }
        } else {
            $broken = (bool)$this->get('broken');
        }

        return $broken;
    }

    /**
     * Mark rule as broken.
     */
    public function mark_broken(): void {
        $this->set('broken', 1);
        $this->set('enabled', 0);
        $this->save();
    }

    /**
     * Mark rule as unbroken,
     */
    public function mark_unbroken(): void {
        $this->set('broken', 0);
        $this->save();
    }

    /**
     * Get a list of condition records for that rule.
     *
     * @return condition[]
     */
    public function get_condition_records(): array {
        // TODO: add cache.
        $conditions = [];
        foreach (condition::get_records(['ruleid' => $this->get('id')], 'position') as $condition) {
            $conditions[$condition->get('id')] = $condition;
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

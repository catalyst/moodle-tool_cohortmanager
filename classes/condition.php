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
use tool_dynamicrule\outcome;

/**
 * Conditions persistent class.
 *
 * @package    tool_cohortmanager
 * @author     Dmitrii Metelkin <dmitriim@catalyst-au.net>
 * @copyright  2022 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class condition extends persistent {

    /** @var string table. */
    const TABLE = 'tool_cohortmanager_cond';

    /**
     * Return the definition of the properties of this model.
     *
     * @return array
     */
    protected static function define_properties() {
        return [
            'ruleid' => [
                'type' => PARAM_INT,
            ],
            'classname' => [
                'type' => PARAM_TEXT,
            ],
            'configdata' => [
                'type' => PARAM_RAW,
                'default' => '{}',
            ],
            'position' => [
                'type' => PARAM_INT,
            ],
        ];
    }

    /**
     * Hook after a condition is deleted.
     *
     * @param bool $result Whether or not the delete was successful.
     * @return void
     */
    protected function after_delete($result): void {
        if ($result) {
            cache_helper::purge_by_event('conditionschanged');
        }
    }

    /**
     * Hook after created a rule.
     */
    protected function after_create() {
        cache_helper::purge_by_event('conditionschanged');
    }

    /**
     * Hook after updating a rule.
     *
     * @param bool $result
     */
    protected function after_update($result) {
        cache_helper::purge_by_event('conditionschanged');
    }

}

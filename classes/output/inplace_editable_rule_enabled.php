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

namespace tool_cohortmanager\output;

use core\output\inplace_editable;
use tool_cohortmanager\helper;
use tool_cohortmanager\rule;

/**
 * In place editable for rule enabled.
 *
 * @package    tool_cohortmanager
 * @author     Dmitrii Metelkin <dmitriim@catalyst-au.net>
 * @copyright  2022 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class inplace_editable_rule_enabled extends inplace_editable {

    /**
     * inplace_editable_rule_enabled constructor.
     *
     * @param \tool_cohortmanager\rule $rule
     */
    public function __construct(rule $rule) {

        $class = $rule->is_enabled() ? '' : 'dimmed_text';
        $icon = $rule->is_enabled() ? 't/hide' : 't/show';
        $alt = $rule->is_enabled() ? get_string('disable') : get_string('enable');
        $value = $rule->is_enabled() ? 1 : 0;

        parent::__construct(
            'tool_cohortmanager',
            'ruleenabled',
            $rule->get('id'),
            true,
            helper::get_renderer()->pix_icon($icon, $alt, 'moodle', ['title' => $alt, 'class' => $class]),
            $value
        );

        $this->set_type_toggle();
    }

}

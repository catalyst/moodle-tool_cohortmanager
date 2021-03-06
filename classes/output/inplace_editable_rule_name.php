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
use html_writer;

/**
 * In place editable for rule name.
 *
 * @package    tool_cohortmanager
 * @author     Dmitrii Metelkin <dmitriim@catalyst-au.net>
 * @copyright  2022 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class inplace_editable_rule_name extends inplace_editable {

    /**
     * inplace_editable_rule_name constructor.
     *
     * @param \tool_cohortmanager\rule $rule
     */
    public function __construct(rule $rule) {
        parent::__construct(
            'tool_cohortmanager',
            'rulename',
            $rule->get('id'),
            true,
            html_writer::link(helper::build_rule_edit_url($rule), $rule->get('name')),
            $rule->get('name'),
            $rule->get('name')
        );
    }

}

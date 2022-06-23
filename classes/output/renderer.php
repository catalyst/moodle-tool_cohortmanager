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

use plugin_renderer_base;
use tool_cohortmanager\local\table\managerules;

/**
 * Renderer class.
 *
 * @package    tool_cohortmanager
 * @author     Dmitrii Metelkin <dmitriim@catalyst-au.net>
 * @copyright  2022 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class renderer extends plugin_renderer_base {

    /**
     * Render rules table.
     *
     * @param managerules $rules
     * @return string
     */
    public function render_managerules(managerules $rules): string {
        ob_start();
        $rules->out($rules->pagesize, true);
        $html = ob_get_contents();
        ob_end_clean();

        return $html;
    }

    /**
     * Render editable rule name.
     *
     * @param inplace_editable_rule_name $renderable
     * @return string
     */
    public function render_inplace_editable_rule_name(inplace_editable_rule_name $renderable): string {
        return $this->render_from_template('core/inplace_editable', $renderable->export_for_template($this));

    }

    /**
     * Render editable rule description.
     *
     * @param inplace_editable_rule_description $renderable
     * @return string
     */
    public function render_inplace_editable_rule_description(inplace_editable_rule_description $renderable): string {
        return $this->render_from_template('core/inplace_editable', $renderable->export_for_template($this));
    }

    /**
     * Render editable rule enable/diable icon.
     *
     * @param inplace_editable_rule_enabled $renderable
     * @return string
     */
    public function render_inplace_editable_rule_enabled(inplace_editable_rule_enabled $renderable): string {
        return $this->render_from_template('core/inplace_editable', $renderable->export_for_template($this));

    }

}

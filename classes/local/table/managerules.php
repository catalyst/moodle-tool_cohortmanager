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

namespace tool_cohortmanager\local\table;

use core\notification;
use tool_cohortmanager\helper;
use tool_cohortmanager\output\inplace_editable_rule_description;
use tool_cohortmanager\output\inplace_editable_rule_enabled;
use tool_cohortmanager\output\inplace_editable_rule_name;
use tool_cohortmanager\rule;
use tool_cohortmanager\output\renderer;
use html_writer;
use table_sql;
use renderable;
use moodle_url;

defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir . '/tablelib.php');

/**
 * List of rules.
 *
 * @package    tool_cohortmanager
 * @author     Dmitrii Metelkin <dmitriim@catalyst-au.net>
 * @copyright  2022 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class managerules extends table_sql implements renderable {

    /**
     * A list of all cohorts.
     * @var array
     */
    protected $cohorts = [];

    /**
     * Plugin render.
     * @var renderer
     */
    protected $renderer;

    /**
     * Sets up the table.
     *
     * @param string $uniqueid Unique id of form.
     * @param moodle_url $url Url where this table is displayed.
     * @param int $perpage Number of rules to display per page.
     */
    public function __construct($uniqueid, moodle_url $url, int $perpage = 100) {
        parent::__construct($uniqueid);

        $this->define_columns([
            'name',
            'description',
            'cohort',
            'users',
            'broken',
            'manage',
        ]);

        $this->define_headers([
            get_string('name'),
            get_string('description'),
            get_string('cohort', 'cohort'),
            get_string('matchingusers', 'tool_cohortmanager'),
            get_string('broken', 'tool_cohortmanager'),
            get_string('actions'),
        ]);

        $this->collapsible(false);
        $this->sortable(false);
        $this->pageable(true);
        $this->is_downloadable(false);
        $this->define_baseurl($url);

        $this->renderer = helper::get_renderer();
        $this->cohorts = helper::get_available_cohorts();
        $this->pagesize = $perpage;
    }

    /**
     * Generate content for name column.
     *
     * @param rule $rule rule object
     * @return string
     */
    public function col_name(rule $rule): string {
        return $this->renderer->render(new inplace_editable_rule_name($rule));
    }


    /**
     * Generate content for description column.
     *
     * @param rule $rule rule object
     * @return string
     */
    public function col_description(rule $rule): string {
        return $this->renderer->render(new inplace_editable_rule_description($rule));
    }


    /**
     * Generate content for cohort column.
     *
     * @param rule $rule rule object
     * @return string
     */
    public function col_cohort(rule $rule): string {
        if (!empty($this->cohorts[$rule->get('cohortid')])) {
            return $this->cohorts[$rule->get('cohortid')]->name;
        } else {
            return '-';
        }
    }

    /**
     * Generate content for users column.
     *
     * @param rule $rule rule object
     * @return string
     */
    public function col_users(rule $rule): string {
        return number_format(count(helper::get_matching_users($rule)), 0, ',', ' ');
    }

    /**
     * Generate content for broken column.
     *
     * @param rule $rule rule object
     * @return string
     */
    public function col_broken(rule $rule): string {
        if ($rule->is_broken()) {
            notification::warning(get_string('brokenruleswarning', 'tool_cohortmanager'));
            return get_string('yes');
        }

        return get_string('no');
    }

    /**
     * Generate content for manage column.
     *
     * @param rule $rule rule object
     * @return string
     */
    public function col_manage(rule $rule): string {
        $manage = '';

        $manage .= $this->renderer->render(new inplace_editable_rule_enabled($rule));

        $editurl = helper::build_rule_edit_url($rule);
        $icon = $this->renderer->render(new \pix_icon('t/edit', get_string('edit')));
        $manage .= html_writer::link($editurl, $icon, array('class' => 'action-icon'));

        $deleteurl = helper::build_rule_delete_url($rule);
        $icon = $this->renderer->render(new \pix_icon('t/delete', get_string('delete')));
        $manage .= html_writer::link($deleteurl, $icon, array('class' => 'action-icon'));

        return $manage;
    }

    /**
     * Query the reader. Store results in the object for use by build_table.
     *
     * @param int $pagesize size of page for paginated displayed table.
     * @param bool $useinitialsbar do you want to use the initials bar.
     */
    public function query_db($pagesize, $useinitialsbar = true) {
        $total = rule::count_records();

        $this->pagesize($pagesize, $total);
        $rules = rule::get_records([], 'name', 'ASC', $this->get_page_start(), $this->get_page_size());

        // Make sure that we update rules before displaying.
        foreach ($rules as $rule) {
            if ($rule->is_broken(true)) {
                $rule->mark_broken();
            }
        }

        // Sort disable to the bottom.
        usort($rules, function(rule $a, rule $b) {
            return ($a->is_enabled() < $b->is_enabled());
        });

        $this->rawdata = $rules;

        if ($useinitialsbar) {
            $this->initialbars($total > $pagesize);
        }
    }

}

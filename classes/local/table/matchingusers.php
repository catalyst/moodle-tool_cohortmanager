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

use tool_cohortmanager\rule;
use table_sql;
use renderable;
use moodle_url;
use tool_cohortmanager\helper;
use core_user;
use html_writer;

defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir . '/tablelib.php');

/**
 * List of users matching a rule.
 *
 * @package    tool_cohortmanager
 * @author     Dmitrii Metelkin <dmitriim@catalyst-au.net>
 * @copyright  2022 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class matchingusers extends table_sql implements renderable {

    /**
     * A list of all cohorts.
     * @var array
     */
    protected $cohorts = [];

    /**
     * Rule to display users for.
     * @var \tool_cohortmanager\rule;
     */
    protected $rule;

    /**
     * Sets up the table.
     *
     * @param string $uniqueid Unique id of form.
     * @param rule $rule Rule to display users for.
     * @param moodle_url $url Url where this table is displayed.
     * @param int $perpage Number of rules to display per page.
     */
    public function __construct($uniqueid, rule $rule, moodle_url $url, $download, int $perpage = 100) {
        parent::__construct($uniqueid);

        $this->define_columns([
            'user',
            'cohort',
        ]);

        $this->define_headers([
            get_string('name'),
            get_string('cohort', 'cohort'),
        ]);

        $this->collapsible(false);
        $this->sortable(false);
        $this->pageable(true);
        $this->is_downloadable(true);
        $this->define_baseurl($url);
        $this->show_download_buttons_at([TABLE_P_BOTTOM]);

        // Set download status.
        $this->is_downloading($download, 'matching-users-rule-' . $rule->get('id'));

        $this->rule = $rule;
        $this->cohorts = helper::get_available_cohorts();
        $this->pagesize = $perpage;
    }

    /**
     * Generate content for name column.
     *
     * @param \stdClass $user rule object
     * @return string
     */
    public function col_user(\stdClass $user): string {
        $user = core_user::get_user($user->id);
        $fullname = fullname($user);

        if ($this->is_downloading()) {
            return $fullname;
        } else {
            $url = new \moodle_url('/user/profile.php', ['id' => $user->id]);
            return html_writer::link($url, $fullname);
        }
    }

    /**
     * Generate content for cohort column.
     *
     * @param \stdClass $user rule object
     * @return string
     */
    public function col_cohort(\stdClass $user): string {
        if (!empty($this->cohorts[$this->rule->get('cohortid')])) {
            return $this->cohorts[$this->rule->get('cohortid')]->name;
        } else {
            return '-';
        }
    }

    /**
     * Query the reader. Store results in the object for use by build_table.
     *
     * @param int $pagesize size of page for paginated displayed table.
     * @param bool $useinitialsbar do you want to use the initials bar.
     */
    public function query_db($pagesize, $useinitialsbar = true) {
        $users = helper::get_matching_users($this->rule);
        $total = count($users);

        $this->pagesize($pagesize, $total);

        if (!$this->is_downloading()) {
            $this->rawdata = array_slice($users, ($pagesize * $this->currpage), $pagesize );
        } else {
            $this->rawdata = $users;
        }

        if ($useinitialsbar) {
            $this->initialbars($total > $pagesize);
        }
    }

}

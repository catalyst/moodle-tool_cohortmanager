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

/**
 * List of users matching a rule.
 *
 * @package    tool_cohortmanager
 * @author     Dmitrii Metelkin <dmitriim@catalyst-au.net>
 * @copyright  2022 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use tool_cohortmanager\local\table\matchingusers;
use tool_cohortmanager\helper;
use tool_cohortmanager\rule;

require_once(__DIR__.'/../../../config.php');
require_once($CFG->libdir.'/adminlib.php');

$ruleid = required_param('ruleid', PARAM_INT);
$download = optional_param('download', '', PARAM_ALPHA);

admin_externalpage_setup('tool_cohortmanager_managerules');

$rule = rule::get_record(['id' => $ruleid]);
if (empty($rule)) {
    throw new dml_missing_record_exception(null);
}

$heading = get_string('usersforrule', 'tool_cohortmanager', $rule->get('name'));
$PAGE->navbar->add($heading);

$url = new moodle_url('/admin/tool/cohortmanager/users.php', ['ruleid' => $ruleid]);
$matchingusers = new matchingusers('tool_cohortmanager_users', $rule, $url, $download);
$renderer = helper::get_renderer();

if ($matchingusers->is_downloading()) {
    echo $renderer->render($matchingusers);
    die();
}

echo $renderer->header();
echo $renderer->heading($heading);
echo $renderer->render($matchingusers);
echo $renderer->footer();

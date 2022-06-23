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
 * Delete action page.
 *
 * @package   tool_cohortmanager
 * @author    Dmitrii Metelkin (dmitriim@catalyst-au.net)
 * @copyright 2022 Catalyst IT
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use tool_cohortmanager\rule;

require_once(__DIR__.'/../../../config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->libdir.'/formslib.php');

admin_externalpage_setup('tool_cohortmanager_managerules');

$ruleid = required_param('ruleid', PARAM_INT);
$confirm = optional_param('confirm', '', PARAM_ALPHANUM);

$manageurl = new moodle_url('/admin/tool/cohortmanager/index.php');

$rule = rule::get_record(['id' => $ruleid]);
if (empty($rule)) {
    throw new dml_missing_record_exception(null);
}

if ($confirm != md5($ruleid)) {
    $confirmstring = get_string('delete_confirm', 'tool_cohortmanager', $rule->get('name'));
    $cinfirmoptions = ['ruleid' => $ruleid, 'confirm' => md5($ruleid), 'sesskey' => sesskey()];
    $deleteurl = new moodle_url('/admin/tool/cohortmanager/delete.php', $cinfirmoptions);

    $PAGE->navbar->add(get_string('delete_breadcrumb', 'tool_cohortmanager'));

    echo $OUTPUT->header();
    echo $OUTPUT->confirm($confirmstring, $deleteurl, $manageurl);
    echo $OUTPUT->footer();

} else if (data_submitted() and confirm_sesskey()) {
    $rule->delete();
    redirect($manageurl);
}

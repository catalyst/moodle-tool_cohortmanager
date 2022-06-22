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
 * Rules edit page.
 *
 * @package    tool_cohortmanager
 * @author     Dmitrii Metelkin <dmitriim@catalyst-au.net>
 * @copyright  2022 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use core\notification;
use tool_cohortmanager\rule_form;
use tool_cohortmanager\rule;
use tool_cohortmanager\helper;

require_once(__DIR__.'/../../../config.php');
require_once($CFG->libdir.'/adminlib.php');

$ruleid = optional_param('ruleid', 0, PARAM_INT);
$action = !empty($ruleid) ? 'edit' : 'add';

admin_externalpage_setup('tool_cohortmanager_managerules');

$manageurl = new moodle_url('/admin/tool/cohortmanager/index.php');
$editurl = new moodle_url('/admin/tool/cohortmanager/edit.php');
$PAGE->navbar->add(get_string($action . '_breadcrumb', 'tool_cohortmanager'));

$mform = new rule_form();

if (!empty($ruleid)) {
    $rule = rule::get_record(['id' => $ruleid]);
    if (empty($rule)) {
        throw new dml_missing_record_exception(null);
    } else {
        $mform->set_data($rule->to_record());
    }
}

if ($mform->is_cancelled()) {
    redirect($manageurl);
} else if ($formdata = $mform->get_data()) {
    try {
        helper::process_rule_form($formdata);
        notification::success(get_string('changessaved'));
    } catch (Exception $e) {
        notification::error($e->getMessage());
    }
    redirect($manageurl);
}

echo $OUTPUT->header();
$mform->display();
echo $OUTPUT->footer();

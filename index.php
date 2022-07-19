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
 * Rules manage page.
 *
 * @package    tool_cohortmanager
 * @author     Dmitrii Metelkin <dmitriim@catalyst-au.net>
 * @copyright  2022 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use tool_cohortmanager\local\table\managerules;
use tool_cohortmanager\helper;

require_once(__DIR__.'/../../../config.php');
require_once($CFG->libdir.'/adminlib.php');

admin_externalpage_setup('tool_cohortmanager_managerules');

$manageurl = new moodle_url('/admin/tool/cohortmanager/index.php');
$editurl = new moodle_url('/admin/tool/cohortmanager/edit.php');
$rulestable = new managerules('tool_cohortmanager_rules', $manageurl);
$renderer = helper::get_renderer();

echo $renderer->header();
echo $renderer->heading(get_string('managerules', 'tool_cohortmanager'));
echo $renderer->render_from_template('tool_cohortmanager/addrulebutton', ['url' => $editurl]);
echo $renderer->render($rulestable);
echo $renderer->footer();

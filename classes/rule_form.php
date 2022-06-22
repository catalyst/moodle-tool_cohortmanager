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

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/formslib.php');
require_once($CFG->dirroot.'/cohort/lib.php');

/**
 * A form for adding/editing rules.
 *
 * @package    tool_cohortmanager
 * @author     Dmitrii Metelkin <dmitriim@catalyst-au.net>
 * @copyright  2022 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class rule_form extends \moodleform {

    /**
     * Form definition.
     */
    protected function definition() {
        $mform = $this->_form;

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);
        $mform->setDefault('id', 0);

        $mform->addElement('text', 'name', get_string('name', 'tool_cohortmanager'), 'size="50"');
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', get_string('required'), 'required');
        $mform->addHelpButton('name', 'name', 'tool_cohortmanager');

        $mform->addElement('textarea', 'description', get_string('description', 'tool_cohortmanager'), ['rows' => 5, 'cols' => 50]);
        $mform->addHelpButton('description', 'description', 'tool_cohortmanager');
        $mform->setType('description', PARAM_TEXT);

        $mform->addElement(
            'autocomplete',
            'cohortid',
            get_string('cohortid', 'tool_cohortmanager'),
            array_merge(
                ['' => get_string('choosedots')],
                $this->get_cohort_options()
            ),
            ['noselectionstring' => get_string('choosedots')]
        );
        $mform->addHelpButton('cohortid', 'cohortid', 'tool_cohortmanager');
        $mform->addRule('cohortid', get_string('required'), 'required');

        $mform->addElement('advcheckbox',
            'enabled',
            get_string ('enabled', 'tool_cohortmanager'),
            get_string ('enable'), [], [0, 1]);
        $mform->setType('tool_cohortmanager', PARAM_INT);
        $mform->addHelpButton('enabled', 'enabled', 'tool_cohortmanager');
        $mform->setDefault('enabled', 1);

        // Hidden text field for step JSON.
        $mform->addElement('hidden', 'conditionjson');
        $mform->setType('conditionjson', PARAM_RAW_TRIMMED);

        // A flag to indicate whether the conditions were updated or not.
        $mform->addElement('hidden', 'isconditionschanged');
        $mform->setType('isconditionschanged', PARAM_BOOL);
        $mform->setDefault('isstepschanged', 0);

        // Conditions table will be added here, in the "definition_after_data()" function (so that it can include
        // conditions from the submission in process, in case we fail validation).

        // Add processing step button.
        $mform->addElement('button', 'conditionmodalbutton', get_string('addcondition', 'tool_cohortmanager'));
        $this->add_action_buttons();
    }

    /**
     * Get a list of all cohorts in the system.
     * @return array
     */
    protected function get_cohort_options(): array {
        $options  = [];

        $cohorts = \cohort_get_all_cohorts(0, 0);
        foreach ($cohorts['cohorts'] as $cohort) {
            $options[$cohort->id] = $cohort->name;
        }

        return $options;
    }

}

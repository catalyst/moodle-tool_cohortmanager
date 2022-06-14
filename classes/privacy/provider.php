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

namespace tool_cohortmanager\privacy;

use \core_privacy\local\metadata\collection;
use \core_privacy\local\request\approved_contextlist;
use \core_privacy\local\request\approved_userlist;
use \core_privacy\local\request\contextlist;
use \core_privacy\local\request\userlist;
use \core_privacy\local\request\transform;
use \core_privacy\local\request\writer;

/**
 * Privacy Subsystem.
 *
 * @package    tool_cohortmanager
 * @author     Dmitrii Metelkin <dmitriim@catalyst-au.net>
 * @copyright  2022 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements \core_privacy\local\metadata\provider,
    \core_privacy\local\request\core_userlist_provider,
    \core_privacy\local\request\plugin\provider {


    /**
     * Return the fields which contain personal data.
     *
     * @param collection $collection a reference to the collection to use to store the metadata.
     * @return collection the updated collection of metadata items.
     */
    public static function get_metadata(collection $collection) : collection {
        $collection->add_database_table(
            'tool_cohortmanager',
            [
                'name' => 'privacy:metadata:tool_cohortmanager:name',
                'usermodified' => 'privacy:metadata:tool_cohortmanager:usermodified',

            ],
            'privacy:metadata:tool_cohortmanager'
        );

        $collection->add_database_table(
            'tool_cohortmanager_cond',
            [
                'ruleid' => 'privacy:metadata:tool_cohortmanager_cond:ruleid',
                'usermodified' => 'privacy:metadata:tool_cohortmanager_cond:usermodified',

            ],
            'privacy:metadata:tool_cohortmanager_cond'
        );

        $collection->add_database_table(
            'tool_cohortmanager_match',
            [
                'ruleid' => 'privacy:metadata:tool_cohortmanager_match:ruleid',
                'userid' => 'privacy:metadata:tool_cohortmanager_match:userid',
                'matchedtime' => 'privacy:metadata:tool_cohortmanager_match:matchedtime',
                'unmatchedtime' => 'privacy:metadata:tool_cohortmanager_match:unmatchedtime',
                'usermodified' => 'privacy:metadata:tool_cohortmanager_match:usermodified',
            ],
            'privacy:metadata:tool_cohortmanager_match'
        );

        return $collection;
    }

    /**
     * Get the list of contexts that contain user information for the specified user.
     *
     * @param int $userid the userid.
     * @return contextlist the list of contexts containing user info for the user.
     */
    public static function get_contexts_for_userid(int $userid) : contextlist {
        global $DB;
        $contextlist = new contextlist();

        if ($DB->record_exists('tool_cohortmanager', ['usermodified' => $userid])) {
            $contextlist->add_system_context();
        }

        if ($DB->record_exists('tool_cohortmanager_cond', ['usermodified' => $userid])) {
            $contextlist->add_system_context();
        }

        if ($DB->record_exists('tool_cohortmanager_match', ['userid' => $userid])) {
            $contextlist->add_system_context();
        }
        return $contextlist;
    }

    /**
     * Get the list of users who have data within a context.
     *
     * @param userlist $userlist The userlist containing the list of users who have data in this context/plugin combination.
     */
    public static function get_users_in_context(userlist $userlist) {
        $context = $userlist->get_context();

        if (!$context instanceof \context_system) {
            return;
        }

        $sql = "SELECT usermodified FROM {tool_cohortmanager}";
        $userlist->add_from_sql('usermodified', $sql, []);

        $sql = "SELECT usermodified FROM {tool_cohortmanager_cond}";
        $userlist->add_from_sql('usermodified', $sql, []);

        $sql = "SELECT userid FROM {tool_cohortmanager_match}";
        $userlist->add_from_sql('userid', $sql, []);
    }

    /**
     * Export personal data for the given approved_contextlist. User and context information is contained within the contextlist.
     *
     * @param approved_contextlist $contextlist a list of contexts approved for export.
     */
    public static function export_user_data(approved_contextlist $contextlist) {
        global $DB;

        $user = $contextlist->get_user();

        // Rules.
        $rules = [];
        $recordset = $DB->get_recordset('tool_cohortmanager', ['usermodified' => $user->id], '', 'name');

        foreach ($recordset as $record) {
            $rules[] = [
                'rulename' => format_string($record->name),
            ];
        }
        $recordset->close();

        if (count($rules) > 0) {
            $context = \context_system::instance();
            $contextpath = [get_string('pluginname', 'tool_cohortmanager')];

            writer::with_context($context)->export_data($contextpath, (object) ['rules' => $rules]);
        }

        // Conditions.
        $conditions = [];
        $sql = 'SELECT c.*, r.name as rulename
                  FROM {tool_cohortmanager_cond} c
                  JOIN {tool_cohortmanager} r ON (r.id = c.ruleid)
                 WHERE c.usermodified = :userid
              ORDER BY r.id ASC';

        $recordset = $DB->get_recordset_sql($sql, ['userid' => $user->id]);

        foreach ($recordset as $record) {
            $conditions[] = [
                'rulename' => format_string($record->rulename),
                'classname' => format_string($record->classname),
            ];
        }
        $recordset->close();

        if (count($conditions) > 0) {
            $context = \context_system::instance();
            $contextpath = [get_string('pluginname', 'tool_cohortmanager')];

            writer::with_context($context)->export_data($contextpath, (object) ['conditions' => $conditions]);
        }

        // User matches.
        $sql = 'SELECT m.*, r.name as rulename
                  FROM {tool_cohortmanager_match} m
                  JOIN {tool_cohortmanager} r ON (r.id = m.ruleid)
                 WHERE m.userid = :userid
              ORDER BY m.matchedtime, m.unmatchedtime, r.id ASC';

        $matches = [];

        $recordset = $DB->get_recordset_sql($sql, ['userid' => $user->id]);
        foreach ($recordset as $record) {
            $matches[] = [
                'rulename' => format_string($record->rulename),
                'matchedtime' => transform::datetime($record->matchedtime),
                'unmatchedtime' => $record->unmatchedtime ? transform::datetime($record->unmatchedtime) : null,
            ];
        }
        $recordset->close();

        if (count($matches) > 0) {
            $context = \context_system::instance();
            $contextpath = [get_string('pluginname', 'tool_cohortmanager')];

            writer::with_context($context)->export_data($contextpath, (object) ['matches' => $matches]);
        }
    }

    /**
     * Delete all data for all users in the specified context.
     *
     * @param \context $context the context to delete in.
     */
    public static function delete_data_for_all_users_in_context(\context $context) {
        global $DB;

        if (!$context instanceof \context_system) {
            return;
        }

        $DB->set_field('tool_cohortmanager', 'usermodified', 0);
        $DB->set_field('tool_cohortmanager_cond', 'usermodified', 0);
        $DB->delete_records('tool_cohortmanager_match');
    }

    /**
     * Delete all user data for the specified user, in the specified contexts.
     *
     * @param approved_contextlist $contextlist a list of contexts approved for deletion.
     */
    public static function delete_data_for_user(approved_contextlist $contextlist) {
        global $DB;

        if (empty($contextlist->count())) {
            return;
        }

        $userid = $contextlist->get_user()->id;
        foreach ($contextlist->get_contexts() as $context) {
            if (!$context instanceof \context_system) {
                continue;
            }

            $DB->set_field('tool_cohortmanager', 'usermodified', 0, ['usermodified' => $userid]);
            $DB->set_field('tool_cohortmanager_cond', 'usermodified', 0, ['usermodified' => $userid]);
            $DB->delete_records('tool_cohortmanager_match', ['userid' => $userid]);
        }
    }

    /**
     * Delete multiple users within a single context.
     *
     * @param approved_userlist $userlist The approved context and user information to delete information for.
     */
    public static function delete_data_for_users(approved_userlist $userlist) {
        global $DB;
        $context = $userlist->get_context();
        if (!$context instanceof \context_system) {
            return;
        }
        list($userinsql, $userinparams) = $DB->get_in_or_equal($userlist->get_userids(), SQL_PARAMS_NAMED);

        $DB->set_field_select('tool_cohortmanager', 'usermodified', 0, ' usermodified ' . $userinsql, $userinparams);
        $DB->set_field_select('tool_cohortmanager_cond', 'usermodified', 0, ' usermodified ' . $userinsql, $userinparams);
        $DB->delete_records_select('tool_cohortmanager_match', ' userid ' . $userinsql, $userinparams);
    }

}

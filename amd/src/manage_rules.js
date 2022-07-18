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
 * Manage rules JS module.
 *
 * @module     tool_cohortmanager/manage_rules
 * @copyright  2022 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import Ajax from 'core/ajax';
import Notification from 'core/notification';

/**
 * Init of the module.
 */
export const init = () => {
    Array.from(document.getElementsByClassName('cohort-manager-matching-users')).forEach((collection) => {
        const ruleid = collection.dataset.ruleid;
        const loader = collection.children[0];
        const link = collection.children[1];

        Ajax.call([{
            methodname: 'tool_cohortmanager_get_total_matching_users_for_rule',
            args: {ruleid: ruleid},
            done: function (number) {
                link.children[0].append(number.toLocaleString().replace(/,/g, " "));
                loader.classList.add('hidden');
                link.classList.remove('hidden');
            },
            fail: function (response) {
                Notification.exception(response);
            }
        }]);
    });
};

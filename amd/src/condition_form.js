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
 * Condition modal form.
 *
 * @module     tool_cohortmanager/condition_form
 * @copyright  2022 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import Ajax from 'core/ajax';
import Templates from 'core/templates';
import Fragment from 'core/fragment';
import ModalEvents from 'core/modal_events';
import ModalFactory from 'core/modal_factory';
import {get_string as getString} from 'core/str';

/**
 * A list of used selectors.
 */
const SELECTORS = {
    ADD_CONDITION_BUTTON: '#id_conditionmodalbutton',
    SELECT_CONDITION: '#id_condition',
    CONDITIONS_LIST: '#conditions',
    RULE_FROM_CONDITIONS_JSON: '#id_conditionjson',
    RULE_FROM_IS_CONDITIONS_CHANGED: '#id_isconditionschanged',
    CONDITIONS_NOT_SAVED_WARNING: '#tool-cohortmanager-not-saved',
    CONDITION_EDIT_ACTION: 'tool-cohortmanager-condition-edit',
    CONDITION_DELETE_ACTION: 'tool-cohortmanager-condition-delete',
};

/**
 * Init of the module.
 */
export const init = () => {
    const addButton = document.querySelector(SELECTORS.ADD_CONDITION_BUTTON);
    const conditionSelect = document.querySelector(SELECTORS.SELECT_CONDITION);

    addButton.addEventListener('click', (e) => {
        e.preventDefault();
        const className = conditionSelect.value;
        if (className !== '') {
            displayModalForm(className, '');
        }
    });
    applyConditionActions();
};

/**
 * Get modal form html body using fragment API.
 *
 * @param {string} className
 * @param {string} submittedData Submitted form data.
 * @param {any} defaults Default values for the form
 * @returns {Promise}
 */
const getModalFormBody = (className, submittedData, defaults) => {
    if (defaults === undefined) {
        defaults = '';
    }

    const params = {
        classname: className,
        jsonformdata: JSON.stringify(submittedData),
        defaults: JSON.stringify(defaults),
    };

    return Fragment.loadFragment('tool_cohortmanager', 'condition_form', 1, params);
};

/**
 * Display Modal form.
 *
 * @param {string} className
 * @param {any} defaults Default values for the form
 */
const displayModalForm = (className, defaults) => {

    if (defaults === undefined) {
        defaults = '';
    }

    ModalFactory.create({
        type: ModalFactory.types.SAVE_CANCEL,
        title: getString('conditionformtitle', 'tool_cohortmanager'),
        body: getModalFormBody(className, '', defaults),
        large: true,
    }).then(function (modal) {

        modal.getRoot().on(ModalEvents.save, function(e) {
            e.preventDefault();
            modal.getRoot().find('form').submit();
        });

        modal.getRoot().on(ModalEvents.hidden, function() {
            modal.destroy();
        });

        modal.getRoot().on('submit', 'form', function(e) {
            e.preventDefault();
            submitModalFormAjax(className, modal);
        });

        modal.show();
    });
};

/**
 * Submit modal form via ajax.
 *
 * @param {string} className Condition class name.
 * @param {object} modal Modal object.
 */
const submitModalFormAjax = (className, modal) => {
    const changeEvent = document.createEvent('HTMLEvents');
    changeEvent.initEvent('change', true, true);

    // Prompt all inputs to run their validation functions.
    // Normally this would happen when the form is submitted, but
    // since we aren't submitting the form normally we need to run client side
    // validation.
    modal.getRoot().find(':input').each(function(index, element) {
        element.dispatchEvent(changeEvent);
    });

    const invalid = modal.getRoot().find('[aria-invalid="true"]');

    // If we found invalid fields, focus on the first one and do not submit via ajax.
    if (invalid.length) {
        invalid.first().focus();
    } else {
        const submittedData = modal.getRoot().find('form').serialize();

        Ajax.call([{
            methodname: 'tool_cohortmanager_submit_condition_form',
            args: {classname: className, jsonformdata: JSON.stringify(submittedData)},
            done: function (response) {
                updateCondition(response);
                renderConditions(getConditions());
                modal.destroy();
            },
            fail: function () {
                modal.setBody(getModalFormBody(className, submittedData, ''));
            }
        }]);
    }
};

/**
 * Update condition with provided data.
 *
 * @param {object} data Updated condition data.
 */
const updateCondition = (data) => {
    let condition = {...data};

    let conditions = getConditions();

    if (condition.position >= 0) {
        conditions[condition.position] = condition;
    } else {
        conditions.push(condition);
        condition.position = conditions.length - 1;
    }

    saveConditionsToRuleForm(conditions);
};

/**
 * Get a list of all conditions.
 *
 * @returns {*[]}
 */
const getConditions = () => {
    let conditions = [];
    const conditionsjson = document.querySelector(SELECTORS.RULE_FROM_CONDITIONS_JSON).value;
    if (conditionsjson !== '') {
        conditions = JSON.parse(conditionsjson);
    }
    return conditions;

};

/**
 * Save a list of conditions to a rule form element.
 *
 * @param {array} conditions A list of conditions to save
 */
const saveConditionsToRuleForm = (conditions) => {
    document.querySelector(SELECTORS.RULE_FROM_CONDITIONS_JSON).setAttribute('value', JSON.stringify(conditions));
    document.querySelector(SELECTORS.RULE_FROM_IS_CONDITIONS_CHANGED).setAttribute('value', 1);
};

/**
 * Display a warning that conditions are not saved.
 */
const displayNotSavedWarning = () => {
    document.querySelector(SELECTORS.CONDITIONS_NOT_SAVED_WARNING).classList.remove('hidden');
};

/**
 * Render conditions.
 *
 * @param {array} conditions A list of conditions to render.
 */
const renderConditions = (conditions) => {
    Templates.render(
        'tool_cohortmanager/conditions',
        {'conditions' : conditions}
    ).then(function(html) {
        document.querySelector(SELECTORS.CONDITIONS_LIST).innerHTML = html;
        applyConditionActions();
        displayNotSavedWarning();
    }).fail(function() {
        Notification.exception({message: 'Error updating conditions'});
    });
};

/**
 * Apply actions to conditions.
 */
const applyConditionActions = () => {

    // Delete actions.
    const deleteActions = document.getElementsByClassName(SELECTORS.CONDITION_DELETE_ACTION);
    for (let i = 0; i < deleteActions.length; i++) {
        deleteActions[i].addEventListener('click', () => {
            // On a click to a delete icon, grab the position of the selected for deleting condition
            // and remove an element of that position from the list of all existing conditions.
            // Then save updated list of conditions to the rule form and render new list on a screen.
            let position = deleteActions[i].dataset.position;
            let conditions = getConditions()
                .filter(c => c.position !== position)
                .map((condition, index) => ({...condition, position: index}));
            saveConditionsToRuleForm(conditions);
            renderConditions(conditions);
        });
    }

    // Edit actions.
    const editActions = document.getElementsByClassName(SELECTORS.CONDITION_EDIT_ACTION);
    for (let i = 0; i < editActions.length; i++) {
        editActions[i].addEventListener('click', () => {
            // On a click to an edit icon for a selected condition, grab condition data from the list of
            // all conditions by its position and then render modal form using the condition class.
            let conditions = getConditions();
            let conditionPosition = editActions[i].dataset.position;
            let condition = conditions[conditionPosition];

            displayModalForm(condition.classname, condition);
        });
    }
};

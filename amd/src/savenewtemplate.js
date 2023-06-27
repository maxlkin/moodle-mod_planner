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
 * Creates the modal for the saving a new template.
 *
 * @author      Jay Churchward <jay@brickfieldlabs.ie>
 * @copyright   2021 Brickfield Education Labs <jay@brickfieldlabs.ie>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import ModalFactory from 'core/modal_factory';
import ModalSaveNewTemplate from './modal_save_new_template';
import PlannerEvents from './events';
import ModalEvents from 'core/modal_events';
import Ajax from "core/ajax";
import Notification from "core/notification";

/**
 * Register event listeners for the module.
 */
const registerEventListeners = () => {
    document.addEventListener('click', e => {
        const trigger = e.target.closest('#id_savenewtemplate');
        if (trigger) {
            e.preventDefault();

            show(trigger, { focusOnClose: e.target });
        }
    });
};

/**
 * Shows the save new template modal.
 *
 * @param {HTMLElement} focusOnClose The element to focus on when the modal is closed.
 */
const show = async ({ focusOnClose = null } = {}) => {
    const modal = await ModalFactory.create({ type: ModalSaveNewTemplate.TYPE });
    modal.show();

    modal.getRoot().on(ModalEvents.hidden, () => {
        // Destroy when hidden.
        modal.destroy();
        try {
            focusOnClose.focus();
        } catch (e) {
            // eslint-disable-line
        }
    });

    modal.getRoot().on(PlannerEvents.savenewtemplate, (e) => {
        e.preventDefault();
        // Get value from input field.
        const templateNameElement = document.getElementById('newTemplateName');
        const templateName = templateNameElement.value;
        const disclaimer = document.getElementById('id_disclaimereditable').innerHTML;
        const stepName = [];
        const stepAllocation = [];
        const stepDescription = [];
        const names = document.querySelectorAll('[selector="planner_stepname"]');
        const allocs = document.querySelectorAll('[selector="planner_stepallocation"]');
        const errorMessage = document.getElementById('id_error_message');
        for (let i = 0; i < names.length; i++) {
            stepName.push(names[i].value);
            stepAllocation.push(allocs[i].value);
            stepDescription.push(document.getElementById('id_stepdescription_' + i + 'editable').innerHTML);
        }
        Ajax.call([{
            methodname: 'mod_planner_save_new_template',
            args: {
                name: templateName,
                disclaimer: disclaimer,
                personal: isPersonal,
                stepname: stepName,
                stepallocation: stepAllocation,
                stepdescription: stepDescription,
                optionrepeats: names.length,
                courseid: courseId,
            },
            done: (data) => {
                if (data !== '') {
                    templateNameElement.classList.add('is-invalid');
                    errorMessage.innerText = data;
                    errorMessage.style.display = 'block';
                } else {
                    modal.destroy();
                    try {
                        focusOnClose.focus();
                    } catch (e) {
                        // eslint-disable-line
                    }
                }
            },
            fail: Notification.exception,
        }]);
    });
};

/**
 * Set up the save new template actions.
 * @param {string} personal Whether the template is personal or global.
 * @param {integer} course The course id.
 */
export const init = (personal, course) => {
    isPersonal = personal;
    courseId = course;
    if (!init.initialised) {
        // Event listeners should only be registered once.
        init.initialised = true;
        registerEventListeners();
    }
};

/**
 * Whether the template is global or personal.
 */
let isPersonal = '';

/**
 * The course id.
 */
let courseId = 0;

/**
 * Whether the init function was called before.
 *
 * @static
 * @type {boolean}
 */
init.initialised = false;
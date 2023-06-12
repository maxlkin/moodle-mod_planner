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
 * Creates the modals viewing the templates.
 *
 * @author      Jay Churchward <jay@brickfieldlabs.ie>
 * @copyright   2021 Brickfield Education Labs <jay@brickfieldlabs.ie>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import ModalFactory from 'core/modal_factory';
import ModalEvents from 'core/modal_events';
import Ajax from "core/ajax";
import Notification from "core/notification";
import Templates from 'core/templates';

/**
 * Register event listeners for the module.
 */
const registerEventListeners = () => {
    document.addEventListener('click', e => {
        const trigger = e.target.closest('[data-action="viewtemplate"]');
        if (trigger) {
            e.preventDefault();

            show(trigger, { focusOnClose: e.target });
        }
    });
};

/**
 * Shows the view template modal.
 *
 * @param {HTMLElement} trigger The element that triggered the modal.
 * @param {HTMLElement} focusOnClose The element to focus on when the modal is closed.
 */
const show = async (trigger, { focusOnClose = null } = {}) => {
    Ajax.call([{
        methodname: 'mod_planner_fetch_template_data',
        args: {
            templateid: trigger.dataset.templateid,
        },
        done: async (data) => {
            const modal = await ModalFactory.create({
                title: data.plannertemplate.name,
                body: Templates.render('mod_planner/modal_view_template', data),
                footer: 'This is a footer',
                large: true,
            });

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
        },
        fail: Notification.exception,
    }]);
};


/**
 * Set up the save new template actions.
 */
export const init = () => {
    if (!init.initialised) {
        // Event listeners should only be registered once.
        init.initialised = true;
        registerEventListeners();
    }
};

/**
 * Whether the init function was called before.
 *
 * @static
 * @type {boolean}
 */
init.initialised = false;
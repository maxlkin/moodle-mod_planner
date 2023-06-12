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
 * Creates the modals for the guide tips.
 *
 * @author      Jay Churchward <jay@brickfieldlabs.ie>
 * @copyright   2021 Brickfield Education Labs <jay@brickfieldlabs.ie>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import $ from 'jquery';
import Modal from 'core/modal';
import ModalRegistry from 'core/modal_registry';
import ModalEvents from 'core/modal_events';
import PlannerEvents from 'mod_planner/events';
import CustomEvents from 'core/custom_interaction_events';

let registered = false;
const SELECTORS = {
    SAVE_BUTTON: '[data-action="saveNewTemplate"]',
    CANCEL_BUTTON: '[data-action="cancel"]',
};

export default class ModalSaveNewTemplate extends Modal {

    /**
     * Constructor for the Modal.
     *
     * @param {object} root The root jQuery element for the modal
     */
    constructor(root) {
        super(root);
    }

    /**
     * Set up all of the event handling for the modal.
     *
     * @method registerEventListeners
     */
    registerEventListeners() {
        // Apply parent event listeners.
        super.registerEventListeners();

        this.getModal().on(CustomEvents.events.activate, SELECTORS.SAVE_BUTTON, (e, data) => {
            var saveEvent = $.Event(PlannerEvents.savenewtemplate);
            this.getRoot().trigger(saveEvent, this);

            if (!saveEvent.isDefaultPrevented()) {
                this.hide();
                data.originalEvent.preventDefault();
            }
        });

        this.getModal().on(CustomEvents.events.activate, SELECTORS.CANCEL_BUTTON, (e, data) => {
            var cancelEvent = $.Event(ModalEvents.cancel);
            this.getRoot().trigger(cancelEvent, this);

            if (!cancelEvent.isDefaultPrevented()) {
                this.hide();
                data.originalEvent.preventDefault();
            }
        });
    }
}
ModalSaveNewTemplate.TYPE = 'mod_planner-modal_save_new_template';

// Automatically register with the modal registry the first time this module is imported so that you can create modals
// of this type using the modal factory.
if (!registered) {
    ModalRegistry.register(ModalSaveNewTemplate.TYPE, ModalSaveNewTemplate, 'mod_planner/modal_save_new_template');
    registered = true;
}
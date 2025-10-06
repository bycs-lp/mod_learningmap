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

import Modal from 'core/modal';
import Ajax from 'core/ajax';
import * as manualcompletion from 'core_course/manual_completion_toggle';
import {renderLearningmap} from 'mod_learningmap/renderer';
import CourseEvents from 'core_course/events';
import $ from 'jquery';

/**
 * Helper for opening course modules in a modal that do not have a view page.
 *
 * @module     mod_learningmap/linkmodal
 * @copyright  2025 ISB Bayern
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Initialize the link modal listener for a learning map.
 *
 * @param {number} learningmapcmid - The course module ID of the learning map.
 * @param {boolean} inmodal - Whether the learning map is already in a modal.
 */
export const init = async(learningmapcmid, inmodal = false) => {
    const container = document.getElementById('learningmap-render-container-' + learningmapcmid + (inmodal ? '-modal' : ''));
    if (container) {
        container.addEventListener('click', async(event) => {
            const target = event.target.closest('a[data-cmid]');
            if (target && !target.hasAttribute('xlink:href')) {
                event.preventDefault();
                const cmid = target.getAttribute('data-cmid');
                if (cmid) {
                    const data = await Ajax.call([{
                        methodname: 'mod_learningmap_get_cm',
                        args: {
                            cmid: cmid
                        },
                    }])[0];
                    let js = $.parseHTML(data.js, null, true).map(node => node.innerHTML).join("\n");
                    const modal = await Modal.create({
                        title: data.name,
                        body: data.completion + data.html,
                        show: false,
                        removeOnClose: true,
                        large: true,
                    });
                    modal.bodyJS = js;
                    modal.show();
                    manualcompletion.init();
                    document.addEventListener(CourseEvents.manualCompletionToggled, () => {
                        renderLearningmap(learningmapcmid);
                    });
                }
            }
        });
    }
};

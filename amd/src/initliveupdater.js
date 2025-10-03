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
 * Main module for the massaction block.
 *
 * @module     mod_learningmap/initliveupdater
 * @copyright 2021-2024, ISB Bayern
 * @author     Philipp Memmel
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
import LiveUpdater from 'mod_learningmap/liveupdater';
import {selectors} from 'mod_learningmap/renderer';
import {getCurrentCourseEditor} from 'core_courseformat/courseeditor';
import Ajax from 'core/ajax';
import Log from 'core/log';
import Pending from 'core/pending';

/**
 * Renders the learningmap into the correct div.
 *
 * @param {number} cmId the course module id of the learningmap
 * @param {boolean} inmodal whether the learningmap is being rendered in a modal
 * @return {Component} the liveupdater component
 */
export const init = async(cmId, inmodal = false) => {
    const initliveupdaterPendingPromise = new Pending('mod_learningmap/initliveupdater');
    try {
        const data = await Ajax.call(
            [
                {
                    methodname: 'mod_learningmap_get_dependingmodules',
                    args: {
                        'cmId': cmId
                    }
                }
            ])[0];

        initliveupdaterPendingPromise.resolve();
        return new LiveUpdater({
            element: document.getElementById(selectors.LEARNINGMAP_RENDER_CONTAINER_PREFIX + cmId + (inmodal ? '-modal' : '')),
            reactive: getCurrentCourseEditor(),
            cmId: cmId,
            dependingModuleIds: data.dependingModuleIds
        });
    } catch (error) {
        Log.error(error);
        initliveupdaterPendingPromise.reject();
        return false;
    }
};

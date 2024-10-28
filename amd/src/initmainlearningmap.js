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
 * Load module for highlighting the main learningmap.
 *
 * @module     mod_learningmap/initmainlearningmap
 * @copyright  2024 ISB Bayern
 * @author     Stefan Hanauska <stefan.hanauska@csg-in.de>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
import MainLearningmap from 'mod_learningmap/mainlearningmap';
import {getCurrentCourseEditor} from 'core_courseformat/courseeditor';

export const init = (cmid, isfirstlearningmap = false) => {
    return new MainLearningmap({
        element: document.querySelector('.activity[data-id="' + cmid + '"]'),
        reactive: getCurrentCourseEditor(),
        cmid: cmid,
        isfirstlearningmap: isfirstlearningmap,
    });
};

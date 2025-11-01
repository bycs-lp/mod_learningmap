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
 * Highlight main learningmap for format_learningmap.
 *
 * @module     mod_learningmap/mainlearningmap
 * @copyright  2024 ISB Bayern
 * @author     Stefan Hanauska <stefan.hanauska@csg-in.de>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
import {BaseComponent} from 'core/reactive';
import {refreshModule} from 'core_course/actions';
import {render as renderTemplate} from 'core/templates';

/**
 * The live updater component.
 */
export default class extends BaseComponent {
    create(descriptor) {
        this.element = descriptor.element;
        this.reactive = descriptor.reactive;
        this.cmid = descriptor.cmid;
        this.isfirstlearningmap = descriptor.isfirstlearningmap;
    }

    getWatchers() {
        const watchers = [
            {watch: `cm:deleted`, handler: this._updateLearningmap},
            {watch: `section:updated`, handler: this._updateLearningmap},
            {watch: `cm:added`, handler: this._updateLearningmap},
            {watch: `cm[${this.cmid}]:deleted`, handler: this.destroy},
        ];
        return watchers;
    }

    async destroy() {
        if (this._getFirstLearningmap() === undefined) {
            await renderTemplate('format_learningmap/notification', {}).then((html) => {
                document.querySelector('.format_learningmap-notification').innerHTML = html;
                return true;
            });
        }
    }

    async _updateLearningmap() {
        if (this._isFirstLearningmap() && !this.isfirstlearningmap) {
            this.isfirstlearningmap = true;
            this.getElement().classList.add('format_learningmap-firstlearningmap');
            refreshModule(this.element, this.cmid);
            document.querySelector('.format_learningmap-notification').innerHTML = '';
        } else {
            if (this.isfirstlearningmap) {
                this.isfirstlearningmap = false;
                this.getElement().classList.remove('format_learningmap-firstlearningmap');
                refreshModule(this.element, this.cmid);
            }
        }
    }

    _isFirstLearningmap() {
        let firstLearningmap = this._getFirstLearningmap();
        return firstLearningmap == this.cmid;
    }

    _getFirstLearningmap() {
        let state = this.reactive.stateManager.state;
        let cmlist = this._getCmlist();
        return cmlist.find((cmid) => {
            let cm = state.cm.get(cmid);
            return (cm.module == 'learningmap');
        });
    }

    _getCmlist() {
        let state = this.reactive.stateManager.state;
        let cmlist = [];
        state.course.sectionlist.forEach((sectionid) => {
            let section = state.section.get(sectionid);
            section.cmlist.forEach((cmid) => {
                cmlist.push(cmid);
            });
        });
        return cmlist;
    }
}

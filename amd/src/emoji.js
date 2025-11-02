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
 * TODO describe module emoji
 *
 * @module     mod_learningmap/emoji
 * @copyright  2024 ISB Bayern
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Returns an text tag containing an emoji with the given dimensions.
 * @param {*} mapsvg
 * @param {*} x
 * @param {*} y
 * @param {*} r
 * @param {*} classes
 * @param {*} id
 * @param {*} content
 * @returns
 */
export default function emoji(mapsvg, x, y, r, classes, id, content) {
    let newelement = mapsvg.nested();
    newelement.center(x, y).attr({'class': classes}).id(id).width(r * 2 + 10).height(r * 2 + 10);
    newelement.circle(r * 2, r, r);
    newelement.text().plain(content ?? '😀');
    return newelement;
}
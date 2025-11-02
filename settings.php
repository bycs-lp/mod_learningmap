<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Admin settings for mod_learningmap
 *
 * @package     mod_learningmap
 * @copyright 2021-2024, ISB Bayern
 * @author      Stefan Hanauska <stefan.hanauska@csg-in.de>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($ADMIN->fulltree) {
    $settings->add(new admin_setting_configtext(
        'mod_learningmap/usecaselink',
        get_string('usecaselink', 'learningmap'),
        '',
        'https://mebis.bycs.de/beitrag/lernlandkarten',
        PARAM_URL
    ));
    $settings->add(new admin_setting_configtext(
        'mod_learningmap/allowedfilters',
        get_string('allowedfilters', 'learningmap'),
        get_string('allowedfilters_desc', 'learningmap'),
        ''
    ));
    $settings->add(new admin_setting_configcheckbox(
        'mod_learningmap/backlinkallowed',
        get_string('backlinkallowed', 'learningmap'),
        get_string('backlinkallowed_desc', 'learningmap'),
        1
    ));
    $settings->add(new admin_setting_configtext(
        'mod_learningmap/colorpalette',
        get_string('colorpalette', 'learningmap'),
        get_string('colorpalette_desc', 'learningmap'),
        '#000000 #ffffff #808080 #996e36 #f55525 #ffe438 #88dd20 #22e0cd #269aff #bb1cd4 #ffffff00'
    ));
}

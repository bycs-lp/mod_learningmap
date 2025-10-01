<?php
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
 * mod_learningmap service definition.
 *
 * @package    mod_learningmap
 * @copyright 2021-2024, ISB Bayern
 * @author     Philipp Memmel
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

$functions = [
    'mod_learningmap_get_learningmap' => [
        'classname'   => 'mod_learningmap\external\get_learningmap',
        'methodname'  => 'execute',
        'description' => 'Retrieves the learningmap',
        'type'        => 'read',
        'ajax' => true,
        'capabilities' => 'mod/learningmap:view',
    ],
    'mod_learningmap_get_dependingmodules' => [
        'classname'   => 'mod_learningmap\external\get_dependingmodules',
        'methodname'  => 'execute',
        'description' => 'Retrieves the course modules a given learningmap depends on',
        'type'        => 'read',
        'ajax' => true,
        'capabilities' => 'mod/learningmap:view',
    ],
    'mod_learningmap_get_cm' => [
        'classname'   => 'mod_learningmap\external\get_cm',
        'methodname'  => 'execute',
        'description' => 'Retrieves the course module data',
        'type'        => 'read',
        'ajax' => true,
        'capabilities' => 'mod/learningmap:view',
    ],
];

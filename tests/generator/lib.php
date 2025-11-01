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
 * mod_learningmap data generator
 *
 * @package     mod_learningmap
 * @copyright   2021-2024, ISB Bayern
 * @author      Stefan Hanauska <stefan.hanauska@csg-in.de>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_learningmap_generator extends testing_module_generator {
    /**
     * Creates an instance of a learningmap. As unit tests do not support JS,
     * the SVG test data is static.
     *
     * @param array $record
     * @param array|null $options
     * @return stdClass learningmap instance
     */
    public function create_instance($record = null, array|null $options = null): stdClass {
        global $CFG;

        $record = (array)$record + [
            'name' => 'test map',
            'intro' => 'test intro',
            'introformat' => 0,
            'svgcode' => file_get_contents($CFG->dirroot . '/mod/learningmap/tests/generator/test.svg'),
            'showmaponcoursepage' => 1,
            'placestore' => file_get_contents($CFG->dirroot . '/mod/learningmap/tests/generator/test.json'),
            'completiontype' => 2,
        ];

        return parent::create_instance($record, (array)$options);
    }
}

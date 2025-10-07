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
 * Backup steps for mod_learningmap
 *
 * @package     mod_learningmap
 * @copyright 2021-2024, ISB Bayern
 * @author      Stefan Hanauska <stefan.hanauska@csg-in.de>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class backup_learningmap_activity_structure_step extends backup_activity_structure_step {
    /**
     * Defines the XML structure for learningmap backups
     *
     * @return backup_nested_element
     */
    protected function define_structure(): backup_nested_element {
        $learningmap = new backup_nested_element(
            'learningmap',
            ['id'],
            [
                'course',
                'name',
                'intro',
                'introformat',
                'timemodified',
                'placestore',
                'completiontype',
                'backlink',
                'svgcode',
                'showmaponcoursepage',
                'usemodal',
            ]
        );

        $learningmap->set_source_table('learningmap', ['id' => backup::VAR_ACTIVITYID]);

        $learningmap->annotate_files('mod_learningmap', 'intro', null);
        $learningmap->annotate_files('mod_learningmap', 'background', null);

        return $this->prepare_activity_structure($learningmap);
    }
}

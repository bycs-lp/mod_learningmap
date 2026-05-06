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
 * Restore steps for mod_learningmap
 *
 * @package     mod_learningmap
 * @copyright 2021-2024, ISB Bayern
 * @author      Stefan Hanauska <stefan.hanauska@csg-in.de>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class restore_learningmap_activity_structure_step extends restore_activity_structure_step {
    /**
     * List of elements that can be restored
     * @return array
     * @throws base_step_exception
     */
    protected function define_structure(): array {
        $paths = [];
        $paths[] = new restore_path_element('learningmap', '/activity/learningmap');
        return $this->prepare_activity_structure($paths);
    }

    /**
     * Restore a learningmap record. This will not change placestore and
     * svgcode as they will be processed in the after_restore method.
     * @param array|object $data
     * @throws base_step_exception
     * @throws dml_exception
     * @throws restore_step_exception
     */
    protected function process_learningmap($data): void {
        global $DB;

        $data = (object) $data;
        $oldid = $data->id;
        $data->course = $this->get_courseid();

        $newid = $DB->insert_record('learningmap', $data);
        $this->set_mapping('learningmap', $oldid, $newid);
        $this->apply_activity_instance($newid);
    }

    /**
     * Extra actions to take once restore is complete.
     */
    protected function after_execute(): void {
        $this->add_related_files('mod_learningmap', 'intro', null);
        $this->add_related_files('mod_learningmap', 'background', null);
    }
}

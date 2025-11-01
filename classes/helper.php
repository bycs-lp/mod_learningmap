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

namespace mod_learningmap;

/**
 * Class helper
 *
 * @package    mod_learningmap
 * @copyright  2024 ISB Bayern
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class helper {
    /**
     * Returns whether the map should be shown on the course page. If course format is format_learningmap
     * the setting in the database is ignored.
     *
     * @param cm_info $cm
     * @return bool
     */
    public static function show_map_on_course_page($cm): bool {
        global $DB;
        $showmaponcoursepage = $DB->get_field('learningmap', 'showmaponcoursepage', ['id' => $cm->instance]);
        [$course, ] = get_course_and_cm_from_cmid($cm->id);
        $courseformat = $course->format;
        return !empty($showmaponcoursepage) && $courseformat != 'learningmap';
    }
}

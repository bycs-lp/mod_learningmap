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

namespace mod_learningmap\output\courseformat;

/**
 * Class activitybadge
 *
 * @package    mod_learningmap
 * @copyright  2024 ISB Bayern
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class activitybadge extends \core_courseformat\output\activitybadge {
    /**
     * Updates the content of the activity badge.
     */
    protected function update_content(): void {
        $course = $this->cminfo->get_course();
        if ($course->format == 'learningmap') {
            $courseformat = course_get_format($course);
            if ($courseformat->main_learningmap_exists()) {
                $mainlearningmap = $courseformat->get_main_learningmap();
                if ($this->cminfo->id == $mainlearningmap->id) {
                    $this->content = get_string('mainlearningmap', 'format_learningmap');
                    $this->style = 'badge-primary';
                }
            }
        }
    }
}

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
     * Returns whether the map should be shown on the course page.
     *
     * If course format format_learningmap is being used the module setting will be ignored.
     *
     * @param cm_info $cm the coursemodule info object
     * @return bool the "showoncoursemap" setting of the coursemodule, or false if current course format is format_learningmap
     */
    public static function show_map_on_course_page($cm): bool {
        global $DB;
        $showmaponcoursepage = $DB->get_field('learningmap', 'showmaponcoursepage', ['id' => $cm->instance]);
        return !empty($showmaponcoursepage) && !self::is_learningmap_format($cm);
    }

    /**
     * Checks if the course format of the course the given cm belongs to is 'learningmap'.
     *
     * @param cm_info $cm The course module info object.
     * @return bool True if the course format is 'learningmap', false otherwise.
     */
    public static function is_learningmap_format($cm): bool {
        [$course, ] = get_course_and_cm_from_cmid($cm->id);
        $courseformat = $course->format;
        return $courseformat === 'learningmap';
    }

    /**
     * Repairs a learning map record by checking if the course exists and updating the record accordingly.
     *
     * @param int $learningmapid The ID of the learning map record to repair.
     * @return void
     */
    public static function repair_learningmap_record(int $learningmapid): void {
        global $DB;

        // Check if the learningmap record exists.
        if (!$DB->record_exists('learningmap', ['id' => $learningmapid])) {
            return;
        }

        // Attempt to repair the learning map record.
        $record = $DB->get_record('learningmap', ['id' => $learningmapid], '*', MUST_EXIST);

        if (!$DB->record_exists('course', ['id' => $record->course])) {
            // If the course does not exist, try to find the course from the course module.
            if (!PHPUNIT_TEST) {
                mtrace("Course with id {$record->course} does not exist, trying to find it from course module.");
            }
            $moduleid = $DB->get_field('modules', 'id', ['name' => 'learningmap']);
            if ($moduleid) {
                $courseid = $DB->get_field('course_modules', 'course', ['module' => $moduleid, 'instance' => $record->id]);
                if ($courseid) {
                    if ($DB->record_exists('course', ['id' => $courseid])) {
                        if (!PHPUNIT_TEST) {
                            mtrace("Updating learning map record to course id {$courseid}.");
                        }
                        $record->course = $courseid;
                        $record->timemodified = time();
                        $DB->update_record('learningmap', $record);
                    } else {
                        if (!PHPUNIT_TEST) {
                            mtrace(
                                "Course with id {$courseid} does not exist, learning " .
                                "map {$record->id} is an orphaned course module."
                            );
                        }
                    }
                } else {
                    if (!PHPUNIT_TEST) {
                        mtrace("No course module found, learning map with id {$record->id} is an orphaned instance.");
                    }
                }
            }
        }
    }

    /**
     * Determines if the current request is an AJAX request for getting a course module.
     *
     * @return bool True if the request is an AJAX request for getting a course module, false otherwise.
     */
    public static function is_ajax_request(): bool {
        global $_REQUEST;
        return
            !empty($_REQUEST['info']) &&
            in_array($_REQUEST['info'], ['core_course_get_module', 'mod_learningmap_get_cm']);
    }

    /**
     * Determines if the current request is for the get_cm web service function.
     *
     * @return bool True if the request is for the get_cm web service function, false otherwise.
     */
    public static function is_get_cm_request(): bool {
        return !empty($_REQUEST['info']) && $_REQUEST['info'] === 'mod_learningmap_get_cm';
    }
}

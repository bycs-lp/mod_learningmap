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
     * Get the current default activity header description without rendering the full header.
     *
     * Calling activity_header::export_for_template() here would add completion actions as a side effect.
     *
     * @return string
     */
    public static function get_activity_header_description(): string {
        global $PAGE;

        $layoutoptions = $PAGE->layout_options['activityheader'] ?? [];
        if (
            empty($layoutoptions['nodescription'])
            && !empty($PAGE->activityrecord->intro)
            && trim($PAGE->activityrecord->intro)
        ) {
            return format_module_intro($PAGE->activityname, $PAGE->activityrecord, $PAGE->cm->id);
        }

        return '';
    }

    /**
     * Render the activity header for learningmap modals.
     *
     * Since Moodle 5.2 the manual completion UI is rendered via the activity header.
     * With linear navigation (introduced in Moodle 5.3), the interactive toggle may be
     * moved to the sticky footer. Modals have no sticky footer, so we inject a fallback
     * toggle into the header actions when needed.
     *
     * @param array $activityheaderdata Exported activity header template data.
     * @return string
     */
    public static function render_activity_header_for_modal(array $activityheaderdata): string {
        global $PAGE, $OUTPUT;

        $headerhtml = $OUTPUT->render_from_template('core/activity_header', $activityheaderdata);

        $headeractions = $PAGE->get_header_actions();
        if (self::should_add_manual_completion_fallback($activityheaderdata, $headeractions)) {
            $headeractions[] = $OUTPUT->render_from_template('core_course/completion_manual', $activityheaderdata);
        }

        if (!empty($headeractions)) {
            $headerhtml .= $OUTPUT->render_from_template('mod_learningmap/modal_header_actions', [
                'headeractions' => $headeractions,
            ]);
        }

        return $headerhtml;
    }

    /**
     * Check whether a manual completion fallback button should be rendered.
     *
     * @param array $activityheaderdata Exported activity header template data.
     * @param array $headeractions Collected page header actions.
     * @return bool
     */
    private static function should_add_manual_completion_fallback(array $activityheaderdata, array $headeractions): bool {
        if (empty($activityheaderdata['showmanualcompletion']) || empty($activityheaderdata['istrackeduser'])) {
            return false;
        }

        foreach ($headeractions as $headeraction) {
            if (str_contains($headeraction, 'data-action="toggle-manual-completion"')) {
                return false;
            }
        }

        return true;
    }

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

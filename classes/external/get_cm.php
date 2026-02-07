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

namespace mod_learningmap\external;

use core_external\external_api;
use core_external\external_value;
use core_external\external_single_structure;
use core_external\external_function_parameters;
use core_courseformat\output\local\content\cm\completion;
/**
 * Class get_cm
 *
 * @package    mod_learningmap
 * @copyright  2025 ISB Bayern
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class get_cm extends external_api {
    /**
     * Returns description of method parameters
     *
     * @return \external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters(
            [
                'cmid' => new external_value(PARAM_INT, 'Course module id'),
            ]
        );
    }

    /**
     * Returns course module data
     *
     * @param int $cmid Course module id
     * @return array Course module data
     * @throws \moodle_exception
     */
    public static function execute(int $cmid): array {
        global $PAGE, $OUTPUT;

        define('LEARNINGMAP_NO_BACKLINK', true);

        [$course, $cm] = get_course_and_cm_from_cmid($cmid);
        $context = \context_module::instance($cm->id);
        self::validate_context($context);

        require_capability('mod/' . $cm->modname . ':view', $context);

        $modinfo = get_fast_modinfo($course);

        if (!$cm->available || !$cm->is_stealth() && $cm->visible == 0) {
            require_capability('moodle/course:viewhiddenactivities', $context);
        }

        $PAGE->set_url($cm->url ?? new \moodle_url('/mod/' . $cm->modname . '/view.php', ['id' => $cm->id]));
        $PAGE->set_context($context);
        $PAGE->set_cm($cm, $cm->course);
        $PAGE->set_pagelayout('embedded');

        $completioninfo = new \completion_info($course);
        $completioninfo->set_module_viewed($cm);

        $data = [
            'id' => $cm->id,
            'course' => $cm->course,
            'module' => $cm->module,
            'instance' => $cm->instance,
            'section' => $cm->sectionnum,
            'visible' => $cm->visible,
            'groupmode' => groups_get_activity_groupmode($cm, $course),
            'groupingid' => $cm->groupingid,
            'modname' => $cm->modname,
        ];

        // Remove description for labels, because it is already in html.
        if ($cm->modname === 'label') {
            $PAGE->activityheader->set_description('');
        }

        $data['completion'] = $OUTPUT->render_from_template(
            'core/activity_header',
            $PAGE->activityheader->export_for_template($OUTPUT)
        );

        $data['name'] = format_string($cm->name, true, ['context' => $context]);

        $PAGE->start_collecting_javascript_requirements();
        $data['html'] = $modinfo->get_cm($cmid)->get_formatted_content(['overflowdiv' => true, 'noclean' => true]);
        $data['js'] = $PAGE->requires->get_end_code();

        return $data;
    }

    /**
     * Returns description of method result value
     *
     * @return \external_description
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure(
            [
                'id' => new external_value(PARAM_INT, 'Course module id'),
                'course' => new external_value(PARAM_INT, 'Course id'),
                'module' => new external_value(PARAM_INT, 'Module id'),
                'instance' => new external_value(PARAM_INT, 'Instance id'),
                'section' => new external_value(PARAM_INT, 'Section id'),
                'name' => new external_value(PARAM_TEXT, 'Course module name'),
                'visible' => new external_value(PARAM_INT, 'Visible'),
                'groupmode' => new external_value(PARAM_INT, 'Group mode'),
                'groupingid' => new external_value(PARAM_INT, 'Grouping id'),
                'html' => new external_value(PARAM_RAW, 'Course module html'),
                'js' => new external_value(PARAM_RAW, 'Course module javascript'),
                'completion' => new external_value(PARAM_RAW, 'Completion html'),
                'modname' => new external_value(PARAM_TEXT, 'Module name'),
            ]
        );
    }
}

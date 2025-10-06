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
 * External function to retrieve the learningmap HTML code via ajax.
 *
 * @package    mod_learningmap
 * @copyright 2021-2024, ISB Bayern
 * @author     Philipp Memmel
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_learningmap\external;

// Next 2 lines can be removed as soon as we inherit from \core_external\external_api instead of old \external_api.
defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot . '/lib/externallib.php');

use coding_exception;
use context_module;
use external_api;
use external_function_parameters;
use external_single_structure;
use external_value;
use invalid_parameter_exception;
use moodle_exception;
use required_capability_exception;
use restricted_context_exception;

/**
 * Class for external function to retrieve a given learningmap HTML code.
 *
 * @copyright 2021-2024, ISB Bayern
 * @author     Philipp Memmel
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class get_learningmap extends external_api {
    /**
     * Returns description of method parameters for the get_learningmap webservice function.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'cmId' => new external_value(PARAM_INT, 'Course module ID of the learningmap'),
        ]);
    }

    /**
     * Definition of return values of the get_learningmap webservice function.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return
            new external_single_structure(
                [
                    'content' => new external_value(PARAM_RAW, 'Rendered learningmap'),
                    'completion' => new external_value(PARAM_RAW, 'Activity header HTML code'),
                ]
            );
    }

    /**
     * The actual method returning the rendered learningmap HTML code via webservice.
     *
     * @param int $cmid the course module id of the learningmap which HTML code should be retrieved
     * @return array The raw HTML in an array of the form ['content' => 'learningmap HTML code' ]
     * @throws coding_exception
     * @throws invalid_parameter_exception
     * @throws required_capability_exception
     * @throws restricted_context_exception
     * @throws moodle_exception
     */
    public static function execute(int $cmid): array {
        global $PAGE, $OUTPUT;
        $params = self::validate_parameters(self::execute_parameters(), ['cmId' => $cmid]);
        $cmid = $params['cmId'];
        [$course, $cminfo] = get_course_and_cm_from_cmid($cmid);
        $context = context_module::instance($cmid);
        self::validate_context($context);
        require_capability('mod/learningmap:view', $context);
        $completion = new \completion_info($course);
        $completion->set_module_viewed($cminfo);

        $PAGE->set_context($context);
        $PAGE->set_cm($cminfo);
        $PAGE->set_pagelayout('embedded');

        $completion = $OUTPUT->render_from_template(
            'core/activity_header',
            $PAGE->activityheader->export_for_template($OUTPUT)
        );

        return [
            'content' => learningmap_get_learningmap($cminfo),
            'completion' => $completion,
        ];
    }
}

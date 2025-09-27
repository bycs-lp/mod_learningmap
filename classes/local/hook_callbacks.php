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
 * Hook callbacks for mod_learningmap.
 *
 * @package     mod_learningmap
 * @copyright   2024, ISB Bayern
 * @author      Philipp Memmel
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_learningmap\local;

use cache;
use core\hook\output\before_http_headers;
use Exception;
use mod_learningmap\cachemanager;

/**
 * Hook callbacks for mod_learningmap.
 *
 * @package     mod_learningmap
 * @copyright   2024, ISB Bayern
 * @author      Philipp Memmel
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class hook_callbacks {
    /**
     * Hook listener for before_http_headers hook.
     *
     * This function takes care of injecting the backlinks into an activity page header, if the activity belongs to a learningmap
     * and the learningmap has the backlink feature enabled.
     *
     * @param before_http_headers $beforehttpheadershook the hook object
     */
    public static function inject_backlinks_into_activity_header(before_http_headers $beforehttpheadershook): void {
        global $OUTPUT, $PAGE;

        if (defined('LEARNINGMAP_NO_BACKLINK')) {
            return;
        }

        // Don't run during initial install.
        if (during_initial_install()) {
            return;
        }

        if (get_config('mod_learningmap', 'backlinkallowed') == 0) {
            return;
        }

        if ($PAGE->context->contextlevel != CONTEXT_MODULE || $PAGE->pagelayout == 'admin') {
            return;
        }

        try {
            $cache = cache::make('mod_learningmap', 'backlinks');

            $cachekey = $PAGE->cm->id;
            $backlinks = $cache->get($cachekey);

            if (!$backlinks) {
                // If the cache is not yet filled, fill it for the current course. This is a fallback in
                // case the task has not been executed yet or was not fast enough. Should only happen after
                // cache purging.
                if (!$cache->get('fillstate')) {
                    cachemanager::build_backlink_cache($PAGE->course->id);
                }
                // Try again to get the backlinks.
                $backlinks = $cache->get($cachekey);
            }

            $backlinktext = '';

            if (empty($backlinks)) {
                return;
            }

            $modinfo = get_fast_modinfo($PAGE->course);
            foreach ($backlinks as $backlink) {
                $cminfo = $modinfo->get_cm($backlink['cmid']);
                if ($cminfo->available != 0 && $cminfo->uservisible) {
                    $backlinktext .= $beforehttpheadershook->renderer->render_from_template('learningmap/backtomap', $backlink);
                }
            }

            if ($backlinktext) {
                $activityheader = $PAGE->activityheader->export_for_template($OUTPUT);
                $PAGE->activityheader->set_description(($activityheader['description'] ?? '') . $backlinktext);
            }
        } catch (Exception $e) {
            debugging($e->getMessage());
        }
    }
}

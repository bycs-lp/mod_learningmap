<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

namespace mod_learningmap;

/**
 * Autoupdate class for mod_learningmap
 *
 * @package     mod_learningmap
 * @copyright 2021-2024, ISB Bayern
 * @author      Stefan Hanauska <stefan.hanauska@csg-in.de>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class autoupdate {
    /**
     * Called when a course_module_completion_updated event is triggered. Updates the completion state for all
     * learningmaps in the course of the activity.
     *
     * @param \core\event\base $event
     * @return void
     */
    public static function update_from_event(\core\event\base $event): void {
        $data = $event->get_data();
        if (isset($data['courseid']) && $data['courseid'] > 0) {
            $modinfo = get_fast_modinfo($data['courseid']);
            $instances = $modinfo->get_instances_of('learningmap');
            if (count($instances) > 0) {
                $completion = new \completion_info($modinfo->get_course());
                foreach ($instances as $i) {
                    if (
                        $i->completion == COMPLETION_TRACKING_AUTOMATIC &&
                        $i->instance != $data['contextinstanceid']
                    ) {
                        if ($i->groupmode > 0) {
                            $group = groups_get_activity_group($i);
                        }
                        if (!empty($group)) {
                            $members = groups_get_members($group);
                        }
                        if (empty($members)) {
                            $user = new \stdClass();
                            $user->id = $data['userid'];
                            $members = [$user];
                        }
                        foreach ($members as $member) {
                            $completion->update_state($i, COMPLETION_UNKNOWN, $member->id);
                        }
                    }
                }
            }
        }
    }

    /**
     * Called when a course_module_deleted event is triggered. Removes the deleted course module from all
     * learningmaps in the course of the activity.
     *
     * @param \core\event\base $event
     * @return void
     */
    public static function update_from_delete_event(\core\event\base $event): void {
        global $DB;

        $data = $event->get_data();
        if (isset($data['courseid']) && $data['courseid'] > 0) {
            $modinfo = get_fast_modinfo($data['courseid']);
            $instances = $modinfo->get_instances_of('learningmap');
            if (count($instances) > 0) {
                foreach ($instances as $i) {
                    if ($map = $DB->get_record('learningmap', ['id' => $i->instance], 'placestore')) {
                        $changed = false;
                        $placestore = json_decode($map->placestore);
                        foreach ($placestore->places as $p) {
                            if ($p->linkedActivity == $data['objectid']) {
                                $p->linkedActivity = null;
                                cachemanager::remove_cmid($data['objectid']);
                                $changed = true;
                            }
                        }
                        if ($changed) {
                            $DB->set_field('learningmap', 'placestore', json_encode($placestore), ['id' => $i->instance]);
                        }
                    }
                }
            }
        }

        // If the course module was a learningmap, reset the backlink cache of the whole course.
        self::reset_backlink_cache_if_necessary($event);
    }

    /**
     * Resets backlink cache of the whole course if a learningmap was created / updated / deleted or if
     * the course settings have changed (as course format may have changed).
     * @param \core\event\base $event
     * @return bool
     */
    public static function reset_backlink_cache_if_necessary(\core\event\base $event): bool {
        $data = $event->get_data();
        if (isset($data['courseid']) && $data['courseid'] > 0) {
            if (
                (
                    $data['objecttable'] == 'course' &&
                    is_array($data['other']['updatedfields']) &&
                    in_array('format', $data['other']['updatedfields'])
                ) ||
                ($data['objecttable'] == 'course_modules' && $data['other']['modulename'] == 'learningmap')
            ) {
                cachemanager::reset_backlink_cache($data['courseid']);
                return true;
            }
        }
        return false;
    }
}

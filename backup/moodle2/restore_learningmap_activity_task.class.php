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

use mod_learningmap\migrationhelper;

defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once($CFG->dirroot . '/mod/learningmap/backup/moodle2/restore_learningmap_stepslib.php');

/**
 * Restore class for mod_learningmap
 *
 * @package     mod_learningmap
 * @copyright 2021-2024, ISB Bayern
 * @author      Stefan Hanauska <stefan.hanauska@csg-in.de>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class restore_learningmap_activity_task extends restore_activity_task {
    /**
     * No specific settings for this activity
     *
     * @return void
     */
    protected function define_my_settings(): void {
    }

    /**
     * Defines the restore step for learningmap
     *
     * @return void
     */
    protected function define_my_steps(): void {
        $this->add_step(new restore_learningmap_activity_structure_step('learningmap_structure', 'learningmap.xml'));
    }

    /**
     * Calls decode functions of other plugins for the intro field. This is not necessary for
     * mod_learningmap itself but it makes small hacks in SVG possible and may be convenient for
     * future use.
     *
     * @return array
     */
    public static function define_decode_contents(): array {
        $contents = [];
        $contents[] = new restore_decode_content('learningmap', ['intro', 'svgcode'], 'learningmap');
        return $contents;
    }

    /**
     * Defines rules for decoding links to view.php in restore step
     *
     * @return array
     */
    public static function define_decode_rules(): array {
        $rules = [];
        $rules[] = new restore_decode_rule('LEARNINGMAPVIEWBYID', '/mod/learningmap/view.php?id=$1', 'course_module');
        return $rules;
    }

    /**
     * Update placestore to new module ids after restore is complete
     * @throws dml_exception
     */
    public function after_restore(): void {
        global $DB;
        $courseid = $this->get_courseid();
        $modinfo = get_fast_modinfo($courseid);

        $item = $DB->get_record('learningmap', ['id' => $this->get_activityid()], '*', MUST_EXIST);
        $cm = get_coursemodule_from_instance('learningmap', $item->id, $courseid);

        $placestore = json_decode($item->placestore);

        foreach ($placestore->places as $place) {
            if ($place->linkedActivity) {
                $moduleid = restore_dbops::get_backup_ids_record($this->get_restoreid(), 'course_module', $place->linkedActivity);
                if ($moduleid) {
                    $place->linkedActivity = $moduleid->newitemid;
                } else {
                    try {
                        if (!($place->linkedActivity === null)) {
                            $modinfo->get_cm($place->linkedActivity);
                        }
                    } catch (Exception $e) {
                        $place->linkedActivity = null;
                    }
                }
            }
        }
        $oldmapid = $placestore->mapid;
        $newmapid = uniqid();
        $placestore->mapid = $newmapid;

        if (!isset($placestore->version) || $placestore->version < 2026022200) {
            $placestore->version = 2026022200;
            // Needs 1 as default value (otherwise all place strokes would be hidden).
            if (!isset($placestore->strokeopacity)) {
                $placestore->strokeopacity = 1;
            }
            if (empty($item->svgcode)) {
                $mapcode = $item->intro;
                $item->intro = '';
                $item->showmaponcoursepage = $cm->showdescription;
                migrationhelper::move_files_to_background_filearea($item->id);
            } else {
                $mapcode = $item->svgcode;
            }
            $mapworker = new \mod_learningmap\mapworker($mapcode, (array)$placestore);
            $mapworker->replace_stylesheet();
            $mapworker->replace_defs();
            $mapworker->fix_svg();
            $item->svgcode = $mapworker->get_svgcode();
        }
        // Map ids are used in the svg code as well, so we need to replace them there too.
        $item->svgcode = str_replace('-' . $oldmapid, '-' . $newmapid, $item->svgcode);
        $item->placestore = json_encode($placestore);
        $item->course = $courseid;
        $DB->update_record('learningmap', $item);
    }
}

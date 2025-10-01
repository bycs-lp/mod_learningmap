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

/**
 * Upgrade functions for mod_learningmap
 *
 * @package     mod_learningmap
 * @copyright 2021-2024, ISB Bayern
 * @author      Stefan Hanauska <stefan.hanauska@csg-in.de>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Define upgrade steps to be performed to upgrade the plugin from the old version to the current one.
 *
 * @param int $oldversion Version number the plugin is being upgraded from.
 */
function xmldb_learningmap_upgrade($oldversion) {
    global $DB;
    $dbman = $DB->get_manager();

    if ($oldversion < 2024022102) {
        $entries = $DB->get_records('learningmap', []);
        if ($entries) {
            foreach ($entries as $entry) {
                $placestore = json_decode($entry->placestore, true);
                $placestore['version'] = 2024032401;
                // Needs 1 as default value (otherwise all place strokes would be hidden).
                if (!isset($placestore['strokeopacity'])) {
                    $placestore['strokeopacity'] = 1;
                }
                $mapworker = new \mod_learningmap\mapworker($entry->intro, $placestore);
                $mapworker->replace_stylesheet();
                $mapworker->replace_defs();
                $entry->intro = $mapworker->get_svgcode();
                $entry->placestore = json_encode($placestore);
                $DB->update_record('learningmap', $entry);
            }
        }

        $table = new xmldb_table('learningmap');
        $field = new xmldb_field('backlink', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'completiontype');

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $index = new xmldb_index('backlink', XMLDB_INDEX_NOTUNIQUE, ['backlink']);

        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        upgrade_mod_savepoint(true, 2024022102, 'learningmap');
    }

    if ($oldversion < 2024072201) {
        // Define field id to be added to learningmap.
        $table = new xmldb_table('learningmap');
        $field = new xmldb_field('svgcode', XMLDB_TYPE_TEXT, null, null, null, null, null, 'backlink');

        // Conditionally launch add field id.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('showmaponcoursepage', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '1', 'svgcode');

        // Conditionally launch add field id.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('introformat', XMLDB_TYPE_INTEGER, '4', null, null, null, '0', 'intro');

        // Launch change of default for field introformat.
        $dbman->change_field_default($table, $field);

        // Move the learningmap content to the new database fields. This also handles moving the files to the new
        // filearea 'background'.
        \mod_learningmap\migrationhelper::update_learningmaps_to_use_svgcode();

        // Learningmap savepoint reached.
        upgrade_mod_savepoint(true, 2024072201, 'learningmap');
    }

    if ($oldversion < 2025092200) {
        // Define field usemodal to be added to learningmap.
        $table = new xmldb_table('learningmap');
        $field = new xmldb_field('usemodal', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'showmaponcoursepage');

        // Conditionally launch add field usemodal.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Learningmap savepoint reached.
        upgrade_mod_savepoint(true, 2025092200, 'learningmap');
    }

    return true;
}

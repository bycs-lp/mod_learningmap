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
 * Language file for mod_learningmap
 *
 * @package     mod_learningmap
 * @copyright 2021-2024, ISB Bayern
 * @author      Stefan Hanauska <stefan.hanauska@csg-in.de>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['advancedsettings'] = 'Advanced settings';
$string['allowedfilters'] = 'Filters allowed for use with mod_learningmap';
$string['allowedfilters_desc'] = 'Comma separated list without filter_ prefix';
$string['backgroundfile'] = 'Background image';
$string['backgroundfile_help'] = 'This file will be used as background for the map.';
$string['backlink'] = 'To "{$a->name}"';
$string['backlinkallowed'] = 'Allow automatic backlinks';
$string['backlinkallowed_desc'] = 'If this setting is enabled, users can choose to automatically set backlinks to the learning map from the module pages of the activities used in the learning map.';
$string['cachedef_backlinks'] = 'This cache stores information about whether there is a backlink to the learning map to show on a course module page.';
$string['completion_with_all_places'] = 'Reaching all places is necessary for completion';
$string['completion_with_all_targets'] = 'Reaching all target places is necessary for completion';
$string['completion_with_one_target'] = 'Reaching one target place is necessary for completion';
$string['completiondetail:all_places'] = 'Reach all places';
$string['completiondetail:all_targets'] = 'Reach all target places';
$string['completiondetail:one_target'] = 'Reach one target place';
$string['completiondisabled'] = 'Completion tracking is disabled in course settings. Without completion tracking this plugin won\'t work.';
$string['completiontype'] = 'Type of completion';
$string['editorhelp'] = 'How to use the editor';
$string['editplace'] = 'Edit place';
$string['fill_backlink_cache_task'] = 'Fill learningmap backlink cache';
$string['freetype_required'] = 'FreeType extension to GD is required to run mod_learningmap.';
$string['groupmode'] = 'Group mode';
$string['groupmode_help'] = 'When group mode is active, it is sufficient that one member of the group has completed an activity to be able to have the connected places available.';
$string['hiddenactivitywarning'] = 'This activity is hidden and can\'t be accessed by students';
$string['hidepaths'] = 'Hide paths';
$string['hidepaths_help'] = 'This option hides the paths in the student view while they remain functional for building dependencies between the activities.';
$string['hidestroke'] = 'Hide stroke for places';
$string['hidestroke_help'] = 'This option hides the stroke for the places.';
$string['hover'] = 'Hover animation for places';
$string['hover_help'] = 'This option adds an animation to the places when they are hovered with the cursor.';
$string['intro'] = 'Learning map';
$string['intro_help'] = '<ul><li><b>Add a new place:</b> Double click on background</li>
<li><b>Add a path:</b> Single click on two places</li>
<li><b>Remove a place / path:</b> Double click on it</li>
<li><b>Change properties of a place:</b> Right click on it</li></ul>';
$string['learningmap'] = 'Learning map';
$string['learningmap:addinstance'] = 'Add a new learning map';
$string['learningmap:view'] = 'View learning map';
$string['learningmapnotavailable'] = '"{$a}" is not available';
$string['loading'] = 'Learningmap is loading...';
$string['modulename'] = 'Learning map';
$string['modulename_help'] = 'The learningmap module allows to structure activities in a course as places on a map, connected by paths. Some places are starting places and shown from the beginning. Other places and paths are shown when the activities of the connected places are completed.';
$string['modulenameplural'] = 'Learning maps';
$string['name'] = 'Learning map name';
$string['name_help'] = 'The name of the learning map is only displayed if the "Show map on course page" is not checked.';
$string['nocompletion'] = 'No completion with map items';
$string['nocompletionenabled'] = 'Not available because completion is not enabled';
$string['ownprogress'] = 'My own progress';
$string['paths'] = 'Paths';
$string['places'] = 'Places';
$string['pluginadministration'] = 'Learning map administration';
$string['pluginname'] = 'Learning map';
$string['privacy:metadata'] = '';
$string['pulse'] = 'Pulse animation for unvisited places';
$string['pulse_help'] = 'This option adds an animation to unvisited places to highlight them.';
$string['showall'] = 'Show all paths and places';
$string['showall_help'] = 'This option shows all paths and places of the map right from the start. Places and paths not yet reachable are dimmed.';
$string['showbacklink'] = 'Show backlinks on course module pages';
$string['showbacklink_help'] = 'This option shows a link "Back to learning map" on every course module page that belongs to the map.';
$string['showmaponcoursepage'] = 'Show map on course page';
$string['showmaponcoursepage_help'] = 'If checked, the learning map will be displayed on the course page (like a label). Else there will be a link and the map will be displayed on a separate page.';
$string['showtext'] = 'Show activity names';
$string['showtext_help'] = 'This option shows the names of the activities as a text besides the places. The text can be dragged around and is automatically updated when the activity name changes.';
$string['showwaygone'] = 'Highlight way';
$string['showwaygone_help'] = 'This option highlights the way the participant went through the map (in the order of completion times).';
$string['slicemode'] = 'Reveal map with places';
$string['slicemode_help'] = 'This option subsequently reveals the map when new places become reachable. The hidden parts of the map are covered with fog. The fog will clear completely as soon as all places become reachable.';
$string['startingplace'] = 'Starting place';
$string['svgcode'] = 'SVG code';
$string['targetplace'] = 'Target place';
$string['usecasehelp'] = 'How to use learning maps';
$string['usecaselink'] = 'Link to a page explaining the use of the learning map';
$string['usecheckmark'] = 'Checkmark for visited places';
$string['usecheckmark_help'] = 'This option additionally shows a checkmark in visited places.';
$string['usemodal'] = 'Use modal to display labels';
$string['usemodal_help'] = 'Show labels and label-like activities in a modal.';
$string['visited'] = 'Visited';

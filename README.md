# Learning maps

A learning map provides an easy way of improving the visualization of activities in a moodle course. Activities are represented as places (i.e. dots) on the map, connected by paths (i.e. lines). Activities and paths are initially invisible. Every time an activity attached to a place is completed, the place changes its color (e.g. from red to green) and all connected paths and places are gradually revealed.

## Use of learning maps

Learning maps can be used in many ways. They can include all activities of a course or only parts of it. They can also be nested to have different map levels (e.g. one big map for a complete school year, containing smaller maps for each topic). Usually the activities shown in the map are hidden but available for the participants. By this means, the learning map can provide an easy way of creating a path of dependent activities without the necessity of using moodle access restrictions. Learning maps can be embedded on the course page (like a label) or shown on a separate page (like a page). You can include any activity with any type of activity completion in a learning map.

## Start and completion

On a learning map you can define visible starting places. The first place you put on the map is a starting place by default. You can also define places as target places. They can be used for automatic completion of the map in three different ways: 1. The map is completed if one target place is reached (i.e. the linked activity is completed), 2. all target places are reached, 3. all places are reached. This is very convenient when using nested maps. As a consequence, learning maps can also be used to replace parts of the function of mod_checklist.

## Features

As a background image, you can use any image which can be viewed in a web browser (e.g. JPG, PNG, GIF, SVG). The map is resized to the size of the image and fully responsive. You can change the color of places (different colors for visited / unvisited places) and the lines of places and paths. Target places are highlighted for the participants. If necessary, you can also hide the paths or show a checkmark at visited places.
The map is always updated when the completion state of a linked activity changes. If the learning map is embedded on a course page and manual completion of a linked activity is triggered, a page reload is forced to keep the map status correct. If an activity has additional restrictions (e.g. visible only after a certain date), learning map will display it only if these restrictions are fulfilled.

## Use of the editor

Using the editor is very easy:

1. Choose your background image - it will be immediately shown in the map editor
2. Add places (double click on the map)
3. Link places to activities (right click on the place)
4. Connect places by a (single) click on both places
5. Remove paths / places by double-clicking them

If a place is not linked to an activity it is shown with reduced opacity.

## Installing via uploaded ZIP file ##

1. Log in to your Moodle site as an admin and go to _Site administration >
   Plugins > Install plugins_.
2. Upload the ZIP file with the plugin code. You should only be prompted to add
   extra details if your plugin type is not automatically detected.
3. Check the plugin validation report and finish the installation.

## Installing manually ##

The plugin can be also installed by putting the contents of this directory to

    {your/moodle/dirroot}/mod/learningmap

Afterwards, log in to your Moodle site as an admin and go to _Site administration >
Notifications_ to complete the installation.

Alternatively, you can run

    $ php admin/cli/upgrade.php

to complete the installation from the command line.

## License ##

2021 ISB Bayern, Stefan Hanauska <stefan.hanauska@csg-in.de>
Icon by Dunja Speckner
Dragging of SVG elements by Peter Collingridge

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program. If not, see <https://www.gnu.org/licenses/>.
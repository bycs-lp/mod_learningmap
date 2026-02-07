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
 * Main module for the learningmap editor
 *
 * @module     mod_learningmap/learningmap
 * @copyright  2025 ISB Bayern
 * @author     Stefan Hanauska <stefan.hanauska@csg-in.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
import {exception as displayException, saveCancel} from 'core/notification';
import Templates from 'core/templates';
import placestore from 'mod_learningmap/placestore';
import * as Str from 'core/str';

const circleRadius = 10;

// Constants for updatePathDeclaration.
const targetPoints = {
    firstPoint: 1,
    secondPoint: 2,
    bezierPoint: 3,
};

const pathTypes = {
    line: 1,
    quadraticbezier: 2,
};

export const init = async() => {
    // Load the needed template on startup for better execution speed.
    Templates.prefetchTemplates(['mod_learningmap/cssskeleton']);

    // Variable for storing the mouse offset
    var offset;

    // Variable for draggable element
    var dragel;

    // Variables for storing the paths that need update of the first or
    // the second coordinates.
    var pathsToUpdateFirstPoint, pathsToUpdateSecondPoint;

    // Variables for handling the currently selected elements
    var selectedElement = null,
        firstPlace = null,
        secondPlace = null,
        lastTarget = null;

    // Variable for storing the selected element for the activity selector
    var elementForActivitySelector = null;

    // Variables for simulating double click on touch devices, set when the
    // corresponding events are handled
    var touchstart = false;
    var touchend = false;
    // Counter for touchmove events
    var touchmove = 0;

    // DOM nodes for the editor
    let mapdiv = document.getElementById('learningmap-editor-map');
    let code = document.getElementById('id_svgcode');

    let svgdoc = new DOMParser().parseFromString(code.value, 'image/svg+xml');
    let svgnode = svgdoc.querySelector('svg');

    // DOM nodes for the activity selector
    let activitySetting = document.getElementById('learningmap-activity-setting');
    let activitySelector = document.getElementById('learningmap-activity-selector');
    let activityStarting = document.getElementById('learningmap-activity-starting');
    let activityTarget = document.getElementById('learningmap-activity-target');
    let activityHiddenWarning = document.getElementById('learningmap-activity-hidden-warning');
    let advancedSettingsIcon = document.getElementById('learningmap-advanced-settings-icon');

    // Hide tree view as there is no preview file we can attach to
    let treeView = document.querySelector('.fp-viewbar .fp-vb-tree');
    if (treeView) {
        treeView.setAttribute('style', 'display: none;');
    }

    // Trigger click event on icon view to ensure that tree view is not active.
    let iconView = document.querySelector('.fp-viewbar .fp-vb-icons');
    if (iconView) {
        // Handle possible delay in form loading.
        setTimeout(() => {
            iconView.dispatchEvent(new Event('click'));
        }, 1000);
    }

    // Attach listeners to the activity selector
    if (activitySelector) {
        // Show places that are not linked to an activity
        activitySelector.addEventListener('change', function() {
            placestore.setActivityId(elementForActivitySelector, activitySelector.value);
            if (activitySelector.value) {
                let text = document.getElementById('text' + elementForActivitySelector);
                if (text) {
                    text.replaceChildren(svgdoc.createCDATASection(
                        activitySelector.querySelector('option[value="' + activitySelector.value + '"]').textContent
                    ));
                }
                let title = document.getElementById('title' + elementForActivitySelector);
                if (title) {
                    title.replaceChildren(svgdoc.createCDATASection(
                        activitySelector.querySelector('option[value="' + activitySelector.value + '"]').textContent
                    ));
                }
                document.getElementById(elementForActivitySelector).classList.remove('learningmap-emptyplace');
            } else {
                document.getElementById(elementForActivitySelector).classList.add('learningmap-emptyplace');
            }
            updateActivities();
            updateCode();
        });
        // Add / remove a place to the starting places array
        activityStarting.addEventListener('change', function() {
            if (activityStarting.checked) {
                placestore.addStartingPlace(elementForActivitySelector);
            } else {
                placestore.removeStartingPlace(elementForActivitySelector);
            }
            updateCode();
        });
        // Add / remove a place to the target places array
        activityTarget.addEventListener('change', function() {
            if (activityTarget.checked) {
                placestore.addTargetPlace(elementForActivitySelector);
                document.getElementById(elementForActivitySelector).classList.add('learningmap-targetplace');
            } else {
                placestore.removeTargetPlace(elementForActivitySelector);
                document.getElementById(elementForActivitySelector).classList.remove('learningmap-targetplace');
            }
            updateCode();
        });
    }

    // Load placestore values from the hidden input field
    let placestoreInput = document.getElementsByName('placestore')[0];
    if (placestoreInput) {
        placestore.loadJSON(placestoreInput.value);
    }

    // Mark all activities in the placestore as "used".
    updateActivities();

    // Attach listeners to the advanced settings div
    if (advancedSettingsIcon) {
        let advancedSettings = document.getElementById('learningmap-advanced-settings');
        advancedSettingsIcon.addEventListener('click', function() {
            if (advancedSettings.getAttribute('hidden') === null) {
                hideAdvancedSettings();
            } else {
                advancedSettings.removeAttribute('hidden');
                hideContextMenu();
            }
        });
        advancedSettingsIcon.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                advancedSettingsIcon.click();
            }
        });
        let advancedSettingsClose = document.getElementById('learningmap-advanced-settings-close');
        if (advancedSettingsClose) {
            advancedSettingsClose.addEventListener('click', function() {
                advancedSettings.setAttribute('hidden', '');
            });
        }

        await advancedSettingsLogic('hidepaths', placestore.getHidePaths, placestore.setHidePaths);
        await advancedSettingsLogic('usecheckmark', placestore.getUseCheckmark, placestore.setUseCheckmark);
        await advancedSettingsLogic('hover', placestore.getHover, placestore.setHover);
        await advancedSettingsLogic('pulse', placestore.getPulse, placestore.setPulse);
        await advancedSettingsLogic('showall', placestore.getShowall, placestore.setShowall);
        await advancedSettingsLogic('hidestroke', placestore.getHideStroke, placestore.setHideStroke);
        await advancedSettingsLogic('showtext', placestore.getShowText, placestore.setShowText, fixPlaceLabels);
        await advancedSettingsLogic('slicemode', placestore.getSliceMode, placestore.setSliceMode);
        await advancedSettingsLogic('showwaygone', placestore.getShowWayGone, placestore.setShowWayGone);
        await advancedSettingsLogic('description', getTitleAndDesc, setTitleAndDesc);
    }

    // Attach listener to the color choosers
    colorChooserLogic('stroke', 'text');
    colorChooserLogic('place');
    colorChooserLogic('visited');

    // Get SVG code from the (hidden) textarea field
    if (code && mapdiv) {
        mapdiv.replaceChildren(svgnode);
    }
    // Reload background image to get the correct width and height values
    refreshBackgroundImage();
    registerBackgroundListener();
    updateCode();

    // Enable dragging of places
    makeDraggable(svgnode);

    // Refresh stylesheet values from placestore
    updateCSS();

    // Add listeners for clicking and context menu
    if (mapdiv) {
        mapdiv.addEventListener('dblclick', dblclickHandler);
        mapdiv.addEventListener('click', clickHandler);

        mapdiv.addEventListener('contextmenu', function(e) {
            e.preventDefault();
            showContextMenu(e);
        }, false);
    }
    /**
     * Shows the context menu at the current mouse position
     * @param {*} e
     */
    function showContextMenu(e) {
        unselectAll();
        hideAdvancedSettings();
        // Check for the existence of the target (could have vanished since the event started).
        if (activitySetting && document.getElementById(e.target.id) !== null) {
            if (e.touches) {
                e = e.touches[0];
            }
            if (e.target.classList.contains('learningmap-place')) {
                e.target.classList.add('learningmap-selected-activity-selector');
                let activityId = placestore.getActivityId(e.target.id);
                let scalingFactor = mapdiv.clientWidth / 800;
                activitySetting.style.setProperty('--pos-x', e.target.cx.baseVal.value * scalingFactor + 'px');
                activitySetting.style.setProperty('--pos-y', e.target.cy.baseVal.value * scalingFactor + 'px');
                activitySetting.style.setProperty('--map-width', mapdiv.clientWidth + 'px');
                activitySetting.style.setProperty('--map-height', mapdiv.clientHeight + 'px');
                activitySetting.style.display = 'block';
                document.getElementById('learningmap-activity-selector').value = activityId;
                document.getElementById('learningmap-activity-starting').checked = placestore.isStartingPlace(e.target.id);
                document.getElementById('learningmap-activity-target').checked = placestore.isTargetPlace(e.target.id);
                elementForActivitySelector = e.target.id;
                updateActivities();
            } else {
                hideContextMenu();
                hideAdvancedSettings();
            }
        }
    }

    /**
     * Hides the context menu
     */
    function hideContextMenu() {
        let e = document.getElementById(elementForActivitySelector);
        if (e) {
            e.classList.remove('learningmap-selected-activity-selector');
        }
        activitySetting.style.display = 'none';
    }

    let backgroundfileNode = document.getElementById('id_backgroundfile_fieldset');
    if (backgroundfileNode) {
        let observer = new MutationObserver(refreshBackgroundImage);
        observer.observe(backgroundfileNode, {attributes: true, childList: true, subtree: true});
    }

    /**
     * Helper function for getting the right coordinates from the mouse
     * @param {*} evt
     * @returns {object}
     */
    function getMousePosition(evt) {
        if (evt.touches) {
            evt = evt.touches[0];
        }
        return transformCoordinates(evt.clientX, evt.clientY);
    }

    /**
     * Transforms client coordinates to SVG coordinates
     * @param {number} x x coordinate to transform
     * @param {number} y y coordinate to transform
     * @returns {object} Object containing transformed x and y coordinate
     */
    function transformCoordinates(x, y) {
        var CTM = dragel.getScreenCTM();
        return {
            x: (x - CTM.e) / CTM.a,
            y: (y - CTM.f) / CTM.d
        };
    }

    /**
     * Enables dragging on an DOM node
     * @param {*} el
     */
    function makeDraggable(el) {
        dragel = el;
        if (el) {
            el.addEventListener('mousedown', startDrag);
            el.addEventListener('mousemove', drag);
            el.addEventListener('mouseup', endDrag);
            el.addEventListener('mouseleave', endDrag);
            el.addEventListener('touchstart', startTouch);
            el.addEventListener('touchmove', drag);
            el.addEventListener('touchend', endTouch);
            el.addEventListener('touchleave', endTouch);
            el.addEventListener('touchcancel', endTouch);
        }

        /**
         * Function called whenn dragging starts.
         * @param {*} evt
         */
        function startDrag(evt) {
            if (evt.cancelable) {
                evt.preventDefault();
            }
            pathsToUpdateFirstPoint = [];
            pathsToUpdateSecondPoint = [];
            if (evt.target.classList.contains('learningmap-draggable')) {
                selectedElement = evt.target;
                offset = getMousePosition(evt);
                offset.x -= parseInt(selectedElement.getAttributeNS(null, "cx"));
                offset.y -= parseInt(selectedElement.getAttributeNS(null, "cy"));
                // Get paths that need to be updated.
                pathsToUpdateFirstPoint = placestore.getPathsWithFid(selectedElement.id);
                pathsToUpdateSecondPoint = placestore.getPathsWithSid(selectedElement.id);
            } else if (evt.target.nodeName == 'text') {
                selectedElement = evt.target;
                let place = selectedElement.parentNode.querySelector('.learningmap-place');
                offset = getMousePosition(evt);
                offset.x -= parseInt(selectedElement.getAttributeNS(null, "dx")) + place.cx.baseVal.value;
                offset.y -= parseInt(selectedElement.getAttributeNS(null, "dy")) + place.cy.baseVal.value;
            } else if (evt.target.nodeName == 'path') {
                selectedElement = evt.target;
                offset = getMousePosition(evt);
                let pathPoint = transformCoordinates(evt.layerX, evt.layerY);
                offset.x += pathPoint.x;
                offset.y += pathPoint.y;
            }
        }

        /**
         * Function called during dragging. Continuously updates circles center coordinates and the
         * coordinates of the touching paths.
         * @param {*} evt
         */
        function drag(evt) {
            if (evt.cancelable) {
                evt.preventDefault();
            }
            // Count touchmove events
            touchmove++;
            if (selectedElement) {
                var coord = getMousePosition(evt);
                let cx = coord.x - offset.x;
                let cy = coord.y - offset.y;
                if (selectedElement.nodeName == 'text') {
                    let place = selectedElement.parentNode.querySelector('.learningmap-place');
                    // Calculate the delta from the current mouse position to the corresponding place.
                    // coord: current mouse position
                    // offset: delta from the mouse position to the coordinates of the text node
                    let dx = coord.x - offset.x - place.cx.baseVal.value;
                    let dy = coord.y - offset.y - place.cy.baseVal.value;
                    selectedElement.setAttributeNS(null, "dx", dx);
                    selectedElement.setAttributeNS(null, "dy", dy);
                }
                if (selectedElement.nodeName == 'path') {
                    selectedElement.setAttribute(
                        'd',
                        updatePathDeclaration(selectedElement.getAttribute('d'), coord.x, coord.y, targetPoints.bezierPoint)
                    );
                }
                if (selectedElement.nodeName == 'circle') {
                    selectedElement.setAttributeNS(null, "cx", cx);
                    selectedElement.setAttributeNS(null, "cy", cy);
                    let textNode = document.getElementById('text' + selectedElement.id);
                    if (textNode !== null) {
                        textNode.setAttributeNS(null, 'x', cx);
                        textNode.setAttributeNS(null, 'y', cy);
                    }
                    pathsToUpdateFirstPoint.forEach(function(path) {
                        let pathNode = document.getElementById(path.id);
                        if (pathNode !== null) {
                            if (pathNode.nodeName == 'path') {
                                pathNode.setAttribute(
                                    'd',
                                    updatePathDeclaration(pathNode.getAttribute('d'), cx, cy, targetPoints.firstPoint)
                                );
                            } else {
                                pathNode.setAttribute('x1', cx);
                                pathNode.setAttribute('y1', cy);
                            }
                        }
                    });

                    pathsToUpdateSecondPoint.forEach(function(path) {
                        let pathNode = document.getElementById(path.id);
                        if (pathNode !== null) {
                            if (pathNode.nodeName == 'path') {
                                pathNode.setAttribute(
                                    'd',
                                    updatePathDeclaration(pathNode.getAttribute('d'), cx, cy, targetPoints.secondPoint)
                                );
                            } else {
                                pathNode.setAttribute('x2', cx);
                                pathNode.setAttribute('y2', cy);
                            }
                        }
                    });
                }
            }
        }

        /**
         * Function called when dragging ends.
         * @param {*} evt
         */
        function endDrag(evt) {
            if (evt.cancelable) {
                evt.preventDefault();
            }
            selectedElement = null;
            unselectAll();
            updateCode();
        }

        /**
         * Function called when touchstart event occurs.
         * @param {*} evt
         */
        function startTouch(evt) {
            if (evt.cancelable) {
                evt.preventDefault();
            }
            if (
                evt.target.classList.contains('learningmap-draggable') ||
                evt.target.nodeName == 'text' ||
                evt.target.nodeName == 'path'
            ) {
                if (!touchstart) {
                    touchstart = true;
                    touchmove = 0;
                    touchend = false;
                    setTimeout(
                        (evt) => {
                            if (touchmove < 3 && !touchend) {
                                if (evt.touches) {
                                    evt = evt.touches[0];
                                }
                                showContextMenu(evt);
                            }
                        },
                        2000,
                        evt
                    );
                    setTimeout(
                        () => {
                            touchstart = false;
                        },
                    300);
                } else {
                    dblclickHandler(evt);
                    touchstart = false;
                }
                startDrag(evt);
            } else {
                if (!touchstart) {
                    touchstart = true;
                    touchend = false;
                    touchmove = 0;
                    setTimeout(
                        () => {
                            touchstart = false;
                        },
                    300);
                } else {
                    dblclickHandler(evt);
                    touchstart = false;
                }
            }
        }

        /**
         * Function called when touchend, touchleave or touchcancel event occurs.
         * @param {*} evt
         */
        function endTouch(evt) {
            selectedElement = null;
            touchend = true;
            // If there was only a small move (<3 move events), this also counts as a click.
            if (touchmove < 3 && touchstart) {
                clickHandler(evt);
            } else {
                endDrag(evt);
            }
            if (evt.cancelable) {
                evt.preventDefault();
            }
        }

        /**
         * Updates the path declaration of lines and quadratic bezier curves setting one of the points.
         * @param {string} oldDefinition SVG path definition string
         * @param {number} targetX x coordinate of the point to set
         * @param {number} targetY y coordinate of the point to set
         * @param {number} targetP Which point to change (you can use the targetPoints constants here)
         * @returns {string} Updated SVG path definition
         */
        function updatePathDeclaration(oldDefinition, targetX, targetY, targetP = targetPoints.firstPoint) {
            let parts = oldDefinition.split(' ');
            let fromX = 0;
            let fromY = 0;
            let toX = 0;
            let toY = 0;
            let bezierX = 0;
            let bezierY = 0;
            let pathType = pathTypes.line;

            // The d attribute of an SVG path in a learning map can have two different formats (in this version):
            // "M x1 y1 L x2 y2"        Line from x1, y1 to x2, y2
            // "M x1 y2 Q x3 y3 x2 y2"  Quadratic bezier curve inside the triangle defined by x1, y1, x2, y2 and x3, y3.
            for (let i = 0; i < parts.length; i++) {
                // Every path contains the first point in that way.
                if (parts[i] == 'M') {
                    fromX = parseInt(parts[i + 1]);
                    fromY = parseInt(parts[i + 2]);
                    i += 2;
                }
                // This path is a direct line, so there are only two points in total.
                if (parts[i] == 'L') {
                    toX = parseInt(parts[i + 1]);
                    toY = parseInt(parts[i + 2]);
                    i += 2;
                }
                // This path is a bezier curve, there are three points in total.
                if (parts[i] == 'Q') {
                    bezierX = parseInt(parts[i + 1]);
                    bezierY = parseInt(parts[i + 2]);
                    toX = parseInt(parts[i + 3]);
                    toY = parseInt(parts[i + 4]);
                    i += 4;
                    pathType = pathTypes.quadraticbezier;
                }
            }

            switch (targetP) {
                case targetPoints.firstPoint:
                    fromX = targetX;
                    fromY = targetY;
                    break;
                case targetPoints.secondPoint:
                    toX = targetX;
                    toY = targetY;
                    break;
                case targetPoints.bezierPoint:
                    // Calculate the third triangle point for the bezier curve.
                    bezierX = targetX * 2 - (fromX + toX) * 0.5;
                    bezierY = targetY * 2 - (fromY + toY) * 0.5;
                    pathType = pathTypes.quadraticbezier;
                    break;
            }

            if (pathType == pathTypes.quadraticbezier) {
                return 'M ' + fromX + ' ' + fromY + ' Q ' + bezierX + ' ' + bezierY + ', ' + toX + ' ' + toY;
            } else {
                return 'M ' + fromX + ' ' + fromY + ' L ' + toX + ' ' + toY;
            }
        }
    }

    /**
     * Updates the form fields for the SVG code and the placestore from the editor.
     */
    function updateCode() {
        if (code && mapdiv) {
            code.innerHTML = mapdiv.innerHTML;
        }
        if (placestoreInput) {
            document.getElementsByName('placestore')[0].value = JSON.stringify(placestore.getPlacestore());
        }
    }

    /**
     * Handles double clicks on the map
     * @param {*} event
     */
    function dblclickHandler(event) {
        hideContextMenu();
        hideAdvancedSettings();
        unselectAll();
        if (event.target.classList.contains('learningmap-mapcontainer') ||
            event.target.classList.contains('learningmap-background-image')) {
            addPlace(event);
        } else if (event.target.classList.contains('learningmap-place')) {
            if (lastTarget == event.target.id) {
                lastTarget = null;
                clickHandler(event);
            } else {
                removePlace(event);
            }
        } else if (event.target.classList.contains('learningmap-path')) {
            removePath(event.target.id);
        }
        updateCode();
    }

    /**
     * Returns an empty title tag with the given id.
     * @param {*} id id for the title
     * @returns {any}
     */
    function title(id) {
        let title = document.createElementNS('http://www.w3.org/2000/svg', 'title');
        title.setAttribute('id', id);
        return title;
    }

    /**
     * Returns an text tag with the given id.
     * @param {*} id id for the text
     * @param {*} content content of the tag
     * @param {*} x x coordinate of the text
     * @param {*} y y coordinate of the text
     * @returns {any}
     */
     function text(id, content, x, y) {
        let text = document.createElementNS('http://www.w3.org/2000/svg', 'text');
        text.setAttribute('id', id);
        text.setAttribute('x', x);
        text.setAttribute('y', y);
        // Default value for delta: Circle radius * 1.5 (as a padding)
        text.setAttribute('dx', circleRadius * 1.5);
        text.setAttribute('dy', circleRadius * 1.5);
        let textcontent = svgdoc.createCDATASection(content);
        text.replaceChildren(textcontent);
        return text;
    }

    /**
     * Returns a circle tag with the given dimensions.
     * @param {*} x x coordinate of the center
     * @param {*} y y coordinate of the center
     * @param {*} r radius
     * @param {*} classes classes to add
     * @param {*} id id of the circle
     * @returns {any}
     */
    function circle(x, y, r, classes, id) {
        let circle = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
        circle.setAttribute('class', classes);
        circle.setAttribute('id', id);
        circle.setAttribute('cx', x);
        circle.setAttribute('cy', y);
        circle.setAttribute('r', r);
        return circle;
    }

    /**
     * Returns a path between two points.
     * @param {*} x1 x coordinate of the first point
     * @param {*} y1 y coordinate of the first point
     * @param {*} x2 x coordinate of the second point
     * @param {*} y2 y coordinate of the second point
     * @param {*} classes CSS classes to set
     * @param {*} id id of the path
     * @returns {any}
     */
     function path(x1, y1, x2, y2, classes, id) {
        let path = document.createElementNS('http://www.w3.org/2000/svg', 'path');
        path.setAttribute('class', classes);
        path.setAttribute('id', id);
        path.setAttribute('d', 'M ' + x1 + ' ' + y1 + ' L ' + x2 + ' ' + y2);
        return path;
    }

    /**
     * Returns a link around a given child element. This function also adds a title element next
     * to the child for accessibility.
     * @param {*} child child item to set the link on
     * @param {*} id id of the link
     * @param {*} title title of the link
     * @param {*} text text to describe the link
     * @returns {any}
     */
    function link(child, id, title = null, text = null) {
        let link = document.createElementNS('http://www.w3.org/2000/svg', 'a');
        link.setAttribute('id', id);
        link.setAttribute('xlink:href', '');
        link.setAttribute('tabindex', '0');
        link.appendChild(child);
        if (title !== null) {
            link.appendChild(title);
        }
        if (text !== null) {
            link.appendChild(text);
        }
        return link;
    }

    /**
     * Set the title and description of the svg document.
     * @param {*} placestore
     * @param {*} values
     */
    function setTitleAndDesc(placestore, values) {
        let titlenode = svgnode.querySelector('svg>title');
        if (!titlenode) {
            titlenode = document.createElementNS('http://www.w3.org/2000/svg', 'title');
            svgnode.appendChild(titlenode);
        }
        let titlecontent = svgdoc.createCDATASection(values.title);
        titlenode.replaceChildren(titlecontent);
        titlenode.setAttribute('id', 'title-' + placestore.getMapid());

        let descnode = svgnode.querySelector('svg>desc');
        if (!descnode) {
            descnode = document.createElementNS('http://www.w3.org/2000/svg', 'desc');
            svgnode.appendChild(descnode);
        }
        let desccontent = svgdoc.createCDATASection(values.description);
        descnode.replaceChildren(desccontent);
        descnode.setAttribute('id', 'desc-' + placestore.getMapid());
    }

    /**
     * Get title and description of the svg document.
     * @returns object
     */
    function getTitleAndDesc() {
        let title = '';
        let desc = '';
        let titlenode = svgnode.querySelector('svg>title');
        if (titlenode) {
            title = titlenode.textContent;
        }
        let descnode = svgnode.querySelector('svg>desc');
        if (descnode) {
            desc = descnode.textContent;
        }
        return {
            title: title,
            description: desc
        };
    }

    /**
     * Adds a place on the SVG map. This function also prepares the code for linking activities
     * and adding titles (for accessibility).
     * @param {*} event event causing the command
     */
    function addPlace(event) {
        let placesgroup = document.getElementById('placesGroup');
        let placeId = 'p' + placestore.getId();
        let linkId = 'a' + placestore.getId();
        var CTM = event.target.getScreenCTM();
        if (event.touches) {
            event = event.touches[0];
        }
        let cx = (event.clientX - CTM.e) / CTM.a;
        let cy = (event.clientY - CTM.f) / CTM.d;
        placesgroup.appendChild(
            link(
                circle(cx, cy, circleRadius, 'learningmap-place learningmap-draggable learningmap-emptyplace', placeId),
                linkId,
                title('title' + placeId),
                text('text' + placeId, '', cx, cy)
            )
        );
        placestore.addPlace(placeId, linkId);
    }

    /**
     * Handles single clicks on the background image.
     * @param {*} event click event
     * @returns {void}
     */
    function clickHandler(event) {
        event.preventDefault();
        hideContextMenu();
        hideAdvancedSettings();
        if (event.target.classList.contains('learningmap-place') && selectedElement === null) {
            if (firstPlace === null) {
                firstPlace = event.target.id;
                document.getElementById(firstPlace).classList.add('learningmap-selected');
            } else {
                secondPlace = event.target.id;
                let fid = parseInt(firstPlace.replace('p', ''));
                let sid = parseInt(secondPlace.replace('p', ''));
                if (sid == fid) {
                    return;
                }
                if (sid < fid) {
                    let z = sid;
                    sid = fid;
                    fid = z;
                }
                addPath(fid, sid);
                let first = document.getElementById(firstPlace);
                if (first) {
                    first.classList.remove('learningmap-selected');
                }
                firstPlace = null;
                lastTarget = secondPlace;
                secondPlace = null;
            }
        } else {
            unselectAll();
            firstPlace = null;
        }
    }
    /**
     * Removes the classes 'learningmap-selected' and 'learningmap-selectet-activity-selector'
     * from all nodes
     */
    function unselectAll() {
        Array.from(document.getElementsByClassName('learningmap-selected')).forEach(function(e) {
            e.classList.remove('learningmap-selected');
        });
        Array.from(document.getElementsByClassName('learningmap-selected-activity-selector')).forEach(function(e) {
            e.classList.remove('learningmap-selected-activity-selector');
        });
    }

    /**
     * Adds a path between two places.
     * @param {number} fid id of the first place (meant to be the smaller one)
     * @param {number} sid id of the second place (meant to be the bigger one)
     */
    function addPath(fid, sid) {
        let pid = 'p' + fid + '_' + sid;
        if (document.getElementById(pid) === null) {
            let pathsgroup = document.getElementById('pathsGroup');
            let first = document.getElementById('p' + fid);
            let second = document.getElementById('p' + sid);
            if (pathsgroup && first && second) {
                pathsgroup.appendChild(
                    path(
                        first.cx.baseVal.value,
                        first.cy.baseVal.value,
                        second.cx.baseVal.value,
                        second.cy.baseVal.value,
                        'learningmap-path',
                        pid
                    )
                );
                placestore.addPath(pid, 'p' + fid, 'p' + sid);
            }
        }
    }

    /**
     * Removes a place from the SVG and the placestore. This function also removes all
     * touching paths and entries in startingplaces / targetplaces linking to the removed
     * place.
     * @param {any} event event causing the remove order
     */
    function removePlace(event) {
        let place = document.getElementById(event.target.id);
        let parent = place.parentNode;
        removePathsTouchingPlace(event.target.id);
        placestore.removePlace(event.target.id);
        parent.removeChild(place);
        parent.parentNode.removeChild(parent);

        updateCode();
    }

    /**
     * Removes all paths touching a certain place
     * @param {number} id id of the place
     */
    function removePathsTouchingPlace(id) {
        placestore.getTouchingPaths(id).forEach(
            function(e) {
                removePath(e.id);
            }
        );
    }

    /**
     * Removes a path from the SVG and from the placestore
     * @param {number} id id of the path
     */
    function removePath(id) {
        let path = document.getElementById(id);
        if (path !== null) {
            path.parentNode.removeChild(path);
            placestore.removePath(id);
        }
    }

    /**
     * Sets the background image of the SVG to the current image in filemanager.
     */
    function refreshBackgroundImage() {
        let previewimage = document.getElementsByClassName('realpreview');
        if (previewimage.length > 0) {
            let background = document.getElementById('learningmap-background-image');
            let backgroundurl = previewimage[0].getAttribute('src').split('?')[0];
            // If the uploaded file reuses the filename of a previously uploaded image, they differ
            // only in the oid. So one has to append the oid to the url.
            if (previewimage[0].getAttribute('src').split('?')[1].includes('&oid=')) {
                backgroundurl += '?oid=' + previewimage[0].getAttribute('src').split('&oid=')[1];
            }
            background.setAttribute('xlink:href', backgroundurl);
        }
    }

    /**
     * Adds an eventListener to the background image for watching file changes and updating
     * height and width of the image.
     */
    function registerBackgroundListener() {
        let background = document.getElementById('learningmap-background-image');
        if (background) {
            background.addEventListener('load', function() {
                background.removeAttribute('height');
                let height = parseInt(background.getBBox().height);
                let width = background.getBBox().width;
                placestore.setBackgroundDimensions(width, height);
                svgnode.setAttribute('viewBox', '0 0 ' + placestore.width + ' ' + placestore.height);
                background.setAttribute('width', width);
                background.setAttribute('height', height);
                updateCode();
            });
        }
    }

    /**
     * Updates CSS code inside the SVG (called, when one of the colors is changed).
     * Calls updateCode() when completed.
     */
    function updateCSS() {
        Templates.renderForPromise('mod_learningmap/cssskeleton', placestore.getPlacestore())
            .then(({html, js}) => {
                Templates.replaceNode('#learningmap-svgstyle', html, js);
                updateCode();
                return true;
            })
            .catch(ex => displayException(ex));
    }

    /**
     * Updates the activity selector to highlight the activities already used
     * and to show the alert for hidden activities.
     */
    function updateActivities() {
        let activities = placestore.getAllActivities();
        let options = Array.from(activitySelector.getElementsByTagName('option'));
        activityHiddenWarning.setAttribute('hidden', '');
        options.forEach(function(n) {
            if (activities.includes(n.value)) {
                n.classList.add('learningmap-used-activity');
                if (n.selected) {
                    if (n.getAttribute('data-activity-hidden') == true) {
                        activityHiddenWarning.removeAttribute('hidden');
                    }
                }
            } else {
                n.classList.remove('learningmap-used-activity');
            }
        });
    }

    /**
     * Adds the event listener to the color chooser buttons.
     * @param {*} name name of the color
     * @param {*} secondValue name of a second placestore value that has to be changed along
     */
    function colorChooserLogic(name, secondValue = '') {
        let colorChooser = document.getElementById('learningmap-color-' + name);
        if (colorChooser) {
            colorChooser.addEventListener('change', function() {
                placestore.setColor(name, colorChooser.value);
                if (secondValue != '') {
                    placestore.setColor(secondValue, colorChooser.value);
                }
                updateCSS();
            });
            colorChooser.value = placestore.getColor(name);
        }
    }

    /**
     * Adds the event listener to advanced settings menu items
     * @param {*} name Name of the item
     * @param {*} getCall Method of placestore to call to read value
     * @param {*} setCall Method of placestore to call to save value
     * @param {*} callback Additional callback after value is saved
     */
    async function advancedSettingsLogic(name, getCall, setCall, callback = null) {
        let settingItem = document.getElementById('learningmap-advanced-setting-' + name);
        if (settingItem) {
            // Settings that need a modal for editing (e.g. title and description) have a link to
            // open the modal, other settings have a checkbox.
            if (settingItem.nodeName == 'A') {
                const descriptionHandler = async() => {
                    const values = getCall();
                    try {
                        const strings = await Str.get_strings([
                            {key: 'titleanddescription', component: 'mod_learningmap'},
                            {key: 'save', component: 'core'},
                        ]);
                        return saveCancel(
                            strings[0],
                            Templates.render('mod_learningmap/' + name + '-modal', values),
                            strings[1],
                            () => {
                                let values = {};
                                let formentries = document.querySelectorAll('.mod_learningmap_' + name + '_value');
                                formentries.forEach((element) => {
                                    values[element.name] = element.value;
                                });
                                setCall(placestore, values);
                                if (callback !== null) {
                                    callback();
                                }
                                updateCSS();
                            }
                        );
                    } catch (ex) {
                        displayException(ex);
                        return null;
                    }
                };
                settingItem.addEventListener('click', descriptionHandler);
                settingItem.addEventListener('keypress', (e) => {
                    if (e.key === 'Enter') {
                        descriptionHandler();
                    }
                });
            // Boolean settings can be directly changed with a checkbox in the advanced settings menu.
            } else {
                settingItem.checked = getCall.call(placestore);
                settingItem.addEventListener('change', function() {
                    setCall.call(placestore, settingItem.checked);
                    if (callback !== null) {
                        callback();
                    }
                    updateCSS();
                });
            }
        }
    }

    /**
     * Adds missing text nodes
     */
    function fixPlaceLabels() {
        let options = Array.from(activitySelector.getElementsByTagName('option'));
        let places = placestore.getPlaces();
        for (const place of places) {
            if (document.getElementById('text' + place.id) === null) {
                let content = '';
                for (const option of options) {
                    if (option.value == place.linkedActivity) {
                        content = option.textContent;
                        break;
                    }
                }
                let placeNode = document.getElementById(place.id);
                let textNode = text('text' + place.id, content, placeNode.cx.baseVal.value, placeNode.cy.baseVal.value);
                placeNode.parentNode.appendChild(textNode);
            }
        }
    }

    /**
     * Hides the advanced settings menu.
     */
    function hideAdvancedSettings() {
        let advancedSettings = document.getElementById('learningmap-advanced-settings');
        advancedSettings.setAttribute('hidden', '');
    }
};

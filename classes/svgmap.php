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

use DOMDocument;
use DOMNode;
use DOMElement;
use DOMXPath;

/**
 * Class for handling the content of the learningmap
 *
 * @package     mod_learningmap
 * @copyright 2021-2024, ISB Bayern
 * @author      Stefan Hanauska <stefan.hanauska@csg-in.de>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class svgmap {
    /**
     * DOMDocument for parsing the SVG
     * @var DOMDocument
     */
    protected DOMDocument $dom;
    /**
     * DOMXPath for querying the SVG
     * @var DOMXPath
     */
    protected DOMXPath $xpath;
    /**
     * String containing the SVG code (synchronized with $dom)
     * @var string
     */
    protected string $svgcode;
    /**
     * Array containing the placestore
     * @var array
     */
    protected array $placestore;
    /**
     * String to prepend to the SVG code (for parsing by DOMDocument)
     * @var string
     */
    protected string $prepend;
    /**
     * Creates map from SVG code
     *
     * @param string $svgcode The SVG code to build the map from
     * @param array $placestore The placestore data to use while processing the map
     */
    public function __construct(string $svgcode, array $placestore) {
        $this->svgcode = $svgcode;
        $this->placestore = $placestore;

        $this->dom = new \DOMDocument('1.0', 'UTF-8');
        $this->dom->preserveWhiteSpace = false;
        $this->dom->formatOutput = true;

        $this->load_dom();
        $this->xpath = new \DOMXPath($this->dom);
    }

    /**
     * Loads the code from svgcode attribute for DOM processing
     *
     * @return void
     */
    public function load_dom(): void {
        $this->remove_tags_before_svg();
        $this->dom->loadXML($this->svgcode);
    }

    /**
     * Replaces the stylesheet with a new one generated from placestore
     *
     * @param array $placestoreoverride array of overrides for placestore
     * @return void
     */
    public function replace_stylesheet(array $placestoreoverride = []): void {
        global $OUTPUT;
        $placestorelocal = array_merge($this->placestore, $placestoreoverride);
        $this->svgcode = preg_replace(
            '/<style[\s\S]*style>/i',
            $OUTPUT->render_from_template('mod_learningmap/cssskeleton', $placestorelocal),
            $this->svgcode
        );
        $this->load_dom();
    }

    /**
     * Replaces the svg defs (e.g.) filters or patterns that are defined for use in the document without being directly visible.
     *
     * @return void
     */
    public function replace_defs(): void {
        global $OUTPUT;
        $this->svgcode = preg_replace(
            '/<defs[\s\S]*defs>/i',
            $OUTPUT->render_from_template('mod_learningmap/svgdefs', []),
            $this->svgcode
        );
        $this->load_dom();
    }

    /**
     * Removes tags before the SVG tag to avoid parsing problems
     *
     * @return void
     */
    public function remove_tags_before_svg(): void {
        $remove = ['<?xml version="1.0"?>'];
        $this->svgcode = str_replace($remove, '', $this->svgcode);
    }

    /**
     * Returns the current svg code
     *
     * @return string
     */
    public function get_svgcode(): string {
        return $this->svgcode;
    }

    /**
     * Save processed SVG data to svgcode
     *
     * @return void
     */
    public function save_svg_data(): void {
        $this->svgcode = $this->dom->saveXML();
    }

    /**
     * Get attribute value (for unit testing)
     *
     * @param string $id The id of the DOM element
     * @param string $attribute The name of the attribute
     * @return ?string null, if element doesn't exist
     */
    public function get_attribute(string $id, string $attribute): ?string {
        $element = $this->get_element_by_id($id);
        return $element === null ? null : $element->getAttribute($attribute);
    }

    /**
     * Remove a place or path. If removing a place also the link and the connected paths are removed.
     *
     * @param string $id Id of a place or path
     * @return void
     */
    public function remove_place_or_path(string $id): void {
        $placeorpath = $this->get_element_by_id($id);
        if ($placeorpath) {
            if ($placeorpath->nodeName == 'circle') {
                // Also remove connected paths for places.
                foreach ($this->placestore['paths'] as $path) {
                    if ($path['sid'] == $id || $path['fid'] == $id) {
                        $this->remove_place_or_path($path['id']);
                    }
                }
                // Make sure that also the link node is removed.
                $placeorpath = $placeorpath->parentNode;
            }
            $placeorpath->parentNode->removeChild($placeorpath);
        }
    }

    /**
     * Sets the URL of a link.
     *
     * @param string $linkid Id of the link
     * @param string $url URL to set the xlink:href attribute to
     * @return void
     */
    public function set_link(string $linkid, string $url): void {
        $link = $this->get_element_by_id($linkid);
        if ($link) {
            $link->setAttribute('xlink:href', $url);
        }
    }

    /**
     * Removes a link without removing the place.
     *
     * @param string $linkid Id of the link
     * @return void
     */
    public function remove_link(string $linkid): void {
        $link = $this->get_element_by_id($linkid);
        if ($link) {
            $link->removeAttribute('xlink:href');
        }
    }

    /**
     * Updates the activity name for a place.
     *
     * @param string $placeid Id of the place
     * @param string $text Name of the activity
     * @param string $additionaltitle Additional information to add to the title (for accessibility)
     * @return void
     */
    public function update_text_and_title(string $placeid, string $text, string $additionaltitle): void {
        // Set the title element for the link (for accessibility) and for a tooltip when hovering
        // the link.
        $titlenode = $this->get_element_by_id('title' . $placeid);
        if ($titlenode) {
            $titlenode->nodeValue = $text . $additionaltitle;
        }
        // Set the text element for the link.
        $textnode = $this->get_element_by_id('text' . $placeid);
        if ($textnode) {
            $textnode->nodeValue = $text;
        }
    }

    /**
     * Updates the title element of a place or path.
     *
     * @param string $id Id of a place or path
     * @param string $text Text to set the title element to
     * @return void
     */
    public function update_title(string $id, string $text): void {
        $node = $this->get_element_by_id($id);
        if ($node) {
            $titlenode = null;
            foreach ($node->childNodes as $child) {
                if ($child->nodeName == 'title') {
                    $titlenode = $child;
                    break;
                }
            }
            if ($titlenode) {
                $titlenode->nodeValue = $text;
            } else {
                $titlenode = $this->dom->createElementNS('http://www.w3.org/2000/svg', 'title', $text);
                $node->insertBefore($titlenode, $node->firstChild);
            }
        }
    }

    /**
     * Adds the learningmap-hidden class to a place or path.
     *
     * @param string $id Id of a place or path
     * @return void
     */
    public function set_hidden(string $id): void {
        $placeorpath = $this->get_element_by_id($id);
        if ($placeorpath) {
            $placeorpath->setAttribute('class', $placeorpath->getAttribute('class') . ' learningmap-hidden');
        }
    }

    /**
     * Adds the learningmap-reachable class to a place or path.
     *
     * @param string $id Id of a place or path
     * @return void
     */
    public function set_reachable(string $id): void {
        $placeorpath = $this->get_element_by_id($id);
        if ($placeorpath) {
            $placeorpath->setAttribute('class', $placeorpath->getAttribute('class') . ' learningmap-reachable');
        }
    }

    /**
     * Adds the learningmap-visited class to a place or path. Currently only used for places.
     *
     * @param string $id Id of a place or path
     * @return void
     */
    public function set_visited(string $id): void {
        $placeorpath = $this->get_element_by_id($id);
        if ($placeorpath) {
            $placeorpath->setAttribute('class', $placeorpath->getAttribute('class') . ' learningmap-visited');
        }
    }

    /**
     * Adds the learningmap-waygone class to a path.
     *
     * @param string $id Id of a path
     * @return void
     */
    public function set_waygone(string $id): void {
        $path = $this->get_element_by_id($id);
        if ($path) {
            $path->setAttribute('class', $path->getAttribute('class') . ' learningmap-waygone');
        }
    }

    /**
     * Adds a checkmark to a place.
     *
     * @param string $placeid Id of a place
     * @return void
     */
    public function add_checkmark(string $placeid): void {
        $place = $this->get_element_by_id($placeid);
        if ($place) {
            $x = $place->getAttribute('cx');
            $y = $place->getAttribute('cy');
            $use = $this->dom->createElement('use');
            $use->setAttribute('xlink:href', '#checkmark');
            $use->setAttribute('transform', 'translate(' . $x . ' ' . $y . ')');
            $use->setAttribute('class', 'learningmap-checkmark');
            $place->parentNode->appendChild($use);
        }
    }

    /**
     * Returns the coordinates of all paths and places for building the overlay.
     *
     * @return array Array of x and y coordinates
     */
    public function get_coordinates(): array {
        global $CFG;
        $coordinates = [];
        $pathsgroup = $this->get_element_by_id('pathsGroup');
        $placesgroup = $this->get_element_by_id('placesGroup');
        if (empty($this->placestore['hidepaths'])) {
            // Only processing quadratic bezier curves here as other paths are already handled
            // via the coordinates of the corresponding places.
            $paths = $pathsgroup->getElementsByTagName('path');
            foreach ($paths as $pathnode) {
                // When path is a quadratic bezier curve, the extremal point needs to be in the coordinates array.
                // The point is calculated here.
                if (strpos($pathnode->getAttribute('d'), 'Q')) {
                    $parts = explode(' ', $pathnode->getAttribute('d'));
                    $fromx = intval($parts[1]);
                    $fromy = intval($parts[2]);
                    $betweenx = intval($parts[4]);
                    $betweeny = intval($parts[5]);
                    $tox = intval($parts[6]);
                    $toy = intval($parts[7]);
                    $coordx = $betweenx * 0.5 + ($fromx + $tox) * 0.25;
                    $coordy = $betweeny * 0.5 + ($fromy + $toy) * 0.25;
                    $coordinates[] = ['x' => intval($coordx), 'y' => intval($coordy)];
                }
            }
        }
        $places = $placesgroup->getElementsByTagName('circle');
        foreach ($places as $placenode) {
            $cx = intval($placenode->getAttribute('cx'));
            $cy = intval($placenode->getAttribute('cy'));
            $coordinates[] = ['x' => $cx, 'y' => $cy];
            if ($this->placestore['showtext']) {
                $text = $this->get_element_by_id('text' . $placenode->getAttribute('id'));
                if ($text) {
                    // Delta of the text in relation to the places center coordinates.
                    $dx = $text->getAttribute('dx');
                    $dy = $text->getAttribute('dy');
                    // Calculate the corner coordinates of the text element. They all are added
                    // to the coordinates array as they extend the area that needs to be visible.
                    $bbox = imagettfbbox(20, 0, $CFG->dirroot . '/lib/default.ttf', $text->nodeValue);
                    $coordinates[] = ['x' => $cx + $dx + $bbox[0], 'y' => $cy + $dy + $bbox[1]];
                    $coordinates[] = ['x' => $cx + $dx + $bbox[2], 'y' => $cy + $dy + $bbox[3]];
                    $coordinates[] = ['x' => $cx + $dx + $bbox[4], 'y' => $cy + $dy + $bbox[5]];
                    $coordinates[] = ['x' => $cx + $dx + $bbox[6], 'y' => $cy + $dy + $bbox[7]];
                }
            }
        }
        return $coordinates;
    }

    /**
     * Adds an overlay to the map (for slicemode) revealing only the availble parts of the map.
     *
     * @return void
     */
    public function add_overlay(): void {
        $coordinates = $this->get_coordinates();
        if (count($coordinates) > 0) {
            $backgroundnode = $this->get_element_by_id('learningmap-background-image');
            $height = $backgroundnode->getAttribute('height');
            $c = array_pop($coordinates);
            $minx = $c['x'];
            $miny = $c['y'];
            $maxx = $c['x'];
            $maxy = $c['y'];
            // Find the maximum / minimum x and y coordinates.
            foreach ($coordinates as $coord) {
                $minx = min($minx, $coord['x']);
                $miny = min($miny, $coord['y']);
                $maxx = max($maxx, $coord['x']);
                $maxy = max($maxy, $coord['y']);
            }

            // When the maximum / minimum coordinates are too tight, increase padding.
            if ($maxx - $minx < 100 && $maxy - $miny < 100) {
                $padding = 50;
            } else {
                $padding = 15;
            }

            // Maximum / minimum coordinates should not be outside the background image.
            $minx = max(0, $minx - $padding);
            $miny = max(0, $miny - $padding);
            $maxx = min(800, $maxx + $padding);
            $maxy = min($height, $maxy + $padding);

            $placesgroup = $this->get_element_by_id('placesGroup');

            // Create the overlay for slicemode.
            $overlay = $this->dom->createElement('path');
            $overlaydescription = "M 0 0 L 0 $height L 800 $height L 800 0 Z ";
            // In future versions there will be more options for the inner part of the overlay.
            // For now the default is a rectangular shape.
            $type = 'rect';
            switch ($type) {
                // Kept for future use.
                case 'ellipse':
                    $radiusx = 0.5 * ($maxx - $minx);
                    $radiusy = 0.5 * ($maxy - $miny);
                    $overlaydescription .= "M $minx $miny A $radiusx $radiusy 0 1 1 $maxx $maxy ";
                    $overlaydescription .= "A $radiusx $radiusy 0 1 1 $minx $miny";
                    break;
                default:
                    $overlaydescription .= "M $minx $miny L $maxx $miny L $maxx $maxy L $minx $maxy Z";
            }
            $overlay->setAttribute('d', $overlaydescription);
            $overlay->setAttribute('fill', 'url(#fog)');
            $overlay->setAttribute('filter', 'url(#blur)');
            $overlay->setAttribute('stroke', 'none');
            $overlay->setAttribute('id', 'learningmap-overlay');
            $placesgroup->appendChild($overlay);
        }
    }

    /**
     * Emulates getElementsByClassname via XPath
     *
     * @param string $classname The class name to search for
     * @return array An array of matching elements
     */
    public function get_elements_by_classname(string $classname): array {
        $elements = $this->xpath->query("//*[contains(concat(' ', normalize-space(@class), ' '), ' $classname ')]");
        return iterator_to_array($elements);
    }

    /**
     * Emulates getElementById via XPath
     *
     * @param string $id The id to search for
     * @return DOMElement|null The matching element or null if not found
     */
    public function get_element_by_id(string $id): ?DOMElement {
        $elements = $this->xpath->query("//*[@id='$id']");
        return $elements->length > 0 ? $elements->item(0) : null;
    }

    /**
     * Wraps all items with a specific class name in links.
     *
     * @param string $classname The class name to search for
     * @param string $url The URL to link to
     * @return void
     */
    public function wrap_items_in_links(string $classname, string $url): void {
        $elements = $this->get_elements_by_classname($classname);
        foreach ($elements as $element) {
            $this->wrap_element_in_link($element, $url);
        }
    }

    /**
     * Removes all elements with a specific class name.
     *
     * @param string $classname The class name to search for
     * @return void
     */
    public function remove_elements_by_classname(string $classname): void {
        $elements = $this->get_elements_by_classname($classname);
        foreach ($elements as $element) {
            $element->parentNode->removeChild($element);
        }
    }

        /**
         * Wraps an item in a link.
         *
         * @param string $id Id of a place or path
         * @param string $url URL to link to
         * @return void
         */
    public function wrap_in_link(string $id, string $url): void {
        $element = $this->get_element_by_id($id);
        if ($element) {
            $this->wrap_element_in_link($element, $url);
        }
    }

    /**
     * Wraps an element in a link.
     *
     * @param DOMNode $element The element to wrap
     * @param string $url The URL to link to
     * @return void
     */
    public function wrap_element_in_link(DOMNode $element, string $url): void {
        $link = $this->dom->createElement('a');
        $link->setAttribute('xlink:href', $url);
        $element->parentNode->insertBefore($link, $element);
        $link->appendChild($element);
    }

    /**
     * Sets an attribute of an element.
     *
     * @param string $id The id of the DOM element
     * @param string $attribute The name of the attribute
     * @param string $value The value to set the attribute to
     * @return void
     */
    public function set_attribute(string $id, string $attribute, string $value): void {
        $element = $this->get_element_by_id($id);
        if ($element) {
            $element->setAttribute($attribute, $value);
        }
    }

    /**
     * Replaces all CDATA sections with properly escaped content
     *
     * @return void
     */
    public function replace_cdata(): void {
        $this->svgcode = preg_replace_callback(
            '/<!\[CDATA\[(.*?)\]\]>/s',
            function ($matches) {
                return htmlspecialchars($matches[1], ENT_XML1, 'UTF-8', false);
            },
            $this->svgcode
        );
        $this->load_dom();
    }
}

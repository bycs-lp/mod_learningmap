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

namespace mod_learningmap;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\CoversClass;
/**
 * Tests for svgmap class.
 *
 * @package    mod_learningmap
 * @category   test
 * @copyright  2026 ISB Bayern
 * @author     Stefan Hanauska <stefan.hanauska@csg-in.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \mod_learningmap\svgmap
 */
#[CoversClass(\mod_learningmap\svgmap::class)]
final class mod_learningmap_svgmap_test extends \advanced_testcase {
    /**
     * Test constructor (especially handling CDATA removal).
     *
     * @param string $svg The SVG code to test.
     * @param array $placestore The placestore to pass to the constructor.
     * @param array $expected The expected content of the title and desc elements after construction.
     * @dataProvider constructor_data_provider
     * @covers \mod_learningmap\svgmap::__construct
     */
    #[DataProvider('constructor_data_provider')]
    public function test_constructor(string $svg, array $placestore, array $expected): void {
        $svgmap = new svgmap($svg, $placestore);
        foreach ($expected as $key => $value) {
            $this->assertStringContainsString($value, $svgmap->get_svgcode());
        }
    }

    /**
     * Data provider for test_constructor.
     *
     * @return array
     */
    public static function constructor_data_provider(): array {
        return [
            'Simple SVG with CDATA' => [
                '<svg>
                <title id="title1"><![CDATA[Title with <tags> & special characters]]></title>
                <desc id="desc1"><![CDATA[Description with <tags> & special characters]]></desc>
                </svg>',
                [],
                [
                    'title1' => '<title id="title1">Title with &lt;tags&gt; &amp; special characters</title>',
                    'desc1' => '<desc id="desc1">Description with &lt;tags&gt; &amp; special characters</desc>',
                ],
            ],
            'SVG without CDATA' => [
                '<svg>
                <title id="title1">Title</title><desc id="desc1">Description</desc></svg>',
                [],
                ['title1' => '<title id="title1">Title</title>', 'desc1' => '<desc id="desc1">Description</desc>'],
            ],
            'empty title and desc with CDATA' => [
                '<svg><title id="title1"><![CDATA[]]></title><desc id="desc1"><![CDATA[]]></desc></svg>',
                [],
                ['title1' => '<title id="title1"></title>', 'desc1' => '<desc id="desc1"></desc>'],
            ],
            'Multiple CDATA sections' => [
                '<svg>
                <title id="title1"><![CDATA[First part]]><![CDATA[Second part]]></title>
                </svg>',
                [],
                ['title1' => '<title id="title1">First partSecond part</title>'],
            ],
            'CDATA as comment' => [
                '<svg>
                <title id="title1"><!--[CDATA[xy]]--><![CDATA[Chrome destroyed this]]></title>
                </svg>',
                [],
                ['title1' => '<title id="title1">xyChrome destroyed this</title>'],
            ],
            'Bad SVG' => [
                '<svg><title id="title1"><</title></svg>',
                [],
                ['title1' => '<title id="title1">&lt;</title>'],
            ],
        ];
    }

    /**
     * Test get_svgcode method.
     *
     * @covers \mod_learningmap\svgmap::get_svgcode
     */
    public function test_get_svgcode(): void {
        $svg = '<svg id="svg1"><circle id="p1" cx="10" cy="20" /></svg>';
        $placestore = ['mapid' => 42];
        $map = new svgmap($svg, $placestore);
        $this->assertStringContainsString($svg, $map->get_svgcode());
    }

    /**
     * Test set_attribute method.
     *
     * @covers \mod_learningmap\svgmap::set_attribute
     */
    public function test_set_attribute(): void {
        $svg = '<svg><circle id="p1" cx="10" cy="20" /></svg>';
        $placestore = ['mapid' => 42];
        $map = new svgmap($svg, $placestore);
        $map->set_attribute('p1', 'cx', '100');
        $this->assertEquals('100', $map->get_element_by_id('p1')->getAttribute('cx'));
        $map->set_attribute('p1', 'fill', '#ff0000');
        $this->assertEquals('#ff0000', $map->get_element_by_id('p1')->getAttribute('fill'));
    }

    /**
     * Test get_attribute method.
     *
     * @covers \mod_learningmap\svgmap::get_attribute
     */
    public function test_get_attribute(): void {
        $svg = '<svg><circle id="c1" cx="10" cy="20" /></svg>';
        $placestore = ['mapid' => 42];
        $map = new svgmap($svg, $placestore);
        $this->assertEquals('10', $map->get_attribute('c1', 'cx'));
        $this->assertEquals('', $map->get_attribute('c1', 'fill'));
    }

    /**
     * Test set_link method.
     *
     * @covers \mod_learningmap\svgmap::set_link
     */
    public function test_set_link(): void {
        $svg = '<svg><a id="link1"><circle id="c1" /></a></svg>';
        $placestore = ['mapid' => 42];
        $map = new svgmap($svg, $placestore);
        $map->set_link('link1', 'https://lernplattform.bycs.de');
        $this->assertEquals('https://lernplattform.bycs.de', $map->get_attribute('link1', 'xlink:href'));
    }

    /**
     * Test remove_link method.
     *
     * @covers \mod_learningmap\svgmap::remove_link
     */
    public function test_remove_link(): void {
        $svg = '<svg xmlns:xlink="http://www.w3.org/1999/xlink">
        <a id="link1" xlink:href="https://lernplattform.bycs.de"><circle id="c1" /></a></svg>';
        $placestore = ['mapid' => 42];
        $map = new svgmap($svg, $placestore);
        $map->remove_link('link1');
        $this->assertEquals('', $map->get_attribute('link1', 'xlink:href'));
    }

    /**
     * Test update_text_and_title method.
     *
     * @covers \mod_learningmap\svgmap::update_text_and_title
     */
    public function test_update_text_and_title(): void {
        $svg = '<svg xmlns:xlink="http://www.w3.org/1999/xlink"><title id="titlep0"></title><text id="textp0"></text></svg>';
        $placestore = ['mapid' => 42];
        $map = new svgmap($svg, $placestore);
        $map->update_text_and_title('p0', 'Test text', 'Extra');
        $this->assertStringContainsString('Test textExtra', $map->get_element_by_id('titlep0')->nodeValue);
        $this->assertStringContainsString('Test text', $map->get_element_by_id('textp0')->nodeValue);
        $map->update_text_and_title('p0', 'New text', '');
        $this->assertStringContainsString('New text', $map->get_element_by_id('titlep0')->nodeValue);
        $this->assertStringContainsString('New text', $map->get_element_by_id('textp0')->nodeValue);
    }

    /**
     * Test set_hidden method.
     *
     * @covers \mod_learningmap\svgmap::set_hidden
     */
    public function test_set_hidden(): void {
        $svg = '<svg><circle id="p1" /><circle id="p2" class="none"/></svg>';
        $placestore = ['mapid' => 42];
        $map = new svgmap($svg, $placestore);
        $map->set_hidden('p1');
        $this->assertStringContainsString('learningmap-hidden', $map->get_attribute('p1', 'class'));
        $map->set_hidden('p2');
        $this->assertStringContainsString('learningmap-hidden', $map->get_attribute('p2', 'class'));
    }

    /**
     * Test set_reachable method.
     *
     * @covers \mod_learningmap\svgmap::set_reachable
     */
    public function test_set_reachable(): void {
        $svg = '<svg><circle id="p1" /><circle id="p2" class="none"/></svg>';
        $placestore = ['mapid' => 42];
        $map = new svgmap($svg, $placestore);
        $map->set_reachable('p1');
        $this->assertStringContainsString('learningmap-reachable', $map->get_attribute('p1', 'class'));
        $map->set_reachable('p2');
        $this->assertStringContainsString('learningmap-reachable', $map->get_attribute('p2', 'class'));
    }

    /**
     * Test set_visited method.
     *
     * @covers \mod_learningmap\svgmap::set_visited
     */
    public function test_set_visited(): void {
        $svg = '<svg><circle id="p1" /><circle id="p2" class="none"/></svg>';
        $placestore = ['mapid' => 42];
        $map = new svgmap($svg, $placestore);
        $map->set_visited('p1');
        $this->assertStringContainsString('learningmap-visited', $map->get_attribute('p1', 'class'));
        $map->set_visited('p2');
        $this->assertStringContainsString('learningmap-visited', $map->get_attribute('p2', 'class'));
    }

    /**
     * Test set_waygone method.
     *
     * @covers \mod_learningmap\svgmap::set_waygone
     */
    public function test_set_waygone(): void {
        $svg = '<svg><circle id="p1" /><circle id="p2" class="none"/></svg>';
        $placestore = ['mapid' => 42];
        $map = new svgmap($svg, $placestore);
        $map->set_waygone('p1');
        $this->assertStringContainsString('learningmap-waygone', $map->get_attribute('p1', 'class'));
        $map->set_waygone('p2');
        $this->assertStringContainsString('learningmap-waygone', $map->get_attribute('p2', 'class'));
    }

    /**
     * Test add_checkmark method.
     *
     * @covers \mod_learningmap\svgmap::add_checkmark
     */
    public function test_add_checkmark(): void {
        $svg = '<svg><a id="parent"><circle id="p1" cx="10" cy="20" /></a></svg>';
        $placestore = ['mapid' => 42];
        $map = new svgmap($svg, $placestore);
        $map->add_checkmark('p1');
        $use = $map->get_element_by_id('parent')->childNodes->item(1);
        $this->assertEquals('use', $use->nodeName);
    }

    /**
     * Test get_coordinates method.
     * There are no tests for text elements right now as there are no reliable coordinates for them.
     *
     * @covers \mod_learningmap\svgmap::get_coordinates
     */
    public function test_get_coordinates(): void {
        $svg = '<svg>
        <g id="pathsGroup-42">
        <path id="p0_1" d="M 291 492 Q 94.31258368912239 499.0358621739614, 223 386" />
        </g>
        <g id="placesGroup">
        <circle id="p1" cx="10" cy="20" />
        </g>
        </svg>';
        $placestore = ['mapid' => 42, 'showtext' => true];
        $map = new svgmap($svg, $placestore);
        $coords = $map->get_coordinates();
        $this->assertEqualsCanonicalizing(
            [
                ['x' => 10, 'y' => 20],
                ['x' => 175, 'y' => 469],
            ],
            $coords
        );
    }

    /**
     * Test get_elements_by_classname method.
     *
     * @covers \mod_learningmap\svgmap::get_elements_by_classname
     */
    public function test_get_elements_by_classname(): void {
        $svg = '<svg>
        <circle id="p1" class="learningmap-place" />
        <circle id="p2" class="learningmap-place" />
        <text id="t1" class="learningmap-text" />
        </svg>';
        $placestore = ['mapid' => 42];
        $map = new svgmap($svg, $placestore);
        $elements = $map->get_elements_by_classname('learningmap-place');
        $this->assertCount(2, $elements);
    }

    /**
     * Test get_element_by_id method.
     *
     * @covers \mod_learningmap\svgmap::get_element_by_id
     */
    public function test_get_element_by_id(): void {
        $svg = '<svg><circle id="p1"/></svg>';
        $placestore = ['mapid' => 42];
        $map = new svgmap($svg, $placestore);
        $element = $map->get_element_by_id('p1');
        $this->assertEquals('p1', $element->getAttribute('id'));
    }

    /**
     * Test wrap_in_link method.
     *
     * @covers \mod_learningmap\svgmap::wrap_in_link
     */
    public function test_wrap_in_link(): void {
        $svg = '<svg><circle id="p1" class="learningmap-place" /></svg>';
        $placestore = ['mapid' => 42];
        $map = new svgmap($svg, $placestore);
        $map->wrap_in_link('p1', 'https://lernplattform.bycs.de');
        $link = $map->get_element_by_id('p1')->parentNode;
        $this->assertEquals('a', $link->nodeName);
        $this->assertEquals('https://lernplattform.bycs.de', $link->getAttribute('xlink:href'));
    }

    /**
     * Test wrap_items_in_links method.
     *
     * @covers \mod_learningmap\svgmap::wrap_items_in_links
     */
    public function test_wrap_items_in_links(): void {
        $svg = '<svg><circle id="p1" class="learningmap-place" /><circle id="p2" class="learningmap-place" /></svg>';
        $placestore = ['mapid' => 42];
        $map = new svgmap($svg, $placestore);
        $map->wrap_items_in_links('learningmap-place', 'https://lernplattform.bycs.de');
        $links = $map->get_elements_by_classname('learningmap-place');
        foreach ($links as $el) {
            $this->assertEquals('a', $el->parentNode->nodeName);
            $this->assertEquals('https://lernplattform.bycs.de', $el->parentNode->getAttribute('xlink:href'));
        }
    }

    /**
     * Test remove_elements_by_classname method.
     *
     * @covers \mod_learningmap\svgmap::remove_elements_by_classname
     */
    public function test_remove_elements_by_classname(): void {
        $svg = '<svg><circle id="p1" class="learningmap-place" /><circle id="p2" class="learningmap-place" /></svg>';
        $placestore = ['mapid' => 42];
        $map = new svgmap($svg, $placestore);
        $map->remove_elements_by_classname('learningmap-place');
        $this->assertEmpty($map->get_elements_by_classname('learningmap-place'));
    }

    /**
     * Test escape_content method.
     *
     * @covers \mod_learningmap\svgmap::escape_content
     */
    public function test_escape_content(): void {
        $escaped = svgmap::escape_content('<test>"&');
        $this->assertEquals('&lt;test&gt;&quot;&amp;', $escaped);
    }

    /**
     * Test fix_svg method.
     *
     * @covers \mod_learningmap\svgmap::fix_svg
     */
    public function test_fix_svg(): void {
        $svg = '<svg><g id="placesGroup"></g><g id="pathsGroup"></g><g id="backgroundGroup"></g></svg>';
        $placestore = ['mapid' => 42];
        $svgmap = new svgmap($svg, $placestore);
        $svgmap->fix_svg();
        $this->assertNotNull($svgmap->get_element_by_id('learningmap-svgmap-42'));
        $this->assertNotNull($svgmap->get_element_by_id('placesGroup-42'));
        $this->assertNotNull($svgmap->get_element_by_id('pathsGroup-42'));
        $this->assertNotNull($svgmap->get_element_by_id('backgroundGroup-42'));
        $this->assertNotNull($svgmap->get_element_by_id('textGroup-42'));
    }
}

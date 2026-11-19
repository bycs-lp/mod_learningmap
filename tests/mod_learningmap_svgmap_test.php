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

use mod_learningmap\completion\custom_completion;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversFunction;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversClass(svgmap::class)]
#[CoversClass(mapworker::class)]
#[CoversClass(\mod_learningmap_mod_form::class)]
#[CoversFunction('learningmap_get_learningmap')]
/**
 * Unit tests for the sanitizing of the SVG code in mod_learningmap
 *
 * @package     mod_learningmap
 * @copyright   2026, ISB Bayern
 * @author      Thomas Schönlein
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers      \mod_learningmap\svgmap
 * @covers      \mod_learningmap\mapworker
 * @covers      \mod_learningmap_mod_form
 * @covers      ::learningmap_get_learningmap
 */
final class mod_learningmap_svgmap_test extends \advanced_testcase {
    /**
     * Opening tag of the SVG root used in the tests
     * @var string
     */
    const SVG_OPEN = '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" id="map">';

    /**
     * Previous state of libxml_use_internal_errors()
     * @var bool
     */
    protected bool $previouslibxmlerrors;

    /**
     * Collect libxml errors instead of emitting warnings (some tests use malformed XML on purpose).
     */
    public function setUp(): void {
        parent::setUp();
        $this->previouslibxmlerrors = libxml_use_internal_errors(true);
    }

    /**
     * Restore libxml error handling.
     */
    public function tearDown(): void {
        libxml_clear_errors();
        libxml_use_internal_errors($this->previouslibxmlerrors);
        parent::tearDown();
    }

    /**
     * Wraps content in an SVG root element.
     *
     * @param string $content
     * @return string
     */
    protected function wrap(string $content): string {
        return self::SVG_OPEN . $content . '</svg>';
    }

    /**
     * Parses SVG code for assertions.
     *
     * @param string $svgcode
     * @return \DOMXPath
     */
    protected function parse(string $svgcode): \DOMXPath {
        $dom = new \DOMDocument();
        $dom->loadXML($svgcode);
        return new \DOMXPath($dom);
    }

    /**
     * Returns the names of all elements in lower case.
     *
     * @param \DOMXPath $xpath
     * @return array
     */
    protected function get_element_names(\DOMXPath $xpath): array {
        $names = [];
        foreach ($xpath->query('//*') as $element) {
            $names[] = strtolower($element->localName);
        }
        return $names;
    }

    /**
     * Returns the test fixture with injected unsafe content.
     *
     * @return string
     */
    protected function get_unsafe_fixture(): string {
        global $CFG;
        $fixture = file_get_contents($CFG->dirroot . '/mod/learningmap/tests/generator/test.svg');
        $unsafe = '<image id="injected" xlink:href="broken.png" onerror="marker()"/>' .
            '<script>marker()</script>' .
            '<TITLE><style><!--</style><g id="hidden"/>--></style></TITLE>';
        $position = strrpos($fixture, '</svg>');
        return substr($fixture, 0, $position) . $unsafe . substr($fixture, $position);
    }

    /**
     * Asserts that the SVG code contains no unsafe content of get_unsafe_fixture().
     *
     * @param string $code
     * @return void
     */
    protected function assert_safe_output(string $code): void {
        $lowercode = strtolower($code);
        $this->assertStringNotContainsString('onerror', $lowercode);
        $this->assertStringNotContainsString('<script', $lowercode);
        $this->assertStringNotContainsString('<!--', $code);
        $this->assertStringNotContainsString('id="hidden"', $code);
    }

    /**
     * Creates a course with a learning map.
     *
     * @return array course, learningmap record and cm_info
     */
    protected function create_learningmap(): array {
        global $DB;
        $course = $this->getDataGenerator()->create_course(['enablecompletion' => 1]);
        $learningmap = $this->getDataGenerator()->create_module('learningmap', [
            'course' => $course,
            'completion' => COMPLETION_TRACKING_AUTOMATIC,
            'completiontype' => custom_completion::NOCOMPLETION,
        ]);
        // The placestore of the generator contains spare course module IDs 9999x, replacing them by real ones.
        for ($i = 0; $i < 9; $i++) {
            $page = $this->getDataGenerator()->create_module('page', ['course' => $course]);
            $learningmap->placestore = str_replace(99990 + $i, $page->cmid, $learningmap->placestore);
        }
        $DB->set_field('learningmap', 'placestore', $learningmap->placestore, ['id' => $learningmap->id]);
        $cm = get_fast_modinfo($course)->get_cm($learningmap->cmid);
        return [$course, $learningmap, $cm];
    }

    /**
     * Creates the editing form for an existing learning map.
     *
     * @param \cm_info $cm
     * @param \stdClass $course
     * @return \mod_learningmap_mod_form
     */
    protected function create_form(\cm_info $cm, \stdClass $course): \mod_learningmap_mod_form {
        global $CFG, $PAGE;
        require_once($CFG->dirroot . '/course/modlib.php');
        require_once($CFG->dirroot . '/mod/learningmap/mod_form.php');
        $PAGE->set_course($course);
        $PAGE->set_url('/course/modedit.php', ['update' => $cm->id]);
        $cmrecord = get_coursemodule_from_id('', $cm->id, 0, false, MUST_EXIST);
        [$cmrecord, $context, $module, $data, $cw] = get_moduleinfo_data($cmrecord, $course);
        return new \mod_learningmap_mod_form($data, $cw->section, $cmrecord, $course);
    }

    /**
     * Data provider for test_denied_elements. Element names are listed explicitly (not taken from the constant).
     *
     * @return array
     */
    public static function denied_elements_provider(): array {
        $names = [
            'script', 'SCRIPT', 'Script', 'handler', 'listener',
            'foreignObject', 'ForeignObject', 'foreignobject', 'math',
            'iframe', 'frame', 'frameset', 'embed', 'object', 'applet',
            'base', 'link', 'meta',
            'animate', 'animateColor', 'animateMotion', 'animateTransform', 'set',
            'b', 'big', 'blockquote', 'body', 'br', 'center', 'code', 'dd', 'div', 'dl', 'dt', 'em', 'font',
            'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'head', 'hr', 'i', 'img', 'IMG', 'li', 'listing', 'menu', 'nobr',
            'ol', 'p', 'pre', 'ruby', 's', 'small', 'span', 'strike', 'strong', 'sub', 'sup', 'table', 'tt', 'u',
            'ul', 'var',
        ];
        $cases = [];
        foreach ($names as $name) {
            $cases[$name] = ['<' . $name . ' class="marker">content</' . $name . '>', strtolower($name)];
        }
        $cases['prefixed script in SVG namespace'] = [
            '<s:script xmlns:s="http://www.w3.org/2000/svg">content</s:script>',
            'script',
        ];
        $cases['nested in group'] = ['<g id="outer"><script>content</script></g>', 'script'];
        return $cases;
    }

    #[DataProvider('denied_elements_provider')]
    /**
     * Tests that denied elements are removed and other elements are kept.
     *
     * @param string $markup
     * @param string $deniedname
     * @return void
     * @dataProvider denied_elements_provider
     */
    public function test_denied_elements(string $markup, string $deniedname): void {
        $svgmap = new svgmap($this->wrap('<g id="keep"/>' . $markup), []);
        $names = $this->get_element_names($this->parse($svgmap->get_svgcode()));
        $this->assertNotContains($deniedname, $names);
        $this->assertContains('g', $names);
        $this->assertTrue($svgmap->has_valid_root());
    }

    /**
     * Tests that doctype, processing instructions and comments are removed.
     *
     * @return void
     */
    public function test_removed_node_types(): void {
        $svgcode = '<!DOCTYPE svg [<!ENTITY marker "marker">]>' .
            '<?marker instruction?>' .
            '<!-- top level comment -->' .
            $this->wrap(
                '<style><!-- comment in style --></style>' .
                '<g id="keep"><!-- comment --><?inner instruction?></g>'
            );
        $svgmap = new svgmap($svgcode, []);
        $result = $svgmap->get_svgcode();
        $xpath = $this->parse($result);
        $this->assertNull($xpath->document->doctype);
        $this->assertEquals(0, $xpath->query('//comment()')->length);
        $this->assertEquals(0, $xpath->query('//processing-instruction()')->length);
        $this->assertStringNotContainsString('ENTITY', $result);
        $this->assertEquals(1, $xpath->query('//*[@id="keep"]')->length);
    }

    /**
     * Data provider for test_title_desc.
     *
     * @return array
     */
    public static function title_desc_provider(): array {
        return [
            'empty title stays empty' => ['<title id="t"></title>', '', false],
            'empty self closing desc' => ['<desc id="t"/>', '', false],
            'single text node stays' => ['<title id="t">Place 1</title>', 'Place 1', false],
            'single cdata stays' => ['<title id="t"><![CDATA[Place & <b>1</b>]]></title>', 'Place & <b>1</b>', false],
            'text and cdata stay' => ['<title id="t">a<![CDATA[<b>]]></title>', 'a<b>', false],
            'element child is unwrapped' => ['<title id="t">a<tspan>b</tspan>c</title>', 'abc', true],
            'nested elements are unwrapped' => ['<title id="t">a<tspan>x &amp; <tspan>y</tspan></tspan>z</title>', 'ax & yz', true],
            'cdata in child element is kept' => ['<title id="t">a<tspan><![CDATA[<b>]]></tspan></title>', 'a<b>', true],
            'comment child is removed' => ['<desc id="t">a<!-- x -->b</desc>', 'ab', true],
            'empty element child is unwrapped' => ['<title id="t"><tspan></tspan></title>', '', true],
            'uppercase TITLE' => ['<TITLE id="t">a<tspan>b</tspan></TITLE>', 'ab', true],
            'mixed case Desc' => ['<Desc id="t"><style>a</style></Desc>', 'a', true],
            'denied element inside is removed completely' => ['<title id="t">a<script>b</script>c</title>', 'ac', true],
        ];
    }

    #[DataProvider('title_desc_provider')]
    /**
     * Tests that title and desc contain no elements, text and CDATA of unwrapped elements are kept.
     *
     * @param string $markup
     * @param string $expectedtext Expected text content
     * @param bool $expectchange Whether the SVG code is expected to change
     * @return void
     * @dataProvider title_desc_provider
     */
    public function test_title_desc(string $markup, string $expectedtext, bool $expectchange): void {
        $svgcode = $this->wrap($markup);
        $svgmap = new svgmap($svgcode, []);
        $result = $svgmap->get_svgcode();
        $xpath = $this->parse($result);
        $element = $xpath->query('//*[@id="t"]')->item(0);
        $this->assertNotNull($element);
        $this->assertEquals(0, $xpath->query('//*[@id="t"]/*')->length);
        $this->assertEquals($expectedtext, $element->textContent);
        if ($expectchange) {
            $this->assertNotEquals($svgcode, $result);
        } else {
            $this->assertEquals($svgcode, $result);
        }
    }

    /**
     * Data provider for test_title_desc_pipeline. Expected texts are the output of the pipeline before the fix.
     *
     * @return array
     */
    public static function title_desc_pipeline_provider(): array {
        return [
            'mixed text and cdata' => ["<title id=\"t\">before\n<![CDATA[A &amp; B &#65; &lt;]]></title>", "before\nA & B A <"],
            'cdata in nested element' => ["<title id=\"t\">a\n<tspan><![CDATA[x &amp; y]]></tspan></title>", "a\nx & y"],
            'desc with quotes in cdata' => [
                "<desc id=\"t\">a\n<![CDATA[&quot;q&quot; &apos;s&apos; &gt;]]> b</desc>",
                "a\n\"q\" 's' > b",
            ],
            'markup in cdata stays text' => ["<title id=\"t\">a\n<![CDATA[<b>x</b> & y]]></title>", "a\n<b>x</b> & y"],
            'single cdata' => ['<title id="t"><![CDATA[A &amp; B &#65;]]></title>', 'A & B A'],
            'plain text' => ['<title id="t">A &amp; B</title>', 'A & B'],
        ];
    }

    #[DataProvider('title_desc_pipeline_provider')]
    /**
     * Tests that the text of title and desc is shown as before through the complete mapworker pipeline.
     *
     * @param string $markup
     * @param string $expectedtext
     * @return void
     * @dataProvider title_desc_pipeline_provider
     */
    public function test_title_desc_pipeline(string $markup, string $expectedtext): void {
        $this->resetAfterTest();
        $mapworker = new mapworker($this->wrap($markup), []);
        $xpath = $this->parse($mapworker->get_svgcode());
        $element = $xpath->query('//*[@id="t"]')->item(0);
        $this->assertNotNull($element);
        $this->assertEquals($expectedtext, $element->textContent);
        $this->assertEquals(0, $xpath->query('//*[@id="t"]/*')->length);
    }

    /**
     * Tests that a second sanitizing run does not change an already sanitized document.
     *
     * @return void
     */
    public function test_sanitizing_is_idempotent(): void {
        $svgmap = new svgmap($this->wrap('<title>a<tspan>b</tspan></title><g onclick="marker()"/>'), []);
        $this->assertFalse($svgmap->sanitize_dom());
    }

    /**
     * Data provider for test_event_handlers.
     *
     * @return array
     */
    public static function event_handler_provider(): array {
        return [
            'onload' => ['onload="marker()"'],
            'ONLOAD' => ['ONLOAD="marker()"'],
            'OnError' => ['OnError="marker()"'],
            'onclick' => ['onclick="marker()"'],
            'prefixed onclick' => ['xmlns:x="urn:marker" x:onclick="marker()"'],
            'prefix starting with on' => ['xmlns:onx="urn:marker" onx:attribute="marker()"'],
        ];
    }

    #[DataProvider('event_handler_provider')]
    /**
     * Tests that event handler attributes are removed and other attributes are kept.
     *
     * @param string $attribute
     * @return void
     * @dataProvider event_handler_provider
     */
    public function test_event_handlers(string $attribute): void {
        $svgmap = new svgmap($this->wrap('<path id="target" class="c" d="M 0 0 L 1 1" ' . $attribute . '/>'), []);
        $element = $this->parse($svgmap->get_svgcode())->query('//*[@id="target"]')->item(0);
        $names = [];
        foreach ($element->attributes as $attr) {
            $names[] = $attr->nodeName;
        }
        sort($names);
        $this->assertEquals(['class', 'd', 'id'], $names);
    }

    /**
     * Data provider for test_url_attributes. Attribute names are listed explicitly (not taken from the constant).
     *
     * @return array
     */
    public static function url_attribute_provider(): array {
        $alias = 'xmlns:alias="http://www.w3.org/1999/xlink" ';
        return [
            // Completeness: one blocked case per URL attribute.
            'href blocked' => ['href="javascript:void(0)"', false],
            'src blocked' => ['src="javascript:void(0)"', false],
            'data blocked' => ['data="javascript:void(0)"', false],
            'action blocked' => ['action="javascript:void(0)"', false],
            'formaction blocked' => ['formaction="javascript:void(0)"', false],
            'from blocked' => ['from="javascript:void(0)"', false],
            'to blocked' => ['to="javascript:void(0)"', false],
            'values blocked' => ['values="javascript:void(0)"', false],
            // Prefix, namespace and case of the attribute name.
            'xlink:href blocked' => ['xlink:href="javascript:void(0)"', false],
            'alias prefix for xlink blocked' => [$alias . 'alias:href="javascript:void(0)"', false],
            'alias prefix for xlink allowed' => [$alias . 'alias:href="https://example.org/bg.png"', true],
            'xml:base blocked' => ['xml:base="javascript:void(0)"', false],
            'xml:base allowed' => ['xml:base="https://example.org/"', true],
            'HREF blocked' => ['HREF="javascript:void(0)"', false],
            'Src blocked' => ['Src="javascript:void(0)"', false],
            // Normalization of the value.
            'uppercase scheme' => ['href="JAVASCRIPT:void(0)"', false],
            'mixed case scheme' => ['xlink:href="JavaScript:void(0)"', false],
            'leading space' => ['href=" javascript:void(0)"', false],
            'raw tab in scheme' => ["href=\"java\tscript:void(0)\"", false],
            'raw newline in scheme' => ["xlink:href=\"java\nscript:void(0)\"", false],
            'tab entity in scheme' => ['href="java&#9;script:void(0)"', false],
            'newline entity before scheme' => ['xlink:href="&#10;javascript:void(0)"', false],
            'carriage return entity in scheme' => ['href="java&#13;script:void(0)"', false],
            'other scheme' => ['href="vbscript:marker"', false],
            // Data URLs.
            'data svg' => ['xlink:href="data:image/svg+xml,%3Csvg/%3E"', false],
            'data svg base64' => ['xlink:href="data:image/svg+xml;base64,PHN2Zy8+"', false],
            'data svg uppercase' => ['xlink:href="DATA:IMAGE/SVG+XML,%3Csvg/%3E"', false],
            'data html' => ['href="data:text/html,marker"', false],
            'data png' => ['xlink:href="data:image/png;base64,iVBORw0KGgo="', true],
            'data jpeg' => ['xlink:href="data:image/jpeg;base64,/9j/4AAQ"', true],
            'data gif' => ['xlink:href="data:image/gif;base64,R0lGODlh"', true],
            'data webp' => ['xlink:href="data:image/webp;base64,UklGRg=="', true],
            // Allowed URLs.
            'pluginfile placeholder' => ['xlink:href="@@PLUGINFILE@@/bg.png"', true],
            'pluginfile url' => [
                'xlink:href="https://example.org/pluginfile.php/1/mod_learningmap/background/0/bg.png"',
                true,
            ],
            'fragment' => ['xlink:href="#checkmark"', true],
            'relative' => ['href="bg.png"', true],
            'relative with colon in path' => ['href="dir/a:b.png"', true],
            'http' => ['href="http://example.org/"', true],
            'https' => ['href="https://example.org/"', true],
            'scheme-relative' => ['href="//example.org/bg.png"', true],
            'scheme-relative xlink' => ['xlink:href="//example.org/bg.png"', true],
            'numeric values' => ['values="1 0 0 0 0"', true],
        ];
    }

    #[DataProvider('url_attribute_provider')]
    /**
     * Tests the URL check of URL attributes.
     *
     * @param string $attribute
     * @param bool $kept Whether the attribute is expected to be kept
     * @return void
     * @dataProvider url_attribute_provider
     */
    public function test_url_attributes(string $attribute, bool $kept): void {
        $svgcode = $this->wrap('<image id="target" ' . $attribute . '/>');
        $svgmap = new svgmap($svgcode, []);
        $result = $svgmap->get_svgcode();
        $element = $this->parse($result)->query('//*[@id="target"]')->item(0);
        $this->assertNotNull($element);
        if ($kept) {
            $this->assertEquals(2, $element->attributes->length);
            $this->assertEquals($svgcode, $result);
        } else {
            $this->assertEquals(1, $element->attributes->length);
        }
    }

    /**
     * Tests that malformed code leads to an empty map and that following operations do not fail.
     *
     * @return void
     */
    public function test_fail_closed(): void {
        $this->resetAfterTest();
        $svgmap = new svgmap($this->wrap('<g><image onload="marker()"/>'), []);
        $this->assertFalse($svgmap->has_valid_root());
        $this->assertEquals('', $svgmap->get_svgcode());
        $svgmap->replace_stylesheet();
        $svgmap->replace_cdata();
        $svgmap->save_svg_data();
        $svgmap->load_dom();
        $this->assertFalse($svgmap->has_valid_root());
        $this->assertStringNotContainsString('<svg', $svgmap->get_svgcode());

        $mapworker = new mapworker($this->wrap('<g><image onload="marker()"/>'), []);
        $mapworker->replace_stylesheet();
        $this->assertStringNotContainsString('<svg', $mapworker->get_svgcode());
        $this->assertFalse($mapworker->has_valid_root());
    }

    /**
     * Data provider for test_has_valid_root.
     *
     * @return array
     */
    public static function root_provider(): array {
        return [
            'empty' => ['', false],
            'only whitespace' => ["\n  ", false],
            'only xml declaration' => ['<?xml version="1.0"?>', false],
            'svgjunk element' => ['<svgjunk xmlns="http://www.w3.org/2000/svg"/>', false],
            'wrapper with nested svg' => [
                '<g xmlns="http://www.w3.org/2000/svg"><svg xmlns="http://www.w3.org/2000/svg"/></g>',
                false,
            ],
            'svg without namespace' => ['<svg/>', false],
            'svg with wrong namespace' => ['<svg xmlns="urn:marker"/>', false],
            'prefixed svg root' => ['<s:svg xmlns:s="http://www.w3.org/2000/svg"/>', false],
            'uppercase SVG root' => ['<SVG xmlns="http://www.w3.org/2000/svg"/>', false],
            'forbidden control character' => [
                '<svg xmlns="http://www.w3.org/2000/svg"><image href="&#1;javascript:void(0)"/></svg>',
                false,
            ],
            'valid root' => ['<svg xmlns="http://www.w3.org/2000/svg"/>', true],
            'valid root with declaration' => ['<?xml version="1.0"?><svg xmlns="http://www.w3.org/2000/svg"/>', true],
        ];
    }

    #[DataProvider('root_provider')]
    /**
     * Tests the structural check of the SVG root.
     *
     * @param string $svgcode
     * @param bool $expected
     * @return void
     * @dataProvider root_provider
     */
    public function test_has_valid_root(string $svgcode, bool $expected): void {
        $svgmap = new svgmap($svgcode, []);
        $this->assertEquals($expected, $svgmap->has_valid_root());
    }

    /**
     * Tests that legitimate maps (test fixture and fresh skeleton) are not changed.
     *
     * @return void
     */
    public function test_legitimate_maps_unchanged(): void {
        global $CFG, $OUTPUT;
        $fixture = file_get_contents($CFG->dirroot . '/mod/learningmap/tests/generator/test.svg');
        $svgmap = new svgmap($fixture, []);
        $this->assertEquals($fixture, $svgmap->get_svgcode());
        $this->assertTrue($svgmap->has_valid_root());
        $this->assertFalse($svgmap->sanitize_dom());

        $skeleton = $OUTPUT->render_from_template('mod_learningmap/svgskeleton', ['editmode' => true, 'mapid' => 'abc']);
        $svgmap = new svgmap($skeleton, []);
        $this->assertEquals($skeleton, $svgmap->get_svgcode());
        $this->assertTrue($svgmap->has_valid_root());
    }

    /**
     * Tests that unsafe content already stored in the database is removed when rendering the map, when rendering
     * a legacy map from the intro and when preparing the editing form defaults (data_preprocessing()).
     *
     * @return void
     */
    public function test_existing_data(): void {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();
        [$course, $learningmap, $cm] = $this->create_learningmap();
        $unsafe = $this->get_unsafe_fixture();

        $DB->set_field('learningmap', 'svgcode', $unsafe, ['id' => $learningmap->id]);
        $output = learningmap_get_learningmap($cm);
        $this->assertStringContainsString('<svg', $output);
        $this->assert_safe_output($output);

        $placestore = json_decode($learningmap->placestore, true);
        $mapworker = new mapworker($unsafe, $placestore, $cm, true);
        $mapworker->process_map_objects();
        $mapworker->replace_stylesheet();
        $code = $mapworker->get_svgcode();
        $this->assertTrue($mapworker->has_valid_root());
        $this->assert_safe_output($code);
        $this->assertStringContainsString('id="injected"', $code);

        $DB->set_field('learningmap', 'svgcode', '', ['id' => $learningmap->id]);
        $DB->set_field('learningmap', 'intro', $unsafe, ['id' => $learningmap->id]);
        $output = learningmap_get_learningmap($cm);
        $this->assertStringContainsString('<svg', $output);
        $this->assert_safe_output($output);
    }

    /**
     * Tests that data_postprocessing() stores sanitized code and does not save a map that cannot be processed.
     *
     * @return void
     */
    public function test_data_postprocessing(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        [$course, $learningmap, $cm] = $this->create_learningmap();
        $form = $this->create_form($cm, $course);

        $data = new \stdClass();
        $data->svgcode = $this->get_unsafe_fixture();
        $data->placestore = json_encode(['mapid' => 'abc']);
        $data->backgroundfile = 0;
        $form->data_postprocessing($data);
        $this->assertStringContainsString('<svg', $data->svgcode);
        $this->assert_safe_output($data->svgcode);

        // Valid XML that is broken by the greedy regex in replace_stylesheet().
        $data = new \stdClass();
        $data->svgcode = $this->wrap('<style>a</style><text x="1" y="1">a style> b</text>');
        $data->placestore = json_encode(['mapid' => 'abc']);
        $data->backgroundfile = 0;
        $this->expectException(\moodle_exception::class);
        $this->expectExceptionMessage(get_string('invaliddata', 'error'));
        $form->data_postprocessing($data);
    }
}

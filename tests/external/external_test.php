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

namespace mod_learningmap\external;

/**
 * Tests for the web service functions of mod_learningmap.
 *
 * @package    mod_learningmap
 * @copyright  2026 ISB Bayern
 * @author     Dr. Peter Mayer
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class external_test extends \advanced_testcase {
    #[\PHPUnit\Framework\Attributes\Group('baseline')]
    #[\PHPUnit\Framework\Attributes\RunInSeparateProcess]
    /**
     * The web services only accept course modules of type learningmap.
     *
     * @covers \mod_learningmap\external\get_learningmap::execute
     * @covers \mod_learningmap\external\get_dependingmodules::execute
     * @runInSeparateProcess
     */
    public function test_execute_rejects_other_module_types(): void {
        global $CFG, $DB;
        $this->resetAfterTest();
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $student = $generator->create_and_enrol($course, 'student');
        $page = $generator->create_module('page', ['course' => $course->id]);
        // The own map needs no places, its rendering is not under test.
        $placestore = json_decode(file_get_contents($CFG->dirroot . '/mod/learningmap/tests/generator/test.json'), true);
        $placestore = array_merge($placestore, ['places' => [], 'paths' => [], 'startingplaces' => [], 'targetplaces' => []]);
        $ownmap = $generator->create_module('learningmap', ['course' => $course->id, 'placestore' => json_encode($placestore)]);

        // A map in a foreign course whose instance id equals the id of the page.
        $othercourse = $generator->create_course();
        $othermap = $generator->create_module('learningmap', ['course' => $othercourse->id]);
        $DB->set_field('learningmap', 'id', $page->id, ['id' => $othermap->id]);
        $DB->set_field('course_modules', 'instance', $page->id, ['id' => $othermap->cmid]);
        rebuild_course_cache($othercourse->id, true);

        $this->setUser($student);
        $functions = [get_learningmap::class, get_dependingmodules::class];
        foreach ($functions as $function) {
            try {
                $function::execute($page->cmid);
                $this->fail('Expected moodle_exception for ' . $function);
            } catch (\moodle_exception $e) {
                $this->assertSame('invalidcoursemoduleid', $e->errorcode);
            }
        }

        $this->assertNotEmpty(get_learningmap::execute($ownmap->cmid)['content']);
        $this->assertIsArray(get_dependingmodules::execute($ownmap->cmid));
    }
}

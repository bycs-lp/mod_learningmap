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

/**
 * Tests for Learning map helper class
 *
 * @package    mod_learningmap
 * @category   test
 * @copyright  2025 ISB Bayern
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\PHPUnit\Framework\Attributes\CoversClass(helper::class)]
#[\PHPUnit\Framework\Attributes\CoversMethod(helper::class, 'repair_learningmap_record')]
#[\PHPUnit\Framework\Attributes\CoversMethod(helper::class, 'render_activity_header_for_modal')]
final class mod_learningmap_helper_test extends \advanced_testcase {
    /**
     * Tests the repair_learningmap_record method.
     *
     * @return void
     */
    public function test_repair_learningmap_record(): void {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $learningmapid = $this->getDataGenerator()->create_module('learningmap', ['course' => $course->id])->id;

        $recordbefore = $DB->get_record('learningmap', ['id' => $learningmapid], '*', MUST_EXIST);
        helper::repair_learningmap_record($learningmapid);
        $recordafter = $DB->get_record('learningmap', ['id' => $learningmapid], '*', MUST_EXIST);
        $this->assertEquals($recordbefore->course, $recordafter->course, 'The learning map record should not have changed.');

        $DB->update_record('learningmap', ['id' => $learningmapid, 'course' => -1]);
        helper::repair_learningmap_record($learningmapid);
        $recordafter = $DB->get_record('learningmap', ['id' => $learningmapid], '*', MUST_EXIST);
        $this->assertEquals(
            $course->id,
            $recordafter->course,
            'The course id should have been updated in the learning map record.'
        );

        $DB->delete_records('course', ['id' => $course->id]);
        helper::repair_learningmap_record($learningmapid);
        $recordafter = $DB->get_record('learningmap', ['id' => $learningmapid], '*', MUST_EXIST);
        $this->assertEquals(
            $recordbefore->course,
            $recordafter->course,
            'The learning map record should not have changed after trying to repair it with a non-existing course.'
        );
    }

    /**
     * Tests that the manual completion button is present in rendered modal header HTML.
     *
     * There have been changes to the placement of the completion information/manual completion button
     * across < Mooodle 5.2, Moodle 5.2 and Moodle > 5.2, so this test should make sure there's always
     * a completion button for all supported moodle versions.
     */
    public function test_render_activity_header_for_modal_manual_completion_button_presence(): void {
        global $PAGE, $OUTPUT;

        $this->resetAfterTest();
        set_config('enablecompletion', 1);

        $course = $this->getDataGenerator()->create_course(['enablecompletion' => 1]);
        $user = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($user->id, $course->id, 'student');
        $this->setUser($user);

        $label = $this->getDataGenerator()->create_module('label', [
            'course' => $course->id,
            'completion' => COMPLETION_TRACKING_MANUAL,
        ]);

        $modinfo = get_fast_modinfo($course);
        $cminfo = $modinfo->get_cm($label->cmid);
        $context = \context_module::instance($cminfo->id);

        $PAGE->set_url(new \moodle_url('/mod/label/view.php', ['id' => $cminfo->id]));
        $PAGE->set_context($context);
        $PAGE->set_cm($cminfo, $course);
        $PAGE->set_pagelayout('embedded');

        $activityheaderdata = $PAGE->activityheader->export_for_template($OUTPUT);
        $html = helper::render_activity_header_for_modal($activityheaderdata);

        $this->assertStringContainsString('data-action="toggle-manual-completion"', $html);
    }
}

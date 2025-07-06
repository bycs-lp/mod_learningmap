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

use mod_learningmap\completion\custom_completion;
use mod_learningmap\mapworker;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/course/moodleform_mod.php');
require_once($CFG->dirroot . '/mod/learningmap/lib.php');

/**
 * Editing form for mod_learningmap
 *
 * @package     mod_learningmap
 * @copyright 2021-2024, ISB Bayern
 * @author      Stefan Hanauska <stefan.hanauska@csg-in.de>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_learningmap_mod_form extends moodleform_mod {
    /**
     * Defines the editing form for mod_learningmap
     *
     * @return void
     */
    public function definition(): void {
        global $CFG, $OUTPUT;

        $mform = &$this->_form;

        $cm = get_fast_modinfo($this->current->course);

        $s = [];
        $activitysel = [];
        // Gets only sections with content.
        foreach ($cm->get_sections() as $sectionnum => $section) {
            $sectioninfo = $cm->get_section_info($sectionnum);
            $s['name'] = $sectioninfo->name;
            if (empty($s['name'])) {
                $s['name'] = get_string('section') . ' ' . $sectionnum;
            }
            $s['coursemodules'] = [];
            foreach ($section as $cmid) {
                $module = $cm->get_cm($cmid);
                if ($CFG->branch >= 500 && !plugin_supports('mod', $module->modname, FEATURE_CAN_DISPLAY, true)) {
                    continue;
                }
                // Get only course modules which are not deleted.
                if ($module->deletioninprogress == 0) {
                    $s['coursemodules'][] = [
                        'id' => $cmid,
                        'name' => s($module->name),
                        'completionenabled' => $module->completion > 0,
                        'hidden' => $module->visible == 0,
                    ];
                }
            }
            $activitysel[] = $s;
        }

        $mform->addElement('header', 'general', get_string('general', 'form'));

        $mform->addElement('text', 'name', get_string('name', 'learningmap'), ['size' => '64']);
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addHelpButton('name', 'name', 'learningmap');

        $this->standard_intro_elements();

        $mform->addElement(
            'html',
            $OUTPUT->render_from_template(
                'mod_learningmap/inlinehelp',
                ['usecaselink' => get_config('mod_learningmap', 'usecaselink')]
            )
        );

        $features = [];
        foreach (LEARNINGMAP_FEATURES as $feature) {
            $features[] = [
                'name' => $feature,
                'title' => get_string($feature, 'learningmap'),
                'text' => get_string($feature . '_help', 'learningmap'),
                'alt' => get_string('help'),
            ];
        }
        $mform->addElement(
            'html',
            $OUTPUT->render_from_template(
                'mod_learningmap/formitem',
                ['sections' => $activitysel,
                'help' => $OUTPUT->help_icon('intro', 'learningmap', ''),
                'completiondisabled' => $cm->get_course()->enablecompletion == 0,
                'features' => $features,
                ]
            )
        );

        $learningmapformat = $this->_course->format === 'learningmap';

        // If using learningmap course format, the map is never shown on the course page.
        if ($learningmapformat) {
            $mform->addElement('hidden', 'showmaponcoursepage', 0);
        } else {
            $mform->addElement('advcheckbox', 'showmaponcoursepage', get_string('showmaponcoursepage', 'learningmap'));
            $mform->addHelpButton('showmaponcoursepage', 'showmaponcoursepage', 'learningmap');
        }

        $mform->setType('showmaponcoursepage', PARAM_INT);

        $backlinkallowed = get_config('mod_learningmap', 'backlinkallowed');

        if ($backlinkallowed) {
            $mform->addElement('advcheckbox', 'backlink', get_string('showbacklink', 'learningmap'));
            $mform->setType('backlink', PARAM_INT);
            $mform->addHelpButton('backlink', 'showbacklink', 'learningmap');
            $mform->setDefault('backlink', $learningmapformat);
        } else {
            $mform->addElement('hidden', 'backlink', 0);
        }

        $mform->addElement(
            'filemanager',
            'backgroundfile',
            get_string('backgroundfile', 'learningmap'),
            null,
            [
                'accepted_types' => 'web_image',
                'maxfiles' => 1,
                'subdirs' => 0,
            ]
        );
        $mform->addRule('backgroundfile', null, 'required', null, 'client');
        $mform->addHelpButton('backgroundfile', 'backgroundfile', 'learningmap');

        $mform->addElement('textarea', 'svgcode', get_string('svgcode', 'learningmap'));
        $mform->setType('svgcode', PARAM_RAW);

        $mform->addElement('hidden', 'placestore');
        $mform->setType('placestore', PARAM_RAW);

        $mform->closeHeaderBefore('header');

        $this->standard_coursemodule_elements();

        $this->add_action_buttons(true, null, null);

        $mform->addHelpButton('groupmode', 'groupmode', 'learningmap');
    }

    /**
     * Remove visible groups here to avoid warning
     *
     * @return void
     */
    public function definition_after_data() {
        global $PAGE;
        $data = $this->get_data();
        // Only load the javascript for the map editor if the general part of the form is shown.
        if (empty($data->showonly) || $data->showonly == 'general') {
            $PAGE->requires->js_call_amd('mod_learningmap/learningmap', 'init');
        }
        $this->_form->_elements[$this->_form->_elementIndex['groupmode']]->removeOption(VISIBLEGROUPS);
        parent::definition_after_data();
    }

    /**
     * Returns whether the custom completion rules are enabled.
     *
     * @param array $data form data
     * @return bool
     */
    public function completion_rule_enabled($data): bool {
        return (!empty($data['completiontype' . $this->get_suffix()]));
    }

    /**
     * Adds the custom completion rules for mod_learningmap
     *
     * @return array
     */
    public function add_completion_rules(): array {
        $mform = $this->_form;

        $completionoptions = [
            custom_completion::NOCOMPLETION => get_string('nocompletion', 'learningmap'),
            custom_completion::COMPLETION_WITH_ONE_TARGET => get_string('completion_with_one_target', 'learningmap'),
            custom_completion::COMPLETION_WITH_ALL_TARGETS => get_string('completion_with_all_targets', 'learningmap'),
            custom_completion::COMPLETION_WITH_ALL_PLACES => get_string('completion_with_all_places', 'mod_learningmap'),
        ];

        $completiontype = 'completiontype' . $this->get_suffix();

        $mform->addElement(
            'select',
            $completiontype,
            get_string('completiontype', 'learningmap'),
            $completionoptions,
            []
        );

        $mform->setType($completiontype, PARAM_INT);
        $mform->hideIf($completiontype, 'completion', 'neq', COMPLETION_TRACKING_AUTOMATIC);

        return([$completiontype]);
    }

    /**
     * Processes the form data before loading the form. Adds the default values for empty forms, replaces the CSS
     * with the values for editing.
     *
     * @param array $defaultvalues
     * @return void
     */
    public function data_preprocessing(&$defaultvalues): void {
        global $OUTPUT;

        // Initialize a new learningmap instance.
        if (!$this->current->instance) {
            // Every map gets a unique id for applying CSS.
            $mapid = uniqid();
            // The editmode setting loads the CSS styles for the editor.
            $options = ['editmode' => true, 'mapid' => $mapid];
            // Loads the SVG template to the textarea for the introeditor.
            // The textarea is hidden in the browser.
            $defaultvalues['svgcode'] = $OUTPUT->render_from_template(
                'mod_learningmap/svgskeleton',
                $options
            );
            // Default behaviour is to act as a label. As the user can't see the module description on a view page,
            // the description is shown by default.
            $defaultvalues['showdescription'] = 1;
            $defaultvalues['showmaponcoursepage'] = 1;
            // Encodes the base settings as json. Further default settings are
            // generated by javascript to avoid duplicate code.
            $defaultvalues['placestore'] = json_encode($options);
        } else {
            $context = context_module::instance($defaultvalues['coursemodule']);

            if (empty($defaultvalues['svgcode'])) {
                $mapcode = $defaultvalues['intro'];
                $filearea = 'intro';
            } else {
                $mapcode = $defaultvalues['svgcode'];
                $filearea = 'background';
            }

            $defaultvalues['svgcode'] = file_rewrite_pluginfile_URLS(
                $mapcode,
                'pluginfile.php',
                $context->id,
                'mod_learningmap',
                $filearea,
                null
            );
            $modinfo = get_fast_modinfo($defaultvalues['course']);
            $cm = $modinfo->get_cm($defaultvalues['coursemodule']);
            // Replace the stylesheet for editing mode.
            $mapworker = new mapworker(
                $mapcode,
                json_decode($defaultvalues['placestore'], true),
                $cm,
                true
            );
            $mapworker->process_map_objects();
            $mapworker->replace_stylesheet();
            $defaultvalues['svgcode'] = $mapworker->get_svgcode();

            $draftitemid = file_get_submitted_draft_itemid('backgroundfile');

            $defaultvalues['svgcode'] = file_prepare_draft_area(
                $draftitemid,
                $context->id,
                'mod_learningmap',
                'background',
                0,
                ['subdirs' => 0, 'maxfiles' => 1],
                $defaultvalues['svgcode']
            );
            $defaultvalues['backgroundfile'] = $draftitemid;
        }
    }

    /**
     * Processes the form data after the form is submitted.
     * Replaces the CSS in the SVG with the parts suitable for output.
     *
     * @param stdClass $data
     * @return void
     */
    public function data_postprocessing($data): void {
        // Only change anything to the SVG code if the general part of the form is shown and
        // there is actual svgcode (which is not the case if the form is used for changing
        // default completion settings).
        if (!empty($data->svgcode) && (empty($data->showonly) || $data->showonly == 'general')) {
            $mapworker = new mapworker(
                $data->svgcode,
                json_decode($data->placestore, true)
            );
            $mapworker->replace_stylesheet();
            $data->svgcode = $mapworker->get_svgcode();

            $data->svgcode = file_rewrite_urls_to_pluginfile(
                $data->svgcode,
                $data->backgroundfile
            );
        }
        parent::data_postprocessing($data);
    }

    /**
     * Get the suffix to be added to the completion elements when creating them.
     * This acts as a spare for compatibility with Moodle 4.1 and 4.2.
     *
     * @return string The suffix
     */
    public function get_suffix(): string {
        if (method_exists(get_parent_class($this), 'get_suffix')) {
            return parent::get_suffix();
        }
        return '';
    }
}

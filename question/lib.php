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

/**
 * Question related functions.
 *
 * This file was created just because Fragment API expects callbacks to be defined on lib.php.
 *
 * Please, do not add new functions to this file.
 *
 * @package   core_question
 * @copyright 2018 Simey Lameze <simey@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Question tags fragment callback.
 *
 * @param array $args Arguments to the form.
 * @return null|string The rendered form.
 */
function core_question_output_fragment_tags_form($args) {

    if (!empty($args['id'])) {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/question/type/tags_form.php');
        require_once($CFG->libdir . '/questionlib.php');
        $id = clean_param($args['id'], PARAM_INT);
        $editingcontext = $args['context'];

        // Load the question and some related information.
        $question = $DB->get_record('question', ['id' => $id]);

        if ($coursecontext = $editingcontext->get_course_context(false)) {
            $course = $DB->get_record('course', ['id' => $coursecontext->instanceid]);
            $filtercourses = [$course];
        } else {
            $filtercourses = null;
        }

        $category = $DB->get_record('question_categories', ['id' => $question->category]);
        $questioncontext = \context::instance_by_id($category->contextid);
        $contexts = new \question_edit_contexts($editingcontext);

        // Load the question tags and filter the course tags by the current course.
        if (core_tag_tag::is_enabled('core_question', 'question')) {
            $tagobjectsbyquestion = core_tag_tag::get_items_tags('core_question', 'question', [$question->id]);
            if (!empty($tagobjectsbyquestion[$question->id])) {
                $tagobjects = $tagobjectsbyquestion[$question->id];
                $sortedtagobjects = question_sort_tags($tagobjects,
                        context::instance_by_id($category->contextid), $filtercourses);
            }
        }
        $formoptions = [
            'editingcontext' => $editingcontext,
            'questioncontext' => $questioncontext,
            'contexts' => $contexts->all()
        ];
        $data = [
            'id' => $question->id,
            'questioncategory' => $category->name,
            'questionname' => $question->name,
            'categoryid' => $category->id,
            'contextid' => $category->contextid,
            'context' => $questioncontext->get_context_name(),
            'tags' => $sortedtagobjects->tags ?? [],
            'coursetags' => $sortedtagobjects->coursetags ?? [],
        ];

        $cantag = question_has_capability_on($question, 'tag');
        $mform = new \core_question\form\tags(null, $formoptions, 'post', '', null, $cantag, $data);
        $mform->set_data($data);

        return $mform->render();
    }
}

/**
 * Given an array with a file path, it returns the itemid and the filepath for the defined filearea.
 *
 * @param  string $filearea The filearea.
 * @param  array  $args The path (the part after the filearea and before the filename).
 * @return array The itemid and the filepath inside the $args path, for the defined filearea.
 */
function question_get_path_from_pluginfile(string $filearea, array $args) : ?array {
    global $CFG, $COURSE, $DB;

    // First argument should be question usage.
    $qubaid = (int)array_shift($args);

    // Second argument  should be question slot.
    $slot = (int)array_shift($args);

    $record = $DB->get_record('question_usages', ['id' => $qubaid]);
    if (empty($record)) {
        return null;
    }

    $context = context::instance_by_id($record->contextid);
    if ($record->component === 'core_question_preview') {
        require_once($CFG->dirroot . '/question/engine/lib.php');
        require_once($CFG->dirroot . '/question/previewlib.php');
        if (!$filepath = question_preview_question_pluginfile_path($COURSE, $context,
                'question', $filearea, $qubaid, $slot, $args, true)) {
            return null;
        }
    } else {
        $dir = core_component::get_component_directory($record->component);
        if (!file_exists("$dir/lib.php")) {
            return null;
        }
        require_once("$dir/lib.php");
        $options = [];

        $filefunction = $record->component . '_question_pluginfile_path';
        if (function_exists($filefunction)) {
            $filepath = $filefunction($COURSE, $context, 'question', $filearea, $qubaid, $slot,
                $args, $forcedownload, $options);
        } else if (strpos($record->component, 'mod_') === 0) {
            // Okay, we're here so lets check for function without 'mod_'.
            $filefunctionold  = substr($record->component, 4) . '_question_pluginfile_path';
            if (function_exists($filefunctionold)) {
                $filepath = $filefunctionold($COURSE, $context, 'question', $filearea, $qubaid, $slot,
                    $args, true, $options);
            }
        }

        if (!$filepath) {
            return null;
        }
    }

    // Third argument is question id which is used as itemid.
    $itemid = array_shift($args);

    // Get the filepath.
    if (empty($args)) {
        $filepath = '/';
    } else {
        $filepath = '/' . implode('/', $args) . '/';
    }

    return [
        'itemid' => $itemid,
        'filepath' => $filepath,
    ];
}

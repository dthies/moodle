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

namespace filter_glossary\external;

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot . '/mod/glossary/lib.php');
require_once($CFG->dirroot . '/mod/glossary/classes/external.php');

use core_external\external_api;
use core_external\external_files;
use core_external\external_format_value;
use core_external\external_function_parameters;
use core_external\external_multiple_structure;
use core_external\external_single_structure;
use core_external\external_value;
use core_external\external_warnings;
use core_text;
use cm_info;
use mod_glossary_entry_query_builder;
use mod_glossary_external;
use moodle_exception;
use user_picture;
use core_external\util;

/**
 * This is the external method for get a glossary entry modal display.
 *
 * @package    filter_glossary
 * @since      Moodle 5.1
 * @copyright  2025 Daniel Thies <dethies@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class get_entry_by_id extends external_api {
    /**
     * Parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'id' => new external_value(PARAM_INT, 'Glossary entry id to update'),
        ]);
    }

    /**
     * Get formatted glossary entry.
     *
     * @param  int $id The id of entry to view
     * @return array with result and warnings
     * @throws moodle_exception
     */
    public static function execute(int $id) {
        global $DB, $PAGE, $USER;

        $params = self::validate_parameters(self::execute_parameters(), compact('id'));
        $id = $params['id'];

        // Get and validate the glossary entry.
        $entry = $DB->get_record('glossary_entries', ['id' => $id], '*', MUST_EXIST);

        [$glossary, $context, $course, $cm] = mod_glossary_external::validate_glossary($entry->glossaryid);

        if (!glossary_can_view_entry($entry, $cm)) {
            throw new invalid_parameter_exception('invalidentry');
        }

        // Trigger view.
        glossary_entry_view($entry, $context);

        $entry->definition = format_text($entry->definition, $entry->definitionformat, [
            'context' => $context,
            'trusted' => true,
        ]);
        $entry->descriptionformat = FORMAT_HTML;

        // Permissions (for entry edition).
        $permissions = [
            'candelete' => mod_glossary_can_delete_entry($entry, $glossary, $context),
            'canupdate' => mod_glossary_can_update_entry($entry, $glossary, $context, $cm),
        ];

        $warnings = [];

        // Fetch attachments.
        $entry->attachment = !empty($entry->attachment) ? 1 : 0;
        $entry->attachments = [];
        if ($entry->attachment) {
            $entry->attachments = util::get_area_files($context->id, 'mod_glossary', 'attachment', $entry->id);
        }
        $definitioninlinefiles = util::get_area_files($context->id, 'mod_glossary', 'entry', $entry->id);
        if (!empty($definitioninlinefiles)) {
            $entry->definitioninlinefiles = $definitioninlinefiles;
        }

        $entry->tags = \core_tag\external\util::get_item_tags('mod_glossary', 'glossary_entries', $entry->id);

        return [
            'entry' => $entry,
            'ratinginfo' => \core_rating\external\util::get_rating_info(
                $glossary,
                $context,
                'mod_glossary',
                'entry',
                [$entry]
            ),
            'permissions' => $permissions,
            'warnings' => $warnings,
        ];
    }

    /**
     * Return.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'entry' => self::get_entry_return_structure(),
            'ratinginfo' => \core_rating\external\util::external_ratings_structure(),
            'permissions' => new external_single_structure(
                [
                    'candelete' => new external_value(PARAM_BOOL, 'Whether the user can delete the entry.'),
                    'canupdate' => new external_value(PARAM_BOOL, 'Whether the user can update the entry.'),
                ],
                'User permissions for the managing the entry.',
                VALUE_OPTIONAL
            ),
            'warnings' => new external_warnings(),
        ]);
    }

    /**
     * Get the return value of an entry.
     *
     * @param bool $includecat Whether the definition should include category info.
     * @return external_definition
     */
    protected static function get_entry_return_structure($includecat = false) {
        $params = [
            'id' => new external_value(PARAM_INT, 'The entry ID'),
            'glossaryid' => new external_value(PARAM_INT, 'The glossary ID'),
            'userid' => new external_value(PARAM_INT, 'Author ID'),
            'concept' => new external_value(PARAM_RAW, 'The concept'),
            'definition' => new external_value(PARAM_RAW, 'The definition'),
            'definitionformat' => new external_format_value('definition'),
            'definitiontrust' => new external_value(PARAM_BOOL, 'The definition trust flag'),
            'definitioninlinefiles' => new external_files('entry definition inline files', VALUE_OPTIONAL),
            'attachment' => new external_value(PARAM_BOOL, 'Whether or not the entry has attachments'),
            'attachments' => new external_files('attachments', VALUE_OPTIONAL),
            'timecreated' => new external_value(PARAM_INT, 'Time created'),
            'timemodified' => new external_value(PARAM_INT, 'Time modified'),
            'teacherentry' => new external_value(PARAM_BOOL, 'The entry was created by a teacher, or equivalent.'),
            'sourceglossaryid' => new external_value(PARAM_INT, 'The source glossary ID'),
            'usedynalink' => new external_value(PARAM_BOOL, 'Whether the concept should be automatically linked'),
            'casesensitive' => new external_value(PARAM_BOOL, 'When true, the matching is case sensitive'),
            'fullmatch' => new external_value(PARAM_BOOL, 'When true, the matching is done on full words only'),
            'approved' => new external_value(PARAM_BOOL, 'Whether the entry was approved'),
            'tags' => new external_multiple_structure(
                \core_tag\external\tag_item_exporter::get_read_structure(),
                'Tags',
                VALUE_OPTIONAL
            ),
        ];

        if ($includecat) {
            $params['categoryid'] = new external_value(
                PARAM_INT,
                'The category ID. This may be' .
                ' \'' . GLOSSARY_SHOW_NOT_CATEGORISED . '\' when the entry is not categorised',
                VALUE_DEFAULT,
                GLOSSARY_SHOW_NOT_CATEGORISED
            );
            $params['categoryname'] = new external_value(PARAM_RAW, 'The category name. May be empty when the entry is' .
                ' not categorised, or the request was limited to one category.', VALUE_DEFAULT, '');
        }

        return new external_single_structure($params);
    }
}

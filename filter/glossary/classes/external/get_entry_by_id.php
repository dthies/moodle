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
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;
use moodle_exception;
use core_external\util;

/**
 * This is the external method to get a glossary entry for modal display.
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

        $settings = \core_external\external_settings::get_instance();
        $settings->set_filter(true);

        return \mod_glossary_external::get_entry_by_id($id);
    }

    /**
     * Return.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return \mod_glossary_external::get_entry_by_id_returns();
    }
}

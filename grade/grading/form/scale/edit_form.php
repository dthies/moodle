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
 * The form used at the editor page for grade scaling is defined here
 *
 * @package    gradingform_scale
 * @copyright  2018 Daniel Thies <dethies@ccal.edu>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/lib/formslib.php');
require_once($CFG->dirroot.'/grade/grading/form/rubric/lib.php');

/**
 * Defines the quiz scaling edit form
 *
 * @package    gradingform_scale
 * @copyright  2018 Daniel Thies <dethies@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class gradingform_scale_editform extends moodleform {

    /**
     * Form element definition
     */
    public function definition() {
        global $DB;
        $form = $this->_form;

        $form->addElement('hidden', 'areaid');
        $form->setType('areaid', PARAM_INT);

        $form->addElement('hidden', 'returnurl');
        $form->setType('returnurl', PARAM_LOCALURL);

        // name
        $form->addElement('text', 'name', get_string('name', 'gradingform_scale'), array('size' => 52, 'aria-required' => 'true'));
        $form->addRule('name', get_string('required'), 'required', null, 'client');
        $form->setType('name', PARAM_TEXT);

        // description
        $options = gradingform_rubric_controller::description_form_field_options($this->_customdata['context']);
        $form->addElement('editor', 'description_editor', get_string('description', 'gradingform_scale'), null, $options);
        $form->setType('description_editor', PARAM_RAW);

        // form completion status
        $choices = array();
        $choices[gradingform_controller::DEFINITION_STATUS_DRAFT]    = html_writer::tag('span', get_string('statusdraft', 'core_grading'), array('class' => 'status draft'));
        $choices[gradingform_controller::DEFINITION_STATUS_READY]    = html_writer::tag('span', get_string('statusready', 'core_grading'), array('class' => 'status ready'));
        $form->addElement('select', 'status', get_string('scalestatus', 'gradingform_scale'), $choices)->freeze();

        // Add level elements similar to overall feedback in quiz settings.
        $repeatarray = array();

        $grades = range(0, $this->_customdata['quizobj']->get_quiz()->grade);

        $repeatarray[] = $form->createElement('select', 'scaledgrade', get_string('gradeforsubmission', 'gradingform_scale'), $grades);

        $repeatarray[] = $form->createElement('editor', 'leveldescription',
                get_string('level', 'gradingform_scale'), array('rows' => 3), array('maxfiles' => EDITOR_UNLIMITED_FILES,
                'noclean' => true, 'context' => $this->context));
        $repeatarray[] = $form->createElement('text', 'levelboundary',
                get_string('gradeboundary', 'quiz'), array('size' => 10));

        $repeatedoptions['scaledgrade']['type'] = PARAM_INT;
        $repeatedoptions['leveldescription']['type'] = PARAM_RAW;
        $repeatedoptions['levelboundary']['type'] = PARAM_RAW;

        // If definition exists, use is to calculate number of fields.
        $sql = "SELECT s.* FROM {gradingform_scale} s
            JOIN {grading_definitions} d ON d.id = s.definitionid
            WHERE d.areaid = :areaid AND d.method = 'scale'"; 
        $records = $DB->get_records_sql($sql, array('areaid' => $areaid));
        $numlevels = max(count($records), 2);

        $nextel = $this->repeat_elements($repeatarray, $numlevels - 1,
                $repeatedoptions, 'boundary_repeats', 'boundary_add_fields', 3,
                get_string('addmorescalelevels', 'gradingform_scale'), true);

        $form->addElement('select', 'defaultscaledgrade', get_string('gradeforsubmission', 'gradingform_scale'), $grades);

        $form->addElement('editor', 'defaultleveldescription',
                get_string('level', 'gradingform_scale'), array('rows' => 3), array('maxfiles' => EDITOR_UNLIMITED_FILES,
                'noclean' => true, 'context' => $this->context));

        $buttonarray = array();
        $buttonarray[] = &$form->createElement('submit', 'savescale', get_string('savescale', 'gradingform_scale'));
        if ($this->_customdata['allowdraft']) {
            $buttonarray[] = &$form->createElement('submit', 'savescaledraft', get_string('savescaledraft', 'gradingform_scale'));
        }
        $buttonarray[] = &$form->createElement('cancel');
        $form->addGroup($buttonarray, 'buttonar', '', array(' '), false);
        $form->closeHeaderBefore('buttonar');
    }

    /**
      * Return submitted data if properly submitted or returns NULL if validation fails or
      * if there is no submitted data.
      *
      * @return object submitted data; NULL if not valid or not submitted or cancelled
      */
    public function get_data() {
        $data = parent::get_data();
        if (!empty($data->savescale)) {
            $data->status = gradingform_controller::DEFINITION_STATUS_READY;
        } else if (!empty($data->savescaledraft)) {
            $data->status = gradingform_controller::DEFINITION_STATUS_DRAFT;
        }
        if (!empty($data->grade)) {
            if ($data->grade > 0) {
                $data->gradetype = 'point';
                $data->maxgrade = $data->grade;
            } else {
                $data->gradetype = 'scale';
                $data->scaleid = -$data->grade;
            }
        }

        return $data;
    }

    function need_confirm_regrading($controller) {
        return false;
    }
}

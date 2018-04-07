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
 * Grading method controller for the grade scaling plugin
 *
 * @package    gradingform_scale
 * @copyright  201i Daniel Thies <dethies@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/grade/grading/form/lib.php');
require_once($CFG->dirroot.'/question/engine/questionusage.php');

/**
 * This controller encapsulates the quiz grade scaling logic
 *
 * @package    gradingform_scale
 * @copyright  2018 Daniel Thies
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class gradingform_scale_controller extends gradingform_controller {
    /**
     * Extends the module settings navigation with the quiz scaling grading settings
     *
     * This function is called when the context for the page is an activity module with the
     * FEATURE_ADVANCED_GRADING, the user has the permission moodle/grade:managegradingforms
     * and there is an area with the active grading method set to 'scale'.
     *
     * @param settings_navigation $settingsnav {@link settings_navigation}
     * @param navigation_node $node {@link navigation_node}
     */
    public function extend_settings_navigation(settings_navigation $settingsnav, navigation_node $node=null) {
        $node->add(get_string('definescale', 'gradingform_scale'),
            $this->get_editor_url(), settings_navigation::TYPE_CUSTOM,
            null, null, new pix_icon('icon', '', 'gradingform_rubric'));
    }

    public function load_definition() {
        global $DB;
        $this->definition = false;
        parent::load_definition();
        $sql = "SELECT *
            FROM {gradingform_scale} 
            WHERE definitionid = :definitionid
            ORDER BY rawscore DESC";
        $params = array('definitionid' => $this->definition->id);
        $rs = $DB->get_recordset_sql($sql, $params);
        $this->definition->leveldescription = array();
        $this->definition->leveldescriptionformat = array();
        $this->definition->rawscore = array();
        $this->definition->scaledscore = array();
        foreach ($rs as $record) {
            $this->definition->leveldescription[] = $record->description;
            $this->definition->leveldescriptionformat[] = $record->descriptionformat;
            $this->definition->rawscore[] = $record->rawscore;
            $this->definition->scaledscore[] = $record->scaledscore;
        }
        $rs->close();
    }

    public function render_preview(moodle_page $page) {
        $definition = $this->get_definition();
        $output = '<table><tr><td>Rawscore</td><td>Description</td><td>Scaled Scored</td></tr>';
        foreach ($definition->rawscore as $key => $rawscore) {
                $output .= "<tr>";
                $output .= "<td>" . 100 * $definition->rawscore[$key] . "</td>";
                $output .= "<td>" . format_text($definition->leveldescription[$key]) . "</td>";
                $output .= "<td>" . $definition->scaledscore[$key] . "</td>";
                $output .= "</tr>";
        }
        $output .= '</tr></table>';
        return $output;
    }

    protected function delete_plugin_definition() {
        global $DB;

        $DB->delete_records('gradingform_scale', array('definitionid' => $this->definition->id));
    }

    /**
     * Saves the grade scaling definition into the database
     *
     * @see parent::update_definition()
     * @param stdClass $newdefinition scale definition data
     * @param int|null $usermodified optional userid of the author of the definition, defaults to the current user
     */
    public function update_definition(stdClass $newdefinition, $usermodified = null) {
        global $DB;
        $this->load_definition();
        if (!$this->definition->id) {
            $this->definition = false;
        }
        parent::update_definition($newdefinition, $usermodified);

        foreach ($newdefinition->levelboundary as $key => $rawscore) {
            $rawscore = trim($rawscore);
            if (!is_numeric($rawscore) && strlen($rawscore) > 0 && strpos($rawscore, '%') == strlen($rawscore) - 1) {;
                $rawscore = substr($rawscore, 0, -1) / 100.0;
            } else {
                $rawscore = $rawscore / $newdefinition->grade;
            }
            $newdefinition->levelboundary[$key] = $rawscore;
        }

        $params = array('areaid' => $newdefinition->areaid, 'method' => $this->get_method_name());
        $record = $DB->get_record('grading_definitions', $params);
        $DB->delete_records('gradingform_scale', array('definitionid' => $record->id));
        for ($key = 0; $key < count($newdefinition->leveldescription); $key++) {
            $level = array('definitionid' => $record->id,
                'description' => $newdefinition->leveldescription[$key]['text'],
                'descriptionformat' => $newdefinition->leveldescription[$key]['format'],
                'rawscore' => $newdefinition->levelboundary[$key],
                'scaledscore' => $newdefinition->scaledgrade[$key]
             );
            $DB->insert_record('gradingform_scale', $level);
        }
        $DB->insert_record('gradingform_scale', array('definitionid' => $record->id,
                'description' => $newdefinition->defaultleveldescription['text'],
                'descriptionformat' => $newdefinition->defaultleveldescription['format'],
                'rawscore' => 0,
                'scaledscore' => $newdefinition->defaultscaledgrade));
    }

}

/**
 * Class to manage one scaling grading instance.
 *
 * Stores information and performs actions like update, copy, validate, submit, etc.
 *
 * @package    gradingform_scale
 * @copyright  2018 Daniel Thies
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *         */
class gradingform_scale_instance extends gradingform_instance {
    public function get_grade() {
        $definition = $this->get_controller()->get_definition();
        $qa = quiz_attempt::create($this->get_data('itemid'));
        $fraction = $this->get_total_mark() / $qa->get_quiz()->sumgrades;
        foreach ($definition->rawscore as $key => $rawscore) {
            if ($rawscore <= $fraction) {
                return $definition->scaledscore[$key];
            }
        }
    }

    function render_grading_element($page, $gradingformelement) {
        return 'grading element';
    }

    public function get_total_mark() {
        global $DB; 
        $attemptid = $this->get_data('itemid');
        $attempt = $DB->get_record('quiz_attempts', array('id' => $attemptid));
        $quba = question_engine::load_questions_usage_by_activity($attempt->uniqueid);
        return $quba->get_total_mark();
    }

    public function render_grade($page, $itemid, $gradinginfo, $defaultcontent, $cangrade) {
        $qa = quiz_attempt::create($this->get_data('itemid'));
        $fraction = $qa->get_sum_marks()/$qa->get_quiz()->sumgrades;
        $output .= '<p>Raw score of attempt ' . $this->get_total_mark() / $qa->get_quiz()->sumgrades * 100 . "%";
        
        return $output . "<p>The scaled grade for this is " . $this->get_grade();
    }

}

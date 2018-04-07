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
 * Quiz grading scale editor page
 *
 * @package    gradingform_scale
 * @copyright  2018 Daniel Thies <dethies@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__.'/../../../../config.php');
require_once(__DIR__.'/lib.php');
require_once(__DIR__.'/../../../../mod/quiz/accessmanager.php');
require_once(__DIR__.'/../../../../mod/quiz/attemptlib.php');
require_once(__DIR__.'/edit_form.php');
require_once($CFG->dirroot.'/grade/grading/lib.php');

$areaid = required_param('areaid', PARAM_INT);

$manager = get_grading_manager($areaid);

list($context, $course, $cm) = get_context_info_array($manager->get_context()->id);

require_login($course, true, $cm);
require_capability('moodle/grade:managegradingforms', $context);

$controller = $manager->get_controller('scale');

$quizobj = quiz::create($cm->instance);

$PAGE->set_url(new moodle_url('/grade/grading/form/scale/edit.php', array('areaid' => $areaid)));
$PAGE->set_title(get_string('definescale', 'gradingform_scale'));
$PAGE->set_heading(get_string('definescale', 'gradingform_scale'));

$mform = new gradingform_scale_editform(null, array('areaid' => $areaid, 'context' => $context, 'allowdraft' => !$controller->has_active_instances(), 'quizobj' => $quizobj), 'post', '', array('class' => 'gradingform_scale_editform'));
$data = $controller->get_definition(true);
$returnurl = optional_param('returnurl', $manager->get_management_url(), PARAM_LOCALURL);
$data->returnurl = $returnurl;
$data->areaid = $areaid;
$data->description_editor = array('text' => $data->description, 'format' => $data->descriptionformat);
$options = json_decode($data->options);
$data->levelboundary = array();
foreach ($data->leveldescription as $key => $description) {
    if ($data->rawscore[$key] > 0) {
        $data->leveldescription[$key] = array('text' => $data->leveldescription[$key], 'format' => $data->leveldescriptionformat[$key]);
        $data->levelboundary[$key] = $data->rawscore[$key] * 100.0 . '%';
        $data->scaledgrade[$key] = $data->scaledscore[$key];
    } else {
        $data->defaultleveldescription = array('text' => $data->leveldescription[$key], 'format' => $data->leveldescriptionformat[$key]);
        $data->defaultscaledgrade = $data->scaledscore[$key];
    }
}
$mform->set_data($data);
if ($mform->is_cancelled()) {
    redirect($returnurl);
} else if ($mform->is_submitted() && $mform->is_validated() && !$mform->need_confirm_regrading($controller)) {
    // Everything ok, validated, re-grading confirmed if needed. Make changes to the scale.
    $data = $mform->get_data();
    $data->description = $data->description_editor['text'];
    $data->descriptionformat = $data->description_editor['format'];
    $data->grade = $quizobj->get_quiz()->grade;
    $data->definition = false;
    $controller->update_definition($data);

    redirect($returnurl, $warning, null, \core\output\notification::NOTIFY_ERROR);
}

echo $OUTPUT->header();
$mform->display();
echo $OUTPUT->footer();

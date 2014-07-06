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
 *  latexml integration settings.
 *
 * @package   filter_math
 * @copyright 2014 Daniel Thies <dthies@ccal.edu>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

$defaultpathlatexmlmath = '/usr/bin/latexmlmath';
$defaultdelimiters = '[["\\\\[", "\\\\]"], ["$$", "$$"], ["d%", "%d"]]';

$plugin = filter_math_plugin::get('displaylatexml');

$file = $plugin->render(
            get_config('math_displaylatexml', 'testexp'),
            md5(get_config('math_displaylatexml', 'testexp')));
$math = 'Not found';
if (file_exists($file)) {
    $doc = $plugin->load_document($file);
    $math = $doc->saveHTML($doc->getElementsByTagName('math')->item(0));
}

if (get_config('math_displaylatexml', 'purgecaches')) {
    $plugin->purge_caches();
    set_config('purgecaches', 0, 'math_displaylatexml');
}

if ($ADMIN->fulltree) {
    $settings->add(new admin_setting_configcheckbox('math_displaylatexml/purgecaches',
        get_string('purgecaches', 'filter_math'), get_string('purgecaches_desc', 'filter_math'), 0));
    $settings->add(new admin_setting_configtext('math_displaylatexml/delimiters',
        get_string('delimiters', 'math_displaylatexml'),
        get_string('delimiters_desc', 'math_displaylatexml'), $defaultdelimiters));
    $settings->add(new admin_setting_configexecutable('math_latexml/pathlatexmlmath',
        get_string('pathlatexmlmath', 'math_latexml'), '', $defaultpathlatexmlmath));
    $settings->add(new admin_setting_configtext('math_displaylatexml/testexp',
        get_string('testexp', 'math_displaylatexml'),
        get_string('testexp_desc', 'math_displaylatexml') .  $math,
        '\\sum^\\infty_{k=1}\\frac 1{k^2}'));

}

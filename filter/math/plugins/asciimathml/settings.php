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
 *  dvisvgm integration settings.
 *
 * @package   filter_math
 * @copyright 2014 Daniel Thies <dthies@ccal.edu>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

$defaultdelimiters = '[["`", "`"], ["a%", "%a"]]';

$plugin = filter_math_plugin::get('asciimathml');
$plugin->get_image_url(get_config('math_asciimathml', 'testexp'));

if (get_config('math_asciimathml', 'purgecaches')) {
    $plugin->purge_caches();
    set_config('purgecaches', 0, 'math_asciimathml');
}

if ($ADMIN->fulltree) {
    $settings->add(new admin_setting_configcheckbox('math_asciimathml/purgecaches',
        get_string('purgecaches', 'filter_math'), get_string('purgecaches_desc', 'filter_math'), 0));
    $settings->add(new admin_setting_configtext('math_asciimathml/delimiters',
        get_string('delimiters', 'filter_math'),
        get_string('delimiters_desc', 'filter_math'), $defaultdelimiters));
    $settings->add(new admin_setting_configtext('math_asciimathml/testexp',
        get_string('testexp', 'math_asciimathml'),
        get_string('testexp_desc', 'math_asciimathml') .
       file_get_contents($plugin->render(
           get_config('math_asciimathml', 'testexp'),
           md5(get_config('math_asciimathml', 'testexp')))),
        'sum_{k=1}^oo frac 1{k^2}'));

}

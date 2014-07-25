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
 *  dvipng integration settings.
 *
 * @package   filter_math
 * @copyright 2014 Daniel Thies <dthies@ccal.edu>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

$defaultpathlatex = '/usr/bin/latex';
$defaultpathdvipng = '/usr/bin/dvipng';
$defaultdelimiters = '[["\\\\(", "\\\\)"], ["i%", "%i"]]';
$defaultscale = 1000;

$plugin = filter_math_plugin::get('dvipng');

$image = $plugin->get_image_url(get_config('math_dvipng', 'testexp') . "\\phantom{" . get_config('math_dvipng', 'scale') . "}");

if ($ADMIN->fulltree) {
    $settings->add(new admin_setting_configtext('math_dvipng/delimiters',
        get_string('delimiters', 'math_dvipng'),
        get_string('delimiters_desc', 'math_dvipng'), $defaultdelimiters));
    $settings->add(new admin_setting_configexecutable('filter_tex/pathlatex',
        get_string('pathlatex', 'filter_tex'), '', $defaultpathlatex));
    $settings->add(new admin_setting_configexecutable('math_dvipng/pathdvipng',
        get_string('pathdvipng', 'math_dvipng'), '', $defaultpathdvipng));
    $settings->add(new admin_setting_configtext('math_dvipng/scale',
        get_string('scale', 'math_dvipng'),
        get_string('scale_desc', 'math_dvipng'), $defaultscale));
    $settings->add(new admin_setting_configtext('math_dvipng/testexp',
        get_string('testexp', 'math_dvipng'),
        get_string('testexp_desc', 'math_dvipng') . "<a href=\"$image\"><img src=\"$image\" alt=\"" .
        get_config('math_dvipng', 'testexp') . "\" ></a>", '\\sum^\\infty_{k=1}\\frac 1{k^2}'));

}

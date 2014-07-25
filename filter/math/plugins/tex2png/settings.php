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
 *  netpbm TeX filter integration settings.
 *
 * @package   math_tex2png
 * @copyright 2014 Daniel Thies <dthies@ccal.edu>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

$defaultpathlatex = '/usr/bin/latex';
$defaultpathdvips = '/usr/bin/dvips';
$defaultpathnetpbm = '/usr/bin';
$defaultpathdelimiters = '[["\\\\(", "\\\\)"], ["i%", "%i"]]';
$defaultscale = 1000;

$plugin = filter_math_plugin::get('tex2png');

$image = $plugin->get_image_url(get_config('math_tex2png', 'testexp') . "\\phantom{" . get_config('math_tex2png', 'scale') . "}");

if ($ADMIN->fulltree) {
    // $settings->add(new admin_setting_configcheckbox('math_tex2png/requiretex',
    //     get_string('requiretex', 'tinymce_dragmath'), get_string('requiretex_desc', 'tinymce_dragmath'), 1));
    $settings->add(new admin_setting_configtext('math_tex2png/delimiters',
        get_string('delimiters', 'filter_math'),
        get_string('delimiters_desc', 'filter_math'), $defaultpathdelimiters));
    $settings->add(new admin_setting_configexecutable('filter_tex/pathlatex',
        get_string('pathlatex', 'filter_tex'), '', $defaultpathlatex));
    $settings->add(new admin_setting_configexecutable('filter_tex/pathdvips',
        get_string('pathdvips', 'filter_tex'), '', $defaultpathdvips));
    $settings->add(new admin_setting_configdirectory('filter_math/pathnetpbm',
        get_string('pathnetpbm', 'math_tex2png'), '', $defaultpathnetpbm));
    $settings->add(new admin_setting_configtext('math_tex2png/scale',
        get_string('scale', 'filter_math'),
        get_string('scale_desc', 'filter_math'), $defaultscale));
    $settings->add(new admin_setting_configtext('math_tex2png/testexp',
        get_string('testexp', 'filter_math'),
        get_string('testexp_desc', 'filter_math') . "<a href=\"$image\"><img src=\"$image\" alt=\"" .
        get_config('math_tex2png', 'testexp') . "\" ></a>", '\\sum^\\infty_{k=1}\\frac 1{k^2}'));

}

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
 * ASCIIMath integration settings.
 *
 * @package   math_asciimath
 * @copyright 2014 Daniel Thies <dthies@ccal.edu>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

$defaultdelimiters = '[["a%", "%a"]]';

$plugin = filter_math_plugin::get('asciimath');
$image = $plugin->get_image_url(get_config('math_asciimath', 'testexp'));


if ($ADMIN->fulltree) {
    $settings->add(new admin_setting_configtext('math_asciimath/delimiters',
        get_string('delimiters', 'filter_math'),
        get_string('delimiters_desc', 'filter_math'),
        $defaultdelimiters));
    $settings->add(new admin_setting_configtext('math_asciimath/testexp',
        get_string('testexp', 'filter_math'),
        get_string('testexp_desc', 'filter_math') . "<p><a href=\"$image\"><img src=\"$image\" alt=\"" .
        get_config('math_asciimath', 'testexp') . "\" ></a></p>", 'sum_{k=1}^oo  1/{k^2}'));

}

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
 * Math filter image server
 *
 * @package    filter_math
 * @copyright  2014 Daniel Thies (dthies@ccal.edu)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * This file fetches images of rendered mathematical scripts from the data directory
 * If not, it obtains the corresponding script from the cache_filters db table
 * and creates it.
 */

// Disable moodle specific debug messages and any errors in output.
define('NO_DEBUG_DISPLAY', true);
define('NO_MOODLE_COOKIES', true); // Because it interferes with caching.

require_once('../../../../config.php');

if (!filter_is_enabled('math')) {
    print_error('filternotenabled');
}

require_once($CFG->libdir.'/filelib.php');
require('./version.php');
$pluginname = str_replace('math_', '', $plugin->component);

$relativepath = get_file_argument();

$plugin = filter_math_plugin::get($pluginname);
$plugin->output_image($relativepath);


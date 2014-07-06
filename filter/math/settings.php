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
 * Math filter admin settings
 *
 * @package    filter_math
 * @copyright  2009 Petr Skoda (http://skodak.org)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

$ADMIN->add('filtersettings', new admin_category('filtermath', $filter->displayname, $filter->is_enabled() === false));

$settings = new admin_settingpage('filtersettingmath', new lang_string('settings', 'filter_math'));
if ($ADMIN->fulltree) {
    require_once(__DIR__.'/adminlib.php');
    $settings->add(new math_subplugins_settings());
    // $settings->add(new admin_setting_heading('mathgeneralheader', new lang_string('settings'), ''));

    // $settings->add(new filter_math_json_setting_textarea('filter_math/customconfig',
       // get_string('customconfig', 'filter_math'), get_string('customconfig_desc', 'filter_math'), '', PARAM_RAW, 100, 8));
}
$ADMIN->add('filtermath', $settings);
unset($settings);

foreach (core_plugin_manager::instance()->get_plugins_of_type('math') as $plugin) {
    /* @var \filter_math\plugininfo\math $plugin */
    $plugin->load_settings($ADMIN, 'filtermath', $hassiteconfig);
}

// Math filter does not have standard settings page.
$settings = null;

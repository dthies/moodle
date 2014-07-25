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
 * Math filter admin setting stuff.
 *
 * @package   filter_math
 * @copyright 2012 Petr Skoda {@link http://skodak.org}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();


/**
 * Special class for Math filter subplugin administration.
 *
 * @package   filter_math
 * @copyright 2012 Petr Skoda {@link http://skodak.org}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class math_subplugins_settings extends admin_setting {
    public function __construct() {
        $this->nosave = true;
        parent::__construct('mathsubplugins', get_string('subplugintype_math_plural', 'filter_math'), '', '');
    }

    /**
     * Always returns true, does nothing.
     *
     * @return true
     */
    public function get_setting() {
        return true;
    }

    /**
     * Always returns true, does nothing.
     *
     * @return true
     */
    public function get_defaultsetting() {
        return true;
    }

    /**
     * Always returns '', does not write anything.
     *
     * @param string $data
     * @return string Always returns ''
     */
    public function write_setting($data) {
        // Do not write any setting.
        return '';
    }

    /**
     * Checks if $query is one of the available subplugins.
     *
     * @param string $query The string to search for
     * @return bool Returns true if found, false if not
     */
    public function is_related($query) {
        if (parent::is_related($query)) {
            return true;
        }

        $subplugins = core_component::get_plugin_list('math');
        foreach ($subplugins as $name => $dir) {
            if (stripos($name, $query) !== false) {
                return true;
            }

            $namestr = get_string('pluginname', 'math_'.$name);
            if (strpos(core_text::strtolower($namestr), core_text::strtolower($query)) !== false) {
                return true;
            }
        }
        return false;
    }

    /**
     * Builds the XHTML to display the control.
     *
     * @param string $data Unused
     * @param string $query
     * @return string
     */
    public function output_html($data, $query='') {
        global $CFG, $OUTPUT, $PAGE;
        require_once("$CFG->libdir/filterlib.php");
        $pluginmanager = core_plugin_manager::instance();

        // Display strings.
        $strscripttypes = get_string('scripttypes', 'filter_math');
        $strdisable = get_string('disable');
        $strenable = get_string('enable');
        $strname = get_string('name');
        $strsettings = get_string('settings');
        $struninstall = get_string('uninstallplugin', 'core_admin');
        $strversion = get_string('version');

        $subplugins = core_component::get_plugin_list('math');
        $return = $OUTPUT->heading(get_string('subplugintype_math_plural', 'filter_math'), 3, 'main', true);
        $return .= $OUTPUT->box_start('generalbox mathsubplugins');

        $table = new html_table();
        $table->head  = array($strname, $strscripttypes, $strversion, $strenable, $strsettings, $struninstall);
        $table->align = array('left', 'left', 'center', 'center', 'center', 'center');
        $table->data  = array();
        $table->attributes['class'] = 'admintable generaltable';

        // Iterate through subplugins.
        foreach ($subplugins as $name => $dir) {
            $namestr = get_string('pluginname', 'math_'.$name);
            $version = get_config('math_'.$name, 'version');
            if ($version === false) {
                $version = '';
            }
            $plugin = filter_math_plugin::get($name);
            $plugininfo = $pluginmanager->get_plugin_info('math_'.$name);

            // Add hide/show link.
            $class = '';
            if (!$version) {
                $hideshow = '';
                $displayname = html_writer::tag('span', $name, array('class' => 'error'));
            } else if ($plugininfo->is_enabled()) {
                $url = new moodle_url('/filter/math/subplugins.php',
                    array('sesskey' => sesskey(), 'return' => 'settings', 'disable' => $name));
                $hideshow = html_writer::empty_tag('img',
                    array('src' => $OUTPUT->pix_url('t/hide'), 'class' => 'iconsmall', 'alt' => $strdisable));
                $hideshow = html_writer::link($url, $hideshow);
                $displayname = $namestr;
            } else {
                $url = new moodle_url('/filter/math/subplugins.php',
                    array('sesskey' => sesskey(), 'return' => 'settings', 'enable' => $name));
                $hideshow = html_writer::empty_tag('img',
                    array('src' => $OUTPUT->pix_url('t/show'), 'class' => 'iconsmall', 'alt' => $strenable));
                $hideshow = html_writer::link($url, $hideshow);
                $displayname = $namestr;
                $class = 'dimmed_text';
            }

            if ($PAGE->theme->resolve_image_location('icon', 'math_' . $name, false)) {
                $icon = $OUTPUT->pix_icon('icon', '', 'math_' . $name, array('class' => 'icon pluginicon'));
            } else {
                $icon = $OUTPUT->pix_icon('spacer', '', 'moodle', array('class' => 'icon pluginicon noicon'));
            }
            $displayname  = $icon . ' ' . $displayname;

            $contenttype = $plugin->get_type();

            // Add settings link.
            if (!$version) {
                $settings = '';
            } else if ($url = $plugininfo->get_settings_url()) {
                $settings = html_writer::link($url, $strsettings);
            } else {
                $settings = '';
            }

            // Add uninstall info.
            $uninstall = '';
            if ($uninstallurl = core_plugin_manager::instance()->get_uninstall_url('math_' . $name, 'manage')) {
                $uninstall = html_writer::link($uninstallurl, $struninstall);
            }

            // Add a row to the table.
            $row = new html_table_row(array($displayname, $contenttype, $version, $hideshow, $settings, $uninstall));
            if ($class) {
                $row->attributes['class'] = $class;
            }
            $table->data[] = $row;
        }
        $return .= html_writer::table($table);
        $return .= html_writer::tag('p', get_string('tablenosave', 'admin'));
        $return .= $OUTPUT->box_end();
        return highlight($query, $return);
    }
}

class filter_math_json_setting_textarea extends admin_setting_configtextarea {
    /**
     * Returns an XHTML string for the filter
     *
     * @param string $data
     * @param string $query
     * @return string XHTML string for the filter
     */
    public function output_html($data, $query='') {
        $result = parent::output_html($data, $query);

        $data = trim($data);
        if ($data) {
            $decoded = json_decode($data, true);
            // Note: it is not very nice to abuse these file classes, but anyway...
            if (is_array($decoded)) {
                $valid = '<span class="pathok">&#x2714;</span>';
            } else {
                $valid = '<span class="patherror">&#x2718;</span>';
            }
            $result = str_replace('</textarea>', '</textarea>'.$valid, $result);
        }

        return $result;
    }
}

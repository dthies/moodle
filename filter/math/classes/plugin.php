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

defined('MOODLE_INTERNAL') || die();

/**
 * Math filter plugin base class.
 *
 * This is a base class for Math filter plugins implemented within Moodle. These
 * plugins can optionally provide new functionality to the filter.
 *
 * As well as overridable functions, other utility functions in this class
 * can be used when writing the plugins.
 *
 * Finally, a static function in this class is used to call into all the
 * plugins when required.
 *
 * @package filter_math
 * @copyright 2012 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class filter_math_plugin {
    /** @var string filter name */
    public $filter = 'math';

    /** @var string debug output */
    public $debug = '';

    /** @var string temporary directory */
    public $tempdir;

    /** @var string Plugin folder */
    protected $plugin;

    /** @var array Plugin settings */
    protected $config = null;

    /** @var array list of buttons defined by this plugin */
    protected $buttons = array();

    /**
     * @param string $plugin Name of folder
     */


    public function __construct($plugin) {
        $this->plugin = $plugin;
    }

    /**
     * Makes sure config is loaded and cached.
     * @return void
     */
    protected function load_config() {
        if (!isset($this->config)) {
            $name = $this->get_name();
            $this->config = get_config("math_$name");
        }
    }

    /**
     * Returns plugin config value.
     * @param  string $name
     * @param  string $default value if config does not exist yet
     * @return string value or default
     */
    public function get_config($name, $default = null) {
        $this->load_config();
        return isset($this->config->$name) ? $this->config->$name : $default;
    }

    /**
     * Sets plugin config value.
     * @param  string $name name of config
     * @param  string $value string config value, null means delete
     * @return string value
     */
    public function set_config($name, $value) {
        $pluginname = $this->get_name();
        $this->load_config();
        if ($value === null) {
            unset($this->config->$name);
        } else {
            $this->config->$name = $value;
        }
        set_config($name, $value, "math_$pluginname");
    }

    /**
     * Returns name of this math plugin.
     * @return string
     */
    public function get_name() {
        // All class names start with "math_".
        $words = explode('_', get_class($this), 2);
        return $words[1];
    }

    /**
     * Gets the order in which to run this plugin. Order usually only matters if
     * (a) the place you add your button might depend on another plugin, or
     * (b) you want to make some changes to layout etc. that should happen last.
     * The default order is 100; within that, plugins are sorted alphabetically.
     * Return a lower number if you want this plugin to run earlier, or a higher
     * number if you want it to run later.
     */
    protected function get_sort_order() {
        return 100;
    }

    /**
     * Obtains version number from version.php for this plugin.
     *
     * @return string Version number
     */
    protected function get_version() {
        global $CFG;

        $plugin = new stdClass;
        require($CFG->dirroot . '/lib/filter/math/plugins/' . $this->plugin . '/version.php');
        return $plugin->version;
    }

    /**
     * Gets a named plugin object. Will cause fatal error if plugin doesn't exist.
     *
     * @param string $plugin Name of plugin e.g. 'tex2svg'
     * @return filter_math_plugin Plugin object
     */
    public static function get($plugin) {
        $dir = core_component::get_component_directory('math_' . $plugin);
        require_once($dir . '/lib.php');
        $classname = 'math_' . $plugin;
        return new $classname($plugin);
    }

    /**
     * Compares two plugins.
     * @param filter_math_plugin $a
     * @param filter_math_plugin $b
     * @return Negative number if $a is before $b
     */
    public static function compare_plugins(filter_math_plugin $a, filter_math_plugin $b) {
        // Use sort order first.
        $order = $a->get_sort_order() - $b->get_sort_order();
        if ($order != 0) {
            return $order;
        }

        // Then sort alphabetically.
        return strcmp($a->plugin, $b->plugin);
    }

    /* generate and url for a script and store the script in the database for
     * reference.
     * @param string $script script to process
     * @param string $options options unsed
     */
    public function get_image_url($script, array $options = array()) {

        global $CFG, $DB;

        $script = html_entity_decode($script, ENT_QUOTES, 'UTF-8');

        if ($script === '') {
            return;
        }

        $md5 = md5($script);
        if (!$DB->record_exists("cache_filters", array("filter" => $this->get_name(), "md5key" => $md5))) {
            $scriptcache = new stdClass();
            $scriptcache->filter = $this->get_name();
            $scriptcache->version = 1;
            $scriptcache->md5key = $md5;
            $scriptcache->rawtext = $script;
            $scriptcache->timemodified = time();
            $DB->insert_record("cache_filters", $scriptcache, false);
        }

        $filename = $md5."." . $this->imgformat;
        return "$CFG->wwwroot/filter/math/plugins/" . $this->get_name() . "/pix.php/$filename";
    }

    /*
     * replace node contents with and image refences for the script
     * @param DOMNode $span node in which to append image reference
     * @param string $script script to use otherwise us node contents
     */
    public function append_image($span, $script = null) {
        if (!$script) {
            $script = $span->nodeValue;
        };
        $span->nodeValue = "";
        $preview = $span->ownerDocument->createElement('span');
        $img = $span->ownerDocument->createElement('img');
        $img->setAttribute('alt', $script);
        $img->setAttribute('title', $script);
        $img->setAttribute('src', $this->get_image_url($script));
        $preview->appendChild($img);
        return $span->appendChild($preview);
    }
    /**
     * execute an external command, with optional logging
     * @param string $command command to execute
     * @param file $log valid open file handle - log info will be written to this file
     * @return return code from execution of command
     */
    protected function execute( $command, $log=null ) {
        $output = array();
        exec( $command, $output, $returncode );
        if ($log) {
            fwrite( $log, "COMMAND: $command \n" );
            $outputs = implode( "\n", $output );
            fwrite( $log, "OUTPUT: $outputs \n" );
            fwrite( $log, "RETURN_CODE: $returncode\n " );
        } else {
            $this->debug .= "COMMAND: $command \n" . implode( "<br />\n", $output ) . "RETURN_CODE: $returncode<br />\n ";
        }
        if (debugging()) {
            return ;
        }
        return $returncode;
    }

    /*
     * get pathname for directory of cached images for filter
     * @return string pathname
     */
    protected function get_image_cache() {
        global $CFG;
        $cache = $CFG->dataroot . "/filter/" . $this->get_name();
        if (!file_exists($cache)) {
            make_upload_directory('filter/' . $this->get_name());
        }
        return $cache;
    }

    /*
     * get object containing information stored in database for object
     * return object database record
     */
    protected function get_record($md5) {
        global $DB;
        return $DB->get_record('cache_filters', array('filter' => $this->get_name(), 'md5key' => $md5));
    }

    /*
     * get pathname to image render if necessary
     * @param string $image filename of image
     * @return string pathname to cached image
     */
    public function get_image($image) {
        global $DB;
        $pathname = $this->get_image_cache() . "/$image";
        if (file_exists($pathname)) {
            return $pathname;
        }
        $md5 = str_replace(".{$this->imgformat}", '', $image);
        $scriptcache = $this->get_record($md5);
        if ($scriptcache) {
            return $this->render($scriptcache->rawtext, $md5);
        }
    }

    /*
     * get pathname for directory to hold temporary files for filter
     * @return string pathname
     */
    protected function get_temp() {
        global $CFG;

        $this->tempdir = $CFG->tempdir . "/" . $this->get_name();
        if (!file_exists($this->tempdir)) {
            make_temp_directory($this->get_name());
        }
        return $this->tempdir;
    }

    /*
     * get current image format
     * @return string format
     */
    public function get_image_format() {
        return $this->imgformat;
    }

    /*
     * get type of script plugin processes
     * @return string type of script e.g math/tex
     */
    public function get_type() {
        return isset($this->type) ? $this->type : '';
    }

    /*
     * handle request for image to the server
     * @param string path of image request
     */
    public function output_image($relativepath) {

        $args = explode('/', trim($relativepath, '/'));

        if (count($args) == 1) {
            $image    = $args[0];
            $pathname = $this->get_image($image);
        } else {
            print_error('invalidarguments', 'error');
        }

        if (file_exists($pathname)) {
            send_file($pathname, $image);
        } else {
            if (debugging()) {
                echo "Image not found!<br />Image format: $this->imgformat<br />Filter: $this->filter<br />\n";
                if ($pathname) {
                    echo "<br>Output: <br /><code>" . $this->debug . "</code><br />\n";
                } else {
                    echo "Database entry empty!<br />";
                }
            } else {
                echo "Image not found!<br />";
            }
        }
    }

    /*
     * delete all images and stored scripts
     * @param unused name of setting passed to update callback
     */
    public function purge_caches($name = null) {

        global $CFG, $DB;

        reset_text_filters_cache();

        $cache = $CFG->dataroot . "/filter/" . $this->get_name();

        if (file_exists($cache)) {
            remove_dir($cache);
        }
        if (file_exists("$CFG->tempdir/$this->filter")) {
            remove_dir("$CFG->tempdir/$this->filter");
        }
        $DB->delete_records('cache_filters', array('filter' => $this->filter));
    }

    /*
     * set current image format
     * @param string $format format
     */
    public function set_image_format($format) {
        $this->imgformat = $format;
    }

    public function load_document($file) {
        // Create a new dom object.
        $dom = new domDocument;
        $dom->formatOutput = true;

        // Load the MathML into an HTML document.
        libxml_use_internal_errors(true);
        $dom->loadHTML(
            '<!DOCTYPE html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"/></head><body>' .
            file_get_contents($file));
        libxml_use_internal_errors(false);

        $dom->preserveWhiteSpace = false;
        $dom->strictErrorChecking = false;
        $dom->recover = true;
        return $dom;
    }

}

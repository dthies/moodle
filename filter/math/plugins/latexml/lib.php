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

/*
 * @package    filter
 * @subpackage math
 * @copyright  2014 Daniel Thies
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

global $CFG;

require_once($CFG->libdir.'/filelib.php');

/**
 * Math filter plugin class.
 * implements method for formatting and generating images
 */
class math_latexml extends filter_math_plugin {
    public $filter = 'latexml';
    public $imgformat = 'xml';
    public $type = 'math/tex';

    /**
     * Convert contents of an element from a script to a MathML node
     * for the script cached the database.
     * @param DOMNode $span a DOM Node that contains the script
     */
    public function process($span) {
        $script = $span->nodeValue;
        $span->nodeValue = '';
        $mathjax = $span->ownerDocument->createElement('script');
        $mathjax->nodeValue = $script;
        $mathjax->setAttribute('type', $this->type);

        $preview = $this->append_image($span, $script);
        $preview->setAttribute('title', $script);
    }

    /**
     * Append MathML for the script
     * @param string $span container to be parent
     * @param string $script script to be rendered
     * @return string math node append
     */
    public function append_image($span, $script=null) {
        if (!$script) {
            $script = $span->nodeValue;
            $span->nodeValue = '';
        }
        // We don't need the url, but this method stores the script in database.
        $this->get_image_url($script);

        $filename = md5($script) . '.xml';
        $xml = $this->get_image($filename);

        // Copy math node to the original document.
        $mathml = $span->ownerDocument->importNode($this->load_document($xml)->getElementsByTagName('math')->item(0), true);
        return $span->appendChild($mathml);
    }

    /**
     * Render the script into an xml mathml file
     * @param string $script script to be rendered
     * @param string $filename md5 hash of the script
     * @return string math node
     */
    public function render($script, $filename) {
        $log = null;
        $this->get_temp();

        $pathlatexmlmath = get_config('math_latexml', 'pathlatexmlmath');
        if (!file_exists($pathlatexmlmath)) {
            return;
        }
        $pathlatexmlmath = escapeshellarg($pathlatexmlmath);

        $xml = $this->get_image_cache() . "/$filename.xml";

        // Run latexmlmath on TeX source.
        $command = "{$pathlatexmlmath} --presentationmathml='$xml' '\($script\)'";
        if ($this->execute($command, $log )) {
            return false;
        }

        return $xml;
    }
}

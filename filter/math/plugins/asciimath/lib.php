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

require('ASCIIMath2TeX.php');

class math_asciimath extends filter_math_plugin {
    public $imgformat = 'svg';
    public $type = 'math/asciimath';
    protected $amt;
    protected $texplugin;

    public function __construct() {
        $this->amt = new AMtoTeX();
        $this->texplugin = filter_math_plugin::get('tex2svg');
    }


    public function process ($span) {
        $script = $span->nodeValue;
        $texexp = $this->amt->convert($script);
        $preview = $this->append_image($span, $script);
        $preview->setAttribute('class', 'MathJax_Preview');
        $preview->firstChild->setAttribute('alt', $script);
        $preview->firstChild->setAttribute('title', $script);
        $mathjax = $span->ownerDocument->createElement('script');
        $mathjax->setAttribute('type', 'math/asciimath');
        $mathjax->nodeValue = $script;
        $span->appendChild($mathjax);
    }

    /*
     *  Use TeX plugin to do image rendering by overriding method.
     */
    public function get_image_url($script, array $options = array()) {
        return $this->texplugin->get_image_url($this->amt->convert($script));
    }

}

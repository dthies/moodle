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

/**
 * Gnuplot filtering class.
 */
class math_gnuplot extends filter_math_plugin {
    public $filter = 'gnuplot';
    public $imgformat = 'png';

    public function __construct() {
        $this->type = 'graph/gnuplot';
    }

    /**
     * Convert contents of an element contents from a script to a formated
     * image reference to the script in the data base.
     * @param DOMNode $span a DOM Node that contains the script
     */
    public function process($span) {
        $this->append_image($span);
    }

    /**
     * Render the script into a image
     * @param string $script Script to be rendered
     * @param string $filename md5 hash of the script
     * @return string image file pathname
     */
    public function render($script, $filename) {
        $pathname = $this->get_image_cache() . "/{$filename}.png";
        $script = "set terminal png size " . $this->get_config('imgwidth') . "," .
            $this->get_config('imgheight') . "; set output \"$pathname\"; $script";
        $pathgnuplot = escapeshellarg(get_config('filter_math', 'pathgnuplot'));
        $command = "$pathgnuplot -e '$script'";
        if (!$this->execute($command)) {
            return $pathname;
        }
    }
}


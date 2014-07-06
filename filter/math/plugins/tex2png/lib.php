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
 * TeX filtering class.
 */
class math_tex2png extends filter_math_plugin {
    public $filter = 'tex2png';
    public $imgformat = 'png';
    public $type = 'math/tex';

    public function __construct() {
        // Findpaths to Nepbm execuables.
        $netpbm = get_config('filter_math', 'pathnetpbm');
        $this->pathpstopnm = escapeshellarg($netpbm . '/pstopnm');
        $this->pathpnmcrop = escapeshellarg($netpbm . '/pnmcrop');
        $this->pathpnmscale = escapeshellarg($netpbm . '/pnmscale');
        $this->pathpnmtopng = escapeshellarg($netpbm . '/pnmtopng');
    }

    public function process($span) {
        $script = $span->nodeValue;
        $class = $span->getAttribute('class');
        $mathjax = $span->ownerDocument->createElement('script');
        $mathjax->nodeValue = $script;
        $mathjax->setAttribute('type', $this->type);
        if ($class == 'filter-math-tex-display') {
            $mathjax->setAttribute('type', 'math/tex; mode=display');
            $preview = $this->append_image($span,  $script);
            $displaydiv = $span->parentNode->insertBefore($span->ownerDocument->createElement('div'), $span);
            $displaydiv->setAttribute('style', 'text-align: center');
            $span = $displaydiv->appendChild($span);
        } else {
            $mathjax->setAttribute('type', 'math/tex');
            $preview = $this->append_image($span);
        }
        $preview->setAttribute('class', 'MathJax_Preview');
        $span->appendChild($mathjax);

    }

    public function render($script, $filename) {
        $log = null;
        $this->get_temp();

        $pathlatex = get_config('filter_tex', 'pathlatex');
        if (!file_exists($pathlatex)) {
            return;
        }
        $pathlatex = escapeshellarg($pathlatex);

        // Construct some file paths.
        $tex = $this->get_image_cache() . "/$filename.tex";
        $dvi = $this->get_temp() . "/$filename.dvi";
        $ps = $this->get_temp() . "/$filename.ps";
        $img = $this->get_image_cache() . "/$filename.{$this->imgformat}";

        // Turn the latex doc into a .tex file in the temp area.
        $doc = $this->construct_latex_document($script);
        file_put_contents($tex, $doc);

        // Run latex on document.
        $command = "{$pathlatex} --interaction=nonstopmode --halt-on-error -output-directory=" . $this->get_temp() . " $tex";
        $this->execute($command, $log);

        // Run dvips (.dvi to .ps).
        $pathdvips = escapeshellarg(get_config('filter_tex', 'pathdvips'));
        $command = "$pathdvips -E $dvi -o $ps";
        $this->execute($command, $log);

        $scale = (int)get_config('math_tex2png', 'scale');

        $command = "$this->pathpstopnm -stdout -portrait -ysize " .
            $this->get_image_height($ps) * 27 * $scale / 1000 .
            " -stdout $ps | $this->pathpnmcrop | $this->pathpnmscale -reduce 16 | $this->pathpnmtopng -transparent white > $img";

        if ($this->execute($command, $log )) {
            return false;
        }

        return $img;
    }

    /**
     * Turn the bit of TeX into a valid latex document
     * @param string $forumula the TeX formula
     * @param int $fontsize the font size
     * @return string the latex document
     */
    public function construct_latex_document( $formula, $fontsize=12 ) {
        global $CFG;

        // $formula = filter_tex_sanitize_formula($formula);

        $doc = "\\documentclass[{$fontsize}pt]{article}\n";
        $doc .= get_config('filter_tex', 'latexpreamble');
        $doc .= "\\pagestyle{empty}\n";
        $doc .= "\\begin{document}\n";
        if (preg_match("/^[[:space:]]*\\\\begin\\{(gather|align|alignat|multline).?\\}/i", $formula)) {
            $doc .= "$formula\n";
        } else {
            $doc .= "\\( {$formula} \\)\n";
        }
        $doc .= "\\end{document}\n";
        return $doc;
    }

    /**
     * Find the relative height of the image from the PostScript bounding box.
     * @param string Postscript filename
     * @return int height of the object from bounding box
     */
    protected function get_image_height($ps) {
        preg_match('/BoundingBox: *([\d]+) +([\d]+) +([\d]+) +([\d]+)/', file_get_contents($ps), $matches);
        if ($matches) {
            return ((int)$matches[4] - (int)$matches[2]);
        }
    }

    /**
     * Find the relative width of the image from the PostScript bounding box.
     * @param string Postscript filename
     * @return int width of the object from bounding box
     */
    protected function get_image_width($ps) {
        preg_match('/BoundingBox: *([\d]+) +([\d]+) +([\d]+) +([\d]+)/', file_get_contents($ps), $matches);
        if ($matches) {
            return ((int)$matches[3] - (int)$matches[1]);
        }
    }

}



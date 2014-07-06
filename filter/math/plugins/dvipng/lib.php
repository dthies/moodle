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
class math_dvipng extends filter_math_plugin {
    public $filter = 'dvipng';
    public $imgformat = 'png';
    public $type = 'math/tex';

    public function process($span) {
        $script = $span->nodeValue;
        $class = $span->getAttribute('class');
        $mathjax = $span->ownerDocument->createElement('script');
        $mathjax->nodeValue = $script;
        $mathjax->setAttribute('type', $this->type);
        $preview = $this->append_image($span);
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

        $doc = $this->construct_latex_document($script);
        // Construct some file paths.
        $tex = $this->get_temp() . "/$filename.tex";
        $dvi = $this->get_temp() . "/$filename.dvi";
        $convertformat = 'png';
        $img = $this->get_image_cache() . "/$filename.{$convertformat}";

        // Turn the latex doc into a .tex file in the temp area.
        file_put_contents($tex, $doc);
        // Run latex on document.
        $command = "{$pathlatex} --interaction=nonstopmode --halt-on-error -output-directory=" . $this->get_temp() . " $tex";
        $this->execute($command, $log);

        // Run dvipng on document (.dvi to .png).
        $scale = (int)$this->get_config('scale') / 10;
        $pathdvipng = escapeshellarg(get_config('math_dvipng', 'pathdvipng'));
        $command = "{$pathdvipng} $dvi -o $img";
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

        // $fontsize does not affect formula's size. $density can change size
        $doc = "\\documentclass[{$fontsize}pt]{article}\n";
        $doc .= get_config('filter_tex', 'latexpreamble');
        $doc .= "\\pagestyle{empty}\n";
        $doc .= "\\begin{document}\n";
        $doc .= "\\topmargin -0.6in\n";
        $doc .= "\\oddsidemargin -0.6in\n";
        if (preg_match("/^[[:space:]]*\\\\begin\\{(gather|align|alignat|multline).?\\}/i", $formula)) {
            $doc .= "$formula\n";
        } else {
            $doc .= "$ {$formula} $\n";
        }
        $doc .= "\\end{document}\n";
        return $doc;
    }

}



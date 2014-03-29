YUI.add('moodle-filter_tex-mathjax', function (Y, NAME) {

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
 * Block for displaying mathematics using TeX filter and MathJax in cooperation
 *
 * @package    filter_tex
 * @copyright  2014 onwards Daniel Thies <dthies@ccal.edu>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author     Daniel Thies <dthies@ccal.edu>
 */

M.filter_tex = M.filter_tex || {};
M.filter_tex.showSource = function(){
        Y.all('.texrender').each(function(i){
            Y.one('body').insert('<span class="texsource" style="display: inline">\\['+i.getAttribute('alt')+'\\]</span> ',i.ancestor());
            i.setStyle('display','none');
        });
};
M.filter_tex.init = function(/* params */){
    MathJax.Hub.Config({
        tex2jax: {
            inlineMath: [ ['$$','$$'], ['\\(','\\)'], ['%i','%i'] ],
            displayMath: [ ['\\[$ $$','$$ $\\]'], ['\\[','\\]'], ['%d','%d'] ]
        }
    });
    MathJax.Hub.Configured();
/*
    if(params.displaytype/2===1){MathJax.Hub.setRenderer('HTML-CSS');}
    if(params.displaytype/2===2){MathJax.Hub.setRenderer('NativeMML');}
    if(params.displaytype/2===3){MathJax.Hub.setRenderer('SVG');}
*/

    MathJax.Hub.Queue( function(){
        Y.all('.texrender').each(function(i){
            var texSrc = '\\('+i.getAttribute('alt')+'\\)';
            texSrc = texSrc.replace( /\\\(\$ \$\$(.*)\$\$ \$\\\)/, '\\[$1\\]');
            Y.one('body').insert(
                '<span class="texsource" style="display: none">'
                +texSrc
                +'</span> ',i.ancestor());
        });
    });
MathJax.Hub.Queue(['Typeset',MathJax.Hub]);
    MathJax.Hub.Queue(function(){
        Y.all('.texrender').each(function(i){
            i.setStyle('display','none');
        });
        Y.all('.texsource').each(function(i){
            i.setStyle('display','inline');
        });
    });
};


}, '@VERSION@', {"requires": ["base", "node"]});

YUI.add('moodle-atto_indent-button', function (Y, NAME) {

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
 * @package    atto_indent
 * @copyright  2013 Damyon Wiese  <damyon@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * @module     moodle-atto_indent-button
 */

/**
 * Atto text editor indent plugin.
 *
 * @namespace M.atto_indent
 * @class button
 * @extends M.editor_atto.EditorPlugin
 */

Y.namespace('M.atto_indent').Button = Y.Base.create('button', Y.M.editor_atto.EditorPlugin, [], {
    initializer: function() {

        this.addButton({
            icon: 'e/decrease_indent',
            title: 'outdent',
            buttonName: 'outdent',
            callback: this.outdent
        });

        this.addButton({
            icon: 'e/increase_indent',
            title: 'indent',
            buttonName: 'indent',
            callback: this.indent
        });
    },

    /**
     * Indents the currently selected content.
     *
     * @method indent
     */
    indent: function() {
        // Save the current selection - we want to restore this.
        var selection = rangy.saveSelection();

        // Remove display:none from rangy markers so browser doesn't delete them.
        this.editor.all('.rangySelectionBoundary').setStyle('display', null);

        // Mark all existing block quotes in case the user has actually added some.
        this.editor.all('blockquote').addClass('pre-existing');

        // Run the indent command.
        document.execCommand('indent', false, null);

        // Any new blockquote it should be marked with indent class.
        this.editor.all('blockquote').addClass('editor-indent');
        this.editor.all('.pre-existing').removeClass('editor-indent');

        // Clean pre-existing blockquote classes.
        this.editor.all('blockquote[class="pre-existing"]').removeAttribute('class');
        this.editor.all('blockquote.pre-existing').removeClass('pre-existing');

        // Set correct margin.
        var margindir = (Y.one('body.dir-ltr')) ? 'marginLeft' : 'marginRight';
        this.editor.all('blockquote.editor-indent').setStyle(margindir, '30px');

        // Change new indent to a div.
        this.get('host').replaceTags(this.editor.all('blockquote.editor-indent'), 'div');

        // Restore the original selection.
        rangy.restoreSelection(selection);

        // Remove the selection markers - a clean up really.
        rangy.removeMarkers(selection);

        // Mark the text as having been updated.
        this.markUpdated();
    },

    /**
     * Outdents the currently selected content.
     *
     * @method outdent
     */
    outdent: function() {
        // Save the selection we will want to restore it.
        var selection = rangy.saveSelection();

        // Remove display:none from rangy markers so browser doesn't delete them.
        this.editor.all('.rangySelectionBoundary').setStyle('display', null);
 
        // Chrome/Safari do not deal well with blockquotes multiple child nodes.
        this.get('host').divideBlockquotes();

        // Replace existing blockquotes so the browser does not outdent them.
        this.editor.all('blockquote').addClass('pre-existing');
        this.get('host').replaceTags(this.editor.all('.pre-existing'), 'div');

        // Replace all div indents with blockquote indents so that we can rely on the browser functionality.
        this.get('host').replaceTags(this.editor.all('.editor-indent'), 'blockquote');

        // Restore the users selection and save again.
        rangy.restoreSelection(selection);
        selection = rangy.saveSelection();
        this.editor.all('.rangySelectionBoundary').setStyle('display', null);

        // Outdent the blockquotes now with method safe across browswers.
        this.get('host').safeOutdent();

        // Mark the remaining blockquotes as indents once again.
        this.editor.all('blockquote').addClass('editor-indent');

        // Set correct margin.
        var margindir = (Y.one('body.dir-ltr')) ? 'marginLeft' : 'marginRight';
        this.editor.all('blockquote.editor-indent').setStyle(margindir, '30px');

        // Change blockquote indent to a div.
        this.get('host').replaceTags(this.editor.all('blockquote.editor-indent'), 'div');

        // Restore pre-existant blockquotes and remove marker class.
        this.get('host').replaceTags(this.editor.all('.pre-existing'), 'blockquote');

        // Merge the neighboring blockquotes again.
        this.get('host').mergeBlockquotes();

        // Remove marker classes.
        this.editor.all('[class="pre-existing"]').removeAttribute('class');
        this.editor.all('.pre-existing').removeClass('pre-existing');

        // Restore the selection again.
        rangy.restoreSelection(selection);

        // Clean up any left over selection markers.
        rangy.removeMarkers(selection);

        // Mark the text as having been updated.
        this.markUpdated();
    }
});


}, '@VERSION@', {"requires": ["moodle-editor_atto-plugin"]});

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
 * @module moodle-gradereport_grader-gradereport_grader
 * @submodule highlighter
 */

/**
 * Functions for the Grader Report Row and Column highlighter
 *
 * See {{#crossLink "M.gradereport_grader.ReportTable"}}{{/crossLink}} for details.
 *
 * @namespace M.gradereport_grader
 * @class Highlighter
 */

var COLMARK = 'vmarked',
    UIDMARK = 'hmarked';

function Highlighter() {}

Highlighter.ATTRS= {
};

Highlighter.prototype = {
    /**
     */
    setupHighlighter: function() {
        // Clicking on the cell should highlight the row.
        this.graderRegion.delegate('click', this._highlightUser, 'th.user, th.userreport, th.userfield, .gradebook-user-cell', this);

        // Clicking on the cell should highlight the current column.
        this.graderRegion.delegate('click', this._highlightColumn, 'th.item[data-column], .gradebook-header-cell', this);

    },

    /**
     * Highlight the current assignment column.
     *
     * @method _highlightColumn
     * @param {EventFacade} e The Event fired. This describes the column to highlight.
     * @protected
     */
    _highlightColumn: function(e) {
        var column = e.target.getData('column');

        if (typeof column === 'undefined') {
            // Unable to determine which user to highlight. Return early.
            return;
        }

        Y.all('td.cell[data-column="' + column + '"]').toggleClass(COLMARK);
    },

    /**
     * Highlight the current user row.
     *
     * @method _highlightUser
     * @param {EventFacade} e The Event fired. This describes the user to highlight.
     * @protected
     */
    _highlightUser: function(e) {
        var tableRow = e.target.ancestor('[data-uid]', true),
            uid;

        if (tableRow) {
            uid = tableRow.getData('uid');
        }

        if (typeof uid === 'undefined') {
            // Unable to determine which user to highlight. Return early.
            return;
        }

        Y.all('td.cell[data-uid="' + uid + '"]').toggleClass(UIDMARK);
    }
};

Y.Base.mix(Y.M.gradereport_grader.ReportTable, [Highlighter]);

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
 * Static header and student column for grader table.
 *
 * @module    moodle-gradereport_grader-gradereporttable
 * @package   gradereport_grader
 * @copyright 2014 UC Regents
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Alfonso Roman <aroman@oid.ucla.edu>
 */

/**
 * @module moodle-gradereport_grader-gradereporttable
 */

var SELECTORS = {
        FOOTERROW: '#user-grades .avg',
        GRADECELL: 'td.cell',
        HEADERCELL: '.gradebook-header-cell',
        STUDENTHEADER: '#studentheader',
        SPINNER: '.gradebook-loading-screen',
        USERCELL: '#user-grades .user.cell'
    },
    CSS = {
        COLMARK: 'vmarked',
        UIDMARK: 'hmarked',
        STICKYFOOTER: 'gradebook-footer-row-sticky'
    };

/**
 * The Grader Report Table.
 *
 * @namespace M.gradereport_grader
 * @class ReportTable
 * @constructor
 */
function ReportTable() {
    ReportTable.superclass.constructor.apply(this, arguments);
}

Y.extend(ReportTable, Y.Base, {
    /**
     * Array of EventHandles.
     *
     * @type EventHandle[]
     * @property _eventHandles
     * @protected
     */
    _eventHandles: [],

    /**
     * Setup the grader report table.
     *
     * @method init
     */
    initializer: function() {
        // Setup the floating headers.
        this.setupFloatingHeaders();

        // Hide the loading spinner - we've finished for the moment.
        this._hideSpinner();
    },

    /**
     * Show the loading spinner.
     *
     * @method showSpinner
     * @protected
     */
    showSpinner: function() {
        // Show the grading spinner.
        Y.one(SELECTORS.SPINNER).show();
    },

    /**
     * Hide the loading spinner.
     *
     * @method hideSpinner
     * @protected
     */
    hideSpinner: function() {
        // Hide the grading spinner.
        Y.one(SELECTORS.SPINNER).hide();
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

        Y.all('td.cell[data-column="' + column + '"]').toggleClass(CSS.COLMARK);
    },

    /**
     * Highlight the current user row.
     *
     * @method _highlightUser
     * @param {EventFacade} e The Event fired. This describes the user to highlight.
     * @protected
     */
    _highlightUser: function(e) {
        var uid = e.target.getData('uid');

        if (typeof uid === 'undefined') {
            // Unable to determine which user to highlight. Return early.
            return;
        }

        Y.all('td.cell[data-uid="' + uid + '"]').toggleClass(CSS.UIDMARK);
    }
});

Y.namespace('M.gradereport_grader').ReportTable = ReportTable;
Y.namespace('M.gradereport_grader').init = function(config) {
    return new Y.M.gradereport_grader.ReportTable(config);
};

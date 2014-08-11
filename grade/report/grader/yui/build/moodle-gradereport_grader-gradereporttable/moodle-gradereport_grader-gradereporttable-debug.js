YUI.add('moodle-gradereport_grader-gradereporttable', function (Y, NAME) {

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
        GRADECELL: 'td.grade',
        GRADERTABLE: '.gradeparent table',
        GRADEPARENT: '.gradeparent',
        HEADERCELL: '.gradebook-header-cell',
        STUDENTHEADER: '#studentheader',
        SPINNER: '.gradebook-loading-screen',
        USERCELL: '#user-grades .user.cell'
    },
    CSS = {
        OVERRIDDEN: 'overridden',
        STICKYFOOTER: 'gradebook-footer-row-sticky',
        TOOLTIPACTIVE: 'tooltipactive'
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
     * A Node reference to the grader table.
     *
     * @property graderTable
     * @type Node
     */
    graderTable: null,

    /**
     * Setup the grader report table.
     *
     * @method initializer
     */
    initializer: function() {
        // Some useful references within our target area.
        this.graderRegion = Y.one(SELECTORS.GRADEPARENT);
        this.graderTable = Y.one(SELECTORS.GRADERTABLE);

        // Setup row and column highlighting.
        this.setupHighlighter();

        // Setup the floating headers.
        this.setupFloatingHeaders();

        // Setup the mouse tooltips.
        this.setupTooltips();

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
     * Get the text content of the username for the specified grade item.
     *
     * @method getGradeUserName
     * @param {Node} cell The grade item cell to obtain the username for
     * @return {String} The string content of the username cell.
     */
    getGradeUserName: function(cell) {
        var userrow = cell.ancestor('tr'),
            usercell = userrow.one("th.user .username");

        if (usercell) {
            return usercell.get('text');
        } else {
            return '';
        }
    },

    /**
     * Get the text content of the item name for the specified grade item.
     *
     * @method getGradeItemName
     * @param {Node} cell The grade item cell to obtain the item name for
     * @return {String} The string content of the item name cell.
     */
    getGradeItemName: function(cell) {
        var itemcell = Y.one("th.item[data-column='" + cell.getData('column') + "']");
        if (itemcell) {
            return itemcell.get('text');
        } else {
            return '';
        }
    },

    /**
     * Get the text content of any feedback associated with the grade item.
     *
     * @method getGradeFeedback
     * @param {Node} cell The grade item cell to obtain the item name for
     * @return {String} The string content of the feedback.
     */
    getGradeFeedback: function(cell) {
        return cell.getData('feedback');
    }
});

Y.namespace('M.gradereport_grader').ReportTable = ReportTable;
Y.namespace('M.gradereport_grader').init = function(config) {
    return new Y.M.gradereport_grader.ReportTable(config);
};
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
 * @submodule tooltip
 */

/**
 * Functions for the Grader Report Grade Editor.
 *
 * See {{#crossLink "M.gradereport_grader.ReportTable"}}{{/crossLink}} for details.
 *
 * @namespace M.gradereport_grader
 * @class Tooltip
 */

var OFFSETWIDTH = 'offsetWidth',
    OFFSETHEIGHT = 'offsetHeight',
    OFFSETLEFT = 'offsetLeft',
    OFFSETPARENT = 'offsetParent';

function FloatingHeaders() {}

FloatingHeaders.ATTRS= {
};

FloatingHeaders.prototype = {
    /**
     * A Node representing the header cell.
     *
     * @property headerCell
     * @type Node
     * @protected
     */
    headerCell: null,

    /**
     * A Node representing the first cell which contains user name information.
     *
     * @property firstUserCell
     * @type Node
     * @protected
     */
    firstUserCell: null,

    /**
     * A Node representing the original table footer row.
     *
     * @property tableFooterRow
     * @type Node
     * @protected
     */
    tableFooterRow: null,

    /**
     * A Node representing the floating footer row in the grading table.
     *
     * @property footerRow
     * @type Node
     * @protected
     */
    footerRow: null,

    /**
     * A Node representing the floating assignment header.
     *
     * @property assignmentHeadingContainer
     * @type Node
     * @protected
     */
    assignmentHeadingContainer: null,

    /**
     * A Node representing the floating user header. This is the header with the Surname/First name
     * sorting.
     *
     * @property userColumnHeader
     * @type Node
     * @protected
     */
    userColumnHeader: null,

    /**
     * A Node representing the floating user column. This is the column containing all of the user
     * names.
     *
     * @property userColumn
     * @type Node
     * @protected
     */
    userColumn: null,

    /**
     * The position of the top of the first user cell.
     * This is used when processing the scroll event as an optimisation. It must be updated when
     * additional rows are loaded, or the window changes in some fashion.
     *
     * @property firstUserCellTop
     * @type Node
     * @protected
     */
    firstUserCellTop: 0,

    /**
     * The position of the left of the first user cell.
     * This is used when processing the scroll event as an optimisation. It must be updated when
     * additional rows are loaded, or the window changes in some fashion.
     *
     * @property firstUserCellLeft
     * @type Node
     * @protected
     */
    firstUserCellLeft: 0,

    /**
     * The position of the top of the final user cell.
     * This is used when processing the scroll event as an optimisation. It must be updated when
     * additional rows are loaded, or the window changes in some fashion.
     *
     * @property lastUserCellTop
     * @type Node
     * @protected
     */
    lastUserCellTop: 0,

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
    setupFloatingHeaders: function() {
        // Grab references to commonly used Nodes.
        this.firstUserCell = Y.one(SELECTORS.USERCELL);

        if (!this.firstUserCell) {
            // There was no first user cell - no need to do anything at this stage.
            this._hideSpinner();
            return;
        }

        // Generate floating elements.
        this._setupFloatingUserColumn();
        this._setupFloatingUserHeader();
        this._setupFloatingAssignmentHeaders();
        this._setupFloatingAssignmentFooter();

        // Calculate the positions of edge cells. These are used for positioning of the floating headers.
        // This must be called after the floating headers are setup, but before the scroll event handler is invoked.
        this._calculateCellPositions();

        // Setup the floating element initial positions by simulating scroll.
        this._handleScrollEvent();

        // Setup the event handlers.
        this._setupEventHandlers();

        // Hide the loading spinner - we've finished for the moment.
        this._hideSpinner();
    },

    /**
     * Calculate the positions of some cells. These values are used heavily
     * in scroll event handling.
     *
     * @method _calculateCellPositions
     * @protected
     */
    _calculateCellPositions: function() {
        // The header row shows the assigment headers and is floated to the top of the window.
        // WAS: var headerCellTop = this.headerCell.get(OFFSETTOP) + this.headerCell.get(OFFSETPARENT).get(OFFSETTOP);
        this.headerCellTop = this.headerCell.getY();

        var userCellList = Y.all(SELECTORS.USERCELL);

        if (userCellList.size() > 1) {
            // Use the top of the second cell for the bottom of the first cell.
            // This is used when scrolling to fix the footer to at the top-left edge of the window.
            this.firstUserCellTop = userCellList.item(1).getY();
            // WAS: var firstUserCellPosition = this.firstUserCell.get(OFFSETLEFT) + this.firstUserCell.get(OFFSETPARENT).get(OFFSETLEFT);
            this.firstUserCellLeft = userCellList.item(0).getX();

            // Use the top of the penultimate cell when scrolling the header.
            // The header is the same size as the cells.
            this.lastUserCellTop = userCellList.item(userCellList.size() - 2).getY();
        } else {
            // TODO Fix to the current position.
            this.firstUserCell = 0;
            this.lastUserCellTop = 0;
        }

        // The footer row shows the grade averages and will be floated to the page bottom.
        if (this.tableFooterRow) {
            // WAS: lastrowpos = lastrow.offsetTop + lastrow.offsetParent.offsetTop;
            this.footerRowPosition = this.tableFooterRow.getY();
        }
    },

    /**
     * Setup the main event listeners.
     * These deal with things like window events.
     *
     * @method _setupEventHandlers
     * @protected
     */
    _setupEventHandlers: function() {
        this._eventHandles.push(
            // Listen for window scrolls, resizes, and rotation events.
            Y.one(Y.config.win).on('scroll', this._handleScrollEvent, this),
            Y.one(Y.config.win).on('resize', this._handleResizeEvent, this),
            Y.one(Y.config.win).on('orientationchange', this._handleResizeEvent, this)
        );
    },

    /**
     * Show the loading spinner.
     *
     * @method _showSpinner
     * @protected
     */
    _showSpinner: function() {
        // Show the grading spinner.
        Y.one(SELECTORS.SPINNER).show();
    },

    /**
     * Hide the loading spinner.
     *
     * @method _hideSpinner
     * @protected
     */
    _hideSpinner: function() {
        // Hide the grading spinner.
        Y.one(SELECTORS.SPINNER).hide();
    },

    /**
     * Create and setup the floating column of user names.
     *
     * @method _setupFloatingUserColumn
     * @protected
     */
    _setupFloatingUserColumn: function() {
        // Grab all cells in the user names column.
        var userColumn = Y.all(SELECTORS.USERCELL),

        // TODO check ARIA - should this be linked to the row somehow?
        // Create a floating table.
            floatingUserColumn = Y.Node.create('<div aria-hidden="true" id="gradebook-user-container"></div>');

        // Generate the new fields.
        userColumn.each(function(node) {
            // Create and configure the new container.
            var containerNode = Y.Node.create('<div class="gradebook-user-cell"></div>');
            containerNode.set('innerHTML', node.get('innerHTML'))
                    .setAttribute('data-uid', node.ancestor('tr').getData('uid'))
                    .setStyles({
                        height: node.get(OFFSETHEIGHT) + 'px',
                        width:  node.get(OFFSETWIDTH) + 'px'
                    });

            // Add the new nodes to our floating table.
            floatingUserColumn.appendChild(containerNode);
        }, this);

        // Style the floating user container.
        floatingUserColumn.setStyles({
            left:       this.firstUserCell.getX() + 'px',
            position:   'absolute',
            top:        this.firstUserCell.getY() + 'px'
        });

        // Append to the grader region.
        this.graderRegion.append(floatingUserColumn);

        // Store a reference to this for later - we use it in the event handlers.
        this.userColumn = floatingUserColumn;
    },

    /**
     * Create and setup the floating username header cell.
     *
     * @method _setupFloatingUserHeader
     * @protected
     */
    _setupFloatingUserHeader: function() {
        // We make various references to the this header cell. Store it for later.
        this.headerCell = Y.one(SELECTORS.STUDENTHEADER);

        // Float the 'user name' header cell.
        var floatingUserCell = Y.Node.create('<div aria-hidden="true" id="gradebook-user-header-container"></div>');

        // Append node contents
        floatingUserCell.set('innerHTML', this.headerCell.getHTML());
        floatingUserCell.setStyles({
            height:     this.headerCell.get(OFFSETHEIGHT) + 'px',
            left:       this.firstUserCell.getX() + 'px',
            position:   'absolute',
            top:        this.headerCell.getY() + 'px',
            width:      this.firstUserCell.get(OFFSETWIDTH) + 'px'
        });

        // Append to the grader region.
        this.graderRegion.append(floatingUserCell);

        // Store a reference to this for later - we use it in the event handlers.
        this.userColumnHeader = floatingUserCell;
    },

    /**
     * Create and setup the floating assignment header row.
     *
     * @method _setupFloatingAssignmentHeaders
     * @protected
     */
    _setupFloatingAssignmentHeaders: function() {
        var gradeHeaders = Y.all('#user-grades tr.heading .cell');

        // Generate a floating headers
        var floatingGradeHeaders = Y.Node.create('<div aria-hidden="true" id="gradebook-header-container"></div>');

        var floatingGradeHeadersWidth = 0;
        var floatingGradeHeadersHeight = 0;
        var gradeHeadersOffset = this.headerCell.getX();

        gradeHeaders.each(function(node) {
            var nodepos = node.getX();

            var newnode = Y.Node.create('<div class="gradebook-header-cell"></div>');
            newnode.append(node.getHTML())
                    .addClass(node.getAttribute('class'))
                    .setData('column', node.getData('column'))
                    .setStyles({
                        height:     node.get(OFFSETHEIGHT) + 'px',
                        left:       (nodepos - gradeHeadersOffset) + 'px',
                        position:   'absolute',
                        width:      node.get(OFFSETWIDTH) + 'px'
                    });

            // Sum up total widths - these are used in the container styles.
            floatingGradeHeadersWidth += parseInt(node.get(OFFSETWIDTH), 10);
            floatingGradeHeadersHeight = node.get(OFFSETHEIGHT);

            // Append to our floating table.
            floatingGradeHeaders.appendChild(newnode);
        }, this);

        // Position header table.
        floatingGradeHeaders.setStyles({
            height:     floatingGradeHeadersHeight + 'px',
            left:       this.headerCell.getX() + 'px',
            position:   'absolute',
            top:        this.headerCell.getY() + 'px',
            width:      floatingGradeHeadersWidth + 'px'
        });

        // Insert in place before the grader headers.
        this.userColumnHeader.insert(floatingGradeHeaders, 'before');

        // Store a reference to this for later - we use it in the event handlers.
        this.assignmentHeadingContainer = floatingGradeHeaders;
    },

    /**
     * Create and setup the floating header row of assignment titles.
     *
     * @method _setupFloatingAssignmentFooter
     * @protected
     */
    _setupFloatingAssignmentFooter: function() {
        this.tableFooterRow = Y.one('#user-grades .avg');
        if (!this.tableFooterRow) {
            Y.log('Averages footer not found - unable to float it.', 'warn', LOGNS);
            return;
        }

        // Generate the sticky footer row.
        var footerCells = this.tableFooterRow.all('.cell');

        // Create a container.
        var floatingGraderFooter = Y.Node.create('<div aria-hidden="true" id="gradebook-footer-container"></div>');
        var footerWidth = 0;
        var footerRowOffset = this.tableFooterRow.getX();

        // Copy cell content.
        footerCells.each(function(node) {
            var newnode = Y.Node.create('<div class="gradebook-footer-cell"></div>');
            newnode.set('innerHTML', node.getHTML());
            newnode.setStyles({
                height:     node.get(OFFSETHEIGHT) + 'px',
                left:       (node.getX() - footerRowOffset) + 'px',
                position:   'absolute',
                width:      node.get(OFFSETWIDTH) + 'px'
            });

            floatingGraderFooter.append(newnode);
            footerWidth += parseInt(node.get(OFFSETWIDTH), 10);
        }, this);

        // Attach 'Update' button.
        var update_button = Y.one('#gradersubmit');
        if (update_button) {
            // TODO decide what to do with classes here to make them compatible with the base themes.
            var button = Y.Node.create('<button class="btn btn-sm btn-default">' + update_button.getAttribute('value') + '</button>');
            button.on('click', function() {
                YUI().use('node-event-simulate', function(Y) {
                    // TODO ICKKKK Remove this. Simulate is not intended for production - it's a
                    // module for testing.
                    Y.one('#gradersubmit').simulate('click');
                });
            });
            floatingGraderFooter.one('.gradebook-footer-cell').append(button);
        }

        // Position the row
        floatingGraderFooter.setStyles({
            position:   'absolute',
            left:       this.tableFooterRow.getX() + 'px',
            bottom:     0,
            height:     '40px',
            width:      footerWidth + 'px'
        });

        // Append to the grader region.
        this.graderRegion.append(floatingGraderFooter);

        this.footerRow = floatingGraderFooter;
    },

    /**
     * Process a Scroll Event on the window.
     *
     * @method _handleScrollEvent
     */
    _handleScrollEvent: function() {
        // Performance is important in this function as it is called frequently and in quick succesion.
        // To prevent layout thrashing when the DOM is repeatedly updated and queried, updated and queried,
        // updates must be batched.

        // Next do all the calculations.
        var assignmentHeadingContainerStyles = {},
            userColumnHeaderStyles = {},
            userColumnStyles = {},
            footerStyles = {};

        // Header position.
        assignmentHeadingContainerStyles.left = this.headerCell.get(OFFSETLEFT) + this.headerCell.get(OFFSETPARENT).get(OFFSETLEFT) + 'px';
        if (Y.config.win.pageYOffset + 40 > this.headerCellTop) {
            if (Y.config.win.pageYOffset + 40 < this.lastUserCellTop) {
                assignmentHeadingContainerStyles.top = Y.config.win.pageYOffset + 40 + 'px';
                userColumnHeaderStyles.top = Y.config.win.pageYOffset + 40 + 'px';
            } else {
                assignmentHeadingContainerStyles.top = this.lastUserCellTop + 'px';
                userColumnHeaderStyles.top = this.lastUserCellTop + 'px';
            }
        } else {
            assignmentHeadingContainerStyles.top = this.headerCellTop + 'px';
            userColumnHeaderStyles.top = this.headerCellTop + 'px';
        }

        // User column position.
        if (Y.config.win.pageXOffset > this.firstUserCellLeft) {
            userColumnStyles.left = Y.config.win.pageXOffset + 'px';
            userColumnHeaderStyles.left = Y.config.win.pageXOffset + 'px';
        } else {
            userColumnStyles.left = this.firstUserCellLeft + 'px';
            userColumnHeaderStyles.left = this.firstUserCellLeft + 'px';
        }

        // Update footer.
        if (this.footerRow) {
            footerStyles.left = this.headerCell.get(OFFSETLEFT) + this.headerCell.get(OFFSETPARENT).get(OFFSETLEFT) + 'px';

            // Determine whether the footer should now be shown as sticky.
            if (Y.config.win.pageYOffset + Y.config.win.innerHeight - 40 < this.footerRowPosition) {
                if (Y.config.win.pageYOffset + Y.config.win.innerHeight - 40 > this.firstUserCellTop) {
                    footerStyles.top = (Y.config.win.pageYOffset + Y.config.win.innerHeight - 40) + 'px';
                } else {
                    footerStyles.top = this.firstUserCellTop;
                }
                this.footerRow.addClass(CSS.STICKYFOOTER);
            } else {
                footerStyles.top = this.footerRowPosition + 'px';
                this.footerRow.removeClass(CSS.STICKYFOOTER);
            }
        }

        // Finally, apply the styles.
        this.assignmentHeadingContainer.setStyles(assignmentHeadingContainerStyles);
        this.userColumnHeader.setStyles(userColumnHeaderStyles);
        this.userColumn.setStyles(userColumnStyles);
        this.footerRow.setStyles(footerStyles);
    },

    /**
     * Process a size change Event on the window.
     *
     * @method _handleResizeEvent
     */
    _handleResizeEvent: function() {
        // Recalculate the position of the edge cells for scroll positioning.
        this._calculateCellPositions();

        // Simulate a scroll.
        this._handleScrollEvent();

        // Resize headers & footers.
        // This is an expensive operation, not expected to happen often.
        var headers = this.assignmentHeadingContainer.all(SELECTORS.HEADERCELL);
        var resizedcells = Y.all('#user-grades .heading .cell');

        var headeroffsetleft = this.headerCell.getX();
        var newcontainerwidth = 0;
        resizedcells.each(function(cell, idx) {
            var headercell = headers.item(idx);

            newcontainerwidth += cell.get(OFFSETWIDTH);
            var styles = {
                width: cell.get(OFFSETWIDTH),
                left: cell.getX() - headeroffsetleft + 'px'
            };
            headercell.setStyles(styles);
        });

        var footers = Y.all('#gradebook-footer-container .gradebook-footer-cell');
        if (footers.size() !== 0) {
            var resizedavgcells = Y.all('#user-grades .avg .cell');

            resizedavgcells.each(function(cell, idx) {
                var footercell = footers.item(idx);
                var styles = {
                    width: cell.get(OFFSETWIDTH),
                    left: cell.getX() - headeroffsetleft + 'px'
                };
                footercell.setStyles(styles);
            });
        }

        this.assignmentHeadingContainer.setStyle('width', newcontainerwidth);
    }
};

Y.Base.mix(Y.M.gradereport_grader.ReportTable, [FloatingHeaders]);
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
 * @submodule tooltip
 */

/**
 * Functions for the Grader Report Grade Editor.
 *
 * See {{#crossLink "M.gradereport_grader.ReportTable"}}{{/crossLink}} for details.
 *
 * @namespace M.gradereport_grader
 * @class Tooltip
 */

function Tooltip() {}

Tooltip.ATTRS= {
};

CONTENT = '<div class="graderreportoverlay {{overridden}}" role="tooltip" aria-describedby="{{id}}">' +
              '<div class="fullname">{{username}}</div><div class="itemname">{{itemname}}</div>' +
              '{{#if feedback}}' +
                  '<div class="feedback">{{feedback}}</div>' +
              '{{/if}}' +
          '</div>';

Tooltip.prototype = {
    _tooltip: null,
    _tooltipHandler: null,
    _tooltipBoundingBox: null,
    tooltipTemplate: null,
    setupTooltips: function() {
        this._eventHandles.push(
            this.graderTable.delegate('hover', this._showTooltip, this._hideTooltip, SELECTORS.GRADECELL, this),
            this.graderTable.delegate('click', this._toggleTooltip, SELECTORS.GRADECELL, this)
        );
    },
    _getTooltip: function() {
        if (!this._tooltip) {
            this._tooltip = new Y.Overlay({
                visible: false,
                render: Y.one(SELECTORS.GRADEPARENT)
            });
            this._tooltipBoundingBox = this._tooltip.get('boundingBox');
            this.tooltipTemplate = Y.Handlebars.compile(CONTENT);
        }
        return this._tooltip;
    },
    _showTooltip: function(e) {
        var cell = e.currentTarget;

        var tooltip = this._getTooltip();

        tooltip.set('bodyContent', this.tooltipTemplate({
                    cellid: cell.get('id'),
                    username: this.getGradeUserName(cell),
                    itemname: this.getGradeItemName(cell),
                    feedback: this.getGradeFeedback(cell),
                    overridden: cell.hasClass(CSS.OVERRIDDEN) ? CSS.OVERRIDDEN : ''
                }))
                .set('xy', [
                    cell.getX() + (cell.get('offsetWidth') / 2),
                    cell.getY() + (cell.get('offsetHeight') / 2)
                ])
                .show();
        e.currentTarget.addClass(CSS.TOOLTIPACTIVE);
    },
    _hideTooltip: function(e) {
        if (e.relatedTarget && this._tooltipBoundingBox && this._tooltipBoundingBox.contains(e.relatedTarget)) {
            // Do not exit if the user is mousing over the tooltip itself.
            return;
        }
        if (this._tooltip) {
            e.currentTarget.removeClass(CSS.TOOLTIPACTIVE);
            this._tooltip.hide();
        }
    },
    _toggleTooltip: function(e) {
        if (e.currentTarget.hasClass(CSS.TOOLTIPACTIVE)) {
            this._hideTooltip(e);
        } else {
            this._showTooltip(e);
        }
    }
};

Y.Base.mix(Y.M.gradereport_grader.ReportTable, [Tooltip]);


}, '@VERSION@', {"requires": ["base", "node", "event", "handlebars", "overlay", "event-hover", "node-event-simulate"]});

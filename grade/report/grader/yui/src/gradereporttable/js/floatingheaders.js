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

function FloatingHeaders() {}

FloatingHeaders.ATTRS= {
};

FloatingHeaders.prototype = {

    /**
     * A Node representing the first cell which contains user name information.
     *
     * @property _cachedNode_firstUserCell
     * @type Node
     * @protected
     */
    _cachedNode_firstUserCell: null,
    _cachedNode_firstHeaderCell: null,
    _cachedNode_lastUserCell: null,

    /**
     * A Node representing the first cell which contains user name information.
     *
     * @property _cachedNode_navbar
     * @type Node
     * @protected
     */
    _cachedNode_navbar: null,
    _cachedNode_tableFooterRow: null,


    /**
     * Container and inner nodes representing the fixed menus.
     *
     * @property _menuNodes
     * @type Node
     * @protected
     */
    _menuNodes: {
        userColumn: null,
        userColumnHeader: null,
        assignmentHeaders: null,
        averageFooters: null
    },

    /**
     * The position of the top of the first user cell.
     * This is used when processing the scroll event as an optimisation. It must be updated when
     * additional rows are loaded, or the window changes in some fashion.
     *
     * @property _firstUserCellTop
     * @type Node
     * @protected
     */
    _firstUserCellTop: 0,
    _firstUserCellLeft: 0,
    _firstUserCellHeight: 0,
    _firstUserCellWidth: 0,

    _firstHeaderCellTop: 0,
    _firstHeaderCellHeight: 0,

    _lastUserCellTop: 0,
    /**
     * The width of the user column.
     *
     * @property _userColumnWidth
     * @type Node
     * @protected
     */
    _userColumnWidth: 211,

    /**
     * The height of the navbar, if present.
     *
     * @property _navbarOffset
     * @type Node
     * @protected
     */
    _navbarOffset: 0,

    /**
     * Array of EventHandles.
     *
     * @type EventHandle[]
     * @property _eventHandles
     * @protected
     */
    _eventHandles: [],

    /**
     * Process a size change Event on the window.
     *
     * @method _handleResizeEvent
     */
    _handleResizeEvent: function() {
        // OR together vendor-specific implementations of requestAnimationFrame as needed.
        window.requestAnimationFrame =
            window.requestAnimationFrame ||
            window.mozRequestAnimationFrame ||
            window.webkitRequestAnimationFrame ||
            window.oRequestAnimationFrame;

        if (typeof(window.requestAnimationFrame === "function")) {
            // Perform scroll event handling in one animation frame on Webkit/Gecko/IE10+.
            var me = this;
            window.requestAnimationFrame(function(timestamp){
                me._handleResizeEventInternal.call(me, timestamp);
            });
        } else {
            // Perform, albeit badly on IE9.
            this._handleResizeEventInternal.call(this);
        }
    },
    _handleResizeEventInternal: function() {

        // Simulate a touch, to hide the menus.
        this._handleTouchStartEvent();

        // Recalculate the position of the edge cells for scroll positioning.
        this._calculateCellPositions();

        // Simulate a scroll.
        this._handleScrollEventInternal();

        // Resize headers & footers.
        // This is an expensive operation, not expected to happen often.
        var headers = this._menuNodes.assignmentHeaders.all(SELECTORS.HEADERCELL);
        var resizedcells = Y.all('#user-grades .heading .cell');

        var headeroffsetleft = this._cachedNode_firstHeaderCell.getX();
        var newcontainerwidth = 0;
        resizedcells.each(function(cell, idx) {
            var headercell = headers.item(idx);

            newcontainerwidth += cell.get('offsetWidth');
            var styles = {
                width: cell.get('offsetWidth'),
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
                    width: cell.get('offsetWidth'),
                    left: cell.getX() - headeroffsetleft + 'px'
                };
                footercell.setStyles(styles);
            });
        }
        this._menuNodes.assignmentHeaders.setStyle('width', newcontainerwidth);
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
        //this.headerCellTop = this.headerCell.getY();

        this._firstUserCellTop = this._cachedNode_firstUserCell.getY();
        this._firstUserCellLeft = this._cachedNode_firstUserCell.getX();
        this._firstUserCellHeight = this._cachedNode_firstUserCell.get('offsetHeight');
        this._firstUserCellWidth = this._cachedNode_firstUserCell.get('offsetWidth');

        this._firstHeaderCellTop = this._cachedNode_firstHeaderCell.getY();
        this._firstHeaderCellHeight = this._cachedNode_firstHeaderCell.get('offsetHeight');

        this._lastUserCellTop = this._cachedNode_lastUserCell.getY();

        if (!this._cachedNode_navbar) {
            // No navbar, so the navbar offset is zero.
            this._navbarOffset = 0;
        } else {
            // Reevaluate the height of the navbar, as it tends to change
            // as the width of the viewport does.
            this._navbarOffset = this._cachedNode_navbar.get('offsetHeight');
        }
        Y.log('_calculateCellPositions <_firstUserCellWidth: '+this._firstUserCellWidth+'>');
        /*
        var userCellList = Y.all(SELECTORS.USERCELL);

        if (userCellList.size() > 1) {
            // Use the top of the second cell for the bottom of the first cell.
            // This is used when scrolling to fix the footer to at the top-left edge of the window.
            this._firstUserCellTop = userCellList.item(1).getY();
            // WAS: var firstUserCellPosition = this.firstUserCell.get(OFFSETLEFT) + this.firstUserCell.get(OFFSETPARENT).get(OFFSETLEFT);
            this.firstUserCellLeft = userCellList.item(0).getX();

            // Use the top of the penultimate cell when scrolling the header.
            // The header is the same size as the cells.
            this.lastUserCellTop = userCellList.item(userCellList.size() - 2).getY();
        } else {
            // TODO Fix to the current position.
            this.firstUserCell = 0;
            this.lastUserCellTop = 0;
            this.firstUserCellLeft = userCellList.item(0).getX();
        }
        this._firstUserCellHeight = this.firstUserCell.get(OFFSETHEIGHT);
        this.firstHeaderCellHeight = this.headerCell.get(OFFSETHEIGHT);

        // The footer row shows the grade averages and will be floated to the page bottom.
        if (this.tableFooterRow) {
            // WAS: lastrowpos = lastrow.offsetTop + lastrow.offsetParent.offsetTop;
            this.footerRowPosition = this.tableFooterRow.getY();
        }
        */
    },

    /**
     * Process a Scroll Event on the window.
     *
     * @method _handleScrollEvent
     */
    _handleScrollEvent: function() {
        // OR together vendor-specific implementations of requestAnimationFrame as needed.
        window.requestAnimationFrame =
            window.requestAnimationFrame ||
            window.mozRequestAnimationFrame ||
            window.webkitRequestAnimationFrame ||
            window.oRequestAnimationFrame;

        if (typeof(window.requestAnimationFrame === "function")) {
            // Perform scroll event handling in one animation frame on Webkit/Gecko/IE10+.
            var me = this;
            window.requestAnimationFrame(function(timestamp){
                me._handleScrollEventInternal.call(me, timestamp);
            });
        } else {
            // Perform, albeit badly on IE9.
            this._handleScrollEventInternal.call(this);
        }
    },
    _handleScrollEventInternal: function() {

        // Styles to add.
        var assignmentHeadersStyles = {
                'visibility': 'visible'
            },
            averageFootersStyles = {
                'visibility': 'visible'
            },
            userColumnStyles = {
                'visibility': 'visible'
            },
            userColumnHeaderStyles = {
                'visibility': 'visible'
            };

        // Cache page offsets.
        var pageOffset = {
            x: Y.config.win.pageXOffset,
            y: Y.config.win.pageYOffset
        };
        var windowDimensions = {
            x: Y.config.win.innerWidth,
            y: Y.config.win.innerHeight
        };

        //this._calculateCellPositions();

        // Is the table sidebar off the left side of the screen?
        var tableSidebarOffLeft = pageOffset.x > this._firstUserCellLeft;
        if (tableSidebarOffLeft) {
            userColumnHeaderStyles.left = pageOffset.x;
            userColumnStyles.left = pageOffset.x;
        } else {
            userColumnHeaderStyles.left = this._firstUserCellLeft;
            userColumnStyles.left = this._firstUserCellLeft;
            averageFootersStyles.left = this._firstUserCellLeft;
        }

        // Should the user column scroll?
        var userColumnShouldScroll = this._firstHeaderCellTop <= pageOffset.y && pageOffset.y <= this._lastUserCellTop;
        if (userColumnShouldScroll) {
            userColumnStyles.top = this._firstUserCellTop + 'px';
            userColumnHeaderStyles.top = pageOffset.y + this._navbarOffset + 'px';
            assignmentHeadersStyles.top = pageOffset.y + this._navbarOffset + 'px';
        } else {
            userColumnStyles.top = (this._firstUserCellTop) + 'px';
            userColumnHeaderStyles.top = this._firstHeaderCellTop + 'px';
            assignmentHeadersStyles.top = this._firstHeaderCellTop + 'px';
        }

        // Should the footer be sticky?
        var footerShouldStick =
            pageOffset.y + windowDimensions.y - this._navbarOffset > this._firstUserCellTop + this._firstUserCellHeight &&
            pageOffset.y + windowDimensions.y - this._navbarOffset < this._cachedNode_tableFooterRow.getY();
        if (footerShouldStick) {
            averageFootersStyles.top = pageOffset.y + windowDimensions.y - this._navbarOffset + 'px';
        } else {
            averageFootersStyles.top = this._cachedNode_tableFooterRow.getY();
        }



        Y.log(footerShouldStick + ' : ' + pageOffset.y);

        this._menuNodes.userColumn.setStyles(userColumnStyles);
        this._menuNodes.userColumnHeader.setStyles(userColumnHeaderStyles);
        this._menuNodes.assignmentHeaders.setStyles(assignmentHeadersStyles);
        this._menuNodes.averageFooters.setStyles(averageFootersStyles);
    },

    _TouchStarted: false,
    /**
     * Set visibility: hidden on touch start so iOS doesn't break everything.
     *
     * @method _handleTouchStartEvent
     * @protected
     */
    _handleTouchStartEvent: function() {
        this._TouchStarted = true;
        Y.log('_handleTouchStartEvent');
        var hiddenStyles = {
            'visibility': 'hidden'
        };

        this._menuNodes.userColumn.setStyles(hiddenStyles);
        this._menuNodes.userColumnHeader.setStyles(hiddenStyles);
        this._menuNodes.assignmentHeaders.setStyles(hiddenStyles);
        this._menuNodes.averageFooters.setStyles(hiddenStyles);
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
            Y.one(Y.config.win).on('orientationchange', this._handleResizeEvent, this),
            Y.one(Y.config.win).on('touchstart', this._handleTouchStartEvent, this)
        );

    },

    _setupAbsUserColumn: function() {

        // Grab all cells in the user names column.
        var userCells = Y.all(SELECTORS.USERCELL);

        // Create the fixed-position container and its inner container.
        this._menuNodes.userColumn =
            Y.Node.create('<div aria-hidden="true" id="gradebook-user-container"></div>');

        // Generate the new fields.
        userCells.each(function(node) {
            // Create and configure the new container.
            var containerNode = Y.Node.create('<div class="gradebook-user-cell"></div>');
            containerNode.set('innerHTML', node.get('innerHTML'))
                    .setAttribute('data-uid', node.ancestor('tr').getData('uid'))
                    .setStyles({
                        height: node.get('offsetHeight') + 'px'
                    });

            // Add the new nodes to our floating table.
            this._menuNodes.userColumn.appendChild(containerNode);
        }, this);

        // Style the fixed-position container.
        this._menuNodes.userColumn.setStyles({
            width:  this._userColumnWidth + 'px'
        });

        // Append to the grader region.
        //this.graderRegion.append(
        this._menuNodes.userColumnHeader.insert(
            this._menuNodes.userColumn,
            'before'
        );

    },

    _setupAbsUserColumnHeader: function() {

        // Float the 'user name' header cell.
        var floatingUserCell = Y.Node.create('<div aria-hidden="true" id="gradebook-user-header-container"></div>');

        // Append node contents
        floatingUserCell.set('innerHTML', this._cachedNode_firstHeaderCell.getHTML());
        floatingUserCell.setStyles({
            height:     this._firstHeaderCellHeight,

            // use the user cell width here to ignore the grade report button column
            width:      this._firstUserCellWidth + 'px'
        });

        // Append to the grader region.
        this.graderRegion.append(floatingUserCell);

        // Store a reference to this for later - we use it in the event handlers.
        this._menuNodes.userColumnHeader = floatingUserCell;
    },

    _setupAbsAssignmentFooters: function() {
        this._cachedNode_tableFooterRow = Y.one('#user-grades .avg');
        if (!this._cachedNode_tableFooterRow) {
            Y.log('Averages footer not found - unable to float it.', 'warn', LOGNS);
            return;
        }

        var footerCells = this._cachedNode_tableFooterRow.all('.cell');

        // Create a container.
        var floatingGraderFooter = Y.Node.create('<div aria-hidden="true" role="presentation" id="gradebook-footer-container"></div>');
        var footerWidth = 0;
        var footerRowOffset = this._cachedNode_tableFooterRow.getX();

        // Copy cell content.
        footerCells.each(function(node) {
            var newnode = Y.Node.create('<div class="gradebook-footer-cell"></div>');
            newnode.set('innerHTML', node.getHTML());
            newnode.setStyles({
                height:     node.get('offsetHeight') + 'px',
                left:       (node.getX() - footerRowOffset) + 'px',
                width:      node.get('offsetWidth') + 'px',
                top:        '0px'
            });

            floatingGraderFooter.append(newnode);
            footerWidth += parseInt(node.get('offsetWidth'), 10);
        }, this);

        // Attach 'Update' button.
        var updateButton = Y.one('#gradersubmit');
        if (updateButton) {
            // TODO decide what to do with classes here to make them compatible with the base themes.
            var button = Y.Node.create('<button class="btn btn-sm btn-default">' + updateButton.getAttribute('value') + '</button>');
            button.on('click', function() {
                    updateButton.simulate('click');
            });
            floatingGraderFooter.one('.gradebook-footer-cell').append(button);
        }

        // Position the row
        floatingGraderFooter.setStyles({
            left:       this._cachedNode_tableFooterRow.getX() + 'px',
            bottom:     0,
            height:     '40px',
            width:      footerWidth + 'px'
        });

        // Append to the grader region.
        this.graderRegion.append(floatingGraderFooter);

        this._menuNodes.averageFooters = floatingGraderFooter;
    },

    _setupAbsAssignmentHeaders: function() {

        var gradeHeaders = Y.all('#user-grades tr.heading .cell');

        this._menuNodes.assignmentHeaders = Y.Node.create('<div aria-hidden="true" id="gradebook-header-container"></div>');

        var floatingGradeHeadersWidth = 0;
        var floatingGradeHeadersHeight = 0;
        var gradeHeadersOffset = this._cachedNode_firstHeaderCell.getX();

        gradeHeaders.each(function(node) {
            var nodepos = node.getX();
            var newnode = Y.Node.create('<div class="gradebook-header-cell"></div>');
            newnode.append(node.getHTML())
                    .addClass(node.getAttribute('class'))
                    .setData('column', node.getData('column'))
                    .setStyles({
                        height:     node.get('offsetHeight') + 'px',
                        display:    'block',
                        left:       (nodepos - gradeHeadersOffset) + 'px',
                        position:   'absolute',
                        width:      node.get('offsetWidth') + 'px'
                    });

            // Sum up total widths - these are used in the container styles.
            floatingGradeHeadersWidth += parseInt(node.get('offsetWidth'), 10);
            floatingGradeHeadersHeight = node.get('offsetHeight');

            // Append to our floating table.
            this._menuNodes.assignmentHeaders.appendChild(newnode);
        }, this);

        this._menuNodes.assignmentHeaders.setStyles({
            'width': floatingGradeHeadersWidth + 'px',
            'height': floatingGradeHeadersHeight + 'px'
        });

        this._menuNodes.userColumnHeader.insert(
            this._menuNodes.assignmentHeaders,
            'before'
        );
    },

    /**
     * Setup the grader report table.
     *
     * @method init
     */
    setupFloatingHeaders: function() {

        // OR together vendor-specific implementations of requestAnimationFrame as needed.
        window.requestAnimationFrame =
            window.requestAnimationFrame ||
            window.mozRequestAnimationFrame ||
            window.webkitRequestAnimationFrame ||
            window.oRequestAnimationFrame;

        if (typeof(window.requestAnimationFrame === "function")) {
            var me = this;
            me._setupFloatingHeadersInternal.call(this);
        } else {
            _setupFloatingHeadersInternal();
        }
    },


    _setupFloatingHeadersInternal: function() {
        Y.log('Huzzah!');

        // Grab references to commonly used Nodes.
        this._cachedNode_firstUserCell = Y.one(SELECTORS.USERCELL);
        this._cachedNode_firstHeaderCell = Y.one(SELECTORS.STUDENTHEADER);
        var userCellList = Y.all(SELECTORS.USERCELL);

        if (userCellList.size() > 1) {
            this._cachedNode_lastUserCell = userCellList.item(userCellList.size() - 2);
        } else {
            this._cachedNode_lastUserCell = this._cachedNode_firstUserCell;
        }

        this._cachedNode_navbar = Y.one('header.navbar');

        if (!this._cachedNode_firstUserCell) {
            // No first user cell - no need to do anything at this stage.
            this._hideSpinner();
            return;
        }

        // Setup the event handlers.
        this._setupEventHandlers();

        // Perform initial calculations.
        this._calculateCellPositions();

        // Construct the headers.
        this._setupAbsUserColumnHeader();
        this._setupAbsUserColumn();
        this._setupAbsAssignmentHeaders();
        this._setupAbsAssignmentFooters();

        // Setup the floating element initial positions by simulating scroll.
        this._handleScrollEvent();
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
    }

};

Y.Base.mix(Y.M.gradereport_grader.ReportTable, [FloatingHeaders]);

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
    firstUserCellHeight: 0,

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
        // Grab references to commonly used Nodes.
        this.firstUserCell = Y.one(SELECTORS.USERCELL);

        if (!this.firstUserCell) {
            // There was no first user cell - no need to do anything at this stage.
            this._hideSpinner();
            return;
        }

        // Generate floating elements.
        this._setupFloatingUserHeader();
        this._setupFloatingAssignmentHeaders();
        this._setupFloatingAssignmentFooter();
        this._setupFloatingUserColumn();

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
            this.firstUserCellLeft = userCellList.item(0).getX();
        }
        this.firstUserCellHeight = this.firstUserCell.get(OFFSETHEIGHT);

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
        var userColumn = Y.all(SELECTORS.USERCELL);

        // TODO check ARIA - should this be linked to the row somehow?
        // Create a floating table.
        var floatingUserColumn = Y.Node.create('<div aria-hidden="true" id="gradebook-user-container"></div>');

        var floatingUserColumnDummy = Y.Node.create('<div aria-hidden="true" id="gradebook-user-container"></div>');
        var floatingUserColumnInner = Y.Node.create('<div id="gradebook-user-container-inner"></div>');


        // Generate the new fields.
        userColumn.each(function(node) {
            // Create and configure the new container.
            var containerNode = Y.Node.create('<div class="gradebook-user-cell"></div>');
            containerNode.set('innerHTML', node.get('innerHTML'))
                    .setAttribute('data-uid', node.ancestor('tr').getData('uid'))
                    .setStyles({
                        height: node.get(OFFSETHEIGHT) + 'px'
                        //width:  node.get(OFFSETWIDTH) + 'px'
                    });

            // Add the new nodes to our floating table.
            floatingUserColumnInner.appendChild(containerNode);
        }, this);

        // Style the floating user container.
        floatingUserColumn.setStyles({
            left:       this.firstUserCell.getX() + 'px',
            position:   'absolute',
            top:        this.firstUserCell.getY() + 'px'
        });

        var navbar = Y.one('header.navbar');
        var navbarOffset = 0;
        if (navbar) {
            navbarOffset = navbar.get('offsetHeight');
        }
        floatingUserColumnDummy.setStyles({
            position:   'fixed',
            left:       0,
            top:        navbarOffset + this.assignmentHeadingContainer.get('offsetHeight') - 1,
            //height:     '400px',
            width:      '211px',
            //background: '#f00',
            bottom:        this.footerRow.get('offsetHeight')
        });

        // Append to the grader region.
        floatingUserColumnDummy.appendChild(floatingUserColumnInner);
        //this.graderRegion.append(floatingUserColumn);

        //this.graderRegion.append(floatingUserColumnDummy);
        this.userColumnHeader.insert(floatingUserColumnDummy, 'before');

        //this.userColumnHeader.insert(floatingGradeHeaders, 'before');

        // Store a reference to this for later - we use it in the event handlers.
        this.userColumn = floatingUserColumn;
        this.userColumnInner = floatingUserColumnInner;
        this.userColumnFixed = floatingUserColumnDummy;
    },

    userColumnFixed: null,
    userColumnInner: null,

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
            //left:       this.firstUserCell.getX() + 'px',
            //position:   'absolute',
            //top:        this.headerCell.getY() + 'px',
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
        //var floatingGradeHeaders = Y.Node.create('<div aria-hidden="true" id="gradebook-header-container"></div>');
        var floatingGradeHeadersFixed = Y.Node.create('<div aria-hidden="true" id="gradebook-header-container"></div>');
        var inner = Y.Node.create('<div id="gradebook-header-container-inner"></div>');
        floatingGradeHeadersFixed.appendChild(inner);

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
                        display:    'block',
                        left:       (nodepos - gradeHeadersOffset) + 'px',
                        position:   'absolute',
                        width:      node.get(OFFSETWIDTH) + 'px'
                    });

            // Sum up total widths - these are used in the container styles.
            floatingGradeHeadersWidth += parseInt(node.get(OFFSETWIDTH), 10);
            floatingGradeHeadersHeight = node.get(OFFSETHEIGHT);

            // Append to our floating table.
            inner.appendChild(newnode);
        }, this);

        // Insert in place before the grader headers.
        this.userColumnHeader.insert(floatingGradeHeadersFixed, 'before');

        // Store a reference to this for later - we use it in the event handlers.
        this.assignmentHeadingContainer = floatingGradeHeadersFixed;
        this.assignmentHeadingContainerInner = inner;
    },

    assignmentHeadingContainerInner: null,

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
        var inner = Y.Node.create('<div id="gradebook-footer-container-inner"></div>');
        floatingGraderFooter.appendChild(inner);

        var footerWidth = 0;
        var footerRowOffset = this.tableFooterRow.getX();

        // Copy cell content.
        footerCells.each(function(node) {
            var newnode = Y.Node.create('<div class="gradebook-footer-cell"></div>');
            newnode.set('innerHTML', node.getHTML());
            newnode.setStyles({
                height:     node.get(OFFSETHEIGHT) + 'px',
                left:       (node.getX() - footerRowOffset) + 'px',
                //position:   'absolute',
                width:      node.get(OFFSETWIDTH) + 'px'
            });

            inner.append(newnode);
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
            inner.one('.gradebook-footer-cell').append(button);
        }

        // Position the row
        //floatingGraderFooter.setStyles({
            //position:   'absolute',
            //left:       this.tableFooterRow.getX() + 'px',
            //bottom:     0,
            //height:     '40px',
            //width:      footerWidth + 'px'
        //});

        // Append to the grader region.
        this.graderRegion.append(floatingGraderFooter);

        this.footerRow = inner;
    },


    /**
     * Actually process a scroll event on the window.
     */
    _handleScrollEventInternal: function(timestamp) {
        // Performance is important in this function as it is called frequently and in quick succesion.
        // To prevent layout thrashing when the DOM is repeatedly updated and queried, updated and queried,
        // updates must be batched.

        // Styles to add.
        var headerInnerStyles = {},
            footerInnerStyles = {},
            userColumnInnerStyles = {},
            userColumnHeaderStyles = {};

        // Cache page offsets.
        var pageOffset = {
            x: Y.config.win.pageXOffset,
            y: Y.config.win.pageYOffset
        };
        var innerHeight = Y.config.win.innerHeight;

        // Inexplicable magic number (it's actually the navbar height).
        var navbarHeight = 40;

        // Calculate X-offset for internal containers of header and footer.
        var xOffset = (this.firstUserCellLeft - pageOffset.x) + 'px';

        // Calculate flags.
        var tableHeaderOriginalVisible = pageOffset.y + navbarHeight <= this.headerCellTop;
        var tableHeaderOffTop = pageOffset.y + navbarHeight > this.headerCellTop;
        var tableFooterOffTop = tableHeaderOffTop && pageOffset.y + navbarHeight >= this.lastUserCellTop;

        var tableVisible = (!tableHeaderOffTop || !tableFooterOffTop);

        var tableFooterOffBot = pageOffset.y + innerHeight - navbarHeight < this.footerRowPosition;
        var tableFooterY = pageOffset.y + innerHeight - navbarHeight > this.firstUserCellTop;


        // User column position.
        var tableSidebarOffLeft = pageOffset.x > this.firstUserCellLeft;

        // Set styles.
        headerInnerStyles.left = xOffset;
        headerInnerStyles.visibility = ((tableHeaderOffTop && !tableFooterOffTop)? 'visible' : 'hidden');

        userColumnHeaderStyles.visibility =
        (((tableHeaderOffTop && !tableFooterOffTop && tableSidebarOffLeft) || tableSidebarOffLeft)?
            'visible' :
            'hidden'
        );
        userColumnHeaderStyles.left = tableSidebarOffLeft ? '0px' : xOffset + 'px';
        userColumnHeaderStyles.top =
        (tableHeaderOffTop && !tableFooterOffTop) ?
            navbarHeight + 'px' :
            (this.firstUserCellTop - pageOffset.y - this.firstUserCellHeight - navbarHeight) + 'px';

        footerInnerStyles.left = xOffset;
        footerInnerStyles.visibility = ((tableFooterOffBot && tableFooterY)? 'visible' : 'hidden');

        userColumnInnerStyles.visibility = (tableSidebarOffLeft? 'visible' : 'hidden');
        userColumnInnerStyles.top = ((this.firstUserCellTop - pageOffset.y) - 80 - this.firstUserCellHeight) + 'px';

        // Apply styles.
        this.footerRow.setStyles(footerInnerStyles);
        this.assignmentHeadingContainerInner.setStyles(headerInnerStyles);
        this.userColumnInner.setStyles(userColumnInnerStyles);
        this.userColumnHeader.setStyles(userColumnHeaderStyles);
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

    _handleResizeEventInternal: function(timestamp) {

        // Recalculate the position of the edge cells for scroll positioning.
        this._calculateCellPositions();

        // Simulate a scroll.
        this._handleScrollEventInternal();

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
    },

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
    }
};

Y.Base.mix(Y.M.gradereport_grader.ReportTable, [FloatingHeaders]);

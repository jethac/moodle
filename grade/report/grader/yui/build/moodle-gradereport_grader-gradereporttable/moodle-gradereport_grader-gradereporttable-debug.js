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
 * @package   gradereport_grader
 * @copyright 2014 UC Regents
 * @author    Alfonso Roman <aroman@oid.ucla.edu>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

var LOGNS = 'moodle-gradereport_grader-gradereporttable';

M.gradereport_grader = M.gradereport_grader || {};

// Create a gradebook module
M.gradereport_grader.gradereporttable = {
    // Resuable nodes
    
    node_student_header_cell: {},
    node_student_cell: {},
    node_footer_row: {},

    test: function() {
        console.log('testing');
    },
    // Init module
    init: function() {
        Y.log('Starting...', 'debug', LOGNS);
        
        // Set up some reusable nodes.
        this.node_student_header_cell = Y.one('#studentheader');
        // First student cell.
        this.node_student_cell = Y.one('#user-grades .user.cell');
        // Averages row.
        this.node_footer_row = Y.one('#user-grades .avg');

        // Check if there are any students.
        if (this.node_student_cell) {
            // Generate floating elements.
            this.float_user_column();
            this.float_assignment_header();
            this.float_user_header();

            // Check if 'averages' is allowed.
            if (this.node_footer_row) {
                this.float_assignment_footer();
                this.update_assignment_footer_position();
            }

            // Set floating element initial positions.
            this.update_assignment_header_position();
            this.update_user_column_position();

            Y.all('#user-grades .overridden').setAttribute('aria-label', 'Overriden grade');
               
            Y.log('Setting onscroll event', 'debug', LOGNS);

            // Use native DOM scroll & resize events instead of YUI synthetic event.
            window.onscroll = function() {
                console.log('foobar');

                // Get better performance by preventing layout thrashing.  This occurs
                // when the DOM is repeatedly updated and queried for updated values.
                // 
                // To prevent, group all reads to happen before your writes


                // First get all the readable values needed.
                
                var headercontainer = document.getElementById('gradebook-header-container');
                var userheadercell = document.getElementById('studentheader');

                var user_column_header = document.getElementById('gradebook-user-header-container');

                var headercelltop = userheadercell.offsetTop + userheadercell.offsetParent.offsetTop;

                // Next do all the writing.
                headercontainer.style.left = userheadercell.offsetLeft + userheadercell.offsetParent.offsetLeft + 'px';
                if (window.pageYOffset > headercelltop) {
                    headercontainer.style.top = window.pageYOffset + 40 + 'px';
                    user_column_header.style.top = window.pageYOffset + 40 + 'px';
                } else {
                    headercontainer.style.top = headercelltop + 'px';
                    user_column_header.style.top = headercelltop + 'px';
                }
                    
                
                var pageleftcutoff = window.pageXOffset;
                var firstusercell = document.querySelectorAll("#user-grades .user.cell")[0];
                var firstusercellpos = firstusercell.offsetLeft + firstusercell.offsetParent.offsetLeft;

                var user_column = document.getElementById('gradebook-user-container');
//                var user_column_header = document.getElementById('gradebook-user-header-container');

                    if (pageleftcutoff > firstusercellpos) {
                        user_column.style.left = pageleftcutoff + 'px';
                        user_column_header.style.left = pageleftcutoff + 'px';
                    } else {
                        user_column.style.left = firstusercellpos + 'px';
                        user_column_header.style.left = firstusercellpos + 'px';
                    }
                
                var lastrow = document.querySelectorAll('#user-grades .avg')[0];
                var footer;
                
                // Check that Average footer is available.
                if (lastrow !== undefined) {
                    footer = document.getElementById('gradebook-footer-container');
                    var lastrowpos = lastrow.offsetTop + lastrow.offsetParent.offsetTop;
                }

                
                if (lastrow !== undefined) {
                    footer.style.left = userheadercell.offsetLeft + userheadercell.offsetParent.offsetLeft + 'px';

                    if (window.pageYOffset + window.innerHeight < lastrowpos) {
                        footer.style.top = (window.pageYOffset + window.innerHeight - 50) + 'px';
                        footer.classList.add('gradebook-footer-row-sticky');
                    } else {
                        footer.style.top = lastrowpos + 'px';
                        footer.classList.remove('gradebook-footer-row-sticky');
                    }
                }                
                
            };

            window.onresize = function() {
                Y.log('Setting resize event', 'debug', LOGNS);
                M.gradereport_grader.gradereporttable.update_assignment_footer_position();
                M.gradereport_grader.gradereporttable.update_assignment_header_position();
                M.gradereport_grader.gradereporttable.update_user_column_position();
                
                // Resize headers & footers.
                // This is an expensive operation, not expected to happen often.
                var headers = Y.all('#gradebook-header-container .gradebook-header-cell');
                var resizedcells = Y.all('#user-grades .heading .cell');
                
                var headeroffsetleft = Y.one('#studentheader').getX();
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
                    Y.one('#gradebook-footer-container').setStyle('width', newcontainerwidth);
                }

                Y.one('#gradebook-header-container').setStyle('width', newcontainerwidth);
                
            };
        }
        Y.on('scroll', function () {
            this.test();
        }, this);

        // Remove loading screen.  Need to do YUI synthetic event to trigger
        // on all browsers.
        Y.on('domready', function() {
//            Y.one('.gradebook-loading-screen').remove(true);
        });
    },
    float_user_column: function() {
        // Grab the user names column
        var user_column = Y.all('#user-grades .user.cell');

        // Generate a floating table
        var floating_user_column = Y.Node.create('<div aria-hidden="true" id="gradebook-user-container"></div>');
        var floating_user_column_height = 0;
        var user_column_offset = this.node_student_cell.getY();
        
        user_column.each(function(node) {

            // Create cloned node and container.
            // We'll absolutely position the container to each cell position,
            // this will guarantee that student cells are always aligned.
            var container_node = Y.Node.create('<div class="gradebook-user-cell"></div>');

            // Grab the username
            var usernamenode = node.cloneNode(true);
            container_node.append(usernamenode.getHTML());
            usernamenode = null;

            container_node.setStyles({
                'height': node.get('offsetHeight') + 'px',
                'width': node.get('offsetWidth') + 'px',
                'position': 'absolute',
                'top': (node.getY() - user_column_offset) + 'px'
            });

            floating_user_column_height += node.get('offsetHeight');
            // Retrieve the corresponding row
            var classes = node.ancestor().getAttribute('class').split(' ').join('.');
            // Attach highlight event
            container_node.on('click', function() {
                Y.one('.' + classes).all('.grade').toggleClass('hmarked');
            });
            // Add the cloned nodes to our floating table
            floating_user_column.appendChild(container_node);

        }, this);

        // Style the table
        floating_user_column.setStyles({
            'position': 'absolute',
            'left': this.node_student_cell.getX() + 'px',
            'top': this.node_student_cell.getY() + 'px',
            'width': this.node_student_cell.get('offsetWidth'),
            'height' : floating_user_column_height + 'px',
            'background-color': '#f9f9f9'
        });

        Y.one('body').append(floating_user_column);
    },
    float_user_header: function() {

        // Float the 'user name' header cell.
        var floating_user_header_cell = Y.Node.create('<div aria-hidden="true" id="gradebook-user-header-container"></div>');

        // Clone the node
        var cellnode = this.node_student_header_cell.cloneNode(true);
        // Append node contents
        floating_user_header_cell.append(cellnode.getHTML());
        floating_user_header_cell.setStyles({
            'position': 'absolute',
            'left': this.node_student_cell.getX() + 'px',
            'top': this.node_student_header_cell.getY() + 'px',
            'width': '200px',
            'height': this.node_student_header_cell.get('offsetHeight') + 'px'
        });

        // Safe for collection
        cellnode = null;

        Y.one('body').append(floating_user_header_cell);
    },
    float_assignment_header: function() {
        var grade_headers = Y.all('#user-grades tr.heading .cell');

        // Generate a floating headers
        var floating_grade_headers = Y.Node.create('<div aria-hidden="true" id="gradebook-header-container"></div>');

        var floating_grade_headers_width = 0;
        var floating_grade_headers_height = 0;
        var grade_headers_offset = this.node_student_header_cell.getX();
        
        grade_headers.each(function(node) {

            // Get the target column to highlight.  This is embedded in
            // the column cell #, but it's off by one, so need to adjust for that.
            var col = node.getAttribute('class');

            // Extract the column #
            var search = /c[0-9]+/g;
            var match = search.exec(col);
            match = match[0].replace('c', '');

            // Offset
            var target_col = parseInt(match, 10);
            ++target_col;

            var nodepos = node.getX();

            // We need to clone the node, otherwise we mutate original obj
            var nodeclone = node.cloneNode(true);

            var newnode = Y.Node.create('<div class="gradebook-header-cell"></div>');
            newnode.append(nodeclone.getHTML());
            newnode.addClass(nodeclone.getAttribute('class'));
            nodeclone = null;

            newnode.setStyles({
                'width': node.get('offsetWidth') + 'px',
                'height': node.get('offsetHeight') + 'px',
                'position': 'absolute',
                'left': (nodepos - grade_headers_offset) + 'px'
            });

            // Sum up total width
            floating_grade_headers_width += parseInt(node.get('offsetWidth'), 10);
            floating_grade_headers_height = node.get('offsetHeight');

            // Attach 'highlight column' event to new node
            newnode.on('click', function() {
                Y.all('.cell.c' + target_col).toggleClass('vmarked');
            });

            // Append to floating table.
            floating_grade_headers.appendChild(newnode);
        }, this);

        // Position header table.
        floating_grade_headers.setStyles({
            'position': 'absolute',
            'top': this.node_student_header_cell.getY() + 'px',
            'left': this.node_student_header_cell.getX() + 'px',
            'width': floating_grade_headers_width + 'px',
            'height' : floating_grade_headers_height + 'px'
        });

        Y.one('body').append(floating_grade_headers);
    },
    float_assignment_footer: function() {

        // Generate the sticky footer row.
        // Grab the row.
        var footer_row = Y.all('#user-grades .lastrow .cell');
        // Create a container.
        var floating_grade_footers = Y.Node.create('<div aria-hidden="true" id="gradebook-footer-container"></div>');
        var floating_grade_footer_width = 0;
        var footer_row_offset = this.node_footer_row.getX();
        // Copy nodes
        footer_row.each(function(node) {

            var nodepos = node.getX();
            var cellnodeclone = node.cloneNode(true);

            var newnode = Y.Node.create('<div class="gradebook-footer-cell"></div>');
            newnode.append(cellnodeclone.getHTML());
            newnode.setStyles({
                'width': node.get('offsetWidth') + 'px',
                'height': '50px',
                'position': 'absolute',
                'left': (nodepos - footer_row_offset) + 'px'
            });

            floating_grade_footers.append(newnode);
            floating_grade_footer_width += parseInt(node.get('offsetWidth'), 10);
        }, this);

        // Attach 'Update' button.
        var update_button = Y.one('#gradersubmit');
        if (update_button) {
            var button = Y.Node.create('<button class="btn btn-sm btn-default">' + update_button.getAttribute('value') + '</button>');
            button.on('click', function() {
                update_button.simulate('click');
            });
            floating_grade_footers.one('.gradebook-footer-cell').append(button);
        }

        // Position the row
        floating_grade_footers.setStyles({
            'position': 'absolute',
            'left': this.node_footer_row.getX() + 'px',
            'bottom': '0',
            'height' : '50px',
            'width' : floating_grade_footer_width + 'px'
        });

        Y.one('body').append(floating_grade_footers);
    },
    update_user_column_position: function() {
        var offsetcutoff = window.pageXOffset;
        var sidebar_active = Y.one('.sidebar.active');

        if (sidebar_active) {
            offsetcutoff = sidebar_active.get('offsetWidth') + window.pageXOffset;
        }

        var firstusercell = document.querySelectorAll("#user-grades .user.cell")[0];
        var firstusercellpos = firstusercell.offsetLeft + firstusercell.offsetParent.offsetLeft;

        var user_column = document.getElementById('gradebook-user-container');
        var user_column_header = document.getElementById('gradebook-user-header-container');

        if (offsetcutoff > firstusercellpos) {
            user_column.style.left = offsetcutoff + 'px';
            user_column_header.style.left = offsetcutoff + 'px';
        }

        if (offsetcutoff < firstusercellpos) {
            user_column.style.left = firstusercellpos + 'px';
            user_column_header.style.left = firstusercellpos + 'px';
        }
    },
    update_assignment_header_position: function() {

        var header = document.getElementById('gradebook-header-container');
        var header_cell = document.getElementById('studentheader');

        var user_column_header = document.getElementById('gradebook-user-header-container');

        header.style.left = header_cell.offsetLeft + header_cell.offsetParent.offsetLeft + 'px';

        var headercelltop = header_cell.offsetTop + header_cell.offsetParent.offsetTop;

        if (window.pageYOffset > headercelltop) {
            header.style.top = window.pageYOffset + 'px';
            user_column_header.style.top = window.pageYOffset + 'px';
        } else {
            header.style.top = headercelltop + 'px';
            user_column_header.style.top = headercelltop + 'px';
        }

    },
    update_assignment_footer_position: function() {

        var lastrow = document.querySelectorAll('#user-grades .avg')[0];
        // Check that Average footer is available.
        if (lastrow === undefined) {
            return;
        }

        var footer = document.getElementById('gradebook-footer-container');
        var lastrowpos = lastrow.offsetTop + lastrow.offsetParent.offsetTop;

        var header_cell = document.getElementById('studentheader');
        footer.style.left = header_cell.offsetLeft + header_cell.offsetParent.offsetLeft + 'px';

        if (window.pageYOffset + window.innerHeight < lastrowpos) {
            footer.style.top = (window.pageYOffset + window.innerHeight - 50) + 'px';
            footer.classList.add('gradebook-footer-row-sticky');
        } else {
            footer.style.top = lastrowpos + 'px';
            footer.classList.remove('gradebook-footer-row-sticky');
        }
    },
    sidebar_toggle: function() {
        // Update positions when sidebar toggles
        this.update_assignment_footer_position();
        this.update_assignment_header_position();
        this.update_user_column_position();
    },
    sidebar_toggle_pre: function() {
        if (this.node_footer_row) {
            Y.one('#gradebook-footer-container').hide();
        }
        Y.one('#gradebook-user-container').hide();
        Y.one('#gradebook-user-header-container').hide();
        Y.one('#gradebook-header-container').hide();
    },
    sidebar_toggle_post: function() {
        if (this.node_footer_row) {
            Y.one('#gradebook-footer-container').show();
        }
        Y.one('#gradebook-user-container').show();
        Y.one('#gradebook-user-header-container').show();
        Y.one('#gradebook-header-container').show();
    }
};


}, '@VERSION@');

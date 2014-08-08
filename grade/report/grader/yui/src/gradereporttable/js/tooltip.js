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

Tooltip.prototype = {
    tooltip: null,
    _setupTooltips: function() {
        this._eventHandles.push(
            Y.delegate('mouseenter', this._showTooltip, SELECTORS.GRADECELL, this)
        );
    },
    _getTooltip: function() {
        if (!this.tooltip) {
        
        }
    },
    _showTooltip: function() {
    }
};

Y.Base.mix(Y.M.gradereport_grader.ReportTable, [Tooltip]);

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
        /*
        this._eventHandles.push(
            this.graderTable.delegate('hover', this._showTooltip, this._hideTooltip, SELECTORS.GRADECELL, this),
            this.graderTable.delegate('click', this._toggleTooltip, SELECTORS.GRADECELL, this)
        );
        */
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

        var me = this;
        var requestID = window.requestAnimationFrame(function(timestamp){
            tooltip.set('bodyContent', me.tooltipTemplate({
                        cellid: cell.get('id'),
                        username: me.getGradeUserName(cell),
                        itemname: me.getGradeItemName(cell),
                        feedback: me.getGradeFeedback(cell),
                        overridden: cell.hasClass(CSS.OVERRIDDEN) ? CSS.OVERRIDDEN : ''
                    }))
                    .set('xy', [
                        cell.getX() + (cell.get('offsetWidth') / 2),
                        cell.getY() + (cell.get('offsetHeight') / 2)
                    ])
                    .show();
            e.currentTarget.addClass(CSS.TOOLTIPACTIVE);
        });
    },
    _hideTooltip: function(e) {
        if (e.relatedTarget && this._tooltipBoundingBox && this._tooltipBoundingBox.contains(e.relatedTarget)) {
            // Do not exit if the user is mousing over the tooltip itself.
            return;
        }
        if (this._tooltip) {
            var me = this;
            var requestID = window.requestAnimationFrame(function(timestamp){
                e.currentTarget.removeClass(CSS.TOOLTIPACTIVE);
                me._tooltip.hide();
            });
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

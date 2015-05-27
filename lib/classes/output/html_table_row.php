<?php
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
 * Table row renderable component.
 *
 * @package    core
 * @copyright  2015 Jetha Chan
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace core\output;
use stdClass;

/**
 * Component representing a table row.
 *
 * @copyright 2009 Nicolas Connault
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since Moodle 2.0
 * @package core
 * @category output
 */
class html_table_row implements \renderable, \templatable {

    /**
     * @var string Value to use for the id attribute of the row.
     */
    public $id = null;

    /**
     * @var array Array of html_table_cell objects
     */
    public $cells = array();

    /**
     * @var string Value to use for the style attribute of the table row
     */
    public $style = null;

    /**
     * @var array Attributes of additional HTML attributes for the <tr> element
     */
    public $attributes = array();

    /**
     * Constructor
     * @param array $cells
     */
    public function __construct(array $cells=null) {
        $this->attributes['class'] = '';
        $cells = (array)$cells;
        foreach ($cells as $cell) {
            if ($cell instanceof \core\output\html_table_cell) {
                $this->cells[] = $cell;
            } else {
                $this->cells[] = new \core\output\html_table_cell($cell);
            }
        }
    }

    /**
     * Export this data so it can be used as the context for a mustache template.
     *
     * @param renderer_base $output typically, the renderer that's calling this function
     * @return stdClass data context for a mustache template
     */
    public function export_for_template(\renderer_base $output) {

        $data = new stdClass();

        $data->classes = array();
        if(!empty($this->attributes['class']))
            $data->classes = explode(' ', $this->attributes['class']);

        $data->cells = array();
        foreach ($this->cells as $cell) {
            if ($cell instanceof \core\output\html_table_cell) {
                $data->cells[] = $cell->export_for_template($output);
            } else {
                $data->cells[] = $cell;
            }
        }

        return $data;

    }
}
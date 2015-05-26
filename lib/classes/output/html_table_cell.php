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
 * Table cell renderable component.
 *
 * @package    core
 * @copyright  2015 Jetha Chan
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace core\output;
use stdClass;

/**
 * Data structure representing a table cell.
 *
 * @copyright 2015 Jetha Chan
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since Moodle 3.0
 * @package core
 * @category output
 */
class html_table_cell implements \renderable, \templatable {


    /**
     * @var string Value to use for the id attribute of the cell.
     */
    public $id = null;

    /**
     * @var string The contents of the cell.
     */
    public $text;

    /**
     * @var string Abbreviated version of the contents of the cell.
     */
    public $abbr = null;

    /**
     * @var int Number of columns this cell should span.
     */
    public $colspan = null;

    /**
     * @var int Number of rows this cell should span.
     */
    public $rowspan = null;

    /**
     * @var string Defines a way to associate header cells and data cells in a table.
     */
    public $scope = null;

    /**
     * @var bool Whether or not this cell is a header cell.
     */
    public $header = false;

    /**
     * @var string Value to use for the style attribute of the table cell
     */
    public $style = null;

    /**
     * @var array Attributes of additional HTML attributes for the <td> element
     */
    public $attributes = array();

    /**
     * Constructs a table cell
     *
     * @param string $text
     */
    public function __construct($text = null) {
        $this->text = $text;
        $this->attributes['class'] = '';
    }

    /**
     * Export this data so it can be used as the context for a mustache template.
     *
     * @param renderer_base $output typically, the renderer that's calling this function
     * @return stdClass data context for a mustache template
     */
    public function export_for_template(\renderer_base $output) {

        $data = new stdClass();

        $data->text = $this->text;
        $data->type = $this->header? 'th' : 'td';
        $data->attributes = array();

        $attributenames = array(
            'id',
            'colspan',
            'rowspan',
            'scope',
            'style'
        );
        foreach ($attributenames as $name) {
            $obj = new stdClass();
            $obj->name = $name;
            $obj->value = $this->$name;

            $data->attributes[] = $obj;
        }

        foreach ($this->attributes as $key => $value) {
            $obj = new stdClass();
            $obj->name = $key;
            $obj->value = $value;

            $data->attributes[] = $obj;
        }

        return $data;
    }
}

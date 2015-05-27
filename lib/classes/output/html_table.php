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
 * Table renderable component.
 *
 * @package    core
 * @copyright  2015 Jetha Chan
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace core\output;
use stdClass;

/**
 * Holds all the information required to render a <table> by {@link core_renderer::table()}
 *
 * Example of usage:
 * $t = new html_table();
 * ... // set various properties of the object $t as described below
 * echo html_writer::table($t);
 *
 * @copyright 2009 David Mudrak <david.mudrak@gmail.com>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since Moodle 2.0
 * @package core
 * @category output
 */
class html_table implements \renderable, \templatable {

    /**
     * @var string Value to use for the id attribute of the table
     */
    public $id = null;

    /**
     * @var array Attributes of HTML attributes for the <table> element
     */
    public $attributes = array();

    /**
     * @var array An array of headings. The n-th array item is used as a heading of the n-th column.
     * For more control over the rendering of the headers, an array of html_table_cell objects
     * can be passed instead of an array of strings.
     *
     * Example of usage:
     * $t->head = array('Student', 'Grade');
     */
    public $head;

    /**
     * @var array An array that can be used to make a heading span multiple columns.
     * In this example, {@link html_table:$data} is supposed to have three columns. For the first two columns,
     * the same heading is used. Therefore, {@link html_table::$head} should consist of two items.
     *
     * Example of usage:
     * $t->headspan = array(2,1);
     */
    public $headspan;

    /**
     * @var array An array of column alignments.
     * The value is used as CSS 'text-align' property. Therefore, possible
     * values are 'left', 'right', 'center' and 'justify'. Specify 'right' or 'left' from the perspective
     * of a left-to-right (LTR) language. For RTL, the values are flipped automatically.
     *
     * Examples of usage:
     * $t->align = array(null, 'right');
     * or
     * $t->align[1] = 'right';
     */
    public $align;

    /**
     * @var array The value is used as CSS 'size' property.
     *
     * Examples of usage:
     * $t->size = array('50%', '50%');
     * or
     * $t->size[1] = '120px';
     */
    public $size;

    /**
     * @var array An array of wrapping information.
     * The only possible value is 'nowrap' that sets the
     * CSS property 'white-space' to the value 'nowrap' in the given column.
     *
     * Example of usage:
     * $t->wrap = array(null, 'nowrap');
     */
    public $wrap;

    /**
     * @var array Array of arrays or html_table_row objects containing the data. Alternatively, if you have
     * $head specified, the string 'hr' (for horizontal ruler) can be used
     * instead of an array of cells data resulting in a divider rendered.
     *
     * Example of usage with array of arrays:
     * $row1 = array('Harry Potter', '76 %');
     * $row2 = array('Hermione Granger', '100 %');
     * $t->data = array($row1, $row2);
     *
     * Example with array of html_table_row objects: (used for more fine-grained control)
     * $cell1 = new \core\output\html_table_cell();
     * $cell1->text = 'Harry Potter';
     * $cell1->colspan = 2;
     * $row1 = new \core\output\html_table_row();
     * $row1->cells[] = $cell1;
     * $cell2 = new \core\output\html_table_cell();
     * $cell2->text = 'Hermione Granger';
     * $cell3 = new \core\output\html_table_cell();
     * $cell3->text = '100 %';
     * $row2 = new \core\output\html_table_row();
     * $row2->cells = array($cell2, $cell3);
     * $t->data = array($row1, $row2);
     */
    public $data;

    /**
     * @deprecated since Moodle 2.0. Styling should be in the CSS.
     * @var string Width of the table, percentage of the page preferred.
     */
    public $width = null;

    /**
     * @deprecated since Moodle 2.0. Styling should be in the CSS.
     * @var string Alignment for the whole table. Can be 'right', 'left' or 'center' (default).
     */
    public $tablealign = null;

    /**
     * @deprecated since Moodle 2.0. Styling should be in the CSS.
     * @var int Padding on each cell, in pixels
     */
    public $cellpadding = null;

    /**
     * @var int Spacing between cells, in pixels
     * @deprecated since Moodle 2.0. Styling should be in the CSS.
     */
    public $cellspacing = null;

    /**
     * @var array Array of classes to add to particular rows, space-separated string.
     * Class 'lastrow' is added automatically for the last row in the table.
     *
     * Example of usage:
     * $t->rowclasses[9] = 'tenth'
     */
    public $rowclasses;

    /**
     * @var array An array of classes to add to every cell in a particular column,
     * space-separated string. Class 'cell' is added automatically by the renderer.
     * Classes 'c0' or 'c1' are added automatically for every odd or even column,
     * respectively. Class 'lastcol' is added automatically for all last cells
     * in a row.
     *
     * Example of usage:
     * $t->colclasses = array(null, 'grade');
     */
    public $colclasses;

    /**
     * @var string Description of the contents for screen readers.
     */
    public $summary;

    /**
     * @var string Caption for the table, typically a title.
     *
     * Example of usage:
     * $t->caption = "TV Guide";
     */
    public $caption;

    /**
     * @var bool Whether to hide the table's caption from sighted users.
     *
     * Example of usage:
     * $t->caption = "TV Guide";
     * $t->captionhide = true;
     */
    public $captionhide = false;

    /**
     * Constructor
     */
    public function __construct() {
        $this->attributes['class'] = 'generaltable';
    }

    /**
     * Export this data so it can be used as the context for a mustache template. Contains a lot of
     * logic from html_writer::table().
     *
     * @param renderer_base $output typically, the renderer that's calling this function
     * @return stdClass data context for a mustache template
     */
    public function export_for_template(\renderer_base $output) {

        $data = new stdClass();

        // id
        if(!is_null($this->id))
            $data->id = $this->id;
        $data->captionhide = $this->captionhide;
        if(strlen($this->caption) > 0)
            $data->caption = $this->caption;

        // attributes
        // explicitly assigned properties override those defined via $table->attributes
        $attrs = array_merge($this->attributes, array(
            'id'            => $this->id,
            'width'         => $this->width,
            'summary'       => $this->summary,
            'cellpadding'   => $this->cellpadding,
            'cellspacing'   => $this->cellspacing,
        ));
        foreach ($this->attributes as $key => $value) {
            $attrs[$key] = $value;
        }
        if(isset($attrs['class']))
            unset($attrs['class']);
        $data->attributes = $attrs;

        // classes
        $tableclasses = array();
        if(!empty($this->attributes['class']))
            $tableclasses = array_merge($tableclasses, explode(' ', $this->attributes['class']));
        if (!empty($table->tablealign)) {
            $tableclasses[] = 'boxalign' . $this->tablealign;
        }

        $data->classes = $tableclasses;

        $countcols = 0;
        // headings
        if (!empty($this->head)) {
            $keys = array_keys($this->head);
            $lastkey = end($keys);

            $headingcells = array();

            foreach ($this->head as $key => $heading) {
                // Convert plain string headings into html_table_cell objects.
                if (!($heading instanceof \core\output\html_table_cell)) {
                    //$headingtext = $heading;
                    $heading = new \core\output\html_table_cell($heading);
                    //$heading->text = $headingtext;
                    $heading->header = true;
                }
                if ($heading->header !== false) {
                    $heading->header = true;
                }
                if ($heading->header && empty($heading->scope)) {
                    $heading->scope = 'col';
                }
                $heading->attributes['class'] .= ' header c' . $key;
                if (isset($this->headspan[$key]) && $this->headspan[$key] > 1) {
                    $heading->colspan = $this->headspan[$key];
                    $countcols += $this->headspan[$key] - 1;
                }
                if ($key == $lastkey) {
                    $heading->attributes['class'] .= ' lastcol';
                }
                if (isset($this->colclasses[$key])) {
                    $heading->attributes['class'] .= ' ' . $this->colclasses[$key];
                }
                $heading->attributes['class'] = trim($heading->attributes['class']);
                //$heading->attributes['class'] = explode(' ', $heading->attributes['class']);

                $heading->attributes = array_merge($heading->attributes, array(
                        'style'     => $this->align[$key] . $this->size[$key] . $heading->style,
                        'scope'     => $heading->scope,
                        'colspan'   => $heading->colspan,
                    ));

                $headingcells[] = $heading;
            }

            $headingrow = new \core\output\html_table_row($headingcells);
            $headingrow->classes = array();

            $data->head = new stdClass();
            $data->head->rows = array($headingrow->export_for_template($output));
        }







        $data->body = new stdClass();
        $data->body->rows = array();
        foreach ($this->data as $row) {
            $data->body->rows[] = $row->export_for_template($output);
        }

        return $data;

    }
}
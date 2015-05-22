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
 * Paging bar renderable component.
 *
 * @package    core
 * @copyright  2015 Jetha Chan
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace core\output;
use stdClass;

/**
 * Data structure representing a paging bar.
 *
 * @copyright 2015 Jetha Chan
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since Moodle 3.0
 * @package core
 * @category output
 */
class paging_bar implements \renderable, \templatable {

    /**
     * @var int The maximum number of pagelinks to display.
     */
    public $maxdisplay = 18;

    /**
     * @var int The total number of entries to be pages through..
     */
    public $totalcount;

    /**
     * @var int The page you are currently viewing.
     */
    public $page;

    /**
     * @var int The number of entries that should be shown per page.
     */
    public $perpage;

    /**
     * @var string|\moodle_url If this  is a string then it is the url which will be appended with $pagevar,
     * an equals sign and the page number.
     * If this is a \moodle_url object then the pagevar param will be replaced by
     * the page no, for each page.
     */
    public $baseurl;

    /**
     * @var string This is the variable name that you use for the pagenumber in your
     * code (ie. 'tablepage', 'blogpage', etc)
     */
    public $pagevar;

    /**
     * @var string A HTML link representing the "previous" page.
     */
    public $previouslink = null;

    /**
     * @var string A HTML link representing the "next" page.
     */
    public $nextlink = null;

    /**
     * @var string A HTML link representing the first page.
     */
    public $firstlink = null;

    /**
     * @var string A HTML link representing the last page.
     */
    public $lastlink = null;

    /**
     * @var array An array of strings. One of them is just a string: the current page
     */
    public $pagelinks = array();

    /**
     * @var string Size with which to render.
     */
    public $displaysize = 'small';

    /**
     * Constructor paging_bar with only the required params.
     *
     * @param int $totalcount The total number of entries available to be paged through
     * @param int $page The page you are currently viewing
     * @param int $perpage The number of entries that should be shown per page
     * @param string|\moodle_url $baseurl url of the current page, the $pagevar parameter is added
     * @param string $pagevar name of page parameter that holds the page number
     */
    public function __construct($totalcount, $page, $perpage, $baseurl, $pagevar = 'page') {
        $this->totalcount = $totalcount;
        $this->page       = $page;
        $this->perpage    = $perpage;
        $this->baseurl    = $baseurl;
        $this->pagevar    = $pagevar;
    }

    /**
     * Prepares the paging bar for output.
     *
     * This method validates the arguments set up for the paging bar and then
     * produces fragments of HTML to assist display later on.
     *
     * @param renderer_base $output
     * @param moodle_page $page
     * @param string $target
     * @throws coding_exception
     */
    public function prepare(renderer_base $output, moodle_page $page, $target) {
        if (!isset($this->totalcount) || is_null($this->totalcount)) {
            throw new coding_exception('paging_bar requires a totalcount value.');
        }
        if (!isset($this->page) || is_null($this->page)) {
            throw new coding_exception('paging_bar requires a page value.');
        }
        if (empty($this->perpage)) {
            throw new coding_exception('paging_bar requires a perpage value.');
        }
        if (empty($this->baseurl)) {
            throw new coding_exception('paging_bar requires a baseurl value.');
        }

        if ($this->totalcount > $this->perpage) {
            $pagenum = $this->page - 1;

            if ($this->page > 0) {
                $this->previouslink = html_writer::link(
                    new \moodle_url($this->baseurl, array($this->pagevar => $pagenum)),
                    get_string('previous'),
                    array('class' => 'previous')
                );
            }

            if ($this->perpage > 0) {
                $lastpage = ceil($this->totalcount / $this->perpage);
            } else {
                $lastpage = 1;
            }

            if ($this->page > round(($this->maxdisplay / 3) * 2)) {
                $currpage = $this->page - round($this->maxdisplay / 3);

                $this->firstlink = html_writer::link(
                    new \moodle_url($this->baseurl, array($this->pagevar => 0)),
                    '1',
                    array('class' => 'first')
                );
            } else {
                $currpage = 0;
            }

            $displaycount = $displaypage = 0;

            while ($displaycount < $this->maxdisplay and $currpage < $lastpage) {
                $displaypage = $currpage + 1;

                if ($this->page == $currpage) {
                    $this->pagelinks[] = html_writer::span($displaypage, 'current-page');
                } else {
                    $pagelink = html_writer::link(
                        new \moodle_url($this->baseurl, array($this->pagevar => $currpage)),
                        $displaypage
                    );
                    $this->pagelinks[] = $pagelink;
                }

                $displaycount++;
                $currpage++;
            }

            if ($currpage < $lastpage) {
                $lastpageactual = $lastpage - 1;
                $this->lastlink = html_writer::link(
                    new \moodle_url($this->baseurl, array($this->pagevar => $lastpageactual)),
                    $lastpage,
                    array('class' => 'last')
                );
            }

            $pagenum = $this->page + 1;

            if ($pagenum != $displaypage) {
                $this->nextlink = html_writer::link(
                    new \moodle_url($this->baseurl, array($this->pagevar => $pagenum)),
                    get_string('next'),
                    array('class' => 'next')
                );
            }
        }
    }

    /**
     * Validates the paging bar for output, and returns \moodle_urls for rendering.
     * Replaces paging_bar::prepare().
     *
     * @param int $maxnumlinks The maximum number of page links to return.
     * @return object An object, where object->urls is an array of \moodle_urls and
     *         object->links is an array of pages.
     */
    public function validate($maxnumlinks = null) {

        if (!isset($this->totalcount) || is_null($this->totalcount)) {
            throw new coding_exception('paging_bar requires a totalcount value.');
        }
        if (!isset($this->page) || is_null($this->page)) {
            throw new coding_exception('paging_bar requires a page value.');
        }
        if (empty($this->perpage)) {
            throw new coding_exception('paging_bar requires a perpage value.');
        }
        if (empty($this->baseurl)) {
            throw new coding_exception('paging_bar requires a baseurl value.');
        }

        if ($maxnumlinks === null) {
            $maxnumlinks = $this->maxdisplay;
        }
        $returnobj = new stdClass();

        // Page count is the total item count divided by the per-page item count, clamped to a
        // minimum of 1.
        $pagecount = ($this->perpage > 0) ? ceil($this->totalcount / $this->perpage) : 1;

        // Clamp the requested page in a range between 0 and $pagecount.
        $this->page = min($pagecount - 1, max(0, $this->page));

        // We always want to try and render $maxnumlinks worth of pages; it's a bit
        // silly otherwise.
        $pagelinks = array();

        // Only render paging_bar if we have more than one page in our collection.
        if ($pagecount > 1) {

            // Pages displayed so far.
            $displaycount = 0;

            // Current page index.
            $pagesbefore = $pagesafter = floor($maxnumlinks / 2);

            // Adjust for off-by-one when $maxnumlinks is even.
            if ($pagesbefore + $pagesafter == $maxnumlinks) {
                $pagesafter--;
            }

            $currpage = max(0, $this->page - $pagesbefore);

            while ($currpage >= 0 && $currpage <= $this->page && $currpage < $pagecount) {
                $pagelinks[] = $currpage++;
            }
            while ($currpage < $pagecount && $currpage <= $this->page + $pagesafter) {
                $pagelinks[] = $currpage++;
            }

            $count = count($pagelinks);
            if ($count < $maxnumlinks) {
                // Okay, let's try shifting things onto the front.
                $currpage = $pagelinks[0] - 1;
                while ($currpage >= 0 && $count < $maxnumlinks) {
                    $count = array_unshift($pagelinks, $currpage--);
                }
                // Now let's try shifting things onto the front.
                $currpage = $pagelinks[$count - 1] + 1;
                while ($currpage < $pagecount && $count < $maxnumlinks) {
                    $count = array_push($pagelinks, $currpage++);
                }
            }
        }

        // At this point we have a list of page indices to render links for.
        // If we're not starting from page 0, we need to shift off the first two indices.
        if ($pagelinks[0] != 0) {

            $pagelink = new stdClass();
            $pagelink->attrs = array();
            $pagelink->text = 1;
            $pagelink->href = new \moodle_url(
                $this->baseurl,
                array(
                    $this->pagevar =>
                    0
                )
            );
            $returnobj->first = $pagelink;

            array_shift($pagelinks);
            array_shift($pagelinks);
        }
        // If we're not ending on page ($pagecount - 1), we need to pop off the last two
        // indices.
        if ($pagelinks[count($pagelinks) - 1] != $pagecount - 1) {

            $pagelink = new stdClass();
            $pagelink->attrs = array();
            $pagelink->text = $pagecount;
            $pagelink->href = new \moodle_url(
                $this->baseurl,
                array(
                    $this->pagevar =>
                    $pagecount - 1
                )
            );
            $returnobj->last = $pagelink;

            array_pop($pagelinks);
            array_pop($pagelinks);
        }

        // Build an array of page link objects.
        $returnobj->links = array();
        foreach ($pagelinks as $key => $value) {

            $pagelink = new stdClass();
            $pagelink->attrs = array();
            $pagelink->text = $value + 1;
            $pagelink->href = new \moodle_url(
                $this->baseurl,
                array(
                    $this->pagevar =>
                    $value
                )
            );
            if ($this->page == $value) {
                $class = new stdClass();
                $class->attr = 'class';
                $class->val = 'active';
                $pagelink->attrs[] = $class;
            }
            $returnobj->links[] = $pagelink;
        }

        return $returnobj;
    }

    /**
     * Export this data so it can be used as the context for a mustache template.
     *
     * @param renderer_base $output typically, the renderer that's calling this function
     * @return stdClass data context for a mustache template
     */
    public function export_for_template(\renderer_base $output) {

        $data = $this->validate();
        $data->size = $this->displaysize;
        $data->type = get_string('page');

        return $data;
    }

}
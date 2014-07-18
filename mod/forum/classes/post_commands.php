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
 * @package   mod_forum
 * @copyright 2014 Jetha Chan <jethachan@gmail.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();


class mod_forum_post_commands implements renderable {

    // String cache.
    static $str;

    // Meta.
    protected $post;
    protected $forum;
    protected $usercaps;

    public function __construct(&$postmeta, &$forummeta, &$usercaps) {

        if (empty(mod_forum_post_commands::$str)) {
            mod_forum_post_commands::$str = new stdClass;
            mod_forum_post_commands::$str->edit         = get_string('edit', 'forum');
            mod_forum_post_commands::$str->delete       = get_string('delete', 'forum');
            mod_forum_post_commands::$str->reply        = get_string('reply', 'forum');
            mod_forum_post_commands::$str->parent       = get_string('parent', 'forum');
            mod_forum_post_commands::$str->pruneheading = get_string('pruneheading', 'forum');
            mod_forum_post_commands::$str->prune        = get_string('prune', 'forum');
            mod_forum_post_commands::$str->markread     = get_string('markread', 'forum');
            mod_forum_post_commands::$str->markunread   = get_string('markunread', 'forum');
        }

        $this->post = $postmeta;
        $this->forum = $forummeta;
        $this->usercaps = $usercaps;

        // Build local, re-used variables based on the meta objects we received.
        $this->discussionlink = new moodle_url('/mod/forum/discuss.php', array('d' => $this->post->discussion));

        // Build command list.
        if ($this->post->showmarkread) {
            $this->add_markread();
        }
        $this->add_parent();
        $this->add_edit();
        if ($this->post->showprune) {
            $this->add_prune();
        }
        if ($this->post->showdelete) {
            $this->add_delete();
        }
        if ($this->post->showreply) {
            $this->add_reply();
        }
        if ($this->post->showexport) {
            $this->add_export();
        }
    }

    // DONE
    private function add_markread() {
        $url = new moodle_url($this->discussionlink, array('postid' => $this->post->id, 'mark' => 'unread'));
        $text = mod_forum_post_commands::$str->markunread;
        if (!$this->post->isread) {
            $url->param('mark', 'read');
            $text = mod_forum_post_commands::$str->markread;
        }
        if ($this->forummeta->displaymode == FORUM_MODE_THREADED) {
            $url->param('parent', $this->post->parent);
        } else {
            $url->set_anchor('p' . $this->post->id);
        }

        $this->enqueue($url, $text);
    }

    // DONE
    private function add_parent() {
        // Zoom in to the parent specifically.
        if ($this->post->parent) {
            $url = new moodle_url($this->discussionlink);
            if ($this->forum->displaymode == FORUM_MODE_THREADED) {
                $url->param('parent', $this->post->parent);
            } else {
                $url->set_anchor('p' . $this->post->parent);
            }
            $this->enqueue($url, mod_forum_post_commands::$str->parent);
        }
    }

    // DONE
    private function add_edit() {
        $url = false;
        $text = '';

        if ($this->forum->type == 'single' && $this->post->isfirst) {
            if ($this->usercaps['moodle/course:manageactivities']) {
                // The first post in single simple is the forum description.
                $url = new moodle_url('/course/modedit.php', array(
                    'update' => $this->coursemodule->id,
                    'sesskey' => sesskey(),
                    'return' => 1
                ));
            }
        } else if ($this->post->canstilledit || $this->usercaps['mod/forum:editanypost']) {
            $url = new moodle_url('/mod/forum/post.php', array('edit' => $this->post->id));
        }
        if ($url) {
            $text = mod_forum_post_commands::$str->edit;
            $this->enqueue($url, $text);
        }
    }

    // DONE
    private function add_prune() {
        $url = new moodle_url('/mod/forum/post.php', array('prune' => $this->post->id));
        $text = mod_forum_post_commands::$str->prune;

        $this->enqueue($url, $text, array('title' => mod_forum_post_commands::$str->pruneheading));
    }
    private function add_delete() {
        $url = new moodle_url('/mod/forum/post.php', array('delete' => $this->post->id));

        $this->enqueue($url, mod_forum_post_commands::$str->delete);
    }
    private function add_reply() {
        $url = new moodle_url('/mod/forum/post.php#mformforum', array('reply' => $this->post->id));

        $this->enqueue($url, mod_forum_post_commands::$str->reply);
    }
    private function add_export() {
        global $CFG;
        require_once($CFG->libdir . '/portfoliolib.php');

        $button = new portfolio_add_button();
        $button->set_callback_options('forum_portfolio_caller', array('postid' => $this->post->id), 'mod_forum');
        if ($this->post->hasattachments) {
            $button->set_formats(PORTFOLIO_FORMAT_PLAINHTML);
        } else {
            $button->set_formats(PORTFOLIO_FORMAT_RICHHTML);
        }

        $porfoliohtml = $button->to_html(PORTFOLIO_ADD_TEXT_LINK);
        if (!empty($porfoliohtml)) {
            $this->enqueueraw($porfoliohtml, 'portfolio');
        }
    }

    public function enqueue($url, $text, $attributes = null, $slug = null) {
        if (empty($slug)) {
            $slug = strtolower(preg_replace('#[ ]+#', '-', trim($text)));
        }
        if (empty($attributes)) {
            $attributes = array();
        }
        if (!array_key_exists('class', $attributes)) {
            $attributes['class'] = '';
        }
        if (strlen($attributes['class'] > 0)) {
            $attributes['class'] .= ' ';
        }
        $attributes['class'] .= $slug;
        $this->commands[$slug] = html_writer::link(
            $url,
            $text,
            $attributes
        );
    }

    public function enqueueraw($html, $slug) {
        $this->commands[$slug] = $html;
    }
}

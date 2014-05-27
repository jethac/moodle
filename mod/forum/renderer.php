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
 * This file contains a custom renderer class used by the forum module.
 *
 * @package   mod_forum
 * @copyright 2009 Sam Hemelryk
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * A custom renderer class that extends the plugin_renderer_base and
 * is used by the forum module.
 *
 * @package   mod_forum
 * @copyright 2009 Sam Hemelryk
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 **/
class mod_forum_renderer extends plugin_renderer_base {
    /**
     * This method is used to generate HTML for a subscriber selection form that
     * uses two user_selector controls
     *
     * @param user_selector_base $existinguc
     * @param user_selector_base $potentialuc
     * @return string
     */
    public function subscriber_selection_form(user_selector_base $existinguc, user_selector_base $potentialuc) {
        $output = '';
        $formattributes = array();
        $formattributes['id'] = 'subscriberform';
        $formattributes['action'] = '';
        $formattributes['method'] = 'post';
        $output .= html_writer::start_tag('form', $formattributes);
        $output .= html_writer::empty_tag('input', array('type'=>'hidden', 'name'=>'sesskey', 'value'=>sesskey()));

        $existingcell = new html_table_cell();
        $existingcell->text = $existinguc->display(true);
        $existingcell->attributes['class'] = 'existing';
        $actioncell = new html_table_cell();
        $actioncell->text  = html_writer::start_tag('div', array());
        $actioncell->text .= html_writer::empty_tag('input', array('type'=>'submit', 'name'=>'subscribe', 'value'=>$this->page->theme->larrow.' '.get_string('add'), 'class'=>'actionbutton'));
        $actioncell->text .= html_writer::empty_tag('br', array());
        $actioncell->text .= html_writer::empty_tag('input', array('type'=>'submit', 'name'=>'unsubscribe', 'value'=>$this->page->theme->rarrow.' '.get_string('remove'), 'class'=>'actionbutton'));
        $actioncell->text .= html_writer::end_tag('div', array());
        $actioncell->attributes['class'] = 'actions';
        $potentialcell = new html_table_cell();
        $potentialcell->text = $potentialuc->display(true);
        $potentialcell->attributes['class'] = 'potential';

        $table = new html_table();
        $table->attributes['class'] = 'subscribertable boxaligncenter';
        $table->data = array(new html_table_row(array($existingcell, $actioncell, $potentialcell)));
        $output .= html_writer::table($table);

        $output .= html_writer::end_tag('form');
        return $output;
    }

    /**
     * This function generates HTML to display a subscriber overview, primarily used on
     * the subscribers page if editing was turned off
     *
     * @param array $users
     * @param object $forum
     * @param object $course
     * @return string
     */
    public function subscriber_overview($users, $forum , $course) {
        $output = '';
        $modinfo = get_fast_modinfo($course);
        if (!$users || !is_array($users) || count($users)===0) {
            $output .= $this->output->heading(get_string("nosubscribers", "forum"));
        } else if (!isset($modinfo->instances['forum'][$forum->id])) {
            $output .= $this->output->heading(get_string("invalidmodule", "error"));
        } else {
            $cm = $modinfo->instances['forum'][$forum->id];
            $canviewemail = in_array('email', get_extra_user_fields(context_module::instance($cm->id)));
            $output .= $this->output->heading(get_string("subscribersto","forum", "'".format_string($forum->name)."'"));
            $table = new html_table();
            $table->cellpadding = 5;
            $table->cellspacing = 5;
            $table->tablealign = 'center';
            $table->data = array();
            foreach ($users as $user) {
                $info = array($this->output->user_picture($user, array('courseid'=>$course->id)), fullname($user));
                if ($canviewemail) {
                    array_push($info, $user->email);
                }
                $table->data[] = $info;
            }
            $output .= html_writer::table($table);
        }
        return $output;
    }

    /**
     * This is used to display a control containing all of the subscribed users so that
     * it can be searched
     *
     * @param user_selector_base $existingusers
     * @return string
     */
    public function subscribed_users(user_selector_base $existingusers) {
        $output  = $this->output->box_start('subscriberdiv boxaligncenter');
        $output .= html_writer::tag('p', get_string('forcesubscribed', 'forum'));
        $output .= $existingusers->display(true);
        $output .= $this->output->box_end();
        return $output;
    }

    /**
     * We keept he wrapper separate to the post body to allow themers to
     * replace basic markup around each post as required.
     *
     * @param mod_forum_post $post
     * @return string
     */
    public function render_mod_forum_post(mod_forum_post $post) {
        $o = '';
        $o .= "<article
                id='p{$post->id}'
                class='forumpost clearfix {$post->postclass} {$post->topicclass}'
                role='region'
                data-level='{$post->depth}'
                aria-label='" . get_string('postbyuser', 'forum', $post->author) . "'
                aria-labelledby='p{$post->id}_heading'
                tabindex='0'
            >";

        $o .= $this->render_mod_forum_post_body($post);

        foreach ($post->children as $child) {
            $o .= $this->render($child);
        }

        $o .= '</article>';
        return $o;
    }

    private function render_mod_forum_post_body($post) {
        $o = '';
        $o .= "<div class='row header clearfix'>";
        $o .= "<div class='left picture'>";
        $o .= $this->user_picture($post->author, array('courseid' => $post->courseid));
        $o .= '</div>';
        $o .= "<div class='topic{$post->topicclass}'>";

        // Subject
        $o .= $this->render_mod_forum_post_subject($post);

        // Byline
        $o .= $this->render_mod_forum_post_byline($post);

        $o .= "</div>";
        $o .= "</div>";


        // The main content.
        $o .= "<div class='row maincontent clearfix'>";

        // The group picture.
        $o .= $this->render_mod_forum_post_grouppicture($post);

        $o .= "<div class='no-overflow'>";
        $o .= "<div class='content'>";
        // TODO make this it's own renderable/render.
        $o .= $this->render_mod_forum_post_attachments($post);

        // TODO make this it's own renderable/render.
        $o .= "<div class='posting {$post->postclass}'>";
        $o .= $post->message;
        $o .= $post->plagiarismlinks;
        if ($post->wordcount) {
            $o .= "<div class='post-word-count'>";
            $o .= get_string('numwords', 'moodle', $p->wordcount);
            $o .= "</div>";
        }
        $o .= "</div>";
        // $o .= $this->render($post->message);
        $o .= "</div>"; // content.
        $o .= '</div>'; // no-overflow.

        $o .= '</div>'; // row.

        // The final row.
        $o .= "<div class='row side'>";
        $o .= "<div class='left'>&nbsp;</div>";
        $o .= "<div class='options clearfix'>";

        // Ratings.
        if (!empty($post->rating)) {
            $o .= $this->render($post->ratings);
        }

        // Commands.
        // TODO make this it's own render.
        $o .= $this->render_mod_forum_post_commands($post);
        // $o .= $this->render($post->commands);

        // Links.
        // TODO Rewrite this abomination.
        // $o .= $this->render($post->replies);
        if ($post->link) {
            $o .="<div class='link'>{$post->link} ({$post->replies})</div>";
        }

        // Footer.
        // $o .= $this->render($post->footer);
        $o .= $this->render_mod_forum_post_footer($post);

        $o .= '</div>'; // content.
        $o .= '</div>'; // row.

        return $o;
    }

    private function render_mod_forum_post_subject($post) {
        $o = '';
        $o .= "<div class='subject' role='heading' aria-level='2' id='p{$post->id}_heading'>";
        $o .= s($post->subject);
        $o .= "</div>";

        return $o;
    }

    private function render_mod_forum_post_byline($post) {
        $o = '';
        $o .= "<address>";
        $o .= get_string('bynameondate', 'forum', $post->author);
        $o .= "</address>";

        return $o;
    }

    private function render_mod_forum_post_attachments($post) {
        $o = '';
        $o .= "<div class='attachments'>{$post->attachments}</div>";

        return $o;
    }

    private function render_mod_forum_post_commands($post) {
        $o = '';
        $o .= "<div class='commands'>{$post->commands}</div>";

        return $o;
    }

    private function render_mod_forum_post_grouppicture($post) {
        $o = '';
        $o .= "<div class='left'>";
        $o .= "<div class='grouppictures'>";
        // TODO Make this a renderer.
        $o .= $post->grouppicture;
        $o .= "</div>";
        $o .= "</div>";

        return $o;
    }

    public function render_mod_forum_post_author(mod_forum_post_author $author) {
        $o = '';
        return $o;
    }

    public function render_mod_forum_post_message(mod_forum_post_message $message) {
        $o = '';
        return $o;
    }

    private function render_mod_forum_post_footer($post) {
        $o = '';
        $o .= "<footer>{$post->footer}</footer>";

        return $o;
    }

}

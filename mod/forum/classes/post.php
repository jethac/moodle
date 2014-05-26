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
 * @copyright 2014 Andrew Robert Nicols <andrew@nicols.co.uk>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

class mod_forum_post implements renderable {
    public $id = null;
    public $courseid = null;
    /**
     * @var author The User who made this post.
     */
    public $author = null;

    public $parent = null;

    public $subject = null;

    public $message = null;

    public $messageformat = null;
    public $messagetrust = null;

    public $created = null;
    public $modified = null;

    public $level = null;

    public $istracked = false;
    public $isread = false;
    public $postclass = '';
    public $topicclass = '';

    public $grouppicture = '&nbsp;';
    public $attachments = '';

    public $rating = null;
    public $commands = '';

    public $link = '';
    public $replies = '';
    public $footer = '';

    public function __construct($post, $discussion) {
        $this->id = $post->id;

        $this->courseid = $discussion->course;
        // TODO - this shouldl become a user object?
        $this->author = $post->userid;
        $this->parent = $post->parent;
        $this->subject = $post->subject;
        if (empty($post->subjectnoformat)) {
            $this->subject = format_string($this->subject);
        }
        $this->message = $post->message;
        $this->messageformat = $post->messageformat;
        $this->messagetrust = $post->messagetrust;
        $this->created = $post->created;
        $this->modified = $post->modified;

        // TODO Determine the post level.
        $this->level = 1;

        if (empty($this->parent)) {
            $this->topicclass = 'firstpost starter';
        }
    }

    public function set_tracked($istracked) {
        $this->istracked = $istracked;
    }

    public function set_read($isread) {
        $this->isread = $isread;
        if ($this->istracked) {
            if ($isread) {
                $this->postclass = 'read';
            } else {
                $this->postclass = 'unread';
            }
        }
    }

    public function set_author($author) {
        $this->author = $author;
    }
}

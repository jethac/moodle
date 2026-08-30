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

namespace qbank_protectednode;

/**
 * Navigation node which is only available to users who can manage question categories.
 *
 * @package   core_question
 * @copyright 2026 Moodle
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class navigation extends \core_question\local\bank\navigation_node_base {
    /**
     * Title for this node.
     *
     * @return string
     */
    public function get_navigation_title(): string {
        return 'Protected node';
    }

    /**
     * Key for this node.
     *
     * @return string
     */
    public function get_navigation_key(): string {
        return 'protectednode';
    }

    /**
     * URL for this node.
     *
     * @return \moodle_url
     */
    public function get_navigation_url(): \moodle_url {
        return new \moodle_url('/question/bank/protectednode/index.php');
    }

    /**
     * Capabilities required to see this node.
     *
     * @return ?array
     */
    public function get_navigation_capabilities(): ?array {
        return ['moodle/question:managecategory'];
    }
}

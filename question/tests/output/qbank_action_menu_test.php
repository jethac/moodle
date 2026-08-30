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

namespace core_question\output;

use context_course;
use moodle_url;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/../fixtures/fakeplugins/qbank/protectednode/classes/navigation.php');
require_once(__DIR__ . '/../fixtures/fakeplugins/qbank/protectednode/classes/plugin_feature.php');

/**
 * Tests for the question bank tertiary navigation menu.
 *
 * @package   core_question
 * @copyright 2026 Moodle
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers    \core_question\output\qbank_action_menu
 */
final class qbank_action_menu_test extends \advanced_testcase {
    /**
     * Make the fake qbank_protectednode plugin, whose navigation node requires moodle/question:managecategory, available.
     */
    protected function setup_fake_qbank_plugin(): void {
        global $CFG;

        $this->add_mocked_plugin(
            'qbank',
            'protectednode',
            "{$CFG->dirroot}/question/tests/fixtures/fakeplugins/qbank/protectednode",
        );
        \core_plugin_manager::reset_caches();
    }

    /**
     * Render the menu for the given course context.
     *
     * @param context_course $context
     * @return string
     */
    protected function render_menu(context_course $context): string {
        global $PAGE;

        $menu = new qbank_action_menu(new moodle_url('/question/edit.php', ['courseid' => $context->instanceid]), $context);
        return $PAGE->get_renderer('core_question', 'bank')->render($menu);
    }

    /**
     * A plugin node is shown to a user who has one of the node's capabilities.
     *
     * Note: this injects a mocked plugin into core_component and must be run in isolation.
     *
     * @runInSeparateProcess
     */
    public function test_navigation_node_shown_with_capability(): void {
        $this->resetAfterTest();
        $this->setup_fake_qbank_plugin();

        $course = $this->getDataGenerator()->create_course();
        $teacher = $this->getDataGenerator()->create_and_enrol($course, 'editingteacher');
        $this->setUser($teacher);

        $this->assertStringContainsString('Protected node', $this->render_menu(context_course::instance($course->id)));
    }

    /**
     * A plugin node is not shown to a user who has none of the node's capabilities.
     *
     * Note: this injects a mocked plugin into core_component and must be run in isolation.
     *
     * @runInSeparateProcess
     */
    public function test_navigation_node_hidden_without_capability(): void {
        $this->resetAfterTest();
        $this->setup_fake_qbank_plugin();

        $course = $this->getDataGenerator()->create_course();
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $this->setUser($student);

        $this->assertStringNotContainsString('Protected node', $this->render_menu(context_course::instance($course->id)));
    }
}

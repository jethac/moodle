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

namespace core_backup;

use backup;
use context_course;
use moodle_url;
use moodleform;
use restore_controller;
use restore_ui;
use restore_ui_stage_settings;

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot . '/backup/util/includes/backup_includes.php');
require_once($CFG->dirroot . '/backup/util/includes/restore_includes.php');
require_once($CFG->dirroot . '/backup/tests/backup_restore_base_testcase.php');

/**
 * Tests for base_moodleform class.
 *
 * @package   core_backup
 * @copyright 2026 Jethro Chan <jethachan@gmail.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers    \base_moodleform::add_dependencies
 */
final class base_moodleform_test extends \core_backup_backup_restore_base_testcase {
    /**
     * Returns the client side dependencies of the restore settings stage form.
     *
     * @param \stdClass $course course to restore into.
     * @return array the dependencies of the form, as stored by MoodleQuickForm::disabledIf().
     */
    protected function get_restore_settings_form_dependencies(\stdClass $course): array {
        global $PAGE, $USER;

        $PAGE->set_url(new moodle_url('/backup/restore.php'));

        $backupid = $this->perform_backup($course);
        $rc = new restore_controller(
            $backupid,
            $course->id,
            backup::INTERACTIVE_YES,
            backup::MODE_GENERAL,
            $USER->id,
            backup::TARGET_EXISTING_ADDING,
        );

        // The restore user interface picks the stage up from the request.
        $_GET['stage'] = restore_ui::STAGE_SETTINGS;
        $ui = new restore_ui($rc, ['contextid' => context_course::instance($course->id)->id]);
        $stage = new restore_ui_stage_settings($ui, []);

        // The stage form and the quickform it wraps are only available internally, but they are what
        // carries the client side dependencies we want to assert on.
        $initialiseform = new \ReflectionMethod($stage, 'initialise_stage_form');
        $initialiseform->setAccessible(true);
        $stageform = $initialiseform->invoke($stage);
        $formproperty = new \ReflectionProperty(moodleform::class, '_form');
        $formproperty->setAccessible(true);
        $mform = $formproperty->getValue($stageform);

        return $mform->_dependencies;
    }

    /**
     * A setting the user can change disables the settings depending on it.
     */
    public function test_add_dependencies_for_changeable_setting(): void {
        $course = $this->getDataGenerator()->create_course();

        $dependencies = $this->get_restore_settings_form_dependencies($course);

        $this->assertContains('setting_root_badges', $dependencies['setting_root_activities']['notchecked'][1]);
        $this->assertContains('setting_root_badges', $dependencies['setting_root_users']['notchecked'][1]);
    }

    /**
     * A locked setting is displayed as a fixed value, so it must not disable the settings depending on it.
     *
     * The fixed setting is rendered as a hidden element, which is never checked, so a client side dependency
     * on it would disable and hence discard the settings depending on it. See MDL-86505.
     */
    public function test_add_dependencies_for_locked_setting(): void {
        $course = $this->getDataGenerator()->create_course();

        set_config('restore_general_activities', 1, 'restore');
        set_config('restore_general_activities_locked', 1, 'restore');

        $dependencies = $this->get_restore_settings_form_dependencies($course);

        $this->assertArrayNotHasKey('setting_root_activities', $dependencies);
        // Settings depending on a setting the user can still change are not affected.
        $this->assertContains('setting_root_badges', $dependencies['setting_root_users']['notchecked'][1]);
    }
}

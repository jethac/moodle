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

namespace core\check\access;

use core\check\result;

/**
 * Tests for the XSS trusted users check.
 *
 * @package    core
 * @category   check
 * @copyright  2026 Jetha Chan <jethachan@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \core\check\access\riskxss_result
 */
final class riskxss_test extends \advanced_testcase {
    /**
     * Create a role granting a single capability with an XSS risk in its definition.
     *
     * @return int the role id
     */
    private function create_trusted_role(): int {
        $roleid = create_role('XSS risk', 'xssrisk', 'Grants a capability with an XSS risk');
        assign_capability('moodle/site:trustcontent', CAP_ALLOW, $roleid, \context_system::instance()->id);
        accesslib_clear_all_caches_for_unit_testing();
        return $roleid;
    }

    /**
     * A site where nobody holds a capability with an XSS risk passes the check.
     */
    public function test_no_trusted_users(): void {
        $this->resetAfterTest();

        $this->getDataGenerator()->create_user();

        $result = (new riskxss())->get_result();
        $this->assertEquals(result::OK, $result->get_status());
    }

    /**
     * A user assigned a role which grants a capability with an XSS risk is reported.
     */
    public function test_trusted_user_is_reported(): void {
        $this->resetAfterTest();

        $user = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();
        role_assign($this->create_trusted_role(), $user->id, \context_course::instance($course->id)->id);

        $result = (new riskxss())->get_result();
        $this->assertEquals(result::WARNING, $result->get_status());
        $this->assertStringContainsString(fullname($user), $result->get_details());
    }

    /**
     * A user is reported when the capability is granted by an override below the assignment.
     */
    public function test_trusted_user_is_reported_when_granted_by_override(): void {
        $this->resetAfterTest();

        $user = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();
        $coursecontext = \context_course::instance($course->id);

        $roleid = create_role('Overridden', 'overridden', 'Only granted the risky capability in a course');
        role_assign($roleid, $user->id, \context_system::instance()->id);
        assign_capability('moodle/site:trustcontent', CAP_ALLOW, $roleid, $coursecontext->id);
        accesslib_clear_all_caches_for_unit_testing();

        $result = (new riskxss())->get_result();
        $this->assertEquals(result::WARNING, $result->get_status());
        $this->assertStringContainsString(fullname($user), $result->get_details());
    }

    /**
     * A user whose only grant is taken away again by an override is not reported.
     *
     * @dataProvider removed_permission_provider
     * @param int $permission the permission set by the override
     * @param bool $onparent whether the override is placed on the parent category instead
     */
    public function test_user_without_the_capability_is_not_reported(int $permission, bool $onparent): void {
        $this->resetAfterTest();

        $user = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();
        $coursecontext = \context_course::instance($course->id);
        $roleid = $this->create_trusted_role();
        role_assign($roleid, $user->id, $coursecontext->id);

        $overridecontext = $onparent ? \context_coursecat::instance($course->category) : $coursecontext;
        assign_capability('moodle/site:trustcontent', $permission, $roleid, $overridecontext->id, true);
        accesslib_clear_all_caches_for_unit_testing();

        $this->assertFalse(has_capability('moodle/site:trustcontent', $coursecontext, $user, false));

        $result = (new riskxss())->get_result();
        $this->assertEquals(result::OK, $result->get_status());
        $this->assertStringNotContainsString(fullname($user), $result->get_details());
    }

    /**
     * Data provider for {@see test_user_without_the_capability_is_not_reported}.
     *
     * @return array[]
     */
    public static function removed_permission_provider(): array {
        return [
            'prevented in the assignment context' => [CAP_PREVENT, false],
            'prohibited in the assignment context' => [CAP_PROHIBIT, false],
            'prevented in a parent context' => [CAP_PREVENT, true],
            'prohibited in a parent context' => [CAP_PROHIBIT, true],
        ];
    }
}

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
 * Unit tests for the sign-up form.
 *
 * @package    core
 * @copyright  2026 Jetha Chan <jethachan@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace core;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/login/signup_form.php');

/**
 * Sign-up form testcase.
 *
 * @package    core
 * @copyright  2026 Jetha Chan <jethachan@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \login_signup_form
 */
final class signup_form_test extends \advanced_testcase {
    /**
     * Enable email based self-registration with the captcha element on the sign-up form.
     */
    protected function enable_signup_captcha(): void {
        global $CFG;

        $CFG->registerauth = 'email';
        $CFG->recaptchapublickey = 'publickey';
        $CFG->recaptchaprivatekey = 'privatekey';
        set_config('recaptcha', 1, 'auth_email');
    }

    /**
     * Sign-up data of a would be new account, clashing with the given user.
     *
     * @param \stdClass $user the existing user whose username and email address are reused
     * @return array the submitted sign-up data
     */
    protected function get_clashing_signup_data(\stdClass $user): array {
        return [
            'username' => $user->username,
            'password' => 'Password-1',
            'firstname' => 'First',
            'lastname' => 'Last',
            'email' => $user->email,
            'email2' => $user->email,
            'city' => 'Perth',
            'country' => 'AU',
        ];
    }

    /**
     * A missing captcha response must stop the submitted data from being validated at all.
     *
     * Otherwise the errors reported back to the client disclose whether the submitted username or
     * email address belongs to an existing account, which lets a bot enumerate the accounts of the
     * site without ever solving the captcha.
     */
    public function test_validation_skips_data_validation_when_captcha_is_missing(): void {
        $this->resetAfterTest();
        $this->enable_signup_captcha();

        $user = $this->getDataGenerator()->create_user([
            'username' => 'existinguser',
            'email' => 'existinguser@example.com',
        ]);

        $form = new \login_signup_form();
        $errors = $form->validation($this->get_clashing_signup_data($user), []);

        $this->assertEquals([
            'recaptcha_element' => get_string('missingrecaptchachallengefield'),
        ], $errors);
    }

    /**
     * With the captcha element disabled, the submitted data is validated as usual.
     */
    public function test_validation_reports_data_errors_when_captcha_is_disabled(): void {
        global $CFG;

        $this->resetAfterTest();
        $CFG->registerauth = 'email';

        $user = $this->getDataGenerator()->create_user([
            'username' => 'existinguser',
            'email' => 'existinguser@example.com',
        ]);

        $form = new \login_signup_form();
        $errors = $form->validation($this->get_clashing_signup_data($user), []);

        $this->assertEquals(get_string('usernameexists'), $errors['username']);
        $this->assertStringContainsString(get_string('emailexists'), $errors['email']);
    }
}

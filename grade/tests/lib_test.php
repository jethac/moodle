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
 * Unit tests for grade/lib.php.
 *
 * @package   core_grades
 * @category  test
 * @copyright 2015 Jetha Chan <jethachan@gmail.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU Public License
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/grade/lib.php');

/**
 * Tests grade_helper functions.
 *
 * @package   core_grades
 * @category  test
 * @copyright 2015 Jetha Chan <jethachan@gmail.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU Public License
 */
class core_grade_helper_test extends advanced_testcase {

    /**
     * Tests grade_helper::get_user_field_value.
     */
    public function test_get_user_field_value() {
        global $CFG, $USER, $DB;

        $this->resetAfterTest();

        $record = array(
            'username' => 'yamadat',
            'password' => 'Moodle2015!',
            'idnumber' => 'idnumbertest1',
            'firstname' => '山田たろう',
            'lastname' => '太郎',
            'lastnamephonetic' => 'やまだ',
            'firstnamephonetic' => 'たろう',
            'email' => 'usertest1@example.com',
            'description' => "Everybody knows Yamada Tarou.",
            'city' => 'Tokyo',
            'country' => 'jp',
            'institution' => 'Frontier Heavy Industries',
            'department' => 'Product Development'
            );

        // Create user.
        $user = $this->getDataGenerator()->create_user($record);

        // Set up the alternate fullname format.
        $CFG->fullnamedisplay =
            'firstname lastname (firstnamephonetic lastnamephonetic)';
        $expectedname = implode(
            ' ',
            array(
                $record['firstname'],
                $record['lastname'],
                '(' . $record['firstnamephonetic'],
                $record['lastnamephonetic'] . ')'
            )
        );

        // Do we get the right result out of fullname()?
        $testname = fullname($user);
        $this->assertSame($expectedname, $testname);

        // Now, do we get the right results out of get_user_field_value?
        $CFG->grade_export_userprofilefields = implode(
            ',',
            array(
                'firstname',
                'lastname',
                'fullname',
                'idnumber',
                'institution',
                'department',
                'email'
            )
        );
        $record['fullname'] = $testname;
        unset($record['password']);
        foreach ($record as $key => $value) {
            $field = new stdClass();
            $field->shortname = $key;
            $this->assertSame($value, grade_helper::get_user_field_value($user, $field));
        }
    }
}
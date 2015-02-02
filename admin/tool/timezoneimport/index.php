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
 * Automatic update of Timezones from a new source
 *
 * @package    tool
 * @subpackage timezoneimport
 * @copyright  1999 onwards Martin Dougiamas  {@link http://moodle.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(dirname(dirname(__DIR__))) . '/config.php');
require_once(__DIR__ . '/lib.php');
require_once($CFG->libdir . '/outputcomponents.php');

admin_externalpage_setup('tooltimezoneimport');

$ok = optional_param('ok', 0, PARAM_BOOL);

// Print headings.
$strimporttimezones = get_string('importtimezones', 'tool_timezoneimport');
echo $OUTPUT->header();
echo $OUTPUT->heading($strimporttimezones);

if (!$ok or !confirm_sesskey()) {

    $unicodeloc = 'http://unicode.org/repos/cldr/trunk/common/supplemental/windowsZones.xml';

    $tzlabels = array(
        get_string('timezonelocs', 'tool_timezoneimport'),
        get_string('timezonemappinglocs', 'tool_timezoneimport')
    );
    $tzlocs = array(
        array(
            $CFG->tempdir . '/olson.txt',
            $CFG->tempdir . '/timezone.txt',
            '<a href="https://download.moodle.org/timezone/">https://download.moodle.org/timezone/</a>',
            '<a href="' . $CFG->wwwroot.'/lib/timezone.txt">' . $CFG->dirroot . '/lib/timezone.txt</a>'
        ),
        array(
            $CFG->tempdir . '/windowsZones.xml',
            '<a href="' . $unicodeloc . '">' . $unicodeloc . '</a>',
            '<a href="' . $CFG->wwwroot . '/lib/timezonewindows.xml">' . $CFG->dirroot . '/lib/timezonewindows.xml</a>'
        )
    );

    $message = '';
    for ($i = 0; $i < count($tzlabels); $i++) {

        $messageinner = '';
        $locs = $tzlocs[$i];
        for ($j = 0; $j < count($locs); $j++) {
            $messageinner .= html_writer::tag('li', $locs[$j]);
        }
        $message .= $OUTPUT->heading($tzlabels[$i], 5);
        $message .= html_writer::tag('ol', $messageinner, array('class' => 'tzloclist'));
    }

    $message = get_string("configintrotimezones", 'tool_timezoneimport', $message);

    echo $OUTPUT->confirm($message, 'index.php?ok=1', new moodle_url('/admin/index.php'));
    echo $OUTPUT->footer();

    exit;
}

// Try to find a source of timezones to import from.
$timezones = array();
$importdone = tool_timezoneimport_import_olson($timezones);

if ($importdone) {

    $mapsource = tool_timezoneimport_import_windows_mappings();

        $a = new stdClass();
        $a->count = count($timezones);
        $a->source  = $importdone;

        echo $OUTPUT->notification(get_string('importtimezonescount', 'tool_timezoneimport', $a), 'notifysuccess');

    echo $OUTPUT->notification(get_string('importtimezonesmappings', 'tool_timezoneimport', $mapsource), 'notifysuccess');

    echo $OUTPUT->continue_button(new moodle_url('/admin/index.php'));

    $timezonelist = array();
    foreach ($timezones as $timezone) {
        if (is_array($timezone)) {
            $timezone = (object)$timezone;
        }
        if (isset($timezonelist[$timezone->name])) {
            $timezonelist[$timezone->name]++;
        } else {
            $timezonelist[$timezone->name] = 1;
        }
    }
    ksort($timezonelist);
    $timezonetable = new html_table();
    $timezonetable->head = array(
        get_string('timezone', 'moodle'),
        get_string('entries', 'moodle')
    );
    $rows = array();
    foreach ($timezonelist as $name => $count) {
        $row = new html_table_row(
            array(
                new html_table_cell($name),
                new html_table_cell($count)
            )
        );
        $rows[] = $row;
    }
    $timezonetable->data = $rows;
    echo html_writer::table($timezonetable);

} else {
    echo $OUTPUT->heading(get_string('importtimezonesfailed', 'tool_timezoneimport'), 3);
    echo $OUTPUT->continue_button(new moodle_url('/admin/index.php'));
}

echo $OUTPUT->footer();

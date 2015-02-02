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
 * Timezone import tool functions.
 *
 * @package    tool_timezoneimport
 * @copyright  2015 Jetha Chan <jetha@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->libdir . '/filelib.php');
require_once($CFG->libdir . '/olson.php');

/**
 * Import IANA Olson timezone db information.
 *
 * @param array $timezones An array to be populated with timezone information.
 * @return string The data source timezone information was loaded from.
 */
function tool_timezoneimport_import_olson(&$timezones) {

    global $CFG;

    $importdone = false;
    // First, look for an Olson file locally.
    $source = $CFG->tempdir.'/olson.txt';
    if (!$importdone and is_readable($source)) {
        if ($timezones = olson_to_timezones($source)) {
            update_timezone_records($timezones);
            $importdone = $source;
        }
    }

    // Next, look for a CSV file locally.
    $source = $CFG->tempdir.'/timezone.txt';
    if (!$importdone and is_readable($source)) {
        if ($timezones = get_records_csv($source, 'timezone')) {
            update_timezone_records($timezones);
            $importdone = $source;
        }
    }

    // Otherwise, let's try moodle.org's copy.
    $source = 'https://download.moodle.org/timezone/';
    if (!$importdone && ($content = download_file_content($source))) {

        // Make local copy.
        if ($file = fopen($CFG->tempdir.'/timezone.txt', 'w')) {
            fwrite($file, $content);
            fclose($file);

            // Parse it.
            if ($timezones = get_records_csv($CFG->tempdir.'/timezone.txt', 'timezone')) {
                update_timezone_records($timezones);
                $importdone = $source;
            }
            unlink($CFG->tempdir.'/timezone.txt');
        }
    }

    // Final resort, use the copy included in Moodle.
    $source = $CFG->dirroot.'/lib/timezone.txt';
    if (!$importdone and is_readable($source)) {
        if ($timezones = get_records_csv($source, 'timezone')) {
            update_timezone_records($timezones);
            $importdone = $source;
        }
    }

    return $importdone;
}

/**
 * Import Windows -> Olson mappings from the Unicode CLDR.
 *
 * @return string The data source that Windows -> Olson mappings were loaded from.
 */
function tool_timezoneimport_import_windows_mappings() {

    global $DB, $CFG;

    $legacymappings = array(
        'America/Chicago' => array('CST', 'Central Time', 'Central Standard Time'),
        'Europe/Berlin' => array('CET', 'Central European Time'),
        'America/New_York' => array('EST', 'Eastern Time', 'Eastern Standard Time'),
        'America/Los_Angeles' => array('PST', 'Pacific Time', 'Pacific Standard Time'),
        'Asia/Shanghai' => array('China Time', 'China Standard Time'),
        'Asia/Kolkata' => array('IST', 'India Time', 'India Standard Time'),
        'Asia/Tokyo' => array('JST', 'Japan Time', 'Japan Standard Time')
    );

    try {
        $transaction = $DB->start_delegated_transaction();

        $timezoneswithids = $DB->get_records_menu('timezone', null, $fields = 'id, name');

        $unicodecldr = new stdClass();
        $unicodecldr->dom = new DOMDocument();

        $domloaded = false;

        // First, check for a copy in the moodle temp dir.
        $source = $CFG->tempdir . '/windowsZones.xml';
        if (!$domloaded and is_readable($source)) {
            $domloaded = $unicodecldr->dom->load($source);
            $unicodecldr->source = $source;
        }

        // Second, try and grab a copy from the Unicode organisation.
        $source = "http://unicode.org/repos/cldr/trunk/common/supplemental/windowsZones.xml";
        if (!$domloaded && ($content = download_file_content($source))) {
            // Make local copy.
            if ($file = fopen($CFG->tempdir . '/windowsZones.xml', 'w')) {
                fwrite($file, $content);
                fclose($file);

                $domloaded = $unicodecldr->dom->load($source);
                unlink($CFG->tempdir . '/windowsZones.xml');
                $unicodecldr->source = $source;
            }
        }

        // Final resort, use the copy included in Moodle.
        $source = $CFG->dirroot . '/lib/timezonewindows.xml';
        if (!$domloaded and is_readable($source)) {
            $domloaded = $unicodecldr->dom->load($source);
            $unicodecldr->source = $source;
        }

        $mapzones = array();

        if ($domloaded) {
            // Load version information.
            $root = $unicodecldr->dom->getElementsByTagName("mapTimezones")->item(0);

            if ($root) {
                $unicodecldr->version = new stdClass();
                $unicodecldr->version->unicode = $root->getAttribute("typeVersion");
                $unicodecldr->version->windows = $root->getAttribute("otherVersion");

                // Load zones.
                $mapzones = $unicodecldr->dom->getElementsByTagName("mapZone");
            } else {
                throw new moodle_exception('errorunexpectedxmlstructure', 'tool_timezoneimport', '');
            }
        } else {
            throw new moodle_exception('errorgettingmappingxml', 'tool_timezoneimport', '', $unicodecldr->source);
        }

        if (count($mapzones) > 0) {
            $maprows = array();
            foreach ($mapzones as $zone) {

                // Build row.
                $row = new stdClass();
                $row->name = $zone->getAttribute('other');
                $row->territory = $zone->getAttribute('territory');

                // Sometimes the CLDR's mapping includes multiple items.
                $searchterms = explode(' ', $zone->getAttribute('type'));

                $foundolson = -1;
                foreach ($searchterms as $searchterm) {
                    $extantid = array_search($searchterm, $timezoneswithids);
                    if ($extantid) {
                        // We found an existing Olson mapping.
                        $foundolson = $extantid;
                        break;
                    }
                }
                if ($foundolson != -1) {
                    $row->olsonid = $foundolson;
                    $maprows[] = $row;
                }
            }

            // Load legacy mappings for backwards compatibility.
            foreach ($legacymappings as $result => $keys) {
                foreach ($keys as $key) {

                    $row = new stdClass();
                    $row->name = $key;
                    $row->territory = "001";

                    $foundolson = -1;
                    $extantid = array_search($result, $timezoneswithids);
                    if ($extantid) {
                        // We found an existing Olson mapping.
                        $foundolson = $extantid;
                    }

                    if ($foundolson != -1) {
                        $row->olsonid = $foundolson;
                        $maprows[] = $row;
                    }

                }
            }

            $DB->delete_records('timezone_windows_to_olson');
            $DB->insert_records('timezone_windows_to_olson', $maprows);

            $transaction->allow_commit();

            return $unicodecldr->source;
        } else {
            throw new moodle_exception('errorunexpectedxmlstructure', 'tool_timezoneimport', '');
        }

    } catch (Exception $e) {
        $transaction->rollback($e);
    }

}

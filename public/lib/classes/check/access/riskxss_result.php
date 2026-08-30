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
 * Lists all users with XSS risk
 *
 * It would be great to combine this with risk trusts in user table,
 * unfortunately nobody implemented user trust UI yet :-(
 *
 * @package    core
 * @category   check
 * @copyright  2020 Brendan Heywood <brendan@catalyst-au.net>
 * @copyright  2008 petr Skoda
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace core\check\access;

defined('MOODLE_INTERNAL') || die();

use core\check\result;

/**
 * Lists all users with XSS risk
 *
 * It would be great to combine this with risk trusts in user table,
 * unfortunately nobody implemented user trust UI yet :-(
 *
 * @copyright  2020 Brendan Heywood <brendan@catalyst-au.net>
 * @copyright  2008 petr Skoda
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class riskxss_result extends \core\check\result {

    /** @var int[] Ids of the users who hold a capability with an XSS risk. */
    protected $userids;

    /**
     * Constructor
     */
    public function __construct() {

        $this->userids = $this->get_trusted_userids();
        $count = count($this->userids);

        if ($count == 0) {
            $this->status = result::OK;
        } else {
            $this->status = result::WARNING;
        }

        $this->summary = get_string('check_riskxss_warning', 'report_security', $count);
    }

    /**
     * Ids of the users who really hold at least one capability with an XSS risk somewhere.
     *
     * Role assignments, role definitions and overrides only tell us where a capability may be
     * granted to a user; a prevent or prohibit override in between can still take it away again,
     * so every candidate is re-evaluated with the access API.
     *
     * @return int[]
     */
    protected function get_trusted_userids(): array {

        global $DB;

        $select = $DB->sql_bitand('riskbitmask', RISK_XSS) . ' <> 0';
        $xsscapabilities = $DB->get_fieldset_select('capabilities', 'name', $select);
        if (!$xsscapabilities) {
            return [];
        }

        // Candidate users, together with the context the capability is granted in ('c', either
        // the system context for a role definition or the context of an override) and the context
        // their role is assigned in ('sc').
        $sql = "SELECT DISTINCT ra.userid, c.id AS grantcontextid, c.depth AS grantdepth,
                                sc.id AS assigncontextid, sc.depth AS assigndepth
                           FROM (SELECT DISTINCT rcx.contextid, rcx.roleid
                                   FROM {role_capabilities} rcx
                                   JOIN {capabilities} cap ON (cap.name = rcx.capability AND
                                        " . $DB->sql_bitand('cap.riskbitmask', RISK_XSS) . " <> 0)
                                  WHERE rcx.permission = :capallow) rc
                           JOIN {context} c ON c.id = rc.contextid
                           JOIN {context} sc ON (sc.path = c.path OR
                                sc.path LIKE " . $DB->sql_concat('c.path', "'/%'") . " OR
                                c.path LIKE " . $DB->sql_concat('sc.path', "'/%'") . ")
                           JOIN {role_assignments} ra ON (ra.contextid = sc.id AND ra.roleid = rc.roleid)
                           JOIN {user} u ON (u.id = ra.userid AND u.deleted = 0)
                       ORDER BY ra.userid";

        $userids = [];
        $rs = $DB->get_recordset_sql($sql, ['capallow' => CAP_ALLOW]);
        foreach ($rs as $candidate) {
            if (isset($userids[$candidate->userid])) {
                continue;
            }
            // A grant only applies from its own context downwards, so the capability has to be
            // evaluated in the deeper of the two contexts.
            $contextid = $candidate->grantdepth > $candidate->assigndepth
                ? $candidate->grantcontextid
                : $candidate->assigncontextid;
            $context = \context::instance_by_id($contextid, IGNORE_MISSING);
            if ($context && has_any_capability($xsscapabilities, $context, $candidate->userid)) {
                $userids[$candidate->userid] = (int)$candidate->userid;
            }
        }
        $rs->close();

        return array_values($userids);
    }

    /**
     * Showing the full list of user may be slow so defer it
     *
     * @return string
     */
    public function get_details(): string {

        global $CFG, $DB;

        $userfieldsapi = \core_user\fields::for_userpic();
        $userfields = $userfieldsapi->get_sql('u', false, '', '', false)->selects;

        $items = [];
        // Chunked to keep the number of query parameters sane on large sites.
        foreach (array_chunk($this->userids, 1000) as $chunk) {
            [$insql, $params] = $DB->get_in_or_equal($chunk, SQL_PARAMS_NAMED);
            $users = $DB->get_records_sql("SELECT $userfields
                                             FROM {user} u
                                            WHERE u.id $insql", $params);
            foreach ($users as $user) {
                $url = "$CFG->wwwroot/user/view.php?id=$user->id";
                $link = \html_writer::link($url, fullname($user, true) . ' (' . s($user->email) . ')');
                $items[] = \html_writer::tag('li', $link);
            }
        }
        $users = \html_writer::tag('ul', implode('', $items));

        return get_string('check_riskxss_details', 'report_security', $users);
    }
}


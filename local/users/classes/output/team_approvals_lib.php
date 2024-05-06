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
 * Course Allocation block
 *
 * @package    block_courseallocation
 * @copyright  2017 Arun Kumar Mukka
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_users\output;

defined('MOODLE_INTERNAL') || die;
class team_approvals_lib{

    public function get_team_approval_requests($learningtype = 'elearning', $search = false){
        global $DB, $USER, $OUTPUT;

        if(empty($learningtype)){
        	return false;
        }
        if($search){
            $condition = " AND CONCAT(u.firstname, ' ', u.lastname) LIKE '%".$search."%' ";
        } else {
            $condition = "";
        }
        $departmentuserssql = "SELECT lrr.id, lrr.createdbyid, lrr.compname, lrr.componentid, lrr.status, lrr.responder, lrr.respondeddate,
        							u.firstname, u.lastname, u.email, u.idnumber
        							FROM {local_request_records} as lrr
        							JOIN {user} as u ON u.id = lrr.createdbyid
                                    WHERE lrr.compname = '".$learningtype."' AND u.open_supervisorid = $USER->id AND u.id != $USER->id
                                    AND u.confirmed = 1 AND u.suspended = 0 AND u.deleted = 0 AND u.id > 2".$condition." ORDER BY lrr.timemodified DESC";
        
        $departmentusers = $DB->get_records_sql($departmentuserssql);
        return $departmentusers;
	}
}
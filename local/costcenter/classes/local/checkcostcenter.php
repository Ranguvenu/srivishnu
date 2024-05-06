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
 * local costcenter
 *
 * @package    local_costcenter
 * @copyright  2019 eAbyas <eAbyas.in>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
*/

namespace local_costcenter\local;

class checkcostcenter{
        public function costcenter_modules_exist($universityid,$subid,$subormain = false) {
        global $DB;
        $countsql = "SELECT count(u.id) ";
        $fromsql = "FROM {user} AS u
                    JOIN {local_costcenter} AS lc ON lc.id = u.open_costcenterid 
                    WHERE u.open_costcenterid = :id AND u.deleted = :deleted AND u.suspended = :suspended";
        $params = array('deleted' => 0, 'suspended' => 0, 'id' => $universityid);
        if(!$subormain){
            $fromsql .= " AND u.open_departmentid = :departmentid ";
             $params['departmentid'] = $subid;
        }

        $userscount = $DB->count_records_sql($countsql.$fromsql, $params);

        $countsql = "SELECT count(c.id) ";
        $fromsql = "FROM {course} AS c
                    JOIN {local_costcenter} AS lc ON lc.id = c.open_costcenterid 
                    WHERE c.open_costcenterid = :id ";
        $params = array('id' => $universityid);
        if(!$subormain){
            $fromsql .= " AND c.open_departmentid = :departmentid ";
             $params['departmentid'] = $subid;
        }

        $coursescount = $DB->count_records_sql($countsql.$fromsql, $params);

        $countsql = "SELECT count(lp.id) ";
        $fromsql = "FROM {local_program} AS lp
                    JOIN {local_costcenter} AS lc ON lc.id = lp.costcenter 
                    WHERE lp.costcenter = :id ";
        $params = array('deleted' => 0, 'suspended' => 0, 'id' => $universityid);
        if(!$subormain){
            $fromsql .= " AND lp.departmentid = :departmentid ";
             $params['departmentid'] = $subid;
        }

        $programscount = $DB->count_records_sql($countsql.$fromsql, $params);

        return array('userscount'=>$userscount, 'coursescount'=>$coursescount, 'programscount'=>$programscount);
    }
}
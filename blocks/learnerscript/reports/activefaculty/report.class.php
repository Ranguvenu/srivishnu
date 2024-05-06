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
 * LearnerScript
 * A Moodle block for creating customizable reports
 * @package blocks
 * @author: Jahnavi
 * @date: 2023
 */
namespace block_learnerscript\lsreports;
use block_learnerscript\local\reportbase;
use block_learnerscript\report;
use block_learnerscript\local\querylib;
use block_learnerscript\local\ls as ls;
use context_system;
use stdClass;

defined('MOODLE_INTERNAL') || die();
class report_activefaculty extends reportbase implements report {

    public function __construct($report, $reportproperties) {
        global $DB;
        parent::__construct($report, $reportproperties);
        $columns = ['faculty', 'college', 'department', 'noofcourse', 'filesuploaded', 'activitiescreated'];
        $this->columns = ['activefacultycolumns' => $columns];
        $this->components = array('columns', 'conditions', 'ordering', 'filters','permissions', 'plot');
         $this->filters = array('college','department','program','courses','faculty');
        $this->parent = true;
        $this->orderable = array('');
        $this->searchable = array("CONCAT(u.firstname,  ' ', u.lastname)", "lc.fullname");
        $this->defaultcolumn = 'u.id';
        $this->excludedroles = array("'student'");
    }

    public function init() {
        global $DB, $USER;
        
        if (!$this->scheduling && isset($this->basicparams) && !empty($this->basicparams)) {
            $basicparams = array_column($this->basicparams, 'name');
            foreach ($basicparams as $basicparam) {
                if (empty($this->params['filter_' . $basicparam])) {
                    return false;
                }
            }
        } 
        
    }
    public function count() {
        global $DB;
        $this->sql = "SELECT COUNT(DISTINCT u.id)";
    }

    public function select() {
        $this->sql = "SELECT DISTINCT u.id, CONCAT(u.firstname,  ' ', u.lastname) AS faculty, lc.fullname AS college, lco.fullname AS department, COUNT(DISTINCT lsl.courseid) AS noofcourse";
        parent::select();
    }

    public function from() {
        $this->sql .= " FROM {user} u";
    }
    public function joins() {
        $this->sql .= " JOIN {role_assignments} ra ON ra.userid = u.id 
                        JOIN {role} r ON r.id = ra.roleid AND r.shortname = 'faculty' 
                        JOIN {context} ctx ON ctx.id = ra.contextid AND ctx.contextlevel = 50 
                        JOIN {course} c ON c.id = ctx.instanceid 
                        LEFT JOIN {local_cc_semester_courses} lcc ON lcc.courseid = c.id 
                        JOIN {local_costcenter} lc ON lc.id = u.open_costcenterid AND lc.parentid = 0 
                        LEFT JOIN {local_costcenter} lco ON lco.id = u.open_departmentid AND lco.parentid = lc.id AND lco.univ_dept_status = 0
                        LEFT JOIN {local_program} lp ON lp.id = lcc.programid 
                        JOIN {logstore_standard_log} lsl ON lsl.userid = u.id AND lsl.courseid = ctx.instanceid";
        parent::joins();
    }

    public function where() {
        global $USER;
        $this->sql .= " WHERE lsl.action = 'created' AND lsl.target = 'course_module' AND FROM_UNIXTIME(lsl.timecreated, '%Y-%m-%d') >= (curdate() - interval 90 day) 
                AND (c.open_parentcourseid != 0 OR (c.open_parentcourseid = 0 AND c.forpurchaseindividually =2))";

        $systemcontext = context_system::instance();
       if ((!is_siteadmin() && !has_capability('local/costcenter:manage_multiorganizations', $systemcontext)) && has_capability('local/costcenter:manage_ownorganization',$systemcontext) && has_capability('block/learnerscript:managelsreports',$systemcontext)) {
             $this->sql .= " AND u.open_costcenterid = $USER->open_costcenterid AND c.open_costcenterid = $USER->open_costcenterid ";
        } else if(!has_capability('local/costcenter:manage_ownorganization',$systemcontext) && has_capability('local/costcenter:manage_owndepartments',$systemcontext) && has_capability('block/learnerscript:managelsreports',$systemcontext)) {
             $this->sql .= " AND u.open_departmentid = $USER->open_departmentid  AND c.open_departmentid = $USER->open_departmentid ";
        } else if((!is_siteadmin() && !has_capability('local/costcenter:manage_multiorganizations', $systemcontext)) && !has_capability('local/costcenter:manage_ownorganization',$systemcontext) && has_capability('block/learnerscript:managelsreports',$systemcontext) && has_capability('block/learnerscript:manageownreports',$systemcontext)){
             $this->sql .= " AND u.open_costcenterid = $USER->open_costcenterid AND c.open_costcenterid = $USER->open_costcenterid ";

        } else if(!has_capability('local/costcenter:manage_ownorganization',$systemcontext) && !has_capability('local/costcenter:manage_owndepartments',$systemcontext) && !has_capability('block/learnerscript:managelsreports',$systemcontext) && has_capability('block/learnerscript:manageownreports',$systemcontext)) {
             $this->sql .= " AND u.open_departmentid = $USER->open_departmentid  AND c.open_departmentid = $USER->open_departmentid ";
        }

        parent::where();
    }

    public function search() {
        global $DB;
        if (isset($this->search) && $this->search) {
            $statsql = array();
            foreach ($this->searchable as $key => $value) {
                $statsql[] =$DB->sql_like($value, "'%" . $this->search . "%'",$casesensitive = false,$accentsensitive = true, $notlike = false);
            }
            $fields = implode(" OR ", $statsql);          
            $this->sql .= " AND ($fields) ";
        }
    }

    public function filters() {
        if (isset($this->params['filter_college']) && $this->params['filter_college'] > 0) {
            $this->sql .= " AND lc.id = :filter_college";
        }
        if (isset($this->params['filter_department']) && $this->params['filter_department'] > 0) {
            $this->sql .= " AND lco.id = :filter_department";
        }
        if (isset($this->params['filter_program']) && $this->params['filter_program'] > 0) {
            $this->sql .= " AND lp.id = :filter_program";
        }
        if (isset($this->params['filter_courses']) && $this->params['filter_courses'] > 0) {
            $this->sql .= " AND c.id = :filter_courses";
        }
        if (isset($this->params['filter_faculty']) && $this->params['filter_faculty'] > 0) {
            $this->sql .= " AND u.id = :filter_faculty";
        }
    }


    public function groupby() {
        $this->sql .= " GROUP BY u.id";

    }

    public function get_rows($elements) {
        return $elements;
    }

    public function column_queries($columnname, $userid, $users = null) { 
        global $DB;
        $where = " AND %placeholder% = $userid";
        if (isset($this->params['filter_program']) && $this->params['filter_program'] > 0) {
            $where .= " AND lpp.id = ".$this->params['filter_program'];
        }
        if (isset($this->params['filter_courses']) && $this->params['filter_courses'] > 0) {
            $where .= " AND c.id = ".$this->params['filter_courses'];
        }
        switch ($columnname) {
            case 'filesuploaded':
                $identy = 'lsl.userid';
                $query = " SELECT COUNT(DISTINCT lsl.id)
                            FROM {logstore_standard_log} lsl 
                            JOIN {course} c ON c.id = lsl.courseid
                            LEFT JOIN {local_cc_semester_courses} lcs ON lcs.courseid = c.id
                            LEFT JOIN {local_program} lpp ON lpp.id = lcs.programid
                            JOIN {course_modules} cm ON cm.id = lsl.contextinstanceid 
                            JOIN {modules} m ON m.id = cm.module AND m.name = 'resource'
                            WHERE lsl.action = 'created' AND lsl.target = 'course_module' $where AND (c.open_parentcourseid != 0 OR (c.open_parentcourseid = 0 AND c.forpurchaseindividually =2))  AND FROM_UNIXTIME(lsl.timecreated, '%Y-%m-%d') >= (curdate() - interval 90 day)";
            break;
            case 'activitiescreated':
                $identy = 'lsl.userid';
                $query = " SELECT COUNT(DISTINCT lsl.id)
                            FROM {logstore_standard_log} lsl 
                            JOIN {course} c ON c.id = lsl.courseid
                            LEFT JOIN {local_cc_semester_courses} lcs ON lcs.courseid = c.id
                            LEFT JOIN {local_program} lpp ON lpp.id = lcs.programid
                            JOIN {grade_items} gi ON gi.courseid = c.id AND gi.itemtype = 'mod' AND gi.id = lsl.objectid
                            WHERE lsl.action = 'created' AND lsl.target = 'grade_item' $where AND (c.open_parentcourseid != 0 OR (c.open_parentcourseid = 0 AND c.forpurchaseindividually =2)) AND FROM_UNIXTIME(lsl.timecreated, '%Y-%m-%d') >= (curdate() - interval 90 day) ";
            break;
            default:
            return false;
                break;
        }
        $query = str_replace('%placeholder%', $identy, $query);
        return $query;
    }
}

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
class report_coursefiles extends reportbase implements report {

    public function __construct($report, $reportproperties) {
        global $DB;
        parent::__construct($report, $reportproperties);
        $columns = ['course', 'college', 'department', 'program', 'faculty', 'noofresources'];
        $this->columns = ['coursefilecolumns' => $columns];
        $this->components = array('columns', 'conditions', 'ordering', 'filters','permissions', 'plot');
        $this->filters = array('college','department','program','courses','faculty');
        $this->parent = true;
        $this->orderable = array('');
        $this->searchable = array();
        $this->defaultcolumn = "ra.id";
        $this->searchable = array("c.fullname", "CONCAT(u.firstname,  ' ', u.lastname)", "lc.fullname");
        $this->excludedroles = array("'student'");
    }

    public function init() {
        global $DB, $USER;
        $systemcontext = context_system::instance();
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
        $this->sql = "SELECT COUNT(DISTINCT ra.id) ";
    }

    public function select() {
        global $DB;
        $this->sql = "SELECT DISTINCT ra.id, c.fullname AS course, lc.fullname AS college, lco.fullname AS department, lp.fullname AS program, CONCAT(u.firstname, ' ', u.lastname) AS faculty, COUNT(DISTINCT lsl.id) AS noofresources";
        parent::select();
    }

    public function from() {
        $this->sql .= " FROM {course} c ";
    }
    public function joins() {
        $this->sql .= " JOIN {context} ctx ON c.id = ctx.instanceid AND ctx.contextlevel = 50
                        JOIN {role_assignments} ra ON ctx.id = ra.contextid 
                        JOIN {user} u ON u.id = ra.userid 
                        JOIN {role} r ON r.id = ra.roleid AND r.shortname = 'faculty' 
                        LEFT JOIN {local_cc_semester_courses} lcc ON lcc.courseid = c.id 
                        JOIN {local_costcenter} lc ON lc.id = c.open_costcenterid AND lc.parentid = 0
                        LEFT JOIN {local_costcenter} lco ON lco.parentid = lc.id AND lco.id = c.open_departmentid AND lco.univ_dept_status = 0 
                        LEFT JOIN {local_program} lp ON lp.id = lcc.programid 
                        JOIN {logstore_standard_log} lsl ON lsl.courseid = c.id AND lsl.userid = u.id AND lsl.target = 'course_module' AND lsl.action = 'created' 
                        JOIN {course_modules} cm ON cm.id = lsl.contextinstanceid 
                        JOIN {modules} m ON m.id = cm.module AND m.name = 'resource' ";
        parent::joins();
    }

    public function where() { 
        global $USER;
        $this->sql .= " WHERE c.visible = 1 AND FROM_UNIXTIME(lsl.timecreated, '%Y-%m-%d') >= (curdate() - interval 90 day) AND (c.open_parentcourseid != 0 OR (c.open_parentcourseid = 0 AND c.forpurchaseindividually = 2))";

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
        $this->sql .= " GROUP BY ra.id";
    }

    public function get_rows($elements) {
        return $elements;
    }

}

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

/** LearnerScript Reports
 * A Moodle block for creating customizable reports
 * @package blocks
 * @subpackage learnerscript
 * @author: sudharani<sudharani.sadula@moodle.com>
 * @date: 2023
 */
namespace block_learnerscript\lsreports;
use block_learnerscript\local\querylib;
use block_learnerscript\local\reportbase;
use block_learnerscript\local\ls as ls;
use block_learnerscript\report;
use stdClass;
use context_system;

defined('MOODLE_INTERNAL') || die();
class report_coursewithgradable extends reportbase implements report {
    /**
     * [__construct description]
     * @param object $report Report object
     * @param object $reportproperties Report properties object
     */
    public function __construct($report, $reportproperties) {
        global $USER;
        parent::__construct($report, $reportproperties);
        $this->components = array('columns', 'filters', 'permissions', 'calcs', 'plot');
        $columns = ['course', 'college', 'department', 'program', 'faculty', 'numofactivities'];
        $this->columns = ['coursewithgradable' => $columns];
        $this->courselevel = true;
         $this->filters = array('college','department','program','courses','faculty');
        $this->parent = true;
        $this->orderable = array('course', 'college', 'department', 'program', 'faculty');
        $this->searchable = array("c.fullname","concat(u.firstname,' ',u.lastname)","lco.fullname","lc.fullname","lp.fullname");
        $this->defaultcolumn = "concat(c.id,'-',u.id)";
        $this->excludedroles = array("'student'");
    }
    function init() {
        global $DB, $USER;
        
    }
    function count() {
        global $DB;
        $this->sql = "SELECT COUNT(DISTINCT concat(c.id,'-',u.id))";


    }

    function select() {
          $this->sql = "SELECT DISTINCT concat(c.id,'-',u.id), c.fullname AS course, lc.fullname AS college, lco.fullname AS department, lp.fullname AS program, concat(u.firstname,' ',u.lastname) AS faculty, COUNT(DISTINCT lsl.id) AS numofactivities";
        parent::select();
    }

    function from() {

        $this->sql .= " FROM {user} u";
    }

    function joins() {

        $this->sql .= " JOIN {role_assignments} ra ON ra.userid = u.id 
                        JOIN {role} r ON r.id = ra.roleid AND r.shortname = 'faculty' 
                        JOIN {context} ctx ON ctx.id = ra.contextid AND ctx.contextlevel = 50 
                        JOIN {course} c ON c.id = ctx.instanceid 
                        LEFT JOIN {local_cc_semester_courses} lcc ON lcc.courseid = c.id 
                        LEFT JOIN {local_costcenter} lc ON lc.id = c.open_costcenterid AND lc.parentid = 0 
                        LEFT JOIN {local_costcenter} lco ON lco.parentid = lc.id AND lco.id = c.open_departmentid AND lco.univ_dept_status = 0
                        LEFT JOIN {local_program} lp ON lp.id = lcc.programid 
                        JOIN {logstore_standard_log} lsl ON lsl.userid = u.id AND lsl.courseid = ctx.instanceid AND lsl.action = 'created' AND lsl.target = 'grade_item'
                        JOIN {grade_items} gi ON gi.courseid = c.id AND gi.itemtype = 'mod' AND gi.id = lsl.objectid ";
        parent::joins();
    }

    function where() {
        global $DB, $USER;
        $systemcontext = context_system::instance();
        $this->sql .= " WHERE 1=1 AND c.visible =1 AND FROM_UNIXTIME(lsl.timecreated, '%Y-%m-%d') > (curdate() - interval 90 day) AND (c.open_parentcourseid != 0 OR (c.open_parentcourseid = 0 AND c.forpurchaseindividually = 2)) ";

        if ($this->ls_startdate > 0 && $this->ls_enddate) {
            $this->params['ls_fstartdate'] = ROUND($this->ls_startdate);
            $this->params['ls_fenddate'] = ROUND($this->ls_enddate);
            $this->sql .= " AND lsl.timecreated BETWEEN :ls_fstartdate AND :ls_fenddate ";
        }
        
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

    function search() {
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

    function filters() { 
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

    function groupby() {
        $this->sql .= " GROUP BY concat(c.id,'-',u.id)";
    }

    /**
     * @param  array $activites Activites
     * @return array $reportarray Activities information
     */
    public function get_rows($elements) {
        return $elements;
    }
}

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
 * LearnerScript Report Dashboard Header
 *
 * @package    block_reportdashboard
 * @copyright  2017 eAbyas Info Solutions
 * @license    http://www.gnu.org/copyleft/gpl.reportdashboard GNU GPL v3 or later
 */
namespace block_reportdashboard\output;

defined('MOODLE_INTERNAL') || die();

use renderable;
use renderer_base;
use templatable;
use stdClass;
use context_system;
use block_learnerscript\local\ls as ls;
use block_reportdashboard\local\reportdashboard as reportdashboard;
use block_learnerscript\local\querylib as querylib;

class dashboardheader implements renderable, templatable {
    public $editingon;
    public function __construct($data) {
        $this->editingon = $data->editingon;
        $this->configuredinstances = $data->configuredinstances;
        isset($data->getdashboardname) ? $this->getdashboardname = $data->getdashboardname : null;
        $this->dashboardurl = $data->dashboardurl;
    }

    /**
     * Export this data so it can be used as the context for a mustache template.
     *
     * @return stdClass
     */
    public function export_for_template(renderer_base $output) {
        global $DB, $PAGE, $USER;
        $systemcontext = context_system::instance();
        $data = array();
        $switchableroles = (new ls)->switchrole_options();
        $data['editingon'] = $this->editingon;
        $data['issiteadmin'] = is_siteadmin();
        if ($_SESSION['role'] == 'manager' && $_SESSION['ls_contextlevel'] == CONTEXT_SYSTEM) {
            $data['managerrole'] = 'manager';
        }

        $dashboardcollegeid = 0;
        if (is_siteadmin($USER->id) || (new ls)->is_manager($USER->id, $_SESSION['ls_contextlevel'], $_SESSION['role'])) {
            $dashboardcollege = $DB->get_records_sql_menu("SELECT id, fullname FROM {local_costcenter} WHERE parentid = 0 ORDER BY id ASC");
            if (!empty($dashboardcollege)) {
                $dashboardcollege = array(0 => 'Select College') + $dashboardcollege;
                foreach ($dashboardcollege as $key => $value) {
                    $collegedata = new stdClass();
                    $collegedata = ['id' => $key, 'fullname' => $value];
                    $collegelist[] = $collegedata;
                }      
                $dashboardcollegeid = array_keys($collegelist)[0];          
                $data['collegelist'] = $collegelist;
                $data['dashboardcollege'] = 1;
            } else {
                $data['dashboardcollege'] = 0;
            }
        } else {
            $dashboardcollege = (new querylib)->get_rolecolleges($USER->id, $role, 0, '', ' LIMIT 0, 1', false, false, '', true);
            if (!empty($dashboardcollege)) {
                foreach ($dashboardcollege as $key => $value) {
                    $collegedata = new stdClass();
                    $collegedata = ['id' => $key, 'fullname' => $value];
                    $collegelist[] = $collegedata;
                }      
                $dashboardcollegeid = $collegelist[0];
                $data['collegelist'] = $collegelist;
                $data['dashboardcollege'] = 1;
            } else {
                $data['dashboardcollege'] = 0;
            }
        }
        $dashboarddepartmentid = 0;
        if (is_siteadmin($USER->id) || (new ls)->is_manager($USER->id, $_SESSION['ls_contextlevel'], $_SESSION['role'])) {
            $dashboarddepartment = $DB->get_records_sql("SELECT id, fullname FROM {local_costcenter} WHERE id <> 0 AND parentid = $dashboardcollegeid AND univ_dept_status = 0 ORDER BY id ASC");
            if (!empty($dashboardcollegeid)) {
                    $dashboarddepartment = array(0 => 'Select Department') + $dashboarddepartment;
                  
                foreach ($dashboarddepartment as $key => $value) {
                    $deptdata = new stdClass();
                    $deptdata = ['id' => $key, 'fullname' => $value];
                    $deptlist[] = $deptdata;
                }      
                $dashboarddepartmentid = array_keys($deptlist)[0];
                $data['departmentlist'] = $deptlist;
                $data['dashboarddept'] = 1;
                $data['singledept'] = 0;
            } else {
                $dashboarddepartment = array(0 => 'Select Department');
                foreach ($dashboarddepartment as $key => $value) {
                    $deptdata = new stdClass();
                    $deptdata = ['id' => $key, 'fullname' => $value];
                    $dasboarddeptlist[] = $deptdata;
                }  
                $data['departmentlist'] = $dasboarddeptlist;
                $data['dashboarddept'] = 1;
            }    
        } else {
            $dashboarddepartment = (new querylib)->get_roledepartments($USER->id, $_SESSION['role'], $dashboardcollegeid['id'], 0, '', ' LIMIT 0, 1', false, false, '', true);
            if (!empty($dashboarddepartment)) {
                if ((!is_siteadmin() && !has_capability('local/costcenter:manage_multiorganizations', $systemcontext)) && has_capability('local/costcenter:manage_ownorganization',$systemcontext) && has_capability('block/learnerscript:managelsreports',$systemcontext)) {
                    $data['singledept'] = 0;
                    $dashboarddepartment = array(0 => 'Select Department') + $dashboarddepartment;
                } else if ((!is_siteadmin() && !has_capability('local/costcenter:manage_multiorganizations', $systemcontext)) && !has_capability('local/costcenter:manage_ownorganization',$systemcontext) && has_capability('block/learnerscript:managelsreports',$systemcontext) && has_capability('block/learnerscript:manageownreports',$systemcontext) && !has_capability('local/costcenter:manage_owndepartments',$systemcontext)) {
                    $data['singledept'] = 0;
                    $dashboarddepartment = array(0 => 'Select Department') + $dashboarddepartment;
                } else {
                    $data['singledept'] = 1;
                    $dashboarddepartment = $dashboarddepartment;
                }
                foreach ($dashboarddepartment as $key => $value) {
                    $deptdata = new stdClass();
                    $deptdata = ['id' => $key, 'fullname' => $value];
                    $deptlist[] = $deptdata;
                }      
                $dashboarddepartmentid = array_keys($deptlist)[0];
                $data['departmentlist'] = $deptlist;
                $data['dashboarddept'] = 1;
            } else {
                $data['dashboarddept'] = 0;
            }
        }
        $data['courselist'] = array();
        if($this->dashboardurl == 'Course'){
            if (is_siteadmin() || (new ls)->is_manager($USER->id, $_SESSION['ls_contextlevel'], $_SESSION['role'])) {
                $dashboardcourse = $DB->get_records_select('course','id <> :id',array('id' => SITEID), '','id,fullname', 0, 1);
            } else {
                $dashboardcourse = (new querylib)->get_rolecourses($USER->id, $_SESSION['role'], $_SESSION['ls_contextlevel'], SITEID, '', 'LIMIT 1');
            }
            if (!empty($dashboardcourse)) {
                $data['courselist'] = array_values($dashboardcourse);
                $data['coursedashboard'] = 1;
            }
        } else {
            $data['coursedashboard'] = 0;
        }
        $data['dashboardurl'] = $this->dashboardurl;
        $data['configuredinstances'] = $this->configuredinstances;
        $dashboardlist = array();
        $dashboardlist = $this->get_dashboard_reportscount();
        $data['sesskey'] = sesskey();
        if (count($dashboardlist)) {
            $data['get_dashboardname'] = $dashboardlist;
        }

        $data['reporttilestatus'] = $PAGE->blocks->is_known_block_type('reporttiles', false);
        $data['reportdashboardstatus'] = $PAGE->blocks->is_known_block_type('reportdashboard', false);
        $data['reportwidgetstatus'] = ($data['reporttilestatus'] || $data['reportdashboardstatus']) ? true : false;
        $data['role'] = $_SESSION['role'];
        $data['contextlevel'] = $_SESSION['ls_contextlevel'];
        if($this->dashboardurl != 'VishnuLearning'){
            $data['dashboardcollege'] = 0;
            $data['dashboarddept'] = 0;
        }
        return array_merge($data, $switchableroles);
    }

    public function get_dashboard_reportscount() {
        global $DB;
        $role = $_SESSION['role'];
        if (!empty($role) && !is_siteadmin()) {
            $getreports = $DB->get_records_sql("SELECT DISTINCT(subpagepattern) FROM {block_instances}
            	            WHERE pagetypepattern LIKE '%blocks-reportdashboard-dashboard-$role%' ");
        } else {
            $getreports = $DB->get_records_sql("SELECT DISTINCT(subpagepattern) FROM {block_instances}
            	           WHERE pagetypepattern LIKE '%blocks-reportdashboard-dashboard%' ");
        }
        $dashboardname = array();
        $pagetypepatternarray = array();
        $i = 0;
        $rolelist = $DB->get_records_sql_menu("SELECT id, shortname FROM {role} ");
        if (!empty($getreports)) {
            foreach ($getreports as $getreport) {
                $dashboardname[$getreport->subpagepattern] = $getreport->subpagepattern;
            }
        } else {
            $dashboardname['Dashboard'] = 'Dashboard';
        }
        $getdashboardname = array();
        foreach ($dashboardname as $key => $value) {
            if ($value != 'Dashboard' && !(new reportdashboard)->is_dashboardempty($key)) {
                continue;
            }
            $getreports = $DB->count_records_sql("SELECT COUNT(id) FROM {block_instances} WHERE subpagepattern LIKE '%$key%' ");
            $getdashboardname[$i]['name'] = ucfirst($value);
            $getdashboardname[$i]['pagetypepattern'] = $value;
            // $getdashboardname[$i]['counts'] = $getreports;
            $getdashboardname[$i]['random'] = $i;
            if ($value == 'Dashboard' || $value == 'Course') {
                $getdashboardname[$i]['default'] = 0;
            } else {
                $getdashboardname[$i]['default'] = 1;
            }
            $i++;
        }
        return $getdashboardname;
    }
}

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

/** LearnerScript
 * A Moodle block for creating customizable reports
 * @package blocks
 * @subpackage learnerscript
 * @author: Arun Kumar M <arun@eabyas.in>
 * @date: 2017
 */
namespace block_learnerscript\local;
use context_system; 

require_once "$CFG->dirroot/enrol/locallib.php";
class querylib {
	/**
	 * List of Enrolled Courses for a Particular RoleWise User
	 * @param  integer  $userid      User ID for Particular user
	 * @param  string  $role         Role ShortName
	 * @param  integer $courseid     Course ID for exception particular course
	 * @param  string  $searchconcat Search Value
	 * @param  string  $concatsql    Sql Conditions
	 * @param  string  $limitconcat  Limit 0, 10 like....
	 * @param  boolean $count        Count for get results count or list of records
	 * @param  boolean $check        Check that user has role in LMS
	 * @return integer|Object              If $count true, returns courses count or returns Enrolled Cousrse as per role for that user
	 */
	public function get_rolecourses($userid, $role, $contextlevel, $courseid = SITEID, $concatsql = '', $limitconcat = '', $count = false, $check = false, $datefiltersql = '', $menu = false) {
		GLOBAL $DB;
		$params = array('courseid' => $courseid);
		$params['contextlevel'] = isset($_SESSION['ls_contextlevel']) ? $_SESSION['ls_contextlevel'] : $contextlevel;
		$params['userid'] = $userid;
		$params['userid1'] = $params['userid'];
		$params['userids'] = $userid;
		$params['role'] = $role;
		$params['active'] = ENROL_USER_ACTIVE;
		$params['enabled'] = ENROL_INSTANCE_ENABLED;
		$params['now1'] = round(time(), -2); // improves db caching
		$params['now2'] = $params['now1'];
		if ($count) {
			$coursessql = "SELECT COUNT(c.id) AS totalcount FROM {course} AS c";
		} else {
			$coursessql = "SELECT DISTINCT c.id, c.fullname, c.timecreated AS timecreated FROM {course} AS c";
		}
		$enroljoin = " JOIN (SELECT DISTINCT e.courseid
                               FROM {enrol} AS e
                               JOIN {user_enrolments} AS ue ON (ue.enrolid = e.id AND ue.userid = :userid1)
                               WHERE ue.status = :active AND e.status = :enabled AND ue.timestart < :now1 AND
                                (ue.timeend = 0 OR ue.timeend > :now2)) en ON (en.courseid = c.id)";
        if($_SESSION['ls_contextlevel'] == CONTEXT_SYSTEM || $contextlevel == CONTEXT_SYSTEM){
            $coursessql .= " $enroljoin LEFT JOIN {context} AS ctx ON ctx.instanceid = 0 AND ctx.contextlevel = :contextlevel";
         } else if($_SESSION['ls_contextlevel'] == CONTEXT_COURSECAT || $contextlevel == CONTEXT_COURSECAT){
            $coursessql .=" $enroljoin JOIN {course_categories} as cc ON cc.id = c.category
                LEFT JOIN {context} AS ctx ON ctx.instanceid = cc.id AND ctx.contextlevel = :contextlevel";
        } else {
         $coursessql .= " $enroljoin LEFT JOIN {context} AS ctx ON (ctx.instanceid = c.id AND ctx.contextlevel = :contextlevel)";
        }
        $coursessql .=" JOIN {role_assignments} AS ra ON ra.contextid = ctx.id
                 JOIN {role} AS r ON r.id = ra.roleid
                  WHERE c.id <> :courseid AND c.visible = 1 AND ra.userid = :userid AND r.shortname = :role
                       $concatsql ORDER BY c.id ASC $limitconcat";
		try {
			if ($count) {
				$courses = $DB->count_records_sql($coursessql, $params);
			} else {
				if ($menu) {
					$courses = $DB->get_records_sql_menu($coursessql, $params);
				} else {
					$courses = $DB->get_records_sql($coursessql, $params);
				}
			}
		} catch (dml_exception $ex) {
			print_error("Sql Query Wrong!");
		}
		if ($check) {
			return !empty($courses) ? true : false;
		}
		return $courses;
	}

	public function filter_get_courses($pluginclass, $selectoption = true, $search = false, $filterdata = false, $type = false, $userid = null, $filtercourses, $coursecollegeid = false, $coursedepartmentid = false,$courseprogramid = false) {
		global $DB, $USER;
		$limitnum = 1;
		$searchvalue = '';
		$concatsql = "";
		$concat = "";
		$searchsql = "";
		$courseoptions = array();
		 $systemcontext = context_system::instance();
		if ($search) {
            $searchvalue .= $search; 
            $searchsql = " AND fullname LIKE '%$search%' ";
            $searchsql1 = " AND c.fullname LIKE '%$search%' ";
            $limitnum = 0;
        }
		if($selectoption){
			$courseoptions[0] = isset($pluginclass->singleselection) && $pluginclass->singleselection ? get_string('filter_course', 'block_learnerscript') :
								array('id' => 0, 'text' => get_string('select') . ' ' . get_string('course'));
		}
        if (!empty($filterdata) && !empty($filterdata['filter_users']) && $filterdata['filter_users_type'] == 'basic' && $filterdata['filter_courses_type'] == 'custom') {
            $userid = $filterdata['filter_users'];
        }
        if (!empty($filterdata) && !empty($filterdata['filter_coursecategories'])) {
            $concatsql .= " AND category = " . $filterdata['filter_coursecategories'];
        }
        if (!empty($filterdata) && !empty($filterdata['filter_college'])) {
            $concatsql .= " AND open_costcenterid = " . $filterdata['filter_college'];
            $concat .= " AND c.open_costcenterid = " . $filterdata['filter_college'];
        }
        if (!empty($filterdata) && !empty($filterdata['filter_department'])) {
            $concatsql .= " AND open_departmentid = " . $filterdata['filter_department'];
            $concat .= " AND c.open_departmentid = " . $filterdata['filter_department'];
        }
        if (!empty($filterdata) && !empty($filterdata['filter_program'])) {
    
            $concat .= " AND lp.id = " . $filterdata['filter_program'];
        }
        if (!empty($coursecollegeid) && $coursecollegeid > 0) {
            $concatsql .= " AND open_costcenterid = " . $coursecollegeid;
            $concat .= " AND c.open_costcenterid = " . $coursecollegeid;
        }
        if (!empty($coursedepartmentid) && $coursedepartmentid > 0) {
            $concatsql .= " AND open_departmentid = " . $coursedepartmentid;
            $concat .= " AND c.open_departmentid = " . $coursedepartmentid;
        }
        if (!empty($courseprogramid) && $courseprogramid > 0) {
            $concat .= " AND lp.id = " . $courseprogramid;
        }
        if (!empty($filterdata) && !empty($filterdata['filter_courses']) && ((isset($filterdata['filter_users_type']) && $filterdata['filter_users_type'] != 'basic' && $filterdata['filter_courses_type'] != 'basic') || !$type)) {
            $concatsql .= " AND id = " . $filterdata['filter_courses'];
        }
        if (!empty($filtercourses && !$search)) {
        	$concatsql .= " AND id = " . $filtercourses;
        }
        if (!isset($pluginclass->reportclass->userid)) {
        	$pluginclass->reportclass->userid = $USER->id;
        }
        $rolewisesql = "SELECT DISTINCT c.id,c.fullname 
						FROM {user} u
						JOIN {role_assignments} ra ON ra.userid = u.id 
						JOIN {role} r ON r.id = ra.roleid AND r.shortname = 'faculty' 
						JOIN {context} ctx ON ctx.id = ra.contextid AND ctx.contextlevel = 50 
						JOIN {course} c ON c.id = ctx.instanceid 
						LEFT JOIN {local_cc_semester_courses} lcc ON lcc.courseid = c.id 
						LEFT JOIN {local_program} lp ON lp.id = lcc.programid
						WHERE 1=1 AND c.visible =1 AND (c.open_parentcourseid != 0 OR (c.open_parentcourseid = 0 AND c.forpurchaseindividually = 2)) AND c.fullname LIKE '%".$searchvalue."%' $concat";
		if(is_siteadmin($pluginclass->reportclass->userid) || (new ls)->is_manager($pluginclass->reportclass->userid, $pluginclass->reportclass->contextlevel, $pluginclass->reportclass->role)) { 
			if ($userid > 0) {
				$courselist = array_keys(enrol_get_users_courses($userid));
				if(!empty($courselist)) {
					if(!empty($pluginclass->reportclass->rolewisecourses)) {
						$rolecourses = explode(',', $pluginclass->reportclass->rolewisecourses);
						$courselist = array_intersect($courselist, $rolecourses);
					}
					$courseids = implode(',', $courselist);
					$courses = $DB->get_records_select('course', "id > :siteid AND visible=:visible AND fullname LIKE '%$searchvalue%' AND id IN ($courseids)" . $concatsql, ['siteid' => SITEID, 'visible' => 1], '', 'id, fullname', 0, $limitnum);
				} else {
					$courses = array();
				}
			} else {
				$courses = $DB->get_records_sql($rolewisesql);
			}
			
		} else if ((!is_siteadmin() && !has_capability('local/costcenter:manage_multiorganizations', $systemcontext)) && has_capability('local/costcenter:manage_ownorganization',$systemcontext) && has_capability('block/learnerscript:managelsreports',$systemcontext)) {
             $rolewisesql .= " AND u.open_costcenterid = $USER->open_costcenterid AND c.open_costcenterid = $USER->open_costcenterid ";

             $courses = $DB->get_records_sql($rolewisesql);
        } else if((!is_siteadmin() &&!has_capability('local/costcenter:manage_ownorganization',$systemcontext)) && has_capability('local/costcenter:manage_owndepartments',$systemcontext) && has_capability('block/learnerscript:managelsreports',$systemcontext)) {
             $rolewisesql .= " AND u.open_departmentid = $USER->open_departmentid  AND c.open_departmentid = $USER->open_departmentid ";
             $courses = $DB->get_records_sql($rolewisesql);
        } else if((!is_siteadmin() && !has_capability('local/costcenter:manage_multiorganizations', $systemcontext)) && !has_capability('local/costcenter:manage_ownorganization',$systemcontext) && has_capability('block/learnerscript:managelsreports',$systemcontext) && has_capability('block/learnerscript:manageownreports',$systemcontext)){
             $rolewisesql .= " AND u.open_costcenterid = $USER->open_costcenterid AND c.open_costcenterid = $USER->open_costcenterid ";
             $courses = $DB->get_records_sql($rolewisesql);

        } else if((!is_siteadmin() && !has_capability('local/costcenter:manage_ownorganization',$systemcontext)) && !has_capability('local/costcenter:manage_owndepartments',$systemcontext) && !has_capability('block/learnerscript:managelsreports',$systemcontext) && has_capability('block/learnerscript:manageownreports',$systemcontext)) {
             $rolewisesql .= " AND u.open_departmentid = $USER->open_departmentid  AND c.open_departmentid = $USER->open_departmentid ";
             $courses = $DB->get_records_sql($rolewisesql);
        } else{
			if(empty($pluginclass->reportclass->rolewisecourses)){
			  //$courses = [];
              $courses = $this->get_rolecourses($USER->id, $_SESSION['role'], $_SESSION['ls_contextlevel'], SITEID, '', '');

			}else{
				$rolewisecourses = explode(',', $pluginclass->reportclass->rolewisecourses);
				list($usql, $params) = $DB->get_in_or_equal($rolewisecourses);
				$usql .= " AND visible=1 $searchsql $concatsql";
				$courses = $DB->get_records_select('course', "id $usql", $params);
	        }
		}
		foreach ($courses as $c) {
			if ($c->id == SITEID) {
				continue;
			}
			if ($search) {
                $courseoptions[] = array('id' => $c->id, 'text' => format_string($c->fullname));
            } else {
                $courseoptions[$c->id] = format_string($c->fullname);
            }
		}
		return $courseoptions;
	}

	public function filter_get_users($pluginclass, $selectoption = true, $search = false, $filterdata = false, $type = false, $filterusers='', $courses = null) {
        global $DB, $USER;
        $searchsql = "";
        $concatsql = "";
        $concatsql1 = ""; 
        $limitnum = 1;
        if ($search) {
            $searchsql = " AND CONCAT(u.firstname, ' ', u.lastname) LIKE '%$search%' "; 
            $concatsql .= $searchsql;
            $limitnum = 0;
        }
		if ($pluginclass->report->type != 'sql') {
			$pluginclass->report->components = isset($pluginclass->report->components) ? $pluginclass->report->components : '';
			$components = (new \block_learnerscript\local\ls)->cr_unserialize($pluginclass->report->components);
			if (!empty($components['conditions']['elements'])) {
				$conditions = $components['conditions'];
				$reportclassname = 'block_learnerscript\lsreports\report_' . $pluginclass->report->type;
				$properties = new \stdClass();
				$reportclass = new $reportclassname($pluginclass->report, $properties);
				$userslist = $reportclass->elements_by_conditions($conditions);
			} else {
                $role = isset($_SESSION['role']) ? $_SESSION['role'] : '';
                if (!empty($filterdata) && !empty($filterdata['filter_users']) && ((isset($filterdata['filter_courses_type']) && $filterdata['filter_courses_type'] != 'basic' && $filterdata['filter_users_type'] != 'basic') || !$type)) {
                    $userid = $filterdata['filter_users'];
                    $concatsql .= " AND u.id = $userid";
                }
                if (!empty($filterdata) && !empty($filterdata['filter_courses']) && $filterdata['filter_courses_type'] == 'basic' && $filterdata['filter_users_type'] == 'custom') {
                    $courseid = $filterdata['filter_courses'];
                    $role = 'student';
                    $concatsql1 .= " AND c.id IN ($courseid) ";
                }
                if (!empty($filterusers) && !$search) {
                	$concatsql .= " AND u.id = $filterusers";
                }
                if(empty($pluginclass->reportclass)) {
                	$pluginclass->reportclass = new \stdClass;
                	$pluginclass->reportclass->userid = $USER->id;
                }
				if(is_siteadmin($pluginclass->reportclass->userid) || (new ls)->is_manager($pluginclass->reportclass->userid, $pluginclass->reportclass->contextlevel, $pluginclass->reportclass->role)) {
					$sql = "SELECT DISTINCT u.*
				 			  FROM {course} AS c
		                      JOIN {enrol} AS e ON c.id = e.courseid
		                      JOIN {user_enrolments} AS ue ON ue.enrolid = e.id
		                      JOIN {role_assignments} AS ra ON ra.userid = ue.userid
		                      JOIN {role} as r ON r.id = ra.roleid AND r.shortname = 'student'
		                      JOIN {context} ctx ON ctx.instanceid = c.id 
		                      JOIN {user} AS u ON u.id = ue.userid AND u.deleted = 0 
		                      WHERE 1 = 1 $concatsql $concatsql1" ;
		       
					$userslist = $DB->get_records_sql($sql, array(), 0, $limitnum);
				}else{
					if(empty($pluginclass->reportclass->rolewisecourses)){
						if($_SESSION['role'] == 'coursecreator' && ($_SESSION['ls_contextlevel'] == CONTEXT_SYSTEM || $_SESSION['ls_contextlevel'] == CONTEXT_COURSECAT)){	
								$courses = $this->get_rolecourses($USER->id, $_SESSION['role'], $_SESSION['ls_contextlevel'], SITEID, '', '');
								$courselists = array();
								foreach ($courses as $key => $course) {
									$courselists[] =  $course->id;
								}
								$courselist = join(',',$courselists);
								if(!empty($courselist)){
									$sql = "SELECT DISTINCT u.*
								 			  FROM {course} AS c
						                      JOIN {enrol} AS e ON c.id = e.courseid
						                      JOIN {user_enrolments} AS ue ON ue.enrolid = e.id
						                      JOIN {role_assignments} AS ra ON ra.userid = ue.userid
						                      JOIN {role} AS r ON r.id = ra.roleid AND r.shortname = 'student'
						                      JOIN {context} ctx ON ctx.instanceid = c.id 
						                      JOIN {user} AS u ON u.id = ue.userid
						                      WHERE c.id in($courselist) AND u.deleted = 0 AND ra.contextid = ctx.id  $concatsql $concatsql1";
						            $userslist = $DB->get_records_sql($sql, array(), 0, $limitnum);
					               }else{
					               	 $userlist = [];
					               }
					            	
				            } else {
							        $userlist = [];
				            }
					}else{
						 $courselist = $pluginclass->reportclass->rolewisecourses;
						 $sql = "SELECT DISTINCT u.*
					 			  FROM {course} AS c
			                      JOIN {enrol} AS e ON c.id = e.courseid
			                      JOIN {user_enrolments} AS ue ON ue.enrolid = e.id
			                      JOIN {role_assignments} AS ra ON ra.userid = ue.userid
			                      JOIN {role} as r ON r.id = ra.roleid AND r.shortname = 'student'
			                      JOIN {context} ctx ON ctx.instanceid = c.id 
			                      JOIN {user} AS u ON u.id = ue.userid
			                      WHERE c.id in($courselist) AND u.deleted = 0 AND ra.contextid = ctx.id AND ctx.contextlevel = 50 $concatsql $concatsql1";
				        $userslist = $DB->get_records_sql($sql, array(), 0, $limitnum);
			        }
				}

			}
		} else {
			$sql = " SELECT * FROM {user} as u WHERE id > 2 AND u.deleted = 0 $concatsql" ;
				$userslist = $DB->get_records_sql($sql);
		}

		$usersoptions = array();
		if($selectoption){
			$usersoptions[0] = isset($pluginclass->singleselection) && $pluginclass->singleselection ? get_string('filter_user', 'block_learnerscript') :
								array('id' => 0, 'text' => get_string('select') . ' ' . get_string('users'));
		}
		if (!empty($userslist)) {
			foreach ($userslist as $c) {
				if (isset($c->id)) {
					if ($search) {
	                    $usersoptions[] = array('id' => $c->id, 'text' => format_string(fullname($c)));
	                } else {
	                    $usersoptions[$c->id] = fullname($c);
	                }
	            }
			}
		}
        return $usersoptions;
	}
	public function get_learners($useroperatorsql = '', $courseoperatorsql = ''){

		if(empty($courseoperatorsql) && empty($useroperatorsql)){
			return false;
		}
		if(!empty($useroperatorsql)){
			$sql = " SELECT DISTINCT c.id ";
		}
		if(!empty($courseoperatorsql)){
			$sql = " SELECT DISTINCT ue.userid ";
		}
		$sql .= " FROM {course} c
				  JOIN {enrol} e ON e.courseid = c.id AND e.status = 0
				  JOIN {user_enrolments} ue on ue.enrolid = e.id AND ue.status = 0
				  JOIN {role_assignments}  ra ON ra.userid = ue.userid
				  JOIN {role} r ON r.id = ra.roleid AND r.shortname = 'student'
				  JOIN {context} ctx ON ctx.instanceid = c.id 
                  JOIN {user} u ON u.id = ra.userid AND u.confirmed = 1 AND u.deleted = 0 
				  AND ra.contextid = ctx.id AND ctx.contextlevel = 50 AND c.visible = 1";
		if(!empty($courseoperatorsql)){
			$sql .= " WHERE c.id = $courseoperatorsql";
		}
		if(!empty($useroperatorsql)){
			$sql .= " WHERE ra.userid = $useroperatorsql";
		}
		return $sql;
	}

	public function filter_get_colleges($pluginclass, $selectoption = true, $filtercollege = false) {
		global $DB, $USER;
		$systemcontext = context_system::instance();
		if ($pluginclass->report->type != 'sql') {
			if (is_siteadmin() || ($_SESSION['role'] == 'manager')) {
				$collegelist = array_keys($DB->get_records_sql("SELECT * FROM {local_costcenter} WHERE 1 = 1 AND parentid = 0 "));
			}else{
				
				$collegelist = array_keys($DB->get_records_sql("SELECT * FROM {local_costcenter} WHERE 1 = 1 AND parentid = 0 AND id = $USER->open_costcenterid"));
			}
		} else {
			$collegelist = array_keys($DB->get_records_sql("SELECT * FROM {local_costcenter} WHERE 1 = 1 AND parentid = 0 "));
		}

		$collegeoptions = array();
		if($selectoption){
			$collegeoptions[0] = $pluginclass->singleselection ? get_string('filter_college', 'block_learnerscript') :
			get_string('select') . ' ' . get_string('college', 'block_learnerscript');
		}

		if (!empty($collegelist)) {
			list($usql, $params) = $DB->get_in_or_equal($collegelist);
			$companies = $DB->get_records_select('local_costcenter', "id $usql", $params);
			foreach ($companies as $c) {
				if ($c->id == 0) {
					continue;
				}
				$collegeoptions[$c->id] = format_string($c->fullname);
			}
		}
		return $collegeoptions;
	}
	public function filter_get_departments($pluginclass, $selectoption = true, $departmentcollegeid, $filterdepartment = false) {
		global $DB, $USER;
		 $systemcontext = context_system::instance();
		if ($pluginclass->report->type != 'sql') {
			if (is_siteadmin() || ($_SESSION['role'] == 'manager')) {
				$departmentlist = array_keys($DB->get_records_sql("SELECT * FROM {local_costcenter} WHERE parentid = $departmentcollegeid AND univ_dept_status = 0"));
			}else if ((!is_siteadmin() && !has_capability('local/costcenter:manage_multiorganizations', $systemcontext)) && has_capability('local/costcenter:manage_ownorganization',$systemcontext) && has_capability('block/learnerscript:managelsreports',$systemcontext)) {
                    $departmentlist = array_keys($DB->get_records_sql_menu("SELECT * FROM {local_costcenter} WHERE parentid = $USER->open_costcenterid "));
                    
            }else if ((!is_siteadmin() && !has_capability('local/costcenter:manage_multiorganizations', $systemcontext)) && !has_capability('local/costcenter:manage_ownorganization',$systemcontext) && has_capability('block/learnerscript:managelsreports',$systemcontext) && has_capability('block/learnerscript:manageownreports',$systemcontext) && !has_capability('local/costcenter:manage_owndepartments',$systemcontext)) {
                    $departmentlist = array_keys($DB->get_records_sql_menu("SELECT * FROM {local_costcenter} WHERE parentid = $USER->open_costcenterid AND univ_dept_status =0 "));
            }else {
                $departmentlist = array_keys($DB->get_records_sql("SELECT * FROM {local_costcenter} WHERE 1 = 1 AND parentid = $departmentcollegeid AND univ_dept_status = 0 AND id = $USER->open_departmentid "));
            }
		} else {
			$departmentlist = array_keys($DB->get_records_sql("SELECT * FROM {local_costcenter} WHERE parentid = $departmentcollegeid AND univ_dept_status = 0 AND id = $USER->open_departmentid "));
		}
		$departmentoptions = array();
		if($selectoption){
			$departmentoptions[0] = $pluginclass->singleselection ? get_string('filter_department', 'block_learnerscript') :
			get_string('select') . ' ' . get_string('department');
		}
		if (!empty($departmentlist)) {
			list($usql, $params) = $DB->get_in_or_equal($departmentlist);
			$departments = $DB->get_records_select('local_costcenter', "id $usql", $params);
			foreach ($departments as $d) {
				if ($d->id == 0) {
					continue;
				}
				$departmentoptions[$d->id] = format_string($d->fullname);
			}
		}
		return $departmentoptions;
	}
	public function filter_get_programs($pluginclass, $selectoption = true, $programcollegeid, $programdeptid) {
		global $DB, $USER;
		$programoptions = array();
		if($selectoption){
			$programoptions[0] = $pluginclass->singleselection ? get_string('filter_program', 'block_learnerscript') :
			get_string('select') . ' ' . get_string('program');
		}
		$sql = "SELECT * FROM {local_program} WHERE costcenter = $programcollegeid";
		if (!empty($programdeptid) && $programdeptid > 0) { 
			$sql .= "  AND departmentid = $programdeptid";
		}
		$programs = $DB->get_records_sql_menu($sql);
		foreach ($programs as $p) {
			if ($p->id == 0) {
				continue;
			}
			$programoptions[$p->id] = format_string($p->fullname);
		}
		return $programoptions;
	}
	public function get_rolecolleges($userid, $role) {
		global $DB;
		$colleges = $DB->get_records_sql_menu("SELECT lc.id, lc.fullname FROM {local_costcenter} lc
								JOIN {user} u ON u.open_costcenterid = lc.id 
								WHERE u.id = $userid AND lc.parentid = 0");
		return $colleges;						

	}
	public function get_roledepartments($userid, $role, $collegeid) {
		global $DB, $USER;
		$systemcontext = context_system::instance();
		$sql = "SELECT lco.id, lco.fullname FROM {local_costcenter} lc
								JOIN {user} u ON u.open_costcenterid = lc.id
								LEFT JOIN {local_costcenter} lco ON lc.id = u.open_costcenterid AND lco.parentid = lc.id
								WHERE u.id = $userid AND lco.parentid = $collegeid AND lco.univ_dept_status = 0 AND lc.parentid = 0";
		if ((!is_siteadmin() && !has_capability('local/costcenter:manage_multiorganizations', $systemcontext)) && has_capability('local/costcenter:manage_ownorganization',$systemcontext) && has_capability('block/learnerscript:managelsreports',$systemcontext)) {
			$sql .= " ";
        } else if ((!is_siteadmin() && !has_capability('local/costcenter:manage_multiorganizations', $systemcontext)) && !has_capability('local/costcenter:manage_ownorganization',$systemcontext) && has_capability('block/learnerscript:managelsreports',$systemcontext) && has_capability('block/learnerscript:manageownreports',$systemcontext)) {
			$sql .= " ";
        } else {
            $sql .= " AND u.open_departmentid = $USER->open_departmentid ";
        }
		$departments = $DB->get_records_sql_menu($sql);
		return $departments;
	}
}

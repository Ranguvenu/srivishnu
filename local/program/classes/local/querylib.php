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
 * curriculum Queries
 *
 * @package    local_curriculum
 * @copyright  2018 Arun Kumar M <arun@eabyas.in>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_program\local;

defined('MOODLE_INTERNAL') || die();
use context_system;

class querylib {
    /**
     * Get authenticated user manage departments
     * @return array format departments list
     */
    public function get_user_departments() {
        global $DB, $USER;

        $sql = "SELECT lc.id, CONCAT(lc.fullname, '-', lc.shortname) AS fullshrtname
                  FROM {local_costcenter} lc
                  JOIN {user} u on (u.open_costcenterid = lc.id OR
                        u.open_costcenterid = lc.parentid)
                WHERE u.id = :userid";
        $departments = $DB->get_records_sql_menu($sql, array('userid' => $USER->id));
        if (empty($departments)) {
            $departments = array();
        }
        return $departments;
    }
    /**
     * Get authenticated user manage courses based on departments
     * @return array format courses list
     */
    public function get_courses($costcenters = false) {
        global $DB, $USER;
        $costcentersql = '';
        $params = array();
        $courses = array();
        if (!empty($costcenters)) {
            $costcenter = implode(',', $costcenters);
            $costcentersql .= " AND c.open_costcenterid in (:costcenter) ";
            $params['costcenter'] = $costcenter;
        }
        $sql = "SELECT c.id, c.fullname
                  FROM {course} as c
                  JOIN {enrol} AS en on en.courseid=c.id and en.enrol='curriculum' and en.status=0
                 WHERE 1 = 1 AND c.visible = :visible AND c.id <> :siteid $costcentersql";
        $params['siteid'] = SITEID;
        $params['visible'] = 1;
        $courses = $DB->get_records_sql_menu($sql, $params);
        return $courses;
    }
    /**
     * [get_user_department_trainerslist description]
     * @method get_user_department_trainerslist
     * @param  boolean                          $service     [description]
     * @param  boolean                          $costcenters [description]
     * @param  array                            $trainers    [description]
     * @param  string                           $query       [description]
     * @return [type]                                        [description]
     */
    public function get_user_department_trainerslist($service = false, $costcenters = false,
        $trainers = array(), $query = '') {
        global $DB, $USER;
        $costcentersql = '';
        $concatsql = '';
        $context = context_system::instance();
        $params = array();
        list($ctxcondition, $ctxparams) = $DB->get_in_or_equal($context->get_parent_context_ids(true), SQL_PARAMS_NAMED, 'ctx');
        $params = array_merge($params, $ctxparams);
        if (!empty($trainers)) {
            list($trainerslistsql, $trainerslistparams) = $DB->get_in_or_equal($trainers, SQL_PARAMS_NAMED, 'crtr');
            $params = array_merge($params, $trainerslistparams);
        }

        if (!empty($costcenters)) {
            $costcenters = implode(',', $costcenters);
            $concatsql .= " AND u.open_costcenterid in ( $costcenters ) ";
            //$params['costcenterid'] = $costcenters;
        }
         if ((has_capability('local/program:manageprogram', context_system::instance())) &&
            (!is_siteadmin() )&&(!has_capability('local/program:manage_multiorganizations', context_system::instance()) && !has_capability('local/costcenter:manage_multiorganizations', context_system::instance()))) {
            $concatsql .= " AND u.open_costcenterid in ( :costcenterid ) ";
            $params['costcenterid'] = $USER->open_costcenterid;
             if ((has_capability('local/program:manage_owndepartments', context_system::instance()) || has_capability('local/costcenter:manage_owndepartments', context_system::instance()))) {
                $concatsql .= " AND u.open_departmentid in ( :open_departmentid ) ";
                $params['open_departmentid'] = $USER->open_departmentid;
             }
        }
        if (!empty($query)) {
            $fields = array('u.email', 'CONCAT(u.firstname, " ", u.lastname)');
            $fields = implode(" LIKE :search1 OR ", $fields);
            $fields .= " LIKE :search2 ";
            $params['search1'] = '%' . $query . '%';
            $params['search2'] = '%' . $query . '%';
            $concatsql .= " AND ($fields) ";
        }

        $trainerslist = array();
        $params['confirmed'] = 1;
        $params['suspended'] = 0;
        $params['deleted'] = 0;
        $params['roleid'] = $DB->get_field('role', 'id', array('shortname' => 'faculty'));

        $fields = "SELECT u.id , CONCAT(u.firstname, ' ', u.lastname) AS fullname ";
        $sql = "FROM {role_assignments} ra
                  JOIN {user} u ON u.id = ra.userid
                  JOIN {context} ctx ON ra.contextid = ctx.id
                 WHERE u.confirmed = :confirmed
                  AND u.suspended = :suspended AND u.deleted = :deleted AND u.id > 2
                       AND ctx.id $ctxcondition
                       AND ra.roleid = :roleid";
        if (!empty($trainers)) {
            $sql .= " AND u.id $trainerslistsql";
        }
        $sql.= $concatsql;

        $order = " ORDER BY u.id ASC";
        /*print_object($fields . $sql . $order);
        print_object($params);exit;*/
        if ($service) {
            $trainerslist = $DB->get_records_sql($fields . $sql . $order, $params);
        } else {
            $trainerslist = $DB->get_records_sql_menu($fields . $sql . $order, $params);
        }

        return $trainerslist;

    }
    public function get_department_institute_list($costcenters, $institutetype) {
        global $DB;

        $costcenters = implode(',', array_flip($costcenters));

        $sql = "SELECT lci.id,CONCAT(lci.fullname, '-', lci.shortnname) AS fullshrtname
                 FROM {local_location_institutes} lci
                WHERE lci.costcenter IN (:costcenterid) AND lci.institute_type = :institutetype AND lci.visible= :visiblefld";
        $institutelist = $DB->get_records_sql_menu($sql,
            array('costcenterid' => $costcenters, 'institutetype' => $institutetype,
                'visiblefld' => 1));

        if (empty($institutelist)) {
            $institutelist = array();
        }
        return $institutelist;

    }
    public function get_coursecategories() {
        global $DB;
        $params = array();
        $coursecategories = array();
        $sql = "SELECT cc.id, cc.name
                  FROM {course_categories} cc
                 WHERE 1 = 1 AND cc.visible = :visible";
        $params['visible'] = 1;
        $coursecategories = $DB->get_records_sql_menu($sql, $params);
        return $coursecategories;
    }
    public function get_curriculum_institutes($institutetype = 0, $service = array()) {
        global $DB;
        $institutes = array();
        if ($institutetype > 0) {
            $params = array();
            $institutessql = "SELECT id, fullname
                                FROM {local_location_institutes}
                               WHERE institute_type = :institute_type";
            $params['institute_type'] = $institutetype;
            if (!empty($service)) {
                if ($service['instituteid'] > 0) {
                    $institutessql .= " AND id = :instituteid ";
                    $params['instituteid'] = $instituteid;
                }
                if ($service['curriculumid'] > 0) {
                    $institutessql .= " AND costcenter = :costcenter ";
                    $params['costcenter'] = $DB->get_field('local_curriculum', 'costcenter', array('id' => $service['curriculumid']));
                }
                if (!empty($service['query'])) {
                    $institutessql .= " AND fullname LIKE :query ";
                    $params['query'] = '%' . $service['query'] . '%';
                }
            }
            $institutes = $DB->get_records_sql($institutessql, $params);
        }
        return $institutes;
    }
    public function get_curriculum_institute_rooms($curriculumid) {
        global $DB;
        $locationroomlists = array();
        if ($curriculumid > 0) {
            $locationroomlistssql = "SELECT cr.id, cr.name
                                       FROM {local_location_room} AS cr
                                       JOIN {local_location_institutes} AS ci ON ci.id = cr.instituteid
                                       JOIN {local_curriculum} AS c ON ( c.instituteid = ci.id
                                        AND c.institute_type = ci.institute_type)
                                       WHERE cr.visible = 1 AND ci.visible = 1 AND c.id = $curriculumid ";

            $locationroomlists = $DB->get_records_sql_menu($locationroomlistssql);
        }
        return $locationroomlists;
    }
     public function get_curriculum_programlist($id=0) {
        global $DB;
        $param=array();
        $programssql = "SELECT id, fullname,costcenter
                           FROM {local_program} where 1=1 ";
        if($id){
          $programssql.= " AND id=:id";
          $param['id']=$id;
          $programs = $DB->get_record_sql($programssql,$param);
        }else{
            $programs = $DB->get_records_sql($programssql,$param);
        }
        return $programs;
     }
}
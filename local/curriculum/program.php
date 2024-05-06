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
 * curriculum View
 *
 * @package    local_curriculum
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
//namespace local_program;
defined('MOODLE_INTERNAL') || die();
use context_system;
use stdClass;
use moodle_url;
use completion_completion;
use html_table;
use html_writer;
use core_component;
use \local_courses\action\insert as insert;
require_once($CFG->dirroot . '/local/curriculum/lib.php');
if (file_exists($CFG->dirroot . '/local/lib.php')) {
  require_once($CFG->dirroot . '/local/lib.php');
}
use \local_curriculum\notifications_emails as curriculumnotifications_emails;
// curriculum
define('curriculum_NEW', 0);
define('curriculum_COMPLETED', 2);
// Session Attendance
define('SESSION_PRESENT', 1);
define('SESSION_ABSENT', 2);
// Types
define('curriculum', 1);

class program {
    /**
     * Manage curriculum (Create or Update the curriculum)
     * @method manage_curriculum
     * @param  Object           $data Clasroom Data
     * @return Integer               curriculum ID
     */
    public function manage_curriculum($curriculum,$copy=false) {
        global $DB, $USER;
        $curriculum->shortname = $curriculum->name;
        if (empty($curriculum->trainers)) {
            $curriculum->trainers = null;
        }
        if (empty($curriculum->capacity) || $curriculum->capacity == 0) {
            $curriculum->capacity = 0;
        }
// <mallikarjun> - ODL-763 Unable to create curriculum under college -- starts
        if($curriculum->open_univdept_status == 1){
            $curriculum->departmentid = $curriculum->open_collegeid;
        }else{
            $curriculum->departmentid = $curriculum->open_departmentid;
        }    
// <mallikarjun> - ODL-763 Unable to create curriculum under college -- ends
        $curriculum->startdate = 0;
        $curriculum->enddate = 0;
        $curriculum->description = strip_tags($curriculum->cr_description['text']);
        $curriculum->department = $curriculum->departmentid;
      
        try {
            if ($curriculum->id > 0) {
                $curriculum->timemodified = time();
                $curriculum->usermodified = $USER->id;
                if($DB->update_record('local_curriculum', $curriculum)){
                      $semesteryearsdata = new stdClass();
                      $semesteryearsdata->id = $curriculum->id;
                       $semesteryearsdata->action = 'curriculum_form_data';
                    $this->manage_program_curriculum_years($semesteryearsdata, true);
                }
                $params = array(
                    'context' => context_system::instance(),
                    'objectid' => $curriculum->id
                );
                // Trigger curriculum updated event.

                $event = \local_curriculum\event\curriculum_updated::create($params);
                $event->add_record_snapshot('local_curriculum', $curriculum);
                $event->trigger();
            } else {
                $curriculum->status = 0;
                $curriculum->timecreated = time();
                $curriculum->usercreated = $USER->id;
       
                $curriculum->id = $DB->insert_record('local_curriculum', $curriculum);

                $params = array(
                    'context' => context_system::instance(),
                    'objectid' => $curriculum->id
                );

                $event = \local_curriculum\event\curriculum_created::create($params);
                $event->add_record_snapshot('local_curriculum', $curriculum);
                $event->trigger();

                $curriculum->shortname = 'curriculum' . $curriculum->id;
                $DB->update_record('local_curriculum', $curriculum);
                if ($curriculum->id && $copy==false) {
                    // $semesterdata = new stdClass();
                    // $semesterdata->curriculumid = $curriculum->id;
                    // $this->manage_curriculum_program_semesters($semesterdata, true);

                    $semesteryearsdata = new stdClass();
                    $semesteryearsdata->programid = $curriculum->program;
                    $semesteryearsdata->curriculumid = $curriculum->id;
                   
                    $this->manage_program_curriculum_years($semesteryearsdata, true);

                }
            }
            $curriculum->totalsemesters = $DB->count_records('local_curriculum_semesters', array('curriculumid' => $curriculum->id));
            $DB->update_record('local_curriculum', $curriculum);
        } catch (dml_exception $ex) {
            print_error($ex);
        }
        return $curriculum->id;
    }

 

    public function manage_curriculum_semester_completions($curriculumid, $semesterid, $yearid = 0) {
        global $DB, $USER;

        $courses = $DB->get_records_menu('local_cc_semester_courses',
            array('curriculumid' => $curriculumid, 'semesterid' => $semesterid), '', 'id, courseid');
        $bclcomptlcheck = $DB->record_exists('local_ccs_cmplt_criteria',
            array('curriculumid' => $curriculumid, 'semesterid' => $semesterid));
        if ($bclcomptlcheck) {
            $completions = $DB->get_record('local_ccs_cmplt_criteria',
                array('curriculumid' => $curriculumid, 'semesterid' => $semesterid));
        } else {
            $completions = new stdClass();
            $completions->curriculumid = $curriculumid;
            $completions->semesterid = $semesterid;
        }
        $completions->sessionids = null;

        if (!empty($courses) && is_array($courses)) {
            $completions->courseids = implode(', ', array_values($courses));

        } else {
            $completions->courseids = null;
        }

        $completions->sessiontracking = null;
        $completions->coursetracking = 'AND';
        try {
            if ($completions->id > 0) {
                $completions->timemodified = time();
                $completions->usermodified = $USER->id;
                $DB->update_record('local_ccs_cmplt_criteria', $completions);
                $params = array(
                    'context' => context_system::instance(),
                    'objectid' => $completions->id,
                    'other' => array('curriculumid' => $completions->curriculumid,
                                             'semesterid' => $completions->semesterid)
                );
                $event = \local_curriculum\event\program_completions_settings_updated::create($params);
                $event->add_record_snapshot('local_ccs_cmplt_criteria', $completions->curriculumid);
                $event->trigger();
            } else {
                $completions->timecreated = time();
                $completions->usercreated = $USER->id;
                $completions->yearid = $yearid;

                $completions->id = $DB->insert_record('local_ccs_cmplt_criteria', $completions);
                $params = array(
                    'context' => context_system::instance(),
                    'objectid' => $completions->id,
                    'other' => array('curriculumid' => $completions->curriculumid,
                                             'semesterid' => $completions->semesterid)
                );
                $event = \local_curriculum\event\program_completions_settings_created::create($params);
                $event->add_record_snapshot('local_ccs_cmplt_criteria', $completions);
                $event->trigger();
            }
        } catch (dml_exception $ex) {
            print_error($ex);
        }
        return $completions->id;
    }
    /**
     * [curriculum_sessions_delete description]
     * @param  [type] $curriculumid [description]
     * @return [type]              [description]
     */
  /*  public function curriculum_sessions_delete($curriculumid){
        global $DB, $USER;
        $curriculum_sessions = $DB->get_records_sql_menu("SELECT *
                                                FROM {local_cc_course_sessions}
                                                where curriculumid = $curriculumid");
         foreach ($curriculum_sessions as $curriculum_session) {
            $id = $curriculum_session->id;
            $DB->delete_records('local_curriculum_attendance', array('sessionid' => $id));
            $params = array(
                'context' => context_system::instance(),
                'objectid' => $id,
                'other' => array('curriculumid' => $curriculum_session->curriculumid,
                                 'semesterid' => $curriculum_session->semesterid,
                                 'bclcid' => $curriculum_session->bclcid)
            );
            $event = \local_program\event\bcsemestercourse_session_deleted::create($params);
            $event->add_record_snapshot('local_cc_course_sessions', $curriculum_session);
            $event->trigger();

            $DB->delete_records('local_cc_course_sessions', array('id' => $id));

            $curriculum = new stdClass();
            $curriculum->id = $curriculumid;
            $curriculum->totalsessions = $DB->count_records('local_cc_course_sessions',
                array('curriculumid' => $curriculumid));
            $curriculum->activesessions = $DB->count_records('local_cc_course_sessions',
                array('curriculumid' => $curriculumid, 'attendance_status' => 1));
            $DB->update_record('local_curriculum', $curriculum);
         }
        $curriculum_users=$DB->get_records_menu('local_curriculum_users',
                                        array('curriculumid' =>$curriculumid), 'id', 'id, userid');

        foreach ($curriculum_users as $curriculum_user) {
            $attendedsessions = $DB->count_records('local_curriculum_attendance',
                        array('curriculumid' => $curriculumid,
                            'userid' => $curriculum_user, 'status' => SESSION_PRESENT));

            $attendedsessions_hours=$DB->get_field_sql("SELECT ((sum(lcs.duration))/60) AS hours
                                                FROM {local_cc_course_sessions} AS lcs
                                                WHERE  lcs.curriculumid =$curriculumid
                                                AND lcs.id IN (SELECT sessionid  FROM {local_curriculum_attendance} where curriculumid =
                                                $curriculumid AND userid = $curriculum_user AND status=1)");

            if (empty($attendedsessions_hours)) {
                $attendedsessions_hours = 0;
            }

            $DB->execute('UPDATE {local_curriculum_users} SET attended_sessions = ' .
                        $attendedsessions . ',hours = ' .
                        $attendedsessions_hours . ', timemodified = ' . time() . ',
                        usermodified = ' . $USER->id . ' WHERE curriculumid = ' .
                        $curriculumid . ' AND userid = ' . $curriculum_user);
        }
    }*/
    /**
     * Update curriculum Location and Date
     * @method location_date
     * @param  Object        $data curriculum Location and Nomination Data
     * @return Integer        curriculum ID
     */
  /*  public function location_date($data) {
        global $DB, $USER;
        $location = new stdClass();
        $location->institute_type = $data->institute_type;
        $location->instituteid = $data->instituteid;
        $location->nomination_startdate = $data->nomination_startdate;
        $location->nomination_enddate = $data->nomination_enddate;
        try {
            $local_curriculum = $DB->get_record_sql("SELECT id,instituteid FROM {local_curriculum} where id=$data->id");
            if (isset($location->instituteid) && ($location->instituteid != $local_curriculum->instituteid) && ($local_curriculum->instituteid != 0)) {
                $DB->execute('UPDATE {local_cc_course_sessions} SET roomid = 0,
                    timemodified = ' . time() . ',
                   usermodified = ' . $USER->id . ' WHERE curriculumid = ' .
                   $data->id. '');
            }
            $location->id = $data->id;
            $location->timemodified = time();
            $location->usermodified = $USER->id;
            $DB->update_record('local_curriculum', $location);
        } catch (dml_exception $ex) {
            print_error($ex);
        }
        return $data->id;
    }*/
    /**
     * curriculums
     * @method curriculums
     * @param  Object     $stable Datatable fields
     * @return Array  curriculums and totalcurriculumcount
     */
    public function curriculums($stable, $request = false) {
        global $DB, $USER;
        $params = array();
        $curriculums = array();
        $curriculumscount = 0;
        $concatsql = '';

        if (!empty($stable->search)) {
            $fields = array("bc.name");
            $fields = implode(" LIKE :search1 OR ", $fields);
            $fields .= " LIKE :search2 ";
            $params['search1'] = '%' . $stable->search . '%';
            $params['search2'] = '%' . $stable->search . '%';
            $concatsql .= " AND ($fields) ";
        }
    /*    if (!is_siteadmin()&&!has_capability('local/curriculum:manage_multiorganizations', context_system::instance()) &&
            ((has_capability('local/curriculum:manage_ownorganization', context_system::instance())))) {*/
                    /*$colleges=$DB->get_field_sql("SELECT GROUP_CONCAT(id) FROM {local_costcenter} where parentid=:parentid",  array('parentid'=>$USER->open_costcenterid),  $strictness=IGNORE_MISSING);

                    $condition = " AND (ps.costcenter = :costcenter OR ps.costcenter = :parentcostcenter)";
                    $params['parentcostcenter'] = $colleges;*/// Commented by Harish//
               //     $params['costcenter'] = $USER->open_costcenterid;
                    /*$colleges = $DB->get_records_sql_menu("SELECT id,id as cid FROM {local_costcenter} where parentid=:parentid",  array('parentid'=>$USER->open_costcenterid),  $strictness=IGNORE_MISSING);

                    list($relatedprogramyearysql, $relatedprogramyearparams) = $DB->get_in_or_equal($colleges, SQL_PARAMS_NAMED);

                    $condition.= " AND ( ps.costcenter $relatedprogramyearysql)";

                    $params = $params+$relatedprogramyearparams;

                $concatsql .= $condition;*/
       /* } else if (!is_siteadmin()&&!has_capability('local/curriculum:manage_multiorganizations', context_system::instance())&&(has_capability('local/curriculum:manage_owndepartments', context_system::instance()))) {

                $condition .= " AND (ps.costcenter = :department )";
                $params['department'] = $USER->open_departmentid;
                $concatsql .= $condition;

        } else if (!is_siteadmin() && !has_capability('local/curriculum:manage_multiorganizations', context_system::instance())&&has_capability('local/curriculum:trainer_viewcurriculum', context_system::instance())) {
                $mycurriculums = $DB->get_records_menu('local_cc_session_trainers',
                    array('trainerid' => $USER->id), 'programid', 'programid, programid as ps');

                if (!empty($mycurriculums)) {
                    $mycurriculums = implode(', ', $mycurriculums);
                    $concatsql .= " AND ps.id IN ( $mycurriculums )";
                } else {
                    return compact('curriculums', 'curriculumscount');
                }
        } else if (!is_siteadmin() && (!has_capability('local/curriculum:manage_multiorganizations', context_system::instance()) && !has_capability('local/curriculum:manage_multiorganizations', context_system::instance()))) {

              $mycurriculums = $DB->get_records_menu('local_cc_session_signups',
                  array('userid' => $USER->id), 'programid', 'programid, programid as ps');

                  if (!empty($mycurriculums)) {
                      $mycurriculums = implode(', ', $mycurriculums);
                      $concatsql .= " AND ps.id IN ( $mycurriculums ) ";
                  } else {
                      return compact('curriculums', 'curriculumscount');
                  }

          }
          print_object($mycurriculums);
*/
        // if ((has_capability('local/program:managecurriculum', context_system::instance())) &&
        //     (!is_siteadmin() && (!has_capability('local/program:manage_multiorganizations', context_system::instance()) && !has_capability('local/costcenter:manage_multiorganizations', context_system::instance())))) {

        //         $colleges=$DB->get_field_sql("SELECT GROUP_CONCAT(id) FROM `mdl_local_costcenter` where parentid=:parentid",  array('parentid'=>$USER->open_costcenterid),  $strictness=IGNORE_MISSING);

        //         $condition = " AND (cc.id = :costcenter OR cc.id = :parentcostcenter)";
        //         $params['costcenter'] = $USER->open_costcenterid;
        //         $params['parentcostcenter'] =$colleges;

        //     if ((has_capability('local/program:manage_owndepartments', context_system::instance())
        //             || has_capability('local/costcenter:manage_owndepartments', context_system::instance()))) {
        //         $condition .= " AND (bc.department = :department )";
        //         $params['department'] = $USER->open_departmentid;
        //     }
        //     $concatsql .= $condition;
        //     if (has_capability('local/program:trainer_viewcurriculum', context_system::instance())) {
        //         $mycurriculums = $DB->get_records_menu('local_cc_course_sessions',
        //             array('trainerid' => $USER->id), 'id', 'id, curriculumid');
        //         if (!empty($mycurriculums)) {
        //             $mycurriculums = implode(', ', $mycurriculums);
        //             $concatsql .= " AND bc.id IN ( $mycurriculums )";
        //         } else {
        //             return compact('curriculums', 'curriculumscount');
        //         }
        //     }
        // } else if (!is_siteadmin() && (!has_capability('local/program:manage_multiorganizations', context_system::instance()) && !has_capability('local/costcenter:manage_multiorganizations', context_system::instance()))) {

        //     $mycurriculums = $DB->get_records_menu('local_curriculum_users',
        //         array('userid' => $USER->id), 'id', 'id, curriculumid');

        //     if (isset($stable->curriculumid) && !empty($stable->curriculumid)) {
        //         $userenrolstatus = $DB->record_exists('local_curriculum_users',
        //             array('curriculumid' => $stable->curriculumid, 'userid' => $USER->id));
        //         $status = $DB->get_field('local_curriculum', 'status',
        //             array('id' => $stable->curriculumid));
        //          $curriculum_costcenter = $DB->get_field('local_curriculum', 'costcenter',
        //             array('id' => $stable->curriculumid));
        //         if ($status == 1 && !$userenrolstatus &&
        //             $curriculum_costcenter == $USER->open_costcenterid) {
        //         } else {
        //             if (!empty($mycurriculums)) {
        //                 $mycurriculums = implode(', ', $mycurriculums);
        //                 $concatsql .= " AND bc.id IN ( $mycurriculums )";
        //             } else {
        //                 return compact('curriculums', 'curriculumscount');
        //             }
        //         }
        //     } else {
        //         if (!empty($mycurriculums)) {
        //             $mycurriculums = implode(', ', $mycurriculums);
        //             $concatsql .= " AND bc.id IN ( $mycurriculums ) ";
        //         } else {
        //             return compact('curriculums', 'curriculumscount');
        //         }
        //     }
        // }
        if (isset($stable->curriculumid) && $stable->curriculumid > 0) {
            $concatsql .= " AND bc.id = :curriculumid";
            $params['curriculumid'] = $stable->curriculumid;
        }

        $countsql = "SELECT COUNT(bc.id) ";
        if ($request == true) {
            $fromsql = "SELECT group_concat(bc.id) AS curriculumids";
        } else {
            $fromsql = "SELECT bc.*, ps.parentid, ps.admissionenddate, (SELECT COUNT(DISTINCT cu.userid)
                                  FROM {local_curriculum_users} AS cu
                                  WHERE cu.curriculumid = bc.id
                              ) AS enrolled_users, (SELECT COUNT(DISTINCT bu.userid)
                                  FROM {local_curriculum_users} AS bu
                                  WHERE bu.curriculumid = bc.id AND bu.completion_status = 1 AND bu.completiondate > 0
                              ) AS completed_users";
        }
        // if ((has_capability('local/program:managecurriculum', context_system::instance())) &&
        //     (!is_siteadmin() && (!has_capability('local/program:manage_multiorganizations', context_system::instance()) && !has_capability('local/costcenter:manage_multiorganizations', context_system::instance())))) {
        //         $joinon = "cc.id = bc.costcenter";
        //     if ((has_capability('local/program:manage_owndepartments', context_system::instance()) || has_capability('local/costcenter:manage_owndepartments', context_system::instance()))) {
        //         $joinon = "cc.id = bc.department OR cc.id = bc.costcenter";
        //     }
        // } else {
        //     $joinon = "cc.id = bc.costcenter";
        // }
        $sql = " FROM {local_curriculum} AS bc
                 JOIN {local_program} ps ON ps.id = bc.program
                 JOIN {local_costcenter} AS cc ON cc.id=ps.costcenter
                WHERE 1 = 1 ";
        $sql .= $concatsql;

        if (isset($stable->curriculumid) && $stable->curriculumid > 0) {
            $curriculums = $DB->get_record_sql($fromsql . $sql, $params);
        } else {
            try {
                $curriculumscount = $DB->count_records_sql($countsql . $sql, $params);
                if ($stable->thead == false) {
                    $sql .= " ORDER BY bc.id DESC";
                    if ($request == true) {
                        $curriculums = $DB->get_record_sql($fromsql . $sql, $params, $stable->start, $stable->length);
                    } else {
                        $curriculums = $DB->get_records_sql($fromsql . $sql, $params, $stable->start, $stable->length);
                    }
                }
            } catch (dml_exception $ex) {
                $curriculumscount = 0;
            }
        }
        if (isset($stable->curriculumid) && $stable->curriculumid > 0) {
            return $curriculums;
        } else {
            return compact('curriculums', 'curriculumscount');
        }
    }
    /**
     * curriculum sessions
     * @method sessions
     * @param  Integer   $bclcid bclcid ID
     * @param  Object   $stable      Datatable fields
     * @return Array    Sessions and total session count for the perticular curriculum
     */
    public function curriculumsessions($bclcdata, $stable, $userview = false, $tab=null) {
        global $DB, $USER;
        $systemcontext = context_system::instance();
        $curriculumid = $bclcdata->curriculumid;
        $semesterid = $bclcdata->semesterid;
        $bclcid = $bclcdata->bclcid;
        $bccourse = $DB->get_record('local_cc_semester_courses', array('id' => $bclcid));
        if (empty($bccourse)) {
            print_error('curriculum data missing');
        }
        $concatsql = '';
        if (!empty($stable->search)) {
            $fields = array (0 => 'bcs.name',
                            1 => 'lr.name'
                        );
            $fields = implode(" LIKE '%" . $stable->search . "%' OR ", $fields);
            $fields .= " LIKE '%" .$stable->search. "%' ";
            $concatsql .= " AND ($fields) ";
        }
        $params = array();
        $curriculums = array();

        $countsql = "SELECT COUNT(bcs.id) ";
        $fromsql = "SELECT bcs.*, lr.name as room";
        if ($userview) {
            $fromsql .= ", bss.id as signupid, bss.completion_status,
                    (SELECT COUNT(id) FROM {local_cc_session_signups} WHERE bclcid = bcs.bclcid AND sessionid = bcs.id) signups, (SELECT COUNT(id) FROM {local_cc_session_signups} WHERE bclcid = bcs.bclcid AND userid = $USER->id) as mysignupstatus ";
        }

        $sql = " FROM {local_cc_course_sessions} AS bcs
                LEFT JOIN {user} AS u ON u.id = bcs.trainerid
                LEFT JOIN {local_location_room} AS lr ON lr.id = bcs.roomid ";
        if ($userview) {
            $sql .= " LEFT JOIN {local_cc_session_signups} AS bss ON bss.sessionid = bcs.id  AND bss.userid = $USER->id";
        }

        $sql .= " WHERE 1 = 1 AND bcs.curriculumid = $curriculumid AND bcs.semesterid = $semesterid AND bcs.bclcid = $bclcid";
        $sql .= $concatsql;

        if ($userview) {
            $time = time();
            $sql .= " AND (bcs.timefinish > $time OR bcs.id IN (SELECT sessionid
                    FROM {local_cc_course_sessions} WHERE curriculumid = $curriculumid AND semesterid = $semesterid AND bclcid = $bclcid AND userid = $USER->id ) )";
        } else if (has_capability('local/curriculum:takesessionattendance', $systemcontext) && !is_siteadmin() && !has_capability('local/curriculum:manageprogram', $systemcontext)){
            $sql .= " AND bcs.trainerid = $USER->id ";
        }
        if($tab == 'upcomingsessions'){
            $currtime = time();
            $currdate = date("d-m-y",$currtime);
            $date = explode('-', $currdate);
            $starttime = mktime(0,0,0,$date[1],$date[0],$date[2]);
            $endtime = mktime(23,59,59,$date[1],$date[0],$date[2]);
            $sql .= " AND bcs.timestart > $starttime";
        }
        if($tab == 'completedsessions'){
            $currtime = time();
            $currdate = date("d-m-y",time());
            $date = explode('-', $currdate);
            $starttime = mktime(0,0,0,$date[1],$date[0],$date[2]);
            $endtime = mktime(23,59,59,$date[1],$date[0],$date[2]);
            $sql .= " AND bcs.timestart < $starttime ";
        }
        // print_object($userview);
        // echo $fromsql . $sql;
        try {
            $sessionscount = $DB->count_records_sql($countsql . $sql, $params);
            if ($stable->thead == false) {
                if (!$userview) {
                    $sql .= " ORDER BY bcs.id DESC";
                }
                $sessions = $DB->get_records_sql($fromsql . $sql, $params, $stable->start,
                             $stable->length);
            }
        } catch (dml_exception $ex) {
            $sessionscount = 0;
            $sessions = array();
        }
        return compact('sessions', 'sessionscount');
    }
    /* public function sessions_validation($curriculumid, $sessiondate, $sessionid=0) {
        global $DB;
        $return = 0;
        if ($curriculumid && $sessiondate) {
            $params = array();
            $params['curriculumid'] = $curriculumid;
            $params['sessiondate_start'] = date('Y-m-d H:i', $sessiondate);
            $params['sessiondate_end'] = date('Y-m-d H:i', $sessiondate);

            $sql = "SELECT * FROM {local_cc_course_sessions} WHERE curriculumid = :curriculumid AND (from_unixtime(timestart, '%Y-%m-%d %H:%i') = :sessiondate_start OR from_unixtime(timefinish, '%Y-%m-%d %H:%i') = :sessiondate_end)";
            if ($sessionid > 0) {
                 $sql .= " AND id != :sessionid ";
                 $params['sessionid'] = $sessionid;
            }
            $return = $DB->record_exists_sql($sql,$params);
        }
        return $return;
     }*/
    /**
     * [add_curriculum_signups description]
     * @method add_curriculum_signups
     * @param  [type]                $curriculumid [description]
     * @param  [type]                $userid      [description]
     * @param  integer               $sessionid   [description]
     */
    /*public function add_curriculum_signups($curriculumid, $userid, $sessionid = 0) {
        global $DB, $USER;
        $curriculum = $DB->record_exists('local_curriculum',  array('id' => $curriculumid));
        if (!$curriculum) {
            print_error("curriculum Not Found!");
        }
        $user = $DB->record_exists('user', array('id' => $userid));
        if (!$user) {
            print_error("User Not Found!");
        }
        if ($sessionid > 0) {
            $session = $DB->record_exists('local_cc_course_sessions', array('id' => $sessionid, 'curriculumid' => $curriculumid));
            if (!$session) {
                print_error("Session Not Found!");
            }
        }
        $sessions = $DB->get_records('local_cc_course_sessions', array('curriculumid' => $curriculumid));
        foreach ($sessions as $session) {
            $checkattendeesignup = $DB->get_record('local_curriculum_attendance',
            array('curriculumid' => $curriculumid, 'sessionid' => $session->id, 'userid' => $userid));
            if (!empty($checkattendeesignup)) {
                continue;
            } else {
                $attendeesignup = new stdClass();
                $attendeesignup->curriculumid = $curriculumid;
                $attendeesignup->sessionid = $session->id;
                $attendeesignup->userid = $userid;
                $attendeesignup->status = 0;
                $attendeesignup->usercreated = $USER->id;
                $attendeesignup->timecreated = time();
                $id = $DB->insert_record('local_curriculum_attendance',  $attendeesignup);
                $params = array(
                    'context' => context_system::instance(),
                    'objectid' => $id
                );
                $event = \local_program\event\program_attendance_created_updated::create($params);
                $event->add_record_snapshot('local_program', $curriculumid);
                $event->trigger();
            }
        }
        return true;
    }*/
    /**
     * [remove_curriculum_signups description]
     * @method remove_curriculum_signups
     * @param  [type]                   $curriculumid [description]
     * @param  [type]                   $userid      [description]
     * @param  integer                  $sessionid   [description]
     * @return [type]                                [description]
     */
 /*   public function remove_curriculum_signups($curriculumid, $userid, $sessionid = 0) {
        global $DB, $USER;
        if ($sessionid > 0) {
            $sessions = $DB->get_records('local_cc_course_sessions',
             array('curriculumid' => $curriculumid, 'id' => $sessionid));
        } else {
            $sessions = $DB->get_records('local_cc_course_sessions',
                array('curriculumid' => $curriculumid));
        }
        foreach ($sessions as $session) {
            $checkattendeesignup = $DB->get_record('local_curriculum_attendance',
                array('curriculumid' => $curriculumid, 'sessionid' => $session->id,
                    'userid' => $userid));
            if (!empty($checkattendeesignup)) {
                $DB->delete_records('local_curriculum_attendance',
                    array('curriculumid' => $curriculumid, 'sessionid' => $session->id,
                        'userid' => $userid));
            }
        }
        return true;
    }*/
    /**
     * [curriculum_get_attendees description]
     * @method curriculum_get_attendees
     * @param  [type]                  $sessionid [description]
     * @return [type]                             [description]
     */
  /*  public function curriculum_get_attendees($curriculumid, $sessionid) {
        global $DB, $OUTPUT;
        $concatsql = "";
        $selectfileds = '';
        $whereconditions = '';*/
        // if ($sessionid > 0) {
        //     $selectfileds = ", ca.id as attendanceid, ca.completion_status";
        //     $concatsql .= " JOIN {local_cc_course_sessions} AS bcs ON bcs.curriculumid = bss.curriculumid AND bcs.curriculumid = $curriculumid
        //     JOIN {local_cc_session_signups} AS ca ON ca.curriculumid = bss.curriculumid
        //       AND ca.sessionid = bcs.id AND ca.userid = bss.userid";
        //     $whereconditions = " AND bcs.id = $sessionid";
        // }
     /*   $signupssql = "SELECT DISTINCT u.id, u.firstname, u.lastname,
                              u.email, u.picture, u.firstnamephonetic, u.lastnamephonetic,
                              u.middlename, u.alternatename, u.imagealt, ca.id as attendanceid, ca.completion_status
                        FROM {user} AS u
                        JOIN {local_cc_session_signups} AS bss ON
                                (bss.userid = u.id AND bss.curriculumid = $curriculumid)
                        JOIN {local_cc_course_sessions} AS bcs ON
                        bcs.curriculumid = bss.curriculumid AND bcs.curriculumid = $curriculumid
                        JOIN {local_cc_session_signups} AS ca ON ca.curriculumid = bss.curriculumid
                            AND ca.sessionid = bcs.id AND ca.userid = bss.userid
                       WHERE bss.curriculumid = $curriculumid AND bcs.id = $sessionid";
        $signups = $DB->get_records_sql($signupssql);
        return $signups;
    }*/
    /**
     * [curriculum_add_assignusers description]
     * @method curriculum_add_assignusers
     * @param  [type]                    $curriculumid   [description]
     * @param  [type]                    $userstoassign [description]
     * @return [type]                                   [description]
     */
    public function curriculum_add_assignusers($curriculumid, $userstoassign) {
        global $DB, $USER,$CFG;
        if (file_exists($CFG->dirroot . '/local/lib.php')) {
            require_once($CFG->dirroot . '/local/lib.php');
        }
        // require_once($CFG->dirroot . '/local/program/notifications_emails.php');
        $emaillogs = new programnotifications_emails();
        $allow = true;
        $type = 'curriculum_enrol';
        $dataobj = $curriculumid;
        $fromuserid = $USER->id;
        if ($allow) {
            foreach ($userstoassign as $key => $adduser) {
                if (true) {
                    $curriculumuser = new stdClass();
                    $curriculumuser->curriculumid = $curriculumid;
                    $curriculumuser->courseid = 0;
                    $curriculumuser->userid = $adduser;
                    $curriculumuser->supervisorid = 0;
                    $curriculumuser->prefeedback = 0;
                    $curriculumuser->postfeedback = 0;
                    $curriculumuser->trainingfeedback = 0;
                    $curriculumuser->confirmation = 0;
                    $curriculumuser->attended_sessions = 0;
                    $curriculumuser->hours = 0;
                    $curriculumuser->completion_status = 0;
                    $curriculumuser->completiondate = 0;
                    $curriculumuser->usercreated = $USER->id;
                    $curriculumuser->timecreated = time();
                    $curriculumuser->usermodified = $USER->id;
                    $curriculumuser->timemodified = time();
                    try {
                        $curriculumuser->id = $DB->insert_record('local_curriculum_users',
                            $curriculumuser);
                        $local_curriculum = $DB->get_record_sql("SELECT * FROM {local_curriculum} where id = $curriculumid");

                        $params = array(
                            'context' => context_system::instance(),
                            'objectid' => $curriculumuser->id,
                            'other' => array('curriculumid' => $curriculumid)
                        );

                        $event = \local_curriculum\event\program_users_enrol::create($params);
                        $event->add_record_snapshot('local_curriculum_users', $curriculumuser);
                        $event->trigger();

                        if ($local_curriculum->status == 0) {
                            $email_logs = $emaillogs->curriculum_emaillogs($type, $dataobj, $curriculumuser->userid, $fromuserid);
                        }
                    } catch (dml_exception $ex) {
                        print_error($ex);
                    }
                } else {
                    break;
                }
            }
            $curriculum = new stdClass();
            $curriculum->id = $curriculumid;
            $curriculum->totalusers = $DB->count_records('local_curriculum_users',
                array('curriculumid' => $curriculumid));
            $DB->update_record('local_curriculum', $curriculum);
        }
        return true;
    }
    /**
     * [curriculum_remove_assignusers description]
     * @method curriculum_remove_assignusers
     * @param  [type]                       $curriculumid     [description]
     * @param  [type]                       $userstounassign [description]
     * @return [type]                                        [description]
     */
    public function curriculum_remove_assignusers($curriculumid, $userstounassign) {
        global $DB, $USER,$CFG;
        if (file_exists($CFG->dirroot . '/local/lib.php')) {
            require_once($CFG->dirroot . '/local/lib.php');
        }
        // require_once($CFG->dirroot . '/local/program/notifications_emails.php');
        $emaillogs = new programnotifications_emails();
        $curriculumenrol = enrol_get_plugin('curriculum');
        //$studentroleid = $DB->get_field('role', 'id', array('shortname' => 'student'));
        $courses = $DB->get_records_menu('local_cc_semester_courses',
            array('curriculumid' => $curriculumid), 'id', 'id, courseid');
        $type = 'curriculum_unenroll';
        $dataobj = $curriculumid;
        $fromuserid = $USER->id;
        try {
            foreach ($userstounassign as $key=>$removeuser) {
                $local_curriculum = $DB->get_record_sql("SELECT id, status FROM {local_curriculum} WHERE id = $curriculumid");
                    if ($local_curriculum->status != 0) {
                        if (!empty($courses)) {
                            foreach ($courses as $course) {
                                if ($course > 0) {
                                    //$instance = $DB->get_record('enrol', array('courseid' => $course, 'enrol'=>'curriculum'), '*', MUST_EXIST);
                                    //$curriculumenrol->unenrol_user($instance, $removeuser, $instance->roleid, time());
                                    $unenrolcurriculumuser = $this->manage_curriculum_course_enrolments(
                                        $course, $removeuser, 'employee', 'unenrol');
                                }
                            }
                        }
                    }
                $params = array(
                   'context' => context_system::instance(),
                   'objectid' => $curriculumid
                );

                $event = \local_program\event\program_users_unenrol::create($params);
                $event->add_record_snapshot('local_curriculum_users', $curriculumid);
                $event->trigger();
                $DB->delete_records('local_curriculum_users',  array('curriculumid' => $curriculumid,
                    'userid' => $removeuser));
                if ($local_curriculum->status == 0) {
                    $email_logs = $emaillogs->curriculum_emaillogs($type ,$dataobj, $removeuser, $fromuserid);
                }
            }
            $curriculum = new stdClass();
            $curriculum->id = $curriculumid;
            $curriculum->totalusers = $DB->count_records('local_curriculum_users',
                array('curriculumid' => $curriculumid));
            $DB->update_record('local_curriculum', $curriculum);
        } catch (dml_exception $ex) {
            print_error($ex);
        }
        return true;
    }
    // OL-1042 Add Target Audience to curriculums//
    /**
     * [curriculum_logo description]
     * @method curriculum_logo
     * @param  integer        $curriculumlogo [description]
     * @return [type]                        [description]
     */
    /*public function curriculum_logo($curriculumlogo = 0) {
        global $DB;
        $curriculumlogourl = false;
        if ($curriculumlogo > 0){
            $sql = "SELECT * FROM {files} WHERE itemid = $curriculumlogo AND filename != '.'
            ORDER BY id DESC LIMIT 1";
            $curriculumlogorecord = $DB->get_record_sql($sql);
        }
        if (!empty($curriculumlogorecord)) {
          if ($curriculumlogorecord->filearea == "curriculumlogo") {
            $curriculumlogourl = moodle_url::make_pluginfile_url($curriculumlogorecord->contextid,
                $curriculumlogorecord->component, $curriculumlogorecord->filearea,
                $curriculumlogorecord->itemid, $curriculumlogorecord->filepath,
                $curriculumlogorecord->filename);
          }
        }
        return $curriculumlogourl;
    }*/
    /**
     * [manage_curriculum_courses description]
     * @method manage_curriculum_courses
     * @param  [type]                   $courses [description]
     * @return [type]                            [description]
     */
    public function manage_curriculum_courses($courses) {
        global $DB, $USER, $CFG;
        require_once($CFG->dirroot. '/course/lib.php');

            $curriculumcourseexists = $DB->record_exists('local_cc_semester_courses',
                array('curriculumid' => $courses->curriculumid, 'semesterid' => $courses->semesterid, 'open_parentcourseid' => $courses->course));
            $mastercourse = $DB->get_record('course',  array('id' => $courses->course));

            if($mastercourse){

                $mastercourse->shortname = $mastercourse->shortname .'_'.$courses->semesterid . '_' .$courses->curriculumid;
                $mastercourse->open_parentcourseid = $mastercourse->id;
                $mastercourse->id = 0;
                $clonedcourse = create_course($mastercourse);

                insert::add_enrol_meathod_tocourse($clonedcourse, 1);

                $curriculumcourse = new stdClass();
                $curriculumcourse->programid = $courses->programid;
                $curriculumcourse->curriculumid = $courses->curriculumid;
                $curriculumcourse->yearid = $courses->yearid;
                $curriculumcourse->semesterid = $courses->semesterid;
                $curriculumcourse->open_parentcourseid = $courses->course;
                $curriculumcourse->courseid = $clonedcourse->id;
                $curriculumcourse->coursetype = $courses->coursetype;
                $curriculumcourse->timecreated = time();
                $curriculumcourse->usercreated = $USER->id;
                $curriculumcourse->id = $DB->insert_record('local_cc_semester_courses',
                    $curriculumcourse);
                $params = array(
                    'context' => context_system::instance(),
                    'objectid' => $curriculumcourse->id,
                    'other' => array('programid' => $courses->programid,
                                     'curriculumid' => $courses->curriculumid,
                                     'semesterid' => $courses->semesterid,
                                     'yearid' => $courses->yearid)
                );

                $event = \local_program\event\bcsemestercourse_created::create($params);
                $event->add_record_snapshot('local_cc_semester_courses', $curriculumcourse);
                $event->trigger();

                $this->manage_curriculum_semester_completions($courses->curriculumid, $courses->semesterid, $courses->yearid);

            }
            $totalcourses = $DB->count_records('local_cc_semester_courses',
                array('curriculumid' => $courses->curriculumid, 'semesterid' => $courses->semesterid, 'yearid' => $courses->yearid));
            $semesterdata = new stdClass();
            $semesterdata->id = $courses->semesterid;
            $semesterdata->programid = $courses->programid;
            $semesterdata->curriculumid = $courses->curriculumid;
            $semesterdata->totalcourses = $totalcourses;
            $semesterdata->timemodified = time();
            $semesterdata->usermodified = $USER->id;
            $DB->update_record('local_curriculum_semesters', $semesterdata);
            $totalbccourses = $DB->count_records('local_cc_semester_courses',
                array('curriculumid' => $courses->curriculumid));
            $curriculumdata = new stdClass();
            $curriculumdata->programid = $courses->programid;
            $curriculumdata->id = $courses->curriculumid;
            $curriculumdata->totalcourses = $totalbccourses;
            $curriculumdata->timemodified = time();
            $curriculumdata->usermodified = $USER->id;
            $DB->update_record('local_curriculum', $curriculumdata);

        return true;
    }
    /**
     * [manage_curriculum_course_enrolments description]
     * @method manage_curriculum_course_enrolments
     * @param  [type]                             $cousre        [description]
     * @param  [type]                             $user          [description]
     * @param  string                             $roleshortname [description]
     * @param  string                             $type          [description]
     * @param  string                             $pluginname    [description]
     * @return [type]                                            [description]
     */
    public function manage_curriculum_course_enrolments($cousre, $user, $roleshortname = 'employee',
        $type = 'enrol', $pluginname = 'curriculum') {
        global $DB;
        $enrolmethod = enrol_get_plugin($pluginname);
        $roleid = $DB->get_field('role', 'id', array('shortname' => $roleshortname));
        $instance = $DB->get_record('enrol', array('courseid' => $cousre, 'enrol' => $pluginname), '*', MUST_EXIST);
        if (!empty($instance)) {
            if ($type == 'enrol') {
                $enrolmethod->enrol_user($instance, $user, $roleid, time());
            } else if ($type == 'unenrol'){
                $enrolmethod->unenrol_user($instance, $user,$roleid, time());
            }
        }
        return true;
    }
    public function curriculum_semesteryears($curriculumid, $yearid = 0) {
        global $DB, $USER;
        $params = array();
        $curriculumsemesteryearssql = "SELECT pcy.id, pcy.year,pcy.curriculumid,  pcy.cost
                                     FROM {local_program_cc_years} pcy
                                     JOIN {local_curriculum} cc ON cc.id = pcy.curriculumid
                                    WHERE cc.id = :curriculumid";
          $params['curriculumid'] = $curriculumid;
        if ($yearid) {
          $curriculumsemesteryearssql .= " AND pcy.id = :yearid ";
          $params['yearid'] = $yearid;
        }
         // $curriculumsemesteryearssql .=" ORDER BY pcy.year ASC";

        $curriculumsemesteryears = $DB->get_records_sql($curriculumsemesteryearssql, $params);
       // print_object($curriculumsemesteryears);
        return $curriculumsemesteryears;
    }
  /*  public function curriculum_semesters($curriculumid) {
        global $DB, $USER;
        $curriculumsemesterssql = "SELECT bcl.id, bcl.semester, bcl.position
                                FROM {local_curriculum_semesters} bcl
                                JOIN {local_curriculum} bc ON bc.id = bcl.curriculumid
                                WHERE bc.id = :curriculumid";
        $curriculumsemesters = $DB->get_records_sql($curriculumsemesterssql,
            array('curriculumid' => $curriculumid));
        return $curriculumsemesters;
    }*/
    /**
     * [curriculum_courses description]
     * @method curriculum_courses
     * @param  [type]            $curriculumid [description]
     * @return [type]                         [description]
     */
    /*public function curriculum_semester_courses($curriculumid, $semesterid, $userview = false) {
        global $DB, $USER;
        $context = context_system::instance();
        if ($semesterid > 0) {
            $params = array();
            $curriculumcourses = array();
            $semestercousrsesselect = '';
            $semestercousrsessql = '';
            if ($userview && !is_siteadmin() && !has_capability('local/program:createprogram', $context)) {
                $semestercousrsesselect = ", bss.id AS signupid";
                $semestercousrsessql = " LEFT JOIN {local_cc_session_signups} bss ON bss.sessionid = bcs.id AND userid = $USER->id";
            }
            $curriculumcoursesssql = "SELECT bclc.id AS bcsemestercourseid, bclc.curriculumid,
                                    bclc.semesterid, c.fullname AS course, c.*, bcs.id AS sessionid,
                                    bcs.name AS session, bcs.timestart, bcs.timefinish

                                    $semestercousrsesselect
                                      FROM {local_cc_semester_courses} bclc
                                      JOIN {course} c ON c.id = bclc.courseid
                                 LEFT JOIN {local_cc_course_sessions} bcs ON bcs.bclcid = bclc.id
                                      $semestercousrsessql
                                     WHERE bclc.curriculumid = :curriculumid
                                     AND bclc.semesterid = $semesterid";
            if ($userview && !is_siteadmin() && !has_capability('local/program:createprogram', $context)) {
                $curriculumcoursesssql .= " ORDER BY bcsemestercourseid, signupid ASC";
            }
            $curriculumsemestercourses = $DB->get_records_sql($curriculumcoursesssql,
                array('curriculumid' => $curriculumid));
        }
        return $curriculumsemestercourses;
    }*/
    /**
     * [update_curriculum_status description]
     * @method update_curriculum_status
     * @param  [type]                  $curriculumid     [description]
     * @param  [type]                  $curriculumstatus [description]
     * @return [type]                                   [description]
     */
    /*public function update_curriculum_status($curriculumid, $curriculumstatus) {
        global $DB, $USER;
        $curriculum = new stdClass();
        $curriculum->id = $curriculumid;
        $curriculum->status = $curriculumstatus;
        if($curriculumstatus == curriculum_COMPLETED) {
            $activeusers = $DB->count_records('local_curriculum_users', array('curriculumid' => $curriculumid,
                'completion_status' => 1));
            $curriculum->activeusers = $activeusers;
            $totalusers = $DB->count_records('local_curriculum_users', array('curriculumid' => $curriculumid));
            $curriculum->totalusers = $totalusers;
            $activesessions = $DB->count_records('local_cc_course_sessions', array('curriculumid' => $curriculumid,
                'attendance_status' => 1));
            $curriculum->activesessions = $activesessions;
            $totalsessions = $DB->count_records('local_cc_course_sessions', array('curriculumid' => $curriculumid));
            $curriculum->totalsessions = $totalsessions;
        }
        $curriculum->usermodified = $USER->id;
        $curriculum->timemodified = time();
        $curriculum->completiondate = time();
        try {
            $DB->update_record('local_curriculum', $curriculum);
            $params = array(
                    'context' => context_system::instance(),
                    'objectid' => $curriculumid
                );
            $event  = \local_program\event\program_completed::create($params);
            $event->add_record_snapshot('local_program', $curriculumid);
            $event->trigger();
           //  $params = array(
           //     'context' => context_system::instance(),
           //     'objectid' => $curriculum->id
           //  );

           // $event = \local_program\event\program_updated::create($params);
           // $event->add_record_snapshot('local_program', $curriculum->id);
           // $event->trigger();
        } catch (dml_exception $ex) {
            print_error($ex);
        }
        return true;
    }*/
    /**
     * [curriculumusers description]
     * @method curriculumusers
     * @param  [type]         $curriculumid [description]
     * @param  [type]         $stable      [description]
     * @return [type]                      [description]
     */
    public function curriculumusers($curriculumid, $stable) {
        global $DB, $USER;
        $params = array();
        $curriculumusers = array();
        $concatsql = '';
        if (!empty($stable->search)) {
            $fields = array(0 => 'u.firstname',
                            1 => 'u.lastname',
                            2 => 'u.email',
                            3 => 'u.idnumber'
                            );
                $fields = implode(" LIKE '%" .$stable->search. "%' OR ", $fields);
                $fields .= " LIKE '%" .$stable->search. "%' ";
                $concatsql .= " AND ($fields) ";
        }
        $countsql = "SELECT COUNT(cu.id) ";
        $fromsql = "SELECT u.*, cu.attended_sessions, cu.hours, cu.completion_status, c.totalsessions,
        c.activesessions";
        $sql = " FROM {user} AS u
                 JOIN {local_curriculum_users} AS cu ON cu.userid = u.id ";
        if ($stable->yearid > 0) {
          $sql .= " JOIN {local_cc_session_signups} ccss ON ccss.userid = cu.userid ";
          $concatsql .= " AND ccss.yearid = :yearid ";
          $params['yearid'] = $stable->yearid;
        }
        $sql .= " JOIN {local_curriculum} AS c ON c.id = cu.curriculumid
                  WHERE c.id = $curriculumid AND u.confirmed = 1 AND u.suspended = 0 AND u.deleted = 0 AND u.id > 2";
        $sql .= $concatsql;
        try {
            $curriculumuserscount = $DB->count_records_sql($countsql . $sql, $params);
            if ($stable->thead == false) {
                $sql .= " ORDER BY id ASC";
                $curriculumusers = $DB->get_records_sql($fromsql . $sql, $params, $stable->start, $stable->length);
            }
        } catch (dml_exception $ex) {
            $curriculumuserscount = 0;
        }
        return compact('curriculumusers', 'curriculumuserscount');
    }
    /**
     * [curriculum_completions description]
     * @method curriculum_completions
     * @param  [type]                $curriculumid [description]
     * @return [type]                             [description]
     */
    public function curriculum_completions($programid, $curriculumid, $userid) {
        global $DB, $USER, $CFG;

        $totalyears = $DB->count_records('local_program_cc_years', array('programid' => $programid, 'curriculumid' => $curriculumid));

        $completedyears = $DB->count_records('local_cc_session_signups', array('programid' => $programid, 'curriculumid' => $curriculumid, 'completion_status' => 1, 'userid' => $userid));

        if ($totalyears == $completedyears) {
            $curriculumuser = $DB->get_record('local_curriculum_users', array('programid' => $programid, 'curriculumid' => $curriculumid, 'userid' => $userid));

            if (!empty($curriculumuser)) {

                    $curriculumuser->completion_status = 1;
                    $curriculumuser->completiondate = time();
                    $curriculumuser->usermodified = $USER->id;
                    $curriculumuser->timemodified = time();

                    $DB->update_record('local_curriculum_users', $curriculumuser);
                    $params = array(
                        'context' => context_system::instance(),
                        'objectid' => $curriculumuser->id
                    );
                    $notifications_exists = $DB->record_exists('local_notification_type', array('shortname' => 'program_completion'));
                    if($notifications_exists){
                        $emaillogs = new programnotifications_emails();
                        $email_logs = $emaillogs->curriculum_emaillogs('program_completion', $curriculumuser, $userid, $USER->id);
                    }
                    $event = \local_curriculum\event\program_users_updated::create($params);
                    $event->add_record_snapshot('local_curriculum_users', $curriculumuser);
                    $event->trigger();
            }
        }
        return true;
    }
   /* public function curriculumcategories($formdata){
        global $DB;
        if ($formdata->id) {
            $DB->update_record('local_curriculum_categories', $formdata);
        } else {
            $DB->insert_record('local_curriculum_categories', $formdata);
        }
    }*/
    /**
     * [select_to_and_from_users description]
     * @param  [type]  $type       [description]
     * @param  integer $curriculumid [description]
     * @param  [type]  $params     [description]
     * @param  integer $total      [description]
     * @param  integer $offset1    [description]
     * @param  integer $perpage    [description]
     * @param  integer $lastitem   [description]
     * @return [type]              [description]
     */
    /*public function select_to_and_from_users($type = null, $curriculumid = 0, $params, $total = 0, $offset1 = -1, $perpage = -1, $lastitem = 0) {

        global $DB, $USER;
        $curriculum = $DB->get_record('local_curriculum', array('id' => $curriculumid));

        $params['suspended'] = 0;
        $params['deleted'] = 0;

        if ($total == 0) {
            $sql = "SELECT u.id, concat(u.firstname,' ',u.lastname,' ','(',u.email,')',' ','(',u.open_employeeid,')') as fullname";
        } else {
            $sql = "SELECT count(u.id) as total";
        }
        $sql .= " FROM {user} AS u
                 WHERE  u.id > 2 AND u.suspended = :suspended
                                     AND u.deleted = :deleted ";
        if ($lastitem != 0) {
            $sql.=" AND u.id > $lastitem";
        }
        if ((has_capability('local/program:manageprogram', context_system::instance())) && (!is_siteadmin() && (!has_capability('local/program:manage_multiorganizations', context_system::instance()) && !has_capability('local/costcenter:manage_multiorganizations', context_system::instance())))) {
            $sql .= " AND u.open_costcenterid = :costcenter";
            $params['costcenter'] = $USER->open_costcenterid;
            if ((has_capability('local/program:manage_owndepartments', context_system::instance()) || has_capability('local/costcenter:manage_owndepartments', context_system::instance()))) {
                $sql .= " AND u.open_departmentid = :department";
                $params['department'] = $USER->open_departmentid;
            }
        }
        $sql .= " AND u.id <> $USER->id ";
        if (!empty($params['email'])) {
            $sql .= " AND u.id IN ({$params['email']})";
        }
        if (!empty($params['uname'])) {
            $sql .= " AND u.id IN ({$params['uname']})";
        }
        if (!empty($params['department'])) {
            $sql .= " AND u.open_departmentid IN ({$params['department']})";
        }
        if (!empty($params['organization'])) {
            $sql .= " AND u.open_costcenterid IN ({$params['organization']})";
        }
        if (!empty($params['idnumber'])) {
            $sql .= " AND u.id IN ({$params['idnumber']})";
        }

        if (!empty($params['groups'])) {
            $sql .= " AND u.id IN (select cm.userid from {cohort_members} cm, {user} u where u.id = cm.userid AND u.deleted = 0 AND u.suspended = 0 AND cm.cohortid IN ({$params['groups']}))";
        }

        if ($type == 'add') {
            $sql .= " AND u.id NOT IN (SELECT lcu.userid as userid
                                       FROM {local_curriculum_users} AS lcu
                                       WHERE lcu.curriculumid = $curriculumid)";
        } else if ($type == 'remove') {
            $sql .= " AND u.id IN (SELECT lcu.userid as userid
                                       FROM {local_curriculum_users} AS lcu
                                       WHERE lcu.curriculumid = $curriculumid)";
        }

        $sql .= " AND u.id NOT IN (SELECT lcu.trainerid as userid
                                       FROM {local_curriculum_trainers} AS lcu
                                       WHERE lcu.curriculumid = $curriculumid)";

        $order = ' ORDER BY u.id ASC ';
        if ($perpage != -1) {
            $order .= "LIMIT $perpage";
        }

        if ($total == 0) {
            $availableusers = $DB->get_records_sql_menu($sql . $order, $params);
        } else {
            $availableusers = $DB->count_records_sql($sql, $params);
        }
        return $availableusers;
    }*/
    /**
     * [curriculum_self_enrolment description]
     * @param  [type] $curriculumid   [description]
     * @param  [type] $curriculumuser [description]
     * @return [type]                [description]
     */
  /*  public function curriculum_self_enrolment($curriculumid,$curriculumuser){
        global $DB;
        $curriculum_capacity_check=$this->curriculum_capacity_check($curriculumid);
        if (!$curriculum_capacity_check) {
            $this->curriculum_add_assignusers($curriculumid,array($curriculumuser));
            // $curriculumcourses = $DB->get_records_menu('local_cc_semester_courses', array('curriculumid' => $curriculumid), 'id', 'id, courseid');
            // foreach($curriculumcourses as $curriculumcourse) {
            //    $this->manage_curriculum_course_enrolments($curriculumcourse, $curriculumuser);
            // }
        }
    }*/

    public function curriculum_capacity_check($curriculumid) {
        global $DB;
        $return             = false;
        $curriculumcapacity = $DB->get_field('local_curriculum', 'capacity', array(
            'id' => $curriculumid
        ));
        $enrolledusers     = $DB->count_records('local_curriculum_users', array(
            'curriculumid' => $curriculumid
        ));
        if ($curriculumcapacity <= $enrolledusers && !empty($curriculumcapacity) && $classroomcapacity != 0) {
            $return = true;
        }
        return $return;
    }

    /**
     * [function to get user enrolled curriculums count]
     * @param  [INT] $userid [id of the user]
     * @return [INT]         [count of the curriculums enrolled]
     */
  /*  public function enrol_get_users_curriculums_count($userid) {
        global $DB;
        $curriculum_sql = "SELECT count(id)
                           FROM {local_curriculum_users}
                          WHERE userid = :userid";
        $curriculum_count = $DB->count_records_sql($curriculum_sql, array('userid' => $userid));
        return $curriculum_count;
    }*/
    /**
     * [function to get user enrolled curriculums ]
     * @param  [int] $userid [id of the user]
     * @return [object]         [curriculums object]
     */
   /* public function enrol_get_users_curriculums($userid) {
        global $DB;
        $curriculum_sql = "SELECT lc.id, lc.name, lc.description
                           FROM {local_curriculum} AS lc
                           JOIN {local_curriculum_users} AS lcu ON lcu.curriculumid = lc.id
                          WHERE userid = :userid";
        $curriculums = $DB->get_records_sql($curriculum_sql, array('userid' => $userid));
        return $curriculums;
    }*/
    public function manage_program_curriculum_years($semesteryears, $autocreate = false) {
        global $DB, $USER;

        try {
            if ($semesteryears->id > 0) {
                $semesteryears->usermodified = $USER->id;
                $semesteryears->timemodified = time();
                $DB->update_record('local_program_cc_years',$semesteryears);
                //Added By Yamini for Updating Years in Curriculum as well as program_cc_years tables
                $durationdata = $DB->get_record_sql('SELECT duration, duration_format FROM {local_curriculum} WHERE id = :id ', array('id' => $semesteryears->id));
               if ($durationdata->duration_format == 'Y') {
                        $years = $durationdata->duration;
               } else if ($durationdata->duration_format == 'M') 
               {       
                        $months =$durationdata->duration;

               }
                if($years == 1){
                        
                        if($DB->get_field('local_program_cc_years','id',array('curriculumid' => $semesteryears->id,'year' => 'Year 1'))){
                            $res = $DB->execute('DELETE FROM {local_program_cc_years} WHERE curriculumid ='.$semesteryears->id.' AND year != "Year 1"' );
                        }
                        else{
                            $res = $DB->execute('DELETE FROM {local_program_cc_years} WHERE curriculumid ='.$semesteryears->id );
                        $record =  new stdClass();
                        $record->curriculumid = $semesteryears->id;
                        $record->year = 'Year ' . 1;
                        $record->cost = 0;
                        $record->usercreated = $USER->id;
                        $record->timecreated = time();
                        $DB->insert_record('local_program_cc_years', $record);
                        }
                        $yearscount = $DB->count_records_sql('SELECT count(id) as id FROM {local_program_cc_years} WHERE curriculumid = '.$semesteryears->id.' AND year = "'.$record->year.'"');
                           
                        $DB->execute('UPDATE {local_curriculum} SET duration = 1 WHERE id = '.$semesteryears->id);
                }
                else{
                    if($months){

                       //if($months == 1){
                        
                        /*if($DB->get_field('local_program_cc_years','id',array('curriculumid' => $semesteryears->id,'year' => 'Month 1'))){
                            $res = $DB->execute('DELETE FROM {local_program_cc_years} WHERE curriculumid ='.$semesteryears->id.' AND year != "Year 1"' );
                        }
                        else{*/
                            $res = $DB->execute('DELETE FROM {local_program_cc_years} WHERE curriculumid ='.$semesteryears->id );
                        $record =  new stdClass();
                        $record->curriculumid = $semesteryears->id;
                        $record->year = $durationdata->duration.' Months' ;
                        $record->cost = 0;
                        $record->usercreated = $USER->id;
                        $record->timecreated = time();
                        $DB->insert_record('local_program_cc_years', $record);
                      //  }
                        $yearscount = $DB->count_records_sql('SELECT count(id) as id FROM {local_program_cc_years} WHERE curriculumid = '.$semesteryears->id.' AND year = "'.$record->year.'"');
                           
                        $DB->execute('UPDATE {local_curriculum} SET duration = '.$durationdata->duration.' WHERE id = '.$semesteryears->id);
                        //}
                        /*else{
                           $cc_years_count = $DB->count_records_sql("SELECT count(id) as ccids FROM {local_program_cc_years} WHERE curriculumid = ".$semesteryears->id);             
                        if($durationdata->duration >= $cc_years_count){
                            for ($i = 1; $i <= $months; $i++) 
                            {      
                                $record =  new stdClass();
                                $record->curriculumid = $semesteryears->id;
                                $record->year = 'Month ' . $i;
                                $record->cost = 0;
                                $record->usercreated = $USER->id;
                                $record->timecreated = time();
                                // print_object($record);
                                 if($DB->get_field('local_program_cc_years','id',array('curriculumid' => $record->curriculumid,'year' => $record->year))){
                                    continue;
                                 }
                                 else{
                                $DB->insert_record('local_program_cc_years', $record);
                                $yearscount = $DB->count_records_sql('SELECT count(id) as id FROM {local_program_cc_years} WHERE curriculumid = '.$semesteryears->id.' AND year = "'.$record->year.'"');
                                   }
                                $DB->execute('UPDATE {local_curriculum} SET duration ='. $yearscount.' WHERE id = '.$semesteryears->id);
                            }

                        }
                        else if($durationdata->duration < $cc_years_count){                           for ($i = $durationdata->duration; $i < $cc_years_count; $i++) {    
                                $duration = $i+1;
                                $count = $DB->count_records_sql('SELECT count(id) as id FROM {local_program_cc_years} WHERE curriculumid = '.$semesteryears->id.' AND year = "Year '.$duration.'"');
                                if($count == 1){

                                    $sql = "DELETE FROM {local_program_cc_years} WHERE curriculumid = ".$semesteryears->id." AND year = 'Year ".$duration."'";
                                    $DB->execute($sql);
                                    $yearscount = $DB->count_records_sql('SELECT count(id) as id FROM {local_program_cc_years} WHERE curriculumid = '.$semesteryears->id.' AND year = "Year '.$duration.'"');
                                        $DB->execute('UPDATE {local_curriculum} SET duration ='. $yearscount.' WHERE id = '.$semesteryears->id);
                                }
                            }
                        }
                        }*/
                    }
                        $cc_years_count = $DB->count_records_sql("SELECT count(id) as ccids FROM {local_program_cc_years} WHERE curriculumid = ".$semesteryears->id);             
                       /* if ($durationdata->duration_format == 'Y') {
                            $res = $DB->execute('DELETE FROM {local_program_cc_years} WHERE curriculumid ='.$semesteryears->id );
                        }*/
                        if($durationdata->duration >= $cc_years_count){
                            for ($i = 1; $i <= $years; $i++) 
                            {      
                                $record =  new stdClass();
                                $record->curriculumid = $semesteryears->id;
                                $record->year = 'Year ' . $i;
                                $record->cost = 0;
                                $record->programid = 0;
                                $record->usercreated = $USER->id;
                                $record->timecreated = time();
                                // print_object($record);exit;
                                $exists_months = $DB->get_field_sql("SELECT id FROM {local_program_cc_years} WHERE curriculumid = ".$record->curriculumid." AND year LIKE '%Months%'");
                                if($exists_months){
                                   $res = $DB->execute('DELETE FROM {local_program_cc_years} WHERE id ='.$exists_months ); 
                                }
                                 if($DB->get_field('local_program_cc_years','id',array('curriculumid' => $record->curriculumid,'year' => $record->year))){
                                    continue;
                                 }
                                 else{
                                $DB->insert_record('local_program_cc_years', $record);
                                $yearscount = $DB->count_records_sql('SELECT count(id) as id FROM {local_program_cc_years} WHERE curriculumid = '.$semesteryears->id.' AND year = "'.$record->year.'"');
                                   }
                                $DB->execute('UPDATE {local_curriculum} SET duration ='. $yearscount.' WHERE id = '.$semesteryears->id);
                            }

                        }
                        else if($durationdata->duration < $cc_years_count){                           for ($i = $durationdata->duration; $i < $cc_years_count; $i++) {    
                                $duration = $i+1;
                                $count = $DB->count_records_sql('SELECT count(id) as id FROM {local_program_cc_years} WHERE curriculumid = '.$semesteryears->id.' AND year = "Year '.$duration.'"');
                                if($count == 1){

                                    $sql = "DELETE FROM {local_program_cc_years} WHERE curriculumid = ".$semesteryears->id." AND year = 'Year ".$duration."'";
                                    $DB->execute($sql);
                                    $yearscount = $DB->count_records_sql('SELECT count(id) as id FROM {local_program_cc_years} WHERE curriculumid = '.$semesteryears->id.' AND year = "Year '.$duration.'"');
                                        $DB->execute('UPDATE {local_curriculum} SET duration ='. $yearscount.' WHERE id = '.$semesteryears->id);
                                }
                            }
                        }
                    }
                //Ends By Yamini
                $params = array(
                    'context' => context_system::instance(),
                    'objectid' => $semesteryears->id,
                    'other' =>array('programid' => $semesteryears->programid, 'curriculumid' => $semesteryears->curriculumid)
                );

                $event = \local_curriculum\event\year_updated::create($params);
                $event->add_record_snapshot('local_program_cc_years', $semesteryears);
                $event->trigger();
            } else {
                if ($autocreate) {
                    $records = array();
                    $durationdata = $DB->get_record_sql('SELECT duration, duration_format FROM {local_curriculum} WHERE id = :id ', array('id' => $semesteryears->curriculumid));
                    if ($durationdata->duration_format == 'Y') {
                        $years = $durationdata->duration;
                        $sequence = 1;
                        for ($i = 1; $i <= $years; $i++) {
                        ${'record' . $i} = new stdClass();
                        ${'record' . $i}->curriculumid = $semesteryears->curriculumid;
                        ${'record' . $i}->year = 'Year ' . $i;
                        ${'record' . $i}->cost = 0;
                        ${'record' . $i}->programid = $semesteryears->programid;
                        ${'record' . $i}->usercreated = $USER->id;
                        ${'record' . $i}->timecreated = time();
                        ${'record' . $i}->sequence = $sequence;
                        $records[$i] = ${'record' . $i};
                        $sequence++;
                    }
                    $DB->insert_records('local_program_cc_years', $records);
                    } else if($durationdata->duration_format == 'M') {
                        $months = $durationdata->duration;
                          $datarecord = new \stdClass();
                        $datarecord->curriculumid = $semesteryears->curriculumid;
                        $datarecord->year = $durationdata->duration.' Months';
                        $datarecord->cost = 0;
                        $datarecord->programid = $semesteryears->programid;
                        $datarecord->timecreated =  time();
                        $datarecord->usercreated =  $USER->id;
                        $DB->insert_record('local_program_cc_years',  $datarecord);

                    }                           
                    return true;
                }else {
                    $semesteryears->usercreated = $USER->id;
                    $semesteryears->timecreated = time();
                    //$semesteryears->programid = 0;
                    $semesteryears->programid = $semesteryears->programid;
                      //Added by Yamini for updating duration count in curriculum table when we add years. 
                    $existing_yearid = $DB->get_field('local_program_cc_years','id',array('curriculumid' => $semesteryears->curriculumid , 'year' => $semesteryears->year)); 
                    if(empty($existing_yearid)){
                      $semesteryears->id = $DB->insert_record('local_program_cc_years', $semesteryears);
                    }
                    $yearscount = $DB->count_records_sql('SELECT count(id) as id FROM {local_program_cc_years} WHERE curriculumid = '.$semesteryears->curriculumid);
                    $DB->execute('UPDATE {local_curriculum} SET duration ='. $yearscount.',duration_format = "Y" WHERE id = '.$semesteryears->curriculumid);
                    //Ends
                    $params = array(
                        'context' => context_system::instance(),
                        'objectid' => $semesteryears->id,
                        'other' =>array('programid' => $semesteryears->programid, 'curriculumid' => $semesteryears->curriculumid)
                    );

                    $event = \local_curriculum\event\year_created::create($params);
                    $event->add_record_snapshot('local_program_cc_years', $semesteryears);
                    $event->trigger();
                }
            }
        } catch (dml_exception $ex) {
            print_error($ex);
        }
        return $semesteryears->id;
    }
    public function manage_curriculum_program_semesters($semester, $autocreate = false) {
        global $DB, $USER;

        $semester->description = $semester->semester_description['text'];
        try {
            if ($semester->id > 0) {
                $semester->usermodified = $USER->id;
                $semester->timemodified = time();
                $DB->update_record('local_curriculum_semesters', $semester);
                $params = array(
                    'context' => context_system::instance(),
                    'objectid' => $semester->id,
                    'other' =>array('curriculumid' => $semester->curriculumid)
                );

                $event = \local_curriculum\event\semester_updated::create($params);
                $event->add_record_snapshot('local_curriculum_semesters', $semester);
                $event->trigger();
            } else {
                if ($autocreate) {
                    $records = array();
                    for ($i = 0; $i < 7; $i++) {
                        ${'record' . $i} = new stdClass();
                        ${'record' . $i}->curriculumid = $semester->curriculumid;
                        ${'record' . $i}->semester = 'Semester ' . $i;
                        ${'record' . $i}->description = '';
                        ${'record' . $i}->programid = $semester->programid;
                        ${'record' . $i}->usercreated = $USER->id;
                        ${'record' . $i}->timecreated = time();
                        $records[$i] = ${'record' . $i};
                    }
                    $DB->insert_records('local_curriculum_semesters', $records);
                    return true;
                } else {
                    $semester->usercreated = $USER->id;
                    $semester->timecreated = time();
                    $semester->id = $DB->insert_record('local_curriculum_semesters', $semester);

                    $params = array(
                        'context' => context_system::instance(),
                        'objectid' => $semester->id,
                        'other' =>array('curriculumid' => $semester->curriculumid)
                    );

                    $event = \local_curriculum\event\semester_created::create($params);
                    $event->add_record_snapshot('local_curriculum_semesters', $semester);
                    $event->trigger();
                }
            }
        } catch (dml_exception $ex) {
            print_error($ex);
        }
        return $semester->id;
    }
    public function bc_session_enrolments($enroldata) {
        global $DB, $CFG, $USER;
        // require_once($CFG->dirroot . '/local/program/notifications_emails.php');
        $sessionenroldatasql = "SELECT bss.*, bcs.timestart, bcs.timefinish, bcs.attendance_status, bcs.totalusers, bcs.mincapacity, bcs.maxcapacity
                                  FROM {local_cc_course_sessions} bcs
                                  JOIN {local_cc_session_signups} bss ON bss.sessionid = bcs.id
                                 WHERE bss.curriculumid = :curriculumid AND bss.semesterid = :semesterid
                                 AND bss.bclcid = :bclcid
                                 AND bss.userid = :userid ";
        $sessionenroldata = $DB->get_record_sql($sessionenroldatasql,
            array('curriculumid' => $enroldata->curriculumid,
                'semesterid' => $enroldata->semesterid,
                'bclcid' => $enroldata->bclcid,
                'userid' => $enroldata->userid));
        if (!empty($sessionenroldata) && $enroldata->enrol == 3) {
           $params = array(
                   'context' => context_system::instance(),
                   'objectid' => $enroldata->bclcid
                );

            $event = \local_curriculum\event\session_users_unenrol::create($params);
            $event->add_record_snapshot('local_cc_session_signups', $enroldata);
            $event->trigger();

            $DB->delete_records('local_cc_session_signups',
                array('bclcid' => $enroldata->bclcid,
                    'userid' => $enroldata->userid, 'completion_status' => 0));
            $totaluserssql = "SELECT COUNT(DISTINCT id) FROM {local_cc_session_signups} WHERE sessionid = $enroldata->sessionid";
            $totalusers = $DB->count_records_sql($totaluserssql);
            $sessiondata = new stdClass();
            $sessiondata->id = $enroldata->sessionid;
            $sessiondata->totalusers = $totalusers;
            $DB->update_record('local_cc_course_sessions', $sessiondata);
            if ($enroldata->signupid) {
                $courseid = $DB->get_field('local_cc_semester_courses', 'courseid',
                        array('id' => $enroldata->bclcid));
                $this->manage_bcsemester_course_enrolments($courseid, $USER->id, 'employee', 'unenrol');
            }
            //cancel session
            $emaillogs = new programnotifications_emails();
            $email_logs = $emaillogs->curriculum_emaillogs('curriculum_session_cancel', $enroldata, $enroldata->userid,
                                $USER->id);
        } else if (!empty($sessionenroldata) && $enroldata->enrol == 2) {
            $allsessions =  $DB->get_records('local_cc_session_signups', array('bclcid' => $enroldata->bclcid, 'userid' => $USER->id, 'completion_status' => 0));
            foreach ($allsessions as $res) {
                $DB->delete_records('local_cc_session_signups',
                array('bclcid' => $enroldata->bclcid, 'userid' => $USER->id, 'sessionid'=>$res->sessionid, 'completion_status' => 0));

                $totaluserssql = "SELECT COUNT(DISTINCT id) FROM {local_cc_session_signups} WHERE sessionid = $res->sessionid";
                $totalusers = $DB->count_records_sql($totaluserssql);
                $sessiondata = new stdClass();
                $sessiondata->id = $res->sessionid;
                $sessiondata->totalusers = $totalusers;
                $res = $DB->update_record('local_cc_course_sessions', $sessiondata);
            }
            $enroldata->userid = $USER->id;
            $enroldata->supervisorid = $USER->open_supervisorid;
            $enroldata->hours = 0;
            $enroldata->usercreated = $USER->id;
            $enroldata->timecreated = time();
            $signupid = $DB->insert_record('local_cc_session_signups', $enroldata);
            $params = array(
                            'context' => context_system::instance(),
                            'objectid' => $signupid->id,
                            'other' => array('curriculumid' => $curriculumid,
                              'semesterid' => $enroldata->semesterid,
                              'bclcid' => $enroldata->bclcid)
                        );

            $event = \local_curriculum\event\session_users_enrol::create($params);
            $event->add_record_snapshot('local_cc_session_signups', $signupid);
            $event->trigger();

            $totaluserssql = "SELECT COUNT(DISTINCT id) FROM {local_cc_session_signups} WHERE sessionid = $enroldata->sessionid";
            $totalusers = $DB->count_records_sql($totaluserssql);
            $sessiondata = new stdClass();
            $sessiondata->id = $enroldata->sessionid;
            $sessiondata->totalusers = $totalusers;
            $DB->update_record('local_cc_course_sessions', $sessiondata);
            //reschedule session
            $emaillogs = new programnotifications_emails();
            $email_logs = $emaillogs->curriculum_emaillogs('curriculum_session_reschedule', $enroldata, $enroldata->userid, $USER->id);
        } else {
            if ($enroldata->enrol == 1) {
                $supervisorid = $DB->get_field('user', 'open_supervisorid', array('id'=>$enroldata->userid));
                // $enroldata->userid = $USER->id;
                $enroldata->supervisorid = $supervisorid;
                $enroldata->hours = 0;
                $enroldata->usercreated = $USER->id;
                $enroldata->timecreated = time();
                $signupid = $DB->insert_record('local_cc_session_signups', $enroldata);

                $params = array(
                            'context' => context_system::instance(),
                            'objectid' => $signupid->id,
                            'other' => array('curriculumid' => $enroldata->curriculumid,
                              'semesterid' => $enroldata->semesterid,
                              'bclcid' => $enroldata->bclcid)
                        );

                $event = \local_curriculum\event\session_users_enrol::create($params);
                $event->add_record_snapshot('local_cc_session_signups', $signupid);
                $event->trigger();

                $totaluserssql = "SELECT COUNT(DISTINCT id) FROM {local_cc_session_signups} WHERE sessionid = $enroldata->sessionid";
                $totalusers = $DB->count_records_sql($totaluserssql);
                $sessiondata = new stdClass();
                $sessiondata->id = $enroldata->sessionid;
                $sessiondata->totalusers = $totalusers;
                $DB->update_record('local_cc_course_sessions', $sessiondata);

                //enroll session
                $emaillogs = new programnotifications_emails();
                $email_logs = $emaillogs->curriculum_emaillogs('curriculum_session_enrol', $enroldata, $enroldata->userid,
                                $USER->id);
                if ($signupid) {
                    $courseid = $DB->get_field('local_cc_semester_courses', 'courseid',
                        array('id' => $enroldata->bclcid));
                    $this->manage_bcsemester_course_enrolments($courseid, $USER->id);
                }
                return $signupid;
            }
        }
        $totaluserssql = "SELECT COUNT(DISTINCT id) FROM {local_cc_session_signups} WHERE sessionid = $enroldata->sessionid";
        $totalusers = $DB->count_records_sql($totaluserssql);
        $sessiondata = new stdClass();
        $sessiondata->id = $enroldata->sessionid;
        $sessiondata->totalusers = $totalusers;
        $DB->update_record('local_cc_course_sessions', $sessiondata);
        return true;
    }
    /**
     * [unassign_courses_to_bcsemester description]
     * @method unassign_courses_to_bcsemester
     * @param  [type]                      $curriculumid [description]
     * @param  [type]                      $semesterid    [description]
     * @param  [type]                      $bclcid     [description]
     * @return [type]                                  [description]
     */
    public function unassign_courses_from_semester($curriculumid, $yearid, $semesterid, $courseid) {
        global $DB, $CFG;
          //echo $programid.$curriculumid.$yearid.$semesterid.$courseid;exit;
        require_once($CFG->dirroot. '/course/lib.php');
        $signups = $DB->get_records('local_cc_session_signups', array('curriculumid' => $curriculumid, 'yearid' => $yearid));
        if (!empty($signups)) {
          print_error("please unassign students");
        }

        \core_php_time_limit::raise();
        // We do this here because it spits out feedback as it goes.
        delete_course($courseid, false);
        // Update course count in categories.
        fix_course_sortorder();

        $DB->delete_records('local_cc_semester_courses', array('curriculumid' => $curriculumid, 'yearid' => $yearid, 'courseid' => $courseid));

        $totalcourses = $DB->count_records('local_cc_semester_courses',
            array('curriculumid' => $curriculumid, 'yearid' => $yearid, 'semesterid' => $semesterid));
        $semesterdata = new stdClass();
        $semesterdata->id = $semesterid;
        $semesterdata->curriculumid = $curriculumid;
        $semesterdata->totalcourses = $totalcourses;
        $semesterdata->timemodified = time();
        $semesterdata->usermodified = $USER->id;
        $DB->update_record('local_curriculum_semesters', $semesterdata);
        $totalbccourses = $DB->count_records('local_cc_semester_courses',
            array('curriculumid' => $curriculumid));
        $curriculumdata = new stdClass();
        $curriculumdata->id = $curriculumid;
        $curriculumdata->totalcourses = $totalbccourses;
        $curriculumdata->timemodified = time();
        $curriculumdata->usermodified = $USER->id;
        $DB->update_record('local_curriculum', $curriculumdata);
        return true;
    }
    /**
     * [manage_bcsemester_course_enrolments description]
     * @method manage_bcsemester_course_enrolments
     * @param  [type]                           $course     [description]
     * @param  [type]                           $user       [description]
     * @param  string                           $role       [description]
     * @param  string                           $type       [description]
     * @param  string                           $pluginname [description]
     * @return [type]                                       [description]
     */
    public function manage_bcsemester_course_enrolments($course, $user, $role = 'employee',
        $type = 'enrol', $pluginname = 'curriculum') {
        global $DB;
        $enrolmethod = enrol_get_plugin($pluginname);
        $roleid = $DB->get_field('role', 'id', array('shortname' => $role));
        $instance = $DB->get_record('enrol', array('courseid' => $course, 'enrol' => $pluginname), '*', MUST_EXIST);
        if (!empty($instance)) {
            if ($type == 'enrol') {
                $enrolmethod->enrol_user($instance, $user, $roleid, time());
            } else if ($type == 'unenrol') {
                $enrolmethod->unenrol_user($instance, $user, $roleid, time());
            }
        }
        return true;
    }
    /**
     * [curriculum_semester_completions description]
     * @method curriculum_semester_completions
     * @param  [type]              $bcsemester [description]
     * @param  [type]              $user    [description]
     * @return [type]                       [description]
     */
    public function curriculum_semester_completions($bcsemester, $userid) {
        global $DB;
        // $bccoursessql = "SELECT bclc.id as bclcid, bclc.curriculumid, bclc.semesterid, bclc.courseid, bclc.totalsessions,
        //                  (SELECT COUNT(bss.id) FROM {local_cc_session_signups} bss WHERE bss.bclcid = bclc.id
        //                   AND bss.completion_status = 1 AND bss.userid = :userid) AS completedsessions
        //                  FROM {local_cc_semester_courses} bclc
        //                 WHERE bclc.semesterid = :semesterid";
        // $bccourses = $DB->get_records_sql($bccoursessql,
        //     array('semesterid' => $bcsemester->semesterid, 'userid' => $user));
        // $completedsessions = 0;
        // foreach ($bccourses as $bccourse) {
        //     if ($bccourse->completedsessions > 0) {
        //         $completedsessions++;
        //     }
        // }
        // if ($completedsessions == count($bccourses)) {
        //     return true;
        // }
        // return false;

        /*$semestercourses = $DB->count_records('local_cc_semester_courses', array('semesterid' => $bcsemester->semesterid, 'coursetype' => 1));*/
        $semestercourses = $DB->get_records('local_cc_semester_courses', array('semesterid' => $bcsemester->semesterid, 'coursetype' => 1));
        $mancoursescmpltion = 0;
        foreach($semestercourses as $semcourse){
            $coursecompleted = $DB->get_field_sql("SELECT id FROM {course_completions} WHERE course = $semcourse->courseid AND userid = $userid AND timecompleted IS NOT NULL");
            if($coursecompleted){
                $mancoursescmpltion++;
            }
        }
        $completedcourses = $DB->get_field_sql("SELECT length(courseids)
    - length(replace(courseids, ',', '')) + 1 as coursecount FROM {local_cc_semester_cmptl} WHERE semesterid = :semesterid AND userid = :userid", array('semesterid' => $bcsemester->semesterid, 'userid' => $userid));
        /*$completedcourses = $DB->get_record_sql('SELECT GROUP_CONCAT(id, ",") AS ids, count(*) AS completedcourses FROM {local_cc_semester_cmptl} WHERE semesterid = :semesterid AND userid = :userid ',  array());*/
        if ($mancoursescmpltion == $completedcourses) {
            return true;
        }
        return false;
    }
    /**
     * [bccourse_sessions_completions description]
     * @method bccourse_sessions_completions
     * @param  [type]                        $bccourse [description]
     * @return [type]                                  [description]
     */
    public function bccourse_sessions_completions($bccourse) {
        global $DB, $USER;
        $bcsessionssql = "SELECT bccs.id as sessionid, bccs.curriculumid, bccs.semesterid,
                            bccs.bclcid,
                         (SELECT COUNT(bss.id) FROM {local_cc_session_signups} bss WHERE bss.bclcid = bccs.bclcid
                          AND bss.completion_status = 1 AND bss.userid = :userid) AS completedsessions
                         FROM {local_cc_course_sessions} bccs
                        WHERE bccs.semesterid = :semesterid AND bccs.bclcid = :bclcid";
        $bcsessions = $DB->get_records_sql($bcsessionssql,
            array('semesterid' => $bccourse->semesterid,
                    'userid' => $USER->id, 'bclcid' => $bccourse->bclcid));
        $completedsessions = false;
        foreach ($bcsessions as $bcsessions) {
            if ($bcsessions->completedsessions > 0) {
                $completedsessions = true;
            }
        }
    }
    /**
     * [bc_semester_courses_completions description]
     * @method bc_semester_courses_completions
     * @param  [type]                       $userdata [description]
     * @return [type]                                 [description]
     */
    public function bc_semester_courses_completions($userdata) {
        global $DB, $USER, $CFG;
        // require_once($CFG->dirroot . '/local/program/notifications_emails.php');
        $userid = $userdata->userid;
        if ($userdata->courseid > 0) {
            $coursedata = $DB->get_record_select('local_cc_semester_courses', 'courseid = :courseid', array('courseid' => $userdata->courseid));
            if($coursedata){
                $getsemester_courses = $DB->get_records_sql_menu("SELECT id, courseid FROM {local_cc_semester_courses} WHERE semesterid = $coursedata->semesterid AND coursetype = 1");
                $mandatorycourses = implode(',', $getsemester_courses);
            }
            $semesterid = $coursedata->semesterid;
            $yearid = $coursedata->yearid;
            $curriculumid = $coursedata->curriculumid;
            $programid = $coursedata->programid;
            $checkcousrecmptlsql = "SELECT *
                                      FROM {local_cc_semester_cmptl}
                                      WHERE userid = :userid
                                      AND curriculumid = :curriculumid
                                      AND yearid = :yearid
                                      AND semesterid = :semesterid";
            $checkcousrecmptl = $DB->get_record_sql($checkcousrecmptlsql, array('userid' => $userid, 'curriculumid' => $curriculumid, 'yearid' => $yearid, 'semesterid' => $semesterid));
            if (empty($checkcousrecmptl)) {
                // $programid = $DB->get_field('local_curriculum', 'program',
                //     array('id' => $userdata->curriculumid));
                $bcsemestercmptl = new stdClass();
                $bcsemestercmptl->curriculumid = $coursedata->curriculumid;
                $bcsemestercmptl->programid = $programid;
                $bcsemestercmptl->yearid = $yearid;
                $bcsemestercmptl->type = 0;
                $bcsemestercmptl->semesterid = $coursedata->semesterid;
                $bcsemestercmptl->userid = $userdata->userid;
                if($mandatorycourses){
                    /*$completedcourses = $DB->get_records_sql("SELECT id, course FROM {course_completions} WHERE course IN ($mandatorycourses) AND userid = 71 AND timecompleted IS NOT NULL");
                    print_object($completedcourses);exit;*/
                    $bcsemestercmptl->courseids = $mandatorycourses;
                }
                $completionstatus = 0;
                $bcsemestercmptl->completion_status = $completionstatus;
                $bcsemestercmptl->completiondate = time();
                $bcsemestercmptl->usercreated = $USER->id;
                $bcsemestercmptl->timecreated = time();
                $bcsemestercmptl->id = $DB->insert_record('local_cc_semester_cmptl',
                    $bcsemestercmptl);
                $completion_status = $this->curriculum_semester_completions($coursedata, $userdata->userid);
                if ($completion_status) {
                    $bcsemestercmptl->completion_status = 1;
                    $bcsemestercmptl->completiondate = time();
                }
                $bcsemestercmptl->usermodified = $USER->id;
                $bcsemestercmptl->timemodified = time();
                $DB->update_record('local_cc_semester_cmptl', $bcsemestercmptl);
                $semestercompletion = $completion_status;
                //semester completions $completionstatus=1
                if($bcsemestercmptl->completion_status == 1){
                     $notifications_exists = $DB->record_exists('local_notification_type', array('shortname' => 'program_semester_completion'));
                    if($notifications_exists){
                      $type = 'program_semester_completion';
                      $emaillogs = new programnotifications_emails();
                      $email_logs = $emaillogs->curriculum_emaillogs($type, $coursedata, $userdata->userid, $USER->id);
                  }
                }
            } else {
                $courseids = $checkcousrecmptl->courseids;
                if (!empty($checkcousrecmptl->courseids)) {
                    $courseidslist = explode(',', $checkcousrecmptl->courseids);
                    if($coursedata->coursetype == 1){
                        if (!in_array($coursedata->courseid, $courseidslist)) {
                            $courseidslist[] = $coursedata->courseid;
                        }
                    }
                    $courseids = implode(',', $courseidslist);
                } else {
                  $courseids = $coursedata->courseid;
                }
                $checkcousrecmptl->courseids = $courseids;

                $checkcousrecmptl->usermodified = $USER->id;
                $checkcousrecmptl->timemodified = time();
                $DB->update_record('local_cc_semester_cmptl', $checkcousrecmptl);
                $completionstatus = $this->curriculum_semester_completions($coursedata, $userdata->userid);
                $semestercompletion = $completionstatus;
                if ($completionstatus) {
                    $checkcousrecmptl->completion_status = 1;
                    $checkcousrecmptl->completiondate = time();
                }
                $checkcousrecmptl->usermodified = $USER->id;
                $checkcousrecmptl->timemodified = time();
                $DB->update_record('local_cc_semester_cmptl', $checkcousrecmptl);
                //semester completions $completionstatus=1
                if ($completionstatus == 1) {
                  $notifications_exists = $DB->record_exists('local_notification_type', array('shortname' => 'program_semester_completion'));
                    if($notifications_exists){
                      $type = 'program_semester_completion';
                      $emaillogs = new programnotifications_emails();
                      $email_logs = $emaillogs->curriculum_emaillogs($type, $coursedata, $userdata->userid,
                                $USER->id);
                  }
                }
                //semester course completions $checkcousrecmptl->bclcids
            }

            if ($semestercompletion) {
                $ccyearcompletionstatus = $this->curriculum_year_completions($programid, $curriculumid, $yearid, $userid);

                $cccompletionstatus = $this->curriculum_completions($programid, $curriculumid, $userid);
                // $bcuser = $DB->get_record('local_curriculum_users',
                //     array('curriculumid' => $userdata->curriculumid,
                //         'userid' => $userdata->userid, 'completion_status' => 0));
                // if (!empty($bcuser)) {
                //     $bcsemesters = $DB->get_records_menu('local_curriculum_semesters',
                //         array('curriculumid' => $coursedata->curriculumid), 'id',
                //         'id, id AS semester');
                //     $bcusercmptlsemesterids = $bcuser->semesterids;
                //     if (empty($bcusercmptlsemesterids)) {
                //         $bcuser->semesterids = $coursedata->semesterid;
                //         $semesterids = array($coursedata->semesterid);
                //     } else {
                //         $semesterids = explode(',', $bcusercmptlsemesterids);
                //         if (!in_array($coursedata->semesterid, $semesterids)) {
                //            $semesterids[] = $coursedata->semesterid;
                //         }
                //         array_unique($semesterids);
                //         $bcuser->semesterids = implode(',', $semesterids);;
                //     }
                //     $bcsemestercompletionstatus = array_diff($bcsemesters, $semesterids);
                //     if (empty($bcsemestercompletionstatus)) {
                //         $bcuser->completion_status = 1;
                //         $bcuser->completiondate = time();
                //     }
                //     $DB->update_record('local_curriculum_users', $bcuser);
                //     //curriculum completions $bcuser->completion_status=1
                //     if($bcuser->completion_status == 1){
                //       $type = 'curriculum_completion';
                //       $emaillogs = new programnotifications_emails();
                //       $email_logs = $emaillogs->curriculum_emaillogs($type, $bcuser->curriculumid, $bcuser->userid,
                //                 $USER->id);
                //     }

                // }
            }
            return true;
        }
        return false;
    }
    public function curriculum_year_completions($programid, $curriculumid, $yearid, $userid) {
        global $DB, $USER;
        $ccyearcompletionstatussql = 'SELECT * FROM ((SELECT COUNT(cs.id) AS totalsemesters
                                   FROM {local_curriculum_semesters} cs
                                  WHERE cs.yearid = :yearid) AS totalsemesters,
                                (SELECT COUNT(csc.id) AS completedsemesters
                                   FROM {local_cc_semester_cmptl} csc
                                  WHERE csc.yearid = :yearid1 AND csc.userid = :userid ) AS completedsemesters  )';
        $ccyearcompletionstatus = $DB->get_record_sql($ccyearcompletionstatussql, array('yearid' => $yearid, 'yearid1' => $yearid, 'userid' => $userid));
        // if ($ccyearcompletionstatus->totalsemesters == $ccyearcompletionstatus->completedsemesters) {
        //     return true;
        // }
        // return false;

        if ($ccyearcompletionstatus->totalsemesters == $ccyearcompletionstatus->completedsemesters) {
            $yearcompletionstatus = $DB->get_record('local_cc_session_signups', array('programid' => $programid, 'curriculumid' => $curriculumid, 'yearid' => $yearid, 'userid' => $userid));
            if (!empty($yearcompletionstatus)) {
                $yearcompletionstatus->completion_status = 1;
                $yearcompletionstatus->completiondate = time();
                $yearcompletionstatus->usermodified = $USER->id;
                $yearcompletionstatus->timemodified = time();
                $DB->update_record('local_cc_session_signups', $yearcompletionstatus);
                $notifications_exists = $DB->record_exists('local_notification_type', array('shortname' => 'program_year_completion'));
                if($notifications_exists){
                  $emaillogs = new programnotifications_emails();
                  $email_logs = $emaillogs->curriculum_emaillogs('program_year_completion', $yearcompletionstatus, $userid, $USER->id);
                }
            }
        }
        return true;
    }
    /**
     * [mycompletedsemesters description]
     * @method mycompletedsemesters
     * @param  [type]            $curriculumid [description]
     * @param  [type]            $userid     [description]
     * @return [type]                        [description]
     */
    public function mycompletedsemesters($curriculumid, $userid) {
        global $DB;
        $mycompletedsemesters = array();
        $mycompletedsemesterssql = "SELECT semesterids
                                   FROM {local_curriculum_users}
                                  WHERE curriculumid = :curriculumid AND userid = :userid ";
        $mycompletedsemesterslist = $DB->get_field_sql($mycompletedsemesterssql,
            array('curriculumid' => $curriculumid, 'userid' => $userid));
        if (!empty($mycompletedsemesterslist)) {
            $mycompletedsemesters = explode(',', $mycompletedsemesterslist);
        }
        return $mycompletedsemesters;
    }
    /**
     * [mycompletedsemesters description]
     * @method mycompletedsemesters
     * @param  [type]            $curriculumid [description]
     * @param  [type]            $userid     [description]
     * @return [type]                        [description]
     */
    public function mycompletedsemesteryears($curriculumid, $userid) {
        global $DB;
        $mycompletedsemesteryears = array();
        $mycompletedsemesteryearssql = "SELECT years
                                   FROM {local_curriculum_users}
                                  WHERE curriculumid = :curriculumid AND userid = :userid ";
        $mycompletedsemesteryearslist = $DB->get_field_sql($mycompletedsemesteryearssql,
            array('curriculumid' => $curriculumid, 'userid' => $userid));
        if (!empty($mycompletedsemesteryearslist)) {
            $mycompletedsemesteryears = explode(',', $mycompletedsemesteryearslist);
        }
        return $mycompletedsemesteryears;
    }
    /**
     * [mycompletedsemestercourses description]
     * @method mycompletedsemestercourses
     * @param  [type]                  $bcsemester [description]
     * @return [type]                           [description]
     */
    public function mycompletedsemestercourses($bcsemester) {
        global $DB, $USER;
        $curriculumid = $bcsemester->curriculumid;
        $semesterid = $bcsemester->semesterid;
        $courses = $DB->get_fieldset_select('local_cc_semester_courses', 'id', 'curriculumid = :curriculumid AND semesterid = :semesterid ORDER BY position ASC',
            array('curriculumid' => $curriculumid, 'semesterid' => $semesterid));
        $mycoursecomptllist = $DB->get_field('local_cc_semester_cmptl', 'bclcids',
            array('curriculumid' => $curriculumid, 'semesterid' => $semesterid, 'userid' => $USER->id));
        $mycoursecomptl = explode(',', $mycoursecomptllist);
        return array($courses, $mycoursecomptl);
    }
    /**
     * [mynextsemestercourses description]
     * @method mynextsemestercourses
     * @param  [type]             $bcsemester [description]
     * @return [type]                      [description]
     */
    public function mynextsemestercourses($bcsemester){
        list($courses, $mycoursecomptl) = $this->mycompletedsemestercourses($bcsemester);
        $notcmptlcourses = array_values(array_diff($courses, $mycoursecomptl));
        return $notcmptlcourses;
    }
    /**
     * [mycompletedveles description]
     * @method mycompletedveles
     * @param  [type]           $curriculumid [description]
     * @return [type]                       [description]
     */
    public function mysemestersandcompletedsemesters($curriculumid) {
        global $DB, $USER;
        $semesters = $DB->get_fieldset_select('local_curriculum_semesters', 'id', 'curriculumid = :curriculumid ORDER BY id ASC',
            array('curriculumid' => $curriculumid));
        $mysemestercomptllist = $DB->get_field('local_curriculum_users', 'semesterids',
            array('curriculumid' => $curriculumid, 'userid' => $USER->id));
        $mysemestercomptl = explode(',', $mysemestercomptllist);
        return array($semesters, $mysemestercomptl);

    }
    /**
     * [mynextsemesters description]
     * @method mynextsemesters
     * @param  [type]       $curriculumid [description]
     * @return [type]                   [description]
     */
    public function mynextsemesters($curriculumid) {
        global $DB, $USER;
        list($semesters, $mysemestercomptl) = $this->mysemestersandcompletedsemesters($curriculumid, $USER->id);
        $notcmptlsemesters = array_values(array_diff($semesters, $mysemestercomptl));
        return $notcmptlsemesters;
    }
    /**
     * [mynextsemesters description]
     * @method mynextsemesters
     * @param  [type]       $curriculumid [description]
     * @return [type]                   [description]
     */
    public function mynextsemesteryears($curriculumid) {
        global $DB, $USER;
    }

    /**
     * [myattendedsessions description]
     * @method myattendedsessions
     * @param  [type]             $bclcdata [description]
     * @return [type]                       [description]
     */
    /*public function myattendedsessions($bclcdata, $lastsession = false) {
        global $DB, $USER;
        $myattendedsessionssql = "SELECT bss.*, bcs.timestart, bcs.timefinish
                                    FROM {local_cc_session_signups} bss
                                    JOIN {local_cc_course_sessions} bcs ON bcs.id = bss.sessionid
                                   WHERE bss.curriculumid = :curriculumid AND bss.semesterid = :semesterid AND bss.userid = :userid
                                   AND bss.completion_status IN (1,2)
                                   AND bss.enrolstatus = :enrolstatus";
        if ($lastsession) {
            $myattendedsessionssql .= " ORDER BY bss.id DESC LIMIT 0, 1";
        }
        if ($lastsession) {
            $mylastattendedsession = $DB->get_record_sql($myattendedsessionssql, array('curriculumid' => $bclcdata->curriculumid, 'semesterid' => $bclcdata->semesterid,
                'userid' => $USER->id, 'enrolstatus' => 1));
            return $mylastattendedsession;

        } else {
            $myattendedsessions = $DB->get_records_sql($myattendedsessionssql, array('curriculumid' => $bclcdata->curriculumid, 'semesterid' => $bclcdata->semesterid,
                'userid' => $USER->id, 'enrolstatus' => 1));
            return $myattendedsessions;
        }
    }*/
    /**
     * [manage_curriculum_session_trainers description]
     * @method manage_curriculum_session_trainers
     * @param  [type]                           $bcsession [description]
     * @param  [type]                           $action    [description]
     * @return [type]                                      [description]
     */
    public function manage_curriculum_session_trainers($bcsession, $action) {
        global $DB, $USER,$CFG;
        if (file_exists($CFG->dirroot . '/local/lib.php')) {
            require_once($CFG->dirroot . '/local/lib.php');
        }
        $bcsemestercourse = $DB->get_field('local_cc_semester_courses', 'courseid',
            array('curriculumid' => $bcsession->curriculumid, 'semesterid' => $bcsession->semesterid, 'id' => $bcsession->bclcid));
        switch ($action) {
            case 'insert':
                $type = 'curriculum_enrol';
                $fromuserid = $USER->id;
                $string = 'trainer';
                $enrolbcsessionuser = $this->manage_bcsemester_course_enrolments(
                    $bcsemestercourse, $bcsession->trainerid,
                    'editingteacher', 'enrol');
                // $email_logs = emaillogs($type, $curriculumid, $bcsession->trainerid,
                //     $fromuserid, $string);
            break;
            case 'update':
            break;
            case 'delete';
                $enrolsamecoursessql = "SELECT *
                                          FROM {local_cc_course_sessions}
                                         WHERE trainerid = :oldtrainerid
                                         AND id != :sessionid";

                $enrolsamecourses = $DB->get_record_sql($enrolsamecoursessql,
                    array('oldtrainerid' => $session->oldtrainerid,
                        'sessionid' => $session->id));
                if (!empty($enrolsamecourses) || $session->oldtrainerid == $session->trainerid) {
                    continue;
                }
                $type = 'curriculum_unenroll';
                $fromuserid = $USER->id;
                $string = 'trainer';
                $enrolbcsessionuser = $this->manage_bcsemester_course_enrolments(
                    $bcsemestercourse, $bcsession->trainerid, 'editingteacher',
                    'unenrol');
                // $email_logs = emaillogs($type, $curriculumid, $bcsession->oldtrainerid,
                //     $fromuserid, $string);
            break;
            case 'all':
                $this->manage_curriculum_session_trainers($bcsession, 'insert');
                $this->manage_curriculum_session_trainers($bcsession, 'update');
                $this->manage_curriculum_session_trainers($bcsession, 'delete');
            break;
            case 'default':
            break;
        }
        return true;
    }
    public function manage_curriculum_programs($program) {
        global $DB, $USER;

        $program->description = $program->program_description['text'];
        try {
            if ($program->id > 0) {
                $program->usermodified = $USER->id;
                $program->timemodified = time();
                $parentid=$DB->update_record('local_program', $program);

               $DB->execute("UPDATE {local_program} SET admissionstartdate='$program->admissionstartdate',admissionenddate='$program->admissionenddate',validtill='$program->validtill',pre_requisites='$program->pre_requisites' WHERE parentid = $program->id");



                if($program->editabel==0){
                    $DB->execute("UPDATE {local_program} SET fullname = :fullname,shortname=:shortname,shortcode=:shortcode,year=:year,admissionstartdate = :admissionstartdate,admissionenddate=:admissionenddate,validtill=:validtill,description=:description WHERE parentid=:parentid and costcenter <> :costcenter", array('fullname' =>$program->fullname,'shortname'=>$program->shortname,'shortcode'=>$program->shortcode,'year'=>$program->year,'admissionstartdate' =>$program->admissionstartdate,'admissionenddate'=>$program->admissionenddate,'validtill'=>$program->validtill,'description'=>$program->description,'parentid'=>$program->id,'costcenter'=>$program->costcenter));
                }
                $curriculumid=$DB->get_field('local_curriculum','id',  array('program'=>$program->id));
                if($curriculumid){
                    $program->id = $curriculumid;
                    $program->costcenter = $program->costcenter;
                    $DB->update_record('local_curriculum', $program);
                }

                $params = array(
                'context' => context_system::instance(),
                'objectid' => $program->id
                );
                // Trigger curriculum updated event.

                $event = \local_curriculum\event\program_updated::create($params);
                $event->add_record_snapshot('local_program', $program);
                $event->trigger();

            } else {
                 //print_object($program);
                $program->usercreated = $USER->id;
                $program->timecreated = time();
                if($program->parentid != 0 || $program->parentid != null){
                    $program->publishstatus = 0;
                }
                $program->id = $DB->insert_record('local_program', $program);

                $params = array(
                    'context' => context_system::instance(),
                    'objectid' => $program->id
                );

                $event = \local_curriculum\event\program_created::create($params);
                $event->add_record_snapshot('local_program', $program);
                $event->trigger();
            }
        } catch (dml_exception $ex) {
            print_error($ex);
        }
        return $program->id;
    }
    public function curriculumprograms($stable,$mode=0,$filterparams=array(),$masterprogramid=0) {
        global $DB, $USER;

        $filterparams=(array)json_decode($filterparams,false);
        $params=array();
        $curriculumprograms = array();
        $concatsql = '';
        if (!empty($stable->search)) {
            $fields = array('ps.fullname','ps.shortname','ps.shortcode','ps.year');
            $fields = implode(" LIKE '%" .$stable->search. "%' OR ", $fields);
            $fields .= " LIKE '%" .$stable->search. "%' ";
            $concatsql .= " AND ($fields) ";
        }
        $countsql = "SELECT COUNT(ps.id) ";
        $fromsql = "SELECT ps.*,cc.fullname as costcenterfullname ";
        $sql = " FROM {local_program} as ps
                JOIN {local_costcenter} cc ON cc.id=ps.costcenter
                WHERE 1=1 ";
        $condition="";

        if($masterprogramid){

          $mastercostcenter=$DB->get_field('local_program','costcenter',  array('id'=>$masterprogramid));
          $sql .= " AND ps.parentid =:masterprogramid  AND ps.costcenter =:costcenter";
          $params['masterprogramid'] = $masterprogramid;
          $params['costcenter'] = $mastercostcenter;

        }else{
          if(isset($filterparams['duration'])&&$filterparams['duration']!='-1'){

             $sql .= " AND ps.duration =:duration  ";
             $params['duration'] = $filterparams['duration'];

          }if(isset($filterparams['duration_format'])&&$filterparams['duration_format']!='-1'){

             $sql .= " AND ps.duration_format =:duration_format  ";
             $params['duration_format'] = $filterparams['duration_format'];

          }if(isset($filterparams['departments'])){

            list($relateddepartmentssql, $relateddepartmentsparams) = $DB->get_in_or_equal($filterparams['departments'], SQL_PARAMS_NAMED);

            $sql .= " AND ps.costcenter $relateddepartmentssql ";

            $params = $params+$relateddepartmentsparams;

          }if(isset($filterparams['organizations'])){

              if($mode==2){

                list($relatedorganizationssql, $relatedorganizationsparams) = $DB->get_in_or_equal($filterparams['organizations'], SQL_PARAMS_NAMED);

                $subparams=array();
                $subparams = $subparams+$relatedorganizationsparams;
                $collegeids=$DB->get_records_sql_menu("SELECT id, id as collegeids FROM {local_costcenter} where parentid $relatedorganizationssql", $subparams);

                if($collegeids){

                    list($relatedcollegessql, $relatedcollegesparams) = $DB->get_in_or_equal($collegeids, SQL_PARAMS_NAMED);

                    $sql .= " AND ps.costcenter $relatedcollegessql ";

                    $params = $params+$relatedcollegesparams;

                }else{
                  $sql .= " AND ps.costcenter=0 ";
                }

              }else{
                list($relatedorganizationssql, $relatedorganizationsparams) = $DB->get_in_or_equal($filterparams['organizations'], SQL_PARAMS_NAMED);

                $sql .= " AND ps.costcenter $relatedorganizationssql ";

                $params = $params+$relatedorganizationsparams;
              }



          }if(isset($filterparams['programfaculty'])){

            list($relatedprogramfacultysql, $relatedprogramfacultyparams) = $DB->get_in_or_equal($filterparams['programfaculty'], SQL_PARAMS_NAMED);

            $sql .= " AND ps.facultyid $relatedprogramfacultysql ";

            $params = $params+$relatedprogramfacultyparams;

          }if(isset($filterparams['programlevel'])){

            list($relatedprogramprogramlevelysql, $relatedprogramprogramlevelparams) = $DB->get_in_or_equal($filterparams['programlevel'], SQL_PARAMS_NAMED);

            $sql .= " AND ps.curriculumsemester $relatedprogramprogramlevelysql ";

            $params = $params+$relatedprogramprogramlevelparams;

          }if(isset($filterparams['programs'])){

            list($relatedprogramssql, $relatedprogramsparams) = $DB->get_in_or_equal($filterparams['programs'], SQL_PARAMS_NAMED);

            $sql .= " AND ps.fullname $relatedprogramssql ";

            $params = $params+$relatedprogramsparams;


          }if(isset($filterparams['programshortcode'])){

            list($relatedprogramshortcodeysql, $relatedprogramshortcodeparams) = $DB->get_in_or_equal($filterparams['programshortcode'], SQL_PARAMS_NAMED);

            $sql .= " AND ps.shortcode $relatedprogramshortcodeysql ";

            $params = $params+$relatedprogramshortcodeparams;

          }if(isset($filterparams['programshortname'])){

            list($relatedprogramshortnameysql, $relatedprogramshortnameparams) = $DB->get_in_or_equal($filterparams['programshortname'], SQL_PARAMS_NAMED);

            $sql .= " AND ps.shortname $relatedprogramshortnameysql ";

            $params = $params+$relatedprogramshortnameparams;

          }if(isset($filterparams['programyear'])){

            list($relatedprogramyearysql, $relatedprogramyearparams) = $DB->get_in_or_equal($filterparams['programyear'], SQL_PARAMS_NAMED);

            $sql .= " AND ps.year $relatedprogramyearysql ";

            $params = $params+$relatedprogramyearparams;

          }if(isset($filterparams['admissionstartdate'])){

                $start_year=$filterparams['admissionstartdate']->year;
                $start_month=$filterparams['admissionstartdate']->month;
                $start_day=$filterparams['admissionstartdate']->day;
                $start_hour=$filterparams['admissionstartdate']->hour;
                $start_minute=$filterparams['admissionstartdate']->minute;
                $start_second=0;
                $filter_starttime_con=mktime($start_hour, $start_minute, 0, $start_month, $start_day, $start_year);
                $sql.=" AND ps.admissionstartdate >= '$filter_starttime_con' ";

          }if(isset($filterparams['admissionenddate'])){
                $start_year=$filterparams['admissionenddate']->year;
                $start_month=$filterparams['admissionenddate']->month;
                $start_day=$filterparams['admissionenddate']->day;
                $start_hour=$filterparams['admissionenddate']->hour;
                $start_minute=$filterparams['admissionenddate']->minute;
                $start_second=0;
                $filter_starttime_con=mktime($start_hour, $start_minute, 0, $start_month, $start_day, $start_year);
                $sql.=" AND ps.admissionenddate <= '$filter_starttime_con' ";

          }if(isset($filterparams['validstartdate'])){

                $start_year=$filterparams['validstartdate']->year;
                $start_month=$filterparams['validstartdate']->month;
                $start_day=$filterparams['validstartdate']->day;
                $start_hour=$filterparams['validstartdate']->hour;
                $start_minute=$filterparams['validstartdate']->minute;
                $start_second=0;
                $filter_starttime_con=mktime($start_hour, $start_minute, 0, $start_month, $start_day, $start_year);
                $sql.=" AND ps.validtill >= '$filter_starttime_con' ";

          }if(isset($filterparams['validenddate'])){

                $start_year=$filterparams['validenddate']->year;
                $start_month=$filterparams['validenddate']->month;
                $start_day=$filterparams['validenddate']->day;
                $start_hour=$filterparams['validenddate']->hour;
                $start_minute=$filterparams['validenddate']->minute;
                $start_second=0;
                $filter_starttime_con=mktime($start_hour, $start_minute, 0, $start_month, $start_day, $start_year);
                $sql.=" AND ps.validtill <= '$filter_starttime_con' ";
          }

          if (is_siteadmin() || has_capability('local/curriculum:manage_multiorganizations', context_system::instance())){
            if($mode==2){
              $sql .= " AND cc.parentid >0  ";
            }else{
              $sql .= " AND cc.parentid=0 ";
            }

          }else if (!is_siteadmin()&&!has_capability('local/curriculum:manage_multiorganizations', context_system::instance())&&(has_capability('local/curriculum:manageprogram', context_system::instance())) &&
            ((has_capability('local/curriculum:manage_ownorganization', context_system::instance())))) {
                if($mode==2){
                    /*$colleges=$DB->get_field_sql("SELECT GROUP_CONCAT(id) FROM {local_costcenter} where parentid=:parentid",  array('parentid'=>$USER->open_costcenterid),  $strictness=IGNORE_MISSING);
                    $condition.= " AND ( ps.costcenter = :parentcostcenter)";
                    $params['parentcostcenter'] = $colleges;*/// Existing code commented by Harish //

                    $colleges = $DB->get_records_sql_menu("SELECT id,id as cid FROM {local_costcenter} where parentid=:parentid",  array('parentid'=>$USER->open_costcenterid),  $strictness=IGNORE_MISSING);
                    if(!empty($colleges)){
                        list($relatedprogramyearysql, $relatedprogramyearparams) = $DB->get_in_or_equal($colleges, SQL_PARAMS_NAMED);
                        $condition.= " AND ( ps.costcenter $relatedprogramyearysql)";
                        $params = $params+$relatedprogramyearparams;
                    }else{
                        $sql .= " AND (ps.costcenter = :costcenter)";
                        $params['costcenter'] = $USER->open_costcenterid;
                    }

                    $sql .= " AND cc.parentid > 0 ";
                }else{
                    $sql .= " AND cc.parentid = 0 ";
                    $condition.= " AND (ps.costcenter = :costcenter)";
                    $params['costcenter'] = $USER->open_costcenterid;
                }
                $concatsql .= $condition;

            }elseif (!is_siteadmin()&&!has_capability('local/curriculum:manage_multiorganizations', context_system::instance())&&(has_capability('local/curriculum:manage_owndepartments', context_system::instance()))) {

                $condition.= " AND (ps.costcenter = :department )";
                $params['department'] = $USER->open_departmentid;
                $concatsql .= $condition;

            }elseif (!is_siteadmin()&&!has_capability('local/curriculum:manage_multiorganizations', context_system::instance())&&has_capability('local/curriculum:trainer_viewprogram', context_system::instance())) {
                $mycurriculums = $DB->get_records_menu('local_cc_session_trainers',
                    array('trainerid' => $USER->id), 'programid', 'programid, programid as ps');

                if (!empty($mycurriculums)) {
                    $mycurriculums = implode(', ', $mycurriculums);
                    $concatsql .= " AND ps.id IN ( $mycurriculums )";
                } else {
                    return compact('curriculums', 'curriculumscount');
                }
            }else if (!is_siteadmin() && (has_capability('local/curriculum:viewprogram', context_system::instance()))) {

              $mycurriculums = $DB->get_records_menu('local_cc_session_signups',
                  array('userid' => $USER->id), 'programid', 'programid, programid as ps');

                  if (!empty($mycurriculums)) {
                      $mycurriculums = implode(', ', $mycurriculums);
                      $concatsql .= " AND ps.id IN ( $mycurriculums ) ";
                  } else {
                      return compact('curriculums', 'curriculumscount');
                  }

          }
        }
        $sql .= $concatsql;
        try {
            $curriculumprogramscount = $DB->count_records_sql($countsql . $sql, $params);
            if ($stable->thead == false) {
                $sql .= " ORDER BY ps.id DESC";
                $curriculumprograms = $DB->get_records_sql($fromsql . $sql, $params, $stable->start, $stable->length);
            }
        } catch (dml_exception $ex) {
            $curriculumprogramscount = 0;
        }
        return compact('curriculumprograms', 'curriculumprogramscount');
    }

     /**
     * [curriculumsession_capacity_check description]
     * @param  [type] $classroomid [description]
     * @return [type]              [description]
     */
    public function session_capacity_check($curriculumid, $semesterid, $bclcid, $sessionid){
        global $DB;
        $return =false;
        $session_capacity=$DB->get_field('local_cc_course_sessions','maxcapacity',array('curriculumid'=>$curriculumid, 'semesterid' => $semesterid, 'bclcid' => $bclcid));
        $sessionenrolledusers=$DB->count_records('local_cc_session_signups',array('curriculumid'=>$curriculumid, 'semesterid' => $semesterid, 'bclcid' => $bclcid, 'sessionid' => $sessionid));
        //if($classroom_capacity <= $enrolled_users){
        //    $return =true;
        //}
        if($session_capacity <= $sessionenrolledusers && !empty($session_capacity) && $session_capacity!=0){
            $return =true;
        }
        return $return;
    }

    /**
     * [curriculum_add_assignusers description]
     * @method curriculum_add_assignusers
     * @param  [type]                    $curriculumid   [description]
     * @param  [type]                    $userstoassign [description]
     * @return [type]                                   [description]
     */
    public function session_add_assignusers($curriculumid, $semesterid, $bclcid, $sessionid, $userstoassign) {
        global $DB, $USER,$CFG;
        if (file_exists($CFG->dirroot . '/local/lib.php')) {
            require_once($CFG->dirroot . '/local/lib.php');
        }
        $allow = true;
        // $type = 'session_enrol';
        // $dataobj = $curriculumid;
        // $sessionid = $sessionid;
        // $fromuserid = $USER->id;
        if ($allow) {
            foreach ($userstoassign as $key => $adduser) {
                $session_capacity_check=$this->session_capacity_check($curriculumid, $semesterid, $bclcid, $sessionid);
                if(!$session_capacity_check){
                    $curriculumuser = new stdClass();
                    $curriculumuser->curriculumid = $curriculumid;
                    $curriculumuser->semesterid = $semesterid;
                    $curriculumuser->bclcid = $bclcid;
                    $curriculumuser->sessionid = $sessionid;
                    $curriculumuser->enrol = 1;
                    $curriculumuser->userid = $adduser;
                    try {
                        $this->bc_session_enrolments($curriculumuser);
                    }catch (dml_exception $ex) {
                        print_error($ex);
                    }
                } else {
                    break;
                }
            }
        }
        return true;
    }

    /**
     * [curriculum_add_assignusers description]
     * @method curriculum_add_assignusers
     * @param  [type]                    $curriculumid   [description]
     * @param  [type]                    $userstoassign [description]
     * @return [type]                                   [description]
     */
 /*   public function session_remove_assignusers($curriculumid, $semesterid, $bclcid, $sessionid, $userstoassign) {
        global $DB, $USER,$CFG;
        if (file_exists($CFG->dirroot . '/local/lib.php')) {
            require_once($CFG->dirroot . '/local/lib.php');
        }
        $allow = true;
        // $type = 'session_enrol';
        // $dataobj = $curriculumid;
        // $sessionid = $sessionid;
        // $fromuserid = $USER->id;
        if ($allow) {
            foreach ($userstoassign as $key => $adduser) {
                if (true) {
                    $curriculumuser = new stdClass();
                    $curriculumuser->curriculumid = $curriculumid;
                    $curriculumuser->semesterid = $semesterid;
                    $curriculumuser->bclcid = $bclcid;
                    $curriculumuser->sessionid = $sessionid;
                    $curriculumuser->enrol = 3;
                    $curriculumuser->userid = $adduser;
                    try {
                        $this->bc_session_enrolments($curriculumuser);
                    }catch (dml_exception $ex) {
                        print_error($ex);
                    }
                } else {
                    break;
                }
            }
        }
        return true;
    }*/

    /**
     * [select_to_and_from_users description]
     * @param  [type]  $type       [description]
     * @param  integer $curriculumid [description]
     * @param  [type]  $params     [description]
     * @param  integer $total      [description]
     * @param  integer $offset1    [description]
     * @param  integer $perpage    [description]
     * @param  integer $lastitem   [description]
     * @return [type]              [description]
     */
    /*public function select_to_and_from_users_sessions($type = null, $curriculumid = 0, $semesterid=0, $bclcid,  $sessionid = 0, $params, $total = 0, $offset1 = -1, $perpage = -1, $lastitem = 0) {

        global $DB, $USER;
        // $curriculumusers = $DB->get_records('local_curriculum_users', array('curriculumid' => $curriculumid));
        // $curriculumusersids = array();
        // foreach($curriculumusers as $curriculumuser){
        //   $curriculumusersids[] = $curriculumuser->userid;
        // }
        // $bcuserids = implode(',', $curriculumusersids);
        $params['suspended'] = 0;
        $params['deleted'] = 0;

        if ($total == 0) {
            $sql = "SELECT u.id, concat(u.firstname,' ',u.lastname,' ','(',u.email,')',' ','(',u.open_employeeid,')') as fullname";
        } else {
            $sql = "SELECT count(u.id) as total";
        }
        $sql .= " FROM {user} AS u
                  JOIN {local_curriculum_users} as bcu
                 WHERE  u.id > 2 AND bcu.curriculumid = $curriculumid AND u.id = bcu.userid AND u.suspended = :suspended
                                     AND u.deleted = :deleted ";
        if ($lastitem != 0) {
            $sql.=" AND u.id > $lastitem";
        }
        // if ((has_capability('local/program:manageprogram', context_system::instance())) && (!is_siteadmin() && (!has_capability('local/program:manage_multiorganizations', context_system::instance()) && !has_capability('local/costcenter:manage_multiorganizations', context_system::instance())))) {
        //     $sql .= " AND u.open_costcenterid = :costcenter";
        //     $params['costcenter'] = $USER->open_costcenterid;
        //     if ((has_capability('local/program:manage_owndepartments', context_system::instance()) || has_capability('local/costcenter:manage_owndepartments', context_system::instance()))) {
        //         $sql .= " AND u.open_departmentid = :department";
        //         $params['department'] = $USER->open_departmentid;
        //     }
        // }
        $sql .= " AND u.id <> $USER->id ";
        if (!empty($params['email'])) {
            $sql .= " AND u.id IN ({$params['email']})";
        }
        if (!empty($params['uname'])) {
            $sql .= " AND u.id IN ({$params['uname']})";
        }
        // if (!empty($params['department'])) {
        //     $sql .= " AND u.open_departmentid IN ({$params['department']})";
        // }
        // if (!empty($params['organization'])) {
        //     $sql .= " AND u.open_costcenterid IN ({$params['organization']})";
        // }
        if (!empty($params['idnumber'])) {
            $sql .= " AND u.id IN ({$params['idnumber']})";
        }

        if (!empty($params['groups'])) {
            $sql .= " AND u.id IN (select cm.userid from {cohort_members} cm, {user} u where u.id = cm.userid AND u.deleted = 0 AND u.suspended = 0 AND cm.cohortid IN ({$params['groups']}))";
        }
        if ($type == 'add') {
            $sql .= " AND u.id NOT IN (SELECT lcu.userid as userid
                                       FROM {local_cc_session_signups} AS lcu
                                       WHERE lcu.curriculumid = $curriculumid AND lcu.bclcid = $bclcid AND lcu.sessionid=$sessionid)";
              // echo $sql;exit;
        } else if ($type == 'remove') {
            $sql .= " AND u.id IN (SELECT lcu.userid as userid
                                       FROM {local_cc_session_signups} AS lcu
                                       WHERE lcu.curriculumid = $curriculumid AND lcu.sessionid = $sessionid)";
        }

        // $sql .= " AND u.id NOT IN (SELECT lcu.trainerid as userid
        //                                FROM {local_cc_session_signups} AS lcu
        //                                WHERE lcu.curriculumid = $curriculumid)";

        $order = ' ORDER BY u.id ASC ';
        if ($perpage != -1) {
            $order .= "LIMIT $perpage";
        }

        if ($total == 0) {
            $availableusers = $DB->get_records_sql_menu($sql . $order, $params);
        } else {
            $availableusers = $DB->count_records_sql($sql, $params);
        }
        // print_object($availableusers);
        return $availableusers;
    *///}
    public function curriculumsemesteryear($curriculumid, $yearid) {
        global $DB, $USER;
        $semesters = $DB->get_records('local_curriculum_semesters', array('curriculumid' => $curriculumid, 'yearid' => $yearid), '', '*, id as semesterid');
        return $semesters;

    }
    public function curriculumsemesteryearcourses($curriculumid, $yearid) {
        global $DB, $USER;
        $semestercoursess = $DB->get_records_sql('SELECT lcs.*, lcs.id as semesterid, ccsc.courseid, c.fullname
                                             FROM {local_curriculum_semesters} lcs
                                        LEFT JOIN {local_cc_semester_courses} ccsc ON ccsc.semesterid = lcs.id
                                        LEFT JOIN {course} c ON c.id = ccsc.courseid
                                        WHERE lcs.curriculumid = :curriculumid AND lcs.yearid = :yearid ', array('curriculumid' => $curriculumid, 'yearid' => $yearid));
        return $semestercoursess;

    }
    public function addfaculty($facultydata) {
      global $DB, $CFG, $USER;
            require_once($CFG->dirroot.'/local/curriculum/classes/programnotifications_emails.php');

      $pluginname = 'program';
      $enrolmethod = enrol_get_plugin($pluginname);
      $roleid = $DB->get_field('role', 'id', array('shortname' => 'faculty'));
      $instance = $DB->get_record('enrol', array('courseid' => $facultydata->courseid, 'enrol' => $pluginname), '*', MUST_EXIST);
      foreach ($facultydata->faculty as $faculty) {
        $facultydata->trainerid = $faculty;
        $this->cc_course_faculty_enrolments($facultydata);
        $this->manage_program_curriculum_course_enrolments($faculty, $roleid, 'enrol', $enrolmethod, $instance);
      }
      return true;
    }
    public function cc_course_faculty_enrolments($enroldata) {
      global $DB, $CFG, $USER;
      $studentsignup = $DB->get_record('local_cc_session_trainers', array('programid' => $enroldata->programid, 'curriculumid' => $enroldata->curriculumid, 'yearid' => $enroldata->yearid, 'semesterid' => $enroldata->semesterid, 'courseid' => $enroldata->courseid, 'trainerid' => $enroldata->trainerid));
      if (empty($studentsignup)) {
        $signupdata = new stdClass();
        $signupdata->trainerid = $enroldata->trainerid;
        $signupdata->courseid = $enroldata->courseid;
        $signupdata->semesterid = $enroldata->semesterid;
        $signupdata->yearid = $enroldata->yearid;
        $signupdata->curriculumid = $enroldata->curriculumid;
        $signupdata->programid = $enroldata->programid;
        $signupdata->feedback_id = 0;
        $signupdata->usercreated = $USER->id;
        $signupdata->timecreated = time();
        $DB->insert_record('local_cc_session_trainers', $signupdata);
        $notifications_exists = $DB->record_exists('local_notification_type', array('shortname' => 'program_cc_year_faculty_enrol'));
       /* if($notifications_exists){
          $emaillogs = new programnotifications_emails();
          $email_logs = $emaillogs->curriculum_emaillogs('program_cc_year_faculty_enrol', $signupdata, $signupdata->trainerid, $USER->id);
        }*/
      }
      return true;
    }
    public function manage_program_curriculum_course_enrolments($user, $roleid,
        $type, $enrolmethod, $instance) {
        global $DB;
        if (!empty($instance)) {
            if ($type == 'enrol') {
                $enrolmethod->enrol_user($instance, $user, $roleid, time());
            } else if ($type == 'unenrol') {
                $enrolmethod->unenrol_user($instance, $user, $roleid, time());
            }
        }
        return true;
    }
    public function addstudent($studentdata) {
      global $DB, $CFG, $USER;
     // print_object($studentdata);
      $pluginname = 'curriculum';
      $params = array();
      $semestercoursessql = 'SELECT c.id, c.id as courseid
                               FROM {course} c
                               JOIN {local_cc_semester_courses} ccsc ON ccsc.courseid = c.id
                              WHERE ccsc.yearid = :yearid ';
      $params['yearid'] = $studentdata->yearid;

      $semestercourses = $DB->get_records_sql($semestercoursessql, $params);
     // print_object($semestercourses);exit;

      $enrolmethod = enrol_get_plugin(program);
      $roleid = $DB->get_field('role', 'id', array('shortname' => 'student'));
              
      foreach ($semestercourses as $semestercourse) {
      //  print_object($semestercourse);
/*        $instance = $DB->get_record('enrol', array('courseid' => $semestercourse->courseid, 'enrol' => $pluginname), '*', MUST_EXIST);*/
           //echo 'SELECT * FROM {enrol} WHERE courseid = '.$semestercourse->id.' AND enrol = "'.$pluginname.'"';
          $instance = $DB->get_record_sql('SELECT * FROM {enrol} WHERE courseid = '.$semestercourse->id.' AND enrol = "program"');
          //print_object($instance);
        if (!empty($instance)) {
          foreach ($studentdata->students as $student) {
            $studentdata->student = $student;
            $programuser = $DB->record_exists('local_curriculum_users', array('curriculumid' => $studentdata->curriculumid, 'userid' => $student));
            if (!$programuser) {
              $programuser = $this->enrolusertoprogram($studentdata->programid, $studentdata->curriculumid, $student);
            }
            if ($programuser) {
              $this->cc_year_enrolments($studentdata);
            }
            $this->manage_program_curriculum_course_enrolments($student, $roleid, 'enrol', $enrolmethod, $instance);
          }
        }
      }
      return true;
    }
    public function cc_year_enrolments($enroldata) {
      global $DB, $CFG, $USER;
      require_once($CFG->dirroot.'/local/curriculum/classes/programnotifications_emails.php');
      $studentsignup = $DB->get_record('local_cc_session_signups', array('programid' => $enroldata->programid, 'curriculumid' => $enroldata->curriculumid, 'yearid' => $enroldata->yearid, 'userid' => $enroldata->student));
      if (empty($studentsignup)) {
        $signupdata = new stdClass();
        $signupdata->userid = $enroldata->student;
        $signupdata->yearid = $enroldata->yearid;
        $signupdata->curriculumid = $enroldata->curriculumid;
        $signupdata->programid = $enroldata->programid;
        $signupdata->supervisorid = 0;
        $signupdata->hours = 0;
        $signupdata->usercreated = $USER->id;
        $signupdata->timecreated = time();
        $DB->insert_record('local_cc_session_signups', $signupdata);

        $notifications_exists = $DB->record_exists('local_notification_type',
            array('shortname' => 'program_cc_year_enrol'));
       /* if($notifications_exists){
          $emaillogs = new programnotifications_emails();
          $email_logs = $emaillogs->curriculum_emaillogs('program_cc_year_enrol', $signupdata, $signupdata->userid, $USER->id);
        }*/
      }
      return true;
    }
    /*public function move_universityprogram_instance($programs,$url){
      global $DB, $CFG, $USER,$OUTPUT;

        $progressbar = new \core\progress\display_if_slow(get_string('copyprogramsprogress', 'local_program'));

        $transaction = $DB->start_delegated_transaction();

        $progressbar->start_html();

        $progressbar->start_progress('', count($programs));
        $reurn='';

        $data=array();
        foreach ($programs as $program => $key) {
            $row=array();
            $outreturn=$this->copy_program_instance($program,$collegeid=0,$showfeedback = true,$url,$returntwo=true);

          $localcostcenter=$DB->get_field('local_program','costcenter',array('id'=>$program));

           $collegestoassign=$DB->get_field_sql('SELECT count(id) FROM {local_program} where parentid=:programid AND costcenter <>:costcenter',  array('programid'=>$program,'costcenter'=>$localcostcenter));

            if($outreturn && $collegestoassign){
              $reurn.=$outreturn['programnotification'];
              if($outreturn['programid']){
                $newprogramid=$outreturn['programid'];
                $row[]=html_writer::empty_tag('input', array('type' => 'checkbox', 'name' => 'collegeprogram['.$program.']','value'=>$newprogramid));

                $row[]= $DB->get_field('local_program','fullname',  array('id'=>$newprogramid));

                $data[]=$row;
              }
            }
        }

      $progressbar->end_html();

      $transaction->allow_commit();

      $result=new stdClass();
      $result->changecount=count($programs);

      $returnout=$reurn;
      $returnout.=$OUTPUT->notification(get_string('copyprogramssuccess', 'local_program',$result),'success');

      if(!empty($data)){
        $table = new html_table();
        $table->data =$data;
        $table->id ='copyprogramcollege';
        $table->head = array(get_string("collegeselection", "local_program"),get_string('program', 'local_program'));

        $returnout .= html_writer::table($table);
      }else{

          $button = new \single_button($url, get_string('click_continue','local_program'), 'get', true);
          $button->class = 'continuebutton';
          $returnout.=$OUTPUT->render($button);

          echo $returnout;
          die();
      }

      return $returnout;

    }*/
    public function copy_program_instance($programid,$collegeid=0,$showfeedback = true,$url,$returntwo=false){
      global $DB, $CFG, $USER,$OUTPUT;
        $reurn='';

        $retrunprogramid=0;

        $oldprogramid=$programid;

        $conleprogram=$DB->get_record('local_program',  array('id'=>$oldprogramid),  $fields='*',  $strictness=IGNORE_MISSING);

        if($conleprogram){

            if($collegeid){
              $conleprogram->costcenter=$collegeid->id;
              $stradded = 'Added - ';
              $resultstring='affiliatcolleges';
              $title=$collegeid->fullname." College";
              $progressbartittle = get_string('affiliatcollegeprogress', 'local_program',$title);
            }else{
              $conleprogram->year=date('Y',time());
              $stradded = 'Copied - ';
              $resultstring='copyprograms';
              $title=$conleprogram->fullname;
              $progressbartittle = get_string('copyprogramprogress', 'local_program',$title);
            }

          $conleprogram->program_description['text'] = $conleprogram->description;
          $conleprogram->parentid=$conleprogram->id;
          $conleprogram->id=0;

          $newprogramid = $this->manage_curriculum_programs($conleprogram);

          if($newprogramid){
              $retrunprogramid=$newprogramid;
              if ($showfeedback) {
                $reurn.=$OUTPUT->notification($stradded.' Program <b>'.$title.'</b>', 'notifysuccess');
              }

              $conlecurriculum=$DB->get_record('local_curriculum',  array('program'=>$oldprogramid),  $fields='*',  $strictness=IGNORE_MISSING);
              if($conlecurriculum){

                  $conlecurriculum->costcenter=$conleprogram->costcenter;
                  $conlecurriculum->cr_description['text'] = $conlecurriculum->description;
                  $conlecurriculum->program=$newprogramid;
                  $oldcurriculumid=$conlecurriculum->id;
                  $conlecurriculum->id=0;
                  $newcurriculumid = $this->manage_curriculum($conlecurriculum,$copy=true);

                  if($newcurriculumid){

                    if ($showfeedback) {
                      $reurn.=$OUTPUT->notification($stradded.'<b>'.$conlecurriculum->name.'</b> curriculum in this <b>'.$title.'</b>', 'notifysuccess');
                    }

                    $conleprogramccyears=$DB->get_records('local_program_cc_years',  array('programid'=>$oldprogramid,'curriculumid'=>$oldcurriculumid),$sort='',$fields='*',  $strictness=IGNORE_MISSING);

                     if($conleprogramccyears){
                        $progressbarone = new \core\progress\display_if_slow($stradded.'Years in this <b>'.$conlecurriculum->name.'</b> curriculum and <b>'.$title.'</b>');
                        $progressbarone->start_html();
                        $progressbarone->start_progress('', count($conleprogramccyears));
                         foreach ($conleprogramccyears as $key => $conleprogramccyear) {

                            $progressbarone->increment_progress();

                            $conleprogramccyear->programid = $newprogramid;
                            $conleprogramccyear->curriculumid = $newcurriculumid;
                            $oldcurriculumyearid=$conleprogramccyear->id;
                            $conleprogramccyear->id=0;

                            $conleprogramccyear->usercreated = $USER->id;
                            $conleprogramccyear->timecreated = time();
                            $newconleprogramccyearid = $DB->insert_record('local_program_cc_years', $conleprogramccyear);

                            $params = array(
                            'context' => context_system::instance(),
                            'objectid' => $newconleprogramccyearid,
                            'other' =>array('curriculumid' => $newcurriculumid)
                            );

                            $event = \local_curriculum\event\year_created::create($params);
                            $event->add_record_snapshot('local_program_cc_years', $newconleprogramccyearid);
                            $event->trigger();

                            if($newconleprogramccyearid){

                                if ($showfeedback) {
                                    $reurn.=$OUTPUT->notification($stradded.'<b>'.$conleprogramccyear->year.'</b> year in this <b>'.$conlecurriculum->name.'</b> curriculum for this <b>'.$title.'</b>', 'notifysuccess');
                                }
;
                                $conleprogramsemesters=$DB->get_records('local_curriculum_semesters',  array('programid'=>$oldprogramid,'curriculumid'=>$oldcurriculumid,'yearid'=>$oldcurriculumyearid),$sort='', $fields='*',  $strictness=IGNORE_MISSING);

                              if($conleprogramsemesters){
                                  $progressbartwo = new \core\progress\display_if_slow($stradded.'Semesters in this <b>'.$conleprogramccyear->year.'</b> year and <b>'.$conlecurriculum->name.'</b> curriculum for this <b>'.$title.'</b>');
                                  $progressbartwo->start_html();
                                  $progressbartwo->start_progress('', count($conleprogramsemesters));

                                  foreach ($conleprogramsemesters as $key => $conleprogramsemester) {

                                      $progressbartwo->increment_progress();

                                      $conleprogramsemester->programid = $newprogramid;
                                      $conleprogramsemester->curriculumid = $newcurriculumid;
                                      $conleprogramsemester->yearid = $newconleprogramccyearid;
                                      $oldsemesterid=$conleprogramsemester->id;
                                      $conleprogramsemester->id=0;

                                     $newcurriculumsemesterid =$this->manage_curriculum_program_semesters($conleprogramsemester);

                                     if($newcurriculumsemesterid){

                                      if ($showfeedback) {
                                          $reurn.=$OUTPUT->notification($stradded.'<b>'.$conleprogramsemester->semester.'</b> semester in this <b>'.$conleprogramccyear->year.'</b> year and <b>'.$conlecurriculum->name.'</b> curriculum for this <b>'.$title.'</b>', 'notifysuccess');
                                      }

                                      $conleprogramsemestercourses=$DB->get_records('local_cc_semester_courses',  array('programid'=>$oldprogramid,'curriculumid'=>$oldcurriculumid,'yearid'=>$oldcurriculumyearid,'semesterid'=>$oldsemesterid),$sort='',  $fields='*',  $strictness=IGNORE_MISSING);

                                        if($conleprogramsemestercourses){
                                          $progressbarthree = new \core\progress\display_if_slow($stradded.'Couress in this <b>'.$conleprogramsemester->semester.'</b> semester and <b>'.$conleprogramccyear->year.'</b> year and <b>'.$conlecurriculum->name.'</b> curriculum for this <b>'.$title.'</b>');
                                          $progressbarthree->start_html();
                                          $progressbarthree->start_progress('', count($conleprogramsemestercourses));

                                          foreach ($conleprogramsemestercourses as $key => $conleprogramsemestercourse) {

                                              $progressbarthree->increment_progress();

                                              $conlesemestercourse=$DB->get_record('course',  array('id'=>$conleprogramsemestercourse->courseid),  $fields='*',  $strictness=IGNORE_MISSING);


                                              if($conlesemestercourse){


                                                $conleprogramsemestercourse->programid = $newprogramid;
                                                $conleprogramsemestercourse->curriculumid = $newcurriculumid;
                                                $conleprogramsemestercourse->yearid = $newconleprogramccyearid;
                                                $conleprogramsemestercourse->semesterid = $newcurriculumsemesterid;
                                                $oldcourseid=$conleprogramsemestercourse->courseid;

                                                $conlesemestercourse->shortname=$conlesemestercourse->shortname.'_'.$newprogramid;
                                                $conlesemestercourse->id=0;
                                                $conlesemestercourse->open_parentcourseid=$oldcourseid;

                                                $courseid=create_course($conlesemestercourse);
                                                insert::add_enrol_meathod_tocourse($courseid,1);
                                                $conleprogramsemestercourse->courseid = $courseid->id;

                                                $conleprogramsemestercourse->timecreated = time();
                                                $conleprogramsemestercourse->usercreated = $USER->id;

                                                $oldsemestercourseid=$conleprogramsemestercourse->id;
                                                $conleprogramsemestercourse->id=0;
                                                $conleprogramsemestercourse->open_parentcourseid = $oldcourseid;
                                                $conleprogramsemestercourse->importstatus=0;

                                                $newprogramsemestercourseid = $DB->insert_record('local_cc_semester_courses',
                                                    $conleprogramsemestercourse);
                                                $params = array(
                                                    'context' => context_system::instance(),
                                                    'objectid' => $newprogramsemestercourseid,
                                                    'other' => array('curriculumid' => $conleprogramsemestercourse->curriculumid,
                                                                     'semesterid' => $conleprogramsemestercourse->semesterid,
                                                                     'yearid' => $conleprogramsemestercourse->yearid)
                                                );

                                                $event = \local_curriculum\event\bcsemestercourse_created::create($params);
                                                $event->add_record_snapshot('local_cc_semester_courses', $conleprogramsemestercourse);
                                                $event->trigger();

                                                $this->manage_curriculum_semester_completions($conleprogramsemestercourse->curriculumid, $conleprogramsemestercourse->semesterid);

                                                if($newprogramsemestercourseid && !$collegeid){

                                                    if ($showfeedback) {
                                                        $reurn.=$OUTPUT->notification($stradded.'<b>'.$conlesemestercourse->fullname.'</b> course  in this <b>'.$conleprogramsemester->semester.'</b> semester and <b>'.$conleprogramccyear->year.'</b> year and <b>'.$conlecurriculum->name.'</b> curriculum for this <b>'.$title.'</b>', 'notifysuccess');
                                                    }
                                                    $conlecoursefaculties=$DB->get_records('local_cc_session_trainers',  array('programid'=>$oldprogramid,'curriculumid'=>$oldcurriculumid,'yearid'=>$oldcurriculumyearid,'semesterid'=>$oldsemesterid,'courseid'=>$oldcourseid),$sort='',  $fields='*',  $strictness=IGNORE_MISSING);
                                                   $progressbarfour = new \core\progress\display_if_slow($stradded.'faculties  in this <b>'.$conlesemestercourse->fullname.'</b> course and <b>'.$conleprogramsemester->semester.'</b> semester and <b>'.$conleprogramccyear->year.'</b> year and <b>'.$conlecurriculum->name.'</b> curriculum for this <b>'.$title.'</b>');
                                                    $progressbarfour->start_html();
                                                    $progressbarfour->start_progress('', count($conlecoursefaculties));

                                                    foreach ($conlecoursefaculties as $key => $conlecoursefaculty) {

                                                        $progressbarfour->increment_progress();

                                                        $facultyexist=$DB->get_field('user','firstname',array('id'=>$conlecoursefaculty->trainerid,'suspended'=>0,'deleted'=>0));
                                                        if($facultyexist){
                                                          $conlecoursefaculty->programid = $newprogramid;
                                                          $conlecoursefaculty->curriculumid = $newcurriculumid;
                                                          $conlecoursefaculty->yearid = $newconleprogramccyearid;
                                                          $conlecoursefaculty->semesterid = $newcurriculumsemesterid;
                                                          $conlecoursefaculty->courseid = $conleprogramsemestercourse->courseid;
                                                          $conlecoursefaculty->id=0;
                                                          $conlecoursefaculty->faculty=array($conlecoursefaculty->trainerid);
                                                          $this->addfaculty($conlecoursefaculty);
                                                        if ($showfeedback) {
                                                          $reurn.=$OUTPUT->notification($stradded.'<b> '.$facultyexist.'</b> faculty  in this <b>'.$conlesemestercourse->fullname.'</b> course  and <b>'.$conleprogramsemester->semester.'</b> semester and <b>'.$conleprogramccyear->year.'</b> year and <b>'.$conlecurriculum->name.'</b> curriculum for this <b>'.$title.'</b>', 'notifysuccess');
                                                        }

                                                      }
                                                    }
                                                    // $progressbarfour->end_progress();
                                                    $progressbarfour->end_html();
                                                }
                                              }
                                          }
                                            $totalcourses = $DB->count_records('local_cc_semester_courses',
                                            array('curriculumid' => $newcurriculumid, 'semesterid' =>  $newcurriculumsemesterid, 'yearid' => $newconleprogramccyearid));

                                            $semesterdata = new stdClass();
                                            $semesterdata->id =  $newcurriculumsemesterid;
                                            $semesterdata->programid = $newprogramid;
                                            $semesterdata->curriculumid = $newcurriculumid;
                                            $semesterdata->totalcourses = $totalcourses;
                                            $semesterdata->timemodified = time();
                                            $semesterdata->usermodified = $USER->id;
                                            $DB->update_record('local_curriculum_semesters', $semesterdata);
                                            $totalbccourses = $DB->count_records('local_cc_semester_courses',
                                            array('curriculumid' => $newcurriculumid));
                                            $curriculumdata = new stdClass();
                                            $curriculumdata->programid = $newprogramid;
                                            $curriculumdata->id = $newcurriculumid;
                                            $curriculumdata->totalcourses = $totalbccourses;
                                            $curriculumdata->timemodified = time();
                                            $curriculumdata->usermodified = $USER->id;
                                            $DB->update_record('local_curriculum', $curriculumdata);
                                          // $progressbarthree->end_progress();
                                          $progressbarthree->end_html();
                                        }
                                     }
                                  }
                                // $progressbartwo->end_progress();
                                $progressbartwo->end_html();
                              }
                            }

                         }
                         // $progressbarone->end_progress();
                         $progressbarone->end_html();
                    }
                  }
              }
          }
        }
      if($returntwo){
        return array('programnotification'=>$reurn,'programid'=>$retrunprogramid);
      }else{
        return $reurn;
      }
    }
    /*public function uncopy_program_instance($programid,$showfeedback = true,$progressbar=true,$removecollege=null){
      global $DB, $CFG, $USER,$OUTPUT;

      $reurn='';

      if($removecollege){

        $stratdeleted="Removed -";

        $programfullname=$removecollege->fullname;
      }else{
        $stratdeleted="Deleted ";

        $programfullname=$DB->get_field('local_program','fullname',array('id'=>$programid));
      }

      if($programfullname){

          $localccsessionsignups=$DB->record_exists('local_cc_session_signups',array('programid'=>$programid));
          if($localccsessionsignups){

                $signups=$DB->delete_records('local_cc_session_signups',array('programid'=>$programid));
              if ($showfeedback&&$signups) {
                $reurn.=$OUTPUT->notification($stratdeleted.' Students  for this <b>'.$programfullname.'</b>', 'notifysuccess');
              }
          }

          $localccsessiontrainers=$DB->record_exists('local_cc_session_trainers',array('programid'=>$programid));
          if($localccsessiontrainers){

              $trainers=$DB->delete_records('local_cc_session_trainers',array('programid'=>$programid));

              if ($showfeedback&&$trainers) {
                $reurn.=$OUTPUT->notification($stratdeleted.' Faculties  for this <b>'.$programfullname.'</b>', 'notifysuccess');
              }
          }

          $courses=$DB->get_records('local_cc_semester_courses',  array('programid'=>$programid),  $sort='',  $fields='id,courseid');

          if($courses){
             $progressbarone = new \core\progress\display_if_slow($stratdeleted.' Courses unassign is in process...');
            $progressbarone->start_html();

             $progressbarone->start_progress('', count($courses));
              foreach ($courses as $key => $course) {

                $localcourse=$DB->get_field('course','fullname',array('id'=>$course->courseid));
                $programcourses[] = $localcourse;// Added by harish for displaying courses(IUMS-376) in comma separated while un affiliating college from program //
                if($localcourse){

                    if($progressbar){
                        $progressbarone->increment_progress();
                    }

                    delete_course($course->courseid,false);

                    $localccsemestercourses=$DB->record_exists('local_cc_semester_courses',array('id'=>$course->id));
                    if($localccsemestercourses){
                        $semestercourses=$DB->delete_records('local_cc_semester_courses',array('id'=>$course->id));
                    }
                }

              }
              // Added by harish for displaying courses(IUMS-376) in comma separated while un affiliating college from program starts here//
              if($programcourses){
                  $programcourses = implode(' , ', $programcourses);
              }else{
                  $programcourses = $localcourse;
              }
              // Added by harish for displaying courses(IUMS-376) in comma separated while un affiliating college from program ends here//
              $progressbarone->end_html();

              if ($showfeedback&&$courses) {
                $reurn.=$OUTPUT->notification($stratdeleted.' <b>'.$programcourses.'</b> courses  for this <b>'.$programfullname.'</b>', 'notifysuccess');
              }
          }
          $localcurriculumsemesters=$DB->record_exists('local_curriculum_semesters',array('programid'=>$programid));
          if($localcurriculumsemesters){
              $semesters=$DB->delete_records('local_curriculum_semesters',array('programid'=>$programid));
              if ($showfeedback&&$semesters) {
                $reurn.=$OUTPUT->notification($stratdeleted.' Semesters  for this <b>'.$programfullname.'</b>', 'notifysuccess');
              }
          }

          $localprogramccyears=$DB->record_exists('local_program_cc_years',array('programid'=>$programid));
          if($localprogramccyears){

              $ccyears=$DB->delete_records('local_program_cc_years',array('programid'=>$programid));

              if ($showfeedback&&$ccyears) {
                $reurn.=$OUTPUT->notification($stratdeleted.' Years  for this <b>'.$programfullname.'</b>', 'notifysuccess');
              }
          }

          $localcurriculum=$DB->record_exists('local_curriculum',array('program'=>$programid));
          if($localcurriculum){
              $curriculum=$DB->delete_records('local_curriculum',array('program'=>$programid));
              if ($showfeedback&&$curriculum) {
                $reurn.=$OUTPUT->notification($stratdeleted.' Curriculum  for this <b>'.$programfullname.'</b>', 'notifysuccess');
              }
          }
          $localprogram=$DB->record_exists('local_program',array('id'=>$programid));
          if($localprogram){

              $program=$DB->delete_records('local_program',array('id'=>$programid));
              if ($showfeedback&&$program) {
                $reurn .= $OUTPUT->notification($stratdeleted.' <b>'.$programfullname.'</b> Program', 'notifysuccess');
              }
          }
    }
     return $reurn;
    }*/
 /*   public function addyearcost($yearcost) {
      global $DB, $USER;
      $costexists = $DB->get_record('local_program_cc_year_cost', array('yearid' => $yearcost->yearid, 'curriculumid' => $yearcost->curriculumid, 'programid' => $yearcost->programid));
      if (empty($costexists)) {
        $yearcost->usercreated = $USER->id;
        $yearcost->timecreated = time();
        $return = $DB->insert_record('local_program_cc_year_cost', $yearcost);
      } else {
        $costexists->cost = $yearcost->cost;
        $costexists->usermodified = $USER->id;
        $costexists->timemodified = time();
        $DB->update_record('local_program_cc_year_cost', $costexists);
        $return = $costexists->id;
      }
      return $return;
    }*/
    /*public function maximum_programsections($programs,$typemode='add')
    {
      global $DB, $USER;
      if($typemode=='delete'){

        $sql="SELECT (SELECT count(id) FROM {local_cc_semester_courses} where programid in(:programidd)) as courses FROM {local_program} as pr LIMIT 1";

      }else{

        $sql="SELECT (SELECT count(id) FROM {local_curriculum} where program in(:programida)) as curriculum,(SELECT count(id) FROM {local_program_cc_years} where programid in(:programidb)) as years,(SELECT count(id) FROM {local_curriculum_semesters} where programid in(:programidc)) as semesters,(SELECT count(id) FROM {local_cc_semester_courses} where programid in(:programidd)) as courses,(SELECT count(id) FROM {local_cc_session_trainers} where programid in(:programide)) as trainers FROM {local_program} as pr LIMIT 1";
      }

        if(is_array($programs)){
            $programs=implode(',', $programs);
        }
        $params=array('programida'=>$programs,'programidb'=>$programs,'programidc'=>$programs,'programidd'=>$programs,'programide'=>$programs);

        $maximumprogramsections=$DB->get_record_sql($sql,$params);

        if($maximumprogramsections&&$typemode=='add'){
          $arrayobject=array_flip((array)$maximumprogramsections);
          krsort($arrayobject);

          $currentvalue=current($arrayobject);
          $currentvaluekey=array_search($currentvalue,$arrayobject);

          $return=array($currentvalue,$currentvaluekey);
        }elseif($maximumprogramsections&&$typemode=='delete'){
            $return=array($maximumprogramsections->courses,'courses');
        }else{
          $return=false;
        }
        return $return;
    }*/
    /**
     * [curriculumusers description]
     * @method curriculumusers
     * @param  [type]         $curriculumid [description]
     * @param  [type]         $stable      [description]
     * @return [type]                      [description]
     */
    public function coursefaculty($yearid, $semesterid, $courseid, $stable) {
        global $DB, $USER;
        $params = array();
        $coursefaculty = array();
        $concatsql = '';
        if (!empty($stable->search['value'])) {
            $fields = array(0 => 'u.firstname',
                            1 => 'u.lastname',
                            2 => 'u.email',
                            3 => 'u.idnumber'
                            );
                $fields = implode(" LIKE '%" .$stable->search. "%' OR ", $fields);
                $fields .= " LIKE '%" .$stable->search. "%' ";
                $concatsql .= " AND ($fields) ";
        }
        $countsql = "SELECT COUNT(u.id) ";
        $fromsql = "SELECT u.*, ccst.trainerid, ccst.yearid, ccst.semesterid, ccst.courseid ";
        $sql = " FROM {user} AS u
                 JOIN {local_cc_session_trainers} AS ccst ON ccst.trainerid = u.id
                WHERE ccst.yearid = ".$yearid." AND ccst.semesterid = ".$semesterid." AND ccst.courseid = ".$courseid." AND u.confirmed = 1 AND u.suspended = 0 AND u.deleted = 0 AND u.id > 2";
        $sql .= $concatsql;
        $params['yearid'] = $yearid;
        $params['semesterid'] = $semesterid;
        $params['courseid'] = $courseid;
        try {
            $coursefacultycount = $DB->count_records_sql($countsql . $sql, $params);
            if ($stable->thead == false) {
                $sql .= " ORDER BY u.id ASC";
                
                $coursefaculty = $DB->get_records_sql($fromsql . $sql);
            }
        } catch (dml_exception $ex) {
            $coursefacultycount = 0;
        } 
        return compact('coursefaculty', 'coursefacultycount');
    }
    public function cc_course_faculty_unenrol($facultydata) {
      global $DB, $CFG, $USER;
      $trainerexist = $DB->get_record('local_cc_session_trainers', array('trainerid' => $facultydata->trainerid, 'courseid' => $facultydata->courseid, 'semesterid' => $facultydata->semesterid, 'yearid' => $facultydata->yearid));
      if (empty($trainerexist)) {
        print_error('trainer not found!');
      } else {
        $DB->delete_records('local_cc_session_trainers', array('trainerid' => $facultydata->trainerid, 'courseid' => $facultydata->courseid, 'semesterid' => $facultydata->semesterid, 'yearid' => $facultydata->yearid));
      }
      return true;
    }
    public function unassignfaculty($facultydata) {
      global $DB, $CFG, $USER;
      $pluginname = 'program';
      $enrolmethod = enrol_get_plugin($pluginname);
      $roleid = $DB->get_field('role', 'id', array('shortname' => 'faculty'));
      $instance = $DB->get_record('enrol', array('courseid' => $facultydata->courseid, 'enrol' => $pluginname), '*', MUST_EXIST);
      if ($this->manage_program_curriculum_course_enrolments($facultydata->trainerid, $roleid, 'unenrol', $enrolmethod, $instance)) {
        $this->cc_course_faculty_unenrol($facultydata);
      }
      return true;
    }
  /*public function enrolusertoprogram($programid, $curriculumid, $userid) {
    global $DB, $USER, $CFG;
    $curriculumuser = new stdClass();
    $curriculumuser->programid = $programid;
    $curriculumuser->curriculumid = $curriculumid;
    $curriculumuser->courseid = 0;
    $curriculumuser->userid = $userid;
    $curriculumuser->supervisorid = 0;
    $curriculumuser->prefeedback = 0;
    $curriculumuser->postfeedback = 0;
    $curriculumuser->trainingfeedback = 0;
    $curriculumuser->confirmation = 0;
    $curriculumuser->attended_sessions = 0;
    $curriculumuser->hours = 0;
    $curriculumuser->completion_status = 0;
    $curriculumuser->completiondate = 0;
    $curriculumuser->usercreated = $USER->id;
    $curriculumuser->timecreated = time();
    $curriculumuser->usermodified = $USER->id;
    $curriculumuser->timemodified = time();
    try {
      $curriculumuser->id = $DB->insert_record('local_curriculum_users',
                            $curriculumuser);
      $local_curriculum = $DB->get_record_sql("SELECT * FROM {local_curriculum} where id = $curriculumid");

      $params = array(
        'context' => context_system::instance(),
        'objectid' => $curriculumuser->id,
        'other' => array('curriculumid' => $curriculumid)
      );

      $event = \local_program\event\program_users_enrol::create($params);
      $event->add_record_snapshot('local_curriculum_users', $curriculumuser);
      $event->trigger();

      if ($local_curriculum->status == 0) {
        // $email_logs = $emaillogs->curriculum_emaillogs($type, $dataobj, $curriculumuser->userid, $fromuserid);
      }
    } catch (dml_exception $ex) {
      print_error($ex);
    }
    return true;
  }*/
    public function programtemplatestatus($programid) {
        global $DB, $CFG, $USER;
        $program = $DB->get_record('local_program', array('id' => $programid));

        $checkcostcentersql = 'SELECT id, parentid
                                 FROM {local_costcenter}
                                WHERE id = :costcenter ';
        $checkcostcenter = $DB->get_record_sql($checkcostcentersql, array('costcenter' => $program->costcenter));

        $checkparent = $DB->count_records_sql('SELECT COUNT(id) FROM {local_program} WHERE parentid = :parentid',  array('parentid' => $program->id));

        $affiliatecolleges = $DB->count_records_sql('SELECT COUNT(id) FROM {local_program} WHERE parentid = :parentid AND costcenter <> :costcenter',  array('parentid' => $program->id, 'costcenter' => $program->costcenter));

        $copiedprograms = $DB->count_records_sql("SELECT COUNT(id) FROM {local_program} WHERE parentid = :parentid AND costcenter = :costcenter",  array('parentid' => $program->id, 'costcenter' => $program->costcenter));
        
        if ($checkcostcenter->parentid == 0/* && $affiliatecolleges == 0*/) {
            return true;
        } else if ($program->parentid == 0 && $checkparent > 0 ) {
            return false;
        } else {
            return false;
        }

    }

    public function checkcopyprogram($programid) {
        global $DB;
        $currentprogramsql = 'SELECT p.id, p.costcenter, p.parentid
                FROM {local_program} p
                WHERE p.id = :programid ';
        $currentprogram = $DB->get_record_sql($currentprogramsql, array('programid' => $programid));
        $programsql = 'SELECT pp.id, pp.costcenter, pp.parentid
                FROM {local_program} pp
                WHERE pp.id = :programid ';
        $program = $DB->get_record_sql($programsql, array('programid' => $currentprogram->parentid));

        if ($currentprogram->parentid > 0 &&($currentprogram->costcenter != $program->costcenter)) {
            return false;
        }
        return true;
    }

    public function deletesemonlinecourses($semesterid = null, $curriculumid = null, $yearid = null){
        global $DB, $CFG;
        require_once($CFG->dirroot. '/course/lib.php');
        $params = array();
        $onlinecourses_sql = "SELECT courseid as id, courseid as courseid
                                FROM {local_cc_semester_courses}
                               WHERE 1 = 1";
        if($semesterid){
            $onlinecourses_sql .= " AND semesterid = :semester";
            $params['semester'] = $semesterid;
        }
        if($yearid){
            $onlinecourses_sql .= " AND yearid = :year";
            $params['year'] = $yearid;
        }
        if($curriculumid){
            $onlinecourses_sql .= " AND curriculumid = :curriculum";
        }
            $params['curriculum'] = $curriculumid;

        $onlinecourseslist = $DB->get_records_sql_menu($onlinecourses_sql, $params);
        if($onlinecourseslist){
            foreach($onlinecourseslist as $key => $value){
                \core_php_time_limit::raise();
                // We do this here because it spits out feedback as it goes.
                delete_course($value, false);
                // Update course count in categories.
                fix_course_sortorder();
            }
        }
        return true;
    }
    public function enrolusertoprogram($programid, $curriculumid, $userid) {
    global $DB, $USER, $CFG;
    $curriculumuser = new stdClass();
    $curriculumuser->programid = $programid;
    $curriculumuser->curriculumid = $curriculumid;
    $curriculumuser->courseid = 0;
    $curriculumuser->userid = $userid;
    $curriculumuser->supervisorid = 0;
    $curriculumuser->prefeedback = 0;
    $curriculumuser->postfeedback = 0;
    $curriculumuser->trainingfeedback = 0;
    $curriculumuser->confirmation = 0;
    $curriculumuser->attended_sessions = 0;
    $curriculumuser->hours = 0;
    $curriculumuser->completion_status = 0;
    $curriculumuser->completiondate = 0;
    $curriculumuser->usercreated = $USER->id;
    $curriculumuser->timecreated = time();
    $curriculumuser->usermodified = $USER->id;
    $curriculumuser->timemodified = time();
    try {
      $curriculumuser->id = $DB->insert_record('local_curriculum_users',
                            $curriculumuser);
      $local_curriculum = $DB->get_record_sql("SELECT * FROM {local_curriculum} where id = $curriculumid");

      $params = array(
        'context' => context_system::instance(),
        'objectid' => $curriculumuser->id,
        'other' => array('curriculumid' => $curriculumid)
      );

      $event = \local_program\event\program_users_enrol::create($params);
      $event->add_record_snapshot('local_curriculum_users', $curriculumuser);
      $event->trigger();

      if ($local_curriculum->status == 0) {
        // $email_logs = $emaillogs->curriculum_emaillogs($type, $dataobj, $curriculumuser->userid, $fromuserid);
      }
    } catch (dml_exception $ex) {
      print_error($ex);
    }
    return true;
  }
}

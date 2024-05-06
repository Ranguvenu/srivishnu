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
 * External curriculums API
 *
 * @package    local_curriculum
 * @category   external
 * @copyright  2018 Arun Kumar M <arun@eabyas.in>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();
global $PAGE, $OUTPUT;

require_once("$CFG->libdir/externallib.php");
require_once($CFG->dirroot . '/local/program/lib.php');
use \local_program\program as program;
use \local_program\form\program_form as program_form;

class local_program_external extends external_api {

    public static function curriculum_instance_parameters() {
        return new external_function_parameters(
            array(
                'id' => new external_value(PARAM_INT, 'ID', 0),
                'contextid' => new external_value(PARAM_INT, 'The context id', false),
                'form_status' => new external_value(PARAM_INT, 'Form position', 0),
                'jsonformdata' => new external_value(PARAM_RAW, 'Submitted Form Data', false),
            )
        );
    }

    public static function curriculum_instance($id, $contextid, $form_status, $jsonformdata) {
        global $PAGE, $DB, $CFG, $USER;
        $context = context::instance_by_id($contextid, MUST_EXIST);
        self::validate_context($context);
        $serialiseddata = json_decode($jsonformdata);
        $data = array();
        parse_str($serialiseddata, $data);

        $warnings = array();

        $curriculum = new stdClass();

        // The last param is the ajax submitted data.
        $mform = new program_form(null, array('form_status' => $form_status), 'post', '', null, true, $data);
        $validateddata = $mform->get_data();
        if ($validateddata) {
            // Do the action.
            $curriculumid = (new program)->manage_curriculum($validateddata);
            if ($curriculumid > 0) {
                $form_status = -2;
                $error = false;
            } else {
                $form_status = -2;
                $error = true;
            }
        } else {
            // Generate a warning.
            throw new moodle_exception('missingcurriculum', 'local_program');
        }
        $return = array(
            'id' => $curriculumid,
            'form_status' => $form_status);
        return $return;

    }

    public static function curriculum_instance_returns() {
        return new external_single_structure(array(
            'id' => new external_value(PARAM_INT, 'Context id for the framework'),
            'form_status' => new external_value(PARAM_INT, 'form_status'),
        ));
    }

    public static function delete_curriculum_instance_parameters() {
        return new external_function_parameters(
            array(
                'action' => new external_value(PARAM_ACTION, 'Action of the event', false),
                'id' => new external_value(PARAM_INT, 'ID of the record', 0),
                 'curriculumid' => new external_value(PARAM_INT, 'ID of the record', 0),
                'confirm' => new external_value(PARAM_BOOL, 'Confirm', false),
                'curriculumname' => new external_value(PARAM_RAW, 'Action of the event', false),
            )
        );
    }

    public static function delete_curriculum_instance($action, $id, $confirm,$curriculumname) {
        global $DB;
        try {
            $deletesemcourses = (new program)->deletesemonlinecourses('', $id, '');
            $DB->delete_records('local_cc_semester_courses', array('curriculumid' => $id));
            $DB->delete_records('local_program_cc_years', array('curriculumid' => $id));
            $DB->delete_records('local_curriculum_semesters', array('curriculumid' => $id));

            $DB->delete_records('local_cc_course_sessions', array('curriculumid' => $id));

            $DB->delete_records('local_curriculum_users', array('curriculumid' => $id));
            $DB->delete_records('local_curriculum_trainers', array('curriculumid' => $id));
            $DB->delete_records('local_curriculum_trainerfb', array('curriculumid' => $id));

            // delete events in calendar
            $DB->delete_records('event', array('plugin_instance'=>$id, 'plugin'=>'local_curriculum')); // added by sreenivas
            $params = array(
                    'context' => context_system::instance(),
                    'objectid' =>$id
            );

            $event = \local_program\event\curriculum_deleted::create($params);
            $event->add_record_snapshot('local_program', $id);
            $event->trigger();
            $DB->delete_records('local_curriculum', array('id' => $id));
            $return = true;
        } catch (dml_exception $ex) {
            print_error('deleteerror', 'local_program');
            $return = false;
        }
        return $return;
    }
    public static function delete_curriculum_instance_returns() {
        return new external_value(PARAM_BOOL, 'return');
    }

    public static function program_course_selector_parameters() {
        $query = new external_value(
            PARAM_RAW,
            'Query string'
        );
        $includes = new external_value(
            PARAM_ALPHA,
            'What other contexts to fetch the frameworks from. (all, parents, self)',
            VALUE_DEFAULT,
            'parents'
        );
        return new external_function_parameters(array(
            'query' => $query,
            'context' => self::get_context_parameters(),
            'includes' => $includes
        ));
    }

    public static function program_course_selector($query, $context, $includes = 'parents') {
        global $CFG, $DB, $USER;
        $params = self::validate_parameters(self::program_course_selector_parameters(), array(
            'query' => $query,
            'context' => $context,
            'includes' => $includes
        ));
        $query = $params['query'];
        $includes = $params['includes'];
        $context = self::get_context_from_params($params['context']);

        self::validate_context($context);
        $courses = array();
        if ($query) {
            $queryparams = array();
            $concatsql = '';
            if ((has_capability('local/program:manageprogram', context_system::instance())) && ( !is_siteadmin() && (!has_capability('local/program:manage_multiorganizations', context_system::instance()) && !has_capability('local/costcenter:manage_multiorganizations', context_system::instance())))) {
                $concatsql .= " AND open_costcenterid = :costcenterid";
                $queryparams['costcenterid'] = $USER->open_costcenterid;
                if ((has_capability('local/program:manage_owndepartments', context_system::instance())|| has_capability('local/costcenter:manage_owndepartments', context_system::instance()))) {
                     $concatsql .= " AND open_departmentid = :department";
                     $queryparams['department'] = $USER->open_departmentid;
                 }
           }
            $cousresql = "SELECT c.id, c.fullname
                           FROM {course} AS c
                           JOIN {enrol} AS en on en.courseid = c.id AND en.enrol = 'curriculum' and en.status = 0
                          WHERE c.visible = 1 AND FIND_IN_SET(5, c.open_identifiedas) AND c.fullname LIKE '%$query%' AND c.id <> " . SITEID . " $concatsql";
            $courses = $DB->get_records_sql($cousresql, $queryparams);
        }

        return array('courses' => $courses);
    }
    public static function program_course_selector_returns() {
        return new external_single_structure(array(
            'courses' => new external_multiple_structure(
                new external_single_structure(array(
                    'id' => new external_value(PARAM_INT, 'ID of the course'),
                    'fullname' => new external_value(PARAM_RAW, 'course fullname'),
                ))
            ),
        ));
    }
    public static function delete_session_instance_parameters() {
        return new external_function_parameters(
            array(
                'action' => new external_value(PARAM_ACTION, 'Action of the event', false),
                'id' => new external_value(PARAM_INT, 'ID of the record', 0),
                'curriculumid' => new external_value(PARAM_INT, 'ID of the record', 0),
                'semesterid' => new external_value(PARAM_INT, 'ID of the record', 0),
                'bclcid' => new external_value(PARAM_INT, 'ID of the record', 0),
                'confirm' => new external_value(PARAM_BOOL, 'Confirm', false),
            )
        );
    }

    public static function delete_session_instance($action, $id, $curriculumid, $semesterid, $bclcid, $confirm) {
        global $DB, $USER;
        try {
            if ($confirm) {
                $params = array(
                    'context' => context_system::instance(),
                    'objectid' =>$id
                );

                $event = \local_program\event\session_deleted::create($params);
                $event->add_record_snapshot('local_cc_course_sessions', $id);
                $event->trigger();

                $DB->delete_records('local_cc_course_sessions', array('id' => $id));
                $DB->delete_records('local_cc_session_signups', array('sessionid' => $id, 'curriculumid' => $curriculumid, 'semesterid'=>$semesterid, 'bclcid' => $bclcid));
                $return = true;
            } else {
                $return = false;
            }
        } catch (dml_exception $ex) {
            print_error('deleteerror', 'local_program');
            $return = false;
        }
        return $return;
    }

    public static function delete_session_instance_returns() {
        return new external_value(PARAM_BOOL, 'return');
    }

    public static function delete_offlineclassroom_instance_parameters() {
        return new external_function_parameters(
            array(
                'action' => new external_value(PARAM_ACTION, 'Action of the event', false),
                'id' => new external_value(PARAM_INT, 'ID of the record', 0),
                'curriculumid' => new external_value(PARAM_INT, 'ID of the record', 0),
                'semesterid' => new external_value(PARAM_INT, 'ID of the record', 0),
                'yearid' => new external_value(PARAM_INT, 'ID of the record', 0),
                'programid' => new external_value(PARAM_INT, 'ID of the record', 0),
                'classname' => new external_value(PARAM_RAW, 'ID of the record', 0),
                'confirm' => new external_value(PARAM_BOOL, 'Confirm', false),
                'count' => new external_value(PARAM_INT, 'Count of the records', 0),
            )
        );
    }

    public static function delete_offlineclassroom_instance($action, $id, $curriculumid, $semesterid, $yearid, $programid, $classname, $confirm, $count) {
        global $DB, $USER;
        try {
            if ($confirm) {
                $params = array(
                    'context' => context_system::instance(),
                    'objectid' =>$id
                );

                /*$event = \local_program\event\classroom_deleted::create($params);
                $event->add_record_snapshot('local_cc_semester_classrooms', $id);
                $event->trigger();*/

                $DB->delete_records('local_cc_semester_classrooms', array('id' => $id, 'curriculumid' => $curriculumid, 'yearid' => $yearid, 'semesterid' => $semesterid));
                $DB->delete_records('local_cc_course_sessions', array('bclcid' => $id, 'curriculumid' => $curriculumid, 'yearid' => $yearid, 'semesterid' => $semesterid, 'programid' => $programid));
                $return = true;
            } else {
                $return = false;
            }
        } catch (dml_exception $ex) {
            print_error('deleteerror', 'local_program');
            $return = false;
        }
        return $return;
    }

    public static function delete_offlineclassroom_instance_returns() {
        return new external_value(PARAM_BOOL, 'return');
    }

    public static function program_form_option_selector_parameters() {
        $query = new external_value(
            PARAM_RAW,
            'Query string'
        );
        $action = new external_value(
            PARAM_RAW,
            'Action for the curriculum form selector'
        );
        $options = new external_value(
            PARAM_RAW,
            'Action for the curriculum form selector'
        );

        return new external_function_parameters(array(
            'query' => $query,
            'context' => self::get_context_parameters(),
            'action' => $action,
            'options' => $options
        ));
    }

    public static function program_form_option_selector($query, $context, $action, $options) {
        global $CFG, $DB, $USER;
        $params = self::validate_parameters(self::program_form_option_selector_parameters(), array(
            'query' => $query,
            'context' => $context,
            'action' => $action,
            'options' => $options
        ));
        $query = $params['query'];
        $action = $params['action'];
        $context = self::get_context_from_params($params['context']);
        $options = $params['options'];
        if (!empty($options)) {
            $formoptions = json_decode($options);
        }
        self::validate_context($context);
        if ($query && $action) {
            $querieslib = new \local_program\local\querylib();
            $return = array();
            switch($action) {
                case 'program_trainer_selector':
                    $parent = array();
                    if ($formoptions->parnetid > 0) {
                        $parent = array($formoptions->parnetid);
                    }
                    $return = $querieslib->get_user_department_trainerslist(true, $parent, array(),
                        $query);
                break;
                case 'program_institute_selector':
                    $service = array();
                    $service['curriculumid'] = $formoptions->id;
                    $service['query'] = $query;
                    $return = $querieslib->get_curriculum_institutes($formoptions->institute_type, $service);
                break;
                case 'program_costcenter_selector':
                // OL-1042 Add Target Audience to curriculums//
                    if ($formoptions->id > 0 && !isset($formoptions->parnetid)) {
                        $parentid = $DB->get_field('local_curriculum', 'costcenter', array('id' => $formoptions->id));
                    } else{
                         $parentid = $formoptions->parnetid;
                    }
                // OL-1042 Add Target Audience to curriculums//
                    $depth = $formoptions->depth;
                    $params = array();
                    $costcntersql = "SELECT id, fullname
                                        FROM {local_costcenter}
                                        WHERE visible = 1 ";
                    if ($parentid >= 0) {
                        $costcntersql .= " AND parentid = :parentid ";
                        $params['parentid'] = $parentid;
                    }
                    if ($depth > 0) {
                        $costcntersql .= " AND depth = :depth ";
                        $params['depth'] = $depth;
                    }
                    if (!empty($query)) {
                        $costcntersql .= " AND fullname LIKE :query ";
                        $params['query'] = '%' . $query . '%';
                    }

                    $return = $DB->get_records_sql($costcntersql, $params);
                    $return = (object)((array)$return + array('0' => (object)array('id' => -1,
                        'fullname' => get_string('all')) ));
                break;
                case 'programsession_trainer_selector':
                    $parent = array();
                    if ($formoptions->parnetid > 0) {
                        $parent = array($formoptions->parnetid);
                    }
                    $return = $querieslib->get_user_department_trainerslist(true, $parent,
                        array(), $query);
                break;
                case 'program_completions_sessions_selector':
                    $sessions_sql = "SELECT id, name as fullname
                                        FROM {local_cc_course_sessions}
                                        WHERE curriculumid = $formoptions->curriculumid";
                    $return = $DB->get_records_sql($sessions_sql);
                break;
                case 'program_completions_courses_selector':
                    $courses_sql = "SELECT c.id, c.fullname FROM {course} as c JOIN {local_cc_semester_courses} as lcc on lcc.courseid=c.id where lcc.curriculumid = $formoptions->curriculumid";
                    $return = $DB->get_records_sql($courses_sql);
                break;
                case 'program_room_selector':
                    if (!empty($formoptions->instituteid)) {
                        $locationroomlistssql = "SELECT cr.id, cr.name AS fullname
                                           FROM {local_location_room} AS cr
                                           WHERE cr.visible = 1 AND cr.instituteid = $formoptions->instituteid";
                        $return = $DB->get_records_sql($locationroomlistssql);
                    } else {
                        $return = array();
                    }

                break;
                case 'program_facultie_selector':
                    $sql="select id,facultyname as fullname  from {local_faculties} where 1=1 ";

                    if ($formoptions->parnetid > 0) {
                        $sql.=" AND university IN(:costcenterid)";
                        $params['costcenterid'] = $formoptions->parnetid;
                    }
                    if (!empty($query)) {
                        $sql .= " AND facultyname LIKE :query ";
                        $params['query'] = '%' . $query . '%';
                    }
                    if ($formoptions->parnetid > 0) {
                        $return=$DB->get_records_sql($sql, $params);
                    }

                break;
                case 'classroom_completions_sessions_selector':
                    // print_object($formoptions);
                    $sessions_sql = "SELECT id, name as fullname
                                        FROM {local_cc_course_sessions}
                                        WHERE bclcid = $formoptions->bclcid";
                    if($formoptions->ccses_action == 'coursesessions'){
                        $sessions_sql .= " AND sessiontype = 0";
                    }elseif ($formoptions->ccses_action == 'class_sessions') {
                        $sessions_sql .= " AND sessiontype = 1";
                    }elseif ($formoptions->ccses_action == 'semsessions') {
                        $sessions_sql .= " AND sessiontype = 2";
                    }
                    // print_object($sessions_sql);
                    $return = $DB->get_records_sql($sessions_sql);


                break;
                case 'program_curriculum_selector':
                    $sql="select id,name as fullname from {local_curriculum} where program = 0 AND curriculum_publish_status = 1";

                    if ($formoptions->parnetid > 0) {
                        $sql.=" AND costcenter IN (:costcenterid)";
                        $params['costcenterid'] = $formoptions->parnetid;
                    }
                    if (!empty($query)) {
                        $sql .= " AND name LIKE :query ";
                        $params['query'] = '%' . $query . '%';
                    }
                    if ($formoptions->parnetid > 0) {
                        $return = $DB->get_records_sql($sql, $params);
                    }
                break;
                case 'program_department_selector':
                    $sql="select id, fullname from {local_costcenter} where 1=1 ";

                    if ($formoptions->parnetid > 0) {
                        $sql.="AND univ_dept_status = :univstatus AND parentid IN (:costcenterid)";
                        $params['univstatus'] = 0;
                        $params['costcenterid'] = $formoptions->parnetid;
                    }
                    if (!empty($query)) {
                        $sql .= " AND fullname LIKE :query ";
                        $params['query'] = '%' . $query . '%';
                    }
                    if ($formoptions->parnetid > 0) {
                        $return = $DB->get_records_sql($sql, $params);
                    }
                break;
                case 'program_costcenterall_selector':
                // OL-1042 Add Target Audience to curriculums//
                    if ($formoptions->id > 0 && !isset($formoptions->parnetid)) {
                        $parentid = $DB->get_field('local_curriculum', 'costcenter', array('id' => $formoptions->id));
                    } else{
                         $parentid = $formoptions->parnetid;
                    }
                // OL-1042 Add Target Audience to curriculums//
                    $depth = $formoptions->depth;
                    $params = array();
                    $costcntersql = "SELECT id, fullname
                                        FROM {local_costcenter}
                                        WHERE visible = 1 ";
                    if ($parentid >= 0) {
                        $costcntersql .= " AND parentid = :parentid ";
                        $params['parentid'] = $parentid;
                    }
                    if ($depth > 0) {
                        $costcntersql .= " AND depth = :depth ";
                        $params['depth'] = $depth;
                    }
                    if (!empty($query)) {
                        $costcntersql .= " AND fullname LIKE :query ";
                        $params['query'] = '%' . $query . '%';
                    }

                    $return = $DB->get_records_sql($costcntersql, $params);
                    $return = (object)((array)$return);
                break;
                case 'program_course_selector':
                    $courses = array();
                    if ($query) {
                        $queryparams = array();
                        $concatsql = '';
                        // if ((has_capability('local/program:manageprogram', context_system::instance())) && ( !is_siteadmin() && (!has_capability('local/program:manage_multiorganizations', context_system::instance()) && !has_capability('local/costcenter:manage_multiorganizations', context_system::instance())))) {
                        //     $concatsql .= " AND open_costcenterid = :costcenterid";
                        //     $queryparams['costcenterid'] = $USER->open_costcenterid;
                        //     if ((has_capability('local/program:manage_owndepartments', context_system::instance())|| has_capability('local/costcenter:manage_owndepartments', context_system::instance()))) {
                        //         $concatsql .= " AND open_departmentid = :department";
                        //         $queryparams['department'] = $USER->open_departmentid;
                        //     }
                        // }
                        if($formoptions->department){
                            $concatsql .= " AND c.category IN ($formoptions->department) ";
                        }
                        // $queryparams['department'] = $formoptions->department;
                        $existedcourses = array();
                        if ($formoptions->programid && $formoptions->curriculumid) {

                            $existedcoursessql = "SELECT id, open_parentcourseid
                                                FROM {local_cc_semester_courses}
                                               WHERE programid = :programid AND curriculumid = :curriculumid";
                            $existedcourses = $DB->get_records_sql_menu($existedcoursessql, array('programid' => $formoptions->programid, 'curriculumid' => $formoptions->curriculumid));
                        }
                        if (!empty($existedcourses)) {
                            $existedcourseslist = implode(',', $existedcourses);
                            $concatsql .= " AND c.id NOT IN ($existedcourseslist) ";
                        }
                        $cousresql = "SELECT c.id, c.fullname
                                       FROM {course} c
                                       JOIN {enrol} e ON e.courseid = c.id
                                      WHERE c.visible = 1 AND c.open_parentcourseid = 0 AND e.enrol = 'program' AND e.status = 0 AND c.fullname LIKE '%$query%' AND c.id <> " . SITEID . $concatsql;
                        $courses = $DB->get_records_sql($cousresql, $queryparams);
                    }

                    $return = $courses;
                break;
                case 'program_course_faculty_selector':
                    $courses = array();
                    if ($query) {
                        $queryparams = array();
                        $concatsql = " AND (CONCAT(u.firstname, ' ', u.lastname)  LIKE '%" . $query . "%')";

                        // if ($formoptions->programid) {
                        //     $schoolssql = "SELECT cc.id, cc.parentid
                        //                      FROM {local_costcenter} cc
                        //                      JOIN {local_program} p ON p.costcenter = cc.id
                        //                     WHERE p.id = :programid ";
                        //     $school = $DB->get_record_sql($schoolssql, array('programid' => $formoptions->programid ));
                        //     if ($school->parentid > 0) {
                        //         $concatsql .= " AND u.open_departmentid = :schoolid ";
                        //     } else {
                        //         $concatsql .= " AND u.open_costcenterid = :schoolid AND (u.open_departmentid IS NULL OR u.open_departmentid = 0 )";
                        //     }
                        //     $queryparams['schoolid'] = $school->id;
                        // }
                         # AM changed to get costcenter and departemnt based faculty
                        if ($formoptions->programid) {
                            $departmentsql = "SELECT cc.id, cc.parentid
                                             FROM {local_costcenter} cc
                                             JOIN {local_program} p ON p.departmentid = cc.id
                                            WHERE p.id = :programid ";
                            $department = $DB->get_record_sql($departmentsql, array('programid' => $formoptions->programid ));
                            $concatsql .= " AND u.open_costcenterid = :costcenterid";
                            $queryparams['costcenterid'] = $department->parentid;
                            //$queryparams['departmentid'] = $department->id;
                        }
                        # AM ends here
                        if ($formoptions->courseid && $formoptions->semesterid && $formoptions->yearid ) {
                            $concatsql .= " AND u.id NOT IN (SELECT trainerid FROM {local_cc_session_trainers} WHERE courseid = :courseid AND semesterid = :semesterid AND yearid = :yearid ) ";
                            $queryparams['courseid'] = $formoptions->courseid;
                            $queryparams['semesterid'] = $formoptions->semesterid;
                            $queryparams['yearid'] = $formoptions->yearid;
                        }
                        $userssql = "SELECT u.id, CONCAT(u.firstname, ' ', u.lastname) AS fullname
                                       FROM {user} AS u
                                       JOIN {role_assignments} ra ON ra.userid = u.id
                                       JOIN {role} r ON r.id = ra.roleid
                                      WHERE u.confirmed = 1 AND u.deleted = 0 AND u.suspended = 0 AND u.id > 2 AND r.shortname='faculty' AND ra.contextid = 1 AND u.open_employee = 1 " . $concatsql;

                        $users = $DB->get_records_sql($userssql, $queryparams);

                    }
                    $return = $users;
                break;
                case 'program_course_student_selector':
                    $courses = array();
                    if ($query) {
                        $queryparams = array();

                        $concatsql = " AND (CONCAT(u.firstname, ' ', u.lastname)  LIKE '%" . $query . "%')";

                        // if ($formoptions->programid) {
                        //     $schoolssql = "SELECT cc.id, cc.parentid
                        //                      FROM {local_costcenter} cc
                        //                      JOIN {local_program} p ON p.costcenter = cc.id
                        //                     WHERE p.id = :programid ";
                        //     $school = $DB->get_record_sql($schoolssql, array('programid' => $formoptions->programid ));
                        //     if ($school->parentid > 0) {
                        //         $concatsql .= " AND u.open_departmentid = :schoolid ";
                        //     } else {
                        //         $concatsql .= " AND u.open_costcenterid = :schoolid AND (u.open_departmentid IS NULL OR u.open_departmentid = 0) ";
                        //     }
                        //     $queryparams['schoolid'] = $school->id;
                        // }
                        # AM changed to get costcenter and departemnt based faculty
                        if ($formoptions->programid) {
                            $departmentsql = "SELECT cc.id, cc.parentid
                                             FROM {local_costcenter} cc
                                             JOIN {local_program} p ON p.departmentid = cc.id
                                            WHERE p.id = :programid ";
                            $department = $DB->get_record_sql($departmentsql, array('programid' => $formoptions->programid ));
                            $concatsql .= " AND u.open_costcenterid = :costcenterid AND u.open_departmentid = :departmentid ";
                            $queryparams['costcenterid'] = $department->parentid;
                            $queryparams['departmentid'] = $department->id;
                        }
                        # AM ends here
                        $enrolledusersconcatsql = "";

                        if ($formoptions->programid && $formoptions->curriculumid && $formoptions->yearid) {
                            $recordexists = $DB->record_exists('local_ccuser_year_signups', array('programid' => $formoptions->programid, 'curriculumid' => $formoptions->curriculumid, 'yearid' => $formoptions->yearid));
                            if ($recordexists) {
                                $enrolledusersconcatsql .= " AND u.id NOT IN (SELECT userid FROM {local_ccuser_year_signups} WHERE programid = :programid AND curriculumid = :curriculumid AND yearid = :yearid ) ";
                                $queryparams['programid'] = $formoptions->programid;
                                $queryparams['curriculumid'] = $formoptions->curriculumid;
                                $queryparams['yearid'] = $formoptions->yearid;
                            }
                        }
                        // $sequence = $DB->get_field('local_program_cc_years','sequence',array('id' => $formoptions->yearid));
                        // if($sequence > 1){
                        //     $newsequence = $sequence-1;
                        //     $newyearid = $DB->get_field('local_program_cc_years','id',array('sequence' => $newsequence,'programid' => $formoptions->programid, 'curriculumid' => $formoptions->curriculumid));
                        //     if($newyearid){
                        //         $useridschecking = (new program)->yearwisecompletionchecking($formoptions->programid, $formoptions->curriculumid, $newyearid);
                        //         if($useridschecking){
                        //             $userids = implode(',', $useridschecking);
                        //             $userssql = "SELECT u.id, CONCAT(u.firstname, ' ', u.lastname) AS fullname
                        //                FROM {user} AS u
                        //               WHERE u.confirmed = 1 AND u.deleted = 0 AND u.suspended = 0 AND u.id > 2 AND u.open_employee = 2 AND u.id IN ($userids) ".$enrolledusersconcatsql;
                        //             $users = $DB->get_records_sql($userssql, $queryparams);
                        //         }
                        //     }
                        // }else{
                            $userssql = "SELECT u.id, CONCAT(u.firstname, ' ', u.lastname) AS fullname
                                       FROM {user} AS u
                                      WHERE u.confirmed = 1 AND u.deleted = 0 AND u.suspended = 0 AND u.id > 2 AND u.open_employee = 2 " . $concatsql.$enrolledusersconcatsql;
                            $users = $DB->get_records_sql($userssql, $queryparams);
                        //}
                    }

                    $return = $users;
                break;
            }
            return json_encode($return);
        }
    }
    public static function program_form_option_selector_returns() {
        return new external_value(PARAM_RAW, 'data');
    }
    public static function program_session_instance_parameters() {
        return new external_function_parameters(
            array(
                'id' => new external_value(PARAM_INT, 'ID', 0),
                'contextid' => new external_value(PARAM_INT, 'The context id', false),
                'form_status' => new external_value(PARAM_INT, 'Form position', 0),
                'jsonformdata' => new external_value(PARAM_RAW, 'Submitted Form Data', false)
            )
        );
    }
    public static function program_session_instance($id, $contextid, $form_status, $jsonformdata) {
        global $PAGE, $DB, $CFG, $USER;
        $context = context::instance_by_id($contextid, MUST_EXIST);
        self::validate_context($context);
        $serialiseddata = json_decode($jsonformdata);
        $data = array();
        parse_str($serialiseddata, $data);

        $warnings = array();
        $curriculum = new stdClass();
       $mform = new \local_program\form\session_form(null, array('programid' => $data['programid'], 'curriculumid' => $data['curriculumid'],'yearid' => $data['yearid'], 'semesterid' => $data['semesterid'], 'ccses_action' => $data['ccses_action'], 'form_status' => $form_status),
           'post', '', null, true, $data);
        $validateddata = $mform->get_data();
        if ($validateddata) {
            $sessionid = (new program)->manage_bc_courses_sessions($validateddata);
            if ($sessionid > 0) {
                $form_status = -2;
                $error = false;
            } else {
                $error = true;
            }
        } else {
            // Generate a warning.
            throw new moodle_exception('missingcurriculum', 'local_program');
        }
        $return = array(
            'id' => $sessionid,
            'form_status' => $form_status);
        return $return;
    }

    public static function program_session_instance_returns() {
        return new external_single_structure(array(
            'id' => new external_value(PARAM_INT, 'Context id for the framework'),
            'form_status' => new external_value(PARAM_INT, 'form_status'),
        ));
    }
    public static function program_completion_settings_instance_parameters() {
        return new external_function_parameters(
            array(
                'id' => new external_value(PARAM_INT, 'ID', 0),
                'contextid' => new external_value(PARAM_INT, 'The context id', false),
                'form_status' => new external_value(PARAM_INT, 'Form position', 0),
                'jsonformdata' => new external_value(PARAM_RAW, 'Submitted Form Data', false),
            )
        );
    }

    public static function program_completion_settings_instance($id, $contextid, $form_status, $jsonformdata) {
        global $PAGE, $DB, $CFG, $USER;
        $context = context::instance_by_id($contextid, MUST_EXIST);
        self::validate_context($context);
        $serialiseddata = json_decode($jsonformdata);
        $data = array();
        parse_str($serialiseddata, $data);

        $warnings = array();
        $curriculum = new stdClass();
        // The last param is the ajax submitted data.
        $mform = new \local_program\form\program_completion_form(null, array('id' => $data['id'],
            'ccid' => $data['curriculumid'], 'form_status' => $form_status), 'post', '', null,
             true, $data);
        $validateddata = $mform->get_data();
        if ($validateddata) {
            // Do the action.
            $curriculum_completionid = (new program)->manage_curriculum_completions($validateddata);
            if ($curriculum_completionid > 0) {
                $form_status = -2;
                $error = false;
            } else {
                $error = true;
            }
        } else {
            // Generate a warning.
            throw new moodle_exception('missingcurriculum', 'local_program');
        }
        $return = array(
            'id' => $curriculum_completionid,
            'form_status' => $form_status);
        return $return;
    }

    public static function program_completion_settings_instance_returns() {
        return new external_single_structure(array(
            'id' => new external_value(PARAM_INT, 'Context id for the framework'),
            'form_status' => new external_value(PARAM_INT, 'form_status'),
        ));
    }

    public static function program_classroom_completion_settings_instance_parameters() {
        return new external_function_parameters(
            array(
                'id' => new external_value(PARAM_INT, 'ID', 0),
                'contextid' => new external_value(PARAM_INT, 'The context id', false),
                'form_status' => new external_value(PARAM_INT, 'Form position', 0),
                'jsonformdata' => new external_value(PARAM_RAW, 'Submitted Form Data', false),
            )
        );
    }

    public static function program_classroom_completion_settings_instance($id, $contextid, $form_status, $jsonformdata) {
        global $PAGE, $DB, $CFG, $USER;
        $context = context::instance_by_id($contextid, MUST_EXIST);
        self::validate_context($context);
        $serialiseddata = json_decode($jsonformdata);
        $data = array();
        parse_str($serialiseddata, $data);

        $warnings = array();
        $curriculum = new stdClass();
        // The last param is the ajax submitted data.
        /*$mform = new \local_program\form\program_completion_form(null, array('id' => $data['id'],
            'ccid' => $data['curriculumid'], 'form_status' => $form_status), 'post', '', null, true, $data);*/
        $mform = new \local_program\form\classroom_completion_form(null, array('id' => $data['id'], 'ccid' => $data['curriculumid'], 'semesterid' => $data['semesterid'],
            'bclcid' => $data['bclcid'], 'programid' => $data['programid'], 'yearid' => $data['yearid'], 'courseid' => $data['courseid'], 'form_status' => $form_status), 'post', '', null,
             true, $data);
        $validateddata = $mform->get_data();
        // print_object("here");
        // print_object($validateddata);exit;
        if ($validateddata) {
            // Do the action.
            $curriculum_completionid = (new program)->manage_classroom_completionsettings($validateddata);
            if ($curriculum_completionid > 0) {
                $form_status = -2;
                $error = false;
            } else {
                $error = true;
            }
        } else {
            // Generate a warning.
            throw new moodle_exception('missingprogramcompletiondata', 'local_program');
        }
        $return = array(
            'id' => $curriculum_completionid,
            'form_status' => $form_status);
        return $return;
    }

    public static function program_classroom_completion_settings_instance_returns() {
        return new external_single_structure(array(
            'id' => new external_value(PARAM_INT, 'Context id for the framework'),
            'form_status' => new external_value(PARAM_INT, 'form_status'),
        ));
    }

    public static function program_course_instance_parameters() {
        return new external_function_parameters(
            array(
                'id' => new external_value(PARAM_INT, 'ID', 0),
                'contextid' => new external_value(PARAM_INT, 'The context id', false),
                'form_status' => new external_value(PARAM_INT, 'Form position', 0),
                'jsonformdata' => new external_value(PARAM_RAW, 'Submitted Form Data', false),
            )
        );
    }

    public static function program_course_instance($id, $contextid, $form_status, $jsonformdata) {
        global $PAGE, $DB, $CFG, $USER;
        //require_once($CFG->dirroot.'/local/curriculum/classes/form/programcourse_form.php');
        require_once($CFG->dirroot.'/local/curriculum/lib.php');
        $context = context::instance_by_id($contextid, MUST_EXIST);
        self::validate_context($context);
        $serialiseddata = json_decode($jsonformdata);
        $data = array();
        parse_str($serialiseddata, $data);

        // The last param is the ajax submitted data.
        $mform = new programcourse_form(null, array('programid' => $data['programid'], 'curriculumid' => $data['curriculumid'],
            'semesterid' => $data['semesterid'], 'yearid' => $data['yearid'], 'form_status' => $form_status),
            'post', '', null, true, $data);
        $validateddata = $mform->get_data();
     
        if ($validateddata) {;
            // Do the action.
            $sessionid = (new program)->manage_curriculum_courses($validateddata);
            if ($sessionid > 0) {
                $form_status = -2;
                $error = false;
            } else {
                $error = true;
            }
        } else {
            // Generate a warning.
            throw new moodle_exception('missingcurriculum', 'local_program');
        }
        $return = array(
            'id' => $sessionid,
            'form_status' => $form_status);
        return $return;
    }

    public static function program_course_instance_returns() {
        return new external_single_structure(array(
            'id' => new external_value(PARAM_INT, 'Context id for the framework'),
            'form_status' => new external_value(PARAM_INT, 'form_status'),
        ));
    }
    public static function delete_programcourse_instance_parameters() {
        return new external_function_parameters(
            array(
                'action' => new external_value(PARAM_ACTION, 'Action of the event', false),
                'id' => new external_value(PARAM_INT, 'ID of the record', 0),
                'curriculumid' => new external_value(PARAM_INT, 'curriculum ID', 0),
                'confirm' => new external_value(PARAM_BOOL, 'Confirm', false),
            )
        );
    }

    public static function delete_programcourse_instance($action, $id, $curriculumid, $confirm) {
        global $DB;
        try {
            if ($confirm) {
                $course = $DB->get_field('local_cc_semester_courses', 'courseid', array('curriculumid' => $curriculumid, 'id' => $id));

                $curriculum_completiondata =$DB->get_record_sql("SELECT id,courseids
                                        FROM {local_curriculum_completion}
                                        WHERE curriculumid = $curriculumid");

                if ($curriculum_completiondata->courseids != null) {
                    $curriculum_courseids = explode(',', $curriculum_completiondata->courseids);
                    $array_diff = array_diff($curriculum_courseids, array($course));
                    if (!empty($array_diff)) {
                        $curriculum_completiondata->courseids = implode(',', $array_diff);
                    } else {
                        $curriculum_completiondata->courseids = "NULL";
                    }
                    $DB->update_record('local_curriculum_completion', $curriculum_completiondata);
                    $params = array(
                        'context' => context_system::instance(),
                        'objectid' => $curriculum_completiondata->id
                    );

                    $event = \local_program\event\program_completions_settings_updated::create($params);
                    $event->add_record_snapshot('local_program', $curriculumid);
                    $event->trigger();
                }

                $curriculumtrainers = $DB->get_records_menu('local_curriculum_trainers',
                    array('curriculumid' => $curriculumid), 'trainerid', 'id, trainerid');
                if (!empty($curriculumtrainers)) {
                    foreach ($curriculumtrainers as $curriculumtrainer) {
                        $unenrolcurriculumtrainer = (new program)->manage_curriculum_course_enrolments($course, $curriculumtrainer,
                            'editingteacher', 'unenrol');
                    }
                }
                $curriculumusers = $DB->get_records_menu('local_curriculum_users',
                    array('curriculumid' => $curriculumid), 'userid', 'id, userid');
                if (!empty($curriculumusers)) {
                    foreach ($curriculumusers as $curriculumuser) {
                        $unenrolcurriculumuser = (new program)->manage_curriculum_course_enrolments($course, $curriculumuser,
                            'employee', 'unenrol');
                    }
                }
                $params = array(
                    'context' => context_system::instance(),
                    'objectid' =>$id
                );

                $event = \local_program\event\program_courses_deleted::create($params);
                $event->add_record_snapshot('local_cc_semester_courses', $id);
                $event->trigger();
                $DB->delete_records('local_cc_semester_courses', array('id' => $id));
                $return = true;
            } else {
                $return = false;
            }
        } catch (dml_exception $ex) {
            print_error('deleteerror', 'local_program');
            $return = false;
        }
        return $return;
    }

    public static function delete_programcourse_instance_returns() {
        return new external_value(PARAM_BOOL, 'return');
    }

    /*sree*/
    public static function submit_instituteform_form_parameters() {
        return new external_function_parameters(
            array(
                'contextid' => new external_value(PARAM_INT, 'The context id for the evaluation'),
                'jsonformdata' => new external_value(PARAM_RAW, 'The data from the create group form, encoded as a json array')
            )
        );
    }

    /**
     * form submission of institute name and returns instance of this object
     *
     * @param int $contextid
     * @param [string] $jsonformdata
     * @return institute form submits
     */
    public function submit_catform_form($contextid, $jsonformdata){
        global $PAGE, $CFG;

        require_once($CFG->dirroot . '/local/program/lib.php');
        // We always must pass webservice params through validate_parameters.
        $params = self::validate_parameters(self::submit_instituteform_form_parameters(),
                                    ['contextid' => $contextid, 'jsonformdata' => $jsonformdata]);
        // $context = $params['contextid'];
        $context = context_system::instance();
        // We always must call validate_context in a webservice.
        self::validate_context($context);
        $serialiseddata = json_decode($params['jsonformdata']);
        // throw new moodle_exception('Error in creation');
        // die;
        $data = array();

        parse_str($serialiseddata, $data);
        $warnings = array();
         $mform = new local_curriculum\form\catform(null, array(), 'post', '', null, true, $data);
        $category  = new local_curriculum\event\category();
        $valdata = $mform->get_data();

        if ($valdata) {
            if ($valdata->id > 0) {
                $institutes->category_update_instance($valdata);
            } else {
                $institutes->category_insert_instance($valdata);
            }
        } else {
            // Generate a warning.
            throw new moodle_exception('Error in creation');
        }
    }


    /**
     * Returns description of method result value.
     *
     * @return external_description
     * @since Moodle 3.0
     */
    public static function submit_catform_form_returns() {
        return new external_value(PARAM_INT, 'category id');
    }

    public static function managecurriculumsemesters_parameters() {
        return new external_function_parameters(
            array(
                'contextid' => new external_value(PARAM_INT, 'The context id', true, 1),
                'form_status' => new external_value(PARAM_INT, 'Form position', false, 0),
                'jsonformdata' => new external_value(PARAM_RAW, 'Submitted Form Data', false),
            )
        );
    }

    public static function managecurriculumsemesters($contextid, $form_status, $jsonformdata) {
        global $PAGE, $DB, $CFG, $USER;
        $context = context::instance_by_id($contextid, MUST_EXIST);
        self::validate_context($context);
        $serialiseddata = json_decode($jsonformdata);
        $data = array();
        parse_str($serialiseddata, $data);

        $warnings = array();
        $curriculum = new stdClass();

        // The last param is the ajax submitted data.
        $mform = new program_managesemester_form(null, array('id' => $data['id'],
            'programid' => $data['programid'], 'curriculumid' => $data['curriculumid'], 'yearid' => $data['yearid'],
            'form_status' => $form_status), 'post', '', null, true, $data);
        $validateddata = $mform->get_data();
        if ($validateddata) {
            // Do the action.
            $program = $DB->get_field('local_curriculum', 'program',
                array('id' => $validateddata->curriculumid));
            $action = 'create';
            if ($validateddata->id > 0) {
                $action = 'update';
            }
            $sessionid = (new program)->manage_curriculum_program_semesters($validateddata);
            if ($sessionid > 0) {
                $form_status = -2;
                $error = false;
            } else {
                $error = true;
            }
        } else {
            // Generate a warning.
            throw new moodle_exception('missingcurriculum', 'local_program');
        }
        $return = array(
            'id' => $sessionid,
            'form_status' => $form_status);
        return $return;
    }

    public static function managecurriculumsemesters_returns() {
        return new external_single_structure(array(
            'id' => new external_value(PARAM_INT, 'Context id for the framework'),
            'form_status' => new external_value(PARAM_INT, 'form_status'),
        ));
    }
    public static function curriculum_unassign_course_parameters(){
        return new external_function_parameters(
            array(
                'programid' => new external_value(PARAM_INT, 'ID of the curriculum'),
                'curriculumid' => new external_value(PARAM_INT, 'ID of the curriculum'),
                'yearid' => new external_value(PARAM_INT, 'ID of the curriculum'),
                'semesterid' => new external_value(PARAM_INT, 'ID of the curriculum semester'),
                'courseid' => new external_value(PARAM_INT, 'ID of the curriculum semester course to be unassigned')
            )
        );
    }
    public static function curriculum_unassign_course($programid, $curriculumid, $yearid, $semesterid, $courseid){
        if ($programid > 0 && $curriculumid > 0 && $yearid > 0 && $semesterid > 0 && $courseid > 0) {
            $program = new program();
            $program->unassign_courses_from_semester($programid, $curriculumid, $yearid, $semesterid, $courseid);
            return true;
        } else {
            throw new moodle_exception('Error in unassigning of course');
            return false;
        }
    }
    public static function curriculum_unassign_course_returns(){
        return new external_value(PARAM_BOOL, 'return');
    }
    public static function bc_session_enrolments_parameters(){
        return new external_function_parameters(
            array(
                'curriculumid' => new external_value(PARAM_INT, 'ID of the curriculum', VALUE_REQUIRED),
                'semesterid' => new external_value(PARAM_INT, 'ID of the curriculum semester', VALUE_REQUIRED),
                'bclcid' => new external_value(PARAM_INT, 'ID of the curriculum semester course to be unassigned', VALUE_REQUIRED),
                'programid' => new external_value(PARAM_INT, 'ID of the program semester to be unassigned', VALUE_REQUIRED),
                'yearid' => new external_value(PARAM_INT, 'ID of the curriculum semester year to be unassigned', VALUE_REQUIRED),
                'sessionid' => new external_value(PARAM_INT, 'ID of the session', VALUE_REQUIRED),
                'ccses_action' => new external_value(PARAM_RAW, 'ID of the session', VALUE_REQUIRED),
                'signupid' => new external_value(PARAM_INT, 'ID of the session signup', false, 0),
                'enrol' => new external_value(PARAM_INT, 'enroment action status', VALUE_REQUIRED)
            )
        );
    }
    public static function bc_session_enrolments($curriculumid, $semesterid, $bclcid, $programid, $yearid, $sessionid, $ccses_action, $signupid, $enrol) {
        global $USER;
        $enroldata = new stdClass();
        $enroldata->curriculumid = $curriculumid;
        $enroldata->semesterid = $semesterid;
        $enroldata->bclcid = $bclcid;
        $enroldata->programid = $programid;
        $enroldata->yearid = $yearid;
        $enroldata->sessionid = $sessionid;
        $enroldata->ccses_action = $ccses_action;
        $enroldata->signupid = $signupid;
        $enroldata->enrol = $enrol;
        $enroldata->userid = $USER->id;
        $return = (new program)->bc_session_enrolments($enroldata);
    }
    public static function bc_session_enrolments_returns(){
        return new external_value(PARAM_BOOL, 'return');
    }

    public static function delete_semester_instance_parameters() {
        return new external_function_parameters(
            array(
                'action' => new external_value(PARAM_ACTION, 'Action of the event', false),
                'id' => new external_value(PARAM_INT, 'ID of the record', 0),
                'curriculumid' => new external_value(PARAM_INT, 'ID of the record', 0),
                'yearid' => new external_value(PARAM_INT, 'ID of the record', 0),
                'programid' => new external_value(PARAM_INT, 'ID of the program', 0),
                'confirm' => new external_value(PARAM_BOOL, 'Confirm', false),
            )
        );
    }

    public static function delete_semester_instance($action, $id, $curriculumid, $yearid, $programid, $confirm) {
        global $DB,$USER;
        try {
            $deletesemcourses = (new program)->deletesemonlinecourses($id, $curriculumid, $yearid, $programid);
            $DB->delete_records('local_cc_semester_courses', array('semesterid' => $id));
            $params = array(
                    'context' => context_system::instance(),
                    'objectid' => $id
            );

            $event = \local_program\event\semester_deleted::create($params);
            $event->add_record_snapshot('local_curriculum_semesters', $id);
            $event->trigger();

            $DB->delete_records('local_curriculum_semesters', array('id' => $id));
            $return = true;
        } catch (dml_exception $ex) {
            print_error('deleteerror', 'local_program');
            $return = false;
        }
        return $return;
    }

    public static function delete_semester_instance_returns() {
        return new external_value(PARAM_BOOL, 'return');
    }
    public static function managecurriculumprograms_parameters() {
        return new external_function_parameters(
            array(
                'contextid' => new external_value(PARAM_INT, 'The context id', true, 1),
                'form_status' => new external_value(PARAM_INT, 'Form position', false, 0),
                'jsonformdata' => new external_value(PARAM_RAW, 'Submitted Form Data', false),
            )
        );
    }

    public static function managecurriculumprograms($contextid, $form_status, $jsonformdata) {
        global $PAGE, $DB, $CFG, $USER;
        $context = context::instance_by_id($contextid, MUST_EXIST);
        self::validate_context($context);
        /*$PAGE->requires->js('/local/program/js/custom.js',true);
        $PAGE->requires->js('/local/program/js/jquery.min.js',true);*/
        $serialiseddata = json_decode($jsonformdata);
        $data = array();
        parse_str($serialiseddata, $data);

        $warnings = array();
        $curriculum = new stdClass();


        // The last param is the ajax submitted data.
        $mform = new program_manageprogram_form(null, array('id' => $data['id'],
            'form_status' => $form_status,'editabel'=>$data['editabel'],'copyeditabel'=>$data['copyeditabel']), 'post', '', null, true, $data);
        $validateddata = $mform->get_data();
        if ($validateddata) {
            $action = 'create';
            if ($validateddata->id > 0) {
                $action = 'update';
            }

            $programid = (new program)->manage_curriculum_programs($validateddata);
            if ($programid > 0) {
                $form_status = -2;
                $error = false;
            } else {
                $error = true;
            }
        } else {
            // Generate a warning.
            throw new moodle_exception('missingcurriculum', 'local_program');
        }
        $return = array(
            'id' => $programid,
            'form_status' => $form_status);
        return $return;
    }

    public static function managecurriculumprograms_returns() {
        return new external_single_structure(array(
            'id' => new external_value(PARAM_INT, 'Context id for the framework'),
            'form_status' => new external_value(PARAM_INT, 'form_status'),
        ));
    }

    public static function delete_program_instance_parameters() {
        return new external_function_parameters(
            array(
                'action' => new external_value(PARAM_ACTION, 'Action of the event', false),
                'id' => new external_value(PARAM_INT, 'ID of the record', 0),
                'confirm' => new external_value(PARAM_BOOL, 'Confirm', false),
            )
        );
    }

    public static function delete_program_instance($action, $id, $confirm) {
        global $DB, $USER;
        try {
            if ($confirm) {
                $transaction = $DB->start_delegated_transaction();

                    $deleteprogram=(new program)->uncopy_program_instance($id,$showfeedback = false,$progressbar=false);
                    $params = array(
                        'context' => context_system::instance(),
                        'objectid' =>$id
                    );

                    $event = \local_program\event\program_deleted::create($params);
                    $event->add_record_snapshot('local_program', $id);
                    $event->trigger();

                $transaction->allow_commit();

                $return = true;
            } else {
                $return = false;
            }
        } catch (dml_exception $ex) {
            print_error('deleteerror', 'local_program');
            $return = false;
        }
        return $return;
    }

    public static function delete_program_instance_returns() {
        return new external_value(PARAM_BOOL, 'return');
    }

    public static function managecurriculumStatus_instance_parameters() {
        return new external_function_parameters(
            array(
                'action' => new external_value(PARAM_RAW, 'Action of the event', false),
                'id' => new external_value(PARAM_INT, 'ID of the record', 0),
                 'curriculumid' => new external_value(PARAM_INT, 'ID of the record', 0),
                'confirm' => new external_value(PARAM_BOOL, 'Confirm', false),
                'actionstatusmsg' => new external_value(PARAM_ACTION, 'Action of the event', false),
                'curriculumname' => new external_value(PARAM_RAW, 'Action of the event', false),
            )
        );
    }

    public static function managecurriculumStatus_instance($action, $id, $confirm,$actionstatusmsg,$curriculumname) {
        global $DB,$USER;
        try {
            if ($action === 'selfenrol') {
                $return = (new program)->curriculum_self_enrolment($id,$USER->id, $selfenrol=1);
            }else{
                $return = (new program)->curriculum_status_action($id, $action);
            }

        } catch (dml_exception $ex) {
            print_error('deleteerror', 'local_program');
            $return = false;
        }
        return $return;
    }

    public static function managecurriculumStatus_instance_returns() {
        return new external_value(PARAM_BOOL, 'return');
    }

    public static function managecurriculumyears_parameters() {
        return new external_function_parameters(
            array(
                'contextid' => new external_value(PARAM_INT, 'The context id', true, 1),
                'form_status' => new external_value(PARAM_INT, 'Form position', false, 0),
                'jsonformdata' => new external_value(PARAM_RAW, 'Submitted Form Data', false),
            )
        );
    }

    public static function managecurriculumyears($contextid, $form_status, $jsonformdata) {
        global $PAGE, $DB, $CFG, $USER;
        $context = context::instance_by_id($contextid, MUST_EXIST);
        self::validate_context($context);
        $serialiseddata = json_decode($jsonformdata);
        $data = array();
        parse_str($serialiseddata, $data);

        $warnings = array();
        $curriculum = new stdClass();

        // The last param is the ajax submitted data.
        $mform = new program_manageyear_form(null, array('id' => $data['id'],
            'curriculumid' => $data['curriculumid'], 'programid' => $data['programid'],
            'form_status' => $form_status), 'post', '', null, true, $data);
        $validateddata = $mform->get_data();
        if ($validateddata) {
            // Do the action.
            $program = $DB->get_field('local_curriculum', 'program',
                array('id' => $validateddata->curriculumid));
            $action = 'create';
            if ($validateddata->id > 0) {
                $action = 'update';
            }
            $sessionid = (new program)->manage_program_curriculum_years($validateddata);
            if ($sessionid > 0) {
                $form_status = -2;
                $error = false;
            } else {
                $error = true;
            }
        } else {
            // Generate a warning.
            throw new moodle_exception('missingcurriculum', 'local_program');
        }
        $return = array(
            'id' => $sessionid,
            'form_status' => $form_status);
        return $return;
    }

    public static function managecurriculumyears_returns() {
        return new external_single_structure(array(
            'id' => new external_value(PARAM_INT, 'Context id for the framework'),
            'form_status' => new external_value(PARAM_INT, 'form_status'),
        ));
    }
    public static function addfaculty_submit_instance_parameters() {
        return new external_function_parameters(
            array(
                'id' => new external_value(PARAM_INT, 'ID', 0),
                'contextid' => new external_value(PARAM_INT, 'The context id', false),
                'form_status' => new external_value(PARAM_INT, 'Form position', 0),
                'jsonformdata' => new external_value(PARAM_RAW, 'Submitted Form Data', false),
            )
        );
    }

    public static function addfaculty_submit_instance($id, $contextid, $form_status, $jsonformdata) {
        global $PAGE, $DB, $CFG, $USER;
        $context = context::instance_by_id($contextid, MUST_EXIST);
        self::validate_context($context);
        $serialiseddata = json_decode($jsonformdata);
        $data = array();
        parse_str($serialiseddata, $data);

        $warnings = array();
        $curriculum = new stdClass();

        // The last param is the ajax submitted data.
        $mform = new managefaculty_form(null, array('programid' => $data['programid'], 'curriculumid' => $data['curriculumid'],'yearid' => $data['yearid'], 'semesterid' => $data['semesterid'], 'courseid' => $data['courseid'], 'form_status' => $form_status),
            'post', '', null, true, $data);
        $validateddata = $mform->get_data();
        if ($validateddata) {
            // Do the action.
            $managefaculty = (new program)->addfaculty($validateddata);
            if ($managefaculty > 0) {
                $form_status = -2;
                $error = false;
            } else {
                $error = true;
            }
        } else {
            // Generate a warning.
            throw new moodle_exception('missingfaculty', 'local_program');
        }
        $return = array(
            'id' => $managefaculty,
            'form_status' => $form_status);
        return $return;
    }

    public static function addfaculty_submit_instance_returns() {
        return new external_single_structure(array(
            'id' => new external_value(PARAM_INT, 'Context id for the framework'),
            'form_status' => new external_value(PARAM_INT, 'form_status'),
        ));
    }

    public static function addclassroom_submit_instance_parameters() {
        return new external_function_parameters(
            array(
                'id' => new external_value(PARAM_INT, 'ID', 0),
                'contextid' => new external_value(PARAM_INT, 'The context id', false),
                'form_status' => new external_value(PARAM_INT, 'Form position', 0),
                'jsonformdata' => new external_value(PARAM_RAW, 'Submitted Form Data', false),
            )
        );
    }

    public static function addclassroom_submit_instance($id, $contextid, $form_status, $jsonformdata) {
        global $PAGE, $DB, $CFG, $USER;
        $context = context::instance_by_id($contextid, MUST_EXIST);
        self::validate_context($context);
        $serialiseddata = json_decode($jsonformdata);
        // print_object($jsonformdata);
        $data = array();
        parse_str($serialiseddata, $data);

        $warnings = array();
        $curriculum = new stdClass();
        // print_object($data);
        // The last param is the ajax submitted data.
        $mform = new manageclassroom_form(null, array('programid' => $data['programid'], 'curriculumid' => $data['curriculumid'],'yearid' => $data['yearid'], 'semesterid' => $data['semesterid'], 'ccses_action' => $data['ccses_action'], 'form_status' => $form_status),
            'post', '', null, true, $data);
        $validateddata = $mform->get_data();
        if ($validateddata) {
            // Do the action.
            $managefaculty = (new program)->addclassroom($validateddata);
            if ($managefaculty > 0) {
                $form_status = -2;
                $error = false;
            } else {
                $error = true;
            }
        } else {
            // Generate a warning.
            throw new moodle_exception('missingclassroom', 'local_program');
        }
        $return = array(
            'id' => $managefaculty,
            'form_status' => $form_status);
        return $return;
    }

    public static function addclassroom_submit_instance_returns() {
        return new external_single_structure(array(
            'id' => new external_value(PARAM_INT, 'Context id for the framework'),
            'form_status' => new external_value(PARAM_INT, 'form_status'),
        ));
    }

    public static function addstudent_submit_instance_parameters() {
        return new external_function_parameters(
            array(
                'id' => new external_value(PARAM_INT, 'ID', 0),
                'contextid' => new external_value(PARAM_INT, 'The context id', false),
                'form_status' => new external_value(PARAM_INT, 'Form position', 0),
                'jsonformdata' => new external_value(PARAM_RAW, 'Submitted Form Data', false),
            )
        );
    }

    public static function addstudent_submit_instance($id, $contextid, $form_status, $jsonformdata) {
        global $PAGE, $DB, $CFG, $USER;
        $context = context::instance_by_id($contextid, MUST_EXIST);
        self::validate_context($context);
        $serialiseddata = json_decode($jsonformdata);
        $data = array();
        parse_str($serialiseddata, $data);

        $warnings = array();
        $curriculum = new stdClass();

        // The last param is the ajax submitted data.
        $mform = new managestudent_form(null, array('curriculumid' => $data['curriculumid'],'yearid' => $data['yearid'], 'programid' => $data['programid'], 'form_status' => $form_status),
            'post', '', null, true, $data);
        $validateddata = $mform->get_data();
        if ($validateddata) {
            // Do the action.
           # AM ODL-713 to enroll classroom assign users to programs
            $semestercoursessql = 'SELECT c.id, c.id as courseid
                               FROM {course} c
                               JOIN {local_cc_semester_courses} ccsc ON ccsc.courseid = c.id
                              WHERE ccsc.yearid = :yearid ';
            $params['yearid'] = $validateddata->yearid;
            $semestercourses = $DB->get_records_sql($semestercoursessql, $params);
            if($semestercourses){
              $managefaculty = (new program)->addstudent($validateddata);
            }else{
              $managefaculty = (new program)->addstudenttoclassroom($validateddata);
            }
            # AM ends here
            if ($managefaculty > 0) {
                $form_status = -2;
                $error = false;
            } else {
                $error = true;
            }
        } else {
            // Generate a warning.
            throw new moodle_exception('missingfaculty', 'local_program');
        }
        $return = array(
            'id' => $managefaculty,
            'form_status' => $form_status);
        return $return;
    }

    public static function addstudent_submit_instance_returns() {
        return new external_single_structure(array(
            'id' => new external_value(PARAM_INT, 'Context id for the framework'),
            'form_status' => new external_value(PARAM_INT, 'form_status'),
        ));
    }
    public static function delete_semesteryear_instance_parameters() {
        return new external_function_parameters(
            array(
                'action' => new external_value(PARAM_ACTION, 'Action of the event', false),
                'id' => new external_value(PARAM_INT, 'ID of the record', 0),
                'curriculumid' => new external_value(PARAM_INT, 'ID of the record', 0),
                'programid' => new external_value(PARAM_INT, 'ID of the record', 0),
                'confirm' => new external_value(PARAM_BOOL, 'Confirm', false),
            )
        );
    }

    public static function delete_semesteryear_instance($action, $id, $curriculumid, $programid, $confirm) {
        global $DB,$USER;
        try {
            $deletesemcourses = (new program)->deletesemonlinecourses('', $curriculumid, $id);
            $DB->delete_records('local_cc_semester_courses', array('yearid' => $id));
            $DB->delete_records('local_curriculum_semesters', array('yearid' => $id));
            $DB->delete_records('local_cc_session_signups', array('yearid' => $id));
            $params = array(
                    'context' => context_system::instance(),
                    'objectid' => $id
            );

            $event = \local_program\event\year_deleted::create($params);
            $event->add_record_snapshot('local_program_cc_years', $id);
            $event->trigger();

            $DB->delete_records('local_program_cc_years', array('id' => $id));
$yearscount = $DB->count_records_sql('SELECT count(id) as id FROM {local_program_cc_years} WHERE curriculumid = '.$curriculumid.'');
$DB->execute('UPDATE {local_curriculum} SET duration ='. $yearscount.' WHERE id = '.$curriculumid);
            $return = true;
        } catch (dml_exception $ex) {
            print_error('deleteerror', 'local_program');
            $return = false;
        }
        return $return;
    }

    public static function delete_semesteryear_instance_returns() {
        return new external_value(PARAM_BOOL, 'return');
    }

        public static function unassignuser_parameters() {
        return new external_function_parameters(
            array(
                'action' => new external_value(PARAM_ACTION, 'Action of the event', false),
                'confirm' => new external_value(PARAM_BOOL, 'Confirm', false),
                'curriculumid' => new external_value(PARAM_INT, 'ID of the record', 0),
                'programid' => new external_value(PARAM_INT, 'ID of the record', 0),
                'userid' => new external_value(PARAM_INT, 'ID of the record', 0),
                'yearid' => new external_value(PARAM_INT, 'ID of the record', 0),
            )
        );
    }

    public static function unassignuser($action, $confirm, $curriculumid, $programid, $userid, $yearid) {
        global $DB,$USER;
        try {
            $data = (new program)->programyear_unassignusers($programid, $curriculumid, $yearid, $userid);
            $return = true;
        } catch (dml_exception $ex) {
            print_error('unassignerror', 'local_program');
            $return = false;
        }
        return $return;
    }

    public static function unassignuser_returns() {
        return new external_value(PARAM_BOOL, 'return');
    }

    public static function yearcost_submit_instance_parameters() {
        return new external_function_parameters(
            array(
                'contextid' => new external_value(PARAM_INT, 'The context id', false),
                'form_status' => new external_value(PARAM_INT, 'Form position', 0),
                'jsonformdata' => new external_value(PARAM_RAW, 'Submitted Form Data', false),
            )
        );
    }

    public static function yearcost_submit_instance($contextid, $form_status, $jsonformdata) {
        global $PAGE, $DB, $CFG, $USER;
        $context = context::instance_by_id($contextid, MUST_EXIST);
        self::validate_context($context);
        $serialiseddata = json_decode($jsonformdata);
        $data = array();
        parse_str($serialiseddata, $data);

        $warnings = array();
        $curriculum = new stdClass();

        // The last param is the ajax submitted data.
        $mform = new yearcost_form(null, array('programid' => $data['programid'], 'curriculumid' => $data['curriculumid'],'yearid' => $data['yearid'], 'cost' => $data['cost'], 'form_status' => $form_status),
            'post', '', null, true, $data);

        $validateddata = $mform->get_data();

        if ($validateddata) {
            // Do the action.
            $managefaculty = (new program)->addyearcost($validateddata);
            if ($managefaculty > 0) {
                $form_status = -2;
                $error = false;
            } else {
                $error = true;
            }
        } else {
            // Generate a warning.
            throw new moodle_exception('missingcost', 'local_program');
        }
        $return = array(
            'id' => $managefaculty,
            'form_status' => $form_status);
        return $return;
    }

    public static function yearcost_submit_instance_returns() {
        return new external_single_structure(array(
            'id' => new external_value(PARAM_INT, 'Context id for the framework'),
            'form_status' => new external_value(PARAM_INT, 'form_status'),
        ));
    }
      public static function unassignfaculty_parameters() {
        return new external_function_parameters(
            array(
                'action' => new external_value(PARAM_ACTION, 'Action of the event', false),
                'programid' => new external_value(PARAM_INT, 'ID of the record', 0),
                'curriculumid' => new external_value(PARAM_INT, 'ID of the record', 0),
                'yearid' => new external_value(PARAM_INT, 'ID of the record', 0),
                'semesterid' => new external_value(PARAM_INT, 'ID of the record', 0),
                'courseid' => new external_value(PARAM_INT, 'ID of the record', 0),
                'trainerid' => new external_value(PARAM_INT, 'ID of the record', 0),
                'confirm' => new external_value(PARAM_BOOL, 'Confirm', false),
            )
        );
    }

    public static function unassignfaculty($action, $programid, $curriculumid, $yearid, $semesterid, $courseid, $trainerid, $confirm) {
        global $DB,$USER;
        try {
            $facultydata = new stdClass();
            $facultydata->yearid = $yearid;
            $facultydata->semesterid = $semesterid;
            $facultydata->courseid = $courseid;
            $facultydata->trainerid = $trainerid;
            (new program)->unassignfaculty($facultydata);
            $return = true;
        } catch (dml_exception $ex) {
            print_error('deleteerror', 'local_program');
            $return = false;
        }
        return $return;
    }

    public static function unassignfaculty_returns() {
        return new external_value(PARAM_BOOL, 'return');
    }

    /* get programs - start */
     /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function get_programs_parameters() {
        return new external_function_parameters(
                array(
//if I had any parameters, they would be described here. But I don't have any, so this array is empty.
                )
        );
    }

    /**
     * Returns Competency programid, fullname, shortname, description, associated Moodle course category and visibility
     * @return INT universityid
     * @return TEXT fullname
     * @return TEXT shortname
     * @return TEXT description
     * @return INT visible
     */
    public static function get_programs() {
        global $USER, $DB, $CFG;

        //Parameter validation
        $params = self::validate_parameters(self::get_programs_parameters(), array());

        /*$sql = 'SELECT p.* FROM {local_program} p JOIN {local_costcenter} c on p.costcenter = c.id WHERE c.parentid = 0';*/// Commented existing query by harish for missing duration & duration_format
        $sql = 'SELECT p.*, lc.duration as ccduration, lc.duration_format as ccduration_format FROM {local_program} p JOIN {local_costcenter} c on p.costcenter = c.id JOIN {local_curriculum} lc on lc.id = p.curriculumid WHERE c.parentid = 0';
        $programs = $DB->get_records_sql($sql);

        $programsinfo = array();

        foreach ($programs as $program) {

                $programinfo = array();
                $programinfo['programid'] = $program->id;
                $programinfo['fullname'] = $program->fullname;
                $programinfo['shortname'] = $program->shortname;
                $programinfo['shortcode'] = $program->shortcode;
                $programinfo['universityid'] = $program->costcenter;
                $programinfo['facultyid'] = $program->facultyid;
                $programinfo['duration'] = $program->ccduration.' '.$program->ccduration_format;
                $programinfo['validity'] = $program->validtill;
                $programinfo['offeringyear'] = $program->year;
                $programinfo['admissionstartdate'] = $program->admissionstartdate;
                $programinfo['admissionenddate'] = $program->admissionenddate;
                $programinfo['programlevel'] = ($program->curriculumsemester == '1') ? 'Undergraduate' : 'Post Graduate';
                $programinfo['status'] = $program->status;
                $programinfo['program_approval'] = $program->program_approval;
                $programinfo['pre_requisites']=$program->pre_requisites;
                $programinfo['description'] = $program->description;

                $programsinfo[] = $programinfo;
            }

       return $programsinfo;
    }

    /**
     * Returns description of method result value
     * @return external_description
     */
    public static function get_programs_returns() {
        return new external_multiple_structure(
                new external_single_structure(
                array(
            'programid' => new external_value(PARAM_INT, 'Program ID'),
            'fullname' => new external_value(PARAM_TEXT, 'Fullname of Program'),
            'shortname' => new external_value(PARAM_TEXT, 'Shortname of Program'),
            'shortcode' => new external_value(PARAM_TEXT, 'Shortcode of Program'),
            'universityid' => new external_value(PARAM_INT, 'University of Program'),
            'facultyid' => new external_value(PARAM_INT, 'Facultyid of Program'),
            'duration' => new external_value(PARAM_TEXT, 'Duration of Program'),
            'validity' => new external_value(PARAM_INT, 'Program validity'),
            'offeringyear' => new external_value(PARAM_INT, 'Year of program offering'),
            'admissionstartdate' => new external_value(PARAM_INT, 'Program admission startdate'),
            'admissionenddate' => new external_value(PARAM_INT, 'Program admission enddate'),
            'programlevel' => new external_value(PARAM_TEXT, 'Program level i.e UG or PG'),
            'status' => new external_value(PARAM_INT, 'Program active/inactive status'),
            'program_approval' => new external_value(PARAM_INT, 'Pre-requisites and approval process required ?'),
            'pre_requisites' => new external_value(PARAM_TEXT, ' Pre-requisites and approval process required '),
            'description' => new external_value(PARAM_RAW, 'Description about Program')
                )
             )
        );
    }

/* get programs - ends */


 /* get programinstances - start */
     /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function get_programinstances_parameters() {
        return new external_function_parameters(
                array(
//if I had any parameters, they would be described here. But I don't have any, so this array is empty.
                )
        );
    }

    /**
     * Returns program instances details
     */
    public static function get_programinstances() {
        global $USER, $DB, $CFG;

        //Parameter validation
        $params = self::validate_parameters(self::get_programinstances_parameters(), array());

        $sql = 'SELECT p.* FROM {local_program} p JOIN {local_costcenter} c on p.costcenter = c.id WHERE c.parentid != 0';
        $programs_instances = $DB->get_records_sql($sql);

        $programs_instinfo = array();
        foreach ($programs_instances as $program_instance) {

                $program_inst = array();
                $program_inst['programinstanceid'] = $program_instance->id;
                $program_inst['fullname'] = $program_instance->fullname;
                $program_inst['shortname'] = $program_instance->shortname;
                $program_inst['shortcode'] = $program_instance->shortcode;
                $program_inst['centerid'] = $program_instance->costcenter;
                $program_inst['templateprogramid'] = $program_instance->parentid;
                $program_inst['facultyid'] = $program_instance->facultyid;
                $program_inst['duration'] = $program_instance->duration.' '.$program_instance->duration_format;
                $program_inst['validity'] = $program_instance->validtill;
                $program_inst['offeringyear'] = $program_instance->year;
                $program_inst['admissionstartdate'] = $program_instance->admissionstartdate;
                $program_inst['admissionenddate'] = $program_instance->admissionenddate;
                $program_inst['programlevel'] = ($program_instance->curriculumsemester == '1') ? 'Undergraduate' : 'Post Graduate';
                $program_inst['status'] = $program_instance->status;
                 $program_inst['program_approval'] = $program->program_approval;
                $program_inst['pre_requisites']=$program->pre_requisites;
                $program_inst['description'] = $program_instance->description;

                $programs_instinfo[] = $program_inst;
            }

       return $programs_instinfo;
    }

    /**
     * Returns description of method result value
     * @return external_description
     */
    public static function get_programinstances_returns() {
        return new external_multiple_structure(
                new external_single_structure(
                array(
            'programinstanceid' => new external_value(PARAM_INT, 'Program ID'),
            'fullname' => new external_value(PARAM_TEXT, 'Fullname of Program'),
            'shortname' => new external_value(PARAM_TEXT, 'Shortname of Program'),
            'shortcode' => new external_value(PARAM_TEXT, 'Shortcode of Program'),
            'centerid' => new external_value(PARAM_INT, 'College/Study center ID of Program Instance'),
            'templateprogramid' => new external_value(PARAM_INT, 'Template program from where this program instance is created'),
            'duration' => new external_value(PARAM_TEXT, 'Duration of Program'),
            'validity' => new external_value(PARAM_INT, 'Program validity'),
            'offeringyear' => new external_value(PARAM_INT, 'Year of program offering'),
            'admissionstartdate' => new external_value(PARAM_INT, 'Program admission startdate'),
            'admissionenddate' => new external_value(PARAM_INT, 'Program admission enddate'),
            'programlevel' => new external_value(PARAM_TEXT, 'Program level i.e UG or PG'),
            'status' => new external_value(PARAM_INT, 'Program active/inactive status'),
            'program_approval' => new external_value(PARAM_INT, 'Pre-requisites and approval process required ?'),
            'pre_requisites' => new external_value(PARAM_TEXT, ' Pre-requisites and approval process required '),
            'description' => new external_value(PARAM_RAW, 'Description about Program')
                )
             )
        );
    }

/* get programinstances - ends */


 /* get programinstanceyears - start */
     /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function get_programinstanceyears_parameters() {
        return new external_function_parameters(
                array(
//if I had any parameters, they would be described here. But I don't have any, so this array is empty.
                )
        );
    }

    /**
     * Returns program instances- years details
     */
    public static function get_programinstanceyears() {
        global $USER, $DB, $CFG;

        //Parameter validation
        $params = self::validate_parameters(self::get_programinstanceyears_parameters(), array());

        $sql = 'SELECT y.* FROM {local_program_cc_years} y
				JOIN {local_program} p on y.programid = p.id
				JOIN {local_costcenter} c ON c.id = p.costcenter
				WHERE c.parentid != 0';
        $programs_instyears = $DB->get_records_sql($sql);

        $program_instyearinfo = array();
        foreach ($programs_instyears as $programs_instyear) {

                $program_instyeardet = array();
                $program_instyeardet['yearid'] = $programs_instyear->id;
                $program_instyeardet['year'] = $programs_instyear->year;
                $program_instyeardet['programinstid'] = $programs_instyear->programid;
                $program_instyeardet['cost'] = $programs_instyear->cost;

                $program_instyearinfo[] = $program_instyeardet;
            }

       return $program_instyearinfo;
    }

    /**
     * Returns description of method result value
     * @return external_description
     */
    public static function get_programinstanceyears_returns() {
        return new external_multiple_structure(
                new external_single_structure(
                array(
                'yearid' => new external_value(PARAM_INT, 'Year ID of a Program Instance'),
                'year' => new external_value(PARAM_TEXT, 'Year name'),
                'programinstid' => new external_value(PARAM_INT, 'Program Instance ID of the year'),
                'cost' => new external_value(PARAM_TEXT, 'Cost of all semester courses under that year of a program instance')
                )
             )
        );
    }

/* get programinstanceyears - ends */

/* Enrol Programs to students through WP - starts */

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

    public function program_curriculum_course_enrolments($user, $roleid,
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
    public function enrol_program_addstudent($studentdata) {

      global $DB, $CFG, $USER;
      $pluginname = 'program';
      $params = array();
      $semestercoursessql = 'SELECT c.id, c.id as courseid
                               FROM {course} c
                               JOIN {local_cc_semester_courses} ccsc ON ccsc.courseid = c.id
                              WHERE ccsc.yearid = :yearid ';
      $params['yearid'] = $studentdata->id;
      $semestercourses = $DB->get_records_sql($semestercoursessql, $params);

      $enrolmethod = enrol_get_plugin($pluginname);
      $roleid = $DB->get_field('role', 'id', array('shortname' => 'student'));
      foreach ($semestercourses as $semestercourse) {
        $instance = $DB->get_record('enrol', array('courseid' => $semestercourse->courseid, 'enrol' => $pluginname), '*', MUST_EXIST);
       // foreach ($studentdata->students as $student) {
          $studentdata->student = $studentdata->user;
          $programuser = $DB->record_exists('local_curriculum_users', array('curriculumid' => $studentdata->curriculumid, 'userid' => $studentdata->user));
          if (!$programuser) {
            $programuser = self::enrolusertoprogram($studentdata->programid, $studentdata->curriculumid, $studentdata->user);
          }
          if ($programuser) {
            self::enrol_program_cc_year_enrolments($studentdata);
          }
          self::program_curriculum_course_enrolments($studentdata->user, $roleid, 'enrol', $enrolmethod, $instance);
       // }
      }
      return true;
    }


    public function enrol_program_cc_year_enrolments($enroldata) {
      global $DB, $CFG, $USER;
      $studentsignup = $DB->get_record('local_ccuser_year_signups', array('programid' => $enroldata->programid, 'curriculumid' => $enroldata->curriculumid, 'yearid' => $enroldata->id, 'userid' => $enroldata->student));
      if (empty($studentsignup)) {
        $signupdata = new stdClass();
        $signupdata->userid = $enroldata->student;
        $signupdata->yearid = $enroldata->id;
        $signupdata->curriculumid = $enroldata->curriculumid;
        $signupdata->programid = $enroldata->programid;
        $signupdata->usercreated = $USER->id;
        $signupdata->timecreated = time();
        $DB->insert_record('local_ccuser_year_signups', $signupdata);

        $useryear_enrollog = new stdClass();
        $useryear_enrollog->userid = $enroldata->student;
        $useryear_enrollog->yearid = $enroldata->id;
        $useryear_enrollog->curriculumid = $enroldata->curriculumid;
        $useryear_enrollog->programid = $enroldata->programid;
        $useryear_enrollog->orderid = $enroldata->orderid;
        $useryear_enrollog->timecreated = time();
        $useryear_enrollog->timemodified = time();
        $useryear_enrollog->usermodified = 2;
        $res = $DB->insert_record('local_userenrolments_log', $useryear_enrollog);

        $userdetails_update = new stdclass;
        $userdetails_update->id = $enroldata->student;
        $userdetails_update->open_employeeid = $enroldata->user_uniqueid;
		$userdetails_update->idnumber = $enroldata->user_uniqueid;
        $userdetails_update->open_departmentid = $enroldata->universityid;
        $universityid = $DB->get_field('local_costcenter', 'parentid', array('id' => $enroldata->universityid));
        $userdetails_update->open_costcenterid = $universityid;
        $studentroleid = $DB->get_field('role', 'id', array('shortname' => 'student'));
        $userdetails_update->open_role = $studentroleid;
        $userdetails_update->open_employee = 2;
        $DB->update_record('user',$userdetails_update);
      }
      return true;
    }

    public static function enrol_program_parameters() {
        return new external_function_parameters(
                array(
                    'enrolments' => new external_multiple_structure(
                            new external_single_structure(
                                array(
                                    'yearid' => new external_value(PARAM_INT, 'Year of the program the user has paid fee and is getting enrolled to the courses under that Year'),
                                    'userid' => new external_value(PARAM_INT, 'Incoming users moodle userid'),
                                    'orderid' => new external_value(PARAM_INT, 'Orderid of the payment after success on WP')
                                )
                            )
                    )
                )
        );
    }
    public static function enrol_program($enrolments) {
        global $USER, $DB, $CFG;

        require_once($CFG->libdir . '/enrollib.php');

        $params = self::validate_parameters(self::enrol_program_parameters(), array('enrolments' => $enrolments));

        $transaction = $DB->start_delegated_transaction();

        foreach ($params['enrolments'] as $enrolment) {

                $student_enroldata = new stdClass();
                $student_enroldata = $DB->get_record('local_program_cc_years', array('id'=> $enrolment['yearid']), 'id, programid, curriculumid');
                $universityid = $DB->get_record('local_program', array('id'=> $student_enroldata->programid), 'costcenter');
                $user_uniqueid = $DB->get_record('user', array('id'=> $enrolment['userid']), 'username');

                $student_enroldata->user = $enrolment['userid'];
                $student_enroldata->orderid = $enrolment['orderid'];
                $student_enroldata->universityid = $universityid->costcenter;
                $student_enroldata->user_uniqueid = $user_uniqueid->username;

                if ($student_enroldata) {
                    $stu_added_res = self::enrol_program_addstudent($student_enroldata);
                    if ($stu_added_res > 0) {
                        $return = array(
                        'ErrorID' => $stu_added_res,
                        'ErrorMessage' => "Success");
                    } else {
                        $return = array(
                        'ErrorID' => 0,
                        'ErrorMessage' => "Failed");
                    }
                } else {
                    // Generate a warning.
                    throw new moodle_exception('missingfaculty', 'local_program');
                }
        }

        $transaction->allow_commit();

        return $return;
    }

    public static function enrol_program_returns() {
        return new external_single_structure(array(
            'ErrorID' => new external_value(PARAM_INT, 'Error ID 1 is Success and 0 is Failure'),
            'ErrorMessage' => new external_value(PARAM_TEXT, 'Success or Failure message'),
        ));
    }

/* Enrol Programs to students through WP - ends */
    public function suspend_program_instance_parameters(){
        return new external_function_parameters(
            array(
                'id' => new external_value(PARAM_INT, 'id', 0),
                'contextid' => new external_value(PARAM_INT, 'The context id for the evaluation', false)
            )
        );
    }
    public function suspend_program_instance($id,$contextid){
        global $DB;

        $program = $DB->get_record('local_program', array('id' => $id));
        if($program){
            if($program->status){
                $status = 0;
            }else{
                $status = 1;
            }
            $DB->execute('UPDATE {local_program} SET `status` = :status WHERE id = :id', array('id' => $program->id, 'status' => $status));
            $return = TRUE;
        }else {
            throw new moodle_exception('Error in inactivating');
            $return = FALSE;
        }
        return $return;
    }
    public function suspend_program_instance_returns(){
        return new external_value(PARAM_BOOL, 'return');
    }

    /**
    * [data_for_program_courses_parameters description]
     * @return parameters for data_for_program_courses
     */
    public static function userprograms_parameters() {
        return new external_function_parameters(
            array('status' => new external_value(PARAM_TEXT, 'status'),
                  'search' =>  new external_value(PARAM_TEXT, 'search', VALUE_OPTIONAL, ''),
                  'page' =>  new external_value(PARAM_INT, 'page', VALUE_OPTIONAL, 0),
                  'perpage' =>  new external_value(PARAM_INT, 'perpage', VALUE_OPTIONAL, 15)
            )
        );
    }

    public static function userprograms($status, $search = '', $page = 0, $perpage = 15) {
        global $PAGE;

        $params = self::validate_parameters(self::userprograms_parameters(), array(
            'status' => $status, 'search' => $search, 'page' => $page, 'perpage' => $perpage
        ));

        $PAGE->set_context(context_system::instance());
        $renderable = new \block_userdashboard\output\program_courses($status, $search, $page * $perpage, $perpage);
        $output = $PAGE->get_renderer('block_userdashboard');

        $data = $renderable->export_for_template($output);
        $programs = json_decode($data->inprogress_elearning);

        return array('programs' => $programs, 'total' => $data->total);
    }

    public static function userprograms_returns() {
        return new external_single_structure(array (
                'total' => new external_value(PARAM_INT, 'Number of enrolled courses.'),
                'programs' => new external_multiple_structure(
                    new external_single_structure(
                        array(
                            'programid' => new external_value(PARAM_INT, 'programid'),
                            'programyears' => new external_value(PARAM_INT, 'programyears'),
                            'programcourses' => new external_value(PARAM_INT, 'programcourses'),
                            'bootcamp_fullname' => new external_value(PARAM_RAW, 'bootcamp_fullname'),
                            'bootcampdescription' => new external_value(PARAM_RAW, 'bootcampdescription'),
                            'bootcamp_url' => new external_value(PARAM_URL, 'bootcamp_url'),
                            'curriculumid' => new external_value(PARAM_INT, 'curriculumid')
                        )
                    ), VALUE_DEFAULT, array()
                )
            )
        );
    }

    /**
    * [data_for_program_courses_parameters description]
     * @return parameters for data_for_program_courses
     */
    public static function programsyllabus_parameters() {
        return new external_function_parameters(
            array('programid' => new external_value(PARAM_INT, 'programid'),
                  'curriculumid' =>  new external_value(PARAM_INT, 'curriculumid')
            )
        );
    }

    public static function programsyllabus($programid, $curriculumid) {
        global $PAGE, $CFG;

        $params = self::validate_parameters(self::programsyllabus_parameters(), array(
            'programid' => $programid, 'curriculumid' => $curriculumid
        ));

        $PAGE->set_context(context_system::instance());
        require_once($CFG->dirroot . '/local/program/lib.php');

        $syllabus = get_user_program_syllabus($programid);
        return array('syllabus' => $syllabus);
    }

    public static function programsyllabus_returns() {
        return new external_single_structure(array (
                'syllabus' => new external_multiple_structure(
                    new external_single_structure(
                        array(
                            'yearnames' => new external_value(PARAM_RAW, 'yearnames'),
                            'semesters' => new external_multiple_structure(
                                new external_single_structure(
                                    array(
                                        'semesternames'=> new external_value(PARAM_TEXT, 'semesternames'),
                                        'courses' => new external_multiple_structure(
                                            new external_single_structure(
                                                array(
                                                    'coursenames'=> new external_value(PARAM_TEXT, 'coursenames')
                                                )
                                            ), VALUE_DEFAULT, array()
                                        )
                                    )
                                ), VALUE_DEFAULT, array()
                            )
                        )
                    ), VALUE_DEFAULT, array()
                )
            )
        );
    }

    /**
    * [data_for_program_courses_parameters description]
     * @return parameters for data_for_program_courses
     */
    public static function programcontent_parameters() {
        return new external_function_parameters(
            array('programid' => new external_value(PARAM_INT, 'programid'),
                  'curriculumid' =>  new external_value(PARAM_INT, 'curriculumid')
            )
        );
    }

    public static function programcontent($programid, $curriculumid) {
        global $PAGE, $CFG;

        $params = self::validate_parameters(self::programcontent_parameters(), array(
            'programid' => $programid, 'curriculumid' => $curriculumid
        ));

        $PAGE->set_context(context_system::instance());

        $programcontent = (new program)->programcontent($curriculumid, $programid);
        // print_object($programcontent);exit;
        return array('programcontent' => $programcontent);
    }

    public static function programcontent_returns() {
        return new external_single_structure(array (
                'programcontent' =>
                    new external_single_structure(
                        array(
                            'curriculum' =>
                                new external_single_structure(
                                    array(
                                        'id' => new external_value(PARAM_INT, 'id'),
                                        'name' => new external_value(PARAM_RAW, 'name'),
                                        'shortname' => new external_value(PARAM_RAW, 'shortname'),
                                        'program' => new external_value(PARAM_INT, 'program'),
                                        'description' => new external_value(PARAM_RAW, 'description'),
                                        'visible' => new external_value(PARAM_INT, 'visible'),
                                        'points' => new external_value(PARAM_INT, 'points'),
                                        'costcenter' => new external_value(PARAM_INT, 'costcenter'),
                                        'curriculum_type' => new external_value(PARAM_INT, 'curriculum_type'),
                                        'status' => new external_value(PARAM_INT, 'status'),
                                        'enrolled_users' => new external_value(PARAM_INT, 'enrolled_users'),
                                        'active_users' => new external_value(PARAM_INT, 'active_users'),
                                        'total_hours' => new external_value(PARAM_INT, 'total_hours'),
                                        'totalsessions' => new external_value(PARAM_INT, 'totalsessions'),
                                        'activeusers' => new external_value(PARAM_INT, 'activeusers'),
                                        'totalsemesters'=> new external_value(PARAM_INT, 'totalsemesters'),
                                        'totalcourses' => new external_value(PARAM_INT, 'totalcourses'),
                                        'activesessions' => new external_value(PARAM_INT, 'activesessions'),
                                        'startdate' => new external_value(PARAM_INT, 'startdate'),
                                        'enddate' => new external_value(PARAM_INT, 'enddate'),
                                        'trainingfeedbackid' => new external_value(PARAM_INT, 'trainingfeedbackid'),
                                        'training_feedback_score' => new external_value(PARAM_INT, 'training_feedback_score'),
                                        'capacity' => new external_value(PARAM_INT, 'capacity'),
                                        'morethan_capacity_allow' => new external_value(PARAM_INT, 'morethan_capacity_allow'),
                                        'cr_category' => new external_value(PARAM_INT, 'cr_category'),
                                        'manage_approval' => new external_value(PARAM_INT, 'manage_approval'),
                                        'allow_multi_session' => new external_value(PARAM_INT, 'allow_multi_session'),
                                        'nomination_startdate' => new external_value(PARAM_INT, 'nomination_startdate'),
                                        'nomination_enddate' => new external_value(PARAM_INT, 'nomination_enddate'),
                                        'department' => new external_value(PARAM_INT, 'department'),
                                        'curriculumlogo' => new external_value(PARAM_INT, 'curriculumlogo'),
                                        'approvalreqd' => new external_value(PARAM_INT, 'approvalreqd'),
                                        'curriculum_publish_status' => new external_value(PARAM_INT, 'curriculum_publish_status'),
                                        'duration' => new external_value(PARAM_INT, 'duration'),
                                        'duration_format' => new external_value(PARAM_TEXT, 'duration_format'),
                                        'timecreated' => new external_value(PARAM_INT, 'timecreated'),
                                        'usercreated' => new external_value(PARAM_INT, 'usercreated'),
                                        'timemodified' => new external_value(PARAM_INT, 'timemodified'),
                                        'usermodified' => new external_value(PARAM_INT, 'usermodified'),
                                        'parentid' => new external_value(PARAM_INT, 'parentid'),
                                        'admissionenddate' => new external_value(PARAM_INT, 'admissionenddate'),
                                        'completed_users' => new external_value(PARAM_INT, 'completed_users')

                                ), VALUE_DEFAULT, array()
                            ),
                            'programdata' =>
                                new external_single_structure(
                                    array(
                                        'id' => new external_value(PARAM_INT, 'id'),
                                        'fullname' => new external_value(PARAM_RAW, 'fullname'),
                                        'shortname' => new external_value(PARAM_RAW, 'shortname'),
                                        'shortcode' => new external_value(PARAM_RAW, 'shortcode'),
                                        'costcenter' => new external_value(PARAM_INT, 'costcenter'),
                                        'parentid' => new external_value(PARAM_INT, 'parentid'),
                                        'departmentid' => new external_value(PARAM_INT, 'departmentid'),
                                        'curriculumid' => new external_value(PARAM_INT, 'curriculumid'),
                                        'type' => new external_value(PARAM_INT, 'type'),
                                        'duration' => new external_value(PARAM_INT, 'duration'),
                                        'duration_format' => new external_value(PARAM_TEXT, 'duration_format'),
                                        'sortorder' => new external_value(PARAM_INT, 'sortorder'),
                                        'curriculumsemester' => new external_value(PARAM_INT, 'curriculumsemester'),
                                        'facultyid' => new external_value(PARAM_INT, 'facultyid'),
                                        'admissionstartdate' => new external_value(PARAM_INT, 'admissionstartdate'),
                                        'admissionenddate'=> new external_value(PARAM_INT, 'admissionenddate'),
                                        'description' => new external_value(PARAM_RAW, 'description'),
                                        'status' => new external_value(PARAM_INT, 'status'),
                                        'year' => new external_value(PARAM_INT, 'year'),
                                        'validtill' => new external_value(PARAM_INT, 'validtill'),
                                        'publishstatus' => new external_value(PARAM_INT, 'publishstatus'),
                                        'program_approval' => new external_value(PARAM_INT, 'program_approval'),
                                        'pre_requisites' => new external_value(PARAM_RAW, 'pre_requisites'),
                                        'timecreated' => new external_value(PARAM_INT, 'timecreated'),
                                        'usercreated' => new external_value(PARAM_INT, 'usercreated'),
                                        'timemodified' => new external_value(PARAM_INT, 'timemodified'),
                                        'usermodified' => new external_value(PARAM_INT, 'usermodified')

                                ), VALUE_DEFAULT, array()
                            ),
                            'curriculumcompletion' => new external_value(PARAM_BOOL, 'curriculumcompletion', VALUE_DEFAULT, 0),
                            'curriculumcompletionstatus' => new external_value(PARAM_BOOL, 'curriculumcompletionstatus', VALUE_DEFAULT, 0),
                            'yearid' => new external_value(PARAM_INT, 'yearid'),
                            'curriculumsemesteryearscontent' =>
                                new external_single_structure(
                                    array(
                                        'canviewsemesteryear'=> new external_value(PARAM_BOOL, 'canviewsemesteryear'),
                                        'yearid' => new external_value(PARAM_INT, 'yearid'),
                                        'userview'=> new external_value(PARAM_BOOL, 'userview'),
                                        'activetab' => new external_value(PARAM_INT, 'activetab'),
                                        'curriculumsemesteryears' => new external_multiple_structure(
                                            new external_single_structure(
                                                array(
                                                    'id' => new external_value(PARAM_INT, 'id'),
                                                    'year' => new external_value(PARAM_RAW, 'year'),
                                                    'programid' => new external_value(PARAM_INT, 'programid'),
                                                    'curriculumid' => new external_value(PARAM_INT, 'curriculumid'),
                                                    'active' => new external_value(PARAM_INT, 'active'),
                                                    'disabled' => new external_value(PARAM_INT, 'disabled'),
                                                    'progressstatus' => new external_value(PARAM_INT, 'progressstatus'),
                                                    'cost' => new external_value(PARAM_RAW, 'cost'),
                                                    'yearcontent' => new external_single_structure(
                                                            array(
                                                                'canviewsemester' => new external_value(PARAM_BOOL, 'canviewsemester', VALUE_OPTIONAL),
                                                                'yearid' => new external_value(PARAM_INT, 'yearid', VALUE_OPTIONAL),
                                                                'userview' => new external_value(PARAM_BOOL, 'userview', VALUE_OPTIONAL),
                                                                'coursesadded' => new external_value(PARAM_BOOL, 'coursesadded', VALUE_OPTIONAL),
                                                                'curriculumsemesters' => new external_multiple_structure(
                                                                    new external_single_structure(
                                                                        array(
                                                                            'id' => new external_value(PARAM_INT, 'id', VALUE_OPTIONAL),
                                                                            'curriculumid' => new external_value(PARAM_INT, 'curriculumid', VALUE_OPTIONAL),
                                                                            'yearid' => new external_value(PARAM_INT, 'yearid', VALUE_OPTIONAL),
                                                                            'semester' => new external_value(PARAM_RAW, 'semester', VALUE_OPTIONAL),
                                                                            'description' => new external_value(PARAM_RAW, 'description', VALUE_OPTIONAL),
                                                                            'status' => new external_value(PARAM_INT, 'status', VALUE_OPTIONAL),
                                                                            'programid' => new external_value(PARAM_INT, 'programid', VALUE_OPTIONAL),
                                                                            'totalcourses' => new external_value(PARAM_INT, 'totalcourses', VALUE_OPTIONAL),
                                                                            'position' => new external_value(PARAM_RAW, 'position', VALUE_OPTIONAL),
                                                                            'totalusers' => new external_value(PARAM_RAW, 'totalusers', VALUE_OPTIONAL),
                                                                            'activeusers' => new external_value(PARAM_INT, 'activeusers', VALUE_OPTIONAL),
                                                                            'totalhours' => new external_value(PARAM_INT, 'totalhours', VALUE_OPTIONAL),
                                                                            'totalsessions' => new external_value(PARAM_INT, 'totalsessions', VALUE_OPTIONAL),
                                                                            'activesessions' => new external_value(PARAM_INT, 'activesessions', VALUE_OPTIONAL),
                                                                            'usercreated' => new external_value(PARAM_INT, 'usercreated', VALUE_OPTIONAL),
                                                                            'timecreated' => new external_value(PARAM_INT, 'timecreated', VALUE_OPTIONAL),
                                                                            'usermodified' => new external_value(PARAM_INT, 'usermodified', VALUE_OPTIONAL),
                                                                            'timemodified' => new external_value(PARAM_INT, 'timemodified', VALUE_OPTIONAL),
                                                                            'semesterid' => new external_value(PARAM_INT, 'semesterid', VALUE_OPTIONAL),
                                                                            'semcompletionstatus' => new external_value(PARAM_BOOL, 'semcompletionstatus', VALUE_OPTIONAL),
                                                                            'parentsemcmplstatus' => new external_value(PARAM_BOOL, 'parentsemcmplstatus', VALUE_OPTIONAL),
                                                                            'ccyearfirstsem' => new external_value(PARAM_BOOL, 'ccyearfirstsem', VALUE_OPTIONAL),
                                                                            'usersemcompletionstatus' => new external_value(PARAM_BOOL, 'usersemcompletionstatus', VALUE_OPTIONAL),
                                                                            'semesterid' => new external_value(PARAM_INT, 'semesterid', VALUE_OPTIONAL),
                                                                            'semcompletionstatus' => new external_value(PARAM_BOOL, 'semcompletionstatus', VALUE_OPTIONAL),
                                                                            'courses' => new external_multiple_structure(
                                                                                new external_single_structure(
                                                                                    array(
                                                                                        'id' => new external_value(PARAM_INT, 'id'),
                                                                                        'courseid' => new external_value(PARAM_INT, 'courseid'),
                                                                                        'course' => new external_value(PARAM_RAW, 'course', VALUE_OPTIONAL),
                                                                                        'importstatus' => new external_value(PARAM_INT, 'importstatus', VALUE_OPTIONAL),
                                                                                        'coursetype' => new external_value(PARAM_INT, 'coursetype', VALUE_OPTIONAL),
                                                                                        'cc_courseid' => new external_value(PARAM_INT, 'cc_courseid', VALUE_OPTIONAL),
                                                                                        'completioncriteria' => new external_value(PARAM_INT, 'completioncriteria', VALUE_OPTIONAL)
                                                                                    )
                                                                                ), VALUE_DEFAULT, array()
                                                                            ),
                                                                            'offlineclassrooms' => new external_multiple_structure(
                                                                                new external_single_structure(
                                                                                    array(
                                                                                        'cc_courseid' => new external_value(PARAM_INT, 'cc_courseid', VALUE_OPTIONAL),
                                                                                        'classname' => new external_value(PARAM_RAW, 'classname', VALUE_OPTIONAL),
                                                                                        'requiredsessions' => new external_value(PARAM_INT, 'requiredsessions', VALUE_OPTIONAL),
                                                                                        'courseid' => new external_value(PARAM_INT, 'courseid', VALUE_OPTIONAL),
                                                                                        'attendancecount' => new external_value(PARAM_INT, 'attendancecount', VALUE_OPTIONAL)
                                                                                    )
                                                                                ), VALUE_DEFAULT, array()
                                                                            )
                                                                        )
                                                                    ), VALUE_OPTIONAL
                                                                ), VALUE_OPTIONAL, array()
                                                            )
                                                        ), VALUE_DEFAULT, array()

                                                )
                                            ), VALUE_DEFAULT, array()
                                        ), VALUE_DEFAULT, array()
                                    )
                                ), VALUE_DEFAULT, array()

                        )
                    ), VALUE_DEFAULT, array()
                //)
            )
        );
    }

    /**
    * [data_for_program_courses_parameters description]
     * @return parameters for data_for_program_courses
     */
    public static function classroomcontent_parameters() {
        return new external_function_parameters(
            array('programid' => new external_value(PARAM_INT, 'programid'),
                  'curriculumid' =>  new external_value(PARAM_INT, 'curriculumid'),
                  'yearid' => new external_value(PARAM_INT, 'yearid'),
                  'semesterid' =>  new external_value(PARAM_INT, 'semesterid'),
                  'bclcid' =>  new external_value(PARAM_INT, 'bclcid'),
                  'search' =>  new external_value(PARAM_RAW, 'search', VALUE_OPTIONAL, ''),
                  'page' =>  new external_value(PARAM_INT, 'page', VALUE_OPTIONAL, 0)
            )
        );
    }

    public static function classroomcontent($programid, $curriculumid, $yearid, $semesterid, $bclcid, $search = '', $page = 0) {
        global $PAGE, $CFG, $DB;

        $params = self::validate_parameters(self::classroomcontent_parameters(), array(
            'programid' => $programid, 'curriculumid' => $curriculumid, 'yearid' => $yearid, 'semesterid' => $semesterid,
            'bclcid' => $bclcid, 'search' => $search, 'page' => $page
        ));
        $perpage = 30;
        $stable = new stdClass();
        $stable->search = $search;
        $stable->start = $page * $perpage;
        $stable->length = $perpage;
        $stable->thead = false;

        $bclcdata = new stdClass();
        $bclcdata->curriculumid = $curriculumid;
        $bclcdata->semesterid = $semesterid;
        $bclcdata->bclcid = $bclcid;
        $bclcdata->programid = $programid;
        $bclcdata->yearid = $yearid;
        $bclcdata->ccses_action = 'class_sessions';

        $PAGE->set_context(context_system::instance());

        $classroomcontent = (new program)->curriculumsessions($bclcdata, $stable);
        $sessions = array();
        foreach ($classroomcontent['sessions'] as $session) {
            $trainer = $DB->get_record('user', array('id' => $session->trainerid));
            $trainer->profileimageurl = (new user_picture($trainer))->get_url($PAGE)->out(false);
            $session->trainer = (array)$trainer;
        }

        return array('sessions' => $classroomcontent['sessions'], 'total' => $classroomcontent['sessionscount']);
    }
    public static function classroomcontent_returns() {
        return new external_single_structure(
            array(
                'sessions' => new external_multiple_structure(
                    new external_single_structure(
                        array(
                            'id' => new external_value(PARAM_INT, 'Classroom ID'),
                            'name' => new external_value(PARAM_RAW, 'Classroom Name'),
                            'costcenter' => new external_value(PARAM_INT, 'costcenter'),
                            'curriculumid' => new external_value(PARAM_INT, 'curriculumid'),
                            'semesterid' => new external_value(PARAM_INT, 'semesterid'),
                            'programid' => new external_value(PARAM_INT, 'programid'),
                            'bclcid' => new external_value(PARAM_INT, 'bclcid'),
                            'yearid' => new external_value(PARAM_INT, 'yearid'),
                            'courseid' => new external_value(PARAM_INT, 'courseid'),
                            'onlinesession' => new external_value(PARAM_INT, 'onlinesession'),
                            'capacity' => new external_value(PARAM_INT, 'capacity'),
                            'description' => new external_value(PARAM_RAW, 'description'),
                            'datetimeknown' => new external_value(PARAM_INT, 'datetimeknown'),
                            'duration' => new external_value(PARAM_FLOAT, 'duration'),
                            'sessiontimezone' => new external_value(PARAM_RAW, 'sessiontimezone'),
                            'timestart' => new external_value(PARAM_INT, 'timestart'),
                            'timefinish' => new external_value(PARAM_INT, 'timefinish'),
                            'nomination_startdate' => new external_value(PARAM_INT, 'nomination_startdate'),
                            'nomination_enddate' => new external_value(PARAM_INT, 'nomination_enddate'),
                            'dailysessionstarttime' => new external_value(PARAM_RAW, 'dailysessionstarttime'),
                            'dailysessionendtime' => new external_value(PARAM_RAW, 'dailysessionendtime'),
                            'attendance_status' => new external_value(PARAM_INT, 'attendance_status'),
                            'trainerid' => new external_value(PARAM_INT, 'trainerid'),
                            'institute_type' => new external_value(PARAM_INT, 'institute_type'),
                            'instituteid' => new external_value(PARAM_RAW, 'instituteid'),
                            'roomid' => new external_value(PARAM_INT, 'roomid'),
                            'moduletype' => new external_value(PARAM_RAW, 'moduletype'),
                            'moduleid' => new external_value(PARAM_INT, 'moduleid'),
                            'usercreated' => new external_value(PARAM_INT, 'usercreated'),
                            'mincapacity' => new external_value(PARAM_INT, 'mincapacity'),
                            'maxcapacity' => new external_value(PARAM_INT, 'maxcapacity'),
                            'totalusers' => new external_value(PARAM_INT, 'totalusers'),
                            'activeusers' => new external_value(PARAM_INT, 'activeusers'),
                            'sessiontype' => new external_value(PARAM_INT, 'sessiontype'),
                            'timecreated' => new external_value(PARAM_INT, 'timecreated'),
                            'usermodified' => new external_value(PARAM_INT, 'usermodified'),
                            'timemodified' => new external_value(PARAM_INT, 'timemodified'),
                            'room' => new external_value(PARAM_RAW, 'room'),
                            'trainer' => new external_single_structure(
                                array(
                                    'id'  => new external_value(PARAM_RAW, 'trainer id'),
                                    'picture'  => new external_value(PARAM_RAW, 'trainer picture'),
                                    'firstname'  => new external_value(PARAM_RAW, 'trainer firstname'),
                                    'lastname'  => new external_value(PARAM_RAW, 'trainer lastname'),
                                    'firstnamephonetic'  => new external_value(PARAM_RAW, 'trainer firstname phonetic', VALUE_OPTIONAL),
                                    'lastnamephonetic'  => new external_value(PARAM_RAW, 'trainer lastname phonetic', VALUE_OPTIONAL),
                                    'middlename'  => new external_value(PARAM_RAW, 'trainer middlename', VALUE_OPTIONAL),
                                    'alternatename'  => new external_value(PARAM_RAW, 'trainer alternatename', VALUE_OPTIONAL),
                                    'imagealt'  => new external_value(PARAM_RAW, 'trainer imagealt', VALUE_OPTIONAL),
                                    'email'  => new external_value(PARAM_RAW, 'trainer email'),
                                    'profileimageurl' => new external_value(PARAM_RAW, 'trainer profile image')
                                )
                            )
                        )
                    )
                ),
                'total' => new external_value(PARAM_INT, 'Total Records'),
            )
        );
    }
}

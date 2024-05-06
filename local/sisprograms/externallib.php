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
 * @package    lmsapi
 * @category   external
 * @copyright  2019 Pramod Kumar K <pramod@eabyas.in>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();
global $PAGE, $OUTPUT;
require_once("$CFG->libdir/externallib.php");

require_once($CFG->dirroot . '/user/lib.php');

class local_create_user_and_enrolto_onlinecourse_external extends external_api {
    /* Create and enrol users to courses ends - starts */

    public static function create_user_and_enrolto_onlinecourse_parameters() {
        return new external_function_parameters(
                array(
            'stuenrolment' => new external_single_structure(
                    array(
                'student_prn' => new external_value(PARAM_ALPHANUMEXT, 'Student permanenet registration number used as login username on LMS'),
                'firstname' => new external_value(core_user::get_property_type('firstname'), 'The first name(s) of the user'),
                'lastname' => new external_value(core_user::get_property_type('lastname'), 'The family name of the user'),
                'email' => new external_value(core_user::get_property_type('email'), 'A valid and unique email address'),
                'coursecode' => new external_value(PARAM_ALPHANUMEXT, 'Online course to wich student has to be enrolled'),
                'role' => new external_value(PARAM_TEXT, 'Role of user'),
                'mobile' => new external_value(PARAM_INT, 'Mobile number of user'),
                'gender' => new external_value(PARAM_TEXT, 'Gender of user'),
                'city' => new external_value(PARAM_TEXT, 'City of user')
                    )
            )
                )
        );
    }

    public static function create_user_and_enrolto_onlinecourse($enrolment) {
        global $USER, $DB, $CFG;

        require_once($CFG->libdir . '/enrollib.php');

        $params = self::validate_parameters(self::create_user_and_enrolto_onlinecourse_parameters(), array('stuenrolment' => $enrolment));

        $transaction = $DB->start_delegated_transaction();

        try {

            $stu_added_res = self::createuser_enrolcourse($enrolment);

            if ($stu_added_res['errorcode'] > 0) {
                $finalreponse = array();
                $finalreponse = $stu_added_res;
            } else {
                $finalreponse = array();
                $finalreponse = $stu_added_res;
            }
        } catch (Exception $e) {
            throw new moodle_exception($e->getMessage());
        }

        $transaction->allow_commit();
        return $finalreponse;
    }

    public static function create_user_and_enrolto_onlinecourse_returns() {
        return new external_single_structure(
                array(
            'exception' => new external_value(PARAM_TEXT, 'Exception type'),
            'errorcode' => new external_value(PARAM_INT, 'Error code 1 is Success and 0 is Failure'),
            'message' => new external_value(PARAM_TEXT, 'Success or Failure message'),
            'warnings' => new external_warnings()
                )
        );
    }

    public function createuser_enrolcourse($enrollment) {
        global $DB;

        if (!$enrol_manual = enrol_get_plugin('manual')) {
            throw new coding_exception('Can not instantiate enrol_manual');
        }
        if (!empty($enrollment['coursecode']) && !empty($enrollment['student_prn'])) {

            $courseid = $DB->get_field('course', 'id', array('shortname' => $enrollment['coursecode']));
            $costcenterid = $DB->get_field('local_sisonlinecourses', 'costcenterid', array('coursecode' => $enrollment['coursecode']));
            $siscourseid = $DB->get_field('local_sisonlinecourses', 'courseid', array('coursecode' => $enrollment['coursecode']));
            $sisrecord = $DB->get_record('local_sisonlinecourses', array('coursecode' => $enrollment['coursecode']));
            $programname = $DB->get_field('local_sisprograms', 'fullname', array('id' => $sisrecord->programid));
            $schoolname = $DB->get_field('local_costcenter', 'fullname', array('id' => $sisrecord->costcenterid));
            $coursename = $DB->get_field('course', 'fullname', array('id' => $sisrecord->courseid));

            if (!$courseid || !$siscourseid) {
                $warning = array();
                $warning['warningcode'] = '0';
                $warning['message'] = "The given coursecode '" . $enrollment['coursecode'] . "' does not exist on LMS.";
                $warnings[] = $warning;

                $response = array();
                $response['exception'] = 'moodle_exception';
                $response['errorcode'] = 0;
                $response['message'] = 'Failed';
                $result = array();
                $result = $response;
                $result['warnings'] = $warnings;
                return $result;
            }

            $sisprnuserid = $DB->get_field('user', 'id', array('idnumber' => $enrollment['student_prn'], 'deleted' => 0, 'suspended' => 0));
            $sisuseremail = $DB->get_field('user', 'id', array('email' => $enrollment['email'], 'deleted' => 0, 'suspended' => 0));

            if (!$sisprnuserid && !$sisuseremail) {
                $userdata = new stdClass();
                $userdata->confirmed = 1;
                $userdata->deleted = 0;
                $userdata->auth = 'manual';
                $userdata->policyagreed = 0;
                $userdata->suspended = 0;
                $userdata->mnethostid = 1;
                $userdata->username = strtolower($enrollment['student_prn']);
                $userdata->password = 'Welcome#3';
                $userdata->firstname = $enrollment['firstname'];
                $userdata->lastname = $enrollment['lastname'];
                $userdata->idnumber = $enrollment['student_prn'];
                $userdata->open_employeeid = $enrollment['student_prn'];
                $userdata->email = $enrollment['email'];
                $userdata->phone1 = $enrollment['mobile'];
                $userdata->city = $enrollment['city'];
                $userdata->open_costcenterid = $costcenterid;
                // Fetching roleid //
                $roleid = $DB->get_field('role', 'id', array('shortname' => $enrollment['role']));
                $userdata->open_role = $roleid;
                $sisprnuserid = user_create_user($userdata);
                $sisuserdata = new stdClass();
                $sisuserdata->sisprnid = $enrollment['student_prn'];
                $sisuserdata->roleid = $roleid;
                $sisuserdata->costcenterid = $costcenterid;
                $sisuserdata->mdluserid = $sisprnuserid;
                $sisuserdata->gender = $enrollment->gender;
                $sisuserdata->timecreated = time();
                $sisuserdata->timemodified = 0;
                $sisuserdata->usercreated = $USER->id;
                $sisuserdata->usermodified = 0;
                $sisuserid = $DB->insert_record('local_sisuserdata', $sisuserdata);
                $contextid = 1;
                if ($roleid != 5 && $enrollment['role'] != 'student') {
                    $assignedrole = role_assign($roleid, $sisprnuserid, SITEID);
                }
            }

            if ($sisprnuserid) {
                if ($enrollment['role']) {
                    $roleid = $DB->get_field('role', 'id', array('shortname' => $enrollment['role']));
                }
                $enrolid = $DB->get_field('enrol', 'id', array('courseid' => $courseid, 'enrol' => 'manual'));
                if (!$DB->record_exists('user_enrolments', array('enrolid' => $enrolid, 'userid' => $sisprnuserid))) {
                    $instance = $DB->get_record('enrol', array('id' => $enrolid, 'enrol' => 'manual'), '*', MUST_EXIST);
                    $enrol_manual->enrol_user($instance, $sisprnuserid, $roleid);
                } else {
                    $coursename = $DB->get_field('course', 'fullname', array('id' => $courseid));
                    $coursecode = $DB->get_field('course', 'shortname', array('id' => $courseid));
                    $warning = array();
                    $warning['warningcode'] = '0';
                    $warning['message'] = "User already enrolled to '" . $coursename . " (" . $coursecode . ")' course on LMS.";
                    $warnings[] = $warning;

                    $response = array();
                    $response['exception'] = 'moodle_exception';
                    $response['errorcode'] = 0;
                    $response['message'] = 'Failed';
                    $result = array();
                    $result = $response;
                    $result['warnings'] = $warnings;
                    return $result;
                }
                $result = $DB->record_exists('local_courseenrolments', array('courseid' => $courseid, 'mdluserid' => $sisprnuserid, 'programid' => $sisrecord->programid));
                if (!$DB->record_exists('local_courseenrolments', array('courseid' => $courseid, 'mdluserid' => $sisprnuserid, 'programid' => $sisrecord->programid))) {
                    $courseenrol = new stdClass();
                    $courseenrol->courseid = $courseid;
                    $courseenrol->costcenterid = $costcenterid;
                    $courseenrol->programid = $sisrecord->programid;
                    $courseenrol->mdluserid = $sisprnuserid;
                    $courseenrol->sisuserid = $sisuserid;
                    $courseenrol->roleid = $roleid;
                    $courseenrol->coursename = $coursename;
                    $courseenrol->schoolname = $schoolname;
                    $courseenrol->programname = $programname;
                    $courseenrol->timecreated = time();
                    $courseenrol->timemodified = 0;
                    $courseenrol->usercreated = $USER->id;
                    $courseenrol->usermodified = 0;
                    $enrolmentid = $DB->insert_record('local_courseenrolments', $courseenrol);
                }
            }
        }

        $warning = array();
        $warning['warningcode'] = '1';
        $warning['message'] = "no warnings";
        $warnings[] = $warning;

        $response = array();
        $response['exception'] = 'no exception';
        $response['errorcode'] = 1;
        $response['message'] = 'Success';
        $result = array();
        $result = $response;
        $result['warnings'] = $warnings;

        return $result;
    }

    /* Create and enrol users to courses ends */
}

class local_create_faculty_and_enrolto_onlinecourse_external extends external_api {
    /* Create and enrol faculty users to courses ends - starts */

    public static function create_faculty_and_enrolto_onlinecourse_parameters() {
        return new external_function_parameters(
                array(
            'facenrolment' => new external_single_structure(
                    array(
                'employeecode' => new external_value(PARAM_ALPHANUMEXT, 'Student permanenet registration number used as login username on LMS'),
                'firstname' => new external_value(core_user::get_property_type('firstname'), 'The first name(s) of the user'),
                'lastname' => new external_value(core_user::get_property_type('lastname'), 'The family name of the user'),
                'email' => new external_value(core_user::get_property_type('email'), 'A valid and unique email address'),
                'coursecode' => new external_value(PARAM_ALPHANUMEXT, 'Online course to wich student has to be enrolled'),
                'role' => new external_value(PARAM_TEXT, 'Role of user'),
                'mobile' => new external_value(PARAM_INT, 'Mobile number of user'),
                'gender' => new external_value(PARAM_TEXT, 'Gender of user'),
                'city' => new external_value(PARAM_TEXT, 'City of user')
                    )
            )
                )
        );
    }

    public static function create_faculty_and_enrolto_onlinecourse($enrolment) {
        global $USER, $DB, $CFG;

        require_once($CFG->libdir . '/enrollib.php');

        $params = self::validate_parameters(self::create_faculty_and_enrolto_onlinecourse_parameters(), array('facenrolment' => $enrolment));

        $transaction = $DB->start_delegated_transaction();

        try {
            $fac_added_res = self::createfac_enrolcourse($enrolment);
            if ($fac_added_res['errorcode'] > 0) {
                $finalreponse = array();
                $finalreponse = $fac_added_res;
            } else {
                $finalreponse = array();
                $finalreponse = $fac_added_res;
            }
        } catch (Exception $e) {
            throw new moodle_exception($e->getMessage());
        }

        $transaction->allow_commit();
        return $finalreponse;
    }

    public static function create_faculty_and_enrolto_onlinecourse_returns() {
        return new external_single_structure(
                array(
            'exception' => new external_value(PARAM_TEXT, 'Exception type'),
            'errorcode' => new external_value(PARAM_INT, 'Error code 1 is Success and 0 is Failure'),
            'message' => new external_value(PARAM_TEXT, 'Success or Failure message'),
            'warnings' => new external_warnings()
                )
        );
    }

    public function createfac_enrolcourse($enrollment) {
        global $DB;

        if (!$enrol_manual = enrol_get_plugin('manual')) {
            throw new coding_exception('Can not instantiate enrol_manual');
        }
        if (!empty($enrollment['coursecode']) && !empty($enrollment['employeecode'])) {

            $courseid = $DB->get_field('course', 'id', array('shortname' => $enrollment['coursecode']));
            $costcenterid = $DB->get_field('local_sisonlinecourses', 'costcenterid', array('coursecode' => $enrollment['coursecode']));
            $siscourseid = $DB->get_field('local_sisonlinecourses', 'courseid', array('coursecode' => $enrollment['coursecode']));
            $sisrecord = $DB->get_record('local_sisonlinecourses', array('coursecode' => $enrollment['coursecode']));
            $programname = $DB->get_field('local_sisprograms', 'fullname', array('id' => $sisrecord->programid));
            $schoolname = $DB->get_field('local_costcenter', 'fullname', array('id' => $sisrecord->costcenterid));
            $coursename = $DB->get_field('course', 'fullname', array('id' => $sisrecord->courseid));

            if (!$courseid || !$siscourseid) {
                $warning = array();
                $warning['warningcode'] = '0';
                $warning['message'] = "The given coursecode '" . $enrollment['coursecode'] . "' does not exist on LMS.";
                $warnings[] = $warning;

                $response = array();
                $response['exception'] = 'moodle_exception';
                $response['errorcode'] = 0;
                $response['message'] = 'Failed';
                $result = array();
                $result = $response;
                $result['warnings'] = $warnings;
                return $result;
            }

            $sisprnuserid = $DB->get_field('user', 'id', array('idnumber' => $enrollment['employeecode'], 'deleted' => 0, 'suspended' => 0));
            $sisuseremail = $DB->get_field('user', 'id', array('email' => $enrollment['email'], 'deleted' => 0, 'suspended' => 0));

            if (!$sisprnuserid && !$sisuseremail) {
                $userdata = new stdClass();
                $userdata->confirmed = 1;
                $userdata->deleted = 0;
                $userdata->auth = 'manual';
                $userdata->policyagreed = 0;
                $userdata->suspended = 0;
                $userdata->mnethostid = 1;
                $userdata->username = strtolower($enrollment['employeecode']);
                $userdata->password = 'Welcome#3';
                $userdata->firstname = $enrollment['firstname'];
                $userdata->lastname = $enrollment['lastname'];
                $userdata->idnumber = $enrollment['employeecode'];
                $userdata->open_employeeid = $enrollment['employeecode'];
                $userdata->email = $enrollment['email'];
                $userdata->phone1 = $enrollment['mobile'];
                $userdata->city = $enrollment['city'];
                $userdata->open_costcenterid = $costcenterid;
                // Fetching roleid //
                $roleid = $DB->get_field('role', 'id', array('shortname' => $enrollment['role']));
                $userdata->open_role = $roleid;
                $sisprnuserid = user_create_user($userdata);
                $sisuserdata = new stdClass();
                $sisuserdata->sisprnid = $enrollment['employeecode'];
                $sisuserdata->roleid = $roleid;
                $sisuserdata->costcenterid = $costcenterid;
                $sisuserdata->mdluserid = $sisprnuserid;
                $sisuserdata->gender = $enrollment->gender;
                $sisuserdata->timecreated = time();
                $sisuserdata->timemodified = 0;
                $sisuserdata->usercreated = $USER->id;
                $sisuserdata->usermodified = 0;
                $sisuserid = $DB->insert_record('local_sisuserdata', $sisuserdata);
                $contextid = 1;
                if ($roleid != 11 && $enrollment['role'] != 'faculty') {
                    $assignedrole = role_assign($roleid, $sisprnuserid, SITEID);
                }
            }

            if ($sisprnuserid) {
                if ($enrollment['role']) {
                    $roleid = $DB->get_field('role', 'id', array('shortname' => $enrollment['role']));
                }
                $enrolid = $DB->get_field('enrol', 'id', array('courseid' => $courseid, 'enrol' => 'manual'));
                if (!$DB->record_exists('user_enrolments', array('enrolid' => $enrolid, 'userid' => $sisprnuserid))) {
                    $instance = $DB->get_record('enrol', array('id' => $enrolid, 'enrol' => 'manual'), '*', MUST_EXIST);
                    $enrol_manual->enrol_user($instance, $sisprnuserid, $roleid);
                } else {
                    $coursename = $DB->get_field('course', 'fullname', array('id' => $courseid));
                    $coursecode = $DB->get_field('course', 'shortname', array('id' => $courseid));
                    $warning = array();
                    $warning['warningcode'] = '0';
                    $warning['message'] = "User already enrolled to '" . $coursename . " (" . $coursecode . ")' course on LMS.";
                    $warnings[] = $warning;

                    $response = array();
                    $response['exception'] = 'moodle_exception';
                    $response['errorcode'] = 0;
                    $response['message'] = 'Failed';
                    $result = array();
                    $result = $response;
                    $result['warnings'] = $warnings;
                    return $result;
                }
                $result = $DB->record_exists('local_courseenrolments', array('courseid' => $courseid, 'mdluserid' => $sisprnuserid, 'programid' => $sisrecord->programid));
                if (!$DB->record_exists('local_courseenrolments', array('courseid' => $courseid, 'mdluserid' => $sisprnuserid, 'programid' => $sisrecord->programid))) {
                    $courseenrol = new stdClass();
                    $courseenrol->courseid = $courseid;
                    $courseenrol->costcenterid = $costcenterid;
                    $courseenrol->programid = $sisrecord->programid;
                    $courseenrol->mdluserid = $sisprnuserid;
                    $courseenrol->sisuserid = $sisuserid;
                    $courseenrol->roleid = $roleid;
                    $courseenrol->coursename = $coursename;
                    $courseenrol->schoolname = $schoolname;
                    $courseenrol->programname = $programname;
                    $courseenrol->timecreated = time();
                    $courseenrol->timemodified = 0;
                    $courseenrol->usercreated = $USER->id;
                    $courseenrol->usermodified = 0;
                    $enrolmentid = $DB->insert_record('local_courseenrolments', $courseenrol);
                }
            }
        }
        $warning = array();
        $warning['warningcode'] = '1';
        $warning['message'] = "no warnings";
        $warnings[] = $warning;

        $response = array();
        $response['exception'] = 'no exception';
        $response['errorcode'] = 1;
        $response['message'] = 'Success';
        $result = array();
        $result = $response;
        $result['warnings'] = $warnings;

        return $result;
    }

    /* Create and enrol faculty users to courses ends */
}

/* create programs */
class local_create_programs_from_api_external extends external_api {

    public static function create_programs_from_api_parameters() {
        return new external_function_parameters(
                array(
            'params' => new external_value(PARAM_RAW, 'Program details', VALUE_DEFAULT, "")
                )
        );
    }

    public static function create_programs_from_api($data) {
       global $DB, $CGF, $USER;
        $programsids = array();
        $det = json_decode($data, true);
        foreach ($det['Data'] as $program) {
            // Make sure that the username, firstname and lastname are not blank.
            foreach (array('SmbId', 'CourseName', 'CourseCode','CourseShortName','CourseLevelId','CourseDuration','CourseType','CourseStatus') as $fieldname) {
                if (trim($program[$fieldname]) === '') {
                    throw new invalid_parameter_exception('The field '.$fieldname.' cannot be blank');
                }
            }
            // Make sure auth is valid.
            if (empty($program['Mode'])) {
                throw new invalid_parameter_exception('Invalid authentication type: '.$program['Mode']);
            }

            $projectid = 15;
            $universityid = $DB->get_field('local_costcenter', 'id', array('projectid'=> $projectid));

            $programs = new stdClass();
            $programs->smbid = $program['SmbId'];
            $programs->fullname =  $program['CourseName'];
            $programs->programcode =  $program['CourseCode'];
            $programs->shortname =  $program['CourseShortName'];
            $programs->costcenterid =  $universityid;
            $programs->type =  $program['CourseType'];
            $programs->duration =  $program['CourseDuration'];
            $programs->runningfromyear = date("Y");
            $programs->coursepattern =  $program['CoursePattern'];
            $programs->visible = 1;
            $programs->coursestatus =  $program['CourseStatus'];
            $programs->courselevelid =  $program['CourseLevelId'];
            $programs->timecreated = time(); 
            $programs->timemodified = 0; 
            $programs->usercreated = $USER->id; 
            $programs->usermodified = 0;
            // Create the user data now!
            if($program['Mode'] == "I"){

                $programid = $DB->insert_record('local_sisprograms', $programs);

            }elseif($program['Mode'] == "U"){
                $programexist_id = $DB->get_record('local_sisprograms', array('SmbId' => $program['SmbId']));
                $programs = new stdClass();
                $programs->fullname =  $program['CourseName'];
                $programs->shortname =  $program['CourseShortName'];
                $programs->costcenterid = 1;
                $programs->type =  $program['CourseType'];
                $programs->duration =  $program['CourseDuration'];
                $programs->CoursePattern =  $program['CoursePattern'];
                $programs->CourseStatus =  $program['CourseStatus'];
                $programs->timemodified = time();
                $programs->usermodified = $USER->id;
                $programs->id = $programexist_id;
                $programid = $DB->update_record('local_sisprograms', $program);

            }


        }
        $programsids[] = array('id' => $programid, 'SmbId' => $program['SmbId']);

        return $programsids;
    }

    public static function create_programs_from_api_returns() {
         return new external_multiple_structure(
            new external_single_structure(
                array(
                    'id'       => new external_value(PARAM_INT, 'program id'),
                    'SmbId' => new external_value(PARAM_INT, 'Unique id'),
                )
            )
        );
    }
    
}
    
//Create Batches
class local_create_branch_from_api_external extends external_api {

    public static function create_branch_from_api_parameters() {
        return new external_function_parameters(
                array(
            'params' => new external_value(PARAM_RAW, 'Branch details', VALUE_DEFAULT, "")
                )
        );
     }

    public static function create_branch_from_api($data) {
       global $DB, $CGF, $USER;
        $branchids = array();
        $det = json_decode($data, true);
        foreach ($det['Data'] as $branch) {
            // Make sure that the username, firstname and lastname are not blank.
            foreach (array('SmbId', 'CourseId', 'BranchCode','BranchName','ActiveStat') as $fieldname) {
                if (trim($branch[$fieldname]) === '') {
                    throw new invalid_parameter_exception('The field '.$fieldname.' cannot be blank');
                }
            }
            // Make sure auth is valid.
            if (empty($branch['Mode'])) {
                throw new invalid_parameter_exception('Invalid authentication type: '.$subject['Mode']);
            }

            $projectid = 15;
            $universityid = $DB->get_field('local_costcenter', 'id', array('projectid'=> $projectid));

            $branches = new stdClass();
            $universityid=1;
            $branches->smbid = $branch['SmbId'];
            $branches->courseid = $branch['CourseId'];
            $branches->branchcode = $branch['BranchCode'];
            $branches->branchname = $branch['BranchName'];
            $branches->activestat = $branch['ActiveStat'];
            $branches->university = $universityid;
            $branches->timecreated = time(); 
            $branches->timemodified = 0; 
            $branches->usercreated = $USER->id; 
            $branches->usermodified = 0;
          
            if($branch['Mode'] == "I"){

                $branchid = $DB->insert_record('local_sisbranches', $branches);

            }elseif($branch['Mode'] == "U"){
                $subjectexist_id = $DB->get_record('local_sissubjects', array('SmbId' => $branch['SmbId']));
                $branches = new stdClass();
                $branches->smbid = $branch['SmbId'];
                $branches->courseid =  $branch['CourseId'];
                $branches->branchcode =  $branch['BranchCode'];
                $branches->branchname =  $branch['BranchName'];
                $branches->activestat =  $branch['ActiveStat'];
                $branches->timemodified = time();
                $branches->usermodified = $USER->id;
                $branches->university =$DB->get_field('local_costcenter','id', array('id'=> $universityid));

                $branches->id = $branchexist_id;
                $branchid = $DB->update_record('local_sisbranches', $branches);

            }


        }
        $branchids[] = array('id' => $branchid, 'SmbId' => $branch['SmbId']);

        return $branchids;
    }

    public static function create_branch_from_api_returns() {
         return new external_multiple_structure(
            new external_single_structure(
                array(
                    'id'       => new external_value(PARAM_INT, 'program id'),
                    'SmbId' => new external_value(PARAM_INT, 'Unique id'),
                )
            )
        );
    }

}

//Create Exams
class local_create_exams_from_api_external extends external_api {

   public static function create_exams_from_api_parameters() {
        return new external_function_parameters(
                array(
            'params' => new external_value(PARAM_RAW, 'Exams details', VALUE_DEFAULT, "")
                )
        );
    }
   public static function create_exams_from_api($data) {
       global $DB, $CGF, $USER;
       
        $examids = array();
        $det = json_decode($data, true);
        foreach ($det['Data'] as $exam) {
            // Make sure that the username, firstname and lastname are not blank.
            foreach (array('SmbId', 'BranchId', 'ExamCode','ExamName','ExamSequence') as $fieldname) {
                if (trim($exam[$fieldname]) === '') {
                    throw new invalid_parameter_exception('The field '.$fieldname.' cannot be blank');
                }
            }
            // Make sure auth is valid.
            if (empty($exam['Mode'])) {
                throw new invalid_parameter_exception('Invalid authentication type: '.$exam['Mode']);
            }

            $projectid = 15;
            $universityid = $DB->get_field('local_costcenter', 'id', array('projectid'=> $projectid));

            $exams = new stdClass();
            $exams->smbid = $exam['SmbId'];
            $exams->branchid =  $exam['BranchId'];
            $exams->examcode =  $exam['ExamCode'];
            $exams->examname =  $exam['ExamName'];
            $exams->sequence =  $exam['ExamSequence'];
            $exams->university =  $universityid;
            $exams->timecreated = time(); 
            $exams->timemodified = 0; 
            $exams->usercreated = $USER->id; 
            $exams->usermodified = 0;
          
            if($exam['Mode'] == "I"){

                $examid = $DB->insert_record('local_sisexams', $exams);

            }elseif($exam['Mode'] == "U"){
                $examexist_id = $DB->get_record('local_sisexams', array('SmbId' => $exam['SmbId']));
                $exams = new stdClass();
                $exams->smbid = $exam['SmbId'];
                $exams->branchid =  $exam['BranchId'];
                $exams->examcode =  $exam['ExamCode'];
                $exams->examname =  $exam['ExamName'];
                $exams->sequence =  $exam['ExamSequence'];
                $exams->timemodified = time();
                $exams->usermodified = $USER->id;
                $exams->university =$DB->get_field('local_costcenter','id', array('id'=> $universityid));

                $exams->id = $examexist_id;
                $examid = $DB->update_record('local_sisexams', $exams);

            }


        }
        $examids[] = array('id' => $examid, 'SmbId' => $exam['SmbId']);

        return $examids;
    }

    public static function create_exams_from_api_returns() {
         return new external_multiple_structure(
            new external_single_structure(
                array(
                    'id'       => new external_value(PARAM_INT, 'program id'),
                    'SmbId' => new external_value(PARAM_INT, 'Unique id'),
                )
            )
        );
    }
}
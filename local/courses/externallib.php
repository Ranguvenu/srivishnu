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
 * External Courses API
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

/* Create master courses from API */
class local_create_courses_from_api_external extends external_api {

    public static function create_courses_from_api_parameters() {
        return new external_function_parameters(
                array(
            'params' => new external_value(PARAM_RAW, 'Course details', VALUE_DEFAULT, "")
                )
        );
    }

    public static function create_courses_from_api($data) {
       global $DB, $CGF;
        
        $coursesids = array();
        $det = json_decode($data, true);
        foreach ($det['Data'] as $course) {
            // Make sure that the ExamId, SubjectCode and SubjectName are not blank.
            foreach (array('SmbId', 'ExamId', 'SubjectCode','SubjectName') as $fieldname) {
                if (trim($course[$fieldname]) === '') {
                    throw new invalid_parameter_exception('The field '.$fieldname.' cannot be blank');
                }
            }
            // Make sure mode is valid.
            if (empty($course['Mode'])) {
                throw new invalid_parameter_exception('Invalid authentication type: '.$program['Mode']);
            }

            $projectid = 15;
            $universityid = $DB->get_field('local_costcenter', 'id', array('projectid'=> $projectid));
            $universityname = $DB->get_field('local_costcenter', 'fullname', array('id'=> $universityid));
            $coursecategoryid = $DB->get_field('local_costcenter', 'category', array('id'=> $universityid));
            
            if($course['Mode'] == "I"){

                 /*course creation*/
                $coursedata = new stdClass();
                $coursedata->fullname = $course['SubjectName'];
                $coursedata->shortname = $course['SubjectCode'];
                $coursedata->category = $coursecategoryid;
                $courseid = self::moodlecourse_create($coursedata);

                /*siscourse creation*/
                $siscourse = new stdClass();
                $siscourse->coursecode = $courseid->shortname;
                $siscourse->courseid = $courseid->id;
                $siscourse->examid = $course['ExamId'];
                $siscourse->programid = NULL;
                $siscourse->programcode = NULL;
                $siscourse->costcenterid = $universityid;
                $siscourse->schoolname = $universityname;
                $siscourse->coursetype = '';
                $siscourse->sissourceid = 0;
                $siscourse->timecreated = time(); 
                $siscourse->timemodified = 0; 
                $siscourse->usercreated = $USER->id; 
                $siscourse->usermodified = 0;
                $siscourse->smbid = $course['SmbId'];

                 $siscourseid = $DB->insert_record('local_sisonlinecourses',$siscourse);

            $coursesids[] = array('id' => $siscourseid, 'SmbId' => $course['SmbId']);

            }elseif($course['Mode'] == "U"){

                $courseexist = $DB->get_record('local_sisonlinecourses', array('smbid' => $course['SmbId']), 'id, courseid'); 
                
                $siscourseup = new stdClass();
                $siscourseup->examid = $course['ExamId'];
                $siscourseup->costcenterid = $universityid;
                $siscourseup->schoolname = $universityname;
                $siscourseup->coursetype = '';
                $siscourseup->sissourceid = 0;
                $siscourseup->timecreated = time(); 
                $siscourseup->timemodified = 0; 
                $siscourseup->usercreated = $USER->id; 
                $siscourseup->usermodified = 0;
                $siscourseup->id = $courseexist->id;
                $siscourseid = $DB->update_record('local_sisonlinecourses', $siscourseup);

                $courseup = new stdClass();
                $courseup->fullname = $course['SubjectName'];
                $courseup->id = $courseexist->courseid;
                $courseupid = $DB->update_record('course', $courseup);

                $coursesids[] = array('id' => $siscourseid, 'SmbId' => $course['SmbId']);
            }
        }
        
        return $coursesids;
    }


    /*insert moodle course for online siscourse*/
    function moodlecourse_create($coursedata){
        global $CFG,$PAGE,$USER,$DB;
        require_once($CFG->dirroot.'/course/lib.php');

        $categoryid = $coursedata->category;
        $category = $DB->get_record('course_categories', array('id' => $categoryid), '*', MUST_EXIST);
        $catcontext = context_coursecat::instance($category->id);

        $sortorderlist = $DB->get_records('course', array('category' => $category->id));
        foreach ($sortorderlist as $sortlist)
            $sortorder = $sortlist->sortorder;
        if ($sortorder)
            $sortorder = $sortorder++;
        else
            $sortorder = 0;
        $data = new stdClass();
        $data->category = $category->id;
        $data->sortorder = $sortorder;
        $data->fullname = $coursedata->fullname;
        $data->shortname = $coursedata->shortname;
        $data->idnumber = 0;
        $data->summary = '';
        $data->summaryformat = 1;
        $data->format = 'weeks';
        $data->showgrades = 1;
        $data->newsitems = 5;
        $data->startdate = time();
        $data->marker = 0;
        $data->maxbytes = 0;
        $data->legacyfiles = 0;
        $data->showreports = 0;
        $data->visible = 1;
        $data->visibleold = 1;
        $data->groupmode = 0;
        $data->groupmodeforce = 0;
        $data->defaultgroupingid = 0;
        $data->lang = '';
        $data->theme = '';
        $data->timecreated = time();
        $data->timemodified = time();
        $data->requested = 0;
        $data->enablecompletion = 1;
        $data->completionnotify = 0;
        $data->coursetype = 0;
        $courseid = create_course($data);
        $coursedata = $courseid;
        return $courseid;
    }

    public static function create_courses_from_api_returns() {
         return new external_multiple_structure(
            new external_single_structure(
                array(
                    'id'       => new external_value(PARAM_INT, 'Course id'),
                    'SmbId' => new external_value(PARAM_INT, 'Source System Unique id'),
                )
            )
        );
    }

     public static function get_courseinstances_parameters() {
        return new external_function_parameters( 
            array(
                //'parentcourse' => new external_value(PARAM_INT, 'parentcourse', VALUE_REQUIRED)
            )
        );
    }

    /**
     * Returns program instances details
     */
    public static function get_courseinstances() {
        global $USER, $DB, $CFG;
        $params = self::validate_parameters(self::get_courseinstances_parameters(), array());

        $sql = 'SELECT * FROM {course} WHERE forpurchaseindividually IS NOT NULL';
        $course_instances = $DB->get_records_sql($sql);

        $courses_instinfo = array();
        if($course_instances){
            foreach ($course_instances as $course_instance) {

                $course_inst = array();
                $course_inst['courseid'] = $course_instance->id;
                $course_inst['fullname'] = $course_instance->fullname;
                $course_inst['shortname'] = $course_instance->shortname;
                $course_inst['category'] = $course_instance->category;
                $course_inst['sortorder'] = $course_instance->sortorder;
                $course_inst['summary'] = $course_instance->summary;
                $course_inst['summaryformat'] = $course_instance->summaryformat;
                $course_inst['format'] = $course_instance->format;
                $course_inst['showgrades'] = $course_instance->showgrades;
                $course_inst['newsitems'] = $course_instance->newsitems;
                $course_inst['startdate'] = $course_instance->startdate;
                $course_inst['enddate'] = $course_instance->enddate;
                $course_inst['maxbytes'] = $course_instance->maxbytes;
                $course_inst['showreports'] = $course_instance->showreports;
                $course_inst['cost'] = $course_instance->open_cost;
                $course_inst['visible'] = $course_instance->visible;
                $course_inst['groupmode'] = $course_instance->groupmode;
                $course_inst['groupmodeforce'] = $course_instance->groupmodeforce;
                $course_inst['defaultgroupingid'] = $course_instance->defaultgroupingid;
                $course_inst['timecreated'] = $course_instance->timecreated;
                $course_inst['timemodified'] = $course_instance->timemodified;
                $course_inst['open_costcenterid'] = $course_instance->open_costcenterid;
                $course_inst['open_departmentid'] = $course_instance->open_departmentid;
                $course_inst['enablecompletion'] = $course_instance->enablecompletion;
                $course_inst['completionnotify'] = $course_instance->completionnotify;               

                $courses_instinfo[] = $course_inst;
            }
        }
       return $courses_instinfo;
    }

    /**
     * Returns description of method result value
     * @return external_description
     */
    public static function get_courseinstances_returns() {
        return new external_multiple_structure(
                    new external_single_structure(
                           array(
                            'courseid' =>
                            new external_value(PARAM_RAW, 'course id', VALUE_REQUIRED),
                            'fullname' =>
                            new external_value(PARAM_RAW, 'course name', VALUE_REQUIRED),
                            'shortname' =>
                            new external_value(PARAM_RAW, 'short name', VALUE_REQUIRED),
                            'category' =>
                            new external_value(PARAM_RAW, 'category', VALUE_REQUIRED),
                            'sortorder' =>
                            new external_value(PARAM_RAW, 'Sort Order', VALUE_REQUIRED),
                            'summary' =>
                            new external_value(PARAM_RAW, 'Summary', VALUE_REQUIRED),
                            'summaryformat' =>
                            new external_value(PARAM_RAW, 'Summary Format', VALUE_REQUIRED),
                            'format' =>
                            new external_value(PARAM_RAW, 'Format', VALUE_REQUIRED),
                            'showgrades' =>
                            new external_value(PARAM_RAW, 'Show Grades', VALUE_REQUIRED),
                            'newsitems' =>
                            new external_value(PARAM_RAW, 'News Items', VALUE_REQUIRED),
                            'startdate' =>
                            new external_value(PARAM_RAW, 'Start Date', VALUE_REQUIRED),
                            'enddate' =>
                            new external_value(PARAM_RAW, 'End Date', VALUE_REQUIRED),
                            'maxbytes' =>
                            new external_value(PARAM_RAW, 'Maxbytes', VALUE_REQUIRED),
                            'showreports' =>
                            new external_value(PARAM_RAW, 'Show Reports', VALUE_REQUIRED),
                            'groupmode' =>
                            new external_value(PARAM_RAW, 'Group Mode', VALUE_REQUIRED),
                            'groupmodeforce' =>
                            new external_value(PARAM_RAW, 'Groupmodeforce', VALUE_REQUIRED),
                            'enablecompletion' =>
                            new external_value(PARAM_RAW, 'Enable Completion', VALUE_REQUIRED),
                            'completionnotify' =>
                            new external_value(PARAM_RAW, 'Completion Notify', VALUE_REQUIRED),
                            'cost' =>
                            new external_value(PARAM_RAW, 'Cost', VALUE_REQUIRED),
                            'visible' =>
                            new external_value(PARAM_RAW, 'Visible', VALUE_REQUIRED),
                            'timecreated' =>
                            new external_value(PARAM_RAW, 'Time Created', VALUE_REQUIRED),
                            'timemodified' =>
                            new external_value(PARAM_RAW, 'Time Modified', VALUE_REQUIRED),
                            'open_costcenterid' =>
                            new external_value(PARAM_RAW, 'Costcenter', VALUE_REQUIRED),
                            'open_departmentid' =>
                            new external_value(PARAM_RAW, 'Department', VALUE_REQUIRED)
                        )
                    )
        );
        // return new external_value(PARAM_BOOL, 'return');

    }
        public static function courseenrol_users_parameters() {
             return new external_function_parameters( 
                    array(
                    'enrolments' => new external_multiple_structure(
                            new external_single_structure(
                             array(
                                     'roleid' => new external_value(PARAM_INT, 'Role to assign to the user'),
                                        'userid' => new external_value(PARAM_INT, 'The user that is going to be enrolled'),
                                        'courseid' => new external_value(PARAM_INT, 'The course to enrol the user role in'),
                                        'timestart' => new external_value(PARAM_INT, 'Timestamp when the enrolment start', VALUE_OPTIONAL),
                                        'timeend' => new external_value(PARAM_INT, 'Timestamp when the enrolment end', VALUE_OPTIONAL),
                                        'orderid' => new external_value(PARAM_INT, 'Orderid of the payment after success on WP', VALUE_OPTIONAL),
                                        'suspend' => new external_value(PARAM_INT, 'set to 1 to suspend the enrolment', VALUE_OPTIONAL)
                                   )
                             )
                    )
                )
        );
    }

    /**
     * Enrolment of users.
     *
     * Function throw an exception at the first error encountered.
     * @param array $enrolments  An array of user enrolment
     * @since Moodle 2.2
     */
    public static function courseenrol_users($enrolments) {
        global $DB, $CFG;

        require_once($CFG->libdir . '/enrollib.php');

        $params = self::validate_parameters(self::courseenrol_users_parameters(), array('enrolments' => $enrolments));


        $transaction = $DB->start_delegated_transaction(); // Rollback all enrolment if an error occurs
                                                           // (except if the DB doesn't support it).

        // Retrieve the manual enrolment plugin.
        $enrol = enrol_get_plugin('manual');
        if (empty($enrol)) {
            throw new moodle_exception('manualpluginnotinstalled', 'enrol_manual');
        }
        foreach ($params['enrolments'] as $enrolment) {

            // Ensure the current user is allowed to run this function in the enrolment context.
            $context = context_course::instance($enrolment['courseid'], IGNORE_MISSING);
            self::validate_context($context);

            // Check that the user has the permission to manual enrol.
            require_capability('enrol/manual:enrol', $context);

            // Throw an exception if user is not able to assign the role.
            $roles = get_assignable_roles($context);
            if (!array_key_exists($enrolment['roleid'], $roles)) {
                $errorparams = new stdClass();
                $errorparams->roleid = $enrolment['roleid'];
                $errorparams->courseid = $enrolment['courseid'];
                $errorparams->userid = $enrolment['userid'];
                throw new moodle_exception('wsusercannotassign', 'enrol_manual', '', $errorparams);
            }

            // Check manual enrolment plugin instance is enabled/exist.
            $instance = null;

            $enrolinstances = enrol_get_instances($enrolment['courseid'], true);

            foreach ($enrolinstances as $courseenrolinstance) {
              if ($courseenrolinstance->enrol == "manual") {
                  $instance = $courseenrolinstance;
                  break;
              }
            }
            if (empty($instance)) {
              $errorparams = new stdClass();
              $errorparams->courseid = $enrolment['courseid'];
              throw new moodle_exception('wsnoinstance', 'enrol_manual', $errorparams);
            }

            // Check that the plugin accept enrolment (it should always the case, it's hard coded in the plugin).
            if (!$enrol->allow_enrol($instance)) {
                $errorparams = new stdClass();
                $errorparams->roleid = $enrolment['roleid'];
                $errorparams->courseid = $enrolment['courseid'];
                $errorparams->orderid = $enrolment['orderid'];
                $errorparams->userid = $enrolment['userid'];
                throw new moodle_exception('wscannotenrol', 'enrol_manual', '', $errorparams);
            }

            // Finally proceed the enrolment.
            $enrolment['timestart'] = isset($enrolment['timestart']) ? $enrolment['timestart'] : 0;
            $enrolment['timeend'] = isset($enrolment['timeend']) ? $enrolment['timeend'] : 0;
            $enrolment['status'] = (isset($enrolment['suspend']) && !empty($enrolment['suspend'])) ?
                    ENROL_USER_SUSPENDED : ENROL_USER_ACTIVE;
            $enrol->enrol_user($instance, $enrolment['userid'], $enrolment['roleid'],
                    $enrolment['timestart'], $enrolment['timeend'], $enrolment['status']);
          
        }

        $transaction->allow_commit();
          $enrolled = $DB->get_field_sql('SELECT ue.id FROM {user_enrolments} ue JOIN {enrol} e ON e.id = ue.enrolid WHERE e.courseid ='.$enrolment['courseid'].' AND ue.userid ='.$enrolment['userid']);
           //  print_object($enrolled);exit;
           if(!empty($enrolled)){
                 $return = array('Status' => "Success",'Msg' => "User Enrolled to Course");
            }
            else{
                $return = array('Status' => "Failed",'Msg' => "User not Enrolled to Course");
            }
          return $return;
    }

    /**
     * Returns description of method result value.
     *
     * @return null
     * @since Moodle 2.2
     */
    public static function courseenrol_users_returns() {
        return new external_single_structure(array(
            'Status' => new external_value(PARAM_TEXT, 'Status'),
            'Msg' => new external_value(PARAM_TEXT, 'Success or Failure message'),
        ));
      //  return null;
    }

}

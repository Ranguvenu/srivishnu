<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or localify
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
 * mooccourses external API
 *
 * @package    local_mooccourses
 * @category   external
 * @copyright  eAbyas <www.eabyas.in>
 */
defined('MOODLE_INTERNAL') || die;

use \local_mooccourses\form\custom_course_form as custom_course_form;
use \local_mooccourses\action\insert as insert;
use \local_mooccourses\form\managestudents_form as managestudents_form; 

require_once("$CFG->libdir/externallib.php");
require_once($CFG->dirroot.'/course/lib.php');
require_once($CFG->dirroot.'/local/mooccourses/lib.php');
require_once($CFG->dirroot.'/course/externallib.php');

class local_mooccourses_external extends external_api {

     /**
     * Describes the parameters for submit_create_course_form webservice.
     * @return external_function_parameters
     */
    public static function submit_create_mooccourse_form_parameters() {
        return new external_function_parameters(
            array(
                'contextid' => new external_value(PARAM_INT, 'The context id for the evaluation'),
                'parentcourseid' => new external_value(PARAM_INT, 'The parent course id '),

                'jsonformdata' => new external_value(PARAM_RAW, 'The data from the create group form, encoded as a json array'),
            
            )
        );
    }

    /**
     * Submit the create course form.
     *
     * @param int $contextid The context id for the course.
     * @param int $form_status form position.
     * @param int $id course id -1 as default.
     * @param string $jsonformdata The data from the form, encoded as a json array.
     * @return int new course id.
     */
    public static function submit_create_mooccourse_form( $contextid, $parentcourseid, $jsonformdata/*, $form_status,$course*/) {
        global $DB, $CFG, $USER;
        require_once($CFG->dirroot.'/course/lib.php');
        require_once($CFG->dirroot . '/local/mooccourses/lib.php');
        $params = self::validate_parameters(self::submit_create_mooccourse_form_parameters(),
                                    ['contextid' => $contextid, 'parentcourseid' => $parentcourseid,'jsonformdata' => $jsonformdata]);
        $context = context_system::instance();
        self::validate_context($context);
        $serialiseddata = json_decode($params['jsonformdata']);
        $data = array();
        parse_str($serialiseddata, $data);
        $warnings = array();
        $parentcourseid = $params['parentcourseid'];
        $data['courseid'] = $parentcourseid;
        $editoroptions = array('maxfiles' => EDITOR_UNLIMITED_FILES, 'maxbytes'=>$CFG->maxbytes, 'trusttext'=>false, 'noclean'=>true);
        $overviewfilesoptions = course_overviewfiles_options($course);
        if (!empty($course)) {
                $editoroptions['context'] = $coursecontext;
                $editoroptions['subdirs'] = file_area_contains_subdirs($coursecontext, 'course', 'summary', 0);
                $course = file_prepare_standard_editor($course, 'summary', $editoroptions, $coursecontext, 'course', 'summary', 0);
                if ($overviewfilesoptions) {
                    file_prepare_standard_filemanager($course, 'overviewfiles', $overviewfilesoptions, $coursecontext, 'course', 'overviewfiles', 0);
                }
            $get_coursedetails=$DB->get_record('course',array('id'=>$course->id));
        } else {
                $editoroptions['context'] = $catcontext;
                $editoroptions['subdirs'] = 0;
                $course = file_prepare_standard_editor($course, 'summary', $editoroptions, null, 'course', 'summary', null);
                if ($overviewfilesoptions) {
                    file_prepare_standard_filemanager($course, 'overviewfiles', $overviewfilesoptions, null, 'course', 'overviewfiles', 0);
                }
        }
        $mform = new local_mooccourses\form\categories_form(null, array('courseid' => $data['courseid']), 'post', '', null, true, $data);
        $validateddata = $mform->get_data();
        $options = array(array('name' => 'blocks', 'value' => 1),
                 array('name' => 'activities', 'value' => 1),
                 array('name' => 'filters', 'value' => 1),
                 array('name' => 'users', 'value' => 1)
                 );

        if($validateddata){
             $coursedetails = $DB->get_record_sql("SELECT * FROM {course} WHERE id = ".$data['courseid']);
            $validateddata->forpurchaseindividually = 1;
            $validateddata->open_costcenterid = $coursedetails->open_costcenterid;
            $validateddata->open_departmentid = $coursedetails->open_departmentid;
            $validateddata->open_cost = $validateddata->open_cost;            
            $validateddata->open_parentcourseid = $data['courseid'];
            //Added by Yamini for duplication of course
            $externalObj = new core_course_external();
            $validateddata->category = $DB->get_field('local_costcenter','category',array('id' => $validateddata->category));
            $res = $externalObj->duplicate_course($params['parentcourseid'], $validateddata->fullname, $validateddata->shortname, $validateddata->category, '1', $options);
            if(!empty($res)){
                $opencost = $validateddata->open_cost ? $validateddata->open_cost : 0;
                $validateddata->id = $res['id'];              
                $result = $DB->execute('UPDATE {course} SET open_departmentid = '.$validateddata->open_departmentid.' , open_costcenterid = '.$validateddata->open_costcenterid.' , open_parentcourseid ='.$validateddata->open_parentcourseid.' , forpurchaseindividually = 1,open_cost = '.$opencost.' WHERE id = '.$validateddata->id);                  
            }   
        }else {
            // Generate a warning.
            throw new moodle_exception('Error in creation');
        }
        if($result){
            return true;
        }else{
            return false;
        }
    }

    /**
     * Returns description of method result value.
     *
     * @return external_description
     * @since Moodle 3.0
     */
    public static function submit_create_mooccourse_form_returns() {
              return new external_value(PARAM_BOOL, 'return');

    }
   

    public static function submit_course_create_form_parameters() {
        return new external_function_parameters(
            array(
                'contextid' => new external_value(PARAM_INT, 'The context id for the course'),
                // 'form_status' => new external_value(PARAM_INT, 'Form position', 0),
             //   'id' => new external_value(PARAM_INT, 'Course id', 0),
                'jsonformdata' => new external_value(PARAM_RAW, 'The data from the create course form, encoded as a json array')
            )
        );
    }

    /**
     * Submit the create course form.
     *
     * @param int $contextid The context id for the course.
     * @param int $form_status form position.
     * @param int $id course id -1 as default.
     * @param string $jsonformdata The data from the form, encoded as a json array.
     * @return int new course id.
     */
    public static function submit_course_create_form($contextid/*, $form_status, $id,*/, $jsonformdata) {
        global $DB, $CFG, $USER;
       // require_once($CFG->dirroot.'/mooccourses/lib.php');
       // require_once($CFG->dirroot . '/local/courses/lib.php');

        // We always must pass webservice params through validate_parameters.
        $params = self::validate_parameters(self::submit_course_create_form_parameters(),
                                            ['contextid' => $contextid/*, 'form_status'=>$form_status*/,  'jsonformdata' => $jsonformdata]);

        $context = context::instance_by_id($params['contextid'], MUST_EXIST);
        // We always must call validate_context in a webservice.
        self::validate_context($context);
        $serialiseddata = json_decode($params['jsonformdata']);

        $data = array();
        parse_str($serialiseddata, $data);

        $warnings = array();
       /* if ($id) {
            $course = get_course($id);
            $category = $DB->get_record('course_categories', array('id'=>$course->category), '*', MUST_EXIST);
        }else{
            $course = null;
        }
*/
        $editoroptions = array('maxfiles' => EDITOR_UNLIMITED_FILES, 'maxbytes'=>$CFG->maxbytes, 'trusttext'=>false, 'noclean'=>true);
        $overviewfilesoptions = course_overviewfiles_options($course);
        if (!empty($course)) {
            // Add context for editor.
                $editoroptions['context'] = $coursecontext;
                $editoroptions['subdirs'] = file_area_contains_subdirs($coursecontext, 'course', 'summary', 0);
                $course = file_prepare_standard_editor($course, 'summary', $editoroptions, $coursecontext, 'course', 'summary', 0);
                if ($overviewfilesoptions) {
                    file_prepare_standard_filemanager($course, 'overviewfiles', $overviewfilesoptions, $coursecontext, 'course', 'overviewfiles', 0);
                }
            $get_coursedetails=$DB->get_record('course',array('id'=>$course->id));
        } else {
            // Editor should respect category context if course context is not set.
            $editoroptions['context'] = $catcontext;
            $editoroptions['subdirs'] = 0;
            $course = file_prepare_standard_editor($course, 'summary', $editoroptions, null, 'course', 'summary', null);
            if ($overviewfilesoptions) {
                file_prepare_standard_filemanager($course, 'overviewfiles', $overviewfilesoptions, null, 'course', 'overviewfiles', 0);
            }
        }

        // The last param is the ajax submitted data.
            $mform = new custom_course_form(null, array(/*'form_status' => $form_status,*/), 'post', '', null, true, $data);
        $validateddata = $mform->get_data();
        if ($validateddata) {
            $formheaders = array_keys($mform->formstatus);
            // $category_id=$data['category'];
// <mallikarjun> - ODL-841 adding college to mooc course -- starts
if($validateddata->open_univdept_status == 1){
    $validateddata->open_departmentid = $validateddata->open_collegeid;
    $open_departmentid = $data['open_collegeid'];
}else{
    $validateddata->open_departmentid = $validateddata->open_departmentid;
    $open_departmentid = $data['open_departmentid'];
}
//            $open_departmentid = $data['open_departmentid'];
// <mallikarjun> - ODL-841 adding college to mooc course -- ends
// <sandeep> - SRIVSPT-3 Manage Mooc Courses Module Changes -- Starts
            $open_departmentid = implode(',', array_values($open_departmentid));
            if($validateddata->open_departmentid == 0){
                $category_id = $DB->get_field('local_costcenter', 'category', array('parentid' => $validateddata->open_departmentid));
                
            }
            else{
            $category_id = $DB->get_field('local_costcenter', 'category', array('id' => $validateddata->open_departmentid));
            }  
                $validateddata->category = $category_id;
            if ($validateddata->id <= 0) {
                // $validateddata->open_identifiedas=implode(',',$validateddata->open_identifiedas);
                $validateddata->open_identifiedas = 5;
                // if($open_departmentid=='null'){
                //     $validateddata->open_departmentid = 0;
                // }else{
                //     $validateddata->open_departmentid = $open_departmentid;
                // }
                $validateddata->open_parentcourseid = 0;
                $validateddata->forpurchaseindividually = 2;
                $validateddata->affiliationstatus = 1;
// <sandeep> - SRIVSPT-3 Manage Mooc Courses Module Changes -- Ends
                $courseid = create_course($validateddata, $editoroptions);
// <mallikarjun> - ODL-838 displaying created mooc course to college head -- starts
$collegeheadroleid = $DB->get_field('role', 'id', array('shortname' => 'college_head'));
if($USER->open_role == $collegeheadroleid){
                $oldcourseid = $courseid->id;
        $clonecourse=$DB->get_record('course',  array('id'=>$oldcourseid),  $fields='*',  $strictness=IGNORE_MISSING);
        $collegeid = $DB->get_record('local_costcenter',  array('id'=>$open_departmentid),  $fields='*',  $strictness=IGNORE_MISSING);
        if($clonecourse){
            if (!is_dir($CFG->dataroot . DIRECTORY_SEPARATOR . 'courseimport')) {
                @mkdir($CFG->dataroot . DIRECTORY_SEPARATOR . 'courseimport', 0777, true);
            }//Added by Harish to create a folder for course content import using moosh
            if($collegeid){
              $stradded = 'Added - ';
              $resultstring='affiliatcolleges';
              $title=$collegeid->fullname." College";
              $progressbartittle = get_string('affiliatcollegeprogress', 'local_program',$title);
            }else{
              $stradded = 'Copied - ';
              $resultstring='copycourses';
              $title=$clonecourse->fullname;
              $progressbartittle = get_string('copyprogramprogress', 'local_mooccourses',$title);
            }

                if ($showfeedback) {
                    $reurn.=$OUTPUT->notification($stradded.' Course <b>'.$title.'</b>', 'notifysuccess');
                }
                $clonecourse->shortname=$clonecourse->shortname.'_'.$collegeid->shortname.'_'.$collegeid->id;
                $clonecourse->id=0;
                $clonecourse->open_costcenterid = $collegeid->parentid;
                $clonecourse->open_departmentid = $collegeid->id;
                $clonecourse->open_parentcourseid = $oldcourseid;
                $clonecourse->category = $collegeid->category;
                // $clonecourse->affiliationstatus = 1;
                // print_object($clonecourse);exit;
                $courseid = create_course($clonecourse);
                $parentcourseid = $oldcourseid;
                $clonedcourse = $courseid->id;
                shell_exec('rm -rf ' . $CFG->dataroot . '/courseimport/' . $clonedcourse . '.mbz ');
                $command = 'moosh -n course-backup -f ' . $CFG->dataroot . '/courseimport/' . $clonedcourse . '.mbz ' . $parentcourseid ;
                $output = shell_exec($command);
                $command1 = 'moosh -n course-restore -e ' . $CFG->dataroot . '/courseimport/' . $clonedcourse . '.mbz ' . $clonedcourse;
                $output1 = shell_exec($command1);
                shell_exec('rm -rf ' . $CFG->dataroot . '/courseimport/' . $clonedcourse . '.mbz ');
                // Course content duplication by Harish ends here //
                insert::add_enrol_meathod_tocourse($clonedcourse,1);
}}
// <mallikarjun> - ODL-838 displaying created mooc course to college head -- end
              //  $coursedata = $courseid;
              //  $enrol_status = $validateddata->selfenrol;
              //  insert::add_enrol_meathod_tocourse($coursedata,$enrol_status);

            }
            else{                    
                     update_course($validateddata, $editoroptions);
            } 
            // $next = $form_status + 1;
         //   $nextform = array_key_exists($next, $formheaders);
            // if ($nextform !== false) {
            //     $form_status = $next;
            //     $error = false;
            // } else {
            //     $form_status = -1;
            //     $error = true;
            // }
         //   $enrolid = $DB->get_field('enrol', 'id' ,array('courseid' => $courseid->id ,'enrol' => 'manual'));
          //  $existing_method = $DB->get_record('enrol',array('courseid'=> $courseid->id  ,'enrol' => 'self'));
          //  $courseenrolid = $DB->get_field('course','selfenrol',array('id'=> $courseid->id));  
           // if($courseenrolid == 1){
           //     $existing_method->status = 0;
           //     $existing_method->customint6 = 1;
           // }else{
           //     $existing_method->status = 1;
           // }
           // $DB->update_record('enrol', $existing_method);
//
        } else {
            // Generate a warning.
            throw new moodle_exception('Error in submission');
        }
     //   $return = $courseid;
           // 'enrolid' => $enrolid,
            /*'form_status' => $form_status*/
       if($courseid)
         return true;
    }

    /**
     * Returns description of method result value.
     *
     * @return external_description
     * @since Moodle 3.0
     */
    public static function submit_course_create_form_returns() {
       
            return new external_value(PARAM_BOOL, 'return');
//'enrolid' => new external_value(PARAM_INT, 'manual enrol id for the course'),
            // 'form_status' => new external_value(PARAM_INT, 'form_status'),
       // ));
    }
     public static function submit_mooccourse_edit_form_parameters() {
        return new external_function_parameters(
            array(
                'contextid' => new external_value(PARAM_INT, 'The context id for the course'),
               'courseid' => new external_value(PARAM_INT, 'The mooccourse id '),
               'forpurchaseindividually' => new external_value(PARAM_INT, 'The mooccourse id '),

                'jsonformdata' => new external_value(PARAM_RAW, 'The data from the create course form, encoded as a json array')
            )
        );
    }

    /**
     * Submit the create course form.
     *
     * @param int $contextid The context id for the course.
     * @param int $form_status form position.
     * @param int $id course id -1 as default.
     * @param string $jsonformdata The data from the form, encoded as a json array.
     * @return int new course id.
     */
    public static function submit_mooccourse_edit_form($contextid,$courseid,$forpurchaseindividually/*, $form_status, $id,*/, $jsonformdata) {
        global $DB, $CFG, $USER;

        $params = self::validate_parameters(self::submit_mooccourse_edit_form_parameters(),
                                            ['contextid' => $contextid, 'courseid'=>$courseid,'forpurchaseindividually'=>$forpurchaseindividually,  'jsonformdata' => $jsonformdata]);
//print_object("camee");exit;
        $context = context::instance_by_id($params['contextid'], MUST_EXIST);
        self::validate_context($context);
        $serialiseddata = json_decode($params['jsonformdata']);
        
        $data = array();
        parse_str($serialiseddata, $data);

        $warnings = array();

        $coursedetails = $DB->get_record('course',array('id' => $params['courseid'] ));
        $mform = new local_mooccourses\form\categories_form(null, array('courseid'=>$coursedetails->open_parentcourseid,'id' => $params['courseid']), 'post', '', null, true, $data);
        $validateddata = $mform->get_data();
        if ($validateddata) {

            $courseid = update_course($validateddata);
            
        } else {
            throw new moodle_exception('Error in submission');
        }
    
       if($courseid)
         return true;
    }

    /**
     * Returns description of method result value.
     *
     * @return external_description
     * @since Moodle 3.0
     */
    public static function submit_mooccourse_edit_form_returns() {
       
            return new external_value(PARAM_BOOL, 'return');
//'enrolid' => new external_value(PARAM_INT, 'manual enrol id for the course'),
            // 'form_status' => new external_value(PARAM_INT, 'form_status'),
       // ));
    }
     public static function delete_mooccourse_parameters(){
        return new external_function_parameters(
            array(
                'contextid' => new external_value(PARAM_INT, 'contextid',1),
                'courseid' => new external_value(PARAM_INT, 'courseid', 0)
                )
        );

    }
  
    public static function delete_mooccourse($contextid, $courseid){
        global $DB;
        $params = self::validate_parameters(self::delete_mooccourse_parameters(),
                                    ['contextid' => $contextid, 'courseid' => $courseid]);        
        if($courseid){
           $courseid = $DB->delete_records('course', array('id' => $courseid));
            return true;
        }else {
            throw new moodle_exception('Error in deleting');
            return false;
        }
    }
   
    public static function delete_mooccourse_returns() {
        return new external_value(PARAM_BOOL, 'return');
    }
     public static function addstudent_submit_instance_parameters() {
        return new external_function_parameters(
            array(
                'courseid' => new external_value(PARAM_INT, 'ID', 0),
                'contextid' => new external_value(PARAM_INT, 'The context id', false),
               // 'form_status' => new external_value(PARAM_INT, 'Form position', 0),
                'jsonformdata' => new external_value(PARAM_RAW, 'Submitted Form Data', false),
            )
        );
    }

    public static function addstudent_submit_instance($courseid, $contextid,/* $form_status, */$jsonformdata) {
        global $PAGE, $DB, $CFG, $USER;
        $context = context::instance_by_id($contextid, MUST_EXIST);
        self::validate_context($context);
        $serialiseddata = json_decode($jsonformdata);
        $data = array();
        parse_str($serialiseddata, $data);

        $warnings = array();
        $curriculum = new stdClass();

        // The last param is the ajax submitted data.
        $mform = new local_mooccourses\form\managestudents_form(null, array(),'post', '', null, true, $data);
        $validateddata = $mform->get_data();
        $studentid = $validateddata->students;

        if ($validateddata) {
            // Do the action.
            $managefaculty = addstudent($studentid,$courseid);
            if ($managefaculty > 0) {
              /*  $form_status = -2;
                $error = false;
            } else {*/
               $return = true;
            }
        } else {
            // Generate a warning.
            throw new moodle_exception('missingfaculty', 'local_program');
        }
      /*  $return = array(
            'id' => $managefaculty,
            'form_status' => $form_status);*/
        return $return;
    }

    public static function addstudent_submit_instance_returns() {
      return new external_value(PARAM_BOOL, 'return');
    }
}

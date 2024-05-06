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
 * Courses external API
 *
 * @package    local_courses
 * @category   external
 * @copyright  eAbyas <www.eabyas.in>
 */
defined('MOODLE_INTERNAL') || die;

use \local_courses\form\custom_course_form as custom_course_form;
use \local_courses\action\insert as insert;


require_once("$CFG->libdir/externallib.php");
require_once($CFG->dirroot.'/course/lib.php');
require_once($CFG->dirroot.'/local/courses/lib.php');

class local_courses_external extends external_api {

     /**
     * Describes the parameters for submit_create_course_form webservice.
     * @return external_function_parameters
     */
    public static function submit_create_course_form_parameters() {
        return new external_function_parameters(
            array(
                'contextid' => new external_value(PARAM_INT, 'The context id for the course'),
                // 'form_status' => new external_value(PARAM_INT, 'Form position', 0),
                'id' => new external_value(PARAM_INT, 'Course id', 0),
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
    public static function submit_create_course_form($contextid/*, $form_status*/, $id, $jsonformdata) {
        global $DB, $CFG, $USER;
        require_once($CFG->dirroot.'/course/lib.php');
        require_once($CFG->dirroot . '/local/courses/lib.php');

        // We always must pass webservice params through validate_parameters.
        $params = self::validate_parameters(self::submit_create_course_form_parameters(),
                                            ['contextid' => $contextid/*, 'form_status'=>$form_status*/,  'jsonformdata' => $jsonformdata]);

        $context = context::instance_by_id($params['contextid'], MUST_EXIST);

        // We always must call validate_context in a webservice.
        self::validate_context($context);
        $serialiseddata = json_decode($params['jsonformdata']);

        $data = array();
        parse_str($serialiseddata, $data);

        $warnings = array();
        if ($id) {
            $course = get_course($id);
            $category = $DB->get_record('course_categories', array('id'=>$course->category), '*', MUST_EXIST);
        }else{
            $course = null;
        }

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
            $mform = new custom_course_form(null, array(/*'form_status' => $form_status,*/'courseid'=>$data['id']), 'post', '', null, true, $data);
        $validateddata = $mform->get_data();
      //  print_r($validateddata);
        if ($validateddata) {
            $formheaders = array_keys($mform->formstatus);
            // $category_id=$data['category'];
// <mallikarjun> - ODL-750 adding college to curriculums -- starts
if($validateddata->open_univdept_status == 1){
    $validateddata->open_departmentid = $validateddata->open_collegeid;
    $open_departmentid = $data['open_collegeid'];
}else{
    $validateddata->open_departmentid = $validateddata->open_departmentid;
    $open_departmentid = $data['open_departmentid'];
}
//            $open_departmentid = $data['open_departmentid'];
// <mallikarjun> - ODL-750 adding college to curriculums -- ends
            $category_id = $DB->get_field('local_costcenter', 'category', array('id' => $open_departmentid));
            if ($validateddata->id <= 0) {
                // $validateddata->open_identifiedas=implode(',',$validateddata->open_identifiedas);
                //$validateddata->open_identifiedas=implode(',',$validateddata->open_identifiedas);
                $validateddata->open_identifiedas=5;
                $validateddata->category = $category_id;
                if($open_departmentid=='null'){
                    $validateddata->open_departmentid = 0;
                }else{
                    $validateddata->open_departmentid = $open_departmentid;
                }
                $validateddata->open_parentcourseid = 0;
               // print_r($validateddata);exit;
                $courseid = create_course($validateddata, $editoroptions);
                $coursedata = $courseid->id;
                $coursedata = $courseid->open_identifiedas;
                $enrol_status = $validateddata->selfenrol;
                $coursedata = $DB->get_record('course',array('id' => $courseid->id));
                 //print_r($courseid);exit;
                insert::add_enrol_meathod_tocourse($coursedata,$enrol_status);

            } elseif($validateddata->id > 0) {
                $validateddata->open_identifiedas=implode(',',$validateddata->open_identifiedas);
                // if($form_status == 0){
                     $courseid =new stdClass();
                      $courseid->id=$data['id'];
                      $validateddata->category = $category_id;
                      $validateddata->open_identifiedas = 5;
                      $validateddata->open_departmentid = $open_departmentid;
                     update_course($validateddata, $editoroptions);

                     $coursedata = $DB->get_record('course',array('id' => $data['id']));
                     insert::add_enrol_meathod_tocourse($coursedata, $coursedata->selfenrol);

                // }else{
                     // $data=(object)$data;
                     // $data->startdate=$validateddata->startdate;
                     // $data->enddate=$validateddata->enddate;
                     // $courseid =new stdClass();
                     //  $courseid->id=$data->id;
                     // $DB->update_record('course', $data);
                // }
            }
            // $next = $form_status + 1;
            $nextform = array_key_exists($next, $formheaders);
            // if ($nextform !== false) {
            //     $form_status = $next;
            //     $error = false;
            // } else {
            //     $form_status = -1;
            //     $error = true;
            // }
            $enrolid = $DB->get_field('enrol', 'id' ,array('courseid' => $courseid->id ,'enrol' => 'manual'));
            $existing_method = $DB->get_record('enrol',array('courseid'=> $courseid->id  ,'enrol' => 'self'));
            $courseenrolid = $DB->get_field('course','selfenrol',array('id'=> $courseid->id));  
            if($courseenrolid == 1){
                $existing_method->status = 0;
                $existing_method->customint6 = 1;
            }else{
                $existing_method->status = 1;
            }
          if(isset($existing_method->id)){
            $DB->update_record('enrol', $existing_method);
         }

        } else {
            // Generate a warning.
            throw new moodle_exception('Error in submission');
        }
        $return = array(
            'courseid' => $courseid->id,
            'enrolid' => $enrolid,
            /*'form_status' => $form_status*/);

        return $return;
    }

    /**
     * Returns description of method result value.
     *
     * @return external_description
     * @since Moodle 3.0
     */
    public static function submit_create_course_form_returns() {
       return new external_single_structure(array(
            'courseid' => new external_value(PARAM_INT, 'Course id'),
            'enrolid' => new external_value(PARAM_INT, 'manual enrol id for the course'),
            // 'form_status' => new external_value(PARAM_INT, 'form_status'),
        ));
    }

         /**
     * Describes the parameters for submit_create_course_form webservice.
     * @return external_function_parameters
     */
    public static function submit_create_category_form_parameters() {
        return new external_function_parameters(
            array(
                'contextid' => new external_value(PARAM_INT, 'The context id for the category'),
                'jsonformdata' => new external_value(PARAM_RAW, 'The data from the create course form, encoded as a json array')
            )
        );
    }

    /**
     * Submit the create category form.
     *
     * @param int $contextid The context id for the category.
     * @param string $jsonformdata The data from the form, encoded as a json array.
     * @return int new category id.
     */
    public static function submit_create_category_form($contextid, $jsonformdata) {
        global $DB, $CFG, $USER;
        require_once($CFG->dirroot.'/course/lib.php');
        require_once($CFG->libdir.'/coursecatlib.php');
        require_once($CFG->dirroot . '/local/courses/lib.php');

        // We always must pass webservice params through validate_parameters.
        $params = self::validate_parameters(self::submit_create_course_form_parameters(),
                                            ['contextid' => $contextid, 'jsonformdata' => $jsonformdata]);

        $context = context::instance_by_id($params['contextid'], MUST_EXIST);
        // We always must call validate_context in a webservice.
        self::validate_context($context);
        $serialiseddata = json_decode($params['jsonformdata']);

        $data = array();
        parse_str($serialiseddata, $data);

        $warnings = array();
        $id = $data['id'];
        if ($id) {
            $coursecat = coursecat::get($id, MUST_EXIST, true);
            $category = $coursecat->get_db_record();
            $context = context_coursecat::instance($id);
            $itemid = 0; // Initialise itemid, as all files in category description has item id 0.
        } else {
            $parent = $data['parent'];
            if ($parent) {
                $DB->record_exists('course_categories', array('id' => $parent), '*', MUST_EXIST);
                $context = context_coursecat::instance($parent);
            } else {
                $context = context_system::instance();
            }
            $category = new stdClass();
            $category->id = 0;
            $category->parent = $parent;
            $itemid = null; // Set this explicitly, so files for parent category should not get loaded in draft area.

        }

        // The last param is the ajax submitted data.
        $mform = new local_courses\form\coursecategory_form(null, array(), 'post', '', null, true, $data);

        $validateddata = $mform->get_data();
        if ($validateddata) {
            if ($validateddata->id > 0) {
                if ((int)$validateddata->parent !== (int)$coursecat->parent && !$coursecat->can_change_parent($validateddata->parent)) {
                    print_error('cannotmovecategory');
                }
                $category = $coursecat->update($validateddata, $mform->get_description_editor_options());
            } else {
                $category = coursecat::create($validateddata, $mform->get_description_editor_options());
            }

        } else {
            // Generate a warning.
            throw new moodle_exception('Error in submission');
        }

        return $category->id;
    }

    /**
     * Returns description of method result value.
     *
     * @return external_description
     * @since Moodle 3.0
     */
    public static function submit_create_category_form_returns() {
        return new external_value(PARAM_INT, 'category id');
    }

    /**
     * Describes the parameters for delete_category_form webservice.
     * @return external_function_parameters
     */
    public static function submit_delete_category_form_parameters() {
        return new external_function_parameters(
            array(
                //'evalid' => new external_value(PARAM_INT, 'The evaluation id '),
                'contextid' => new external_value(PARAM_INT, 'The context id for the category'),
                'jsonformdata' => new external_value(PARAM_RAW, 'The data from the create category form, encoded as a json array'),
                'categoryid' => new external_value(PARAM_INT, 'The category id for the category')
            )
        );
    }

    /**
     * Submit the delete category form.
     *
     * @param int $contextid The context id for the category.
     * @param int $categoryid The id for the category.
     * @param string $jsonformdata The data from the form, encoded as a json array.
     * @return int new category id.
     */
    public static function submit_delete_category_form($contextid, $jsonformdata, $categoryid) {
        global $DB, $CFG, $USER;
        require_once($CFG->dirroot.'/course/lib.php');
        require_once($CFG->libdir.'/coursecatlib.php');
        require_once($CFG->dirroot . '/local/courses/lib.php');

        // We always must pass webservice params through validate_parameters.
        $params = self::validate_parameters(self::submit_create_course_form_parameters(),
                                            ['contextid' => $contextid, 'jsonformdata' => $jsonformdata]);

        $context = context::instance_by_id($params['contextid'], MUST_EXIST);

        // We always must call validate_context in a webservice.
        self::validate_context($context);
        $serialiseddata = json_decode($params['jsonformdata']);

        $data = array();
        parse_str($serialiseddata, $data);

        $warnings = array();
        if ($categoryid) {
            $category = coursecat::get($categoryid);
            $context = context_coursecat::instance($category->id);
        }else {
            $category = coursecat::get_default();
            $categoryid = $category->id;
            $context = context_coursecat::instance($category->id);
        }

        // The last param is the ajax submitted data.
        $mform = new local_courses\form\deletecategory_form(null, $category, 'post', '', null, true, $data);
        $validateddata = $mform->get_data();
        if ($validateddata) {
            // The form has been submit handle it.
                if ($validateddata->fulldelete == 1 && $category->can_delete_full()) {
                    $continueurl = new moodle_url('/local/courses/index.php');
                    if ($category->parent != '0') {
                        $continueurl->param('categoryid', $category->parent);
                    }
                    $deletedcourses = $category->delete_full(false);
                    $DB->delete_records('local_departments', array('catid'=>$validateddata->categoryid));
                    
                } else if ($validateddata->fulldelete == 0 && $category->can_move_content_to($validateddata->newparent)) {
                    $deletedcourses = $category->delete_move($validateddata->newparent, false);
                    $DB->delete_records('local_departments', array('catid'=>$validateddata->categoryid));

                } else {
                    // Some error in parameters (user is cheating?)
                    $mform->display();
                }

        } else {
            // Generate a warning.
            throw new moodle_exception('Error in submission');
        }

            return true;
    }

    /**
     * Returns description of method result value.
     *
     * @return external_description
     * @since Moodle 3.0
     */
    public static function submit_delete_category_form_returns() {
        return new external_value(PARAM_INT, '');
    }

          /**
     * Describes the parameters for departmentlist webservice.
     * @return external_function_parameters
     */
    public static function departmentlist_parameters() {
        return new external_function_parameters(
            array(
                'costcenter' => new external_value(PARAM_INT, 'The id for the costcenter / organization'),
            )
        );
    }

    /**
     * departments list
     *
     * @param int $costcenter id for the organization.
     * @return array 
     */
    public static function departmentlist($costcenter) {
        global $DB, $CFG, $USER;
        $userlib = new local_users\functions\userlibfunctions();
        $departmentlist = $userlib->find_departments_list($costcenter);
        $supervisorlist = $userlib->find_supervisor_list($supervisor);

        $category = $DB->get_field('local_costcenter','category',array('id' => $costcenter));
        $categorylist = categorylist('moodle/category:manage','','/',0,$category);

         $return = array(
            'departmentlist' => json_encode($departmentlist),
            'categorylist' => json_encode($categorylist),
            'supervisorlist' => json_encode($supervisorlist));
        return $return;
    }

    /**
     * Returns description of method result value
     *
     * @return external_description
     */
    public static function departmentlist_returns() {
        return new external_function_parameters(
            array(
                'departmentlist' => new external_value(PARAM_RAW, 'Department list '),
                'categorylist' => new external_value(PARAM_RAW, 'Category list '),
                'supervisorlist' => new external_value(PARAM_RAW, 'Supervisor list '),
            )
        );
    }

    /** Describes the parameters for delete_course webservice.
     * @return external_function_parameters
    */
    public static function delete_course_parameters() {
        return new external_function_parameters(
            array(
                'action' => new external_value(PARAM_ACTION, 'Action of the event', false),
                'id' => new external_value(PARAM_INT, 'ID of the record', 0),
                'confirm' => new external_value(PARAM_BOOL, 'Confirm', false),
                'name' => new external_value(PARAM_RAW, 'name', false),
                'count' => new external_value(PARAM_INT, 'count of duplicated courses', 0),
            )
        );
    }

    /**
     * Deletes course
     *
     * @param int $action 
     * @param int $confirm 
     * @param int $id course id
     * @param string $name
     * @return int new course id.
     */
    public static function delete_course($action, $id, $confirm, $name, $count) {
        global $DB;
        try {
            if ($confirm) {
                $corcat = $DB->get_field('course','category',array('id' => $id));
                $category = $DB->get_record('course_categories',array('id'=>$corcat));
                delete_course($id,false);
                $category->coursecount = $category->coursecount-1;
                $DB->update_record('course_categories',$category);
                $return = true;
            } else {
                $return = false;
            }
        } catch (dml_exception $ex) {
            print_error('deleteerror', 'local_classroom');
            $return = false;
        }
        return $return;
    }

    /**
     * Returns description of method result value
     * @return external_description
     */

    public static function delete_course_returns() {
        return new external_value(PARAM_BOOL, 'return');
    }

    /* Describes the parameters for global_filters_form_option_selector webservice.
    * @return external_function_parameters
    */
    public static function global_filters_form_option_selector_parameters() {
        $query = new external_value(
            PARAM_RAW,
            'Query string'
        );
        $action = new external_value(
            PARAM_RAW,
            'Action for the classroom form selector'
        );
        $options = new external_value(
            PARAM_RAW,
            'Action for the classroom form selector'
        );
        $searchanywhere = new external_value(
            PARAM_BOOL,
            'find a match anywhere, or only at the beginning'
        );
        $page = new external_value(
            PARAM_INT,
            'Page number'
        );
        $perpage = new external_value(
            PARAM_INT,
            'Number per page'
        );
        return new external_function_parameters(array(
            'query' => $query,
            'action' => $action,
            'options' => $options,
            'searchanywhere' => $searchanywhere,
            'page' => $page,
            'perpage' => $perpage,

        ));
    }

    /**
     * Creates filter elements
     *
     * @param string $query
     * @param int $action
     * @param array $options
     * @param string $searchanywhere
     * @param int $page 
     * @param int $perpage
     * @param string $jsonformdata The data from the form, encoded as a json array.
     * @return string filter form element
     */
    public static function global_filters_form_option_selector($query, $action, $options, $searchanywhere, $page, $perpage) {
        global $CFG, $DB, $USER;
        $params = self::validate_parameters(self::global_filters_form_option_selector_parameters(), array(
            'query' => $query,
            'action' => $action,
            'options' => $options,
            'searchanywhere' => $searchanywhere,
            'page' => $page,
            'perpage' => $perpage
        ));
        $query = $params['query'];
        $action = $params['action'];
        $options = $params['options'];
        $searchanywhere=$params['searchanywhere'];
        $page=$params['page'];
        $perpage=$params['perpage'];

        if (!empty($options)) {
            $formoptions = json_decode($options);
        }
        if ($action) {
            $return = array();
            if($action === 'categories' || $action === 'elearning'){
                $filter = 'courses';
            } else if($action === 'email' || $action === 'employeeid' || $action === 'username' || $action === 'users'){
                $filter = 'users';
            } else if($action === 'organizations' || $action === 'departments'){
                $filter = 'costcenter';
            } //RM add issue ODL-755
            else if($action === 'programdepartments' || $action === 'programcolleges' ){
                $filter = 'program';
            //RM end issue ODL-755
            } else{
                $filter = $action;
            }
            $core_component = new core_component();
            $courses_plugin_exist = $core_component::get_plugin_directory('local', $filter);
            if ($courses_plugin_exist) {
                require_once($CFG->dirroot . '/local/' . $filter . '/lib.php');
                $functionname = $action.'_filter';
                if(!empty($formoptions->costcenter)){
                    $costcenter = $formoptions->costcenter;
                    $roleid = $formoptions->roleid;
                    $return=$functionname('',$query,$searchanywhere, $page, $perpage, $costcenter, $roleid);
                }else{
                    $return=$functionname('',$query,$searchanywhere, $page, $perpage);
                }
            }
            return json_encode($return);
        }
    }

    /**
     * Returns description of method result value
     *
     * @return external_description
     */
    public static function global_filters_form_option_selector_returns() {
        return new external_value(PARAM_RAW, 'data');
    }
    public function suspend_local_course_parameters(){
        return new external_function_parameters(
            array(
                'id' => new external_value(PARAM_INT, 'id', 0),
                'contextid' => new external_value(PARAM_INT, 'The context id for the evaluation', false)
            )
        );
    }
    public function suspend_local_course($id,$contextid){
        global $DB;

        $course = $DB->get_record('course', array('id' => $id));
        if($course){
            if($course->visible){
                $status = 0;
            }else{
                $status = 1;
            }
            $DB->execute('UPDATE {course} SET `visible` = :status WHERE id = :id', array('id' => $course->id, 'status' => $status));
            $return = TRUE;
        }else {
            throw new moodle_exception('Error in inactivating');
            $return = FALSE;
        }
        return $return;
    }
    public function suspend_local_course_returns(){
        return new external_value(PARAM_BOOL, 'return');
    }
      public function availability_local_course_parameters(){
        return new external_function_parameters(
            array(
                'id' => new external_value(PARAM_INT, 'id', 0),
                'contextid' => new external_value(PARAM_INT, 'The context id for the evaluation', false)
            )
        );
    }
    public function availability_local_course($id,$contextid){
        global $DB;

        $course = $DB->get_record('course', array('id' => $id));

        if($course){
            if($course->sold_status){
                $status = 0;
            }else{
               
                $status = 1;
            }
            $DB->execute('UPDATE {course} SET sold_status = :status WHERE id = :id', array('id' => $course->id, 'status' => $status));
            $return = TRUE;
        }else {
            throw new moodle_exception('Error in inactivating');
            $return = FALSE;
        }
        return $return;
    }
    public function availability_local_course_returns(){
        return new external_value(PARAM_BOOL, 'return');
    }

    public static function get_users_course_status_information_parameters() {
        return new external_function_parameters(
            array('status' => new external_value(PARAM_RAW, 'status of course', true),
                'searchterm' => new external_value(PARAM_RAW, 'searchterm', VALUE_OPTIONAL, ''),
                'page' => new external_value(PARAM_INT, 'page', VALUE_OPTIONAL, 0),
                'perpage' => new external_value(PARAM_INT, 'perpage', VALUE_OPTIONAL, 15)
            )
        );
    }
    public static function get_users_course_status_information($status, $searchterm = "", $page = 0, $perpage = 15) {
        global $USER, $DB, $CFG;

        $result = array();
        // if ($status == 'completed') {
        //     $user_course_info = general_lib::completed_coursenames($searchterm, $page * $perpage, $perpage);
        //     $total = general_lib::completed_coursenames_count($searchterm);
        // } else if ($status == 'inprogress') {
        //     $user_course_info = general_lib::inprogress_coursenames($searchterm, $page * $perpage, $perpage);
        //     $total = general_lib::inprogress_coursenames_count($searchterm);
        // } else if($status == 'enrolled') {
        //     $user_course_info = general_lib::enrolled_coursenames($searchterm, $page * $perpage, $perpage);
        //     $total = general_lib::enrolled_coursenames_count($searchterm);
        // }
        $user_course_info = array();
        $total = 0;
        foreach ($user_course_info as $userinfo) {
            $context = context_course::instance($userinfo->id, IGNORE_MISSING);
            list($userinfo->summary,$userinfo->summaryformat) =
                external_format_text($userinfo->summary ,$userinfo->summaryformat , $context->id, 'course', 'summary', null);
                $progress = null;
            // Return only private information if the user should be able to see it.
            if ($userinfo->enablecompletion) {
                $progress = \core_completion\progress::get_course_progress_percentage($userinfo, $userid);
            }

            $result[] = array(
                'id' => $userinfo->id,
                'fullname' => $userinfo->fullname,
                'shortname' => $userinfo->shortname,
                'summary' => $userinfo->summary,
                'summaryformat' => $userinfo->summaryformat,
                'startdate' => $userinfo->startdate,
                'enddate' => $userinfo->enddate,
                'timecreated' => $userinfo->timecreated,
                'timemodified' => $userinfo->timemodified,
                'visible' => $userinfo->visible,
                'idnumber' => $userinfo->idnumber,
                'format' => $userinfo->format,
                'showgrades' => $userinfo->showgrades,
                'lang' => clean_param($userinfo->lang,PARAM_LANG),
                'enablecompletion' => $userinfo->enablecompletion,
                'category' => $userinfo->category,
                'progress' => $progress
            );
        }

        return array('mycourses' => $result, 'total' => $total);
    }
    public static function get_users_course_status_information_returns(){
        return new external_single_structure(
            array(
                'mycourses' => new external_multiple_structure(
                    new external_single_structure(
                        array(
                            'id'=> new external_value(PARAM_INT, 'id of course'),
                            'fullname'=> new external_value(PARAM_RAW, 'fullname of course'),
                            'shortname' => new external_value(PARAM_RAW, 'short name of course'),
                            'summary' => new external_value(PARAM_RAW, 'course summary'),
                            'summaryformat' => new external_value(PARAM_RAW, 'course summary format'),
                            'startdate' => new external_value(PARAM_RAW, 'startdate of course'),
                            'enddate' => new external_value(PARAM_RAW, 'enddate of course'),
                            'timecreated' => new external_value(PARAM_RAW, 'course create time'),
                            'timemodified' => new external_value(PARAM_RAW, 'course modified time'),
                            'visible' => new external_value(PARAM_RAW, 'course status'),
                            'idnumber' => new external_value(PARAM_RAW, 'course idnumber'),
                            'format' => new external_value(PARAM_RAW, 'course format'),
                            'showgrades' => new external_value(PARAM_RAW, 'course grade status'),
                            'lang' => new external_value(PARAM_RAW, 'course language'),
                            'enablecompletion' => new external_value(PARAM_RAW, 'course completion'),
                            'category' => new external_value(PARAM_RAW, 'course category'),
                            'progress' => new external_value(PARAM_FLOAT, 'Progress percentage')
                        )
                    )
                ),
                'total' => new external_value(PARAM_INT, 'Total Pages')
            )
        );
    }
    public static function get_recently_enrolled_courses_parameters(){
        return new external_function_parameters(
            array(
            )
        );
    }
    public function get_recently_enrolled_courses(){
        global $DB,$USER;
        $result = array();
        $enrolledcourses = array();
        // $enrolledcourses = general_lib::enrolled_coursenames();
        if(empty($enrolledcourses)){
            // $enrolledcourses = general_lib::inprogress_coursenames();
            $header = 'Recently Enrolled Courses';
        }
        else {
            $header = 'Recently Accessed Courses';
        }
        foreach ($enrolledcourses as $userinfo) {

            $context = context_course::instance($userinfo->id, IGNORE_MISSING);
            list($userinfo->summary,$userinfo->summaryformat) =
                external_format_text($userinfo->summary ,$userinfo->summaryformat , $context->id, 'course', 'summary', null);
                $progress = null;
            // Return only private information if the user should be able to see it.
            if ($userinfo->enablecompletion) {
                $progress = \core_completion\progress::get_course_progress_percentage($userinfo, $userid);
            }
            $result[] = array(
                'id' => $userinfo->id,
                'fullname' => $userinfo->fullname,
                'shortname' => $userinfo->shortname,
                'summary' => $userinfo->summary,
                'summaryformat' => $userinfo->summaryformat,
                'startdate' => $userinfo->startdate,
                'enddate' => $userinfo->enddate,
                'timecreated' => $userinfo->timecreated,
                'timemodified' => $userinfo->timemodified,
                'visible' => $userinfo->visible,
                'idnumber' => $userinfo->idnumber,
                'format' => $userinfo->format,
                'showgrades' => $userinfo->showgrades,
                'lang' => clean_param($userinfo->lang,PARAM_LANG),
                'enablecompletion' => $userinfo->enablecompletion,
                'category' => $userinfo->category,
                'progress' => $progress,
            );
        }
        if(empty($result)){
                $header = 'Recently Enrolled Courses';
            }
       return array('mycourses' => $result, 'heading' => $header);
    }
    public static function get_recently_enrolled_courses_returns(){
        return new external_single_structure(
            array(
                'mycourses' => new external_multiple_structure(
                    new external_single_structure(
                        array(
                            'id'=> new external_value(PARAM_INT, 'id of course'),
                            'fullname'=> new external_value(PARAM_RAW, 'fullname of course'),
                            'shortname' => new external_value(PARAM_RAW, 'short name of course'),
                            'summary' => new external_value(PARAM_RAW, 'course summary'),
                            'summaryformat' => new external_value(PARAM_RAW, 'course summary format'),
                            'startdate' => new external_value(PARAM_RAW, 'startdate of course'),
                            'enddate' => new external_value(PARAM_RAW, 'enddate of course'),
                            'timecreated' => new external_value(PARAM_RAW, 'course create time'),
                            'timemodified' => new external_value(PARAM_RAW, 'course modified time'),
                            'visible' => new external_value(PARAM_RAW, 'course status'),
                            'idnumber' => new external_value(PARAM_RAW, 'course idnumber'),
                            'format' => new external_value(PARAM_RAW, 'course format'),
                            'showgrades' => new external_value(PARAM_RAW, 'course grade status'),
                            'lang' => new external_value(PARAM_RAW, 'course language'),
                            'enablecompletion' => new external_value(PARAM_RAW, 'course completion'),
                            'category' => new external_value(PARAM_RAW, 'course category'),
                            'progress' => new external_value(PARAM_FLOAT, 'Progress percentage')
                        )
                    )
                 ),
                'heading' => new external_value(PARAM_RAW, 'Heading')
            )
        );
    }
     public static function courses_view_parameters() {
    return new external_function_parameters([
        'options' => new external_value(PARAM_RAW, 'The paging data for the service'),
        'dataoptions' => new external_value(PARAM_RAW, 'The data for the service'),
        'offset' => new external_value(PARAM_INT, 'Number of items to skip from the begging of the result set',
            VALUE_DEFAULT, 0),
        'limit' => new external_value(PARAM_INT, 'Maximum number of results to return',
            VALUE_DEFAULT, 0),
        'contextid' => new external_value(PARAM_INT, 'contextid'),
        'filterdata' => new external_value(PARAM_RAW, 'filters applied'),
    ]);
  }

  /**
   * lists all courses
   *
   * @param array $options
   * @param array $dataoptions
   * @param int $offset
   * @param int $limit
   * @param int $contextid
   * @param array $filterdata
   * @return array courses list.
   */
public static function courses_view($options, $dataoptions, $offset = 0, $limit = 0, $contextid, $filterdata) {
    global $DB, $PAGE;
    require_login();
    $PAGE->set_url('/local/courses/courses.php', array());
    $PAGE->set_context($contextid);
    // Parameter validation.
    $params = self::validate_parameters(
        self::courses_view_parameters(),
        [
            'options' => $options,
            'dataoptions' => $dataoptions,
            'offset' => $offset,
            'limit' => $limit,
            'contextid' => $contextid,
            'filterdata' => $filterdata
        ]
    );
    $offset = $params['offset'];
    $limit = $params['limit'];
    $filtervalues = json_decode($filterdata);

    $stable = new \stdClass();
    $stable->thead = false;
    $stable->start = $offset;
    $stable->length = $limit;
    $data = get_listof_courses($stable, $filtervalues);
    $totalcount = $data['totalcourses'];

    return [
        'totalcount' => $totalcount,
        'length' => $totalcount,
        'filterdata' => $filterdata,
        'records' =>$data,
        'options' => $options,
        'dataoptions' => $dataoptions,
    ];
  }

  /**
   * Returns description of method result value
   * @return external_description
   */

  public static function courses_view_returns() {
      return new external_single_structure([
          'options' => new external_value(PARAM_RAW, 'The paging data for the service'),
          'dataoptions' => new external_value(PARAM_RAW, 'The data for the service'),
          'totalcount' => new external_value(PARAM_INT, 'total number of challenges in result set'),
          'filterdata' => new external_value(PARAM_RAW, 'total number of challenges in result set'),
          'length' => new external_value(PARAM_RAW, 'total number of challenges in result set'),
          'records' => new external_single_structure(
                  array(
                      'hascourses' => new external_multiple_structure(
                          new external_single_structure(
                              array(
                                  'coursename' => new external_value(PARAM_RAW, 'coursename'),
                                //  'coursenameCut' => new external_value(PARAM_RAW, 'coursenameCut', VALUE_OPTIONAL),
                                  'catname' => new external_value(PARAM_RAW, 'catname'),
                                  'catnamestring' => new external_value(PARAM_RAW, 'catnamestring'),
                                  'courseimage' => new external_value(PARAM_RAW, 'catnamestring'),
                                  'enrolled_count' => new external_value(PARAM_INT, 'enrolled_count', VALUE_OPTIONAL),
                                  'courseid' => new external_value(PARAM_INT, 'courseid'),
                                  'completed_count' => new external_value(PARAM_INT, 'completed_count', VALUE_OPTIONAL),
                                  'open_year' => new external_value(PARAM_RAW, 'open_year', VALUE_OPTIONAL),
                                  'points' => new external_value(PARAM_INT, 'points', VALUE_OPTIONAL),
                                  'coursetype' => new external_value(PARAM_RAW, 'coursetype', VALUE_OPTIONAL),
                                  'coursesummary' => new external_value(PARAM_RAW, 'coursesummary', VALUE_OPTIONAL),
                                  'courseurl' => new external_value(PARAM_RAW, 'courseurl',VALUE_OPTIONAL),
                                  'enrollusers' => new external_value(PARAM_RAW, 'enrollusers', VALUE_OPTIONAL),
                                  'enrollstaff' => new external_value(PARAM_RAW, 'enrollstaff', VALUE_OPTIONAL),
                                  'editcourse' => new external_value(PARAM_RAW, 'editcourse', VALUE_OPTIONAL),
                                  'update_status' => new external_value(PARAM_RAW, 'update_status', VALUE_OPTIONAL),
                                  'course_class' => new external_value(PARAM_TEXT, 'course_status', VALUE_OPTIONAL),
                                  'deleteaction' => new external_value(PARAM_RAW, 'designation', VALUE_OPTIONAL),
                                  'grader' => new external_value(PARAM_RAW, 'grader', VALUE_OPTIONAL),
                                  'activity' => new external_value(PARAM_RAW, 'activity', VALUE_OPTIONAL),
                                  'enrolusers'=> new external_value(PARAM_RAW, 'enrolusers', VALUE_OPTIONAL),
                                  'requestlink' => new external_value(PARAM_RAW, 'requestlink', VALUE_OPTIONAL),
                                  'skillname' => new external_value(PARAM_RAW, 'skillname', VALUE_OPTIONAL),
                                  'ratings_value' => new external_value(PARAM_RAW, 'ratings_value', VALUE_OPTIONAL),
                                  'ratingenable' => new external_value(PARAM_BOOL, 'ratingenable', VALUE_OPTIONAL),
                                  'tagstring' => new external_value(PARAM_RAW, 'tagstring', VALUE_OPTIONAL),
                                  'tagenable' => new external_value(PARAM_BOOL, 'tagenable', VALUE_OPTIONAL),
                                  'masterclass' => new external_value(PARAM_RAW, 'masterclass', VALUE_OPTIONAL),
                                  'syllabus' => new external_value(PARAM_RAW, 'syllabus', VALUE_OPTIONAL),
                                  'school' => new external_value(PARAM_RAW, 'school', VALUE_OPTIONAL),
                                  'coursecode' => new external_value(PARAM_RAW, 'coursecode', VALUE_OPTIONAL),
                                  'action1' => new external_value(PARAM_RAW, 'action1', VALUE_OPTIONAL),
                                  'curriculumcount' => new external_value(PARAM_RAW, 'curriculumcount', VALUE_OPTIONAL),
// <mallikarjun> - ODL-782 labels display -- starts
                                  'open_univdept_status' => new external_value(PARAM_RAW, 'open_univdept_status', VALUE_OPTIONAL),
// <mallikarjun> - ODL-782 labels display -- end
                              )
                          )
                      ),
                      'request_view' => new external_value(PARAM_INT, 'request_view', VALUE_OPTIONAL),
                      'report_view' => new external_value(PARAM_INT, 'report_view', VALUE_OPTIONAL),
                      'grade_view' => new external_value(PARAM_INT, 'grade_view', VALUE_OPTIONAL),
                      'delete' => new external_value(PARAM_INT, 'delete', VALUE_OPTIONAL),
                      'update' => new external_value(PARAM_INT, 'update', VALUE_OPTIONAL),
                      'enrol' => new external_value(PARAM_INT, 'enrol', VALUE_OPTIONAL),
                      'actions' => new external_value(PARAM_INT, 'actions', VALUE_OPTIONAL),
                      'nocourses' => new external_value(PARAM_BOOL, 'nocourses', VALUE_OPTIONAL),
                      'totalcourses' => new external_value(PARAM_INT, 'totalcourses', VALUE_OPTIONAL),
                      'length' => new external_value(PARAM_INT, 'length', VALUE_OPTIONAL),
                  )
              )

      ]);
  }
}

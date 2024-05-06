<?php
namespace local_mooccourses\form;
use core;
use moodleform;
use context_system;
use core_component;

require_once($CFG->dirroot . '/lib/formslib.php');
class categories_form extends moodleform { 
    public function definition() {
        global $USER, $CFG,$DB;
        // $corecomponent = new \core_component();

        $mform = $this->_form;
        $id = $this->_customdata['courseid'];
        $mooccourseid = $this->_customdata['id'];
        $parentcourseid = $this->_customdata['parentcourseid'];
        $editoroptions = $this->_customdata['editoroptions'];
        if($mooccourseid){
            $mform->addElement('hidden','id',$mooccourseid);
             $mform->setType('id', PARAM_INT);
        }
        $costcenterid = $DB->get_field('course','open_costcenterid',array('id' => $parentcourseid));
        $costcenters =  $DB->get_field('local_costcenter','fullname',array('id' => $costcenterid));
        $mform->addElement('static', 'university', get_string('university','local_courses'),$costcenters);
        $mform->setDefault('university',$costcenters);

        

        $systemcontext = context_system::instance();
        if($this->_ajaxformdata['open_costcenterid']){
          $parentid = $this->_ajaxformdata['open_costcenterid'];
          $sql_query = "select id,fullname from {local_costcenter} where parentid = $parentid AND visible = 1 AND univ_dept_status = 0";
        }else{
          $sql_query = "select ld.id,ld.fullname from {local_costcenter} ld JOIN {course} c ON c.open_departmentid = ld.id where c.id = ".$id;
        }
         
     
         $deptlist = $DB->get_record_sql($sql_query);
         
         $sql = "select lc.category,lc.fullname from {local_costcenter} lc JOIN {course} c ON c.open_costcenterid = lc.id where c.id = ".$id." AND lc.univ_dept_status = 0";
         $costcenterlist = $DB->get_records_sql_menu($sql);
         $selectdept = array(null => '--Select Department--');
         //$categorylist = $selectdept+$deptlist+$costcenterlist;
       
        if($mooccourseid > 0){
            $courses_sql = "SELECT COUNT(ue.id) FROM {course} AS course
                JOIN {enrol} AS e ON course.id = e.courseid AND e.enrol IN('self','manual','auto')
                JOIN {user_enrolments} ue ON e.id = ue.enrolid
                WHERE course.id = $mooccourseid AND course.id>1";
            $enrolmentcount = $DB->count_records_sql($courses_sql);
        }
        
        if($enrolmentcount){
            $course = $DB->get_record('course',array('id' => $mooccourseid));
            $department = $DB->get_record('course_categories',array('id' => $course->category));
            $mform->addElement('static', 'departmentname', get_string('departments','local_courses'));
            $mform->setDefault('departmentname',$department->name);

            $mform->addElement('hidden', 'category');
          //  $mform->setType('category', PARAM_INT);
            $mform->setConstant('category', $course->open_departmentid);
        }else{
          //   $mform->addElement('static', 'department', get_string('departments','local_courses'));
          // //  $mform->addRule('category', get_string('missingdepartment','local_courses'), 'required', null, 'client');  
          //   $mform->setDefault('department',$deptlist->fullname);
          //   $mform->addElement('hidden', 'category');
          // //  $mform->setType('category', PARAM_INT);
          //   $mform->setConstant('category', $deptlist->id);

          //  $mform->setType('category', PARAM_INT);

          //Revathi Issues ODL-826 changing label starts
           $departmentname = $DB->get_record('local_costcenter',array('id' => $deptlist->id));
           if($departmentname->univ_dept_status == '1'){
            $mform->addElement('static', 'department', get_string('college', 'local_program')); 
            }else{

              $mform->addElement('static', 'department', get_string('departments','local_courses'));
            }
            $mform->setDefault('department',$departmentname->fullname);
            $mform->addElement('hidden', 'category');          
            $mform->setConstant('category', $departmentname->id);
             //Revathi Issues ODL-826 changing label ends

        }

    
        $mform->addElement('text', 'fullname', get_string('coursename', 'local_mooccourses'));
        // $mform->setType('fullname', PARAM_TEXT);
        $mform->addRule('fullname', get_string('missingfullname', 'local_mooccourses'), 'required', null, 'client');
        
        $mform->addElement('text', 'shortname', get_string('courseshortname','local_mooccourses'), 'maxlength="100" size="20"');        
        $mform->addRule('shortname', get_string('missingshortname', 'local_mooccourses'), 'required', null, 'client');       
        $mform->addElement('text',  'open_cost', 'Cost');
            //$mform->setType('open_cost', PARAM_INT);
       // $mform->addRule('open_cost', null , 'required', null, 'client');     
       
        $mform->addElement('date_selector', 'startdate', get_string('startdate','local_courses'),
         array());
        $mform->addHelpButton('startdate', 'startdate');
    
        $mform->addElement('date_selector', 'enddate', get_string('enddate','local_courses'), array('optional' => false));
        $mform->addHelpButton('enddate', 'enddate');
       
        $mform->disable_form_change_checker();
    }
    function validation($data, $files) {
        global $DB;
        $errors = array();
        $errors = parent::validation($data, $files);
        $shortname = $data['shortname'];
        $fullname = $data['fullname'];
        if(!empty($shortname)){
           $shortname = preg_match('/^\S*$/', $shortname); 
           if(!$shortname){
            $errors['shortname'] = get_string('spacesnotallowed', 'local_mooccourses');
           }

        }
        if($data['category'] == 'null' || $data['category'] == 0) {
            $errors['category'] = get_string('missingdepartment','local_courses');
        }
        if(empty($shortname)){
            $errors['shortname'] = get_string('missingshortname', 'local_mooccourses');
        }
        if(empty($fullname)){
            $errors['fullname'] = get_string('missingfullname', 'local_mooccourses');
        }
        if(!empty($data['open_cost'])){
           if(!is_numeric($data['open_cost'])){
               $errors['open_cost'] = get_string('costcannotbenonnumericwithargs', 'local_mooccourses',$data['open_cost']);   
           }
        }
// <mallikarjun> - ODL-827 update mooc course -- starts
        $idval = $data['id'];
        if($idval > 0){
        if(!empty($fullname)){
        $subsql1 = "SELECT * FROM {course} WHERE id != $idval AND shortname = '".$data['shortname']."' AND open_departmentid = '".$data['department']."' ";
        $existing = $DB->get_record_sql($subsql1);
        if(!empty($existing)){
        $errors['shortname'] = get_string('shortnametaken', '', $course->fullname);
        }
        }
        }else{
        // Add field validation check for duplicate shortname.
        if ($DB->record_exists('course', array('shortname' => $data['shortname']), '*', IGNORE_MULTIPLE)) {
           // if (empty($data['id']) || $course->id != $data['id']) {
                $errors['shortname'] = get_string('shortnametaken', '', $course->fullname);
            //}
        }
        }
// <mallikarjun> - ODL-827 update mooc course -- ends
            if ($data['enddate'] < $data['startdate']) {
                $errors['enddate'] = get_string('nosameenddate', 'local_mooccourses');
            }
        

        return $errors;
    }
}

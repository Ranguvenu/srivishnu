<?php
require_once($CFG->libdir . '/formslib.php');
use \local_curriculum\form\curriculum_form as curriculum_form;
//use \local_curriculum\form\program_manageyear_form as program_manageyear_form;


function local_curriculum_leftmenunode(){
    global $USER, $DB;
    $systemcontext = context_system::instance();
    $usersnode = '';

    $managecurriculum_string =  get_string('manage_curriculam','local_curriculum');

    if(has_capability('local/costcenter:manage',$systemcontext) || is_siteadmin()) {
        $usersnode .= html_writer::start_tag('li', array('id'=> 'id_leftmenu_curriculum', 'class'=>'pull-left user_nav_div users dropdown-item'));
        $users_url = new moodle_url('/local/curriculum/index.php');
        $users = html_writer::link($users_url, '<i class="fa fa-list-alt" aria-hidden="true"></i><span class="user_navigation_link_text">'.$managecurriculum_string.'</span>',array('class'=>'user_navigation_link'));
        $usersnode .= $users;
        $usersnode .= html_writer::end_tag('li');
    }
    return array('6' => $usersnode);
}
function local_curriculum_output_fragment_curriculum_form($args){

    global $CFG, $PAGE, $DB;
    $args = (object) $args;
    $context = $args->context;
    $return = '';
    $renderer = $PAGE->get_renderer('local_curriculum');
    $formdata = [];
    if (!empty($args->jsonformdata)) {
        $serialiseddata = json_decode($args->jsonformdata);
        parse_str($serialiseddata, $formdata);
    }
    if ($args->id > 0) {
        $curriculumdata = $DB->get_record('local_curriculum', array('id' => $args->id));
    }
    $formdata['id'] = $args->id;
    //$formdata['program'] = $args->program;
    $curriculumyear = $DB->get_field('local_curriculum','duration',array('id' => $args->id));
    $mform = new curriculum_form(null, array('id' => $args->id, 'departmentid' => $args->department, 'costcenter' => $curriculumdata->costcenter, 'form_status' => $args->form_status), 'post', '', null, true, $formdata);
    //code Comment by Harish starts here//
    /*$curriculumdata = new stdClass();
    $curriculumdata->id = $args->id;
    $curriculumdata->departmentid = $args->department;
    $curriculumdata->form_status = $args->form_status;*/
    //code Comment by Harish ends here//
     
  //  $curriculumdata->duration =  preg_replace("/[^0-9]/", '',  $curriculumyear);

    $mform->set_data($curriculumdata);

    if (!empty((array) $serialiseddata)) {
        // If we were passed non-empty form data we want the mform to call validation functions and show errors.
        $mform->is_validated();
    }
    $formheaders = array_keys($mform->formstatus);
    $nextform = array_key_exists($args->form_status, $formheaders);
    if ($nextform === false) {
        return false;
    }
    ob_start();
    $formstatus = array();
    foreach (array_values($mform->formstatus) as $k => $mformstatus) {
        $activeclass = $k == $args->form_status ? 'active' : '';
        $formstatus[] = array('name' => $mformstatus, 'activeclass' => $activeclass);
    }
    $formstatusview = new \local_program\output\form_status($formstatus);
    $return .= $renderer->render($formstatusview);
    $mform->display();
    $return .= ob_get_contents();
    ob_end_clean();

    return $return;

}
function local_curriculum_output_fragment_curriculum_manageyear_form($args) {
    global $CFG, $PAGE, $DB;
    $args = (object) $args;
    $context = $args->context;
    $return = '';
    $renderer = $PAGE->get_renderer('local_curriculum');
    $formdata = [];
    if (!empty($args->jsonformdata)) {
        $serialiseddata = json_decode($args->jsonformdata);
        parse_str($serialiseddata, $formdata);
    }
    $formdata['id'] = $args->id;
    $formdata['curriculumid'] = $args->curriculumid;
    $formdata['programid'] = $args->programid;

    $mform = new curriculum_manageyear_form(null, array('id' => $args->id,
        'curriculumid' => $args->curriculumid,'cost' => $args->cost, 'programid' => $args->programid, 'form_status' => $args->form_status), 'post', '', null,
        true, $formdata);
    $bcsemester = new stdClass();
    $bcsemester->curriculumid = $args->curriculumid;
    $bcsemester->programid = $args->programid;
    if ($args->id > 0) {
        $bcsemester = $DB->get_record('local_program_cc_years', array('id' => $args->id));
    }

    $bcsemester->form_status = $args->form_status;
    $mform->set_data($bcsemester);

    if (!empty((array) $serialiseddata)) {
        // If we were passed non-empty form data we want the mform to call validation functions and show errors.
        $mform->is_validated();
    }
    $formheaders = array_keys($mform->formstatus);
    $nextform = array_key_exists($args->form_status, $formheaders);
    if ($nextform === false) {
        return false;
    }
    ob_start();
    $formstatus = array();
    foreach (array_values($mform->formstatus) as $k => $mformstatus) {
        $activeclass = $k == $args->form_status ? 'active' : '';
        $formstatus[] = array('name' => $mformstatus, 'activeclass' => $activeclass);
    }
    $formstatusview = new \local_program\output\form_status($formstatus);
    $return .= $renderer->render($formstatusview);
    $mform->display();
    $return .= ob_get_contents();
    ob_end_clean();

    return $return;
}

function local_curriculum_output_fragment_curriculum_managesemester_form($args) {
    global $CFG, $PAGE, $DB;
    $args = (object) $args;
    $context = $args->context;
    $return = '';
    $renderer = $PAGE->get_renderer('local_curriculum');
    $formdata = [];
    if (!empty($args->jsonformdata)) {
        $serialiseddata = json_decode($args->jsonformdata);
        parse_str($serialiseddata, $formdata);
    }
    $formdata['id'] = $args->id;
    $formdata['curriculumid'] = $args->curriculumid;
    $formdata['programid'] = $args->programid;

    $mform = new program_managesemester_form(null, array('id' => $args->id,
        'curriculumid' => $args->curriculumid, 'programid' => $args->programid, 'yearid' => $args->yearid, 'form_status' => $args->form_status), 'post', '', null,
        true, $formdata);
    $bcsemester = new stdClass();
    $bcsemester->curriculumid = $args->curriculumid;
    $bcsemester->programid = $args->programid;
    if ($args->id > 0) {
        $bcsemester = $DB->get_record('local_curriculum_semesters', array('id' => $args->id));
    }

    $bcsemester->form_status = $args->form_status;
    $bcsemester->semester_description['text'] = $bcsemester->description;
    $mform->set_data($bcsemester);

    if (!empty((array) $serialiseddata)) {
        // If we were passed non-empty form data we want the mform to call validation functions and show errors.
        $mform->is_validated();
    }
    $formheaders = array_keys($mform->formstatus);
    $nextform = array_key_exists($args->form_status, $formheaders);
    if ($nextform === false) {
        return false;
    }
    ob_start();
    $formstatus = array();
    foreach (array_values($mform->formstatus) as $k => $mformstatus) {
        $activeclass = $k == $args->form_status ? 'active' : '';
        $formstatus[] = array('name' => $mformstatus, 'activeclass' => $activeclass);
    }
    $formstatusview = new \local_program\output\form_status($formstatus);
    $return .= $renderer->render($formstatusview);
    $mform->display();
    $return .= ob_get_contents();
    ob_end_clean();

    return $return;
}
function local_curriculum_output_fragment_course_form($args) {
    global $CFG, $PAGE, $DB;
    require_once($CFG->dirroot.'/local/curriculum/classes/form/programcourse_form.php');
    $args = (object) $args;
    $context = $args->context;
    $return = '';
    $renderer = $PAGE->get_renderer('local_curriculum');
    $formdata = [];
    if (!empty($args->jsonformdata)) {
        $serialiseddata = json_decode($args->jsonformdata);
        parse_str($serialiseddata, $formdata);
    }
    $formdata['programid'] = $args->programid;
    $formdata['curriculumid'] = $args->curriculumid;
    $formdata['yearid'] = $args->yearid;
    $formdata['semesterid'] = $args->semesterid;
    $mform = new programcourses_form(null, array('programid' => $args->programid,'curriculumid' => $args->curriculumid, 'yearid' => $args->yearid, 'semesterid' => $args->semesterid,
        'form_status' => $args->form_status), 'post', '', null, true, $formdata);
    $coursedata = new stdClass();
    $coursedata->id = $args->id;
    $coursedata->form_status = $args->form_status;
    $mform->set_data($coursedata);

    if (!empty((array) $serialiseddata)) {
        // If we were passed non-empty form data we want the mform to call validation functions and show errors.
        $mform->is_validated();
    }
    $formheaders = array_keys($mform->formstatus);
    $nextform = array_key_exists($args->form_status, $formheaders);
    if ($nextform === false) {
        return false;
    }
    ob_start();
    $formstatus = new \local_program\output\form_status(array_values($mform->formstatus));
   // $return .= $renderer->render($managefaculty_formformstatus);
    $mform->display();
    $return = ob_get_contents();
    ob_end_clean();

    return $return;
}
function local_curriculum_output_fragment_curriculum_managefaculty_form($args) {
    global $CFG, $PAGE, $DB;
    require_once($CFG->dirroot."/local/curriculum/classes/form/managefaculty_form.php");
    $args = (object) $args;
    $context = $args->context;
    $return = '';
    $renderer = $PAGE->get_renderer('local_curriculum');
    $formdata = [];
    if (!empty($args->jsonformdata)) {
        $serialiseddata = json_decode($args->jsonformdata);
        parse_str($serialiseddata, $formdata);
    }
    $formdata['yearid'] = $args->yearid;
    $formdata['programid'] = $args->programid;
    $formdata['curriculumid'] = $args->curriculumid;
    $formdata['semesterid'] = $args->semesterid;
    $formdata['courseid'] = $args->courseid;


    $mform = new managefaculties_form(null, array('programid' => $args->programid, 'curriculumid' => $args->curriculumid,'yearid' => $args->yearid, 'semesterid' => $args->semesterid, 'courseid' => $args->courseid,
        'form_status' => $args->form_status), 'post', '', null, true, $formdata);
    $curriculumdata = new stdClass();
    $curriculumdata->id = $args->id;
    $curriculumdata->programid = $args->programid;
    $curriculumdata->curriculumid = $args->curriculumid;
    $curriculumdata->form_status = $args->form_status;
    $mform->set_data($curriculumdata);

    if (!empty((array) $serialiseddata)) {
        // If we were passed non-empty form data we want the mform to call validation functions and show errors.
        $mform->is_validated();
    }
    $formheaders = array_keys($mform->formstatus);
    $nextform = array_key_exists($args->form_status, $formheaders);
    if ($nextform === false) {
        return false;
    }
    ob_start();
    $formstatus = array();
    foreach (array_values($mform->formstatus) as $k => $mformstatus) {
        $activeclass = $k == $args->form_status ? 'active' : '';
        $formstatus[] = array('name' => $mformstatus, 'activeclass' => $activeclass);
    }
    $formstatusview = new \local_program\output\form_status($formstatus);
    $return .= $renderer->render($formstatusview);
    $mform->display();
    $return .= ob_get_contents();
    ob_end_clean();

    return $return;
}
function local_curriculum_output_fragment_curriculum_managestudent_form($args) {
    global $CFG, $PAGE, $DB;
    //    print_object($args);
    $args = (object) $args;
  //  print_object($args);exit;

    $context = $args->context;
    $return = '';
    $renderer = $PAGE->get_renderer('local_curriculum');
    $formdata = [];
    if (!empty($args->jsonformdata)) {
        $serialiseddata = json_decode($args->jsonformdata);
        parse_str($serialiseddata, $formdata);
    }
    $formdata['yearid'] = $args->yearid;
    $formdata['curriculumid'] = $args->curriculumid;
    $formdata['programid'] = $args->programid;

    $mform = new managestudent_form(null, array('curriculumid' => $args->curriculumid,'yearid' => $args->yearid, 'programid' => $args->programid,
        'form_status' => $args->form_status), 'post', '', null, true, $formdata);
    $curriculumdata = new stdClass();
    $curriculumdata->id = $args->id;
    $curriculumdata->curriculumid = $args->curriculumid;
    $curriculumdata->form_status = $args->form_status;
    $mform->set_data($curriculumdata);

    if (!empty((array) $serialiseddata)) {
        // If we were passed non-empty form data we want the mform to call validation functions and show errors.
        $mform->is_validated();
    }
    $formheaders = array_keys($mform->formstatus);
    $nextform = array_key_exists($args->form_status, $formheaders);
    if ($nextform === false) {
        return false;
    }
    ob_start();
    $formstatus = array();
    foreach (array_values($mform->formstatus) as $k => $mformstatus) {
        $activeclass = $k == $args->form_status ? 'active' : '';
        $formstatus[] = array('name' => $mformstatus, 'activeclass' => $activeclass);
    }
    $formstatusview = new \local_program\output\form_status($formstatus);
    $return .= $renderer->render($formstatusview);
    $mform->display();
    $return .= ob_get_contents();
    ob_end_clean();

    return $return;
}
function costcenter_filter($mform){

      global $DB,$USER;
    $systemcontext = context_system::instance();
    $sql = "SELECT id, fullname FROM {local_costcenter} WHERE parentid = 0";
    $sql .= " ORDER BY fullname";
    $costcenterslist = $DB->get_records_sql_menu($sql);   
    $select = $mform->addElement('autocomplete', 'costcenter', '', $costcenterslist, array('placeholder' => get_string('costcenters','local_curriculum')));
    $mform->setType('costcenter', PARAM_RAW);
    $select->setMultiple(true);
}
 function find_departments($costcenter){
        global $DB;
        if($costcenter) {
            $univdep_sql = "select id,fullname from {local_costcenter} where parentid = $costcenter AND visible = 1 AND univ_dept_status = 0";
            $univ_dep = $DB->get_records_sql($univdep_sql);
            return $costcenter =  $univ_dep;
        }else {
            return $costcenter;
        }
    }

function find_courses($department,$semesterid,$yearid,$curriculumid){
        global $DB;
        if($department) {
           $catids = $DB->get_records_sql_menu("SELECT id,category FROM {local_costcenter} WHERE id IN ($department)");
           $categoryid = implode(',', $catids);
            if(!empty($catids)){
              $concatsql = " AND c.category IN ($categoryid)";
            }else{
              $concatsql = " ";
            }
            $existedcourses = array();                       
            $cousresql = "SELECT c.id, c.fullname
                          FROM {course} c                                      
                          WHERE c.visible = 1 AND open_parentcourseid = 0 AND c.fullname LIKE '%$query%' AND c.id <> " . SITEID . $concatsql;
            $displayedcourses = $DB->get_records_sql($cousresql);
            $cids = array();
            $parentcourseids = array();
                        //--Changes by Yamini to filter the courses--//
            foreach($displayedcourses as $key => $value){
                
              $courseids = $key;
              $childids = $DB->get_record_sql('SELECT cc.courseid FROM {local_cc_semester_courses} cc JOIN {course} c ON c.id = cc.courseid WHERE c.open_parentcourseid ='.$courseids.' AND curriculumid = '.$curriculumid.' AND yearid ='.$yearid);
              if($childids){
                $parentcourseids[] = $courseids;
              }
              $coursenames = $value;
              $cids[] = $courseids;

            }
            if(!empty($cids)){

               foreach($cids as $key => $courseid){
                   $cid = $courseid;
               }
            }
            $course_sql =  "ORDER BY c.id DESC"; 
           if($parentcourseids){
            $pcids = implode(',', $parentcourseids);
            $sql = " AND c.id NOT IN (".$pcids.")";
            $courses = $DB->get_records_sql($cousresql.$sql.$course_sql);
            } 
           else{ 
            $courses = $DB->get_records_sql($cousresql.$course_sql);
            }                   
                    
            return $department =  $courses;
        }else {
            return $department;
        }
    }
    class curriculum_manageyear_form extends moodleform {

    public function definition() {
        global $CFG, $DB, $USER;
        //$querieslib = new querylib();
        $mform = &$this->_form;
        $id = $this->_customdata['id'];
        $programid = $this->_customdata['programid'];
        $curriculumid = $this->_customdata['curriculumid'];
        $cost = $this->_customdata['cost'];
        $context = context_system::instance();

        //$mform->addElement('header', 'general', get_string('addcourses', 'local_program'));

        $mform->addElement('hidden', 'id', $id);
        $mform->setType('id', PARAM_INT);

        $mform->addElement('hidden', 'curriculumid', $curriculumid);
        $mform->setType('curriculumid', PARAM_INT);

        $mform->addElement('hidden', 'programid', $programid);
        $mform->setType('programid', PARAM_INT);
        $mform->addElement('hidden', 'yeardiff', $cost);
        $mform->setType('yeardiff', PARAM_INT);

        if($cost == 0){
        $mform->addElement('text', 'year', get_string('acedemicyear', 'local_curriculum'));
        }

        $mform->addElement('text', 'cost', get_string('cost', 'local_curriculum'));
       // $mform->addRule('cost', null, 'required', null, 'client');
        $mform->addRule('cost', null, 'numeric', null, 'client');
        $mform->addRule('cost', null, 'nonzero', null, 'client');
        $mform->setType('cost', PARAM_FLOAT);

        $mform->disable_form_change_checker();
    }

    public function validation($data, $files) {
        $errors = array();
        global $DB, $CFG;
        $errors = parent::validation($data, $files);

        $cost = $data['cost'];
        if($data['yeardiff'] == 0){
        if($data['year'] == null){
             $errors['year'] = get_string('missingyear', 'local_curriculum');
        }
         }
        if (!empty($data['year']) && strlen($data['year']) > 200) {
                $errors['year'] = get_string('lengthofyear', 'local_curriculum');
        }
        // if(!is_numeric($cost)){
        //     $errors['cost'] = get_string('costshouldinteger', 'local_curriculum');
        // } else if($cost <= 0){
        //     $errors['cost'] = get_string('costshouldpositive', 'local_curriculum');
        // }

        return $errors;
    }

}

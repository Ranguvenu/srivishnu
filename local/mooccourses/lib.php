<?php
defined('MOODLE_INTERNAL') || die;
require_once($CFG->dirroot.'/user/selector/lib.php');
require_once($CFG->libdir . '/formslib.php');

require_once($CFG->dirroot.'/local/mooccourses/filter_form.php');
use \local_mooccourses\form\custom_course_form as custom_course_form;
use \local_mooccourses\form\managestudents_form as managestudents_form;
use \local_courses\action\insert as insert;

    function local_mooccourses_leftmenunode(){
    global $USER, $DB;
    $systemcontext = context_system::instance();
    $usersnode = '';

    if(is_siteadmin() || has_capability('local/program:manageprogram', context_system::instance()) || has_capability('local/mooccourses:manage', context_system::instance())){
        $managemoochcourse_string=get_string('manage_mooc_courses','local_mooccourses');
    }else{
        $managemoochcourse_string=get_string('viewmooccourse','local_mooccourses');
    }

      // $managemoochcourse_string =  get_string('manage_mooc_courses','local_mooccourses');

     if(has_capability('local/costcenter:manage_multiorganizations',$systemcontext) || is_siteadmin() || has_capability('local/costcenter:manage_ownorganization', $systemcontext) || has_capability('local/program:viewprogram', $systemcontext)){
        $usersnode .= html_writer::start_tag('li', array('id'=> 'id_leftmenu_mooccourses', 'class'=>'pull-left user_nav_div users dropdown-item'));
        if(!is_siteadmin() && !has_capability('local/costcenter:manage_multiorganizations',$systemcontext) &&  !has_capability('local/costcenter:manage_ownorganization', $systemcontext) && has_capability('local/costcenter:manage_owndepartments', $systemcontext)){
            $users_url = new moodle_url('/local/mooccourses/index.php',array('type'=>2));
        }
        else{
// <sandeep> - SRIVSPT-3 Manage Mooc Courses Module Changes -- Starts
            $users_url = new moodle_url('/local/mooccourses/index.php',array('type'=>2));
// <sandeep> - SRIVSPT-3 Manage Mooc Courses Module Changes -- Ends
        }
            $users = html_writer::link($users_url, '<i class="fa fa-columns" aria-hidden="true"></i><span class="user_navigation_link_text">'.$managemoochcourse_string.'</span>',array('class'=>'user_navigation_link'));
            $usersnode .= $users;
        $usersnode .= html_writer::end_tag('li');
    }
    return array('8' => $usersnode);
}
function local_mooccourses_output_fragment_custom_mooccourse_form($args){
    global $DB,$CFG,$PAGE;
    $args = (object) $args;
    $context = $args->context;
    $renderer = $PAGE->get_renderer('local_mooccourses');
        $courseid = $args->courseid;
        $o = '';
        $formdata = [];
        if (!empty($args->jsonformdata)) {
            $serialiseddata = json_decode($args->jsonformdata);
            parse_str($serialiseddata, $formdata);
        }
        if ($courseid) {
            $course = get_course($courseid);
            $course = course_get_format($course)->get_course();
            $category = $DB->get_record('course_categories', array('id'=>$course->category), '*', MUST_EXIST);
            $coursecontext = context_course::instance($course->id);
            require_capability('moodle/course:update', $coursecontext);
        }else{
            $category = $CFG->defaultrequestcategory;
        }

        if ($courseid > 0) {
            $heading = get_string('updatecourse', 'local_courses');
            $collapse = false;
            $data = $DB->get_record('course', array('id'=>$courseid));
        }

        $editoroptions = array('maxfiles' => EDITOR_UNLIMITED_FILES, 'maxbytes'=>$CFG->maxbytes, 'trusttext'=>false, 'noclean'=>true,'autosave'=>false);
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

        $params = array(
            'course' => $course,
            'category' => $category,
            'editoroptions' => $editoroptions,
            'returnto' => $returnto,
            'get_coursedetails'=>$get_coursedetails,
            // 'form_status' => $args->form_status,
            'costcenterid' => $data->open_costcenterid
        );
        $mform = new custom_course_form(null, $params, 'post', '', null, true, $formdata);
        // Used to set the courseid.
        $mform->set_data($formdata);

        if (!empty($args->jsonformdata) && strlen($args->jsonformdata)>2) {
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
        $o .= html_writer::script("$(document).ready(function(){
            $('#id_open_costcenterid').on('change',function(){
                var progID = $(this).val();
                if(progID){
                    $.ajax({
                        type:'post',
                        dataType:'json',
                        url: M.cfg.wwwroot + '/local/courses/custom_ajax.php?prog='+progID,
                        success: function(resp){
                            var template =  '<option value=\'\'>--Select Department--</option>';                                    
                            $.each(resp.department, function( index, value) {
                                template += '<option value = ' + index + ' >' +value + '</option>';
                            });
                            $('#id_open_departmentid').html(template);
                            
                        }
                    });
                } else {
                    var template =  '<option value=\'\'>--Select Department--</option>';
                    $('#id_open_departmentid').html(template);
                    var cattemplate =  '<option value=\'\'>--Select Department--</option>';
                    $('#id_category').html(cattemplate);
                }
            });


            $('#id_open_departmentid').on('change',function(){
                var catID = $(this).val();
                if(catID !== 'null'){
                    $.ajax({
                        type:'post',
                        dataType:'json',
                        url: M.cfg.wwwroot + '/local/courses/custom_ajax.php?category=1&cat='+catID,
                        success: function(resp){
                            var cattemplate =  '<option value=\'\'>--Select Department--</option>';
                            $.each(resp, function( index, value) {
                                cattemplate += '<option value = ' + index + ' >' +value + '</option>';
                            });
                            $('#id_category').html(cattemplate);
                        }
                    });
                } else {
                    costcenter = $('#id_open_costcenterid').val();
                    
                    if(costcenter){
                    $.ajax({
                        type:'post',
                        dataType:'json',
                        url: M.cfg.wwwroot + '/local/courses/custom_ajax.php?prog='+costcenter,
                        success: function(resp){
                            var template =  '<option value=null>Select department</option>';                                    
                            $.each(resp.department, function( index, value) {
                                template += '<option value = ' + index + ' >' +value + '</option>';
                            });
                            $('#id_open_departmentid').html(template);
                        }
                    });
                }
                 }
            });
        });");
        $mform->display();
        $o .= ob_get_contents();
        ob_end_clean();


    return $o;
}
function mooccourseslistdetails(){
    global $DB,$USER;
    

     $systemcontext = context_system::instance();
     // $mform = new filter_form();
    $heading = '<span class="filter-lebel">'.get_string('filters').'</span>';
    
     $sql = "select id,fullname from {course} where 1=1";
  
    $sql .= " AND open_parentcourseid = 0 AND sold_status = 1";
     // <mallikarjun> - ODL-838 Displaying all courses in template course page --- starts
    if(!is_siteadmin() && has_capability('local/costcenter:manage_ownorganization',$systemcontext)){
        $sql .= " AND open_costcenterid = ".$USER->open_costcenterid ;
    }

    if(!is_siteadmin() && has_capability('local/costcenter:manage_owndepartments',$systemcontext)){
    
    $sql .= " AND open_costcenterid = ".$USER->open_costcenterid." AND open_univdept_status = ".$USER->open_univdept_status ;
    // <mallikarjun> - ODL-838 Displaying all courses in template course page --- ends
    }
    $res =  $DB->get_records_sql($sql);
     $table = new html_table();
    $table->id = 'selling_courses';
    $table->head = array(get_string('coursename', 'local_mooccourses'),get_string('action', 'local_mooccourses'));
    $table->align = array('left', 'center');
    if($res){     
      foreach ($res as $row) {
         $data = array();
         $output = "";
         $data[] = format_string($row->fullname, true, ['context' => $context]);
      

         $output .= " <a id='extended_menu_createcourses' title='".get_string('create_newcourse', 'local_mooccourses')."' data-action='createcoursemodal' onclick ='(function(e){ require(\"local_mooccourses/newmooccourses\").init({selector:\"createcoursemodal\", context:$systemcontext->id, cid:".$row->id.", form_status:0,course:1}) })(event)' > <span class='createicon'><button id= ".$row->id.">".get_string('useastemplate','local_mooccourses')."</button></span></a>";
         $data[] = $output;
         $table->data[] = $data;
       }
     }
     else{
         $table->data[] = array("no records to display",'');
      }
    $string = html_writer::table($table);

    return $string;
}
function local_mooccourses_output_fragment_selectcategory($args){
 global $CFG,$DB, $PAGE;
   
    $args = (object) $args;
    $context = $args->context;
    $o = '';
    $formdata = [];
    if (!empty($args->jsonformdata)) {
        $serialiseddata = json_decode($args->jsonformdata);
        parse_str($serialiseddata, $formdata);
    }
    if($args->courseid){
        $courseid = $args->courseid;
    }
    if($args->courseid > 0){
    $parentcourseid = $DB->get_field('course','open_parentcourseid',array('id' => $args->courseid));
    }
    else{
    $parentcourseid = $args->cid;
    }
    if ($args->courseid > 0) {
        $course = $DB->get_record('course', array('id' => $args->courseid));
    }
    if($args->courseid){
    $mform = new local_mooccourses\form\categories_form(null,array('courseid' => $courseid,'forpurchaseindividually' => $args->forpurchaseindividually,'id' => $courseid,'parentcourseid' => $parentcourseid), 'post', '', null, true, $formdata);
    }else{
    $mform = new local_mooccourses\form\categories_form(null,array('parentcourseid' => $parentcourseid,'courseid'=>$parentcourseid), 'post', '', null, true, $formdata);
      }
    $course->category = $course->open_departmentid;
    $mform->set_data($course);
    if(!empty($formdata)) {
        // If we were passed non-empty form data we want the mform to call validation functions and show errors.
        $mform->is_validated();
    }
    ob_start();  
    // $o .= $mform->display();
    $mform->display();
    $o .= ob_get_contents();
    ob_end_clean();
    return $o;
}
function local_mooccourses_output_fragment_enroluser($args) {

    global $CFG, $PAGE, $DB;

    $args = (object) $args;
  
   $context = $args->context->id;
    $return = '';
  //  $renderer = $PAGE->get_renderer('local_mooccourses');
    $formdata = [];
    if (!empty($args->jsonformdata)) {
        $serialiseddata = json_decode($args->jsonformdata);
        parse_str($serialiseddata, $formdata);
    }
 // print_object($args->courseid);exit;
    $mform = new managestudents_form(null,array('courseid' => $args->courseid,'department' => $args->department,'id' => $courseid), 'post', '', null, true, $formdata);
    if (!empty($formdata)) {
        // If we were passed non-empty form data we want the mform to call validation functions and show errors.
        $mform->is_validated();
    }
    ob_start();
    $return .= $mform->display();

    $return .= ob_get_contents();
    ob_end_clean();

    return $return;
}
function addstudent($valdata,$courseid){
    global $DB;
  
    foreach($valdata as $value){
    $pluginname = 'mooccourses';
    $user = $value;
    $roleid = $DB->get_field('role','id',array('shortname' => 'student'));
    $enrolmethod = enrol_get_plugin($pluginname);
    $enrolid = $DB->get_field('enrol','id',array('courseid' => $courseid,'enrol' => 'manual'));
    $instance = $DB->get_record('enrol', array('id'=>$enrolid, 'enrol'=>'manual'), '*', MUST_EXIST);
    $enrol_manual = enrol_get_plugin('manual');
    if(!$enrol_manual){
        throw new coding_exception('Can not instantiate enrol_manual');
    }
    $a = $enrol_manual->enrol_user($instance, $user, $roleid, time());
    }
     if($a){
         return true;
     }       
}

/**
* [course_enrolled_users description]
* @param  string  $type       [description]
* @param  integer $evaluationid [description]
* @param  [type]  $params     [description]
* @param  integer $total      [description]
* @param  integer $offset1    [description]
* @param  integer $perpage    [description]
* @param  integer $lastitem   [description]
* @return [type]              [description]
*/
function mooccourse_enrolled_users($type = null, $course_id = 0, $params, $total=0, $offset1=-1, $perpage=-1, $lastitem=0){
    global $DB, $USER;
    $context = context_system::instance();
    $course = $DB->get_record('course', array('id' => $course_id));
 
    $params['suspended'] = 0;
    $params['deleted'] = 0;
    if($total==0){
         $sql = "SELECT u.id,concat(u.firstname,' ',u.lastname,' ','(',u.email,')') as fullname";
    }else{
        $sql = "SELECT count(u.id) as total";
    }
    if($course->open_departmentid == 0){
        $sql.=" FROM {user} AS u
            JOIN {role} as r ON r.id = u.open_role 
            WHERE  u.id > 2 AND u.suspended = :suspended AND u.deleted = :deleted AND u.open_departmentid IS NOT NULL";
    } else {
    // $sql.=" FROM {user} AS u
    //         JOIN {local_costcenter} cc ON cc.id = u.open_departmentid
    //         JOIN {course} as c ON c.open_departmentid = u.open_departmentid AND c.id=$course_id
    //         JOIN {role} as r ON r.id = u.open_role 
    //         WHERE  u.id > 2 AND u.suspended = :suspended AND u.deleted = :deleted AND u.open_departmentid IS NOT NULL AND u.open_departmentid IN ($course->open_departmentid)";

        $sql .= " FROM {user} AS u
            JOIN {role} as r ON r.id = u.open_role 
            WHERE  u.id > 2 AND u.open_departmentid IS NOT NULL AND u.suspended = :suspended AND u.deleted = :deleted AND u.open_departmentid IN ($course->open_departmentid)";
    }
    if($lastitem!=0){
       $sql.=" AND u.id > $lastitem";
    }
    if (!is_siteadmin()) {
        $user_detail = $DB->get_record('user', array('id'=>$USER->id));
        $sql .= " AND u.open_costcenterid = :costcenter";
        $params['costcenter'] = $course ->open_costcenterid;
        if (has_capability('local/costcenter:manage_owndepartments',$context) AND !has_capability('local/costcenter:manage_ownorganization',$context)) {
            $sql .=" AND u.open_departmentid = :department";
            $params['department'] = $user_detail->open_departmentid;
        }
    }
    $sql .=" AND u.id <> $USER->id";
    if (!empty($params['email'])) {
         $sql.=" AND u.id IN ({$params['email']})";
    }
    if (!empty($params['uname'])) {
         $sql .=" AND u.id IN ({$params['uname']})";
    }
    if (!empty($params['department'])) {
         $sql .=" AND u.open_departmentid IN ({$params['department']})";
    }
    if (!empty($params['organization'])) {
        $organizationID = $params['organization'];
        $sql .=" AND u.open_costcenterid = $organizationID";
    }
    if (!empty($params['idnumber'])) {
         $sql .=" AND u.id IN ({$params['idnumber']})";
    }
    if (!empty($params['groups'])) {
         $sql .=" AND u.id IN (SELECT userid FROM {cohort_members} WHERE cohortid IN ({$params['groups']}))";
    }
    if ($type=='add') {
        $sql .= " AND u.id NOT IN (SELECT ue.userid
                             FROM {user_enrolments} AS ue 
                             JOIN {enrol} e ON (e.id = ue.enrolid and e.courseid=$course_id and (e.enrol='manual' OR e.enrol='self')))";
    }elseif ($type=='remove') {
        $sql .= " AND u.id IN (SELECT ue.userid
                             FROM {user_enrolments} AS ue 
                             JOIN {enrol} e ON (e.id = ue.enrolid and e.courseid=$course_id and (e.enrol='manual' OR e.enrol='self')))";
    }
    if (!empty($params['roleid'])) {
        $roleid = $params['roleid'];
        $sql .= " AND u.open_role = $roleid";
    }
    $order = ' ORDER BY u.id ASC ';
    if($perpage!=-1){
        $order.="LIMIT $perpage";
    }
    /*print_object($sql .$order);
    print_object($params);exit;*/
    if($total==0){
        $availableusers = $DB->get_records_sql_menu($sql .$order,$params);
    }else{
        $availableusers = $DB->count_records_sql($sql,$params);
    }
    return $availableusers;
}

function copy_course_instance($cid,$collegeid,$showfeedback = false,$url=null){
    global $DB, $CFG, $USER,$OUTPUT;
        $reurn='';
        $affiliatetemplatecourse='';
        $returncourseid=0;
        $oldcourseid = $cid;
        $clonecourse=$DB->get_record('course',  array('id'=>$oldcourseid),  $fields='*',  $strictness=IGNORE_MISSING);
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
                $clonecourse->affiliationstatus = 1;
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
      }
}

function uncopy_course_instance($cid,$collegeid,$showfeedback = false,$url=null){
    global $DB, $CFG, $USER,$OUTPUT;
    $reurn='';
    $mooccid = $DB->get_record('course', array('open_parentcourseid' => $cid, 'open_departmentid' => $collegeid->id, 'open_costcenterid' => $collegeid->parentid, 'affiliationstatus' => 1), '*', MUST_EXIST);
    if($mooccid){
        delete_course($mooccid->id, false);
        if($showfeedback&&$mooccid) {
            $reurn.= $OUTPUT->notification('Unaffiliated <b>'.$mooccid->fullname.'</b> course under <b>'.$collegeid->fullname.'</b>', 'notifysuccess');
        }
    }
    return $cid;
}

//changes by Harish starts here for Affiliating Mooc courses to College/Study centers
class local_mooccourses_potential_colleges extends user_selector_base {
    protected $cid;
    protected $uid;
    protected $context;
    /**
     * @param string $name control name
     * @param array $options should have two elements with keys groupid and courseid.
     */
    public function __construct($name, $options) {
        global $CFG;
        $this->jsmodule1 = array(
                'name' => 'local_mooccourses',
                'fullpath' => '/local/mooccourses/module.js',
                'requires'  => array('node', 'event-custom', 'datasource', 'json', 'moodle-core-notification'),
                'strings' => array(
                    array('previouslyselectedcolleges', 'local_program', '%%SEARCHTERM%%'),
                    array('nomatchingcolleges', 'local_program', '%%SEARCHTERM%%'),
                    array('none', 'moodle')
                ));
        if (isset($options['context'])) {
            $this->context = context_system::instance();
        } else {
            $this->context = context_system::instance();
        }
        $options['accesscontext'] = context_system::instance();
        $options['extrafields'] = array();
        parent::__construct($name, $options);
        $this->cid = $options['cid'];
        $this->uid = $options['uid'];
        $this->searchanywhere = true;
        require_once($CFG->dirroot . '/group/lib.php');
        // $this->maxusersperpage = 10;
    }

    protected function get_options() {
        global $CFG;
        $options = parent::get_options();
        $options['file'] = 'local/mooccourses/lib.php';
        $options['cid'] = $this->cid;
        $options['uid'] = $this->uid;
        $options['contextid'] = $this->context->id;
        return $options;
    }
    public function output_user($user) {
        $out = $user->fullname.' ('.$user->shortname.')';
        return $out;
    }
    public function display($return = false) {
        global $PAGE;

        // Get the list of requested users.
        $search = optional_param($this->name . '_searchtext', '', PARAM_RAW);
        if (optional_param($this->name . '_clearbutton', false, PARAM_BOOL)) {
            $search = '';
        }
        $groupedusers = $this->find_users($search);

        // Output the select.
        $name = $this->name;
        $multiselect = '';
        if ($this->multiselect) {
            $name .= '[]';
            $multiselect = 'multiple="multiple" ';
        }
        $output = '<div class="userselector" id="' . $this->name . '_wrapper">' . "\n" .
                '<select name="' . $name . '" id="' . $this->name . '" ' .
                $multiselect . 'size="' . $this->rows . '" class="form-control no-overflow">' . "\n";

        // Populate the select.
        $output .= $this->output_options($groupedusers, $search);

        // Output the search controls.
        $output .= "</select>\n<div class=\"form-inline\">\n";
        $output .= '<input type="text" name="' . $this->name . '_searchtext" id="' .
                $this->name . '_searchtext" size="15" value="' . s($search) . '" class="form-control"/>';
        $output .= '<input type="submit" name="' . $this->name . '_searchbutton" id="' .
                $this->name . '_searchbutton" value="' . $this->search_button_caption() . '" class="btn btn-secondary"/>';
        $output .= '<input type="submit" name="' . $this->name . '_clearbutton" id="' .
                $this->name . '_clearbutton" value="' . get_string('clear') . '" class="btn btn-secondary"/>';

        // And the search options.
        $optionsoutput = false;
        if (true) {
            
            $PAGE->requires->js_init_call('M.local_mooccourses.init_user_selector_options_tracker', array(), false, $this->jsmodule1);
            // user_selector_base::$searchoptionsoutput = true;
        }
        $output .= "</div>\n</div>\n\n";

        // Initialise the ajax functionality.
        $output .= $this->initialise_javascript($search);

        // Return or output it.
        if ($return) {
            return $output;
        } else {
            echo $output;
        }
    }

    protected function initialise_javascript($search) {
        global $USER, $PAGE, $OUTPUT;
        $output = '';

        // Put the options into the session, to allow search.php to respond to the ajax requests.
        $options = $this->get_options();
        $hash = md5(serialize($options));
        $USER->userselectors[$hash] = $options;
        // Initialise the selector.
        $PAGE->requires->js_init_call(
            'M.local_mooccourses.init_user_selector',
            array($this->name, $hash, $this->extrafields, $search),
            false,
            $this->jsmodule1
        );
        return $output;
    }

    private function option_checkbox($name, $on, $label) {
        if ($on) {
            $checked = ' checked="checked"';
        } else {
            $checked = '';
        }
        $name = 'userselector_' . $name;
        // For the benefit of brain-dead IE, the id must be different from the name of the hidden form field above.
        // It seems that document.getElementById('frog') in IE will return and element with name="frog".
        $output = '<div class="form-check"><input type="hidden" name="' . $name . '" value="0" />' .
                    '<label class="form-check-label" for="' . $name . 'id">' .
                        '<input class="form-check-input" type="checkbox" id="' . $name . 'id" name="' . $name .
                            '" value="1"' . $checked . ' /> ' . $label .
                    "</label>
                   </div>\n";
        user_preference_allow_ajax_update($name, PARAM_BOOL);
        return $output;
    }
    protected function output_options($groupedusers, $search) {
        $output = '';

        // Ensure that the list of previously selected users is up to date.
        $this->get_selected_users();

        // If $groupedusers is empty, make a 'no matching users' group. If there is
        // only one selected user, set a flag to select them if that option is turned on.
        $select = false;
        if (empty($groupedusers)) {
            if (!empty($search)) {
                $groupedusers = array(get_string('nomatchingcolleges','local_program', $search) => array());
            } else {
                $groupedusers = array(get_string('none') => array());
            }
        } else if ($this->autoselectunique && count($groupedusers) == 1 &&
                count(reset($groupedusers)) == 1) {
            $select = true;
            if (!$this->multiselect) {
                $this->selected = array();
            }
        }

        // Output each optgroup.
        foreach ($groupedusers as $groupname => $users) {
            $output .= $this->output_optgroup($groupname, $users, $select);
        }

        // If there were previously selected users who do not match the search, show them too.
        if ($this->preserveselected && !empty($this->selected)) {
            $output .= $this->output_optgroup(get_string('previouslyselectedcolleges', 'local_program', $search), $this->selected, true);
        }

        // This method trashes $this->selected, so clear the cache so it is rebuilt before anyone tried to use it again.
        $this->selected = null;

        return $output;
    }
    public function find_users($search) {
        global $DB;
        $params = array();
        $colleges = $DB->record_exists('local_costcenter',array('id'=>$this->uid));
        if (!$colleges) {
            print_error('Colleges not found!');
        }
        $deptid = $DB->get_field('local_program', 'departmentid', array('id'=>$this->cid));
        $collegeids = $DB->get_records_sql_menu("SELECT id as id, id as clgid FROM {local_costcenter} WHERE parentid = $this->uid AND visible = 1 AND univ_dept_status = 1");
        if($deptid){
            $departmentids = $DB->get_records_sql_menu("SELECT id as id, id as deptid FROM {local_costcenter} WHERE id = $deptid AND visible = 1 AND univ_dept_status = 0");
        }
        $temparray = array_merge($collegeids, $departmentids);
        $clg_deptids = implode(',', $temparray);
        $fields      = 'SELECT * ';
        $countfields = 'SELECT COUNT(id)';
        $sql   = " FROM {local_costcenter}
                    WHERE 1 = 1 AND parentid = :uid AND visible = :status"; 
        if($clg_deptids){
            $sql .= " AND id IN ($clg_deptids)";
        }
        $params['uid'] = $this->uid;
        $params['status'] = 1;
        if ($search) {
            $sql .= " AND (fullname LIKE '%$search%' OR shortname LIKE '%$search%')";
        }

        $options = array('contextid' => $this->context->id, 'cid' => $this->cid, 'uid' => $this->uid);
        $local_mooccourses_existing_colleges = new local_mooccourses_existing_colleges('removeselect', $options);

        $enrolledcollegeslist = $local_mooccourses_existing_colleges->find_users('', true);
        if (!empty($enrolledcollegeslist)) {
            $enrolledcolleges = implode(',', $enrolledcollegeslist);
            $sql .= " AND id NOT IN ($enrolledcolleges)";
        }

        $availablecolleges = $DB->get_records_sql($fields . $sql , $params);
        if (empty($availablecolleges)) {
            return array();
        }

        if ($search) {
            $groupname = get_string('potcollegesmatching', 'local_program', $search);
        } else {
            $groupname = get_string('potcolleges', 'local_program');
        }
        return array($groupname => $availablecolleges);
    }
}


class local_mooccourses_existing_colleges extends user_selector_base {
    protected $cid;
    protected $uid;
    protected $context;
    /**
     * @param string $name control name
     * @param array $options should have two elements with keys groupid and courseid.
     */
    public function __construct($name, $options) {
        global $CFG;
        $this->jsmodule1 = array(
                'name' => 'local_mooccourses',
                'fullpath' => '/local/mooccourses/module.js',
                'requires'  => array('node', 'event-custom', 'datasource', 'json', 'moodle-core-notification'),
                'strings' => array(
                    array('previouslyselectedcolleges', 'local_program', '%%SEARCHTERM%%'),
                    array('nomatchingcolleges', 'local_program', '%%SEARCHTERM%%'),
                    array('none', 'moodle')
                ));
        $this->searchanywhere = true;
        if (isset($options['context'])) {
            $this->context = context_system::instance();
        } else {
            $this->context = context_system::instance();
        }
        $options['extrafields'] = array();
        $options['accesscontext'] = context_system::instance();
        parent::__construct($name, $options);
        $this->cid = $options['cid'];
        $this->uid = $options['uid'];
        require_once($CFG->dirroot . '/group/lib.php');
        // $this->maxusersperpage = 10;
    }

    protected function get_options() {
        global $CFG;
        $options = parent::get_options();
        $options['file'] = 'local/mooccourses/lib.php';
        $options['cid'] = $this->cid;
        $options['uid'] = $this->uid;
        $options['contextid'] = $this->context->id;
        return $options;
    }
    public function find_users($search, $idsreturn = false) {
        global $DB;

        list($wherecondition, $params) = $this->search_sql($search, '');

        $params['cid'] = $this->cid;
        $params['uid'] = $this->uid;
        $params['status'] = 1;
        // $clgaffiliatedcid = $DB->get_field('course', 'id', array('open_parentcourseid'=>$this->cid, 'open_costcenterid' => $this->uid, 'affiliationstatus' => 1));
        // print_object($clgaffiliatedcid);exit;
        /*$sql = "SELECT u.id, c.id, cc.id
                FROM mdl_user u
                INNER JOIN mdl_role_assignments ra ON ra.userid = u.id
                INNER JOIN mdl_context ct ON ct.id = ra.contextid
                INNER JOIN mdl_course c ON c.id = ct.instanceid
                INNER JOIN mdl_role r ON r.id = ra.roleid
                WHERE r.id =5";
        print_object($clgaffiliatedcid);exit;*/
        $fields = "SELECT s.*, (SELECT COUNT(u.id)
                FROM mdl_user u
                INNER JOIN mdl_role_assignments ra ON ra.userid = u.id
                INNER JOIN mdl_context ct ON ct.id = ra.contextid
                INNER JOIN mdl_course c ON c.id = ct.instanceid
                INNER JOIN mdl_role r ON r.id = ra.roleid
                WHERE r.shortname ='student' AND c.open_parentcourseid = $this->cid
                  AND c.affiliationstatus = 1 AND c.open_departmentid = s.id) AS disabled ";
        // $fields = "SELECT s.* ";
        $countfields = "SELECT COUNT(DISTINCT s.id) ";

        $sql = " FROM {local_costcenter} s
                 JOIN {course} course ON course.open_departmentid = s.id
                WHERE course.open_parentcourseid = :cid AND s.parentid = :uid AND course.affiliationstatus = :status";

        if ($search) {
            $sql .= " AND (s.fullname LIKE '%$search%' OR s.shortname LIKE '%$search%')";
        }
        
        if ($idsreturn) {
            $contextusers = $DB->get_records_sql_menu('SELECT DISTINCT s.id, s.id as collegeid ' . $sql, $params);
            return $contextusers;
        } else {
            // $contextusers = $DB->get_records_sql($fields . $sql , $params, 0, $this->maxusersperpage);
            $contextusers = $DB->get_records_sql($fields . $sql , $params);
        }
        // No users at all.
        if (empty($contextusers)) {
            return array();
        }

        if ($search) {
            $groupname = get_string('enrolledcollegesmatching', 'local_program', $search);
        } else {
            $groupname = get_string('enrolledcolleges', 'local_program');
        }
        return array($groupname => $contextusers);
    }
    public function display($return = false) {
        global $PAGE;

        // Get the list of requested users.
        $search = optional_param($this->name . '_searchtext', '', PARAM_RAW);
        if (optional_param($this->name . '_clearbutton', false, PARAM_BOOL)) {
            $search = '';
        }
        $groupedusers = $this->find_users($search);

        // Output the select.
        $name = $this->name;
        $multiselect = '';
        if ($this->multiselect) {
            $name .= '[]';
            $multiselect = 'multiple="multiple" ';
        }
        $output = '<div class="userselector" id="' . $this->name . '_wrapper">' . "\n" .
                '<select name="' . $name . '" id="' . $this->name . '" ' .
                $multiselect . 'size="' . $this->rows . '" class="form-control no-overflow">' . "\n";

        // Populate the select.
        $output .= $this->output_options($groupedusers, $search);

        // Output the search controls.
        $output .= "</select>\n<div class=\"form-inline\">\n";
        $output .= '<input type="text" name="' . $this->name . '_searchtext" id="' .
                $this->name . '_searchtext" size="15" value="' . s($search) . '" class="form-control"/>';
        $output .= '<input type="submit" name="' . $this->name . '_searchbutton" id="' .
                $this->name . '_searchbutton" value="' . $this->search_button_caption() . '" class="btn btn-secondary"/>';
        $output .= '<input type="submit" name="' . $this->name . '_clearbutton" id="' .
                $this->name . '_clearbutton" value="' . get_string('clear') . '" class="btn btn-secondary"/>';

        // And the search options.
        $optionsoutput = false;
        if (true) {
            $output .= print_collapsible_region_start('', 'userselector_options',
                get_string('searchoptions'), 'userselector_optionscollapsed', true, true);
            $output .= $this->option_checkbox('preserveselected', $this->preserveselected,
                get_string('collegeselectorpreserveselected', 'local_program'));
            $output .= $this->option_checkbox('autoselectunique', $this->autoselectunique,
                get_string('collegeselectorautoselectunique', 'local_program'));
            $output .= $this->option_checkbox('searchanywhere', $this->searchanywhere,
                get_string('collegeselectorsearchanywhere', 'local_program'));
            $output .= print_collapsible_region_end(true);
            $PAGE->requires->js_init_call('M.local_mooccourses.init_user_selector_options_tracker', array(), false, $this->jsmodule1);
            // user_selector_base::$searchoptionsoutput = true;
        }
        $output .= "</div>\n</div>\n\n";

        // Initialise the ajax functionality.
        $output .= $this->initialise_javascript($search);

        // Return or output it.
        if ($return) {
            return $output;
        } else {
            echo $output;
        }
    }
    protected function initialise_javascript($search) {
        global $USER, $PAGE, $OUTPUT;
        $output = '';

        // Put the options into the session, to allow search.php to respond to the ajax requests.
        $options = $this->get_options();
        $hash = md5(serialize($options));
        $USER->userselectors[$hash] = $options;
        // Initialise the selector.
        $PAGE->requires->js_init_call(
            'M.local_mooccourses.init_user_selector',
            array($this->name, $hash, $this->extrafields, $search),
            false,
            $this->jsmodule1
        );
        return $output;
    }

    private function option_checkbox($name, $on, $label) {
        if ($on) {
            $checked = ' checked="checked"';
        } else {
            $checked = '';
        }
        $name = 'userselector_' . $name;
        // For the benefit of brain-dead IE, the id must be different from the name of the hidden form field above.
        // It seems that document.getElementById('frog') in IE will return and element with name="frog".
        $output = '<div class="form-check"><input type="hidden" name="' . $name . '" value="0" />' .
                    '<label class="form-check-label" for="' . $name . 'id">' .
                        '<input class="form-check-input" type="checkbox" id="' . $name . 'id" name="' . $name .
                            '" value="1"' . $checked . ' /> ' . $label .
                    "</label>
                   </div>\n";
        user_preference_allow_ajax_update($name, PARAM_BOOL);
        return $output;
    }
    protected function output_options($groupedusers, $search) {
        $output = '';

        // Ensure that the list of previously selected users is up to date.
        $this->get_selected_users();

        // If $groupedusers is empty, make a 'no matching users' group. If there is
        // only one selected user, set a flag to select them if that option is turned on.
        $select = false;
        if (empty($groupedusers)) {
            if (!empty($search)) {
                $groupedusers = array(get_string('nomatchingcolleges','local_program', $search) => array());
            } else {
                $groupedusers = array(get_string('none') => array());
            }
        } else if ($this->autoselectunique && count($groupedusers) == 1 &&
                count(reset($groupedusers)) == 1) {
            $select = true;
            if (!$this->multiselect) {
                $this->selected = array();
            }
        }

        // Output each optgroup.
        foreach ($groupedusers as $groupname => $users) {
            $output .= $this->output_optgroup($groupname, $users, $select);
        }

        // If there were previously selected users who do not match the search, show them too.
        if ($this->preserveselected && !empty($this->selected)) {
            $output .= $this->output_optgroup(get_string('previouslyselectedcolleges', 'local_program', $search), $this->selected, true);
        }

        // This method trashes $this->selected, so clear the cache so it is rebuilt before anyone tried to use it again.
        $this->selected = null;

        return $output;
    }
    public function output_user($user) {
        $out = $user->fullname.' ('.$user->shortname.')';
        return $out;
    }
    protected function this_con_group_name($search, $numusers) {
        if ($this->context->contextlevel == CONTEXT_SYSTEM) {
            // Special case in the System context.
            if ($search) {
                return get_string('affliatedmatching', 'local_program', $search);
            } else {
                return get_string('affliated', 'local_program');
            }
        }
        $contexttype = context_helper::get_level_name($this->context->contextlevel);
        if ($search) {
            $a = new stdClass;
            $a->search = $search;
            $a->contexttype = $contexttype;
            if ($numusers) {
                return get_string('collegesinthisxmatching', 'local_program', $a);
            } else {
                return get_string('noneinthisxmatching', 'local_program', $a);
            }
        } else {
            if ($numusers) {
                return get_string('collegesinthisx', 'local_program', $contexttype);
            } else {
                return get_string('noneinthisx', 'local_program', $contexttype);
            }
        }
    }

    protected function parent_con_group_name($search, $contextid) {
        $context = context::instance_by_id($contextid);
        $contextname = $context->get_context_name(true, true);
        if ($search) {
            $a = new stdClass;
            $a->contextname = $contextname;
            $a->search = $search;
            return get_string('collegesfrommatching', 'local_program', $a);
        } else {
            return get_string('collegesfrom', 'local_program', $contextname);
        }
    }

}
//changes by Harish ends here for Affiliating Mooc courses to College/Study centers

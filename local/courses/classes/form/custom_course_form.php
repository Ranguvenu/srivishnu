<?php

namespace local_courses\form;
use local_users\functions\userlibfunctions as userlib;
use core;
use moodleform;
use context_system;
use context_course;
use context_coursecat;
use core_component;
defined('MOODLE_INTERNAL') || die;
require_once($CFG->libdir.'/formslib.php');
require_once($CFG->libdir.'/completionlib.php');
require_once($CFG->libdir. '/coursecatlib.php');

class custom_course_form extends moodleform {
    protected $course;
    protected $context;
    public $formstatus;
    public function __construct($action = null, $customdata = null, $method = 'post', $target = '', $attributes = null, $editable = true, $formdata = null) {
        $this->formstatus = array(
            'manage_course' => get_string('manage_course', 'local_courses'),
            'other_details' => get_string('courseother_details', 'local_courses')
        );
        parent::__construct($action, $customdata, $method, $target, $attributes, $editable, $formdata);
    }
    /**
     * Form definition.
     */
    function definition() {
        global $DB,$OUTPUT,$CFG, $PAGE, $USER;
        $mform    = $this->_form;
        $course        = $this->_customdata['course']; // this contains the data of this form
        $course_id        = $this->_customdata['courseid']; // this contains the data of this form
        $category      = $this->_customdata['category'];
        $formstatus = $this->_customdata['form_status'];
		$get_coursedetails = $this->_customdata['get_coursedetails'];
        $editoroptions = $this->_customdata['editoroptions'];
        $returnto = $this->_customdata['returnto'];
        $returnurl = $this->_customdata['returnurl'];
        $costcenterid = $this->_customdata['costcenterid'];
        $systemcontext   = context_system::instance();

        $formheaders = array_keys($this->formstatus);
        $formheader = $formheaders[$formstatus];

        if(empty($category)){
            $category = $CFG->defaultrequestcategory;
        }

        if (!empty($course->id)) {
            $coursecontext = context_course::instance($course->id);
            $context = $coursecontext;
            $categorycontext = context_coursecat::instance($category->id);
        } else {
            $coursecontext = null;
            $categorycontext = context_coursecat::instance($category);
            $context = $categorycontext;
        }

        $courseconfig = get_config('moodlecourse');

        $this->course  = $course;
        $this->context = $context;

        // Form definition with new course defaults.
        $mform->addElement('hidden', 'returnto', null);
        $mform->setType('returnto', PARAM_ALPHANUM);
        $mform->setConstant('returnto', $returnto);

        $mform->addElement('hidden', 'form_status', $formstatus);
        $mform->setType('form_status', PARAM_ALPHANUM);

        $mform->addElement('hidden', 'returnurl', null);
        $mform->setType('returnurl', PARAM_LOCALURL);
        $mform->setConstant('returnurl', $returnurl);

        $mform->addElement('hidden', 'getselectedclients');
        $mform->setType('getselectedclients', PARAM_BOOL);

        $defaultformat = $courseconfig->format;
        $mform->addElement('hidden', 'format', null);
        $mform->setType('format', PARAM_ALPHANUM);
        $mform->setConstant('format', $defaultformat);

        if(empty($course->id)){
            $courseid = 0;
        }else{
            $courseid = $course->id;
        }

        $mform->addElement('hidden', 'id', $courseid);
        $mform->setType('id', PARAM_INT);
		$systemcontext = context_system::instance();
        // if($formstatus == 0){
			$selectdepartmentslist = array(null=>get_string('selectdept','local_courses'));
			// organisation list
            if($courseid){
                $duplicatedcount = $DB->count_records_sql("SELECT count(id) FROM {course}
                                                    WHERE open_parentcourseid = $courseid"); 
            }
            
            if (is_siteadmin($USER->id) || has_capability('local/costcenter:manage_multiorganizations',$systemcontext)) {
                // parentid = 0 for gettings only organisations in dropdown .
                if($duplicatedcount > 0){
                    $parentid = $DB->get_field('course','open_costcenterid',array('id' => $courseid));
                    $universities_sql = "SELECT id, fullname FROM {local_costcenter} WHERE id = $parentid";
                    $universityname = $DB->get_record_sql($universities_sql);
                    $mform->addElement('static', 'universityname', get_string('university', 'local_boards'));
                    $mform->setDefault('universityname',$universityname->fullname.'<br><div class="usermessage">Courses are assigned to Curriculum uder this university</div>');

                    $mform->addElement('hidden', 'open_costcenterid', null);
                    $mform->setType('open_costcenterid', PARAM_INT);
                    $mform->setConstant('open_costcenterid', $parentid);
                }else{
                    $costcenters=$DB->get_records_sql_menu("SELECT id, fullname FROM {local_costcenter} WHERE parentid = 0 and visible = 1");
                    $select=$mform->addElement('select', 'open_costcenterid',get_string('university','local_courses'),array(null=>get_string('selectorg','local_courses')) + $costcenters);
                    $mform->addRule('open_costcenterid', get_string('missingcostcenter','local_courses'), 'required', null, 'client');
                    $costcenter=$DB->get_field('local_costcenter','id',array('id'=>$get_coursedetails->costcenterid));
                    if($costcenter)
                    $select->setSelected(''.$costcenter.'');
                }
                
            } elseif (has_capability('local/costcenter:manage_ownorganization',$systemcontext)){
				$user_organisation = $DB->get_field('user','open_costcenterid', array('id'=>$USER->id));
				$mform->addElement('hidden', 'open_costcenterid', null);
				$mform->setType('open_costcenterid', PARAM_INT);
				$mform->setConstant('open_costcenterid', $user_organisation);
				/*$sql="select id,name from {local_departments} where university = $user_organisation and visible = 1";
				$departmentslist = $DB->get_records_sql_menu($sql);*/
			} else {
                $user_organisation = $DB->get_field('user','open_costcenterid', array('id'=>$USER->id));
                $mform->addElement('hidden', 'open_costcenterid', null);
                $mform->setType('open_costcenterid', PARAM_INT);
                $mform->setConstant('open_costcenterid', $user_organisation);
				
				$mform->addElement('hidden', 'open_departmentid');
				$mform->setType('open_departmentid', PARAM_INT);
				$mform->setConstant('open_departmentid', $USER->open_departmentid);
            }
			
			// department list
            if (is_siteadmin($USER->id) || has_capability('local/costcenter:manage_multiorganizations',$systemcontext) ||
                has_capability('local/costcenter:manage_ownorganization',$systemcontext)) {
                if($courseid > 0 || $this->_ajaxformdata['open_costcenterid']){
                    $sql="select id,fullname from {local_costcenter} WHERE 1=1 and visible = 1";
                        if($courseid && !$this->_ajaxformdata['open_costcenterid']){
                            $sql .= " AND parentid = $costcenterid AND univ_dept_status = 0";
                        }
                        if(($this->_ajaxformdata['open_costcenterid'])){
                            $sql .= " AND parentid = ".$this->_ajaxformdata['open_costcenterid']." AND univ_dept_status = 0";
                        }
                    $departmentslist = $DB->get_records_sql_menu($sql);
                } elseif($USER->open_costcenterid){
                    $departments = userlib::find_universitydepartments_list($USER->open_costcenterid);

                    foreach($departments as $depart){
                        $departmentslist[$depart->id]=$depart->fullname;
                    }
                  
                }/*elseif($opencostcenterid){
                    $departments = userlib::find_universitydepartments_list($opencostcenterid);
                    foreach($departments as $depart){
                        $departmentslist[$depart->id]=$depart->name;
                    }
                }*/
                if(isset($departmentslist)&&!empty($departmentslist)){
                    $finaldepartmentslist = $selectdepartmentslist + $departmentslist;
                }else{
                    $finaldepartmentslist = $selectdepartmentslist;
                }

                if($duplicatedcount > 0){
                    $parentid = $DB->get_field('course','open_departmentid',array('id' => $courseid));
                    $departments_sql = "SELECT id, fullname FROM {local_costcenter} WHERE id = $parentid";
                    $departmentname = $DB->get_record_sql($departments_sql);
// <mallikarjun> - ODL-782 adding college to curriculums -- starts
                    $course_data = $DB->get_record_sql("SELECT * FROM {course} WHERE id = $courseid");
if($course_data->open_univdept_status == 1){
    $mform->addElement('static', 'departmentname', get_string('college','local_costcenter'));
}else{
                    $mform->addElement('static', 'departmentname', get_string('department'));
}
// <mallikarjun> - ODL-782 adding college to curriculums -- ends
                    $mform->setDefault('departmentname',$departmentname->fullname.'<br><div class="usermessage">Courses are assigned to Curriculum uder this department</div>');

                    $mform->addElement('hidden', 'open_departmentid');
                    $mform->setType('open_departmentid', PARAM_INT);
                    $mform->setConstant('open_departmentid', $parentid);
                }else{
// <mallikarjun> - ODL-750 adding college to curriculums -- starts
                    $attributes = array('1' => 'university departments','2' => 'Non university departments');
                    $radioarray=array();
                        if($courseid > 0){
                            $radioarray[] = $mform->createElement('radio', 'open_univdept_status', '', get_string('univ_depart','local_costcenter'), 0, $attributes);
                            $radioarray[] = $mform->createElement('radio', 'open_univdept_status', '', get_string('non_univ_depart','local_costcenter'), 1, $attributes);
                                $mform->addGroup($radioarray, 'univdept_statusclass', get_string('customreq_selectdeptcoll', 'local_users'), array('class' => 'univdept_statusclass'), false);
                        }else{
                                $radioarray[] = $mform->createElement('radio', 'open_univdept_status', '', get_string('univ_depart','local_costcenter'), 0, $attributes);
                                $radioarray[] = $mform->createElement('radio', 'open_univdept_status', '', get_string('non_univ_depart','local_costcenter'), 1 , $attributes);
                                $mform->addGroup($radioarray, 'univdept_statusclass', get_string('customreq_selectdeptcoll', 'local_users') , array('class' => 'univdept_statusclass'), false);
                        }
				    // Fetching college list mapped under university starts here //
			  		$departmentslist = array(null => '--Select College--');
			  		if($courseid > 0){
			  			$existing_costcenter = $DB->get_field('course', 'open_costcenterid',array('id' => $courseid));
			  		}
			  		if($courseid > 0 && $existing_costcenter && !isset($this->_ajaxformdata['open_costcenterid'])){
			  			$open_costcenterid = $existing_costcenter;
					} else{
			  			$open_costcenterid = $this->_ajaxformdata['open_costcenterid'];
					}
					if(!empty($open_costcenterid) && is_siteadmin()){
						$departments = userlib::find_departments_list($open_costcenterid);
						foreach($departments['nonuniv_dep'] as $depart){
							$departmentslist[$depart->id]=$depart->fullname;
						}
					}else if(!is_siteadmin() && has_capability('local/costcenter:view', $systemcontext)){
						$departments = userlib::find_departments_list($USER->open_costcenterid);
						foreach($departments['nonuniv_dep'] as $depart){
							$departmentslist[$depart->id]=$depart->fullname;
						}
					}
					// print_object($departmentslist);
//					$mform->addElement('static', 'open_collegeid', get_string('collegelabel','local_users'),$departmentslist, array('class' => 'college_univ'));
					$mform->addElement('select', 'open_collegeid', get_string('collegelabel','local_users'),$departmentslist, array('class' => 'college_univ'));
			        /*$mform->addRule('open_collegeid', get_string('miisingcollegeid','local_users'), 'required', null, 'client');*/// Commented by Harish
			        $mform->hideIf('open_collegeid', 'open_univdept_status', 'eq', 0);
                    if($courseid > 0){
                       $collegeid = $DB->get_record('course',array('id' => $courseid));
                       $mform->setDefault('open_collegeid',$collegeid->open_departmentid);
                    }
			        // Fetching college list mapped under university ends here //

			        // Fetching departments list mapped under university starts here //
			        $departmentslist = array(null => '--Select Department--');
			  		if($courseid > 0){
			  			$existing_costcenter = $DB->get_field('course', 'open_costcenterid',array('id' => $courseid));
			  		}
			  		if($courseid > 0 && $existing_costcenter && !isset($this->_ajaxformdata['open_costcenterid'])){
			  			$open_costcenterid = $existing_costcenter;
					} else{
			  			$open_costcenterid = $this->_ajaxformdata['open_costcenterid'];
					}
					if(!empty($open_costcenterid) && is_siteadmin()){
						$departments = userlib::find_departments_list($open_costcenterid);
						foreach($departments['univ_dep'] as $depart){
							$departmentslist[$depart->id]=$depart->fullname;
						}
					}else if(!is_siteadmin() && has_capability('local/costcenter:view', $systemcontext)){
						$departments = userlib::find_departments_list($USER->open_costcenterid);
						foreach($departments['univ_dep'] as $depart){
							$departmentslist[$depart->id]=$depart->fullname;
						}
					}
					$mform->addElement('select', 'open_departmentid', get_string('departments','local_users'),$departmentslist, array('class' => 'department_univ'));
			        /*$mform->addRule('open_departmentid', get_string('missing_departments','local_users'), 'required', null, 'client');*/// Commented by Harish
			        $mform->hideIf('open_departmentid', 'open_univdept_status', 'eq', 1);
//                    $mform->addElement('select', 'open_departmentid', get_string('department'),$finaldepartmentslist);
//                    $mform->addRule('open_departmentid', get_string('missingdepartment','local_courses'), 'required', null, 'client');
// <mallikarjun> - ODL-750 adding college to curriculums -- ends
                        // $mform->setType('open_departmentid', PARAM_INT);
                        // $mform->setConstant('open_departmentid', $this->_ajaxformdata['open_departmentid']);
                }
            }
            // Verify permissions to change course category or keep current.
			if ($courseid <= 0)
			$costcenterid = $this->_ajaxformdata['open_costcenterid'];
			$selectcatlist = array(null=>get_string('selectcat','local_courses'));
            if (empty($course->id)) {
                if(is_siteadmin() || has_capability('local/costcenter:manage_multiorganizations',$systemcontext) ||
                has_capability('local/costcenter:manage_ownorganization',$systemcontext)){
    				$opendepartmentid = $this->_ajaxformdata['open_departmentid'];    				
    				if ( $opendepartmentid ) {
    					$sql  = " SELECT id,category FROM {local_costcenter} WHERE  id = $opendepartmentid ";
    					$cat = $DB->get_record_sql($sql);
                        $displaylist = $DB->get_records_sql_menu("SELECT id,name from {course_categories}
                                                      where (path like '/$cat->category/%' or path like '%/$cat->category' or path like '%/$cat->category/%') AND visible=1");
    				} else {
    					if ($costcenterid) {
							$usercategory = $DB->get_field('local_costcenter','category',array('id'=>$costcenterid));
    						$displaylist = $DB->get_records_sql_menu("select id,name from {course_categories}
                                                      where (path like '/$usercategory/%' or path like '%/$usercategory' or path like '%/$usercategory/%') AND visible=1");
						} elseif ((!is_siteadmin()) AND (!has_capability('local/costcenter:manage_multiorganizations',$systemcontext)) AND has_capability('local/costcenter:manage_ownorganization',$systemcontext)){

							$usercategory = $DB->get_field('local_costcenter','category',array('id'=>$USER->open_costcenterid));
							$displaylist = $DB->get_records_sql_menu("select id,name from {course_categories}
                                                      where (path like '/$usercategory/%' or path like '%/$usercategory' or path like '%/$usercategory/%') AND visible=1");
						}
    				}
                }else {
                    $deptcategory = $DB->get_field('local_costcenter','category',array('id'=>$USER->open_departmentid));
                    $displaylist = $DB->get_records_sql_menu("SELECT id,name from {course_categories}
                                                      where (path like '/$deptcategory/%' or path like '%/$deptcategory' or path like '%/$deptcategory/%' OR id = $deptcategory) AND visible=1");
                }
               /* if(isset($displaylist)&&!empty($displaylist)) {
                    $catdisplaylist = $selectcatlist+$displaylist;
                } else {
                    $catdisplaylist = $selectcatlist;
                }*/
				// $mform->addElement('select', 'category', get_string('coursecategory','local_courses'), $catdisplaylist);
				// $mform->addHelpButton('category', 'coursecategory');
				// $mform->addRule('category', null, 'required', null, 'client');
            } else {
				if ($get_coursedetails->open_departmentid!=0 && $get_coursedetails->open_departmentid!=NULL) {
				 	$edited_open_costcenterid = $get_coursedetails->open_departmentid;
                    $costcenter_category = $DB->get_field('local_costcenter','category',array('id'=>$edited_open_costcenterid));
					if ($costcenter_category)
					$displaylist = $DB->get_records_sql_menu("select id,name from {course_categories}
                                                      where (path like '/$costcenter_category/%' or path like '%/$costcenter_category' or path like '%/$costcenter_category/%') AND visible=1");
				} elseif ($get_coursedetails->open_costcenterid && ($get_coursedetails->open_departmentid==0 || $get_coursedetails->open_departmentid==NULL)) {
				 	$edited_open_costcenterid = $get_coursedetails->open_costcenterid;
                    $costcenter_cat = $DB->get_field('local_costcenter','category',array('id'=>$edited_open_costcenterid));
                    $displaylist = $DB->get_records_sql_menu("select id,name from {course_categories}
                                                      where (path like '/$costcenter_cat/%' or path like '%/$costcenter_cat' or path like '%/$costcenter_cat/%') AND visible=1");
				}

                /*if( isset($displaylist) && !empty($displaylist) ){
                    $catdisplaylist = $selectcatlist+$displaylist;
                }else {
                    $catdisplaylist = $selectcatlist;
                }*/
                // $select=$mform->addElement('select', 'category', get_string('coursecategory','local_courses'), $catdisplaylist);
                // $mform->addHelpButton('category', 'coursecategory');
                // $mform->addRule('category', null, 'required', null, 'client');
                // $select->setSelected(''.$category->id.'');
            }

            $mform->addElement('text','fullname', get_string('fullnamecourse','local_courses'),'maxlength="254" size="50"');
            $mform->addHelpButton('fullname', 'fullnamecourse');
            $mform->addRule('fullname', get_string('missingfullname','local_courses'), 'required', null, 'client');
            $mform->setType('fullname', PARAM_TEXT);
            if (!empty($course->id) and !has_capability('moodle/course:changefullname', $coursecontext)) {
                $mform->hardFreeze('fullname');
                $mform->setConstant('fullname', $course->fullname);
            }

            $mform->addElement('text', 'shortname', get_string('coursecode','local_courses'), 'maxlength="100" size="20"');
            $mform->addHelpButton('shortname', 'shortnamecourse');
            $mform->addRule('shortname', get_string('missingshortname','local_courses'), 'required', null, 'client');
            $mform->setType('shortname', PARAM_TEXT);
            if (!empty($course->id) and !has_capability('moodle/course:changeshortname', $coursecontext)) {
                $mform->hardFreeze('shortname');
                $mform->setConstant('shortname', $course->shortname);
            }

            $identify = array();
            $core_component = new core_component();
            $classroom_plugin_exist = $core_component::get_plugin_directory('local', 'classroom');
            $learningplan_plugin_exist = $core_component::get_plugin_directory('local', 'learningplan');
            $program_plugin_exist = $core_component::get_plugin_directory('local', 'program');
            $certification_plugin_exist = $core_component::get_plugin_directory('local', 'certification');
            // if(!empty($classroom_plugin_exist)){
            //     $identify['2'] = get_string('ilt','local_courses');
            // }
            // $identify['3'] = get_string('elearning','local_courses');
            // if(!empty($learningplan_plugin_exist)){
            //     $identify['4'] = get_string('learningplan','local_courses');
            // }
       //      if(!empty($program_plugin_exist)){
			    // $identify['5'] = get_string('program','local_courses');
       //      }
            // if(!empty($certification_plugin_exist)){
            //     $identify['6'] = get_string('certification','local_courses');
            // }
			// $identify['7'] = get_string('bootcamp','local_courses');
            // print_r($identify);
            // exit;
            // $select = $mform->addElement('autocomplete', 'open_identifiedas', get_string('type','local_courses'), $identify);
            // $mform->addRule('open_identifiedas', get_string('missingtype','local_courses'), 'required', null, 'client');
            // $mform->setType('open_identifiedas',PARAM_RAW);
            // $select->setMultiple(true);

            $mform->addElement('hidden', 'minpoints', 0);
            $mform->setType('minpoints', PARAM_INT);

            /*$mform->addElement('text', 'open_points', get_string('points','local_courses'));
            $mform->setType('open_points', PARAM_INT);*/
           /* $mform->addRule('open_points', null, 'required', null, 'client');
            $mform->addRule(array('open_points', 'minpoints'), get_string('points_positive',
             'local_courses'), 'compare', 'gt', 'client');
            $mform->setType('open_points', PARAM_INT);*/
	    
	  //   $manageselfenrol = array();
   //          $manageselfenrol[] = $mform->createElement('radio', 'selfenrol', '', get_string('yes'), 1, $attributes);
   //          $manageselfenrol[] = $mform->createElement('radio', 'selfenrol', '', get_string('no'), 0, $attributes);
   //          $mform->addGroup($manageselfenrol, 'selfenrol',
   //              get_string('need_self_enrol', 'local_courses'),
   //              array('&nbsp;&nbsp;'), false);
		
			// $manageapproval = array();
			// $manageapproval[] = $mform->createElement('radio', 'approvalreqd', '', get_string('yes'), 1, $attributes);
			// $manageapproval[] = $mform->createElement('radio', 'approvalreqd', '', get_string('no'), 0, $attributes);
			// $mform->addGroup($manageapproval, 'approvalreqd',
			// 	get_string('need_manage_approval', 'local_courses'),
			// 	array('&nbsp;&nbsp;'), false);
			
            // Completion tracking.
			$mform->addElement('hidden', 'enablecompletion');
			$mform->setType('enablecompletion', PARAM_INT);
			$mform->setDefault('enablecompletion', 1);
            

            $mform->addElement('editor','summary_editor', get_string('coursesummary','local_courses'), null, $editoroptions);
            $mform->addHelpButton('summary_editor', 'coursesummary');
            $mform->setType('summary_editor', PARAM_RAW);
            $summaryfields = 'summary_editor';

         /*   $mform->addElement('text',  'open_cost', 'Cost');
            $mform->setType('open_cost', PARAM_INT);
            $mform->addRule('open_cost', get_string('numeric','local_users'), 'numeric', null, 'client');*/
            //$skillselect = array(0 => 'Select Skill');
            //$skills = $DB->get_records_menu('local_skill',  array(), '', 'id,name');
            //if(!empty($skills)){
            //    $skillselect = $skillselect+$skills;
            //}
            //$mform->addElement('select',  'open_skill', 'Skill', $skillselect);
            //$mform->setType('open_skill', PARAM_INT);

            //$levelselect = array(0 => 'Select Level');
            //$levels = $DB->get_records_menu('local_course_levels',  array(), '', 'id,name');
            //if(!empty($levels)){
            //    $levelselect = $levelselect+$levels;
            //}
            //$mform->addElement('select',  'open_level', 'Level', $levelselect);
            //$mform->setType('open_level', PARAM_INT);
    //-----Comment by Yamini for displaying these fields in mooccourses-----//
          /*  $mform->addElement('date_selector', 'enrolment_date', get_string('enrolment_date','local_mooccourses'),
             array());
            $mform->addHelpButton('enrolment_date', 'startdate');    

            $mform->addElement('date_selector', 'startdate', get_string('startdate','local_courses'),
             array());
            $mform->addHelpButton('startdate', 'startdate');
        
            $mform->addElement('date_selector', 'enddate', get_string('enddate','local_courses'), array('optional' => false));
            $mform->addHelpButton('enddate', 'enddate');*/

            if ($overviewfilesoptions = course_overviewfiles_options($course)) {
                $mform->addElement('filemanager', 'overviewfiles_filemanager', get_string('courseoverviewfiles','local_courses'), null, $overviewfilesoptions);
                $mform->addHelpButton('overviewfiles_filemanager', 'courseoverviewfiles');
                $summaryfields .= ',overviewfiles_filemanager';
            }

   //      }elseif($formstatus == 1){

   //          $mform->addElement('text',  'open_cost', 'Cost');
   //          $mform->setType('open_cost', PARAM_INT);
   //          $mform->addRule('open_cost', get_string('numeric','local_users'), 'numeric', null, 'client');
   //          //$skillselect = array(0 => 'Select Skill');
   //          //$skills = $DB->get_records_menu('local_skill',  array(), '', 'id,name');
   //          //if(!empty($skills)){
   //          //    $skillselect = $skillselect+$skills;
   //          //}
   //          //$mform->addElement('select',  'open_skill', 'Skill', $skillselect);
   //          //$mform->setType('open_skill', PARAM_INT);

   //          //$levelselect = array(0 => 'Select Level');
   //          //$levels = $DB->get_records_menu('local_course_levels',  array(), '', 'id,name');
   //          //if(!empty($levels)){
   //          //    $levelselect = $levelselect+$levels;
   //          //}
   //          //$mform->addElement('select',  'open_level', 'Level', $levelselect);
   //          //$mform->setType('open_level', PARAM_INT);

   //          $mform->addElement('date_selector', 'startdate', get_string('startdate','local_courses'),
   //           array());
   //          $mform->addHelpButton('startdate', 'startdate');
		
			// $mform->addElement('date_selector', 'enddate', get_string('enddate','local_courses'), array('optional' => false));
   //          $mform->addHelpButton('enddate', 'enddate');
   //      }
        $mform->closeHeaderBefore('buttonar');
		$mform->disable_form_change_checker();
        // Finally set the current form data
        if(empty($course)&&$course_id>0){
             $course = get_course($course_id);
        }
        $this->set_data($course);
		 $mform->disable_form_change_checker();
    }
     /**
     * Validation.
     *
     * @param array $data
     * @param array $files
     * @return array the errors that were found
     */
    function validation($data, $files) {
        global $DB;

        $errors = parent::validation($data, $files);
		$form_data = data_submitted();
		
        // Add field validation check for duplicate shortname.
        $shortname = $data['shortname'];
        if(!empty($shortname)){
           $shortname = preg_match('/^\S*$/', $shortname); 
           if(!$shortname){
            $errors['shortname'] = get_string('spacesnotallowed', 'local_courses');
           }

        }
        if ($course = $DB->get_record('course', array('shortname' => $data['shortname']), '*', IGNORE_MULTIPLE)) {
            if (empty($data['id']) || $course->id != $data['id']) {
                $errors['shortname'] = get_string('shortnametaken', '', $course->fullname);
            }
        }  
		 if (isset($data['startdate']) && $data['startdate']
                && isset($data['enddate']) && $data['enddate']) {
            if ($data['enddate'] < $data['startdate']) {
                $errors['enddate'] = get_string('nosameenddate', 'local_courses');
            }
        }

        // if (isset($data['category']) && $data['form_status'] == 0){
        //     if(empty($data['category'])){
        //         $errors['category'] = get_string('err_category', 'local_courses');
        //     }
        // }

// <mallikarjun> - ODL-750 adding college to curriculums -- starts
            if($data['open_univdept_status'] == 0){
                    if($data['open_departmentid'] == null){
                            $errors['open_departmentid'] = get_string('missing_departments', 'local_users');
                    }
            }else{
                    if($data['open_collegeid'] == null){
                            $errors['open_collegeid'] = get_string('miisingcollegeid', 'local_users');
                    }
           }
// <mallikarjun> - ODL-750 adding college to curriculums -- ends
        $errors = array_merge($errors, enrol_course_edit_validation($data, $this->context));

//        print_r($errors);
//        exit;
        return $errors;
    }
}

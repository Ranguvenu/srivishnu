<?php
namespace local_users\forms;

defined('MOODLE_INTERNAL') || die;
require_once($CFG->libdir . '/formslib.php');
require_once($CFG->libdir . '/completionlib.php');
require_once($CFG->dirroot . '/local/costcenter/lib.php');
require_once($CFG->dirroot . '/local/assignroles/lib.php');

use moodleform;
use context_system;
use costcenter;
use events;
use context_user;
use local_users\functions\userlibfunctions as userlib;

class create_user extends moodleform {
	public $formstatus;
	public function __construct($action = null, $customdata = null, $method = 'post', $target = '', $attributes = null, $editable = true, $formdata = null) {

	 	$this->formstatus = array(
	 		'generaldetails' => get_string('generaldetails', 'local_users'),
			// 'otherdetails' => get_string('otherdetails', 'local_users'),
			'contactdetails' => get_string('contactdetails', 'local_users'),
			);
	 	parent::__construct($action, $customdata, $method, $target, $attributes, $editable, $formdata);
	}
    public function definition() {
        global $USER, $CFG, $DB, $PAGE;
		$systemcontext = context_system::instance();
        $costcenter = new costcenter();
        $mform = $this->_form;
        $form_status = $this->_customdata['form_status'];
        $id = $this->_customdata['id'];
        $employee = $this->_customdata['employee'];
        $roleid = $this->_customdata['open_role'];
        $open_departmentid = $this->_customdata['open_departmentid'];
        $open_collegeid = $this->_customdata['open_collegeid'];
        $open_univdept_status = $this->_customdata['open_univdept_status'];
        $editoroptions = $this->_customdata['editoroptions'];
        $filemanageroptions = $this->_customdata['filemanageroptions'];
        $admin = $this->_customdata['admin'];
        $random = rand(1,10);
        // $radio_status = $this->_customdata['univ_dept_status'];
        if($id > 0){
        	$this->_form->_attributes['id'] ='edituser'.$id.$random;
        }else{
        	$this->_form->_attributes['id'] ='createuser'.$employee.$random;
        }
        if($form_status == 0){

	        if (is_siteadmin()) {
				$sql="select id,fullname from {local_costcenter} where visible =1 and parentid=0 ";
	            $costcenters = $DB->get_records_sql($sql);
	        }elseif(has_capability('local/users:manage',$systemcontext)|| has_capability('local/costcenter:manage',$systemcontext)){
                $university = $DB->get_record_sql("SELECT id, fullname FROM {local_costcenter} WHERE id = $USER->open_costcenterid AND visible = 1");
            }

			if (is_siteadmin($USER) || has_capability('local/costcenter:manage_multiorganizations', $systemcontext)) {
				$organizationlist=array(null=>'--Select University--');
				foreach ($costcenters as $scl) {
					$organizationlist[$scl->id]=$scl->fullname;
				}
				$mform->addElement('select', 'open_costcenterid', get_string('organization', 'local_users'), $organizationlist);
				$mform->addRule('open_costcenterid', get_string('errororganization', 'local_users'), 'required', null, 'client');
			} else if(has_capability('local/costcenter:manage_ownorganization', $systemcontext)|| has_capability('local/costcenter:manage_owndepartments', $systemcontext)){
				/*$user_dept=$DB->get_field('user','open_costcenterid', array('id'=>$USER->id));
				$mform->addElement('hidden', 'open_costcenterid', null);
				$mform->setType('open_costcenterid', PARAM_ALPHANUM);
				$mform->setConstant('open_costcenterid', $user_dept);*/
				$mform->addElement('static', 'universityname', get_string('university', 'local_boards'));
                $mform->setDefault('universityname',$university->fullname);
                $mform->addElement('hidden', 'open_costcenterid',
                get_string('university', 'local_boards'));
                $mform->setType('open_costcenterid', PARAM_INT);
                $mform->setDefault('open_costcenterid', $university->id);
			}
	        $count = count($costcenters);
	        $mform->addElement('hidden', 'count', $count);
	        $mform->setType('count', PARAM_INT);
	        if($employee == 1 || $employee == 2){
		        if(is_siteadmin() || has_capability('local/costcenter:manage_ownorganization', $systemcontext) || has_capability('local/costcenter:manage_multiorganizations', $systemcontext)){
		        	$attributes = array('1' => 'university departments','2' => 'Non university departments');
		        	/*$typestring = 'Select any one<span class="text-nowrap">
            <abbr class="initialism text-danger field_required" title="Required"><img class="icon " alt="Required" title="Required" src="http://localhost/odllmstest/theme/image.php/epsilon/core/1586516269/new_req"></abbr></span>';*/
		        	$radioarray=array();
				        if($id > 0){
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
			  		if($id > 0){
			  			$existing_costcenter = $DB->get_field('user', 'open_costcenterid',array('id' => $id));
			  		}
			  		if($id > 0 && $existing_costcenter && !isset($this->_ajaxformdata['open_costcenterid'])){
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
					$mform->addElement('select', 'open_collegeid', get_string('collegelabel','local_users'),$departmentslist, array('class' => 'college_univ'));
			        /*$mform->addRule('open_collegeid', get_string('miisingcollegeid','local_users'), 'required', null, 'client');*/// Commented by Harish
			        $mform->hideIf('open_collegeid', 'open_univdept_status', 'eq', 0);
			        // Fetching college list mapped under university ends here //

			        // Fetching departments list mapped under university starts here //
			        $departmentslist = array(null => '--Select Department--');
			  		if($id > 0){
			  			$existing_costcenter = $DB->get_field('user', 'open_costcenterid',array('id' => $id));
			  		}
			  		if($id > 0 && $existing_costcenter && !isset($this->_ajaxformdata['open_costcenterid'])){
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
			        // Fetching departments list mapped under university ends here //
					// $mform->addHelpButton('open_departmentid', 'college','local_users');
			        /*$mform->addRule('open_departmentid', get_string('collegerequired', 'local_users'), 'required', null, 'client');*/
			    }elseif(has_capability('local/costcenter:manage_owndepartments', $systemcontext)){
			    	$college = $DB->get_record_sql("SELECT id, fullname FROM {local_costcenter} WHERE id = $USER->open_departmentid AND visible = 1");
			    	$mform->addElement('static', 'collegename', get_string('college/univdept', 'local_users'));
	                $mform->setDefault('collegename',$college->fullname);
	                $mform->addElement('hidden', 'open_departmentid',
	                get_string('university', 'local_boards'));
	                $mform->setType('open_departmentid', PARAM_INT);
	                $mform->setDefault('open_departmentid', $college->id);
	    		// $departmentid = $DB->get_field('user', 'open_departmentid', array('id' => $USER->id));
			  //   	$mform->addElement('hidden', 'open_departmentid');
			  //   	$mform->setType('open_departmentid', PARAM_INT);
					// $mform->setConstant('open_departmentid', $departmentid);
			    }

			    //revathi student edit profile displaying department/college starts

			    if(!is_siteadmin()){
				    if($id>0){
				    	$college = $DB->get_record_sql("SELECT open_departmentid FROM {user} WHERE id = $id");
				    	$mform->addElement('hidden', 'open_departmentid',get_string('university', 'local_boards'));
		                $mform->setType('open_departmentid', PARAM_INT);
		                $mform->setDefault('open_departmentid', $college->id);
				    }
				}
				//revathi student edit profile displaying department/college ends

			}
			    	// $depart = array();
	       //  		$depart[null] = "--Select Department--";
	        		/*if($id > 0){
			  			$existing_costcenter = $DB->get_field('user', 'open_costcenterid',array('id' => $id));
			  		}
			  		// print_object($existing_costcenter);exit;
			    	if($id > 0){
			            $departments_sql = "SELECT id, name
			                                FROM {local_departments}
			                                WHERE university = $existing_costcenter ";
			            $dept_list = $DB->get_records_sql_menu($departments_sql);

			            $depart = $depart + $dept_list;
			        }else if($this->_ajaxformdata['open_costcenterid']){
			            $costcenter = $this->_ajaxformdata['open_costcenterid'];
			            $departments_sql = "SELECT id, name
			                                FROM {local_departments}
			                                WHERE university = $costcenter";
			            // echo    $university;
			            $dept_list = $DB->get_records_sql_menu($departments_sql);

			            $depart= $depart + $dept_list;
			        }
			    	$mform->addElement('select', 'open_depid', get_string('universitydepartment','local_users'),$depart);
					$mform->addHelpButton('open_depid', 'department','local_users');
			        $mform->addRule('open_depid', get_string('universitydepartment', 'local_users'), 'required', null, 'client');*/
			if($employee == 1){
				if($id > 0 && $this->_customdata['open_role']){
					$userrole = $DB->get_record_sql("SELECT id, name FROM {role} WHERE id = $roleid");
					$assignedrole = array($userrole->id => $userrole->name);
				if(is_siteadmin() || (has_capability('local/costcenter:manage_ownorganization',$systemcontext))){
					$mform->addElement('select', 'open_userrole', get_string('assign_roles','local_users'), $assignedrole,'disabled');
					$mform->addRule('open_userrole', get_string('rolerequired', 'local_users'), 'required', null, 'client');
					$mform->addElement('hidden', 'open_role', get_string('maprole', 'local_users'));
	                $mform->setType('open_role', PARAM_INT);
	                $mform->setDefault('open_role', $this->_customdata['open_role']);
	            }
				}else{
			        list($assignableroles, $assigncounts, $nameswithcounts) = local_get_assignable_roles($systemcontext, ROLENAME_BOTH, true);
					$assignablerole = array(null=>'--Select Role--');
					$assignableroles = $assignablerole+$assignableroles;

					if(is_siteadmin() || (has_capability('local/costcenter:manage_ownorganization',$systemcontext)) || (has_capability('local/costcenter:manage_owndepartments', $systemcontext))){
						$mform->addElement('select', 'open_role', get_string('assign_roles','local_users'), $assignableroles);
						$mform->addRule('open_role', get_string('rolerequired', 'local_users'), 'required', null, 'client');
					}
				}
		    }elseif($employee == 3){
		    		// $universityhead = $DB->get_record_sql("SELECT id, name FROM {role} WHERE shortname = 'university_head'");
					// $assignableroles = array($universityhead->id => $universityhead->name);
		    	list($assignableroles, $assigncounts, $nameswithcounts) = local_get_assignable_roles_admins($systemcontext, ROLENAME_BOTH, true);
					$assignablerole = array(null=>'--Select Role--');
					$assignableroles = $assignablerole+$assignableroles;

					$mform->addElement('select', 'open_role', get_string('assign_roles','local_users'), $assignableroles/*, 'disabled'*/);
					$mform->addRule('open_role', get_string('rolerequired', 'local_users'), 'required', null, 'client');
					// $mform->addElement('hidden', 'open_role',
	                // get_string('maprole', 'local_users'));
	                // $mform->setType('open_role', PARAM_INT);
	                // $mform->setDefault('open_role', $universityhead->id);
		    }else{
		    		$studentrole = $DB->get_record_sql("SELECT id, name FROM {role} WHERE shortname = 'student'");
		    		$mform->addElement('hidden', 'open_role',
	                get_string('maprole', 'local_users'));
	                $mform->setType('open_role', PARAM_INT);
	                $mform->setDefault('open_role', $studentrole->id);
		    }

	       /* $mform->addElement('text', 'username', get_string('username', 'local_users'));
	        $mform->addRule('username', get_string('usernamerequired', 'local_users'), 'required', null, 'client');
	        $mform->setType('username', PARAM_RAW);*/
				$mform->addElement('passwordunmask', 'password', get_string('password'), 'size="20"');
				$mform->addHelpButton('password', 'newpassword');
				$mform->setType('password', PARAM_RAW);
			if ($id <= 0){
				$mform->addRule('password', get_string('passwordrequired', 'local_users'), 'required', null, 'client');
			}
			$mform->addElement('text', 'firstname', get_string('firstname', 'local_users'));
	        $mform->addRule('firstname', get_string('errorfirstname', 'local_users'), 'required', null, 'client');
	        $mform->setType('firstname', PARAM_RAW);

	        $mform->addElement('text', 'lastname', get_string('lastname', 'local_users'));
	        $mform->addRule('lastname', get_string('errorlastname', 'local_users'), 'required', null, 'client');
	        $mform->setType('lastname', PARAM_RAW);

	        $mform->addElement('text', 'email', get_string('email', 'local_users'));
	        $mform->addRule('email', get_string('erroremail','local_users'), 'required', null, 'client');
	        $mform->addRule('email', get_string('emailexists', 'local_users'), 'email', null, 'client');
	        $mform->setType('email', PARAM_RAW);
	        // if($employee == 2){
	        	$mform->addElement('text', 'open_employeeid', get_string('serviceid', 'local_users'));
	        	$mform->addRule('open_employeeid', get_string('prnnumberrequired', 'local_users'), 'required', null, 'client');
	        	// $mform->addRule('open_employeeid',  get_string('employeeidrequired','local_users'),  'required',  '',  'client');
	        	// $mform->addRule('open_employeeid',  get_string('open_employeeiderror','local_users'),  'alphanumeric',  'extraruledata',  'client');
		        $mform->setType('open_employeeid', PARAM_RAW);
		    // }
		    // Removed supervisor field by Harish starts here//
	        /*if(!is_siteadmin() && has_capability('local/costcenter:manage_ownorganization', $systemcontext)){
				$reporting= userlib::find_supervisor_list($USER->open_costcenterid,$id);
			}else if(!is_siteadmin() && has_capability('local/costcenter:manage_owndepartments', $systemcontext)){
				$reporting = userlib::find_dept_supervisor_list($USER->open_departmentid,$id);
			}else if($id>0){
				$costcenterid = $DB->get_field('user','open_costcenterid',array('id' => $id));
				$reporting= userlib::find_supervisor_list($costcenterid,$id);
			}
			$reportingmanger = array(null=>'--Select Supervisor--');
			foreach($reporting as $report){
				$reportingmanger[$report->id]=$report->username;
			}
           	$select= $mform->addElement('select', 'open_supervisorid', get_string('supervisor','local_users'), $reportingmanger,array('id'=>'open_supervisorid'));
	        $mform->setType('open_supervisorid', PARAM_RAW);*/
	        // Removed supervisor field by Harish ends here//
		}//end of if($form_status = 0) condition.
		// else if($form_status ==1){
	  // 		$userrecord = $DB->get_record('user',array('id'=>$id));
			// $subdepartmentlist =array(null=>'--Select Sub Department--');
			// if(!empty($userrecord->open_departmentid)){
			// 	$subdepartments = userlib::find_subdepartments_list($userrecord->open_departmentid);
			// 	foreach($subdepartments as $subdepartment){
			// 		$subdepartmentlist[$subdepartment->id] = $subdepartment->fullname;
			// 	}
			// }
			// $mform->addElement('select', 'open_subdepartment', get_string('subdepartment','local_users'), $subdepartmentlist);
			// $mform->addHelpButton('subdepartment', 'subdepartment','local_users');

			// $mform->addElement('select', 'lang', get_string('preferredlanguage', 'local_users'), get_string_manager()->get_list_of_translations());
	  //       $mform->setDefault('lang', $CFG->lang);

	  //       $mform->addElement('text', 'open_designation', get_string('designation', 'local_users'));
	  //       $mform->setType('open_designation', PARAM_RAW);


	  //       $mform->addElement('text', 'open_client', get_string('client', 'local_users'));
	  //       $mform->setType('open_client', PARAM_RAW);

	  //       $mform->addElement('text', 'open_team', get_string('team', 'local_users'));
	  //       $mform->setType('open_team', PARAM_RAW);

	  //       $mform->addElement('text', 'open_grade', get_string('grade', 'local_users'));
	  //       $mform->setType('open_group', PARAM_RAW);

	  //       $mform->addElement('text', 'open_hrmsrole', get_string('open_role', 'local_users'));
	  //       $mform->setType('open_group', PARAM_RAW);

	  //       $mform->addElement('text', 'open_zone', get_string('open_zone', 'local_users'));
	  //       $mform->setType('open_group', PARAM_RAW);

	  //       $mform->addElement('text', 'open_region', get_string('open_region', 'local_users'));
	  //       $mform->setType('open_group', PARAM_RAW);


	  //       $mform->addElement('text', 'open_branch', get_string('open_branch', 'local_users'));
	  //       $mform->setType('open_group', PARAM_RAW);

		// }//end of if($form_status = 1) condition.
		else if ($form_status == 1){
			$mform->addElement('text', 'city', get_string('open_location','local_users'));
	        $mform->setType('city', PARAM_RAW);
			$mform->addElement('text', 'open_state', get_string('state','local_users'));
	        $mform->setType('open_state', PARAM_RAW);

	        $mform->addElement('text', 'phone1', get_string('contactno', 'local_users'));
	        $mform->addRule('phone1', get_string('numeric','local_users'), 'numeric', null, 'client');
	        $mform->addRule('phone1', get_string('phoneminimum', 'local_users'), 'minlength', 10, 'client');
	        $mform->addRule('phone1', get_string('phonemaximum', 'local_users'), 'maxlength', 15, 'client');
	        $mform->setType('phone1', PARAM_RAW);

	        $mform->addElement('textarea', 'address', get_string('address', 'local_users'));
	        // $mform->setType('address', PARAM_RAW);

	        $mform->addElement('static', 'currentpicture', get_string('currentpicture'));
	        $mform->addElement('checkbox', 'deletepicture', get_string('delete'));
	        $mform->setDefault('deletepicture', 0);
	        $mform->addElement('filepicker', 'imagefile', get_string('newpicture'));
	        $mform->addHelpButton('imagefile', 'newpicture');
		}
		// end of form status = 2 condition
        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);
        $mform->setDefault('id',  $id);
        $mform->addElement('hidden', 'form_status');
        $mform->setType('form_status', PARAM_INT);
        $mform->setDefault('form_status',  $form_status);

        $mform->addElement('hidden', 'open_employee');
        $mform->setType('open_employee', PARAM_INT);
        $mform->setDefault('open_employee',  $employee);
        $mform->disable_form_change_checker();

    }

    public function definition_after_data() {
        global $USER, $CFG, $DB, $OUTPUT;
        $mform = & $this->_form;
        $form_status = $this->_customdata['form_status'];
        if ($userid = $mform->getElementValue('id')) {
            $user = $DB->get_record('user', array('id' => $userid));
        } else {
            $user = false;
        }
        // print picture
        if (empty($USER->newadminuser)) {
            if ($user) {
                $context = context_user::instance($user->id, MUST_EXIST);
                $fs = get_file_storage();
                $hasuploadedpicture = ($fs->file_exists($context->id, 'user', 'icon', 0, '/', 'f2.png') || $fs->file_exists($context->id, 'user', 'icon', 0, '/', 'f2.jpg'));

                if (!empty($user->picture) && $hasuploadedpicture) {
                    $imagevalue = $OUTPUT->user_picture($user, array('courseid' => SITEID, 'size' => 64,'link' => false));
                } else {
                    $imagevalue = get_string('none');
                }
            } else {
                $imagevalue = get_string('none');
            }
            if($form_status == 2){
	            $imageelement = $mform->getElement('currentpicture');
	            $imageelement->setValue($imagevalue);
			}
            if ($user && $mform->elementExists('deletepicture') && !$hasuploadedpicture) {
                $mform->removeElement('deletepicture');
            }
        }
    }

   public function validation($data, $files) {
        $errors = array();
        global $DB, $CFG;
		$sub_data=data_submitted();
		$errors = parent::validation($data, $files);
        $email = $data['email'];
        $employeeid = $data['open_employeeid'];
        $id = $data['id'];
        $uname = $data['username'];
        $password = $data['password'];
        $uniqueid = $data['open_employeeid'];
        $form_status = $data['form_status'];
        $systemcontext = context_system::instance();
        if($form_status == 0){// as these fields are in only form part 1(form_status=0)
        	$username = $data['username'];
        	$firstname = $data['firstname'];
        	$lastname = $data['lastname'];
        if(is_siteadmin() || (has_capability('local/costcenter:manage_ownorganization',$systemcontext))){	
        	if($data['open_employee'] == 1 || $data['open_employee'] == 2)
        	{
                if($data['open_univdept_status'] == 0){
	                if($data['open_departmentid'] == null){
	                	$errors['open_departmentid'] = get_string('missing_departments', 'local_users');
	                }
                }else{
	                if($data['open_collegeid'] == null){
	                	$errors['open_collegeid'] = get_string('miisingcollegeid', 'local_users');
	                }
               }
        	}
        }	
        	/* if(!empty($username)){
            $username = preg_match('/^\S*$/', $username); 
            if(!$username){
              $errors['username'] = get_string('spacesnotallowed', 'local_users');
            }

            }
        	if(empty(trim($username))){
        		$errors['username'] = get_string('valusernamerequired','local_users');
        	}*/
        	if(empty(trim($firstname))){
        		$errors['firstname'] = get_string('valfirstnamerequired','local_users');
        	}
        	if(empty(trim($lastname))){
        		$errors['lastname'] = get_string('vallastnamerequired','local_users');
        	}
        	if(!empty($password)){
                 $uppercase = preg_match('@[A-Z]@', $password);
                 $lowercase = preg_match('@[a-z]@', $password);
                 $number    = preg_match('@[0-9]@', $password);
                 $symbols   = preg_match("#\W+#", $password );
                 if(!$uppercase || !$lowercase || !$number || !$symbols || strlen($password) < 8) {
                     
                    $errors['password'] = get_string('passwordvalidation','local_users');
                 	}
                 }
            if($uniqueid){
            	$idnumber = $DB->get_field('user','idnumber',array('id' => $data['id']));
            	if($id <= 0 || $idnumber != $uniqueid){
            	$existsuniqueid = $DB->get_field('user','id',array('idnumber' => $uniqueid));
            	if($existsuniqueid){
                    $errors['open_employeeid'] = get_string('uniqueidexists','local_users');
            	}
                }
            }
	        // OL72 issue department as mandatory.
	        // $department = $data['open_departmentid'];
	        // print_object(!isset($data['open_departmentid']));
	        /*if(!isset($data['open_collegeid'])){
	        	$errors['open_collegeid'] = get_string('nodepartmenterror', 'local_users');
	        }
	        if(!isset($data['open_departmentid'])){
	        	$errors['open_departmentid'] = get_string('nodepartmenterror', 'local_users');
	        }*/
	        // OL72 ends here.
		    if ($user = $DB->get_record('user', array('email' => $data['email']), '*', IGNORE_MULTIPLE)) {
	            if (empty($data['id']) || $user->id != $data['id']) {
	                $errors['email'] = get_string('emailexists', 'local_users');
	            }
	        }
	        if(!empty($data['email'])){
		        if(!preg_match("/^([a-z0-9\+_\-]+)(\.[a-z0-9\+_\-]+)*@([a-z0-9\-]+\.)+[a-z]{2,6}$/ix", $data['email'])){
		        	 $errors['email'] = get_string('validemailexists', 'local_users');
		        }
	        }

	        // if (ctype_upper($uname)) {
	        if(strtolower($uname)!=$uname){
	        	$errors['username'] = get_string('lowercaseunamerequired', 'local_users');
	        }
	        if ($user = $DB->get_record('user', array('username' => $data['username']), '*', IGNORE_MULTIPLE)) {
	            if (empty($data['id']) || $user->id != $data['id']) {
	                $errors['username'] = get_string('unameexists', 'local_users');
	            }
	        }
	    }
	    if($form_status == 2){// as these fields are in only form part 3(form_status=2)
	    	$phone = $data['phone1'];
	    	if($phone){
	    		if(!is_numeric($phone)){
	    			$errors['phone1'] = get_string('numeric','local_users');
	    		}
		    	else if(($phone<999999999 || $phone>10000000000) && $phone){

		    		$errors['phone1'] = get_string('phonenumvalidate', 'local_users');
		    	}
		    }
	    }
        return $errors;
    }
}

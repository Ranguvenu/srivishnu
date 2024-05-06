<?php
namespace local_users\forms;

defined('MOODLE_INTERNAL') || die;
require_once($CFG->libdir . '/formslib.php');
require_once($CFG->libdir . '/completionlib.php');
require_once($CFG->dirroot . '/local/costcenter/lib.php');

use moodleform;
use context_system;
use costcenter;
use events;
use context_user;
use local_users\functions\userlibfunctions as userlib;

class profile_info extends moodleform {
    public function definition() {
        global $USER, $CFG, $DB, $PAGE;
		$systemcontext = context_system::instance();
        $costcenter = new costcenter();
        $mform = $this->_form;
        $id = $this->_customdata['id'];
        $editoroptions = $this->_customdata['editoroptions'];
        $filemanageroptions = $this->_customdata['filemanageroptions'];
        $admin = $this->_customdata['admin'];
        $random = rand(1,10);
        if($id > 0){
        	$this->_form->_attributes['id'] ='profileinfo'.$id.$random;
        }
            $profiluser = $DB->get_record('user',array('id' => $id));
            $university = $DB->get_record_sql("SELECT id, fullname FROM {local_costcenter} WHERE id = $profiluser->open_costcenterid AND visible = 1");
            $department = $DB->get_record_sql("SELECT id, fullname FROM {local_costcenter} WHERE id = $profiluser->open_departmentid AND visible = 1");

            $mform->addElement('static', 'universityname', get_string('university', 'local_boards'));
            $mform->setDefault('universityname',$university->fullname);
            $mform->addElement('hidden', 'open_costcenterid',
            get_string('university', 'local_boards'));
            $mform->setType('open_costcenterid', PARAM_INT);
            $mform->setDefault('open_costcenterid', $university->id);

            if($profiluser->open_univdept_status == 1){
                $mform->addElement('static', 'collegename', get_string('college','local_users'));
                $mform->setDefault('collegename',$department->fullname);
                
            }else{
                $mform->addElement('static', 'departmentname', get_string('departments', 'local_users'));
                $mform->setDefault('departmentname',$department->fullname);
            }

            $mform->addElement('hidden', 'open_departmentid',
                get_string('university', 'local_boards'));
            $mform->setType('open_departmentid', PARAM_INT);
            $mform->setDefault('open_departmentid', $department->id);

            $mform->addElement('text', 'username', get_string('username', 'local_users'));
            $mform->addRule('username', get_string('usernamerequired', 'local_users'), 'required', null, 'client');
            $mform->setType('username', PARAM_RAW);
            $mform->addElement('passwordunmask', 'password', get_string('password'), 'size="20"');
            $mform->addHelpButton('password', 'newpassword');
            $mform->setType('password', PARAM_RAW);

			$mform->addElement('text', 'firstname', get_string('firstname', 'local_users'));
	        $mform->addRule('firstname', get_string('errorfirstname', 'local_users'), 'required', null, 'client');
	        $mform->setType('firstname', PARAM_RAW);

	        $mform->addElement('text', 'lastname', get_string('lastname', 'local_users'));
	        $mform->addRule('lastname', get_string('errorlastname', 'local_users'), 'required', null, 'client');
	        $mform->setType('lastname', PARAM_RAW);

	        $mform->addElement('text', 'email', get_string('email', 'local_users'));
	        $mform->addRule('email', get_string('erroremail','local_users'), 'required', null, 'client');
	        $mform->addRule('email', get_string('emailerror', 'local_users'), 'email', null, 'client');
	        $mform->setType('email', PARAM_RAW);

        	$mform->addElement('text', 'open_employeeid', get_string('serviceid', 'local_users'));
        	$mform->addRule('open_employeeid', get_string('prnnumberrequired', 'local_users'), 'required', null, 'client');
	        $mform->setType('open_employeeid', PARAM_RAW);
		    
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

	        $mform->addElement('static', 'currentpicture', get_string('currentpicture'));
	        $mform->addElement('checkbox', 'deletepicture', get_string('delete'));
	        $mform->setDefault('deletepicture', 0);
	        $mform->addElement('filepicker', 'imagefile', get_string('newpicture'));
	        $mform->addHelpButton('imagefile', 'newpicture');
		
        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);
        $mform->setDefault('id',  $id);
        $mform->disable_form_change_checker();

    }

    public function definition_after_data() {
        global $USER, $CFG, $DB, $OUTPUT;
        $mform = & $this->_form;
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
            
	            $imageelement = $mform->getElement('currentpicture');
	            $imageelement->setValue($imagevalue);
			
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
        $uniqueid = $data['open_employeeid'];
        $username = $data['username'];
        $uname = $data['username'];
        $password = $data['password'];

        
    	$firstname = $data['firstname'];
    	$lastname = $data['lastname'];
        
        if(!empty($username)){
            $username = preg_match('/^\S*$/', $username); 
            if(!$username){
              $errors['username'] = get_string('spacesnotallowed', 'local_users');
            }

            }
            if(empty(trim($username))){
                $errors['username'] = get_string('valusernamerequired','local_users');
            }

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

	    if ($user = $DB->get_record('user', array('email' => $data['email']), '*', IGNORE_MULTIPLE)) {
            if (empty($data['id']) || $user->id != $data['id']) {
                $errors['email'] = get_string('emailexists', 'local_users');
            }
        }
    
        if(strtolower($uname)!=$uname){
            $errors['username'] = get_string('lowercaseunamerequired', 'local_users');
        }
        if ($user = $DB->get_record('user', array('username' => $data['username']), '*', IGNORE_MULTIPLE)) {
            if (empty($data['id']) || $user->id != $data['id']) {
                $errors['username'] = get_string('unameexists', 'local_users');
            }
        }

    	$phone = $data['phone1'];
    	if($phone){
    		if(!is_numeric($phone)){
    			$errors['phone1'] = get_string('numeric','local_users');
    		}
	    	else if(($phone<999999999 || $phone>10000000000) && $phone){

	    		$errors['phone1'] = get_string('phonenumvalidate', 'local_users');
	    	}
	    }
        return $errors;
    }
}

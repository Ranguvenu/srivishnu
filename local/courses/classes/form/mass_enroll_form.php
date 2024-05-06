<?php

namespace local_courses\form;
use core;
use moodleform;
use context_system;
require_once($CFG->dirroot . '/lib/formslib.php');
class mass_enroll_form extends moodleform {

	function definition() {
		global $CFG,$DB;
		$mform = & $this->_form;
		$course = $this->_customdata['course'];
		$context = $this->_customdata['context'];
		$type = $this->_customdata['type'];

		//for program bulkenrolments
		
		$programid = $this->_customdata['programid'];
		$curriculumid = $this->_customdata['ccid'];
        $yearid = $this->_customdata['year'];

        if(!empty($programid)){
	   		$mform->addElement('hidden', 'programid', $programid);
	        $mform->setType('programid', PARAM_INT);
	    }
        if(!empty($curriculumid)){
	        $mform->addElement('hidden', 'curriculumid', $curriculumid);
	        $mform->setType('curriculumid', PARAM_INT);
        }
        if(!empty($yearid)){
	        $mform->addElement('hidden', 'yearid', $yearid);
	        $mform->setType('yearid', PARAM_INT);
	    }
        
        //end for program bulkenrolments
        
		// the upload manager is used directly in post precessing, moodleform::save_files() is not used yet
		//$this->set_upload_manager(new upload_manager('attachment'));

		$mform->addElement('header', 'general', ''); //fill in the data depending on page params
		//later using set_data
		$mform->addElement('filepicker', 'attachment', get_string('location', 'enrol_flatfile'));

		$mform->addRule('attachment', null, 'required');
		
		if(empty($programid)){

		$choices = \csv_import_reader::get_delimiter_list();
        $mform->addElement('select', 'delimiter_name', get_string('csvdelimiter', 'tool_uploaduser'), $choices);
        if (array_key_exists('cfg', $choices)) {
            $mform->setDefault('delimiter_name', 'cfg');
        } else if (get_string('listsep', 'langconfig') == ';') {
            $mform->setDefault('delimiter_name', 'semicolon');
        } else {
            $mform->setDefault('delimiter_name', 'comma');
        }

        $choices = \core_text::get_encodings();
        $mform->addElement('select', 'encoding', get_string('encoding', 'tool_uploaduser'), $choices);
        $mform->setDefault('encoding', 'UTF-8');
	    
	  

	    $id=$DB->get_field('role','id',array('shortname'=>'student'));
		$mform->addElement('hidden', 'roleassign', $id);
        $mform->setType('roleassign', PARAM_INT);

		$ids = array (
			'email' => get_string('email', 'local_courses')
		);
		$mform->addElement('select', 'firstcolumn', get_string('firstcolumn', 'local_courses'), $ids);
		$mform->setDefault('firstcolumn', 'idnumber');

		$mform->addElement('selectyesno', 'creategroups', get_string('creategroups', 'local_courses'));
		$mform->setDefault('creategroups', 1);

		$mform->addElement('selectyesno', 'creategroupings', get_string('creategroupings', 'local_courses'));
		$mform->setDefault('creategroupings', 1);
         
        } 
		/*The code has been disbaled to stop sending auto maila and make loading issues*/
		//$mform->addElement('selectyesno', 'mailreport', get_string('mailreport', 'local_mass_enroll'));
		//$mform->setDefault('mailreport', 1);

		//-------------------------------------------------------------------------------
		// buttons
		if($type == 'course') {
			$buttonname = get_string('enroll', 'local_courses');
		} else if($type == 'onlinetest'){
			$buttonname = get_string('enroll', 'local_onlinetests');
		} else if($type == 'groups'){
			$buttonname = get_string('enroll', 'local_groups');
		} else if($type == 'program'){
			$buttonname = get_string('enroll', 'local_program');
		} else {
			$buttonname = 'Enroll';
		}
		$this->add_action_buttons(true, $buttonname);

		$mform->addElement('hidden', 'id', $course->id);
		$mform->setType('id', PARAM_INT);
	}

	function validation($data, $files) {
		$errors = parent :: validation($data, $files);
		return $errors;
	}
}
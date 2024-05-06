<?php
require_once($CFG->libdir.'/formslib.php');
class filter_form extends moodleform {

    function definition() {
        global $CFG, $DB;
	
	$systemcontext = context_system::instance();
        $mform    = $this->_form;
     //  $filterlist  = $this->_customdata['filterlist']; 
       $select_dept = array('null' => '--Select Department--');
       $dept = $DB->get_records_sql_menu('SELECT id,fullname FROM {local_costcenter} WHERE univ_dept_status = 0 AND visible = 1');

       $mform->addElement('autocomplete', 'department',get_string('department', 'local_mooccourses'),$select_dept+$dept);
      
        $buttonarray = array();
        $classarray = array('class' => 'form-submit');
        $buttonarray[] = &$mform->createElement('submit', 'saveanddisplay', get_string('apply','local_mooccourses'), $classarray);
        $buttonarray[] = &$mform->createElement('cancel', 'cancel', get_string('reset','local_mooccourses'), $classarray);
        $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);
        }
}

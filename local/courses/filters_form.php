<?php
use core_component;
if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');    ///  It must be included from a Moodle page
}

require_once($CFG->libdir.'/formslib.php');
class filters_form extends moodleform {

    function definition() {
        global $CFG;

        $mform    = $this->_form;
        $filterlist        = $this->_customdata['filterlist']; // this contains the data of this form
        $filterparams      = $this->_customdata['filterparams'];
        $action      = $this->_customdata['action'];

        $options           = $filterparams['options'];
        $dataoptions       = $filterparams['dataoptions'];
        $submitid = $this->_customdata['submitid'] ? $this->_customdata['submitid'] : 'filteringform';

        $this->_form->_attributes['id'] = $submitid;

        if(in_array("enrolid",$filterlist)){
            $enrolid           = $this->_customdata['enrolid']; // this contains the data of this form
            $mform->addElement('hidden', 'enrolid', $enrolid);
            $mform->setType('enrolid', PARAM_INT);
        }
        if(in_array("courseid",$filterlist)){
            $courseid          = $this->_customdata['courseid']; // this contains the data of this form
            $mform->addElement('hidden', 'id', $courseid);
            $mform->setType('id', PARAM_INT);
        }

        $mform->addElement('hidden', 'options', $options);
        $mform->setType('options', PARAM_RAW);

        $mform->addElement('hidden', 'dataoptions', $dataoptions);
        $mform->setType('dataoptions', PARAM_RAW);

       
       
        foreach ($filterlist as $key => $value) {
            if($value === 'categories' || $value === 'elearning'){
                $filter = 'courses';
            } else if($value === 'email' || $value === 'employeeid' || $value === 'username' || $value === 'users' || $value === 'role'){
                $filter = 'users';
            } else if($value === 'organizations' || $value === 'departments'){
                $filter = 'costcenter';
            } else if($value === 'sorting'){
                $filter = 'request';
            } else if($value === 'costcenter'){
                $filter = 'curriculum';
            } else if($value === 'faculties'){
                $filter = 'faculties';
            } else if($value === 'subcollege'){
                $filter = 'costcenter';
            } else if($value === 'subdepartment'){
                $filter = 'costcenter';
            }else if($value === 'department'){
                //departments under costcenter
                $filter = 'costcenter';
            }
            else if($value === 'programname' || $value === 'programshortcode' || $value === 'programshortname' || $value === 'programyear'|| $value === 'programduration' || $value === 'programadmission'  ||$value === 'programvaliddates' ||$value === 'programlevel'||$value === 'programfaculty' ||$value === 'programorganizations'||$value === 'programdepartments' || $value === 'programcolleges' ){
                $filter = 'program';
            } 
            else{
                $filter = $value;
            }
            $core_component = new \core_component();
            $courses_plugin_exist = $core_component::get_plugin_directory('local', $filter);
            if ($courses_plugin_exist) {
                require_once($CFG->dirroot . '/local/' . $filter . '/lib.php');
                $functionname = $value.'_filter';
                $functionname($mform);
            }
        }
        // When two elements we need a group.
        // $buttonarray = array();
        // $classarray = array('class' => 'form-submit');
        // $buttonarray[] = &$mform->createElement('submit', 'saveanddisplay', get_string('apply','local_courses'), $classarray);
        // $buttonarray[] = &$mform->createElement('cancel', 'cancel', get_string('reset','local_courses'), $classarray);
        // $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);
        if($action === 'user_enrolment'){
            $buttonarray = array();
            $applyclassarray = array('class' => 'form-submit');
            $buttonarray[] = &$mform->createElement('submit', 'filter_apply', get_string('apply','local_courses'), $applyclassarray);
            $buttonarray[] = &$mform->createElement('cancel', 'cancel', get_string('reset','local_courses'), $applyclassarray);
        }else{
            $buttonarray = array();
            $applyclassarray = array('class' => 'form-submit','onclick' => '(function(e){ require("local_costcenter/cardPaginate").filteringData(e,"'.$submitid.'") })(event)');
            $cancelclassarray = array('class' => 'form-submit','onclick' => '(function(e){ require("local_costcenter/cardPaginate").resetingData(e,"'.$submitid.'") })(event)');
            $buttonarray[] = &$mform->createElement('button', 'filter_apply', get_string('apply','local_courses'), $applyclassarray);
            $buttonarray[] = &$mform->createElement('button', 'cancel', get_string('reset','local_courses'), $cancelclassarray);

        }
        $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);
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
        return $errors;
    }
}
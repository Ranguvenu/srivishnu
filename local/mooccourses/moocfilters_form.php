<?php
// use core_component;
if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');    ///  It must be included from a Moodle page
}

require_once($CFG->libdir.'/formslib.php');
class moocfilters_form extends moodleform {

    function definition() {
        global $CFG;

        $mform = $this->_form;
        $filterlist = $this->_customdata['filterlist']; // this contains the data of this form
        $costcenter = $this->_customdata['costcenter'];
        $roleid = $this->_customdata['roleid'];
        $courseid = $this->_customdata['courseid'];
        if(in_array("enrolid",$filterlist)){
			$enrolid = $this->_customdata['enrolid']; // this contains the data of this form
			$mform->addElement('hidden', 'enrolid', $enrolid);
			$mform->setType('enrolid', PARAM_INT);
		}
		if(in_array("courseid",$filterlist)){
			$courseid = $this->_customdata['courseid']; // this contains the data of this form
			$mform->addElement('hidden', 'id', $courseid);
			$mform->setType('id', PARAM_INT);
		}
        if(in_array("costcenter",$filterlist)){
            $costcenter = $this->_customdata['costcenter']; // this contains the data of this form
            $mform->addElement('hidden', 'costcenter', $costcenter);
            $mform->setType('costcenter', PARAM_INT);
        }
        if(in_array("roleid",$filterlist)){
            $roleid = $this->_customdata['roleid']; // this contains the data of this form
            $mform->addElement('hidden', 'roleid', $roleid);
            $mform->setType('roleid', PARAM_INT);
        }
        if(in_array("courseid",$filterlist)){
            $course_id = $this->_customdata['courseid']; // this contains the data of this form
            $mform->addElement('hidden', 'course_id', $course_id);
            $mform->setType('id', PARAM_INT);
        }
        foreach ($filterlist as $key => $value) {
            if($value === 'email' || $value === 'employeeid' || $value === 'username' || $value === 'users' || $value === 'role' || $value === 'departmentcourseusers' || $value === 'departmentcourseusersemail'){
                $filter = 'users';
            } else if($value === 'organizations' || $value === 'departments'){
                $filter = 'costcenter';
            } /*else if($value === 'sorting'){
                $filter = 'request';
            } else if($value === 'costcenter'){
                $filter = 'curriculum';
            } else if($value === 'faculties'){
                $filter = 'faculties';
            } else if($value === 'subcollege'){
                $filter = 'costcenter';
            } */else if($value === 'subdepartment'){
                $filter = 'costcenter';
            } else if($value === 'department'){
                //departments under costcenter
                $filter = 'costcenter';
            } else{
                $filter = $value;
            }

            $core_component = new \core_component();
			$courses_plugin_exist = $core_component::get_plugin_directory('local', $filter);
			if ($courses_plugin_exist) {
				require_once($CFG->dirroot . '/local/' . $filter . '/lib.php');
				$functionname = $value.'_filter';
				if($value === 'departmentcourseusers' || $value === 'departmentcourseusersemail'){
                $functionname($mform, $query='', $searchanywhere=false, $page=0, $perpage=25, $costcenter, $roleid,$courseid);   
                }else{
                $functionname($mform, $query='', $searchanywhere=false, $page=0, $perpage=25, $costcenter, $roleid);
                }
			}
        }
        // When two elements we need a group.
        $buttonarray = array();
        $classarray = array('class' => 'form-submit');
        $buttonarray[] = &$mform->createElement('submit', 'saveanddisplay', get_string('apply','local_courses'), $classarray);
        $buttonarray[] = &$mform->createElement('cancel', 'cancel', get_string('reset','local_courses'), $classarray);
        $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);
		
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

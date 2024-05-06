<?php
class programcourses_form extends moodleform {

    public function definition() {
        global $CFG, $DB, $USER;
        $mform = &$this->_form;
        $programid = $this->_customdata['programid'];
        $curriculumid = $this->_customdata['curriculumid'];
        $yearid = $this->_customdata['yearid'];
        $semesterid = $this->_customdata['semesterid'];

        $context = context_system::instance();
        $mform->addElement('hidden', 'programid', $programid);
        $mform->setType('programid', PARAM_INT);

        $mform->addElement('hidden', 'curriculumid', $curriculumid);
        $mform->setType('curriculumid', PARAM_INT);

        $mform->addElement('hidden', 'yearid', $yearid);
        $mform->setType('yearid', PARAM_INT);

        $mform->addElement('hidden', 'semesterid', $semesterid);
        $mform->setType('semesterid', PARAM_INT);


        $universityid = $DB->get_field_sql('SELECT p.costcenter
                                              FROM {local_program} p
                                            WHERE p.id = :programid ',
                                            array('programid' => $programid));
        $departments = array();
    
        $departmentid = $DB->get_field('local_curriculum','department',array('id' => $curriculumid));
        $mform->addElement('hidden', 'department', $departmentid);
        $mform->setType('department', PARAM_INT);
      
        $courses = find_courses($departmentid,$semesterid,$yearid,$curriculumid);
        foreach($courses as $course){
            //added by yamini to display courses which have program enrolment
           $courseenrol = $DB->get_field('enrol','id',array('enrol' => 'program','courseid' => $course->id));          
            if(!empty($courseenrol)){
              $courselists[$course->id] = $course->fullname;           
            }         
        }
        $course_select = array('null' => '--Select Courses--');  
        if($courselists){
            $courselist = $course_select+$courselists; 
        }else{
            $courselist = $course_select;
        }
        $mform->addElement('select', 'course', get_string('course', 'local_curriculum'),$courselist);
        $mform->addRule('course', get_string('missingcourse', 'local_curriculum'), 'required', null, 'client');

        $coursetype = array(null => get_string('selectcoursetype', 'local_curriculum'), '0' => 'Optional', '1' => 'Mandatory');

        $mform->addElement('select', 'coursetype', get_string('coursetype', 'local_curriculum'), $coursetype);
        $mform->addRule('coursetype', get_string('missingcoursetype', 'local_curriculum'), 'required', null, 'client');
        $mform->disable_form_change_checker();
    }
    public function validation($data){
        $errors = array();
      //  print_object($data);
        if($data['course'] == 'null'){
            $errors['course'] = get_string('missingcourse', 'local_curriculum');
        }
       /* if(empty($data['coursetype'])){
            $errors['coursetype'] = get_string('missingcoursetype', 'local_curriculum');
        }*/
        return $errors;
    }
}

<?php
namespace local_mooccourses\form;
use core;
use moodleform;
use context_system;
use core_component;

require_once($CFG->dirroot . '/lib/formslib.php');
class managestudents_form extends moodleform {

    public function definition() {
        global $CFG, $DB, $USER;
      //  $querieslib = new querylib();
        $mform = &$this->_form;
        $programid = $this->_customdata['programid'];
        $curriculumid = $this->_customdata['curriculumid'];
        $department = $this->_customdata['department'];
        $yearid = $this->_customdata['yearid'];

        $context = context_system::instance();

     /*   $mform->addElement('hidden', 'programid', $programid);
        $mform->setType('programid', PARAM_INT);

        $mform->addElement('hidden', 'curriculumid', $curriculumid);
        $mform->setType('curriculumid', PARAM_INT);

        $mform->addElement('hidden', 'yearid', $yearid);
        $mform->setType('yearid', PARAM_INT);*/

        $students = array();
        $students = array('null' => 'Select Students');
      /*  $student = $this->_ajaxformdata['students'];
        if (!empty($student)) {
            $student = implode(',', $student);
            $studentssql = "SELECT u.id, CONCAT(u.firstname, ' ', u.lastname) AS fullname
                             FROM {user} AS u
                            WHERE u.id IN ($student) AND u.id > 2 AND u.confirmed = 1";
            $students = $DB->get_records_sql_menu($studentssql);
        }
        $options = array(
            'ajax' => 'local_curriculum/form-options-selector',
            'multiple' => true,
            'data-action' => 'program_course_student_selector',
            'data-contextid' => $context->id,
            'data-options' => json_encode(array('programid' => $programid, 'curriculumid' => $curriculumid, 'yearid' => $yearid))
        );*/
        $roleid = $DB->get_field('role','id',array('shortname' => 'student'));
        $usersdata = $DB->get_records_sql_menu("SELECT id,CONCAT(firstname, ' ', lastname) AS fullname FROM {user} WHERE open_role = ".$roleid);
        $select = $mform->addElement('autocomplete', 'students', get_string('students', 'local_program'), $usersdata);
        $mform->addRule('students', get_string('missingstudent', 'local_mooccourses'), 'required', null, 'client');
        $select->setMultiple(true);
        $mform->disable_form_change_checker();
    }

    public function validation($data, $files) {
        global $DB, $CFG;
        $errors = array();
        $errors = parent::validation($data, $files); 
        if ($data['students'] == 'null' || $data['students'] == 0) {
            $errors['students'] = get_string('missingstudent','local_mooccourses');
        }
        return $errors;
    }

}

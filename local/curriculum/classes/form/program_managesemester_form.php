<?php
class program_managesemester_form extends moodleform {

    public function definition() {
        global $CFG, $DB, $USER;
      //  $querieslib = new querylib();
        $mform = &$this->_form;
        $id = $this->_customdata['id'];
        $programid = $this->_customdata['programid'];
        $curriculumid = $this->_customdata['curriculumid'];
        $yearid = $this->_customdata['yearid'];
        $context = context_system::instance();

        //$mform->addElement('header', 'general', get_string('addcourses', 'local_program'));

        $mform->addElement('hidden', 'id', $id);
        $mform->setType('id', PARAM_INT);

        $mform->addElement('hidden', 'programid', $programid);
        $mform->setType('programid', PARAM_INT);

        $mform->addElement('hidden', 'curriculumid', $curriculumid);
        $mform->setType('curriculumid', PARAM_INT);

        $mform->addElement('hidden', 'yearid', $yearid);
        $mform->setType('yearid', PARAM_INT);

        $mform->addElement('text', 'semester', get_string('semester', 'local_curriculum'));
        $mform->addRule('semester', null, 'required', null, 'client');

        $mform->addElement('editor', 'semester_description', get_string('description', 'local_curriculum'), null, array('autosave' => false));
        $mform->setType('semester_description', PARAM_RAW);
        // $mform->addRule('description', null, 'required', null, 'client');

        $mform->disable_form_change_checker();
    }
}
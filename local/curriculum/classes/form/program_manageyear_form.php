<?php
class program_manageyear_form extends moodleform {

    public function definition() {
        global $CFG, $DB, $USER;
      //  $querieslib = new querylib();
        $mform = &$this->_form;
        $id = $this->_customdata['id'];
        $programid = $this->_customdata['programid'];
        $curriculumid = $this->_customdata['curriculumid'];
        $context = context_system::instance();

        //$mform->addElement('header', 'general', get_string('addcourses', 'local_program'));

        $mform->addElement('hidden', 'id', $id);
        $mform->setType('id', PARAM_INT);

        $mform->addElement('hidden', 'curriculumid', $curriculumid);
        $mform->setType('curriculumid', PARAM_INT);

        $mform->addElement('hidden', 'programid', $programid);
        $mform->setType('programid', PARAM_INT);

        $mform->addElement('text', 'year', get_string('year', 'local_curriculum'));
        $mform->addRule('year', null, 'required', null, 'client');
        $mform->setType('year', PARAM_NOTAGS);

        $mform->addElement('text', 'cost', get_string('cost', 'local_curriculum'));
        $mform->addRule('cost', null, 'required', null, 'client');
        $mform->addRule('cost', null, 'numeric', null, 'client');
        $mform->addRule('cost', null, 'nonzero', null, 'client');
        $mform->setType('cost', PARAM_FLOAT);

        $mform->disable_form_change_checker();
    }

    public function validation($data, $files) {
        $errors = array();
        global $DB, $CFG;
        $errors = parent::validation($data, $files);

        $cost = $data['cost'];

        if(!is_numeric($cost)){
            $errors['cost'] = get_string('costshouldinteger', 'local_curriculum');
        } else if($cost <= 0){
            $errors['cost'] = get_string('costshouldpositive', 'local_curriculum');
        }

        return $errors;
    }
}


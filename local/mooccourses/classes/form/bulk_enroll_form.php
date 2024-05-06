<?php
		defined('MOODLE_INTERNAL') || die();

require_once $CFG->libdir . '/formslib.php';

//use moodleform;
//use csv_import_reader;
//use core_text;

class bulk_enrollform extends moodleform{


	function definition() {
		
		$mform = $this->_form;
        
        $config = get_config('local_mooccourses');
//Revathi Issue ODl-833 removing upload string starts
		//$mform->addElement('header', 'settingsheader', get_string('upload'));
//Revathi Issue ODl-833 removing upload string ends
		$mform->addElement('filepicker', 'userfile', get_string('file'));
		$mform->addRule('userfile', null, 'required');
		
		$mform->addElement('hidden',  'delimiter_name');
		$mform->setType('delimiter_name', PARAM_TEXT);
		$mform->setDefault('delimiter_name',  'comma');


		$mform->addElement('hidden',  'encoding');
		$mform->setType('encoding', PARAM_RAW);
		$mform->setDefault('encoding',  'UTF-8');
		
        
		$this->add_action_buttons(true, get_string('Enrol','local_mooccourses'));
        


	}


    /**
     * Form data validation
     *
     * @param \stdClass $data
     * @param array $files
     * @return array
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);
        return $errors;
    }

}
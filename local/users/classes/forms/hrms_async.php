<?php
namespace local_users\forms;
		defined('MOODLE_INTERNAL') || die();

require_once $CFG->libdir . '/formslib.php';

use moodleform;
use csv_import_reader;
use core_text;
define('ONLY_ADD', 1);
define('ONLY_UPDATE', 2);
define('ADD_UPDATE', 3);
class hrms_async extends moodleform{


	function definition() {
		$mform = $this->_form;

		//$mform->addElement('header', 'settingsheader', get_string('upload'));

		$mform->addElement('filepicker', 'userfile', get_string('file'));
		$mform->addRule('userfile', null, 'required');

		// $choices = csv_import_reader::get_delimiter_list();
		// $mform->addElement('select', 'delimiter_name', get_string('csvdelimiter', 'local_users'), $choices);
		// if (array_key_exists('cfg', $choices)) {
		// 	$mform->setDefault('delimiter_name', 'cfg');
		// } else if (get_string('listsep', 'langconfig') == ';') {
		// 	$mform->setDefault('delimiter_name', 'semicolon');
		// } else {
		// 	$mform->setDefault('delimiter_name', 'comma');
		// }

		// $choices = core_text::get_encodings();
		// $mform->addElement('select', 'encoding', get_string('encoding', 'local_users'), $choices);
		// $mform->setDefault('encoding', 'UTF-8');

		
		$mform->addElement('hidden',  'delimiter_name');
		$mform->setType('delimiter_name', PARAM_TEXT);
		$mform->setDefault('delimiter_name',  'comma');


		$mform->addElement('hidden',  'encoding');
		$mform->setType('encoding', PARAM_RAW);
		$mform->setDefault('encoding',  'UTF-8');

		//$choices = array('10' => 10, '20' => 20, '100' => 100, '1000' => 1000, '100000' => 100000);
		//$mform->addElement('select', 'previewrows', get_string('rowpreviewnum', 'local_users'), $choices);
		//$mform->setType('previewrows', PARAM_INT);

// <sandeep> - SRIVSPT-4 Removed Authentication field under Manage Users -> Bulk Upload screen -- Starts
        // $enrollmentmethod = array(null=>'---Select---',2 =>'oauth2',1 =>'Manual');
		// $mform->addElement('select', 'enrollmentmethod', get_string('authenticationmethods', 'local_users'), $enrollmentmethod);
        // $mform->addRule('enrollmentmethod', null, 'required', null, 'client');
		//$mform->setType('enrollmentmethod', PARAM_INT);
// <sandeep> - SRIVSPT-4 Removed Authentication field under Manage Users -> Bulk Upload screen -- Ends
        
        $options = array(null=>'---Select---',ONLY_ADD=>'Only Add', ONLY_UPDATE=>'Only Update', ADD_UPDATE=>'Both Add and Update');
		$mform->addElement('select', 'option', get_string('options', 'local_users'), $options);
        $mform->addRule('option', null, 'required', null, 'client');
		$mform->setType('option', PARAM_INT);

		$this->add_action_buttons(true, get_string('upload'));
	}

}

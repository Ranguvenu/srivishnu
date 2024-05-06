<?php
namespace local_boards\form;
use core;
use moodleform;
use context_system;

require_once($CFG->libdir.'/formslib.php');
class filters_form extends moodleform {

    function definition() {
        global $CFG, $DB;
	
	$systemcontext = context_system::instance();
        $mform    = $this->_form;
        $filterlist  = $this->_customdata['filterlist']; // this contains the data of this form
        if (is_siteadmin($USER->id) || has_capability('local/costcenter:manage_multiorganizations', $systemcontext)) {
                $universities = $DB->get_records_sql_menu("SELECT id, fullname FROM {local_costcenter} WHERE parentid = 0");
                $select = $mform->addElement('autocomplete', 'university',get_string('university','local_boards'),array(null=>get_string('selectuniversity','local_boards')) + $universities, array('placeholder' => get_string('university', 'local_boards')));
                // $mform->addRule('university', null, 'required', null, 'client');
                $select->setMultiple(true);
        }
        foreach ($filterlist as $key => $value) {
            if($value === 'university'){
                $filter = 'university';
                $core_component = new \core_component();
                $courses_plugin_exist = $core_component::get_plugin_directory('local', $filter);
                if ($courses_plugin_exist) {
                    require_once($CFG->dirroot . '/local/' . $filter . '/lib.php');
                    $functionname = $value.'_filter';
                    $functionname($mform);
                }
            }
            if($value === 'boards'){
                $filter = 'boards';
                $core_component = new \core_component();
                $courses_plugin_exist = $core_component::get_plugin_directory('local', $filter);
                if ($courses_plugin_exist) {
                    require_once($CFG->dirroot . '/local/' . $filter . '/lib.php');
                    $functionname = $value.'_filter';
                    $functionname($mform);
                }
            }
        }
        $buttonarray = array();
        $classarray = array('class' => 'form-submit');
        $buttonarray[] = &$mform->createElement('submit', 'saveanddisplay', get_string('apply','local_boards'), $classarray);
        $buttonarray[] = &$mform->createElement('cancel', 'cancel', get_string('reset','local_boards'), $classarray);
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

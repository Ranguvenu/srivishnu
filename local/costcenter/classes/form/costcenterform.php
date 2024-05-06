<?php
namespace local_costcenter\form;
use core;
use moodleform;
use context_system;
use core_component;

require_once($CFG->dirroot . '/lib/formslib.php');
require_once($CFG->dirroot . '/local/costcenter/lib.php');
class costcenterform extends moodleform { /*costcenter creation form*/

    public function definition() {
        global $USER, $CFG,$DB;
        $costcenter = new \costcenter();
        $corecomponent = new \core_component();

        $mform = $this->_form;
        $id = $this->_customdata['id'];
        $costcenters = $this->_customdata['tool'];
        $editoroptions = $this->_customdata['editoroptions'];
        $depts = $this->_customdata['dept'];
        $subdept = $this->_customdata['subdept']; 
        $parentid = $this->_customdata['parentid'];
        $cids = $this->_customdata['cid'];
        $child = $this->_customdata['child'];
        $radio_status = $this->_customdata['univ_dept_status']; 
        $tools = array();
                
        /*sree*/
        $systemcontext = context_system::instance();
        
        $mform->addElement('text', 'fullname', get_string('costcentername', 'local_costcenter'), $tools);
        $mform->setType('fullname', PARAM_TEXT);
        $mform->addRule('fullname', get_string('missingcostcentername', 'local_costcenter'), 'required', null, 'client');
        
        $mform->addElement('text', 'shortname', get_string('shortname','local_costcenter'), 'maxlength="100" size="20"');
        $mform->addRule('shortname', get_string('shortnamecannotbeempty', 'local_costcenter'), 'required', null, 'client');
            
        $mform->setType('shortname', PARAM_TEXT);
        $attributes = array('rows' => '8', 'cols' => '40');

        $mform->addElement('hidden', 'id', $id);
        $mform->setType('id', PARAM_INT);

        $mform->addElement('hidden', 'parentid', 0);
        $mform->setType('parentid', PARAM_INT);

        
        $now = date("d-m-Y");
        $now = strtotime($now);
        $mform->addElement('hidden', 'timecreated', $now);
        $mform->setType('timecreated', PARAM_RAW);
        
        $mform->addElement('hidden', 'usermodified', $USER->id);
        $mform->setType('usermodified', PARAM_RAW);

        $logoupload = array('maxbytes'       => $CFG->maxbytes,
                              'subdirs'        => 0,                             
                              'maxfiles'       => 1,                             
                              'accepted_types' => 'web_image');
        $mform->addElement('filemanager', 'costcenter_logo', get_string('costcenter_logo', 'local_costcenter'), '', $logoupload);

        $submit = ($id > 0) ? get_string('update_costcenter', 'local_costcenter') : get_string('create', 'local_costcenter');
       // $this->add_action_buttons('false', $submit);
    }

    /**
     * validates costcenter name and returns instance of this object
     *
     * @param [object] $files 
     * @param [object] $data 
     * @return costcenter validation errors
     */
     public function validation($data, $files) {
        global $COURSE, $DB, $CFG;
        $errors = parent::validation($data, $files);
        
        $shortname = $data['shortname'];
        if(!empty($shortname)){
           $shortname = preg_match('/^\S*$/', $shortname); 
           if(!$shortname){
            $errors['shortname'] = get_string('spacesnotallowed', 'local_costcenter');
           }

        }
        // fix for OL01 issue by mahesh
        if(empty(trim($data['shortname']))){
            $errors['shortname'] = get_string('shortnamecannotbeempty', 'local_costcenter');
        }
        if(empty(trim($data['fullname']))){
            $errors['fullname'] = get_string('fullnamecannotbeempty', 'local_costcenter');
        }
        // OL01 fix ends.
        if ($DB->record_exists('local_costcenter', array('shortname' => trim($data['shortname'])), '*', IGNORE_MULTIPLE)) {
            $costcenter = $DB->get_record('local_costcenter', array('shortname' => trim($data['shortname'])), '*', IGNORE_MULTIPLE);
            if (empty($data['id']) || $costcenter->id != $data['id']) {
                $errors['shortname'] = get_string('shortnametakenlp', 'local_costcenter', $costcenter->shortname);
            }
        }
        return $errors;
     }
     
}

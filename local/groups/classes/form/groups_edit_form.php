<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * @package    local_groups
 * @copyright  2018 sreenivas
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/lib/formslib.php');
require_once($CFG->dirroot . '/local/costcenter/lib.php');
use local_users\functions\userlibfunctions as userlib;
class group_edit_form extends moodleform {

    /**
     * Define the group edit form
    */
    public function definition() {
        global $DB, $USER;
        $mform = $this->_form;
        $editoroptions = $this->_customdata['editoroptions'];
        $cohort = $this->_customdata['data'];
        
        $context = context_system::instance();
        $departmentslist = array(null=>get_string('select_departments','local_groups'));
        if (is_siteadmin($USER->id) && has_capability('local/costcenter:manage_multiorganizations',$context)) {
            $sql="select id,fullname from {local_costcenter} where visible =1 AND parentid = 0";
            $costcenters = $DB->get_records_sql($sql);
            $organizationlist=array(null=>'--Select University--');
            foreach ($costcenters as $scl) {
                $organizationlist[$scl->id]=$scl->fullname;
            }
            $mform->addElement('select', 'costcenterid', get_string('organization', 'local_users'), $organizationlist);
            $mform->addRule('costcenterid', get_string('missingcostcenter', 'local_groups'), 'required', null, 'client');
           // $mform->setType('costcenterid', PARAM_INT);
        } elseif (has_capability('local/costcenter:manage_ownorganization',$context)){
            $user_dept = $DB->get_field('user','open_costcenterid', array('id'=>$USER->id));
            $mform->addElement('hidden', 'costcenterid', null);
            $mform->setConstant('costcenterid', $user_dept);
            $universities_sql = "SELECT id, fullname FROM {local_costcenter} WHERE id = $user_dept";
            $universityname = $DB->get_record_sql($universities_sql);
            $mform->addElement('static', 'universityname', get_string('university', 'local_boards'));
            $mform->setDefault('universityname',$universityname->fullname);
            $sql="select id,fullname from {local_costcenter} where visible =1 AND parentid = $user_dept and univ_dept_status = 0";
            $departmentslists = $DB->get_records_sql_menu($sql);
//            if(isset($departmentslists)&&!empty($departmentslists))
//            $departmentslist = $departmentslist+$departmentslists;
        } else {
            $user_dept = $DB->get_field('user','open_costcenterid', array('id'=>$USER->id));
            $mform->addElement('hidden', 'costcenterid', null);
            $mform->setType('costcenterid', PARAM_INT);
            $mform->setConstant('costcenterid', $user_dept);
            
            $mform->addElement('hidden', 'departmentid');
            $mform->setType('departmentid', PARAM_INT);
            $mform->setConstant('departmentid', $USER->open_departmentid);
            
        }
            
        if (is_siteadmin($USER->id) || has_capability('local/costcenter:manage_multiorganizations',$context) ||
            has_capability('local/costcenter:manage_ownorganization',$context)) {
            if($cohort->id > 0){
                $open_costcenterid = $cohort->costcenterid;
               // $open_costcenterid = $DB->get_field('local_groups','costcenterid',array('cohortid'=>$cohort->id));
            } else {
                $open_costcenterid = $this->_ajaxformdata['costcenterid'];
            }
            if(!empty($open_costcenterid)) {
              
                $departments = userlib::find_departments($open_costcenterid);
                foreach($departments as $depart){
//                    $departmentslist[$depart->id]=$depart->fullname;
                }
            }
// <mallikarjun> - ODL-801 added college in groups -- starts
//            $departmentselect = $mform->addElement('select', 'departmentid', get_string('department'),$departmentslist);
//            $mform->setType('departmentid', PARAM_RAW);
//            $mform->addRule('departmentid', get_string('missing_department', 'local_groups'), 'required', null, 'client');
            
		        	$attributes = array('1' => 'university departments','2' => 'Non university departments');
		        	/*$typestring = 'Select any one<span class="text-nowrap">
            <abbr class="initialism text-danger field_required" title="Required"><img class="icon " alt="Required" title="Required" src="http://localhost/odllmstest/theme/image.php/epsilon/core/1586516269/new_req"></abbr></span>';*/
		        	$radioarray=array();
				        if($cohort->id > 0){
				            $radioarray[] = $mform->createElement('radio', 'open_univdept_status', '', get_string('univ_depart','local_costcenter'), 0, $attributes);
				            $radioarray[] = $mform->createElement('radio', 'open_univdept_status', '', get_string('non_univ_depart','local_costcenter'), 1, $attributes);
				           	$mform->addGroup($radioarray, 'univdept_statusclass', get_string('customreq_selectdeptcoll', 'local_users'), array('class' => 'univdept_statusclass'), false);
				        }else{
					        $radioarray[] = $mform->createElement('radio', 'open_univdept_status', '', get_string('univ_depart','local_costcenter'), 0, $attributes);
					        $radioarray[] = $mform->createElement('radio', 'open_univdept_status', '', get_string('non_univ_depart','local_costcenter'), 1 , $attributes);
					        $mform->addGroup($radioarray, 'univdept_statusclass', get_string('customreq_selectdeptcoll', 'local_users') , array('class' => 'univdept_statusclass'), false);
				        }

				    // Fetching college list mapped under university starts here //
			  		$departmentslist = array(null => '--Select College--');
			  		if($cohort->id > 0){
			  			$existing_costcenter = $DB->get_field('local_groups', 'costcenterid',array('id' => $cohort->id));
			  		}
			  		if($cohort->id > 0 && $existing_costcenter && !isset($this->_ajaxformdata['costcenterid'])){
			  			$open_costcenterid = $existing_costcenter;
					} else{
			  			$open_costcenterid = $this->_ajaxformdata['costcenterid'];
					}
					if(!empty($open_costcenterid) && is_siteadmin()){
						$departments = userlib::find_departments_list($open_costcenterid);
						foreach($departments['nonuniv_dep'] as $depart){
							$departmentslist[$depart->id]=$depart->fullname;
						}
					}else if(!is_siteadmin() && has_capability('local/costcenter:view', $context)){
						$departments = userlib::find_departments_list($USER->open_costcenterid);
						foreach($departments['nonuniv_dep'] as $depart){
							$departmentslist[$depart->id]=$depart->fullname;
						}
					}
					// print_object($departmentslist);
					$mform->addElement('select', 'open_collegeid', get_string('collegelabel','local_users'),$departmentslist, array('class' => 'college_univ'));
			        /*$mform->addRule('open_collegeid', get_string('miisingcollegeid','local_users'), 'required', null, 'client');*/// Commented by Harish
			        $mform->hideIf('open_collegeid', 'open_univdept_status', 'eq', 0);
                    if($cohort->id > 0){
                       $currid = $DB->get_record('local_groups',array('id' => $cohort->id));
                       $mform->setDefault('open_collegeid',$currid->departmentid);
                    }
			        // Fetching college list mapped under university ends here //

			        // Fetching departments list mapped under university starts here //
			        $departmentslist = array(null => '--Select Department--');
			  		if($cohort->id > 0){
			  			$existing_costcenter = $DB->get_field('local_groups', 'costcenterid',array('id' => $cohort->id));
			  		}
			  		if($cohort->id > 0 && $existing_costcenter && !isset($this->_ajaxformdata['costcenterid'])){
			  			$open_costcenterid = $existing_costcenter;
					} else{
			  			$open_costcenterid = $this->_ajaxformdata['costcenterid'];
					}
					if(!empty($open_costcenterid) && is_siteadmin()){
						$departments = userlib::find_departments_list($open_costcenterid);
						foreach($departments['univ_dep'] as $depart){
							$departmentslist[$depart->id]=$depart->fullname;
						}
					}else if(!is_siteadmin() && has_capability('local/costcenter:view', $context)){
						$departments = userlib::find_departments_list($USER->open_costcenterid);
						foreach($departments['univ_dep'] as $depart){
							$departmentslist[$depart->id]=$depart->fullname;
						}
					}
					$mform->addElement('select', 'departmentid', get_string('departments','local_users'),$departmentslist, array('class' => 'department_univ'));
			        /*$mform->addRule('open_departmentid', get_string('missing_departments','local_users'), 'required', null, 'client');*/// Commented by Harish
			        $mform->hideIf('departmentid', 'open_univdept_status', 'eq', 1);
                    if($cohort->id > 0){
                       $currid = $DB->get_record('local_groups',array('id' => $cohort->id));
                       $mform->setDefault('departmentid',$currid->departmentid);
                    }
// <mallikarjun> - ODL-801 added college in groups -- ends
        }

        $mform->addElement('text', 'name', get_string('name', 'local_groups'), 'maxlength="254" size="50"');
        $mform->addRule('name', get_string('missingname','local_groups'), 'required', null, 'client');
        $mform->setType('name', PARAM_TEXT);
        
        $mform->addElement('hidden', 'contextid', $context->id);
        $mform->setType('contextid', PARAM_INT);
        $mform->setConstant('contextid', $context->id);

        $mform->addElement('text', 'idnumber', get_string('idnumber', 'local_groups'), 'maxlength="254" size="50"');
        $mform->addRule('idnumber', get_string('missingid','local_groups'), 'required', null, 'client');
        $mform->setType('idnumber', PARAM_RAW); // Idnumbers are plain text, must not be changed.

        /*$mform->addElement('advcheckbox', 'visible', get_string('visible', 'local_groups'));
        $mform->setDefault('visible', 1);
        $mform->addHelpButton('visible', 'visible', 'cohort');*/

        $mform->addElement('editor', 'description_editor', get_string('description', 'local_groups'), null, $editoroptions);
        $mform->setType('description_editor', PARAM_RAW);

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        if (isset($this->_customdata['returnurl'])) {
            $mform->addElement('hidden', 'returnurl', $this->_customdata['returnurl']->out_as_local_url());
            $mform->setType('returnurl', PARAM_LOCALURL);
        }

       // $this->add_action_buttons();

        $this->set_data($cohort);
    }

    public function validation($data, $files) {
        global $DB;

        $errors = parent::validation($data, $files);

        $idnumber = trim($data['idnumber']);
        if(!empty($idnumber)){
           $idnumber = preg_match('/^\S*$/', $idnumber); 
           if(!$idnumber){
            $errors['idnumber'] = get_string('spacesnotallowed', 'local_costcenter');
           }

        }
        if ($idnumber === '') {
            // Fine, empty is ok.

        } else if ($data['id']) {
            $current = $DB->get_record('cohort', array('id'=>$data['id']), '*', MUST_EXIST);
            if ($current->idnumber !== $data['idnumber']) {
                if ($DB->record_exists('cohort', array('idnumber'=>$data['idnumber']))) {
                    $errors['idnumber'] = get_string('duplicateidnumber', 'local_groups');
                }
            }

        } else {




            if ($DB->record_exists('cohort', array('idnumber'=>$data['idnumber']))) {
                $errors['idnumber'] = get_string('duplicateidnumber', 'local_groups');
            }
        }
// <mallikarjun> - ODL-801 added college in groups -- starts
        if($data['open_univdept_status'] == 0){
                if($data['departmentid'] == ''){
                        $errors['departmentid'] = get_string('missing_departments', 'local_users');
                }
        }else{
                if($data['open_collegeid'] == ''){
                        $errors['open_collegeid'] = get_string('miisingcollegeid', 'local_users');
                }
       }
//        if($data['departmentid'] == ''){
//             $errors['departmentid'] = get_string('missing_department', 'local_groups');
//        }
         if($data['costcenterid'] == ''){
             $errors['costcenterid'] = get_string('missingcostcenter', 'local_groups');
        }

        return $errors;
    }

    protected function get_category_options($currentcontextid) {
        global $CFG;
        require_once($CFG->libdir. '/coursecatlib.php');
        $displaylist = coursecat::make_categories_list('moodle/cohort:manage');
        $options = array();
        $syscontext = context_system::instance();
        if (has_capability('moodle/cohort:manage', $syscontext)) {
            $options[$syscontext->id] = $syscontext->get_context_name();
        }
        foreach ($displaylist as $cid=>$name) {
            $context = context_coursecat::instance($cid);
            $options[$context->id] = $name;
        }
        // Always add current - this is not likely, but if the logic gets changed it might be a problem.
        if (!isset($options[$currentcontextid])) {
            $context = context::instance_by_id($currentcontextid, MUST_EXIST);
            $options[$context->id] = $syscontext->get_context_name();
        }
        return $options;
    }
}


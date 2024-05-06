<?php
namespace local_location\form;
use core;
use moodleform;
use context_system;
/*
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @package Local
 * @subpackage classroom
 */

if (!defined('MOODLE_INTERNAL')) {
	die('Direct access to this script is forbidden.'); ///  It must be included from a Moodle page
}

require_once "{$CFG->dirroot}/lib/formslib.php";

class instituteform extends moodleform {

	/**
	 * Definition of the room form
	 */
	function definition() {
		global $DB,$USER;

		$mform = &$this->_form;
		$systemcontext = context_system::instance();
		$instituteid = $this->_customdata['instituteid'];
		$costcenter = $this->_customdata['costcenter'];
		// $selected_ins_name = $DB->get_records('local_costcenter');
		$mform->setType('id', PARAM_INT);
		$instinfo=$DB->get_record("local_location_institutes",array('id'=>$instituteid));
		$mform->addElement('hidden', 'id', $instituteid);
		$mform->setType('instituteid', PARAM_INT);

		$sql = "SELECT id,fullname FROM {local_costcenter} where 1=1 AND parentid=0 AND visible = 1";

		$params = array();
		if ((has_capability('local/costcenter:manage_ownorganization', context_system::instance())) && (!is_siteadmin()) ) {
            $sql .= " AND (id = :costcenter)";
            $params['costcenter'] = $USER->open_costcenterid;
        }
       
    	$institutes = $DB->get_records_sql($sql,$params);
		$institutenames = array();
		$institutenames[null] = get_string('selectuniversity', 'local_location');
		if ($institutes) {
			foreach ($institutes as $institute) {
				$institutenames[$institute->id] = $institute->fullname;
			}
		}

		if (is_siteadmin()) {
			if($instituteid){
				$locations_countinsessions = $DB->count_records('local_cc_course_sessions', array('instituteid' => $instituteid));
			}
			if($locations_countinsessions){
				$universityname=$DB->get_field('local_costcenter','fullname',array('id'=>$instinfo->costcenter));
				$mform->addElement('static', 'universityname', get_string('department', 'local_location'));
	            $mform->setDefault('universityname',$universityname);

	            $mform->addElement('hidden', 'costcenter',
	            get_string('department', 'local_location'));
	            $mform->setType('costcenter', PARAM_INT);
	            $mform->setDefault('costcenter', $instinfo->costcenter);
			}else{
				$mform->addElement('select', 'costcenter', get_string('department', 'local_location'), $institutenames, array());
				$mform->addRule('costcenter', get_string('error_costcenter', 'local_location'), 'required', null, 'client');
			}
				
	    }else{
	    	$mform->addElement('hidden', 'costcenter',$USER->open_costcenterid);
	    }
	    if (is_siteadmin($USER->id) || has_capability('local/location:manageinstitute',$systemcontext) || has_capability('local/location:manageroom',$systemcontext)) {
            $colleges = array(null=>get_string('selectcollege','local_location'));
            if($costcenter > 0){
                $subsql = "SELECT id, fullname FROM {local_costcenter} WHERE 1=1 AND univ_dept_status IS NOT NULL AND visible = 1";
                if($costcenter){
                    $subsql .= " AND parentid = ".$costcenter."";
                }
                $colleges = $colleges+$DB->get_records_sql_menu($subsql);
            }elseif($this->_ajaxformdata['costcenter']){
            	$subsql = "SELECT id, fullname FROM {local_costcenter} WHERE 1=1 AND univ_dept_status IS NOT NULL AND visible = 1";
                if(!empty($this->_ajaxformdata['costcenter'])){
                    $subsql .= " AND parentid = ".$this->_ajaxformdata['costcenter']."";
                }
                $colleges = $colleges+$DB->get_records_sql_menu($subsql);
            }elseif($USER->open_costcenterid){
                $colleges = $colleges+$DB->get_records_sql_menu("SELECT id, fullname FROM {local_costcenter} WHERE univ_dept_status IS NOT NULL AND parentid = $USER->open_costcenterid");
            }
           if(is_siteadmin() || has_capability('local/costcenter:manage_ownorganization', context_system::instance())){

            	if($instituteid){
					$locations_countinsessions = $DB->count_records('local_cc_course_sessions', array('instituteid' => $instituteid));
				}
				if($locations_countinsessions){
					$departmentname=$DB->get_field('local_costcenter','fullname',array('id'=>$instinfo->subcostcenter));
					$mform->addElement('static', 'departmentname', get_string('college', 'local_location'));
		            $mform->setDefault('departmentname',$departmentname);

		            $mform->addElement('hidden', 'subcostcenter',
		            get_string('college', 'local_location'));
		            $mform->setType('subcostcenter', PARAM_INT);
		            $mform->setDefault('subcostcenter', $instinfo->subcostcenter);
				}else{
					$select = $mform->addElement('select', 'subcostcenter',get_string('college','local_location'), $colleges);
		            $mform->addRule('subcostcenter', get_string('error_college', 'local_location'), 'required', null, 'client');
				}
	            
            }else{
            	$mform->addElement('hidden', 'subcostcenter',$USER->open_departmentid);
            }
        }
        if($instituteid){
			$locations_countinsessions = $DB->count_records('local_cc_course_sessions', array('instituteid' => $instituteid));
		}
		if($locations_countinsessions){
			$institutetypename= ($instinfo->institute_type == 1) ? get_string('internal', 'local_location') : get_string('external', 'local_location');
			$mform->addElement('static', 'institutetypename', get_string('institutetype', 'local_location'));
            $mform->setDefault('institutetypename',$institutetypename);

            $mform->addElement('hidden', 'institute_type',
            get_string('institutetype', 'local_location'));
            $mform->setType('institute_type', PARAM_INT);
            $mform->setDefault('institute_type', $instinfo->institute_type);
		}else{
			$allow_multi_session = array();
			$allow_multi_session[] = $mform->createElement('radio', 'institute_type', '', get_string('internal', 'local_location'), 1);
			$allow_multi_session[] = $mform->createElement('radio', 'institute_type', '', get_string('external', 'local_location'), 2);
			$mform->addGroup($allow_multi_session, 'radioar', get_string('institutetype', 'local_location'), array(' '), false);
			$mform->setDefault('institute_type',1);
			$mform->addHelpButton('radioar', 'locationtype', 'local_location');   
		}
		

		$mform->addElement('text', 'fullname', get_string('institute_name', 'local_location'));
		$mform->setType('fullname', PARAM_TEXT);
		$mform->addRule('fullname', get_string('error_locationname', 'local_location'), 'required', null, 'client');

		$mform->addElement('textarea', 'address', get_string('address', 'local_location')	);
		$mform->setType('address', PARAM_TEXT);
		$mform->addRule('address', get_string('error_locationaddress',  'local_location'), 'required', null, 'client');

		//$this->add_action_buttons();
	}
	public function validation($data, $files){
		global $DB;
		$errors = parent::validation($data, $files);
		$fullname = $data['fullname'];
		$costcenter = $data['costcenter'];
		$subcostcenter = $data['subcostcenter'];
		$institute_type = $data['institute_type'];
		$params = array();
		if(strlen($data['address'])> 500){
			$errors['address'] = get_string('addresstoolong', 'local_location');
		}
		if(!empty($data['fullname']) && $data['costcenter'] && $data['subcostcenter']){
			$subsql = "SELECT * FROM {local_location_institutes} 
							   WHERE costcenter = :costcenter
							   	 AND subcostcenter = :subcostcenter
							   	 AND institute_type = :institute_type
							   	 AND fullname = :fullname";
			if($data['id'] > 0){
				$subsql .= " AND id != :id";
				$params['id'] = $data['id'];
			}
			// print_object($subsql);
			$params['fullname'] = strip_tags($fullname);
			$params['costcenter'] = $costcenter;
			$params['subcostcenter'] = $subcostcenter;
			$params['institute_type'] = $institute_type;
			// print_object($params);
			$exists = $DB->get_record_sql($subsql, $params);
			if($exists){
				$errors['fullname'] = get_string('fullnameexistserror', 'local_location');
			}
		}
		return $errors; 
	}

}

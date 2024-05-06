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

class roomform extends moodleform {

	/**
	 * Definition of the room form
	 */
	function definition() {
		global $DB,$USER;

		$mform = &$this->_form;
		$systemcontext = context_system::instance();
		$roomid = $this->_customdata['id'];
		$costcenterid = $this->_customdata['costcenter'];

		$roominfo = $DB->get_record('local_location_room',array('id' => $roomid));
		$mform->addElement('hidden', 'id', $roomid);
		$mform->setType('id', PARAM_INT);

		$params = array();
        
        //-----Added by Yamini for mapping costcenter to location-------
        /*$costcenterselect = array('null' => get_string('selectuniversity', 'local_location'));
        $costcenter = $DB->get_records_sql_menu("SELECT id, fullname FROM {local_costcenter} WHERE visible = 1 AND univ_dept_status IS NULL AND parentid = 0 AND visible = 1");
        $select = $mform->addElement('select', 'costcenter', get_string('costcenter', 'local_curriculum'),$costcenterselect+$costcenter);
        $mform->addRule('costcenter', get_string('error_costcenter', 'local_location'), 'required', null, 'client');*/
        $sql = "SELECT id,fullname FROM {local_costcenter} where 1=1 AND parentid=0 AND univ_dept_status IS NULL AND visible = 1";

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
			if($roomid){
				$rooms_countinsessions = $DB->count_records('local_cc_course_sessions', array('roomid' => $roomid));
			}
			if($rooms_countinsessions){
				$universityname=$DB->get_field('local_costcenter','fullname',array('id'=>$roominfo->costcenter));
				$mform->addElement('static', 'universityname', get_string('department', 'local_location'));
	            $mform->setDefault('universityname',$universityname);

	            $mform->addElement('hidden', 'costcenter',
	            get_string('department', 'local_location'));
	            $mform->setType('costcenter', PARAM_INT);
	            $mform->setDefault('costcenter', $roominfo->costcenter);
			}else{
				$mform->addElement('select', 'costcenter', get_string('department', 'local_location'), $institutenames, array());
				$mform->addRule('costcenter', get_string('error_costcenter', 'local_location'), 'required', null, 'client');
			}
	    }else{
	    	$mform->addElement('hidden', 'costcenter',$USER->open_costcenterid, '', array('id' => 'id_costcenter'));
	    }
       
        if (is_siteadmin($USER->id) || has_capability('local/location:manageinstitute',$systemcontext) || has_capability('local/location:manageroom',$systemcontext)) {
            $colleges = array(null=>get_string('selectcollege','local_location'));
            if($costcenterid > 0){
                $subsql = "SELECT id, fullname FROM {local_costcenter} WHERE 1=1 AND univ_dept_status IS NOT NULL AND visible = 1";
                if($costcenterid){
                    $subsql .= " AND parentid = ".$costcenterid."";
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
	            if($roomid){
					$rooms_countinsessions = $DB->count_records('local_cc_course_sessions', array('roomid' => $roomid));
				}
				if($rooms_countinsessions){
					$departmentname=$DB->get_field('local_costcenter','fullname',array('id'=>$roominfo->subcostcenter));
					$mform->addElement('static', 'departmentname', get_string('college', 'local_location'));
		            $mform->setDefault('departmentname',$departmentname);

		            $mform->addElement('hidden', 'subcostcenter',
		            get_string('college', 'local_location'));
		            $mform->setType('subcostcenter', PARAM_INT);
		            $mform->setDefault('subcostcenter', $roominfo->subcostcenter);
				}else{
					$select = $mform->addElement('select', 'subcostcenter',get_string('college','local_location'), $colleges);
		            $mform->addRule('subcostcenter', get_string('error_college', 'local_location'), 'required', null, 'client');
				}
            }elseif($USER->open_departmentid){
            	$mform->addElement('hidden', 'subcostcenter',$USER->open_departmentid, '', array('id' => 'id_subcostcenter'));
            }
        }

        /*$open_costcenterid = $this->_ajaxformdata['costcenter'];
         if(!empty($open_costcenterid)){
              $locations = find_locations($open_costcenterid);
              foreach($locations as $location){
                  $locations[$location->id] = $location->fullname;
              }   
          }
        if($roomid > 0){
              $open_costcenterid = $DB->get_field('local_location_room','costcenter',array('id' => $roomid));               
              $locations = find_locations($open_costcenterid);
              $location_select = array('null' => '--select--');
             
              foreach($locations as $location){
                  $locationslist[$location->id] = $location->fullname;
              }   
           $select = $mform->addElement('select', 'location', get_string('location'),$locationslist);
        }
        else{ 
         $select = $mform->addElement('select', 'location', get_string('location'),$locations);
        }*/
        if (is_siteadmin($USER->id) || has_capability('local/location:manageinstitute',$systemcontext) || has_capability('local/location:manageroom',$systemcontext)) {
            $locations = array(null=>get_string('selectlocation','local_location'));
            if($costcenterid > 0){
                $subsql = "SELECT id, fullname FROM {local_location_institutes} WHERE 1=1 ";
                if($costcenterid){
                    $subsql .= " AND costcenter = ".$costcenterid."";
                }
                if($USER->open_departmentid){
                	$subsql .= " AND subcostcenter = ".$USER->open_departmentid."";	
                }
                $locations = $locations+$DB->get_records_sql_menu($subsql);
            }elseif($this->_ajaxformdata['costcenter']){
            	$subsql = "SELECT id, fullname FROM {local_location_institutes} WHERE 1=1 ";
                if(!empty($this->_ajaxformdata['costcenter'])){
                    $subsql .= " AND costcenter = ".$this->_ajaxformdata['costcenter']."";
                }
                if($USER->open_departmentid){
                	$subsql .= " AND subcostcenter = ".$USER->open_departmentid."";	
                }
                $locations = $locations+$DB->get_records_sql_menu($subsql);
            }elseif($USER->open_costcenterid && $USER->open_departmentid){
                $locations = $locations+$DB->get_records_sql_menu("SELECT id, fullname FROM {local_location_institutes} WHERE costcenter = $USER->open_costcenterid AND subcostcenter = $USER->open_departmentid");
            }/*elseif($USER->open_costcenterid){
                $colleges = $colleges+$DB->get_records_sql_menu("SELECT id, fullname FROM {local_location_institutesr} WHERE univ_dept_status = 1 AND costcenter = $USER->open_costcenterid");
            }*/
            	if($roomid){
					$rooms_countinsessions = $DB->count_records('local_cc_course_sessions', array('roomid' => $roomid));
				}
				if($rooms_countinsessions){
					$locationname=$DB->get_field('local_location_institutes','fullname',array('id'=>$roominfo->instituteid));
					$mform->addElement('static', 'locationname', get_string('location'));
		            $mform->setDefault('locationname',$locationname);

		            $mform->addElement('hidden', 'location',
		            get_string('location'));
		            $mform->setType('location', PARAM_INT);
		            $mform->setDefault('location', $roominfo->subcostcenter);
				}else{
		            $select = $mform->addElement('select', 'location', get_string('location'),$locations);
		            $mform->addRule('location', get_string('error_location', 'local_location'), 'required', null, 'client');
		        }
        }

		/*$sql = "SELECT id,fullname FROM {local_location_institutes} where 1=1 ";
         if ((has_capability('local/location:manageroom', context_system::instance())) && (!is_siteadmin() ) ) {
            $sql .= " AND (costcenter = :costcenter OR usercreated = :usercreated)";
            $params['costcenter'] = $USER->open_costcenterid;
            $params['usercreated'] = $USER->id;
        }
       
    	$institutes = $DB->get_records_sql($sql,$params);
		$institutenames = array();
		$institutenames[null] = get_string('select', 'local_location');
		if ($institutes) {
			foreach ($institutes as $institute) {
				$institutenames[$institute->id] = $institute->fullname;
			}
		}

		$mform->addElement('select', 'instituteid', get_string('institute', 'local_location'), $institutenames, array());
		$mform->addRule('instituteid', null, 'required', null, 'client');*/

		
		$mform->addElement('text', 'name', get_string('room_name', 'local_location'));
		$mform->setType('name', PARAM_TEXT);
		$mform->addRule('name', get_string('error_roomname', 'local_location'), 'required', null, 'client');
         
         //-----------Commented by Yamini-------
		/*$mform->addElement('text', 'building', get_string('building', 'local_location'), array('size' => '45'));
		$mform->setType('building', PARAM_TEXT);
		$mform->addRule('building', null, 'required', null, 'client');

		$mform->addElement('text', 'address', get_string('address', 'local_location'), array('size' => '45'));
		$mform->setType('address', PARAM_TEXT);
		$mform->addRule('address', null, 'required', null, 'client');
*/		// Commented by Harish starts here //
		/*$mform->addElement('hidden', 'mincapacity', 0);
        $mform->setType('mincapacity', PARAM_INT);
        $maxcapacity = 1500;//9223372036854775807
        $mform->addElement('hidden', 'maxcapacity', $maxcapacity);
        $mform->setType('maxcapacity', PARAM_INT);
		$mform->addElement('text', 'capacity', get_string('capacity', 'local_location'));
		$mform->setType('capacity', PARAM_INT);
		$mform->addHelpButton('capacity','capacityofusers','local_location');
        $mform->addRule('capacity', null, 'required', null, 'client');
		$mform->addRule(array('capacity', 'mincapacity'), get_string('capacity_positive',
             'local_location'), 'compare', 'gt', 'client');
		$mform->addRule(array('capacity', 'maxcapacity'), 
			get_string('capacity_limitexceeded', 'local_classroom', $maxcapacity), 'compare', 'lt', 'client');*/
		// Commented by Harish starts here //

		$mform->addElement('textarea', 'description', get_string('description', 'local_location'));
		$mform->setType('description', PARAM_TEXT);
		$mform->addRule('description', get_string('error_desc', 'local_location'), 'required', null, 'client');
		$mform->addHelpButton('description', 'descript', 'local_location');

		//$this->add_action_buttons();
	}

	public function validation($data, $files){
		global $DB;
		$errors = parent::validation($data, $files);
		$roomname = $data['name'];
		$costcenter = $data['costcenter'];
		$subcostcenter = $data['subcostcenter'];
		$location = $data['location'];
		$params = array();
		/*if(strlen($data['address'])> 500){
			$errors['address'] = get_string('addresstoolong', 'local_location');
		}*/
		if(!empty($data['name']) && $data['costcenter'] && $data['subcostcenter'] && $data['location']){
			$subsql = "SELECT * FROM {local_location_room} 
							   WHERE costcenter = :costcenter
							   	 AND subcostcenter = :subcostcenter
							   	 AND instituteid = :instituteid
							   	 AND name = :name";
			if($data['id'] > 0){
				$subsql .= " AND id != :id";
				$params['id'] = $data['id'];
			}
			$params['name'] = strip_tags($roomname);
			$params['costcenter'] = $costcenter;
			$params['subcostcenter'] = $subcostcenter;
			$params['instituteid'] = $location;
			$exists = $DB->get_record_sql($subsql, $params);
			if($exists){
				$errors['name'] = get_string('roomalreadyexistserror', 'local_location');
			}
		}
		return $errors; 
	}
}

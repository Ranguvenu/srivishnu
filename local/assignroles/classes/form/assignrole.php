<?php
/**
 * This file is part of eAbyas
 *
 * Copyright eAbyas Info Solutons Pvt Ltd, India
 *
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
 * @author Maheshchandra  <maheshchandra@eabyas.in>
 */
/**
 * Assign roles to users.
 * @package    local
 * @subpackage assignroles
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace local_assignroles\form;
use moodleform;
use context_system;
require_once("{$CFG->libdir}/formslib.php");
require_once($CFG->dirroot . '/local/assignroles/lib.php');

class assignrole extends moodleform {

    public function definition() {
        global $USER,$DB;
		$contextid = optional_param('contextid', 1, PARAM_INT);
    $systemcontext = context_system::instance();
        $mform = & $this->_form;
		$roleid = $this->_customdata['roleid'];
		/*$options = array(
            'ajax' => 'local_assignroles/form-options-selector',
            'multiple' => true,
            'data-action' => 'role_users',
            'data-options' => json_encode(array('id' => 0, 'roleid' => $roleid)),
        );*/
		$users =array();
		$costcenterselect = array('null' => '--Select--');
        $costcenter = $DB->get_records_sql_menu("SELECT id, fullname FROM {local_costcenter} WHERE visible = 1 AND univ_dept_status IS NULL AND parentid = 0 AND visible = 1");
        if(!is_siteadmin() && has_capability('local/costcenter:manage_ownorganization', $systemcontext)){
    
          $university = $DB->get_record_sql("SELECT id, fullname FROM {local_costcenter} WHERE id = $USER->open_costcenterid AND visible = 1");
                $mform->addElement('hidden', 'costcenter',get_string('costcenter', 'local_boards'));
                $mform->setType('costcenter', PARAM_INT);
                $mform->setDefault('costcenter', $university->id);
        }
        else{
          $mform->addElement('select', 'costcenter', get_string('costcenter', 'local_curriculum'),$costcenterselect+$costcenter);
          $mform->addRule('costcenter', null, 'required', null, 'client');
          $open_costcenterid = $this->_ajaxformdata['costcenter'];
         }
      //  print_object("came");
      //  echo $open_costcenterid;
       /* $mform->addElement('autocomplete', 'users', get_string('employees', 'local_users'), $users, $options);
        $mform->setType('users', PARAM_RAW);
		$mform->addRule('users', null, 'required', null, 'client');*/
      if(!is_siteadmin() && has_capability('local/costcenter:manage_ownorganization', $systemcontext)){
        $open_costcenterid = $USER->open_costcenterid;
        $userselect = array('null' => 'Select');
        if(!empty($open_costcenterid)){
          
          $users = find_users($open_costcenterid,$roleid);
          if($users){
          foreach($users as $user){
              $userslist[$user->id] = $user->fullname;
          }  
          $userselect = $userselect+$userslist;
         }
        }
        $mform->addElement('autocomplete', 'users', get_string('users','local_program'),$userselect);
        $mform->addRule('users', get_string('missingusers','local_assignroles'), 'required', null, 'client');
     }
     else{
		     
        if(!empty($open_costcenterid)){
        	$userselect = array('null' => 'Select');
          $users = find_users($open_costcenterid,$roleid);
          foreach($users as $user){
              $userslist[$user->id] = $user->fullname;
          }  
        }
        if($userslist){
          $userselect = $userselect+$userslist;
        }
        
        $mform->addElement('autocomplete', 'users', get_string('users','local_program'),$userselect);
        $mform->addRule('users', get_string('missingusers','local_assignroles'), 'required', null, 'client');
      }

       
		$mform->addElement('hidden', 'roleid');
		$mform->setType('roleid', PARAM_TEXT);
		$mform->setDefault('roleid', $roleid);
		
		if(!$contextid){
			$mform->addElement('text', 'contextid', get_string('contextid', 'local_assignroles'));
			$mform->setType('contextid', PARAM_TEXT);
			$mform->setDefault('contextid', $contextid);
		}else{
			$mform->addElement('hidden', 'contextid');
			$mform->setType('contextid', PARAM_TEXT);
			$mform->setDefault('contextid', $contextid);
		}

       // $this->add_action_buttons($cancel = null,get_string('assign', 'local_assignroles'));
    }
    public function validation($data, $files) {
        global $CFG, $DB, $USER;
       
        $errors = parent::validation($data, $files);   
        if ($data['costcenter'] == 'null') {
            $errors['costcenter'] = get_string('missingcostcenter','local_assignroles');
        }
        if ($data['users'] == 'null' || $data['users'] == 0) {
            $errors['users'] = get_string('missingusers','local_assignroles');
        }

        return $errors;
    }
}
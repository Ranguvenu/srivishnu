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
 *
 * @package    notifications
 * @copyright  2018 sreenivas
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace local_notifications\forms;
require_once($CFG->libdir . '/formslib.php');
require_once($CFG->dirroot .'/local/notifications/lib.php');
use moodleform;
class notification_form extends moodleform {
    public $formstatus;
    public function __construct($action = null, $customdata = null, $method = 'post', $target = '', $attributes = null, $editable = true, $formdata = null) {

        /*$this->formstatus = array(
            'generaldetails' => get_string('generaldetails', 'local_users'),
            'otherdetails' => get_string('otherdetails', 'local_users'),
            );*///Commented by Harish ODL-222
        parent::__construct($action, $customdata, $method, $target, $attributes, $editable, $formdata);
    }

    public function definition() {
        global $DB, $PAGE, $USER;
        $mform = $this->_form;
        $lib = new \notifications();
        // $form_status = $this->_customdata['form_status'];//Commented by Harish ODL-222
        $org = $this->_customdata['org'];
        $id = $this->_customdata['id'] > 0 ? $this->_customdata['id'] : 0;
        $context = \context_system::instance();
        //$mform->addElement('header', 'general', get_string('pluginname', 'local_notifications')); 
        $moduleid = $this->_customdata['moduleid'];
        $notificationid = $this->_customdata['notificationid'];
        // if($form_status == 0){//Commented by Harish ODL-222
            $departments = array();
            $departmentnull[null] = get_string('select_costcenter', 'local_notifications');
            if (is_siteadmin($USER->id)) {
    			$departments = $DB->get_records_sql_menu("SELECT * FROM {local_costcenter} WHERE visible = 1 AND parentid = 0");
    			$departments = $departmentnull+$departments;
    			$mform->addElement('select', 'costcenterid', get_string('organization', 'local_users'), $departments);
                 $mform->addRule('costcenterid', get_string('missingcostcenter','local_notifications'), 'required', null, 'client'); 
    			//$mform->setType('costcenterid', PARAM_INT);
    			
    		} elseif(!is_siteadmin()  && !has_capability('local/assign_multiple_departments:manage',$context)){
    			$user_dept = $DB->get_field('user','open_costcenterid', array('id'=>$USER->id));
    			$departments = $DB->get_records_sql_menu("SELECT * FROM {local_costcenter} WHERE visible = 1 AND id = $user_dept");
    			$departments = $departmentnull+$departments;
    			$mform->addElement('select', 'costcenterid', get_string('organization', 'local_users'), $departments);
    			$mform->setType('costcenterid', PARAM_INT);
                $mform->addRule('costcenterid', get_string('missingcostcenter','local_notifications'), 'required', null, 'client');        

    			$mform->setConstant('costcenterid', $user_dept);
    		}else{
    			$user_dept = $DB->get_field('user','open_costcenterid', array('id'=>$USER->id));
    			$mform->addElement('hidden', 'costcenterid', null);
                $mform->addRule('costcenterid', get_string('missingcostcenter','local_notifications'), 'required', null, 'client');        
    			$mform->setType('costcenterid', PARAM_INT);
    			$mform->setConstant('costcenterid', $user_dept);
    		}
            
            $notification_type = array();
            $select = array();
            $select[null] = get_string('select_opt', 'local_notifications');
            $notification_type[null] = $select;
            $module_categories = $DB->get_records('local_notification_type', array('parent_module'=>0));
            if($module_categories){
                 foreach($module_categories as $module_category){
                    $notifications = $DB->get_records_sql_menu("SELECT * FROM {local_notification_type} WHERE parent_module = $module_category->id AND parent_module <> 0 AND (shortname!='course_reminder') AND shortname NOT IN ('classroom_invitation', 'program_invitation','forum','forum_subscription','forum_unsubscription','forum_reply','forum_post','lep_reminder')");// and shortname!='classroom_invitation'and shortname!='program_invitation' and shortname NOT LIKE '%forum%'
                    $notification_type[$module_category->name] = $notifications;
                }
            }
            
            $mform->addElement('selectgroups', 'notificationid', get_string('notification_type', 'local_notifications'), $notification_type,array());
            $mform->addRule('notificationid', get_string('missingnotificationtype','local_notifications'), 'required', null, 'client');        
            
            $mform->addElement('text', 'subject', get_string('subject', 'local_notifications'));
            $mform->setType('subject', PARAM_RAW);

            $datamoduleids=array();
            $datamodule_label="Courses";
            $strings = 'None';
            if($id > 0 || ($notificationid && is_array($moduleid) && !empty($moduleid))){
                if($id > 0){
                    $notifyid = $DB->get_record('local_notification_info',  array('id'=>$id));
                    
                    $notif_type = $DB->get_field('local_notification_type', 'shortname', array('id'=>$notifyid->notificationid));
                }else{
                    $notif_type = $DB->get_field('local_notification_type', 'shortname', array('id'=>$notificationid));
                    $notifyid=new stdClass();
                    $notif_type_find=explode('_',$notif_type);
                    $notifyid->moduletype=$notif_type_find[0];
                    $notifyid->costcenterid=$org;
                }
                $strings = $lib->get_string_identifiers($notif_type);
                switch(strtolower($notifyid->moduletype)){
                    case 'course':  
                        $sql = "SELECT c.id, c.fullname as name FROM {course} c                           
                        WHERE  c.visible = 1 AND c.open_costcenterid =$notifyid->costcenterid ";                    
                        $datamoduleids = $DB->get_records_sql_menu($sql);
                        
                        $datamodule_label="Courses";
                        
                    break;  
                    
                    case 'program': 
                        $sql = "SELECT c.id, c.fullname FROM {local_program} c                           
                        WHERE c.costcenter =$notifyid->costcenterid ";                 
                        $datamoduleids = $DB->get_records_sql_menu($sql);
                        
                        $datamodule_label="Programs";
                        
                    break;
                }
            }
            $mform->addElement('static', 'string_identifiers', get_string('string_identifiers', 'local_notifications'),  $strings);
            $mform->addHelpButton('string_identifiers', 'string_identifiers','local_notifications');
            
            $mform->addElement('editor', 'body', get_string('emp_body', 'local_notifications'), array(), array('autosave'=>false));
            $mform->setType('body', PARAM_RAW);
        // }// end of form status = 0 condition    
        /*else if($form_status ==1){    
            $mform->addElement('editor', 'adminbody', get_string('admin_body', 'local_notifications'), array(), array('autosave'=>false));
            $mform->setType('adminbody', PARAM_RAW);
        }// end of form status = 1 condition*///Commented by Harish ODL-222
        
        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        //$this->add_action_buttons(true);
        $mform->disable_form_change_checker();
    }
    
    public function validation($data, $files) {
        global $DB;
        $errors = parent::validation($data, $files);
        
        $notificationid = $data['notificationid'];
        $costcenterid = $data['costcenterid'];
        $id = $data['id'];
        $record = $DB->get_record_sql('SELECT * FROM {local_notification_info} WHERE costcenterid = ? AND notificationid = ? AND  id <> ?', array($costcenterid, $notificationid, $id));
        if (!empty($record)) {
           $errors['notificationid'] = get_string('codeexists', 'local_notifications');
        }
        return $errors;
    }
    
}

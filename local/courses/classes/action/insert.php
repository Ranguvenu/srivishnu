<?php

namespace local_courses\action;
defined('MOODLE_INTERNAL') or die;
use enrol_get_plugin;
class insert{
    /*
     * @method local_logs Get logs
     * @param $event 
     * @param $module
     * @param $description
     * @param $type
     * @output data will be insert into mdl_local_logs table
     */    
    function local_custom_logs($event, $module, $description, $type=NULL){
    
        global $DB, $USER, $CFG;       
        
        $userid                 = $USER->id; // current userid
        /* creating an object to store parameters*/
        $log_data               = new \stdClass();
        $log_data->event        = $event;
        $log_data->module       = $module;
        $log_data->description  = $description;
        $log_data->type         = $type;
        $log_data->timecreated  = time();
        $log_data->timemodified = time();
        $log_data->usercreated  = $userid;
        $log_data->usermodified = $userid;
        
        $result = $DB->insert_record('local_logs', $log_data);
        return $result;
    }
    function add_enrol_meathod_tocourse($coursedata,$enrol_status = null){
        global $DB;
        // define(1,'mooc');
        define(2,'classroom');
        define(3,'self');
        define(4,'learningplan');
        define(5,'program');
        define(6,'certification');
        $types = $coursedata->open_identifiedas;
        $coursetypes = explode(',', $types);
        // echo "hlo";
        // print_object($coursetypes);
        // exit;
        foreach($coursetypes as $type){
            if($type == 2 ||$type == 3 || $type == 5 || $type == 4 || $type ==6){
               // echo "fdssdgsdgg";
                $plugin = \enrol_get_plugin(constant($type));
                if (!$plugin) {
                    throw new moodle_exception('invaliddata', 'error');
                }
                $fields = array();
                $fields['roleid'] = $DB->get_field('role','id',array('shortname' => 'student'));
                $fields['type'] = constant($type);
                $fields['courseid'] = $coursedata->id;
                $value = $DB->get_record('enrol',array('courseid'=> $coursedata->id ,'enrol' => constant($type)));
                // echo "hloo";
                // print_object($fields);
                // print_object($value);
               
                if(!$DB->record_exists('enrol',array('courseid'=> $coursedata->id ,'enrol' => constant($type)))){
                   // echo "hi123";
                     
                $plugin->add_instance($coursedata, $fields);
                } else {
                   $existing_method = $DB->get_record('enrol',array('courseid'=> $coursedata->id ,'enrol' => constant($type)));
                   // echo "test124";
                   // exit;
                    if(constant($type) == 'self'){
                        if($enrol_status == 1){
                            $existing_method->status = 0;
                        }else{
                             $existing_method->status = 1;
                        }
                    }else{
                        $existing_method->status = 0;
                    }
                    $DB->update_record('enrol', $existing_method);
                }
            }
        }
    }
}

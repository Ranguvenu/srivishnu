<?php

namespace local_faculties\events;
defined('MOODLE_INTERNAL') or die;

class insert{
    /*
     * @method local_logs Get logs
     * @param $event 
     * @param $module
     * @param $description
     * @param $type
     * @output data will be insert into mdl_local_logs table
     */    
    function faculty_insert_instance($faculty){
        global $DB, $CFG, $USER;
        if($faculty) {
            $datarecord = new \stdClass();
            $datarecord->university = $faculty->university;
            $datarecord->facultyname = $faculty->facultyname;
            $datarecord->facultycode = $faculty->facultycode;
            $datarecord->board = $faculty->board;
            $datarecord->description =  $faculty->description['text'];
            $datarecord->timecreated =  time();
            $datarecord->timemodified =  time();
            $datarecord->usercreated =  $USER->id;
            $inserteddata = $DB->insert_record('local_faculties',  $datarecord);
        }
        return $inserteddata;
}
}

<?php

namespace local_boards\events;
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
    function board_insert_instance($board){
        global $DB, $CFG, $USER;
        if($board) {
            $datarecord = new \stdClass();
            $datarecord->university = $board->university;
            $datarecord->fullname = $board->fullname;
            $datarecord->shortname = $board->shortname;
            $datarecord->description =  $board->description['text'];
            $datarecord->timecreated =  time();
            $datarecord->timemodified =  time();
            $datarecord->usercreated =  $USER->id;
            $inserteddata = $DB->insert_record('local_boards',  $datarecord);
        }
        return $inserteddata;
}
}

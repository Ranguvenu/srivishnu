<?php

namespace local_boards\events;
defined('MOODLE_INTERNAL') or die;
class update{
	/** @var array of categories in a specific costcenter */
    function board_update_instance($board){
        global $USER,$DB;
        if($board->id>0) {
            $datarecord = new \stdClass();
            $datarecord->id = $board->id;
            $datarecord->university = $board->university;
            $datarecord->fullname = $board->fullname;
            $datarecord->shortname = $board->shortname;
            $datarecord->description =  $board->description['text'];
            $datarecord->timemodified =  time();
            $datarecord->usermodified =  $USER->id;
            $updateddata = $DB->update_record('local_boards',  $datarecord);
        }
        return $updateddata;
    }
}
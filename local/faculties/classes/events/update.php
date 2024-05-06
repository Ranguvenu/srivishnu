<?php

namespace local_faculties\events;
defined('MOODLE_INTERNAL') or die;
class update{
	/** @var array of categories in a specific costcenter */
    function faculty_update_instance($faculty){
        global $USER,$DB;
        if($faculty->id>0) {
            $datarecord = new \stdClass();
            $datarecord->id = $faculty->id;
            $datarecord->university = $faculty->university;
            $datarecord->facultyname = $faculty->facultyname;
            $datarecord->facultycode = $faculty->facultycode;
            $datarecord->board = $faculty->board;
            $datarecord->description =  $faculty->description['text'];
            $datarecord->timemodified =  time();
            $datarecord->usermodified =  $USER->id;
            $updateddata = $DB->update_record('local_faculties',  $datarecord);
        }
        return $updateddata;
    }
}
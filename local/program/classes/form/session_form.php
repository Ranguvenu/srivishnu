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
 * Manage curriculum Session form
 *
 * @package    local_curriculum
 * @copyright  2018 Arun Kumar M <arun@eabyas.in>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_program\form;
defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');
use \local_program\program as program;
use moodleform;
use local_program\local\querylib;
use context_system;

class session_form extends moodleform {

    public function definition() {
        global $CFG, $DB, $USER;
        // print_object($this->_customdata);exit;
        $querieslib = new querylib();
        $context = context_system::instance();
        $mform = &$this->_form;
        $ccid = $this->_customdata['ccid'];
        $sid = $this->_customdata['id'];
        $semesterid = $this->_customdata['semesterid'];
        $bclcid = $this->_customdata['bclcid'];
        $programid = $this->_customdata['programid'];
        $yearid = $this->_customdata['yearid'];
        $courseid = $this->_customdata['courseid'];
        $ccses_action = $this->_customdata['ccses_action'];
        $costcenterid = $this->_customdata['costcenter'];
        $instituteid = $this->_customdata['location'];
        $roomid = $this->_customdata['room'];
        $institute_type = $this->_customdata['institute_type'];

        $mform->addElement('hidden', 'id', $sid);
        $mform->setType('id', PARAM_INT);

        $mform->addElement('hidden', 'datetimeknown', 1);
        $mform->setType('datetimeknown', PARAM_INT);

        $mform->addElement('hidden', 'bclcid', $bclcid);
        $mform->setType('bclcid', PARAM_INT);

        $mform->addElement('hidden', 'programid', $programid);
        $mform->setType('programid', PARAM_INT);

        $mform->addElement('hidden', 'curriculumid', $ccid);
        $mform->setType('curriculumid', PARAM_INT);

        $mform->addElement('hidden', 'yearid', $yearid);
        $mform->setType('yearid', PARAM_INT);

        $mform->addElement('hidden', 'semesterid', $semesterid);
        $mform->setType('semesterid', PARAM_INT);

        $mform->addElement('hidden', 'courseid', $courseid);
        $mform->setType('courseid', PARAM_INT);

        $mform->addElement('hidden', 'ccses_action', $ccses_action);
        $mform->setType('ccses_action', PARAM_RAW);

        $mform->addElement('text', 'name', get_string('name'), array());
        if (!empty($CFG->formatstringstriptags)) {
            $mform->setType('name', PARAM_TEXT);
        } else {
            $mform->setType('name', PARAM_CLEANHTML);
        }
        $mform->addRule('name', null, 'required', null, 'client');

        $getconfig = get_config('local_curriculum', 'curriculum_onlinesession_type');
        if (!empty($getconfig)) {
            $instancetype = explode('_', $getconfig);
            $visible = $DB->get_field('modules', 'visible', array('name' => $instancetype[1]));
            if ($visible) {
                $mform->addElement('advcheckbox', 'onlinesession',
                    get_string('onlinesession', 'local_program'), '', array(), array(0, 1));
                $mform->addHelpButton('onlinesession', 'onlinesession', 'local_program');
            }
        }

        $institutetypes = array();
        $institutetypes[] = $mform->createElement('radio', 'institute_type', '',
                get_string('internal', 'local_program'), 1, $attributes);
        $institutetypes[] = $mform->createElement('radio', 'institute_type', '',
                get_string('external', 'local_program'), 2, $attributes);
        $mform->addGroup($institutetypes, 'radioar', get_string('bc_location_type',
                'local_program'), array(' '), false);
        $mform->addRule('radioar', get_string('selectbc_location_type', 'local_program'), 'required', null, 'client');
        //---changes by Yamini--//
        $nulllocationtype = array(null=>get_string('selectlocation','local_program'));
        if($sid > 0){
            $institute_type = $this->_ajaxformdata['institute_type'] ? $this->_ajaxformdata['institute_type'] : $institute_type;
            $locations = find_locations_basedon_type($institute_type,$programid);              
            foreach($locations as $location){
                $curriculumlocations[$location->id] = $location->fullname;
            }     
        }else{
            $institute_type = $this->_ajaxformdata['institute_type'];
            if(!empty($institute_type)){
                $locations = find_locations_basedon_type($institute_type,$programid); 
                foreach($locations as $location){
                    $curriculumlocations[$location->id] = $location->fullname;
                }
            }
        }
        if(!empty($curriculumlocations)){
            $curriculumlocations = $nulllocationtype+$curriculumlocations;
        }else{
            $curriculumlocations = $nulllocationtype;
        }
        $mform->addElement('select', 'instituteid', get_string('curriculum_location','local_program'), $curriculumlocations);
        $mform->addRule('instituteid', get_string('missinginstituteid', 'local_program'), 'required', null, 'client');
     
        $mform->hideIf('instituteid', 'classroom_type', 'eq', 0);
      
        $roomid = $this->_ajaxformdata['instituteid'];

        if($sid > 0){
            $rooms = find_rooms($instituteid);
            foreach($rooms as $room){
                $roomslist[$room->id] = $room->name;
            }
        }else{
        $location= $this->_ajaxformdata['instituteid'];
            if(!empty($location)){
                $rooms = find_rooms($location);
                foreach($rooms as $room){
                    $roomslist[$room->id] = $room->name;
                }
            }
        }
        if(empty($roomslist)){
            $roomslist =  array(null => get_string('selectroom', 'local_program'));
        }else{
            $roomslist =  array(null => get_string('selectroom', 'local_program'))+$roomslist;
        }

        $mform->addElement('select', 'room', get_string('room','local_program'),$roomslist);
        $mform->disabledIf('room', 'onlinesession', 'checked');
        $mform->addRule('room', get_string('missingroom', 'local_program'), 'required', null, 'client');

        $mform->hideIf('room', 'classroom_type', 'eq', 0);
        $roomid = $this->_ajaxformdata['room'];
   
        if(!empty($programid)){              
              $faculties = findfaculty($programid);  
              foreach($faculties as $faculty){
                  $facultylist[$faculty->id] = $faculty->username;
              }                
        }
        $faculty_select = array(null => '--Select Faculty--');       
        if($facultylist){
            $facultylist = $faculty_select+$facultylist; 
        }else{
            $facultylist = $faculty_select;
        }
        $mform->addElement('select', 'trainerid', get_string('trainer','local_program'),$facultylist);
        $mform->addRule('trainerid', get_string('missingtrainerid', 'local_program'), 'required', null, 'client');

        /*$mform->addElement('text', 'maxcapacity', get_string('maxcapacity', 'local_program'),'readonly');
        $mform->setType('maxcapacity', PARAM_RAW);
        $mform->addRule('maxcapacity', get_string('selectmaxcapacity', 'local_program'), 'required', null, 'client');
        $mform->addRule('maxcapacity', null, 'numeric', null, 'client');*/
       

        /*$mform->addElement('text', 'mincapacity', get_string('mincapacity', 'local_program'));
        $mform->addRule('mincapacity', null, 'numeric', null, 'client');
        $mform->addRule('mincapacity', null, 'nonzero', null, 'client');
        $mform->setType('mincapacity', PARAM_RAW);
        $mform->addRule('mincapacity', get_string('selectmincapacity', 'local_program'), 'required', null, 'client');
        $mform->addRule('mincapacity', null, 'numeric', null, 'client');*/

        $mform->addElement('date_selector', 'timestart', get_string('cs_timestart', 'local_program'));
        $mform->setType('timestart', PARAM_INT);
        $mform->addRule('timestart', get_string('missingdailystarttime', 'local_program'), 'required', null, 'client');

        $mform->addElement('date_selector', 'timefinish', get_string('cs_timefinish', 'local_program'));
        $mform->setType('timefinish', PARAM_INT);
        $mform->addRule('timefinish', get_string('missingdailyendtime', 'local_program'), 'required', null, 'client');

        $starttimearray = array();
        $endtimearray = array();
        $hoursattr = array();
        $minsattr = array();
        $hoursattr[null] = get_string('hours');
        for ($hours=0; $hours < 24; $hours++) {
            if($hours < 10){
                $hoursattr['0'.$hours] = '0'.$hours;
            }else{
                $hoursattr[$hours] = $hours;
            }
        }
        $minsattr[null] = get_string('minutes');
        for ($mins=0; $mins < 60; $mins++) { 
            if($mins < 10){
                $minsattr['0'.$mins] = '0'.$mins;
            }else{
                $minsattr[$mins] = $mins;
            }
        }
        $starttimearray[] = $mform->createElement('select', 'dailystarttimehours', get_string('hours'), $hoursattr);
        $starttimearray[] = $mform->createElement('select', 'dailystarttimemins', get_string('minutes'), $minsattr);
        $mform->addGroup($starttimearray, 'dailysessionstarttime', get_string('dailysessionstarttime', 'local_program'), array('class' => 'dailysessionstarttime'));
        $mform->addRule('dailysessionstarttime', get_string('pleaseselectstarttime', 'local_program'), 'required', null, 'client');

        $endtimearray[] = $mform->createElement('select', 'dailyendtimehours', get_string('hours'), $hoursattr);
        $endtimearray[] = $mform->createElement('select', 'dailyendtimemins', get_string('minutes'), $minsattr);
        $mform->addGroup($endtimearray, 'dailysessionendtime', get_string('dailysessionendtime', 'local_program'), array('class' => 'dailysessionendtime'));
        $mform->addRule('dailysessionendtime', get_string('pleaseselectendtime', 'local_program'), 'required', null, 'client');

        $editoroptions = array(
            'noclean' => false,
            'autosave' => false
        );
        /*$mform->addElement('editor', 'cs_description', get_string('description', 'local_program'), null, $editoroptions);
        $mform->setType('cs_description', PARAM_RAW);
        $mform->addHelpButton('cs_description', 'description', 'local_program');*/

        $mform->disable_form_change_checker();
    }

    public function validation($data, $files) {
        global $CFG, $DB, $USER;
       
        $errors = parent::validation($data, $files);
        $curriculumdates = $DB->get_record_select('local_curriculum', 'id = :curriculumid ',
            array('curriculumid' => $data['curriculumid']), 'startdate, enddate');
        $classroominfo = $DB->get_record('local_cc_semester_classrooms',array('id' => $data['bclcid']));
        // $mincapacity = $data['mincapacity'];
       // $maxcapacity = $data['maxcapacity'];
        $timestart = $data['timestart'];
        $timefinish = $data['timefinish'];
        $timestarthours = $data['dailysessionstarttime']['dailystarttimehours']*60*60 + $data['dailysessionstarttime']['dailystarttimemins']*60;
        $timefinishhours = $data['dailysessionendtime']['dailyendtimehours']*60*60 + $data['dailysessionendtime']['dailyendtimemins']*60;
        
        if(!isset($data['dailysessionstarttime']['dailystarttimehours'])){
            $errors['dailysessionstarttime'] = get_string('pleaseselectstarttime', 'local_program'); 
        }
        if(!isset($data['dailysessionendtime']['dailyendtimehours'])){
            $errors['dailysessionendtime'] = get_string('pleaseselectendtime', 'local_program'); 
        }
        if($timestart < $classroominfo->nomination_startdate){
            $errors['timestart'] = get_string('sessionstartdatemustbegreaterthanclassroomstartdate', 'local_program'); 
        }

        if($timefinish > $classroominfo->nomination_enddate){
            $errors['timefinish'] = get_string('sessionenddatemustbelessthanclassroomenddate', 'local_program'); 
        }

        if($timestart > $classroominfo->nomination_enddate){
            $errors['timestart'] = get_string('sessionstartdatemustbelessthanclassroomenddate', 'local_program'); 
        }

        if($timefinish < $classroominfo->nomination_startdate){
            $errors['timefinish'] = get_string('sessionenddatemustbegreaterthanclassroomstartdate', 'local_program'); 
        }
        /*if($mincapacity > $maxcapacity) {
            $errors['mincapacity'] = get_string('lessmincapacity', 'local_program');
        }
        if(!empty($mincapacity)){
            if(preg_match("/[^0-9]/", $mincapacity)){
                $errors['mincapacity'] = get_string('validnumbererror','local_program');
            }
        }*/
        if($timestart > $timefinish /*|| $timestart == $timefinish*/) {
            $errors['timestart'] = get_string('startdatelessthanenddate', 'local_program');
        }
        /*if($timestarthours == $timefinishhours){
            $errors['timestart'] = get_string('starttimelessthanendtime', 'local_program');   
        }*/
        if($timestarthours > $timefinishhours){
            $errors['dailysessionstarttime'] = get_string('starttimelessthanendtime', 'local_program');   
        }
        if($data['dailysessionstarttime']['dailystarttimehours'] != null && $data['dailysessionendtime']['dailyendtimehours'] != null && $timestarthours == $timefinishhours){
            $errors['dailysessionstarttime'] = get_string('starttimelessthanendtime', 'local_program');
        }
        if (isset($data['name']) && empty(trim($data['name']))) {
            $errors['name'] = get_string('valnamerequired', 'local_program');
        }
        $curriculumid = $data['curriculumid'];
        $sessiondata = (object) $data;
        $timestart = ($timestarthours) ? $timestart+$timestarthours : $timestart+0;
        $timefinish = ($timefinishhours) ? $timefinish+$timefinishhours : $timefinish+0;
        $sessions_validation_start = (new program)->sessions_validation($sessiondata,
                                    $timestart, $data['id']);
        $session->duration = ($timefinish - $timestart)/60;
        if($data['dailysessionstarttime']['dailystarttimehours'] != null && $sessions_validation_start > 0) {
            $errors['dailysessionstarttime'] = 'There is already another session at this date and time';
        }

        $sessions_validation_end = (new program)->sessions_validation($sessiondata,
            $timefinish, $data['id']);
        if ($data['dailysessionstarttime']['dailystarttimehours'] != null && $sessions_validation_end > 0) {
            $errors['dailysessionendtime'] = 'There is already another session at this date and time';
        }
        return $errors;
    }
}

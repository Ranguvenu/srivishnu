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
 * Manage Classroom Session form
 *
 * @package    local_program
 * @copyright  2017 Arun Kumar M <arun@eabyas.in>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_program\form;
defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');
use \local_program\program as program;
use moodleform;
use local_program\local\querylib;
use context_system;

class classroom_completion_form extends moodleform {

    public function definition() {
        global $CFG, $DB, $USER;
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

        if($id > 0){
            $this->_form->_attributes['id'] ='editcompletion_form'.$id;
        }else{
            $this->_form->_attributes['id'] ='createcompletion_form';
        }
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

        $session_tracking=array('AND'=>get_string('classroom_allsessionscompletion', 'local_program'),
                                'OR'=>get_string('classroom_anysessioncompletion', 'local_program'),
                                'REQ'=> get_string('classroom_requiredsessionscompletion', 'local_program'));

        $mform->addElement('select', 'sessiontracking', get_string('sessiontracking', 'local_program'), $session_tracking, array());
        
        $sessions = array();
        $sessions = $this->_ajaxformdata['sessionids'];
        if (!empty($sessions)) {
            $sessions = $sessions;
        } else if ($id > 0) {
            $sessions = $DB->get_records_menu('local_program_classroomcompletion',
                array('id' => $id), 'id', 'id, sessionids');
        }
        /*if (!empty($sessions)) {
                if(is_array($sessions)){
                    $sessions=implode(',',$sessions);
                 }
                $sessions_sql = "SELECT id, name as fullname
                                        FROM {local_cc_course_sessions}
                                        WHERE bclcid = $bclcid AND id IN ($sessions)";
                $sessions = $DB->get_records_sql_menu($sessions_sql);
        }elseif (empty($sessions)) {
            $sessions_sql = "SELECT id, name as fullname
                                        FROM {local_cc_course_sessions}
                                        WHERE bclcid = $bclcid";
            $sessions = $DB->get_records_sql_menu($sessions_sql);
        }*/
        $sessions_sql = "SELECT id, name as fullname
                                        FROM {local_cc_course_sessions}
                                        WHERE bclcid = $bclcid";
        if (!empty($sessions)) {
                if(is_array($sessions)){
                    $sessions=implode(',',$sessions);
                 }
                $sessions_sql .= " AND id IN ($sessions)";
        }
        if($ccses_action == 'coursesessions'){
            $sessions_sql .= " AND sessiontype = 0";
        }elseif ($ccses_action == 'class_sessions') {
            $sessions_sql .= " AND sessiontype = 1";
        }elseif ($ccses_action == 'semsessions') {
            $sessions_sql .= " AND sessiontype = 2";
        }
        // print_object($sessions_sql);
        $sessions = $DB->get_records_sql_menu($sessions_sql);
        $options = array(
            'ajax' => 'local_program/form-options-selector',
            'multiple' => true,
            'data-contextid' => $context->id,
            'data-action' => 'classroom_completions_sessions_selector',
            'data-options' => json_encode(array('id' => $id, 'bclcid'=> $bclcid, 'ccses_action' => $ccses_action)),
        );
            
        $mform->addElement('autocomplete', 'sessionids', get_string('session_completion', 'local_program'), $sessions,$options);
        $mform->hideIf('sessionids', 'sessiontracking', 'neq','OR');
        
        $mform->addElement('text', 'requiredsessions', get_string('requiredsessions', 'local_program'), array());
        $mform->hideIf('requiredsessions', 'sessiontracking', 'neq','REQ');
        /*$mform->addRule('requiredsessions', get_string('missingrequiredsessions', 'local_program'), 'required', null, 'client');*/
        $mform->addRule('requiredsessions', null, 'numeric', null, 'client');
        $mform->addRule('requiredsessions', null, 'nonzero', null, 'client');
        $mform->setType('requiredsessions', PARAM_FLOAT);
        // $course_tracking=array(NULL=>get_string('classroom_donotcoursecompletion','local_program'),
        //                        'AND'=>get_string('classroom_allcoursescompletion', 'local_program'),
        //                         'OR'=>get_string('classroom_anycoursecompletion', 'local_program'));
                         
        // $mform->addElement('select', 'coursetracking', get_string('coursetracking', 'local_program'), $course_tracking, array());
        

        // $courses = array();
        // $courses = $this->_ajaxformdata['courseids'];
        // if (!empty($courses)) {
        //     $courses = $courses;
        // } else if ($id > 0) {
        //     $courses = $DB->get_records_menu('local_program_completion',
        //         array('id' => $id), 'id', 'id, courseids');
        // }
        // if (!empty($courses)) {
        //          if(is_array($courses)){
        //                  $courses=implode(',',$courses);
        //          }
        //          $courses_sql = "SELECT c.id,c.fullname as fullname FROM {course} as c JOIN {local_program_courses} as lcc on lcc.courseid=c.id where lcc.classroomid= $cid and lcc.courseid in ($courses)";
        //         $courses = $DB->get_records_sql_menu($courses_sql);
        // }elseif (empty($courses)) {
        //     $courses_sql = "SELECT c.id,c.fullname as fullname FROM {course} as c JOIN {local_program_courses} as lcc on lcc.courseid=c.id where lcc.classroomid= $cid ";
        //     $courses = $DB->get_records_sql_menu($courses_sql);
        // }
   
        // $options = array(
        //     'ajax' => 'local_program/form-options-selector',
        //     'multiple' => true,
        //     'data-contextid' => $context->id,
        //     'data-action' => 'classroom_completions_courses_selector',
        //     'data-options' => json_encode(array('id' => $id,'classroomid'=>$cid)),
        // );
        
        // $mform->addElement('autocomplete', 'courseids', get_string('course_completion', 'local_program'), $courses,$options);
        //$mform->disabledIf('courseids', 'coursetracking', 'neq','OR');

        $mform->disable_form_change_checker();
    }

    public function validation($data, $files) {
        global $CFG, $DB, $USER;
        $errors = parent::validation($data, $files);
        if (isset($data['sessiontracking']) && $data['sessiontracking'] == "OR" && isset($data['sessionids']) && empty($data['sessionids'])) {
            $errors['sessionids'] = get_string('select_sessions', 'local_program');
        }
        if (isset($data['coursetracking']) && $data['coursetracking'] == "OR" && isset($data['courseids']) && empty($data['courseids'])) {
            $errors['courseids'] = get_string('select_courses', 'local_program');
        }
        return $errors;
    }
}

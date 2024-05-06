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
 * Sessions in curriculum Semester Course
 *
 * @package    local_curriculum
 * @copyright  2017 M Arun Kumar <arun@eabyas.in>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once($CFG->dirroot.'/local/program/lib.php');
require_login();
use local_program\program;

global $PAGE, $CFG, $DB;
$curriculumid = required_param('ccid', PARAM_INT);
$semesterid = optional_param('semesterid', 0, PARAM_INT);
$bclcid = optional_param('bclcid', 0, PARAM_INT);
$programid = required_param('programid', PARAM_INT);
$yearid = required_param('yearid', PARAM_INT);
$courseid = optional_param('courseid', 0, PARAM_INT);
$ccses_action = optional_param('ccses_action', '', PARAM_RAW);
$action = optional_param('action', '', PARAM_RAW);
if(empty($action)){
    $action = 'upcomingsessions';
} else {
    $action = $action;
}
require_login();
$systemcontext = context_system::instance();
$PAGE->set_context($systemcontext);
$url = new moodle_url($CFG->wwwroot . '/local/program/sessions.php', array('ccses_action' => $ccses_action,'bclcid' => $bclcid, 'ccid' => $curriculumid, 'semesterid' => $semesterid, 'programid' => $programid, 'yearid' => $yearid, 'courseid' => $courseid));
if($ccses_action == "semsessions"){
    $semester = $DB->get_field('local_curriculum_semesters', 'semester', array('id' => $semesterid, 'curriculumid' => $curriculumid, 'programid' => $programid, 'yearid' => $yearid));
    // $semname = $DB->get_field('course', 'fullname', array('id' => $courseid));
    $classname = $semester; 
    $PAGE->set_heading(get_string('semestersessions', 'local_program', $semester));
}elseif($ccses_action == "class_sessions") {
    $classname = $DB->get_field('local_cc_semester_classrooms', 'classname', array('id' => $bclcid, 'semesterid' => $semesterid, 'curriculumid' => $curriculumid));
    // $coursename = $DB->get_field('course', 'fullname', array('id' => $courseid));
    $classname = $classname;
    $PAGE->set_heading(get_string('sessionsclassrooms', 'local_program', $classname));
}else{
    $courseid = $DB->get_field('local_cc_semester_courses', 'courseid', array('id' => $bclcid));
    $coursename = $DB->get_field('course', 'fullname', array('id' => $courseid));
    $classname = $coursename;
    $PAGE->set_heading(get_string('sessionscourses', 'local_program', $coursename));
}
// $curriculum = $DB->get_record('local_curriculum', array('id' => $curriculumid));
if($programid){
    $programname = $DB->get_field('local_program', 'fullname', array('id' => $programid));//Fetching program name//
}
$PAGE->requires->js_call_amd('local_program/ajaxforms', 'load');
if (!is_siteadmin() && !has_capability('local/program:createprogram', $systemcontext) && has_capability('local/program:takesessionattendance', $systemcontext)) {
    $PAGE->requires->js_call_amd('local_program/program', 'SessionDatatable',
                    array(array('curriculumstatus' => -1, 'curriculumid' => $curriculumid,
                        'semesterid' => $semesterid, 'bclcid' => $bclcid, 'programid' => $programid, 'yearid' => $yearid, 'courseid' => $courseid, 'ccses_action' => $ccses_action, 'action'=>$action)));
} else {
    $PAGE->requires->js_call_amd('local_program/program', 'SessionDatatable',
                    array(array('curriculumstatus' => -1, 'curriculumid' => $curriculumid,
                        'semesterid' => $semesterid, 'bclcid' => $bclcid, 'programid' => $programid, 'yearid' => $yearid, 'courseid' => $courseid, 'ccses_action' => $ccses_action, 'action'=>$action)));
}
$PAGE->set_url($url);
$PAGE->set_title($classname);
$PAGE->set_pagelayout('admin');
if(is_siteadmin() || has_capability('local/program:createprogram', $systemcontext) || has_capability('local/costcenter:manage_owndepartments', $systemcontext) || has_capability('local/program:takesessionattendance', $systemcontext)) {
    $PAGE->navbar->add(get_string("pluginname", 'local_program'), new moodle_url('/local/program/index.php'));
}
$PAGE->navbar->add($programname, new moodle_url('/local/program/view.php', array('ccid' => $curriculumid,'prgid' => $programid)));
$PAGE->navbar->add($classname, $url);
$renderer = $PAGE->get_renderer('local_program');
$PAGE->requires->jquery();
// $PAGE->requires->js('/blocks/achievements/js/jquery-ui.min.js',true);
// $PAGE->requires->js('/local/program/js/tabs_script.js',true);// Commented by harish for stopping js issues
// $PAGE->requires->jquery_plugin('ui');
$PAGE->requires->jquery_plugin('ui-css');
// $PAGE->requires->js('/blocks/achievements/js/jquery.dataTables.min.js',true);
echo $OUTPUT->header();
$stable = new stdClass();
$stable->thead = true;
$stable->start = 0;
$stable->length = -1;
$stable->search = '';
$bclcdata = new stdClass();
$bclcdata->curriculumid = $curriculumid;
$bclcdata->semesterid = $semesterid;
$bclcdata->bclcid = $bclcid;
$bclcdata->programid = $programid;
$bclcdata->yearid = $yearid;
$bclcdata->courseid = $courseid; 
$bclcdata->ccses_action = $ccses_action;

$curriculumuser = $DB->record_exists('local_curriculum_users', array('curriculumid' => $bclcdata->curriculumid, 'userid' => $USER->id));
$userview = false;
$enrolmentpending = false;
if ($curriculumuser && !is_siteadmin() && !has_capability('local/program:createprogram', $systemcontext)) {
    $bcsemester = new stdClass();
    $bcsemester->curriculumid = $bclcdata->curriculumid;
    $bcsemester->semesterid = $bclcdata->semesterid;
    $notcmptlcourses = (new program)->mynextsemestercourses($bcsemester);
    if (!empty($notcmptlcourses)) {
        $coursesql = "SELECT *
                        FROM {course} c
                        JOIN {local_cc_semester_courses} blc ON blc.courseid = c.id
                       WHERE blc.id = $notcmptlcourses[0]";
        $course = $DB->get_record_sql($coursesql);
        unset($notcmptlcourses[0]);
        if (!empty($notcmptlcourses) && array_search($bclcid, $notcmptlcourses) !== false) {
            echo '<div class="alert alert-warning">Enrolment will open post completion of ' . $course->fullname . ' course!</div>';
            $enrolmentpending = true;
        }
    }
    $userview = true;
}
$classroomcompletion = $DB->get_record('local_classroom_completion',array('classroomid' => $bclcid));

if($classroomcompletion){
    if($classroomcompletion->sessiontracking == 'AND'){
        $textmsg = 'To complete this classroom need to complete all the sessions';
    }else if($classroomcompletion->sessiontracking == 'OR'){
        if($classroomcompletion->sessionids){
            $nameslist = $DB->get_records_sql_menu("SELECT id,name FROM {local_cc_course_sessions} WHERE id IN ($classroomcompletion->sessionids)");
        }
        $names = implode(',', $nameslist);
        $textmsg = 'To complete this classroom need to complete '.$names.'';
    }else if($classroomcompletion->sessiontracking == 'REQ'){
        $textmsg = 'To complete this classroom need to complete any '.$classroomcompletion->requiredsessions.' sessions';
    }
    $msg = '<b>Classroom Completion Criteria:</b>'.$textmsg.'';
}else{
    $msg = '<b>No Classroom Completion Criteria for this classroom:</b> But to complete this classroom need to complete any one session';
}
echo '<div class="alert alert-info text-center">'.$msg.'</div>';
if(is_siteadmin() || has_capability('local/program:createprogram', $systemcontext) || has_capability('local/costcenter:manage_owndepartments', $systemcontext) || has_capability('local/program:takesessionattendance', $systemcontext)) {
        // $PAGE->requires->js_call_amd('local_program/program', 'SessionDatatable',
        //             array(array('curriculumstatus' => -1, 'curriculumid' => $curriculumid,
        //                 'semesterid' => $semesterid, 'bclcid' => $bclcid)));
    echo $renderer->viewcurriculumsessionstabs($bclcdata, $stable, $userview, $enrolmentpending, $action);
    echo $renderer->viewcurriculumsessions($bclcdata, $stable, $userview, $enrolmentpending, $action);
} else {
    echo $renderer->viewcurriculumsessions($bclcdata, $stable, $userview, $enrolmentpending, $action);
}
echo $OUTPUT->footer();

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
 * Handle ajax requests in curriculum
 *
 * @package    local_curriculums
 * @copyright  2018 Arun Kumar M {arun@eabyas.in}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define('AJAX_SCRIPT', true);
require_once(dirname(__FILE__) . '/../../config.php');
require_once("lib.php");
require_once($CFG->dirroot.'/local/curriculum/lib.php');
global $DB, $CFG, $USER, $PAGE;

$action = required_param('action', PARAM_ACTION);
$curriculumid = optional_param('curriculumid', 0, PARAM_INT);
$semesterid = optional_param('semesterid', 0, PARAM_INT);
$bclcid = optional_param('bclcid', 0, PARAM_INT);
$programid = optional_param('programid', 0, PARAM_INT);
$faculty = optional_param('faculty', 0, PARAM_INT);
$yearid = optional_param('yearid', 0, PARAM_INT);
$courseid = optional_param('courseid', 0, PARAM_INT);
$ccses_action = optional_param('ccses_action', '', PARAM_RAW);
$draw = optional_param('draw', 1, PARAM_INT);
$start = optional_param('start', 0, PARAM_INT);
$length = optional_param('length', 10, PARAM_INT);
$search = optional_param_array('search', '', PARAM_RAW);
$tab = optional_param('tab', '', PARAM_RAW);
$curriculumstatus = optional_param('curriculumstatus', -1, PARAM_INT);
$curriculummodulehead = optional_param('curriculummodulehead', false, PARAM_BOOL);
$departmentid = optional_param('departmentid', '', PARAM_INT);
$cat = optional_param('categoryname', '', PARAM_RAW);
$yearid = optional_param('yearid', 0, PARAM_INT);
$type = optional_param('type',0, PARAM_RAW);
$options = optional_param('options', '', PARAM_RAW);
$stablehead = optional_param('stable',1, PARAM_INT);
$programid = optional_param('programid', 0, PARAM_INT);
$curriculumid = optional_param('curriculumid', 0, PARAM_INT);
$costcenter = optional_param('costcenter', 0, PARAM_INT);
$location = optional_param('location', 0, PARAM_INT);
$room = optional_param('room', 0, PARAM_INT);
$locationvalue = optional_param('locationvalue', 0, PARAM_INT);
$context = context_system::instance();
$department = optional_param('department', '', PARAM_INT);

$course = optional_param('course', 0, PARAM_INT);
$switch_type = optional_param('switch_type', '', PARAM_TEXT);

require_login();
$PAGE->set_context($context);
$renderer = $PAGE->get_renderer('local_program');
$userlib = new local_users\functions\userlibfunctions();
switch ($action) {
    case 'viewcurriculums':
        $stable = new stdClass();
        $stable->thead = false;
        $stable->search = $search['value'];
        $stable->start = $start;
        $stable->length = $length;
        $stable->curriculumstatus = $curriculumstatus;
        $return = $renderer->viewcurriculums($stable);
    break;
    case 'viewcurriculumsessions':
        $stable = new stdClass();
        $stable->search = $search['value'];
        $stable->start = $start;
        $stable->length = $length;
        if ($curriculummodulehead) {
            $stable->thead = true;
        } else {
            $stable->thead = false;
        }
        $bclcdata = new stdClass();
        $bclcdata->curriculumid = $curriculumid;
        $bclcdata->semesterid = $semesterid;
        $bclcdata->bclcid = $bclcid;
        $bclcdata->programid = $programid;
        $bclcdata->yearid = $yearid;
        $bclcdata->ccses_action = $ccses_action;
        // print_object($bclcdata);exit;
        $return = $renderer->viewcurriculumsessions($bclcdata, $stable, false, false, $tab);
    break;
    case 'curriculumsbystatus':
        $stable = new stdClass();
        $stable->thead = true;
        $stable->start = 0;
        $stable->length = -1;
        $stable->search = '';
        $stable->curriculumstatus = $curriculumstatus;
        $return = $renderer->viewcurriculums($stable);
    break;
    case 'viewcurriculumcourses':
        $return = $renderer->viewcurriculumcourses($curriculumid);
    break;
    case 'viewcurriculumusers':
        $stable = new stdClass();
        $stable->search = $search['value'];
        $stable->start = $start;
        $stable->length = $length;
        $stable->curriculumid = $curriculumid;
        $stable->yearid = $yearid;
        if ($curriculummodulehead) {
            $stable->thead = true;
        } else {
            $stable->thead = false;
        }
        $return = $renderer->viewcurriculumusers($stable);
    break;
    case 'viewprogramenrolsers':
        $stable = new stdClass();
        $stable->search = $search['value'];
        $stable->start = $start;
        $stable->length = $length;
        $stable->curriculumid = $curriculumid;
        $stable->yearid = $yearid;
        if ($curriculummodulehead) {
            $stable->thead = true;
        } else {
            $stable->thead = false;
        }
        $return = $renderer->viewprogramenrolsers($stable);
    break;
    case 'managecurriculumcategory':
    $rec = new stdClass();
    $rec->fullname = $cat;
    $rec->shortname = $cat;
    if ($rec->id) {
        $DB->update_record('local_curriculum_categories', $rec);
    } else {
        $DB->insert_record('local_curriculum_categories', $rec);
    }
    break;
    case 'curriculumlastchildpopup':
        $stable = new stdClass();
        $stable->search = $search['value'];
        $stable->start = $start;
        $stable->length = $length;
        if ($curriculummodulehead) {
            $stable->thead = true;
        } else {
            $stable->thead = false;
        }
        $return = $renderer->viewcurriculumlastchildpopup($curriculumid, $stable);
    break;
    case 'viewcurriculumrequested_users_tab':
         $curriculum = $DB->get_records('local_request_records', array('compname' => 'curriculum','componentid' =>
            $curriculumid));

        $output = $PAGE->get_renderer('local_request');
        $component = 'program';
        if ($curriculum) {
            $return = $output->render_requestview(new local_request\output\requestview($curriculum, $component));
        } else {
            $return = '<div class="alert alert-info">'.get_string('requestavail', 'local_program').'</div>';
        }
    break;
    case 'curriculumyearsemesters':
         $return = $renderer->viewcurriculumsemesteryear($curriculumid, $yearid);
    break;

    case 'viewcurriculumprograms':
        $stable = new stdClass();
        $stable->search = $search['value'];
        $stable->start = $start;
        $stable->length = $length;
        $stable->thead = false;
        $return = $renderer->viewcurriculumprograms($stable,$type,$options);
    break;
    case 'viewcoursefaculty':
        $curriculum = $DB->get_record_sql('SELECT cc.*
                                     FROM {local_curriculum} cc
                                     JOIN {local_cc_semester_courses} ccsc ON ccsc.curriculumid = cc.id
                                    WHERE ccsc.courseid = :courseid AND ccsc.semesterid = :semesterid AND ccsc.yearid = :yearid ', array('yearid' => $yearid, 'semesterid' => $semesterid, 'courseid' => $courseid));
        $stable = new stdClass();
        $stable->search = $search['value'];
        $stable->start = $start;
        $stable->length = $length;
        $stable->curriculumid = $curriculum->id;
        $stable->yearid = $yearid;
        $stable->semesterid = $semesterid;
        $stable->courseid = $courseid;
        if ($curriculummodulehead) {
            $stable->thead = true;
        } else {
            $stable->thead = false;
        }
        $return = $renderer->viewcoursefaculty($stable);
    break;
    case 'masterprogramchildpopup':
        $stable = new stdClass();
        if(!$stablehead){
            $stable->thead = false;
        }else{
            $stable->thead = true;
        }
        $stable->search = $search['value'];
        $stable->start = $start;
        $stable->length = $length;

        $stable->curriculumstatus = $curriculumstatus;
        $return = $renderer->masterprogramchildpopup($stable,$type,$options,$curriculumid);
    break;
    case 'programstatusvalidation':
        $programstructure = $renderer->programstatusvalidation($programid, $curriculumid, $costcenter);
        $return = $programstructure;
    break;
    case 'publishprogram':
        $publishstatus_sql = "UPDATE {local_program} SET publishstatus = 1
                                  WHERE id = :programid";
        $record = $DB->execute($publishstatus_sql, array('programid' => $programid));
        if($record){
            /*$return = '<div class="modal-body">
                        <div class="thank-you-pop">
                            <p>Program published Successfully</p>
                        </div>
                    </div>';*/// Commented by Harish for IUMS-318 //
            $return = true;
        }
    case 'roomlocation':
            $locationlist = find_location($costcenter);
            echo json_encode(['data' => $locationlist]);
    exit;
    break;
    case 'findroom':
            $locationlist = find_rooms($location);
           // print_object($locationlist);
            echo json_encode(['data' => $locationlist]);
    exit;
    break;
    case 'faculties':
            $facultylist = find_faculties($costcenter);
        //  print_object($facultylist);
            echo json_encode(['data' => $facultylist]);
    exit;
    break;
    case 'displaymaxvalue':

            $maxcapacity = find_max_capacity($room);
          //print_object($maxcapacity);exit;
            echo json_encode(['data' => $maxcapacity]);
    exit;
    break;
    case 'costcenterdata':

            $facultylist = find_costcenterfaculties($costcenter);
            $departments = find_departments($costcenter);
            $curriculums = find_curriculums($costcenter,$department);
            $departmentlist = $userlib->find_departments_list($costcenter);
            /*echo json_encode(['faculties' => $facultylist, 'departments' => $departments, 'curriculums' => $curriculums]);*/
            echo json_encode(['faculties' => $facultylist, 'colleges' => $departmentlist['nonuniv_dep'], 'departments' => $departmentlist['univ_dep']/*, 'departments' => $departments, 'curriculums' => $curriculums*/]);
    exit;
    break;
    case 'facultydepts':
            $departments = find_facultydepartments($costcenter, $faculty);
            echo json_encode(['departments' => $departments]);
    exit;
    break;
    case 'deptcurriculums':
            $curriculums = find_curriculums($costcenter,$department);
            echo json_encode(['curriculums' => $curriculums]);
    exit;
    break;
    case 'displaylocations':
            $institute_type = find_locations_basedon_type($locationvalue,$programid);
            echo json_encode(['data' => $institute_type]);
    exit;
    break;
    case 'findfaculty':
            $faculties = findfaculty($programid);
            echo json_encode(['data' => $faculties]);
    exit;
    break;
}

echo json_encode($return);

if($switch_type==0){
    $id=$DB->get_record('local_cc_semester_courses',array('courseid'=>$course));
    $sql="UPDATE {local_cc_semester_courses} SET coursetype=0 WHERE id=:id";
    $DB->execute($sql, array('id' => $id->id));
}elseif($switch_type==1){
     $id=$DB->get_record('local_cc_semester_courses',array('courseid'=>$course));
     $sql="UPDATE {local_cc_semester_courses} SET coursetype=1 WHERE id=:id";
    $DB->execute($sql, array('id' => $id->id));
}

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
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define('AJAX_SCRIPT', true);
require_once(dirname(__FILE__) . '/../../config.php');
require_once('lib.php');
require_once($CFG->dirroot.'/local/curriculum/lib.php');
global $DB, $CFG, $USER, $PAGE;
$context = context_system::instance();

$PAGE->set_context($context);
$renderer = $PAGE->get_renderer('local_curriculum');


$action = required_param('action', PARAM_ACTION);
$curriculumid = optional_param('curriculumid', 0, PARAM_INT);
$semesterid = optional_param('semesterid', 0, PARAM_INT);
$bclcid = optional_param('bclcid', 0, PARAM_INT);
$courseid = optional_param('courseid', 0, PARAM_INT);
$draw = optional_param('draw', 1, PARAM_INT);
$start = optional_param('start', 0, PARAM_INT);
$length = optional_param('length', 10, PARAM_INT);
$search = optional_param_array('search', '', PARAM_RAW);
$tab = optional_param('tab', '', PARAM_RAW);
$curriculumstatus = optional_param('curriculumstatus', -1, PARAM_INT);
 $curriculummodulehead = optional_param('curriculummodulehead', false, PARAM_BOOL);
$cat = optional_param('categoryname', '', PARAM_RAW);
$yearid = optional_param('yearid', 0, PARAM_INT);
$type = optional_param('type',0, PARAM_RAW);
$options = optional_param('options', '', PARAM_RAW);
$stablehead = optional_param('stable',1, PARAM_INT);
$programid = optional_param('programid', 0, PARAM_INT);
$curriculumid = optional_param('curriculumid', 0, PARAM_INT);
$costcenter = optional_param('costcenter', 0, PARAM_INT);
$context = context_system::instance();
$departmentid = optional_param('departmentid', 0, PARAM_INT);
$course = optional_param('course', 0, PARAM_INT);
$switch_type = optional_param('switch_type', '', PARAM_TEXT);
$userlib = new local_users\functions\userlibfunctions();

switch ($action) {

 case 'viewcoursefaculty':
        $curriculum = $DB->get_record_sql('SELECT cc.*
                                     FROM {local_curriculum} cc
                                     JOIN {local_cc_semester_courses} ccsc ON ccsc.curriculumid = cc.id
                                    WHERE ccsc.courseid = :courseid AND ccsc.semesterid = :semesterid AND ccsc.yearid = :yearid ', array('yearid' => $yearid, 'semesterid' => $semesterid, 'courseid' => $courseid));
        $stable = new stdClass();
        $stable->search = $search;
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
        $return = $renderer->viewcoursefacultydata($stable);
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
     case 'publishprogram':
        $publishstatus_sql = "UPDATE {local_program} SET publishstatus = 1 
                                  WHERE id = :programid";
        $record = $DB->execute($publishstatus_sql, array('programid' => $programid));
        if($record){
            $return = '<div class="modal-body">
                        <div class="thank-you-pop">
                            <p>Curriculum published Successfully</p>
                        </div>
                    </div>';
             $DB->execute("UPDATE {local_curriculum} SET curriculum_publish_status = 1 WHERE id = ".$curriculumid);

        }
    break;
     case 'programstatusvalidation':

        $programstructure = $renderer->programstatusvalidation($id, $curriculumid, $costcenter);
        $return = $programstructure; 
    break;
     case 'curriculumyearsemesters':
         $return = $renderer->viewcurriculumsemesteryear($curriculumid, $yearid);
    break;
    //  case 'departmentlist':
    //     // $department = find_departments($costcenter);
    //     // foreach ($department as $key => $value) {
    //     //     $department[0]->id = 0;
    //     //     $department[0]->fullname = 'All';
    //     // }
    //     // $departmentlist = $userlib->find_departments_list($costcenter);
    //     // foreach ($departmentlist as $key => $value) {
    //     //     $departmentlist[0]->id = 0;
    //     //     $departmentlist[0]->fullname = 'All';
    //     // }
    //     // echo json_encode(['colleges' => $departmentlist['nonuniv_dep'], 'departments' => $departmentlist['univ_dep']/*, 'departments' => $departments, 'curriculums' => $curriculums*/]);
    //    // echo json_encode(['data' => $departmentlist]);
    // exit;
    // break;
    case 'courseslist':
        $courseslist = find_courses($departmentid,$semesterid,$yearid,$curriculumid);
        echo json_encode(['data' => $courseslist]);
    exit;
    break;


}
echo json_encode($return);


if($switch_type==0){
    $id=$DB->get_record('local_cc_semester_courses',array('courseid'=>$course));
    $sql="UPDATE {local_cc_semester_courses} SET coursetype = 0 WHERE id=:id";
    $DB->execute($sql, array('id' => $id->id));
    
}elseif($switch_type==1){
     $id=$DB->get_record('local_cc_semester_courses',array('courseid'=>$course));
     $sql="UPDATE {local_cc_semester_courses} SET coursetype = 1 WHERE id=:id";
    $DB->execute($sql, array('id' => $id->id)); 
}

<?php

define('AJAX_SCRIPT', true);
require_once(dirname(__FILE__) . '/../../config.php');
global $DB, $OUTPUT;

$systemcontext = context_system::instance();
$PAGE->set_context($systemcontext);

$requestData= $_REQUEST;

$aColumns = array('');
$sIndexColumn = "id";
$sTable = "ajax";
$input =& $_GET;
$sLimit = "";
$totalprograms =0;
 if($DB->record_exists('local_courseenrolments', ['mdluserid' => $USER->id])){
    // $countsql = "SELECT  count(ce.programid) ";// Commented by Harish //
    $countsql = "SELECT  ce.programid ";
    $selectsql  = "SELECT ce.id, ce.courseid, ce.programid, ce.programname, sisp.duration, sisp.programcode, sisp.runningfromyear ";
    $formsql   =" FROM {local_courseenrolments} AS ce
                        JOIN {local_sisprograms} AS sisp ON ce.programid = sisp.id 
                        WHERE ce.mdluserid = $USER->id";
        
        // print_object($requestData);
if ( $requestData['sSearch'] != '' ) {
    // $formsql .= " and ((p.id LIKE '%".$requestData['sSearch']."%'
    //                                              or (p.fullname LIKE '%".$requestData['sSearch']."%')
    //                                              or (p.duration LIKE '%".$requestData['sSearch']."%')
    //                                              or (p.year LIKE '%".$requestData['sSearch']."%')
    //                                          or (p.shortcode) LIKE '%".$requestData['sSearch']."%'))
    $search_value = $requestData['sSearch'];
    $formsql .= " and ((ce.programname LIKE '%$search_value%' ) 
                        OR (sisp.duration LIKE '%$search_value%') 
                        OR (sisp.runningfromyear LIKE '%$search_value%')
                        OR (sisp.programcode LIKE '%$search_value%')
                        )";

    }
// echo $selectsql.$formsql;
    $groupsql .= " group by ce.programid";
    $totalprograms = count($DB->get_records_sql($countsql.$formsql.$groupsql));
    $formsql .= " group by ce.programid order by ce.programid desc";
    if ( isset( $requestData['iDisplayStart'] ) && $requestData['iDisplayLength'] != '-1' ) {
        $formsql .= " LIMIT ".intval( $requestData['iDisplayStart'] ).", ".intval( $requestData['iDisplayLength'] );
    }
    $programs = $DB->get_records_sql($selectsql.$formsql);
}
  $data = array();
  if(!empty($programs)){
            foreach($programs as $program){
                $row = array();
                $line = array();
                $trainer_program_courses = "SELECT c.id, c.fullname
                        FROM {course} AS c
                        JOIN {local_courseenrolments} AS ce ON ce.courseid = c.id WHERE ce.mdluserid = $USER->id AND ce.programid = $program->programid";
                $trainercourses = $DB->get_records_sql($trainer_program_courses);
                $line['programname'] = $program->programname;
                if(!empty($trainercourses)){
                    $line['coursesenable'] = true;
                    foreach($trainercourses as $trainercourse){
                        $coursecontext = context_course::instance($trainercourse->id);
                        $studentroleid = $DB->get_field('role', 'id', array('shortname' => 'student'));
                        $course_users_count = count(get_role_users($studentroleid, $coursecontext));
                        $line['courses'][] = array('coursename' => $trainercourse->fullname,'enrolled_users_count' => $course_users_count, 'courseid'=>$trainercourse->id);
                    }
                }
                $line['programid'] = $program->programid;
                $line['programduration'] = $program->duration ? $program->duration : 'N/A';
                $line['programshortcode'] = $program->programcode ? $program->programcode : 'N/A';
                $line['programyear'] = $program->runningfromyear ? $program->runningfromyear : 'N/A';
                $row[] = $OUTPUT->render_from_template('block_faculty_dashboard/content', $line);
                $data[] = $row;
            }
        }
$iTotal = $totalprograms; 
$iFilteredTotal = $iTotal;
$output = array(
    "sEcho"                => intval($input['sEcho']),
    "iTotalRecords"        => $iTotal,
    "iTotalDisplayRecords" => $iFilteredTotal,
    "aaData"               => $data
);
echo json_encode($output);
 ?>

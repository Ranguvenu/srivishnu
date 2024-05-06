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
 if($DB->record_exists('local_cc_session_trainers', ['trainerid' => $USER->id])){
 	$countsql = "SELECT  count(p.id) ";
    $selectsql  = "SELECT p.id, p.fullname, p.duration, p.year, p.shortcode, p.curriculumsemester, cc.id AS curriculumid";
    $formsql   =" FROM {local_program} AS p
    	JOIN {local_curriculum} AS cc ON cc.program = p.id
        JOIN {local_cc_session_trainers} AS lcst ON lcst.programid = p.id WHERE lcst.trainerid = $USER->id";
	if ( $requestData['sSearch'] != '' ) {
		$search_value = $requestData['sSearch'];
		$formsql .= " and ((p.fullname LIKE '%$search_value%' ) 
							OR (p.duration LIKE '%$search_value%') 
							OR (p.year LIKE '%$search_value%')
							OR (p.shortcode LIKE '%$search_value%')
							)";

	}
	$totalprograms = $DB->count_records_sql($countsql.$formsql);   
	$formsql .= " order by p.id desc";
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
                        JOIN {local_cc_session_trainers} AS lcst ON lcst.courseid = c.id WHERE lcst.trainerid = $USER->id AND lcst.programid = $program->id";
                $trainercourses = $DB->get_records_sql($trainer_program_courses);
                $line['programname'] = $program->fullname;
                if(!empty($trainercourses)){
                    $line['coursesenable'] = true;
                    foreach($trainercourses as $trainercourse){
                        $coursecontext = context_course::instance($trainercourse->id);
                        $studentroleid = $DB->get_field('role', 'id', array('shortname' => 'student'));
                        $course_users_count = count(get_role_users($studentroleid, $coursecontext));
                        $line['courses'][] = array('coursename' => $trainercourse->fullname,'enrolled_users_count' => $course_users_count, 'courseid'=>$trainercourse->id);
                    }
                }
                if($program->curriculumsemester == 1){
                    $line['programlevel'] = get_string('undergraduation', 'block_faculty_dashboard');
                }else{
                    $line['programlevel'] = get_string('postgraduation', 'block_faculty_dashboard');
                }
                $line['programname'] = $program->fullname;
                $durationyear = $DB->get_field('local_curriculum','duration',array('id' => $program->curriculumid));
                $duration_format = $DB->get_field('local_curriculum','duration_format',array('id' => $program->curriculumid));
                if($duration_format == 'Y'){
                    $duration_format = 'Year(s)';
                }
                else{
                    $duration_format = 'Month(s)';
                }
                $duration = $durationyear." ".$duration_format;
                $line['programduration'] = $duration ? $duration : '--';
                $line['programyear'] = $program->year ? $program->duration : '--';
                $line['programshortcode'] = $program->shortcode;
                $line['programid'] = $program->id;
                $line['curriculumid'] = $program->curriculumid;
                $row[] = $OUTPUT->render_from_template('block_faculty_dashboard/content', $line);
                $data[] =$row;
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

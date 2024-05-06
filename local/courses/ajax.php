<?php
//added by Yamini for display difference for selling individual courses
require_once('../../config.php');
global $CFG, $OUTPUT,$PAGE, $DB;
$courseid = optional_param('id', 0, PARAM_INT);
$course = optional_param('course', 0, PARAM_INT);
 $switch_type = optional_param('switch_type', '', PARAM_TEXT);
if($course){

     $table='course';
	$result->id = $course;
	$res_status = $DB->get_field('course','sold_status',array('id' => $course));
	if($res_status == 0){
		$result->sold_status = 1;
	}else{
	$result->sold_status = 0;
     }
	$res = $DB->update_record($table, $result);
	$status = $DB->get_field('course','sold_status',array('id' => $course));
	
	if ($res == 1) {
	 	echo $status ;
	} 

}
if($courseid){
$table='course';
$result->id = $courseid;
$res_status = $DB->get_field('course','sold_status',array('id' => $course));
	if($res_status == 0){
		$result->sold_status = 1;
	}else{
	$result->sold_status = 0;
     }
$res = $DB->update_record($table, $result);
$status = $DB->get_field('course','sold_status',array('id' => $courseid));
	if ($res == 1) {
	 	echo $status;
	} 
}
	
?>
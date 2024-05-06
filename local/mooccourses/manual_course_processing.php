<?php
define('AJAX_SCRIPT', true);
require_once(dirname(__FILE__) . '/../../config.php');
global $DB, $PAGE,$USER,$CFG,$OUTPUT;

$systemcontext = context_system::instance();
$PAGE->set_context($systemcontext);
require_once($CFG->dirroot.'/local/mooccourses/lib.php');
$filterjson = optional_param('param', '',  PARAM_RAW);
$filterdata = json_decode($filterjson);
$department=$filterdata->department;
$sSearch = $requestData['search']; 
$systemcontext = context_system::instance();
$selectsql = "SELECT c.id, c.fullname, c.shortname, c.category,c.open_cost,c.open_points,c.open_costcenterid, c.open_departmentid, c.open_identifiedas, co.fullname as costcenter, cc.name FROM {course} AS c"; 
$countsql  = "SELECT count(c.id) FROM {course} AS c"; 

if(is_siteadmin()){
    $formsql = " JOIN {local_costcenter} AS co ON co.id = c.open_costcenterid 
                 JOIN {course_categories} AS cc ON cc.id = c.category";
}else if(has_capability('local/costcenter:manage_ownorganization',$systemcontext)){
    $formsql = " JOIN {local_costcenter} AS co ON co.id = c.open_costcenterid
               JOIN {course_categories} AS cc ON cc.id = c.category
               WHERE c.open_costcenterid = $USER->open_costcenterid";
}else if(has_capability('local/costcenter:manage_owndepartments',$systemcontext)){
    $formsql = " JOIN {local_costcenter} AS co ON co.id = c.open_costcenterid
               JOIN {course_categories} AS cc ON cc.id = c.category
               WHERE c.open_costcenterid = $USER->open_costcenterid 
               AND c.open_departmentid = $USER->open_departmentid";
}else{
    $formsql = " JOIN {local_costcenter} AS co ON co.id = c.open_costcenterid
               JOIN {course_categories} AS cc ON cc.id = c.category
               WHERE c.open_costcenterid = $USER->open_costcenterid 
               AND c.open_departmentid = $USER->open_departmentid";
}
if(!empty($filterdata->department)){
  $departments = $filterdata->department;
    $formsql .= " AND c.open_departmentid IN ($departments)";

}
$formsql .= " AND c.id>1 AND c.open_parentcourseid = 0 AND sold_status = 1";
if ( $requestData['sSearch'] != "" ) {
    $formsql .= " and ((c.fullname LIKE '%".$requestData['sSearch']."%')
                                                    or (c.shortname LIKE '%".$requestData['sSearch']."%')
                                                    or (co.fullname LIKE '%".$requestData['sSearch']."%')
                                                    or (cc.name LIKE '%".$requestData['sSearch']."%'))";
}

$totalcourses = $DB->count_records_sql($countsql.$formsql);
 if($totalcourses==0){
    $totalcourses=0;
  }
  else{
    $totalcourses=$advisorscount;
  }
$formsql .=" ORDER BY c.id DESC";
$courses = $DB->get_records_sql($selectsql.$formsql);
$data = array();

foreach ($courses as $course) {
  $row = [];
    // $organization = $DB->get_field('local_costcenter', 'fullname', array('id'=>$course->open_costcenterid));

    $dept = $DB->get_field('local_departments', 'name', array('id'=>$course->open_departmentid));
  
   
    
    $row[] = $course->shortname;
    $row[] = html_writer::tag('a', $course->fullname,array('href' =>$CFG->wwwroot. '/course/view.php?id='.$course->id));
    $row[] = ($course->costcenter) ? $course->costcenter : 'N/A';
    $row[] = $dept ? $dept : 'N/A';
    $row[] = $course->open_cost  ? $course->open_cost : 'N/A';
    $row[] = $course->open_points != NULL ? $course->open_points: 'N/A';
    $data[] = $row;
  }
$iTotal = $totalcourses;
$iFilteredTotal = $iTotal;
// print_object($data);exit;
$output = array(
    "sEcho" => intval($requestData['sEcho']),
    "iTotalRecords" => $iTotal,
    "iTotalDisplayRecords" => $iFilteredTotal,
    "aaData" => $data
);
echo json_encode($output);
?>

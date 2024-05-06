<?php
define('AJAX_SCRIPT', true);
require_once(dirname(__FILE__).'/../../config.php');
global $DB,$OUTPUT,$CFG,$USER,$PAGE;
$PAGE->requires->jquery();
$PAGE->requires->jquery_plugin('ui');
$PAGE->set_pagelayout('admin');  
$PAGE->set_url('/local/sisprograms/courses_processings.php');
require_login();

$systemcontext = context_system::instance();
$PAGE->set_context($systemcontext);

echo $OUTPUT->header();
$requestData= $_REQUEST;

$aColumns = array('');
$sIndexColumn = "id";
$sTable = "ajax";
$input =& $_GET;
$sLimit = "";
$countsql = "SELECT count(soc.id) ";
$allsql = "SELECT soc.id, soc.coursecode, course.fullname as coursename, lsp.fullname as programname, lc.fullname as costcentername
            ";

    if(is_siteadmin() || has_capability('local/costcenter:manage_multiorganizations',$systemcontext) || has_capability('local/costcenter:manage_ownorganization',$systemcontext)){
        $sql .= " FROM {local_sisonlinecourses} AS soc
                  JOIN {course} AS course ON course.id = soc.courseid 
                  JOIN {local_sisprograms} AS lsp ON lsp.id = soc.programid
                  JOIN {local_costcenter} AS lc ON lc.id = soc.costcenterid
                  WHERE lc.visible = 1";
    }
    if(!is_siteadmin() && has_capability('local/costcenter:manage_ownorganization',$systemcontext)){
        $sql .= " AND soc.costcenterid = $USER->open_costcenterid";
    }

if ( $requestData['sSearch'] != "" ) {
	$sql .= " and ((soc.coursecode LIKE '%".$requestData['sSearch']."%')
													or (course.fullname LIKE '%".$requestData['sSearch']."%')
													or (lsp.fullname LIKE '%".$requestData['sSearch']."%')
													or (lc.fullname LIKE '%".$requestData['sSearch']."%'))";
}
// print_object($countsql.$sql);
$siscoursescount = $DB->count_records_sql($countsql.$sql);
// print_object($siscoursescount);exit;
$sql .= " order by soc.id desc";

if ( isset( $input['iDisplayStart'] ) && $input['iDisplayLength'] != '-1' ) {
				$sql .= " LIMIT ".intval( $input['iDisplayStart'] ).", ".intval( $input['iDisplayLength'] );
}
$siscourses = $DB->get_records_sql($allsql.$sql);
// print_object($sisprograms);exit;
        $data = array();

        foreach ($siscourses as $course) {
            $line = array();
            $line[] = $course->coursename;
            $line[] = $course->coursecode;
            /*$line[] = $DB->get_field('local_sisprograms','fullname',array('id' => $course->programid));
            $line[] = $DB->get_field('local_costcenter', 'fullname', array('id' => $course->costcenterid));*/
            $line[] = $course->programname;
            $line[] = $course->costcentername;
            $data[] = $line;
        }

$iTotal = $siscoursescount; 
$iFilteredTotal = $iTotal;

$output = array(
    "sEcho"                => intval($input['sEcho']),
    "iTotalRecords"        => $iTotal,
    "iTotalDisplayRecords" => $iFilteredTotal,
    "aaData"               => $data
);
echo json_encode($output);
?>
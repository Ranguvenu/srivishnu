<?php
define('AJAX_SCRIPT', true);
require_once(dirname(__FILE__).'/../../../config.php');
global $PAGE;
global $DB,$OUTPUT,$CFG,$USER;
$PAGE->requires->jquery();
$PAGE->requires->jquery_plugin('ui');
$PAGE->set_pagelayout('admin');  
$PAGE->set_url('/local/users/error_processing.php');
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
if((has_capability('local/costcenter:manage_multiorganizations',$systemcontext) || is_siteadmin())){
	$sql="SELECT * FROM {local_syncerrors} ls where 1=1";
} else {
	$sql="SELECT * FROM {local_syncerrors} ls where 1=1 AND modified_by = $USER->id";
}
//print_object($sync_errors);
//Paging   //Filtering of data
if ( $requestData['sSearch'] != "" ) {
	$sql .= " and ((ls.idnumber LIKE '%".$requestData['sSearch']."%')
													or (ls.email LIKE '%".$requestData['sSearch']."%')
													or (ls.mandatory_fields LIKE '%".$requestData['sSearch']."%')
													or (ls.error LIKE '%".$requestData['sSearch']."%')
												or (FROM_UNIXTIME(ls.date_created) LIKE '%".$requestData['sSearch']."%')
																									) ";
}
    
$sql .= " order by id desc";
if ( isset( $input['iDisplayStart'] ) && $input['iDisplayLength'] != '-1' ) {
				$sql .= " LIMIT ".intval( $input['iDisplayStart'] ).", ".intval( $input['iDisplayLength'] );
}

if((has_capability('local/costcenter:manage_multiorganizations',$systemcontext) || is_siteadmin())){
	$count_sql="SELECT * FROM {local_syncerrors} ls where 1=1";
} else {
	$count_sql="SELECT * FROM {local_syncerrors} ls where 1=1 AND modified_by = $USER->id";
}

if ( $requestData['sSearch'] != "" ) {
	$count_sql .= " and ((ls.idnumber LIKE '%".$requestData['sSearch']."%')
												or (ls.email LIKE '%".$requestData['sSearch']."%')
												or (ls.mandatory_fields LIKE '%".$requestData['sSearch']."%')
												or (ls.error LIKE '%".$requestData['sSearch']."%')
												or (FROM_UNIXTIME(ls.date_created) LIKE '%".$requestData['sSearch']."%')
																								) ";
}
$sync_errors1=$DB->get_records_sql($count_sql);

$sync_errors=$DB->get_records_sql($sql);
      
$data=array();
foreach($sync_errors as $sync_error) {
	$list=array();
	// $list[]=$sync_error->sync_file_name?$sync_error->sync_file_name: '-';
// $list[] = 'test'; 
	$list[]=$sync_error->idnumber?$sync_error->idnumber: '-';
	$list[]=$sync_error->email?$sync_error->email: '-';
	$str=$sync_error->mandatory_fields;
	$exp = explode(',',$str);
	$exp = implode('<br><br>',$exp);
	$list[]= $exp;
	$err=$sync_error->error;	
	$exp1 = explode(',',$err);
	$expe = implode('<br><br>',$exp1);
	$list[]= $expe;
	$date=$sync_error->date_created;
	
	$list[]=fullname($DB->get_record('user',array('id'=>$sync_error->modified_by)));;
	$list[]=date('Y-m-d h:i:sa',$date);
	$data[]=$list;
}
$iTotal = count($sync_errors1); 
$iFilteredTotal = $iTotal;

$output = array(
    "sEcho"                => intval($input['sEcho']),
    "iTotalRecords"        => $iTotal,
    "iTotalDisplayRecords" => $iFilteredTotal,
    "aaData"               => $data
);
echo json_encode($output);
 ?>

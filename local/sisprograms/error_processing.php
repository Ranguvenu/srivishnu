<?php
define('AJAX_SCRIPT', true);
require_once(dirname(__FILE__).'/../../config.php');
global $DB,$OUTPUT,$CFG,$USER,$PAGE;
$PAGE->requires->jquery();
$PAGE->requires->jquery_plugin('ui');
$PAGE->set_pagelayout('admin');  
$PAGE->set_url('/local/sisprograms/error_processing.php');
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
$countsql = "SELECT count(ls.id) ";
$allsql="SELECT ls.* ";

/*if((has_capability('local/costcenter:manage_multiorganizations',$systemcontext) || is_siteadmin())){
	$sql=" FROM {local_sissyncerrors} ls where 1=1";
} else {
	$sql=" FROM {local_sissyncerrors} ls where 1=1 AND modified_by = $USER->id";
}*/
	if(is_siteadmin() || has_capability('local/costcenter:manage_multiorganizations',$systemcontext) || has_capability('local/costcenter:manage_ownorganization',$systemcontext)){
       $sql =" FROM {local_sissyncerrors} ls where 1=1";
    }
    if(!is_siteadmin() && has_capability('local/costcenter:manage_ownorganization',$systemcontext)){
        $sql = " FROM {local_sissyncerrors} ls where 1=1 AND ls.modified_by = $USER->id";
    }

if ( $requestData['sSearch'] != "" ) {
	$sql .= " and ((ls.idnumber LIKE '%".$requestData['sSearch']."%')
													or (ls.email LIKE '%".$requestData['sSearch']."%')
													or (ls.firstname LIKE '%".$requestData['sSearch']."%')
													or (ls.lastname LIKE '%".$requestData['sSearch']."%')
													or (ls.mandatory_fields LIKE '%".$requestData['sSearch']."%')
													or (ls.error LIKE '%".$requestData['sSearch']."%')
												or (FROM_UNIXTIME(ls.date_created) LIKE '%".$requestData['sSearch']."%')
																									) ";
}
$sync_errorscount = $DB->count_records_sql($countsql.$sql);

$sql .= " order by ls.id desc";

if ( isset( $input['iDisplayStart'] ) && $input['iDisplayLength'] != '-1' ) {
				$sql .= " LIMIT ".intval( $input['iDisplayStart'] ).", ".intval( $input['iDisplayLength'] );
}

$sync_errors=$DB->get_records_sql($allsql.$sql);
$data=array();
foreach($sync_errors as $sync_error) {
	$list=array();

	$list[]=$sync_error->idnumber?$sync_error->idnumber: '-';
	$list[]=$sync_error->email?$sync_error->email: '-';
	$list[]=$sync_error->firstname?$sync_error->firstname: '-';
	$list[]=$sync_error->lastname?$sync_error->lastname: '-';
	$str=$sync_error->mandatory_fields;
	$exp = explode(',',$str);
	$exp = implode('<br><br>',$exp);
	$list[]= $exp;
	$err=$sync_error->error;	
	$exp1 = explode(',',$err);
	$expe = implode('<br><br>',$exp1);
	$list[]= $expe;
	$date=$sync_error->date_created;
	$list[]=$DB->get_field('user','firstname',array('id'=>$sync_error->modified_by));
	$list[]=date('Y-m-d h:i:sa',$date);
	$data[]=$list;
}
$iTotal = $sync_errorscount; 
$iFilteredTotal = $iTotal;

$output = array(
    "sEcho"                => intval($input['sEcho']),
    "iTotalRecords"        => $iTotal,
    "iTotalDisplayRecords" => $iFilteredTotal,
    "aaData"               => $data
);
echo json_encode($output);
 ?>

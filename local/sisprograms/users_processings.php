<?php
define('AJAX_SCRIPT', true);
require_once(dirname(__FILE__).'/../../config.php');
global $DB,$OUTPUT,$CFG,$USER,$PAGE;
$PAGE->requires->jquery();
$PAGE->requires->jquery_plugin('ui');
$PAGE->set_pagelayout('admin');  
$PAGE->set_url('/local/sisprograms/users_processings.php');
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
$countsql = "SELECT count(u.id) ";
$allsql = "SELECT u.id, u.firstname, u.lastname, u.email, lsu.sisprnid, lsu.costcenterid, u.open_role, lc.fullname";

    if(is_siteadmin() || has_capability('local/costcenter:manage_multiorganizations',$systemcontext) || has_capability('local/costcenter:manage_ownorganization',$systemcontext)){
        $sql .= " FROM {user} AS u
                 JOIN {local_sisuserdata} AS lsu ON u.id = lsu.mdluserid
                 JOIN {local_costcenter} AS lc ON lc.id = lsu.costcenterid
                WHERE u.deleted = 0";
    }
    if(!is_siteadmin() && has_capability('local/costcenter:manage_ownorganization',$systemcontext)){
        $sql .= " AND u.open_costcenterid = $USER->open_costcenterid";
    }

if ( $requestData['sSearch'] != "" ) {
	$sql .= " and ((lsu.sisprnid LIKE '%".$requestData['sSearch']."%')
													or (u.email LIKE '%".$requestData['sSearch']."%')
													or (u.firstname LIKE '%".$requestData['sSearch']."%')
													or (u.lastname LIKE '%".$requestData['sSearch']."%')
													or (lc.fullname LIKE '%".$requestData['sSearch']."%'))";
}
// print_object($countsql.$sql);
$syncuserscount = $DB->count_records_sql($countsql.$sql);
$sql .= " order by u.id desc";

if ( isset( $input['iDisplayStart'] ) && $input['iDisplayLength'] != '-1' ) {
				$sql .= " LIMIT ".intval( $input['iDisplayStart'] ).", ".intval( $input['iDisplayLength'] );
}
$sync_users = $DB->get_records_sql($allsql.$sql);
    $data = array();

        foreach ($sync_users as $usersdata) {
            // print_object($usersdata);exit;
            $line = array();
            $line[] = $usersdata->firstname;
            $line[] = $usersdata->lastname;
            $line[] = $usersdata->sisprnid;
            $line[] = $usersdata->email;
            $line[] = $usersdata->fullname; 
            $userrole = $DB->get_field('role', 'shortname', array('id' => $usersdata->open_role));
            $userrole = ucfirst($userrole);
            $line[] = ($userrole) ? $userrole : 'Not assigned';
            $data[] = $line;
        }
$iTotal = $syncuserscount; 
$iFilteredTotal = $iTotal;

$output = array(
    "sEcho"                => intval($input['sEcho']),
    "iTotalRecords"        => $iTotal,
    "iTotalDisplayRecords" => $iFilteredTotal,
    "aaData"               => $data
);
echo json_encode($output);
?>
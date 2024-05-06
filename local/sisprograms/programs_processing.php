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
$countsql = "SELECT count(sp.id) ";
$allsql = "SELECT sp.id, sp.fullname, sp.shortname, sp.costcenterid, sp.runningfromyear, sp.duration, lc.fullname as costcentername
            ";

    if(is_siteadmin() || has_capability('local/costcenter:manage_multiorganizations',$systemcontext) || has_capability('local/costcenter:manage_ownorganization',$systemcontext)){
        $sql .= " FROM {local_sisprograms} AS sp
                  JOIN {local_costcenter} AS lc ON lc.id = sp.costcenterid
                  WHERE lc.visible = 1";
    }
    if(!is_siteadmin() && has_capability('local/costcenter:manage_ownorganization',$systemcontext)){
        $sql .= " AND sp.costcenterid = $USER->open_costcenterid";
    }

if ( $requestData['sSearch'] != "" ) {
	$sql .= " and ((sp.fullname LIKE '%".$requestData['sSearch']."%')
													or (sp.shortname LIKE '%".$requestData['sSearch']."%')
													or (sp.runningfromyear LIKE '%".$requestData['sSearch']."%')
													or (sp.duration LIKE '%".$requestData['sSearch']."%')
													or (lc.fullname LIKE '%".$requestData['sSearch']."%'))";
}
// print_object($countsql.$sql);exit;
$sisprogramscount = $DB->count_records_sql($countsql.$sql);
$sql .= " order by sp.id desc";

if ( isset( $input['iDisplayStart'] ) && $input['iDisplayLength'] != '-1' ) {
				$sql .= " LIMIT ".intval( $input['iDisplayStart'] ).", ".intval( $input['iDisplayLength'] );
}
$sisprograms = $DB->get_records_sql($allsql.$sql);
// print_object($sisprograms);exit;
        $data = array();
        foreach ($sisprograms as $program) {
            $line = array();
            $line[] =  format_string($program->fullname);
            $line[] = $program->shortname;
           if(strpos($program->duration, 'm')){
                $line[]=intval($program->duration). strtolower(get_string('months', 'local_sisprograms'));
            }
            else{
            $line[] = intval($program->duration) . strtolower(get_string('example', 'local_sisprograms'));
             }
            $line[] = $program->runningfromyear;
            // $line[] = $DB->get_field('local_costcenter', 'fullname', array('id' => $program->costcenterid));
            $line[] = $program->costcentername;
            $data[] = $line;
        }

$iTotal = $sisprogramscount; 
$iFilteredTotal = $iTotal;

$output = array(
    "sEcho"                => intval($input['sEcho']),
    "iTotalRecords"        => $iTotal,
    "iTotalDisplayRecords" => $iFilteredTotal,
    "aaData"               => $data
);
echo json_encode($output);
?>
<?php
define('AJAX_SCRIPT', true);
require_once(dirname(__FILE__) . '/../../config.php');
global $DB, $PAGE,$USER,$CFG,$OUTPUT;

$systemcontext = context_system::instance();
$PAGE->set_context($systemcontext);
$filterjson = optional_param('param', '',  PARAM_RAW);
$filterdata = json_decode($filterjson);
$requestData = $_REQUEST;
$perpage = $requestData['iDisplayLength'];
$recordsperpage = $requestData['iDisplayStart'];
$sSearch = $requestData['sSearch']; 
$countsql = "SELECT  count(id) ";
$selectsql = "SELECT  * ";
$formsql   .="  FROM {local_costcenter} WHERE 1 = 1";
if(!is_siteadmin() && has_capability('local/costcenter:manage_ownorganization',$systemcontext)){
    $formsql   .= " AND parentid = $USER->open_costcenterid";
}
if($sSearch){
    $formsql   .= " AND fullname LIKE '%$sSearch%' ";
}

if(!empty($filterdata->organizations)){
    $univ = implode(',',$filterdata->organizations);
    $formsql   .= " AND parentid IN ($univ) ";
}
if(!empty($filterdata->college)){
    $college = implode(',',$filterdata->college);
    $formsql   .= " AND id IN ($college) ";
}
$formsql .=" AND univ_dept_status = 1";
$totalcolleges = $DB->count_records_sql($countsql.$formsql);
$formsql .=" order by id desc LIMIT $recordsperpage, $perpage";
// $formsql .=" order by id desc";
$colleges = $DB->get_records_sql($selectsql.$formsql);
$data = array();
$dep_list = array();
// print_object($colleges);exit;
foreach($colleges as $college){
    $dep = array();
    $count = $DB->count_records('course',  array('category' => $college->catid));
    /*$edit = '<a id="extended_menu_createcategories" data-action="createcategorymodal"
                class="course_extended_menu_itemlink"
                onclick = "(function(e){ require(\'local_colleges/newcollege\').init({selector:\'createcategorymodal\',
                    contextid:1, id:'.$college->id.',underuniversity:'.$college->parentid.'}) })(event)"
                title="Edit"><span class="createicon"><i class="fa fa-cog fa-fw catcreateicon" aria-hidden="true" aria-label=""></i></span></a>';*/

    $edit = html_writer::link('javascript:void(0)', '<i class="fa fa-cog fa-fw" title="Edit"></i>', array('data-action' => 'createcategorymodal', 'class'=>'course_extended_menu_itemlink', 'data-value'=>$department->id, 'class' => '', 'onclick' =>'(function(e){ require(\'local_colleges/newcollege\').init({selector:\'createcategorymodal\',
                    contextid:1, id:'.$college->id.',underuniversity:0}) })(event)','style'=>'cursor:pointer' , 'title' => 'Edit'));
    
    $checkcostcenter = new \local_costcenter\local\checkcostcenter();
    $modulecount = $checkcostcenter->costcenter_modules_exist($college->parentid,$college->id);
    if(!$modulecount['userscount']){
        /*$delete1 = '<a href="javascript:void(0)" title = "Delete"  onclick = "(function(e){ require(\'local_costcenter/costcenterdatatables\').costcenterDelete({action:\'deletecostcenter\', id: '.$college->id.' ,actionstatus:\'Confirmation\', actionstatusmsg:\'Are you sure, you really want to delete this <b>'.$college->fullname.'</b> ?\' }) })(event)"><i class="fa fa-trash fa-fw" aria-hidden="true" title="" aria-label="Delete"></i></a>';*/
        $delete1 = html_writer::link('javascript:void(0)', '<i class="fa fa-trash fa-fw" aria-hidden="true" title="Delete" aria-label="Delete"></i>', array('title' => get_string('delete'), 'onclick' => '(function(e){ require(\'local_costcenter/costcenterdatatables\').costcenterDelete({action:\'deletecostcenter\', id: '.$college->id.' ,  parentid:'.$college->parentid.',  actionstatus:\'Confirmation\', actionstatusmsg:\'Are you sure, you really want to delete this <b>'.$college->fullname.'</b> ?\' }) })(event)'));
    }else{
       $delete1 = '<a href="javascript:void(0)" title = "Delete"  onclick = "(function(e){ require(\'local_departments/newdepartment\').messageConfirm({title:\'confirm\',message:\'confirmmessageusers\',component:\'local_colleges\' }) })(event)"><i class="fa fa-trash fa-fw" aria-hidden="true" title="" aria-label="Delete"></i></a>';
    }
   
    $dep[]  = $college->fullname;
    $university_name = $DB->get_field('local_costcenter', 'fullname',  array('id'=>$college->parentid));
    $dep[]  =  $university_name ? $university_name : '--';
     $dep[]  = $edit." ".$delete1;
    /*$collegename = $DB->get_field('local_costcenter', 'fullname',  array('id'=>$college->college));
    $dep[]  =  $collegename ? $collegename : '--';*/
    
    // $college->college;
    // $dep[]  = $edit;
    $dep_list[] = $dep;
}

$iTotal = $totalcolleges;
$iFilteredTotal = $iTotal;

$output = array(
    "sEcho" => intval($requestData['sEcho']),
    "iTotalRecords" => $iTotal,
    "iTotalDisplayRecords" => $iFilteredTotal,
    "aaData" => $dep_list
);
echo json_encode($output);
?>

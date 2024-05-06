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
$formsql   .="  FROM {local_costcenter} WHERE univ_dept_status = 0";
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
if(!empty($filterdata->department)){
    $depts = implode(',',$filterdata->department);
    $formsql   .= " AND id IN ($depts) ";
}
if(!empty($filterdata->faculties)){
    $faculty = implode(',',$filterdata->faculties);
    $formsql   .= " AND faculty IN ($faculty) ";
}
if(!empty($filterdata->faculty)){
   
    $formsql   .= " AND faculty IN ($filterdata->faculty) ";
}
$totaldepartments = $DB->count_records_sql($countsql.$formsql);
$formsql .=" order by id desc LIMIT $recordsperpage, $perpage";
// $formsql .=" order by id desc";
$departments = $DB->get_records_sql($selectsql.$formsql);
$data = array();
$dep_list = array();
foreach($departments as $department){
    $dep = array();
    /*$edit = '<a id="extended_menu_createcategories" data-action="createcategorymodal"
                class="course_extended_menu_itemlink"
                onclick = "(function(e){ require(\'local_departments/newdepartment\').init({selector:\'createcategorymodal\',
                    contextid:1, categoryid:'.$department->id.',underuniversity:'.$department->parentid.'}) })(event)"
                title="Edit"><span class="createicon"><i class="fa fa-cog fa-fw catcreateicon" aria-hidden="true" aria-label=""></i></span></a>';*/
    $edit = html_writer::link('javascript:void(0)', '<i class="fa fa-cog fa-fw" title="Edit"></i>', array('data-action' => 'createcategorymodal', 'class'=>'course_extended_menu_itemlink', 'data-value'=>$department->id, 'class' => '', 'onclick' =>'(function(e){ require(\'local_departments/newdepartment\').init({selector:\'createcategorymodal\',
                    contextid:1, categoryid:'.$department->id.',underuniversity:0}) })(event)','style'=>'cursor:pointer' , 'title' => 'Edit'));
    
    $checkcostcenter = new \local_costcenter\local\checkcostcenter();
    $modulecount = $checkcostcenter->costcenter_modules_exist($department->parentid,$department->id);
    if(!$modulecount['userscount'] && !$modulecount['coursescount'] && !$modulecount['programscount']){
        /*$delete1 = '<a href="javascript:void(0)" title = "Delete"  onclick = "(function(e){ require(\'local_costcenter/costcenterdatatables\').costcenterDelete({action:\'deletecostcenter\', id: '.$department->id.' ,actionstatus:\'Confirmation\', actionstatusmsg:\'Are you sure, you really want to delete this <b>'.$department->fullname.'</b> ?\' }) })(event)"><i class="fa fa-trash fa-fw" aria-hidden="true" title="" aria-label="Delete"></i></a>';*/
        $delete1 = html_writer::link('javascript:void(0)', '<i class="fa fa-trash fa-fw" aria-hidden="true" title="Delete" aria-label="Delete"></i>', array('title' => get_string('delete'), 'onclick' => '(function(e){ require(\'local_costcenter/costcenterdatatables\').costcenterDelete({action:\'deletecostcenter\', id: '.$department->id.', parentid:'.$department->parentid.', actionstatus:\'Confirmation\', actionstatusmsg:\'Are you sure, you really want to delete this <b>'.$department->fullname.'</b> ?\' }) })(event)'));
    }else{
        $delete1 = '<a href="javascript:void(0)" title = "Delete"  onclick = "(function(e){ require(\'local_departments/newdepartment\').messageConfirm({title:\'confirm\',message:\'confirmmessage\',component:\'local_departments\' }) })(event)"><i class="fa fa-trash fa-fw" aria-hidden="true" title="" aria-label="Delete"></i></a>';
    }
    $dep[]  = $department->fullname;
    $university_name = $DB->get_field('local_costcenter', 'fullname',  array('id'=>$department->parentid));
// <mallikarjun> - removed faculty option -- starts
//    if(!empty($department->faculty)){
//        $faculty_name = $DB->get_field('local_faculties', 'facultyname',  array('id'=>$department->faculty));
//        $dep[]  =  $faculty_name;
//    }else{
//        $dep[]  =  '--';
//    }
// <mallikarjun> - removed faculty option -- ends
    $dep[]  =  $university_name ? $university_name : '--';
    $dep[]  = $edit." ".$delete1;
    $dep_list[] = $dep;
}

$iTotal = $totaldepartments;
$iFilteredTotal = $iTotal;

$output = array(
    "sEcho" => intval($requestData['sEcho']),
    "iTotalRecords" => $iTotal,
    "iTotalDisplayRecords" => $iFilteredTotal,
    "aaData" => $dep_list
);
echo json_encode($output);
?>

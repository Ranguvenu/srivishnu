<?php
define('AJAX_SCRIPT', true);
require_once(dirname(__FILE__) . '/../../config.php');
global $DB, $PAGE,$USER,$CFG,$OUTPUT;

$systemcontext = context_system::instance();
$PAGE->set_context($systemcontext);
require_once($CFG->dirroot.'/local/curriculum/lib.php');
$filterjson = optional_param('param', '',  PARAM_RAW);
$filterdata = json_decode($filterjson);
//print_object($filterdata);
$department=$filterdata->department;
$sSearch = $requestData['search']; 
$systemcontext = context_system::instance();
$selectsql = "SELECT * FROM {local_curriculum} AS c WHERE 1=1 AND c.program = 0 "; 
$countsql  = "SELECT count(c.id) FROM {local_curriculum} AS c WHERE 1=1 "; 

if(!is_siteadmin() && has_capability('local/costcenter:manage_ownorganization', $systemcontext)){
    $selectsql .= " AND c.costcenter = $USER->open_costcenterid";
    $countsql .= " AND c.costcenter = $USER->open_costcenterid";
}
//$formsql .= " AND c.id>1";
if ( $requestData['sSearch'] != "" ) {
    $formsql .= " and ((c.fullname LIKE '%".$requestData['sSearch']."%')
                                                    or (c.shortname LIKE '%".$requestData['sSearch']."%')
                                                    or (co.fullname LIKE '%".$requestData['sSearch']."%')
                                                    or (cc.name LIKE '%".$requestData['sSearch']."%'))";
}
if(!empty($filterdata->organizations)){
    $organizations = implode(',',$filterdata->organizations);
        if(!empty($filterdata->organizations)){
            $deptquery = array();
            foreach ($filterdata->organizations as $key => $group) {
                $deptquery[] = " FIND_IN_SET($group,c.costcenter) ";
            }
            $groupqueeryparams = implode('OR',$deptquery);
            $formsql .= ' AND ('.$groupqueeryparams.')';
        }
}
if(!empty($filterdata->department)){
        if(!empty($filterdata->department)){
            $deptquery = array();
            foreach ($filterdata->department as $key => $group) {
                $deptquery[] = " FIND_IN_SET($group,c.department) ";
                }
            $groupqueeryparams = implode('OR',$deptquery);
            $formsql .= ' AND ('.$groupqueeryparams.')';
        }
}
$totalcourses = $DB->count_records_sql($countsql.$formsql);
 if($totalcourses==0){
    $totalcourses=0;
  }
  else{
    $totalcourses=$advisorscount;
  }
$formsql .=" ORDER BY c.id DESC";
$curriculums = $DB->get_records_sql($selectsql.$formsql);
$data = array();
foreach ($curriculums as $curriculum) {
    $row = array();
  //  $curriculumname = $DB->get_field('local_costcenter', 'fullname', array('id' =>  $curriculum->costcenter));
   // $curriculumnames = $DB->get_records_sql_menu("SELECT id,fullname FROM {local_costcenter} WHERE id IN ($curriculum->costcenter)");
   // $curriculumname = implode(',',$curriculumnames);
    $costcentername = $DB->get_field('local_costcenter','fullname',array('id' => $curriculum->costcenter));
    $department = $DB->get_field('local_costcenter','fullname',array('id' => $curriculum->department, 'univ_dept_status' => 0));
    $curriculums_url = $CFG->wwwroot.'/local/curriculum/view.php?ccid='.$curriculum->id.'&type=1';
    $row[] = '<a href ="'.$curriculums_url.'" alt = "' . get_string('view') . '" title = "' . get_string('view') . '"  target = "_blank"> '.$curriculum->name.'</a>';
    $row[] = $costcentername;
    if(!$department){
    $department=$DB->get_field('local_costcenter','fullname',array('id'=>$curriculum->department, 'univ_dept_status' => 1));
    }
    if($department){
        $row[] = $department;
    }else{
        $row[] = 'N/A';
    }
    $duration_format = $DB->get_field('local_curriculum','duration_format',array('id' => $curriculum->id));
    if($duration_format == 'Y'){
      $duration_format = ' Years';
    }
    else{
      $duration_format = ' Months';
    }
    $row[] = $curriculum->duration.$duration_format;
    $programscount = $DB->count_records("local_program",array('curriculumid' => $curriculum->id));

    $action = html_writer::link('javascript:void(0)',  $OUTPUT->pix_icon('t/edit', get_string('edit'), 'moodle', array('')), array('title' => get_string('edit'), 'alt' => get_string('edit'),'data-value' => $advisor->id, 'onclick' =>'(function(e){ require("local_curriculum/ajaxforms").init({contextid:1, component:"local_curriculum", callback:"curriculum_form", form_status:0, plugintype: "local", pluginname: "curriculum",id: '.$curriculum->id.'}) })(event)'));
       if($programscount > 0)
    {
     // $action =  '<a alt = "' . get_string('already_published','local_curriculum') . '" title = "' . get_string('already_published','local_curriculum') . '" >'.$OUTPUT->pix_icon('t/edit', get_string('already_published','local_curriculum')).'</a>';
     $action .= html_writer::link('javascript:void(0)', $OUTPUT->pix_icon('t/delete', get_string('delete'), 'moodle', array('')), array('title' => get_string('delete'), 'id' => $curriculum->id, 'onclick'=>'(function(e){ require(\'local_departments/newdepartment\').messageConfirm({title:\'alert\' ,message:\'confirmmessage\',component:\'local_curriculum\' }) })(event)'));
    }
    else{
     
      $action .= html_writer::link('javascript:void(0)', $OUTPUT->pix_icon('t/delete', get_string('delete'), 'moodle', array('')), array('title' => get_string('delete'), 'id' => $curriculum->id, 'onclick'=>'(function(e){ require(\'local_curriculum/ajaxforms\').deleteConfirm({action:\'deletecurriculum\' ,id: '.$curriculum->id.' }) })(event)'));
     }
    //$action .= html_writer::link('javascript:void(0)', $OUTPUT->pix_icon('t/delete', get_string('delete'), 'moodle', array('')), array('title' => get_string('delete'), 'id' => $curriculum->id, 'onclick'=>'(function(e){ require(\'local_curriculum/ajaxforms\').deleteConfirm({action:\'deletecurriculum\' ,id: '.$curriculum->id.' }) })(event)'));
   
   /* $action .=    '<a href="'.$curriculums_url.'" alt = "' . get_string('view') . '" title = "' . get_string('view') . '" ><i class = "icon fa fa-list-alt"></i>
                   </a>';*/

    if($programscount > 0 || $curriculum->curriculum_publish_status == 1)
    {

        $action .=  '<a alt = "' . get_string('already_published','local_curriculum') . '" title = "' . get_string('already_published','local_curriculum') . '" ><i class="icon fa fa-id-card"></i></a>';

     }
     else{           
    $action .= '<a href="javascript:void(0);" id = "programpublish_icon'.$curriculum->id.'" alt = "' . get_string('publishcurriculum','local_curriculum') . '" onclick="(function(e){ require(\'local_curriculum/curriculum_views\').checkProgramStatus({id:\''.$curriculum->id.'\',costcenter : \''.$curriculum->costcenter.'\', ccid : \''.$curriculum->id.'\'}) })(event)" title = "' . get_string('publishcurriculum','local_curriculum') . '" ><i class="icon fa fa-list-alt"></i></a>';
     }
  
    
    

    $row[] = $action;
    $data[] = $row;
  
  }

$iTotal = $totalcourses;
$iFilteredTotal = $iTotal;
$output = array(
    "sEcho" => intval($requestData['sEcho']),
    "iTotalRecords" => $iTotal,
    "iTotalDisplayRecords" => $iFilteredTotal,
    "aaData" => $data
);
echo json_encode($output);
?>

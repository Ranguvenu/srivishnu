<?php
define('AJAX_SCRIPT', true);
require_once(dirname(__FILE__) . '/../../config.php');
global $DB, $PAGE,$USER,$CFG,$OUTPUT;

$systemcontext = context_system::instance();
$PAGE->set_context($systemcontext);
require_once($CFG->dirroot.'/local/groups/lib.php');
$filterjson = optional_param('param', '',  PARAM_RAW);
$filterdata = json_decode($filterjson);
//print_object($filterdata);
$department=$filterdata->department;
$sSearch = $requestData['search']; 
$systemcontext = context_system::instance();
if(is_siteadmin()){
$selectsql = "SELECT * FROM {local_groups} WHERE 1=1 "; 
}
else{
  $selectsql = "SELECT * FROM {local_groups} WHERE 1=1 AND costcenterid = ".$USER->open_costcenterid; 
}
$countsql  = "SELECT count(id) FROM {local_groups}  WHERE 1=1 "; 

$formsql .= " AND id>=1";
if ( $requestData['sSearch'] != "" ) {
  //  $formsql .= " and ((c.fullname LIKE '%".$requestData['sSearch']."%')
                                              //      or (c.shortname LIKE '%".$requestData['sSearch']."%')
                                              //      or (co.fullname LIKE '%".$requestData['sSearch']."%')
                                              //      or (cc.name LIKE '%".$requestData['sSearch']."%'))";
}
/*if(!empty($filterdata->costcenter)){
    $costcenter = implode(',',$filterdata->costcenter);
        if(!empty($filterdata->costcenter)){
            $deptquery = array();
            foreach ($filterdata->costcenter as $key => $group) {
                $deptquery[] = " FIND_IN_SET($group,c.costcenter) ";
            }
            $groupqueeryparams = implode('OR',$deptquery);
            $formsql .= ' AND ('.$groupqueeryparams.')';
        }
}*/

$totalcourses = $DB->count_records_sql($countsql.$formsql);
 if($totalcourses==0){
    $totalcourses=0;
  }
  else{
    $totalcourses=$advisorscount;
  }
$formsql .=" ORDER BY id DESC";
$groups = $DB->get_records_sql($selectsql.$formsql);
//print_object($groups);
$data = array();
foreach ($groups as $group) {
    $row = array();
  
    $groupnamename = $DB->get_field('cohort','name',array('id' => $group->cohortid));
    $groupid = $DB->get_field('cohort','idnumber',array('id' => $group->cohortid));
    $description = $DB->get_field('cohort','description',array('id' => $group->cohortid));
    $visible = $DB->get_field('cohort','visible',array('id' => $group->cohortid));
   
    $users = $DB->count_records_sql("SELECT count(u.id) as users FROM {user} u JOIN {cohort_members} cm ON cm.userid = u.id JOIN {local_groups} lg ON lg.cohortid = cm.cohortid WHERE cm.cohortid = ".$group->cohortid." AND u.open_departmentid =".$group->departmentid);
    $group_members_count = $users;
    $baseurl = new moodle_url('/local/groups/index.php', $params);
    $urlparams = array('id' => $group->cohortid,'dept' => $group->departmentid ,'costcenter' => $group->costcenterid, 'returnurl' => $baseurl->out_as_local_url());
    $urlparam = array('id' => $group->cohortid, 'costcenter' => $group->costcenterid,'size' => 'size','returnurl' => $baseurl->out_as_local_url());

                 //  $cohortcontext = context::instance_by_id($cohort->contextid);

    $actions = '';
    if (empty($cohort->component)) {

          $groupedit = html_writer::link('javascript:void(0)',  $OUTPUT->pix_icon('t/edit', get_string('edit'), 'moodle', array('')), array('title' => get_string('edit'), 'alt' => get_string('edit'),'data-action' => 'creategroupsmodal', 'class'=>'creategroupsmodal', 'data-value'=>$group->id, 'onclick' =>'(function(e){ require("local_groups/newgroups").init({contextid:'.$systemcontext->id.', component:"local_groups",  form_status:0, plugintype: "local", pluginname: "groups", id: ' . $group->id . ' }) })(event)'));

          $actions .= $groupedit;
          
        /*  $showhideurl = new moodle_url('/local/groups/edit.php', $urlparams + array('sesskey' => sesskey()));
         if ($visible) {
                  $showhideurl->param('hide', 1);
                  $visibleimg = $OUTPUT->pix_icon('t/hide', get_string('inactive'));
                  $actions .= html_writer::link($showhideurl, $visibleimg, array('title' => get_string('inactive')));
              } else {
                  $showhideurl->param('show', 1);
                  $visibleimg = $OUTPUT->pix_icon('t/show', get_string('active'));
                  $actions .= html_writer::link($showhideurl, $visibleimg, array('title' => get_string('active')));
              } */     

          $actions .= html_writer::link(new moodle_url('/local/groups/assign.php', $urlparams),
                  $OUTPUT->pix_icon('i/enrolusers', get_string('assign', 'local_groups')),
                  array('title' => get_string('assign', 'local_groups')));
              $editcolumnisempty = false;
          $actions .= html_writer::link(new moodle_url('/local/groups/mass_enroll.php', $urlparams),
                  $OUTPUT->pix_icon('i/users', get_string('bulk_enroll', 'local_groups')),
                  array('title' => get_string('bulk_enroll', 'local_groups')));

         $deleteurl = html_writer::link('javascript:void(0)', $OUTPUT->pix_icon('t/delete', get_string('delete'), 'moodle', array('')), array('title' => get_string('delete'), 'id' => "courses_delete_confirm_".$group->id,'onclick'=>'(function(e){ require(\'local_groups/renderselections\').deletecohort(' . $group->cohortid . ',"'.$groupnamename.'" ) })(event)'));
         $actions .= $deleteurl;
  
    }

    $row[] = $groupnamename;
    $row[] = $groupid;
    $row[] = $DB->get_field('local_costcenter','fullname',array('id' => $group->costcenterid));
    $row[] = $DB->get_field('local_costcenter','fullname',array('id' => $group->departmentid));
    $row[] = html_writer::link(new moodle_url('/local/groups/assign.php', $urlparam),
                        $group_members_count,
                        array('title' => get_string('assign', 'local_groups')));
                    $editcolumnisempty = false;
   /* if (empty($cohort->component)) {
      $row[] = get_string('nocomponent', 'cohort');
    } else {
      $row[] = $DB->get_field('cohort','component',array('id' => $group->cohortid));
    }*/
    $row[] = $actions;

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

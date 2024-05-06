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
$mode = $filterdata->mode;
$sSearch = $requestData['search']; 
$systemcontext = context_system::instance();
$selectsql = "SELECT c.id, c.fullname, c.shortname, c.category,c.open_cost,c.open_points,c.open_costcenterid, c.open_departmentid, c.open_identifiedas,c.forpurchaseindividually FROM {course} AS c ";
$formsql = "";
// <mallikarjun> - ODL-834 Mooc courses are displaying for faculty -- starts
if(is_siteadmin() || has_capability('local/mooccourses:manage',$systemcontext)){
    $formsql .= " WHERE 1=1";
}
else{
    $formsql .= ", {user_enrolments} AS uen, {enrol} AS en WHERE 1=1";
}
if($mode == 2){
    $formsql .= " AND c.affiliationstatus = 1";
}else if($mode == 1){
    $formsql .= " AND c.forpurchaseindividually IN (1,2) AND c.affiliationstatus IS NULL";
}
if(!is_siteadmin() && has_capability('local/costcenter:manage_ownorganization',$systemcontext)){
    $formsql .= " AND c.open_costcenterid = $USER->open_costcenterid";
}
if(!is_siteadmin() && !has_capability('local/costcenter:manage_ownorganization',$systemcontext) && has_capability('local/costcenter:manage_owndepartments',$systemcontext)){
    $formsql .= " AND c.open_costcenterid = $USER->open_costcenterid AND c.open_departmentid = $USER->open_departmentid";
}
/*if($mode == 3){
    $formsql = " AND c.subcollege =".$USER->open_departmentid; 
}*/
$countsql  = "SELECT count(c.id) FROM {course} AS c WHERE 1=1 "; 

/*if(is_siteadmin()){
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
}*/
if(!empty($filterdata->organizations)){
    $costcenter = implode(',',$filterdata->organizations);
    $formsql .= " AND c.open_costcenterid IN ($costcenter)";
}
if(!empty($filterdata->department)){
    $department = implode(',',$filterdata->department);
    $formsql .= " AND c.open_departmentid IN ($department)";
}
if(is_siteadmin() || has_capability('local/mooccourses:manage',$systemcontext)){
$formsql .= " AND c.id>1 AND c.open_parentcourseid IS NOT NULL AND c.forpurchaseindividually IS NOT NULL";
  if ( $requestData['sSearch'] != "" ) {
    $formsql .= " and ((c.fullname LIKE '%".$requestData['sSearch']."%')
                                                    or (c.shortname LIKE '%".$requestData['sSearch']."%')
                                                    or (co.fullname LIKE '%".$requestData['sSearch']."%')
                                                    or (cc.name LIKE '%".$requestData['sSearch']."%'))";
  }
}else{
$formsql .= " AND c.id>1 AND c.open_parentcourseid IS NOT NULL AND c.forpurchaseindividually IS NOT NULL AND c.affiliationstatus = 1 AND c.open_costcenterid = $USER->open_costcenterid AND c.id = en.courseid AND en.id = uen.enrolid AND uen.userid = $USER->id ";
  if ( $requestData['sSearch'] != "" ) {
    $formsql .= " and ((c.fullname LIKE '%".$requestData['sSearch']."%')
                                                    or (c.shortname LIKE '%".$requestData['sSearch']."%')
                                                    or (co.fullname LIKE '%".$requestData['sSearch']."%')
                                                    or (cc.name LIKE '%".$requestData['sSearch']."%'))";
  }  
}
// <mallikarjun> - ODL-834 Mooc courses are displaying for faculty -- ends
//$totalcourses = $DB->count_records_sql($countsql.$formsql);
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

   $row[] = html_writer::tag('a', $course->fullname,array('href' =>$CFG->wwwroot. '/course/view.php?id='.$course->id));
    $row[] = $course->shortname;
    $row[] = $DB->get_field('local_costcenter', 'fullname',array('id' => $course->open_costcenterid));
// <sandeep> - SRIVSPT-3 Manage Mooc Courses Module Changes -- Starts    
if($course->open_departmentid == 0){
        $alldepartments = get_string('alldepartments', 'local_mooccourses');
        $row[] = $alldepartments;
    }
    else{
    $multipledeparts = $DB->get_fieldset_sql("SELECT fullname FROM {local_costcenter} WHERE id IN ($course->open_departmentid)");

    $row[] = implode('/',array_values($multipledeparts));
    // $row[] = $DB->get_field('local_costcenter', 'fullname', array('id' => $course->open_departmentid));
   // $row[] = $course->open_cost;
   // $row[] = $course->open_points;
    }
// <sandeep> - SRIVSPT-3 Manage Mooc Courses Module Changes -- Ends
    $action = '';
    if($course->forpurchaseindividually == 1){
     $courseedit = html_writer::link('javascript:void(0)',  $OUTPUT->pix_icon('t/edit', get_string('edit'), 'moodle', array('')), array('title' => get_string('edit'), 'alt' => get_string('edit'),'data-action' => 'createcoursemodal', 'class'=>'createcoursemodal', 'data-value'=>$course->id, 'onclick' =>'(function(e){ require("local_mooccourses/newmooccourses").init({contextid:1, component:"local_mooccourses", form_status:0, plugintype: "local", pluginname: "mooccourses",course:3, forpurchaseindividually:'.$course->forpurchaseindividually.', courseid: ' . $course->id . ' }) })(event)'));
      // $action .= $courseedit;
     }else{
     $courseedit = html_writer::link('javascript:void(0)',  $OUTPUT->pix_icon('t/edit', get_string('edit'), 'moodle', array('class'=>'fa fa-cog icon text-muted')), array('title' => get_string('edit'), 'alt' => get_string('edit'),'data-action' => 'createcoursemodal', 'class'=>'createcoursemodal', 'data-value'=>$course->id, 'onclick' =>'(function(e){ require("local_mooccourses/courseAjaxform").init({contextid:1, component:"local_mooccourses",callback:"custom_mooccourse_form", form_status:0, plugintype: "local", pluginname: "mooccourses",course:3, forpurchaseindividually:'.$course->forpurchaseindividually.', courseid: ' . $course->id . ' }) })(event)'));
      // $action .= $courseedit;
     }
        if($course->id > 0){
            $courses_sql = "SELECT COUNT(ue.id) FROM {course} AS course
                JOIN {enrol} AS e ON course.id = e.courseid AND e.enrol IN('self','manual','auto')
                JOIN {user_enrolments} ue ON e.id = ue.enrolid
                WHERE course.id = $course->id AND course.id>1";
            $enrolmentcount = $DB->count_records_sql($courses_sql);
//<mallikarjun> - ODL-825 Should not delete if mooc course is delete after affiliated to collage -- starts
             $affileatecourses_sql = "SELECT COUNT(course.id) FROM {course} AS course
                
                WHERE course.open_parentcourseid = $course->id AND course.id>1";
            $affiliateenrolmentcount = $DB->count_records_sql($affileatecourses_sql);
            //<mallikarjun> - ODL-825 Should not delete if mooc course is delete after affiliated to collage -- ends
        }

        if($enrolmentcount){
            // Sandeep - Edit and Delete icons are added - Starts // 
          $deleteurl = html_writer::link('javascript:void(0)', $OUTPUT->pix_icon('t/delete', get_string('delete'), 'moodle', array('class'=>'fa fa-trash icon text-muted')), array('title' => get_string('delete'), 'id' => $course->id, 'onclick'=>'(function(e){ require(\'local_departments/newdepartment\').messageConfirm({title:\'alert\' ,message:\'confirmmessage\',component:\'local_mooccourses\' }) })(event)'));
          // $courseedit = html_writer::link('javascript:void(0)', $OUTPUT->pix_icon('t/edit', get_string('edit'), 'moodle', array('class'=>'fa fa-cog icon text-muted')), array('title' => get_string('edit'), 'id' => $course->id, 'onclick'=>'(function(e){ require(\'local_departments/newdepartment\').editmessageConfirm({title:\'alert\' ,message:\'editconfirmmessage\',component:\'local_mooccourses\' }) })(event)'));
          $action .= $deleteurl.$courseedit;
            // Sandeep - Edit and Delete icons are added - Ends // 
        }
        //<mallikarjun> - ODL-825 Should not delete if mooc course is delete after affiliated to collage -- starts
        elseif($affiliateenrolmentcount){
          $deleteurl = html_writer::link('javascript:void(0)', $OUTPUT->pix_icon('t/delete', get_string('delete'), 'moodle', array('')), array('title' => get_string('delete'), 'id' => $course->id, 'onclick'=>'(function(e){ require(\'local_departments/newdepartment\').messageConfirm({title:\'alert\' ,message:\'affiliateconfirmmessage\',component:\'local_mooccourses\' }) })(event)'));
        }
        //<mallikarjun> - ODL-825 Should not delete if mooc course is delete after affiliated to collage -- ends
        else{
            // Sandeep - Edit and Delete icons are added - Starts //
           $deleteurl = html_writer::link('javascript:void(0)', $OUTPUT->pix_icon('t/delete', get_string('delete'), 'moodle', array('class'=>'fa fa-trash icon text-muted')), array('title' => get_string('delete'), 'id' => $course->id, 'onclick'=>'(function(e){ require(\'local_mooccourses/mooccourses\').deleteConfirm({action:\'delete_mooccourse\' ,  contextid:1,courseid: '.$course->id.' }) })(event)'));
          $action .= $deleteurl.$courseedit;
            // Sandeep - Edit and Delete icons are added - Ends // 
        }
  
      /*$user_enrolments = " <a id='extended_menu_createcourses' class='enrolstudent_tomooccourse' title='".get_string('create_newstudent', 'local_mooccourses')."' data-action='createcoursemodal' onclick ='(function(e){ require(\"local_mooccourses/newmooccourses\").init({selector:\"createcoursemodal\",act:1, context:$systemcontext->id,department:".$course->open_departmentid.", courseid:".$course->id.", form_status:0}) })(event)' ><i class='fa fa-user'></i> </a>";*/
      // $action .= $deleteurl.$courseedit;

      $affiliatecourses_url = new moodle_url('/local/mooccourses/affiliatecourses.php',array('cid'=>$course->id,'uid'=>$course->open_costcenterid,'type'=>$mode));
      $affiliatecourse = '<a href="'.$affiliatecourses_url.'" id = "affiliatecourses_icon'.$course->id.'" alt = "' . get_string('affiliatecourses','local_mooccourses') . '" target="_blank" title = "' . get_string('affiliatecourses','local_mooccourses') . '" ><i class="icon fa fa-university fa-fw"></i></a>';
      if($mode == 1){
          $action .= $deleteurl.$courseedit.$affiliatecourse;
      }

      $enrolid = $DB->get_field('enrol','id', array('courseid'=>$course->id, 'enrol'=>'manual'));
      $studentroleid = $DB->get_field('role', 'id', array('shortname' => 'student'));
// <sandeep> - SRIVSPT-3 Manage Mooc Courses Module Changes -- Starts      
$student_enrolments = html_writer::link(new moodle_url('/local/mooccourses/courseenrol.php',array('id'=>$course->id,'enrolid' => $enrolid,'department' => $course->open_departmentid, 'costcenter' => $course->open_costcenterid, 'roleid' => $studentroleid)),html_writer::tag('i','',array('class'=>'fa fa-user icon text-muted', 'title' => get_string('create_newstudent','local_mooccourses'), 'alt' => get_string('create_newstudent', 'local_mooccourses'))));
// <sandeep> - SRIVSPT-3 Manage Mooc Courses Module Changes -- Ends
      $facultyroleid = $DB->get_field('role', 'id', array('shortname' => 'faculty'));
      $faculty_enrolments = html_writer::link(new moodle_url('/local/mooccourses/courseenrol.php',array('id'=>$course->id,'enrolid' => $enrolid, 'costcenter' => $course->open_costcenterid, 'roleid' => $facultyroleid)),html_writer::tag('i','',array('class'=>'fa fa-user-plus icon text-muted', 'title' => get_string('enrolfaculty','local_mooccourses'), 'alt' => get_string('enrolfaculty', 'local_mooccourses'))));
      
       $mass_enroll=html_writer::link(new moodle_url('/local/mooccourses/mass_enroll.php', array('id'=>$course->id)),html_writer::tag('i','',array('class'=>'fa fa-users fa-fw icon text-muted', 'title' => get_string('bulkuploadenrolusers','local_mooccourses'), 'alt' => get_string('bulkuploadenrolusers', 'local_mooccourses'))));//code added for bulk enrolment icon

      if($mode == 2 || (!is_siteadmin() && !has_capability('local/costcenter:manage_ownorganization',$systemcontext) && has_capability('local/costcenter:manage_owndepartments',$systemcontext))){
          $action .= $student_enrolments.$faculty_enrolments.$mass_enroll;
      }
    
      $row[] = $action ? $action : 'No actions';
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

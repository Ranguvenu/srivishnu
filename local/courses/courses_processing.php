<?php
define('AJAX_SCRIPT', true);
require_once(dirname(__FILE__) . '/../../config.php');
global $DB, $PAGE,$USER,$CFG,$OUTPUT;
require_once($CFG->dirroot.'/course/renderer.php');
require_once($CFG->dirroot . '/enrol/locallib.php');

$systemcontext = context_system::instance();
$core_component = new core_component();

$autoenroll_plugin_exist = $core_component::get_plugin_directory('enrol','auto');
if(!empty($autoenroll_plugin_exist)){
  require_once($CFG->dirroot . '/enrol/auto/lib.php');
}
$PAGE->set_context($systemcontext);
$filterjson = optional_param('param', '',  PARAM_RAW);
$filterdata = json_decode($filterjson);

$requestData = $_REQUEST;
$perpage = $requestData['iDisplayLength'];
$recordsperpage = $requestData['iDisplayStart'];

$sSearch = $requestData['sSearch']; 
$chelper = new coursecat_helper();
$selectsql = "SELECT c.id, c.fullname, c.shortname, c.category,c.open_points,c.open_costcenterid, c.open_departmentid, c.open_identifiedas,c.visible, co.fullname as costcenter, cc.name FROM {course} AS c"; 
$countsql  = "SELECT count(c.id) FROM {course} AS c"; 
if(is_siteadmin()){
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
}
$formsql .= " AND c.id>1 AND c.open_parentcourseid = 0 and c.forpurchaseindividually IS NULL";
if ( $requestData['sSearch'] != "" ) {
    $formsql .= " and ((c.fullname LIKE '%".$requestData['sSearch']."%')
                                                    or (c.shortname LIKE '%".$requestData['sSearch']."%')
                                                    or (co.fullname LIKE '%".$requestData['sSearch']."%')
                                                    or (cc.name LIKE '%".$requestData['sSearch']."%'))";
}
/*if(!empty($filterdata->universitydepartment)){
    $departs = implode(',',$filterdata->universitydepartment);
    $univ_depts = $DB->get_records_sql_menu("SELECT catid as id, catid as categoryid FROM {local_departments} WHERE id IN ($departs)");
    $categories = implode(',',$univ_depts);
    $formsql .= " AND cc.id IN ($categories)";
}*/
if(!empty($filterdata->courses)){
    $courses = implode(',',$filterdata->courses);
    $formsql .= " AND c.id IN ($courses)";
}
if(!empty($filterdata->department)){
    $departments = implode(',',$filterdata->department);
    $formsql .= " AND c.open_departmentid IN ($departments)";

}
if(!empty($filterdata->organizations)){
    $universities = implode(',',$filterdata->organizations);
    $formsql .= " AND c.open_costcenterid IN ($universities)";

}
$totalcourses = $DB->count_records_sql($countsql.$formsql);
$formsql .=" ORDER BY c.id DESC LIMIT $recordsperpage, $perpage";
$courses = $DB->get_records_sql($selectsql.$formsql);

$data = array();
foreach ($courses as $course) {
    $row = [];
    // $organization = $DB->get_field('local_costcenter', 'fullname', array('id'=>$course->open_costcenterid));

    $dept = $DB->get_field('local_costcenter', 'fullname', array('id'=>$course->open_departmentid));
    $auto_enrol = '';
    $enrolid = $DB->get_field('enrol','id',array('enrol'=>'manual','courseid'=>$course->id));
    if(!empty($autoenroll_plugin_exist)){
        $autoplugin = enrol_get_plugin('auto');
        $instance = $autoplugin->get_instance_for_course($course->id);
        if($instance){
            if ($instance->status == ENROL_INSTANCE_DISABLED) {
           $auto_enrol = $CFG->wwwroot."/enrol/auto/edit.php?courseid=".$course->id."&id=".$instance->id;
            }
        }
    }
    $categorycontext = context_coursecat::instance($course->category);
    $action = '';
    if($course->id){
        $duplicatedcount = $DB->count_records_sql("SELECT count(id) FROM {course}
                                                    WHERE open_parentcourseid = $course->id");   
    }
    if(has_capability('local/costcenter_course:delete',$systemcontext)&&has_capability('local/costcenter_course:manage', $systemcontext)){
        $deleteurl = html_writer::link('javascript:void(0)', $OUTPUT->pix_icon('t/delete', get_string('delete'), 'moodle', array('')), array('title' => get_string('delete'), 'id' => "courses_delete_confirm_".$course->id,'onclick'=>'(function(e){ require(\'local_courses/courseAjaxform\').deleteConfirm({action:\'deletecourse\' , id: ' . $course->id . ', count:'.$duplicatedcount.',name:"'.$course->fullname.'" }) })(event)'));
        $action .= $deleteurl;
    }

    /*if((has_capability('local/costcenter_course:enrol', context_system::instance())  || is_siteadmin())&&has_capability('local/costcenter_course:manage', $systemcontext)){
         $enrolurl = html_writer::link($CFG->wwwroot."/local/courses/courseenrol.php?id=".$course->id."&enrolid=".$enrolid, $OUTPUT->pix_icon('t/enrolusers', get_string('enrolusers', 'local_courses'), 'moodle', array('')), array('title' => get_string('enrolusers', 'local_courses')));
        $action .= $enrolurl;
    } */
    if((has_capability('local/costcenter_course:update',context_system::instance()) || is_siteadmin()) &&has_capability('local/costcenter_course:manage', $systemcontext)) {
        if($auto_enrol){
            $autoenrolurl = html_writer::link($auto_enrol, $OUTPUT->pix_icon('t/assignroles', get_string('enrolusers', 'local_courses'), 'moodle', array('')), array('title' => get_string('enrolusers', 'local_courses')));
            $action .= $autoenrolurl;
        }
        $courseedit = html_writer::link('javascript:void(0)',  $OUTPUT->pix_icon('t/edit', get_string('edit'), 'moodle', array('')), array('title' => get_string('edit'), 'alt' => get_string('edit'),'data-action' => 'createcoursemodal', 'class'=>'createcoursemodal', 'data-value'=>$course->id, 'onclick' =>'(function(e){ require("local_courses/courseAjaxform").init({contextid:'.$categorycontext->id.', component:"local_courses", callback:"custom_course_form", form_status:0, plugintype: "local", pluginname: "courses", courseid: ' . $course->id . ' }) })(event)'));
        $action .= $courseedit;
    }
    if((has_capability('local/costcenter_course:grade_view',context_system::instance()) || is_siteadmin())&&has_capability('local/costcenter_course:manage', $systemcontext)){
        $graderurl = html_writer::link($CFG->wwwroot."/grade/report/grader/index.php?id=".$course->id."&enrolid=".$enrolid, '<i class="icon fa fa-star" aria-hidden="true"></i>', array('title' => get_string('grader', 'local_courses')));
        $action .= $graderurl;
    }
    if((has_capability('local/costcenter_course:report_view',
            context_system::instance()) || is_siteadmin())&&has_capability('local/costcenter_course:manage', $systemcontext)){
        $activityurl = html_writer::link($CFG->wwwroot."/report/outline/index.php?id=".$course->id, '<i class="icon fa fa-pie-chart" aria-hidden="true"></i>', array('title' => get_string('activity', 'local_courses')));
        $action .= $activityurl;
    }   
     $soldstatus = $DB->get_field('course','sold_status',array('id'=>$course->id));
 
    $action1 = '';
 
      if($soldstatus == 1){
              $tmpcourse = $DB->get_records_sql("SELECT id,fullname,open_parentcourseid FROM {course} WHERE open_parentcourseid =".$course->id);

         if(!empty($tmpcourse)){

            foreach($tmpcourse as $tempcourse){
                $enrolledusersquery = "SELECT e.id FROM {user_enrolments} ue JOIN {enrol} e ON ue.enrolid = e.id WHERE e.courseid = ".$tempcourse->id;
                 $enroledusers = $DB->get_records_sql_menu($enrolledusersquery);
             }
           
             if(!empty($enroledusers)){
               
                 $visibleurl = html_writer::link('javascript:void(0)','<i class="icon fa fa-toggle-on" aria-hidden="true"></i>', array('title' => get_string('enroledusers','local_courses'), 'id' => $course->id));
                $action1 .= $visibleurl;
              

              }
              else{
               $visibleurl = html_writer::link('javascript:void(0)','<i class="icon fa fa-toggle-on" aria-hidden="true"></i>', array('id' => $course->id, 'onclick'=>'(function(e){ require(\'local_courses/courseAjaxform\').courseavailability({id: '.$course->id.', context:1,action: "availabityyes",fullname:"'.$course->fullname.'" }) })(event)'));
                $action1 .= $visibleurl;
              }                     
       } 
       else{
           
            $visibleurl = html_writer::link('javascript:void(0)','<i class="icon fa fa-toggle-on" aria-hidden="true"></i>', array('id' => $course->id, 'onclick'=>'(function(e){ require(\'local_courses/courseAjaxform\').courseavailability({id: '.$course->id.', context:1,action: "availabityyes",fullname:"'.$course->fullname.'" }) })(event)'));
            $action1 = $visibleurl;
          }
        }else{
          
            $hideurl = html_writer::link('javascript:void(0)', '<i class="icon fa fa-toggle-off" aria-hidden="true"></i>', array('id' => $course->id, 'onclick'=>'(function(e){ require(\'local_courses/courseAjaxform\').courseavailability({id: '.$course->id.', context:1,action: "availabityno", fullname:"'.$course->fullname.'" }) })(event)'));
            $action1 = $hideurl;
        }
     /* if($course->visible == 1){
            $visibleurl = html_writer::link('javascript:void(0)', $OUTPUT->pix_icon('t/hide', get_string('inactive'), 'moodle', array('')), array('title' => get_string('inactive'), 'id' => $course->id, 'onclick'=>'(function(e){ require(\'local_courses/courseAjaxform\').userSuspend({id: '.$course->id.', context:1,action: "inactive",fullname:"'.$course->fullname.'" }) })(event)'));
            $action .= $visibleurl;
        }else{
            $hideurl = html_writer::link('javascript:void(0)', $OUTPUT->pix_icon('t/show', get_string('active'), 'moodle', array('')), array('title' => get_string('inactive'), 'id' => $course->id, 'onclick'=>'(function(e){ require(\'local_courses/courseAjaxform\').userSuspend({id: '.$course->id.', context:1,action: "active", fullname:"'.$course->fullname.'" }) })(event)'));
            $action .= $hideurl;
        }*/


 
   
    $row[] = html_writer::tag('a', $course->fullname,array('href' =>$CFG->wwwroot. '/course/view.php?id='.$course->id));  
    $row[] = $course->shortname;
    $sellresult = html_writer::tag('span',' ', array('id' => 'display_info'.$course->id));
    if($soldstatus == '1'){
        $status = "Yes";
    }
    else{
        $status = " ";
    }
    $curriculumcount = $DB->count_records_sql("SELECT COUNT(id) FROM {local_cc_semester_courses} WHERE open_parentcourseid =:courseid",array('courseid' => $course->id));
    $row[] = $action1;    
    $row[] = ($course->costcenter) ? $course->costcenter : 'N/A';
    $row[] = ($dept) ? $dept : 'N/A';
    $row[] = ($curriculumcount) ? $curriculumcount : 0;
  //  $row[] = $course->open_points != NULL ? $course->open_points: 'N/A';
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

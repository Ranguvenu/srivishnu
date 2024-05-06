<?php
define('AJAX_SCRIPT', true);
require_once(dirname(__FILE__) . '/../../config.php');
global $DB, $PAGE,$USER,$CFG,$OUTPUT;
require_once($CFG->dirroot.'/course/renderer.php');

$systemcontext = context_system::instance();
$PAGE->set_context($systemcontext);
require_once($CFG->dirroot.'/local/costcenter/lib.php');
$filterjson = optional_param('param', '',  PARAM_RAW);
$filterdata = json_decode($filterjson);

$requestData = $_REQUEST;
$perpage = $requestData['iDisplayLength'];
$recordsperpage = $requestData['iDisplayStart'];

$sSearch = $requestData['sSearch']; 
$systemcontext = context_system::instance();
$countsql = "SELECT  count(u.id) ";
$selectsql = "SELECT  u.* ";
// $formsql   ="  FROM {user} AS u WHERE u.id > 2 AND u.deleted = 0 ";// Commented by Harish //
$formsql   ="  FROM {user} AS u WHERE u.id > 2 AND u.deleted = 0 AND u.open_employee IS NOT NULL";// Query changes by Harish to fetch only Regular college users in local users view //
if(is_siteadmin() || has_capability('local/costcenter:manage_multiorganizations', $systemcontext)){
    $formsql .= " AND(u.firstname LIKE '%%$sSearch%%' OR u.lastname LIKE '%%$sSearch%%' OR u.username LIKE '%%$sSearch%%' OR u.email LIKE '%%$sSearch%%' OR u.open_employeeid LIKE '%%$sSearch%%')";
}else if(!is_siteadmin() && has_capability('local/costcenter:manage_ownorganization', $systemcontext)){
    $formsql .= " AND open_costcenterid = $USER->open_costcenterid AND(u.firstname LIKE '%%$sSearch%%' OR u.lastname LIKE '%%$sSearch%%' OR u.username LIKE '%%$sSearch%%' OR u.email LIKE '%%$sSearch%%' OR u.open_employeeid LIKE '%%$sSearch%%')";
}else if(!is_siteadmin() && has_capability('local/costcenter:manage_owndepartments', $systemcontext)){
    $formsql .= " AND open_costcenterid = $USER->open_costcenterid AND open_departmentid = $USER->open_departmentid AND(u.firstname LIKE '%%$sSearch%%' OR u.lastname LIKE '%%$sSearch%%' OR u.username LIKE '%%$sSearch%%' OR u.email LIKE '%%$sSearch%%' OR u.open_employeeid LIKE '%%$sSearch%%') ";
}
if(!is_siteadmin() || has_capability('local/costcenter:manage_multiorganizations', $systemcontext)){
    $formsql .= " AND u.id != $USER->id ";
}
if(!empty($filterdata->email) || !empty($filterdata->idnumber) || !empty($filterdata->designation) || !empty($filterdata->location) || !empty($filterdata->band) ){
    $lsemail = implode(',',$filterdata->email);
    $lsidnum = implode(',',$filterdata->idnumber);
    $lsdesignation = implode(',',$filterdata->designation);
    $lslocation = implode(',',$filterdata->location);
    $lsband = implode(',',$filterdata->band);
    if(!empty($lsemail)){
        $formsql .= " AND u.id IN ($lsemail)";
    }
    if(!empty($lsidnum)){
        $formsql .= " AND u.id IN ($lsidnum)";
    }
    if(!empty($lsdesignation)){
        $formsql .= " AND u.id IN ($lsdesignation)";
    }
    if(!empty($lslocation)){
        $formsql .= " AND u.id IN ($lslocation)";
    }
    if(!empty($lsband)){
        $formsql .= " AND u.id IN ($lsband)";
    }
}
    
if(!empty($filterdata->organizations)){
    $organizations = implode(',',$filterdata->organizations);
    $formsql .= " AND u.open_costcenterid IN ($organizations)";
}
if(!empty($filterdata->departments)){
    $departments = implode(',',$filterdata->departments);
    $formsql .= " AND u.open_departmentid IN ($departments)";
}
if(!empty($filterdata->role)){
    $roles = implode(',',$filterdata->role);
    $formsql .= " AND u.open_role IN ($roles)";
}
$totalusers = $DB->count_records_sql($countsql.$formsql);

$formsql .="order by u.id desc LIMIT $recordsperpage, $perpage";
$users = $DB->get_records_sql($selectsql.$formsql);
$output = '';
$data = array();
$myuser = new local_users\events\users();
foreach ($users as $user) {
    $row = array();
     $organization = $DB->get_field('local_costcenter', 'fullname', array('id'=>$user->open_costcenterid));
    $dept = $DB->get_field('local_costcenter', 'fullname', array('id'=>$user->open_departmentid));
   
    if(!$user->suspended){
        $status = 'Active';
    }else{
        $status = 'Inactive';
    }
    if(!empty($user->open_supervisorid)){            
        $supervisior=$DB->get_field_sql("SELECT CONCAT(firstname,' ',lastname) AS fullname 
                                         FROM {user} WHERE id = $user->open_supervisorid");
    } else{
        $supervisior = '--';
    }
     $action = '';
    $pruserscount = $DB->count_records('local_curriculum_users',array('userid' => $user->id));
    $enroluserscount = $DB->count_records('local_userenrolments_log',array('userid' => $user->id));
    if(has_capability('local/users:edit', context_system::instance())){
        if($pruserscount > 0 || $enroluserscount > 0){
            $editurl = html_writer::link('javascript:void(0)',  $OUTPUT->pix_icon('t/edit', get_string('edit'), 'moodle', array('')), array('title' => get_string('edit'), 'alt' => get_string('edit'),'data-value' => $user->id, 'onclick' =>'(function(e){ require("local_users/profileinfo").init({selector:"createusermodal", context:1, id:'. $user->id.' }) })(event)'));

            $action .= $editurl;
        }else{
            $editurl = html_writer::link('javascript:void(0)',  $OUTPUT->pix_icon('t/edit', get_string('edit'), 'moodle', array('')), array('title' => get_string('edit'), 'alt' => get_string('edit'),'data-value' => $user->id, 'onclick' =>'(function(e){ require("local_users/newuser").init({selector:"createusermodal", context:1, id:'. $user->id.', form_status:0, employee:'.$user->open_employee.'}) })(event)'));

            $action .= $editurl;
        }
    } 
    if(has_capability('local/users:delete', context_system::instance())){
        $roleshortname = $DB->get_field('role','shortname',array('id' => $user->open_role));
        if($roleshortname == 'faculty'){
            $trainerscount = $DB->count_records('local_cc_session_trainers',array('trainerid' => $user->id));
            $session_trainerscount = $DB->count_records('local_cc_course_sessions',array('trainerid' => $user->id));
            if($pruserscount > 0 || $enroluserscount > 0 || $trainerscount > 0 || $session_trainerscount > 0){
                $deleteurl = html_writer::link('javascript:void(0)', $OUTPUT->pix_icon('t/delete', get_string('delete'), 'moodle', array('')), array('title' => get_string('delete'), 'id' => $user->id, 'onclick'=>'(function(e){ require(\'local_departments/newdepartment\').messageConfirm({title:\'alert\' , message: \'confirmmessageforfaculty\', component:\'local_users\' }) })(event)'));

                $action .= $deleteurl;
            }else{
                $deleteurl = html_writer::link('javascript:void(0)', $OUTPUT->pix_icon('t/delete', get_string('delete'), 'moodle', array('')), array('title' => get_string('delete'), 'id' => $user->id, 'onclick'=>'(function(e){ require(\'local_users/newuser\').deleteConfirm({action:\'delete_user\' , id: '.$user->id.', context:1, fullname:"'.fullname($user).'" }) })(event)'));

                $action .= $deleteurl;
            }
        }else{
            if($pruserscount > 0 || $enroluserscount > 0){
                $deleteurl = html_writer::link('javascript:void(0)', $OUTPUT->pix_icon('t/delete', get_string('delete'), 'moodle', array('')), array('title' => get_string('delete'), 'id' => $user->id, 'onclick'=>'(function(e){ require(\'local_departments/newdepartment\').messageConfirm({title:\'alert\' , message: \'confirmmessage\', component:\'local_users\' }) })(event)'));

                $action .= $deleteurl;
            }else{
                $deleteurl = html_writer::link('javascript:void(0)', $OUTPUT->pix_icon('t/delete', get_string('delete'), 'moodle', array('')), array('title' => get_string('delete'), 'id' => $user->id, 'onclick'=>'(function(e){ require(\'local_users/newuser\').deleteConfirm({action:\'delete_user\' , id: '.$user->id.', context:1, fullname:"'.fullname($user).'" }) })(event)'));

                $action .= $deleteurl;
            }
        }

       //<revathi> - ODL-815 Faculty is not displaying starts 
        $enroluserscount1 = $DB->count_records('user_enrolments',array('userid' => $user->id));
         if(!$user->suspended && $session_trainerscount>0){
            $visibleurl = html_writer::link('javascript:void(0)', $OUTPUT->pix_icon('t/hide', get_string('inactive'), 'moodle', array('')), array('title' => get_string('inactive'), 'id' => $user->id, 'onclick'=>'(function(e){ require(\'local_departments/newdepartment\').messageConfirm({title:\'alert\' , message: \'confirmmessage\', component:\'local_users\' }) })(event)'));
            $action .= $visibleurl;
        // }elseif(!$user->suspended && $enroluserscount1 > 0 ){
        //      $visibleurl = html_writer::link('javascript:void(0)', $OUTPUT->pix_icon('t/hide', get_string('inactive'), 'moodle', array('')), array('title' => get_string('inactive'), 'id' => $user->id, 'onclick'=>'(function(e){ require(\'local_departments/newdepartment\').messageConfirm({title:\'alert\' , message: \'messageConfirmformooccourse\', component:\'local_users\' }) })(event)'));
        //     $action .= $visibleurl;
        }
        else{  
        //<revathi> - ODL-815 Faculty is not displaying ends 
            if(!$user->suspended){
                $visibleurl = html_writer::link('javascript:void(0)', $OUTPUT->pix_icon('t/hide', get_string('inactive'), 'moodle', array('')), array('title' => get_string('inactive'), 'id' => $user->id, 'onclick'=>'(function(e){ require(\'local_users/newuser\').userSuspend({id: '.$user->id.', context:1,action: "inactive", fullname:"'.fullname($user).'" }) })(event)'));
                $action .= $visibleurl;
            }else{
                $hideurl = html_writer::link('javascript:void(0)', $OUTPUT->pix_icon('t/show', get_string('active'), 'moodle', array('')), array('title' => get_string('active'), 'id' => $user->id, 'onclick'=>'(function(e){ require(\'local_users/newuser\').userSuspend({id: '.$user->id.',action: "active", context:1, fullname:"'.fullname($user).'" }) })(event)'));
                $action .= $hideurl;
            }   
        }
    }
    
   
    //$role = $DB->get_field('role', 'shortname', array('id'=>$user->open_role));
    //Revathi Issue ODL-765 starts
    $role = $DB->get_field('role', 'name', array('id'=>$user->open_role));
    //Revathi Issue ODL-765 ends

    $row[] = html_writer::tag('a', $user->firstname.' '.$user->lastname, 
               array('href' =>$CFG->wwwroot. '/local/users/profile.php?id='.$user->id));
    // $row[] = ($user->open_employeeid) ? $user->open_employeeid : 'N/A' ;
    $row[] = ($user->idnumber) ? $user->idnumber : 'N/A' ;
    $row[] = $user->email;
    $row[] = $organization ? $organization : 'N/A';
    // if ($role) {
    $row[] = $role ? $role : 'N/A';
    // } else if (is_siteadmin($user->id)) {
    //     $row[] = 'Manager';
    // } else {
    //     $row[] = 'Student';
    // }
    // $row[] = ($user->open_designation) ? $user->open_designation :'N/A';
    $row[] = $dept ? $dept : 'N/A';
    $row[] = $action ? $action : 'No actions';
    // $row[] = $supervisior;
    
    $data[] = $row;
}

$iTotal = $totalusers;
$iFilteredTotal = $iTotal;

$output = array(
	"sEcho" => intval($requestData['sEcho']),
	"iTotalRecords" => $iTotal,
	"iTotalDisplayRecords" => $iFilteredTotal,
	"aaData" => $data
);
echo json_encode($output);
?>

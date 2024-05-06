<?php
require_once('../../config.php');
require_once($CFG->dirroot.'/local/costcenter/lib.php');
require_once($CFG->dirroot.'/local/courses/filterclass.php');
global $CFG, $DB, $USER;

$action = optional_param('action','', PARAM_RAW);
$type = optional_param('type','', PARAM_RAW);
$like = optional_param('q','', PARAM_RAW);
$course_id = optional_param('courseid', 0,PARAM_INT);
$filterpage = optional_param('filterpage', '', PARAM_RAW);
$departments = optional_param('depts', '', PARAM_RAW);
$subdepartments = optional_param('subdepts', '', PARAM_RAW);

$page = optional_param('page', 0,PARAM_INT);

if(is_array($departments)){
    $departments = implode(',', $departments);
}else{
    $departments = $departments;
}
if(is_array($subdepartments)){
    $subdepartments = implode(',', $subdepartments);
}else{
    $subdepartments = $subdepartments;
}


$PAGE->set_context(context_system::instance());
$costcenterlib = new costcenter();
$filter_class = new custom_filter; 

if(($action == 'courseenroll')){
    if(is_siteadmin()){
        $costcenter="";
    }else{
        if($filterpage == 'course'){
            $costcenter=$DB->get_field('course','open_costcenterid',array('id'=>$course_id));
            if($costcenter==1){
                $costcenter=$DB->get_field('user','open_costcenterid',array('id'=>$USER->id)) ;
            }
        }else{
            $costcenter=$DB->get_field('user','open_costcenterid',array('id'=>$USER->id)) ;
        }
    }
    
    switch($type){
        case 'idnumber':
            $idnumbers = $costcenterlib->get_enrolledcoursefilter_users_employeeids($costcenter,$like,$page,$course_id, $filterpage);
            echo json_encode($idnumbers);
        break;
        case 'email':
            $emails = $costcenterlib->get_enrolledcoursefilter_users_emails($costcenter,$like,$page,$course_id, $filterpage);
            echo json_encode($emails);
        break;
        case 'department':
            $departments = $costcenterlib->get_enrolledcoursefilter_users_departments($costcenter,$like,$page,$course_id, $filterpage);
            echo json_encode($departments);
        break;
        case 'subdepartment':
            $subdepartments = $costcenterlib->get_enrolledcoursefilter_users_subdepartments($costcenter,$like,$page,$course_id, $filterpage,$departments);
            echo json_encode($subdepartments);
        break;
        case 'costcenter':
            $costcenters = $costcenterlib->get_enrolledcoursefilter_users_costcenters($like,$page,$course_id, $filterpage);
            echo json_encode($costcenters);
        break;
        case 'empname':
            $fullname = $filter_class->get_all_users_id_fullname($costcenter,$like,$page,$course_id, $filterpage);
            echo json_encode($fullname);
        break;
    }
}
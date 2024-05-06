<?php
require_once('../../config.php');
require_login();
$courseid = required_param('courseid', PARAM_INT);
$url = new moodle_url('/local/courses/course_users.php?id='.$courseid.'');
$systemcontext = $context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_url($url);
$PAGE->set_pagelayout('admin');
$PAGE->set_title(get_string('enrolledusers','local_courses'));
$coursename = $DB->get_field('course', 'fullname', array('id' => $courseid));
$PAGE->set_heading(get_string('enrolleduserslist', 'local_courses',$coursename));
$PAGE->requires->js_call_amd('local_courses/datatablesamd', 'coursesusers');
$studentroleid = $DB->get_field('role', 'id', array('shortname' => 'student'));
echo $OUTPUT->header();
$coursecontext = context_course::instance($courseid);
$userfields = 'u.id, u.firstname, u.lastname, u.open_employeeid, u.email, u.open_costcenterid, u.suspended';
$course_users = get_role_users($studentroleid, $coursecontext, false, $userfields);
$table = new html_table();
$table->id = "coursesusers";
$table->head = array('Username','PRN Number', 'Email', 'University', 'Status');
$data = array();
if(!empty($course_users)){
    foreach ($course_users as  $course_user) {
        $row = array();
        if(!$course_user->suspended){
            $status = 'Active';
        }else{
            $status = 'Inactive';
        }
        $university = $DB->get_field('local_costcenter', 'fullname', array('id'=>$course_user->open_costcenterid));

        $row[] = $course_user->firstname.' '.$course_user->lastname;
        $row[] = $course_user->open_employeeid ? $course_user->open_employeeid : '--';
        $row[] = $course_user->email;
        $row[] = $university;
        $row[] = $status;
        $data[] = $row;
    }
}else{
    $data[] = 'No Users to show';    
}
$table->data = $data;
echo html_writer::table($table);
echo $OUTPUT->footer();

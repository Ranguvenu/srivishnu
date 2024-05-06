<?php
// $Id: inscriptions_massives.php 356 2010-02-27 13:15:34Z ppollet $
/**
 * A bulk enrolment plugin that allow teachers to massively enrol existing accounts to their courses,
 * with an option of adding every user to a group
 * Version for Moodle 1.9.x courtesy of Patrick POLLET & Valery FREMAUX  France, February 2010
 * Version for Moodle 2.x by pp@patrickpollet.net March 2012
 */
set_time_limit(0);
ini_set('memory_limit', '-1');
require(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once($CFG->libdir.'/csvlib.class.php');
require_once ('lib.php');
require_once($CFG->dirroot.'/local/lib.php');

/// Get params

$id = required_param('id', PARAM_INT);

if (!$course = $DB->get_record('course', array('id' => $id))) {
    error("Course is misconfigured");
}


/// Security and access check

require_login();
$context =  context_system::instance();
 if(!has_capability('local/costcenter_course:enrol', $context)){
 redirect($CFG->wwwroot . '/local/courses/error.php?id=1');
 }
 
 /*Department level restrictions */
require_once($CFG->dirroot.'/local/includes.php');
$userlist=new has_user_permission();
	
    $haveaccess=$userlist->access_courses_permission($id);
	
	if(!$haveaccess) {
		 redirect($CFG->wwwroot . '/local/courses/error.php?id=2');
	}
/*Department level restrictions */	

/// Start making page
$PAGE->set_pagelayout('incourse');
$PAGE->set_url('/local/courses/mass_enroll.php', array('id'=>$id));

$strinscriptions = get_string('mass_enroll', 'local_courses');

$PAGE->set_title($course->fullname . ': ' . $strinscriptions);
$PAGE->set_heading($course->fullname . ': ' . $strinscriptions);

echo $OUTPUT->header();

$mform = new local_courses\form\mass_enroll_form($CFG->wwwroot . '/local/courses/mass_enroll.php', array (
	'course' => $course,
	'context' => $context,
    'type' => 'course'
));

if ($mform->is_cancelled()) {
    redirect(new moodle_url('/local/courses/courses.php'));
} else
if ($data = $mform->get_data(false)) { // no magic quotes
    echo $OUTPUT->heading($strinscriptions);

    $iid = csv_import_reader::get_new_iid('uploaduser');
    $cir = new csv_import_reader($iid, 'uploaduser');

    $content = $mform->get_file_content('attachment');

    $readcount = $cir->load_csv_content($content, $data->encoding, $data->delimiter_name);
    unset($content);

    if ($readcount === false) {
        print_error('csvloaderror', '', $returnurl);
    } else if ($readcount == 0) {
        print_error('csvemptyfile', 'error', $returnurl);
    }
   
    $result = mass_enroll($cir, $course, $context, $data);
    
    $cir->close();
    $cir->cleanup(false); // only currently uploaded CSV file 
	/** The code has been disbaled to stop sending auto maila and make loading issues **/
    
    echo $OUTPUT->box(nl2br($result), 'center');

    echo $OUTPUT->continue_button(new moodle_url('/course/view.php',array('id'=>$id))); // Back to course page
    echo $OUTPUT->footer($course);
    die();
}
echo $OUTPUT->heading_with_help($strinscriptions, 'mass_enroll', 'local_courses','icon',get_string('mass_enroll', 'local_courses'));
echo $OUTPUT->box (get_string('mass_enroll_info', 'local_courses'), 'center');
echo html_writer::link(new moodle_url('/local/courses/courses.php?s=1'),get_string('back', 'local_courses'),array('id'=>'back_tp_course'));
$sample = html_writer::link(new moodle_url('/local/courses/sample.php',array('format'=>'csv')),get_string('sample', 'local_courses'),array('id'=>'download_users'));
echo $sample;
$mform->display();
echo $OUTPUT->footer($course);

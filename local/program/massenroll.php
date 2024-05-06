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
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->dirroot.'/local/lib.php');
require_once($CFG->dirroot.'/local/program/lib.php');
require_once($CFG->dirroot.'/local/program/cronfunctionality.php');
require_once($CFG->dirroot . '/local/costcenter/lib.php');
require_once($CFG->dirroot . '/message/lib.php');
/// Get params
$id = required_param('id', PARAM_INT);
$ccid = optional_param('ccid',-1, PARAM_INT);
$programid = optional_param('id', -1, PARAM_INT);
$yearid = optional_param('year', -1, PARAM_INT);
$returnurl = new moodle_url('/local/program/index.php');

$STD_FIELDS = array('email','programcode');

$PRF_FIELDS = array();

$program = $DB->get_record('local_program', array('id' => $id));
if (empty($program)) {
    print_error('curriculum not found!');
}


/// Security and access check

require_login();
$context =  context_system::instance();
require_capability('local/program:manageprogram', $context);
require_capability('local/program:manageusers', $context);
 
/// Start making page
$PAGE->set_pagelayout('admin');
$PAGE->set_url('/local/program/massenroll.php', array('id'=>$id));

$strinscriptions = get_string('mass_enroll', 'local_courses');

$PAGE->set_title($program->fullname . ': ' . $strinscriptions);
$PAGE->set_heading($program->fullname . ': ' . $strinscriptions);

echo $OUTPUT->header();
$mform = new local_courses\form\mass_enroll_form($CFG->wwwroot . '/local/program/massenroll.php', array (
	'course' => $program,
    'context' => $context,
	'type' => 'program',
    'ccid'=>$ccid,
    'programid' => $programid,
    'year' => $yearid
));
if ($mform->is_cancelled()) {
    redirect(new moodle_url('/local/program/index.php'));
} else
if ($formdata = $mform->get_data()) { // no magic quotes
    echo $OUTPUT->heading($strinscriptions);

    $iid = csv_import_reader::get_new_iid('uploaduser');
    $cir = new csv_import_reader($iid, 'uploaduser');

    $content = $mform->get_file_content('attachment');

    $readcount = $cir->load_csv_content($content, $data->encoding, $data->delimiter_name);
    $cir->init();
    $linenum = 1; 
    unset($content);

    if ($readcount === false) {
        print_error('csvloaderror', '', $returnurl);
    } else if ($readcount == 0) {
        print_error('csvemptyfile', 'error', $returnurl);
    }

    $progresslibfunctions = new local_users\cron\progresslibfunctions();
    $filecolumns = $progresslibfunctions->uu_validate_user_upload_columns($cir, $STD_FIELDS, $PRF_FIELDS, $returnurl);

    $result = new local_program\cronfunctionality();
    $result->main_hrms_frontendform_method($cir,$filecolumns,$formdata);
   
    
    $cir->close();
    $cir->cleanup(false); // only currently uploaded CSV file 
	/** The code has been disbaled to stop sending auto maila and make loading issues **/
    
    echo $OUTPUT->box(nl2br($result), 'center');

    echo $OUTPUT->continue_button(new moodle_url('/local/program/view.php?ccid='.$program->curriculumid.'&prgid='.$program->id)); // Back to course page
    echo $OUTPUT->footer($program);
    die();
}
//echo $OUTPUT->heading_with_help($strinscriptions, 'mass_enroll', 'local_courses','icon',get_string('mass_enroll', 'local_courses'));

//<Revathi> issue ODL-805 starts
echo $OUTPUT->heading_with_help($strinscriptions, 'mass_enroll', 'local_courses');
//<Revathi> issue ODL-805 ends

echo $OUTPUT->box (get_string('mass_enroll_info', 'local_program'), 'center');
echo html_writer::link(new moodle_url('/local/program/view.php?ccid='.$program->curriculumid.'&prgid='.$program->id.'&type=2'),get_string('back', 'local_courses'),array('id'=>'back_tp_course'));

$sample = html_writer::link(new moodle_url('/local/program/sample.php',array('format'=>'csv')),get_string('sample', 'local_courses'),array('id'=>'download_users'));
echo $sample;
$mform->display();
echo $OUTPUT->footer($program);

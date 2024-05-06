<?php
require('../../../config.php');
global $CFG, $OUTPUT;
//require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->libdir . '/csvlib.class.php');
require_once($CFG->dirroot.'/local/mooccourses/classes/form/bulk_enroll_form.php');
require_once ($CFG->dirroot.'/local/mooccourses/classes/cron/bulkenrol/cronfunctionality.php');
// Instantiate a DateTime with microseconds.

$systemcontext = context_system::instance();
$PAGE->set_context($systemcontext);

$returnurl = new moodle_url('/local/mooccourses/index.php');

$PAGE->set_url('/local/mooccourses/bulkenrol/bulkuploadcron.php');

$PAGE->set_heading(get_string('bulkuploadenroll', 'local_mooccourses'));
$strheading = get_string('pluginname', 'local_mooccourses') . ' : ' . get_string('bulkenrolments', 'local_mooccourses');
$PAGE->set_title($strheading);
$PAGE->navbar->add(get_string('pluginname', 'local_mooccourses'), new moodle_url('/local/mooccourses/index.php'));
$PAGE->navbar->add(get_string('uploadenrol', 'local_mooccourses'));
$returnurl = new moodle_url('/local/mooccourses/index.php');
// - Sandeep bulk upload changes username is removed //
$STD_FIELDS = array('email','role','coursecode');

	$PRF_FIELDS = array();

$mform1 = new bulk_enrollform(null, array());
//print_object($mform1);
if ($mform1->is_cancelled()) {

	redirect($returnurl);
}
if ($formdata = $mform1->get_data()) {

echo $OUTPUT->header();

	
		
	$iid = csv_import_reader::get_new_iid('bulkuploadfile');
	$cir = new csv_import_reader($iid, 'bulkuploadfile');
	$content = $mform1->get_file_content('userfile');

	$readcount = $cir->load_csv_content($content, $formdata->encoding, $formdata->delimiter_name);
	$cir->init();
	$linenum = 1;
	$progresslibfunctions = new local_users\cron\progresslibfunctions();
	$filecolumns = $progresslibfunctions->uu_validate_user_upload_columns($cir, $STD_FIELDS, $PRF_FIELDS, $returnurl);

	$hrms= new local_mooccourses\cron\bulkenrol\cronfunctionality();

	$hrms->main_hrms_frontendform_method($cir,$filecolumns, $formdata);



echo $OUTPUT->footer();
}
else{
	echo $OUTPUT->header();
	
	//Revathi Issue ODl-833 removing Bulk User Enrolment string starts
		//	echo $OUTPUT->heading(get_string('uploadenrol', 'local_mooccourses'));
	//Revathi Issue ODl-833 removing Bulk User Enrolment stringends

    echo html_writer::link(new moodle_url('/local/mooccourses/'),'Back',array('id'=>'download_users'));
	echo html_writer::link(new moodle_url('/local/mooccourses/bulkenrol/sample.php?format=csv'),'Sample',array('id'=>'download_users'));
	echo html_writer::link(new moodle_url('/local/mooccourses/bulkenrol/help.php'),'Help manual' ,array('id'=>'download_users','target'=>'__blank'));
	$mform1->display();
	echo $OUTPUT->footer();
	die;
}


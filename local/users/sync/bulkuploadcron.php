<?php
echo 'hii';
// print_object('$csv->data');
// exit;
require('../../../config.php');
global $CFG, $OUTPUT;
// require_once($CFG->dirroot . '/local/users/parsecsv.lib.php');
require_once($CFG->libdir . '/csvlib.class.php');
// Instantiate a DateTime with microseconds.
echo $OUTPUT->header();
$d = new DateTime('NOW');
$filedate=$d->format('Ymd');
// # create new parseCSV object.
// $csv = new parseCSV();
// # Parse '_books.csv' using automatic delimiter detection.
// $csv->auto($CFG->dirroot.'/local/users/sync/testuser.csv');
$content = file_get_contents($CFG->dirroot.'/local/users/sync/csv/uploaduser_'.$filedate.'.csv');
if(!empty($content)){
	$STD_FIELDS = array('organization','username','employee_id','employee_name', 'first_name', 'middle_name', 'last_name', 'department','sub_department', 'address',
	                    'zone_region', 'area', 'city', 'role_designation', 'group', 'level', 'team', 'client', 'grade', 'gender','mobileno', 'email','marital_status','dob',
	                    'doj','state_name','employee_status','reportingmanager_code','reportingmanager_name','reportingmanager_email','dol','dor','country','officialmail');

	$PRF_FIELDS = array();
	$returnurl = new moodle_url('/local/users/index.php');

	$formdata = new stdClass();

	$formdata->option = 3;
	$formdata->enrollmentmethod = 1;
	$formdata->encoding = "UTF-8";
	$formdata->delimiter_name = "comma";
	$iid = csv_import_reader::get_new_iid('bulkuploadfile');
	$cir = new csv_import_reader($iid, 'bulkuploadfile');

	$readcount = $cir->load_csv_content($content, 'UTF-8', 'comma');
	$cir->init();
	$linenum = 1;
	$progresslibfunctions = new local_users\cron\progresslibfunctions();
	$filecolumns = $progresslibfunctions->uu_validate_user_upload_columns($cir, $STD_FIELDS, $PRF_FIELDS, $returnurl);

	$hrms= new local_users\cron\cronfunctionality();

	$hrms->main_hrms_frontendform_method($cir,$filecolumns, $formdata);

}else{
	echo 'file not found/ empty file error';
}

echo $OUTPUT->footer();

// print_object(file_get_contents($CFG->dirroot.'/local/users/sync/testuser.csv'));
// exit;
// print_object($csv->data);
// print_object($csv->titles);
// print_object($csv);
// exit;
// # Output result.
// if(!empty($csv->data)){
// 	$data=array();
// 	foreach ($csv->data as $key=>$value){
// 	    $theObject1=array_change_key_case($value,CASE_LOWER);
// 	    $data[]=(object)($theObject1); 
// 	}
// }
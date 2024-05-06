<?php

/**
 * script for downloading admissions
 */
require_once(dirname(__FILE__) . '/../../../config.php');
 require_once($CFG->libdir . '/adminlib.php');
 $format = optional_param('format', '', PARAM_ALPHA);
$systemcontext = context_system::instance();
if(!(has_capability('local/mooccourses:manage', $systemcontext) && has_capability('local/mooccourses:create', $systemcontext))){
    echo print_error('no permission');
}
 if ($format) {
   
    $fields = array(
        'email'=>'email',
        'role' => 'role',
        'coursecode'=>'coursecode',

    );

    switch ($format) {
        case 'csv' : user_download_csv($fields);
    }
    die;
}
function user_download_csv($fields) {

    global $CFG;
    require_once($CFG->libdir . '/csvlib.class.php');
    $filename = clean_filename('mooccourses');
    $csvexport = new csv_export_writer();
    $csvexport->set_filename($filename);
    $csvexport->add_data($fields);
	$userprofiledata = array();
	$csvexport->add_data($userprofiledata);
    $csvexport->download_file();
    die;
}






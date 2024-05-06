<?php

/**
 * script for downloading programs
 */
require_once(dirname(__FILE__) . '/../../config.php');
$format = optional_param('format', '', PARAM_ALPHA);

if ($format) {
    $fields = array(
        'student_PRN' => 'student_PRN',
        'coursecode' => 'coursecode',
        'firstname' => 'firstname',
        'lastname' => 'lastname',
        'email' => 'email',
        'mobile' => 'mobile',
        'address' => 'address',
        'country' => 'country',
        'city' => 'city',
        'dob' => 'dob',
        'gender' => 'gender',
        'role' => 'role'
    );
    switch ($format) {
        case 'csv' : user_download_csv($fields);
    }
    die;
}

function user_download_csv($fields) {
    global $CFG;
    require_once($CFG->libdir . '/csvlib.class.php');
    $filename = clean_filename(get_string('courseenrolment', 'local_sisprograms'));
    $csvexport = new csv_export_writer();
    $csvexport->set_filename($filename);
    $csvexport->add_data($fields);
    $userprofiledata = array();
    $csvexport->add_data($userprofiledata);
    $csvexport->download_file();
    die;
}

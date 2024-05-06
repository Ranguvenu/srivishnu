<?php

/**
 * script for downloading admissions
 */
require_once(dirname(__FILE__) . '/../../../config.php');
require_once($CFG->libdir . '/adminlib.php');
$format = optional_param('format', '', PARAM_ALPHA);

if ($format) {
    $fields = array(
		'fullname' => 'fullname',
        'shortname' => 'shortname',
		'category_path' => 'category_path',
        'coursetype' => 'coursetype',
        'summary' => 'summary',
        'points' => 'points',
        'cost' => 'cost',
        'department' => 'department',
    );

    switch ($format) {
        case 'csv' : user_download_csv($fields);
    }
    die;
}

function user_download_csv($fields) {
    global $CFG;
    require_once($CFG->libdir . '/csvlib.class.php');
    $filename = clean_filename(get_string('course'));
    $csvexport = new csv_export_writer();
    $csvexport->set_filename($filename);
    $csvexport->add_data($fields);
	$userprofiledata = array();
	$csvexport->add_data($userprofiledata);
    $csvexport->download_file();
    die;
}

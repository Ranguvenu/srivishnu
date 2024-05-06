<?php

/**
 * script for downloading admissions
 */
require_once(dirname(__FILE__) . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');
$format = optional_param('format', '', PARAM_ALPHA);
$systemcontext = context_system::instance();
if(!(has_capability('local/users:manage', $systemcontext) && has_capability('local/users:create', $systemcontext))){
    echo print_error('no permission');
}
if ($format) {
    if (is_siteadmin($USER) || has_capability('local/costcenter:manage_multiorganizations', $systemcontext)) {
        $fields = array(
            'university' => 'university',
            'department_or_college' => 'department_or_college'
        );
    }else if(!is_siteadmin() && has_capability('local/costcenter:manage_ownorganization', $systemcontext)){
        $fields = array(
            'department_or_college' => 'department_or_college'
        );
    }else if(!is_siteadmin() && has_capability('local/costcenter:manage_owndepartments', $systemcontext)){
        $fields = array(
        );
    }
    $fields += array(
        'email'=>'email',
        'first_name' => 'first_name',
        'last_name' => 'last_name',        
        'uniqueid' => 'uniqueid',
        'role' => 'role',
        'location' => 'location',
        'state'=>'state',
        'address'=>'address',
        'contactno' => 'contactno',
        'employee_status'=>'employee_status',

    );

    switch ($format) {
        case 'csv' : user_download_csv($fields);
    }
    die;
}
function user_download_csv($fields) {

    global $CFG;
    require_once($CFG->libdir . '/csvlib.class.php');
    $filename = clean_filename(get_string('users'));
    $csvexport = new csv_export_writer();
    $csvexport->set_filename($filename);
    $csvexport->add_data($fields);
	$userprofiledata = array();
	$csvexport->add_data($userprofiledata);
    $csvexport->download_file();
    die;
}

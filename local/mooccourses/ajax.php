<?php
define('AJAX_SCRIPT', true);
require_once(dirname(__FILE__) . '/../../config.php');
global $DB, $PAGE, $USER, $CFG, $OUTPUT;
require_once($CFG->dirroot.'/local/mooccourses/renderer.php');
require_once($CFG->dirroot . '/local/mooccourses/lib.php');

//require_once ($CFG->dirroot. '/mod/facetoface/lib.php');
//require_once($CFG->dirroot . '/local/users/lib.php');

global $DB, $PAGE,$CFG;
require_login();
$systemcontext = context_system::instance();
$PAGE->set_context($systemcontext);
$action = optional_param('action', 0, PARAM_TEXT);

switch ($action) {
	
	case 'mooccourseslist':
	 	$courses = mooccourseslistdetails();
		echo json_encode($courses);
	exit;
	break;
	
}

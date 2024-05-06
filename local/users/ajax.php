<?php
//define('AJAX_SCRIPT', true);

require_once(dirname(__FILE__) . '/../../config.php');
// require_once($CFG->dirroot . '/local/users/lib.php');
//require_once ($CFG->dirroot. '/mod/facetoface/lib.php');
//require_once($CFG->dirroot . '/local/users/lib.php');

global $DB, $PAGE,$CFG;
require_login();
$systemcontext = context_system::instance();
$PAGE->set_context($systemcontext);
$action = optional_param('action', 0, PARAM_TEXT);
$costcenter = optional_param('costcenter', 0, PARAM_INT);
$department = optional_param('department', 0, PARAM_INT);
$subdepartment = optional_param('subdepartment', 0, PARAM_INT);
$enroldep=optional_param('enroldept','',PARAM_TEXT);
$enrolsubdept=optional_param('enrolsubdept','',PARAM_TEXT);
$supervisor=optional_param('supervisor','',PARAM_TEXT);
$band=optional_param('band','',PARAM_TEXT);

$userlib = new local_users\functions\userlibfunctions();
switch ($action) {
	 case 'departmentlist':
	 	$departmentlist = $userlib->find_departments_list($costcenter);
	 	// $universitydepartments = $userlib->find_universitydepartments_list($costcenter);
	 	/*echo json_encode(['colleges' =>$departmentlist,'departments' => $universitydepartments]);*/
	 	echo json_encode(['colleges' => $departmentlist['nonuniv_dep'], 'departments' => $departmentlist['univ_dep']]);
	exit;
	break;
	case 'subdepartmentlist':
	 	$subdepartmentlist = $userlib->find_subdepartments_list($department);
		echo json_encode(['data' => $subdepartmentlist]);
	exit;
	break;

	case 'supervisorlist':
	 	$supervisorlist = $userlib->find_supervisor_list($supervisor);
		echo json_encode(['data' => $supervisorlist]);
	exit;
	break;
}

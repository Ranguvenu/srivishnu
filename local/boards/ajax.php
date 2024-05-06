<?php
//define('AJAX_SCRIPT', true);

require_once(dirname(__FILE__) . '/../../config.php');

global $DB, $PAGE,$CFG;
require_login();
$systemcontext = context_system::instance();
$PAGE->set_context($systemcontext);
$action = optional_param('action', 0, PARAM_TEXT);
$university = optional_param('university', 0, PARAM_INT);

$userlib = new local_faculties\functions\facultylibfunctions();
$boardslist = array();
if(!empty($school)){
$boardslist = $userlib->facultyform_boardslist($university);
}
echo json_encode(['data' =>$boardslist]);
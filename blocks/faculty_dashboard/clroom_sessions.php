<?php

define('AJAX_SCRIPT', true);
require_once(dirname(__FILE__) . '/../../config.php');
global $DB, $OUTPUT;
use \block_faculty_dashboard\output\view as view;

$systemcontext = context_system::instance();
$PAGE->set_context($systemcontext);

$return = (new view)->clroomsessions_dashboard();
echo json_encode($return);

<?php
require_once(dirname(__FILE__) . '/../../config.php');
require_once($CFG->dirroot . '/local/notifications/notification.php');
global $DB, $CFG, $USER, $PAGE, $OUTPUT;
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_login();
$PAGE->set_url('/local/notifications/course_remainder.php');
$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('admin');
$PAGE->set_title(get_string('pluginname', 'local_notifications'));
$PAGE->navbar->add(get_string('pluginname', 'local_notifications'));
echo $OUTPUT->header();
$type = "course_reminder";
$reminder = new notification_triger($type);
$reminder->enrol_reminder_schedule();
echo $OUTPUT->footer(); 


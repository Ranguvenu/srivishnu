<?php

require_once(dirname(__FILE__) . '/../../config.php');
global $CFG, $DB;
$systemcontext = get_context_instance(CONTEXT_SYSTEM);
$PAGE->set_context($systemcontext);
$PAGE->set_url('/local/users/help.php');
$PAGE->set_pagelayout('admin');
$strheading = get_string('pluginname', 'local_users') . ' : ' . get_string('manual', 'local_users');
$PAGE->set_title($strheading);
if(!(has_capability('local/users:manage', $systemcontext) && has_capability('local/users:create', $systemcontext))){
	echo print_error('no permission');
}
if ($CFG->forcelogin) {
    require_login();
} else {
    user_accesstime_log();
}
$PAGE->set_heading($SITE->fullname);
$PAGE->navbar->add(get_string('pluginname', 'local_users'), new moodle_url('/local/users/index.php'));
$PAGE->navbar->add(get_string('uploadusers', 'local_users'), new moodle_url('/local/users/sync/hrms_async.php'));
$PAGE->navbar->add(get_string('manual', 'local_users'));
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('manual', 'local_users'));
if (isset($CFG->allowframembedding) and ! $CFG->allowframembedding) {
    echo $OUTPUT->box(get_string('helpmanual', 'local_users'));
    echo '<div style="float:right;margin-bottom:15px;"><a href="sync/hrms_async.php"><button>' . get_string('back_upload', 'local_users') . '</button></a></div>';
}
echo get_string('help_1', 'local_users');
echo get_string('help_2', 'local_users');

echo $OUTPUT->footer();
?>

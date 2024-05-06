<?php

require_once(dirname(__FILE__) . '/../../config.php');
global $CFG, $DB;
$systemcontext = get_context_instance(CONTEXT_SYSTEM);

$PAGE->set_context($systemcontext);

$PAGE->set_url('/local/mooccourse/help.php');
$PAGE->set_pagelayout('admin');
$strheading = get_string('pluginname', 'local_mooccourses') . ' : ' . get_string('manual', 'local_mooccourses');
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
$PAGE->navbar->add(get_string('pluginname', 'local_mooccourses'), new moodle_url('/local/mooccourses/index.php'));
$PAGE->navbar->add(get_string('uploadenrol', 'local_mooccourses'), new moodle_url('/local/mooccourses/mass_enroll.php'));
$PAGE->navbar->add(get_string('manual', 'local_mooccourses'));
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('manual', 'local_mooccourses'));
if (isset($CFG->allowframembedding) and ! $CFG->allowframembedding) {
    echo $OUTPUT->box(get_string('helpmanual', 'local_mooccourses'));
    echo '<div style="float:right;"><a href="mass_enroll.php"><button>' . get_string('back_upload', 'local_mooccourses') . '</button></a></div>';
}
echo get_string('help_1', 'local_mooccourses');

echo $OUTPUT->footer();
?>

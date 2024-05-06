<?php
require_once(dirname(__FILE__) . '/../../config.php');
global $CFG, $DB;
$systemcontext = get_context_instance(CONTEXT_SYSTEM);
$PAGE->set_context($systemcontext);
$PAGE->set_url('/local/user/help.php');
$PAGE->set_pagelayout('admin');
$strheading = get_string('pluginname', 'local_users') . ' : ' . get_string('manual', 'local_users');
$PAGE->set_title($strheading);

if ($CFG->forcelogin) {
    require_login();
} else {
    user_accesstime_log();
}
$PAGE->set_heading($SITE->fullname);
$PAGE->navbar->add(get_string('pluginname', 'local_users'), new moodle_url('/local/users/index.php'));
$PAGE->navbar->add(get_string('uploadusers', 'local_users'), new moodle_url('/local/users/upload.php'));
$PAGE->navbar->add(get_string('manual', 'local_users'));
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('manual', 'local_users'));
if (isset($CFG->allowframembedding) and ! $CFG->allowframembedding) {
    echo $OUTPUT->box(get_string('helpmanual', 'local_users'));
    echo '<div style="float:right;"><a href="upload.php"><button>' . get_string('back_upload', 'local_users') . '</button></a></div>';
}
if (isset($CFG->allowframembedding) and ! $CFG->allowframembedding) {
    echo '<b >' . $OUTPUT->box('<p style="color:red;">' . get_string('delimited', 'local_scheduleexam') . '</p>') . '</b>';
}
$country = get_string_manager()->get_list_of_countries();


$countries = array();
foreach ($country as $key => $value) {
    $countries[] = $key . ' => ' . $value;
}

echo get_string('help_1', 'local_users');
$select = new single_select(new moodle_url('#'), 'proid', $countries, null, '');
$select->set_label('');
echo $OUTPUT->render($select);
echo get_string('help_2', 'local_users');

echo $OUTPUT->footer();
?>

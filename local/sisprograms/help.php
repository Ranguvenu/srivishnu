<?php

require_once(dirname(__FILE__) . '/../../config.php');
global $CFG, $DB;
$systemcontext = context_system::instance();
$PAGE->set_url('/local/programs/help.php');
$PAGE->set_pagelayout('admin');
$PAGE->set_context($systemcontext);
if ($CFG->forcelogin) {
    require_login();
} else {
    user_accesstime_log();
}
$PAGE->set_title(get_string('helpmanual', 'local_sisprograms') . ': ' . get_string('helpmanual', 'local_sisprograms'));
$PAGE->set_heading($SITE->fullname);
$PAGE->navbar->add(get_string('pluginname', 'local_sisprograms'), new moodle_url('/local/sisprograms/index.php'));
$PAGE->navbar->add(get_string('uploadcourses', 'local_sisprograms'), new moodle_url('/local/sisprograms/upload.php'));
$PAGE->navbar->add(get_string('helpmanual', 'local_sisprograms'));
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('helpmanual', 'local_sisprograms'));
if (isset($CFG->allowframembedding) and ! $CFG->allowframembedding) {
    // echo $OUTPUT->box(get_string('helpmanual', 'local_sisprograms'));
    echo '<div class="pull-right help-btn"><a href="upload.php"><button>' . get_string('back_upload', 'local_sisprograms') . '</button></a></div>';
}
if (isset($CFG->allowframembedding) and ! $CFG->allowframembedding) {
    echo '<b >' . $OUTPUT->box('<p style="color:red;">' . get_string('delimited', 'local_sisprograms') . '</p>') . '</b>';
}

echo get_string('help_tab', 'local_sisprograms');
echo $OUTPUT->footer();


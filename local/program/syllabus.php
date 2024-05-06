<?php
require_once('../../config.php');
require_login();
require_once $CFG->dirroot . '/local/program/lib.php';
global $OUTPUT;
$pid = required_param('pid', PARAM_INT);
$ccid = optional_param('ccid', '', PARAM_INT);
$url = new moodle_url('/local/program/syllabus.php?pid='.$pid.'');
$systemcontext = $context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_url($url);
$PAGE->set_pagelayout('admin');
$PAGE->set_title(get_string('syllabus','local_program'));
// $labelname=get_string('view_programs','local_program');
$PAGE->navbar->add(get_string("view_programs", 'local_program'), new moodle_url('/local/program/view.php',array('ccid'=>$ccid,'prgid' => $pid)));
$PAGE->navbar->add(get_string('syllabus','local_program'));
$programname = $DB->get_field('local_program', 'fullname', array('id' => $pid));
$PAGE->set_heading(get_string('viewsyllabus', 'local_program'));
echo $OUTPUT->header();
$context = [
    'syllabus' => get_user_program_syllabus($pid)
];
echo $OUTPUT->render_from_template('local_program/syllabusview', $context);
echo $OUTPUT->footer();

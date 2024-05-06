<?php
// $Id: inscriptions_massives.php 356 2010-02-27 13:15:34Z ppollet $
/**
 * A bulk enrolment plugin that allow teachers to massively enrol existing accounts to their courses,
 * with an option of adding every user to a group
 * Version for Moodle 1.9.x courtesy of Patrick POLLET & Valery FREMAUX  France, February 2010
 * Version for Moodle 2.x by pp@patrickpollet.net March 2012
 */
set_time_limit(0);
ini_set('memory_limit', '-1');
require(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once($CFG->libdir.'/csvlib.class.php');
require_once ('lib.php');
require_once($CFG->dirroot.'/local/lib.php');

/// Get params

$id = required_param('id', PARAM_INT);

// $groups = $DB->get_record('local_groups', array('id'=>$id));
$groups = $DB->get_record('cohort', array('id'=>$id), '*', MUST_EXIST);
if (empty($groups)) {
   print_error("Groups not found");
}

/// Security and access check

require_login();
$context =  context_system::instance();
require_capability('moodle/cohort:assign', $context);

/// Start making page
$PAGE->set_pagelayout('admin');
$PAGE->set_url('/local/groups/mass_enroll.php', array('id'=>$id));

$strinscriptions = get_string('mass_enroll', 'local_courses');

$PAGE->set_title($groups->name . ': ' . $strinscriptions);
$PAGE->set_heading($groups->name . ': ' . $strinscriptions);

echo $OUTPUT->header();

$mform = new local_courses\form\mass_enroll_form($CFG->wwwroot . '/local/groups/mass_enroll.php', array (
	'course' => $groups,
    'context' => $context,
	'type' => 'groups'
));

if ($mform->is_cancelled()) {
    redirect(new moodle_url('/local/groups/index.php'));
} else
if ($data = $mform->get_data(false)) { // no magic quotes
    echo $OUTPUT->heading($strinscriptions);

    $iid = csv_import_reader::get_new_iid('uploaduser');
    $cir = new csv_import_reader($iid, 'uploaduser');

    $content = $mform->get_file_content('attachment');

    $readcount = $cir->load_csv_content($content, $data->encoding, $data->delimiter_name);
    unset($content);

    if ($readcount === false) {
        print_error('csvloaderror', '', $returnurl);
    } else if ($readcount == 0) {
        print_error('csvemptyfile', 'error', $returnurl);
    }
   
    $result = groups_mass_enroll($cir, $groups, $context, $data);
    
    $cir->close();
    $cir->cleanup(false); // only currently uploaded CSV file 
	/** The code has been disbaled to stop sending auto maila and make loading issues **/
    
    echo $OUTPUT->box(nl2br($result), 'center');

    echo $OUTPUT->continue_button(new moodle_url('/local/groups/index.php')); // Back to course page
    echo $OUTPUT->footer($groups);
    die();
}
//echo $OUTPUT->heading_with_help($strinscriptions, 'mass_enroll', 'local_courses','icon',get_string('mass_enroll', 'local_courses'));

//Revathi Issues ODL-805
echo $OUTPUT->heading_with_help($strinscriptions, 'mass_enroll', 'local_courses');
//Revathi Issues ODL-805

echo $OUTPUT->box (get_string('mass_enroll_info', 'local_courses'), 'center');
echo html_writer::link(new moodle_url('/local/groups/index.php'),get_string('back', 'local_courses'),array('id'=>'back_tp_course'));
$sample = html_writer::link(new moodle_url('/local/courses/sample.php',array('format'=>'csv')),get_string('sample', 'local_courses'),array('id'=>'download_users'));
echo $sample;
$mform->display();
echo $OUTPUT->footer($groups);

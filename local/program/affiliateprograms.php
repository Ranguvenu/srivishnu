<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Assign roles to users.
 *
 * @package    core_role
 * @copyright  1999 onwards Martin Dougiamas (http://dougiamas.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


require_once(__DIR__ . '/../../config.php');

define('NO_OUTPUT_BUFFERING', true);
define("MAX_COLLEGES_TO_LIST_PER_PROGRAM", 10);
global $OUTPUT;
$pid    = required_param('pid', PARAM_INT);
$type    = optional_param('type',1,PARAM_INT);
$uid    = optional_param('uid', 0, PARAM_INT);
$returnto  = optional_param('return', null, PARAM_ALPHANUMEXT);

$systemcontext = context_system::instance();
$url = new moodle_url('/local/program/affiliateprograms.php', array('pid' => $pid, 'uid' => $uid,'type'=>$type));
require_once($CFG->dirroot . '/local/program/lib.php');
require_once($CFG->dirroot . '/cohort/lib.php');
require_once($CFG->dirroot. '/course/lib.php');
// Security.
require_login();
use local_program\program;
$PAGE->set_url($url);
$PAGE->set_context($systemcontext);
if (!$enrol_manual = enrol_get_plugin('manual')) {
    throw new coding_exception('Can not instantiate enrol_manual');
}

$programname = $DB->get_field('local_program', 'fullname', array('id' => $pid, 'costcenter' => $uid));

$title = get_string('affiliateprogramsdis', 'local_program',$programname);

$PAGE->set_pagelayout('admin');
$PAGE->set_title($title);
$PAGE->navbar->add(get_string("pluginname", 'local_program'), new moodle_url('/local/program/index.php',array('type'=>$type)));
$PAGE->navbar->add($title);
$PAGE->set_heading($title);
$renderder = $PAGE->get_renderer('local_program');

/*$curriculumidexist = $DB->get_field('local_curriculum', 'id', array('program' => $pid, 'costcenter' => $uid));*/
$curriculumidexist = $DB->get_field('local_program', 'curriculumid', array('id' => $pid, 'costcenter' => $uid));
// print_object($curriculumidexist);exit;
if (!$curriculumidexist) {
    print_error('curriculum not exist!');
}

if ($pid > 0) {
    // Create the user selector objects.
    $options = array('context' => $systemcontext->id, 'pid' => $pid, 'uid' => $uid);
    $potentialcollegeselector = new local_programs_potential_colleges('addselect', $options);
    $currentcollegeselector = new local_programs_existing_colleges('removeselect', $options);

    // Process incoming college assignments.
    $errors = array();

    if (optional_param('add', false, PARAM_BOOL) && confirm_sesskey()) {
        $collegestoassign = $potentialcollegeselector->get_selected_users();
        if (!empty($collegestoassign)) {
            $datasubmitted = data_submitted();
            $program = $DB->get_record('local_program', array('id' => $pid, 'costcenter' => $uid));

            echo $OUTPUT->header();
            $progressbar = new \core\progress\display_if_slow(get_string('affiliateprogramsprogress', 'local_program',$program->fullname));

            $progressbar->start_html();

            // $transaction = $DB->start_delegated_transaction();

            $progressbar->start_progress('', count($collegestoassign));
            $return='';
            foreach ($collegestoassign as $addcollege) {

                $progressbar->increment_progress();

                $return.=(new program)->copy_program_instance($pid,$addcollege,$showfeedback = true,$url);
            }

            // $transaction->allow_commit();

            $progressbar->end_html();

            $potentialcollegeselector->invalidate_selected_users();
            $currentcollegeselector->invalidate_selected_users();

            $result=new stdClass();
            $result->changecount=count($collegestoassign);
            $result->program=$program->fullname;

            echo $return;
            echo $OUTPUT->notification(get_string('affiliateprogramssuccess', 'local_program',$result),'success');
            $button = new single_button($PAGE->url, get_string('click_continue','local_program'), 'get', true);
            $button->class = 'continuebutton';
            echo $OUTPUT->render($button);

            echo $OUTPUT->footer();

            die();

        }
        //redirect($PAGE->url);
    }

    // Process incoming role unassignments.
    if (optional_param('remove', false, PARAM_BOOL) && confirm_sesskey()) {
        $collegestounassign = $currentcollegeselector->get_selected_users();
        if (!empty($collegestounassign)) {
            $program = $DB->get_record('local_program', array('id' => $pid, 'costcenter' => $uid));

            echo $OUTPUT->header();


            $progressbar = new \core\progress\display_if_slow(get_string('unaffiliateprogramsprogress', 'local_program',$program->fullname));

            $progressbar->start_html();

            $transaction = $DB->start_delegated_transaction();

            $progressbar->start_progress('', count($collegestounassign));
            $return='';
            foreach ($collegestounassign as $removecollege) {

                $progressbar->increment_progress();

                $programid=$DB->get_field('local_program','id',array('parentid'=>$pid,'costcenter'=>$removecollege->id));

                $return.=(new program)->uncopy_program_instance($programid,$showfeedback = true,$progress=true,$removecollege);
            }

            $transaction->allow_commit();

            $progressbar->end_html();
            $potentialcollegeselector->invalidate_selected_users();
            $currentcollegeselector->invalidate_selected_users();

            $result=new stdClass();
            $result->changecount=count($collegestounassign);
            $result->program=$program->fullname;
                echo $return;
                echo $OUTPUT->notification(get_string('unaffiliateprogramssuccess', 'local_program',$result),'success');
                $button = new single_button($PAGE->url, get_string('click_continue','local_program'), 'get', true);
                $button->class = 'continuebutton';
                echo $OUTPUT->render($button);

            echo $OUTPUT->footer();

            die();
        }
        //redirect($PAGE->url);
    }
}

echo $OUTPUT->header();


if(!has_capability('local/program:manageprogram', $systemcontext) && !has_capability('local/program:affiliateprograms', $systemcontext)){
    print_error('You donot have permission this page.');
}else{
    // Print heading.
    echo $OUTPUT->heading($title);

    echo $renderder->assignaffiliateprograms($pid, $uid, $currentcollegeselector, $potentialcollegeselector);

    $button = new single_button(new moodle_url($CFG->wwwroot . '/local/program/index.php',array('type'=>1)), get_string('continue'), 'get');
    $button->class = 'continuebutton';

    echo $OUTPUT->render($button);
}
echo $OUTPUT->footer();

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
 * Take attendance in curriculum.
 *
 * @package    local_curriculum
 * @copyright  2017 M Arun Kumar <arun@eabyas.in>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
global $CFG, $DB, $PAGE, $USER, $OUTPUT;
require_once($CFG->dirroot.'/local/program/lib.php');
require_login();
use local_program\program;
$curriculumid = required_param('ccid', PARAM_INT);
$programid = required_param('programid', PARAM_INT);
$sessionid = required_param('id', PARAM_INT);
$semesterid = optional_param('semesterid', 0, PARAM_INT);
$bclcid = optional_param('bclcid', 0, PARAM_INT);
$enrol = optional_param('enrol', 0, PARAM_INT);
require_login();
$context = context_system::instance();
$PAGE->set_context($context);
$url = new moodle_url($CFG->wwwroot . '/local/program/enrol.php', array('ccid' => $curriculumid,
    'id' => $sessionid));

$PAGE->set_url($url);
$PAGE->set_pagelayout('admin');
if ($enrol > 0) {
    $enroldata = new stdClass();
    $enroldata->curriculumid = $curriculumid;
    $enroldata->programid = $programid;
    $enroldata->semesterid = $semesterid;
    $enroldata->bclcid = $bclcid;
    $enroldata->sessionid = $sessionid;
    $enroldata->enrol = $enrol;
    $enroldata->userid = $USER->id;
    $signup = (new program)->bc_session_enrolments($enroldata);
    if ($signup) {
        redirect($CFG->wwwroot . '/local/program/view.php?ccid=' . $curriculumid.'&prgid='.$programid);
    }
}

echo $OUTPUT->header();
echo $OUTPUT->footer();

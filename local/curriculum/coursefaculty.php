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
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
global $CFG, $DB, $PAGE, $USER, $OUTPUT;
require_once($CFG->dirroot.'/local/curriculum/lib.php');
require_login();
use local_curriculum\program;
$yearid = required_param('yearid', PARAM_INT);
$semesterid = required_param('semesterid', PARAM_INT);
$courseid = required_param('courseid', PARAM_INT);
$download = optional_param('download', false, PARAM_BOOL);
require_login();
$context = context_system::instance();

$PAGE->set_context($context);
$PAGE->set_title(get_string('faculty', 'local_curriculum'));

$curriculum = $DB->get_record_sql('SELECT cc.*
                                     FROM {local_curriculum} cc
                                     JOIN {local_cc_semester_courses} ccsc ON ccsc.curriculumid = cc.id
                                    WHERE ccsc.courseid = :courseid AND ccsc.semesterid = :semesterid AND ccsc.yearid = :yearid ', array('yearid' => $yearid, 'semesterid' => $semesterid, 'courseid' => $courseid));


$url = new moodle_url($CFG->wwwroot . '/local/curriculum/coursefaculty.php', array('yearid' => $yearid, 'semesterid' => $semesterid, 'courseid' => $courseid));
$PAGE->requires->js_call_amd('local_curriculum/ajaxforms', 'load');

$PAGE->requires->js_call_amd('local_curriculum/curriculum_views', 'FacultysDatatable',
                    array(array('yearid' => $yearid, 'semesterid' => $semesterid, 'courseid' => $courseid)));
$renderer = $PAGE->get_renderer('local_curriculum');
$PAGE->set_url($url);
$PAGE->navbar->add(get_string("pluginname", 'local_curriculum'), new moodle_url('/local/curriculum/index.php'));
$PAGE->navbar->add($curriculum->name, new moodle_url('/local/curriculum/view.php', array('ccid' => $curriculum->id)));
$PAGE->navbar->add(get_string("faculty", 'local_curriculum'));
$PAGE->set_heading(get_string('faculty', 'local_curriculum', $curriculum->name));
$PAGE->set_pagelayout('admin');
if(!$download) {
	echo $OUTPUT->header();
	$stable = new stdClass();
	$stable->thead = true;
	$stable->start = 0;
	$stable->length = -1;
	$stable->search = '';
	$stable->yearid = $yearid;
    $stable->semesterid = $semesterid;
    $stable->courseid = $courseid;
    $stable->curriculumid = $curriculum->id;
	echo $renderer->viewcoursefaculty($stable);
	echo $OUTPUT->footer();
} else {
	 // $search = optional_param('search', '', PARAM_RAW);exit;
     $exportplugin = $CFG->dirroot . '/local/curriculum/export_xls.php';
     if (file_exists($exportplugin)) {
         require_once($exportplugin);
         if(!empty($curriculumid)){
         	$stable = new stdClass();
			$stable->thead = true;
			$stable->start = 0;
			$stable->length = -1;
			$stable->search = '';
			$stable->curriculumid = $curriculumid;
         	export_report($curriculumid, $stable, $type);
         }
     }
     die;
}
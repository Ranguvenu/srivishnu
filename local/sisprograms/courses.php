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
 * List the tool provided in a course
 *
 * @package    local
 * @subpackage sisprograms
 * @copyright  2019 S Sarath kumar <sarath@eabyas.in>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once(dirname(__FILE__) . '/../../config.php');
global $DB, $PAGE,$CFG,$OUTPUT;
$PAGE->requires->jquery();
require_once($CFG->dirroot . '/local/sisprograms/lib.php');

require_login();
if ($CFG->forcelogin) {
    require_login();
} else {
    user_accesstime_log();
}

$PAGE->requires->js('/local/sisprograms/js/jquery.dataTables.min.js',true);
$PAGE->requires->js('/local/sisprograms/js/synctable.js',true);
$PAGE->requires->css('/local/sisprograms/css/jquery.dataTables.css');

$systemcontext = context_system::instance();
$PAGE->set_context($systemcontext);
$PAGE->set_url('/local/sisprograms/index.php');
$PAGE->set_pagelayout('admin');
$PAGE->set_title(get_string('sisprograms', 'local_sisprograms'));
require_login();
$PAGE->navbar->add(get_string('pluginname', 'local_sisprograms'), new moodle_url('/local/sisprograms/index.php'));
$PAGE->navbar->add('Master Courses report');
$PAGE->set_heading('Master Courses report');
$myprogram = sisprograms::getInstance();

echo $OUTPUT->header();
//If the loggedin user have the capability of managing the batches allow the page
$capabilities_array =$myprogram->sisprogram_capabilities(); 
if (!has_any_capability($capabilities_array, $systemcontext)) {
    print_cobalterror('permissions_error', 'local_collegestructure');
}
    $currenttab = 'courses';
    $myprogram->createtabview($currenttab);

echo html_writer::link(new moodle_url('/local/sisprograms/upload.php'),'Back to Upload',array('id'=>'masterdatabackbutton', 'style' => 'float:right;'));

$table = new html_table();
$table->id = 'siscoursestable';
$table->head = array('Course Name', 'Course Code', 'Program Name', 'University');
$table->align = array('left','left','left', 'left');
echo html_writer::table($table);
echo $OUTPUT->footer();

/*$myprogram = sisprograms::getInstance();
$systemcontext = context_system::instance();

$systemcontext = context_system::instance();
$PAGE->set_context($systemcontext);
$PAGE->set_pagelayout('admin');
require_login();

if ($CFG->forcelogin) {
    require_login();
} else {
    user_accesstime_log();
}
//If the loggedin user have the capability of managing the batches allow the page
$capabilities_array =$myprogram->sisprogram_capabilities(); 
if (!has_any_capability($capabilities_array, $systemcontext)) {
    print_cobalterror('permissions_error', 'local_collegestructure');
}
$PAGE->set_url('/local/sisprograms/courses.php');
$PAGE->set_title(get_string('sisprograms', 'local_sisprograms'));
//Header and the navigation bar
$PAGE->set_heading(get_string('uploadcoursesreport', 'local_sisprograms'));

$PAGE->navbar->add(get_string('pluginname', 'local_sisprograms'), new moodle_url('/local/sisprograms/index.php'));
$PAGE->navbar->add(get_string('uploadcoursesreport', 'local_sisprograms'));
// $myprogram = sisprograms::getInstance();

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('uploadcoursesreport', 'local_sisprograms'));
echo html_writer::link(new moodle_url('/local/sisprograms/upload.php'),'Back to Upload',array('id'=>'programslist','style'=>'float:right;'));
try {

    if (is_siteadmin()) {
        $sql = "SELECT lo.id,lo.courseid,lo.programid,lo.costcenterid,c.fullname,lo.coursecode FROM {local_sisonlinecourses} as lo JOIN {course} as c ON c.id = lo.courseid";
        $siscourses = $DB->get_records_sql($sql);
    }
    $count = count($siscourses); //Count of schools to which registrar is assigned
    if ($count < 1) {
        throw new Exception(get_string('nomanageuploadcourses', 'local_sisprograms'));
    }

    $data = array();

        foreach ($siscourses as $course) {
            $line = array();
            $line[] = $course->fullname;
            $line[] = $course->coursecode;
            $line[] = $DB->get_field('local_sisprograms','fullname',array('id' => $course->programid));
            $line[] = $DB->get_field('local_costcenter', 'fullname', array('id' => $course->costcenterid));
            $data[] = $line;
        }
    $currenttab = 'courses';
    $myprogram->createtabview($currenttab);

    if (isset($CFG->allowframembedding) and ! $CFG->allowframembedding) {
        echo $OUTPUT->box(get_string('viewuploadcoursespage', 'local_sisprograms'));
    }

    $PAGE->requires->js('/local/sisprograms/js/siscourse.js');

    echo "<div id='filter-box' >";
    echo '<div class="filterarea"></div></div>';

    $table = new html_table();
    $table->id = "siscoursestable";
    $head = array();
    $head[] = get_string('coursename', 'local_sisprograms');
    $head[] = get_string('coursecode', 'local_sisprograms');
    $head[] = get_string('programname', 'local_sisprograms');
    $head[] = get_string('university', 'local_sisprograms');
    
    $table->head = $head;
    $table->align = array('left', 'left', 'left', 'left');
    $table->width = '100%';
    $table->data = $data;
    echo html_writer::table($table);
} catch (Exception $e) {
    echo $e->getMessage();
}
echo $OUTPUT->footer();*/

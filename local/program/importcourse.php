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
 * Handle ajax requests in curriculum
 *
 * @package    local_curriculums
 * @copyright  2018 Arun Kumar M {arun@eabyas.in}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
// define('CLI_SCRIPT', true);
require_once(dirname(__FILE__) . '/../../config.php');
global $DB, $CFG, $USER, $PAGE;

$courseid = optional_param('cid', 0, PARAM_INT);
$curriculumid = optional_param('curriculumid', 0, PARAM_INT);

$context = context_system::instance();
require_login();
$PAGE->set_context($context);
// . ' course-restore -e ' . $CFG->dataroot . '/' . $parentcourseid . '.mbz ' . $courseid
$parentcourseid = $DB->get_field('course', 'open_parentcourseid', array('id' => $courseid));

if (!is_dir($CFG->dataroot . DIRECTORY_SEPARATOR . 'courseimport')) {
    @mkdir($CFG->dataroot . DIRECTORY_SEPARATOR . 'courseimport', 0777, true);
}

if ($parentcourseid > SITEID) {
   // echo "test";
    shell_exec('rm -rf ' . $CFG->dataroot . '/courseimport/' . $courseid . '.mbz ');
    $command = 'moosh course-backup -f ' . $CFG->dataroot . '/courseimport/' . $courseid . '.mbz ' . $parentcourseid ;
    $output = shell_exec($command);
    $command1 = 'moosh course-restore -e ' . $CFG->dataroot . '/courseimport/' . $courseid . '.mbz ' . $courseid;
    $output1 = shell_exec($command1);

    shell_exec('rm -rf ' . $CFG->dataroot . '/courseimport/' . $courseid . '.mbz ');
} else {
   // echo "test1";
   $output1 = false;
}
//exit;
if ($output1) {
    $DB->execute('UPDATE {local_cc_semester_courses} SET importstatus = 1
            WHERE curriculumid = :curriculumid AND courseid = :courseid ', array('curriculumid' => $curriculumid, 'courseid' => $courseid));
    redirect($CFG->wwwroot . '/course/view.php?id=' . $courseid);
} else {
    redirect($CFG->wwwroot . '/local/program/view.php?ccid=' . $curriculumid);
}
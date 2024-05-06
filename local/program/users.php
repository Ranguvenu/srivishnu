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
$programid = optional_param('prgid', 0, PARAM_INT);
$yearid = optional_param('yearid', 0, PARAM_INT);
$download = optional_param('download', 0, PARAM_INT);
$type = optional_param('type', '', PARAM_RAW);
$search = optional_param_array('search', '', PARAM_RAW);
require_login();
$context = context_system::instance();
$curriculum = $DB->get_record('local_curriculum', array('id' => $curriculumid));
$program = $DB->get_record('local_program', array('id' => $programid));
$PAGE->set_context($context);
$PAGE->set_title('Enrolled Users');
$urlparams = array();
$urlparams['ccid'] = $curriculumid;
if ($yearid > 0) {
    $urlparams['yearid'] = $yearid;
}
$url = new moodle_url($CFG->wwwroot . '/local/program/users.php', $urlparams);
$PAGE->requires->js_call_amd('local_program/program', 'UsersDatatable',
                    array(array('curriculumid' => $curriculumid, 'yearid' => $yearid)));
$renderer = $PAGE->get_renderer('local_program');
$PAGE->set_url($url);
$PAGE->navbar->add(get_string("pluginname", 'local_program'), new moodle_url('/local/program/index.php'));
$PAGE->navbar->add($program->fullname, new moodle_url('/local/program/view.php', array('ccid' => $curriculumid, 'prgid' => $program->id)));
$PAGE->navbar->add(get_string("enrolledusers", 'local_program'));
$PAGE->set_heading(get_string('enrolledusers', 'local_program', $program->fullname));
$PAGE->set_pagelayout('admin');

require_capability('local/program:viewusers', $context);

if (!$download) {
    echo $OUTPUT->header();
    $stable = new stdClass();
    $stable->thead = true;
    $stable->start = 0;
    $stable->length = -1;
    $stable->search = '';
    $stable->curriculumid = $curriculumid;
    $stable->yearid = $yearid;
    echo $renderer->viewcurriculumusers($stable);
    echo $OUTPUT->footer();
} else {
    $exportplugin = $CFG->dirroot . '/local/program/export_xls.php';
    if (file_exists($exportplugin)) {
        require_once($exportplugin);
        if (!empty($curriculumid)) {
            $stable = new stdClass();
            $stable->thead = true;
            $stable->start = 0;
            $stable->length = -1;
            $stable->search = '';
            $stable->curriculumid = $curriculumid;
            $stable->yearid = $yearid;
            export_report($curriculumid, $stable, $type);
        }
    }
    die;
}

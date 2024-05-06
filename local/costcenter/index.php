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
 * @subpackage costcenter
 * @copyright  2017 Eabyas Info Solutions <www.eabyas.in> 
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once(dirname(__FILE__) . '/../../config.php');

global $CFG, $USER, $PAGE, $OUTPUT;
//require_once($CFG->dirroot . '/lib/formslib.php');
require_once($CFG->dirroot . '/local/costcenter/lib.php');

$PAGE->requires->css('/local/costcenter/css/jquery.dataTables.min.css');
$PAGE->requires->js_call_amd('local_costcenter/costcenterdatatables', 'costcenterDatatable', array());
$PAGE->requires->js_call_amd('local_departments/newdepartment', 'load', array());
$PAGE->requires->js_call_amd('theme_epsilon/quickactions', 'quickactionsCall');
require_login();

$systemcontext = context_system::instance();
if(!has_capability('local/costcenter:view', $systemcontext)) {
    print_error('nopermissiontoviewpage');
}


if (!((is_siteadmin()) || has_capability('local/costcenter:manage_multiorganizations', $systemcontext))) {
    redirect($CFG->wwwroot . '/local/costcenter/costcenterview.php?id='.$USER->open_costcenterid);
}

$PAGE->set_pagelayout('admin');
$PAGE->set_context($systemcontext);
$PAGE->set_url('/local/costcenter/index.php');
$PAGE->set_heading(get_string('orgStructure', 'local_costcenter'));
$PAGE->set_title(get_string('orgStructure', 'local_costcenter'));
$PAGE->navbar->add(get_string('orgStructure', 'local_costcenter'));


$output = $PAGE->get_renderer('local_costcenter');

echo $OUTPUT->header();


$PAGE->requires->js_call_amd('local_costcenter/newcostcenter', 'load', array());

$PAGE->requires->js_call_amd('local_costcenter/newsubdept', 'load', array());

echo $output->get_dept_view_btns();
echo '<div class="col-12 page-desc"><b class="page-hrdesc">Description:</b><br>
This page will show the university/universities and its affiliated colleges/university departments. 
The numbers you notice on the tiles give Total, Active and Inactive count for Users, Courses and Programs under a university/college.
</div>';
echo $output->departments_view();

echo $OUTPUT->footer();

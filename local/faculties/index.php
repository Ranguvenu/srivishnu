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
 * @subpackage school
 * @copyright  2017 Eabyas Info Solutions <www.eabyas.in> 
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once(dirname(__FILE__) . '/../../config.php');
// require_once($CFG->dirroot . '/local/faculties/filters_form.php');

global $CFG, $USER, $PAGE, $OUTPUT;
require_once($CFG->dirroot . '/lib/formslib.php');
require_once($CFG->libdir.'/adminlib.php');
$page         = optional_param('page', 0, PARAM_INT);
$perpage      = optional_param('perpage', 10, PARAM_INT);
$boardid       = optional_param('id', 0, PARAM_INT);

$PAGE->requires->css('/local/faculties/css/jquery.dataTables.min.css');
$PAGE->requires->js_call_amd('local_faculties/newfaculty', 'load', array());
$PAGE->requires->js_call_amd('local_departments/newdepartment', 'load', array());

$PAGE->requires->js_call_amd('local_faculties/facultydatatables', 'facultyDatatable', array());
require_login();

$systemcontext = context_system::instance();

$PAGE->set_pagelayout('admin');
$PAGE->set_context($systemcontext);
$PAGE->set_url('/local/faculties/index.php');
$PAGE->set_heading(get_string('createfaculty', 'local_faculties'));
$PAGE->set_title(get_string('createfaculty', 'local_faculties'));
$PAGE->navbar->add(get_string('createfaculty', 'local_faculties'));

$output = $PAGE->get_renderer('local_faculties');

echo $OUTPUT->header();
if(!(is_siteadmin() || has_capability('local/costcenter:manage_multiorganizations', $systemcontext) || has_capability('local/costcenter:manage_ownorganization', $systemcontext))){
	throw new moodle_exception(get_string('errornopermission', 'local_boards'));
}
echo "<ul class='course_extended_menu_list'>
        <li>
            <div class='coursebackup course_extended_menu_itemcontainer'>
                <a class='course_extended_menu_itemlink' data-action='createfacultymodal' data-value='0' title = 'Create Faculty' onclick ='(function(e){ require(\"local_faculties/newfaculty\").init({selector:\"createfacultymodal\", contextid:$systemcontext->id, facultyid:0}) })(event)' ><span class='createicon'><i class='fa fa-cubes icon' aria-hidden='true'></i><i class='createiconchild fa fa-plus' aria-hidden='true'></i></span></a>
            </div>
        </li>
    </ul>";
echo '<div class="col-12 page-desc"><b class="page-hrdesc">Description:</b><br>
The page lists all faculties under a university/universities. Faculties like ‘Faculty of Arts’ etc. can be created and managed on this page. Filters are provided below to filter Faculties by University. Faculties offer various regular/distance programs to be enrolled by students. Faculties are created under boards.
</div>';
/*if(!(is_siteadmin() || has_capability('local/costcenter:manage_multiorganizations', $systemcontext) )){
	throw new moodle_exception(get_string('errornopermission', 'local_boards'));
}*/
if(is_siteadmin() || has_capability('local/costcenter:manage_multiorganizations', $systemcontext)){
	$mform = new local_boards\form\filters_form(null, array('filterlist'=>array('university'/*, 'boards'*/)));
	if($mform->is_cancelled()) {
		redirect($CFG->wwwroot . '/local/faculties/index.php');
	}else{
		$filterdata =  $mform->get_data();
		if($filterdata){
			$collapse = false;
		}else{
			$collapse = true;
		}
	}
	$heading = '<span class="filter-lebel">'.get_string('filters').'</span>';
	print_collapsible_region_start(' ', 'filters_form', ' '.' '.$heading, false, $collapse);
	$mform->display();
	print_collapsible_region_end();
	$heading = '<span class="filter-lebel">'.get_string('filters').'</span>';
}/*elseif(has_capability('local/costcenter:manage_ownorganization', $systemcontext)){
	$mform = new local_boards\form\filters_form(null, array('filterlist'=>array('boards')));
}*/

	// $mform = new local_boards\form\filters_form(null, array('filterlist'=>array('university')));
// }

echo $output->faculties_view($filterdata, $page, $perpage);

echo $OUTPUT->footer();

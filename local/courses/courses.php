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
 * local courses rendrer
 *
 * @package    local_courses
 * @copyright  2017 eAbyas <eAbyas.in>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once($CFG->dirroot . '/local/courses/filters_form.php');

$id        = optional_param('id', 0, PARAM_INT);
$deleteid = optional_param('delete', 0, PARAM_INT);
$confirm = optional_param('confirm', 0, PARAM_INT);
$page         = optional_param('page', 0, PARAM_INT);
$perpage      = optional_param('perpage', 10, PARAM_INT); 
$jsonparam    = optional_param('jsonparam', '', PARAM_RAW);       // how many per page
require_login();

$systemcontext = context_system::instance();
if(!has_capability('local/costcenter_course:view', $systemcontext) && !has_capability('local/costcenter_course:manage', $systemcontext) ){
	print_error("You don't have permissions to view this page.");
}
$PAGE->set_pagelayout('admin');

$PAGE->set_context($systemcontext);
$PAGE->set_url('/local/courses/courses.php');
$PAGE->set_heading(get_string('manage_courses','local_courses'));
$PAGE->set_title(get_string('manage_courses','local_courses'));
// $PAGE->requires->jquery();
// $PAGE->requires->css('/local/users/css/jquery.dataTables.css');
// $PAGE->requires->js('/local/courses/js/custom.js', true);
// $PAGE->requires->js('/local/courses/js/jquery.js',true);

$PAGE->requires->js_call_amd('local_courses/courseAjaxform', 'load');
$PAGE->requires->js_call_amd('theme_epsilon/quickactions', 'quickactionsCall');
$PAGE->requires->js_call_amd('local_costcenter/fragment', 'init', array());
// $PAGE->requires->js_call_amd('local_courses/courses', 'load', array());
$PAGE->navbar->ignore_active();
$PAGE->navbar->add(get_string('manage_courses','local_courses'));
if($deleteid && $confirm && confirm_sesskey()){
	$course=$DB->get_record('course',array('id'=>$deleteid));
    delete_course($course, false);
	if($course){
		$custom_delete = new local_courses\action\delete();
		$delete = $custom_delete->delete_coursedetails($deleteid);
	 }
	/***  After deletion of a course, the course details are inserted into local_logs table by Shivani M  ****/
	$course_detail = new stdClass();
	$sql = $DB->get_field('user','firstname', array('id' =>$USER->id));
	$course_detail->userid = $sql;
	$course_detail->courseid = $deleteid;
	$description = get_string('descptn','local_courses',$course_detail);
	$logs = new local_courses\action\insert();
	$insert_logs = $logs->local_custom_logs('delete', 'course', $description, $deleteid);
	redirect($CFG->wwwroot . '/local/courses/courses.php');	
}
$renderer = $PAGE->get_renderer('local_courses');

$extended_menu_links = '';
	
$extended_menu_links = '<div class="course_contextmenu_extended">
			<ul class="course_extended_menu_list">';
// if ((has_capability('local/request:approverecord', context_system::instance()) || is_siteadmin())) {
//     $extended_menu_links .= '<li><div class="courseedit course_extended_menu_itemcontainer">
// 							<a id="extended_menu_createcourses" class="pull-right course_extended_menu_itemlink" title = "Request" href = '.$CFG->wwwroot.'/local/request/index.php?component=elearning>
// 								<i class="icon fa fa-share-square" aria-hidden="true"></i>
// 							</a>
// 						</div></li>';
// }				
// if(((has_capability('local/costcenter:create', $systemcontext)&&has_capability('local/costcenter_course:bulkupload', $systemcontext)&&has_capability('local/costcenter_course:manage', $systemcontext)&&has_capability('moodle/course:create', $systemcontext)&&has_capability('moodle/course:update', $systemcontext)))|| is_siteadmin()){

// 	$extended_menu_links .= '<li><div class="courseedit course_extended_menu_itemcontainer">
// 								<a id="extended_menu_createcourses" class="pull-right course_extended_menu_itemlink" title = "'.get_string('uploadcourses','local_courses').'" href = '.$CFG->wwwroot.'/local/courses/upload/index.php>
// 									<i class="icon fa fa-upload" aria-hidden="true"></i>
// 								</a>
// 							</div></li>';
	// }
	if(is_siteadmin() ||(
	 has_capability('moodle/course:create', $systemcontext)&& has_capability('moodle/course:update', $systemcontext)&&has_capability('local/costcenter_course:manage', $systemcontext))){
		$extended_menu_links .= '<li><div class="courseedit course_extended_menu_itemcontainer">
									<a id="extended_menu_createcourses" class="pull-right course_extended_menu_itemlink" title = "'.get_string('create_newcourse','local_courses').'" data-action="createcoursemodal" onclick="(function(e){ require(\'local_courses/courseAjaxform\').init({contextid:'.$systemcontext->id.', component:\'local_courses\', callback:\'custom_course_form\', form_status:0, plugintype: \'local\', pluginname: \'courses\'}) })(event)">
										<span class="createicon"><i class="icon fa fa-book"></i><i class="fa fa-plus createiconchild" aria-hidden="true"></i></span>
									</a>
								</div></li>';
	}
	
	$extended_menu_links .= '
		</ul>
	</div>';


echo $OUTPUT->header();

echo $extended_menu_links;

$filterparams = $renderer->get_catalog_courses(true);

echo '<div class="col-12 page-desc"><b class="page-hrdesc">Description:</b><br>
This page lists all the courses offered by a university/universities. Courses can be filtered by using university or course category filters. While preparing the master versions of Programs offered by the university, courses from the university "Course repository" are selected.
</div>';
// if(is_siteadmin()){
// 	$mform = new filters_form(null, array('filterlist'=>array('organizations','department','courses')));
// }else if(has_capability('local/costcenter:manage_ownorganization',$systemcontext)){
// 	$mform = new filters_form(null, array('filterlist'=>array('courses', 'department')));
// }else if(has_capability('local/costcenter:manage_owndepartments', $systemcontext)){
// 	$mform = new filters_form(null, array('filterlist'=>array('courses', 'department')));
// }else 
// 	$mform = new filters_form(null, array('filterlist'=>array('courses', 'department')));
if(is_siteadmin()){
    $thisfilters = array('courses', 'organizations','departments');
}else if(has_capability('local/costcenter:manage_ownorganization',$systemcontext)){
    $thisfilters = array('courses','departments');
}else if(has_capability('local/costcenter:manage_owndepartments', $systemcontext)){
    $thisfilters = array('courses');
}
else {
    $thisfilters = array('courses');
}

$mform = new filters_form(null, array('filterlist'=> $thisfilters, 'filterparams' => $filterparams));
	 
if ($mform->is_cancelled()) {
	redirect($CFG->wwwroot . '/local/courses/courses.php');
} else{
	$filterdata =  $mform->get_data();
	if($filterdata){
		$collapse = false;
	} else{
		$collapse = true;
	}
// 	print_r($filterdata);
// exit;
} 

if(empty($filterdata) && !empty($jsonparam)){
    $filterdata = json_decode($jsonparam);
    foreach($thisfilters AS $filter){
        if(empty($filterdata->$filter)){
            unset($filterdata->$filter);
        }
    }
    $mform->set_data($filterdata);
}
if($filterdata){
    $collapse = false;
    $show = 'show';
} else{
    $collapse = true;
    $show = '';
}

// echo '<a class="btn-link btn-sm" href="javascript:void(0);" data-toggle="collapse" data-target="#local_courses-filter_collapse" aria-expanded="false" aria-controls="local_courses-filter_collapse">
//         <i class="m-0 fa fa-sliders fa-2x" aria-hidden="true"></i>
//       </a>';
// echo  '<div class="collapse '.$show.'" id="local_courses-filter_collapse">
//             <div id="filters_form" class="card card-body p-2">';
//                 $mform->display();
// echo        '</div>
//         </div>';

//RM Issue ODL-749 changing the filter icon
print_collapsible_region_start('courses_filters', 'filters_form', ' ' . ' ' . get_string('filters'), false, $collapse);
$mform->display();
print_collapsible_region_end();
//end RM Issue ODL-749
$filterparams['submitid'] = 'form#filteringform';
echo $OUTPUT->render_from_template('local_costcenter/global_filter', $filterparams);
echo $renderer->get_catalog_courses();

echo $OUTPUT->footer();

// $heading = '<span class="filter-lebel">'.get_string('filters').'</span>';
// print_collapsible_region_start(' ', 'filters_form', ' '.' '.$heading, false, $collapse);
// $mform->display();
// print_collapsible_region_end();
// echo $output->get_catalog_courses($filterdata, $page, $perpage);
// echo $OUTPUT->footer();

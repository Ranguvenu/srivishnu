<?php
/**
 * This file is part of eAbyas
 *
 * Copyright eAbyas Info Solutons Pvt Ltd, India
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author eabyas  <info@eabyas.in>
 * @package BizLMS
 * @subpackage local_location
 */



require_once(dirname(__FILE__) . '/../../config.php');
global $CFG,$PAGE;
require_once($CFG->dirroot . '/local/location/lib.php');
$systemcontext = context_system::instance();
$PAGE->set_context($systemcontext);
$PAGE->set_url($CFG->wwwroot .'/local/location/index.php');
$PAGE->set_title(get_string('institute', 'local_location'));
$PAGE->set_heading(get_string('institute', 'local_location'));
$PAGE->navbar->ignore_active();
$PAGE->navbar->add( get_string('institute', 'local_location'), new moodle_url('/local/location/index.php'));
require_login();

$renderer = $PAGE->get_renderer('local_location');
$institute = new local_location\event\location();
$PAGE->requires->jquery();
$PAGE->requires->js('/local/location/js/delconfirm.js');
$PAGE->requires->js('/local/location/js/jquery.min.js',TRUE);
$PAGE->requires->js('/local/location/js/datatables.min.js', TRUE);
$PAGE->requires->css('/local/location/css/datatables.min.css');
$id = optional_param('id',0,PARAM_INT);
$delete = optional_param('delete', 0, PARAM_INT);
echo $OUTPUT->header();

if ((has_capability('local/location:manageinstitute', context_system::instance()) || has_capability('local/location:viewinstitute', context_system::instance()))) {
  

//echo "<button data-action='createinstitutemodal'  onclick ='(function(e){ require(\"local_location/newinstitute\").init({selector:\"createinstitutemodal\", contextid:$systemcontext->id, instituteid:$id}) })(event)' >Create Institute</button>";

if ((has_capability('local/location:manageinstitute', context_system::instance()))|| has_capability('local/location:viewinstitute', context_system::instance())) {
  $PAGE->requires->js_call_amd('local_location/newinstitute', 'load', array());
echo "<ul class='course_extended_menu_list'>";
/*echo" <li>
        <div class = 'coursebackup course_extended_menu_itemcontainer' >
            <a href='".$CFG->wwwroot."/local/program/index.php' class='course_extended_menu_itemlink create_ilt' title='Back to Programs'><i class='icon far fa-arrow-alt-circle-left'></i></a>
        </div>
    </li>";*/
/*if ((has_capability('local/location:manageroom', context_system::instance()) || has_capability('local/location:viewroom', context_system::instance()))) { 
   echo" <li>
        <div class = 'coursebackup course_extended_menu_itemcontainer' >
            <a href='".$CFG->wwwroot."/local/location/room.php' class='course_extended_menu_itemlink create_ilt' title='Room'><i class='icon fa fa-simplybuilt'></i></a>
        </div>
    </li>";
  }*/
  if(has_capability('local/location:manageinstitute', context_system::instance())){
		echo"<li>	
			<div class = 'coursebackup course_extended_menu_itemcontainer'>
				<a data-action='createinstitutemodal' data-value='0' class='course_extended_menu_itemlink' onclick ='(function(e){ require(\"local_location/newinstitute\").init({selector:\"createinstitutemodal\", contextid:$systemcontext->id, instituteid:$id}) })(event)' title='".get_string('createinstitute', 'local_location')."'><span class='createicon'><i class='fa fa-map-marker icon' aria-hidden='true'></i><i class='createiconchild fa fa-plus' aria-hidden='true'></i></span>
				</a>
			</div>
		</li>";
  }
	echo"</ul>";
}
echo '<div class="col-12 page-desc"><b class="page-hrdesc">Description:</b><br>This page displays list of Locations under university/universities.';
if($delete){
  $institute->delete_institutes($id);
  redirect(new moodle_url('/local/location/index.php'));
}
$mform = new local_location\form\instituteform(null,array('id'=>$id));
$form_data = $institute->set_data_institute($id);
$mform->set_data($form_data);
if ($mform->is_cancelled()) {

}else if ($data = $mform->get_data()) {
  if($id > 0){
    $record->id = $data->id;
    $res = $institute->institute_update_instance($data);
  }else{
    $res = $institute->institute_insert_instance($data);
  }
  $returnurl = new moodle_url('/local/location/index.php');
  redirect($returnurl);
}
require_once($CFG->dirroot . '/local/courses/filters_form.php');

if(is_siteadmin()){
  
    $mform = new filters_form(null, array('filterlist'=>array('organizations','departments'), 'courseid' => 1, 'enrolid' => 0,'plugins'=>array('users','costcenter'),'action' => 'user_enrolment'));

    if ($mform->is_cancelled()) {
      redirect($CFG->wwwroot . '/local/location/index.php');
    } else{
      $filterdata =  $mform->get_data();
      if($filterdata){
        $collapse = false;
      } else{
        $collapse = true;
      }
    }
    $heading = '<span class="filter-lebel">'.get_string('filters').'</span>';
    print_collapsible_region_start(' ', 'filters_form', ' '.' '.$heading, false, $collapse);
    $mform->display();
    print_collapsible_region_end();
    $heading = '<span class="filter-lebel">'.get_string('filters').'</span>';
  }
echo $renderer->display_institutes($filterdata);
}else{
 echo get_string('no_permissions','local_location');
}
echo $OUTPUT->footer();




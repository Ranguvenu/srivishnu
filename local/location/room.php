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
$PAGE->set_url($CFG->wwwroot .'/local/location/room.php');
$PAGE->set_title(get_string('room', 'local_location'));
$PAGE->set_heading(get_string('room', 'local_location'));
$PAGE->navbar->ignore_active();
$PAGE->navbar->add(get_string('room', 'local_location'), new moodle_url('/local/location/room.php'));
$PAGE->requires->jquery();
$PAGE->requires->js('/local/location/js/delconfirm.js',TRUE);
$PAGE->requires->js('/local/location/js/jquery.min.js',TRUE);
$PAGE->requires->js('/local/location/js/datatables.min.js', TRUE);
$PAGE->requires->css('/local/location/css/datatables.min.css');
$room = new local_location\event\location();
$renderer = $PAGE->get_renderer('local_location');
$id = optional_param('id',0,PARAM_INT);
$delete = optional_param('delete', 0, PARAM_INT);
echo $OUTPUT->header();
if ((has_capability('local/location:manageinstitute', context_system::instance()) || has_capability('local/location:viewinstitute', context_system::instance()))) {
if ((has_capability('local/location:manageroom', context_system::instance()) || has_capability('local/location:viewroom', context_system::instance()))) {
if ((has_capability('local/location:manageroom', context_system::instance()))) {  
$PAGE->requires->js_call_amd('local_location/newroom', 'load', array());
echo "<ul class='course_extended_menu_list'>
          <li> 
              <div class = 'coursebackup course_extended_menu_itemcontainer'>
                <a data-action='createroommodal' data-value='0' class='course_extended_menu_itemlink' onclick ='(function(e){ require(\"local_location/newroom\").init({selector:\"createroommodal\", contextid:$systemcontext->id, roomid:$id}) })(event)' title='".get_string('createroom', 'local_location')."'><span class='createicon'><i class='fa fa-simplybuilt icon' aria-hidden='true'></i><i class='createiconchild fa fa-plus' aria-hidden='true'></i></span>
                </a>
              </div>
          </li>
      </ul>";
}

echo '<div class="col-12 page-desc"><b class="page-hrdesc">Description:</b><br>This page displays list of Rooms under university/universities.';
if($delete){
  $room->delete_rooms($id);
  redirect(new moodle_url('/local/location/room.php'));
}

$mform = new local_location\form\roomform(null,array('id'=>$id));
$form_data = $room->set_data_institute($id);
$mform->set_data($form_data);
if ($mform->is_cancelled()) {

}else if ($roomdata = $mform->get_data()) {
  if($id > 0){
    $record->id = $data->id;
    $res = $room->room_update_instance($roomdata);
  }else{
    $res = $room->room_insert_instance($roomdata);
  }
  $returnurl = new moodle_url('../../local/location/room.php');
  redirect($returnurl);
}
 /*require_once($CFG->dirroot . '/local/courses/filters_form.php');
 $mform = new filters_form(null, array('filterlist'=>array('organizations',), 'courseid' => 1, 'enrolid' => 0,'plugins'=>array('location')));
  if($mform->is_cancelled()){
        $filterdata = null;
        redirect(new moodle_url('/local/location/room.php'));
      }else{
        $filterdata =  $mform->get_data();

      }
      if($filterdata){
          $collapse = false;
      } else{
          $collapse = true;
      }
       $heading = '<span class="filter-lebel">'.get_string('filters').'</span>';
      print_collapsible_region_start(' ', 'filters_form', ' '.' '.$heading, false, $collapse);
      $mform->display();
      print_collapsible_region_end();*/
            require_once($CFG->dirroot . '/local/courses/filters_form.php');

    if(is_siteadmin()){
        //Revathi Issue ODL-798 starts
        $mform = new filters_form(null, array('filterlist'=>array('organizations','departments'), 'courseid' => 1, 'enrolid' => 0,'plugins'=>array('users','costcenter'),'action' => 'user_enrolment'));
      //Revathi Issue ODL-798 ends
        if ($mform->is_cancelled()) {
            redirect($CFG->wwwroot . '/local/location/room.php');
        }else{
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
echo $renderer->display_rooms($filterdata, $page, $perpage);
}else{
   echo get_string('no_permissions','local_location');
}
}else{
   echo get_string('no_permissions','local_location');
}
echo $OUTPUT->footer();




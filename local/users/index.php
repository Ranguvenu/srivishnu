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
 * List the tool provided 
 *
 * @package   local
 * @subpackage  users
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
use core_component;

require_once($CFG->dirroot.'/lib/enrollib.php');
global $DB, $OUTPUT,$USER,$CFG,$PAGE;
$page         = optional_param('page', 0, PARAM_INT);
$perpage      = optional_param('perpage', 10, PARAM_INT);        // how many per page
$userid = $DB->get_record('user',array('id'=>$USER->id));
require_login();
$pageurl = new moodle_url('/local/users/index.php');
$corecomponent = new \core_component();
$systemcontext = context_system::instance();
$PAGE->set_context($systemcontext);
if(is_siteadmin() || has_capability('local/users:manage', $systemcontext)){
  $PAGE->set_url($pageurl);
  $PAGE->set_pagelayout('standard');
  $PAGE->set_title(get_string('users') . ': ' . get_string('browseusers', 'local_users'));
  //Header and the navigation bar
  $PAGE->set_heading(get_string('browseusers', 'local_users'));
  $PAGE->navbar->add(get_string('browseusers', 'local_users'));
  $PAGE->requires->jquery();
  $PAGE->requires->jquery_plugin('ui');
  $PAGE->requires->js('/local/users/js/custom.js');
  $PAGE->requires->js_call_amd('local_users/newuser', 'load', array());
  $PAGE->requires->js_call_amd('local_users/profileinfo', 'load', array());
  $PAGE->requires->js_call_amd('local_departments/newdepartment', 'load', array());
  $PAGE->requires->js_call_amd('theme_epsilon/quickactions', 'quickactionsCall');

  $PAGE->requires->css('/local/users/css/jquery.dataTables.css');

  $local_costcenter = $DB->get_record('local_costcenter',array('id'=>$userid->open_costcenterid));

  $myuser = new local_users\events\users();
  echo $OUTPUT->header();
echo '<div class="col-12 page-desc"><b class="page-hrdesc">Description:</b><br>
The page lists all the users with different roles created in the application like students, teachers, university admins, college admins etc. under a university/universities. Filters can be applied to filters users list by role. Separate user creation pages are provided on the right side of this page to create University admin/head, Employee (College admin/head, Faculty), Students and also manage their roles, personal, professional and demographic details
</div>';
    $userrenderer = $PAGE->get_renderer('local_users');
    $id=0;
    if(has_capability('local/users:create',$systemcontext) || is_siteadmin()){
         $userrenderer->user_page_top_action_buttons();
    }
    $userspluginexist = $corecomponent::get_plugin_directory('local','users');
    if(!empty($userspluginexist)){
      require_once($CFG->dirroot . '/local/courses/filters_form.php');
        if(is_siteadmin() && has_capability('local/costcenter:manage_multiorganizations', $systemcontext)){
            $mform = new filters_form(null, array('filterlist'=>array('organizations', 'departments','email','employeeid', 'role'), 'courseid' => 1, 'enrolid' => 0,'plugins'=>array('users','costcenter'),'action' => 'user_enrolment'));
        }else if(has_capability('local/costcenter:manage_ownorganization',$systemcontext)){
            $mform = new filters_form(null, array('filterlist'=>array('departments','email','employeeid', 'role'), 'courseid' => 1, 'enrolid' => 0,'plugins'=>array('users','costcenter'),'action' => 'user_enrolment'));
        }else if(has_capability('local/costcenter:manage_owndepartments', $systemcontext)){
            $mform = new filters_form(null, array('filterlist'=>array('email','employeeid'), 'courseid' => 1, 'enrolid' => 0,'plugins'=>array('users','costcenter'),'action' => 'user_enrolment'));
        }
      
      if($mform->is_cancelled()){
        $filterdata = null;
        redirect(new moodle_url('/local/users/index.php'));
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
      print_collapsible_region_end();

      //userscount function added by sarath
      // echo $userrenderer->display_userinformatiom_count($filterdata);
}
  echo $userrenderer->display_userinformatiom($filterdata, $page, $perpage);
}else{
    echo print_error('no permission');
}
echo $OUTPUT->footer();
?>

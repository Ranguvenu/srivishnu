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
 * curriculum View
 *
 * @package    local_curriculum
 * @copyright  2018 Arun Kumar M <arun@eabyas.in>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
global $PAGE, $DB,$CFG;
require_once(dirname(__FILE__) . '/../../config.php');
use local_program\program;
$curriculumid = required_param('ccid', PARAM_INT);
$programid = optional_param('prgid', 0, PARAM_INT);
$yearid = optional_param('year', 0, PARAM_INT);
$action = optional_param('action', '', PARAM_ALPHA);
$status = optional_param('status', 0, PARAM_INT);
$type = optional_param('type',1, PARAM_INT);
$systemcontext = context_system::instance();
require_login();
$program = $DB->get_record('local_program', array('id' => $programid));
$PAGE->set_url('/local/program/view.php', array('ccid' => $curriculumid,'type'=>$type,'prgid' => $programid));
$PAGE->set_context($systemcontext);

$curriculum = $DB->get_record('local_curriculum', array('id' => $curriculumid));

//issue 720 to get enroll icon - starts

$year = $DB->get_field('local_program_cc_years','id',array('curriculumid'=>$curriculumid));

//issue 720 to get enroll icon - ends

$PAGE->set_title($program->fullname);
// if (empty($curriculum)) {
//      redirect("$CFG->wwwroot/local/program/index.php?type=$type");
// }
// if(has_capability('local/program:managecurriculum',
//             context_system::instance()) || has_capability('local/program:createcurriculum',
//             context_system::instance()) || is_siteadmin()){
//     $navbarurl = new moodle_url('/local/program/index.php',array('type'=>$type));
// 	// new moodle_url('index.php',array('type'=>$type))
// }else{
//     $navbarurl = '';
// }
if(is_siteadmin() || !has_capability('block/studentdashboard:view',
            context_system::instance()) ){
	$navbarurl = new moodle_url('/local/program/index.php',array('type'=>$type));
	$PAGE->navbar->add(get_string("pluginname", 'local_program'), $navbarurl);
}
$PAGE->navbar->add($program->fullname);
$PAGE->set_heading($program->fullname);

$PAGE->requires->jquery_plugin('ui-css');
$PAGE->requires->css('/local/program/css/jquery.dataTables.min.css', true);
$PAGE->requires->js_call_amd('local_program/ajaxforms', 'load');
$PAGE->requires->js_call_amd('theme_epsilon/quickactions', 'quickactionsCall');
$PAGE->requires->js_call_amd('local_curriculum/ajaxforms', 'load');
$renderer = $PAGE->get_renderer('local_program');
$courses = $DB->get_record('local_cc_semester_courses',array('programid'=>$programid));
$classroom = $DB->get_record('local_cc_semester_classrooms',array('programid'=>$programid));
echo $OUTPUT->header();
//if($program->parentid > 0){
	$output = "<ul class='course_extended_menu_list'>";
 

  if(!empty($year)){ 
     if(has_capability('local/program:manageprogram', context_system::instance()) && (!empty($courses) || !empty($classroom))){
        $enrol_url = new moodle_url('/local/program/enrolusers.php',array('id'=>$programid,'ccid'=>$curriculumid));
                $output .= '<li>
                     <div class="courseedit course_extended_menu_itemcontainer">
                        <a id="extended_menu_createcourses" class="pull-right course_extended_menu_itemlink" title = "'.get_string('enrolledusers','local_program').'" href="'.$enrol_url.'">   
                          <span class="createicon"><i class="icon fa fa-user"></i></span>
                        </a>
                      </div>
                    </li>';
      }
      if(has_capability('local/program:manageprogram', context_system::instance()) && !empty($courses) || !empty($classroom)){
    		$enrol_url = new moodle_url('/local/program/bulkenrol.php',array('id'=>$programid,'ccid'=>$curriculumid));
                $output .= '<li>
                     <div class="courseedit course_extended_menu_itemcontainer">
                        <a id="extended_menu_createcourses" class="pull-right course_extended_menu_itemlink" title = "'.get_string('bulkenrolallyears','local_program').'" href="'.$enrol_url.'">   
                          <span class="createicon"><i class="icon fa fa-users"></i></span>
                        </a>
                      </div>
                        </li>';
      }
  }
  if(has_capability('local/curriculum:createsemesteryear', $systemcontext)){
      // $output .= '<li
      //       <div class="pull-right">
      //           <a title="Add Semester" class="course_extended_menu_itemlink" href="javascript:void(0)" onclick="(function(e){ require("local_curriculum/ajaxforms").init({contextid:1, component:"local_curriculum", callback:"curriculum_manageyear_form", form_status:0, plugintype: "local", pluginname: "curriculum_addyear", id: 0, curriculumid:'.$curriculumid.'}) })(event)">
      //               <i class="icon fa fa-tags fa-fw" aria-hidden="true" title="Add Year"></i>
      //           </a>
      //       </div>
      //           </li>';
      $output .= '<li><div class="courseedit course_extended_menu_itemcontainer">
                  <a id="extended_menu_createcourses" class="pull-right course_extended_menu_itemlink" title = "'.get_string('addsemester','local_curriculum').'" data-action="createcoursemodal" onclick="(function(e){ require(\'local_curriculum/ajaxforms\').init({contextid:'.$systemcontext->id.', curriculumid:'.$curriculumid.',programid:'.$programid.' ,component:\'local_curriculum\', callback:\'curriculum_manageyear_form\', form_status:0, plugintype: \'local\', pluginname: \'curriculum_addyear\',id:0,curriculumid:'.$curriculumid.'}) })(event)">
                    <span class="createicon"><i class="icon fa fa-tags fa-fw" aria-hidden="true" title="Add Year"></i></span>
                  </a>
                </div></li>';
  }
   			
      $output .= "</ul>";
    	echo $output; 
//}
echo $renderer->viewcurriculum($curriculumid, $programid);
echo $OUTPUT->footer();

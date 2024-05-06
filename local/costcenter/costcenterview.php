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
 * @copyright  2017 Niranjan <niranjan@eabyas.in>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
use core_component;
require_once('../../config.php');
require_once($CFG->dirroot.'/local/costcenter/lib.php');
require_once($CFG->dirroot.'/local/costcenter/renderer.php');

$id = required_param('id', PARAM_INT);
$userid = optional_param('userid', 0, PARAM_INT);
$deptid = optional_param('deptid', 0, PARAM_INT);
$costcenterid = optional_param('id',0,PARAM_INT);
global $DB,$OUTPUT,$CFG, $PAGE;
/* ---First level of checking--- */
require_login();
$systemcontext = context_system::instance();

if(!has_capability('local/costcenter:view', $systemcontext)) {
    print_error('nopermissiontoviewpage');
}
/* ---Get the records from the database--- */
if (!$depart = $DB->get_record('local_costcenter', array('id' => $id))) {
    print_error('invalidschoolid');
}
/*OL-2166- Added the below condition for checking  */
if(!is_siteadmin() || !has_capability('local/costcenter:manage_multiorganizations', $systemcontext)){
    if(!$DB->record_exists('user',array('open_costcenterid'=>$id,'id'=>$USER->id))){
            print_error('nopermissiontoviewpage');

    }
}

$PAGE->requires->jquery();
$PAGE->requires->jquery('ui');
$PAGE->requires->jquery('ui-css');

$PAGE->requires->js_call_amd('local_costcenter/costcenterdatatables', 'costcenterDatatable', array());
$PAGE->requires->js_call_amd('local_costcenter/newcostcenter', 'load', array());
$PAGE->requires->js_call_amd('local_departments/newdepartment', 'load', array());
$PAGE->requires->js_call_amd('local_colleges/newcollege', 'load', array());
$PAGE->requires->js_call_amd('local_costcenter/newsubdept', 'load', array());
$PAGE->requires->js_call_amd('theme_epsilon/quickactions', 'quickactionsCall');
$PAGE->requires->js('/local/colleges/js/custom.js',true);
$PAGE->requires->js('/local/departments/js/custom.js',true);
$PAGE->set_pagelayout('admin');
/* ---check the context level of the user and check whether the user is login to the system or not--- */
$PAGE->set_context($systemcontext);
$PAGE->set_url('/local/costcenter/costcenterview.php');
/* ---Header and the navigation bar--- */
$PAGE->set_heading(get_string('department_structure', 'local_costcenter'));
$PAGE->set_title(get_string('department_structure', 'local_costcenter'));
$PAGE->navbar->ignore_active();

if(is_siteadmin() || has_capability('local/costcenter:manage_multiorganizations', $systemcontext)){
    $PAGE->navbar->add(get_string('orgStructure', 'local_costcenter'), new moodle_url('/local/costcenter/index.php'));// added to show the correct path.
}


$PAGE->navbar->add(get_string('viewcostcenter', 'local_costcenter'));// removed link as current page link is not required.

echo $OUTPUT->header();
$core_component = new core_component();
$pluginnavs = local_costcenter_plugins_count($id);

if (has_capability('local/costcenter:manage', $systemcontext)) {
    $edit = true;
    if ($depart->visible) {
        $hide = true;
        $show = false;
    }else{
        $show = true;
        $hide = false;
    }
    $action_message = get_string('confirmation_to_disable_'.$depart->visible, 'local_costcenter', $depart->fullname);
    $dept_clgcount = $DB->count_records('local_costcenter', array('parentid' => $id));
    if($dept_clgcount == 0 && $pluginnavs['totalusers'] == 0){
        $delete = true;
        $del_confirmationmsg = get_string('confirmationmsgfordel', 'local_costcenter',$depart->fullname);
    }else{
        $delete = false;
        $del_confirmationmsg = '';
    }
}

   /*This query executed when the admin or capablity is allowed*/
$dept_count_link = '';
$subdepartment = '';
$departments_sql="SELECT id,id AS id_val FROM {local_costcenter} WHERE parentid=:parent";
$departments = $DB->get_records_sql_menu($departments_sql, array('parent' => $id));
$department = count($departments);
$department = ($department > 0 ? $department : 'N/A');
$dept_id=implode(',',$departments);


if($dept_id){
     $subdepartments_sql="SELECT id,id AS id_val FROM {local_costcenter} WHERE parentid IN($dept_id);";
     $subdepartments = $DB->get_records_sql_menu($subdepartments_sql);
     $subdepartment = count($subdepartments);
     $subdepartment = ($subdepartment > 0 ? $subdepartment : 'N/A');        
}   

$dept_count_link = $department;

$departments = $DB->get_records('local_costcenter', array('parentid' =>$id));
$totaldepts = count($departments);
/*data for organization details ends here*/
$departments_content = array();
if($totaldepts % 2 == 0){ 
    $deptclass = '';
}else{ 
    $deptclass = 'deptsodd';
} 

$deptkeys = array_values($departments);
echo '<ul class="course_extended_menu_list dept">
  <li>
       <div class="courseedit course_extended_menu_itemcontainer">';
        if (is_siteadmin() || has_capability('local/costcenter:manage', $systemcontext)){
     echo $createsubdeptpopup = "<a class='course_extended_menu_itemlink' data-action='createdeptmodal' data-value='0' title = '".get_string('createcollege','local_costcenter')."' onclick ='(function(e){ require(\"local_colleges/newcollege\").init({selector:\"createcategorymodal\",
                    contextid:1, id:0,underuniversity:$id}) })(event)' ><span class='createicon'><i class='icon fa fa-university'></i><i class='fa fa-plus createiconchild' aria-hidden='true'></i></span></a>"; 
          }
       echo  '</div>
    </li>

    <li>
       <div class="courseedit course_extended_menu_itemcontainer">';
        if (is_siteadmin() || has_capability('local/costcenter:manage', $systemcontext)){
     echo $createsubdeptpopup = "<a class='course_extended_menu_itemlink' data-action='createdeptmodal' data-value='0' title = '".get_string('createdep','local_costcenter')."' onclick ='(function(e){ require(\"local_departments/newdepartment\").init({selector:\"createcategorymodal\",
                    contextid:1, categoryid:0,underuniversity:$id}) })(event)' ><span class='createicon'><i class='icon fa fa-building-o' aria-hidden='true'></i><i class='fa fa-plus createiconchild' aria-hidden='true'></i></span></a>"; 
          }
       echo  '</div>
    </li></ul>';
 

foreach($deptkeys as $key => $dept){
    $even = false;
    $odd = false;
   /* if($key % 2 == 0){ 
        $even = true;
    } 
    else{ 
        $odd = true;
    } */
    //Changes by Yamini for displaying differentiation between departments and colleges..
    if($dept->univ_dept_status == 0){
        $even = true;
    }
    else{
        $odd = true;
    }
 
    $departments_array = array();
    $subdepartments = $DB->get_records('local_costcenter', array('parentid' =>$dept->id));
    
    $subdept = count($subdepartments);
    $subdept = ($subdept > 0 ? $subdept : 'N/A');        

    $deparray = local_costcenter_plugins_count($dept->parentid,$dept->id,$dept->category);
    
    $deptdelete = false;
    if (has_capability('local/costcenter:manage', $systemcontext)) {
        $deptedit = true;
      
        if ($dept->visible) {           
            $department = $DB->get_field('local_curriculum','id',array('department' => $dept->id));
            if($department){
              $depthide = false;
              $deptshow = false;
            }
            else{
            $depthide = true;
            $deptshow = false;
            }
        }else{
            $deptshow = true;
            $depthide = false;
        }
        $deptaction_message = get_string('confirmation_to_disable_'.$dept->visible, 'local_costcenter', $dept->fullname);
        $checkcostcenter = new \local_costcenter\local\checkcostcenter();
        $modulecount = $checkcostcenter->costcenter_modules_exist($dept->parentid,$dept->id);
        if(!$modulecount['userscount'] && !$modulecount['coursescount'] && !$modulecount['programscount']){
            $deptdelete = true;
        }
        $deptdel_confirmationmsg = get_string('confirmationmsgfordel', 'local_costcenter',$dept->fullname);
    }

    $department = $DB->get_record('local_departments',array('university' => $dept->parentid,'catid' => $dept->category,'idnumber' => $dept->shortname));
    
    $departments_array['subdept'] = $subdept;
    $departments_array['departmentparentid'] = $dept->parentid;
    $departments_array['departmentfullname'] = $dept->fullname;
    $departments_array['departmentnamecut'] = strlen($dept->fullname) > 28 ? substr($dept->fullname, 0, 28)."..." : $dept->fullname;
    $departments_array['edit_image_url'] = $OUTPUT->image_url('t/edit');
    $departments_array['subdepartments_content'] = $subdepartments_content;
    $departments_array['even'] = $even;
    $departments_array['odd'] = $odd;
    $departments_array['deptclass'] = $deptclass;
    $departments_array['deptedit'] = $deptedit;
    $departments_array['depthide'] = $depthide;
    $departments_array['deptshow'] = $deptshow;
    $departments_array['type'] = $dept->univ_dept_status;
    $departments_array['deptstatus'] = $dept->visible;
    $departments_array['deptdelete'] = $deptdelete;
    $departments_array['deptid'] = $dept->id;
    $departments_array['departmentid'] = $department->id ? $department->id : 0;
    $departments_array['deptaction_message'] = $deptaction_message;
    $departments_array['deptdel_confirmationmsg'] = $deptdel_confirmationmsg;
    $departments_content[] = $departments_array+$deparray;
}


$costcenter_view_content = [
    "deptcount" => $dept_count_link,
    "subdeptcount" => $subdepartment,
    "deptclass" => $deptclass, 
    "coursefileurl" => $OUTPUT->image_url('/course_images/courseimg', 'local_costcenter'),
    "orgname" => $depart->fullname,
    "orgnamecut" => strlen($depart->fullname) > 20 ? substr($depart->fullname, 0, 20)."..." : $depart->fullname,
    "edit" => $edit,
    "hide" => $hide,
    "show" => $show,
    "status" => $depart->visible,
    "delete" => $delete,
    "recordid" => $depart->id,
    "parentid" => $depart->parentid,
    "action_message" => $action_message,
    "delete_message" => $del_confirmationmsg,
    "departments_content" => $departments_content,
];

$costcenter_view_content = $costcenter_view_content+$pluginnavs;
$output = $PAGE->get_renderer('local_costcenter');
echo $output->get_dept_view_btns($id);
echo $OUTPUT->render_from_template('local_costcenter/departments_view', $costcenter_view_content);

echo $OUTPUT->footer();

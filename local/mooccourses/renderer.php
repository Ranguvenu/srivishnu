<?php

class local_mooccourses_renderer extends plugin_renderer_base {

function top_action_buttons(){
  global $CFG, $DB, $USER;
    $systemcontext = context_system::instance();
    $output = "";
         $output .= "<ul class='course_extended_menu_list'>";
     if(is_siteadmin() || has_capability('local/mooccourses:manage',$systemcontext)){
            $output .= '<li>
                         <div class="courseedit course_extended_menu_itemcontainer">
                  <a id="extended_menu_createcourses" class="pull-right course_extended_menu_itemlink" title = "'.get_string('create_newmooccourse','local_mooccourses').'" data-action="createcoursemodal" onclick="(function(e){ require(\'local_mooccourses/courseAjaxform\').init({contextid:'.$systemcontext->id.', component:\'local_mooccourses\', callback:\'custom_mooccourse_form\', form_status:0, plugintype: \'local\', pluginname: \'mooccourses\'}) })(event)">
                    <span class="createicon"><i class="icon fa fa-book"></i><i class="fa fa-plus createiconchild" aria-hidden="true"></i></span>
                  </a>
                </div>
                    </li>';
// <mallikarjun> - ODL-838 hide create template mooccourse to college head -- starts
$collegeheadroleid = $DB->get_field('role', 'id', array('shortname' => 'college_head'));
// <sandeep> - SRIVSPT-3 Icon is commented -- starts
// if($USER->open_role != $collegeheadroleid){
//             $output .= '<li>
//                  <div class="courseedit course_extended_menu_itemcontainer">
//                     <a id="extended_menu_createcourses" class="pull-right course_extended_menu_itemlink" title = "'.get_string('create_mooccourse','local_mooccourses').'" data-action="createcoursemodal" onclick="(function(e){ require(\'local_mooccourses/courseAjaxform\').moochcourseslist({contextid:'.$systemcontext->id.',form_status:1 })})(event)">
//                       <span class="createicon"><i class="icon fa fa-columns"></i><i class="fa fa-plus createiconchild" aria-hidden="true"></i></span>
//                     </a>
//                   </div>
//                       </li>';
// }
// <sandeep> - SRIVSPT-3 Icon is commented -- ends
// <mallikarjun> - ODL-838 hide create template mooccourse to college head -- end
                      //added bulk enrolment
            $enrol_url = new moodle_url('/local/mooccourses/bulkenrol/bulkuploadcron.php');
            $output .= '<li>
                 <div class="courseedit course_extended_menu_itemcontainer">
                    <a id="extended_menu_createcourses" class="pull-right course_extended_menu_itemlink" title = "'.get_string('bulkuploadenroll','local_mooccourses').'" href="'.$enrol_url.'">
                      <span class="createicon"><i class="icon fa fa-users"></i></span>
                    </a>
                  </div>
                      </li>';
                    //end
                    

      }

      $output .= "</ul>";
      echo $output;
    }

function mooccourses_view($filterdata,$type, $page, $perpage){
	global $DB, $CFG, $OUTPUT, $USER, $PAGE;
		$filterjson = json_encode($filterdata);
        $PAGE->requires->js_call_amd('local_mooccourses/mooccourses', 'soldcoursesDatatable', array('filterdata'=> $filterjson));

        $table = new html_table();
        $table->id = "viewsoldcourses";

        $table->head = array(get_string('coursename', 'local_mooccourses'),get_string('coursecode', 'local_mooccourses'),get_string('university', 'local_mooccourses'),get_string('deptcollege', 'local_program'),get_string('actions', 'local_mooccourses'));

        $table->align = array('center','left', 'center', 'left', 'left', 'left','center');
        $table->size = array('10%','13%', '10%', '16%', '20%', '14%','17%');


        $output = '<div class="w-full pull-left">'. html_writer::table($table).'</div>';
        return $output;
}
public function tabtrees($mode){
        global $OUTPUT;
         $systemcontext = context_system::instance();

            $tabs = array();
// <sandeep> - SRIVSPT-3 Tabs are commented -- starts
            // $tabs[] = new tabobject(1, new moodle_url($CFG->wwwroot . '/local/mooccourses/index.php',array('type'=>1)),'<span title="Template Courses">Template Courses</span>','Template Courses');
// <mallikarjun> - ODL-829 strings changed -- starts
            // $tabs[] = new tabobject(2, new moodle_url($CFG->wwwroot . '/local/mooccourses/index.php',array('type'=>2)),'<span title="College/Department Courses">College/Department Courses</span>','College/Department Courses');
// <sandeep> - SRIVSPT-3 Tabs are commented -- ends            
// <mallikarjun> - ODL-829 strings changed -- ends
            $return = $OUTPUT->tabtree($tabs, $mode);
            return $return;
       
    }

public function assignaffiliatecourses($cid, $uid, $currentcollegeselector, $potentialcollegeselector) {
        global  $DB,$PAGE, $OUTPUT, $CFG;
        $course = $DB->get_record('course', array('id' => $cid));

        $admissionenddate=date('d-m-Y H:i',$course->enddate);

        $footertitle=$OUTPUT->notification(get_string('mooccoursetitle','local_mooccourses', $course->fullname), 'notifymessage');

        // if($course->enddate >= time()){
            /*$headertitle=$OUTPUT->notification(get_string('headertitle','local_program',$admissionenddate), 'notifysuccess');*/

            $add='<input name="add" id="add" type="submit" value="' . $OUTPUT->larrow() . '&nbsp;' . get_string('add') . '" title="' . get_string('add') . '" />';

            $remove='<input name="remove" id="remove" type="submit" value="' . get_string('remove') . '&nbsp;' . $OUTPUT->rarrow() . '" title="' . get_string('remove') . '" />';
        // }else{
        //     /*$headertitle=$OUTPUT->notification(get_string('headertitle','local_program',$admissionenddate), 'notifyerror');*/

        //     $add='<input name="add" id="add" type="button" value="' . $OUTPUT->larrow() . '&nbsp;' . get_string('add') . '" title="' . get_string('add') . '" disabled class="btn btn-secondary"/>';

        //     $remove='<input name="remove" id="remove" type="button" value="' . get_string('remove') . '&nbsp;' . $OUTPUT->rarrow() . '" title="' . get_string('remove') . '" disabled class="btn btn-secondary"/>';
        // }
        $assignurl = new moodle_url($PAGE->url, array('cid' => $cid));
        $return = ''.$headertitle.''.$footertitle.'';
        $return .= '<form id="assignform" method="post" action="' . $assignurl . '">
                        <div>
                            <input type="hidden" name="sesskey" value="' . sesskey() . '" />
                            <table id="assigningrole" summary="" class="admintable roleassigntable generaltable" cellspacing="0">
                                <tr>
                                    <td id="existingcell">
                                        <p><label for="removeselect">' . get_string('extcolleges', 'local_program') . '</label></p>
                                        ' . $currentcollegeselector->display(true) . '
                                    </td>
                                    <td id="buttonscell">
                                        <div id="addcontrols">
                                        '.$add.'<br />
                                        </div>
                                        <div id="removecontrols">
                                            '.$remove.'
                                        </div>
                                    </td>
                                    <td id="potentialcell">
                                        <p><label for="addselect">' . get_string('potcolleges', 'local_program') . '</label></p>
                                        ' . $potentialcollegeselector->display(true) . '
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </form>';
        return $return;
    }
}

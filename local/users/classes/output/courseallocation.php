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
 * Course Allocation block
 *
 * @package    block_courseallocation
 * @copyright  2017 Arun Kumar Mukka
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace local_users\output;

defined('MOODLE_INTERNAL') || die;

use has_user_permission;
use core_component;
use local_users\output\courseallocation_lib;

// require_once(dirname(__FILE__) . '/../config.php');
class courseallocation {
    public function courseallocation_view() {
        global $CFG, $USER, $PAGE;
        $courseallocation_lib = new courseallocation_lib();

        $view = '<div class="coursesallocation_content">
                    <div id="allocation_notifications"></div>'.
                    $this->courseallocation_action().'
                    <div class="portlet-body course_allocation_block_container pull-left w-full">
                        <div class="col-md-6 team_allocation_users_list">
                            <div class="input-icon">
                                <i class="fa fa-search icon_search_inside"></i>
                                <input type="text" style="padding-left: 33px;" name="search_users" placeholder="'.get_string('allocate_search_users', 'local_users').'" class="form-control select-round searchusers" data-type="users" onkeyup="(function(e){ require(\'local_users/courseallocation\').teamsearch({ searchtype: \'users\', searchvalue: event.target.value }) })(event)">
                            </div>
                            <div class="mt-10px pull-left w-full team_list_container" id="departmentusers" >
                                <ul class="task-list departmentusers">';
        $view .= $courseallocation_lib->get_team_users($search = false);
                    $view .=    '</ul>
                            </div>
                        </div>
                        <div class="col-md-6 pt-15px pb-15px pr-0">
                            <div class="input-icon">
                                <i class="fa fa-search icon_search_inside"></i>
                                <input type="text" style="padding-left: 33px;" name="search_learningtypes" placeholder="'.get_string('allocate_search_learnings', 'local_users').'" class="form-control select-round searchcourses" onkeyup="(function(e){ require(\'local_users/courseallocation\').teamsearch({ searchtype: 1, searchvalue: event.target.value }) })(event)">
                            </div>
                        <div class="mt-10px w-full pull-left team_list_container" >
                        <ul class="task-list departmentcourses">';
                        $view .= "<li class='li-course empty_data'>
                                        <div class='alert alert-info text-center'>".get_string('select_user_toproceed', 'local_users')."</div>
                                    </li>";
                $view .= '</ul>
                    </div>
                </div>
            <div class="clearfix"></div>
            </div>
        </div>
        <div id="coursenominate_confirm" style="display:none;"></div>
        <input id="nominate_userslist" name="nominate_userslist" type="hidden" value="" />
        <input type="hidden" name="learning_type" id="learning_type" value=""/>
        <input id="nominate_courseslist" name="nominate_courseslist[]" type="hidden" value="" />';
        return $view;
    }

    public function courseallocation_action(){
        $core_component = new core_component();
        $courses_exists = false;
        $course_plugin = $core_component::get_plugin_directory('local', 'courses');
        if(!empty($course_plugin)){
            $courses_exists = true;
        }
        $classroom_exists = false;
        $classroom_plugin = $core_component::get_plugin_directory('local', 'classroom');
        if(!empty($classroom_plugin)){
            $classroom_exists = true;
        }
        $program_exists = false;
        $program_plugin = $core_component::get_plugin_directory('local', 'program');
        if(!empty($program_plugin)){
            $program_exists = true;
        }
        $certification_exists = false;
        // $certification_plugin = $core_component::get_plugin_directory('local', 'certification');
        // if(!empty($certification_plugin)){
        //     $certification_exists = true;
        // }
        // $learningplan_exists = false;
        // $learningplan_plugin = $core_component::get_plugin_directory('local', 'learningplan');
        // if(!empty($learningplan_plugin)){
        //     $learningplan_exists = true;
        // }
        // $onlinetests_exists = false;
        // $onlinetests_plugin = $core_component::get_plugin_directory('local', 'onlinetests');
        // if(!empty($onlinetests_plugin)){
        //     $onlinetests_exists = true;
        // }
        // $evaluation_exists = false;
        // $evaluation_plugin = $core_component::get_plugin_directory('local', 'evaluation');
        // if(!empty($evaluation_plugin)){
        //     $evaluation_exists = true;
        // }
        // $forum_exists = false;
        // $forum_plugin = $core_component::get_plugin_directory('local', 'forum');
        // if(!empty($forum_plugin)){
        //     $forum_exists = true;
        // }

        $actions = '<div class="portlet-title courseallocation_block_heading mb-10px mt-10px pull-left w-full">
                        <div class="caption pull-left">
                            <span class="caption-subject font-blue-madison uppercase">'.get_string('team_allocation', 'local_users').'</span>
                        </div>
                        <div class="actions pull-right dropdown">
                            <div class="btn-group allocation_course_type_btn">
                                <a href="" class="allocation_course_type btn-circle btn-sm dropdown-toggle text-center pull-right" data-toggle="dropdown" data-hover="dropdown" data-close-others="true" aria-expanded="false">
                                    '.get_string('learning_type', 'local_users').'
                                </a>
                                <ul class="dropdown-menu pull-right ongoingdropdown">';
        
        if($courses_exists == true){
            $actions .= '<li>
                            <a href="javascript:void(0);" onclick="(function(e){require(\'local_users/courseallocation\').select_type({learningtype: 1, user: null, pluginname: \''.get_string('pluginname', 'local_courses').'\'})})(event)" class="changed">
                                '.get_string('pluginname', 'local_courses').'
                            </a>
                        </li>';
        }
        if($classroom_exists == true){
            $actions .= '<li>
                            <a href="javascript:void(0);" onclick="(function(e){require(\'local_users/courseallocation\').select_type({learningtype: 2, user: null, pluginname: \''.get_string('pluginname', 'local_classroom').'\'})})(event)" class="changed">
                                '.get_string('pluginname', 'local_classroom').'
                            </a>
                        </li>';
        }
        if($program_exists == true){
            $actions .= '<li>
                            <a href="javascript:void(0);" onclick="(function(e){require(\'local_users/courseallocation\').select_type({learningtype: 3, user: null, pluginname: \''.get_string('pluginname', 'local_program').'\'})})(event)" class="changed">
                                '.get_string('pluginname', 'local_program').'
                            </a>
                        </li>';
        }
        // if($certification_exists == true){
        //     $actions .= '<li>
        //                     <a href="javascript:void(0);" onclick="(function(e){require(\'local_users/courseallocation\').select_type({learningtype: 4, user: null, pluginname: \''.get_string('pluginname', 'local_learningplan').'\'})})(event)" class="changed">
        //                         '.get_string('pluginname', 'local_learningplan').'
        //                     </a>
        //                 </li>';
        // }
        // if($learningplan_exists == true){
        //     $actions .= '<li>
        //                     <a href="javascript:;" onclick="learningtypefilter(2);" class="changed">
        //                         '.get_string('pluginname', 'local_learningplan').'
        //                     </a>
        //                 </li>';
        // }

        $actions .= '</ul>
                </div>
                <button class="btn1 btn allocate_button btn-sm pull-right ml-10px" data-toggle="modal" disabled data-target="#myModal" 
                            onclick = "(function(e){ require(\'local_users/courseallocation\').allocator({ searchtype: \'courses\', searchvalue: event.target.value }) })(event)"
                            >'.get_string('allocate', 'local_users').'</button>
            </div>
        </div>';
        return $actions;
    }

    public function get_team_courses_view($user, $search = false){
        global $DB;
        $courseallocation_lib = new courseallocation_lib();
        if(!empty($user)){
            $view = '<li class="li-course empty_data">
                        <div class="alert alert-info text-center">'.get_string('no_coursesfound', 'local_users').'</div>
                    </li>';
        }
        $courses = $courseallocation_lib->get_team_courses($user, $search);
        if(!empty($courses)){
            $view = '';
            foreach($courses as $cid => $cname){
                $disattr = '';//'disabled';".$disable."
                $checked = '';//'checked';".$checked."
                $icons = '';

                $sql = "SELECT c.id FROM {user_enrolments} as ue
                            JOIN {enrol} as e ON e.id = ue.enrolid
                            JOIN {course} as c ON c.id = e.courseid
                            WHERE e.courseid = :courseid AND ue.userid = :userid AND e.enrol = :enrollment";
                $costcenterid = $DB->get_field('user', 'open_costcenterid', array('id' => $user));
                if($costcenterid > 0){
                    $sql .= " AND c.open_costcenterid = ".$costcenterid;
                }

                $enrolled = $DB->record_exists_sql($sql,  array('courseid' => $cid, 'userid' => $user, 'enrollment' => 'manual'));
                
                if($enrolled == true){
                    $checked = 'checked';
                    $disattr = 'disabled';
                }

                // onchange='(function(e){require(\"local_users/courseallocation\").select_list({user: ".$user.", courses: ".$cid.", learningtype: 1, element: e.target}) })(event)'
                $view .= "<li class='li-course'>
                            <div class='task-checkbox'>
                                <div class='checker'>
                                    <span>
                                        <input type ='checkbox' data-type='courses' name='allocatecourse[]' ".$checked." ".$disattr."
                                        onchange='(function(e){require(\"local_users/courseallocation\").select_list({user: ".$user.", courses: ".$cid.", learningtype: 1, element: e.target}) })(event)'
                                         value=".$cid." class='liChild allocatecourse' />
                                    </span>
                                </div>
                            </div>
                            <div class='task-title'><span class='task-title-sp' > ".$cname." </span>".$icons."</div>
                        </li>";
            }
        }else{
            $view = '<li class="li-course empty_data">
                        <div class="alert alert-info text-center">'.get_string('no_coursesfound', 'local_users').'</div>
                    </li>';
        }
        return $view;
    }
    
    public function get_team_classrooms_view($user, $search = false){
        global $DB;
        $courseallocation_lib = new courseallocation_lib();
        if(!empty($user)){
            $view = '<li class="li-course empty_data">
                        <div class="alert alert-info text-center">'.get_string('no_classroomsfound', 'local_users').'</div>
                    </li>';
        }
        $classrooms = $courseallocation_lib->get_team_classrooms($user, $search);
        if(!empty($classrooms)){
            $view = '';
            foreach($classrooms as $classid => $classname){
                $disattr = '';//'disabled';".$disable."
                $checked = '';//'checked';".$checked."
                $icons = '';

                $sql = "SELECT cu.classroomid FROM {local_classroom_users} as cu
                            WHERE cu.classroomid = :classid and cu.userid = :userid";
                
                $enrolled = $DB->record_exists_sql($sql,  array('classid' => $classid, 'userid' => $user));
                if($enrolled == true){
                    $checked = 'checked';
                    $disattr = 'disabled';
                }

                // onchange='(function(e){require(\"local_users/courseallocation\").select_list({user: ".$user.", courses: ".$classid.", learningtype: 1, element: e.target}) })(event)'
                $view .= "<li class='li-course'>
                            <div class='task-checkbox'>
                                <div class='checker'>
                                    <span>
                                        <input type ='checkbox' data-type='courses' name='allocatecourse[]' ".$checked." ".$disattr."
                                        onchange='(function(e){require(\"local_users/courseallocation\").select_list({user: ".$user.", courses: ".$classid.", learningtype: 2, element: e.target}) })(event)'
                                         value=".$classid." class='liChild allocatecourse' />
                                    </span>
                                </div>
                            </div>
                            <div class='task-title'><span class='task-title-sp' > ".$classname." </span>".$icons."</div>
                        </li>";
            }
        }else{
            $view = '<li class="li-course empty_data">
                        <div class="alert alert-info text-center">'.get_string('no_classroomsfound', 'local_users').'</div>
                    </li>';
        }
        return $view;
    }

    public function get_team_programs_view($user, $search = false){
        global $DB;
        $courseallocation_lib = new courseallocation_lib();
        if(!empty($user)){
            $view = '<li class="li-course empty_data">
                        <div class="alert alert-info text-center">'.get_string('no_programsfound', 'local_users').'</div>
                    </li>';
        }
        $programs = $courseallocation_lib->get_team_programs($user, $search);
        if(!empty($programs)){
            $view = '';
            foreach($programs as $programid => $programname){
                $disattr = '';//'disabled';".$disable."
                $checked = '';//'checked';".$checked."
                $icons = '';
                $extra_class = '';

                $sql = "SELECT pu.programid FROM {local_program_users} as pu
                            WHERE pu.programid = :programid and pu.userid = :userid";
                
                $enrolled = $DB->record_exists_sql($sql, array('programid' => $programid, 'userid' => $user));
                if($enrolled == true){
                    $checked = 'checked';
                    $disattr = 'disabled';
                    $extra_class = 'checked_b4';
                }

                $view .= "<li class='li-course'>
                            <div class='task-checkbox'>
                                <div class='checker'>
                                    <span>
                                        <input type ='checkbox' data-type='courses' name='allocatecourse[]' ".$checked." ".$disattr."
                                        onchange='(function(e){require(\"local_users/courseallocation\").select_list({user: ".$user.", courses: ".$programid.", learningtype: 3, element: e.target}) })(event)'
                                         value=".$programid." class='liChild allocatecourse ".$extra_class."' />
                                    </span>
                                </div>
                            </div>
                            <div class='task-title'><span class='task-title-sp' > ".$programname." </span>".$icons."</div>
                        </li>";
            }
        }else{
            $view = '<li class="li-course empty_data">
                        <div class="alert alert-info text-center">'.get_string('no_programsfound', 'local_users').'</div>
                    </li>';
        }
        return $view;
    }
    
    // public function get_team_learningpaths_view($user, $search = false){
    //     global $DB;
    //     $courseallocation_lib = new courseallocation_lib();
    //     if(!empty($user)){
    //         $view = '<li class="li-course">
    //                     <div class="alert alert-info">no learning paths found.</div>
    //                 </li>';
    //     }
    //     $learningpaths = $courseallocation_lib->get_team_learningpaths($user, $search);
    //     if(!empty($learningpaths)){
    //         $view = '';
    //         foreach($learningpaths as $learningpathid => $learningpathname){
    //             $disattr = '';//'disabled';".$disable."
    //             $checked = '';//'checked';".$checked."
    //             $icons = '';

    //             $sql = "SELECT lu.planid FROM {local_learningplan_user} as lu
    //                         WHERE lu.planid = :planid and lu.userid = :userid";
                
    //             $enrolled = $DB->record_exists_sql($sql,  array('planid' => $learningpathid, 'userid' => $user));
    //             if($enrolled == true){
    //                 $checked = 'checked';
    //                 $disattr = 'disabled';
    //             }

    //             // onchange='(function(e){require(\"local_users/courseallocation\").select_list({user: ".$user.", courses: ".$classid.", learningtype: 1, element: e.target}) })(event)'
    //             $view .= "<li class='li-course'>
    //                         <div class='task-checkbox'>
    //                             <div class='checker'>
    //                                 <span>
    //                                     <input type ='checkbox' data-type='courses' name='allocatecourse[]' ".$checked." ".$disabled."
    //                                     onchange='(function(e){require(\"local_users/courseallocation\").select_list({user: ".$user.", courses: ".$learningpathid.", learningtype: 2, element: e.target}) })(event)'
    //                                      value=".$learningpathid." class='liChild allocatecourse' />
    //                                 </span>
    //                             </div>
    //                         </div>
    //                         <div class='task-title'><span class='task-title-sp' > ".$learningpathname." </span>".$icons."</div>
    //                     </li>";
    //         }
    //     }else{
    //         $view = '<li class="li-course">
    //                     <div class="alert alert-info">no learning paths found.</div>
    //                 </li>';
    //     }
    //     return $view;
    // }

}
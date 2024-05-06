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
 * @package    local_users
 * @subpackage team_approvals
 * @copyright  2017 Syed Hameed Ullah <hameed@eabyas.in>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace local_users\output;

defined('MOODLE_INTERNAL') || die;
use core_component;
use local_users\output\team_approvals_lib;
use local_request\api\requestapi;

class team_approvals {
    public function team_approvals_view() {
        global $CFG, $USER, $PAGE;
// $this->team_approval_actions()
// $view .= $team_approvals_lib->get_team_approval_requests($search = false);
        $view = "<div class='team_approvals_content pull-left w-full'>";
        $view .= '<div class="team_approval_head pull-left w-full mb-10px mt-10px">';
        $view .= $this->team_approval_actions();
        $view .= "</div>";
        $view .= '<div class="team_approval_body pull-left col-md-12">';
        $view .= '<div class="input-icon" >
                                <i class="fa fa-search icon_search_inside"></i>
                                <input type="text" style="padding-left: 33px;" name="search_requests" placeholder="'.get_string('team_requests_search', 'local_users').'" class="form-control select-round searchcourses" onkeyup="(function(e){ require(\'local_users/team_approvals\').requestsearch({ learningtype: \'elearning\', searchvalue: event.target.value }) })(event)">
                            </div>';
            $view .= '<div class="mt-10px pull-left w-full request_list_container">';
                $view .= '<ul class="task-list w-full" id="team_requests_list">';
                    $view .= $this->team_approval_records_list();
                $view .= "</ul>";
            $view .= "</div>";
        $view .= "</div>";
        $view .= "</div>";
        $view .= '<input id="approval_selected_user" name="approval_selected_user" type="hidden" value="" />
                 <input type="hidden" name="approval_learning_type" id="approval_learning_type" value="elearning"/>';
        return $view;
    }

    public function team_approval_actions(){
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

        $actions = '<div class="portlet-title courseallocation_block_heading">
                        <div class="caption pull-left">
                            <span class="caption-subject font-blue-madison uppercase" >'.get_string('team_approvals', 'local_users').'</span>
                        </div>
                        <div class="actions pull-right dropdown">
                            <div class="btn-group allocation_course_type_btn">
                                <a href="javascript:void(0);" class="team_learningtype_dropdown dropdown-toggle text-center pull-right" data-toggle="dropdown"data-close-others="true" aria-expanded="false">
                                    '.get_string('courses').'
                                </a>
                                <ul class="dropdown-menu pull-right ongoingdropdown">';
        
        if($courses_exists == true){
            $actions .= '<li>
                            <a href="javascript:void(0);" onclick="(function(e){require(\'local_users/team_approvals\').select_learningtype(
                                    {learningtype: \'elearning\', pluginname: \''.get_string('pluginname', 'local_courses').'\'}
                                )})(event)" class="changed">
                                '.get_string('pluginname', 'local_courses').'
                            </a>
                        </li>';
        }
        if($classroom_exists == true){
            $actions .= '<li>
                            <a href="javascript:void(0);" onclick="(function(e){require(\'local_users/team_approvals\').select_learningtype(
                                    {learningtype: \'classroom\', pluginname: \''.get_string('pluginname', 'local_classroom').'\'}
                                )})(event)" class="changed">
                                '.get_string('pluginname', 'local_classroom').'
                            </a>
                        </li>';
        }
        if($program_exists == true){
            $actions .= '<li>
                            <a href="javascript:void(0);" onclick="(function(e){require(\'local_users/team_approvals\').select_learningtype(
                                    {learningtype: \'program\', pluginname: \''.get_string('pluginname', 'local_program').'\'}
                                )})(event)" class="changed">
                                '.get_string('pluginname', 'local_program').'
                            </a>
                        </li>';
        }
        $actions .= '</ul>
                </div>
                <button class="btn request_approval_btn btn-sm pull-right ml-10px" disabled onclick="(function(e){ require(\'local_users/team_approvals\').approve_request({ learningtype: \'courses\'}) })(event)"
                >'.get_string('approve', 'local_users').'</button>
            </div>
        </div>';
        return $actions;
    }

    public function team_approval_records_list($learningtype = 'elearning', $search=""){
        global $DB, $OUTPUT;
        $team_approvals_lib = new team_approvals_lib();
        $team_requests = $team_approvals_lib->get_team_approval_requests($learningtype, $search);
        // print_object($team_requests);
        $return = '';
        if(!empty($team_requests)){
            foreach ($team_requests as $team_request) {
                $requestid = $team_request->id;
                $request_user = $team_request->createdbyid;
                $component_name = $team_request->compname;
                $componentid = $team_request->componentid;
                $status = $team_request->status;
                $requested_user = \core_user::get_user($request_user);
                if($component_name == 'classroom'){//for classrooms
                    $icons = '<img src="'.$OUTPUT->image_url('team_cricon', 'local_users').'"/>';
                    $actual_component_name = '<b>'.fullname($requested_user).'</b> has requested for ';
                    $actual_component_name .= $DB->get_field('local_classroom', 'name', array('id' => $componentid, 'visible' => 1));
                }elseif($component_name == 'learningplan'){//for learningplans
                    $icons = '<i class="fa fa-map"></i>';
                    $actual_component_name = '<b>'.fullname($requested_user).'</b> has requested for ';
                    $actual_component_name .= $DB->get_field('local_learningplan', 'name', array('id' => $componentid, 'visible' => 1));
                }elseif($component_name == 'program'){//for program
                    $icons = '<i class="fa fa-tasks"></i>';
                    $actual_component_name = '<b>'.fullname($requested_user).'</b> has requested for ';
                    $actual_component_name .= $DB->get_field('local_program', 'name', array('id' => $componentid, 'visible' => 1));
                }else{//default for courses/e-learning
                    $icons = '<i class="fa fa-book"></i>';
                    $actual_component_name = '<b>'.fullname($requested_user).'</b> has requested for ';
                    $actual_component_name .= $DB->get_field('course', 'fullname', array('id' => $componentid, 'visible' => 1));
                }
                
                if($status == 'PENDING' || $status == 'REJECTED'){
                    $checked = '';
                    $disattr = '';
                }else{
                    $checked = 'checked';
                    $disattr = 'disabled';
                }
                $return .= "<li class='li-course'>
                            <div class='task-checkbox'>
                                <div class='checker'>
                                    <span>
                                        <input type='checkbox' name='team_requests[]' ".$checked." ".$disattr." value=".$requestid." 
                                        onchange='(function(e){require(\"local_users/team_approvals\").select_request({requestid: ".$requestid.", learningtype: \"".$component_name."\", element: e.target}) })(event)'
                                        class='liChild allocatecourse' />
                                    </span>
                                </div>
                            </div>
                            <div class='task-title'>".$icons."<span class='task-title-sp m-l-5' > ".$actual_component_name." </span></div>
                        </li>";

                        // onchange='(function(e){require(\"local_users/team_approvals\").select_request(
                        //                 {user: ".$request_user.", request: ".$requestid.", learningtype: '".$component_name."', element: e.target}
                        //             ) })(event)'
            }
        }else{
            $return .= '<li class="li-course empty_data">
                            <div class="alert alert-info text-center">'.get_string('no_team_requests', 'local_users').'</div>
                        </li>';
        }
        return $return;
    }

    public function team_requests_approved($learningtype, $requeststoapprove){
        global $DB;
        if(empty($learningtype) || empty($requeststoapprove)){
            return false;
        }
        $requestapi = new requestapi();
        $requeststoapprove = explode(',', $requeststoapprove);
        $return = array();
        foreach ($requeststoapprove as $request) {
            $record_exists = $DB->record_exists('local_request_records', array('id' => $request, 'status' => 'APPROVED'));
            if(!$record_exists){
                $return[$request] = $requestapi->approve($request);
            }else{
                $return[$request] = false;
            }
        }
        return $return;
    }

}

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
 * Classroom View
 *
 * @package    local
 * @subpackage users
 * @copyright  2018 Syed Hameed Ullah <hameed@eabyas.in>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_users\output;

defined('MOODLE_INTERNAL') || die;
use context_system;
use stdClass;
use core_component;
use html_table;
use html_writer;
use local_users\output\team_status_lib;

class myteam{
    
    public function team_status_view() {
		global $CFG, $USER, $PAGE, $DB;
		$systemcontext =context_system::instance();
		$course_exist = 0;
		$classroom_exist = 0;
		$program_exist = 0;
		$learningpath_exist = 0;
		$onlinetests_exist = 0;
		$certification_exist = 0;
		$core_component = new core_component();
		$courses_plugin_exist = $core_component::get_plugin_directory('local','courses');
		if(!empty($courses_plugin_exist)){
			$course_exist = 1;
		}
		$classroom_plugin_exist = $core_component::get_plugin_directory('local','classroom');
		if(!empty($classroom_plugin_exist)){
			$classroom_exist = 1;
		}
		$program_plugin_exist = $core_component::get_plugin_directory('local','program');
		if(!empty($program_plugin_exist)){
			$program_exist = 1;
		}
		$learningpath_plugin_exist = $core_component::get_plugin_directory('local','learningplan');
		if(!empty($learningpath_plugin_exist)){
			$learningpath_exist = 1;
		}
		$certification_plugin_exist = $core_component::get_plugin_directory('local','certification');
		if(!empty($certification_plugin_exist)){
			$certification_exist = 1;
		}

		$onlinetests_plugin_exist = $core_component::get_plugin_directory('local','onlinetests');
		if(!empty($onlinetests_plugin_exist)){
			$onlinetests_exist = 1;
		}
		$teamstatus = new team_status_lib();
		$teammembers = $teamstatus->get_team_members();
		if(!empty($teammembers)){
			$data = array();
			foreach($teammembers as $teammember){
				// $totalelearningcourses = $teamstatus->get_team_member_course_status($teammember->id);
				if($course_exist){
					$mandatorycourses = $teamstatus->get_team_member_course_status($teammember->id);
					$totalelearningcourses = $mandatorycourses->enrolled;
					$mand_color = $teamstatus->get_colorcode_tm_dashboard($mandatorycourses->completed,$mandatorycourses->enrolled);
				}
				
				if($classroom_exist){
					$classroomcourses = $teamstatus->get_team_member_ilt_status($teammember->id);
					$classroomcompleted = $classroomcourses->completed;
					$classroomtotal = $classroomcourses->total;
					$class_color = $teamstatus->get_colorcode_tm_dashboard($classroomcompleted,$classroomtotal); 
				}
				
				if($program_exist){
					$programcourses = $teamstatus->get_team_member_program_status($teammember->id);
					$programcompleted = $programcourses->completed;
					$programtotal = $programcourses->total;
					$program_color = $teamstatus->get_colorcode_tm_dashboard($programcompleted,$programtotal);
				}

				if($learningpath_exist){
					$learningplans = $teamstatus->get_team_member_lp_status($teammember->id);
					$complearningplans = $teamstatus->get_team_member_lp_status($teammember->id,1);
					$learningplans_total = $learningplans;
					$lp_color = $teamstatus->get_colorcode_tm_dashboard($complearningplans,$learningplans);
				}

				if($certification_exist){
					$certificationcourses = $teamstatus->get_team_member_certification_status($teammember->id);
					$certificationcompleted = $certificationcourses->completed;
					$certificationtotal = $certificationcourses->total;
					$cert_color = $teamstatus->get_colorcode_tm_dashboard($certificationcompleted,$certificationtotal);
				}

				if($onlinetests_exist){
					$onlinetests_total = $teamstatus->get_team_member_onlinetest_status($teammember->id);
					$componlinetests = $teamstatus->get_team_member_onlinetest_status($teammember->id,1);
					
					$onlinetest_color = $teamstatus->get_colorcode_tm_dashboard($componlinetests,$onlinetests_total);
				}

				
				$badgecount = $DB->count_records_sql("SELECT count(id) FROM {badge_issued} WHERE userid = $teammember->id");
				$totalbadgesql = "SELECT count(id) FROM {badge}";
				$totalbadges = $DB->count_records_sql($totalbadgesql);
				$badge_color = $teamstatus->get_colorcode_tm_dashboard($badgecount,$totalbadges);
				
				$row = array();
				$row[] = '<a href="'.$CFG->wwwroot.'/local/users/profile.php?id='.$teammember->id.'">'.fullname($teammember).'</a>';

				//this condition s modified by sharath
				if($course_exist){
					$courses_count = html_writer::tag('span', $mandatorycourses->completed . ' / ' . $mandatorycourses->enrolled, array('style'=>'margin-left:10px','class' => 'label_colored_info label_'.$mand_color,'data-action' => 'popupmodalcourse'.$teammember->id));
					$PAGE->requires->js_call_amd('local_users/popupcount', 'init',
		        	array(array('selector'=>'[data-action=popupmodalcourse'.$teammember->id.']','contextid'=>$systemcontext->id,
		                'id'=>$teammember->id, 'username'=> $teammember->firstname,'moduletype' => 'courses')));
					$row[] = $courses_count;
				}
				
				if($classroom_exist){
					$classroom_count = html_writer::tag('span', $classroomcompleted . ' / ' . $classroomtotal, array('style'=>'margin-left:10px','class' => 'label_colored_info label_'.$class_color,'data-action' => 'popupmodalclassroom'.$teammember->id));
					$PAGE->requires->js_call_amd('local_users/popupcount', 'init',
		        	array(array('selector'=>'[data-action=popupmodalclassroom'.$teammember->id.']','contextid'=>$systemcontext->id,
		                'id'=>$teammember->id, 'username'=> $teammember->firstname,'moduletype' => 'classrooms')));
					$row[] = $classroom_count;
				}

				if($program_exist){
					$program_count = html_writer::tag('span', $programcompleted . ' / ' . $programtotal, array('style'=>'margin-left:10px','class' => 'label_colored_info label_'.$program_color,'data-action' => 'popupmodalprogram'.$teammember->id));
					$PAGE->requires->js_call_amd('local_users/popupcount', 'init',
		        	array(array('selector'=>'[data-action=popupmodalprogram'.$teammember->id.']','contextid'=>$systemcontext->id,
		                'id'=>$teammember->id, 'username'=> $teammember->firstname,'moduletype' => 'programs')));
					$row[] = $program_count;
				}

				if($certification_exist){
					$certification_count = html_writer::tag('span', $certificationcompleted . ' / ' . $certificationtotal, array('style'=>'margin-left:10px','class' => 'label_colored_info label_'.$cert_color,'data-action' => 'popupmodalcert'.$teammember->id));
					$PAGE->requires->js_call_amd('local_users/popupcount', 'init',
		        	array(array('selector'=>'[data-action=popupmodalcert'.$teammember->id.']','contextid'=>$systemcontext->id,
		                'id'=>$teammember->id, 'username'=> $teammember->firstname,'moduletype' => 'certifications')));
					$row[] = $certification_count;
				}

				if($learningpath_exist){
					$learningpath_count = html_writer::tag('span', $complearningplans . ' / ' . $learningplans_total, array('style'=>'margin-left:10px','class' => 'label_colored_info label_'.$lp_color,'data-action' => 'popupmodallp'.$teammember->id));
					$PAGE->requires->js_call_amd('local_users/popupcount', 'init',
		        	array(array('selector'=>'[data-action=popupmodallp'.$teammember->id.']','contextid'=>$systemcontext->id,
		                'id'=>$teammember->id, 'username'=> $teammember->firstname,'moduletype' => 'learningplans')));
					$row[] = $learningpath_count;
				}

				if($onlinetests_exist){
					$onlinetests_count = html_writer::tag('span', $componlinetests . ' / ' . $onlinetests_total, array('style'=>'margin-left:10px','class' => 'label_colored_info label_'.$onlinetest_color,'data-action' => 'popupmodalonline'.$teammember->id));
					$PAGE->requires->js_call_amd('local_users/popupcount', 'init',
		        	array(array('selector'=>'[data-action=popupmodalonline'.$teammember->id.']','contextid'=>$systemcontext->id,
		                'id'=>$teammember->id, 'username'=> $teammember->firstname,'moduletype' => 'onlinetests')));
					$row[] = $onlinetests_count;
				}


				$badges_count = html_writer::tag('span', $badgecount . ' / ' . $totalbadges, array('style'=>'margin-left:10px','class' => 'label_colored_info label_'.$badge_color,'data-action' => 'popupmodalbadge'.$teammember->id));
					$PAGE->requires->js_call_amd('local_users/popupcount', 'init',
		        	array(array('selector'=>'[data-action=popupmodalbadge'.$teammember->id.']','contextid'=>$systemcontext->id,
		                'id'=>$teammember->id, 'username'=> $teammember->firstname,'moduletype' => 'badges')));
				$row[] = $badges_count;
				$data[] = $row;
				//ended here by sarath
			}
			
			$table = new html_table();
			$table->data = $data;
			$table->attributes['class'] = 'table-member generaltable myteam_status';
			$header = array();
			$table_align = array();
			$table_size = array();
			$header[] = get_string('members', 'local_users');
			$table_align[] = 'left';
			$table_size[] = '35%';
			if($course_exist){
				$header[] = get_string('pluginname', 'local_courses');
				$table_align[] = 'center';
				$table_size[] = '10%';
			}if($classroom_exist){
				$header[] = get_string('pluginname', 'local_classroom');
				$table_align[] = 'center';
				$table_size[] = '10%';
			}if($program_exist){
				$header[] = get_string('pluginname', 'local_program');
				$table_align[] = 'center';
				$table_size[] = '10%';
			}if($certification_exist){
				$header[] = get_string('pluginname', 'local_certification');
				$table_align[] = 'center';
				$table_size[] = '10%';
			}if($learningpath_exist){
				$header[] = get_string('pluginname', 'local_learningplan');
				$table_align[] = 'center';
				$table_size[] = '15%';
			}
			if($onlinetests_exist){
				$header[] = get_string('pluginname', 'local_onlinetests');
				$table_align[] = 'center';
				$table_size[] = '15%';
			}

			$header[] = get_string('badges');
			$table_align[] = 'center';
			$table_size[] = '10%';

			$table->head = $header;
			$table->size = $table_size;
			$table->align = $table_align;
			
			$team_statis_data = html_writer::table($table);
			$team_statis_data .= html_writer::script("$(\".myteam_status\").dataTable({
														  'iDisplayLength':5,
														'bLengthChange': false,
														'bInfo': false,
														language: {
														       search: '',
														       searchPlaceholder: '".get_string('allocate_search_users', 'local_users')."',
															paginate: {
																'previous': '<',
																'next': '>'
															},
														oLanguage: { 'search': '<i class=\"fa fa-search\"></i>' },
														    \"emptyTable\": \"<div class='alert alert-info'>'".get_string('team_nodata', 'local_users')."'</div>\"
														}
														});"
													);
		} else {
			$team_statis_data = '<div class="alert alert-info">'.get_string('team_nodata', 'local_users').'</div>';
		}
		$view = '<div class="">
                                        <div class="portlet-title">
                                            <div class="actions">
                                            </div>
                                        </div>
                                        <div class="portlet-body">
                                            <div class="slimScrollDiv" style="position: relative; overflow-y: auto; width: auto;  padding-right: 5px;">
											'.$team_statis_data.'
                                            </div>
                                        </div>
                                    </div>';
									
		return $view;
	}
}
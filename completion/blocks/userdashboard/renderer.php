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
 * @package   block
 * @subpackage  userdashboard
 * @copyright  2017  Syed Hameed Ullah <hameed@eabyas.in>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
global $DB, $OUTPUT, $USER, $CFG, $PAGE;
require_once dirname(dirname(dirname(__FILE__))) . '/config.php';
use core_component;
// require_once $CFG->dirroot . '/blocks/userdashboard/lib.php';
require_once $CFG->dirroot . '/local/includes.php';
// require_once $CFG->dirroot . '/local/evaluation/lib.php';

// require_once $CFG->dirroot . '/local/onlinetests/lib.php';
// require_once($CFG->dirroot.'/local/learningplan/lib.php');
//require_once($CFG->dirroot.'/mod/facetoface/lib.php');

class block_userdashboard_renderer extends plugin_renderer_base {



	public function userdashboard_view() {
		global $DB, $PAGE, $USER, $CFG, $OUTPUT;
		$core_component = new core_component();
		$out = '';
	//	$out .= html_writer::start_tag("ul", array("class" => "block_dashboard_tabslist col-md-3 col-12"));
        $program_plugin_exist = $core_component::get_plugin_directory('local', 'program');
         if($program_plugin_exist){
			$out .= html_writer::start_tag("div", array("id" => "block_dashboard_tabslistitem"));
			$out .= html_writer::start_tag("div", array("class" => "dashboard-stat active_main_tab"));
			$tab_icon = html_writer::tag('i', '', array('class' => 'fa fa-tasks'));
			$out .= html_writer::tag('a', $tab_icon . get_string('programs', 'block_userdashboard'), array('id' => 'program_courses', 'class' => 'more'));
			$out .= html_writer::end_tag("div");
			$out .= html_writer::end_tag("div");
		}
        $out .= html_writer::start_tag("div", array("id" => "block_dashboard_tabslistitem"));
		$out .= html_writer::start_tag("div", array("class" => "dashboard-stat"));
		$tab_icon = html_writer::tag('i', '', array('class' => 'fa fa-book'));
		$out .= html_writer::tag('a', $tab_icon . get_string('courses','block_userdashboard'), array('id' => 'elearning_courses', 'class' => 'more'));
		$out .= html_writer::end_tag("div");
		$out .= html_writer::end_tag("div");
       
		
		
		//$out .= html_writer::end_tag("ul");

		return $out;
	}


	public function user_tabs(){
		global $CFG;


		$programs_tab=true;
		$courses_tab=true;
		$inprogress=true;
		$completed=true;

		$usertabslist = [
      
            'contextid' => '1',
            'plugintype' => 'block',
            'plugin_name' =>'userdashboard',
            'programs_tab'=>$programs_tab,
            'courses_tab'=>$courses_tab,
            'inprogress_tab'=>$inprogress,
            'completed_tab'=>$completed,
           
        ];

		//calling the mustache template
		return $this->render_from_template('block_userdashboard/usertabs',$usertabslist);
	}
    


    public function render_manage_elearning_courses(\block_userdashboard\output\program_courses $page) {  
		 $data = $page->export_for_template($this); 
		 $data->inprogress_elearning=json_decode($data->inprogress_elearning, true); 
		 return parent::render_from_template('block_userdashboard/userdashboard_courses', $data);
	} 

	public function dashboard_for_endusers($courses, $curr_tab){

		switch ($courses) {
		  /******This case is for the e-learning tabs to view By Ravi_369******/
		case 'program_courses': 

			$page = new \block_userdashboard\output\program_courses('inprogress','');
			return $this->render_manage_elearning_courses($page);
		break;

	 }// end of switch

	 } // end of function
}

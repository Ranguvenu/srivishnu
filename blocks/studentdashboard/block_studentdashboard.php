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
 * The studentdashboard block
 *
 * @package    block
 * @subpackage    studentdashboard
 * @copyright 2017 Syed Hameed Ullah <hameed@eabyas.in>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class block_studentdashboard extends block_base {
	public function init() {
		$systemcontext = context_system::instance();
	
			$this->title = get_string('myprofile', 'block_studentdashboard');
		
	}
	public function get_content() {

		global $CFG, $PAGE;
		$this->content = new stdClass;
		$systemcontext = context_system::instance();
		if(!is_siteadmin() && has_capability('block/studentdashboard:view', $systemcontext)){
			
		
			$renderer = $PAGE->get_renderer('block_studentdashboard');
			$studentcontent = $renderer->studentprofile_view();
		
		
		}

		$this->content->text .= $studentcontent;
		return $this->content;
	}

}

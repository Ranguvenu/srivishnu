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
 * The facultydashboard block
 *
 * @package    block
 * @subpackage    facultydashboard
 * @copyright 2017 Syed Hameed Ullah <hameed@eabyas.in>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class block_program_courselist extends block_base {
	public function init() {
		$systemcontext = context_system::instance();
	
			$this->title = get_string('programcourselist', 'block_program_courselist');
		
	}
	public function get_content() {

		global $CFG, $PAGE;
		$this->content = new stdClass;
		$systemcontext = context_system::instance();
		if(!is_siteadmin() && !has_capability('local/costcenter:manage_multiorganizations', $systemcontext) && !has_capability('local/costcenter:manage_ownorganization', $systemcontext) && !has_capability('local/costcenter:manage_owndepartments', $systemcontext)){
			$renderer = $PAGE->get_renderer('block_program_courselist');
			if(!is_siteadmin() && has_capability('local/program:trainer_viewprogram', $systemcontext)){
				$studentcontent = $renderer->faculty_program_courselist_view();
			}else{
				$studentcontent = $renderer->program_courselist_view();
			}
		}

		$this->content->text .= $studentcontent;
		return $this->content;
	}

}

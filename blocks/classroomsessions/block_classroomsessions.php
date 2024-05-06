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
 * The classroomsessions block
 *
 * @package    block
 * @subpackage    classroomsessions
 * @copyright 2017 Harish <harish@eabyas.in>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
use \block_classroomsessions\output\view as view;

class block_classroomsessions extends block_base {
	public function init() {
		$this->title = get_string('classroomsessions', 'block_classroomsessions');
	}

	// function hide_header() {
	// 	return true;
	// }

	function instance_allow_multiple() {
		return false;
	}

	function get_content() {
		// global $PAGE
		if ($this->content !== NULL) {
			return $this->content;
		}
		$systemcontext = context_system::instance();
		if (is_siteadmin() || !has_capability('block/classroomsessions:view', $systemcontext) || !has_capability('block/facultydashboard:view', $systemcontext)) {
			return '';
		}
		$this->content = new stdClass();
		$facultyview = new view();
		$this->content->text = $facultyview->display_clroomsessions_dashboard();

		$this->content->footer = '';
		return $this->content;
	}
}

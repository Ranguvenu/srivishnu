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
 * The faculty_dashboard block
 *
 * @package    block_faculty_dashboard
 * @copyright 2017 Syed Hameed Ullah <hameed@eabyas.in>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
use \block_faculty_dashboard\output\view as view;

class block_faculty_dashboard extends block_base {

	function init() {
		$this->title = get_string('facultycourses', 'block_faculty_dashboard');
	}

	function instance_allow_multiple() {
		return false;
	}

	// function hide_header() {
	// 	return true;
	// }
	function get_content() {
		// global $PAGE
		if ($this->content !== NULL) {
			return $this->content;
		}
		$systemcontext = context_system::instance();
		if (is_siteadmin() || !has_capability('block/faculty_dashboard:view', $systemcontext)) {
			return '';
		}
		$this->content = new stdClass();
		$facultyview = new view();
		$this->content->text = $facultyview->display_faculty_dashboard();
		$this->content->footer = '';
		return $this->content;
	}
}

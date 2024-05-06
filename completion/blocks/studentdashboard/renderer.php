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
 * @subpackage  studentdashboard
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
global $DB, $OUTPUT, $USER, $CFG, $PAGE;
require_once dirname(dirname(dirname(__FILE__))) . '/config.php';
use core_component;
// require_once $CFG->dirroot . '/blocks/studentdashboard/lib.php';
require_once $CFG->dirroot . '/local/includes.php';

class block_studentdashboard_renderer extends plugin_renderer_base {

	public function studentprofile_view() {
		global $DB, $PAGE, $USER, $CFG, $OUTPUT;
		$systemcontext = context_system::instance();
		$userdata = $DB->get_record_sql("SELECT * FROM {user} WHERE id = $USER->id");
		$rolename = $DB->get_field('role','name',array('id' => $userdata->open_role));
		$role = !empty($rolename) ? $rolename : 'Student';
		//$collegename = $DB->get_field('local_costcenter','fullname',array('id' => $userdata->open_departmentid,'univ_dept_status' => 1));
		$sql = "SELECT id,fullname FROM {local_costcenter} WHERE id = $userdata->open_departmentid";
		$collegename = $DB->get_record_sql($sql);
		$college = !empty($collegename) ? $collegename : 'N/A';
		$universityname = $DB->get_field('local_costcenter','fullname',array('id' => $userdata->open_costcenterid));
		$university = !empty($universityname) ? $universityname : 'N/A';
		$email = !empty($userdata->email) ? $userdata->email : 'N/A';
		$fullname = fullname($userdata);
	
		$userpicture = $OUTPUT->user_picture($USER, array('size'=>150, 'class' => 'userpic_db', 'link' => false));
		


		if(has_capability('block/studentdashboard:view', $systemcontext)){
			$studentrole = true;
		}else{
			$studentrole = false;
		}

		$data = [
			'username' => $fullname,
			'role' => $role,
			'university' => $university,
			'college' => $college->fullname,
			'email' => $email,
			'userpicture' => $userpicture,
			'profilesrc'=> $CFG->wwwroot. '/local/users/profile.php?id=' .$USER->id
			
		];
		return $OUTPUT->render_from_template('block_studentdashboard/studentview', $data);
	}
}
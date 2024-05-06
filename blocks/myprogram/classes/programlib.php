<?php
namespace block_myprogram;
class programlib{
	public $user;
	public $db;
	public function __construct($user=null, $db=null){
		global $DB, $USER;
		$this->db = !empty($db) ? $db : $DB;
		$this->user = !empty($user) ? $user : $USER;
	}
	public function block_content(){
		global $OUTPUT,$USER;
		$instructorid = $this->get_instructorrole_id();
		// print_object($instructorid);exit;
		$myprogarms = $this->get_userprograms();
		$content = '';
		foreach($myprogarms AS $program){
			$mycourses = $this->get_useronlinecourses($program->programid,$USER->id);
			$templatedata = array();
			foreach($mycourses AS $course){ 
				$instructorsql = "SELECT u.id, concat(u.firstname,' ',u.lastname) FROM {user} AS u
					JOIN {role_assignments} AS ra ON ra.userid = u.id
					JOIN {context} AS c ON c.contextlevel = 50 AND c.id = ra.contextid
					WHERE c.instanceid = :courseid AND ra.roleid=:instructorid ";
				$instructorsdata = $this->db->get_records_sql_menu($instructorsql, array('courseid' => $course->courseid, 'instructorid' => $instructorid));
				$instructors = implode(',', $instructorsdata);
				$course->instructorname = $instructors;
				$templatedata['courses'][] = $course;	
			}
			if(!empty($templatedata['courses'])){
				$templatedata['enabletable'] = true;
			}else{
				$templatedata['enabletable'] = false;
			}
			$templatedata['programname'] = $program->programname;
			$content .= $OUTPUT->render_from_template('block_myprogram/program', $templatedata);
		}
		if(!empty($content)){
			$content = \html_writer::div($content, '', array('id' => 'accordian'));
		}
		return $content;
	}
	public function get_userprograms(){
		// $programssql = "SELECT lsu.programid, lsp.fullname FROM {local_sisuserdata} AS lsu
		// 	JOIN {local_sisprograms} AS lsp ON lsp.id=lsu.programid 
		// 	WHERE lsu.mdluserid=:userid GROUP BY lsu.programid ";
		$programssql = "SELECT DISTINCT(programid), programname FROM {local_courseenrolments} WHERE mdluserid=:userid GROUP BY programname ";
		$programs = $this->db->get_records_sql($programssql, array('userid' => $this->user->id));
		return $programs;
	}
	public function get_useronlinecourses($programid,$userid){
		
		// $onlinecoursesql = "SELECT lso.* FROM {local_sisonlinecourses} AS lso
		// 	JOIN {local_sisuserdata} AS lsu ON lso.courseid=lsu.courseid
		// 	WHERE lso.programid=:programid ";
		// print_object($programid);
		$onlinecoursesql = "SELECT lc.*
			FROM {local_courseenrolments} AS lc WHERE lc.programid=:programid AND lc.mdluserid = :userid";
		$onlinecourses = $this->db->get_records_sql($onlinecoursesql, array('programid' => $programid,'userid' => $userid));
		return $onlinecourses;
	}
	public function get_instructorrole_id(){
		return $this->db->get_field('role', 'id', array('shortname' => 'faculty'));
	}
}
<?php
defined('MOODLE_INTERNAL') || die();
function xmldb_local_courses_uninstall(){
	global $DB;
	$dbman = $DB->get_manager();
    $table = new xmldb_table('course');
	if ($dbman->table_exists($table)) {
		$sql = 'ALTER TABLE `mdl_course`
  			DROP `open_costcenterid`,DROP `open_departmentid`,DROP `open_identifiedas`,
  			DROP `open_points`,DROP `open_requestcourseid`,DROP `open_coursecreator`,
  			DROP `open_coursecompletiondays`,DROP `open_cost`,DROP `open_skill`,DROP `approvalreqd`';
  		$DB->execute($sql);
	}
}
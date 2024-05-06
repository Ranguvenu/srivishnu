<?php
defined('MOODLE_INTERNAL') || die();
function xmldb_local_users_uninstall(){
	global $DB;
	$dbman = $DB->get_manager();
    $table = new xmldb_table('user');
	if ($dbman->table_exists($table)) {
		// $dbman->deletefield('open_costcenterid');
		// $dbman->deletefield('open_supervisorid');
		// $dbman->deletefield('open_employeeid');
		// $dbman->deletefield('open_usermodified');
		// $dbman->deletefield('open_designation');
		// $dbman->deletefield('open_level');
		// $dbman->deletefield('open_state');
		// $dbman->deletefield('open_branch');
		// $dbman->deletefield('open_jobfunction');
		// $dbman->deletefield('open_band');
		// $dbman->deletefield('open_qualification');
		// $dbman->deletefield('open_subdepartment');
		// $dbman->deletefield('open_location');
		// $dbman->deletefield('open_supervisorempid');
		$sql = 'ALTER TABLE `mdl_user`
  			DROP `open_costcenterid`,DROP `open_departmentid`,DROP `open_supervisorid`,DROP `open_employeeid`,
  			DROP `open_usermodified`,DROP `open_designation`,DROP `open_level`,DROP `open_state`,
  			DROP `open_branch`,DROP `open_jobfunction`,DROP `open_group`,DROP `open_qualification`,
  			DROP `open_subdepartment`,DROP `open_location`,DROP `open_supervisorempid`,
  			DROP `open_band`,DROP `open_hrmsrole`,DROP `open_zone`,DROP `open_region`,
  			DROP `open_grade`,DROP `open_team`, DROP `open_role`, DROP `open_depid` ,DROP `open_client`,DROP `open_employee`, DROP `open_univdept_status` ;
  		$DB->execute($sql);
	}
}

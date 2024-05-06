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

/**************To Check The Manager By Ravi_369**************/

function is_organisation_head(){
	global $DB,$USER;
	$sql="select ra.*
	from {context} as cxt
	JOIN {role_assignments} as ra on ra.contextid=cxt.id
	JOIN {role} as r on r.id=ra.roleid
	WHERE cxt.contextlevel=10 and r.shortname='manager' and ra.userid=$USER->id";
	$organization=$DB->record_exists_sql($sql);
	if($organization){
	 $organizationhead = true;
	}else{
		$organizationhead = false;
	}
	return $organizationhead;
}

function get_mydepartments($withoutselect = 0) {
	global $DB, $USER;
    $context = context_system::instance();
    $select = array(null=>get_string('all'));
    $costcenters = array();
    if ( has_capability('local/costcenter:manage_multiorganizations', $context) ) {
		if ($withoutselect == 1) {
			$costcenters = $DB->get_records_sql_menu('select fullname,id from {local_costcenter} ');
		} else {
			$costcenters = $DB->get_records_sql_menu('select id,fullname from {local_costcenter} ');			
		}        
    } else if (has_capability('local/costcenter:manage_multidepartments', $context)) {		
		$costcenter = $DB->get_record_sql("SELECT cc.id, cc.parentid FROM {user} u JOIN {local_costcenter} cc ON u.open_costcenterid = cc.id WHERE u.id={$USER->id}");
        if ($costcenter->parentid != 0) {
            //echo 'one';
            if ($withoutselect == 1) {
				$costcenters = $DB->get_records_sql_menu('select fullname,id from {local_costcenter} where parentid='.$costcenter->parentid.' OR id = '.$costcenter->parentid.'');
            } else {
                $costcenters = $DB->get_records_select_menu('local_costcenter','parentid=?',array($costcenter->parentid),'id','id,fullname');
            }            
        } else {
            //echo 'two';
            if ($withoutselect == 1) {
				$costcenters = $DB->get_records_sql_menu('select fullname,id from {local_costcenter} where parentid='.$costcenter->id.' OR id = '.$costcenter->id.'');
			} else {
				$costcenters = $DB->get_records_select_menu('local_costcenter','parentid=?',array($costcenter->id),'id','id,fullname');
			}            
        }
	}  else {
		if ($withoutselect == 1)
		$costcenters = $DB->get_records_sql_menu("SELECT cc.fullname, cc.id FROM {user} u JOIN {local_costcenter} cc ON u.open_costcenterid = cc.id WHERE u.id={$USER->id}");
		else
		$costcenters = $DB->get_records_sql_menu("SELECT cc.id, cc.fullname  FROM {user} u JOIN {local_costcenter} cc ON u.open_costcenterid = cc.id WHERE u.id={$USER->id}");
	}
    if ($withoutselect == 0) {
        $list = array_replace($select, $costcenters );
    } elseif ($withoutselect == 1) {
        $list = implode(',', $costcenters);
    }
    return $list;	
}


function get_myparentdepartment() {
    global $DB, $USER;
    $costcenter = $DB->get_record_sql("SELECT cc.id, cc.parentid FROM {user} u JOIN {local_costcenter} cc ON u.open_costcenterid = cc.id WHERE u.id={$USER->id}");        
        // $result = $DB->get_records_select_menu($table,$select,$params,$sort,$fields);
    $parent = ($costcenter->parentid != 0) ? $costcenter->parentid : $costcenter->id;
    return $parent;
}

function get_mydepartment() {
    global $DB, $USER;
    $costcenter = $DB->get_field_sql("SELECT cc.id FROM {user} u JOIN {local_costcenter} cc ON u.open_costcenterid = cc.id WHERE u.id={$USER->id}");
    return $costcenter;
}

function get_filterslist() {
	$context = context_system::instance();
	if (is_siteadmin() OR has_capability('local/costcenter:manage_multiorganizations', $context )) {
		$filterlist = array('organizations', 'departments','idnumber', 'email', 'groups','users');
	}
	else if (has_capability('local/costcenter:manage_ownorganization',$context) ) {
		$filterlist = array('departments','idnumber', 'email', 'groups','users');
	} 
	else if (has_capability('local/costcenter:manage_owndepartments',$context) ) {
		$filterlist = array('idnumber', 'email', 'groups', 'users');
	} 
	else {
		$filterlist = array('idnumber', 'email','users');
	}
	return $filterlist;
}
//<revathi> issue 818 geting all users in mass enroll filters starts

function get_filterslist1() {
    $context = context_system::instance();
    if (is_siteadmin() OR has_capability('local/costcenter:manage_multiorganizations', $context )) {
        $filterlist = array('organizations', 'departments','idnumber', /*'email',*/ 'groups','departmentcourseusers1','departmentcourseusersemail1');
    }
    else if (has_capability('local/costcenter:manage_ownorganization',$context) ) {
        $filterlist = array('departments','idnumber', /*'email',*/ 'groups','departmentcourseusers1','departmentcourseusersemail1');
    } 
    else if (has_capability('local/costcenter:manage_owndepartments',$context) ) {
        $filterlist = array('idnumber', /*'email',*/ 'groups', 'departmentcourseusers1','departmentcourseusersemail1');
    } 
    else {
        $filterlist = array('idnumber', /*'email',*/'departmentcourseusers1','departmentcourseusersemail1');
    }
    return $filterlist;
}

//<revathi> issue 818 geting all users in mass enroll filters ends


function get_mooccoursesfilterslist() {
    $context = context_system::instance();
    if (is_siteadmin() OR has_capability('local/costcenter:manage_multiorganizations', $context )) {
        $filterlist = array('departments','idnumber', 'departmentcourseusersemail', 'departmentcourseusers','groups');
    }
    else if (has_capability('local/costcenter:manage_ownorganization',$context) ) {
        $filterlist = array('departments','idnumber', 'departmentcourseusersemail', 'departmentcourseusers','groups');
    } 
    else if (has_capability('local/costcenter:manage_owndepartments',$context) ) {
        $filterlist = array('idnumber', 'departmentcourseusersemail', 'departmentcourseusers','groups');
    } 
    else {
        $filterlist = array('idnumber', 'departmentcourseusersemail', 'departmentcourseusers','groups');
    }
    return $filterlist;
}// Added by Harish for enrolling users to Mooc courses //

function get_more_filters($existingfilters) {	
	$newlist = array(null=>get_string('select'), 'idnumber'=>'idnumber', 'username'=>'username','users'=>'users');
	$unique=array_unique( array_merge($existingfilters, $newlist) );
	$filterlist  = array_diff($unique, $existingfilters);
	return $filterlist;
}

class hierarchy {
    /*
     * @method get_school_parent
     * @param1  form object
     * @param2 Element position (string)
     * @param3 Schoolid(int)
     * @return Element value
     * */

    function get_school_parent($schools, $selected = array(), $inctop = true, $all = false) {
        $out = array();

        //if an integer has been sent, convert to an array
        if (!is_array($selected)) {
            $selected = ($selected) ? array(intval($selected)) : array();
        }
        if ($inctop) {
            $out[null] = '---Select---';
        }
        if ($all) {
            $out[0] = get_string('all');
        }
        if (is_array($schools)) {
            foreach ($schools as $parent) {
                // An item cannot be its own parent and cannot be moved inside itself or one of its own children
                // what we have in $selected is an array of the ids of the parent nodes of selected branches
                // so we must exclude these parents and all their children
                //add using same spacing style as the bulkitems->move available & selected multiselects
                foreach ($selected as $key => $selectedid) {
                    if (preg_match("@/$selectedid(/|$)@", $parent->path)) {
                        continue 2;
                    }
                }
                if ($parent->id != null) {
                    $out[$parent->id] = /* str_repeat('&nbsp;', 4 * ($parent->depth - 1)) . */ format_string($parent->fullname);
                }
            }
        }
        // print_object($out);
        return $out;
    }

    /**
     * @method get_school_items
     * @param boolean $fromschool used to indicate called from school plugin,using while error handling
     * @return list of schools
     * */
    function get_school_items($fromschool = NULL) {

        global $DB, $USER;
        $activeschoollist = $DB->get_records('local_school', array('visible' => 1), 'sortorder, fullname');
  
        if (empty($fromschool)) {
            if (empty($activeschoollist))
                throw new schoolnotfound_exception();
            // print_error('module');
        }
        if (is_siteadmin()) {
            $assigned_schools = $DB->get_records('local_school', array('visible' => 1), 'sortorder, fullname');
        } else {
            //  $sql="SELECT s.* FROM {local_school} s,{local_school_permissions} sp where s.id=sp.schoolid AND sp.userid={$USER->id} AND s.visible=1 ORDER BY sortorder ";
            $sql = " SELECT distinct(s.id),s.* FROM {local_school} s  where s.visible=1 AND id in(select schoolid from {local_school_permissions} where userid={$USER->id})  ORDER BY s.sortorder";
            $assigned_schools = $DB->get_records_sql($sql);
        }
       
        if (empty($fromschool)) {
            if (empty($assigned_schools)) {
                throw new notassignedschool_exception();
                // print_error('module');
            } else
                return $assigned_schools;
        } else
            return $assigned_schools;
    }

    function inttovancode($int = 0) {
        $num = base_convert((int) $int, 10, 36);
        $length = strlen($num);
        return chr($length + ord('0') - 1) . $num;
    }

    /**
     * Convert a vancode to an integer
     * @param string $char Vancode to convert. Must be <= '9zzzzzzzzzz'
     * @return integer The integer representation of the specified vancode
     */
    function vancodetoint($char = '00') {
        return base_convert(substr($char, 1), 36, 10);
    }

    /**
     * Increment a vancode by N (or decrement if negative)
     *
     */
    function increment_vancode($char, $inc = 1) {
        return $this->inttovancode($this->vancodetoint($char) + (int) $inc);
    }

    function increment_sortorder($sortorder, $inc = 1) {
        if (!$lastdot = strrpos($sortorder, '.')) {
            // root level, just increment the whole thing
            return $this->increment_vancode($sortorder, $inc);
        }
        $start = substr($sortorder, 0, $lastdot + 1);
        $last = substr($sortorder, $lastdot + 1);
        // increment the last vancode in the sequence
        return $start . $this->increment_vancode($last, $inc);
    }

    function get_next_child_sortthread($parentid, $table) {
        global $DB, $CFG;
        $maxthread_sql = "";
        $maxthread_sql .= "SELECT MAX(sortorder) AS sortorder FROM {$CFG->prefix}{$table} WHERE 1=1 ";

        if($table == 'local_costcenter'){
        	$maxthread_sql .= " AND parentid = ?";	
        }else{
        	$maxthread_sql .= " AND parent = ?";
        }
        $maxthread = $DB->get_record_sql($maxthread_sql, array($parentid));
        
        //  echo "the parentid".$parentid;
        if (!$maxthread || strlen($maxthread->sortorder) == 0) {
            if ($parentid == 0) {
                // first top level item
                return $this->inttovancode(1);
            } else {
                // parent has no children yet
                return $DB->get_field('local_school', 'sortorder', array('id' => $parentid)) . '.' . $this->inttovancode(1);
            }
        }
        return $this->increment_sortorder($maxthread->sortorder);
    }

    /* Getting the registrar roleid */

    public function get_registrar_roleid() {
        global $DB;

        if ($DB->record_exists('role', array('archetype' => 'manager'))) {
            $role = $DB->get_records('role', array('archetype' => 'manager'));
            return $role;
        }
    }

    /**
     * get_roleid function returns the role if of the role that you mention.
     * Please use "Instructor","Student","Registrar","Mentor" ...Please give same names.
     * returns value is the roleid
     * 
     */
    public function get_roleid($role) {
        global $DB;
        $sql = "SELECT * FROM {role} where archetype=\"{$role}\"";

        if ($DB->record_exists_sql($sql)) {
            $role = $DB->get_records_sql($sql);
            return $role;
        } else {

            return "This role not exist";
        }
    }

    /**
     * get_manager function is for listing all managers having manage collegestructure capability
     *
     * get_roles_with_capability is the function to get the roles defined for the given capability
     * returns the list of all the users having capability of "local/collegestructure:manage"
     * 
     */
    public function get_manager() {
        global $DB, $USER;
        $managerclevel = "local/collegestructure:manage";
        $mrole = get_roles_with_capability($managerclevel, $permission = NULL, $context = '');
        $users = array();
        $teacher = array();
        //print_object($mrole);
        foreach ($mrole as $manager) {
            $sql = "SELECT u.id,u.firstname,u.lastname FROM {role_assignments} ra,{user} u where ra.roleid={$manager->id} AND ra.userid=u.id";
            $users = $DB->get_records_sql($sql);
            foreach ($users as $user) {
                $teacher[$user->id] = $user->firstname . ' ' . $user->lastname;
            }
        }

        //print_object($teacher);
        //return $users;
        return $teacher;
    }

    public function is_instructor($user) {
        global $DB;
        $teacherlevel = "local/clclasses:submitgrades";
        //get all roles that having capability "local/clclasses:submitgrades"
        $roleid = get_roles_with_capability($teacherlevel, $permissions = NULL, $context = '');
        //get all roles that is having the archetype as the "editingteacher"
        $getroleid = $this->get_roleid("editingteacher");

        if (!empty($roleid)) {
            foreach ($roleid as $roleids) {
                //check if the current user is having a role that is define with the capability "local/clclasses:submitgrades"
                $teacherrole = $DB->record_exists('role_assignments', array('roleid' => $roleids->id, 'userid' => $user));
                // if exist then return true
                if ($teacherrole)
                    return true;
            }
        }
        if (!empty($getroleid)) {

            foreach ($getroleid as $roleides) {
                $editteacherole = $DB->record_exists('role_assignments', array('roleid' => $roleides->id, 'userid' => $user));

                $result = !empty($editteacherole) ? 1 : 0;
                return $result;
            }
        }
    }

    
    public function is_student($user) {
        global $DB, $USER, $CFG;
        $roles = $this->get_roleid('student');

        if (is_array($roles)) {
            foreach ($roles as $role)
                $studentroleid = $role->id;
            if ($studentroleid) {
                $studentrole = $DB->record_exists('role_assignments', array('roleid' => $studentroleid, 'userid' => $user));
            }
            if ($studentrole) {           
         
                if ($DB->record_exists('local_userdata', array('userid' => $user))) {
                   return $user;
                } else
                    throw new schoolnotfound_exception();
            }
        }
        else
        return 0;
    }

// end of function
    /**

     * is_authuser function returns the whether the authorized user for the capability mentioned 
     * $param-1: $managerclevel is the capability "local/collegestructure:manage" 
     *
     * get_roles_with_capability is the function to get the roles defined for the given capability
     * returns true if the user is a authorized user
     * 
     */
    public function is_authuser($managerclevel) {
        global $DB, $USER;

        $mrole = get_roles_with_capability($managerclevel, $permission = NULL, $context = '');

        foreach ($mrole as $manager) {
            $sql = "SELECT u.id,ra.roleid FROM {role_assignments} ra,{user} u where u.id={$USER->id} AND ra.roleid={$manager->id} AND ra.userid=u.id";

            if ($DB->record_exists_sql($sql)) {
                return true;
            }
        }
    }

    /* get_assignedschools get the assigned schools for a user. For this version it is the unique identity..
     * One user is assigned to a One school     *   
     * @method get_assignedschools
     * @todo Get the list of assigned schools of registrar
     * @return List of schools in the format of array
     * 
     */

    public function get_assignedschools() {
        global $DB, $CFG, $USER;
        $items = array();
        //$registrarrole = $this->get_registrar_roleid();
        //    if(is_siteadmin()){
        // $sql="SELECT distinct(s.id),s.* FROM {local_school} s ORDER BY s.sortorder";
        $activeschoollist = $DB->get_records('local_school', array('visible' => 1));
        if (empty($activeschoollist))
            throw new schoolnotfound_exception();

        $sql = "SELECT * FROM " . $CFG->prefix . "local_school_permissions WHERE userid = {$USER->id}";
        // / }
        // /  else {
        ///   $sql="SELECT distinct(s.id),s.* FROM {local_school} s  where s.usermodified={$USER->id} OR id in(selectschoolid schoolid from {local_school_permissions} sp/where sp.schoolid=s.id AND sp.userid={$USER->id}) ORDER BY s.sortorder //";
        //  }
        //echo $sql;
        $schools = $DB->get_records_sql($sql);
        if (empty($schools) && (!is_siteadmin()))
            throw new notassignedschool_exception();

        foreach ($schools as $school) {
            $items[$school->schoolid] = $DB->get_record('local_school', array('id' => $school->schoolid, 'visible' => 1));
        }
        if (!empty($items)) {
            foreach ($items as $item) {
                //check the school is allowed to access the child school
                $list = array();
                if ($item->childpermission) {
                    //get te child school upto only one level
                    $childs = $DB->get_records('local_school', array('parentid' => $item->id, 'visible' => 1));
                    foreach ($childs as $child) {
                        $list[$child->id] = $DB->get_record('local_school', array('id' => $child->id, 'visible' => 1));
                    }
                }
            }
            $items = $items+$list;
        }
        return $items;
    }

    /**
     * Get the first two columns from a number of records as an associative array which match a particular WHERE clause.
     *
     * Arguments are like {@link function get_records_select_menu}.
     * Return value is like {@link function get_records_menu}.
     *
     * @param string $table The database table to be checked against.
     * @param string $select A fragment of SQL to be used in a where clause in the SQL call.
     * @param array $params array of sql parameters
     * @param string $sort Sort order (optional) - a valid SQL order parameter
     * @param string $fields A comma separated list of fields to be returned from the chosen table - the number of fields should be 2!
     * @param string $default Default value for NULL key       
     * @param int $limitfrom return a subset of records, starting at this point (optional).
     * @param int $limitnum return a subset comprising this many records (optional, required if $limitfrom is set).
     * @param int $from based called scenrio (means from single select(1) or from moodle form(0) it going to change default key value o ,null  )
     * @return array an associative array
     * @throws dml_exception A DML specific exception is thrown for any errors.
     */
    
    public function get_records_cobaltselect_menu($table, $select, array $params = null, $sort = '', $fields = '*', $default = '', $limitfrom = 0, $limitnum = 0, $from = 0) {
        global $DB, $CFG;
        $result = $DB->get_records_select_menu($table, $select, $params, $sort, $fields, $limitfrom = 0, $limitnum = 0);
        if ($default != '' && !empty($result)) {
            if ($from)
                $result[0] = $default;
            else
                $result[NULL] = $default;
	    $result = array(null => $default) + $result;
        }
        return $result;
    }
    
    /*
     * @method get_programs
     * @todo Get all active programs
     * @return List of programs in the format of array
     * */

    public function get_programs() {
        global $DB, $CFG;
        $pro = array();
        $programs = $DB->get_records('local_program', array('visible' => 1));
        $pro[] = "---Select---";
        foreach ($programs as $program) {
            $pro[$program->id] = $program->fullname;
        }
        return $pro;
    }

    public function get_schools($top = false) {
        global $DB, $CFG;
        $scho = array();
        $schools = $DB->get_records('local_school', array('visible' => 1));
        if (!$top) {
            $scho[] = "---Select---";
        }
        foreach ($schools as $school) {
            $scho[$school->id] = $school->fullname;
        }
        return $scho;
    }

    public function get_myschools($top = false) {
        global $DB, $CFG, $USER;
        $scho = array();
        $sql = "SELECT s.id,s.fullname from {local_school} s ,{local_userdata} u where u.userid = ? AND u.schoolid=s.id AND visible=1";
        $values = array($USER->id);
        // $schools = $DB->get_records_sql($sql);
        if (!$top) {
            $scho[] = "---Select---";
        }
        $scho = $DB->get_records_sql_menu($sql, $values);
        return $scho;
    }

    /*
     * @method get_departments_forschool
     * @todo Get departments under particular school
     * @param1  schoolid(int)
     * @example get_departments_forschool(1);
     * @return List of departments in the format of array
     * */

    public function get_departments_forschool($schoolid, $none = false, $top = true, $concate_withschoolname = false) {
        global $DB;

        $departments = $DB->get_records('local_department', array('schoolid' => $schoolid, 'visible' => 1));
        $depts = $DB->get_records_sql("SELECT d.* FROM {local_department} d, {local_assignedschool_dept} sd WHERE d.id = sd.deptid AND sd.assigned_schoolid = $schoolid AND d.visible=1");

        $departments = $departments + $depts;

        //$out = array('Select Department');
        $out = array();
        $out [NULL] = "---Select---";

        foreach ($departments as $dept) {
            // Edited by hema----------------------------------------------
            $school = $DB->get_record('local_school', array('id' => $dept->schoolid));
            if ($concate_withschoolname)
                $deptname = format_string($dept->fullname . ' - ' . $school->fullname);
            else
                $deptname = format_string($dept->fullname);
            //-------------------------------------------------------------   
            $out[$dept->id] = $deptname;
        }
        return $out;
    }

    /*
     * @method get_program_curriculums
     * @todo Get curriculums under particular program
     *  @param1  programid(int)
     *  @return List of curriculums object
     * */

    // public function get_departments_forschool($programid){
    //   global $DB;
    //   $curriculums = $DB->get_records(' local_curriculum',array('programid'=>$programid));
    //     $curr=array();
    //     $curr[] ="Select Curriculum";
    //     foreach($curriculums as $curriculum){
    //       $curr[$curriculum->id] = format_string($curriculum->fullname);
    //     }
    //   return $curr;  
    //}
    /*
     * @method get_modules_curriculum
     * @todo Get modules under particular curriculum
     *  @param1  curriculumid(int)
     *  @return List of modules in the format of array
     * */
    public function get_modules_curriculum($curriculumid) {
        global $DB;
        $sql = "SELECT m.id,m.fullname FROM
                                    {$CFG->prefix}local_curriculum_modules cm,
                                    {$CFG->prefix}local_modules m WHERE
                                    cm.moduleid=m.id AND cm.curriculumid=$curriculumid";
        $modules = $DB->get_records_sql($sql);
        $mods = array();
        $mods[] = "---Select---";
        foreach ($modules as $module) {
            $mods[$module->id] = format_string($module->fullname);
        }
        return $mods;
    }

    /*
     * @method get_courses_module
     * @todo Get course under particular module
     * @param1  moduleid(int)
     * @return List of courses in the format of array
     * */

    public function get_courses_module($moduleid, $none = false) {
        global $DB, $CFG;
        $sql = "SELECT c.id,c.fullname FROM
                                    {$CFG->prefix}local_module_course mc,
                                    {$CFG->prefix}local_cobaltcourses c WHERE
                                    mc.courseid = c.id AND mc.moduleid = $moduleid AND c.visible=1";
        $courses = $DB->get_records_sql($sql);
        $crs = array();
        if (!$none) {
            $crs[0] = "---Select---";
        }
        foreach ($courses as $course) {
            $crs[$course->id] = format_string($course->fullname);
        }
        return $crs;
    }

    /*
     * @method get_actions
     * @todo Get action buttons for the data(like edit,delete,publish ..etc)
     *  @param1  Plugin name(string)
     *  @param plugin filename(string)
     *  @param ID(int)
     *  @param visible(int)
     *  @param currenttab(string)
     *  @return action buttons
     * */

    public function get_actions($pluginname, $plugin, $id, $visible, $currenttab = null, $schoolid = null) {
        global $CFG, $OUTPUT;
        $buttons = '';
        $systemcontext =context_system::instance();
        $delete_cap = array('local/' . $pluginname . ':manage', 'local/' . $pluginname . ':delete');
        if (has_any_capability($delete_cap, $systemcontext)) {
            $buttons = html_writer::link(new moodle_url('/local/' . $pluginname . '/' . $plugin . '.php', array('id' => $id, 'scid' => $schoolid, 'mode' => $currenttab, 'delete' => 1, 'sesskey' => sesskey())), html_writer::empty_tag('img', array('src' => $OUTPUT->pix_url('t/delete'), 'title' => get_string('delete'), 'alt' => get_string('delete'), 'class' => 'iconsmall')));
        }
        $edit_cap = array('local/' . $pluginname . ':manage', 'local/' . $pluginname . ':update');
        if (has_any_capability($edit_cap, $systemcontext)) {
            $buttons .= html_writer::link(new moodle_url('/local/' . $pluginname . '/' . $plugin . '.php', array('id' => $id, 'scid' => $schoolid, 'mode' => $currenttab, 'sesskey' => sesskey())), html_writer::empty_tag('img', array('src' => $OUTPUT->pix_url('t/edit'), 'title' => get_string('edit'), 'alt' => get_string('edit'), 'class' => 'iconsmall')));
        }
        $visible_cap = array('local/' . $pluginname . ':manage', 'local/' . $pluginname . ':visible');
        if (has_any_capability($visible_cap, $systemcontext)) {
            if ($visible > 0) {
                $buttons .= html_writer::link(new moodle_url('/local/' . $pluginname . '/' . $plugin . '.php', array('id' => $id, 'scid' => $schoolid, 'mode' => $currenttab, 'visible' => !$visible, 'sesskey' => sesskey())), html_writer::empty_tag('img', array('src' => $OUTPUT->pix_url('t/hide'), 'title' => get_string('inactive'), 'alt' => get_string('hide'), 'class' => 'iconsmall')));
            } else {
                $buttons .= html_writer::link(new moodle_url('/local/' . $pluginname . '/' . $plugin . '.php', array('id' => $id, 'scid' => $schoolid, 'mode' => $currenttab, 'visible' => !$visible, 'sesskey' => sesskey())), html_writer::empty_tag('img', array('src' => $OUTPUT->pix_url('t/show'), 'title' => get_string('active'), 'alt' => get_string('show'), 'class' => 'iconsmall')));
            }
        }
        return $buttons;
    }

    /*
     * @method get_school_programs
     * @todo Get programs under particular school
     *  @param  schoolid(int)
     *  @example get_school_programs(1);
     *  @return List of programs object
     * */

    public function get_school_programs($id, $top = false, $ptype = null) {
        global $DB;
        $out = array();
        $sql = "SELECT id,fullname from {local_program} where schoolid={$id} AND visible=1";
        if (!empty($ptype))
            $sql .=" AND programlevel={$ptype}";
        if (!$top) {
            $out[""] = "---Select---";
        }

        $schoollists = $DB->get_records_sql($sql);
        foreach ($schoollists as $schoollist) {
            $out[$schoollist->id] = $schoollist->fullname;
        }
        return $out;
    }

    /*
     * @method create_hierarchyelements
     * @todo To create new select elements with list of schools assigned to the registrar and programs belongs to that school
     *  @param1  form object
     *  @param2 First Element position (string)
     *  @param2 Second Element position (string)
     *  @return Element value
     * */

    public function create_hierarchyelements($mform, $place1, $place2) {
        global $hierarchy;
        $faculties = $hierarchy->get_assignedschools();
        $school = $hierarchy->get_school_parent($faculties);
        $newel = $mform->createElement('select', 'schoolid', get_string('schoolid', 'local_academiccalendar'), $school);
        $mform->insertElementBefore($newel, $place1);
        $mform->addRule('schoolid', get_string('missingschool', 'local_academiccalendar'), 'required', null, 'client');
        $school_value = $mform->getElementValue('schoolid');

        //Creating program element after getting the school value
        if (isset($school_value) && !empty($school_value)) {
            $school_id = $school_value[0];
            $programs = $hierarchy->get_records_cobaltselect_menu('local_program', 'schoolid={$id} AND visible=1', null, '', 'id,fullname', '--Select--');
            $newel2 = $mform->createElement('select', 'programid', get_string('selectprogram', 'local_academiccalendar'), $programs);
            $mform->insertElementBefore($newel2, $place2);
            $mform->addRule('programid', get_string('missingprogram', 'local_academiccalendar'), 'required', null, 'client');
            $program_value = $mform->getElementValue('programid');
            return $program_value;
        }
    }

    public function get_school_semesters($id) {
        global $DB;
        $today = date('Y-m-d');
        $out = array();
        /*
         * ###Bug report #173  -  Grade submission
         * @author Naveen Kumar<naveen@eabyas.in>
         * (Resolved) Retrieving only active semesters depends on present date
         */
        $sql = "SELECT ls.id,ls.fullname
                    FROM {local_school_semester} AS ss
                    JOIN {local_semester} AS ls

                    ON ss.semesterid=ls.id where ss.schoolid={$id} AND '{$today}' between DATE(FROM_UNIXTIME(ls.startdate)) AND DATE(FROM_UNIXTIME(ls.enddate)) AND ls.visible = 1 group by ls.id";
        $out[NULL] = "---Select---";
        $semesterlists = $DB->get_records_sql($sql);
        foreach ($semesterlists as $semesterlist) {

            $out[$semesterlist->id] = $semesterlist->fullname;
        }
        return $out;
    }

    /*
     * @method set_confirmation
     * @todo to show the confirmation message once the user performs any action
     * @param1 $message : confirmation message eg:school is successfully created.
     * @param2 $redirect: the page where you need to redirect the page
     * @param2 $options: the options for the messages to display
     * @return Element value
     * */

    function set_confirmation($message, $redirect = null, $options = array()) {

        // Check options is an array
        if (!is_array($options)) {
            print_error('error:confirmationparamtypewrong', 'local_collegestructure');
        }

        // Add message to options array
        $options['message'] = $message;

        // Add to confirmation queue
        $this->statement_concatinate('confirmation', $options);

        // Redirect if requested
        if ($redirect !== null) {
            redirect($redirect);
            exit();
        }
    }

    /**
     * Return an array containing any confirmation in $SESSION
     *
     * Should be called in the theme's header
     *
     * @return  array
     */
    public function get_confirmation() {
        return $this->statement_shift('confirmation', true);
    }

    public function statement_concatinate($key, $data) {
        global $SESSION;
        

        if (!isset($SESSION->eabyas_queue)) {
            $SESSION->eabyas_queue = array();
        }

        if (!isset($SESSION->eabyas_queue[$key])) {
            $SESSION->eabyas_queue[$key] = array();
        }

        $SESSION->eabyas_queue[$key][] = $data;
      
    }

    /**
     * Return part or all of a eabyas session queue
     *
     * @param   string  $key    Queue key
     * @param   boolean $all    Flag to return entire session queue (optional)
     * @return  mixed
     */
    function statement_shift($key, $all = false) {
        global $SESSION;

        // Value to return if no items in queue
        $return = $all ? array() : null;

        // Check if an items in queue
        if (empty($SESSION->eabyas_queue) || empty($SESSION->eabyas_queue[$key])) {
            return $return;
        }

        // If returning all, grab all and reset queue
        if ($all) {
            $return = $SESSION->eabyas_queue[$key];
            $SESSION->eabyas_queue[$key] = array();
            return $return;
        }
        return array_shift($SESSION->eabyas_queue[$key]);
    }

    /*
     * get_department_cobaltcourses is the function to get the list of cobaltcourses created under a department
     * Param-1: $departmentid is departmentid
     * return value: list of all the courses assigned
     */

    function get_department_cobaltcourses($departmentid) {
        global $DB;
        $out = array();
        $sql = "SELECT id,fullname from {local_cobaltcourses} where departmentid={$departmentid} AND visible=1";
        $out[""] = "---Select---";
        $cobaltlists = $DB->get_records_sql($sql);
        foreach ($cobaltlists as $cobaltlist) {
            $out[$cobaltlist->id] = $cobaltlist->fullname;
        }
        return $out;
    }

    function get_program_curriculum($programid, $scid) {
        global $DB;
        $out = array();
        $sql = "SELECT c.id,c.fullname from {local_curriculum} c where programid={$programid} AND schoolid={$scid} AND visible=1";
        $out[NULL] = "---Select---";
        $curriculumlists = $DB->get_records_sql($sql);
        foreach ($curriculumlists as $curriculumlist) {
            $out[$curriculumlist->id] = $curriculumlist->fullname;
        }
        return $out;
    }

    public function get_allregisters($schoolid) {
        global $DB, $CFG, $OUTPUT, $USER;
        $registrarrole = $this->get_registrar_roleid();
        //   print_object($registrarrole);
        $data = array();
        $i = 0;
        foreach ($registrarrole as $regrole) {
            $sql = "SELECT u.id,u.firstname,u.lastname FROM {local_school_permissions} sp,{user} u WHERE sp.schoolid = {$schoolid} AND roleid= {$regrole->id} AND sp.userid=u.id AND u.deleted = 0";
            $registrars = $DB->get_records_sql($sql);

            foreach ($registrars as $registrar) {
                if ($i !== 0) {
                    $data[] .=',';
                }
                // $data[] .= html_writer::link(new moodle_url('/local/user/profile.php', array('id' => $registrar->id, 'sesskey' => sesskey())), $registrar->firstname . '&nbsp' . $registrar->lastname);
                $data[] .=html_writer::tag('a', $registrar->firstname . '&nbsp' . $registrar->lastname, array('href' => '' . $CFG->wwwroot . '/local/users/profile.php?id=' . $registrar->id . ''));
                if ($USER->id != $registrar->id) {
                    $data[] .= html_writer::link(new moodle_url('/local/collegestructure/school.php', array('id' => $schoolid, 'userid' => $registrar->id, 'unassign' => 1, 'sesskey' => sesskey())), html_writer::empty_tag('img', array('src' => $OUTPUT->pix_url('t/delete'), 'alt' => get_string('unassign', 'local_collegestructure'), 'title' => get_string('unassign', 'local_collegestructure'), 'class' => 'iconsmall')));
                }
                $i++;
            }
        }

        $result = implode(' ', $data);
        //$result = implode(',', $data);
        return $result;
    }

    public function get_myschool_semesters() {
        global $DB, $USER;
        $out = array();
        $sql = "SELECT ss.id ,se.fullname
                    FROM {local_semester} AS se JOIN {local_school_semester} ss ON ss.semesterid=se.id JOIN {local_school_permissions} AS sp ON sp.schoolid=ss.schoolid WHERE sp.userid={$USER->id}";
        $out[NULL] = "---Select---";
        $semesterlists = $DB->get_records_sql($sql);
        foreach ($semesterlists as $semesterlist) {
            $out[$semesterlist->id] = $semesterlist->fullname;
        }
        return $out;
    }

    public function get_schoolOfsemesters($semester) {
        global $DB;

        $sql = "SELECT sp.id ,sp.fullname
                    FROM {local_school_semester} ss JOIN {local_school} AS sp ON sp.id=ss.schoolid WHERE ss.id={$semester}";
        $out = array();
        $schoolmaps = $DB->get_records_sql($sql);
        foreach ($schoolmaps as $school) {

            $out[$school->id] = $school->fullname;
        }
        return $out;
    }

    public function get_department_instructors($departmentid, $schoolid) {
        global $DB;

        $sql = "SELECT u.id ,u.firstname,u.lastname
                    FROM {local_dept_instructor} di JOIN {user} AS u ON u.id=di.instructorid WHERE di.departmentid={$departmentid} AND di.schoolid={$schoolid} AND u.suspended=0 AND u.deleted=0";


        //  $sql="select * FROM {user} where id>2";
        $out = array();

        $instructors = $DB->get_records_sql($sql);
        $out[NULL] = '---Select---';
        foreach ($instructors as $instructor) {

            $out[$instructor->id] = $instructor->firstname . '&nbsp;' . $instructor->lastname;
        }
        return $out;
    }

    public function get_onlinecourses() {
        global $DB;

        $sql = "SELECT * FROM {course} where visible=1 AND category>0";
        $out = array();
        $onlinecourses = $DB->get_records_sql($sql);
        $out[NULL] = '---Select---';
        foreach ($onlinecourses as $onlinecourse) {

            $out[$onlinecourse->id] = $onlinecourse->fullname;
        }
        return $out;
    }

    function get_alldepartments($userid) {
        global $DB;
        $out = array();
        if ($userid) {
            $sql = "SELECT s.fullname,s.id from {local_school} s ,{local_userdata} u where u.userid={$userid} AND u.schoolid=s.id AND visible=1";
            $schools = $DB->get_records_sql($sql);


            foreach ($schools as $school) {
                list($sql, $params) = $DB->get_in_or_equal($school->id);

                $coredepartment = $DB->get_records_sql("SELECT * FROM {local_department} WHERE schoolid $sql", $params);
                $department = $DB->get_records_sql("SELECT d.* FROM {local_department} d, {local_assignedschool_dept} sd WHERE d.id = sd.deptid AND sd.assigned_schoolid $sql GROUP BY sd.deptid", $params);
                $departments = $coredepartment + $department;
                foreach ($departments as $deptlist) {
                    $out[$deptlist->id] = format_string($deptlist->fullname);
                }
            }
        } else {
            $sql = "SELECT id,fullname from {local_department} where visible=1";

            $departmentlists = $DB->get_records_sql($sql);
            foreach ($departmentlists as $departmentlist) {
                $out[$departmentlist->id] = $departmentlist->fullname;
            }
        }
        return $out;
    }

    function get_allmyactivesemester($userid = NULL, $schoolid = NULL) {
        global $DB, $USER;
        $out = array();
        $today = date('Y-m-d');

        $sql = "SELECT s.fullname,s.id from {local_school} s ,{local_userdata} u where u.userid={$USER->id} AND u.schoolid=s.id AND visible=1";
        $schools = $DB->get_records_sql($sql);
        if ($userid) {
            foreach ($schools as $school) {

                $query = "SELECT e.semesterid as id,(select s.fullname from {local_semester} s,{local_school_semester} lss where s.id=e.semesterid AND s.id=lss.semesterid AND s.visible=1 AND lss.schoolid={$school->id}) as fullname FROM {local_event_activities} e";
                $query .=" WHERE e.eventtypeid IN(2,3) AND e.publish=1 AND '{$today}' BETWEEN from_unixtime( startdate,'%Y-%m-%d' ) AND from_unixtime( enddate,'%Y-%m-%d' )";

                $semesterlists = $DB->get_records_sql($query);

                foreach ($semesterlists as $semesterlist) {
                    if ($semesterlist->fullname != null) {

                        $out[$semesterlist->id] = $semesterlist->fullname;
                    }
                }
            }
        } else {
            $query = "SELECT e.semesterid as id,(select s.fullname from {local_semester} s where s.id=e.semesterid) as fullname FROM {local_event_activities} e";
            // Edited by hema
            if ($schoolid)
                $query.= " WHERE e.schoolid=$schoolid AND ";
            else
                $query.= " WHERE ";

            $query .="(e.eventtypeid=2 OR e.eventtypeid=3) AND'{$today}' BETWEEN from_unixtime( startdate,'%Y-%m-%d' ) AND from_unixtime( enddate,'%Y-%m-%d' )";

            $semesterlists = $DB->get_records_sql($query);

            foreach ($semesterlists as $semesterlist) {
                $out[$semesterlist->id] = $semesterlist->fullname;
            }
        }
        return $out;
    }

    function get_entitysetting($level, $schoolid) {
        global $DB;
        $sql = "SELECT * FROM {local_cobalt_entitylevels} where level='{$level}' AND schoolid={$schoolid}";
        $getsettings = $DB->get_records_sql($sql);

        return $getsettings;
    }

    function entity_settings($data) {
        global $DB;
        $setting = new stdClass();
        $setting->entityid = $data->entityid;
        $setting->level = $data->level;
        $setting->levelid = $data->id;
        $mincredithours = $data->mincredithours;
        $setting->schoolid = $data->schoolid;
        //   $setting->timecreated=$data->timecreated;
        $setting->timemodified = $data->timemodified;
        $setting->usermodified = $data->usermodified;
        //   $subentityid = $data->subentityid;
        if ($data->mincrhour) {

            $setting->entityid = $data->entityids;
            $setting->subentityid = $data->subentityidse;
            $setting->mincredithours = $data->mincrhour;
            $data->id = $DB->insert_record('local_level_settings ', $setting);
        }
        if (is_array($mincredithours)) {
            $i = 1;
            foreach ($mincredithours as $mincredit) {
                $setting->entityid = $data->entityid;
                $setting->mincredithours = $mincredit;
                $setting->subentityid = $i;
                // $level="CL";
                //  if(!$DB->record_exists('local_level_settings', array('schoolid'=>$setting->schoolid,'levelid'=>$data->id, 'level'=>$level)))
                $DB->insert_record('local_level_settings', $setting);
                $i++;
            }
        }
    }

    /* function count_course_requests_from_students($param)
     * todo count the no.of course requests from the users
     * $param $id this is the loggin id of the regitrar 
     */

    function count_course_requests_from_students($id) {
        global $DB, $USER;
        $query = "select * from {local_school_permissions} where userid = $id";
        $results = $DB->get_records_sql($query);
        $count_course_req = 0;
        foreach ($results as $reg) {
            $coursereq = $DB->get_records_sql("select luc.* from {local_user_clclasses} luc INNER JOIN {local_userdata} lud ON lud.userid = luc.userid where lud.schoolid = $reg->schoolid and luc.registrarapproval = 0");
            $count_course_req = $count_course_req + sizeof($coursereq);
        }
        if ($count_course_req != 0) {
            return $count_course_req;
        } else {
            return 0;
        }
    }

    /* function count_admissions_from_applicants($param)
     * todo count the no.of application(admissions) from the users
     * $param $id this is the loggin id of the regitrar 
     */

    function count_admissions_from_applicants($id) {
        global $DB, $USER;
        $query = "select * from {local_school_permissions} where userid = $id";
        $registrar = $DB->get_records_sql($query);
        $count_admission = 0;
        foreach ($registrar as $reg) {
            $admissions_count = $DB->get_records_sql("select * from {local_admission} where schoolid = $reg->schoolid and status = 0");
            $count_admission = $count_admission + sizeof($admissions_count);
        }
        if ($count_admission != 0) {
            return $count_admission;
        } else {
            return 0;
        }
    }

    /* function count_new_admission_req_from_student($param)
     * todo count the no.of new application(admisssion) requests from the student
     * $param($id) this is the loggin id of the registrar
     */

    function count_new_admission_req_from_student($id) {
        global $DB, $USER;
        $query = "select * from {local_school_permissions} where userid = $id";
        $registrar = $DB->get_records_sql($query);
        $count_admission = 0;
        foreach ($registrar as $reg) {
            $admissions_count = $DB->get_records_sql("select * from {local_admission} where schoolid = $reg->schoolid and status = 0 and typeofapplication = 1");
            $count_admission = $count_admission + sizeof($admissions_count);
        }
        if ($count_admission != 0) {
            return $count_admission;
        } else {
            return 0;
        }
    }

    /* function count_transfer_admission_req_from_student($param)
     * todo count the no.of transfer application(admisssion) requests from the student
     * $param($id) this is the loggin id of the registrar
     */

    function count_transfer_admission_req_from_student($id) {
        global $DB, $USER;
        $query = "select * from {local_school_permissions} where userid = $id";
        $registrar = $DB->get_records_sql($query);
        $count_admission = 0;
        foreach ($registrar as $reg) {
            $admissions_count = $DB->get_records_sql("select * from {local_admission} where schoolid = $reg->schoolid and status = 0 and typeofapplication = 2");
            $count_admission = $count_admission + sizeof($admissions_count);
        }
        if ($count_admission != 0) {
            return $count_admission;
        } else {
            return 0;
        }
    }

    /* function count_transcript_req_from_student($param)
     * todo count the no.of transcripts request from students
     * $param ($id) id of the loggin in user
     */

    function count_transcript_req_from_student($id) {
        global $DB, $USER;
        $requests = $DB->get_records('local_request_transcript', array('reg_approval' => '0'));
        $counttrans = 0;
        foreach ($requests as $request) {
            $list = array();
            $details = $DB->get_record_sql("select lud.schoolid,luc.semesterid,lud.userid,lud.serviceid,lud.programid
                                                    from {local_userdata} lud
                                                    INNER JOIN {local_user_clclasses} luc 
                                                    ON lud.userid = luc.userid AND lud.userid = {$request->studentid}
                                                    AND luc.semesterid = {$request->req_semester} group by lud.schoolid");
            $reqtrans = $DB->get_records_sql("SELECT * FROM {local_school_permissions} where userid = '" . $id . "' and schoolid='" . $details->schoolid . "'");
            if ($reqtrans) {
                $counttrans = $counttrans + sizeof($reqtrans);
            }
        }
        if ($counttrans == 0) {
            return 0;
        } else {
            return $counttrans;
        }
    }

    /* function count_courseexe_req_from_student($param)
     * todo count the no.of course exemption requests from students
     * $param ($id) id is the loggin registrar id
     */

    function count_coureexe_req_from_student($id) {
        global $DB;
        $others_school = $DB->get_records('local_school_permissions', array('userid' => $id));
        $countcoursx = 0;
        foreach ($others_school as $school) {
            $schoolid = $school->schoolid;
            $courreqforreg = $DB->get_records_sql("SELECT * FROM {local_request_courseexem} where registrarapproval = 0 and schoolid = $schoolid");
            $countcoursx = $countcoursx + sizeof($courreqforreg);
        }
        if ($countcoursx == 0) {
            return 0;
        } else {
            return $countcoursx;
        }
    }

    /* function count_idcard_req_from_student($param)
     * todo count the no.of ID card requests from students
     * $param ($id) id is the loggin registrar id
     */

    function count_idcard_req_from_student($id) {
        global $DB, $USER;
        $others_school = $DB->get_records('local_school_permissions', array('userid' => $id));
        $countids = 0;
        foreach ($others_school as $school) {
            $schoolid = $school->schoolid;
            $idreqforreg = $DB->get_records_sql("SELECT * FROM {local_request_idcard} where reg_approval = 0 and school_id = $schoolid");
            $countids = $countids + sizeof($idreqforreg);
        }
        if ($countids == 0) {
            return 0;
        } else {
            return $countids;
        }
    }

    /* function count_profilechange_req_from_student($param)
     * todo count the no.of Profile Change requests from students
     * $param ($id) id is the loggin registrar id
     */

    function count_profilechange_req_from_student($id) {
        global $DB, $USER;
        $others_school = $DB->get_records('local_school_permissions', array('userid' => $id));
        $countpros = 0;
        foreach ($others_school as $school) {
            $schoolid = $school->schoolid;
            $proreqforreg = $DB->get_records_sql("SELECT * FROM {local_request_profile_change} where reg_approval = 0 and schoolid = $schoolid");
            $countpros = $countpros + sizeof($proreqforreg);
        }
        if ($countpros == 0) {
            return 0;
        } else {
            return $countpros;
        }
    }

    /* function count_trasncripts_approve_from_registrar($param)
     * todo count the no.of transcript approvals from the last loggin date to current login date
     * $param ($id) loggin id of the student
     */

    function count_trasncripts_approve_from_registrar($id) {
        global $DB, $USER;
        $user = $DB->get_record('user', array('id' => $id));
        $transcriptapprov = $DB->get_records_sql("select * from {local_request_transcript} where studentid = $id and reg_approval = 1 and regapproval_date > $user->lastlogin");
        $count_approvals = sizeof($transcriptapprov);
        if ($count_approvals == 0) {
            return 0;
        } else {
            return $count_approvals;
        }
    }

    /* function count_courseexe_approve_from_registrar($param)
     * todo count the no.of courseexemptions approvals from the last loggin date to current login date
     * $param ($id) loggin id of the student
     */

    function count_courseexe_approve_from_registrar($id) {
        global $DB, $USER;
        $user = $DB->get_record('user', array('id' => $id));
        $courseexeapprov = $DB->get_records_sql("select * from {local_request_courseexem} where studentid = $id and registrarapproval = 1  and regapprovedon > $user->lastlogin");
        $count_approvals = sizeof($courseexeapprov);
        if ($count_approvals == 0) {
            return 0;
        } else {
            return $count_approvals;
        }
    }

    /* function count_idcard_approve_from_registrar($param)
     * todo count the no.of ID Card approvals from the last loggin date to current login date
     * $param ($id) loggin id of the student
     */

    function count_idcard_approve_from_registrar($id) {
        global $DB, $USER;
        $user = $DB->get_record('user', array('id' => $id));
        $courseexeapprov = $DB->get_records_sql("SELECT * FROM {local_request_idcard} where studentid = $id and reg_approval = 1 and regapproved_date > $user->lastlogin");

        $count_approvals = sizeof($courseexeapprov);
        if ($count_approvals == 0) {
            return 0;
        } else {

            return $count_approvals;
        }
    }

    /* function count_profilechange_approve_from_registrar($param)
     * todo count the no.of profile change approvals from the last loggin date to current login date
     * $param ($id) loggin id of the student
     */

    function count_profilechange_approve_from_registrar($id) {
        global $DB, $USER;
        $user = $DB->get_record('user', array('id' => $id));
        $courseexeapprov = $DB->get_records_sql("select * from {local_request_profile_change} where studentid = $id and reg_approval = 1 and regapproval_date > $user->lastlogin");
        $count_approvals = sizeof($courseexeapprov);
        if ($count_approvals == 0) {
            return 0;
        } else {
            return $count_approvals;
        }
    }

    function get_upcoming_school_semesters($id) {
        global $DB;
        $today = date('Y-m-d');
        $out = array();

        $sql = "SELECT ls.id,ls.fullname
                    FROM {local_school_semester} AS ss
                    JOIN {local_semester} AS ls

                    ON ss.semesterid=ls.id where ss.schoolid={$id} AND '{$today}' < DATE(FROM_UNIXTIME(ls.enddate)) AND ls.visible = 1 group by ls.id";

        $out[NULL] = "---Select---";
        $semesterlists = $DB->get_records_sql($sql);
        foreach ($semesterlists as $semesterlist) {

            $out[$semesterlist->id] = $semesterlist->fullname;
        }
        return $out;
    }

    function cobalt_navigation_msg($errormsg, $linkname, $linkurl, $styleattributes = '') {
        global $USER, $DB, $CFG;
        // echo $styleattributes;
        $content = $errormsg . html_writer::tag('a', $linkname, array('href' => $linkurl, 'target'=>'_blank', 'style' => 'color:#0EABB7!important;'));
        $output = html_writer::tag('div', $content, array('style' => 'font-weight:bold;' . $styleattributes . ''));
        return $output;
    }

}

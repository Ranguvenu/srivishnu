<?php
/**
 * This file is part of eAbyas
 *
 * Copyright eAbyas Info Solutons Pvt Ltd, India
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Maheshchandra  <maheshchandra@eabyas.in>
 */
/**
 * Assign roles to users.
 * @package    local
 * @subpackage assignroles
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once(__DIR__ . '/../../config.php');
/**
 * Function to display the assign role form in popup
 * returns data of the popup 
 */
function local_get_assignable_roles(context $context, $rolenamedisplay = ROLENAME_ALIAS, $withusercounts = false, $user = null,$costcenterid=0) {
    global $USER, $DB;
    // $systemcontext = context_system::instance();
    // make sure there is a real user specified
    $systemcontext = context_system::instance();

    if ($user === null) {
        $userid = isset($USER->id) ? $USER->id : 0;
    } else {
        $userid = is_object($user) ? $user->id : $user;
    }

    if (!has_capability('moodle/role:assign', $context, $userid)) {
        if ($withusercounts) {
            return array(array(), array(), array());
        } else {
            return array();
        }
    }

    $params = array();
    $extrafields = '';

    if ($withusercounts) {
        $extrafields = ", (SELECT count(u.id)
                             FROM {role_assignments} cra JOIN {user} u ON cra.userid = u.id
                            WHERE cra.roleid = r.id AND cra.contextid = :conid AND u.deleted = 0";
        if((!is_siteadmin() && !has_capability('local/costcenter:manage_multiorganizations', $systemcontext)) && has_capability('local/costcenter:manage_ownorganization',$systemcontext)){
            $extrafields .= " AND u.open_costcenterid = :open_costcenterid ";
        }
        $extrafields .= " ) AS usercount";
        $params['conid'] = $context->id;
        $params['open_costcenterid'] = $USER->open_costcenterid;
        $params['open_departmentid'] = $USER->open_departmentid;
    }

    if (is_siteadmin($userid)  || has_capability('local/costcenter:manage_multiorganizations', $context, $userid) || has_capability('local/costcenter:manage_ownorganization',$systemcontext) || has_capability('local/costcenter:manage_owndepartments',$systemcontext)) {
        // show all roles allowed in this context to admins
        $assignrestriction = "";
    } else {  
        $parents = $context->get_parent_context_ids(true);
        $contexts = implode(',' , $parents);
        $assignrestriction = "JOIN (SELECT DISTINCT raa.allowassign AS id
                                      FROM {role_allow_assign} raa
                                      JOIN {role_assignments} ra ON ra.roleid = raa.roleid
                                      JOIN {user} u ON u.id = ra.userid
                                     WHERE ra.userid = :userid AND ra.contextid IN ($contexts)";
          
        $assignrestriction .=  ") ar ON ar.id = r.id";
        
        $params['userid'] = $userid;

    }
    $params['contextlevel'] = $context->contextlevel;

    if ($coursecontext = $context->get_course_context(false)) {
        $params['coursecontext'] = $coursecontext->id;
    } else {
        $params['coursecontext'] = 0; // no course aliases
        $coursecontext = null;
    }
    $sql = "SELECT r.id, r.name, r.shortname, rn.name AS coursealias $extrafields
              FROM {role} r
              $assignrestriction 
              JOIN {role_context_levels} rcl ON (rcl.contextlevel = :contextlevel AND r.id = rcl.roleid)
         LEFT JOIN {role_names} rn ON (rn.contextid = :coursecontext AND rn.roleid = r.id) 
             WHERE r.shortname != 'collegeadmin' AND r.shortname !='student' AND r.shortname != 'principal'";
    //addedby swathi to hide the oh role for oh
    /*if((!is_siteadmin() && has_capability('local/costcenter:manage_ownorganization',$systemcontext))){
        $sql .= " WHERE r.shortname != 'university_head' ";
     }else*/if((!is_siteadmin() && has_capability('local/costcenter:manage_owndepartments', $systemcontext))){
        $sql .= " AND r.shortname = 'faculty'";
     }
        $sql .= " ORDER BY r.sortorder ASC";
        // echo $sql;
    $roles = $DB->get_records_sql($sql, $params);
    $rolenames = role_fix_names($roles, $coursecontext, $rolenamedisplay, true);

    if (!$withusercounts) {
        return $rolenames;
    }

    $rolecounts = array();
    $nameswithcounts = array();
    foreach ($roles as $role) {
        $nameswithcounts[$role->id] = $rolenames[$role->id] . ' (' . $roles[$role->id]->usercount . ')';
        $rolecounts[$role->id] = $roles[$role->id]->usercount;
    }
    return array($rolenames, $rolecounts, $nameswithcounts);
}

function local_get_assignable_roles_admins(context $context, $rolenamedisplay = ROLENAME_ALIAS, $withusercounts = false, $user = null,$costcenterid=0) {
    global $USER, $DB;
    // $systemcontext = context_system::instance();
    // make sure there is a real user specified
    $systemcontext = context_system::instance();

    if ($user === null) {
        $userid = isset($USER->id) ? $USER->id : 0;
    } else {
        $userid = is_object($user) ? $user->id : $user;
    }

    if (!has_capability('moodle/role:assign', $context, $userid)) {
        if ($withusercounts) {
            return array(array(), array(), array());
        } else {
            return array();
        }
    }

    $params = array();
    $extrafields = '';

    if ($withusercounts) {
        $extrafields = ", (SELECT count(u.id)
                             FROM {role_assignments} cra JOIN {user} u ON cra.userid = u.id
                            WHERE cra.roleid = r.id AND cra.contextid = :conid AND u.deleted = 0";
        if((!is_siteadmin() && !has_capability('local/costcenter:manage_multiorganizations', $systemcontext)) && has_capability('local/costcenter:manage_ownorganization',$systemcontext)){
            $extrafields .= " AND u.open_costcenterid = :open_costcenterid ";
        }
        $extrafields .= " ) AS usercount";
        $params['conid'] = $context->id;
        $params['open_costcenterid'] = $USER->open_costcenterid;
        $params['open_departmentid'] = $USER->open_departmentid;
    }

    if (is_siteadmin($userid)  || has_capability('local/costcenter:manage_multiorganizations', $context, $userid) || has_capability('local/costcenter:manage_ownorganization',$systemcontext) || has_capability('local/costcenter:manage_owndepartments',$systemcontext)) {
        // show all roles allowed in this context to admins
        $assignrestriction = "";
    } else {  
        $parents = $context->get_parent_context_ids(true);
        $contexts = implode(',' , $parents);
        $assignrestriction = "JOIN (SELECT DISTINCT raa.allowassign AS id
                                      FROM {role_allow_assign} raa
                                      JOIN {role_assignments} ra ON ra.roleid = raa.roleid
                                      JOIN {user} u ON u.id = ra.userid
                                     WHERE ra.userid = :userid AND ra.contextid IN ($contexts)";
          
        $assignrestriction .=  ") ar ON ar.id = r.id";
        
        $params['userid'] = $userid;

    }
    $params['contextlevel'] = $context->contextlevel;

    if ($coursecontext = $context->get_course_context(false)) {
        $params['coursecontext'] = $coursecontext->id;
    } else {
        $params['coursecontext'] = 0; // no course aliases
        $coursecontext = null;
    }
    $sql = "SELECT r.id, r.name, r.shortname, rn.name AS coursealias $extrafields
              FROM {role} r
              $assignrestriction 
              JOIN {role_context_levels} rcl ON (rcl.contextlevel = :contextlevel AND r.id = rcl.roleid)
         LEFT JOIN {role_names} rn ON (rn.contextid = :coursecontext AND rn.roleid = r.id) 
             WHERE r.shortname != 'college_head' AND r.shortname !='student' /*AND r.shortname != 'principal'*/
             AND r.shortname != 'faculty' AND r.shortname != 'departmentadmin' AND r.shortname != 'hod'";
    //addedby swathi to hide the oh role for oh
    /*if((!is_siteadmin() && has_capability('local/costcenter:manage_ownorganization',$systemcontext))){
        $sql .= " WHERE r.shortname != 'university_head' ";
     }else*/if((!is_siteadmin() && has_capability('local/costcenter:manage_owndepartments', $systemcontext))){
        $sql .= " AND r.shortname = 'faculty'";
     }
        $sql .= " ORDER BY r.sortorder ASC";
        // echo $sql;
    $roles = $DB->get_records_sql($sql, $params);
    $rolenames = role_fix_names($roles, $coursecontext, $rolenamedisplay, true);

    if (!$withusercounts) {
        return $rolenames;
    }

    $rolecounts = array();
    $nameswithcounts = array();
    foreach ($roles as $role) {
        $nameswithcounts[$role->id] = $rolenames[$role->id] . ' (' . $roles[$role->id]->usercount . ')';
        $rolecounts[$role->id] = $roles[$role->id]->usercount;
    }
    return array($rolenames, $rolecounts, $nameswithcounts);
}







function local_assignroles_output_fragment_new_assignrole($args) {
    global $CFG, $DB;

    $args = (object) $args;
    $context = $args->context;
    $roleid = $args->roleid;
    $o = '';
    $formdata = [];
    if (!empty($args->jsonformdata)) {
        $serialiseddata = json_decode($args->jsonformdata);
        parse_str($serialiseddata, $formdata);
    }
    $mform = new local_assignroles\form\assignrole(null, array('editoroptions' => $editoroptions,'roleid'=>$roleid), 'post', '', null, true, $formdata);
    $mform->set_data($data);
    if (!empty($formdata)) {
        // If we were passed non-empty form data we want the mform to call validation functions and show errors.
        $mform->is_validated();
    }
    ob_start();
    $mform->display();
    $o .= ob_get_contents();
    ob_end_clean();
    return $o;
}
/**
 * Function to display the role users in popup
 * returns data of the popup 
 */
function local_assignroles_output_fragment_roleusers_display($args){
    global $DB,$CFG,$PAGE,$OUTPUT,$USER;

    $args = (object) $args;
    $context = $args->context;
    $roleid = $args->roleid;
    $rolename = $DB->get_field('role', 'name', array('id' => $roleid));
    $systemcontext = context_system::instance();
    if(is_siteadmin() || has_capability('local/costcenter:manage_multiorganizations', $systemcontext)){
        $sql = "SELECT * FROM {role_assignments} WHERE roleid = $roleid AND contextid = $context->id";
         $sql .= " order by id desc";
         $users = $DB->get_records_sql($sql);         
    }else if(has_capability('local/costcenter:manage_ownorganization',$systemcontext)){
        $sql = "SELECT ra.* FROM {role_assignments} AS ra JOIN {user} AS u on u.id=ra.userid 
            WHERE ra.roleid=:roleid AND ra.contextid=:contextid AND u.open_costcenterid=:costcenter";
        $sql .= " order by ra.id desc";
        $users= $DB->get_records_sql($sql, array('roleid' => $roleid, 'contextid' => $context->id, 'costcenter' => $USER->open_costcenterid));
    }else{
        $sql = "SELECT ra.* FROM {role_assignments} AS ra JOIN {user} AS u on u.id=ra.userid 
            WHERE ra.roleid=:roleid AND ra.contextid=:contextid AND u.open_costcenterid=:costcenter AND u.open_departmentid=:department";
        $sql .= " order by ra.id desc";
        $users= $DB->get_records_sql($sql, array('roleid' => $roleid, 'contextid' => $context->id, 'costcenter' => $USER->open_costcenterid, 'department' => $USER->open_departmentid));  
    }
    $templatedata = array();
    $templatedata['roleid'] = $roleid;
    $templatedata['rolename'] = $rolename;
    if($users){
        $templatedata['enabletable'] = true;
        foreach($users as $user){
            $rowdata = array();
            $user_data_sql = "SELECT u.id,u.firstname,u.lastname,u.email,u.open_employeeid,lc.fullname FROM {user} AS u JOIN {local_costcenter} AS lc ON lc.id=u.open_costcenterid WHERE u.id = :id";
            $userdata = $DB->get_record_sql($user_data_sql,array('id' => $user->userid));
            $fullname = $userdata->firstname.' '.$userdata->lastname;
            $rowdata['fullname'] = $fullname;
            $rowdata['employeeid'] = $userdata->open_employeeid;
            $rowdata['email'] = $userdata->email;
            $rowdata['orgname'] = $userdata->fullname;
            $rowdata['userid'] = $user->userid;
            $rowdata['username'] = $fullname;
            //AM added
            if($rolename == 'University Head'){
                $rowdata['canassign'] = false;
            }else{
                $rowdata['canassign'] = true;
            }
             //AM ends
            $templatedata['rowdata'][] = $rowdata;
        }
    }else{
        $templatedata['enabletable'] = false;
    }
    $output .= $OUTPUT->render_from_template('local_assignroles/popupcontent', $templatedata);
    
    return $output;
}
/*
* Author Rizwana
* Displays a node in left side menu
* @return  [type] string  link for the leftmenu
*/
function local_assignroles_leftmenunode(){
    $systemcontext = context_system::instance();
    $assignrolesnode = '';
    if(has_capability('local/costcenter:manage_multiorganizations',$systemcontext) || is_siteadmin() || has_capability('local/costcenter:manage_ownorganization', $systemcontext)) {
        $assignrolesnode .= html_writer::start_tag('li', array('id'=> 'id_leftmenu_assign_roles', 'class'=>'pull-left user_nav_div assign_roles dropdown-item'));
            $users_url = new moodle_url('/local/assignroles/index.php');
            $users = html_writer::link($users_url, '<i class="fa fa-user-circle" aria-hidden="true"></i><span class="user_navigation_link_text">'.get_string('pluginname', 'local_assignroles').'</span>',array('class'=>'user_navigation_link'));
            $assignrolesnode .= $users;
        $assignrolesnode .= html_writer::end_tag('li');
    }
    return array('7' => $assignrolesnode);
}
function find_users($costcenter,$roleid){
    global $DB,$USER;
    if($costcenter && $roleid) {     
                    $roleshortname = $DB->get_field('role','shortname',array('id' => $roleid));
                    $systemcontext = \context_system::instance();
                    $userssql =  "SELECT u.id, concat(u.firstname,' ',u.lastname) as fullname 
                        FROM {user} AS u 
                        WHERE u.id > 2 AND u.deleted = 0 AND u.suspended = 0 AND u.id <> ".$USER->id." AND u.id NOT IN (SELECT userid FROM {role_assignments} WHERE contextid=$systemcontext->id AND roleid = $roleid) AND u.open_costcenterid=".$costcenter;
                        if($roleshortname != 'university_head'){
                           $userssql .= " AND open_employee != 3 "; 
                        }
                   
                    $userslist = $DB->get_records_sql($userssql);
                 
            return $costcenter =  $userslist;
        }else {
            return $costcenter;
        }
}

function unassignuser_parentrole($roleid, $userid){
    global $DB,$USER;
    $parentroleid = $DB->get_field('user', 'open_role', array('id' => $userid));
    if($parentroleid == $roleid){
        $DB->execute("UPDATE {user} SET open_role = NULL WHERE id = $userid");
    }
    return true;
}

function assignuser_parentrole($roleid, $userid){
    global $DB,$USER;
    $parentroleid = $DB->get_field('user', 'open_role', array('id' => $userid));
    if(empty($parentroleid)){
        $DB->execute("UPDATE {user} SET open_role = $roleid WHERE id = $userid");
    }
    return true;
}

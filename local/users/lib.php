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
 * local_classroom LIB
 *
 * @package    local_user
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
use local_users\output\team_status_lib;

defined('MOODLE_INTERNAL') || die;
require_once($CFG->libdir . '/formslib.php');
/**
 * Description: To display the form in modal on modal trigger event.
 * @param  [array] $args [the parameters required for the form]
 * @return        [modal content]
 */
function local_users_output_fragment_new_create_user($args){
 global $CFG,$DB, $PAGE;
    // print_r($args);
    // exit;
    $args = (object) $args;
    $context = $args->context;
    $o = '';
    $formdata = [];
    if (!empty($args->jsonformdata)) {
        $serialiseddata = json_decode($args->jsonformdata);
        parse_str($serialiseddata, $formdata);
    }
    // print_r($args->jsonformdata);
    // exit;
    $editoroptions = [
        'maxfiles' => EDITOR_UNLIMITED_FILES,
        'maxbytes' => $course->maxbytes,
        'trust' => false,
        'context' => $context,
        'noclean' => true,
        'subdirs' => false
    ];
    $group = file_prepare_standard_editor($group, 'description', $editoroptions, $context, 'group', 'description', null);
    if ($args->id > 0) {
        $heading = 'Update User';
        $collapse = false;
        $data = $DB->get_record('user', array('id'=>$args->id));
       // print_r($data);
        // exit;
        if($data->open_univdept_status == 1){
            $data->open_collegeid = $data->open_departmentid;
            $collegeid = $data->open_departmentid;
        }elseif($data->open_univdept_status == 0){
            $data->departmentid = $data->open_departmentid;
            $departmentid = $data->open_departmentid;
        }
        unset($data->password);
        $mform = new local_users\forms\create_user(null, array('editoroptions' => $editoroptions,'form_status' => $args->form_status,'id' => $data->id,'org'=>$data->open_costcenterid, 'open_departmentid' => $departmentid, 'open_collegeid' => $collegeid, 'subdept'=>$data->open_subdepartment, 'open_univdept_status' => $data->open_univdept_status,'open_role' => $data->open_role,'employee' => $args->employee), 'post', '', null, true, $formdata);
        $mform->set_data($data);
    }
    else{
        $mform = new local_users\forms\create_user(null, array('editoroptions' => $editoroptions,'form_status' => $args->form_status,'employee'=>$args->employee), 'post', '', null, true, $formdata);
    }

    if (!empty($args->jsonformdata) && strlen($args->jsonformdata) > 2) {
        // If we were passed non-empty form data we want the mform to call validation functions and show errors.
        $mform->is_validated();
    }
    $formheaders = array_keys($mform->formstatus);
    $nextform = array_key_exists($args->form_status, $formheaders);
    if ($nextform === false) {
        return false;
    }
    $renderer = $PAGE->get_renderer('local_users');
    ob_start();
    // $formstatus = array();
    // foreach (array_values($mform->formstatus) as $k => $mformstatus) {
    //     $activeclass = $k == $args->form_status ? 'active' : '';
    //     $formstatus[] = array('name' => $mformstatus, 'activeclass' => $activeclass);
    // }
    // $formstatusview = new \local_users\output\form_status($formstatus);
    // $o .= $renderer->render($formstatusview);
    $mform->display();
    $o .= ob_get_contents();
    ob_end_clean();
    return $o;
}

function local_users_output_fragment_new_profile_info($args){
 global $CFG,$DB, $PAGE;

    $args = (object) $args;
    $context = $args->context;
    $o = '';
    $formdata = [];
    if (!empty($args->jsonformdata)) {
        $serialiseddata = json_decode($args->jsonformdata);
        parse_str($serialiseddata, $formdata);
    }

    $editoroptions = [
        'maxfiles' => EDITOR_UNLIMITED_FILES,
        'maxbytes' => $course->maxbytes,
        'trust' => false,
        'context' => $context,
        'noclean' => true,
        'subdirs' => false
    ];
    $group = file_prepare_standard_editor($group, 'description', $editoroptions, $context, 'group', 'description', null);
    if ($args->id > 0) {
        $heading = 'Update User';
        $collapse = false;
        $data = $DB->get_record('user', array('id'=>$args->id));
        unset($data->password);
        $mform = new local_users\forms\profile_info(null, array('editoroptions' => $editoroptions,'id' => $data->id), 'post', '', null, true, $formdata);
        $mform->set_data($data);
    }
    else{
        $mform = new local_users\forms\profile_info(null, array('editoroptions' => $editoroptions), 'post', '', null, true, $formdata);
    }

    if (!empty($args->jsonformdata) && strlen($args->jsonformdata) >2) {
        // If we were passed non-empty form data we want the mform to call validation functions and show errors.
        $mform->is_validated();
    }
    
    $renderer = $PAGE->get_renderer('local_users');
    ob_start();
    $mform->display();
    $o .= ob_get_contents();
    ob_end_clean();
    return $o;
}
/**
 * Description: User fullname filter code
 * @param  [mform object]  $mform          [the form object where the form is initiated]
 * @param  string  $query          [text inserted in filter]
 * @param  boolean $searchanywhere [description]
 * @param  integer $page           [page value]
 * @param  integer $perpage        [entities per page]
 */
function users_filter($mform,$query='',$searchanywhere=false, $page=0, $perpage=25, $costcenter = null, $roleid = null){
    global $DB, $USER;

    $systemcontext = context_system::instance();
    $userslist=array();
    $data=data_submitted();

    if(is_siteadmin() || has_capability('local/costcenter:manage_multiorganizations', $systemcontext)){
        $userslist_sql="SELECT id, concat(firstname,' ',lastname) as fullname FROM {user} WHERE id > 2 AND deleted = 0 AND suspended = 0 AND id <> $USER->id ";

    }else if(has_capability('local/costcenter:manage_ownorganization', $systemcontext)){
        $userslist_sql="SELECT id, concat(firstname,' ',lastname) as fullname FROM {user} WHERE id > 2 AND open_costcenterid = $USER->open_costcenterid AND deleted = 0 AND suspended = 0 AND id <> $USER->id ";

    }else if(has_capability('local/costcenter:manage_owndepartments', $systemcontext)){
        $userslist_sql="SELECT id, concat(firstname,' ',lastname) as fullname FROM {user} WHERE id > 2 AND open_costcenterid = $USER->open_costcenterid AND open_departmentid = $USER->open_departmentid AND deleted = 0 AND suspended = 0 AND id <> $USER->id";
    }
    if(!empty($query)){
        if ($searchanywhere) {
            $userslist_sql.=" AND CONCAT(firstname, ' ',lastname) LIKE '%$query%' ";
        } else {
           $userslist_sql.=" AND CONCAT(firstname, ' ',lastname) LIKE '$query%' ";
        }
    }
    if(isset($data->users)&&!empty(($data->users))&&is_array($data->users)){

        $implode=implode(',',$data->users);

        $userslist_sql.=" AND id in ($implode)";
    }
    if(!empty($costcenter)){
        // $studentroleid = $DB->get_field('role', 'id', array('shortname' => 'student'));
        $userslist_sql.=" AND open_costcenterid = $costcenter";
        if($roleid){
            $userslist_sql.=" AND open_role = $roleid";
        }
    }// Added by Harish for fetching within university users in mooc course enrollments functionality
    $userslist_sql.="  LIMIT $page, $perpage";
    if(!empty($query)||empty($mform)){
        $userslist = $DB->get_records_sql($userslist_sql);
        return $userslist;
    }
    if(!empty($costcenter) && !empty($roleid)){
        $userslist = $DB->get_records_sql_menu($userslist_sql);
    }// Added by Harish for fetching within university users in mooc course enrollments functionality
    if((isset($data->users)&&!empty($data->users))){
         $userslist = $DB->get_records_sql_menu($userslist_sql);
    }

    $options = array(
                    'ajax' => 'local_courses/form-options-selector',
                    'multiple' => true,
                    'data-action' => 'users',
                    'data-options' => json_encode(array('id' => 0, 'costcenter' => $costcenter, 'roleid' => $roleid)),
                    'placeholder' => get_string('users')
    );
    $select = $mform->addElement('autocomplete', 'users', '',$userslist,$options);
    $mform->setType('users', PARAM_RAW);
}
/**
 * Description: User email filter code
 * @param  [mform object]  $mform          [the form object where the form is initiated]
 * @param  string  $query          [text inserted in filter]
 * @param  boolean $searchanywhere [description]
 * @param  integer $page           [page value]
 * @param  integer $perpage        [entities per page]
 */
function email_filter($mform,$query='',$searchanywhere=false, $page=0, $perpage=25, $costcenter = null, $roleid = null){
    global $DB, $USER;
    $systemcontext = context_system::instance();
    $userslist=array();
    $data=data_submitted();

    if(is_siteadmin() || has_capability('local/costcenter:manage_multiorganizations', $systemcontext)){
        $userslist_sql="SELECT id, email as fullname FROM {user} WHERE id > 2 AND deleted = 0 AND suspended = 0 AND id <> $USER->id ";
    }else if(has_capability('local/costcenter:manage_ownorganization', $systemcontext)){
        $userslist_sql="SELECT id, email as fullname FROM {user} WHERE id > 2 AND open_costcenterid = $USER->open_costcenterid AND deleted = 0 AND suspended = 0 AND id <> $USER->id ";
    }else if(has_capability('local/costcenter:manage_owndepartments', $systemcontext)){
        $userslist_sql="SELECT id, email as fullname FROM {user} WHERE id > 2 AND open_costcenterid = $USER->open_costcenterid AND open_departmentid = $USER->open_departmentid AND deleted = 0 AND suspended = 0 AND id <> $USER->id ";
    }
    if(!empty($query)){
        if ($searchanywhere) {
          $userslist_sql.=" AND email LIKE '%$query%' ";
        } else {
         $userslist_sql.=" AND email LIKE '$query%' ";
        }
    }
    if(isset($data->email)&&!empty(($data->email))&&is_array($data->email)){

        $implode=implode(',',$data->email);

        $userslist_sql.=" AND id in ($implode)";
    }
    if(!empty($costcenter)){
        // $studentroleid = $DB->get_field('role', 'id', array('shortname' => 'student'));
        $userslist_sql.=" AND open_costcenterid = $costcenter";
        if($roleid){
            $userslist_sql.=" AND open_role = $roleid";
        }
    }// Added by Harish for fetching within university users in mooc course enrollments functionality
    $userslist_sql.="  LIMIT $page, $perpage";
    if(!empty($query)||empty($mform)){
        $userslist = $DB->get_records_sql($userslist_sql);
        return $userslist;
    }
    if(!empty($costcenter) && !empty($roleid)){
        $userslist = $DB->get_records_sql_menu($userslist_sql);
    }// Added by Harish for fetching within university users in mooc course enrollments functionality
    if((isset($data->email)&&!empty($data->email))){
        $userslist = $DB->get_records_sql_menu($userslist_sql);
    }
    $options = array(
        'ajax' => 'local_courses/form-options-selector',
        'multiple' => true,
        'data-action' => 'email',
        'data-options' => json_encode(array('id' => 0, 'costcenter' => $costcenter, 'roleid' => $roleid)),
        'placeholder' => get_string('email')
    );
    $select = $mform->addElement('autocomplete', 'email', '',$userslist,$options);
    $mform->setType('email', PARAM_RAW);
}
/**
 * Description: User role filter code
 * @param  [mform object]  $mform          [the form object where the form is initiated]
 * @param  string  $query          [text inserted in filter]
 * @param  boolean $searchanywhere [description]
 * @param  integer $page           [page value]
 * @param  integer $perpage        [entities per page]
 */
function role_filter($mform,$query='',$searchanywhere=false, $page=0, $perpage=25) {
    global $DB, $USER;
    $systemcontext = context_system::instance();
    $userslist=array();
/*    $data=data_submitted();
*/

     $roleslist_sql = "SELECT r.id, r.name As role
              FROM {role} r
              JOIN {role_context_levels} rcl ON  r.id = rcl.roleid
         LEFT JOIN {role_names} rn ON rn.roleid = r.id
         WHERE r.shortname NOT IN ('manager', 'coursecreator', 'editingteacher', 'teacher')";

//     if(is_siteadmin() || has_capability('local/costcenter:manage_multiorganizations', $systemcontext)){
//         // $params['contextlevel'] = $systemcontext->contextlevel;
//         // if ($coursecontext = $systemcontext->get_course_context(false)) {
//         //     $params['coursecontext'] = $coursecontext->id;
//         // } else {
//         //     $params['coursecontext'] = 0; // no course aliases
//         //     $coursecontext = null;
//         // }
//         $roleslist_sql = "SELECT r.id, r.name As role
//               FROM {role} r
//               JOIN {role_context_levels} rcl ON  r.id = rcl.roleid
//          LEFT JOIN {role_names} rn ON rn.roleid = r.id
//          WHERE r.shortname NOT IN ('manager', 'coursecreator', 'editingteacher', 'teacher')";
//          // $sql .= " WHERE r.shortname NOT IN ('manager', 'coursecreator') ";
//     // if((!is_siteadmin() && has_capability('local/costcenter:manage_owndepartments', $systemcontext))){
//         // $sql .= " AND r.shortname = 'faculty'";
//          // $sql .= " AND r.shortname = 'student'";
//      // }
//         // $sql .= " ORDER BY r.sortorder ASC";
// // shortname IN ('employee', 'student')
//         // echo $sql;
//      // $roles = $DB->get_records_sql($sql, $params);
//     // print_object($roles);
//     // $rolenames = role_fix_names($roles, $systemcontext, ROLENAME_ORIGINAL);
//     // print_object($rolenames);exit;
//     }else
 if(!(is_siteadmin() || has_capability('local/costcenter:manage_multiorganizations', $systemcontext)) && has_capability('local/costcenter:manage_ownorganization', $systemcontext)){
        $roleslist_sql .=  " AND r.shortname != 'university_head'";
    }
    // else if(has_capability('local/costcenter:manage_owndepartments', $systemcontext)){
    //     $userslist_sql="SELECT id, email as fullname FROM {user} WHERE id > 2 AND open_costcenterid = $USER->open_costcenterid AND open_departmentid = $USER->open_departmentid AND deleted = 0 AND suspended = 0 AND id <> $USER->id AND id NOT IN (SELECT mdluserid FROM {local_sisuserdata})";
    // }
    // $roleslist = $DB->get_records_sql($roleslist_sql);
    // print_object($roleslist);exit;
    if(!empty($query)){
        if ($searchanywhere) {
            // echo "hiii";exit;
          $roleslist_sql.=" AND role LIKE '%$query%' ";
        } else {
         $roleslist_sql.=" AND role LIKE '$query%' ";
        }
    }
    if(isset($data->role)&&!empty(($data->role))){
        // echo "bye";exit;
        $implode=implode(',',$data->role);

        $roleslist_sql.=" AND r.id in ($implode)";
    }
    $roleslist_sql.="  LIMIT $page, $perpage";
    // if(!empty($query)||empty($mform)){
    // // echo $roleslist_sql;
    //     $roleslist = $DB->get_records_sql($roleslist_sql);
    //     // print_object($roleslist);
    //     return $roleslist;
    // }
    // if((isset($data->role)&&!empty($data->role))){
        $roleslist = $DB->get_records_sql_menu($roleslist_sql);
    // }
         // print_object($roleslist);

    $options = array(
        // 'ajax' => 'local_courses/form-options-selector',
        'multiple' => true,
        'data-action' => 'role',
        'data-options' => json_encode(array('id' => 0)),
        'placeholder' => get_string('role')
    );
    $select = $mform->addElement('autocomplete', 'role', '',$roleslist,$options);
    $mform->setType('role', PARAM_RAW);
}
/**
 * Description: User employeeid filter code
 * @param  [mform object]  $mform          [the form object where the form is initiated]
 * @param  string  $query          [text inserted in filter]
 * @param  boolean $searchanywhere [description]
 * @param  integer $page           [page value]
 * @param  integer $perpage        [entities per page]
 */
function employeeid_filter($mform,$query='',$searchanywhere=false, $page=0, $perpage=25){
    global $DB, $USER;
    $systemcontext = context_system::instance();
    $userslist=array();
    $data=data_submitted();
    if(is_siteadmin() || has_capability('local/costcenter:manage_multiorganizations', $systemcontext)){
        $userslist_sql="SELECT id, open_employeeid as fullname FROM {user} WHERE id > 2 AND deleted = 0 AND suspended = 0 AND id <> $USER->id ";
    }else if(has_capability('local/costcenter:manage_ownorganization', $systemcontext)){
        $userslist_sql="SELECT id, open_employeeid as fullname FROM {user} WHERE id > 2 AND open_costcenterid = $USER->open_costcenterid AND deleted = 0 AND suspended = 0 AND id <> $USER->id ";
    }else if(has_capability('local/costcenter:manage_owndepartments', $systemcontext)){
        $userslist_sql="SELECT id, open_employeeid as fullname FROM {user} WHERE id > 2 AND open_costcenterid = $USER->open_costcenterid AND open_departmentid = $USER->open_departmentid AND deleted = 0 AND suspended = 0 AND id <> $USER->id ";
    }
    if(!empty($query)){
        if ($searchanywhere) {
            $userslist_sql.=" AND open_employeeid LIKE '%$query%' ";
        } else {
            $userslist_sql.=" AND open_employeeid LIKE '$query%' ";
        }
    }
    if(isset($data->idnumber)&&!empty(($data->idnumber))&&is_array($data->idnumber)){

        $implode=implode(',',$data->idnumber);

        $userslist_sql.=" AND id in ($implode)";
    }
    $userslist_sql .= " AND open_employeeid IS NOT NULL";
    $userslist_sql.="  LIMIT $page, $perpage";
    if(!empty($query)||empty($mform)){
        $userslist = $DB->get_records_sql($userslist_sql);
        return $userslist;
    }
    if((isset($data->idnumber)&&!empty($data->idnumber))){
        $userslist = $DB->get_records_sql_menu($userslist_sql);
    }
    $options = array(
        'ajax' => 'local_courses/form-options-selector',
        'multiple' => true,
        'data-action' => 'employeeid',
        'data-options' => json_encode(array('id' => 0)),
        'placeholder' => get_string('idnumber','local_users')
    );
    $select = $mform->addElement('autocomplete', 'idnumber', '',$userslist,$options);
    $mform->setType('idnumber', PARAM_RAW);
}
/**
 * Description: User designation filter code
 * @param  [mform object]  $mform          [the form object where the form is initiated]
 */
function designation_filter($mform){
    global $DB, $USER;
    $systemcontext = context_system::instance();
    if(is_siteadmin() || has_capability('local/costcenter:manage_multiorganizations', $systemcontext)){
        $userslist = $DB->get_records_sql_menu("SELECT id, open_designation FROM {user} WHERE id > 2 AND deleted = 0 AND suspended = 0 AND id <> $USER->id");
    }else if(has_capability('local/costcenter:manage_ownorganization', $systemcontext)){
        $userslist = $DB->get_records_sql_menu("SELECT id, open_designation FROM {user} WHERE id > 2 AND open_costcenterid = $USER->open_costcenterid AND deleted = 0 AND suspended = 0 AND id <> $USER->id");
    }else if(has_capability('local/costcenter:manage_owndepartments', $systemcontext)){
        $userslist = $DB->get_records_sql_menu("SELECT id, open_designation FROM {user} WHERE id > 2 AND open_costcenterid = $USER->open_costcenterid AND open_departmentid = $USER->open_departmentid AND deleted = 0 AND suspended = 0 AND id <> $USER->id");
    }
    $select = $mform->addElement('autocomplete', 'designation', '', $userslist, array('placeholder' => get_string('designation','local_users')));
    $mform->setType('idnumber', PARAM_RAW);
    $select->setMultiple(true);
}
/**
 * Description: User location filter code
 * @param  [mform object]  $mform          [the form object where the form is initiated]
 */
function location_filter($mform){
    global $DB, $USER;
    $systemcontext = context_system::instance();
    if(is_siteadmin() || has_capability('local/costcenter:manage_multiorganizations', $systemcontext)){
        $userslist = $DB->get_records_sql_menu("SELECT id, open_location FROM {user} WHERE id > 2 AND deleted = 0 AND suspended = 0 AND id <> $USER->id");
    }else if(has_capability('local/costcenter:manage_ownorganization', $systemcontext)){
        $userslist = $DB->get_records_sql_menu("SELECT id, open_location FROM {user} WHERE id > 2 AND open_costcenterid = $USER->open_costcenterid AND deleted = 0 AND suspended = 0 AND id <> $USER->id");
    }else if(has_capability('local/costcenter:manage_owndepartments', $systemcontext)){
        $userslist = $DB->get_records_sql_menu("SELECT id, open_location FROM {user} WHERE id > 2 AND open_costcenterid = $USER->open_costcenterid AND open_departmentid = $USER->open_departmentid AND deleted = 0 AND suspended = 0 AND id <> $USER->id");
    }
    $select = $mform->addElement('autocomplete', 'location', '', $userslist, array('placeholder' => get_string('location','local_users')));
    $mform->setType('idnumber', PARAM_RAW);
    $select->setMultiple(true);
}

/**
 * Description: User band filter code
 * @param  [mform object]  $mform          [the form object where the form is initiated]
 */
function band_filter($mform){
    global $DB, $USER;
    $systemcontext = context_system::instance();
    if(is_siteadmin() || has_capability('local/costcenter:manage_multiorganizations', $systemcontext)){
        $userslist = $DB->get_records_sql_menu("SELECT id, open_band FROM {user} WHERE id > 2 AND deleted = 0 AND suspended = 0 AND id <> $USER->id");
    }else if(has_capability('local/costcenter:manage_ownorganization', $systemcontext)){
        $userslist = $DB->get_records_sql_menu("SELECT id, open_band FROM {user} WHERE id > 2 AND open_costcenterid = $USER->open_costcenterid AND deleted = 0 AND suspended = 0 AND id <> $USER->id");
    }else if(has_capability('local/costcenter:manage_owndepartments', $systemcontext)){
        $userslist = $DB->get_records_sql_menu("SELECT id, open_band FROM {user} WHERE id > 2 AND open_costcenterid = $USER->open_costcenterid AND open_departmentid = $USER->open_departmentid AND deleted = 0 AND suspended = 0 AND id <> $USER->id");
    }
    $select = $mform->addElement('autocomplete', 'band', '', $userslist, array('placeholder' => get_string('band','local_users')));
    $mform->setType('idnumber', PARAM_RAW);
    $select->setMultiple(true);
}
/**
 * Description: User name filter code
 * @param  [mform object]  $mform          [the form object where the form is initiated]
 */
function username_filter($mform){
    global $DB, $USER;
    $systemcontext = context_system::instance();
    if(is_siteadmin()){
        $userslist = $DB->get_records_sql_menu("SELECT id, username FROM {user} WHERE id > 2 AND deleted = 0 AND suspended = 0 AND id <> $USER->id");
    }else if(has_capability('local/costcenter:manage_ownorganization', $systemcontext)){
        $userslist = $DB->get_records_sql_menu("SELECT id, username FROM {user} WHERE id > 2 AND open_costcenterid = $USER->open_costcenterid AND deleted = 0 AND suspended = 0 AND id <> $USER->id");
    }else if(has_capability('local/costcenter:manage_owndepartments', $systemcontext)){
        $userslist = $DB->get_records_sql_menu("SELECT id, username FROM {user} WHERE id > 2 AND open_costcenterid = $USER->open_costcenterid AND open_departmentid = $USER->open_departmentid AND deleted = 0 AND suspended = 0 AND id <> $USER->id");
    }
    $select = $mform->addElement('autocomplete', 'username', '',$userslist, array('placeholder' => get_string('username')));
    $mform->setType('username', PARAM_RAW);
    $select->setMultiple(true);
}
/**
 * Description: University boards filter code
 * @param  [mform object]  $mform          [the form object where the form is initiated]
 */
function boards_filter($mform){
    global $DB, $USER;
    $systemcontext = context_system::instance();
    if(is_siteadmin()){
        $boardslist = $DB->get_records_sql_menu("SELECT id, fullname FROM {local_boards}");
    }else if(has_capability('local/costcenter:manage_ownorganization', $systemcontext)){
        $boardslist = $DB->get_records_sql_menu("SELECT id, fullname FROM {local_boards} WHERE university = $USER->open_costcenterid ");
    }
    $select = $mform->addElement('autocomplete', 'boards', '',$boardslist, array('placeholder' => get_string('boards', 'local_boards')));
    $mform->setType('boards', PARAM_RAW);
    $select->setMultiple(true);
}
/**
 * Description: User custom filter code
 * @param  [mform object]  $mform          [the form object where the form is initiated]
 */
function custom_filter($mform){
    global $DB, $USER;
    $systemcontext = context_system::instance();
    //$fieldvalue='email';
   // $filters=array();
    $filterv=$DB->get_field('local_filters','filters',array('plugins'=>'users'));
    $filterv=explode(',',$filterv);
    foreach($filterv as $fieldvalue){
        if(is_siteadmin() || has_capability('local/costcenter:manage_multiorganizations', $systemcontext)){
            $userslist = $DB->get_records_sql_menu("SELECT id, $fieldvalue FROM {user} WHERE id > 2 AND deleted = 0 AND suspended = 0 AND id <> $USER->id");
        }else if(has_capability('local/costcenter:manage_ownorganization', $systemcontext)){
            $userslist = $DB->get_records_sql_menu("SELECT id, $fieldvalue FROM {user} WHERE id > 2 AND open_costcenterid = $USER->open_costcenterid AND deleted = 0 AND suspended = 0 AND id <> $USER->id");
        }else if(has_capability('local/costcenter:manage_owndepartments', $systemcontext)){
            $userslist = $DB->get_records_sql_menu("SELECT id, $fieldvalue FROM {user} WHERE id > 2 AND open_costcenterid = $USER->open_costcenterid AND open_departmentid = $USER->open_departmentid AND deleted = 0 AND suspended = 0 AND id <> $USER->id");
        }

        $select = $mform->addElement('autocomplete', $fieldvalue, '', $userslist, array('placeholder' => get_string($fieldvalue,'local_users')));
        $mform->setType($fieldvalue, PARAM_RAW);
        $select->setMultiple(true);
    }
}
// OL-1042 Add Target Audience to Classrooms//
/**
 * [globaltargetaudience_elementlist description]
 * @param  [type] $mform       [description]
 * @param  [type] $elementlist [description]
 * @return [type]              [description]
 */
function globaltargetaudience_elementlist($mform,$elementlist){
    global $CFG, $DB, $USER;

    $context = context_system::instance();
    if(is_siteadmin()||has_capability('local/costcenter:manage_multiorganizations',$context)){
        $main_sql="";
    }elseif(has_capability('local/costcenter:manage_ownorganization',$context)){
        $main_sql=" AND u.suspended =0 AND u.deleted =0  AND u.open_costcenterid = $USER->open_costcenterid ";
    }else if(has_capability('local/costcenter:manage_owndepartments',$context)){
        $main_sql=" AND u.suspended =0 AND u.deleted =0  AND u.open_costcenterid = $USER->open_costcenterid AND u.open_departmentid = $USER->open_departmentid ";
    }
    $dbman = $DB->get_manager();
    if (in_array('group', $elementlist)){
        $groupslist[null]=get_string('all');
        if(is_siteadmin() || has_capability('local/costcenter:manage_multiorganizations',$context) ){
            if($dbman->table_exists('local_groups')){
                $groupslist+= $DB->get_records_sql_menu("SELECT c.id, c.name FROM {local_groups} g, {cohort} c  WHERE c.visible = 1 AND c.id = g.cohortid ");
            }
        }else if(has_capability('local/costcenter:manage_ownorganization', $context)){
            $sql = "SELECT c.id, c.name FROM {local_groups} g, {cohort} c  WHERE c.visible = 1 AND c.id = g.cohortid AND g.costcenterid IN( $USER->open_costcenterid )";
            $groupslist+= $DB->get_records_sql_menu("SELECT c.id, c.name FROM {local_groups} g, {cohort} c  WHERE c.visible = 1 AND c.id = g.cohortid AND g.costcenterid IN( $USER->open_costcenterid )");
        }else if(has_capability('local/costcenter:manage_owndepartments', $context)){
            $groupslist+= $DB->get_records_sql_menu("SELECT c.id, c.name FROM {local_groups} g, {cohort} c  WHERE c.visible = 1 AND c.id = g.cohortid AND ( find_in_set($USER->open_departmentid, departmentid) <> 0)");
        }
        $selectgroup = $mform->addElement('autocomplete',  'open_group',  get_string('open_group', 'local_users'),$groupslist);
        $mform->setType('open_group', PARAM_RAW);
        $selectgroup->setMultiple(true);
    }
    if (in_array('hrmsrole', $elementlist)){
        $hrmsrole_details[null]=get_string('all');
        $hrmsrole_sql = "SELECT u.open_hrmsrole,u.open_hrmsrole AS hrmsrolevalue FROM {user} AS u WHERE u.id > 2 $main_sql AND u.open_hrmsrole IS NOT NULL GROUP BY u.open_hrmsrole";
        $hrmsrole_details+= $DB->get_records_sql_menu($hrmsrole_sql);
        $selecthrmsrole = $mform->addElement('autocomplete',  'open_hrmsrole',  get_string('open_hrmsrole', 'local_users'),$hrmsrole_details);
        $mform->setType('open_hrmsrole', PARAM_RAW);
        $selecthrmsrole->setMultiple(true);
    }
    if (in_array('designation', $elementlist)){
        $designation_details[null]=get_string('all');
        $designation_sql = "SELECT u.open_designation,u.open_designation AS designationvalue FROM {user} AS u WHERE u.id > 2 $main_sql AND u.open_designation IS NOT NULL GROUP BY u.open_designation";
        $designation_details+= $DB->get_records_sql_menu($designation_sql);
        $selectdesignation = $mform->addElement('autocomplete',  'open_designation',  get_string('open_designation', 'local_users'),$designation_details);
        $mform->setType('open_designation', PARAM_RAW);
        $selectdesignation->setMultiple(true);
    }
    if (in_array('location', $elementlist)){
        $location_details[null]=get_string('all');
        $location_sql = "SELECT u.city, u.city AS locationvalue FROM {user} AS u WHERE u.id > 2 $main_sql AND u.city IS NOT NULL GROUP BY u.city";
        $location_details+= $DB->get_records_sql_menu($location_sql);
        $selectlocation = $mform->addElement('autocomplete',  'open_location',  get_string('open_location', 'local_users'),$location_details);
        $mform->setType('open_location', PARAM_RAW);
        $selectlocation->setMultiple(true);
    }
    if (in_array('branch', $elementlist)){
        $branch_details[null]=get_string('all');
        $branch_sql = "SELECT u.open_branch,u.open_branch AS branchvalue FROM {user} AS u WHERE u.id > 2 $main_sql AND u.open_branch IS NOT NULL GROUP BY u.open_branch";
        $branch_details+= $DB->get_records_sql_menu($branch_sql);
        $selectbranch = $mform->addElement('autocomplete',  'open_branch',  get_string('open_branch', 'local_users'), $branch_details);
        $mform->setType('open_branch', PARAM_RAW);
        $selectbranch->setMultiple(true);
    }
    if (in_array('band', $elementlist)){
        $band_details[null]=get_string('all');
        $band_sql = "SELECT u.open_band,u.open_band AS bandvalue FROM {user} AS u WHERE u.id > 2 $main_sql AND u.open_band IS NOT NULL GROUP BY u.open_band";
        $band_details+= $DB->get_records_sql_menu($band_sql);
        $selectband = $mform->addElement('autocomplete',  'open_band',  get_string('open_band',  'local_users'),$band_details);
        $mform->setType('open_band', PARAM_RAW);
        $selectband->setMultiple(true);
    }
}


// Count users display with staus of that moduletype//
/**
 * @param  [type] $args       object of sending from service
 */
function local_users_output_fragment_users_display_modulewise($args){
 global $CFG, $USER, $PAGE, $DB;
        $args = (object) $args;
        $userid = $args->id;
        $teamstatus = new team_status_lib();
        switch ($args->moduletype) {
            case 'courses':
                $inprogresscourses = $teamstatus->inprogress_coursenames($userid);
                $completedcourses = $teamstatus->completed_coursenames($userid);
                $totalmodules = $inprogresscourses+$completedcourses;
            break;
            case 'classrooms':
                $totalmodules = $teamstatus->classrooms_status_count_eachuser($userid,'1,4');
            break;

            case 'programs':
                $totalmodules = $teamstatus->programs_status_count_eachuser($userid,'0,1');
            break;

            case 'learningplans':
                $learningplans = $teamstatus->get_team_member_lp_status_display($userid);
                $complearningplans = $teamstatus->get_team_member_lp_status_display($userid,1);
                $totalmodules = $learningplans+$complearningplans;
            break;

            case 'certifications':
                $totalmodules = $teamstatus->certification_status_count_display($userid,'1,4');
            break;

            case 'onlinetests':
                $onlinetests = $teamstatus->get_team_member_onlinetest_status_display($userid);
                $componlinetests = $teamstatus->get_team_member_onlinetest_status_display($userid,1);
                $totalmodules = $onlinetests+$componlinetests;
            break;

            case 'badges':
                $badgecount = $DB->count_records_sql("SELECT count(id) FROM {badge_issued} WHERE userid = $userid");
                $totalbadgecountsql = "SELECT count(id) FROM {badge}";
                $totalbadges = $DB->count_records_sql($totalbadgecountsql);
                $totalbadgesql = "SELECT id,name as fullname FROM {badge}";
                $totalmodules = $DB->get_records_sql($totalbadgesql);
            break;
        }
        $data = array();
    if(!empty($totalmodules)){
        foreach($totalmodules as $totalmodule){
            $row = array();
            $row[] = $totalmodule->fullname;
            if($args->moduletype != 'badges'){
                $row[] = $totalmodule->code;
                $row[] = date('d-m-Y',$totalmodule->enrolldate);
            }
            if($args->moduletype == 'courses'){
                $completionstatus = $DB->get_record_sql("SELECT cc.id FROM {course_completions} as cc WHERE cc.course = $totalmodule->id AND cc.userid = $userid AND cc.timecompleted IS NOT NULL");
                if($completionstatus){
                    $status = 'Completed';
                }else{
                    $status = 'Not Completed';
                }
            }else{
                if($args->moduletype == 'badges'){
                    $completionstatus = $DB->get_record_sql("SELECT id FROM {badge_issued}  WHERE badgeid = $totalmodule->id AND userid = $userid");
                    if($completionstatus){
                        $status = 'Completed';
                    }else{
                        $status = 'Not Completed';
                    }
                }else{
                    if($totalmodule->status){
                        $status = 'Completed';
                    }else{
                        $status = 'Not Completed';
                    }
                }
            }
            $row[] = $status;
            $data[] = $row;
        }

        $table = new html_table();
        $table->data = $data;
        $table->attributes['class'] = 'myteam_status_count'.$userid;
        $header = array();
        $table_align = array();
        $table_size = array();

        $header[] = get_string('name','local_users');
        $table_align[] = 'center';
        $table_size[] = '20%';
        if($args->moduletype != 'badges'){
            $header[] = get_string('code','local_users');
            $table_align[] = 'center';
            $table_size[] = '20%';

            $header[] = get_string('enrolldate','local_users');
            $table_align[] = 'center';
            $table_size[] = '20%';
        }

        $header[] = get_string('status','local_users');
        $table_align[] = 'center';
        $table_size[] = '20%';

        $table->head = $header;
        $table->size = $table_size;
        $table->align = $table_align;

        $team_statis_data = html_writer::table($table);
        $team_statis_data .= html_writer::script("$(\".myteam_status_count$userid\").dataTable({
                                                      'iDisplayLength':5,
                                                    'bLengthChange': false,
                                                    'bInfo': false,
                                                    language: {
                                                           search: '',
                                                           searchPlaceholder: '".get_string('search', 'local_users')."',
                                                        paginate: {
                                                            'previous': '<',
                                                            'next': '>'
                                                        },
                                                    oLanguage: { 'search': '<i class=\"fa fa-search\"></i>' },
                                                        \"emptyTable\": \"<div class='alert alert-info'>'".get_string('team_nodata', 'local_users')."'</div>\"
                                                    }
                                                    });"
                                                );
    } else {
        $team_statis_data = '<div class="alert alert-info">'.get_string('team_nodata', 'local_users').'</div>';
    }
    $view = '<div class="">
                                    <div class="portlet-title">
                                        <div class="actions">
                                        </div>
                                    </div>
                                    <div class="portlet-body">
                                        <div class="slimScrollDiv" style="position: relative; overflow-y: auto; width: auto;  padding-right: 5px;">
                                        '.$team_statis_data.'
                                        </div>
                                    </div>
                                </div>';
        return $view;
}
//ended by sharath here

/*
* Author Rizwana
* Displays a node in left side menu
* @return  [type] string  link for the leftmenu
*/
function local_users_leftmenunode(){
    global $USER, $DB;
    $systemcontext = context_system::instance();
    $usersnode = '';
    if(!is_siteadmin() && (has_capability('local/users:manage',$systemcontext) && has_capability('local/costcenter:manage_owndepartments', $systemcontext))){
        $manageusers_string =  get_string('manage_users','local_users');
    }else{
       $manageusers_string =  get_string('manage_users','local_users');
    }
     if(has_capability('local/users:manage',$systemcontext) || is_siteadmin()) {
        $usersnode .= html_writer::start_tag('li', array('id'=> 'id_leftmenu_users', 'class'=>'pull-left user_nav_div users dropdown-item'));
            $users_url = new moodle_url('/local/users/index.php');
            $users = html_writer::link($users_url, '<i class="fa fa-user-plus" aria-hidden="true"></i><span class="user_navigation_link_text">'.$manageusers_string.'</span>',array('class'=>'user_navigation_link'));
            $usersnode .= $users;
        $usersnode .= html_writer::end_tag('li');
    }
    return array('6' => $usersnode);
}

function local_users_quicklink_node(){
    global $DB, $USER, $CFG;
    $systemcontext = context_system::instance();
    if(is_siteadmin() || has_capability('local/users:view',$systemcontext)){
        if(is_siteadmin() || has_capability('local/costcenter:manage_multiorganizations',$systemcontext)){
            $count_users = count($DB->get_records_sql('SELECT id FROM {user} WHERE id > 2 AND deleted = 0 AND open_employee IS NOT NULL'));
        }else if(has_capability('local/costcenter:manage_ownorganization',$systemcontext)){
            /*$count_users = $DB->count_records('user', array('deleted' => 0, 'open_costcenterid' => $USER->open_costcenterid));*/
            $count_users = $DB->count_records_sql("SELECT count(id) FROM {user} WHERE deleted = 0 AND open_costcenterid = $USER->open_costcenterid AND id != $USER->id AND open_employee IS NOT NULL");
        }else if(has_capability('local/costcenter:manage_owndepartments',$systemcontext)){
            /*$count_users = $DB->count_records('user', array('deleted' => 0, 'open_costcenterid' => $USER->open_costcenterid, 'open_departmentid' => $USER->open_departmentid));*/
            if($USER->open_departmentid){
                $count_users = $DB->count_records_sql("SELECT count(id) FROM {user} WHERE deleted = 0 AND open_costcenterid = $USER->open_costcenterid AND open_departmentid = $USER->open_departmentid AND id != $USER->id AND open_employee IS NOT NULL");
            }else{
                $count_users = 0;
            }
        }
    }
    $users_content .= '<div class="w-full pull-left list_wrapper blue_block">
                                <div class="w-full pull-left top_content">
                                    <span class="pull-left quick_links_icon"><i class="fa fa-user-plus" aria-hidden="true" aria-label=""></i></span>
                                    <span class="pull-right quick_links_count"><a href="'.$CFG->wwwroot.'/local/users/index.php">'.$count_users.'</a></span>
                                </div>
                                <div class="w-full pull-left pl-15px pr-15px"><a class="quick_link" href="'.$CFG->wwwroot.'/local/users/index.php">'.get_string('manage_users', 'local_users').'</a></div>
                            </div>';
    return array('1' => $users_content);
}

/*
* Author Sarath
* return count of users under selected costcenter
* @return  [type] int count of users
*/
function costcenterwise_users_count($costcenter,$department = false){
    global $USER, $DB;
        $params = array();
        $params['costcenter'] = $costcenter;
        $countusersql = "SELECT count(id) FROM {user} WHERE open_costcenterid = :costcenter AND deleted = 0";
        if($department){
            $countusersql .= " AND open_departmentid = :department ";
            $params['department'] = $department;
        }
        $activesql = " AND suspended = 0 ";
        $inactivesql = " AND suspended = 1 ";

        $countusers = $DB->count_records_sql($countusersql, $params);
        $activeusers = $DB->count_records_sql($countusersql.$activesql, $params);
        $inactiveusers = $DB->count_records_sql($countusersql.$inactivesql, $params);
    return array('totalusers' => $countusers,'activeusercount' => $activeusers,'inactiveusercount' => $inactiveusers);
}
//fetch faculty from API
function create_faculty(){
    global $DB, $USER, $CFG;
    require_once($CFG->dirroot.'/user/lib.php');
  $systemcontext = context_system::instance();
    $curl = curl_init();
    $getheaders = array(
        'Api-id: API_HRM_001',
        'token: d6423c99f11b20693cb9cc78b3ea3c14',
        'transaction-id: 20193005_HVM_GHM_ACS',
        'requester-identity: c33c9833-d99d-4280-abce-5c8e94a2b58c',
        'requested-datetime: 1552626751',
        'last-receive-data-datetime: 1552626720',
        'request-id: 1559559828_HRM002',
        'requesting-source-system-id: 24'

    );
    $facultyfetchurl = 'https://mahait.tymra.com/api.php/system/hrappservices/EmployeeMaster';
    curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "GET");
    curl_setopt($curl, CURLOPT_URL, $facultyfetchurl);
    curl_setopt($curl, CURLOPT_HTTPHEADER, $getheaders);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    $output = curl_exec($curl);
    curl_close($curl);
    $facultyfetch = json_decode($output, true);
    $faculties = $facultyfetch['hrm_staff_members'];

    $roleid = $DB->get_field_sql("SELECT id FROM {role} WHERE shortname = 'faculty'");
    $facultylist = array();
    foreach ($faculties as $key => $faculty) {
        $userdata = (object)$faculty;
        $staff_type = $faculty['staff_type'];
        if($staff_type == 'Faculty'){
            $userdata->username =  $faculty['source_system_unique_id_of_staff'];
            $userdata->password =  hash_internal_user_password('Welcome#3', true);
            $userdata->firstname = $faculty['first_name'];
            $userdata->lastname = $faculty['last_name '];
            $userdata->middlename = $faculty['middle_name '];
            $userdata->email = $faculty['email_id'];
            $userdata->phone1 = $faculty['mobile'];
            $userdata->confirmed = 1;
            $userdata->mnethostid = 1;
            
            $projectid = 15;
            $universityid = $DB->get_field('local_costcenter', 'id', array('projectid'=> $projectid));

            $userdata->open_costcenterid =  $universityid ;
            $userdata->idnumber =  $faculty['source_system_unique_id_of_staff'];
            $userdata->address = $faculty['address']['correspondence_address'];
            $userdata->user_type = $faculty['staff_type'];
            $userdata->open_role = $roleid;
            $userdata->timecreated = time();
            $existusers = $DB->get_record_sql("SELECT username
                FROM {user}
                WHERE username = ".$faculty['source_system_unique_id_of_staff'] );
            if(!$existusers){
                $userid = user_create_user($userdata, false);
                if(!user_has_role_assignment($userid,$roleid)){
                    role_assign($roleid, $userid, $systemcontext->id);
                }
                // $sisuserdata = new stdClass();
                // $date = new DateTime($faculty['dob']);
                // $sisuserdata->dob = $date->getTimestamp();
                // $sisuserdata->costcenterid = '1';
                // $sisuserdata->mdluserid = $userid;
                // $sisuserdata->timecreated = time();
                // $sisuserid = $DB->insert_record('local_sisuserdata', $sisuserdata);
                $facultylist[] = $userid;
            }else{
                 $existuser_id = $DB->get_record('user', array('username' => $faculty['source_system_unique_id_of_staff']));

                $userdata = new stdClass();
                $userdata->id = $existuser_id->id;
                $userdata->firstname = $faculty['first_name'];
                $userdata->lastname = $faculty['last_name '];
                $userdata->middlename = $faculty['middle_name '];
                $userdata->email = $faculty['email_id'];
                $userdata->phone1 = $faculty['mobile'];
                $projectid = 15;
                $universityid = $DB->get_field('local_costcenter', 'id', array('projectid'=> $projectid));

                $userdata->open_costcenterid =  $universityid ;
                $userdata->idnumber =  $faculty['source_system_unique_id_of_staff'];
                $userdata->address = $faculty['address']['correspondence_address'];
                $userdata->user_type = $faculty['staff_type'];
                $userdata->open_role = $roleid;

                $userdata->timemodified = time();
                // $dddd = user_update_user($userdata, false);
                $userid = $DB->update_record('user', $userdata);
                // $sisuserexist_id = $DB->get_record('local_sisuserdata', array('mdluserid' => $existuser_id->id ));

                // $sisuserdata = new stdClass();
                // $date = new DateTime($faculty['dob']);
                // $sisuserdata->dob = $date->getTimestamp();
                // $sisuserdata->costcenterid = '1';
                // $sisuserdata->id = $sisuserexist_id->id;

                // $sisuserdata->timemodified = time();
                // $sisuserid = $DB->update_record('local_sisuserdata', $sisuserdata);
                $facultylist[] = $userid;
            }
        }
    }
    return $facultylist;
}
//Fetch Students from API
function create_student(){
    global $DB, $USER, $CFG;
    require_once($CFG->dirroot.'/user/lib.php');
    $systemcontext = context_system::instance();
    $curl = curl_init();
    $studentfetchurl = 'http://campus-iums.com/EDPS/admin/IcrWebApi.php';
    curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "GET");
    curl_setopt($curl, CURLOPT_URL, $studentfetchurl);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    $studentlists = curl_exec($curl);
    curl_close($curl);

    $studentfetch = json_decode($studentlists, true);
    $studentlist = array();
    $roleid = $DB->get_field_sql("SELECT id FROM {role} WHERE shortname = 'student'");
    foreach ($studentfetch['user'] as $student) {
        $userdata = (object)$student;
        $username = strtolower($student['PrnNo']);
        // $username = $student['UniqId'];
        $userdata->username =  $username;
        $userdata->confirmed = 1;
        $userdata->mnethostid = 1;
        $userdata->password = hash_internal_user_password('Welcome#3', true);
        $userdata->firstname = $student['FirstName'];
        $userdata->lastname = $student['LastName'];
        $userdata->middlename = $student['MiddleName'];
        $userdata->email = $student['EMail'];
        $userdata->phone1 = $student['Mobile'];
        $userdata->idnumber =  $student['UniqId'];

        $projectid = 15;
        $universityid = $DB->get_field('local_costcenter', 'id', array('projectid'=> $projectid));

        $userdata->open_costcenterid = $universityid;
        $userdata->address = $student['Address_Permanent'];
        $userdata->user_type = 'Student';
        $userdata->open_role = $roleid;
        $existusers = $DB->get_record_sql("SELECT username
             FROM {user}
             WHERE username =  '$username' " );
        if(!$existusers){
            $userid = user_create_user($userdata, false);
            if(!user_has_role_assignment($userid,$roleid)){
                role_assign($roleid, $userid, $systemcontext->id);
            }
             // $sisuserdata = new stdClass();
             //    $date = new DateTime($student['BirthDate']);
             //    $sisuserdata->dob = $date->getTimestamp();
             //    $sisuserdata->gender = $student['Sex'];
             //    $sisuserdata->sisprnid = $student['PrnNo'];
             //    $projectid = 15;
             //    $universityid = $DB->get_field('local_costcenter', 'id', array('projectid'=> $projectid));
             //    $sisuserdata->costcenterid = $universityid;
             //    $sisuserdata->mdluserid = $userid;
             //    $sisuserdata->timecreated = time();
             //    $sisuserid = $DB->insert_record('local_sisuserdata', $sisuserdata);

            if (!$enrol_manual = enrol_get_plugin('manual')) {
            throw new coding_exception('Can not instantiate enrol_manual');
            }
            
            $enrollment['coursecode'] = 'U-0335-R-BMATH';
            $courseid = $DB->get_field('course', 'id', array('shortname' => $enrollment['coursecode']));
            $costcenterid = $DB->get_field('local_sisonlinecourses', 'costcenterid', array('coursecode' => $enrollment['coursecode']));
            $siscourseid = $DB->get_field('local_sisonlinecourses', 'courseid', array('coursecode' => $enrollment['coursecode']));
            $sisrecord = $DB->get_record('local_sisonlinecourses', array('coursecode' => $enrollment['coursecode']));
            $programname = $DB->get_field('local_sisprograms', 'fullname', array('id' => $sisrecord->programid));
            $schoolname = $DB->get_field('local_costcenter', 'fullname', array('id' => $sisrecord->costcenterid));
            $coursename = $DB->get_field('course', 'fullname', array('id' => $sisrecord->courseid));

             if ($userid) {
                if ($enrollment['role']) {
                    $roleid = $DB->get_field('role', 'id', array('shortname' => $enrollment['role']));
                }
                $enrolid = $DB->get_field('enrol', 'id', array('courseid' => $courseid, 'enrol' => 'manual'));
                if (!$DB->record_exists('user_enrolments', array('enrolid' => $enrolid, 'userid' => $userid))) {
                    $instance = $DB->get_record('enrol', array('id' => $enrolid, 'enrol' => 'manual'), '*', MUST_EXIST);
                    $enrol_manual->enrol_user($instance, $userid, $roleid);
                } else {
                    $coursename = $DB->get_field('course', 'fullname', array('id' => $courseid));
                    $coursecode = $DB->get_field('course', 'shortname', array('id' => $courseid));
                    $warning = array();
                    $warning['warningcode'] = '0';
                    $warning['message'] = "User already enrolled to '" . $coursename . " (" . $coursecode . ")' course on LMS.";
                    $warnings[] = $warning;

                    $response = array();
                    $response['exception'] = 'moodle_exception';
                    $response['errorcode'] = 0;
                    $response['message'] = 'Failed';
                    $result = array();
                    $result = $response;
                    $result['warnings'] = $warnings;
                    return $result;
                }
                $result = $DB->record_exists('local_courseenrolments', array('courseid' => $courseid, 'mdluserid' => $userid, 'programid' => $sisrecord->programid));
                if (!$DB->record_exists('local_courseenrolments', array('courseid' => $courseid, 'mdluserid' => $userid, 'programid' => $sisrecord->programid))) {
                    $courseenrol = new stdClass();
                    $courseenrol->courseid = $courseid;
                    $courseenrol->costcenterid = $costcenterid;
                    $courseenrol->programid = $sisrecord->programid;
                    $courseenrol->mdluserid = $userid;
                    $courseenrol->sisuserid = $sisuserid;
                    $courseenrol->roleid = $roleid;
                    $courseenrol->coursename = $coursename;
                    $courseenrol->schoolname = $schoolname;
                    $courseenrol->programname = $programname;
                    $courseenrol->timecreated = time();
                    $courseenrol->timemodified = 0;
                    $courseenrol->usercreated = $USER->id;
                    $courseenrol->usermodified = 0;
                    $enrolmentid = $DB->insert_record('local_courseenrolments', $courseenrol);
                }
            }

        }else{
            $existuser_id = $DB->get_record('user', array('username' => $username));
             $userdata = new stdClass();
                $userdata->id = $existuser_id->id;

                $userdata->firstname = $student['FirstName'];
                $userdata->lastname = $student['LastName'];
                $userdata->middlename = $student['MiddleName'];
                $userdata->email = $student['EMail'];
                $username = strtolower($student['PrnNo']);
                $userdata->username = $username;
                $userdata->phone1 = $student['Mobile'];
                $userdata->open_costcenterid = '1' ;
                $userdata->idnumber =  $student['UniqId'];
                $userdata->address = $student['Address_Permanent'];
                $userdata->user_type = 'Student';
                $userdata->open_role = $roleid;
                $userdata->timemodified = time();
                $userid = $DB->update_record('user', $userdata);
                //$sisuserexist_id = $DB->get_record('local_sisuserdata', array('mdluserid' => $existuser_id->id ));

                // $sisuserdata = new stdClass();
                // $date = new DateTime($student['BirthDate']);
                // $sisuserdata->dob = $date->getTimestamp();
                // $sisuserdata->sisprnid = $student['PrnNo'];
                // $sisuserdata->gender = $student['Sex'];
                // $sisuserdata->costcenterid = '1';
                // $sisuserdata->id = $sisuserexist_id->id;
                // $sisuserdata->timemodified = time();
                // $sisuserid = $DB->update_record('local_sisuserdata', $sisuserdata);
                $studentlist[] = $userid;
        }

}
return $studentlist;
}

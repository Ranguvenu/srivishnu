<?php

namespace local_users\events;
// require_once($CFG->dirroot . '/local/costcenter/lib.php');
require_once($CFG->dirroot.'/user/lib.php');
// require_once($CFG->dirroot.'/lib.php');
use html_writer;
use moodle_url;
use context_system;
use tabobject;
use user_create_user;
use context_user;
use core_user;
// use hash_internal_user_password;
class users {

    private static $_users;
    private $dbHandle;


    public static function getInstance() {
        if (!self::$_users) {
            self::$_users = new users();
        }
        return self::$_users;
    }

    /**
     * @method insert_newuser
     * @todo To create new user with system role
     * @param object $data Submitted form data
     */
    public function insert_newuser($data) {
        global $DB, $USER, $CFG;
        $systemcontext = context_system::instance();
        $userdata = (object)$data;
        foreach($data as $key => $value){
            $userdata->$key = trim($value);
        } 
        $userdata->username = $userdata->email;
        $userdata->auth = 'manual';
        $userdata->confirmed = 1;
        $userdata->deleted = 0;
        $userdata->mnethostid = 1;
        if($userdata->open_employeeid){
            $userdata->idnumber = $userdata->open_employeeid;
        }
        if($userdata->open_employeeid){
            $userdata->idnumber = $userdata->open_employeeid;
        }
        if(strtolower($userdata->email) != $userdata->email){
            $userdata->email = strtolower($userdata->email);
        }
        if($userdata->open_supervisorid){
            $userdata->open_supervisorempid = $DB->get_field('user', 'open_employeeid', array('id' => $userdata->open_supervisorid));
        }
        if($userdata->open_univdept_status == 1){
            $userdata->open_departmentid = $userdata->open_collegeid;
        }else{
            $userdata->open_departmentid = $userdata->open_departmentid;
        }
        $userdata->password = hash_internal_user_password($userdata->password);
        $data = user_create_user($userdata, false);
        // for assigning user in site level//
        if(!empty($userdata->open_role)){
            $userid = $data;
            $roleid = $userdata->open_role;
            if($userdata->open_role == 5){
                if(!user_has_role_assignment($userid,$roleid)){
                    role_assign($roleid, $userid, $systemcontext->id);
                }
            }else{
                list($assignableroles, $assigncounts, $nameswithcounts) = local_get_assignable_roles($systemcontext, ROLENAME_BOTH, true);
                foreach($assignableroles as $key => $val){
                    if(user_has_role_assignment($userid,$key)){
                        role_unassign($key, $userid, $systemcontext->id);
                    }
                }
                if($userdata->open_role != 5){
                    if(!user_has_role_assignment($userid,$roleid)){
                        role_assign($roleid, $userid, $systemcontext->id);
                    }
                }
            }
        }
        return $data;
    } //End of insert_newuser function.

    /**
     * [update_existinguser description]
     * @param  [object] $data 
     * @return [int] success or failure.
     */
    public function update_existinguser($data) {
        global $DB, $USER, $CFG;
        $systemcontext = context_system::instance();
        $userdata = (object) $data;
        if(empty($userdata->password)){
            unset($userdata->password);
        }else{
            $userdata->password = hash_internal_user_password($userdata->password);
        }
     /*   if(!empty($userdata->password)){


$userdata->password = hash_internal_user_password($userdata->password);

}*/
        foreach($userdata as $key => $value){
            $userdata->$key = trim($value);
        }
        $usercontext = context_user::instance($userdata->id);
        if(strtolower($userdata->email) != $userdata->email){
            $userdata->email = strtolower($userdata->email);
        }
        if($userdata){
            if($userdata->open_supervisorid){
                $userdata->open_supervisorempid = $DB->get_field('user', 'open_employeeid', array('id' => $userdata->open_supervisorid));
            }
            if($userdata->imagefile){
                $editoroptions = array(
                    'maxfiles'   => EDITOR_UNLIMITED_FILES,
                    'maxbytes'   => $CFG->maxbytes,
                    'trusttext'  => false,
                    'forcehttps' => false,
                    'context'    => $usercontext
                );
                $userdata = file_postupdate_standard_editor($userdata, 'description', $editoroptions, $usercontext, 'user', 'profile', 0);
            }
            $userdata->deleted = 0;
            $userdata->descriptionformat = 1;
            if($userdata->open_employeeid){
                $userdata->idnumber = $userdata->open_employeeid;
            }
            $roleid = $DB->get_field('role','id',array('shortname' => 'student'));
            if($userdata->open_role != $roleid){
            if($userdata->form_status == 0){
                if($userdata->open_univdept_status == 1){
                    $userdata->open_departmentid = $userdata->open_collegeid;
                    unset($userdata->open_collegeid);
                }else{
                    $userdata->open_departmentid = $userdata->open_departmentid;
                }
            }
            }
           
            $result = user_update_user($userdata, false);

            // Commented by Harish //
            /*if(!empty($userdata->open_role)){
                    $userid = $userdata->id;
                    $roleid = $userdata->open_role;
                    list($assignableroles, $assigncounts, $nameswithcounts) = local_get_assignable_roles($systemcontext, ROLENAME_BOTH, true);
                    foreach($assignableroles as $key => $val){
                        if(user_has_role_assignment($userid, $key)){
                            role_unassign( $key, $userid, $systemcontext->id);
                        }
                    }
                     if($userdata->open_roleid != 5){
                        if(!user_has_role_assignment($userid,$roleid)){
                            role_assign($roleid, $userid, $systemcontext->id);
                        }
                     }
                }*/
            
            $filemanagercontext = $usercontext;
            $filemanageroptions = array('maxbytes'       => $CFG->maxbytes,
                                        'subdirs'        => 0,
                                        'maxfiles'       => 1,
                                        'accepted_types' => 'web_image');
            core_user::update_picture($userdata, $filemanageroptions);
        }
        // added for updating session variable $USER if updated the current user.
        if($userdata->id){
            $user = $DB->get_record('user', array('id' => $userdata->id), '*', MUST_EXIST);
            if ($USER->id == $user->id) {
                // Override old $USER session variable if needed.
                foreach ((array)$user as $variable => $value) {
                    if ($variable === 'description' or $variable === 'password') {
                        // These are not set for security nad perf reasons.
                        continue;
                    }
                    $USER->$variable = $value;
                }
                // Preload custom fields.
                profile_load_custom_fields($USER);
            }
        }
        // added for updating session variable $USER if updated the current user ends here.
        return $userdata->id;
    } //End of update_existinguser function.

    
    /**
     * [update_existinguserprofile description]
     * @param  [object] $data 
     * @return [int] success or failure.
     */
    public function update_existinguserprofile($data) {
        global $DB, $USER, $CFG;
        $systemcontext = context_system::instance();
        $userdata = (object) $data;
        if(empty($userdata->password)){
            unset($userdata->password);
        }else{
            $userdata->password = hash_internal_user_password($userdata->password);
        }
        foreach($userdata as $key => $value){
            $userdata->$key = trim($value);
        }
        $usercontext = context_user::instance($userdata->id);
        if(strtolower($userdata->email) != $userdata->email){
            $userdata->email = strtolower($userdata->email);
        }
        if($userdata){
            if($userdata->imagefile){
                $editoroptions = array(
                    'maxfiles'   => EDITOR_UNLIMITED_FILES,
                    'maxbytes'   => $CFG->maxbytes,
                    'trusttext'  => false,
                    'forcehttps' => false,
                    'context'    => $usercontext
                );
                $userdata = file_postupdate_standard_editor($userdata, 'description', $editoroptions, $usercontext, 'user', 'profile', 0);
            }
            $userdata->deleted = 0;
            $userdata->descriptionformat = 1;
            if($userdata->open_employeeid){
                $userdata->idnumber = $userdata->open_employeeid;
            }
            $result = user_update_user($userdata, false);
            
            $filemanagercontext = $usercontext;
            $filemanageroptions = array('maxbytes'       => $CFG->maxbytes,
                                        'subdirs'        => 0,
                                        'maxfiles'       => 1,
                                        'accepted_types' => 'web_image');
            core_user::update_picture($userdata, $filemanageroptions);
        }
        // added for updating session variable $USER if updated the current user.
        if($userdata->id){
            $user = $DB->get_record('user', array('id' => $userdata->id), '*', MUST_EXIST);
            if ($USER->id == $user->id) {
                // Override old $USER session variable if needed.
                foreach ((array)$user as $variable => $value) {
                    if ($variable === 'description' or $variable === 'password') {
                        // These are not set for security nad perf reasons.
                        continue;
                    }
                    $USER->$variable = $value;
                }
                // Preload custom fields.
                profile_load_custom_fields($USER);
            }
        }
        // added for updating session variable $USER if updated the current user ends here.
        return $userdata->id;
    } //End of update_existinguser function.


    /**
     * [get_all_users description]
     * @return [object] users data
     */
    function get_all_users() {
        global $DB, $CFG;
        return $DB->get_records_sql("SELECT * FROM {user} WHERE id <> {$CFG->siteguest} AND deleted = 0");
    }

    /* Get all grades*/
    /**
     * [get_all_grades description]
     * @return [array] data of user.
     */
    public function get_all_grades() {
        global $DB, $CFG;
        $userdata = $DB->get_records_sql("select distinct(grade) from {user}");
        $grades = array();
        $grades[null] = get_string('select_grade','local_users');
        foreach($userdata as $data){
            
            $grades[] = $data->grade;
            
        }
        $userdata = array_unique($grades);
        $a = array_combine($userdata, $userdata);
        return $a;
    }
    
    /* Get all career*/
    /**
     * [get_all_career_track description]
     * @return [array] career track info
     */
      function get_all_career_track() {
        global $DB, $CFG;
        $user_career_data = $DB->get_records_sql("select distinct(career_track_tag) from {user} where career_track_tag != '' ");
        $careers = array();
          $careers[null] = get_string('select_career','local_users');
        foreach($user_career_data as $career){
            
            $careers[] = $career->career_track_tag;
            
        }
        $career_tagdata = array_unique($careers);
        $career_output = array_combine($career_tagdata, $career_tagdata);
        return $career_output;
    }
    
    /* To get rolename for logged in user */

    function get_rolename($userid) {
        global $DB;
        return $DB->get_field_sql("SELECT r.name FROM {role_assignments} ra 
                                     JOIN {role} r ON r.id = ra.roleid 
                                     WHERE ra.userid = $userid limit 1");
        /*return $DB->get_field_sql("SELECT r.shortname FROM {role_assignments} ra, {role} r WHERE ra.userid = {$userid} AND r.id = ra.roleid limit 1");*/
    }

    /**
     * @method names
     * @todo to get the names of hierarchy elements
     * @param object $data   
     * @return array, names info
     */
    function names($data) {
        global $DB, $CFG;
        $list = new \stdClass();
        if (isset($data->open_costcenterid)) {
            $list->costcenter = $DB->get_field('local_costcenter', 'fullname', array('id' => $data->open_costcenterid));
        }
        if (isset($data->programid)) {
            $list->program = $DB->get_field('local_program', 'fullname', array('id' => $data->programid));
        }
        if (isset($data->curriculumid)) {
            $list->curriculum = $DB->get_field('local_curriculum', 'fullname', array('id' => $data->curriculumid));
        }
        if (isset($data->courseid)) {
            $course = $DB->get_record('local_cobaltcourses', array('id' => $data->courseid));
            $list->coursename = $course->fullname;
            $list->courseid = $course->shortname;
        }
        return $list;
    }   //End of names function.

    /**
     * @method get_coursestatus
     * @param int $courseid Course ID
     * @param int $userid User ID
     * @param $sem Semester
     * @todo To check the status of course for a particular user
     */
    function get_coursestatus($courseid, $userid, $sem = false) {
        global $DB, $CFG;
        $status = 'Not Enrolled';
        $rejected = $DB->get_record_sql("SELECT cls.* FROM {local_user_classes} AS uc JOIN {local_classes} AS cls ON cls.id = uc.classid AND cls.cobaltcourseid = {$courseid} AND uc.userid = {$userid} AND uc.registrarapproval = 2");
        if (!empty($rejected)) {
            $status = 'Rejected';
        }
        $sql = "SELECT cls.* FROM {local_user_classes} AS uc JOIN {local_classes} AS cls ON cls.id = uc.classid AND cls.cobaltcourseid = {$courseid} AND uc.userid = {$userid} AND uc.registrarapproval = 1";
        $enrolled = $DB->get_record_sql($sql);
        if (!empty($enrolled)) {
            if ($sem)
                return $DB->get_field('local_semester', 'fullname', array('id' => $enrolled->semesterid));
            $status = 'Enrolled (Inprogress)';
            $completed = $DB->get_record_sql("SELECT * FROM {local_user_classgrades} WHERE userid = {$userid} AND classid = {$enrolled->id}");
            if (!empty($completed)) {
                $status = 'Completed (With grade ' . $completed->gradeletter . ')';
            }
        }
        return $status;
    }   //End of get_coursestatus function.

    /* To delete user */

    function cobalt_delete_user($userid) {
        global $DB;
        $DB->set_field('user', 'deleted', 1, array('id' => $userid));
        return true;
    }

    /* Action icons */

    function get_different_actions($plugin, $page, $id, $visible) {
        global $DB, $USER, $OUTPUT;
        $context = context_system::instance();
        $role = $this->get_rolename($id);
        if ($id == $USER->id) {
            // return html_writer::link(new moodle_url('/local/' . $plugin . '/' . $page . '.php', array('id' => $id, 'sesskey' => sesskey())), html_writer::empty_tag('img', array('src' => $OUTPUT->image_url('t/edit'), 'alt' => get_string('edit'), 'class' => 'iconsmall')));
            return html_writer::link('javascript:void(0)', '<i class="fa fa-cog fa-fw" title=""></i>', array('data-action' => 'createusermodal', 'class'=>'createusermodal', 'data-value'=>$id, 'class' => '', 'onclick' =>'(function(e){ require("local_users/newuser").init({selector:"createusermodal", context:'.$context->id.', id:'.$id.', form_status:0}) })(event)','style'=>'cursor:pointer' , 'title' => 'edit'));
        } else if (is_siteadmin($id)) {
            return '';
        } else {
            $userobject = $DB->get_record('user' , array('id' => $id));
            $fullname = fullname($userobject);
            $buttons = array();
            if ($visible) {
                $buttons[] = '<button class="btn btn_active_user">Active</button>';
            }else{
                $buttons[] = '<button class="btn btn_inactive_user">Inactive</button>';
            }
            if(is_siteadmin() || has_capability('local/users:delete',$context)){
                $buttons[] = html_writer::link('javascript:void(0)', '<i class="fa fa-trash fa-fw" aria-hidden="true" title="" aria-label="Delete"></i>', array('title' => get_string('delete'), 'onclick' => '(function(e){ require("local_users/newuser").deleteConfirm({ action: "delete_user" ,id:'.$id.',context:'.$context->id.', fullname:"'.$fullname.'"}) })(event)'));
            }
            if(is_siteadmin() || has_capability('local/users:edit', $context)){
                $buttons[] = html_writer::link('javascript:void(0)', '<i class="fa fa-cog fa-fw" title=""></i>', array('data-action' => 'createusermodal', 'class'=>'createusermodal', 'data-value'=>$id, 'class' => '', 'onclick' =>'(function(e){ require("local_users/newuser").init({selector:"createusermodal", context:'.$context->id.', id:'.$id.', form_status:0}) })(event)','style'=>'cursor:pointer' , 'title' => get_string('edit')));
            }
            // sending parameters for visible as  1 and not visible as 0 by defalut for  OL11
            if(is_siteadmin() || has_capability('local/users:edit',$context)){
                if ($visible) {
                    $buttons[] = html_writer::link('javascript:void(0)', '<i class="fa fa-eye fa-fw " aria-hidden="true" aria-label="Hide"></i>', array('title' => 'Disable', 'onclick' => '(function(e){ require("local_users/newuser").userSuspend({ id:'.$id.',context:'.$context->id.', fullname:"'.$fullname.'"}) })(event)'));
                } else {
                    $buttons[] = html_writer::link('javascript:void(0)', '<i class="fa fa-eye-slash fa-fw " aria-hidden="true" title="" aria-label="Show"></i>', array('title' => 'Enable' , 'onclick' => '(function(e){ require("local_users/newuser").userSuspend({ id:'.$id.',context:'.$context->id.', fullname:"'.$fullname.'"}) })(event)'));
                }
            }
            // OL11 ends here .
            return implode('', $buttons);
        }
    }   //End of get_different_actions function.

    /**
     * @method createtabview
     * @todo provides the tab view
     * @param  currenttab(string)
     * */
    function createtabview($mode, $id = -1) {
        global $OUTPUT;
        $tabs = array();
        $systemcontext = context_system::instance();
        $string = ($id > 0) ? get_string('edituser', 'local_users') : get_string('createuser', 'local_users');
        if (has_capability('local/users:manage', $systemcontext))
            $tabs[] = new tabobject('addnew', new moodle_url('/local/users/user.php'), $string);
        $tabs[] = new tabobject('browse', new moodle_url('/local/users/index.php'), get_string('browseusers', 'local_users'));
        echo $OUTPUT->tabtree($tabs, $mode);
    }

    /**
     * @method get_costcenternames
     * @todo to get costcenter name based on role(admin, registrar)
     * @param object $user user detail
     * @param type $user
     * @return string, costcenter fullname else valid statement based on condition
     */
    function get_costcenternames($user) {
        global $DB;
        $role = $this->get_rolename($user->id);
        $systemcontext = context_system::instance();
        if (is_siteadmin($user->id) || has_capability('local/costcenter:manage_multiorganizations', $systemcontext)) {
            return 'All';
        }
        $table = 'local_costcenter_permissions';
        $field = 'userid';
        if ( $role != 'manager') {
            $table = 'user';
            $field = 'id';
        }
        $costcenters = $DB->get_records_sql("SELECT * FROM {{$table}} WHERE {$field} = {$user->id}");
        $scl = array();
        if ($costcenters) {
            foreach ($costcenters as $costcenter) {
                $scl[] = $DB->get_field('local_costcenter', 'fullname', array('id' => $costcenter->open_costcenterid));
            }
            return implode(', ', $scl);
        }
        return get_string('not_assigned', 'local_users');
    }

    /**
     * @method email_to_user
     * @todo To send a mail to users
     * @param object $data User data
     * @param int $id To check new user or existing
     */
    function email_to_user($data, $id) {
        global $DB, $CFG;
        $costcenter = $DB->get_field('local_costcenter', 'fullname', array('id' => $data->open_costcenterid));
        @$role = $DB->get_field('role', 'name', array('id' => $data->roleid));
        $url = $CFG->wwwroot;
        $email = $data->email;
        $from = 'registrar@cobaltlms.com';
        $subject = 'Appointment Confirmation';
        $body = 'Congratulations! You are appointed as "' . $role . '", for the department: "' . $costcenter . '".<br/>
                    Username: "' . $data->username . '".<br/>
                    Password: "' . $data->password . '".<br/>
                    Please login to your account with following URL: "' . $url . '"';
        if ($id > 0) {
            $subject = 'New Login Credentials';
            $body = 'Hi! <p>Your Login details for the site "' . $CFG->wwwroot . '" are changed. Please use new Credentials for login.</p>
                    Username: "' . $data->username . '".<br/>
                    Password: "' . $data->password . '".<br/>';
        }
        $headers = "MIME-Version: 1.0" . "\r\n";
        $headers .= "Content-type:text/html;charset=iso-8859-1" . "\r\n";
        $headers .= 'From: ' . $from . ' ' . "\r\n";
        mail($email, $subject, $body, $headers);
    }   //End of email_to_user function.

    /**
     * @method get_usercount
     * @todo To get total number of cobaltusers 
     * @param string $extraselect used to add extra condition to get userlist
     * @param array $extraparams it holds values
     * @return int user count
     */
    function get_usercount($extraselect = '', array $extraparams = null) {
        return 10;
    }   //End of get_usercount function.

    /**
     * @method get_users_listing
     * @todo to get user list of costcenter based on condition  
     * @param string $sort fieldname
     * @param string $dir specify the order to sort
     * @param int $page page number
     * @param int $recordsperpage records perpage
     * @param string $extraselect extra condition to select user
     * @param array $extraparams
     * @return array of objects , list of users
     */
    function get_users_listing($sort = 'lastaccess', $dir = 'ASC', $page = 0, $recordsperpage = 0, $extraselect = '', array $extraparams = null, $extracontext = null) {
        global $DB, $CFG,$USER;
        $extraselect;

        $select = "u.deleted <> 1 AND u.id <> :guestid";  //$select = "deleted=0";
        // $select = "u.deleted <> 1 AND u.id <> $CFG->siteguest"; 
        $params = array('guestid' => $CFG->siteguest);

        if ($extraselect) {
            $select .= " AND $extraselect";
            $params = $params + (array) $extraparams;
        }

        // If a context is specified, get extra user fields that the current user
        // is supposed to see.
        $extrafields = '';
        if ($extracontext) {
            $extrafields = get_extra_user_fields_sql($extracontext, '', '', array('id', 'username', 'email', 'firstname', 'lastname', 'city', 'country',
                'lastaccess', 'confirmed', 'mnethostid'));
        }
        /*
         * ###Bugreport#183-Filters
         * (Resolved) Added $select parameters for conditions 
         */
        // warning: will return UNCONFIRMED USERS
        return $DB->get_records_sql("SELECT u.*
                   FROM {user} as u $join WHERE $select GROUP BY id ORDER BY $sort $dir LIMIT $page, $recordsperpage", $params);
    }

}//End of users class.

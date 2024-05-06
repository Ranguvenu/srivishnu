<?php
namespace local_users\local;
class user{
	public function user_profile_content($id,$return = false,$start =0,$limit=5){
        global $OUTPUT,$PAGE,$CFG,$DB;
        require_once($CFG->dirroot.'/course/renderer.php');
        require_once($CFG->libdir . '/badgeslib.php');

        $returnobj = new \stdClass();
        $returnobj->divid = 'user_profile';
        $returnobj->string = get_string('profile', 'local_users');
        $returnobj->moduletype = 'users';
        $returnobj->targetID = 'display_users';
        $returnobj->userid = $id;
        $returnobj->count = 1;
        $returnobj->usersexist = 1;
        
        $systemcontext = \context_system::instance();
        $userrecord = $DB->get_record('user', array('id' => $id));
        /*user image*/
        //$user_image = $OUTPUT->user_picture($userrecord, array('size' => 120, 'link' => false));

        /*user roles*/
        $userroles = get_user_roles($systemcontext, $id);
        if(!empty($userroles)){
                $rolename  = array();
                foreach($userroles as $roles) {
                    $rolename[] = ucfirst($roles->name);
                }
                $roleinfo = implode(", ",$rolename);
        } else {
            $roleinfo = "Employee";
        }
        $sql3 = "SELECT cc.fullname, u.open_employeeid,u.open_costcenterid,
                    u.open_designation, u.open_location,
                    u.open_supervisorid, u.open_group,
                    u.department, u.open_subdepartment ,u.open_departmentid                         
                    FROM {local_costcenter} cc, {user} u
                    WHERE u.id=:id AND u.open_costcenterid=cc.id";
        $userOrg = $DB->get_record_sql($sql3, array('id' => $id));
        $usercostcenter = $DB->get_field('local_costcenter', 'fullname',  array('id' => $userOrg->open_costcenterid));
        $userdepartment = $DB->get_field('local_costcenter', 'fullname',  array('id' => $userOrg->open_departmentid));
        if(!empty($userrecord->phone1)){
                $contact = $userrecord->phone1;
        }else{
                $contact = 'N/A';
        }
        if(!empty($userOrg->open_supervisorid)){
            $get_reporting_username_sql = "SELECT u.id, u.firstname, u.lastname, u.open_employeeid FROM {user} as u WHERE  u.id= :open_supervisorid";
                $get_reporting_username = $DB->get_record_sql($get_reporting_username_sql , array('open_supervisorid' => $userOrg->open_supervisorid));
                $reporting_to_empid = $get_reporting_username->serviceid != NULL ? ' ('.$get_reporting_username->open_employeeid.')' : 'N/A';
                $reporting_username = $get_reporting_username->firstname.' '.$get_reporting_username->lastname/*.$reporting_to_empid*/;
        }else{
                $reporting_username = 'N/A';
        }

        $supervisorname = $get_reporting_username->firstname.' '.$get_reporting_username->lastname;
        $badgeimage = $OUTPUT->image_url('badgeicon','local_users');
        $badgimg = $badgeimage->out_as_local_url(); 

        $certiconimage = $OUTPUT->image_url('certicon','local_users');
        $certimg = $certiconimage->out_as_local_url(); 
        $usersviewContext = [
            "userid" => $userrecord->id,
            "username" => fullname($userrecord),
            "rolename" => $roleinfo,
            "empid" => $userOrg->open_employeeid != NULL ? $userOrg->open_employeeid : 'N/A',
            "user_email" => $userrecord->email,
            "organisation" => $usercostcenter ? $usercostcenter : 'N/A',
            "department" => $userdepartment ? $userdepartment : 'N/A',
            "location" => $userrecord->city != NULL ? $userrecord->city : 'N/A',
            "address" => $userrecord->address != NULL ? $userrecord->address : 'N/A',
            "phnumber" => $contact,
            "badgesimg" => $badgimg,
            "certimg" => $certimg,
            "supervisorname" => $reporting_username,
        ];

        //print_object($usersviewContext);
        $data = array();
        $data[] = $usersviewContext;
        $returnobj->navdata = $data;
        
        return $returnobj;
	}

}
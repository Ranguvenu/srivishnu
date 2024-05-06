<?php
class local_users_renderer extends plugin_renderer_base {   
    /**
     * Description: Employees profile view in profile.php
     * @param  [int] $id [user id whose profile is viewed]
     * @return [HTML]     [user profile page content]
     */
	public function employees_profile_view($id) {
		global $CFG, $OUTPUT, $DB, $PAGE, $USER;
		require_once($CFG->dirroot.'/course/renderer.php');
		require_once($CFG->libdir . '/badgeslib.php');

        $corecomponent = new core_component();

		$systemcontext = context_system::instance();
		$userrecord = $DB->get_record('user', array('id' => $id));
		/*user image*/
		$user_image = $OUTPUT->user_picture($userrecord, array('size' => 140, 'link' => false));
		
		/*user roles*/
		$userroles = get_user_roles($systemcontext, $id);
		if(!empty($userroles)){
				$rolename  = array();
				foreach($userroles as $roles) {
					$rolename[] = ucfirst($roles->name);
				}
				$roleinfo = implode(", ",$rolename);
		} else {
			$roleinfo = "Student";
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

        $usercontent = new stdClass();
        $core_component = new core_component();
        $local_pluginlist = $core_component::get_plugin_list('local');
        $existingplugin = array();
        $usercontent = array();
        $navigationdata = '';
        foreach($local_pluginlist AS $pluginname => $pluginurl){
            $userclass = '\local_'.$pluginname.'\local\user';
            if(class_exists($userclass)){
                $plugininfo = array();
                $pluginclass = new $userclass;
                if(method_exists($userclass, 'user_profile_content')){
                    $plugindata = $pluginclass->user_profile_content($id,true);
                    $usercontent[] = $plugindata;
                    $plugininfo['userenrolledcount'] = $plugindata->count;
                    // $plugininfo['string'] = get_string($pluginname, 'local_'.$pluginname);
                    $plugininfo['string'] = $plugindata->string;
                    if($pluginname != 'users'){
                        $existingplugin[$plugindata->sequence] = $plugininfo;
                    }
                    
                    $navigationdata .= $plugindata->navdata;
                }
            }
        }

        ksort($existingplugin);
     	
       //added by sarath for tabs dispalying
        $core_component = new core_component();
        $plugins = $core_component::get_plugin_list('local');
        $pluginarray = array();

        foreach ($plugins as $key => $valuedata) {
            $userclass = '\local_'.$key.'\local\user';
            if(class_exists($userclass)){
                $pluginclass = new $userclass;
                if(method_exists($userclass, 'user_profile_content')){
                    $pluginarray[$key] = true;
                }
            }
        }

            
        if(is_siteadmin() || has_capability('local/users:edit',$systemcontext)){
            $capabilityedit = 1;
        }else{
            $capabilityedit = 0;
        }
        if(has_capability('moodle/user:loginas', $systemcontext)){
            $loginasurl = new moodle_url('/course/loginas.php', array('id'=> 1, 'user' => $userrecord->id, 'sesskey' => sesskey()));
        }else{
            $loginasurl = false;
        }
        $options = array('targetID' => 'display_modulesdata');

		$supervisorname = $get_reporting_username->firstname.' '.$get_reporting_username->lastname;
// <mallikarjun> - ODL-786 hide department for University head -- starts
                if($roleinfo != 'University Head'){
                    $dptdispay = true;
                }
// <mallikarjun> - ODL-786 hide department for University head -- ends
		$usersviewContext = [
			"userid" => $userrecord->id,
			"username" => fullname($userrecord),
			"userimage" => $user_image,
			"rolename" => $roleinfo,
			"empid" => $userOrg->open_employeeid != NULL ? $userOrg->open_employeeid : 'N/A',
			"user_email" => $userrecord->email,
			"education" => $education,
			"organisation" => $usercostcenter ? $usercostcenter : 'N/A',
			"department" => $userdepartment ? $userdepartment : 'N/A',
			"location" => $userrecord->city != NULL ? $userrecord->city : 'N/A',
			"address" => $userrecord->address != NULL ? $userrecord->address : 'N/A',
			"phnumber" => $contact,
			"badgesimg" => $OUTPUT->image_url('badgeicon','local_users'),
			"certimg" => $OUTPUT->image_url('certicon','local_users'),
            'navigationdata' => $navigationdata,
            "usercontent" => $usercontent, 
            "existingplugin" => $existingplugin,
            "coursescount" => $existingplugin[0]['userenrolledcount'] ? $existingplugin[0]['userenrolledcount'] : 0,
            "programscount" => $existingplugin[1]['userenrolledcount'] ? $existingplugin[1]['userenrolledcount'] : 0,
			"editprofile" => new moodle_url("/user/editadvanced.php", array('id' => $userrecord->id, 'returnto' => 'profile')),
			"messagesurl" => new moodle_url("/message/index.php"),
			"prflbgimageurl" => $OUTPUT->image_url('prflbg', 'local_users'),
			"supervisorname" => $reporting_username,
            "capabilityedit" => $capabilityedit,
            "loginasurl" => $loginasurl,
            "open_univdept_status" => $userrecord->open_univdept_status,
            "options" => $options,
            "pluginslist" => $pluginarray,
            "open_employee" => $userrecord->open_employee,
            "dptdispay" => $dptdispay
		];
    	return $this->render_from_template('local_users/profile', $usersviewContext);
	}

	function display_userinformatiom($filterdata, $page, $perpage,$filterval=''){
		global $DB, $CFG, $OUTPUT, $USER, $PAGE;
		$filterjson = json_encode($filterdata);
		$PAGE->requires->js_call_amd('local_users/datatablesamd', 'userTableDatatable', array('filterdata'=> $filterjson));
        $table = new html_table();
        $table->id = "manage_users1";

        $table->head = array(get_string('name', 'local_users'),get_string('employeeid', 'local_users'),get_string('email', 'local_users'),get_string('organization', 'local_users'),get_string('role'), get_string('departmentcollege', 'local_users'),get_string('actions', 'local_users'));

        $table->align = array('center','left', 'center', 'left', 'left', 'left','center');
        $table->size = array('10%','13%', '10%', '16%', '20%', '14%','17%');


        $output = '<div class="w-full pull-left">'. html_writer::table($table).'</div>';
        return $output;
	}

    function display_userinformatiom_count($filterdata){
        global $DB, $CFG, $OUTPUT, $USER, $PAGE;
        $systemcontext = context_system::instance();
        
        $countsql = "SELECT  count(u.id) FROM {user} AS u WHERE u.id > 2 AND u.deleted = 0 ";
        if(is_siteadmin() || has_capability('local/costcenter:manage_multiorganizations',$systemcontext)){
            $countsql .= " ";
        }else if(has_capability('local/costcenter:manage_ownorganization',$systemcontext)){
            $countsql .= " AND open_costcenterid = $USER->open_costcenterid ";
        }else if(has_capability('local/costcenter:manage_owndepartments',$systemcontext)){
            $countsql .= " AND open_costcenterid = $USER->open_costcenterid AND open_departmentid = $USER->open_departmentid";
        }else{
            $countsql .= " AND open_costcenterid = $USER->open_costcenterid AND open_departmentid = $USER->open_departmentid";
        }
        $activesql = " AND suspended =0 ";
        $inactivesql = " AND suspended =1 ";
        $formsql = '';
        
        if(!empty($filterdata->email) || !empty($filterdata->idnumber) || !empty($filterdata->designation) || !empty($filterdata->location) || !empty($filterdata->band) ){
            $lsemail = implode(',',$filterdata->email);
            $lsidnum = implode(',',$filterdata->idnumber);
            $lsdesignation = implode(',',$filterdata->designation);
            $lslocation = implode(',',$filterdata->location);
            $lsband = implode(',',$filterdata->band);
            if(!empty($lsemail)){
                $formsql .= " AND u.id IN ($lsemail)";
            }
            if(!empty($lsidnum)){
                $formsql .= " AND u.id IN ($lsidnum)";
            }
            if(!empty($lsdesignation)){
                $formsql .= " AND u.id IN ($lsdesignation)";
            }
            if(!empty($lslocation)){
                $formsql .= " AND u.id IN ($lslocation)";
            }
            if(!empty($lsband)){
                $formsql .= " AND u.id IN ($lsband)";
            }
        }

        if(!empty($filterdata->organizations)){
            $organizations = implode(',',$filterdata->organizations);
            $formsql .= " AND u.open_costcenterid IN ($organizations)";
        }
        if(!empty($filterdata->departments)){
            $departments = implode(',',$filterdata->departments);
            $formsql .= " AND u.open_departmentid IN ($departments)";
        }
        $count_users = $DB->count_records_sql($countsql.$formsql);
        $count_activeusers = $DB->count_records_sql($countsql.$activesql.$formsql);
        $count_inactiveusers = $DB->count_records_sql($countsql.$inactivesql.$formsql);
    
        $output = '<div class="customcount">
                                    <ul class="dashboard_count_list w-full pull-left p-0 m-0">
                                        <li class="dashbaord_count_item"><span class="">
                                            <span class="d-block dashboard_count_string">Total</span><span class="dashboard_count_value">'.$count_users.'</span></span>
                                        </li>
                                        <li class="dashbaord_count_item"><span class="">
                                            <span class="d-block dashboard_count_string">Active</span><span class="dashboard_count_value">'.$count_activeusers.'</span></span>
                                        </li>
                                        <li class="dashbaord_count_item"><span class="">
                                            <span class="d-block dashboard_count_string">In Active</span><span class="dashboard_count_value">'.$count_inactiveusers.'</span></span>
                                        </li>
                                    </ul></div>';
        return $output;
    }

    /**
     * [user_page_top_action_buttons description]
     * @return [html] [top action buttons content]
     */
	public function user_page_top_action_buttons(){
		global $CFG;
		$systemcontext = context_system::instance();
		$output = "";
        $output .= "<ul class='course_extended_menu_list'>";
        if(is_siteadmin() || has_capability('local/costcenter:manage_multiorganizations',$systemcontext) || has_capability('local/costcenter:manage_ownorganization',$systemcontext)){
            $output .= "<li>
                 <div class='coursebackup course_extended_menu_itemcontainer'>
                        <a id='extended_menu_syncusers' title='".get_string('bulkuploadusers', 'local_users')."' class='course_extended_menu_itemlink' href='" . $CFG->wwwroot ."/local/users/sync/hrms_async.php'><i class='icon fa fa-users fa-fw' aria-hidden='true' aria-label=''></i></a>
                                    </div>
                                </li>";
            $output .= "<li>
                        <div class='coursebackup course_extended_menu_itemcontainer'>
                            <a id='extended_menu_createusers' title='".get_string('createuser', 'local_users')."' class='course_extended_menu_itemlink' data-action='createusermodal' onclick ='(function(e){ require(\"local_users/newuser\").init({selector:\"createusermodal\", context:$systemcontext->id, userid:0, form_status:0,employee:2}) })(event)' ><i class='icon fa fa-user-plus' aria-hidden='true'></i></a>
                        </div>
                    </li>";
                }
            $output .= "<li>
                    <div class='coursebackup course_extended_menu_itemcontainer'>
                        <a id='extended_menu_createusers' title='".get_string('adnewemployee', 'local_users')."' class='course_extended_menu_itemlink' data-action='createusermodal' onclick ='(function(e){ require(\"local_users/newuser\").init({selector:\"createusermodal\", context:$systemcontext->id, userid:0, form_status:0,employee:1}) })(event)' ><i class='icon fa fa-user-o' aria-hidden='true'></i></a>
                        
                    </div>
                    </li>";
            if(is_siteadmin() || has_capability('local/costcenter:manage_multiorganizations',$systemcontext)){            
                $output .= "<li>
                            <div class='coursebackup course_extended_menu_itemcontainer'>
                                <a id='extended_menu_createusers' title='".get_string('createuniversityhead', 'local_users')."' class='course_extended_menu_itemlink' data-action='createusermodal' onclick ='(function(e){ require(\"local_users/newuser\").init({selector:\"createusermodal\", context:$systemcontext->id, userid:0, form_status:0,employee:3}) })(event)' ><i class='icon fa fa-user-circle-o' aria-hidden='true'></i></a>
                            </div>
                        </li>";
            }
           $output .= "</ul>";
           echo $output;
	}
    /**
     * Description: User Course completion progress
     * @param  INT $courseid course id whose completed percentage to be fetched
     * @param  INT $userid   userid whose completed course prcentage to be fetched
     * @return INT           percentage of completion.
     */
	public function user_course_completion_progress($courseid, $userid) {
        global $DB, $USER, $CFG;
        if(empty($courseid) || empty($userid)){
            return false;
        }
        
        $sql="SELECT id from {course_completions} where course=$courseid and userid=$userid and  timecompleted IS NOT NULL";
        $completionenabled=$DB->get_record_sql($sql);
        if($completionenabled ==''){
        $total_activity_count = $this->total_course_activities($courseid);
        $completed_activity_count = $this->user_course_completed_activities($courseid, $userid);
            if($total_activity_count>0 && $completed_activity_count>0){
            	$course_completion_percent = $completed_activity_count/$total_activity_count*100;
            }
        }else{
            $course_completion_percent=100;
        }
        return $course_completion_percent;
    }

    /**
     * Description: User Course total Activities count
     * @param INT $courseid course id whose total activities count to be fetched
     * @return INT count of total activities
     */
    public function total_course_activities($courseid) {
        global $DB, $USER, $CFG;
        if(empty($courseid)){
            return false;
        }
        $sql="SELECT COUNT(ccc.id) as totalactivities FROM {course_modules} ccc WHERE ccc.course={$courseid}";
        $totalactivitycount = $DB->get_record_sql($sql);
        $out = $totalactivitycount->totalactivities;
        return $out;
    }
    /**
     * Description: User Course Completed Activities count
     * @param  INT $courseid course id whose completed activities count to be fetched
     * @param  INT $userid   userid whose completed activities count to be fetched
     * @return INT           count of completed activities
     */
    public function user_course_completed_activities($courseid, $userid) {
        global $DB, $USER, $CFG;
        if(empty($courseid) || empty($userid)){
            return false;
        }
        $sql="SELECT count(cc.id) as completedact from {course_completion_criteria} ccc JOIN {course_modules_completion} cc ON cc.coursemoduleid = ccc.moduleinstance where ccc.course={$courseid} and cc.userid={$userid} and cc.completionstate=1";
        $completioncount = $DB->get_record_sql($sql);
        $out = $completioncount->completedact;
        return $out;
    }

    /**
     * [render_form_status description]
     * @method render_form_status
     * @param  \local_users\output\form_status $page [description]
     * @return [type]                                    [description]
     */
    public function render_form_status(\local_users\output\form_status $page) {
        $data = $page->export_for_template($this);
        return parent::render_from_template('local_users/form_status', $data);
    }
}

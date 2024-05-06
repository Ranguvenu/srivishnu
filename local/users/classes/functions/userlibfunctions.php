<?php
namespace local_users\functions;
require_once($CFG->dirroot . '/local/costcenter/lib.php');

class userlibfunctions{
	public function generatePassword($level = 4, $length = 10) {
	    $chars[4] = "23456789abcdefghijkmnopqrstuvwxyzABCDEFGHIJKLMNPQRSTUVWXYZ";
	    $name = "STD";
	    $spl = "@";
	    $i = 1;
	    $str = "" . $name . "" . $spl . "" . $i . "";
	    while ($i <= $length) {
	        $str .= $chars[$level][mt_rand(0, strlen($chars[$level]))];
	        $i++;
	    }

	    return $str;
	}


	
	/**
	 * [get_alldepartments description]
	 * @return [array] departments of active users.
	 */
	public function get_alldepartments(){
	    global $DB;
    	$sql = "select * from {user}";
	    $usersdepartment = $DB->get_records_sql($sql);
     	$allusersemails = array();
	    $allusers_emails['-1'] = 'All';
	    if($usersdepartment){
	        foreach($users_department as $users_departments){
	             $allusersemails["$users_departments->department"] =$DB->get_field('local_costcenter','fullname',array('id'=>$users_departments->department));
	        }
	    }
	    return $allusers_emails;
	}
	/**
	 * [get_allsubdepartments description]
	 * @return [array] subdepartments of active users.
	 */
	public function get_allsubdepartments(){
	    global $DB;
	    $users_emails = $DB->get_records('user');
	    $allusers_emails = array();
	    $allusers_emails['-1'] = 'All';
	    if($users_emails){
	        foreach($users_emails as $users_email){
	              $allusers_emails["$users_email->subdepartment"]=$DB->get_field('local_costcenter','fullname',array('id'=>$users_email->subdepartment));
	        }
	    }
	    return $allusers_emails;
	}
	
	// added by anil
	/**
	 * [select_to_manage_users description]
	 * @param  integer $enrolid        
	 * @param  [integer]  $costcenter     
	 * @param  [integer]  $designation   
	 * @param  integer $supervisor 
	 * @param  [integer]  $department   
	 * @param  [integer]  $subdepartment 
	 * @param  [integer]  $sub_sub_department 
	 * @param  [integer]  $band  
	 * @param  [integer]  $category 
	 * @param  integer $role   
	 * @param  integer $userid   
	 * @param  [string]  $email  
	 * @param  [integer]  $idnumber   
	 * @param  [integer]  $requestData 
	 * @param  [integer]  $active  
	 * @param  [integer]  $delete 
	 * @return [object] 
	 */
	public function select_to_manage_users($enrolid=0,$costcenter=null,$designation=null,$supervisor=0,$department=null,$subdepartment=null,$sub_sub_department=null,$band=null,$category=null,$role=0,$userid=0,$email = null,$idnumber,$requestData = null,$active,$delete){
	     global $DB,$USER;
	    $systemcontext = context_system::instance();
	    if(!is_siteadmin() && $userid!=0 && !has_capability('local/costcenter:manage_multiorganizations', $systemcontext)){
	       
	         $userdepartment=$DB->get_record('user',array('id'=>$userid));
	         $sql = "SELECT u.id,u.firstname,u.lastname,u.email,u.open_costcenterid,u.lastaccess,u.suspended  FROM {user} u WHERE u.id >1 AND u.open_costcenterid='".$userdepartment->open_costcenterid."'AND u.deleted!=1";
	          
	    }elseif(is_siteadmin() || has_capability('local/costcenter:manage_multiorganizations', $systemcontext)){
	       
	        $sql = "SELECT  u.id,u.firstname,u.lastname,u.email,u.open_costcenterid,u.lastaccess,u.open_supervisorid,u.suspended  FROM {user} u WHERE u.id >1 AND u.deleted!=1";
	    }
	    if(!empty($costcenter) && $costcenter != null){
	        $departments = implode(',',$costcenter);
	        if($departments !== null && $departments !== '-1'){
	            $sql.= " AND u.open_costcenterid IN($departments)"; 
	        }
	        if($departments =='-1'){
	            $all_departments = $DB->get_records_sql_menu("SELECT id as id_key, id as id_value FROM {local_costcenter}");
	            if($all_departments){
	                $depts = implode(',',$all_departments);
	                $sql.= " AND u.open_costcenterid IN($depts)";
	            }
	             
	        }
	    }
		if(is_array($supervisor)){
	        $supervisor = array_filter($supervisor);
	    }
	    if($supervisor !='_qf__force_multiselect_submission' && $supervisor !== null) {
	        $supervisors= implode(",",$supervisor);
	        if($supervisors !==null && $supervisors !=="-1"){
	            $sql .= " AND ud.supervisorid IN({$supervisors})"; 
	        }
	        if($supervisors =='-1'){
	            $centers=list_supervisors('employee');
	            $allcenters= implode(",",$centers);
	            $sql.= " AND ud.supervisorid IN({$allcenters})"; 
	        }
	    }
	    if(is_array($department)){
	        $department = array_filter($department);
	    }
	      if(!empty($department) && $department !== null){
	       
	        $departments = implode("','",$department);
	        if($departments !== null && $departments !== '-1'){
	            $sql.=" AND u.department IN('{$departments}')";
	        }
	        if($departments =='-1'){
	            $all_emails = $DB->get_records_sql_menu("SELECT distinct(department) as department_key, department as department_value FROM {user}");
	            if($all_emails){
	                $department_1 = implode("','",$all_emails);
	                $sql.= " AND u.department IN('{$department_1}')"; 
	            }
	            
	        }
	    }

	    if(is_array($subdepartment)){
	        $subdepartment = array_filter($subdepartment);
	    }
	      if(!empty($subdepartment) && $subdepartment !== null){
	       
	        $subdepartments = implode("','",$subdepartment);
	        if($subdepartments !== null && $subdepartments !== '-1'){
	            $sql.=" AND ud.subdepartment IN('{$subdepartments}')";
	        }
	        if($subdepartments =='-1'){
	            $all_emails = $DB->get_records_sql_menu("SELECT distinct(open_subdepartment) as subdepartment_key, open_subdepartment as subdepartment_value FROM {user}");
	            if($all_emails){
	                $subdepartment1 = implode("','",$all_emails);
	                $sql.= " AND ud.subdepartment IN('{$subdepartment1}')"; 
	            }
	            
	        }
	    }

	    
	    if(is_array($band)){
	        $band = array_filter($band);
	    }
	    if(!empty($band) && $band !== null){
	       
	        $bands = implode("','",$band);
	        if($bands !== null && $bands !== '-1'){
	            $sql.=" AND u.group IN('{$bands}')";
	        }

	    }    
	    if(is_array($email)){
	        $email = array_filter($email);
	    }
	    if(!empty($email) && $email !== null){
	        
	        $emails = implode(',',$email);
	        if($emails !== null && $emails !== '-1'){
	            $sql.=" AND u.id IN($emails)";
	        }
	        if($emails =='-1'){
	            $all_emails = $DB->get_records_sql_menu("SELECT id as email_key, email as email_value FROM {user}");
	            if($all_emails){
	                $mails = implode(',',$all_emails);
	                 $sql.= " AND u.id IN($mails)"; 
	            }
	            
	        }
	    }
	    
	    
	    if(!empty($idnumber) && $idnumber !== null){
			
	        $idnumbers = implode("','",$idnumber);
	        if($idnumbers !== null && $idnumbers !== '-1'){
	            
	            $sql.=" AND u.idnumber IN('{$idnumbers}')";
	        }
	        if($idnumbers =='-1'){
	            $all_idnumbers = $DB->get_records_sql_menu("SELECT idnumber as idnumber_key, idnumber as idnumber_value FROM {user} where deleted = 0 AND suspended = 0 AND idnumber <> ''");
	            if($all_idnumbers){
	                $employeeids = implode("','",$all_idnumbers);
	                $sql.= " AND u.idnumber IN('{$employeeids}')";
	            }
	            
	        }
	    }
	    
	    if ( $requestData['search'] != "" ){
			$sql .= " and ((CONCAT(u.firstname,' ',u.lastname)  LIKE '%".$requestData['search']."%')
	                    or (u.email LIKE '%".$requestData['search']."%')
	                    or (u.idnumber LIKE '%".$requestData['search']."%'))
	                     ";
		}
		
		if($active == 1){
			$sql .= " and u.suspended=1 or u.deleted=1";
		}
		elseif($active == 2){
			$sql .= " and u.suspended=0";
		}
		if($delete == 1){
			$sql .= " and u.deleted=1";
		}
		elseif($delete == 2){
			$sql .= " and u.deleted=0";
		}
	   
		$sql.= " ORDER BY u.id DESC";

	    $attendees = $DB->get_records_sql($sql);
	    return $attendees;
	}

	public function get_supervisors($name = ''){
	    global $DB;
	    $users = $DB->get_records_sql("SELECT u.id, u.firstname, u.lastname, u.idnumber FROM {user} u WHERE id!= 1 AND id!=2 AND deleted = 0 AND suspended = 0");
	    $options = array();
	    if($name == 'reportingto'){
	        $options[] = get_string('selectreportingmanager','local_users');
	    }else{
	        $options[] = get_string('selectasupervisor','local_users');
	    }
	    foreach($users as $user){    
	       $options[$user->id] = $user->firstname . ' ' .$user->lastname. ' - ' . $user->idnumber;
	    }
	    return $options;
	}
	/*public function find_departments_list($costcenter){
	   
	    global $DB;
	    if($costcenter) {
		    $sql="select id,name from {local_departments} where university = $costcenter";
		    $sub_dep=$DB->get_records_sql($sql);
	      	return $sub_dep;
	  	}else {
	  		return $costcenter;
	  	}
	}*/
	public function find_departments_list($costcenter){
	    global $DB;
	    if($costcenter) {
		    $nonunivdep_sql = "select id,fullname from {local_costcenter} where parentid = $costcenter AND visible = 1 AND univ_dept_status = 1";
		    $nonuniv_dep = $DB->get_records_sql($nonunivdep_sql);

			$univdep_sql = "select id,fullname from {local_costcenter} where parentid = $costcenter AND visible = 1 AND univ_dept_status = 0";
		    $univ_dep = $DB->get_records_sql($univdep_sql);
	      	return $costcenter = ['nonuniv_dep' => $nonuniv_dep, 'univ_dep' => $univ_dep];
	  	}else {
	  		return $costcenter;
	  	}
	}
	function find_departments($costcenter){
        global $DB;
        if($costcenter) {
           
            $univdep_sql = "select id,fullname from {local_costcenter} where parentid = $costcenter AND visible = 1 AND univ_dept_status = 0";
            $univ_dep = $DB->get_records_sql($univdep_sql);
            return $costcenter =  $univ_dep;
        }else {
            return $costcenter;
        }
    }

	

	public function find_universitydepartments_list($costcenter){
		global $DB;
	    if($costcenter) {
		    $sql="select id,fullname from {local_costcenter} where parentid IN ($costcenter) AND visible = 1 AND univ_dept_status = 0";
		    $departs=$DB->get_records_sql($sql);
	      	return $departs;
	  	}else {
	  		return $costcenter;
	  	}
	}
	public function find_subdepartments_list($department){
	    global $DB;
	    $sql="select id,fullname from {local_costcenter} where parentid IN($department)";
	    $sub_dep=$DB->get_records_sql($sql);
	      return $sub_dep;
	}

	public function find_supervisor_list($supervisor,$userid=0){
	    if($supervisor){
	    global $DB;
	    $sql="SELECT u.id,Concat(u.firstname,' ',u.lastname) as username from {user} as u where u.suspended!=1 AND u.deleted!=1 AND u.open_costcenterid= $supervisor AND u.id!= 1 AND u.id!=2";
	    if($userid){
	    	$sql .= " AND u.id != $userid";
	    }
	    $sub_dep=$DB->get_records_sql($sql);
	    
	      return $sub_dep;
	    }
	    
	}
	public function find_dept_supervisor_list($supervisor,$userid=0){
	    if($supervisor){
	    global $DB;
	    $sql="SELECT u.id,Concat(u.firstname,' ',u.lastname) as username from {user} as u where u.suspended!=1 AND u.deleted!=1 AND u.open_departmentid= $supervisor AND u.id!= 1 AND u.id!=2";
	    if($userid){
	    	$sql .= " AND u.id != $userid";
	    }
	    $sub_dep=$DB->get_records_sql($sql);
	    
	      return $sub_dep;
	    }
	    
	}

} //End of userlibfunctions.
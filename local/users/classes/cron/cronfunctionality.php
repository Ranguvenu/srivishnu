<?php
namespace local_users\cron;
require_once($CFG->dirroot.'/user/lib.php');
require_once($CFG->dirroot.'/local/costcenter/lib.php');
use costcenter;
use core_text;
use core_user;
use DateTime;
use html_writer;
define('MANUAL_ENROLL', 1);
define('OAUTH2_ENROLL', 2);
define('SAML2', 3);
define('ADwebservice', 4);
define('ONLY_ADD', 1);
define('ONLY_UPDATE', 2);
define('ADD_UPDATE', 3);
class cronfunctionality{
    

    private $data;
    
    //-------To hold error messages
    private $errors = array();
    
    //----To hold error field name       
    private  $mfields = array();
    
    //-----To hold warning messages----
    private $warnings = array();
    
    //-----To hold warning field names-----
    private $wmfields = array();
    
    private $errormessage;
    
    //-----It hold user field cost center id
    private $costcenterid;

    //-----It hold user field cost center id
    private $roleid;
    
    private $univdeptstatus;
    //-----It will hold the Deparment id
    private $leve1_departmentid;
    
    //----It will hold the Sub_department id
    private $leve2_departmentid;
    
    //---It will holds the status(active or inactive) of the user
    private $activestatus;
    
    //----It will holds the count of inserted record
    private $insertedcount=0;
    
    //----It will holds the count of updated record
    private $updatedcount=0;
    
    
    public $costcenterobj;

    private $errorcount=0;

    private $warningscount=0;

    private $updatesupervisor_warningscount =0;
    
    //---It will holds the costcenter shortname    
    private $costcenter_shortname;

    //-----It holds the unique username    
    private $username;
    
    //----It holds the unique unique id
    private $uniqueid;

    private $department_shortname;

    private $excel_line_number;

    private $contactno;
    
    
    function __construct($data=null){    
        $this->data = $data;
        $this->costcenterobj = new costcenter();
    }// end of constructor
    
    /**BULK UPLOAD FRONTEND METHOD
    * @param  $cir [<csv_import_reader Object >]
    * @param  $[filecolumns] [<colums fields in csv form>]
    * @param array $[formdata] [<data in the csv>]
    * for inserting record in local_userssyncdata.
     **/
    public function  main_hrms_frontendform_method($cir,$filecolumns, $formdata){
           
        global $DB,$USER, $CFG;
        $systemcontext = \context_system::instance();
        $inserted = 0; $updated = 0; 
        $linenum = 1;    
        while($line=$cir->next()){

            $linenum++;
            $user = new \stdClass();
            foreach ($line as $keynum => $value) {
                if (!isset($filecolumns[$keynum])) {
                    // this should not happen
                    continue;
                    }
                $key = $filecolumns[$keynum];
                $user->$key = trim($value);
                 
                }
            $user->username = $user->email;

            $this->data[]=$user;  
            $this->errors = array();
            $this->warnings = array();
            $this->mfields = array();
            $this->wmfields = array();
            $this->excel_line_number = $linenum;
            
            //---to get the costcenter shortname------
            if (is_siteadmin($USER) || has_capability('local/costcenter:manage_multiorganizations', $systemcontext)) {
               //  print_object($user);exit;
                $this->to_get_the_costcentershortname($user);      
            }
                       
            //---to get the department shortname------
            if(is_siteadmin($USER) ||  has_capability('local/costcenter:manage_multiorganizations', $systemcontext) || has_capability('local/costcenter:manage_ownorganization', $systemcontext)){
                $this->to_get_the_departmentshortname($user);
            }
            
            //---It will set the username and unique id-----
            $this->to_get_the_username_uniqueid($user,$formdata->option);  

            //--username validation and also creating costcenter if not available
            if(!is_siteadmin() && (has_capability('local/costcenter:manage_owndepartments', $systemcontext) || has_capability('local/costcenter:manage_ownorganization', $systemcontext))){
                $this->open_costcenterid = $USER->open_costcenterid;
            }else{
                $this->costcenter_validation($user);
            }
             
            //-----It includes firstname and lastname, email fields validation
            $this->required_fields_validations($user,$formdata->option);              
            
            //-----It includes employee status validation , if find  other than the existing string,it will suspend the user
            $this->employee_status_validation($user);

            if(!is_siteadmin() && has_capability('local/costcenter:manage_ownorganization', $systemcontext)){
                if($user->role == 'university_head'){
                echo "<div class='local_users_sync_error'>Cannot create user with university head role at line $this->excel_line_number.</div>";
                $this->errors[] = "Cannot create user with university head role at line $this->excel_line_number.";
                $this->mfields[] = "role";
                $this->errorcount++;
                }
            }

            if(!is_siteadmin() && has_capability('local/costcenter:manage_owndepartments', $systemcontext)){
                if($user->role == 'university_head'){
                echo "<div class='local_users_sync_error'>Cannot create user with university head role as a college head at line $this->excel_line_number.</div>";
                $this->errors[] = "Cannot create user with university head role at line $this->excel_line_number.";
                $this->mfields[] = "role";
                $this->errorcount++;
                }

                if($user->role == 'college_head'){
                echo "<div class='local_users_sync_error'>Cannot create user with college head role as a college head at line $this->excel_line_number.</div>";
                $this->errors[] = "Cannot create user with university head role at line $this->excel_line_number.";
                $this->mfields[] = "role";
                $this->errorcount++;
                }

                if($user->role == 'student'){
                echo "<div class='local_users_sync_error'>Cannot create user with student role as a college head at line $this->excel_line_number.</div>";
                $this->errors[] = "Cannot create user with university head role at line $this->excel_line_number.";
                $this->mfields[] = "role";
                $this->errorcount++;
                }
            }
            
            
            if(!empty($user->contactno)){
                // It includes validation of the contact number to be numeric and of 10 digit else throws an errror
                $this->contactno_validation($user);
            }

            //---It will set the  level1_departmentid-----------
            if(!empty($this->open_costcenterid) && $user->role != 'university_head' && !has_capability('local/costcenter:manage_owndepartments', $systemcontext)){
                $this->get_departmentid('department_or_college', $this->open_costcenterid, $user, 'level1_departmentid');
            }else if(!is_siteadmin() && has_capability('local/costcenter:manage_owndepartments', $systemcontext)){
                $this->level1_departmentid = $USER->open_departmentid;
            }else{
                if($this->univdeptstatus != 1){
                   // $this->level1_departmentid = null;
                }
            }
            
            if($this->errormessage){
                echo '<div class="local_users_sync_error">'.$this->errormessage.'</div>';
                if (count($this->errors) > 0) {
                    // print_object($this->errors);
                    // write error message to db and inform admin
                    $this->write_error_db($user);
                    // $this->errorcount = $this->errorcount+count($this->errors);
                }
                goto errorloop;
            } 
                
            if (count($this->errors) > 0) {
                // write error message to db and inform admin
                $this->write_error_db($user);
                // $this->errorcount = $this->errorcount+count($this->errors);
            } else {
                //-----based on selected form option add and update operation will dones
                if($formdata->option==ONLY_ADD){

                    $exists=$DB->record_exists('user',array('email'=>$user->email));

                    if($exists){ 
                        
                        echo "<div class='local_users_sync_error'>User with email $user->email already exist at line $this->excel_line_number.</div>";
                        $this->errors[] = "User with email $user->email already exist at line $this->excel_line_number.";
                        $this->mfields[] = "useremail";
                        $this->errorcount++;
                        $flag=1;
                        continue;
                    }
                    else if($DB->record_exists('user',  array('username' => $user->username))){
                        echo "<div class='local_users_sync_error'>User with username $user->username already exist at line $this->excel_line_number.</div>";
                        $this->errors[] = "User with username $user->username already exist at line $this->excel_line_number";
                        $this->mfields[] = "username";  
                        $this->errorcount++;
                        $flag=1;
                        continue;

                    } else if($DB->record_exists('user',  array('open_employeeid' => $user->uniqueid))){
                        echo "<div class='local_users_sync_error'>User with unique id $user->uniqueid already exist at line $this->excel_line_number.</div>";
                        $this->errors[] = "User with unique id $user->uniqueid already exist at line $this->excel_line_number.";
                        $this->mfields[] = "useruniqueid";
                        $this->errorcount++;
                        $flag=1;
                        continue;

                    }      
                } 
                if($formdata->option==ONLY_ADD || $formdata->option==ADD_UPDATE){

                   $exists=$DB->record_exists('user',array('open_employeeid'=>$user->uniqueid));
                    if(!$exists){ 
                       
                        $err=$this->specific_costcenter_validation($user,$formdata->option);
                        if(!$err)
                        $this->add_rows($user, $formdata);
                    }else if($formdata->option==ONLY_ADD){
                       
                        echo "<div class='local_users_sync_error'>User with unique id $user->uniqueid already exist at line $this->excel_line_number.</div>";
                        $this->errors[] = "User with unique id $user->uniqueid already exist at line $this->excel_line_number.";
                        $this->mfields[] = "uniqueid";
                        $this->errorcount++;
                        $flag=1;
                        continue;
                    }
                       
                }
                if($formdata->option==ONLY_UPDATE || $formdata->option==ADD_UPDATE){
                    $user_sql = "SELECT id  FROM {user} WHERE (username = :username OR email = :email OR open_employeeid = :uniqueid) AND deleted = 0";
                    $user_exists = $DB->get_record_sql($user_sql,  array('username' => $user->username, 'email' => $user->email, 'uniqueid' => $user->uniqueid));
                    if ($user_exists) {                    
                    //-----Update functionality------------------
                    $userobject=$this->preparing_user_object($user, $formdata);
                    /*if(is_siteadmin()){
                    print_object($userobject);
                    print_object($user);exit;
                    }*/
                    $this->update_rows($user, $userobject);                               
                    }else if($formdata->option==ONLY_UPDATE) {
                        echo "<div class='local_users_sync_error'>User with unique id $user->uniqueid doesn't exist at line $this->excel_line_number.</div>";
                        $this->errors[] = "User with unique id $user->uniqueid doesn't exist at line $this->excel_line_number.";
                        $this->mfields[] = "uniqueid";
                        $this->errorcount++;
                        $flag=1;
                        continue;
                    }              
                }                
                // write warnings to db and inform admin
                if ( count($this->warnings) > 0) {
                    $this->write_warning_db($user);
                    $this->warningscount = count($this->warnings);
                    
                }
            }       
                
            
            $data[]=$user;      
        }
         errorloop:
        
        
        //-----updating Reporting Manager (supervisor id )
        // $this->update_supervisorid($this->data);
        if ( count($this->warnings) > 0 ) {
            $this->write_warning_db($excel);
            $this->updatesupervisor_warningscount= count($this->warnings); 
                    
        }
        
        if($this->data){
            $upload_info =  '<div class="critera_error1"><h3 style="text-decoration: underline;">Users upload status</h3>
            <div class=local_users_sync_success>Total '.$this->insertedcount . ' new users added to the system.</div>
            <div class=local_users_sync_success>Total '.$this->updatedcount . ' users details updated.</div>
            <div class=local_users_sync_error>Total '.$this->errorcount . ' errors occured while creating user.</div></div>
            <div class=local_users_sync_warning>Total '.$this->warningscount . ' warnings occured in the sync update.</div>
         
            ';
            /*code added by Rizwan for continue button*/
            $button=html_writer::tag('button',get_string('button','local_users'),array('class'=>'btn btn-primary'));
            $link= html_writer::tag('a',$button,array('href'=>$CFG->wwwroot. '/local/users/index.php'));
            $upload_info .='<div class="w-full pull-left text-xs-center">'.$link.'</div>';
            /*end of the code*/
            mtrace( $upload_info);
        
            //-------code1 by rizwana starts-------//
            $sync_data=new \stdClass();
            $sync_data->newuserscount=$this->insertedcount;
            $sync_data->updateduserscount=$this->updatedcount;
            $sync_data->errorscount=$this->errorcount;
            $sync_data->warningscount=$this->warningscount;
           
            $sync_data->usercreated=$USER->id;
            $sync_data->usermodified=$USER->id;
            $sync_data->timecreated=time();
            $sync_data->timemodified=time();
            $insert_sync_data = $DB->insert_record('local_userssyncdata',$sync_data);
            //-------code1 by rizwana ends-------//             
        } else {
            echo'<div class="critera_error">File with Employee data is not available for today.</div>';
        }
        
    } // end of main_hrms_frontendform_method function
    
    /**
     * @param   $excel [<data in excel or csv uploaded>]
     */
    private function to_get_the_costcentershortname($excel){        
        $costcenter_shortname= core_text::strtolower($excel->university);
        
        if(empty($costcenter_shortname)){
            echo '<div class=local_users_sync_error>Provide the university info for unique id "' . $excel->uniqueid . '" of uploaded sheet at line '.$this->excel_line_number.'.</div>';
            $this->errors[] = 'Provide the university info for unique id "' . $excel->uniqueid . '" of uploaded sheet at line '.$this->excel_line_number.'.';
            $this->mfields[] = 'university';
            $this->errorcount++;
        }
        else{            
            $this->costcenter_shortname = $costcenter_shortname;            
        }        
    } // end of the to_get_the_costcentershortname  

    /**
     * @param   $excel [<data in excel or csv uploaded>]
     */
    private function to_get_the_departmentshortname($excel){ 
    global $DB;       
        $department_shortname= core_text::strtolower($excel->department_or_college);
        
        if(empty($department_shortname)){
            echo "<div class=local_users_sync_error>Provide the Department or College for user at line $this->excel_line_number .</div>";
            $this->errors[] = 'Provide the Department or College info for unique id "' . $excel->uniqueid . '" of uploaded sheet in line '.$this->excel_line_number.'.';
            $this->mfields[] = 'department_or_college';
            $this->errorcount++;
        }
        else{            
            $this->department_shortname = $department_shortname;
            $this->level1_departmentid = $DB->get_field('local_costcenter', 'id', array('shortname' => $department_shortname));
        }        
    } // end of the to_get_the_departmentshortname 

    /**
     * @param   $excel [<data in excel or csv uploaded>] for validation
     */
    private function costcenter_validation($excel){
         global $DB, $USER;
        $systemcontext = \context_system::instance();
         //------username validation-------------------    
            if ( $this->costcenter_shortname) {      
                $costcenter_shortname=$this->costcenter_shortname;  
                // checking cost center available if not inserting new costcenter
                $costcenterinfo = $DB->get_record_sql("SELECT * FROM {local_costcenter} WHERE lower(shortname)='$costcenter_shortname'");
                if(empty($costcenterinfo)){
                    echo '<div class=local_users_sync_error>university "'.$costcenter_shortname.'"for unique id "'.$excel->uniqueid.'" in uploaded excelsheet does not exist in system at line '.$this->excel_line_number.'.</div>';
                    $this->errors[] = 'university "'.$costcenter_shortname.'"for unique id "'.$excel->uniqueid.'" in uploaded excelsheet does not exist in system at line '.$this->excel_line_number.'.';
                    $this->mfields[] = 'university';
                    $this->errorcount++;
                }elseif ((!$DB->record_exists('user', array('id'=> $USER->id, 'open_costcenterid'=>$costcenterinfo->id))) && (!is_siteadmin()) && (!has_capability('local/costcenter:manage_multiuniversitys', $systemcontext))){
                    echo '<div class=local_users_sync_error>university "'.$costcenter_shortname.'" entered at line '.$this->excel_line_number.' for unique id "'.$excel->uniqueid.'" in uploaded excelsheet does not belongs to you .</div>';
                    $this->errors[] = 'university "'.$costcenter_shortname.'" entered at line '.$this->excel_line_number.' for unique id "'.$excel->uniqueid.'" in uploaded excelsheet does not belongs to you .';
                    $this->mfields[] = 'university';
                    $this->errorcount++;
                } else {
                    $this->open_costcenterid = $costcenterinfo->id;
                }
                //-----incase of didnt get costcenterid----
                // if(empty($this->open_costcenterid)){
                //     echo '<div class=local_users_sync_error>Error in getting cost center info for unique id "'.$excel->uniqueid.'" in uploaded excelsheet.</div>';
                //     $this->errors[] = 'Error in getting cost center info for unique id "' . $excel->uniqueid . '" of uploaded sheet at line'.$this->excel_line_number.'.';
                //     $this->mfields[] = 'university';
                //     $this->errorcount++;
                // }               
            }
            // else{
            //     echo '<div class=local_users_sync_error >Provide valid university name for unique id"'.$excel->uniqueid.'" in uploaded excelsheet.</div>';
            //     $this->errors[] = 'Provide valid university name for unique id "' . $excel->uniqueid . '" of uploaded sheet at line'.$this->excel_line_number.'.';
            //     $this->mfields[] = 'Username';
            //     $this->errorcount++;
            // }     
         //}  //---------end of username validation------------------        
        
    } // end of costcenter_validation function
    
    
   
    /**
     * @param   $excel [<data in excel or csv uploaded>] for validation
     */
    private function required_fields_validations($excel,$option=0){
        global $DB,$USER;  

        if(!empty($excel->uniqueid) && !empty($excel->email) && !empty($excel->username)){

            $exist_sql = "SELECT id,username FROM {user} WHERE (username = :username OR email = :email OR open_employeeid = :uniqueid) AND deleted = 0";
            $users_exist = $DB->get_records_sql_menu($exist_sql, array('username' => $excel->username ,'email' => $excel->email, 'uniqueid' => $excel->uniqueid));
            $cexist_users = count($users_exist);
        }
        //------employee code validation-------------------    
         if ( array_key_exists('uniqueid', $excel) ) {
              //  $excel->uniqueid = strtolower($excel->uniqueid);
                if (!empty($excel->uniqueid)) {
                    $this->uniqueid = $excel->uniqueid;
                    if(ctype_alnum($excel->uniqueid)){
                        if($option!=0){
                            $user_exist = $DB->get_record('user', array('open_employeeid' => $excel->uniqueid));
                            if($option==ONLY_ADD){
                                if($user_exist){
                                    echo "<div class='local_users_sync_error'>".get_string('cannotcreateuseruniqueidadderror', 'local_users',$excel->uniqueid).".</div>";
                                    $this->errors[] = get_string('cannotcreateuseruniqueidadderror', 'local_users',$excel->uniqueid);
                                    $this->mfields[] = 'uniqueid';
                                    $this->errorcount++;
                                    // return; 
                                }
                            }else if($option == ONLY_UPDATE || $option == ADD_UPDATE){
                                
                                $sql = "SELECT id,username,email FROM {user} WHERE  open_employeeid = :uniqueid AND deleted = 0";

                                $user_object = $DB->get_record_sql($sql , array('uniqueid'=>$excel->uniqueid));
                                
                                  /* if($USER->id == 32){
                                    print_object($user_object);
                                    print_object($this->username);
                                    print_object($excel->email);exit;
                                }*/
                                if($user_object){
                                   
                                    if(!(strtolower($user_object->email) == strtolower($excel->email))){
                                      
                                 
                                      
                                        // if($user_object->username == $this->username){
                                        //     $error_string = get_string('multipleedituserusernameediterror','local_users',$this->username);
                                        //     $error_field = 'username';
                                        // }else if($user_object->email == $excel->email){
                                        //     $error_string = get_string('multipleedituseremailupdateerror','local_users',$excel->email);
                                        //     $error_field = 'email';
                                        // }
                                        $errormessage = 'Provide valid unique id value '.$excel->uniqueid.' inserted in the excelsheet at line'.$this->excel_line_number.'.';
                                        echo '<div class=local_users_sync_error>'.$errormessage.'</div>';
                                        $this->errors[] = $errormessage;
                                        $this->mfields[] = 'uniqueid';
                                        $this->errorcount++;
                                    }
                                }
                            }if($option == ONLY_UPDATE){
                                if(!$user_exist){
                                    echo "<div class='local_users_sync_error'>".get_string('cannotedituseremailupdateerror', 'local_users',$excel->uniqueid).".<div>";
                                    $this->errors[] = get_string('cannotedituseremailupdateerror', 'local_users',$excel->uniqueid);
                                    $this->mfields[] = 'uniqueid';
                                    $this->errorcount++;
                                    // return; 
                                }
                            }

                        }
                    }else{
                             /* if($USER->id == 32){
                                   echo "came";exit;
                                }*/
                        // echo '<div class=local_users_sync_error>Error in unique id - Invalid unique id "'.$excel->uniqueid.'" in uploaded excelsheet  at line '.$this->excel_line_number.'.</div>';
                        $errormessage = 'Provide valid unique id value '.$excel->uniqueid.' inserted in the excelsheet at line'.$this->excel_line_number.'.';
                        echo '<div class=local_users_sync_error>'.$errormessage.'</div>';
                        $this->errors[] = $errormessage;
                        $this->mfields[] = 'uniqueid';
                        $this->errorcount++;
                        // return;
                    }
                    
                } else {
                       echo '<div class=local_users_sync_error>Provide unique id for username "' . $excel->username . '" of uploaded sheet at line '.$this->excel_line_number.'.</div>';
                       $this->errors[] = 'Provide unique id for username "' . $excel->username . '" of uploaded sheet at line '.$this->excel_line_number.'.';
                       $this->mfields[] = 'uniqueid';
                       $this->errorcount++;
                       // return;
                }
            } else {
                echo '<div class=local_users_sync_error>Error in unique id column heading in uploaded excelsheet </div>';
                $this->errormessage = 'Error in unique id column heading in uploaded excelsheet ';
                $this->errorcount++;
                // return;
            }
            //---------end of employee code validation------------------
            
            //-----------check firstname-----------------------------------
            if ( array_key_exists('first_name', $excel) ) {
                 if (empty($excel->first_name)) {
                     echo '<div class=local_users_sync_error>Provide first name for  unique id "' . $excel->uniqueid . '" of uploaded excelsheet at line '.$this->excel_line_number.'.</div>'; 
                     $this->errors[] = 'Provide first name for  unique id "' . $excel->uniqueid . '" of uploaded excelsheet at line '.$this->excel_line_number.'.' ;
                     $this->mfields[] = 'firstname';
                     $this->errorcount++;
                     // return;
                       
                 }
            } else {
               // echo '<div class=local_users_sync_error>Error in first name column heading in uploaded excelsheet</div>'; 
               $this->errormessage = 'Error in first name column heading in uploaded excelsheet';
               $this->errorcount++;
               // return;
                
            }

            //-----------check role-----------------------------------
            if ( array_key_exists('role', $excel) ) {
                 if (empty($excel->role)) {
                    echo '<div class=local_users_sync_error>Provide role for  unique id "' . $excel->uniqueid . '" of uploaded excelsheet at line '.$this->excel_line_number.'.</div>'; 
                    $this->errors[] = 'Provide role for  unique id "' . $excel->uniqueid . '" of uploaded excelsheet at line '.$this->excel_line_number.'.' ;
                    $this->mfields[] = 'role';
                    $this->errorcount++;  
                 }else{
                    $roleid = $DB->get_field('role','id',array('shortname' => $excel->role));
                    if($roleid){
                        $this->roleid = $roleid; 
                        $univdeptstatus = $DB->get_field('local_costcenter','univ_dept_status',array('id' => $this->level1_departmentid));
                        $this->univdeptstatus = $univdeptstatus;
                    }else{
                        echo '<div class=local_users_sync_error>Please provide valid role for  unique id "' . $excel->uniqueid . '" of uploaded excelsheet at line '.$this->excel_line_number.'.</div>'; 
                        $this->errors[] = 'Please provide valid role for  unique id "' . $excel->uniqueid . '" of uploaded excelsheet at line '.$this->excel_line_number.'.' ;
                        $this->mfields[] = 'role';
                        $this->errorcount++;  
                    }
                 }
            } else {
               $this->errormessage = 'Error in role column heading in uploaded excelsheet';
               $this->errorcount++;
            }
            
            //-------- check lastname-------------------------------------
            if ( array_key_exists('last_name', $excel) ) {
                if (empty($excel->last_name)) {
                   echo '<div class=local_users_sync_error>Provide last name for  unique id "' . $excel->uniqueid . '" of uploaded excelsheet at line '.$this->excel_line_number.'.</div>'; 
                   $this->errors[] = 'Provide last name for  unique id "' . $excel->uniqueid . '" of uploaded excelsheet at line '.$this->excel_line_number.'.';
                   $this->mfields[] = 'last_name';
                   $this->errorcount++;
                   // return;
                }
            } else {
                // echo '<div class=local_users_sync_error>Error in last name column heading in uploaded excelsheet </div>'; 
                $this->errormessage = 'Error in last name column heading in uploaded excelsheet ';
                $this->errorcount++;
                // return;
            }
             
            
            //----------------- check email id------------------------------------------------
            if ( array_key_exists('email', $excel) ) {
                
                if (empty($excel->email)) {
                    echo '<div class=local_users_sync_error>Provide email id for  unique id "' . $excel->uniqueid. '" of uploaded excelsheet at line '.$this->excel_line_number.'.</div>';
                    $this->errors[] = 'Provide email id for  unique id "' . $excel->uniqueid. '" of uploaded excelsheet at line '.$this->excel_line_number.'.';
                    $this->mfields[] = 'email';
                    $this->errorcount++;
                    // return;
                } else {
                    if (! validate_email($excel->email)) {
                        echo '<div class=local_users_sync_error>Invalid email id entered for  uniqueid "' . $excel->uniqueid. '" of uploaded excelsheet at line '.$this->excel_line_number.'.</div>';
                        $this->errors[] = 'Invalid email id entered for  unique id "' . $excel->uniqueid. '" of uploaded excelsheet at line '.$this->excel_line_number.'.';
                        $this->mfields[] = 'email';
                        $this->errorcount++;
                        // return;
                    }
                    if($option!=0){
                        $sql = "SELECT id FROM {user} WHERE (username = :username OR open_employeeid = :uniqueid OR email = :email) AND deleted = 0";

                        $user_exist = $DB->get_record_sql($sql , array('username' => $excel->username, 'uniqueid'=>$excel->uniqueid, 'email' => $excel->email));
                        if($option == ONLY_ADD){
                            
                            if($user_exist){
                                echo "<div class='local_users_sync_error'>".get_string('cannotcreateuseremailadderror', 'local_users',$excel->email)."</div>";
                                $this->errors[] = get_string('cannotcreateuseremailadderror', 'local_users',$excel->email);
                                $this->mfields[] = 'email';
                                $this->errorcount++;
                                // return;
                            }
                        }else if($option == ONLY_UPDATE || $option == ADD_UPDATE){
                            $sql = "SELECT id,username,open_employeeid FROM {user} WHERE email = :email AND deleted = 0";
                            $user_object = $DB->get_record_sql($sql,  array('email' => $excel->email));
                            if($user_object){
                                if(!($user_object->username == $this->username || $user_object->open_employeeid == $excel->uniqueid) && $cexist_users > 1){
                                    // if($user_object->username == $this->username){
                                    //     $error_string = get_string('multipleedituserusernameediterror','local_users',$this->username);
                                    //     $error_field = 'username';
                                    // }else if($user_object->uniqueid == $excel->uniqueid){
                                    //     $error_string = get_string('multipleuseruniqueidupdateerror','local_users',$excel->uniqueid);
                                    //     $error_field = 'uniqueid';
                                    // }
                                    $error_string = get_string('multipleedituseremailupdateerror','local_users',$excel->email);
                                    $error_field = 'email';
                                    echo "<div class='local_users_sync_error'>".$error_string."</div>";
                                    $this->errors[] = $error_string;
                                    $this->mfields[] = $error_field;
                                    $this->errorcount++;
                                    // return;
                                }
                            }
                        }else if($option == ONLY_UPDATE){
                            if(!$user_exist){
                                echo "<div class='local_users_sync_error'>".get_string('cannotedituseremailupdateerror', 'local_users',$excel->email)."</div>";
                                $this->errors[] = get_string('cannotedituseremailupdateerror', 'local_users',$excel->email);
                                $this->mfields[] = 'email';
                                $this->errorcount++;
                                // return;
                            }
                        }
                    }
                }
            } else {
                // echo '<div class=local_users_sync_error>Error in arrangement of column email in uploaded excelsheet at line '.$this->excel_line_number.'.</div>';
                $this->errormessage = 'Error in arrangement of columns in uploaded excelsheet at line '.$this->excel_line_number.'.';
                $this->errorcount++;
              
            }        
    } // end of required_fields_validations function
    
    /**
     * @param   $excel [<data in excel or csv uploaded>] for validation
     */
    private function to_get_the_username_uniqueid($excel,$option=0){                
        global $CFG, $DB;
        if($excel->username){
            if($excel->username){
                    $this->username = $excel->username;                
            } else {
                echo '<div class=local_users_sync_error>Provide valid emailid for unique id "' . $excel->uniqueid . '" of uploaded excelsheet at line '.$this->excel_line_number.'</div>';
                $this->errors[] = 'Provide the valid emailid for unique id ' . $excel->uniqueid . ' in excel sheet at line '.$this->excel_line_number.'.';
                $this->mfields[] = 'username';
                $this->errorcount++;
            }
            $sql = "SELECT id,email,open_employeeid FROM {user} WHERE username = :username OR open_employeeid = :uniqueid AND deleted = 0";
            $user_object = $DB->get_record_sql($sql, array('username' => $this->username,'uniqueid' => $excel->uniqueid));
            
           if($option == ONLY_UPDATE || $option == ADD_UPDATE){
                if(!empty($excel->uniqueid) && !empty($excel->email) && !empty($excel->username)){
                    $exist_sql = "SELECT id FROM {user} WHERE (username = :username AND open_employeeid = :uniqueid) AND deleted = 0";
                    $users_exist = $DB->get_records_sql_menu($exist_sql, array('username' => strtolower($excel->username) ,'email' => strtolower($excel->email), 'uniqueid' => $excel->uniqueid));
                    $cexist_users = count($users_exist);
                }
                if($user_object){
                    if(!($user_object->email == $excel->email || $user_object->open_employeeid == $excel->uniqueid) && $cexist_users > 1){    
                        $error_string = get_string('multipleedituserusernameediterror','local_users',$this->username);
                        $error_field = 'username';
                        echo "<div class='local_users_sync_error'>".$error_string.".</div>";
                        $this->errors[] = $error_string;
                        $this->mfields[] = $error_field;
                        $this->errorcount++;
                    }
                }
            }
            if($option == ONLY_UPDATE){
                if(!$user_object){
                    echo "<div class='local_users_sync_error'>".get_string('cannotedituserusernameediterror', 'local_users',$this->username).".</div>";
                    $this->errors[] = get_string('cannotedituserusernameediterror', 'local_users',$this->username);
                    $this->mfields[] = 'username';
                    $this->errorcount++;
                }
            }
        }else{
            echo '<div class=local_users_sync_error>Provide mailid for unique id "' . $excel->uniqueid . '" of uploaded excelsheet at line '.$this->excel_line_number.'</div>';
            $this->errors[] = 'Provide the mailid for unique id ' . $excel->uniqueid . ' in excel sheet at line '.$this->excel_line_number.'.';
            $this->mfields[] = 'username_notexist';
            $this->errorcount++;
        }
    } // end of function to_ge_the_username_uniqueid
    
    
    
    
    /**
     * @param   $excel [<data in excel or csv uploaded>] for validation
     */
    
    private function employee_status_validation($excel){
        
        // check employeestatus
        if (array_key_exists('employee_status', $excel)) {
            if (empty($excel->employee_status)) {
                echo '<div class=local_users_sync_error>Provide employee status for  unique id "' . $excel->uniqueid . '" of uploaded excelsheet at line '.$this->excel_line_number.'.</div>';
                $this->errors[] = 'Provide employee status for  unique id "' . $excel->uniqueid . '" of uploaded excelsheet at line '.$this->excel_line_number.'.';
                $this->mfields[] = 'employee_status';
                $this->errorcount++;
            } else {
                if (strtolower($excel->employee_status) == 'active') {
                    $this->activestatus = 0;
                } elseif ( strtolower($excel->employee_status) == 'inactive' ) {
                    $this->activestatus = 1;
                } else {
                    $this->activestatus = 0;
                }
            }
        } else {
            // echo 'ststy validation';
            echo '<div class=local_users_sync_error>Error in arrangement of columns in uploaded excelsheet at line '.$this->excel_line_number.'</div>';
            $this->errormessage = 'Error in arrangement of columns in uploaded excelsheet at line '.$this->excel_line_number.'.';
            $this->errorcount++;

        }        
    } // end of  employee_status_validation method
    
    /**
     * [contactno_validation description]
     * @param  [type] $excel [description]
     * @return [type]        [description]
     */
    private function contactno_validation($excel){
        $this->contactno = $excel->contactno;
        if(!is_numeric($this->contactno)){
            echo '<div class=local_users_sync_error>Enter a valid contact number for unique id '.$excel->uniqueid.' at line '.$this->excel_line_number.'</div>';
            $this->errors[] = 'Enter a valid contact number for unique id '.$excel->uniqueid.' at line '.$this->excel_line_number.'';
            $this->mfields[] = 'contactno';
            $this->errorcount++;
        }else if(($this->contactno<999999999 || $this->contactno>10000000000)){
            echo '<div class=local_users_sync_error>Enter a valid contact number of 10 digits for unique id '.$excel->uniqueid.' at line '.$this->excel_line_number.'</div>';
            $this->errors[] = 'Enter a valid contact number of 10 digits for unique id '.$excel->uniqueid.' at line '.$this->excel_line_number.'';
            $this->mfields[] = 'contactno';
            $this->errorcount++;
        }
        // if(strlen($this->contactno) > 20){
        //     echo '<div class=local_users_sync_error>Enter a valid contact number for unique id '.$excel->uniqueid.' at line '.$this->excel_line_number.'</div>';
        //     $this->errors[] = 'Enter a valid contact number for unique id '.$excel->uniqueid.' at line '.$this->excel_line_number.'';
        //     $this->mfields[] = 'contactno';
        // }

    }


    /**
     * @param   $excel [<data in excel or csv uploaded>] for validation
     */
    private function specific_costcenter_validation($excel,$option = 0){
        global $DB; $flag=0;
               $costcenter_shortname= core_text::strtolower($excel->university);
   
        if (!$DB->record_exists('user', array('open_employeeid'=> $excel->uniqueid))) {
     
            if($DB->get_record('user', array('username'=>  $this->username))){
                if($option==0){
                   echo '<div class=local_users_sync_error>emailid for  unique id "' . $excel->uniqueid . '" of uploaded excelsheet is already exists  in the system</div>';
                   $this->errors[] = 'emailid for  unique id "' . $excel->uniqueid . '" of uploaded excelsheet is already exists in the system at line '.$this->excel_line_number.'.';
                   $this->mfields[] = 'email';
                   $this->errorcount++;
                  $flag=1;
                  return $flag;
                }
            }

        /******To Check unique id already exist with costcenter a unique id can be there with other costcenter****/
        $sql="select u.id,u.open_costcenterid from {user} u where u.open_employeeid='".$excel->uniqueid."'";
        $employecodevalidation=$DB->get_record_sql($sql);
        $excel_costcenter=$this->open_costcenterid;
        $id_costcenter=$employecodevalidation->open_costcenterid;

        if($id_costcenter==$excel_costcenter){
            if($option==0){
                /*****Here we check and throw the error of unique id****/
                echo '<div class=local_users_sync_error>Employee code for  unique id "' . $excel->uniqueid . '" of uploaded excelsheet is already under this university</div>';
                $this->errors[] = 'emailid for  unique id "' . $excel->uniqueid . '" of uploaded excelsheet is already exists in the system at line '.$this->excel_line_number.'.';
                $this->mfields[] = 'email';
                $flag=1;
                $this->errorcount++;
                }
            }
        }
        return $flag;
         
    } //end of specific_costcenter_validation
    
    
    /* method  get_departmentid
     * used to get the department(costcenter) id
     * @param : $field string (excel field name)
     * @param : $parentid int
     * @param : $excel object it holds single row
     * @param : $classmember 
     * @return : int department id  
    */
    private function get_departmentid($field, $parentid, $excel, $classmember){
        global $DB, $USER;
   
        if ( array_key_exists($field, $excel) ) {
            if ( !empty( $excel->$field ) ) {
                $dep = trim($excel->$field);
                $dep =strtolower($dep);
                if($field == "department_or_college"){
                   $head = "university";
                   $parent_name = $excel->university; 
                }
                 //$username = explode("\\",$excel->username);
                // $parent=$DB->get_field('local_costcenter','id',array('shortname'=>$this->costcenter_shortname));
                $dep=str_replace("\n", "", $dep);

                $departmentname = $DB->get_record_sql("SELECT * from {local_costcenter} where lower(shortname) = '$dep' AND parentid= $parentid");      
                  
                if (empty($departmentname)) {     
                    echo '<div class=local_users_sync_error>'.ucfirst($field).' "'.$dep.'"for unique id "'.$excel->uniqueid.'" in uploaded excelsheet does not exist under '.$head.' '.$parent_name.' at line '.$this->excel_line_number.'.</div>';
                    $this->errors[] = ucfirst($field).' "'.$dep.'"for unique id "'.$excel->uniqueid.'" in uploaded excelsheet does not exist under '.$head.' '.$parent_name.' at line '.$this->excel_line_number.'.';
                    $this->mfields[] = $field;
                    $this->errorcount++;
                    $this->$classmember = null;              
                    // create department if not exists
                    // $newdep = new \stdclass();
                    // $newdep->parentid = $parentid;
                    // $newdep->fullname = $excel->$field;
                    // $newdep->shortname = $excel->$field;
                    // $newdep->usermodified  = $USER->id;
                    // $newdep->timecreated = time();
                    // $newdep->type = 1;
                    //  if ($newdep->parentid == 0) {
                    //     $newdep->depth = 1;
                    //     $newdep->path = '';
                    // } else {
                    //     /* ---parent item must exist--- */
                    //     $parent = $DB->get_record('local_costcenter', array('id' => $parentid));
                    //     $newdep->depth = $parent->depth + 1;
                    //     $newdep->path = $parent->path;
                    // }
                    // /* ---get next child item that need to provide--- */
                        
                    // if (!$sortorder = $this->costcenterobj->get_next_child_sortthread($newdep->parentid, 'local_costcenter')) {
                    //     return false;
                    // }
                
                    // $newdep->sortorder = $sortorder;
                    // $costcenter_id = $this->costcenterobj->costcenter_add_instance($newdep);

                    // $this->$classmember = $costcenter_id;
                } else {
                    $this->$classmember = $departmentname->id;
                }
            }else{
                echo '<div class=local_users_sync_error>Provide '.ucfirst($field).' for uniqueid "'.$excel->uniqueid.'" at line'.$this->excel_line_number.'.</div>';
                // $this->warningscount++;
                // $this->$classmember = null;
                $this->errors[] = 'Provide '.ucfirst($field).' for uniqueid "'.$excel->uniqueid.'" at line '.$this->excel_line_number.'.';
                $this->mfields[] = $field;
                $this->errorcount++;
                $this->$classmember = null;  

            }        
        }

    } // end of  get_departmentid method
    
    
    private function write_error_db($excel){
        global $DB, $USER;
        // write error message to db and inform admin
        $syncerrors = new \stdclass();
        $today = date('Y-m-d');
        $syncerrors->date_created = time();
        $errors_list = implode(',',$this->errors);
        $mandatory_list = implode(',',$this->mfields);
        $syncerrors->error = $errors_list;
        $syncerrors->modified_by = $USER->id;
        $syncerrors->mandatory_fields = $mandatory_list;
        if (empty($excel->email))
            $syncerrors->email = '-';
        else
            $syncerrors->email = $excel->email;
                
        if (empty($excel->uniqueid))
            $syncerrors->idnumber = '-';
        else
            $syncerrors->idnumber =$excel->uniqueid;
            $syncerrors->firstname = $excel->first_name;
            $syncerrors->lastname = $excel->first_name;
            $syncerrors->sync_file_name="Employee";
           // $syncwarnings->type = 'Error';
            $DB->insert_record('local_syncerrors', $syncerrors);   
        
    } // end of write_error_db method
    
    private function preparing_user_object($excel, $formdata=null){
        global $USER;
        $user = new \stdclass();    
      
        $user->suspended = $this->activestatus;

        $user->idnumber = $this->uniqueid;
        $user->open_employeeid = $excel->uniqueid;
        $user->username = strtolower($this->username);        
        $user->firstname = $excel->first_name;
        $user->lastname = $excel->last_name;
        $user->phone1 = $excel->contactno ? $excel->contactno : '';
        $user->email = strtolower($excel->email);
        $user->country = 'IN';
        $user->open_role = $this->roleid;
        $user->open_univdept_status = $this->univdeptstatus;
        $user->employee_status = $excel->employee_status;
        $user->city = $excel->location ? $excel->location : ' ';
        $user->open_state = $excel->state ? $excel->state : ' ';
        $user->address = $excel->address ? $excel->address : ' ';
        //----costcenter and department info -----
        $user->open_costcenterid =$this->open_costcenterid;
        $user->open_departmentid = $this->level1_departmentid;
        // $user->open_subdepartment = $this->level2_departmentid;
        $user->usermodified = $USER->id;
        // print_r($user);
        // print_r($excel);
        // exit;
         
        if($formdata){ 
            switch($formdata->enrollmentmethod){
                case MANUAL_ENROLL:
                      $user->auth = "manual";
                      break;
                case OAUTH2_ENROLL:
                      $user->auth = "oauth2";
                      break;
                case SAML2:
                      $user->auth = "saml2";
                      break; 
                case ADwebservice:
                      $user->auth = "adwebservice";
                      break;                     
            }
        }

        switch($excel->role){
            case 'university_head':
                  $user->open_employee = 3;
                  break;
            case 'college_head':
                  $user->open_employee = 1;
                  break;
            case 'faculty':
                  $user->open_employee = 1;
                  break; 
            case 'student':
                  $user->open_employee = 2;
                  break;                     
        }
        

        return $user;
    } // end of function
    
    
    
    private function add_newuser_instance_fromhrmssync($excel, $user){
        global $DB, $USER, $CFG;
        $systemcontext = \context_system::instance();
        //--------Insertion part--------------------    
        $user->password = hash_internal_user_password("Welcome#3");
        $user->timecreated = time();
        $user->timemodified = 0;
        $user->mnethostid = 1;
        $user->confirmed = 1;
        $user->suspended = 0;  
        $id  = user_create_user($user, false);     
        // print_object($user);    
        //for assigning user in site level//
        if(!empty($user->open_role)){
            $userid = $id;
            $roleid = $user->open_role;
            if($user->open_role == 5){
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
                if($user->open_role != 5){
                    if(!user_has_role_assignment($userid,$roleid)){
                        role_assign($roleid, $userid, $systemcontext->id);
                    }
                }
            }
        }     
        $this->insertedcount++;
    } // end of add_newuser_instance
    
    
    private function add_rows($excel,$formdata){
        global $DB, $USER, $CFG;
        $user=$this->preparing_user_object($excel,$formdata);
        $sql = "SELECT id FROM {user} WHERE (username = :username OR open_employeeid = :uniqueid OR email = :email) AND deleted = 0";

        $userexist = $DB->get_record_sql($sql , array('username' => $user->username, 'uniqueid'=>$user->uniqueid, 'email' => $user->email));

        if(empty($userexist)){        
            $this->add_newuser_instance_fromhrmssync($excel, $user);
        }
    
    } // end of add_rows function   
    
    private function add_update_rows($excel){
        global $DB, $USER;
        // add or update information       
        $user=$this->preparing_user_object($excel);
        $user_sql = "SELECT id  FROM {user} WHERE (username = :username OR email = :email OR open_employeeid = :uniqueid) AND deleted = 0";
        $user_object = $DB->get_record_sql($user_sql,  array('username' => $user->username, 'email' => $user->email, 'uniqueid' => $user->open_employeeid));
             
        if ($user_object) {
            //-----Update functionality------------------
            $this->update_rows($excel, $user);                               
        } else{              

                $err=$this->specific_costcenter_validation($user);
                if(!$err)             
                    $this->add_newuser_instance_fromhrmssync($excel, $user);            
            
        } // end of else
        
    } // end of add_update_rows method


    public function update_rows($excel, $user){
        global $USER, $DB, $CFG;
        $systemcontext = \context_system::instance();
        //---------Updation part------------------------------
        //-----if user exists updating user(mdl_user) record 
        $user_sql = "SELECT id,username FROM {user} WHERE (username = :username OR email = :email OR open_employeeid = :uniqueid) AND deleted = 0";
        $user_object = $DB->get_record_sql($user_sql,  array('username' => $excel->username, 'email' => $excel->email, 'uniqueid' => $excel->uniqueid));
       
     /*     if(is_siteadmin()){
            print_object($user);
            print_object($user->username);
            echo "came";
            print_object($user_object);echo "camde";
          //  print_object($userid);
            exit;
        }*/
            $userid=$user_object->id;
            if(!empty($user_object)){
           
            if($userid){ 
                $user->id = $userid;
                $user->timemodified = time();
                $user->suspended = $this->activestatus;
                $user->idnumber = $excel->uniqueid;
                $user->open_costcenterid =$this->open_costcenterid;
                $user->open_departmentid = $this->level1_departmentid;
                // $user->open_subdepartment = $this->level2_departmentid;
                $user->phone1 =$excel->contactno;
                $user->open_state = $excel->state;
                $user->usermodified = $USER->id;
                $user->open_role = $this->roleid;
                $user->city = $excel->location;
                $user->address = $excel->address;
                $user->phone1 = $excel->contactno;
                $user->open_univdept_status = $this->univdeptstatus;
                user_update_user($user, false);
                // for assigning user in site level//
                if(!empty($user->open_role)){
                    $userid = $user->id;
                    $roleid = $user->open_role;
                    if($user->open_role == 5){
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
                        if($user->open_role != 5){
                            if(!user_has_role_assignment($userid,$roleid)){
                                role_assign($roleid, $userid, $systemcontext->id);
                            }
                        }
                    }
                }     
                $this->updatedcount++;
            }
        }
    } // end of  update_rows method

    
    
    private function write_warning_db($excel){
        global $DB, $USER;
        if(!empty($this->warnings) && !empty($this->wmfields)){
            $syncwarnings = new \stdclass();
            $today = date('Y-m-d');
            $syncwarnings->date_created = strtotime($today);
            $werrors_list = implode(',',$this->warnings);
            $wmandatory_list = implode(',', $this->wmfields);
            $syncwarnings->error = $werrors_list;
            $syncwarnings->modified_by = $USER->id;
            $syncwarnings->mandatory_fields = $wmandatory_list;
            if (empty($excel->email))
                $syncwarnings->email = '-';
            else
                $syncwarnings->email = $excel->email;
                        
            if (empty($excel->uniqueid))
                $syncwarnings->idnumber = '-';
                else
                $syncwarnings->idnumber = $excel->uniqueid;
                
            $syncwarnings->firstname = $excel->first_name;
            $syncwarnings->lastname = $excel->last_name;
            $syncwarnings->type = 'Warning';
            $DB->insert_record('local_syncerrors', $syncwarnings);
            //$warningscount++;
        }
        
    } // end of write_warning_db method
    
    
    
    // private function update_supervisorid($data){
    //     global $DB;      
       
    //         $this->warnings = array();
    //         $this->mfields = array();
    //         $this->wmfields = array();
    //         $linenum = 1;
    //      // supervisor id check after creating all users
    //     foreach($data as $excel){
    //         $linenum++;
    //         if(!is_object($excel))
    //             $excel = (object)$excel;
            
    //         //---to get the costcenter shortname------
    //         // $this->to_get_the_costcentershortname($excel);
    //         if(!empty($excel->university)){
    //             $this->costcenter_shortname = $excel->university;
    //         }
            
    //         $this->uniqueid = $excel->uniqueid;

                 
    //         if($excel->reportingmanager_email!=''){
    //             $costcenter = $DB->get_field('user', 'open_costcenterid', array('username' => $excel->username));           
    //             $super_userid = $DB->get_record('user', array('email' => $excel->reportingmanager_email, 'open_costcenterid' => $costcenter));

    //             if($super_userid){
    //                 $user_exist = $DB->record_exists('user', array('idnumber'=> $this->uniqueid));
    //                 if ($user_exist) {
    //                     $userid = $DB->get_field('user', 'id', array('uniqueid'=>$this->uniqueid));
    //                     $local_user = $DB->get_record('user', array('id'=>$userid));          
    //                     $local_user->open_supervisorempid = $super_userid->uniqueid;
    //                     $local_user->open_supervisorid=$super_userid->id;
                       
    //                     if(!empty($local_user->id)){
    //                         $data=$DB->update_record('user', $local_user); 
    //                     }
    //                 }
    //             }else{
    //                 $strings = new \stdClass();
    //                 $strings->email = $excel->reportingmanager_email;
    //                 $strings->line = $linenum;
    //                 $warningmessage = get_string('nosupervisormailfound','local_users',$strings);
    //                 $this->errormessage = $warningmessage;
    //                 echo '<div class=local_users_sync_warning>'.$warningmessage.'</div>';
    //                 $this->warningscount++; 
    //             }
    //         }   
    //         $this->write_warning_db($excel);
            
    //     }
    // } // end of  update_supervisorid method

}  // end of class


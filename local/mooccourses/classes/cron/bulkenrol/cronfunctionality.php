<?php
namespace local_mooccourses\cron\bulkenrol;
require_once($CFG->dirroot.'/user/lib.php');
require_once($CFG->dirroot.'/local/costcenter/lib.php');
use costcenter;
use core_text;
use core_user;
use DateTime;
use html_writer;
define('MANUAL_ENROLL', 1);
define('SAML2', 3);
define('ADwebservice', 4);
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
    
    private $errorcount=0;

    private $warningscount=0;

    private $updatesupervisor_warningscount =0;
    
    //-----It holds the unique username    
    private $username;

    private $excel_line_number;
    private $costcenterobj;
    private $enrolledcount=0;
    private $existcount=0;

        
    
    function __construct($data=null){    
        $this->data = $data;
        //print_object( $this->data);
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
            $this->data[]=$user;  
            //print_object($this->data);
            $this->errors = array();
            $this->warnings = array();
            $this->mfields = array();
            $this->wmfields = array();
            $this->excel_line_number = $linenum;

             $this->enrol1($user);

            $this->course_validation($user); 

            $this->email_validation($user);
            
        }
        if($this->data){
            $upload_info =  '<div class="critera_error1"><h3 style="text-decoration: underline;">Bulk user enrolment status</h3>
            <div class=local_users_sync_success>Total '.$this->enrolledcount . ' users enrol to the course .</div>
            <div class=local_users_sync_error>Total '.$this->errorcount . ' errors occured while enroll the  Users.</div></div>
            <div class=local_users_sync_warning>Total '.$this->existcount . ' user are exist .</div></div>
            '
            ;
            /*code added by Rizwan for continue button*/
            $button=html_writer::tag('button',get_string('button','local_mooccourses'),array('class'=>'btn btn-primary'));
            $link= html_writer::tag('a',$button,array('href'=>$CFG->wwwroot. '/local/mooccourses/index.php?type=2'));
            $upload_info .='<div class="w-full pull-left text-xs-center">'.$link.'</div>';
            /*end of the code*/
            mtrace( $upload_info);
               
        }                 
                
    }      // end of main_hrms_frontendform_method function
    
    /**
     * @param   $excel [<data in excel or csv uploaded>]
     */
    
    function course_validation($excel){
        global $DB, $USER;
        $systemcontext = \context_system::instance();
         //------program validation-------------------
        if ($excel->coursecode) {
                $coursecode = $excel->coursecode;
                $coursecodeinfo = $DB->get_record_sql("SELECT * FROM {course} WHERE shortname ='$excel->coursecode'");
            if(empty($coursecodeinfo)){
                    echo '<div class=local_users_sync_error>course "'.$coursecode.'" in uploaded excelsheet does not exist in system at line '.$this->excel_line_number.'.</div>';
                    $this->errors[] = 'course "'.$coursecode.'" in uploaded excelsheet does not exist in system at line '.$this->excel_line_number.'.';
                    $this->mfields[] = 'course';
                    //$this->errorcount++;
            }
        }
    }  

    function email_validation($excel){
        global $DB, $USER;
        $systemcontext = \context_system::instance();

         //------email validation-------------------
        if ($excel->email) {
                $emailcode = $excel->email;
                $emailcodeinfo = $DB->get_record_sql("SELECT * FROM {user} WHERE email ='$excel->email'");
            if(empty($emailcodeinfo)){
                    echo '<div class=local_users_sync_error>email "'.$emailcode.'" in uploaded excelsheet does not exist in system at line '.$this->excel_line_number.'.</div>';
                    $this->errors[] = 'email "'.$emailcode.'" in uploaded excelsheet does not exist in system at line '.$this->excel_line_number.'.';
                    $this->mfields[] = 'email';
                    //$this->errorcount++;
            }
        }
    } // end of email_validation function      

    private function enrol1($excel){
        global $DB,$CFG;
        require_once($CFG->dirroot . '/lib/enrollib.php');
// -Sandeep bulk upload changes username is removed //        
$user= core_text::strtolower($excel->email);
        $email= core_text::strtolower($excel->email);
        $coursecode= core_text::strtolower($excel->coursecode);
        $courseid=$DB->get_field('course','id',array('shortname'=>$coursecode));             
        $coursecost=$DB->get_field('course','open_costcenterid',array('shortname'=>$coursecode));             
        $coursedept=$DB->get_field('course','open_departmentid',array('shortname'=>$coursecode));             
        $roleid = strtolower($excel->role); 

        $user_from_sheet=$DB->get_field('user','id',array('email'=>$user));
        
        $user_sheet=$DB->get_field('user','username',array('email'=>$user));
        $email_sheet=$DB->get_field('user','email',array('email'=>$email));
        $role = $DB->get_field('role', 'id',array('shortname'=>'student'));
        //print_object($courseid);
        if(!empty($courseid)){
            $college_ids_sql = '';
            // print_r($coursedept);
            if($coursedept == 0){
                $college_ids_sql = "SELECT lc.id as collegeid,c.id as courseid from {local_costcenter} lc JOIN {course} c ON c.open_departmentid = lc.parentid WHERE c.affiliationstatus = 1 AND c.id = $courseid AND lc.id = $coursecost";
            } else {
            $college_ids_sql = "SELECT lc.id as collegeid,c.id as courseid from {local_costcenter} lc JOIN {course} c ON c.open_departmentid = lc.id WHERE  (lc.univ_dept_status = 1 OR lc.univ_dept_status =0)  AND  c.affiliationstatus = 1 AND c.id = $courseid";          
            }
            $college_ids = $DB->get_records_sql($college_ids_sql);
             $userids = array();
            foreach($college_ids as $collegeid) {
                if($coursedept == 0) {
                    $userid_sql = "SELECT u.id FROM {user} u JOIN {local_costcenter} lc ON lc.id = u.open_departmentid WHERE (lc.univ_dept_status = 1 OR lc.univ_dept_status = 0) AND lc.parentid = $coursecost";
                }else {
                    $userid_sql = "SELECT u.id FROM {user} u JOIN {local_costcenter} lc ON lc.id = u.open_departmentid WHERE (lc.univ_dept_status =1 OR lc.univ_dept_status = 0) 
                    AND lc.id =". $collegeid->collegeid;
                }
                $users = $DB->get_records_sql($userid_sql);
                
                foreach ($users as $user) {
                    $userid = $user->id;
                    $userids[] = $userid;
                   // print_object($userids);
                }  
                         
          
            $user_sql = $userids;
           
            if(array_search($user_from_sheet,$user_sql)){
                if(empty($excel->email)){
                    echo '<div class=local_users_sync_error> Provide email id for  username "' .$excel->username.'"  of uploaded excelsheet at line '.$this->excel_line_number.'.</div>';                  
                    $this->errorcount++;
                }else{

                    if($roleid == 'student' || $roleid == 'faculty'){
                        $exist = $DB->get_records_sql("select * from mdl_user_enrolments as a, mdl_enrol as b where a.userid=".$user_from_sheet. " and a.enrolid=b.id and b.courseid=".$collegeid->courseid);
                        if($exist){
                             echo '<div class=local_users_sync_warning>UserName"'.$user_sheet.'"  in uploaded excelsheet already enrolled to the Course '.$this->excel_line_number.'.</div>';
                            $this->existcount++;
                         }else{
                            $instance = $DB->get_record('enrol', array('courseid'=>$collegeid->courseid, 'enrol'=>'manual'), '*', MUST_EXIST);
                            $enrol_manual = enrol_get_plugin('manual');
                            $roleid = $DB->get_field('role', 'id',array('shortname'=>$roleid));
                            $startdate=$DB->get_field('course','startdate',array('id'=> $collegeid->courseid));
                            $test=$enrol_manual->enrol_user($instance, $user_from_sheet, $roleid,$startdate,0);
                            echo'<div class=local_users_sync_success>UserName"'.$user_sheet.'" in uploaded excelsheet enrolled to the Course at line '.$this->excel_line_number.'.</div>';

                            $this->enrolledcount++;         
                        }
                    }else{
                        $roleid = strtolower($excel->role); 
                        echo '<div class=local_users_sync_error>UserName"'. $user_sheet.'" user is not a "'.$roleid .'"at line '.$this->excel_line_number.'.</div>';
                        $this->errorcount++;
                    //echo $roleid .' not allowed';
                    }
                }
            }else{
                 echo '<div class=local_users_sync_error>User with username "'.$excel->email.'" does not exist in the system at line '.$this->excel_line_number.'.</div>';
                $this->errorcount++;
            }
         } //foreach
       
        }

    }//end of enrol1 function

}  // end of class


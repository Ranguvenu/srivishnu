<?php
namespace local_program;
require_once($CFG->dirroot.'/user/lib.php');
require_once($CFG->dirroot.'/local/costcenter/lib.php');
use \local_program\program as program;
use costcenter;
use core_text;
use core_user;
use DateTime;
use html_writer;

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

    private $errorcount=0;

    private $warningscount=0;
    
    private $errormessage;

    private $insertedcount=0;

    function __construct($data=null){    
        $this->data = $data;
       // $this->costcenterobj = new costcenter();
    }// end of constructor
  
	public function  main_hrms_frontendform_method($cir,$filecolumns, $formdata){
        global $DB,$USER, $CFG;
       // $pluginname = 'program';
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
            $this->errors = array();
            $this->warnings = array();
            $this->mfields = array();
            $this->wmfields = array();
            $this->excel_line_number = $linenum;
     
            	$this->program_validation($user);
         
            	$this->email_validation($user);

                $this->costcenterprogram_validation($user);

                $this->userenrol_validation($user);

                $this->role_validation($user);

                $this->departmentprogram_validation($user);

                $this->incorrectprograme_validation($user,$formdata);

            $userid = $DB->get_field_sql("SELECT id FROM {user} WHERE email = '$user->email' ");
            $listuser = array();
            $listuser[] = $userid;
            if($this->errormessage){
                echo '<div class="local_users_sync_error">'.$this->errormessage.'</div>';
            }

            if(count($this->errors) > 0) {
                $this->errorcount = count($this->errors);
                
            }else{  
            	$studentdata = new \stdClass();
	            $studentdata->programid = $formdata->programid;
	            $studentdata->curriculumid = $formdata->curriculumid;
                $studentdata->yearid = $formdata->yearid;
	            $studentdata->type = 'course_enrol';
	            $studentdata->students = $listuser;
               # AM ODL-713 to enroll classroom assign users to programs
                $semestercoursessql = 'SELECT c.id, c.id as courseid
                                   FROM {course} c
                                   JOIN {local_cc_semester_courses} ccsc ON ccsc.courseid = c.id
                                   WHERE ccsc.yearid = :yearid ';
                $params['yearid'] = $studentdata->yearid;
                $semestercourses = $DB->get_records_sql($semestercoursessql, $params);
                if($semestercourses){
                  $managefaculty = (new program)->addstudent($studentdata);
                }else{
                  $managefaculty = (new program)->addstudenttoclassroom($studentdata);
                }
                # AM ODL-713 to enroll classroom assign users to programs
                $this->insertedcount++;
            }
            if($this->data){
            $upload_info =  '<div class="critera_error1">
            <div class=local_users_sync_success>Total ' . $this->insertedcount . ' users have enrolled to the program.</div>
            <div class=local_users_sync_error>Total '.$this->errorcount . ' errors occured while enrolling.</div></div>';

              mtrace( $upload_info);
        
            }
	    } 
        if($linenum == 1){
            $this->emptysheet_validation($excel);
        }
    }
            
	function program_validation($excel){
        global $DB, $USER;
        $systemcontext = \context_system::instance();
         //------program validation-------------------
        if(empty($excel->programcode)){
            echo '<div class=local_users_sync_error>Programcode is empty in the uploaded sheet at line  '.$this->excel_line_number.'.</div>';
            $this->errors[] = 'Programcode is empty in the uploaded sheet at line  '.$this->excel_line_number.'.';
            $this->mfields[] = 'program';
            $this->errorcount++;
        }
        elseif ($excel->programcode) {
                $programcode = $excel->programcode;
                $programcodeinfo = $DB->get_record_sql("SELECT * FROM {local_program} WHERE shortname ='$excel->programcode'");
            if(empty($programcodeinfo)){
                    echo '<div class=local_users_sync_error>program "'.$programcode.'" in uploaded excelsheet does not exist in system at line '.$this->excel_line_number.'.</div>';
                    $this->errors[] = 'program "'.$programcode.'" in uploaded excelsheet does not exist in system at line '.$this->excel_line_number.'.';
                    $this->mfields[] = 'program';
                    $this->errorcount++;
            }
        }
    }        
    function email_validation($excel){
        global $DB, $USER;
        $systemcontext = \context_system::instance();

         //------email validation-------------------
         if(empty($excel->email)){
            echo '<div class=local_users_sync_error>Emailid is empty in the uploaded sheet at line '.$this->excel_line_number.'.</div>';
            $this->errors[] = 'Emailid is empty in the uploaded sheet at line '.$this->excel_line_number.'.';
            $this->mfields[] = 'program';
            $this->errorcount++;
        }
        elseif ($excel->email) {
                $emailcode = $excel->email;
                $emailcodeinfo = $DB->get_record_sql("SELECT * FROM {user} WHERE email ='$excel->email'");
            if(empty($emailcodeinfo)){
                    echo '<div class=local_users_sync_error>email "'.$emailcode.'" in uploaded excelsheet does not exist in system at line '.$this->excel_line_number.'.</div>';
                    $this->errors[] = 'email "'.$emailcode.'" in uploaded excelsheet does not exist in system at line '.$this->excel_line_number.'.';
                    $this->mfields[] = 'email';
                    $this->errorcount++;
            }
        }
    } // end of email_validation function
    function userenrol_validation($excel){
        global $DB, $USER;
        $systemcontext = \context_system::instance();
        $emailcode = $excel->email;
        $programid = $DB->get_field_sql("SELECT id FROM {local_program} WHERE shortname = '$excel->programcode'");
        $userid = $DB->get_field_sql("SELECT id FROM {user} WHERE email = '$excel->email' ");
            if($DB->record_exists('local_ccuser_year_signups',array('programid'=>$programid,'userid'=>$userid,'yearid'=>$formdata->yearid))){
                echo '<div class=local_users_sync_error>user "'.$emailcode.'" in uploaded excelsheet already enrolled to the Program at line '.$this->excel_line_number.'';
                    $this->errors[] = 'user "'.$emailcode.'" in uploaded excelsheet exist in system at line '.$this->excel_line_number.'.';
                    $this->mfields[] = 'enrol';
                    $this->errorcount++;
            }
    } // end of userenrol_validation function
    function costcenterprogram_validation($excel){
        global $DB, $USER;
        $systemcontext = \context_system::instance();
        $emailcode = $excel->email;
        $programcode = $excel->programcode;
        if(!empty($programcode) && !empty($emailcode)){
        $programcostcenter = $DB->get_field_sql("SELECT costcenter FROM {local_program} WHERE shortname = '$excel->programcode'");
        $usercostcenter = $DB->get_field_sql("SELECT open_costcenterid FROM {user} WHERE email = '$excel->email' ");
            if($programcostcenter != $usercostcenter){
                    echo '<div class=local_users_sync_error>user "'.$emailcode.'" in uploaded excelsheet does not belong to the same university at line '.$this->excel_line_number.'</div>';
                    $this->errors[] = 'user "'.$emailcode.'" in uploaded excelsheet exist in system at line '.$this->excel_line_number.'.';
                    $this->mfields[] = 'enrol';
                    $this->errorcount++;
            }
        }
    } // end of userenrol_validation function
    function role_validation($excel){
        global $DB, $USER;
        $systemcontext = \context_system::instance();
        $emailcode = $excel->email;
        $programcode = $excel->programcode;
        if(!empty($programcode) && !empty($emailcode)){
        $role = $DB->get_field_sql("SELECT open_role FROM {user} WHERE email = '$excel->email'");
            if($role != 5){
                    echo '<div class=local_users_sync_error>user "'.$emailcode.'" in uploaded excelsheet does not have student role at line '.$this->excel_line_number.'</div>';
                    $this->errors[] = 'user "'.$emailcode.'" in uploaded excelsheet exist in system at line '.$this->excel_line_number.'.';
                    $this->mfields[] = 'enrol';
                    $this->errorcount++;
            }
        }
    } // end of role_validation function
    function departmentprogram_validation($excel){
        global $DB, $USER;
        $systemcontext = \context_system::instance();
        $emailcode = $excel->email;
        $programcode = $excel->programcode;
        if(!empty($programcode) && !empty($emailcode)){
        $programdepartment = $DB->get_field_sql("SELECT departmentid FROM {local_program} WHERE shortname = '$excel->programcode'");
        $userdepartment = $DB->get_field_sql("SELECT open_departmentid FROM {user} WHERE email = '$excel->email' ");
            if($programdepartment != $userdepartment){
                    echo '<div class=local_users_sync_error>user "'.$emailcode.'" in uploaded excelsheet does not belong to the same department at line '.$this->excel_line_number.'</div>';
                    $this->errors[] = 'user "'.$emailcode.'" in uploaded excelsheet exist in system at line '.$this->excel_line_number.'.';
                    $this->mfields[] = 'enrol';
                    $this->errorcount++;
            }
        }
    } // end of departmentprogram_validation function
    function incorrectprograme_validation($excel,$formdata){
        global $DB, $USER;
        $systemcontext = \context_system::instance();
        $emailcode = $excel->email;
        $programcode = $excel->programcode;
        $programshortname = $DB->get_record('local_program',array('id'=>$formdata->programid));
        if(!empty($programcode)){
            if($programcode != $programshortname->shortname){
                 echo '<div class=local_users_sync_error>Programcode entered in uploaded sheet does not belong to this program at line '.$this->excel_line_number.'</div>';
                $this->errors[] = 'Programcode entered in uploaded sheet does not belong to this program at line '.$this->excel_line_number.'.';
                $this->mfields[] = 'program';
                $this->errorcount++;
            }
        }    
    } 
    // end of incorrectprograme_validation function
    function emptysheet_validation(){
        global $DB, $USER;
        $systemcontext = \context_system::instance();
            echo '<div class=local_users_sync_error>Programcode and email are empty in uploaded sheet </div>';
                $this->errors[] = 'Programcode and email are empty in uploaded sheet';
                $this->mfields[] = 'program';
                $this->errorcount++;
    } 
}
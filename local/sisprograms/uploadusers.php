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
 *
 * @package    local
 * @subpackage sisprograms
 * @copyright  2019 onwards Sarath Kumar
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require('../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->libdir . '/csvlib.class.php');
require_once($CFG->libdir . '/accesslib.php');

require_once($CFG->dirroot.'/user/lib.php');

require_once('upload_sisusers_lib.php');
require_once('upload_sisusers_form.php');
require_once('lib.php');

$iid = optional_param('iid', '', PARAM_INT);
$previewrows = optional_param('previewrows', 10, PARAM_INT);

@set_time_limit(60 * 60); // 1 hour should be enough
raise_memory_limit(MEMORY_HUGE);

require_login();

$errorstr = get_string('error');
$stryes = get_string('yes');
$strno = get_string('no');
$stryesnooptions = array(0 => $strno, 1 => $stryes);

global $USER, $DB;
$systemcontext = context_system::instance();
$PAGE->set_context($systemcontext);

if (!$enrol_manual = enrol_get_plugin('manual')) {
    throw new coding_exception('Can not instantiate enrol_manual');
}
$PAGE->set_pagelayout('admin');
$PAGE->set_url('/local/sisprograms/uploadusers.php');
$PAGE->set_heading(get_string('uploadenrolments', 'local_sisprograms'));
$strheading = get_string('pluginname', 'local_sisprograms') . ' : ' . get_string('uploadenrollment', 'local_sisprograms');
$PAGE->set_title(get_string('sisprograms', 'local_sisprograms'));
$PAGE->navbar->add(get_string('pluginname', 'local_sisprograms'), new moodle_url('/local/sisprograms/index.php'));
$PAGE->navbar->add(get_string('uploadenrollment', 'local_sisprograms'));
$returnurl = new moodle_url('/local/sisprograms/index.php');

// array of all valid fields for validation
$STD_FIELDS = array('student_prn','coursecode', 'firstname','lastname','email', 'mobile','address','country','city','dob','gender','role');

$PRF_FIELDS = array();
$sisprogram = sisprograms::getInstance();

    $mform1 = new admin_user_enrolement_form1();
    if ($mform1->is_cancelled()) {
        redirect($returnurl);
    }elseif ($formdata = $mform1->get_data()) {
        echo $OUTPUT->header();
        echo $OUTPUT->heading(get_string('uploadenrollmentsresult', 'local_sisprograms'));
        $iid = csv_import_reader::get_new_iid('uploadenrollment');
        $cir = new csv_import_reader($iid, 'uploadenrollment'); //this class fromcsvlib.php(includes csv methods and clclasses)
        $content = $mform1->get_file_content('userfile');
        $readcount = $cir->load_csv_content($content, $formdata->encoding, $formdata->delimiter_name);
        // unset($content);
        if ($readcount === false) {
            print_error('csvloaderror', '', $returnurl);
        } else if ($readcount == 0) {
            print_error('csvemptyfile', 'error', $returnurl);
        }
        
        $filecolumns = uu_validate_enrollment_upload_columns($cir, $STD_FIELDS, $PRF_FIELDS, $returnurl);
        // test if columns ok(to validate the csv file content)
        $enrolmentnew = 0;
        $enrolmenterrors = 0;
        $enrolmentwarnings = 0;

        // init csv import helper
        $cir->init();
        $linenum = 1; //column header is first line
        $upt = new uu_progress_tracker();
        // $upt->start(); // start table
        $data = new stdclass();
        loop:

        while ($line = $cir->next()) {
            $upt->flush();
            $linenum++;

            $errors = array();
            $warnings = array();
            $mfields = array();
            $existerrors = array();
            $existmfields = array();

            $enrollment = new stdClass();
            // add fields to course object
            foreach ($line as $keynum => $value) {
                if (!isset($filecolumns[$keynum])) {
                    // this should not happen
                    continue;
                }
                $key = $filecolumns[$keynum];
                $enrollment->$key = $value;
            }

            $formdefaults = array();
            foreach ($STD_FIELDS as $field) {
                if (isset($enrollment->$field)) {
                    continue;
                }
                // all validation moved to form2
                if (isset($formdata->$field)) {
                    // process templates
                    $formdefaults[$field] = true;
                }
            }
            foreach ($PRF_FIELDS as $field) {
                if (isset($enrollment->$field)) {
                    continue;
                }
                if (isset($formdata->$field)) {
                    // process templates
                    $formdefaults[$field] = true;
                }
            }  
            
            if (empty($enrollment->student_prn)) {
                echo '<h3 style="color:red;">Please enter student_prn  in line  no. "' . $linenum . '" of uploaded .</h3>';
                $errors[] = 'Please enter student_prn  in line  no. "' . $linenum . '" of uploaded .';
                $mfields[] = 'student_prn';
                $enrolmenterrors++;
                //goto loop;
            }
            if (empty($enrollment->coursecode)) {
                echo '<h3 style="color:red;">Please enter coursecode  in line  no. "' . $linenum . '" of uploaded excelsheet.</h3>';
                $enrolmenterrors++;
                $errors[] = 'Please enter coursecode  in line  no. "' . $linenum . '" of uploaded .';
                $mfields[] = 'coursecode';
                //goto loop;
            }

            if (empty($enrollment->firstname)) {
                echo '<h3 style="color:red;">Please enter firstname  in line  no. "' . $linenum . '" of uploaded excelsheet.</h3>';
                $errors[] = 'Please enter firstname  in line  no. "' . $linenum . '" of uploaded .';
                $mfields[] = 'firstname';
                $enrolmenterrors++;
                //goto loop;
            } 
            if (empty($enrollment->lastname)) {
                echo '<h3 style="color:red;">Please enter lastname  in line  no. "' . $linenum . '" of uploaded .</h3>';
                $errors[] = 'Please enter lastname  in line  no. "' . $linenum . '" of uploaded .';
                $mfields[] = 'lastname';
                $enrolmenterrors++;
                //goto loop;
            }
             
            if (empty($enrollment->email)) {
                echo '<h3 style="color:red;">Please enter email  in line  no. "' . $linenum . '" of uploaded excelsheet.</h3>';
                $errors[] = 'Please enter email  in line  no. "' . $linenum . '" of uploaded .';
                $mfields[] = 'email';
                $enrolmenterrors++;
                //goto loop;
            }

            if (!validate_email($enrollment->email)) { 
                echo '<h3 style="color:red;">Please enter email in correct format in line  no. "' . $linenum . '" of uploaded excelsheet.</h3>';
                $errors[] = 'Please enter email in correct format is not exist  in line  no. "' . $linenum . '" of uploaded excelsheet.';
                $mfields[] = 'email';
                $enrolmenterrors++;
                //goto loop;
            } 
            /*if(!empty($enrollment->student_prn)){
                $prnidexist = $DB->get_field('user','id',array('idnumber' => $enrollment->student_prn));
                if($prnidexist){
                    $seconprnidexist = $DB->get_field_sql("SELECT id FROM {user} WHERE idnumber = :idnumber AND id != $prnidexist",array('idnumber' => $enrollment->student_prn));
                }
                if($seconprnidexist){
                    echo '<h3 style="color:red;">The given student_prn id is already exist to another user in line no. "' . $linenum . '" of uploaded excelsheet.</h3>';
                    $errors[] = 'The given student_prn id is already exist to another user in line  no. "' . $linenum . '" of uploaded excelsheet.';
                    $mfields[] = 'student_prn';
                    $enrolmenterrors++;
                    //goto loop;
                }
            }

            if(!empty($enrollment->email)){
                $emailexist = $DB->get_field('user','id',array('email' => $enrollment->email));
                if($emailexist){
                    $seconemailexist = $DB->get_field_sql("SELECT id FROM {user} WHERE email =:email AND id != $emailexist",array('email' => $enrollment->email));
                }
                if($seconemailexist){
                    echo '<h3 style="color:red;">The given email id is already exist to another user in line  no. "' . $linenum . '" of uploaded excelsheet.</h3>';
                    $errors[] = 'The given email id is already exist to another user in line no. "' . $linenum . '" of uploaded excelsheet.';
                    $mfields[] = 'email';
                    $enrolmenterrors++;
                    //goto loop;
                }
            }*/

            // Added by Harish to show validations if email not belongs to the given PRN user starts here//
            if(!empty($enrollment->email) && !empty($enrollment->student_prn)){
                $prnidexist = $DB->get_field('user','id',array('idnumber' => $enrollment->student_prn));
                $emailexist = $DB->get_field('user','id',array('email' => $enrollment->email, 'idnumber' => $enrollment->student_prn));
                
                if($emailexist != $prnidexist){
                    echo '<h3 style="color:red;">The entered email id is not belongs to the given user of PRN "' . $enrollment->student_prn . '" in line  no. "' . $linenum . '" of uploaded excelsheet.</h3>';
                    $errors[] = 'The entered email id is not belongs to the given user of PRN "' . $enrollment->student_prn . '" in line  no. "' . $linenum . '" of uploaded excelsheet.';
                    $mfields[] = 'email';
                    $enrolmenterrors++;
                    goto loop;
                }
            }
            // Added by Harish to show validations if email not belongs to the given PRN user ends here//
            $validfieldscount = 7;
            //  of email & id number not exist//
            if(!$emailexist && !$prnidexist){
                if (empty($enrollment->role)) {
                    echo '<h3 style="color:red;">Please enter role  in line  no. "' . $linenum . '" of uploaded excelsheet.</h3>';
                    $errors[] = 'Please enter role in line  no. "' . $linenum . '" of uploaded excelsheet.';
                    $mfields[] = 'role';
                    $enrolmenterrors++;
                    //goto loop;
                }
                if(!empty($enrollment->role)){
                    $roleid = $DB->get_field('role','id',array('shortname' => $enrollment->role));
                    if(!$roleid){
                        echo '<h3 style="color:red;">The given role is not exist in line  no. "' . $linenum . '" of uploaded excelsheet.</h3>';
                        $errors[] = 'The given role is not exist  in line  no. "' . $linenum . '" of uploaded excelsheet.';
                        $mfields[] = 'role';
                        $enrolmenterrors++;
                        $validfieldscount--;
                       // goto loop;
                    }
                }  
                if (empty($enrollment->mobile)) {
                    echo '<h3 style="color:red;">you have missed mobile  in line  no. "' . $linenum . '" of uploaded excelsheet.</h3>';
                    $warnings[] = 'you have missed mobile  in line  no. "' . $linenum . '" of uploaded excelsheet.';
                    $mfields[] = 'mobile';
                    $enrolmentwarnings++;
                }

                if (!empty($enrollment->mobile) && (!is_numeric($enrollment->mobile) || !preg_match("/^[6-9][0-9]{9}$/", $enrollment->mobile))) {
                    echo '<h3 style="color:red;">Please enter valid mobile number in line no. "' . $linenum . '" of uploaded excelsheet.</h3>';
                    $errors[] = 'Please enter valid mobile number in line no. "' . $linenum . '" of uploaded excelsheet.';
                    $mfields[] = 'mobile';
                    $enrolmentwarnings++;
                    $validfieldscount--;
                }
                if (empty($enrollment->address)) {
                    echo '<h3 style="color:red;">you have missed address in line  no. "' . $linenum . '" of uploaded excelsheet.</h3>';
                    $errors[] = 'you have missed address in line  no. "' . $linenum . '" of uploaded excelsheet.';
                    $mfields[] = 'address';
                    $enrolmentwarnings++;
                }

                if (!empty($enrollment->address) && is_numeric($enrollment->address)) {
                    echo '<h3 style="color:red;">Please enter valid address in line no. "' . $linenum . '" of uploaded excelsheet.</h3>';
                    $errors[] = 'Please enter valid address in line no. "' . $linenum . '" of uploaded excelsheet.';
                    $mfields[] = 'address';
                    $enrolmentwarnings++;
                    $validfieldscount--;
                }

                if (empty($enrollment->country)) {
                    echo '<h3 style="color:red;">you have missed country  in line  no. "' . $linenum . '" of uploaded excelsheet.</h3>';
                    $errors[] = 'you have missed country  in line  no. "' . $linenum . '" of uploaded excelsheet.';
                    $mfields[] = 'country';
                    $enrolmentwarnings++;
                }

                $country = get_string_manager()->get_list_of_countries();
                if(!empty($enrollment->country) && !array_key_exists($enrollment->country, $country)){
                   echo '<h3 style="color:red;">Please enter valid country in line no. "' . $linenum . '" of uploaded excelsheet.</h3>';
                    $errors[] = 'Please enter valid country in line  no. "' . $linenum . '" of uploaded excelsheet.';
                    $mfields[] = 'country';
                    $enrolmentwarnings++;
                    $validfieldscount--;
                }
                
                if (empty($enrollment->city)) {
                    echo '<h3 style="color:red;">you have missed city in line  no. "' . $linenum . '" of uploaded excelsheet.</h3>';
                    $errors[] = 'you have missed city  in line  no. "' . $linenum . '" of uploaded excelsheet.';
                    $mfields[] = 'city';
                    $enrolmentwarnings++;
                }
                if (!empty($enrollment->city) && !is_string($enrollment->city)) {
                    echo '<h3 style="color:red;">Please enter valid city in line no. "' . $linenum . '" of uploaded excelsheet.</h3>';
                    $errors[] = 'Please enter valid city in line no. "' . $linenum . '" of uploaded excelsheet.';
                    $mfields[] = 'city';
                    $enrolmentwarnings++;
                    $validfieldscount--;
                }

                if (empty($enrollment->dob)) {
                    echo '<h3 style="color:red;">you have missed dob  in line  no. "' . $linenum . '" of uploaded excelsheet.</h3>';
                    $errors[] = 'you have missed dob  in line  no. "' . $linenum . '" of uploaded excelsheet.';
                    $mfields[] = 'dob';
                    $enrolmentwarnings++;
                }

                if($enrollment->dob){
                    list($dd,$mm,$yyyy) = explode('-',$enrollment->dob);
                    if (!checkdate($mm,$dd,$yyyy)) {
                        echo '<h3 style="color:red;">Please enter valid dob in line no. "' . $linenum . '" of uploaded excelsheet.</h3>';
                        $errors[] = 'Please enter valid dob in line no. "' . $linenum . '" of uploaded excelsheet.';
                        $mfields[] = 'dob';
                        $enrolmentwarnings++;
                        $validfieldscount--;
                    }
                }
                if (empty($enrollment->gender)) {
                    echo '<h3 style="color:red;">you have missed gender in line  no. "' . $linenum . '" of uploaded excelsheet.</h3>';
                    $errors[] = 'you have missed gender  in line  no. "' . $linenum . '" of uploaded excelsheet.';
                    $mfields[] = 'gender';
                    $enrolmentwarnings++;
                }
                $gender = ($enrollment->gender == 'female' || $enrollment->gender == 'male') ? $enrollment->gender : false;
                if (!empty($enrollment->gender) && !$gender){
                    echo '<h3 style="color:red;">Please enter valid gender i.e.male/female in line  no. "' . $linenum . '" of uploaded excelsheet.</h3>';
                    $errors[] = 'Please enter valid gender i.e.male/female in line no. "' . $linenum . '" of uploaded excelsheet.';
                    $mfields[] = 'gender';
                    $enrolmentwarnings++;
                    $validfieldscount--;
                }
                if(count($errors) > 0){
                    $sisprogram->syncerrors_preparingobject($enrollment,$errors,$mfields);
                    goto loop;
                }
            }//End of email & id number not exist//
            if(!empty($enrollment->coursecode) && !empty($enrollment->student_prn) && $validfieldscount == 7){
                $courseid = $DB->get_field('course','id',array('shortname' => $enrollment->coursecode));
                $costcenterid = $DB->get_field('local_sisonlinecourses','costcenterid',array('coursecode' => $enrollment->coursecode));
                $siscourseid = $DB->get_field('local_sisonlinecourses','courseid',array('coursecode' => $enrollment->coursecode));
                $sisrecord = $DB->get_record('local_sisonlinecourses',array('coursecode' => $enrollment->coursecode));
                $programname = $DB->get_field('local_sisprograms','fullname',array('id' => $sisrecord->programid));
                $schoolname = $DB->get_field('local_costcenter','fullname',array('id' => $sisrecord->costcenterid));
                $coursename = $DB->get_field('course','fullname',array('id' => $sisrecord->courseid));

                if(!$courseid || !$siscourseid){
                    echo '<h3 style="color:red;">The given coursecode"'.$enrollment->coursecode.'" is not exist in line  no. "' . $linenum . '" of uploaded excelsheet.</h3>';
                    $existerrors[] = 'The given coursecode"'.$enrollment->coursecode.'" is not exist in line  no. "' . $linenum . '" of uploaded excelsheet.';
                    $existmfields[] = 'coursecode';
                    $sisprogram->syncerrors_preparingobject($enrollment,$existerrors,$existmfields);
                    $enrolmenterrors++;
                    goto loop;
                }
                $sisprnuserid = $DB->get_field('user','id',array('idnumber' => $enrollment->student_prn,'deleted' => 0,'suspended' => 0));
                $sisuseremail = $DB->get_field('user','id',array('email' => $enrollment->email,'deleted' => 0,'suspended' => 0));
           
                if(!$sisprnuserid && !$sisuseremail){
                    $userdata = new stdClass();
                    $userdata->confirmed = 1;
                    $userdata->deleted = 0;
                    $userdata->auth = 'manual';
                    $userdata->policyagreed = 0;
                    $userdata->suspended = 0;
                    $userdata->mnethostid = 1;
                    $userdata->username = strtolower($enrollment->student_prn);
                    $userdata->password = 'Welcome#3';
                    $userdata->firstname = $enrollment->firstname;
                    $userdata->lastname = $enrollment->lastname;
                    $userdata->idnumber = $enrollment->student_prn;
                    $userdata->open_employeeid = $enrollment->student_prn;
                    $userdata->email = $enrollment->email;
                    $userdata->phone1 = $enrollment->mobile;
                    $userdata->address = $enrollment->address;
                    $userdata->country = $enrollment->country;
                    $userdata->city = $enrollment->city;
                    $userdata->open_costcenterid = $costcenterid;
                    // Fetching roleid //
                    $roleid = $DB->get_field('role', 'id', array('shortname' => "$enrollment->role"));
                    $userdata->open_role = $roleid;
                    $sisprnuserid = user_create_user($userdata);

                    $sisuserdata = new stdClass();
                    $sisuserdata->sisprnid = $enrollment->student_prn;
                    $sisuserdata->roleid = $roleid;
                    $sisuserdata->costcenterid = $costcenterid;
                    $sisuserdata->mdluserid = $sisprnuserid;
                    $sisuserdata->dob = strtotime($enrollment->dob);
                    $sisuserdata->gender = $enrollment->gender;
                    $sisuserdata->timecreated = time(); 
                    $sisuserdata->timemodified = 0; 
                    $sisuserdata->usercreated = $USER->id; 
                    $sisuserdata->usermodified = 0; 
                    $sisuserid = $DB->insert_record('local_sisuserdata',$sisuserdata);
                    $contextid = 1;
                    if($roleid != 5 && $enrollment->role != 'student'){
                        $assignedrole = role_assign($roleid, $sisprnuserid,SITEID);
                    }
                    
                }
                if($sisprnuserid){
                    if($enrollment->role){
                        $roleid = $DB->get_field('role', 'id', array('shortname' => "$enrollment->role")); 
                    }
                    $enrolid = $DB->get_field('enrol', 'id',array('courseid'=>$courseid, 'enrol'=>'manual'));
                    if(!$DB->record_exists('user_enrolments',  array('enrolid' => $enrolid, 'userid' => $sisprnuserid))){
                    // if($enrolid){
                        $instance = $DB->get_record('enrol', array('id'=>$enrolid, 'enrol'=>'manual'), '*', MUST_EXIST);
                        $enrol_manual->enrol_user($instance, $sisprnuserid, $roleid);
                    // }
                    }else{
                        $coursename = $DB->get_field('course', 'fullname' ,array('id' => $courseid));
                        echo '<h3 style="color:red;">User already enrolled to "' . $coursename . '" course in line no. "' . $linenum . '" of uploaded excelsheet.</h3>';
                        $existerrors[] = 'User already enrolled to "'.$coursename.'" in line no. "' . $linenum . '" of uploaded excelsheet.';
                        $existmfields[] = 'useralreadyenrolled';
                        $sisprogram->syncerrors_preparingobject($enrollment,$existerrors,$existmfields);
                        $enrolmenterrors++;
                        goto loop;
                        // $enrolmentwarnings++;
                    }
                    $result = $DB->record_exists('local_courseenrolments',  array('courseid' => $courseid, 'mdluserid' => $sisprnuserid,'programid' => $sisrecord->programid));
                    if(!$DB->record_exists('local_courseenrolments',  array('courseid' => $courseid, 'mdluserid' => $sisprnuserid,'programid' => $sisrecord->programid))){
                    $courseenrol = new stdClass();
                    $courseenrol->courseid = $courseid;
                    $courseenrol->costcenterid = $costcenterid;
                    $courseenrol->programid = $sisrecord->programid;
                    $courseenrol->mdluserid = $sisprnuserid;
                    $courseenrol->sisuserid = $sisuserid;
                    $courseenrol->roleid = $roleid;
                    $courseenrol->coursename = $coursename;
                    $courseenrol->schoolname = $schoolname;
                    $courseenrol->programname = $programname;
                    $courseenrol->timecreated = time(); 
                    $courseenrol->timemodified = 0; 
                    $courseenrol->usercreated = $USER->id; 
                    $courseenrol->usermodified = 0; 
                    $enrolmentid = $DB->insert_record('local_courseenrolments',$courseenrol);
                    $enrolmentnew++;
                    }
                }
            }
        }

        $cir->cleanup(true);
        echo $OUTPUT->box_start('boxwidthnarrow boxaligncenter generalbox', 'uploadresults');
        echo '<p>';
            if ($enrolmenterrors)
                echo get_string('errors', 'local_sisprograms') . ': ' . $enrolmenterrors;
        echo'</p>';

        if ($enrolmenterrors) {
            echo '<h4>Please fill the sheet without any errors. Refer Help Manual for assistance.</h4>';
        }
        if ($enrolmentnew) {
            echo get_string('enrolmentscreated', 'local_sisprograms') . ': ' . $enrolmentnew . '<br />';
        }

        if ($enrolmentwarnings) {
            echo get_string('enrolmentwarnings', 'local_sisprograms') . ': ' . $enrolmentwarnings . '<br />';
        } 

        echo $OUTPUT->box_end();
        echo '<div style="margin-left:35%;"><a href="sync_errors.php"><button>Continue</button></a></div>';
       
        echo $OUTPUT->footer();
        die;
    } else {
        echo $OUTPUT->header();
        // echo $OUTPUT->heading(get_string('uploadenrolments', 'local_sisprograms'));

        // Current tab
        $currenttab = 'uploadenrolment';
        //adding tabs
        $sisprogram->createtabview($currenttab);
        echo '<div class="pull-right ml-10px"><a href="enrollmentsample.php?format=csv"><button>' . get_string('enrolample_excel', 'local_sisprograms') . '</button></a></div>';
        echo '<div class="pull-right ml-10px"><a href="enrollmenthelp.php"><button>' . get_string('enrolhelp_manual', 'local_sisprograms') . '</button></a></div>';
        $mform1->display();
        echo $OUTPUT->footer();
        die;
    }
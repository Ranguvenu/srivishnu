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
require_once('upload_siscourse_lib.php');
require_once('upload_siscourse_form.php');
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

$PAGE->set_pagelayout('admin');
$PAGE->set_url('/local/sisprograms/upload.php');
// $PAGE->set_heading($SITE->fullname);
$PAGE->set_heading(get_string('managemasterdata', 'local_sisprograms'));
$strheading = get_string('pluginname', 'local_sisprograms') . ' : ' . get_string('uploadcourses', 'local_sisprograms');
$PAGE->set_title(get_string('sisprograms', 'local_sisprograms'));
$PAGE->navbar->add(get_string('pluginname', 'local_sisprograms'), new moodle_url('/local/sisprograms/index.php'));
$PAGE->navbar->add(get_string('uploadcourses', 'local_sisprograms'));
$returnurl = new moodle_url('/local/sisprograms/index.php');

// array of all valid fields for validation
$STD_FIELDS = array('subjectcode', 'subjectname','programcode','programname', 'duration','runningfromyear','universitycode','universityname');

$PRF_FIELDS = array();
$sisprogram = sisprograms::getInstance();
// print_object($sisprogram);
    $mform1 = new admin_course_form1();
    if ($mform1->is_cancelled()) {
        redirect($returnurl);
    }elseif ($formdata = $mform1->get_data()) {
        echo $OUTPUT->header();
        echo $OUTPUT->heading(get_string('uploadcoursesresult', 'local_sisprograms'));
        $iid = csv_import_reader::get_new_iid('uploadcourse');
        $cir = new csv_import_reader($iid, 'uploadcourse'); //this class fromcsvlib.php(includes csv methods and clclasses)
        $content = $mform1->get_file_content('userfile');
        $readcount = $cir->load_csv_content($content, $formdata->encoding, $formdata->delimiter_name);
        // unset($content);
        if ($readcount === false) {
            print_error('csvloaderror', '', $returnurl);
        } else if ($readcount == 0) {
            print_error('csvemptyfile', 'error', $returnurl);
        }
        // test if columns ok(to validate the csv file content)
        $filecolumns = uu_validate_course_upload_columns($cir, $STD_FIELDS, $PRF_FIELDS, $returnurl);

        $coursenew = 0;
        $courseerrors = 0;

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
            $course = new stdClass();
            // add fields to course object
            foreach ($line as $keynum => $value) {
                if (!isset($filecolumns[$keynum])) {
                    // this should not happen
                    continue;
                }
                $key = $filecolumns[$keynum];
                $course->$key = $value;
            }

            $formdefaults = array();
            foreach ($STD_FIELDS as $field) {
                if (isset($course->$field)) {
                    continue;
                }
                // all validation moved to form2
                if (isset($formdata->$field)) {
                    // process templates
                    $formdefaults[$field] = true;
                }
            }
            foreach ($PRF_FIELDS as $field) {
                if (isset($course->$field)) {
                    continue;
                }
                if (isset($formdata->$field)) {
                    // process templates
                    $formdefaults[$field] = true;
                }
            }  
           
            if (empty($course->subjectcode)) {
                echo '<h3 style="color:red;">Please enter subjectcode  in line  no. "' . $linenum . '" of uploaded .</h3>';
                $courseerrors++;
                goto loop;
            }
            if (is_numeric($course->subjectcode)) {
                echo '<h3 style="color:red;">Entered subject code contains only numeric numbers.Please enter valid subjectcode in line  no. "' . $linenum . '" of uploaded .</h3>';
                $courseerrors++;
                goto loop;
            }//$course->subjectcode validation for not allowing only special characters //
            if (!preg_match('/[A-Za-z]+/', $course->subjectcode) ) {
                echo '<h3 style="color:red;">Entered subject code contains only special characters.Please enter valid subjectcode in line  no. "' . $linenum . '" of uploaded .</h3>';
                $courseerrors++;
                goto loop;
            }
            if (empty($course->subjectname)) {
                echo '<h3 style="color:red;">Please enter subjectname  in line  no. "' . $linenum . '" of uploaded excelsheet.</h3>';
                $courseerrors++;
                goto loop;
            }
            if (is_numeric($course->subjectname)) {
                echo '<h3 style="color:red;">Entered subject name contains only numeric numbers.Please enter valid subjectcode in line  no. "' . $linenum . '" of uploaded .</h3>';
                $courseerrors++;
                goto loop;
            }//$course->subjectname validation for not allowing only special characters //
            if (!preg_match('/[A-Za-z]+/', $course->subjectname) ) {
                echo '<h3 style="color:red;">Entered subject name contains only special characters.Please enter valid subjectname in line  no. "' . $linenum . '" of uploaded .</h3>';
                $courseerrors++;
                goto loop;
            }
            if (empty($course->programcode)) {
                echo '<h3 style="color:red;">Please enter program shortname  in line  no. "' . $linenum . '" of uploaded excelsheet.</h3>';
                $courseerrors++;
                goto loop;
            }
            if (is_numeric($course->programcode)) {
                echo '<h3 style="color:red;">Entered program code contains only numeric numbers.Please enter valid programcode in line  no. "' . $linenum . '" of uploaded .</h3>';
                $courseerrors++;
                goto loop;
            }//$course->programcode validation for not allowing only special characters //
            if (!preg_match('/[A-Za-z]+/', $course->programcode) ) {
                echo '<h3 style="color:red;">Entered program code contains only special characters.Please enter valid programcode in line  no. "' . $linenum . '" of uploaded .</h3>';
                $courseerrors++;
                goto loop;
            }
            if (empty($course->programname)) {
                echo '<h3 style="color:red;">Please enter programname  in line  no. "' . $linenum . '" of uploaded .</h3>';
                $courseerrors++;
                goto loop;
            }
            if (is_numeric($course->programname)) {
                echo '<h3 style="color:red;">Entered program name contains only numeric numbers.Please enter valid programname in line  no. "' . $linenum . '" of uploaded .</h3>';
                $courseerrors++;
                goto loop;
            }//$course->programname validation for not allowing only special characters //
            if (!preg_match('/[A-Za-z]+/', $course->programname) ) {
                echo '<h3 style="color:red;">Entered program name contains only special characters.Please enter valid programname in line  no. "' . $linenum . '" of uploaded .</h3>';
                $courseerrors++;
                goto loop;
            }
            if (empty($course->universitycode)) {
                echo '<h3 style="color:red;">Please enter universitycode  in line  no. "' . $linenum . '" of uploaded excelsheet.</h3>';
                $courseerrors++;
                goto loop;
            }
            if (is_numeric($course->universitycode)) {
                echo '<h3 style="color:red;">Entered university code contains only numeric numbers.Please enter valid universitycode in line  no. "' . $linenum . '" of uploaded .</h3>';
                $courseerrors++;
                goto loop;
            }//$course->universitycode validation for not allowing only special characters //
            if (!preg_match('/[A-Za-z]+/', $course->universitycode) ) {
                echo '<h3 style="color:red;">Entered university code contains only special characters.Please enter valid universitycode in line  no. "' . $linenum . '" of uploaded .</h3>';
                $courseerrors++;
                goto loop;
            }
            if (empty($course->universityname)) {
                echo '<h3 style="color:red;">Please enter universityname  in line  no. "' . $linenum . '" of uploaded excelsheet.</h3>';
                $courseerrors++;
                goto loop;
            }
            if (is_numeric($course->universityname)) {
                echo '<h3 style="color:red;">Entered university name contains only numeric numbers.Please enter valid universityname in line  no. "' . $linenum . '" of uploaded .</h3>';
                $courseerrors++;
                goto loop;
            }//$course->universityname validation for not allowing only special characters //
            if (!preg_match('/[A-Za-z]+/', $course->universityname) ) {
                echo '<h3 style="color:red;">Entered university name contains only special characters.Please enter valid universityname in line  no. "' . $linenum . '" of uploaded .</h3>';
                $courseerrors++;
                goto loop;
            }  
            if (empty($course->duration)) {
                echo '<h3 style="color:red;">Please enter duration  in line  no. "' . $linenum . '" of uploaded excelsheet.</h3>';
                $courseerrors++;
                goto loop;
            }
           /* if (!is_numeric($course->duration)) {
                echo '<h3 style="color:red;">Duration should be numeric numbers in line  no. "' . $linenum . '" of uploaded excelsheet.</h3>';
                $courseerrors++;
                goto loop;
            }*/
            if (empty($course->runningfromyear)) {
                echo '<h3 style="color:red;">Please enter runningfromyear  in line  no. "' . $linenum . '" of uploaded excelsheet.</h3>';
                $courseerrors++;
                goto loop;
            }
            if (!is_numeric($course->runningfromyear)) {
                echo '<h3 style="color:red;">Running year should be numeric numbers in line  no. "' . $linenum . '" of uploaded excelsheet.</h3>';
                $courseerrors++;
                goto loop;
            }

            if(!empty($course->subjectcode)){
                $courseexists = $DB->record_exists('course',array('shortname' => $course->subjectcode));

                /*university creation*/
                $universityid = $DB->get_field('local_costcenter','id',array('shortname' => $course->universitycode));
                $coursecategoryid = $DB->get_field('course_categories','id',array('name' => $course->universityname));

                $programid = $DB->get_field('local_sisprograms','id',array('programcode' => $course->programcode,'costcenterid' => $universityid));

                $courseid = $DB->get_field('course','id',array('shortname' => $course->coursecode));

                $siscourseexists = $DB->record_exists('local_sisonlinecourses',array('courseid' => $courseid,'programid' => $programid,'costcenterid' => $universityid));


                if($courseexists){
                    echo '<h3 style="color:red;">Subjectcode is already exist in line  no. "' . $linenum . '" of uploaded excelsheet.</h3>';
                    $courseerrors++;
                    goto loop;
                }elseif($siscourseexists){
                    echo '<h3 style="color:red;">Subjectcode and programcode and universitycode combination is already exist in line  no. "' . $linenum . '" of uploaded excelsheet.</h3>';
                    $courseerrors++;
                    goto loop;
                }else{
                    if(!$universityid){
                        $school = new stdClass();
                        $school->parentid = 0; 
                        $school->fullname = $course->universityname;
                        $school->shortname = $course->universitycode;
                        $school->description = '';
                        $school->type = 4;
                        $school->visible = 1; 
                        $school->timecreated = time(); 
                        $school->timemodified = 0; 
                        $school->usermodified = $USER->id; 
                        $school->childpermission = 0; 
                        $school->theme = 'colms';
                        $universityid = $sisprogram->university_creation($school);
                    }
                    
                    if(!$coursecategoryid){
                        $category = new stdClass();
                        $category->parent = 0; 
                        $category->name = $course->universityname;
                        $category->description = '';
                        $category->idnumber = '';
                        $category->descriptionformat =0;
                        $category->visible = 1; 
                        $category->visibleold = 1;
                        $category->coursescount = 0;
                        $category->timemodified = time(); 
                        $category->theme = '';
                        $coursecategoryid = $sisprogram->coursecategory_creation($category);
                    }


                    /*programs creation*/
                    

                    if(!$programid){
                        $programobject = new stdClass();
                        $programobject->programcode = $course->programcode; 
                        $programobject->fullname = $course->programname;
                        $programobject->shortname = $course->programcode;
                        $programobject->type = 0;
                        $programobject->description = '';
                        $programobject->costcenterid = $universityid;
                        $programobject->duration = $course->duration ; 
                        $programobject->runningfromyear = $course->runningfromyear; 
                        $programobject->visible = 1; 
                        $programobject->timecreated = time(); 
                        $programobject->timemodified = 0; 
                        $programobject->usercreated = $USER->id; 
                        $programobject->usermodified = 0; 
                        $programid = $DB->insert_record('local_sisprograms',$programobject);
                    }

                    
                    if(!$courseid){

                        /*course creation*/
                        $coursedata = new stdClass();
                        $coursedata->fullname = $course->subjectname;
                        $coursedata->shortname = $course->subjectcode;
                        $coursedata->category = $coursecategoryid;
                        $courseid = $sisprogram->moodlecourse_create($coursedata);

                        /*siscourse creation*/
                        $siscourse = new stdClass();
                        $siscourse->coursecode = $courseid->shortname;
                        $siscourse->courseid = $courseid->id;
                        $siscourse->programid = $programid;
                        $siscourse->programcode = $course->programcode;
                        $siscourse->costcenterid = $universityid;
                        $siscourse->schoolname = $course->universityname;
                        $siscourse->coursetype = '';
                        $siscourse->sissourceid = 0;
                        $siscourse->timecreated = time(); 
                        $siscourse->timemodified = 0; 
                        $siscourse->usercreated = $USER->id; 
                        $siscourse->usermodified = 0; 
                        $siscourseid = $DB->insert_record('local_sisonlinecourses',$siscourse);
                        $coursenew++;
                    }
                }
            }
        }

        $cir->cleanup(true);
        echo $OUTPUT->box_start('boxwidthnarrow boxaligncenter generalbox', 'uploadresults');
        echo '<p>';
            if ($courseerrors)
                echo get_string('errors', 'local_sisprograms') . ': ' . $courseerrors;
        echo'</p>';

        if ($courseerrors) {
            echo '<h4>Please fill the sheet without any errors. Refer Help Manual for assistance.</h4>';
        }
        if ($coursenew) {
            echo get_string('coursescreated', 'local_sisprograms') . ': ' . $coursenew . '<br />';
        }
        echo $OUTPUT->box_end();
        echo '<div style="margin-left:35%;"><a href="courses.php"><button>Continue</button></a></div>';
       
        echo $OUTPUT->footer();
        die;
    } else {
        echo $OUTPUT->header();
        echo '<div class="col-12 page-desc"><b class="page-hrdesc">Description:</b><br>
This page allows university admin to upload "Masterdata" about regular programs. Masterdata could be information about "online courses" under any regular program, student enrollments to such online programs. Reports to see the uploaded online courses, student enrollments, upload errors report etc. are also provided. Help on using this feature and sample upload sheets are also provided on this screen.
</div>';
        // echo $OUTPUT->heading(get_string('uploadcourses', 'local_sisprograms'));

        // Current tab
        $currenttab = 'upload';
        //adding tabs
        $sisprogram->createtabview($currenttab);
        echo '<div class="pull-right ml-10px"><a href="sample.php?format=csv"><button>' . get_string('sample_excel', 'local_sisprograms') . '</button></a></div>';
        echo '<div class="pull-right ml-10px"><a href="help.php"><button>' . get_string('dept_manual', 'local_sisprograms') . '</button></a></div>';
        $mform1->display();
        echo $OUTPUT->footer();
        die;
    }



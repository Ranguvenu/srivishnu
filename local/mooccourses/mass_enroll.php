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
 * Bulk user registration script from a comma separated file
 *
 * @package    tool
 * @subpackage user
 * @copyright  2004 onwards Martin Dougiamas (http://dougiamas.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once('../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->libdir . '/csvlib.class.php');
require_once($CFG->dirroot.'/local/mooccourses/classes/form/mass_enroll_form.php');
       

require_once($CFG->dirroot . '/local/lib.php'); 

 $iid = optional_param('iid', '', PARAM_INT);

$courseid = optional_param('id','', PARAM_INT);


require_login();

$errorstr = get_string('error');
$stryes = get_string('yes');
$strno = get_string('no');
$stryesnooptions = array(0 => $strno, 1 => $stryes);

$systemcontext = context_system::instance();
$PAGE->set_context($systemcontext);

$PAGE->set_pagelayout('admin');
global $USER, $DB , $OUTPUT;

$returnurl = new moodle_url('/local/mooccourses/index.php?type=2');


$PAGE->set_url('/local/mooccourses/mass_enroll.php');

$PAGE->set_heading(get_string('bulkuploadenrolusers', 'local_mooccourses'));
$strheading = get_string('pluginname', 'local_mooccourses') . ' : ' . get_string('bulkenrolments', 'local_mooccourses');
$PAGE->set_title($strheading);
$PAGE->navbar->add(get_string('pluginname', 'local_mooccourses'), new moodle_url('/local/mooccourses/index.php?type=2'));
$PAGE->navbar->add(get_string('uploadenrol', 'local_mooccourses'));
$returnurl = new moodle_url('/local/mooccourses/index.php?type=2');


$STD_FIELDS = array('username','email','role');


$PRF_FIELDS = array();
//-------- if variable $iid equal to zero,it allows enter into the form -------  


$mform1 = new mass_enrollform(null, array('id' => $courseid));
if ($mform1->is_cancelled()) {

	redirect($returnurl);
}
if ($formdata = $mform1->get_data()) {

      echo $OUTPUT->header();
     
	$iid = csv_import_reader::get_new_iid('userfile');
	$cir = new csv_import_reader($iid, 'userfile'); //this class fromcsvlib.php(includes csv methods and classes)
	$content = $mform1->get_file_content('userfile');
	//print_object($content);
	$readcount = $cir->load_csv_content($content, $formdata->encoding, $formdata->delimiter_name);
	//print_object($readcount);     
	$cir->init();
	$linenum = 1; //column header is first line
	// init upload progress tracker------this class used to keeping track of code(each rows and columns)-------------
	
	$progresslibfunctions = new local_users\cron\progresslibfunctions();
	//print_object($progresslibfunctions);
	$filecolumns = $progresslibfunctions->uu_validate_user_upload_columns($cir, $STD_FIELDS, $PRF_FIELDS, $returnurl);

	$hrms= new local_mooccourses\cron\cronfunctionality();
	
	$courseid = $formdata->courseid;
	
	$hrms->main_hrms_frontendform_method($cir,$filecolumns, $formdata,$courseid);
	 echo $OUTPUT->footer();
}
else{
	echo $OUTPUT->header();
	
	//Revathi Issue ODl-833 removing Bulk User Enrolment string starts
		//	echo $OUTPUT->heading(get_string('uploadenrol', 'local_mooccourses'));
	//Revathi Issue ODl-833 removing Bulk User Enrolment stringends

    echo html_writer::link(new moodle_url('/local/mooccourses/'),'Back',array('id'=>'download_users'));
	echo html_writer::link(new moodle_url('/local/mooccourses/sample.php?format=csv'),'Sample',array('id'=>'download_users'));
	echo html_writer::link(new moodle_url('/local/mooccourses/help.php'),'Help manual' ,array('id'=>'download_users','target'=>'__blank'));
	$mform1->display();
	echo $OUTPUT->footer();
	die;
}

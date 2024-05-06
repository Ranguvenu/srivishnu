<?php
$string['pluginname'] = "Mooc Courses";
$string['coursecode'] = "Course Code";
$string['university'] = "University";
$string['category'] = "Departments";
$string['points'] = "Points";
$string['cost'] = "Cost";
$string['manage_mooc_courses'] = "Manage Mooc Courses";
$string['mooccourses:cancel'] = 'mooccourses:cancel';
$string['create_mooccourse'] = 'Create Mooc Course from template';
$string['create_newcourse'] = 'Create Mooc Course from template';
$string['create_newmooccourse'] = 'Create Mooc Course';
$string['department'] = 'Department';
$string['apply'] = 'Apply';
$string['reset'] = 'Reset';
$string['action'] = 'Action';
$string['useastemplate'] = 'Use as Template';
$string['coursename'] = 'Course Name';
$string['courseshortname'] = 'Course Short Name';
$string['missingcoursename'] = 'Missing Course Name';
$string['lastnamecannotbeempty'] = 'Shortname cannot be empty';
$string['selectcategory'] = 'Create Mooc Course';
$string['courses'] = 'Mooc Courses';
$string['createnewcourse'] = 'Create Mooc Course';
$string['fullnamecourse'] = 'Course Name';
$string['mooccoursesummary'] = 'Summary';
$string['mooccoursessummary'] = 'Summary';
// Sandeep - Start date and End date Stings are changed Starts // 
$string['startdate'] = 'Start Date';
$string['summary'] = 'Summary';
$string['enddate'] = 'End Date';
// Sandeep - Start date and End date Stings are changed Ends // 
$string['selectdept'] = '--Select Department--';
$string['selectorg'] = '--Select University--';
$string['enrolment_date'] = 'Enrolment Start Date';
$string['edithead'] = 'Update Mooc Course';
$string['deleteconfirm'] = 'Are you sure, you want to delete this course?';
$string['actions'] = 'Actions';
$string['confirmation'] = 'Confirmation';
$string['departments'] = 'Departments';
$string['enroluser'] = 'Enrol Students';
$string['create_newstudent'] = 'Enrol Students';
$string['spacesnotallowed'] = 'Spaces are not allowed.Please enter valid value.';
$string['alert'] = 'Alert !';
$string['editcourse'] = 'Update Mooc Course';
$string['confirmmessage'] = 'You can only delete course when there are no user enrolments';
$string['editconfirmmessage_help'] = 'You can only edit course for dept when there are no user enrolments';
$string['editconfirmmessage'] = 'Departments';
$string['missingstudent'] = 'Please Select Students';
$string['missingshortname'] = 'Please enter Course Short Name';
$string['missingfullname'] = 'Please enter Course Name';
$string['nosameenddate'] = 'Admission End date should be greaterthan Admission Start date';
$string['enrolluserssuccess'] = '<b>{$a->changecount}</b> Students successfully enrolled to this <b>"{$a->course}"</b> course .';
$string['unenrolluserssuccess'] = '<b>{$a->changecount}</b> Students successfully un enrolled from this <b>"{$a->course}"</b> course .';
$string['enrollfacultysuccess'] = '<b>{$a->changecount}</b> Faculties successfully enrolled to this <b>"{$a->course}"</b> course .';
$string['unenrollfacultysuccess'] = '<b>{$a->changecount}</b> Faculties successfully un enrolled from this <b>"{$a->course}"</b> course .';
$string['enrollusers'] = 'Course <b>"{$a}"</b> enrollment is in process...';
$string['un_enrollusers'] = 'Course <b>"{$a}"</b> un enrollment is in process...';
$string['enrolfaculty'] = 'Enrol Faculty';
$string['facultymooccourseenrollments'] = 'Enroll Faculties to "{$a}" Course';
$string['studentmooccourseenrollments'] = 'Enroll Students to "{$a}" Course';
$string['affiliatecoursesdis'] = '<b>{$a}</b>';
$string['mooccoursetitle'] = 'Colleges cannot be affiliated from the course "<b>{$a}</b>" if there are students who have already opted this course under the study center/college.';
$string['affiliatecoursesprogress'] = 'Course <b>"{$a}"</b> Affiliation is in process...';
$string['affiliatecoursessuccess'] = '<b>{$a->changecount}</b> Colleges successfully affiliated to this <b>"{$a->course}"</b> course .';
$string['click_continue'] = 'Click on continue';
$string['unaffiliatecoursesprogress'] = 'Course <b>"{$a}"</b> Un Affiliation is in process...';
$string['unaffiliatecoursessuccess'] = '<b>{$a->changecount}</b> Colleges successfully un affiliated from this <b>"{$a->course}"</b> course .';
$string['copyprogramprogress'] = 'Course <b>"{$a}"</b> Copy is in process...';
$string['affiliatecourses'] = 'Affiliate Courses';
$string['costcannotbenonnumericwithargs'] ='Cost should be in numeric but given "{$a}"';
$string['viewmooccourse'] ='View Mooc Courses';

//added Bulkenrolment
$string['bulkuploadenroll'] ='Bulk Enrol Users For All Courses';
$string['bulkenrolments'] ='Bulk User Enrolment';
$string['bulkuploadenrolusers'] ='Bulk Enrol Users For Single Course';
$string['uploadenrol'] ='Bulk User Enrolment';
$string['options'] ='Option';
$string['Enrol'] ='Enroll to the course';
$string['button']='CONTINUE';


//added help
$string['back_upload'] = 'Back to Upload Users enrollment';
$string['helpmanual'] = 'Download sample Excel sheet and fill the field values in the format specified below.';
$string['manual'] = 'Help Manual';
$string['help_1'] = '<table class="generaltable" border="1">
<tr><td></td><td style="text-align:left;border-left:1px solid white;padding-left:50px;"><b>Mandatory Fields</b></td><tr>
<th>Field</th><th>Restriction</th>
<tr><td>email</td><td>Enter valid email(Must and Should).</td></tr>
<tr><td>Role</td><td>Enter Role Designation, avoid additional spaces.</td></tr>
';

//bulk enrolment add for help

$string['help'] = '<table class="generaltable" border="1">
<tr><td></td><td style="text-align:left;border-left:1px solid white;padding-left:50px;"><b>Mandatory Fields</b></td><tr>
<th>Field</th><th>Restriction</th>
<tr><td>email</td><td>Enter valid email(Must and Should).</td></tr>
<tr><td>Role</td><td>Enter Role Designation, avoid additional spaces.</td></tr>
<tr><td>coursecode</td><td>Enter the Course Shortname,avoid additional spaces.</td></tr>
';
$string['affiliateconfirmmessage'] = 'You cannot delete the course, because the course is affiliated to college/department';
$string['alldepartments'] = 'All Departments';


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
 * Language strings
 *
 * @package    local
 * @subpackage courses
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
$string['pluginname'] = 'Courses';
$string['university']='University';
$string['mooc'] = 'MOOC';
$string['ilt'] = 'Classroom';
$string['elearning'] = 'E-Learning';
$string['learningplan'] = 'Learning Path';
$string['type'] = 'Type';
$string['category'] = 'Category';
$string['department'] = 'Course Category';
$string['subject'] = 'Course Name';
$string['subjectid'] = 'Course Code';
$string['credithours'] = 'Points';

$string['enrolled'] = 'Enrolled Users';
$string['completed'] = 'Completed Users';
$string['manual_enrolment'] = 'Manual Enrollment';
$string['add_users']='<< Add Users';
$string['remove_users']='Remove Users >>';
$string['employeesearch']='Filter';
$string['agentsearch']='Agent Search';
$string['empnumber']='Employee ID';
$string['email']='Email';
$string['band'] = 'Band';
// $string['department']='Department';
$string['sub_departments']='Sub Departments';
$string['sub-sub-departments']='Sub Sub Departments';
$string['designation'] = 'Designation';
$string['im:already_in'] = '{$a} already enroled ';
$string['im:enrolled_ok'] = '{$a} enroled ';
$string['im:error_addg'] = 'Error in adding group {$a->groupe}  to course {$a->courseid} ';
$string['im:error_g_unknown'] = 'Error, unkown group {$a} ';
$string['im:error_add_grp'] = 'Error in adding grouping {$a->groupe} to course {$a->courseid}';
$string['im:error_add_g_grp'] = 'Error in adding group {$a->groupe} to grouping {$a->groupe}';
$string['im:and_added_g'] = ' and added to Moodle\'s  group  {$a}';
$string['im:error_adding_u_g'] = 'Error in adding to group  {$a}';
$string['im:already_in_g'] = ' already in group {$a}';
$string['im:stats_i'] = '{$a} enroled &nbsp&nbsp';
$string['im:stats_g'] = '{$a->nb} group(s) created : {$a->what} &nbsp&nbsp';
$string['im:stats_grp'] = '{$a->nb} grouping(s) created : {$a->what} &nbsp&nbsp';
$string['im:err_opening_file'] = 'error opening file {$a}';
$string['im:user_notcostcenter'] = '{$a->user} not assigned to {$a->csname} costcenter';
$string['mass_enroll'] = 'Bulk enrolments';
$string['mass_enroll_info'] =
"<p>
With this option you are going to enrol a list of known users from a file with one account per line
</p>
<p>
<b> The firstline </b> the empty lines or unknown accounts will be skipped. </p>
<p>
The file may contains one or two columns, separated by a comma, a semi-column or a tabulation.
<br/>
<b>The first one must contains a unique account identifier : idnumber (by default) login or email  </b> of the target user. <br/>
The second <b>if present,</b> contains the group's name in wich you want that user be be added. <br/>
You may repeat this operation at will without dammages, for example if you forgot or mispelled the target group.
</p>";
$string['email'] = 'Email Id';
$string['firstcolumn'] = 'First column contains';
$string['creategroups'] = 'Create group(s) if needed';
$string['creategroupings'] = 'Create  grouping(s) if needed';
$string['enroll'] = 'Enrol them to my course';
$string['im:user_unknown'] = '{$a} unknown - skipping line';
$string['points'] = 'Points';
$string['createnewcourse'] = 'Create Course';
$string['editcourse'] = 'Update Course';
$string['description']   = 'User with Username "{$a->userid}"  created the course  "{$a->courseid}"';
$string['desc']   = 'User with Username "{$a->userid}" has updated the course  "{$a->courseid}"';
$string['descptn']   = 'User with Username "{$a->userid}" has deleted the course with courseid  "{$a->courseid}"';
$string['usr_description']   = 'User with Username "{$a->userid}" has created the user with Username  "{$a->user}"';
$string['usr_desc']   = 'User with Username "{$a->userid}" has updated the user with Username  "{$a->user}"';
$string['usr_descptn']   = 'User with Username "{$a->userid}" has deleted the user with userid  "{$a->user}"';
$string['ilt_description']   = 'User with Username "{$a->userid}"  created the ilt  "{$a->f2fid}"';
$string['ilt_desc']   = 'User with Username "{$a->userid}" has updated the ilt "{$a->f2fid}"';
$string['ilt_descptn']   = 'User with Username "{$a->userid}" has deleted the ilt "{$a->f2fid}"';
$string['coursecompday'] = 'Course Completion Days';
$string['coursecreator'] = 'Course Creator';
$string['coursecode'] = 'Course Code';
$string['addcategory'] = '<i class="fa fa-book popupstringicon" aria-hidden="true"></i> Add New Category <div class= "popupstring"></div>';
$string['editcategory'] = '<i class="fa fa-book popupstringicon" aria-hidden="true"></i> Update Category <div class= "popupstring"></div>';
$string['coursecat'] = 'Course Departments';
$string['deletecategory'] = 'Delete Category';
$string['top'] = 'Top';
$string['parent'] = 'Parent';
$string['actions'] = 'Actions';
$string['count'] = 'Number of Courses';
$string['categorypopup'] = 'Category {$a}';
$string['missingtype'] = 'Missing Type';
$string['catalog'] = 'Catalog';
$string['nocoursedesc'] = 'No description provided';
$string['apply'] = 'Apply';
$string['open_costcenterid'] = 'Costcenter';
$string['uploadcoursespreview'] = 'Upload courses preview';
$string['uploadcoursesresult'] = 'Upload courses results';
$string['uploadcourses'] = 'Upload courses';
$string['coursefile'] = 'File';
$string['csvdelimiter'] = 'CSV delimiter';
$string['encoding'] = 'Encoding';
$string['rowpreviewnum'] = 'Preview rows';
$string['preview'] = 'Preview';
$string['courseprocess'] = 'Course process';
$string['shortnametemplate'] = 'Template to generate a shortname';
$string['templatefile'] = 'Restore from this file after upload';
$string['reset'] = 'Reset course after upload';
$string['defaultvalues'] = 'Default course values';
$string['enrol'] = 'Enrol';
$string['courseexistsanduploadnotallowedwithargs'] = 'Course is already exists with shortname "{$a}", please choose other unique shortname.';
$string['canonlycreatecourseincategoryofsameorganisation'] = 'You can only create the course under your assigned organisation';
$string['canonlycreatecourseincategoryofsameorganisationwithargs'] = 'Cannot create a course under the category "{$a}"';
$string['createcategory'] = 'Create New Category';
$string['manage_course'] = 'Manage Course';
$string['manage_courses'] = 'Manage Courses';
$string['leftmenu_browsecategories'] = 'Manage Categories';
$string['courseother_details'] = 'Other Details';
$string['view_courses'] = 'view courses';
$string['deleteconfirm'] = 'Are you sure, you want to delete "<b>{$a->name}</b>" course?</br> Once deleted, it can not be reverted.';
// $string['department'] = 'Department';
$string['coursecategory'] = 'Department';
$string['fullnamecourse'] = 'Course Name';
$string['coursesummary'] = 'Summary';
$string['courseoverviewfiles'] = 'Banner image';
$string['startdate'] = 'Start Date';
$string['enddate'] = 'End Date';
$string['program'] = 'Program';
$string['certification'] = 'Certification';
$string['create_newcourse'] = 'Create New Course';
$string['userenrolments'] = 'User enrolment';
$string['certificate'] = 'Certificate';
$string['points_positive'] = 'Points must be greater than 0';
$string['enrolusers'] = 'Enrol Users';
$string['grader'] = 'Grader';
$string['activity'] = 'Activity';
$string['courses'] = 'Courses';
$string['nocategories'] = 'No categories available';
$string['nosameenddate'] = '"End date" should not be less than "Start date"';

$string['help_1'] = '<table border="1">
<tr><td></td><td style="text-align:left;border-left:1px solid white;padding-left:50px;"><b>Mandatory Fields</b></td><tr>
<th>Field</th><th>Restriction</th>
<tr><td>fullname</td><td>fullname of the course.</td></tr>
<tr><td>shortname</td><td>Shortname of the course.</td></tr>
<tr><td>category_path</td><td>Path of the category.</td></tr>
<tr><td>coursetype</td><td>Type of the course(Comma seperated).</td></tr>
';
$string['help_2'] = '</td></tr>
<tr><td></td><td style="text-align:left;border-left:1px solid white;padding-left:50px;"><b>Normal Fields</b></td><tr>
<th>Field</th><th>Restriction</th>
<tr><td>Summary</td><td>Summary of the course.</td></tr>
<tr><td>Points</td><td>Points for the course.</td></tr>
<tr><td>Cost</td><td>Cost of the course.</td></tr>
<tr><td>Department</td><td>Shortname of the department.</td></tr>
</table>';
$string['back_upload'] = 'Back to upload courses';
$string['manual'] = 'Help manual';
$string['enrolledusers'] = 'Enrolled users';
$string['notenrolledusers'] = 'Not enrolled users';
$string['finishbutton'] = 'Finish';
$string['updatecourse'] = 'Update Course';
$string['course_name'] = 'Course Name';
$string['completed_users'] = 'Completed Users';
$string['course_filters'] = 'Course Filters';
$string['back'] = 'Back';
$string['sample'] = 'Sample';
$string['selectdept'] = '--Select Department--';
$string['selectorg'] = '--Select University--';
$string['selectcat'] = '--Select Department--';
//$string['select_cat'] = '--Select Categories--';
$string['reset'] = 'Reset';
$string['err_category'] = 'Please select Category';
$string['availablelist'] = '<b>Available users ({$a})</b>';
$string['selectedlist'] = 'Selected users';
$string['status'] = 'Status';
$string['select_all'] = 'Select All';
$string['remove_all'] = 'Un Select ';
$string['not_enrolled_users'] = '<b>Not Enrolled Users ({$a})</b>';
$string['enrolled_users'] = '<b> Enrolled Users ({$a})</b>';
$string['remove_selected_users'] = '<b> Un Enroll Users </b><i class="fa fa-arrow-right" aria-hidden="true"></i><i class="fa fa-arrow-right" aria-hidden="true"></i>';
$string['remove_all_users'] = '<b> Un Enroll All Users </b><i class="fa fa-arrow-right" aria-hidden="true"></i><i class="fa fa-arrow-right" aria-hidden="true"></i>';
$string['add_selected_users'] = '<i class="fa fa-arrow-left" aria-hidden="true"></i><i class="fa fa-arrow-left" aria-hidden="true"></i><b> Enroll Users</b>';
$string['add_all_users'] = ' <i class="fa fa-arrow-left" aria-hidden="true"></i><i class="fa fa-arrow-left" aria-hidden="true"></i> <b> Enroll All Users </b>';$string['course_status_popup'] = 'Activity status for {$a}';
$string['auto_enrol'] = 'Auto Enrol';
$string['need_manage_approval'] = 'Need Manager Approval';
$string['costcannotbenonnumericwithargs'] ='Cost should be in numeric but given "{$a}"';
$string['pointscannotbenonnumericwithargs'] ='Points should be in numeric but given "{$a}"';
$string['need_self_enrol'] = 'Need Self Enrol';
$string['enrolluserssuccess'] = '<b>{$a->changecount}</b> Employess successfully enrolled to this <b>"{$a->course}"</b> course .';
$string['unenrolluserssuccess'] = '<b>{$a->changecount}</b> Employess successfully un enrolled from this <b>"{$a->course}"</b> course .';

$string['enrollusers'] = 'Course <b>"{$a}"</b> enrollment is in process...';

$string['un_enrollusers'] = 'Course <b>"{$a}"</b> un enrollment is in process...';
$string['click_continue'] = 'Click on continue';
$string['bootcamp']= 'XSeeD';
$string['manage_br_courses'] = 'Manage <br/> courses';
$string['enrolleduserslist']='Users list of <b>\'{$a}\'</b> Course' ;
$string['coursevalidationbody'] = 'You cannot delete this course as it is being used under atleast one Program';
$string['availability'] = "Availability";
$string['cannot_change_status'] = "We can't change Status ";
$string['enrolment_date'] = "Enrolment Start Date";
$string['departments'] = "Departments";
$string['suspendconfirm'] = 'Are you sure, you want to {$a->action} {$a->fullname} ?';
// $string['availiabilityofcourse'] = 'Are you sure, you want change the status of "{$a->fullname}" ?';
$string['availiabilityofcourse'] = '<p>Are you sure, That you want to make this course "{$a->fullname}" as a template course ?</p><p>If yes, This course can be used as a template to create an independent course.';
$string['missingdepartment'] = 'Please select Department';
$string['missingcostcenter'] = 'Please select University';
$string['missingfullname'] = 'Please enter valid Course Name';
$string['missingshortname'] = 'Please enter valid Course Code';
$string['confirmation'] = 'Confirmation';
$string['spacesnotallowed'] = 'Spaces are not allowed.Please enter valid value.';
$string['enroledusers'] = 'We can\'t change the status as it has enrolled users';
$string['courseusagecurriculum'] = 'Course usage in Curriculums';
$string['mass_enroll_help'] = 'Help with bulk enrol';
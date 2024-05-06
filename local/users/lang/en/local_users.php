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
 * @subpackage users
 * @copyright  2013 Vinodkumar <avinod@eabyas.in>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
global $OUTPUT;
$string['costcenter'] = 'Organisation';
$string['employeesearch'] = 'Filter';
$string['subsubdepartment'] = 'subsubdepartment'; 
$string['msg_pwd_change'] = 'Hi {$a->username}<br>Your password changed successfully!';
$string['adduser'] = 'Add User';
$string['pluginname'] = 'Manage Users';
$string['selectrole'] = 'Select Role';
$string['assignrole'] = 'Assign Role';
$string['joiningdate'] = 'DATE OF JOINING';
$string['generaldetails'] = 'General Details';
$string['personaldetails'] = 'Personal Details';
$string['contactdetails'] = 'Contact Details';
$string['not_assigned'] = 'Not Assigned.';
$string['address'] = 'Address';

/*sarath added for teamdasbord view string*/
$string['usersinfo'] = '{$a->username} User Information';
$string['search'] = 'Search';
$string['enrolldate'] = 'Enroll Date';
$string['name'] = 'Name';
$string['code'] = 'Code';
$string['confirmation'] = 'Confirmation';

/*ended here by sarath*/

//$string['table_head'] = '' . get_string('semester', 'local_semesters') . ' (Course enrolled in)';
$string['userpicture'] = 'User Picture';
$string['newuser'] = 'New User';
$string['createuser'] = 'Create Student';
$string['edituser'] = 'Update Student <div class= "popupstring"></div>';
$string['updateuser'] = 'Update User';
$string['role'] = 'Role Assigned';
$string['browseusers'] = 'Manage Users';
$string['browseuserspage'] = 'This page allows the user to view the list of users with their profile details which also includes the login summary.';
$string['deleteuser'] = 'Delete User';
$string['delconfirm'] = 'Are you sure, you really want  to delete "{$a->name}" ?';
$string['deletesuccess'] = 'User "{$a->name}" deleted successfully.';
$string['usercreatesuccess'] = 'User "{$a->name}" created Successfully.';
$string['userupdatesuccess'] = 'User "{$a->name}" updated Successfully.';
$string['addnewuser'] = 'Add New User +';
$string['assignedcostcenteris'] = '{$a->label} is "{$a->value}"';
$string['validemailexists'] = 'Enter vaild email';
$string['givevaliddob'] = 'Give a valid Date of Birth';
$string['dateofbirth'] = 'Date of Birth';
$string['dateofbirth_help'] = 'User should have minimum 20 years age for today.';
$string['assignrole_help'] = 'Assign a role to the user in the selected Organisation.';
$string['siteadmincannotbedeleted'] = 'Site Administrator can not be deleted.';
$string['youcannotdeleteyourself'] = 'You can not delete yourself.';
$string['siteadmincannotbesuspended'] = 'Site Administrator can not be suspended.';
$string['youcannotsuspendyourself'] = 'You can not suspend yourself.';
$string['users:manage'] = 'Manage Users';
$string['manage_users'] = 'Manage Users';
$string['manage_faculties'] = 'Manage Faculties';
$string['users:view'] = 'View Users';
$string['users:create'] = 'users:create';
$string['users:delete'] ='users:delete';
$string['users:edit'] = 'users:edit';
$string['infohelp'] = 'Info/Help';
$string['report'] = 'Report';
$string['viewprofile'] = 'View Profile';
$string['myprofile'] = 'My Profile';
$string['adduserstabdes'] = 'This page allows you to add a new user. This can be one by filling up all the required fields and clicking on "submit" button.';
$string['edituserstabdes'] = 'This page allows you to modify details of the existing user.';
$string['helpinfodes'] = 'Browse user will show all the list of users with their details including their first and last access summary. Browse users also allows the user to add new users.';
$string['youcannoteditsiteadmin'] = 'You can not edit Site Admin.';
$string['suspendsuccess'] = 'User "{$a->name}" suspended Successfully.';
$string['unsuspendsuccess'] = 'User "{$a->name}" Unsuspended Successfully.';
$string['p_details'] = 'PERSONAL/ACADEMIC DETAILS';
$string['acdetails'] = 'Academic Details';
$string['manageusers'] = 'Manage Users';
$string['username'] = 'User Name';
$string['unameexists'] = 'Username Already exists';
$string['emailexists'] = 'Email Already exists';
$string['open_employeeidexist'] = 'PRN Number Already exists';
$string['open_employeeiderror'] = 'PRN Number can contain only alplabets or numericals special charecters not allowed';
$string['total_courses'] = 'Total number of Courses';
$string['enrolled'] = 'Number of Courses Enrolled';
$string['completed'] = 'Number of Courses Completed';
$string['signature'] = "Registrar's Signature";
$string['status'] = "Status";
$string['courses'] = "Courses";
$string['date'] = "Date";
$string['doj'] = 'Date of Joining';
$string['hcostcenter'] = 'Organisation';
$string['paddress'] = 'PERMANENT ADDRESS';
$string['caddress'] = 'PRESENT ADDRESS';
$string['invalidpassword'] = 'Invalid password';
$string['dol'] = 'Date of leave';
$string['dor'] = 'Date of resignation';
$string['serviceid'] = 'Unique ID';
$string['help_1'] = '<table class="generaltable" border="1">
<tr><td></td><td style="text-align:left;border-left:1px solid white;padding-left:50px;"><b>Mandatory Fields</b></td><tr>
<th>Field</th><th>Restriction</th>
<tr><td>University</td><td>Use the university/tenant code for your institution(This value is mandatory if you are uploading user as superadmin 
if you are uploading as LMS admin of your institution no need to mention university)</td></tr>
<tr><td>Username</td><td>Email will be used as Username to login the site.</td></tr>
<tr><td>Uniqueid</td><td>Enter the Roll Number, avoid additional spaces.</td></tr>
<tr><td>Firstname</td><td>Enter the first name, avoid additional spaces.</td></tr>
<tr><td>Lastname</td><td>Enter the last name, avoid additional spaces.</td></tr>
<tr><td>Email</td><td>Enter valid email(Must and Should).</td></tr>
<tr><td>Department or College</td><td>Use department or college shortcode here.This is the department or college under which the user going to be mapped on LMS</td></tr>

<tr><td>Employee status</td><td>Use Employee Status either active or inactive(in smallcase).</td></tr>
<tr><td>Role</td><td>Use value as student(smallcase) if you are uploading a student user or faculty(smallcase) if you are uploading a faculty</td></tr>
';
$string['help_2'] = '</td></tr>
<tr><td></td><td style="text-align:left;border-left:1px solid white;padding-left:50px;"><b>Normal Fields</b></td><tr>
<th>Field</th><th>Restriction</th>
<tr><td>Location</td><td>Enter location, avoid additional spaces.<b>(Do not remove the column even its optional)</b></td></tr>
<tr><td>Contactno</td><td>Enter contactno.(only numeric values)<b>(Do not remove the column even its optional)</b></td></tr>
<tr><td>State name</td><td>Enter state name, avoid additional spaces.<b>(Do not remove the column even its optional)</b></td></tr> 
<tr><td>Address</td><td>Enter address, avoid additional spaces.<b>(Do not remove the column even its optional)</b></td></tr> 
</table>';

$string['already_assignedstocostcenter']='{$a} already assigned to costcenter. Please unassign from costcenter to proceed further';
$string['already_instructor']='{$a} already assigned as instructor. Please unassign this user as instructor to proceed further';
$string['already_mentor']='{$a} already assigned as mentor. Please unassign this user as mentor to proceed further';
// ***********************Strings for bulk users**********************
$string['download'] = 'Download';
$string['csvdelimiter'] = 'CSV delimiter';
$string['encoding'] = 'Encoding';
$string['errors'] = 'Errors';
$string['nochanges'] = 'No changes';
$string['uploadusers'] = 'Upload Users';
$string['rowpreviewnum'] = 'Preview rows';
$string['uploaduser'] = 'Upload Users';
$string['back_upload'] = 'Back to Upload Users';
$string['bulkuploadusers'] = 'Bulk Upload Users';
$string['uploaduser_help'] = ' The format of the file should be as follows:

* Each line of the file contains one record
* Each record is a series of data separated by commas (or other delimiters)
* The first record contains a list of fieldnames defining the format of the rest of the file';

$string['uploaduserspreview'] = 'Upload Users Preview';
$string['userscreated'] = 'Users created';
$string['usersskipped'] = 'Users skipped';
$string['usersupdated'] = 'Users updated';
$string['uuupdatetype'] = 'Existing users details';
$string['uuoptype'] = 'Upload type';
$string['uuoptype_addnew'] = 'Add new only, skip existing users';
$string['uuoptype_addupdate'] = 'Add new and update existing users';
$string['uuoptype_update'] = 'Update existing users only';
$string['uuupdateall'] = 'Override with file and defaults';
$string['uuupdatefromfile'] = 'Override with file';
$string['uuupdatemissing'] = 'Fill in missing from file and defaults';
$string['uploadusersresult'] = 'Uploaded Users Result';
$string['helpmanual'] = 'Download sample Excel sheet and fill the field values in the format specified below.';
$string['manual'] = 'Help Manual';
$string['info'] = 'Help';
$string['helpinfo'] = 'Browse user will show all the list of users with their details including their first and last access summary. Browse users also allows the user to add new users.';
$string['changepassdes'] = 'This page allows the user to view the list of users with their profile details which also includes the login summary. Here you can also manage (edit/delete/inactivate) the users.';
$string['changepassinstdes'] = 'This page allows you to update or modify the password at any point of time; provided the instructor must furnish the current password correctly.';
$string['changepassregdes'] = 'This page allows you to update or modify the password at any point of time; provided the registrar must furnish the current password correctly.';
$string['info_help'] = '<h1>Browse Users</h1>
This page allows the user to view the list of users with their profile details which also includes the login summary. Here you can also manage (edit/delete/inactivate) the users.
<h1>Add New/Create User</h1>
This page allows you to add a new user. This can be one by filling up all the required fields and clicking on ‘submit’ button.';
$string['enter_grades'] = 'Enter Grades';
$string['firstname'] = 'First Name';
$string['middlename'] = 'Middle Name';
$string['lastname'] = 'Last Name';
$string['female']='Female';
$string['male']='Male';
$string['userdob']='Date of Birth';
$string['phone']='Mobile';
$string['email']='Email';
$string['emailerror']='Enter valid Email ID';
$string['phoneminimum']='Please Enter Minimum 10 Digits';
$string['phonemaximum']='You Can\' t Enter More Than 15 digits';
$string['country_error']='Please select a country';
$string['numeric'] = 'Only numeric values';
$string['pcountry']='Country';
$string['genderheading']='Generate Heading';
$string['primaryyear']='Primary Year';
$string['score']='Score';
$string['contactname']='Contact Name';
$string['hno']='House Number';
$string['phno']='Contact no';
$string['pob']='Place of Birth';
$string['contactname']='Contact Name';
$string['bulkassign'] = 'Bulk assignment to the costcenter';
$string['im:costcenter_unknown'] = 'Unknown costcenter';
$string['im:user_unknown'] = 'Unkown user';
$string['im:user_notcostcenter'] = 'Loggedin manager not assigned to this costcenter "{$a->csname}"';
$string['im:already_in'] = 'User already assigned to the costcenter';
$string['im:assigned_ok'] = '{$a} User assigned successfully';
$string['upload_employees'] = 'Upload employees';
$string['assignuser_costcenter'] = 'Assign users to organisation';
//-------added by rizwana-----------//
$string['button'] = 'CONTINUE';
/*-----------------------strings added by mani kanta -------------------------------*/
$string['idnumber'] = 'Id number';
$string['username'] = 'Username';
$string['firstcolumn'] = 'User column contains';
$string['enroll_batch'] ='Batch Enroll';
$string['mass_enroll'] = 'Bulk enrolments';
$string['mass_enroll_help'] = <<<EOS
<h1>Bulk enrolments</h1>

<p>
With this option you are going to enrol a list of known users from a file with one account per line
</p>
<p>
<b> The firstline </b> the empty lines or unknown accounts will be skipped. </p>

<p>
The file may contains one or two columns, separated by a comma, a semi-column or a tabulation.

You should prepare it from your usual spreadsheet program from official lists of students, for example,
and add if needed a column with groups to which you want these users to be added. Finally export it as CSV. (*)</p>

<p>
<b> The first one must contains a unique account identifier </b>: idnumber (by default) login or email  of the target user. (**). </p>

<p>
The second <b>if present,</b> contains the group's name in wich you want that user to be added. </p>

<p>
If the group name does not exist, it will be created in your course, together with a grouping of the same name to which the group will be added.
.<br/>
This is due to the fact that in Moodle, activities can be restricted to groupings (group of groups), not groups,
 so it will make your life easier. (this requires that groupings are enabled by your site administrator).

<p>
You may have in the same file different target groups or no groups for some accounts
</p>

<p>
You may unselect options to create groups and groupings if you are sure that they already exist in the course.
</p>

<p>
By default the users will be enroled as students but you may select other roles that you are allowed to manage (teacher, non editing teacher
or any custom roles)
</p>

<p>
You may repeat this operation at will without dammages, for example if you forgot or mispelled the target group.
</p>


<h2> Sample files </h2>

Id numbers and a group name to be created in needed in the course (*)
<pre>
"idnumber";"group"
" 2513110";" 4GEN"
" 2512334";" 4GEN"
" 2314149";" 4GEN"
" 2514854";" 4GEN"
" 2734431";" 4GEN"
" 2514934";" 4GEN"
" 2631955";" 4GEN"
" 2512459";" 4GEN"
" 2510841";" 4GEN"
</pre>

only idnumbers (**)
<pre>
idnumber
2513110
2512334
2314149
2514854
2734431
2514934
2631955
</pre>

only emails (**)
<pre>
email
toto@insa-lyon.fr
titi@]insa-lyon.fr
tutu@insa-lyon.fr
</pre>

usernames and groups, separated by a tab :

<pre>
username	 group
ppollet      groupe_de_test              will be in that group
codet        groupe_de_test              also him
astorck      autre_groupe                will be in another group
yjayet                                   no group for this one
                                         empty line skipped
unknown                                  unknown account skipped
</pre>

<p>
<span <font color='red'>(*) </font></span>: double quotes and spaces, added by some spreadsheet programs will be removed.
</p>

<p>
<span <font color='red'>(**) </font></span>: target account must exit in Moodle ; this is normally the case if Moodle is synchronized with
some external directory (LDAP...)
</p>


EOS;


$string['reportingto'] = 'Reports To';
$string['functionalreportingto'] = 'Functional Reporting To';
$string['ou_name'] = 'OU Name';
$string['department'] = 'College';
$string['costcenter_custom'] = 'Costcenter';
$string['subdepartment'] = 'Sub Department';
$string['designation'] = 'Designation';
$string['client'] = 'Client';
$string['grade'] = 'Grade';
$string['team'] = 'Team';

$string['group'] = 'Group';
$string['preferredlanguage'] = 'Language';
$string['open_group'] = 'Level';
$string['open_band'] = 'Band';
$string['open_role'] = 'Role';
$string['open_zone'] = 'Zone';
$string['open_region'] = 'Region';
$string['open_grade'] = 'Grade';
$string['open_branch'] = 'Branch';
$string['position'] = 'Position';
$string['emp_status'] = 'Employee Status';
$string['resign_status'] = 'Resignation Status';
$string['emp_type'] = 'Employee Type';
$string['dob'] = 'Date of Birth';
$string['career_track_tag'] = 'Career Track';
$string['campus_batch_tag'] = 'Campus Batch';
$string['calendar'] = 'Calendar Name';
$string['otherdetails'] = 'Other Details';
$string['location'] = 'Location';
$string['city'] = 'City';
$string['gender'] = 'Gender';
$string['usersupdated'] = 'Users updated';
$string['supervisor'] = 'Reporting To';
$string['selectasupervisor'] = 'Select Reporting To';
$string['reportingmanagerid'] = 'Functional Reporting To';
$string['selectreportingmanager'] = 'Select Functional Reporting';
$string['salutation'] = 'Salutation';
$string['employment_status'] = 'Employment Status';
$string['confirmation_date'] = 'Confirmation Date';
$string['confirmation_due_date'] = 'Confirmation Due Date';
$string['age'] = 'Age';
$string['paygroup'] = 'Paygroup';
$string['physically_challenge'] = 'Physically Challenge';
$string['disability'] = 'Disability';
$string['employment_type'] = 'Employment Type';
$string['employment_status'] = 'Employment Status';
$string['employee_status'] = 'Employee Status';
$string['enrol_user'] = 'Enrol Users';
$string['level'] = 'Level';
$string['select_career'] = 'Select Career Track';
$string['select_grade'] = 'Select Grade';
/*------------------------------------Ended Here-----------------------------------*/

// added by anil

$string['userinfo'] = 'User info';
$string['addtional_info'] = 'Addtional info';
$string['user_transcript'] = 'User transcript';
$string['type'] = 'Type';
$string['transcript_history'] = 'Transcript History (2015-2016)';
//added by Ravi
$string['sub_sub_department']='Sub Sub Depatement';
$string['zone_region']='Zone Region';
$string['area']='Area';
$string['dob']='DOB';
$string['matrail_status']='Martial Status';
$string['state']='State';

//added by Shivani
$string['course_header']='CURRENT LEARNING';
$string['courses_header_emp']='CURRENT LEARNING FOR ';
$string['courses_data']='No Courses to display.';
$string['page_header']='Profile Details';
$string['adnewuser']='Create Student <div class= "popupstring"></div>';
$string['empnumber']='PRN Number';
$string['departments']='Departments';
$string['sub_departments']='Sub Departments';
$string['sub-sub-departments']='Sub Sub Departments';
$string['college_help']='This setting determines the college under the selected university.';
$string['subdepartment_help']='This setting determines the category in which the sub department will appear in the list of departments.';
$string['subsubdepartment_help']='This setting determines the category in which the sub sub department will appear in the list of sub departments.';
$string['errordept']='Please select department';
$string['errorsubdept']='Please select Sub Department';
$string['errorsubsubdept']='Please select Sub Sub Department';
$string['errorfirstname']='Please enter First Name';
$string['errorlastname']='Please enter Last Name';
$string['erroremail']='Please enter email address';
$string['filemail']='Email Address';
$string['Departments']='Department';
$string['Sub_Departments']='Sub Department';
$string['Sub-Sub-Departments']='Sub Sub Department';
$string['idexits']='PRN Number Already exists';
//-------for sync lang files-------
$string['options']='Option';
$string['enrollmethods']='Enroll method';
$string['authenticationmethods'] = 'Authentication method';


$string['assigned_courses'] = 'Assigned Courses';
$string['completed_courses'] = 'Completed Courses';
$string['not_started_courses'] = 'Not Started';
$string['inprogress_courses'] = 'In Progress';
$string['employee_id'] = 'PRN Number';
$string['certificates'] = 'Certificates';
$string['already_assignedlp']='User assigned to Learning plan';
$string['coursehistory']='History';
$string['employees']="Employee's";
$string['learningplans']="Learning Paths";
$string['lowercaseunamerequired'] = 'Username should be in lowercase only';
$string['sync_users'] = 'Sync users';
$string['sync_errors'] = 'Sync errors';
$string['sync_stats'] = 'Sync statistics';
$string['view_users'] = 'view users';
$string['nodepartmenterror'] = 'Department cannot be empty';
$string['syncstatistics'] = 'Sync Statistics';
$string['phonenumvalidate']='Please enter a 10 digit valid number';

$string['cannotcreateuseremployeeidadderror'] = 'Employee with employeeid {$a} already exist so cannot create user in adduser mode';
$string['cannotfinduseremployeeidupdateerror'] = 'Employee with employeeid {$a} doesn\'t exist';
$string['cannotcreateuseremailadderror'] = 'Employee with mailid {$a} already exist so cannot create user in adduser mode';
$string['cannotedituseremailupdateerror'] = 'Employee with mailid {$a} doesn\'t exist so cannot update in update mode';
$string['multipleuseremployeeidupdateerror'] = 'Multiple employees with employeeid {$a} exist';
$string['multipleedituseremailupdateerror'] = 'Multiple employees with email {$a} exist';
$string['multipleedituserusernameediterror'] = 'Multiple employees with username {$a} exist';
$string['cannotedituserusernameediterror'] = 'Employee with username {$a} doesn\'t exist in update mode';
$string['cannotcreateuserusernameadderror'] = 'Employee with email {$a} already exist cannot create user in add mode';
$string['cannotcreateuseruniqueidadderror'] = 'Employee with unique id {$a} already exist cannot create user in add mode';
$string['deleteconfirm'] = 'Are you sure, you want to delete "{$a->fullname}" user ?';
$string['local_users_table_footer_content'] = 'Showing {$a->start_count} to {$a->end_count} of {$a->total_count} entries';
$string['suspendconfirm'] = 'Are you sure, you want to {$a->action} "{$a->fullname}" ?';
$string['firstname_surname'] = 'First Name / Surname';
$string['employeeid'] = 'Unique ID';
$string['emailaddress'] = 'Email Address';
$string['organization']='University';
$string['department']='Department';
$string['supervisorname'] = 'Supervisor';
$string['lastaccess'] = 'Last Access';
$string['actions'] = 'Actions';
$string['classrooms'] = 'Classrooms';
$string['onlineexams'] = 'Online exams';
$string['programs'] = 'Programs';
$string['contactno'] = 'Contact no';
$string['nosupervisormailfound'] = 'No supervisor found with email {$a->email} at line {$a->line}.';

$string['valusernamerequired'] = 'Please enter a valid username';
$string['valfirstnamerequired'] = 'Please enter a valid firstname';
$string['vallastnamerequired'] = 'Please enter a valid lastname';
$string['errororganization'] = 'Please select university';
$string['usernamerequired'] = 'Please enter username';
$string['passwordrequired'] = 'Please enter password';
$string['departmentrequired'] = 'Please select college';
$string['employeeidrequired'] = 'Please enter employeeid';
$string['noclassroomdesc'] = 'No Description provided';
$string['noprogramdesc'] = 'No Description provided';

$string['team_dashboard'] = 'Team Dashboard';
$string['myteam'] = 'My Team';
$string['idnumber'] = 'Unique ID';
//==============For target audience=========
// OL-1042 Add Target Audience to Classrooms////
$string['target_audience'] = 'Target audience';
$string['open_group'] = 'Group';
$string['open_band'] = 'Band';
$string['open_hrmsrole'] = 'HRMS Role';
$string['open_branch'] = 'Branch';
$string['open_designation'] = 'Designation';
$string['open_location'] = 'Location';

$string['team_allocation'] = 'Team allocation';
$string['myteam'] = 'My team';
$string['allocate'] = 'Allocate';
$string['learning_type'] = 'Learning Type';

$string['team_confirm_selected_allocation'] = 'Confirm allocation?';
$string['team_select_user'] = 'Please select a user.';
$string['team_select_course_s'] = 'Please select valid course/s.';
$string['team_approvals'] = 'Team approvals';
$string['approve'] = 'Approve';
$string['no_team_requests'] = 'No requests from team';
$string['team_no_learningtype'] = 'Please select any learning type.';
$string['select_requests'] = 'Select any requests.';
$string['select_learningtype'] = 'Select any learning type.';
$string['allocate_search_users'] = 'Search Users...';
$string['allocate_search_learnings'] = 'Search Learning Types...';
$string['select_user_toproceed'] = 'Select a user to proceed.';
$string['no_coursesfound'] = 'No courses found';
$string['no_classroomsfound'] = 'No classrooms found';
$string['no_programsfound'] = 'No programs found';
$string['team_requests_search'] = 'Search Team Requests by Users...';
$string['team_nodata'] = 'No records found';
$string['allocate_confirm_allocate'] = 'Are you sure you want to Approve selected requests?';
$string['team_request_confirm'] = 'Are you sure you want to Approve selected requests?';
$string['members'] = 'Members';
$string['permissiondenied'] = 'You dont have permissions to view this page.';
$string['onlinetests'] = 'Online Tests';
$string['manage_br_users'] = 'Manage <br/> users';
$string['profile'] = 'Profile';
$string['badges'] = 'Badges';
$string['editemployee'] = 'Update Employee';
$string['adnewemployee'] = 'Create Employee';
$string['dep'] = 'Department';
$string['assign_roles'] = 'Assign Role';
$string['college'] = 'College';
$string['collegelabel'] = 'Colleges<span><abbr class="initialism text-danger" title="Required"><img src='.$OUTPUT->image_url("req").'></abbr><span>';
$string['universitydepartment'] = 'Department';
$string['collegerequired'] = 'College Required';
$string['addnewhead'] = 'Create University Head';
$string['edithead'] = 'Update University Head';
$string['createuniversityhead'] = 'Create University Head';
$string['rolerequired'] = 'Please Select Role';
$string['prnnumberrequired'] = 'Please enter Unique ID';
$string['manage_teachers'] = 'Manage Teachers';
$string['create_student_api'] = 'Fetch Student from API';
$string['create_faculty_int'] = 'Fetch Faculty from API';
$string['departments'] = 'Departments<span><abbr class="initialism text-danger" title="Required"><img src='.$OUTPUT->image_url("req").'></abbr><span>';
$string['user_department_type'] = 'Type';
$string['departmentcollege'] = 'Univ.Dept/Affiliated Center';
$string['missing_departments'] = 'Please select department';
$string['miisingcollegeid'] = 'Please select college';
$string['passwordvalidation'] = 'Passwords must be at least 8 characters long.
                               Passwords must have at least 1 digit(s).
                               Passwords must have at least 1 upper case letter(s).
                               Passwords must have at least 1 non-alphanumeric character(s) such as as *, -, or #.';
$string['spacesnotallowed'] = 'Spaces are not allowed.Please enter valid value.';
$string['uniqueidexists'] = 'Unique Id already exists.';
$string['college/univdept'] = 'Univ.Dept/<br>Affiliated Center';
$string['confirmation'] = 'Confirmation';
$string['alert'] = 'Alert !';
$string['confirmmessage'] = 'This user has been assigned to program';
$string['customreq_selectdeptcoll'] = 'Select any one <span><abbr class="initialism text-danger" title="Required"><img src='.$OUTPUT->image_url("req").'></abbr><span>';
$string['confirmmessageforfaculty'] = 'This user has been assigned as <b>Faculty</b> for sessions ';

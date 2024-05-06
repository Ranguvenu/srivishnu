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
 * @subpackage sisprograms
 * @copyright  2019 Sarath Kumar <sarath@eabyas.in>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
$string['pluginname'] = 'Master Data';
$string['uploadcourses'] = 'Upload Courses';
$string['csvdelimiter'] = 'Csv delimiter';
$string['encoding'] = 'Encoding';
$string['rowpreviewnum'] = 'Preview Num';
$string['programlist'] = 'Programslist';
$string['helpmanual'] = 'Download sample Excel sheet and fill the field values in the format specified below.';
$string['help_tab'] = '<table border="1">
<tr><th></th><th style="text-align:left !important;border-left:1px solid white;padding-left:50px;"><b>Mandatory Fields</b></th></tr>
<tr><th>Field</th><th style="text-align:left!important; padding-left:50px;">Restriction</th></tr>
<tr><td>subjectname</td><td>Enter the Subject Name without additional spaces..</td></tr>
<tr><td>subjectcode</td><td>Enter the Subject Code without additional spaces.</td></tr>
<tr><td>programcode</td><td>Enter the Program code without additional spaces.</td></tr>
<tr><td>programname</td><td>Enter the Program name without additional spaces.</td></tr>
<tr><td>duration</td><td>Enter  Duration of the program (in numeric values only i.e. years).</td></tr>
<tr><td>runningfromyear</td><td>Enter the runningfromyear(in numeric value only i.e. year) without additional spaces.</td></tr>
<tr><td>universitycode</td><td>Enter the University Code without additional spaces.</td></tr>
<tr><td>universityname</td><td>Enter the University Name without additional spaces.</td></tr>
</table>';

$string['enrolmenthelp_tab'] = '<table border="1">
<tr><th></th><th style="text-align:left !important;border-left:1px solid white;padding-left:50px;"><b>Mandatory Fields</b></th></tr>
<tr><th>Field</th><th style="text-align:left!important; padding-left:50px;">Restriction</th></tr>
<tr><td>student_PRN</td><td>Enter the Student PRN No without additional spaces..</td></tr>
<tr><td>coursecode</td><td>Enter the Course Code without additional spaces.</td></tr>
<tr><td>firstname</td><td>Enter the firstname of the student.</td></tr>
<tr><td>lastname</td><td>Enter the lastname of the student.</td></tr>
<tr><td>email</td><td>Enter valid email(Must and Should).</td></tr>
<tr><td>role</td><td>Enter the role shortname without additional spaces.</td></tr>
<tr><th></th><th style="text-align:left !important;border-left:1px solid white;padding-left:50px;"><b>Normal Fields</b></th></tr>
<tr><td>mobile</td><td>All are digits only.Length should be in between 10 and 15.</td></tr>
<tr><td>address</td><td>Enter the address.</td></tr>
<tr><td>country</td><td>Enter country code. Refer below dropdown for codes.</td></tr>

<tr><td>city</td><td>Enter city name.</td></tr>
<tr><td>dob</td><td>Enter date of birth(dob) in dd-mm-yyyy format.</td></tr>
<tr><td>gender</td><td>Enter gender (male or female).</td></tr>
</table>';
$string['enrolample_excel'] = 'Sample';
$string['enrolhelp_manual'] = 'Help';
$string['helpmanual'] = 'Help Manual';
$string['uploadcourses'] = 'Upload Courses';
$string['uploadenrollments'] = 'Upload Enrollments';
$string['back_upload'] = 'Back to Upload';
$string['courseenrolment'] = 'Courseenrollments';
$string['courses'] = 'Courses';



$string['sample_excel'] = 'Sample';
$string['dept_manual'] = 'Help';
$string['report'] = 'Programs Report';
$string['uploadenrolments'] = 'Upload Enrolments';
$string['uploadenrollment'] = 'Upload Enrolment';
$string['sisprograms'] = 'Master Data';
$string['sisprogram'] = 'Sis Program';
$string['enrolmentscreated'] = 'Enrolments Created';
$string['enrolmentwarnings'] = 'Enrolments Warnings';
$string['coursescreated'] = 'Courses Created';
$string['duration'] = 'Duration';
$string['programcode'] = 'Program Code';
$string['runningfromyear'] = 'Running Year';
$string['programname'] = 'Program Name';
$string['nosisprograms'] = 'No Programs Masterdata is imported to LMS';
$string['viewsisprogramspage'] = 'View Programs Report';
$string['uploadcoursesresult'] = 'Upload Courses Result';
$string['errors'] = 'Errors Count';

$string['uploadcoursesreport'] = 'Courses Report';
$string['nomanageuploadcourses'] = 'No Course Masterdata is imported to LMS';
$string['coursecode'] = 'Course Code';
$string['coursename'] = 'Course Name';
$string['viewuploadcoursespage'] = 'View Upload Courses';
$string['coursesreport'] = 'Courses Report';
$string['sync_errors'] = 'Sync Errors';
$string['uploadenrollmentsresult'] = 'Upload Enrollments Result';
$string['university'] = 'University';
$string['example'] = ' Years';
$string['managemasterdata'] = 'Manage Master Data';
$string['sisprogramusers'] = 'Manage Users';
$string['nomasterdatausers'] = 'No User Masterdata is imported to LMS';
$string['prnnumber'] = 'PRN Number';
$string['actions'] = 'Actions';
$string['usersdata'] = 'Users data';
$string['delimited'] = 'Excelsheet should be saved in CSV (Comma delimited) format';
$string['masterusersdata'] = 'Master Users data'; 
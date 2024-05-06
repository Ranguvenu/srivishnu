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
 * @package    local_groups
 * @copyright  2018 sreenivas
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['addcohort'] = 'Add new group';
$string['allcohorts'] = 'All groups';
$string['anycohort'] = 'Any';
$string['assign'] = 'Assign Students';
$string['assignto'] = 'Group \'{$a}\' members';
$string['backtocohorts'] = 'Back to groups';
$string['bulkadd'] = 'Add to group';
$string['bulknocohort'] = 'No available groups found';
$string['categorynotfound'] = 'Category <b>{$a}</b> not found or you don\'t have permission to create a group there. The default context will be used.';
$string['cohort'] = 'Group';
$string['cohorts'] = 'Manage Groups';
$string['pluginname'] = 'Groups';
$string['cohortsin'] = '{$a}: available groups';
$string['assigncohorts'] = 'Group members';
$string['component'] = 'Source';
$string['contextnotfound'] = 'Context <b>{$a}</b> not found or you don\'t have permission to create a group there. The default context will be used.';
$string['csvcontainserrors'] = 'Errors were found in CSV data. See details below.';
$string['csvcontainswarnings'] = 'Warnings were found in CSV data. See details below.';
$string['csvextracolumns'] = 'Column(s) <b>{$a}</b> will be ignored.';
$string['currentusers'] = 'Current users';
$string['currentusersmatching'] = 'Current users matching';
$string['defaultcontext'] = 'Default context';
$string['delcohort'] = 'Confirmation';
$string['delconfirm'] = 'Are you sure, you want to delete "{$a}"?';
$string['description'] = 'Description';
$string['displayedrows'] = '{$a->displayed} rows displayed out of {$a->total}.';
$string['duplicateidnumber'] = 'group with the same ID number already exists';
$string['editcohort'] = 'Edit group';
$string['editcohortidnumber'] = 'Edit group ID';
$string['editcohortname'] = 'Edit group name';
$string['eventcohortcreated'] = 'Group created';
$string['eventcohortdeleted'] = 'Group deleted';
$string['eventcohortmemberadded'] = 'User added to a group';
$string['eventcohortmemberremoved'] = 'User removed from a group';
$string['eventcohortupdated'] = 'Group updated';
$string['external'] = 'External group';
$string['idnumber'] = 'Group ID';
$string['memberscount'] = 'Group size';
$string['name'] = 'Group Name';
$string['namecolumnmissing'] = 'There is something wrong with the format of the CSV file. Please check that it includes column names.';
$string['namefieldempty'] = 'Field name can not be empty';
$string['newnamefor'] = 'New name for group {$a}';
$string['newidnumberfor'] = 'New ID number for group {$a}';
$string['nocomponent'] = 'Created manually';
$string['potusers'] = 'Potential users';
$string['potusersmatching'] = 'Potential matching users';
$string['preview'] = 'Preview';
$string['removeuserwarning'] = 'Removing users from a group may result in unenrolling of users from multiple courses which includes deleting of user settings, grades, group membership and other user information from affected courses.';
$string['selectfromcohort'] = 'Select members from cohort';
$string['systemcohorts'] = 'System groups';
$string['unknowncohort'] = 'Unknown group ({$a})!';
$string['uploadcohorts'] = 'Upload groups';
$string['uploadedcohorts'] = 'Uploaded {$a} groups';
$string['useradded'] = 'User added to group "{$a}"';
$string['search'] = 'Search';
$string['searchcohort'] = 'Search group';
$string['uploadcohorts_help'] = 'Groups may be uploaded via text file. The format of the file should be as follows:

* Each line of the file contains one record
* Each record is a series of data separated by commas (or other delimiters)
* The first record contains a list of fieldnames defining the format of the rest of the file
* Required fieldname is name
* Optional fieldnames are idnumber, description, descriptionformat, visible, context, category, category_id, category_idnumber, category_path
';
$string['visible'] = 'Visible';
$string['visible_help'] = "Any group can be viewed by users who have 'moodle/cohort:view' capability in the cohort context.<br/>
Visible groups can also be viewed by users in the underlying courses.";
$string['select_all'] = 'Select All';
$string['remove_all'] = 'Un Select ';
$string['not_enrolled_users'] = '<b>Not Assigned Users ({$a})</b>';
$string['enrolled_users'] = '<b> Assigned Users ({$a})</b>';
$string['remove_selected_users'] = '<b> Un Assign Users </b><i class="fa fa-arrow-right" aria-hidden="true"></i><i class="fa fa-arrow-right" aria-hidden="true"></i>';
$string['remove_all_users'] = '<b> Un Enroll All Users </b><i class="fa fa-arrow-right" aria-hidden="true"></i><i class="fa fa-arrow-right" aria-hidden="true"></i>';
$string['add_selected_users'] = '<i class="fa fa-arrow-left" aria-hidden="true"></i><i class="fa fa-arrow-left" aria-hidden="true"></i><b> Assign Users</b>';
$string['add_all_users'] = ' <i class="fa fa-arrow-left" aria-hidden="true"></i><i class="fa fa-arrow-left" aria-hidden="true"></i> <b> Enroll All Users </b>';
$string['groups:manage'] = 'Manage groups';
$string['groups:addinstance'] = 'Add local group instance';
$string['groups:create'] = 'Create local group';
$string['groups:delete'] = 'Delete local groups';
$string['groups:view'] = 'View local groups';
$string['availablelist'] = 'Available users';
$string['selectedlist'] = 'Selected users';
$string['enrolledlist'] = 'Enrolled users';
$string['completedlist'] = 'Completed users';
$string['enrolluserssuccess'] = '<b>{$a->changecount}</b> Student(s) successfully enrolled to this <b>"{$a->group}"</b> group .';
$string['unenrolluserssuccess'] = '<b>{$a->changecount}</b> Student(s) successfully un enrolled from this <b>"{$a->group}"</b> group .';

$string['enrollusers'] = 'Group <b>"{$a}"</b> enrollment is in process...';

$string['un_enrollusers'] = 'Group <b>"{$a}"</b> un enrollment is in process...';
$string['click_continue'] = 'Click on continue';
$string['leftmenu_groups'] = 'Manage Groups';
$string['enroll'] = 'Enrol them to group';
$string['bulk_enroll'] = 'Bulk Enrolments';
$string['user_exist'] = '{$a} - already enrolled to this group';
$string['im:stats_i'] = '{$a} Student(s) successfully enrolled to this group';
$string['managecohorts'] = 'Manage Groups';
$string['actions'] = 'Actions';
$string['editgroups'] = 'Update Group';
$string['addNewgroups'] = 'Create Group';
$string['firstname'] = 'First Name';
$string['lastname'] = 'Last Name';
$string['group'] = 'Group';
$string['missing_department'] = 'Please select Department';
$string['missingcostcenter'] = 'Please select University';
$string['missingname'] = 'Please enter Group Name';
$string['select_departments'] = '--Select Department--';
$string['department'] = 'Departments/Colleges';
$string['costcenter'] = 'University';
$string['missingid'] = 'Please enter Group ID';
$string['spacesnotallowed'] = 'Spaces are not allowed.Please enter valid value.';

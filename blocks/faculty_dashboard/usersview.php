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
 * Cohort related management functions, this file needs to be included manually.
 *
 * @package    local_groups
 * @copyright  2018 sreenivas
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define('NO_OUTPUT_BUFFERING', true);
require('../../config.php');
// require_once($CFG->dirroot.'/local/groups/locallib.php');
require_once($CFG->dirroot.'/local/lib.php');
require_once($CFG->dirroot . '/local/courses/filters_form.php');
$id = required_param('id', PARAM_INT);

require_login();

$PAGE->set_title(get_string('userslist', 'block_faculty_dashboard'));

$PAGE->set_heading(get_string('userslist', 'block_faculty_dashboard'));



echo $OUTPUT->header();
if(!$add&&!$remove){
echo $OUTPUT->heading(get_string('userslist', 'block_faculty_dashboard'));
}
if(!empty($id)){
        $table = new html_table();
        $table->id = "listofusers";
        $PAGE->requires->js_call_amd('block_faculty_dashboard/datatablesamd', 'viewcountDatatable');
        $table->head  = array(get_string('firstname', 'block_faculty_dashboard'),get_string('lastname', 'block_faculty_dashboard'),get_string('email', 'block_faculty_dashboard'),get_string('course','block_faculty_dashboard'),get_string('role','block_faculty_dashboard')); 

        $table->align = array('center','center','center','center','center');
        $table->size = array('20','20','20','20','20');

        $userslist = $DB->get_records_sql('SELECT ue.userid FROM {user_enrolments} ue JOIN {enrol} e ON e.id = ue.enrolid WHERE e.courseid = '.$id.' AND ue.userid !='.$USER->id);
        
       
        $data = array();
        foreach($userslist as $user){
          $row = array();
          $users = $DB->get_record_sql("SELECT * FROM {user} WHERE id =".$user->userid);      
          $row[] = $users->firstname;
          $row[] = $users->lastname;
          $row[] = $users->email;
          $role = $DB->get_field_sql('SELECT r.name FROM {role} r JOIN {role_assignments} ra ON r.id = ra.roleid WHERE ra.userid ='.$user->userid);
          $row[] = $DB->get_field('course','fullname',array('id'=>$id));
         
          $row[] = $role;
          $data[] = $row;
        }    
        if (!empty($users)) {
       
            $table->data  = $data;
        
            $string .= html_writer::table($table);
            echo $string;
        } else {
            $string .= html_writer::table($table);
            $string .= 'No Records Found';
            echo $string;
        }

}

echo $OUTPUT->footer();

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
 * Classroom Upgrade
 *
 * @package     local_notifications
 * @author:     M Arun Kumar <arun@eabyas.in>
 *
 */
defined('MOODLE_INTERNAL') || die();

function xmldb_local_notifications_upgrade($oldversion) {
    global $DB, $CFG;
    $dbman = $DB->get_manager();
    if ($oldversion < 2017111300) {
        $table = new xmldb_table('local_notification_info');
        $field = new xmldb_field('adminbody',XMLDB_TYPE_TEXT, 'big', null, null, null,null);
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        upgrade_plugin_savepoint(true, 2017111300, 'local', 'notifications');
    }
    if ($oldversion < 2017111301) {
        $table = new xmldb_table('local_notification_info');
        $field = new xmldb_field('moduletype', XMLDB_TYPE_CHAR, '250', null, null, null,null, null);
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        $field = new xmldb_field('moduleid', XMLDB_TYPE_TEXT, 'big', null, null, null,null, null);
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        upgrade_plugin_savepoint(true, 2017111301, 'local', 'notifications');
    }
    if ($oldversion < 2017111305) {
        // $time = time();
        $notification_type_data = array(
        array('name' => 'Course','shortname' => 'course','parent_module' => '0','usercreated' => '2','usermodified' => NULL,'timemodified' => NULL),
        array('name' => 'Course Enrollment','shortname' => 'course_enrol','parent_module' => '1','usercreated' => '2','usermodified' => NULL,'timemodified' => NULL),
        array('name' => 'Course Completion','shortname' => 'course_complete','parent_module' => '1','usercreated' => '2','usermodified' => NULL,'timemodified' => NULL),
        array('name' => 'Course Unenrollment','shortname' => 'course_unenroll','parent_module' => '1','usercreated' => '2','usermodified' => NULL,'timemodified' => NULL),
        array('name' => 'Course Reminder','shortname' => 'course_reminder','parent_module' => '1','usercreated' => '2','usermodified' => NULL,'timemodified' => NULL),
        array('name' => 'Course notification','shortname' => 'course_notification','parent_module' => '1','usercreated' => '2','usermodified' => NULL,'timemodified' => NULL));
        foreach($notification_type_data as $notification_type){
            if($DB->record_exists('local_notification_type', array('name' => $notification_type['name'], 'shortname' => $notification_type['shortname']))){
                $DB->delete_records('local_notification_type', $notification_type);
            }
        }
   
         
        upgrade_plugin_savepoint(true, 2017111305, 'local', 'notifications');
    }

    if ($oldversion < 2017111306) {
        $string = array('name' => '[program_university]','module' => 'program','usercreated' => '2','timecreated' => $time,'usermodified' => NULL,'timemodified' => NULL);
         
        $string_obj = (object)$string;
        if(!$DB->record_exists('local_notification_strings', array('name' => '[program_university]','module' => 'program'))){
            $DB->insert_record('local_notification_strings', $string_obj);
        }

        upgrade_plugin_savepoint(true, 2017111306, 'local', 'notifications');
    }
    return true;
}

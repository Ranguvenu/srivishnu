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
defined('MOODLE_INTERNAL') || die();
function xmldb_local_users_upgrade($oldversion) {
    global $DB, $CFG;
    $dbman = $DB->get_manager();
    if ($oldversion < 2016080907) {
        $table = new xmldb_table('user');
        // $field1 = new xmldb_field('source_system_unique_id', XMLDB_TYPE_INTEGER, '10',XMLDB_NOTNULL, null, null, null, null);
        // if (!$dbman->field_exists($table, $field1)) {
        //     $dbman->add_field($table, $field1);
        // }

        // $field2 = new xmldb_field('date_of_joining', XMLDB_TYPE_CHAR,'255', XMLDB_NOTNULL, null, null, null, null);
        // if (!$dbman->field_exists($table, $field2)) {
        //     $dbman->add_field($table, $field2);
        // }

        // $field3 = new xmldb_field('dob', XMLDB_TYPE_CHAR,'255', XMLDB_NOTNULL, null, null, null, null);
        // if (!$dbman->field_exists($table, $field3)) {
        //     $dbman->add_field($table, $field3);
        // }
         $field4 = new xmldb_field('user_type', XMLDB_TYPE_CHAR,'255', XMLDB_NOTNULL, null, null, null, null);
        if (!$dbman->field_exists($table, $field4)) {
            $dbman->add_field($table, $field4);
        }
        upgrade_plugin_savepoint(true, 2016080907, 'local', 'user');
    }

    if ($oldversion < 2016080909.2) {
        $table = new xmldb_table('user');
        $field = new xmldb_field('open_univdept_status', XMLDB_TYPE_INTEGER, '10', null, null, null, null, null);
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        upgrade_plugin_savepoint(true, 2016080909.2, 'local', 'user');
    }
    if ($oldversion < 2016080909.8) {
        $table = new xmldb_table('user');
        $field1 = new xmldb_field('open_costcenterid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        if (!$dbman->field_exists($table, $field1)) {
            $dbman->add_field($table, $field1);
        }
        $field2 = new xmldb_field('open_employeeid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        if (!$dbman->field_exists($table, $field2)) {
            $dbman->add_field($table, $field2);
        }
        $field3 = new xmldb_field('open_departmentid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        if (!$dbman->field_exists($table, $field3)) {
            $dbman->add_field($table, $field3);
        }
        $field4 = new xmldb_field('open_subdepartment', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        if (!$dbman->field_exists($table, $field4)) {
            $dbman->add_field($table, $field4);
        }
        $field5 = new xmldb_field('open_depid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        if (!$dbman->field_exists($table, $field5)) {
            $dbman->add_field($table, $field5);
        }
        $field6 = new xmldb_field('open_employee', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        if (!$dbman->field_exists($table, $field6)) {
            $dbman->add_field($table, $field6);
        }
        $field7 = new xmldb_field('open_univdept_status', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        if (!$dbman->field_exists($table, $field7)) {
            $dbman->add_field($table, $field7);
        }
        $field8 = new xmldb_field('open_collegeid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        if (!$dbman->field_exists($table, $field8)) {
            $dbman->add_field($table, $field8);
        }
        $field9 = new xmldb_field('open_role', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        if (!$dbman->field_exists($table, $field9)) {
            $dbman->add_field($table, $field9);
        }
        upgrade_plugin_savepoint(true, 2016080909.8, 'local', 'user');
    }
    return true;
}
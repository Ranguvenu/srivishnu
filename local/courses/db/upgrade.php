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
 * @package     local_courses
 * @author:     M Arun Kumar <arun@eabyas.in>
 *
 */
defined('MOODLE_INTERNAL') || die();

function xmldb_local_courses_upgrade($oldversion) {
    global $DB, $CFG;
    $dbman = $DB->get_manager();
    if ($oldversion < 2017111300) {
        $table = new xmldb_table('course');
        $field = new xmldb_field('approvalreqd', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        upgrade_plugin_savepoint(true, 2017111300, 'local', 'courses');
    }
    if ($oldversion < 2017111301) {
        $table = new xmldb_table('course');
        $field = new xmldb_field('selfenrol', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        upgrade_plugin_savepoint(true, 2017111301, 'local', 'courses');
    }
    if ($oldversion < 2017111302) {
        $table = new xmldb_table('course');
        $field = new xmldb_field('open_level', XMLDB_TYPE_INTEGER, '10', null, null, null, '0');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        upgrade_plugin_savepoint(true, 2017111302, 'local', 'courses');
    }
    if ($oldversion < 2017111304) {
        $table = new xmldb_table('course');
        $field = new xmldb_field('open_parentcourseid', XMLDB_TYPE_INTEGER, '10', null, null, null, '0', null);
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        upgrade_plugin_savepoint(true, 2017111304, 'local', 'courses');
    }
    if ($oldversion < 2017111306) {
        $table = new xmldb_table('local_sisonlinecourses');
        $field1 = new xmldb_field('smbid', XMLDB_TYPE_INTEGER, '10',XMLDB_NOTNULL, null, null, null, null);
        if (!$dbman->field_exists($table, $field1)) {
            $dbman->add_field($table, $field1);
        }
        $field2 = new xmldb_field('examid', XMLDB_TYPE_INTEGER, '10',XMLDB_NOTNULL, null, null, null, null);
        if (!$dbman->field_exists($table, $field2)) {
            $dbman->add_field($table, $field2);
        }
        upgrade_plugin_savepoint(true, 2017111306, 'local', 'courses');
    }
    if ($oldversion < 2017111310) {
        $table = new xmldb_table('course');
        $field1 = new xmldb_field('sold_status', XMLDB_TYPE_INTEGER, '10',XMLDB_NOTNULL, null, null, null, null);
        if (!$dbman->field_exists($table, $field1)) {
            $dbman->add_field($table, $field1);
        }
        $field2 = new xmldb_field('forpurchaseindividually', XMLDB_TYPE_INTEGER, '10',XMLDB_NOTNULL, null, null, null, null);
        if (!$dbman->field_exists($table, $field2)) {
            $dbman->add_field($table, $field2);
        }
        upgrade_plugin_savepoint(true, 2017111310, 'local', 'courses');
    }
    if ($oldversion < 2017111310) {
        $table = new xmldb_table('course');
        $field1 = new xmldb_field('enrolment_date', XMLDB_TYPE_INTEGER, '10',XMLDB_NOTNULL, null, null, null, null);
        if (!$dbman->field_exists($table, $field1)) {
            $dbman->add_field($table, $field1);
        }
        upgrade_plugin_savepoint(true, 2017111310, 'local', 'courses');
    }
    if ($oldversion < 2017111318) {
        $table = new xmldb_table('course');
        $field1 = new xmldb_field('affiliationstatus', XMLDB_TYPE_INTEGER, '10',XMLDB_NOTNULL, null, null, null, null);
        if (!$dbman->field_exists($table, $field1)) {
            $dbman->add_field($table, $field1);
        }
        upgrade_plugin_savepoint(true, 2017111318, 'local', 'courses');
    }
// <mallikarjun> - ODL-750 adding college to curriculums -- starts
    if ($oldversion < 2017111319.8) {
        $table = new xmldb_table('course');
        $field1 = new xmldb_field('open_univdept_status', XMLDB_TYPE_INTEGER, '10',XMLDB_NOTNULL, null, null, null, null);
        if (!$dbman->field_exists($table, $field1)) {
            $dbman->add_field($table, $field1);
        }
        upgrade_plugin_savepoint(true, 2017111319.8, 'local', 'courses');
    }
// <mallikarjun> - ODL-750 adding college to curriculums -- ends
   
    return true;
}

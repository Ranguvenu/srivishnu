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
function xmldb_local_sisprograms_upgrade($oldversion) {
    global $DB, $CFG;
    $dbman = $DB->get_manager();
    if ($oldversion < 2013110308) {
        $table = new xmldb_table('local_sisprograms');
        $field1 = new xmldb_field('smbid', XMLDB_TYPE_INTEGER, '10',XMLDB_NOTNULL, null, null, null, null);
        if (!$dbman->field_exists($table, $field1)) {
            $dbman->add_field($table, $field1);
        }

        $field2 = new xmldb_field('coursepattern', XMLDB_TYPE_CHAR,'255', XMLDB_NOTNULL, null, null, null, null);
        if (!$dbman->field_exists($table, $field2)) {
            $dbman->add_field($table, $field2);
        }

        $field3 = new xmldb_field('courselevelid', XMLDB_TYPE_INTEGER, '10',XMLDB_NOTNULL, null, null, null, null);
        if (!$dbman->field_exists($table, $field3)) {
            $dbman->add_field($table, $field3);
        }
         $field4 = new xmldb_field('coursestatus', XMLDB_TYPE_INTEGER, '10',XMLDB_NOTNULL, null, null, null, null);
        if (!$dbman->field_exists($table, $field4)) {
            $dbman->add_field($table, $field4);
        }
        upgrade_plugin_savepoint(true, 2013110308, 'local', 'sisprograms');
    }
    
    if ($oldversion < 2013110309) {
            $table = new xmldb_table('local_sisexams');
            if (!$dbman->table_exists($table)) {
               $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
               $table->add_field('smbid', XMLDB_TYPE_INTEGER, '10', 0, null, null);
               $table->add_field('branchid',XMLDB_TYPE_INTEGER, '10', 0,null,null);
               $table->add_field('examcode',XMLDB_TYPE_CHAR, '225', null,null,null,NULL);
               $table->add_field('examname',XMLDB_TYPE_CHAR, '225', null,null,null);
               $table->add_field('university',XMLDB_TYPE_INTEGER, '10', null,null,null,NULL);
               $table->add_field('sequence',XMLDB_TYPE_INTEGER, '10', null,null,null,NULL);
               $table->add_field('usercreated', XMLDB_TYPE_INTEGER, '10', null, null, null);
               $table->add_field('usermodified', XMLDB_TYPE_INTEGER, '10', null,null, null,0);
               $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10',null,null, null,0);
               $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10',null,null, null,0);
               
             
                
               $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
               
               $dbman->create_table($table);
            }
			
			  $table2 = new xmldb_table('local_sisbranches');
            if (!$dbman->table_exists($table2)) {
               $table2->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
               $table2->add_field('smbid', XMLDB_TYPE_INTEGER, '10', null, null, null);
               $table2->add_field('branchid',XMLDB_TYPE_INTEGER, '10', null,null,null);
               $table2->add_field('courseid',XMLDB_TYPE_INTEGER, '10', null,null,null,NULL);
               $table2->add_field('branchcode',XMLDB_TYPE_CHAR, '225', null,null,null);
               $table2->add_field('university',XMLDB_TYPE_INTEGER, '10', null,null,null,NULL);
               $table2->add_field('branchname',XMLDB_TYPE_CHAR, '225', null,null,null,NULL);
			   $table2->add_field('activestat',XMLDB_TYPE_INTEGER, '10', null,null,null,NULL);
               $table2->add_field('usercreated', XMLDB_TYPE_INTEGER, '10', null, null, null);
               $table2->add_field('usermodified', XMLDB_TYPE_INTEGER, '10', null,null, null,0);
               $table2->add_field('timecreated', XMLDB_TYPE_INTEGER, '10',null,null, null,0);
               $table2->add_field('timemodified', XMLDB_TYPE_INTEGER, '10',null,null, null,0);
               
             
                
               $table2->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
               
               $dbman->create_table($table2);
            }
			
			
         upgrade_plugin_savepoint(true, 2013110309, 'local', 'sisprograms');
    }
    return true;
}
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
 * Program Upgrade
 *
 * @package     local_program
 * @author:     M Arun Kumar <arun@eabyas.in>
 *
 */
defined('MOODLE_INTERNAL') || die();

function xmldb_local_program_upgrade($oldversion) {
    global $DB, $CFG;
    $dbman = $DB->get_manager();

    if ($oldversion < 2017050483) {

        $table = new xmldb_table('local_program_cc_years');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('curriculumid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('year', XMLDB_TYPE_CHAR, '225', null, XMLDB_NOTNULL, null, NULL);
        $table->add_field('status', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '1');
        $table->add_field('usercreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, null, null, '0');
        $table->add_field('usermodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, null, null, '0');

        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $result = $dbman->create_table($table);

        upgrade_plugin_savepoint(true, 2017050483, 'local', 'program');
    }
    if ($oldversion < 2017050484) {
        $table = new xmldb_table('local_curriculum_users');

        if ($dbman->table_exists($table)) {
            $field = new xmldb_field('years', XMLDB_TYPE_CHAR, '50', null, XMLDB_NOTNULL, null, NULL);
            if (!$dbman->field_exists($table, $field)) {
                $dbman->add_field($table, $field);
            }
        }
        upgrade_plugin_savepoint(true, 2017050484, 'local', 'program');
    }

    if ($oldversion < 2017050487) {
        $table = new xmldb_table('local_program_curriculum_years');

        if ($dbman->table_exists($table)) {
            $dbman->rename_table($table, 'local_program_cc_years');
        }
        upgrade_plugin_savepoint(true, 2017050487, 'local', 'program');
    }

    if ($oldversion < 2017050492) {
        $table = new xmldb_table('local_cc_semester_courses');

        if ($dbman->table_exists($table)) {
            $field = new xmldb_field('yearid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, 0);
            if (!$dbman->field_exists($table, $field)) {
                $dbman->add_field($table, $field);
            }
        }
        upgrade_plugin_savepoint(true, 2017050492, 'local', 'program');
    }

    if ($oldversion < 2017050495) {
        $table = new xmldb_table('local_cc_session_signups');

        if ($dbman->table_exists($table)) {
            $field = new xmldb_field('bclcid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, 0);
            if ($dbman->field_exists($table, $field)) {
                $dbman->rename_field($table, $field, 'yearid');
            }
            $field1 = new xmldb_field('sessionid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, 0);
            if ($dbman->field_exists($table, $field1)) {
                $dbman->rename_field($table, $field1, 'courseid');
            }

        }
        upgrade_plugin_savepoint(true, 2017050495, 'local', 'program');
    }
    if ($oldversion < 2017050498) {
        $table = new xmldb_table('local_program');

        if ($dbman->table_exists($table)) {

            $field = new xmldb_field('shortcode', XMLDB_TYPE_CHAR, '250', null, null, null);
            if (!$dbman->field_exists($table, $field)) {
               $dbman->add_field($table, $field);
            }
            $field = new xmldb_field('validtill', XMLDB_TYPE_INTEGER, '10', null, null, null);
            if (!$dbman->field_exists($table, $field)) {
                $dbman->add_field($table, $field);
            }
            $field = new xmldb_field('year', XMLDB_TYPE_INTEGER, '10', null, null, null);
            if (!$dbman->field_exists($table, $field)) {
                $dbman->add_field($table, $field);
            }

        }
        upgrade_plugin_savepoint(true, 2017050498, 'local', 'program');
    }
    if ($oldversion < 2017050502) {

        $table = new xmldb_table('local_program_cc_years');
        if ($dbman->table_exists($table)) {
            $field = new xmldb_field('programid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, 0);
            if (!$dbman->field_exists($table, $field)) {
                $dbman->add_field($table, $field);
            }
        }

        $table1 = new xmldb_table('local_curriculum_semesters');
        if ($dbman->table_exists($table1)) {
            $field1 = new xmldb_field('programid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, 0);
            if (!$dbman->field_exists($table1, $field1)) {
                $dbman->add_field($table1, $field1);
            }
        }

        $table2 = new xmldb_table('local_cc_semester_courses');
        if ($dbman->table_exists($table2)) {
            $field2 = new xmldb_field('programid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, 0);
            if (!$dbman->field_exists($table2, $field2)) {
                $dbman->add_field($table2, $field2);
            }
        }

        $table3 = new xmldb_table('local_cc_session_trainers');
        if ($dbman->table_exists($table3)) {
            $field3 = new xmldb_field('programid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, 0);
            if (!$dbman->field_exists($table3, $field3)) {
                $dbman->add_field($table3, $field3);
            }
        }

        $table4 = new xmldb_table('local_cc_session_signups');
        if ($dbman->table_exists($table4)) {
            $field4 = new xmldb_field('programid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, 0);
            if (!$dbman->field_exists($table4, $field4)) {
                $dbman->add_field($table4, $field4);
            }
        }

        $table5 = new xmldb_table('local_curriculum_trainers');
        if ($dbman->table_exists($table5)) {
            $field5 = new xmldb_field('programid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, 0);
            if (!$dbman->field_exists($table5, $field5)) {
                $dbman->add_field($table5, $field5);
            }
        }

        $table6 = new xmldb_table('local_cc_course_sessions');
        if ($dbman->table_exists($table6)) {
            $field6 = new xmldb_field('programid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, 0);
            if (!$dbman->field_exists($table6, $field6)) {
                $dbman->add_field($table6, $field6);
            }
        }

        $table7 = new xmldb_table('local_curriculum_users');
        if ($dbman->table_exists($table7)) {
            $field7 = new xmldb_field('programid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, 0);
            if (!$dbman->field_exists($table7, $field7)) {
                $dbman->add_field($table7, $field7);
            }
        }

        upgrade_plugin_savepoint(true, 2017050502, 'local', 'program');
    }

    if ($oldversion < 2017050504) {

        $table = new xmldb_table('local_program_cc_year_cost');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('programid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('curriculumid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('yearid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '1');
        $table->add_field('cost', XMLDB_TYPE_CHAR, '225', null, XMLDB_NOTNULL, null, NULL);
        $table->add_field('usercreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, null, null, '0');
        $table->add_field('usermodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, null, null, '0');

        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        if (!$dbman->table_exists($table)) {
            $result = $dbman->create_table($table);
        }
        upgrade_plugin_savepoint(true, 2017050504, 'local', 'program');
    }
if ($oldversion < 2017050538) {
        $table = new xmldb_table('local_cc_course_sessions');
        $field = new xmldb_field('costcenter', XMLDB_TYPE_INTEGER, '10',XMLDB_NOTNULL, null, null, null, null);
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        upgrade_plugin_savepoint(true, 2017050538, 'local', 'program');
    }
     
    if ($oldversion < 2017050507) {
        $table = new xmldb_table('local_cc_session_signups');

        if ($dbman->table_exists($table)) {
            $field = new xmldb_field('semesterid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, 0);
            if ($dbman->field_exists($table, $field)) {
                $dbman->drop_field($table, $field);
            }

            $index = new xmldb_index('courseid', XMLDB_INDEX_NOTUNIQUE, array('courseid'));
            if ($dbman->index_exists($table, $index)) {
                $dbman->drop_index($table, $index);
            }
            $index1 = new xmldb_index('courseid-userid', XMLDB_INDEX_NOTUNIQUE, array('courseid', 'userid'));
            if ($dbman->index_exists($table, $index1)) {
                $dbman->drop_index($table, $index1);
            }

            $index2 = new xmldb_index('courseid-completion_status', XMLDB_INDEX_NOTUNIQUE, array('courseid', 'completion_status'));
            if ($dbman->index_exists($table, $index2)) {
                $dbman->drop_index($table, $index2);
            }

            $index3 = new xmldb_index('courseid-trainingfeedback', XMLDB_INDEX_NOTUNIQUE, array('courseid', 'trainingfeedback'));
            if ($dbman->index_exists($table, $index3)) {
                $dbman->drop_index($table, $index3);
            }

            $index4 = new xmldb_index('courseid-userid-completion_status', XMLDB_INDEX_NOTUNIQUE, array('courseid', 'userid', 'completion_status'));
            if ($dbman->index_exists($table, $index4)) {
                $dbman->drop_index($table, $index4);
            }

            $index5 = new xmldb_index('courseid', XMLDB_INDEX_NOTUNIQUE, array('courseid'));
            if ($dbman->index_exists($table, $index5)) {
                $dbman->drop_index($table, $index5);
            }
            $field1 = new xmldb_field('courseid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, 0);
            if ($dbman->field_exists($table, $field1)) {
                $dbman->drop_field($table, $field1);
            }
        }
        upgrade_plugin_savepoint(true, 2017050507, 'local', 'program');
    }
    if ($oldversion < 2017050508) {
        $table = new xmldb_table('local_cc_session_trainers');

        if ($dbman->table_exists($table)) {

            $field = new xmldb_field('bclcid', XMLDB_TYPE_INTEGER, '10', null, null, null);
            if ($dbman->field_exists($table, $field)) {
                $dbman->rename_field($table, $field, 'yearid');
            }
            $field1 = new xmldb_field('sessionid', XMLDB_TYPE_INTEGER, '10', null, null, null);
            if ($dbman->field_exists($table, $field1)) {
                $dbman->rename_field($table, $field1, 'courseid');
            }
        }
        upgrade_plugin_savepoint(true, 2017050508, 'local', 'program');
    }

    if ($oldversion < 2017050514) {
        $table = new xmldb_table('local_program_cc_years');

        if ($dbman->table_exists($table)) {
            $field = new xmldb_field('cost', XMLDB_TYPE_FLOAT, '20,2', null, null, null, 0);
            if (!$dbman->field_exists($table, $field)) {
               $dbman->add_field($table, $field);
            }
        }
        upgrade_plugin_savepoint(true, 2017050514, 'local', 'program');
    }

    if ($oldversion < 2017050518) {
        $table = new xmldb_table('local_cc_semester_courses');

        if ($dbman->table_exists($table)) {
            $field = new xmldb_field('open_parentcourseid', XMLDB_TYPE_INTEGER, '10', null, null, null, 0);
            if (!$dbman->field_exists($table, $field)) {
               $dbman->add_field($table, $field);
            }
        }
        upgrade_plugin_savepoint(true, 2017050518, 'local', 'program');
    }

	if ($oldversion < 2017050521) {
        $table = new xmldb_table('local_userenrolments_log');

        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null);
        $table->add_field('yearid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null);
        $table->add_field('curriculumid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null);
        $table->add_field('programid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null);
        $table->add_field('orderid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, null, null, '0');
        $table->add_field('usermodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');

        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        if (!$dbman->table_exists($table)) {
            $result = $dbman->create_table($table);
        }
        upgrade_plugin_savepoint(true, 2017050521, 'local', 'program');
    }
    if ($oldversion < 2017050522) {
        $table = new xmldb_table('local_cc_semester_cmptl');

        if ($dbman->table_exists($table)) {
            $field = new xmldb_field('bclcids', XMLDB_TYPE_CHAR, '225', null, null, null);
            if ($dbman->field_exists($table, $field)) {
                $dbman->rename_field($table, $field, 'courseids');
            }
        }
        upgrade_plugin_savepoint(true, 2017050522, 'local', 'program');
    }
    if ($oldversion < 2017050523) {
        $table = new xmldb_table('local_cc_semester_cmptl');

        if ($dbman->table_exists($table)) {
            $field = new xmldb_field('yearid', XMLDB_TYPE_INTEGER, '10', null, null, null);
            if (!$dbman->field_exists($table, $field)) {
                $dbman->add_field($table, $field);
            }
        }
        upgrade_plugin_savepoint(true, 2017050523, 'local', 'program');
    }
    if ($oldversion < 2017050524) {
        $table = new xmldb_table('local_cc_semester_cmptl');

        if ($dbman->table_exists($table)) {
            $field = new xmldb_field('programid', XMLDB_TYPE_INTEGER, '10', null, null, null);
            if (!$dbman->field_exists($table, $field)) {
                $dbman->add_field($table, $field);
            }
        }
        upgrade_plugin_savepoint(true, 2017050524, 'local', 'program');
    }
    if ($oldversion < 2017050527) {
        $table = new xmldb_table('local_cc_semester_courses');

        if ($dbman->table_exists($table)) {
            $field = new xmldb_field('importstatus', XMLDB_TYPE_INTEGER, '10', null, null, null, 0);
            if (!$dbman->field_exists($table, $field)) {
                $dbman->add_field($table, $field);
            }
        }
        upgrade_plugin_savepoint(true, 2017050527, 'local', 'program');
    }

    if ($oldversion < 2017050528) {
        $table = new xmldb_table('local_cc_semester_courses');

        if ($dbman->table_exists($table)) {
            $field = new xmldb_field('coursetype', XMLDB_TYPE_INTEGER, '10', XMLDB_NOTNULL, null, null, NULL, null);
            if (!$dbman->field_exists($table, $field)) {
                $dbman->add_field($table, $field);
            }
        }

        $table = new xmldb_table('local_program');

        if ($dbman->table_exists($table)) {
            $field = new xmldb_field('publishstatus', XMLDB_TYPE_INTEGER, '10', XMLDB_NOTNULL, null, null, '0', null);
            if (!$dbman->field_exists($table, $field)) {
                $dbman->add_field($table, $field);
            }
        }
        upgrade_plugin_savepoint(true, 2017050528, 'local', 'program');
    }

     if ($oldversion < 2017050530) {
        $table = new xmldb_table('local_program');

        if ($dbman->table_exists($table)) {

            $field = new xmldb_field('program_approval', XMLDB_TYPE_INTEGER, '10', null, null, null);
            if (!$dbman->field_exists($table, $field)) {
               $dbman->add_field($table, $field);
            }
            $field = new xmldb_field('pre_requisites', XMLDB_TYPE_CHAR, '255', null, null, null);
            if (!$dbman->field_exists($table, $field)) {
                $dbman->add_field($table, $field);
            }

        }
        upgrade_plugin_savepoint(true, 2017050530, 'local', 'program');
    }

    if ($oldversion < 2017050535) {
        $table = new xmldb_table('local_program');

        if ($dbman->table_exists($table)) {
            $field = new xmldb_field('curriculumid', XMLDB_TYPE_INTEGER, '10', XMLDB_NOTNULL, null, null, null);
            if (!$dbman->field_exists($table, $field)) {
                $dbman->add_field($table, $field);
            }
        }
        upgrade_plugin_savepoint(true, 2017050535, 'local', 'program');
    }

    if ($oldversion < 2017050536) {
            $table = new xmldb_table('local_cc_semester_classrooms');
            if (!$dbman->table_exists($table)) {
               $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
               $table->add_field('classname', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
               $table->add_field('shortname',XMLDB_TYPE_CHAR, '225', null,XMLDB_NOTNULL,null, null);
               $table->add_field('requiredsessions',XMLDB_TYPE_INTEGER, '10', null,XMLDB_NOTNULL,null,NULL);               
               $table->add_field('programid',XMLDB_TYPE_INTEGER, '10', null,null,null,null);
               $table->add_field('curriculumid',XMLDB_TYPE_INTEGER, '10', null,null,null,NULL);
               $table->add_field('yearid',XMLDB_TYPE_INTEGER, '10', null,null,null,NULL);
               $table->add_field('semesterid',XMLDB_TYPE_INTEGER, '10', null,null,null,NULL);
               $table->add_field('courseid',XMLDB_TYPE_INTEGER, '10', null,null,null,0);
               $table->add_field('coursetype',XMLDB_TYPE_INTEGER, '10', null,null,null,0);
               $table->add_field('pretestid',XMLDB_TYPE_INTEGER, '10', null,null,null,0);
               $table->add_field('posttestid',XMLDB_TYPE_INTEGER, '10', null,null,null,0);
               $table->add_field('prefeedback',XMLDB_TYPE_INTEGER, '10', null,null,null,0);
               $table->add_field('postfeedback',XMLDB_TYPE_INTEGER, '10', null,null,null,0);
               $table->add_field('course_duration',XMLDB_TYPE_INTEGER, '10', null,null,null,NULL);
               $table->add_field('totalusers',XMLDB_TYPE_INTEGER, '10', null,null,null,0);
               $table->add_field('activeusers',XMLDB_TYPE_INTEGER, '10', null,null,null,0);
               $table->add_field('totalsessions',XMLDB_TYPE_INTEGER, '10', null,null,null,0);
               $table->add_field('activesessions',XMLDB_TYPE_INTEGER, '10', null,null,null,0);
               $table->add_field('position',XMLDB_TYPE_INTEGER, '10', null,null,null,0);
               $table->add_field('importstatus',XMLDB_TYPE_INTEGER, '10', null,null,null,0);
               $table->add_field('classroom_type',XMLDB_TYPE_INTEGER, '10', null,null,null,0);
               $table->add_field('usercreated', XMLDB_TYPE_INTEGER, '10', null, null, null);
               $table->add_field('usermodified', XMLDB_TYPE_INTEGER, '10', null,null, null,0);
               $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10',null,null, null,null);
               $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10',null,null, null,null);
               $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
               $dbman->create_table($table);
            }
         upgrade_plugin_savepoint(true, 2017050536, 'local', 'program');
    }

    if ($oldversion < 2017050536) {
        $table = new xmldb_table('local_classroom_completion');
            if (!$dbman->table_exists($table)) {
               $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
               $table->add_field('classroomid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
               $table->add_field('sessiontracking',XMLDB_TYPE_CHAR, '255', null,XMLDB_NOTNULL,null, 'OR');
               $table->add_field('sessionids',XMLDB_TYPE_TEXT, 'big', null,XMLDB_NOTNULL,null,NULL);               
               $table->add_field('coursetracking',XMLDB_TYPE_CHAR, '255', null,null,null,'OR');
               $table->add_field('courseids',XMLDB_TYPE_TEXT, 'big', null,null,null,NULL);
               $table->add_field('usercreated', XMLDB_TYPE_INTEGER, '10', null, null, null);
               $table->add_field('usermodified', XMLDB_TYPE_INTEGER, '10', null,null, null,0);
               $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10',null,null, null,null);
               $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10',null,null, null,null);
               $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
               $dbman->create_table($table);
            }

        $table = new xmldb_table('local_cc_course_sessions');
        if ($dbman->table_exists($table)) {
            $field = new xmldb_field('sessiontype', XMLDB_TYPE_INTEGER, '10', XMLDB_NOTNULL, null, null, null);
            if (!$dbman->field_exists($table, $field)) {
                $dbman->add_field($table, $field);
            }
        }
        upgrade_plugin_savepoint(true, 2017050536, 'local', 'program');
    }

    if ($oldversion < 2017050537) {
        $table = new xmldb_table('local_cc_session_signups');
        if ($dbman->table_exists($table)) {
            $field = new xmldb_field('bclcid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
            if (!$dbman->field_exists($table, $field)) {
                $dbman->add_field($table, $field);
            }
            $field1 = new xmldb_field('sessionid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
            if (!$dbman->field_exists($table, $field1)) {
                $dbman->add_field($table, $field1);
            }
            $field2 = new xmldb_field('semesterid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
            if (!$dbman->field_exists($table, $field2)) {
                $dbman->add_field($table, $field2);
            }
        }
        upgrade_plugin_savepoint(true, 2017050537, 'local', 'program');
    }

    if ($oldversion < 2017050537) {
        $table = new xmldb_table('local_cc_course_sessions');

        if ($dbman->table_exists($table)) {
            $field = new xmldb_field('yearid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
            if (!$dbman->field_exists($table, $field)) {
                $dbman->add_field($table, $field);
            }
            $field1 = new xmldb_field('courseid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, 0);
            if (!$dbman->field_exists($table, $field1)) {
                $dbman->add_field($table, $field1);
            }
            $field2 = new xmldb_field('sessiontype', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
            if (!$dbman->field_exists($table, $field2)) {
                $dbman->add_field($table, $field2);
            }
            $field3 = new xmldb_field('costcenter', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
            if (!$dbman->field_exists($table, $field3)) {
                $dbman->add_field($table, $field3);
            }
        }
        upgrade_plugin_savepoint(true, 2017050537, 'local', 'program');
    }

    if ($oldversion < 2017050541) {
        $table = new xmldb_table('local_cc_course_sessions');

        if ($dbman->table_exists($table)) {
            $field = new xmldb_field('dailysessionstarttime', XMLDB_TYPE_CHAR, '50', XMLDB_NOTNULL, null, null, null);
            if (!$dbman->field_exists($table, $field)) {
                $dbman->add_field($table, $field);
            }
            $field1 = new xmldb_field('dailysessionendtime', XMLDB_TYPE_CHAR, '50', XMLDB_NOTNULL, null, null, null);
            if (!$dbman->field_exists($table, $field1)) {
                $dbman->add_field($table, $field1);
            }
        }
        $table = new xmldb_table('local_classroom_completion');
        if ($dbman->table_exists($table)) {
            $field2 = new xmldb_field('requiredsessions', XMLDB_TYPE_INTEGER, '10', XMLDB_NOTNULL, null, null, null);
            if (!$dbman->field_exists($table, $field2)) {
                $dbman->add_field($table, $field2);
            }
        }
        $table = new xmldb_table('local_cc_semester_classrooms');
        if ($dbman->table_exists($table)) {
            $field3 = new xmldb_field('requiredsessions', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
            if($dbman->field_exists($table, $field3)){
                $dbman->change_field_notnull($table, $field3);
            }
        }
        upgrade_plugin_savepoint(true, 2017050541, 'local', 'program');
    }

    if ($oldversion < 2017050542) {
        $table = new xmldb_table('local_ccuser_year_signups');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('programid',XMLDB_TYPE_INTEGER, '10', null,XMLDB_NOTNULL,null,null);
        $table->add_field('curriculumid',XMLDB_TYPE_INTEGER, '10', null,XMLDB_NOTNULL,null,null);
        $table->add_field('yearid',XMLDB_TYPE_INTEGER, '10', null,XMLDB_NOTNULL,null,null);
        $table->add_field('userid',XMLDB_TYPE_INTEGER, '10', null,XMLDB_NOTNULL,null,null);
        $table->add_field('completion_status',XMLDB_TYPE_INTEGER, '10', null, null, null, '0');
        $table->add_field('completiondate',XMLDB_TYPE_INTEGER, '10', null,null,null,NULL);
        $table->add_field('usercreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('usermodified', XMLDB_TYPE_INTEGER, '10', null, null, null, '0');
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, null, null, '0');

        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        if (!$dbman->table_exists($table)) {
            $result = $dbman->create_table($table);
        }
        upgrade_plugin_savepoint(true, 2017050542, 'local', 'program');
    }

    if ($oldversion < 2017050543) {
        $table = new xmldb_table('local_cc_semester_classrooms');

        if ($dbman->table_exists($table)) {
            $field = new xmldb_field('nomination_startdate', XMLDB_TYPE_INTEGER, '10', XMLDB_NOTNULL, null, null, null);
            if (!$dbman->field_exists($table, $field)) {
                $dbman->add_field($table, $field);
            }
            $field1 = new xmldb_field('nomination_enddate', XMLDB_TYPE_INTEGER, '10', XMLDB_NOTNULL, null, null, null);
            if (!$dbman->field_exists($table, $field1)) {
                $dbman->add_field($table, $field1);
            }
        }
        upgrade_plugin_savepoint(true, 2017050543, 'local', 'program');
    }

    if ($oldversion < 2017050547) {
        $table = new xmldb_table('local_curriculum_semesters');

        if ($dbman->table_exists($table)) {
            $field = new xmldb_field('startdate', XMLDB_TYPE_INTEGER, '10', XMLDB_NOTNULL, null, null, null);
            if (!$dbman->field_exists($table, $field)) {
                $dbman->add_field($table, $field);
            }
            $field1 = new xmldb_field('enddate', XMLDB_TYPE_INTEGER, '10', XMLDB_NOTNULL, null, null, null);
            if (!$dbman->field_exists($table, $field1)) {
                $dbman->add_field($table, $field1);
            }
        }

        $table = new xmldb_table('local_program_cc_years');
        if ($dbman->table_exists($table)) {
            $field1 = new xmldb_field('sequence', XMLDB_TYPE_INTEGER, '10', XMLDB_NOTNULL, null, null, null);
            if (!$dbman->field_exists($table, $field1)) {
                $dbman->add_field($table, $field1);
            }
        }

        upgrade_plugin_savepoint(true, 2017050547, 'local', 'program');
    }

    if ($oldversion < 2017050550) {
        $table = new xmldb_table('local_program');

        if ($dbman->table_exists($table)) {
            $field = new xmldb_field('program_logo', XMLDB_TYPE_INTEGER, '10', XMLDB_NOTNULL, null, null, null);
            if (!$dbman->field_exists($table, $field)) {
                $dbman->add_field($table, $field);
            }
            $field1 = new xmldb_field('short_description', XMLDB_TYPE_CHAR, '255', XMLDB_NOTNULL, null, null, null);
            if (!$dbman->field_exists($table, $field1)) {
                $dbman->add_field($table, $field1);
            }
        }
        upgrade_plugin_savepoint(true, 2017050550, 'local', 'program');
    }
    if ($oldversion < 2017050550.02) {
        $table = new xmldb_table('local_program_unenroll_log');
            if (!$dbman->table_exists($table)) {
               $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
               $table->add_field('programid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
               $table->add_field('yearid',XMLDB_TYPE_INTEGER, '10', null,XMLDB_NOTNULL,null, null);
               $table->add_field('curriculumid',XMLDB_TYPE_INTEGER, '10', null,XMLDB_NOTNULL,null,NULL);               
               $table->add_field('userid',XMLDB_TYPE_INTEGER, '10', null,XMLDB_NOTNULL,null,null);
               $table->add_field('completion_status',XMLDB_TYPE_INTEGER, '10', null,null,null,NULL);
               $table->add_field('usercreated', XMLDB_TYPE_INTEGER, '10', null, null, null);
               $table->add_field('usermodified', XMLDB_TYPE_INTEGER, '10', null,null, null,null);
               $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10',null,null, null,null);
               $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10',null,null, null,null);
               $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
               $dbman->create_table($table);
            }
            upgrade_plugin_savepoint(true, 2017050550.02, 'local', 'program');
    }
    if ($oldversion < 2017050550.05) {
        $table = new xmldb_table('local_program');
        if ($dbman->table_exists($table)) {
            $field = new xmldb_field('curriculumid', XMLDB_TYPE_INTEGER, '10', XMLDB_NOTNULL, null, null, null);
            if($dbman->field_exists($table, $field)){
                $dbman->change_field_type($table, $field);
            }
        }
        upgrade_plugin_savepoint(true, 2017050550.05, 'local', 'program');
    }    
    if ($oldversion < 2017050550.06) {
        $table = new xmldb_table('local_program_unenroll_log');
        if ($dbman->table_exists($table)) {
            $field = new xmldb_field('yearid', XMLDB_TYPE_INTEGER, '10', XMLDB_NOTNULL, null, null, null);
            if($dbman->field_exists($table, $field)){
               $dbman->change_field_precision($table, $field);
            }
        }
        upgrade_plugin_savepoint(true, 2017050550.06, 'local', 'program');
    } 
    if ($oldversion < 2017050550.07) {
        $table = new xmldb_table('local_program_unenroll_log');
        if ($dbman->table_exists($table)) {
            $field = new xmldb_field('userid', XMLDB_TYPE_CHAR, '255', XMLDB_NOTNULL, null, null, null);
            if($dbman->field_exists($table, $field)){
               $dbman->change_field_type($table, $field);
            }
        }
        upgrade_plugin_savepoint(true, 2017050550.07, 'local', 'program');
    }       
// <mallikarjun> - ODL-763 Unable to create curriculum under college -- starts
    if ($oldversion < 2017050550.08) {
        $table = new xmldb_table('local_curriculum');
        $field1 = new xmldb_field('open_univdept_status', XMLDB_TYPE_INTEGER, '10',XMLDB_NOTNULL, null, null, null, null);
        if (!$dbman->field_exists($table, $field1)) {
            $dbman->add_field($table, $field1);
        }
        upgrade_plugin_savepoint(true, 2017050550.08, 'local', 'program');
    }
// <mallikarjun> - ODL-763 Unable to create curriculum under college -- end
// amulya - issue 740 create year with longtext - starts
    if ($oldversion < 2017050551) {
        $table = new xmldb_table('local_program_cc_years');
        if ($dbman->table_exists($table)) {
            $field = new xmldb_field('year', XMLDB_TYPE_CHAR, '255' , XMLDB_NOTNULL, null, null, null);
            if($dbman->field_exists($table, $field)){
               $dbman->change_field_type($table, $field);
            }
        }
        upgrade_plugin_savepoint(true, 2017050551, 'local', 'program');
    } 
// amulya - issue 740 create year with longtext - ends    
    return true;
}

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
 * This file keeps track of upgrades to the ltiprovider plugin
 *
 * @package    local
 * @subpackage Courses
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();
function xmldb_local_courses_install(){
	global $CFG,$DB,$USER;
	$dbman = $DB->get_manager(); // loads ddl manager and xmldb classes
	$table = new xmldb_table('course');
	if ($dbman->table_exists($table)) {
		$field1 = new xmldb_field('open_costcenterid');
        $field1->set_attributes(XMLDB_TYPE_INTEGER, '10',XMLDB_NOTNULL, null, null, null, null);
        $dbman->add_field($table, $field1);
        
        $field2 = new xmldb_field('open_departmentid');
        $field2->set_attributes(XMLDB_TYPE_INTEGER, '10',XMLDB_NOTNULL, null, null, null, null);
        $dbman->add_field($table, $field2);

        $field3 = new xmldb_field('open_identifiedas');
        $field3->set_attributes(XMLDB_TYPE_CHAR, '255',null, null, null, null);
        $dbman->add_field($table, $field3);
		
	$field4 = new xmldb_field('open_points');
        $field4->set_attributes(XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $dbman->add_field($table, $field4);
		
	$field5 = new xmldb_field('open_requestcourseid');
        $field5->set_attributes(XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $dbman->add_field($table, $field5);
		
	$field6 = new xmldb_field('open_coursecreator');
        $field6->set_attributes(XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $dbman->add_field($table, $field6);
		
	$field7 = new xmldb_field('open_coursecompletiondays');
        $field7->set_attributes(XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $dbman->add_field($table, $field7);

        $field8 = new xmldb_field('open_cost');
        $field8->set_attributes(XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $dbman->add_field($table, $field8);

        $field9 = new xmldb_field('open_skill');
        $field9->set_attributes(XMLDB_TYPE_INTEGER, '10', null, null, 0, null);
        $dbman->add_field($table, $field9);
		
	$field10= new xmldb_field('approvalreqd');
        $field10->set_attributes(XMLDB_TYPE_INTEGER, '10',XMLDB_NOTNULL, null, 0, null, null);
        $dbman->add_field($table, $field10);

        $field11= new xmldb_field('open_level');
        $field11->set_attributes(XMLDB_TYPE_INTEGER, '10',null, null, 0, null, null);
        $dbman->add_field($table, $field11);

        $field12= new xmldb_field('selfenrol');
        $field12->set_attributes(XMLDB_TYPE_INTEGER, '10',XMLDB_NOTNULL, null, 0, null, null);
        $dbman->add_field($table, $field12);

        $field12= new xmldb_field('open_parentcourseid');
        $field12->set_attributes(XMLDB_TYPE_INTEGER, '10',XMLDB_NOTNULL, 0, 0, null, null);
        $dbman->add_field($table, $field12);

        $field13= new xmldb_field('sold_status');
        $field13->set_attributes(XMLDB_TYPE_INTEGER, '10',null, null, 0, null, null);
        $dbman->add_field($table, $field13);

        $field14= new xmldb_field('forpurchaseindividually');
        $field14->set_attributes(XMLDB_TYPE_INTEGER, '10',null, null, 0, null, null);
        $dbman->add_field($table, $field14);

        $field15= new xmldb_field('affiliationstatus');
        $field15->set_attributes(XMLDB_TYPE_INTEGER, '10',null, null, 0, null, null);
        $dbman->add_field($table, $field15);

        $field16= new xmldb_field('enrolment_date');
        $field16->set_attributes(XMLDB_TYPE_INTEGER, '10',null, null, 0, null, null);
        $dbman->add_field($table, $field16);
	}
}

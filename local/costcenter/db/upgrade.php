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
function xmldb_local_costcenter_upgrade($oldversion) {
	global $DB, $CFG;
	$dbman = $DB->get_manager();
	if ($oldversion < 2017051505) {
		$table = new xmldb_table('local_costcenter');
		$field = new xmldb_field('multipleorg', XMLDB_TYPE_CHAR, '50', null, XMLDB_NOTNULL, null, null);
		if (!$dbman->field_exists($table, $field)) {
			$dbman->add_field($table, $field);
		}
		upgrade_plugin_savepoint(true, 2017051505, 'local', 'costcenter');
	}
	if ($oldversion < 2017051509) {
		$table = new xmldb_table('local_costcenter');
		$field = new xmldb_field('costcenter_logo', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NULL, null, '0');
		if (!$dbman->field_exists($table, $field)) {
			$dbman->add_field($table, $field);
		}
		upgrade_plugin_savepoint(true, 2017051509, 'local', 'costcenter');
	}
	if ($oldversion < 2017051512) {
		$table = new xmldb_table('local_costcenter');
		$field = new xmldb_field('projectid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NULL, null, '0');
		if (!$dbman->field_exists($table, $field)) {
			$dbman->add_field($table, $field);
		}
		upgrade_plugin_savepoint(true, 2017051512, 'local', 'costcenter');
	}
	if ($oldversion < 2017051513) {
		$table = new xmldb_table('local_costcenter');
		$facultyfield = new xmldb_field('faculty', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NULL, null, '0');
		if (!$dbman->field_exists($table, $facultyfield)) {
			$dbman->add_field($table, $facultyfield);
		}
		upgrade_plugin_savepoint(true, 2017051513, 'local', 'costcenter');
	}
	return true;
}
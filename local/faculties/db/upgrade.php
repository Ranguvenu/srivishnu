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
 * Faculties Upgrade
 *
 * @package     local_faculties
 * @author:     M Arun Kumar <arun@eabyas.in>
 *
 */
defined('MOODLE_INTERNAL') || die();

function xmldb_local_faculties_upgrade($oldversion) {
    global $DB, $CFG;
    $dbman = $DB->get_manager();
    if ($oldversion < 2017111305) {
        $table = new xmldb_table('local_faculties');
        $field = new xmldb_field('smbid', XMLDB_TYPE_INTEGER, '10',XMLDB_NOTNULL, null, null, null, null);
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        upgrade_plugin_savepoint(true, 2017111305, 'local', 'faculties');
    }
    if ($oldversion < 2017111307) {
        $table = new xmldb_table('local_faculties');
        $field = new xmldb_field('board', XMLDB_TYPE_INTEGER, '1', null, null, null, null);
        if($dbman->field_exists($table, $field)){
            $dbman->change_field_notnull($table, $field);
        }
        upgrade_plugin_savepoint(true, 2017111307, 'local', 'faculties');
    }
    return true;
}

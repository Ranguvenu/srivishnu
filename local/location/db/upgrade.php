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
 * @package     local_location
 *
 */
defined('MOODLE_INTERNAL') || die();

function xmldb_local_location_upgrade($oldversion) {
    global $DB, $CFG;
    $dbman = $DB->get_manager();
    if ($oldversion < 2016120505) {
        $table = new xmldb_table('local_location_room');
        $field = new xmldb_field('costcenter', XMLDB_TYPE_INTEGER, '10',XMLDB_NOTNULL, null, null, null, null);
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        upgrade_plugin_savepoint(true, 2016120505, 'local', 'location');
    }

    if ($oldversion < 2016120506) {
        $table = new xmldb_table('local_location_room');
        $field = new xmldb_field('subcostcenter', XMLDB_TYPE_INTEGER, '10',XMLDB_NOTNULL, null, null, null, null);
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $table = new xmldb_table('local_location_institutes');
        $field1 = new xmldb_field('subcostcenter', XMLDB_TYPE_INTEGER, '10',XMLDB_NOTNULL, null, null, null, null);
        if (!$dbman->field_exists($table, $field1)) {
            $dbman->add_field($table, $field1);
        }
        upgrade_plugin_savepoint(true, 2016120506, 'local', 'location');
    }
     
    return true;
}

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
 * Groups Upgrade
 *
 * @package     local_groups
 * @author:     Mallikarjun <mallikarjun@eabyas.in>
 *
 */
defined('MOODLE_INTERNAL') || die();

function xmldb_local_groups_upgrade($oldversion) {
    global $DB, $CFG;
    $dbman = $DB->get_manager();
    if ($oldversion < 2017111301.1) {
        $table = new xmldb_table('local_groups');
        $field1 = new xmldb_field('open_univdept_status', XMLDB_TYPE_INTEGER, '10',XMLDB_NOTNULL, null, null, null, null);
        if (!$dbman->field_exists($table, $field1)) {
            $dbman->add_field($table, $field1);
        }
        upgrade_plugin_savepoint(true, 2017111301.1, 'local', 'groups');
    }
    return true;
}

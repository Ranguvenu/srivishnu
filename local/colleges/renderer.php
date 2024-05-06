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
 * local courses rendrer
 *
 * @package    local_courses
 * @copyright  2017 eAbyas <eAbyas.in>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;
// use core_component;
class local_colleges_renderer extends plugin_renderer_base {

    
     /**
     * Display the avialable courses
     *
     * @return string The text to render
     */
    public function get_colleges($filterdata=0,$page, $perpage) {    
        global $DB, $CFG, $OUTPUT, $USER, $PAGE;
        $filterjson = json_encode($filterdata);
        //print_object($filterjson);exit;
        $PAGE->requires->js_call_amd('local_colleges/datatablesamd', 'collegeTableDatatable', array('filterdata'=> $filterjson));
        $table = new html_table();
        $table->id = "manage_colleges";
        $table->head = array(get_string('collegename', 'local_colleges'), 'University Name','Actions'/*'College Name',*/);

        //$table->head = array(get_string('firstname_surname', 'local_users'),get_string('employeeid', 'local_users'),get_string('emailaddress', 'local_users'),get_string('organization', 'local_users'),get_string('supervisorname', 'local_users'),get_string('lastaccess', 'local_users'),get_string('actions', 'local_users'));

        $output = '<div class="w-full pull-left">'. html_writer::table($table).'</div>';
        return $output;
    }

    
}

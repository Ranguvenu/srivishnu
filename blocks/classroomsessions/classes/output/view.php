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
 * Classroom View
 *
 * @package    block_classroomsessions
 * @copyright  2017 Harish <harish@eabyas.in>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_classroomsessions\output;

use html_writer;
use core_component;
use html_table;
use context_course;
use local_program\program;
class view{
    public function display_clroomsessions_dashboard(){
        global $OUTPUT;
         $programContext = [
            'upcomingsessions' => $this->get_upcoming_sessions_content('upcomingsessions'),
            'previoussessions' => $this->get_previous_sessions_content('previoussessions')
        ];
        return $OUTPUT->render_from_template('block_classroomsessions/view', $programContext);
    }
    public function get_upcoming_sessions_content($action){
        global $PAGE;
        $table = new html_table();
        $table->id = "trainer_classroom_upcomingsessions";
        $table->head = array('Classroom', 'Session', 'Date & Time', 'Room', 'Status', 'Attended Users', 'Action');
        $PAGE->requires->js_call_amd('block_classroomsessions/datatablesamd', 'trainerUpcomingSessionsDatatable', array('action' => $action));
        return html_writer::table($table);
    }
    public function get_previous_sessions_content($action){
        global $PAGE;
        $table = new html_table();
        $table->id = "trainer_classroom_previoussessions";
        $table->head = array('Classroom', 'Session', 'Date & Time', 'Room', 'Status', 'Attended Users', 'Action');
        $PAGE->requires->js_call_amd('block_classroomsessions/datatablesamd', 'trainerPreviousSessionsDatatable', array('action' => $action));
        return html_writer::table($table);
    }
}
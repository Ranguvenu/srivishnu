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
 * @package    block_faculty_dashboard
 * @copyright  2017 Syed Hameed Ullah <hameed@eabyas.in>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_faculty_dashboard\output;

use html_writer;
use core_component;
use html_table;
use context_course;

class view{
    public function display_faculty_dashboard(){
        global $OUTPUT;
         $programContext = [
            'programs' => $this->get_programs_content()
            //'distanceprograms' => $this->get_regular_programs_content()
            
        ];
        return $OUTPUT->render_from_template('block_faculty_dashboard/view', $programContext);
    }
    public function get_programs_content(){
        global $PAGE;
        $table = new html_table();
        $table->id = "trainer_programs_courses";
        $table->head = array('');
        $PAGE->requires->js_call_amd('block_faculty_dashboard/datatablesamd', 'trainerprogramsDatatable');
        return html_writer::table($table);
    }
    public function get_regular_programs_content(){
        global $PAGE;
        $table = new html_table();
        $table->id = "trainer_distanceprograms_courses";
        $table->head = array('');
        $PAGE->requires->js_call_amd('block_faculty_dashboard/datatablesamd', 'trainerdistanceprogramsDatatable');
        return html_writer::table($table);
    }
}
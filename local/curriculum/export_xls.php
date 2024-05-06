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

/** Configurable Reports
 * A Moodle block for creating customizable reports
 * @package blocks
 * @author: Juan leyva <http://www.twitter.com/jleyvadelgado>
 * @date: 2009
 */
use local_curriculum\program;
function export_report($curriculumid, $stable, $type) {
    global $DB, $CFG;
    require_once($CFG->dirroot . '/lib/excellib.class.php');
    $data = array();            
    // $curriculumusers = (new program)->curriculumusers($curriculumid, $stable);
    $matrix = array();
    $thead =array();
    // print_object($type);
    if($type == 'curriculumwise') {
        $filename = 'curriculum Users.xls';
        $sql = "SELECT u.*, cu.attended_sessions, cu.hours, cu.completion_status, c.totalsessions,
            c.activesessions FROM {user} AS u
                     JOIN {local_curriculum_users} AS cu ON cu.userid = u.id
                     JOIN {local_curriculum} AS c ON c.id = cu.curriculumid
                    WHERE c.id = $curriculumid AND u.confirmed = 1 AND u.suspended = 0 AND u.deleted = 0 AND u.id > 2 ORDER BY id ASC";
        $curriculumusers = $DB->get_records_sql($sql);
        $table = new html_table();
        if (!empty($curriculumusers)) {
            foreach ($curriculumusers as $sdata) {
               $curriculumname = $DB->get_field('local_curriculum', 'name', array('id'=>$curriculumid));
                $line = array();
                $line[] = fullname($sdata);
                $line[] = $curriculumname;
                $line[] = $sdata->open_employeeid;
                $line[] = $sdata->email;
                $supervisor = $DB->get_field('user', 'CONCAT(firstname, " ", lastname)', array('id' => $sdata->open_supervisorid));
                $line[] = !empty($supervisor) ? $supervisor : '--';
                $total_semesters = $DB->count_records('local_curriculum_semesters',  array('curriculumid' => $curriculumid));
                $completed_semesters = $DB->count_records('local_cc_semester_cmptl',  array('curriculumid' => $curriculumid, 'completion_status'=>1, 'userid'=>$sdata->id));
                $line[] = $completed_semesters.'/'.$total_semesters;
                $line[] = $sdata->completion_status == 1 ?'Completed' : 'Not Completed';
                $data[] = $line;
            }
            $table->data = $data;
        }
        $table->head = array(get_string('employee', 'local_curriculum'), get_string('curriculum_name', 'local_curriculum'), get_string('employeeid', 'local_curriculum'), get_string('email'),get_string('supervisor', 'local_curriculum'),get_string('noofsemesters', 'local_curriculum'), get_string('status'));
    } else if($type == 'coursewise') {
        $filename = 'Course wise session report.xls';
        $sql = "SELECT u.* FROM {user} AS u
                     JOIN {local_curriculum_users} AS cu ON cu.userid = u.id
                     JOIN {local_curriculum} AS c ON c.id = cu.curriculumid
                    WHERE c.id = $curriculumid AND u.confirmed = 1 AND u.suspended = 0 AND u.deleted = 0 AND u.id > 2 ORDER BY id ASC";
        $curriculumusers = $DB->get_records_sql($sql);
        $table = new html_table();
        if (!empty($curriculumusers)) {
            foreach ($curriculumusers as $sdata) {
                $sql = "SELECT ss.* FROM {local_cc_session_signups} AS ss
                     -- JOIN {local_curriculum_users} AS cu ON cu.userid = u.id
                     JOIN {local_curriculum} AS c ON c.id = ss.curriculumid
                    WHERE c.id = $curriculumid AND ss.userid = $sdata->id ORDER BY id ASC";
                $sessionenrolledusers = $DB->get_records_sql($sql);
                if(!empty($sessionenrolledusers)){
                    foreach ($sessionenrolledusers as $sessionenrolleduser) {
                        $curriculumname = $DB->get_field('local_curriculum', 'name', array('id'=>$curriculumid));
                        $courseid = $DB->get_field('local_cc_semester_courses', 'courseid', array('id'=>$sessionenrolleduser->bclcid));
                        $coursename = $DB->get_field('course', 'fullname', array('id'=>$courseid));
                        $sessionname = $DB->get_field('local_cc_course_sessions', 'name', array('id'=>$sessionenrolleduser->sessionid));
                        $line = array();
                        $line[] = fullname($sdata);
                        $line[] = $sdata->open_employeeid;
                        $line[] = $sdata->email;
                        $line[] = $curriculumname;
                        $line[] = $coursename;
                        $line[] = $sessionname;
                        $line[] = $sessionenrolleduser->completion_status == 1 ?'Present' : 'Absent';
                        $line[] = $sessionenrolleduser->completion_status == 1 ? date('Y-m-d', $sessionenrolleduser->completiondate) : 'NA';
                        $data[] = $line;
                    }
                } else {
                    $curriculumname = $DB->get_field('local_curriculum', 'name', array('id'=>$curriculumid));
                    $line1 = array();
                    $line1[] = fullname($sdata);
                    $line1[] = $sdata->open_employeeid;
                    $line1[] = $sdata->email;
                    $line1[] = $curriculumname;
                    $line1[] = '--';
                    $line1[] = '--';
                    $line1[] = '--';
                    $line1[] = '--';
                    $data[] = $line1;
                }
            }
            $table->data = $data;
        }
        $table->head = array(get_string('employee', 'local_curriculum'), get_string('employeeid', 'local_curriculum'), get_string('email'), get_string('curriculum_name', 'local_curriculum'),get_string('course', 'local_curriculum'),get_string('session_name', 'local_curriculum'), get_string('status'), 'Date');
    }
    // print_object($table->head);
    // print_object($table->data);exit;
    if (!empty($table->head)) {
        foreach ($table->head as $key => $heading) {
            // $matrix[0][0] = $reportname;
            $matrix[0][$key] = str_replace("\n", ' ', htmlspecialchars_decode(strip_tags(nl2br($heading))));
        }
    }

    if (!empty($table->data)) {
        foreach ($table->data as $rkey => $row) {
            foreach ($row as $key => $item) {
                $matrix[$rkey + 1][$key] = str_replace("\n", ' ', htmlspecialchars_decode(strip_tags(nl2br($item))));
            }
        }
    }
    $downloadfilename = clean_filename($filename);
    /// Creating a workbook
    $workbook = new MoodleExcelWorkbook("-");
    /// Sending HTTP headers
    $workbook->send($downloadfilename);
    /// Adding the worksheet
    $myxls = $workbook->add_worksheet($filename);
    // print_object($matrix);
    foreach ($matrix as $ri => $col) {
        foreach ($col as $ci => $cv) {
            //Formatting by sowmya
            $format = array('border'=>1);
            if($ri == 0){
                $format['bold'] = 1;
                $format['bg_color'] = '#f0a654';
                $format['color'] = '#FFFFFF';
            }
            
            if(is_numeric($cv)){
                $format['align'] = 'center';
                $myxls->write_number($ri, $ci, $cv, $format);
            } else {
                $myxls->write_string($ri, $ci, $cv, $format);
            }
        }
    }//exit;
    $workbook->close();
    exit;
}
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
 * elearning  courses
 *
 * @package    block_userdashboard
 * @copyright  2018 hemalatha c arun <hemalatha@eabyas.in>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace block_userdashboard\lib;

use renderable;
use renderer_base;
use templatable;
use context_course;
use stdClass;
// use block_userdashboard\ouput\
// use block_userdashboard\ouput\program_courses as prcourses;
class program{

    public static function inprogress_programs($filter_text='') {
        global $DB, $USER;
        $sql = "SELECT p.id, p.fullname AS fullname,  p.description, cu.curriculumid
                  FROM {local_program} AS p
                  JOIN {local_curriculum_users} AS cu ON cu.programid = p.id
                 WHERE cu.userid = $USER->id AND cu.completion_status = 0 AND cu.completiondate = 0 ";
        if(!empty($filter_text)){
            $sql .= " AND p.fullname LIKE '%%$filter_text%%'";
        }
        $sql .= "ORDER BY p.id desc";
        $inprogress_programs = $DB->get_records_sql($sql);
        return $inprogress_programs;
    }
    public static function completed_programs($filter_text='') {
            global $DB, $USER;
            $sql = "SELECT p.id, p.fullname AS fullname,  p.description, lcu.curriculumid
                      FROM {local_program} as p
                      JOIN {local_curriculum_users} AS lcu ON p.id = lcu.programid
                     WHERE lcu.completion_status = 1 AND lcu.completiondate > 0
                            AND lcu.userid = $USER->id ";
            if(!empty($filter_text)){
                $sql .= " AND p.fullname LIKE '%%$filter_text%%'";
            }
            $sql .= "ORDER BY p.id desc";
            $completed_bootcamps = $DB->get_records_sql($sql);
            $completed_count = count($completed_bootcamps);
            return $completed_bootcamps;
    }

    public static function my_programs($stable,$filtervalues) {
        global $DB, $USER, $CFG, $PAGE;
        require_once $CFG->dirroot.'/local/program/lib.php';
        $countsql = "SELECT count(p.id) ";
        $sql = "SELECT p.id, p.fullname AS fullname,  p.description, lcu.curriculumid ";
        $fromsql = " FROM {local_program} as p
                  JOIN {local_curriculum_users} AS lcu ON p.id = lcu.programid
                 WHERE lcu.userid = $USER->id ";
        if(!empty($filtervalues) && $filtervalues->status == 'completed'){
            $fromsql .= " AND lcu.completion_status = 1 AND lcu.completiondate > 0 ";
        }else{
            $fromsql .= " AND lcu.completion_status = 0 AND lcu.completiondate = 0 ";
        }
        if(!empty($filtervalues->filterdata)){
           $fromsql .= " AND p.fullname LIKE '%%$filtervalues->filterdata%%'";
        }
        $queryparam = array();
        $count = $DB->count_records_sql($countsql.$fromsql);
        $fromsql .= "ORDER BY p.id desc";
        $myprograms = $DB->get_records_sql($sql.$fromsql, $queryparam, $stable->start, $stable->length);
        // $list=array();
        if ($myprograms) {
          $data = array();
          foreach ($myprograms as $myprogram) {
            $list = array();
            $myprogramsummary = strip_tags($myprogram->description);
            $summarystring = strlen($myprogramsummary) > 100 ? substr($myprogramsummary, 0, 100)."..." : $myprogramsummary;
            $myprogramsummary = $summarystring;
            if(empty($myprogramsummary)){
                $myprogramsummary = '<span class="w-full text-xs-center alert alert-info pull-left">'.get_string('nodecscriptionprovided','block_userdashboard').'</span>';
            }
            $list['bootcampdescription'] = $myprogramsummary;
            $years = $DB->get_records_sql("SELECT id FROM {local_program_cc_years} WHERE programid = $myprogram->id");
            $semesters = $DB->get_records_sql("SELECT id FROM {local_curriculum_semesters} WHERE programid = $myprogram->id");
            $semcourses = $DB->get_records_sql("SELECT c.id, c.fullname
            FROM {course} AS c
            JOIN {local_cc_semester_courses} AS lcsc ON lcsc.programid = $myprogram->id
            WHERE lcsc.courseid = c.id");
            $programdata = $DB->get_record('local_program', array('id' => $myprogram->id), 'program_logo, short_description');
            
            if($programdata->program_logo){
                $prgmimg_urlpath = program_logo($programdata->program_logo);
            }
            if(!empty($prgmimg_urlpath)){
                $list['program_imgpath'] = $prgmimg_urlpath->out();
            }else{
                $list['program_imgpath'] = null;
            }


            if($programdata->short_description){
                $list['program_shortdescp'] = $programdata->short_description;
            }else{
                $list['program_shortdescp'] = null;
            }
            
            //---------get bootcamp fullname-----
            $list['bootcamp_fullname'] = $myprogram->fullname;
            $list['programid'] = $myprogram->id;
            $list['programyears'] = count($years);
            $list['programsemesters'] = count($semesters);
            $list['programcourses'] = count($semcourses);
            if (strlen($myprogram->fullname) >= 22) {
                $myprogram_fullname = substr($myprogram->fullname, 0, 22) . '...';
            } else {
                $myprogram_fullname = $myprogram->fullname;
            }
            $list['inprogress_bootcamp_fullname'] =  $myprogram_fullname;
            $list['bootcamp_url'] = $CFG->wwwroot.'/local/program/view.php?ccid='.$myprogram->curriculumid.'&prgid='.$myprogram->id;
            $list['syllabus_url'] = $CFG->wwwroot . '/local/program/syllabus.php?pid='.$myprogram->id.'&ccid='.$myprogram->curriculumid;
            $data[] = $list;
          }
        }
        return array('count' => $count, 'data' => $data); 
    }

    public static function gettotal_programs(){
            global $DB, $USER;
            $sql = "SELECT bc.id,bc.fullname AS fullname,  bc.description  FROM {local_program} AS bc
                    JOIN {local_curriculum_users} AS bcu ON bc.id = bcu.curriculumid
                    WHERE bc.status IN(1,4) AND bcu.userid=$USER->id ";
            $coursenames = $DB->get_records_sql($sql);
            return count($coursenames);
    }


} // end of class


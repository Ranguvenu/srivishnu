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
 * @copyright  2018 Maheshchandra <maheshchandra@eabyas.in>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace block_userdashboard\output;
require_once $CFG->dirroot . '/local/program/lib.php';
use renderable;
use renderer_base;
use templatable;
use context_course;
use stdClass;
use block_userdashboard\lib\program  as programslib;
use block_userdashboard\includes\generic_content;

class program_courses implements renderable, templatable {

    //-----hold the courselist inprogress or completed
    private $courseslist;

    private $subtab='';

    private $programtemplate=0;

    private $filter_text='';

    private $filter='';

    public function __construct($filter, $filter_text=''){

        switch ($filter){
            case 'inprogress':
                    $this->courseslist = programslib::inprogress_programs($filter_text);
                    $this->subtab='elearning_inprogress';
                break;

            case 'completed' :
                    $this->courseslist = programslib::completed_programs($filter_text);
                    $this->subtab='elearning_completed';
                break;
        }
        $this->filter = $filter;
        $this->filter_text = $filter_text;
        $this->programtemplate=1;

    } // end of the function





    /**
     * Export the data.
     *
     * @param renderer_base $output
     * @return stdClass
     */
    public function export_for_template(renderer_base $output) {
        global $DB, $USER, $CFG;

        $data = new stdClass();
        $courses_active = '';
        $tabs = '';
        $data->inprogress_elearning = array();
        $inprogress_bootcamps = programslib::inprogress_programs($this->filter_text);
        $completed_bootcamps = programslib::completed_programs($this->filter_text);
        $inprogresscount = count($this->courseslist);
        $completedcount = count($completed_bootcamps);
        $total = $inprogresscount+$completedcount;

        if ($courseslist == '') {
            $courseslist = null;
        }

        if ($inprogress_bootcamps == '') {
            $inprogress_bootcamps = null;
        }

        if ($completed_bootcamps == '') {
            $completed_bootcamps = null;
        }
        $data->course_count_view =0;
        $data->total =$total;
        $data->index = count($this->courseslist)-1;
        $data->filter = $this->filter;
        $data->filter_text = $this->filter_text;
        $data->inprogresscount= count($this->courseslist);
        $data->completedcount = count($completed_bootcamps);
        $data->functionname ='program_courses';
        $data->subtab= $this->subtab;
        $data->programtemplate= $this->programtemplate;
        $data->view_more_url = $CFG->wwwroot.'/blocks/userdashboard/userdashboard_courses.php?tab=program_courses&subtab=inprogress';
        $courses_view_count = count($this->courseslist);
        $data->courses_view_count = $courses_view_count;
        if($courses_view_count >= 2)
            $data->enableslider = 1;
        else
            $data->enableslider = 0;

        if (!empty($this->courseslist)) {
            $data->inprogress_elearning_available = 1;
        }
        else{
            $data->inprogress_elearning_available = 0;
        }


        if (!empty($this->courseslist)) {

            $data->course_count_view = generic_content::get_coursecount_class($this->courseslist);

            foreach ($this->courseslist as $inprogress_programs) {
                $onerow=array();
                //-------- get the bootcamp summary------------------------
                $onerow['bootcampdescription'] = $this->get_coursesummary($inprogress_programs);

                // $onerow['syllabus'] = get_user_program_syllabus($inprogress_programs->id);
                $years = $DB->get_records_sql("SELECT id FROM {local_program_cc_years} WHERE programid = $inprogress_programs->id");
                $semesters = $DB->get_records_sql("SELECT id FROM {local_curriculum_semesters} WHERE programid = $inprogress_programs->id");
                $semcourses = $DB->get_records_sql("SELECT c.id, c.fullname
                FROM {course} AS c
                JOIN {local_cc_semester_courses} AS lcsc ON lcsc.programid = $inprogress_programs->id
                WHERE lcsc.courseid = c.id");

                //Added by Harish for fetching program image url path & short description starts here//
                $programdata = $DB->get_record('local_program', array('id' => $inprogress_programs->id), 'program_logo, short_description');
                if($programdata->program_logo){
                    $prgmimg_urlpath = program_logo($programdata->program_logo);
                }
                if(!empty($prgmimg_urlpath)){
                    $onerow['program_imgpath'] = $prgmimg_urlpath;
                }else{
                    $onerow['program_imgpath'] = null;
                }

                if($programdata->short_description){
                    $onerow['program_shortdescp'] = $programdata->short_description;
                }else{
                    $onerow['program_shortdescp'] = null;
                }
                //Added by Harish for fetching program logo url path & short description ends here//
                // $years = $DB->get_records_sql("SELECT id FROM {local_program_cc_years} WHERE programid = $inprogress_programs->id");
                //---------get bootcamp fullname-----
                $onerow['bootcamp_fullname'] = $inprogress_programs->fullname;
                $onerow['programid'] = $inprogress_programs->id;
                $onerow['programyears'] = count($years);
                $onerow['programsemesters'] = count($semesters);
                $onerow['programcourses'] = count($semcourses);
                $onerow['inprogress_bootcamp_fullname'] = $this->get_coursefullname($inprogress_programs);
                $onerow['bootcamp_url'] = $CFG->wwwroot.'/local/program/view.php?ccid='.$inprogress_programs->curriculumid.'&prgid='.$inprogress_programs->id;
                $onerow['syllabus_url'] = $CFG->wwwroot . '/local/program/syllabus.php?pid='.$inprogress_programs->id.'&ccid='.$inprogress_programs->curriculumid;
                $bootcamps_completed= $completedcount;
                array_push($data->inprogress_elearning, $onerow);

            } // end of foreach

        } // end of if condition
        $data->inprogress_elearning= json_encode($data->inprogress_elearning);
        $data->menu_heading = '';
        $data->nodata_string = get_string('nobootcampsavailable','block_userdashboard');
        return $data;
    }


    private function get_coursesummary($course_record){

        $coursesummary = strip_tags($course_record->description);
        $summarystring = strlen($coursesummary) > 100 ? substr($coursesummary, 0, 100)."..." : $coursesummary;
        $coursesummary = $summarystring;
        if(empty($coursesummary)){
            $coursesummary = '<span class="w-full text-xs-center alert alert-info pull-left">'.get_string('nodecscriptionprovided','block_userdashboard').'</span>';
        }

        return $coursesummary;
    } // end of function


    private function get_coursefullname($inprogress_coursename){

        $course_fullname = $inprogress_coursename->fullname;
        if (strlen($course_fullname) >= 22) {
            $inprogress_coursename_fullname = substr($inprogress_coursename->fullname, 0, 22) . '...';
        } else {
            $inprogress_coursename_fullname = $inprogress_coursename->fullname;
        }

        return $inprogress_coursename_fullname;
    } // end of function



} // end of class

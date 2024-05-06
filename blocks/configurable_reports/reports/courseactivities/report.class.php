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

/** Cobalt Reports
* A Moodle block for creating customizable reports
* @package blocks
* @author: Juan leyva <http://www.twitter.com/jleyvadelgado>
* @date: 2009
*/
// Weekwise quizzes by Yamini //

class report_courseactivities extends report_base {

    function init() {
    $this->components = array('columns', 'conditions', 'filters', 'template', 'permissions');
    
    }

    function get_all_elements() {
               return array();         
    }
   

    function get_rows($elements=array(), $sqlorder = '') {   
        global $DB, $CFG, $USER, $OUTPUT;
        $systemcontext = context_system::instance();
        require_once($CFG->dirroot.'/blocks/configurable_reports/lib.php');
        $finalelements = array();
        $filter_course = optional_param('filter_courses',0,PARAM_INT);
        if (!empty($filter_course)) {
            $courseid = $filter_course;          
        
         $data=array();
           $coursemodules = "SELECT cm.id,cm.instance,cm.module from {course_modules} cm WHERE cm.course =".$courseid;
            $coursemodule = $DB->get_records_sql($coursemodules);
           
            $coursemodulenames = array();
            foreach($coursemodule as $modules){
                $course=new stdClass();
                 $modulename = $DB->get_field('modules','name',array('id' => $modules->module));
              $sql = "SELECT name FROM {".$modulename."} WHERE id =".$modules->instance; 
              $module = $DB->get_record_sql($sql);
              $coursemodulenames[] = $module->name;
            }
              $names = implode(',', $coursemodulenames);
              $userres=new stdClass();
             
              $sql = "SELECT COUNT(course) as count FROM {course_modules} WHERE course = $courseid";
              $count = $DB->get_record_sql($sql);
              $userres->noofactivities = $count->count;
              $userres->activitynames = $names;
              
           
        
        $data[] = $userres;
            
    }
    return $data;
   }

}

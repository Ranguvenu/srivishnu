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
 * This is the external API for this tool.
 *
 * @package    block_userdashboard
 * @copyright  2018 hemalataha c arun <hemalatha@eabyas.in>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace block_userdashboard;
defined('MOODLE_INTERNAL') || die();

require_once("$CFG->libdir/externallib.php");


use context;
use context_system;
use context_course;
use context_helper;
use context_user;
use coding_exception;
use external_api;
use external_function_parameters;
use external_value;
use external_format_value;
use external_single_structure;
use external_multiple_structure;
use invalid_parameter_exception;
use required_capability_exception;
use block_userdashboard\lib\program  as programslib;
use block_userdashboard\lib\elearning_courses  as courseslist_lib;
use core_cohort\external\cohort_summary_exporter;


/**
 * This is the external API for this tool.
 *
 * @copyright  2018 Hemalatha c arun
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class external extends external_api {

    /**
     * Returns a prepared structure to use a context parameters.
     * @return external_single_structure
     */
    protected static function get_context_parameters() {
        $id = new external_value(
            PARAM_INT,
            'Context ID. Either use this value, or level and instanceid.',
            VALUE_DEFAULT,
            0
        );
        $level = new external_value(
            PARAM_ALPHA,
            'Context level. To be used with instanceid.',
            VALUE_DEFAULT,
            ''
        );
        $instanceid = new external_value(
            PARAM_INT,
            'Context instance ID. To be used with level',
            VALUE_DEFAULT,
            0
        );
        return new external_single_structure(array(
            'contextid' => $id,
            'contextlevel' => $level,
            'instanceid' => $instanceid,
        ));
    }    

    
    /**
     * Returns the description of the 
     data_for_elearning_courses_parameters.
     *
     * @return external_function_parameters.
     */
    public static function data_for_elearning_courses_parameters() {
        return new external_function_parameters([
            'options' => new external_value(PARAM_RAW, 'The paging data for the service'),
            'dataoptions' => new external_value(PARAM_RAW, 'The data for the service'),
            'offset' => new external_value(PARAM_INT, 'Number of items to skip from the begging of the result set',
                VALUE_DEFAULT, 0),
            'limit' => new external_value(PARAM_INT, 'Maximum number of results to return',
                VALUE_DEFAULT, 0),
            'contextid' => new external_value(PARAM_INT, 'contextid'),
            'filterdata' => new external_value(PARAM_RAW, 'The data for the service'),
        ]);
    }


   /**
     * Gets the list of courses based on the login user
     *
     * @param int $options need to give options targetid,viewtype,perpage,cardclass
     * @param int $dataoptions need to give data which you need to get records
     * @param int $limit Maximum number of results to return
     * @param int $offset Number of items to skip from the beginning of the result set.
     * @param int $filterdata need to pass filterdata.
     * @return array The list of courses
     */
    public static function data_for_elearning_courses(
        $options,
        $dataoptions,
        $offset = 0,
        $limit = 0,
        $contextid,
        $filterdata) {
        global $PAGE;

        $params = self::validate_parameters(self::data_for_elearning_courses_parameters(), array(
            'options' => $options,
            'dataoptions' => $dataoptions,
            'offset' => $offset,
            'limit' => $limit,
            'contextid' => $contextid,
            'filterdata' => $filterdata
        ));

        $PAGE->set_context(context_system::instance());
        $offset = $params['offset'];
        $limit = $params['limit'];
        $decodedata = json_decode($params['dataoptions']);
        $filtervalues = json_decode($filterdata);

        $stable = new \stdClass();
        $stable->thead = true;
       
        $stable->thead = false;
        $stable->start = $offset;
        $stable->length = $limit;
        $result_courses = courseslist_lib::my_courses($stable,$filtervalues);
        $totalcount = $result_courses['count'];
        if($totalcount>0){
            $data = $result_courses['data'];
        }else{
            $data = array();  //No data available in table
        }
        return [
            'totalcount' => $totalcount,
            'records' =>$data,
            'options' => $options,
            'dataoptions' => $dataoptions,
            'filterdata' => $filterdata,
        ];
    }

    /**
     * Returns description of data_for_elearning_courses_returns() result value.
     *
     * @return external_description
     */
   public static function data_for_elearning_courses_returns() {
        return new external_single_structure([
            'options' => new external_value(PARAM_RAW, 'The paging data for the service'),
            'dataoptions' => new external_value(PARAM_RAW, 'The data for the service'),
            'totalcount' => new external_value(PARAM_INT, 'total number of skills in result set'),
            'filterdata' => new external_value(PARAM_RAW, 'The data for the service'),
            'records' => new external_multiple_structure(
                            new external_single_structure(
                                array(
                                    'bootcamp_fullname'=>new external_value(PARAM_RAW, 'fullname', VALUE_OPTIONAL),
                                    'courseid'=>new external_value(PARAM_INT, 'courseid'),
                                    'enrolledusers'=>new external_value(PARAM_INT, 'enrolledusers', VALUE_OPTIONAL),
                                    'inprogress_bootcamp_fullname'=>new external_value(PARAM_RAW, 'inprogress_bootcamp_fullname', VALUE_OPTIONAL),
                                    'bootcamp_url'=>new external_value(PARAM_RAW, 'bootcamp_url', VALUE_OPTIONAL),
                                )
                            )
                        )
        ]);
    }  // end of the function data_for_elearning_courses_returns


/**
 * [data_for_program_courses_parameters description]
 * @return parameters for data_for_program_courses
 */
    public static function data_for_program_courses_parameters() {
        return new external_function_parameters([
            'options' => new external_value(PARAM_RAW, 'The paging data for the service'),
            'dataoptions' => new external_value(PARAM_RAW, 'The data for the service'),
            'offset' => new external_value(PARAM_INT, 'Number of items to skip from the begging of the result set',
                VALUE_DEFAULT, 0),
            'limit' => new external_value(PARAM_INT, 'Maximum number of results to return',
                VALUE_DEFAULT, 0),
            'contextid' => new external_value(PARAM_INT, 'contextid'),
            'filterdata' => new external_value(PARAM_RAW, 'The data for the service'),
        ]);
    }

    /**
     * Gets the list of programs based on the login user
     *
     * @param int $options need to give options targetid,viewtype,perpage,cardclass
     * @param int $dataoptions need to give data which you need to get records
     * @param int $limit Maximum number of results to return
     * @param int $offset Number of items to skip from the beginning of the result set.
     * @param int $filterdata need to pass filterdata.
     * @return array The list of programs
     */
    public static function data_for_program_courses(
        $options,
        $dataoptions,
        $offset = 0,
        $limit = 0,
        $contextid,
        $filterdata) {
        global $PAGE;

        $params = self::validate_parameters(self::data_for_program_courses_parameters(), array(
            'options' => $options,
            'dataoptions' => $dataoptions,
            'offset' => $offset,
            'limit' => $limit,
            'contextid' => $contextid,
            'filterdata' => $filterdata
        ));

        $PAGE->set_context(context_system::instance());
        $offset = $params['offset'];
        $limit = $params['limit'];
        $decodedata = json_decode($params['dataoptions']);
        $filtervalues = json_decode($filterdata);

        $stable = new \stdClass();
        $stable->thead = true;
       
        $stable->thead = false;
        $stable->start = $offset;
        $stable->length = $limit;
        $result_programs = programslib::my_programs($stable,$filtervalues);
        $totalcount = $result_programs['count'];
        if($totalcount>0){
            $data = $result_programs['data'];
        }else{
            $data = array();  //No data available in table
        }
        return [
            'totalcount' => $totalcount,
            'records' =>$data,
            'options' => $options,
            'dataoptions' => $dataoptions,
            'filterdata' => $filterdata,
        ];
    }


    public static function data_for_program_courses_returns() {
        return new external_single_structure([
            'options' => new external_value(PARAM_RAW, 'The paging data for the service'),
            'dataoptions' => new external_value(PARAM_RAW, 'The data for the service'),
            'totalcount' => new external_value(PARAM_INT, 'total number of skills in result set'),
            'filterdata' => new external_value(PARAM_RAW, 'The data for the service'),
            'records' => new external_multiple_structure(
                            new external_single_structure(
                                array(
                                    'bootcampdescription'=>new external_value(PARAM_RAW, 'description', VALUE_OPTIONAL),
                                    'program_imgpath'=>new external_value(PARAM_RAW, 'program_imgpath', VALUE_OPTIONAL),
                                    'program_shortdescp'=>new external_value(PARAM_RAW, 'program_shortdescp', VALUE_OPTIONAL),
                                    'bootcampdescription'=>new external_value(PARAM_RAW, 'fullname', VALUE_OPTIONAL),
                                    'bootcamp_fullname'=>new external_value(PARAM_RAW, 'fullname', VALUE_OPTIONAL),

                                    'programid'=>new external_value(PARAM_INT, 'programid'),
                                    'programyears'=>new external_value(PARAM_INT, 'programyears', VALUE_OPTIONAL),
                                    'programsemesters'=>new external_value(PARAM_INT, 'programsemesters', VALUE_OPTIONAL),

                                    'programcourses'=>new external_value(PARAM_INT, 'programcourses', VALUE_OPTIONAL),
                                    'inprogress_bootcamp_fullname'=>new external_value(PARAM_RAW, 'inprogress_bootcamp_fullname', VALUE_OPTIONAL),
                                    'bootcamp_url'=>new external_value(PARAM_RAW, 'bootcamp_url', VALUE_OPTIONAL),
                                    'syllabus_url'=>new external_value(PARAM_RAW, 'syllabus_url', VALUE_OPTIONAL),
                                )
                            )
                        )
        ]);

    }  // end of the function data_for_program_courses_returns
    
// /**
//  * [data_for_program_courses_parameters description]
//  * @return parameters for data_for_program_courses
//  */
//     public static function data_for_xseed_parameters() {
//         $filter = new external_value(PARAM_TEXT, 'Filter text');
//         $filter_text = new external_value(PARAM_TEXT, 'Filter name',VALUE_OPTIONAL);
//         $params = array(
//             'filter' => $filter,
//             'filter_text' => $filter_text,
//         );
//         return new external_function_parameters($params);
//     }


//     public static function data_for_xseed($filter, $filter_text='') {
//         global $PAGE;

//         $params = self::validate_parameters(self::data_for_xseed_parameters(), array(
//             'filter' => $filter,
//             'filter_text' => $filter_text,
//         ));

//         $PAGE->set_context(context_system::instance());
//         $renderable = new output\program_courses($params['filter'],$params['filter_text']);
//         $output = $PAGE->get_renderer('block_userdashboard');

//         $data= $renderable->export_for_template($output);
      
//         return $data;
//     }


//     public static function data_for_xseed_returns() {
     

//         return new external_single_structure(array (
    
//             'total' => new external_value(PARAM_INT, 'Number of enrolled courses.', VALUE_OPTIONAL),           
//             'inprogresscount'=>  new external_value(PARAM_INT, 'Number of inprogress course count.'),  
//             'completedcount'=>  new external_value(PARAM_INT, 'Number of complete course count.'), 
//             'courses_view_count'=>  new external_value(PARAM_INT, 'Number of courses count.'), 
//             'enableslider'=>  new external_value(PARAM_INT, 'Flag for enable the slider.'),
//             'inprogress_elearning_available'=>  new external_value(PARAM_INT, 'Flag to check enrolled course available or not.'),
//             'course_count_view'=>  new external_value(PARAM_TEXT, 'to add course count class'),
//                'functionname' => new external_value(PARAM_TEXT, 'Function name'),
//                'subtab' => new external_value(PARAM_TEXT, 'Sub tab name'),
//                'xseedtemplate' => new external_value(PARAM_INT, 'template name',VALUE_OPTIONAL),
//                 'menu_heading' => new external_value(PARAM_TEXT, 'heading string of the dashboard'),
//                 'inprogress_elearning' => new external_value(PARAM_RAW, 'Function name'),
//                 // 'menu_heading' => new external_value(PARAM_TEXT, 'heading string of the dashboard'),
//                'nodata_string' => new external_value(PARAM_TEXT, 'no data message'),
//                'index' => new external_value(PARAM_INT, 'number of courses count'),
//                'filter' => new external_value(PARAM_TEXT, 'filter for display data'),
//                'filter_text' => new external_value(PARAM_TEXT, 'filtertext content',VALUE_OPTIONAL),
//                'view_more_url' => new external_value(PARAM_URL, 'view_more_url for tab'),

//         ));

//     }  // end of the function data_for_program_courses_returns

} // end of class

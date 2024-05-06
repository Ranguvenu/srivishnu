<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or localify
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
 * Courses external API
 *
 * @package    local_courses
 * @category   external
 * @copyright  eAbyas <www.eabyas.in>
 */
defined('MOODLE_INTERNAL') || die;

// use \local_courses\form\custom_course_form as custom_course_form;
// use \local_courses\action\insert as insert;


require_once("$CFG->libdir/externallib.php");
require_once($CFG->dirroot.'/course/lib.php');
require_once($CFG->dirroot.'/local/courses/lib.php');

class local_departments_external extends external_api {

         /**
     * Describes the parameters for submit_create_course_form webservice.
     * @return external_function_parameters
     */
    public static function submit_create_department_form_parameters() {
        return new external_function_parameters(
            array(
                'contextid' => new external_value(PARAM_INT, 'The context id for the category'),
                'jsonformdata' => new external_value(PARAM_RAW, 'The data from the create course form, encoded as a json array')
            )
        );
    }

    /**
     * Submit the create category form.
     *
     * @param int $contextid The context id for the category.
     * @param string $jsonformdata The data from the form, encoded as a json array.
     * @return int new category id.
     */
    public static function submit_create_department_form($contextid, $jsonformdata) {
        global $DB, $CFG, $USER;
        require_once($CFG->dirroot.'/course/lib.php');
        require_once($CFG->libdir.'/coursecatlib.php');
        require_once($CFG->dirroot . '/local/courses/lib.php');
        require_once($CFG->dirroot . '/local/costcenter/lib.php');

        // We always must pass webservice params through validate_parameters.
        $params = self::validate_parameters(self::submit_create_department_form_parameters(),
                                            ['contextid' => $contextid, 'jsonformdata' => $jsonformdata]);

        $context = context::instance_by_id($params['contextid'], MUST_EXIST);
        // We always must call validate_context in a webservice.
        self::validate_context($context);
        $serialiseddata = json_decode($params['jsonformdata']);

        $data = array();
        parse_str($serialiseddata, $data);
        $warnings = array();
        $id = $data['id']; 
        /*$catcustomid = $data['catid'];
        $univ_cost = $data['university'];
        $college_cost = $data['college'];
        if($univ_cost){
                $parent = $DB->get_field('local_costcenter','category',array('id'=>$univ_cost));
                $univcatid = $parent;
        }
        if($college_cost){
            $parent = $DB->get_field('local_costcenter','category',array('id'=>$college_cost));
            $collegecatid = $parent;
        }

        if ($id) {
            $coursecat = coursecat::get($catcustomid, MUST_EXIST, true);
            $category = $coursecat->get_db_record();
            $context = context_coursecat::instance($catcustomid);
            $itemid = 0; // Initialise itemid, as all files in category description has item id 0.
        } else {
            // $parent = $data['parent'];            
            if ($parent) {
                $DB->record_exists('course_categories', array('id' => $parent), '*', MUST_EXIST);
                $context = context_coursecat::instance($parent);
            } else {
                $context = context_system::instance();
            }
            $category = new stdClass();
            $category->id = 0;
            $category->parent = $parent;
            $itemid = null; // Set this explicitly, so files for parent category should not get loaded in draft area.

        }*/
        // The last param is the ajax submitted data.
        $mform = new local_departments\form\department_form(null, array(), 'post', '', null, true, $data);
        $validateddata = $mform->get_data();
        // print_object($validateddata);exit;
        if ($validateddata) {
            if ($validateddata->id > 0) {
                // *******Without testing parent categoryid mapping departments under another college *******// 
                // $validateddata->parentid = $validateddata->university;
                // $validateddata->fullname = $validateddata->name;
                // $validateddata->shortname = $validateddata->idnumber;
                $validateddata->description = $validateddata->description_editor['text'];
                $costcenterupdate = costcenter_edit_instance($validateddata->id, $validateddata);
                $catid = $DB->get_field('local_costcenter','category',array('id' => $validateddata->id));

                // if($costcenterupdate){       
                //     $record = new stdClass();   
                //     $record->id = $validateddata->id;
                //     $record->idnumber = $validateddata->shortname;
                //     $record->name = $validateddata->fullname;
                //     $record->faculty = $validateddata->faculty;
                //     $record->university = $validateddata->university;
                //     $department->description = $validateddata->description_editor['text'];
                   
                //     $department = $DB->update_record('local_departments', $record);
                // }               
            } else {
                // $validateddata->parentid = $validateddata->university;
                // $validateddata->fullname = $validateddata->name;
                // $validateddata->shortname = $validateddata->idnumber;
                $validateddata->description = $validateddata->description_editor['text'];
                $costcenterinsert = costcenter_insert_instance($validateddata);
            }
        } else {
            // Generate a warning.
            throw new moodle_exception('Error in submission');
        }
        return $category->id;
    }

    /**
     * Returns description of method result value.
     *
     * @return external_description
     * @since Moodle 3.0
     */
    public static function submit_create_department_form_returns() {
        return new external_value(PARAM_INT, 'category id');
    }
  }

    
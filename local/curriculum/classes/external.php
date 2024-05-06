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
 * External curriculums API
 *
 * @package    local_curriculum
 * @category   external
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();
global $PAGE, $OUTPUT;

require_once("$CFG->libdir/externallib.php");
require_once($CFG->dirroot . '/local/curriculum/program.php');
//use \local_curriculum\curriculum_form as curriculum_form;

class local_curriculum_external extends external_api {
    
    public static function submit_curriculum_data_parameters() {
        return new external_function_parameters(
            array(
                'contextid' => new external_value(PARAM_INT, 'The context id', false),
                'jsonformdata' => new external_value(PARAM_RAW, 'Submitted Form Data', false),
            )
        );
    }

    public static function submit_curriculum_data($contextid, $jsonformdata) {
        global $PAGE, $DB, $CFG, $USER;
        require_once($CFG->dirroot . '/local/curriculum/lib.php');
        $params = self::validate_parameters(self::submit_curriculum_data_parameters(),
                                    ['contextid' => $contextid, 'jsonformdata' => $jsonformdata]);
        $context = context_system::instance();
        self::validate_context($context);
        $serialiseddata = json_decode($params['jsonformdata']);
        $data = array();
        parse_str($serialiseddata, $data);
        $warnings = array();
        $curriculum = new stdClass();

        // The last param is the ajax submitted data.
        $mform = new local_curriculum\form\curriculum_form(null, array(), 'post', '', null, true, $data);
        $validateddata = $mform->get_data();
        if ($validateddata) {
            // Do  action.
            $curriculumid = (new program)->manage_curriculum($validateddata);
            if ($curriculumid > 0) {
                $form_status = -2;
                $error = false;
            } else {
                $form_status = -2;
                $error = true;
            }
        } else {
            // Generate a warning.
            throw new moodle_exception('missingcurriculum', 'local_curriculum');
        }
        $return = array(
            'id' => $curriculumid,
            'form_status' => $form_status);
        return true;

    }

    public static function submit_curriculum_data_returns() {
          return new external_value(PARAM_BOOL, 'return');
    }
     public static function delete_curriculum_instance_parameters() {
        return new external_function_parameters(
            array(
                'action' => new external_value(PARAM_ACTION, 'Action of the event', false),
                'id' => new external_value(PARAM_INT, 'ID of the record', 0),
                 'curriculumid' => new external_value(PARAM_INT, 'ID of the record', 0),
                'confirm' => new external_value(PARAM_BOOL, 'Confirm', false),
               // 'curriculumname' => new external_value(PARAM_RAW, 'Action of the event', false),
            )
        );
    }

    public static function delete_curriculum_instance($action, $id, $curriculumid,$confirm) {
        global $DB;

         try {
            $deletesemcourses = (new program)->deletesemonlinecourses('', $id, '');
            $DB->delete_records('local_cc_semester_courses', array('curriculumid' => $id));
            $DB->delete_records('local_program_cc_years', array('curriculumid' => $id));
            $DB->delete_records('local_curriculum_semesters', array('curriculumid' => $id));

            $DB->delete_records('local_cc_course_sessions', array('curriculumid' => $id));

            $DB->delete_records('local_curriculum_users', array('curriculumid' => $id));
            $DB->delete_records('local_curriculum_trainers', array('curriculumid' => $id));
            $DB->delete_records('local_curriculum_trainerfb', array('curriculumid' => $id));

            // delete events in calendar
            $DB->delete_records('event', array('plugin_instance'=>$id, 'plugin'=>'local_curriculum')); // added by sreenivas
            $params = array(
                    'context' => context_system::instance(),
                    'objectid' =>$id
            );

            $event = \local_program\event\curriculum_deleted::create($params);
            $event->add_record_snapshot('local_curriculum', $id);
            $event->trigger();
            $DB->delete_records('local_curriculum', array('id' => $id));
            $return = true;
        } catch (dml_exception $ex) {
            print_error('deleteerror', 'local_curriculum');
            $return = false;
        }
        return $return;
    }
    public static function delete_curriculum_instance_returns() {
        return new external_value(PARAM_BOOL, 'return');
    }
      public static function managecurriculumyears_parameters() {
        return new external_function_parameters(
            array(
                'contextid' => new external_value(PARAM_INT, 'The context id', true, 1),
                'form_status' => new external_value(PARAM_INT, 'Form position', false, 0),
                'jsonformdata' => new external_value(PARAM_RAW, 'Submitted Form Data', false),
            )
        );
    }

    public static function managecurriculumyears($contextid, $form_status, $jsonformdata) {
        global $PAGE, $DB, $CFG, $USER;
        require_once($CFG->dirroot.'/local/curriculum/lib.php');
        $context = context::instance_by_id($contextid, MUST_EXIST);
        self::validate_context($context);
        $serialiseddata = json_decode($jsonformdata);

        $data = array();
        parse_str($serialiseddata, $data);
        $warnings = array();
        $curriculum = new stdClass();

        // The last param is the ajax submitted data.
        $mform = new curriculum_manageyear_form(null, array('id' => $data['id'],
            'curriculumid' => $data['curriculumid'], /*'programid' => $data['programid'],*/
            'form_status' => $form_status), 'post', '', null, true, $data);
        $validateddata = $mform->get_data();

        if ($validateddata) {
            // Do the action.
            $program = $DB->get_field('local_curriculum', 'program',
                array('id' => $validateddata->curriculumid));
            $action = 'create';
            if ($validateddata->id > 0) {
                $action = 'update';
            }
            $sessionid = (new program)->manage_program_curriculum_years($validateddata);
            if ($sessionid > 0) {
                $form_status = -2;
                $error = false;
            } else {
                $error = true;
            }
        } else {
            // Generate a warning.
            throw new moodle_exception('missingcurriculum', 'local_curriculum');
        }
        $return = array(
            'id' => $sessionid,
            'form_status' => $form_status);
        return $return;
    }

    public static function managecurriculumyears_returns() {
        return new external_single_structure(array(
            'id' => new external_value(PARAM_INT, 'Context id for the framework'),
            'form_status' => new external_value(PARAM_INT, 'form_status'),
        ));
    }
    public static function managecurriculumsemesters_parameters() {
        return new external_function_parameters(
            array(
                'contextid' => new external_value(PARAM_INT, 'The context id', true, 1),
                'form_status' => new external_value(PARAM_INT, 'Form position', false, 0),
                'jsonformdata' => new external_value(PARAM_RAW, 'Submitted Form Data', false),
            )
        );
    }

    public static function managecurriculumsemesters($contextid, $form_status, $jsonformdata) {
        global $PAGE, $DB, $CFG, $USER;
        require_once($CFG->dirroot.'/local/curriculum/classes/form/program_managesemester_form.php');
        $context = context::instance_by_id($contextid, MUST_EXIST);
        self::validate_context($context);
        $serialiseddata = json_decode($jsonformdata);
        $data = array();
        parse_str($serialiseddata, $data);

        $warnings = array();
        $curriculum = new stdClass();

        // The last param is the ajax submitted data.
        $mform = new program_managesemester_form(null, array('id' => $data['id'],
            'programid' => $data['programid'], 'curriculumid' => $data['curriculumid'], 'yearid' => $data['yearid'],
            'form_status' => $form_status), 'post', '', null, true, $data);
        $validateddata = $mform->get_data();
        if ($validateddata) {
            // Do the action.
            $program = $DB->get_field('local_curriculum', 'program',
                array('id' => $validateddata->curriculumid));
            $action = 'create';
            if ($validateddata->id > 0) {
                $action = 'update';
            }
            $sessionid = (new program)->manage_curriculum_program_semesters($validateddata);
            if ($sessionid > 0) {
                $form_status = -2;
                $error = false;
            } else {
                $error = true;
            }
        } else {
            // Generate a warning.
            throw new moodle_exception('missingcurriculum', 'local_curriculum');
        }
        $return = array(
            'id' => $sessionid,
            'form_status' => $form_status);
        return $return;
    }

    public static function managecurriculumsemesters_returns() {
        return new external_single_structure(array(
            'id' => new external_value(PARAM_INT, 'Context id for the framework'),
            'form_status' => new external_value(PARAM_INT, 'form_status'),
        ));
    }

    public static function curriculum_course_instance_parameters() {
        return new external_function_parameters(
            array(
                'id' => new external_value(PARAM_INT, 'ID', 0),
                'contextid' => new external_value(PARAM_INT, 'The context id', false),
                'form_status' => new external_value(PARAM_INT, 'Form position', 0),
                'jsonformdata' => new external_value(PARAM_RAW, 'Submitted Form Data', false),
            )
        );
    }

    public static function curriculum_course_instance($id, $contextid, $form_status, $jsonformdata) {
        global $PAGE, $DB, $CFG, $USER;
        require_once($CFG->dirroot.'/local/curriculum/classes/form/programcourse_form.php');
        $context = context::instance_by_id($contextid, MUST_EXIST);
        self::validate_context($context);
        $serialiseddata = json_decode($jsonformdata);
        $data = array();
        parse_str($serialiseddata, $data);

        // The last param is the ajax submitted data.
        $mform = new programcourses_form(null, array('programid' => $data['programid'], 'curriculumid' => $data['curriculumid'],
            'semesterid' => $data['semesterid'], 'yearid' => $data['yearid'], 'form_status' => $form_status),
            'post', '', null, true, $data);
        $validateddata = $mform->get_data();
       // print_object($validateddata);
        if ($validateddata) {
            // Do the action.
            $sessionid = (new program)->manage_curriculum_courses($validateddata);
            if ($sessionid > 0) {
                $form_status = -2;
                $error = false;
            } else {
                $error = true;
            }
        } else {
            // Generate a warning.
            throw new moodle_exception('missingcurriculum', 'local_curriculum');
        }
        $return = array(
            'id' => $sessionid,
            'form_status' => $form_status);
        return $return;
    }

    public static function curriculum_course_instance_returns() {
        return new external_single_structure(array(
            'id' => new external_value(PARAM_INT, 'Context id for the framework'),
            'form_status' => new external_value(PARAM_INT, 'form_status'),
        ));
    }
     public static function curriculum_unassign_course_parameters(){
        return new external_function_parameters(
            array(
              //  'programid' => new external_value(PARAM_INT, 'ID of the curriculum'),
                'curriculumid' => new external_value(PARAM_INT, 'ID of the curriculum'),
                'yearid' => new external_value(PARAM_INT, 'ID of the curriculum'),
                'semesterid' => new external_value(PARAM_INT, 'ID of the curriculum semester'),
                'courseid' => new external_value(PARAM_INT, 'ID of the curriculum semester course to be unassigned')
            )
        );
    }
    public static function curriculum_unassign_course($curriculumid, $yearid, $semesterid, $courseid){
        global $CFG;
        if ($curriculumid > 0 && $yearid > 0 && $semesterid > 0 && $courseid > 0) {
            $program = new program();

            $program->unassign_courses_from_semester($curriculumid, $yearid, $semesterid, $courseid);
            return true;
        } else {
            throw new moodle_exception('Error in unassigning of course');
            return false;
        }
    }
    public static function curriculum_unassign_course_returns(){
        return new external_value(PARAM_BOOL, 'return');
    }
     public static function addstudent_submit_data_parameters() {
        return new external_function_parameters(
            array(
                'id' => new external_value(PARAM_INT, 'ID', 0),
                'contextid' => new external_value(PARAM_INT, 'The context id', false),
                'form_status' => new external_value(PARAM_INT, 'Form position', 0),
                'jsonformdata' => new external_value(PARAM_RAW, 'Submitted Form Data', false),
            )
        );
    }

    public static function addstudent_submit_data($id, $contextid, $form_status, $jsonformdata) {
        global $PAGE, $DB, $CFG, $USER;
        require_once($CFG->dirroot.'/local/curriculum/classes/form/managestudent_form.php');

        $context = context::instance_by_id($contextid, MUST_EXIST);
        self::validate_context($context);
        $serialiseddata = json_decode($jsonformdata);
        $data = array();
        parse_str($serialiseddata, $data);

        $warnings = array();
        $curriculum = new stdClass();

        // The last param is the ajax submitted data.
        $mform = new managestudent_form(null, array('curriculumid' => $data['curriculumid'],'yearid' => $data['yearid'], 'programid' => $data['programid'], 'form_status' => $form_status),
            'post', '', null, true, $data);
        $validateddata = $mform->get_data();
        if ($validateddata) {
            // Do the action.
            $managefaculty = (new program)->addstudent($validateddata);
            if ($managefaculty > 0) {
                $form_status = -2;
                $error = false;
            } else {
                $error = true;
            }
        } else {
            // Generate a warning.
            throw new moodle_exception('missingfaculty', 'local_curriculum');
        }
        $return = array(
            'id' => $managefaculty,
            'form_status' => $form_status);
        return $return;
    }

    public static function addstudent_submit_data_returns() {
        return new external_single_structure(array(
            'id' => new external_value(PARAM_INT, 'Context id for the framework'),
            'form_status' => new external_value(PARAM_INT, 'form_status'),
        ));
    }
       public static function addfaculty_submit_data_parameters() {
        return new external_function_parameters(
            array(
                'id' => new external_value(PARAM_INT, 'ID', 0),
                'contextid' => new external_value(PARAM_INT, 'The context id', false),
                'form_status' => new external_value(PARAM_INT, 'Form position', 0),
                'jsonformdata' => new external_value(PARAM_RAW, 'Submitted Form Data', false),
            )
        );
    }

    public static function addfaculty_submit_data($id, $contextid, $form_status, $jsonformdata) {
        global $PAGE, $DB, $CFG, $USER;
        require_once($CFG->dirroot."/local/curriculum/classes/form/managefaculty_form.php");

        $context = context::instance_by_id($contextid, MUST_EXIST);
        self::validate_context($context);
        $serialiseddata = json_decode($jsonformdata);
        $data = array();
        parse_str($serialiseddata, $data);

        $warnings = array();
        $curriculum = new stdClass();
        // The last param is the ajax submitted data.
        $mform = new managefaculties_form(null, array('programid' => $data['programid'], 'curriculumid' => $data['curriculumid'],'yearid' => $data['yearid'], 'semesterid' => $data['semesterid'], 'courseid' => $data['courseid'], 'form_status' => $form_status),
            'post', '', null, true, $data);
        $validateddata = $mform->get_data();
        if ($validateddata) {
            // Do the action.
            $managefaculty = (new program)->addfaculty($validateddata);
            if ($managefaculty > 0) {
                $form_status = -2;
                $error = false;
            } else {
                $error = true;
            }
        } else {
            // Generate a warning.
            throw new moodle_exception('missingfaculty', 'local_curriculum');
        }
        $return = array(
            'id' => $managefaculty,
            'form_status' => $form_status);
        return $return;
    }

    public static function addfaculty_submit_data_returns() {
        return new external_single_structure(array(
            'id' => new external_value(PARAM_INT, 'Context id for the framework'),
            'form_status' => new external_value(PARAM_INT, 'form_status'),
        ));
    }
      public static function unassignfaculty_parameters() {
        return new external_function_parameters(
            array(
                'action' => new external_value(PARAM_ACTION, 'Action of the event', false),
                'programid' => new external_value(PARAM_INT, 'ID of the record', 0),
                'curriculumid' => new external_value(PARAM_INT, 'ID of the record', 0),
                'yearid' => new external_value(PARAM_INT, 'ID of the record', 0),
                'semesterid' => new external_value(PARAM_INT, 'ID of the record', 0),
                'courseid' => new external_value(PARAM_INT, 'ID of the record', 0),
                'trainerid' => new external_value(PARAM_INT, 'ID of the record', 0),
                'confirm' => new external_value(PARAM_BOOL, 'Confirm', false),
            )
        );
    }

    public static function unassignfaculty($action, $programid, $curriculumid, $yearid, $semesterid, $courseid, $trainerid, $confirm) {
        global $DB,$USER;
        try {
            $facultydata = new stdClass();
            $facultydata->yearid = $yearid;
            $facultydata->semesterid = $semesterid;
            $facultydata->courseid = $courseid;
            $facultydata->trainerid = $trainerid;
            (new program)->unassignfaculty($facultydata);
            $return = true;
        } catch (dml_exception $ex) {
            print_error('deleteerror', 'local_program');
            $return = false;
        }
        return $return;
    }

    public static function unassignfaculty_returns() {
        return new external_value(PARAM_BOOL, 'return');
    }

    public static function delete_semester_data_parameters() {
        return new external_function_parameters(
            array(
                'action' => new external_value(PARAM_ACTION, 'Action of the event', false),
                'id' => new external_value(PARAM_INT, 'ID of the record', 0),
                'curriculumid' => new external_value(PARAM_INT, 'ID of the record', 0),
                'yearid' => new external_value(PARAM_INT, 'ID of the record', 0),
                'confirm' => new external_value(PARAM_BOOL, 'Confirm', false),
            )
        );
    }

    public static function delete_semester_data($action, $id, $curriculumid, $yearid, $confirm) {
        global $DB,$USER;
        try {
            $deletesemcourses = (new program)->deletesemonlinecourses($id, $curriculumid, $yearid);
            $DB->delete_records('local_cc_semester_courses', array('semesterid' => $id));
            $params = array(
                    'context' => context_system::instance(),
                    'objectid' => $id
            );

            $event = \local_program\event\semester_deleted::create($params);
            $event->add_record_snapshot('local_curriculum_semesters', $id);
            $event->trigger();

            $DB->delete_records('local_curriculum_semesters', array('id' => $id));
            $return = true;
        } catch (dml_exception $ex) {
            print_error('deleteerror', 'local_curriculum');
            $return = false;
        }
        return $return;
    }

    public static function delete_semester_data_returns() {
        return new external_value(PARAM_BOOL, 'return');
    }
     public static function delete_semesteryear_data_parameters() {
        return new external_function_parameters(
            array(
                'action' => new external_value(PARAM_ACTION, 'Action of the event', false),
                'id' => new external_value(PARAM_INT, 'ID of the record', 0),
                'curriculumid' => new external_value(PARAM_INT, 'ID of the record', 0),
               // 'programid' => new external_value(PARAM_INT, 'ID of the record', 0),
                'confirm' => new external_value(PARAM_BOOL, 'Confirm', false),
            )
        );
    }

    public static function delete_semesteryear_data($action, $id, $curriculumid,  $confirm) {
        global $DB,$USER;
        try {
            $deletesemcourses = (new program)->deletesemonlinecourses('', $curriculumid, $id);
            $DB->delete_records('local_cc_semester_courses', array('yearid' => $id));
            $DB->delete_records('local_curriculum_semesters', array('yearid' => $id));
            $DB->delete_records('local_cc_session_signups', array('yearid' => $id));
           


            $params = array(
                    'context' => context_system::instance(),
                    'objectid' => $id
            );

            $event = \local_program\event\year_deleted::create($params);
            $event->add_record_snapshot('local_program_cc_years', $id);
            $event->trigger();

            $yearrecord = $DB->get_record('local_program_cc_years', array('id' => $id));
            $DB->delete_records('local_program_cc_years', array('id' => $id));
            $years = $DB->get_records('local_program_cc_years',array('curriculumid' => $yearrecord->curriculumid));
            $sequence = 1;
            foreach ($years as $key => $year) {
                $yearobject = '';
                $yearobject->id = $year->id;
                $yearobject->sequence = $sequence;
                $DB->update_record('local_program_cc_years',$yearobject);
                $sequence++;
            }

            $yearscount = $DB->count_records_sql('SELECT count(id) as id FROM {local_program_cc_years} WHERE curriculumid = '.$curriculumid);
                               
            $DB->execute('UPDATE {local_curriculum} SET duration ='. $yearscount.' WHERE id = '.$curriculumid);
            $return = true;
        } catch (dml_exception $ex) {
            print_error('deleteerror', 'local_curriculum');
            $return = false;
        }
        return $return;
    }

    public static function delete_semesteryear_data_returns() {
        return new external_value(PARAM_BOOL, 'return');
    }
   
    public static function program_course_selector_parameters() {
        $query = new external_value(
            PARAM_RAW,
            'Query string'
        );
        $includes = new external_value(
            PARAM_ALPHA,
            'What other contexts to fetch the frameworks from. (all, parents, self)',
            VALUE_DEFAULT,
            'parents'
        );
        return new external_function_parameters(array(
            'query' => $query,
            'context' => self::get_context_parameters(),
            'includes' => $includes
        ));
    }

    public static function program_course_selector($query, $context, $includes = 'parents') {
        global $CFG, $DB, $USER;
        $params = self::validate_parameters(self::program_course_selector_parameters(), array(
            'query' => $query,
            'context' => $context,
            'includes' => $includes
        ));
        $query = $params['query'];
        $includes = $params['includes'];
        $context = self::get_context_from_params($params['context']);

        self::validate_context($context);
        $courses = array();
        if ($query) {
            $queryparams = array();
            $concatsql = '';
            if ((has_capability('local/program:manageprogram', context_system::instance())) && ( !is_siteadmin() && (!has_capability('local/curriculum:manage_multiorganizations', context_system::instance()) && !has_capability('local/costcenter:manage_multiorganizations', context_system::instance())))) {
                $concatsql .= " AND open_costcenterid = :costcenterid";
                $queryparams['costcenterid'] = $USER->open_costcenterid;
                if ((has_capability('local/curriculum:manage_owndepartments', context_system::instance())|| has_capability('local/costcenter:manage_owndepartments', context_system::instance()))) {
                     $concatsql .= " AND open_departmentid = :department";
                     $queryparams['department'] = $USER->open_departmentid;
                 }
           }
            $cousresql = "SELECT c.id, c.fullname
                           FROM {course} AS c
                           JOIN {enrol} AS en on en.courseid = c.id AND en.enrol = 'curriculum' and en.status = 0
                          WHERE c.visible = 1 AND FIND_IN_SET(5, c.open_identifiedas) AND c.fullname LIKE '%$query%' AND c.id <> " . SITEID . " $concatsql";
            $courses = $DB->get_records_sql($cousresql, $queryparams);
        }

        return array('courses' => $courses);
    }
    public static function program_course_selector_returns() {
        return new external_single_structure(array(
            'courses' => new external_multiple_structure(
                new external_single_structure(array(
                    'id' => new external_value(PARAM_INT, 'ID of the course'),
                    'fullname' => new external_value(PARAM_RAW, 'course fullname'),
                ))
            ),
        ));
    }

    public static function program_form_option_selector_parameters() {
        $query = new external_value(
            PARAM_RAW,
            'Query string'
        );
        $action = new external_value(
            PARAM_RAW,
            'Action for the curriculum form selector'
        );
        $options = new external_value(
            PARAM_RAW,
            'Action for the curriculum form selector'
        );

        return new external_function_parameters(array(
            'query' => $query,
            'context' => self::get_context_parameters(),
            'action' => $action,
            'options' => $options
        ));
    }

    public static function program_form_option_selector($query, $context, $action, $options) {
        global $CFG, $DB, $USER;
        $params = self::validate_parameters(self::program_form_option_selector_parameters(), array(
            'query' => $query,
            'context' => $context,
            'action' => $action,
            'options' => $options
        ));
        $query = $params['query'];
        $action = $params['action'];
        $context = self::get_context_from_params($params['context']);
        $options = $params['options'];
        if (!empty($options)) {
            $formoptions = json_decode($options);
        }

        self::validate_context($context);
        if ($query && $action) {
            $querieslib = new \local_curriculum\local\querylib();
            $return = array();

            switch($action) {
               
                case 'program_costcenter_selector':
                // OL-1042 Add Target Audience to curriculums//
                    if ($formoptions->id > 0 && !isset($formoptions->parnetid)) {
                        $parentid = $DB->get_field('local_curriculum', 'costcenter', array('id' => $formoptions->id));
                    } else{
                         $parentid = $formoptions->parnetid;
                    }
                // OL-1042 Add Target Audience to curriculums//
                    $depth = $formoptions->depth;
                    $params = array();
                    $costcntersql = "SELECT id, fullname
                                        FROM {local_costcenter}
                                        WHERE visible = 1 ";
                    if ($parentid >= 0) {
                        $costcntersql .= " AND parentid = :parentid ";
                        $params['parentid'] = $parentid;
                    }
                    if ($depth > 0) {
                        $costcntersql .= " AND depth = :depth ";
                        $params['depth'] = $depth;
                    }
                    if (!empty($query)) {
                        $costcntersql .= " AND fullname LIKE :query ";
                        $params['query'] = '%' . $query . '%';
                    }

                    $return = $DB->get_records_sql($costcntersql, $params);
                    $return = (object)((array)$return + array('0' => (object)array('id' => -1,
                        'fullname' => get_string('all')) ));
                break;
               
                case 'program_course_selector':
                    $courses = array();

                    if ($query) {
                        $queryparams = array();
                        $concatsql = '';
                        // if ((has_capability('local/program:manageprogram', context_system::instance())) && ( !is_siteadmin() && (!has_capability('local/program:manage_multiorganizations', context_system::instance()) && !has_capability('local/costcenter:manage_multiorganizations', context_system::instance())))) {
                        //     $concatsql .= " AND open_costcenterid = :costcenterid";
                        //     $queryparams['costcenterid'] = $USER->open_costcenterid;
                        //     if ((has_capability('local/program:manage_owndepartments', context_system::instance())|| has_capability('local/costcenter:manage_owndepartments', context_system::instance()))) {
                        //         $concatsql .= " AND open_departmentid = :department";
                        //         $queryparams['department'] = $USER->open_departmentid;
                        //     }
                        // }
                        if($formoptions->department){
                            $categoryid = implode(',', $formoptions->department);
                            $concatsql .= " AND c.category IN ($categoryid) ";
                        }
                        // $queryparams['department'] = $formoptions->department;
                        $existedcourses = array();
                        if ($formoptions->programid && $formoptions->curriculumid) {

                            $existedcoursessql = "SELECT id, open_parentcourseid
                                                FROM {local_cc_semester_courses}
                                               WHERE programid = :programid AND curriculumid = :curriculumid";
                            $existedcourses = $DB->get_records_sql_menu($existedcoursessql, array('programid' => $formoptions->programid, 'curriculumid' => $formoptions->curriculumid));
                        }
                        if (!empty($existedcourses)) {
                            $existedcourseslist = implode(',', $existedcourses);
                            $concatsql .= " AND c.id NOT IN ($existedcourseslist) ";
                        }
                        $cousresql = "SELECT c.id, c.fullname
                                       FROM {course} c
                                       JOIN {enrol} e ON e.courseid = c.id
                                      WHERE c.visible = 1 AND c.open_parentcourseid = 0 AND e.enrol = 'program' AND e.status = 0 AND c.fullname LIKE '%$query%' AND c.id <> " . SITEID . $concatsql;
                        $courses = $DB->get_records_sql($cousresql, $queryparams);
                    }
                      //print_object($courses);exit;
                    $return = $courses;
                break;
                 case 'program_course_selectors':
                    $courses = array();
                    
                    if ($query) {
                        $queryparams = array();
                        $concatsql = '';
                        // if ((has_capability('local/program:manageprogram', context_system::instance())) && ( !is_siteadmin() && (!has_capability('local/program:manage_multiorganizations', context_system::instance()) && !has_capability('local/costcenter:manage_multiorganizations', context_system::instance())))) {
                        //     $concatsql .= " AND open_costcenterid = :costcenterid";
                        //     $queryparams['costcenterid'] = $USER->open_costcenterid;
                        //     if ((has_capability('local/program:manage_owndepartments', context_system::instance())|| has_capability('local/costcenter:manage_owndepartments', context_system::instance()))) {
                        //         $concatsql .= " AND open_departmentid = :department";
                        //         $queryparams['department'] = $USER->open_departmentid;
                        //     }
                        // }
                        if($formoptions->department){
                            $categoryids = implode(',', $formoptions->department);

                            $catids = $DB->get_records_sql_menu("SELECT id,category FROM {local_costcenter} WHERE id IN ($categoryids)");
                            $categoryid = implode(',', $catids);
                            $concatsql = " AND c.category IN ($categoryid)";
                        }
                        // $queryparams['department'] = $formoptions->department;
                        $existedcourses = array();
                        
                       
                        $cousresql = "SELECT c.id, c.fullname
                                       FROM {course} c                                      
                                      WHERE c.visible = 1 AND open_parentcourseid = 0 AND c.fullname LIKE '%$query%' AND c.id <> " . SITEID . $concatsql;
                        $displayedcourses = $DB->get_records_sql($cousresql);
                        $cids = array();
                        $parentcourseids = array();
                        //--Changes by Yamini to filter the courses--//
                        foreach($displayedcourses as $key => $value){
                            
                          $courseids = $key;
                          $childids = $DB->get_record_sql('SELECT cc.courseid FROM {local_cc_semester_courses} cc JOIN {course} c ON c.id = cc.courseid WHERE c.open_parentcourseid ='.$courseids.' AND curriculumid = '.$formoptions->curriculumid.' AND semesterid ='.$formoptions->semesterid.' AND yearid ='.$formoptions->yearid);
                          if($childids){
                            $parentcourseids[] = $courseids;
                          //  $parentcourseids[] = 
                          }
                          $coursenames = $value;
                          $cids[] = $courseids;
         
                        }
                        if(!empty($cids)){

                           foreach($cids as $key => $courseid){
                               $cid = $courseid;
                           }
                        }
                       
                      /* if($displayedcourses){

                        $dept_courses = $DB->get_record_sql('SELECT id,courseid FROM {local_cc_semester_courses} WHERE curriculumid = '.$formoptions->curriculumid.' AND semesterid ='.$formoptions->semesterid.' AND yearid ='.$formoptions->yearid.' AND courseid = '.$cids[0]);

                       }
                       */
                      $course_sql =  "ORDER BY c.id DESC"; 
                       if($parentcourseids){
                        $pcids = implode(',', $parentcourseids);
                       $sql = " AND c.id NOT IN (".$pcids.")";
                         $courses = $DB->get_records_sql($cousresql.$sql.$course_sql);
                        } 
                       else{ 
                       $courses = $DB->get_records_sql($cousresql.$course_sql);
                         }
                    }
                    
                    $return = $courses;
                break;
                case 'program_course_faculty_selector':
                    $courses = array();
                    if ($query) {
                        $queryparams = array();
                        $concatsql = " AND (CONCAT(u.firstname, ' ', u.lastname)  LIKE '%" . $query . "%')";
                        if ($formoptions->programid) {
                            $schoolssql = "SELECT cc.id, cc.parentid
                                             FROM {local_costcenter} cc
                                             JOIN {local_program} p ON p.costcenter = cc.id
                                            WHERE p.id = :programid ";
                            $school = $DB->get_record_sql($schoolssql, array('programid' => $formoptions->programid ));
                            //print_object($school);
                            if ($school->parentid > 0) {
                                $concatsql .= " AND u.open_departmentid = :schoolid ";
                            } else {
                                $concatsql .= " AND u.open_costcenterid = :schoolid AND (u.open_departmentid IS NULL OR u.open_departmentid = 0 )";
                            }

                            $queryparams['schoolid'] = $school->id;
                        }
                        if ($formoptions->courseid && $formoptions->semesterid && $formoptions->yearid ) {
                            $concatsql .= " AND u.id NOT IN (SELECT trainerid FROM {local_cc_session_trainers} WHERE courseid = :courseid AND semesterid = :semesterid AND yearid = :yearid ) ";
                            $queryparams['courseid'] = $formoptions->courseid;
                            $queryparams['semesterid'] = $formoptions->semesterid;
                            $queryparams['yearid'] = $formoptions->yearid;
                        }
                       $userssql = "SELECT u.id, CONCAT(u.firstname, ' ', u.lastname) AS fullname
                                   FROM {user} AS u
                                   JOIN {role_assignments} ra ON ra.userid = u.id
                                   JOIN {role} r ON r.id = ra.roleid
                                   WHERE u.confirmed = 1 AND u.deleted = 0 AND u.suspended = 0 AND u.id > 2 AND r.shortname='faculty' AND ra.contextid = 1 AND u.open_employee = 1 " . $concatsql;
                      
                        $users = $DB->get_records_sql($userssql, $queryparams);

                    }

                    $return = $users;
                break;
                case 'program_course_student_selector':
                    $courses = array();
                    if ($query) {
                        $queryparams = array();

                        $concatsql = " AND (CONCAT(u.firstname, ' ', u.lastname)  LIKE '%" . $query . "%')";


                        if ($formoptions->programid) {
                            $schoolssql = "SELECT cc.id, cc.parentid
                                             FROM {local_costcenter} cc
                                             JOIN {local_program} p ON p.costcenter = cc.id
                                            WHERE p.id = :programid ";
                            $school = $DB->get_record_sql($schoolssql, array('programid' => $formoptions->programid ));
                            if ($school->parentid > 0) {
                                $concatsql .= " AND u.open_departmentid = :schoolid ";
                            } else {
                                $concatsql .= " AND u.open_costcenterid = :schoolid AND (u.open_departmentid IS NULL OR u.open_departmentid = 0) ";
                            }
                            $queryparams['schoolid'] = $school->id;
                        }


                        if ($formoptions->programid && $formoptions->curriculumid && $formoptions->yearid) {
                            $recordexists = $DB->record_exists('local_cc_session_signups', array('programid' => $formoptions->programid, 'curriculumid' => $formoptions->curriculumid, 'yearid' => $formoptions->yearid));
                            if ($recordexists) {
                                $concatsql .= " AND u.id NOT IN (SELECT userid FROM {local_cc_session_signups} WHERE programid = :programid AND curriculumid = :curriculumid AND yearid = :yearid ) ";
                                $queryparams['programid'] = $formoptions->programid;
                                $queryparams['curriculumid'] = $formoptions->curriculumid;
                                $queryparams['yearid'] = $formoptions->yearid;
                            }
                        }

                        $userssql = "SELECT u.id, CONCAT(u.firstname, ' ', u.lastname) AS fullname
                                       FROM {user} AS u
                                      WHERE u.confirmed = 1 AND u.deleted = 0 AND u.suspended = 0 AND u.id > 2 AND u.open_employee = 2 " . $concatsql;
                        $users = $DB->get_records_sql($userssql, $queryparams);
                    }

                    $return = $users;
                break;
            }
            return json_encode($return);
        }
    }
    public static function program_form_option_selector_returns() {
        return new external_value(PARAM_RAW, 'data');
    }
}

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
 * Manage curriculum Session form
 *
 * @package    local_curriculum
 * @copyright  2018 Arun Kumar M <arun@eabyas.in>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_program\form;
defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');
use \local_program\program as program;
use moodleform;
use local_program\local\querylib;
use context_system;

class program_completion_form extends moodleform {

    public function definition() {
        global $CFG, $DB, $USER;
        $querieslib = new querylib();
        $context = context_system::instance();
        $mform = &$this->_form;
        $ccid = $this->_customdata['ccid'];
        $sid = $this->_customdata['id'];

        $mform->addElement('hidden', 'id', $sid);
        $mform->setType('id', PARAM_INT);

        $mform->addElement('hidden', 'curriculumid', $ccid);
        $mform->setType('curriculumid', PARAM_INT);

        $session_tracking = array(
                            NULL => get_string('curriculum_donotsessioncompletion', 'local_program'),
                            'AND' => get_string('curriculum_allsessionscompletion', 'local_program'),
                            'OR' => get_string('curriculum_anysessioncompletion', 'local_program')
                            );

        $mform->addElement('select', 'sessiontracking',
            get_string('sessiontracking', 'local_program'), $session_tracking, array());

        $sessions = array();
        $sessions = $this->_ajaxformdata['sessionids'];
        if (!empty($sessions)) {
            $sessions = $sessions;
        } else if ($id > 0) {
            $sessions = $DB->get_records_menu('local_curriculum_completion',
                array('id' => $id), 'id', 'id, sessionids');
        }
        if (!empty($sessions)) {
                if (is_array($sessions)){
                    $sessions = implode(',', $sessions);
                 }

                $sessions_sql = "SELECT id, name AS fullname
                                   FROM {local_cc_course_sessions}
                                  WHERE curriculumid = $ccid AND id IN ($sessions)";
                $sessions = $DB->get_records_sql_menu($sessions_sql);
        } else if (empty($sessions)) {
            $sessions_sql = "SELECT id, name AS fullname
                               FROM {local_cc_course_sessions}
                              WHERE curriculumid = $ccid ";
            $sessions = $DB->get_records_sql_menu($sessions_sql);
        }
        $options = array(
            'ajax' => 'local_program/form-options-selector',
            'multiple' => true,
            'data-contextid' => $context->id,
            'data-action' => 'program_completions_sessions_selector',
            'data-options' => json_encode(array('id' => $id, 'curriculumid' => $ccid)),
        );

        $mform->addElement('autocomplete', 'sessionids',
                    get_string('session_completion', 'local_program'), $sessions,$options);

        $course_tracking = array(
                                NULL=>get_string('curriculum_donotcoursecompletion','local_program'),
                                'AND'=>get_string('curriculum_allcoursescompletion', 'local_program'),
                                'OR'=>get_string('curriculum_anycoursecompletion', 'local_program')
                            );

        $mform->addElement('select', 'coursetracking', get_string('coursetracking', 'local_program'), $course_tracking, array());

        $courses = array();
        $courses = $this->_ajaxformdata['courseids'];
        if (!empty($courses)) {
            $courses = $courses;
        } else if ($id > 0) {
            $courses = $DB->get_records_menu('local_curriculum_completion',
                array('id' => $id), 'id', 'id, courseids');
        }
        if (!empty($courses)) {
                if (is_array($courses)) {
                        $courses = implode(',', $courses);
                }
                $courses_sql = "SELECT c.id,c.fullname as fullname FROM {course} as c JOIN {local_cc_semester_courses} as lcc on lcc.courseid=c.id where lcc.curriculumid= $ccid and lcc.courseid in ($courses)";
                $courses = $DB->get_records_sql_menu($courses_sql);
        }else if (empty($courses)) {
            $courses_sql = "SELECT c.id,c.fullname as fullname FROM {course} as c JOIN {local_cc_semester_courses} as lcc on lcc.courseid=c.id where lcc.curriculumid= $ccid ";
            $courses = $DB->get_records_sql_menu($courses_sql);
        }

        $options = array(
            'ajax' => 'local_program/form-options-selector',
            'multiple' => true,
            'data-contextid' => $context->id,
            'data-action' => 'program_completions_courses_selector',
            'data-options' => json_encode(array('id' => $id,'curriculumid'=>$ccid)),
        );

        $mform->addElement('autocomplete', 'courseids',
                    get_string('course_completion', 'local_program'), $courses,$options);

        $mform->disable_form_change_checker();
    }

    public function validation($data, $files) {
        global $CFG, $DB, $USER;
        $errors = parent::validation($data, $files);
        if (isset($data['sessiontracking']) && $data['sessiontracking'] == "OR" && isset($data['sessionids']) && empty($data['sessionids'])) {
            $errors['sessionids'] = get_string('select_sessions', 'local_program');
        }
        if (isset($data['coursetracking']) && $data['coursetracking'] == "OR" && isset($data['courseids']) && empty($data['courseids'])) {
            $errors['courseids'] = get_string('select_courses', 'local_program');
        }
        return $errors;
    }
}
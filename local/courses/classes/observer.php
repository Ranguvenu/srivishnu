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

defined('MOODLE_INTERNAL') || die();
/**
 * Event observer for local_courses. Dont let other user to view unauthorized courses
 */
class local_courses_observer extends \core\event\course_viewed {
    /**
     * Triggered via course_viewed event.
     *
     * @param \core\event\course_viewed $event
     */
    public static function course_viewed(\core\event\course_viewed $event) {
        global $DB, $CFG, $USER, $COURSE;
        $systemcontext = context_system::instance();
        if (!(is_siteadmin() OR has_capability('local/costcenter:manage_multiorganizations',$systemcontext))) {
            $enroltypeslist  = $DB->get_records_sql_menu("select id, id as enrolid from {enrol} where courseid = $COURSE->id AND status = 0");
			$enroltypes = implode(',', $enroltypeslist);
			$exist = $DB->get_record_sql("select id  from {user_enrolments} where userid = $USER->id AND status = 0 AND enrolid IN ($enroltypes)");
            if (!$exist) {
                $user_costcenter = $DB->get_field('user', 'open_costcenterid', array('id'=>$USER->id));
                $course_costcenter = $DB->get_field('course', 'open_costcenterid', array('id'=>$COURSE->id));
                if ($user_costcenter != $course_costcenter) {
                    redirect($CFG->wwwroot.'/local/courses/courses.php');
                    die;
                }
            }
            
        }
    }
    /**
    * Event observer for local_courses. Dont let other user to view moodle deafult course categories
    */
    /**
     * Triggered via course_category_viewed event.
     *
     * @param \core\event\course_category_viewed $event
     */
    public static function course_category_viewed(\core\event\course_category_viewed $event) {
        global $CFG;
        if (!is_siteadmin() ) {
            redirect($CFG->wwwroot.'/local/courses/index.php');
            die;            
        }
    }
}


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
 * Local stuff for category enrolment plugin.
 *
 * @package    core_badges
 * @copyright  2013 Rajesh Taneja <rajesh@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
use local_program\program;
use local_program\programnotifications_emails;

/**
 * Event observer for badges.
 */
class local_program_observer {

    /**
     * Triggered when 'course_completed' event is triggered.
     *
     * @param \core\event\course_completed $event
     */
    public static function program_complete(\core\event\course_completed $event) {
        global $DB, $CFG, $USER;

        $eventdata = $event->get_record_snapshot('course_completions', $event->objectid);
        $userid = $event->relateduserid;
        $courseid = $event->courseid;

        $userdata = new stdClass();
        $userdata->userid = $userid;
        $userdata->courseid = $courseid;
        $coursedata = $DB->get_record('local_cc_semester_courses', array('courseid' => $courseid));
        $userdata->curriculumid = $coursedata->curriculumid;
        $userdata->semesterid = $coursedata->semesterid;
        $userdata->programid = $coursedata->programid;
        $notifications_exists = $DB->record_exists('local_notification_type', array('shortname' => 'program_course_completion'));
        if($notifications_exists){
            $emaillogs = new programnotifications_emails();
            $email_logs = $emaillogs->curriculum_emaillogs('program_course_completion', $userdata, $userdata->userid, $USER->id);
        }
        (new program)->bc_semester_courses_completions($userdata);
    }
}

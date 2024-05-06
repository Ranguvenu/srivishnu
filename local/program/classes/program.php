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
 * curriculum View
 *
 * @package    local_curriculum
 * @copyright  2018 Arun Kumar M <arun@eabyas.in>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace local_program;
defined('MOODLE_INTERNAL') || die();
use context_system;
use stdClass;
use moodle_url;
use completion_completion;
use html_table;
use html_writer;
use core_component;
use \local_courses\action\insert as insert;
require_once($CFG->dirroot . '/local/program/lib.php');
if (file_exists($CFG->dirroot . '/local/lib.php')) {
  require_once($CFG->dirroot . '/local/lib.php');
}
use \local_curriculum\notifications_emails as curriculumnotifications_emails;
// curriculum
define('curriculum_NEW', 0);
define('curriculum_COMPLETED', 2);
// Session Attendance
define('SESSION_PRESENT', 1);
define('SESSION_ABSENT', 2);
// Types
define('curriculum', 1);

class program {
    /**
     * Manage curriculum (Create or Update the curriculum)
     * @method manage_curriculum
     * @param  Object           $data Clasroom Data
     * @return Integer               curriculum ID
     */
    public function manage_curriculum($curriculum,$copy=false) {
        global $DB, $USER;
        $curriculum->shortname = $curriculum->name;
        if (empty($curriculum->trainers)) {
            $curriculum->trainers = null;
        }
        if (empty($curriculum->capacity) || $curriculum->capacity == 0) {
            $curriculum->capacity = 0;
        }
        if (is_array($curriculum->department)) {
            $curriculum->department = !empty($curriculum->department) ? implode(',', $curriculum->department) : -1;
        } else {
             $curriculum->department = !empty($curriculum->department) ? $curriculum->department : -1;
        }
        $curriculum->startdate = 0;
        $curriculum->enddate = 0;
        $curriculum->description = $curriculum->cr_description['text'];
        $curriculumdepar = $curriculum->department;
        //print_r($curriculumdepar);
        try {
            if ($curriculum->id > 0) {
                $curriculum->timemodified = time();
                $curriculum->usermodified = $USER->id;

                $DB->update_record('local_curriculum', $curriculum);
                // $this->curriculum_set_events($curriculum); // Added by sreenivas.
                $params = array(
                    'context' => context_system::instance(),
                    'objectid' => $curriculum->id
                );
                // Trigger curriculum updated event.

                $event = \local_program\event\curriculum_updated::create($params);
                $event->add_record_snapshot('local_curriculum', $curriculum);
                $event->trigger();
            } else {
                $curriculum->status = 0;
                $curriculum->timecreated = time();
                $curriculum->usercreated = $USER->id;
                if (has_capability('local/program:manageprogram', context_system::instance())) {
                    $curriculum->department = -1;
                    if ((has_capability('local/program:manage_owndepartments', context_system::instance())
                       || has_capability('local/costcenter:manage_owndepartments', context_system::instance()))) {
                        $curriculum->department = $USER->open_departmentid;
                    }
                }
                if(empty($curriculum->department)){
                  $curriculum->department = $curriculumdepar;
                }
                $curriculum->id = $DB->insert_record('local_curriculum', $curriculum);

                $params = array(
                    'context' => context_system::instance(),
                    'objectid' => $curriculum->id
                );

                $event = \local_program\event\curriculum_created::create($params);
                $event->add_record_snapshot('local_curriculum', $curriculum);
                $event->trigger();

                $curriculum->shortname = 'curriculum' . $curriculum->id;
                $DB->update_record('local_curriculum', $curriculum);
                if ($curriculum->id && $copy==false) {
                    // $semesterdata = new stdClass();
                    // $semesterdata->curriculumid = $curriculum->id;
                    // $this->manage_curriculum_program_semesters($semesterdata, true);

                    $semesteryearsdata = new stdClass();
                    $semesteryearsdata->programid = $curriculum->program;
                    $semesteryearsdata->curriculumid = $curriculum->id;
                    $this->manage_program_curriculum_years($semesteryearsdata, true);

                }
            }
            $curriculum->totalsemesters = $DB->count_records('local_curriculum_semesters', array('curriculumid' => $curriculum->id));
            $DB->update_record('local_curriculum', $curriculum);
        } catch (dml_exception $ex) {
            print_error($ex);
        }
        return $curriculum->id;
    }

    /**
    * This creates new events given as timeopen and closeopen by curriculum.
    *
    * @global object
    * @param object $curriculum
    * @return void
    */
   function curriculum_set_events($curriculum) {
        global $DB, $CFG, $USER;
        // Include calendar/lib.php.
        require_once($CFG->dirroot . '/calendar/lib.php');

        // evaluation start calendar events.
        $eventid = $DB->get_field('event', 'id',
               array('modulename' => '0', 'instance' => 0, 'plugin'=> 'local_curriculum',
                'plugin_instance' => $curriculum->id, 'eventtype' => 'open',
                'local_eventtype' => 'open'));

        if (isset($curriculum->startdate) && $curriculum->startdate > 0) {
           $event = new stdClass();
           $event->eventtype    = 'open';
           $event->type         = empty($curriculum->enddate) ? CALENDAR_EVENT_TYPE_ACTION : CALENDAR_EVENT_TYPE_STANDARD;
           $event->name         = $curriculum->name;
           $event->description  = $curriculum->name;
           $event->timestart    = $curriculum->startdate;
           $event->timesort     = $curriculum->startdate;
           $event->visible      = 1;
           $event->timeduration = 0;
           $event->plugin_instance = $curriculum->id;
           $event->plugin = 'local_program';
           $event->local_eventtype    = 'open';
           $event->relateduserid    = $USER->id;
           if ($eventid) {
               // Calendar event exists so update it.
               $event->id = $eventid;
               $calendarevent = \calendar_event::load($event->id);
               $calendarevent->update($event);
           } else {
               // Event doesn't exist so create one.
               $event->courseid     = 0;
               $event->groupid      = 0;
               $event->userid       = 0;
               $event->modulename   = 0;
               $event->instance     = 0;
               $event->eventtype    = 'open';;
               \calendar_event::create($event);
           }
       } else if ($eventid) {
           // Calendar event is on longer needed.
           $calendarevent = \calendar_event::load($eventid);
           $calendarevent->delete();
       }

       // evaluation close calendar events.
       $eventid = $DB->get_field('event', 'id',
               array('modulename' => '0', 'instance' => 0, 'plugin' => 'local_curriculum',
                'plugin_instance' => $curriculum->id, 'eventtype' => 'close',
                'local_eventtype' => 'close'));

       if (isset($curriculum->enddate) && $curriculum->enddate > 0) {
           $event = new stdClass();
           $event->type         = CALENDAR_EVENT_TYPE_ACTION;
           $event->eventtype    = 'close';
           $event->name         = $curriculum->name;
           $event->description  = $curriculum->name;
           $event->timestart    = $curriculum->enddate;
           $event->timesort     = $curriculum->enddate;
           $event->visible      = 1;
           $event->timeduration = 0;
           $event->plugin_instance = $curriculum->id;
           $event->plugin = 'local_program';
           $event->local_eventtype    = 'close';
           $event->relateduserid    = $USER->id;
           if ($eventid) {
               // Calendar event exists so update it.
               $event->id = $eventid;
               $calendarevent = \calendar_event::load($event->id);
               $calendarevent->update($event);
           } else {
               // Event doesn't exist so create one.
               $event->courseid     = 0;
               $event->groupid      = 0;
               $event->userid       = 0;
               $event->modulename   = 0;
               $event->instance     = 0;
               \calendar_event::create($event);
           }
       } else if ($eventid) {
           // Calendar event is on longer needed.
           $calendarevent = \calendar_event::load($eventid);
           $calendarevent->delete();
       }
    }
    /**
     * Manage curriculum Sessions (Create / Update)
     * @method session_management
     * @param  Object             $data Session Data
     * @return Integer                  Session ID
     */
    public function manage_bc_courses_sessions($session) {
        global $DB, $USER;
    
        $session->description = $session->cs_description['text'];        
        try {
            if($classroomdata->attendancemapped == null || $classroomdata->attendancemapped == 0){
                $dailysttime_timestamp = $session->dailysessionstarttime['dailystarttimehours']*60*60 + $session->dailysessionstarttime['dailystarttimemins']*60;
                $dailyendtime_timestamp = $session->dailysessionendtime['dailyendtimehours']*60*60 + $session->dailysessionendtime['dailyendtimemins']*60;
                $timestart = $session->timestart+$dailysttime_timestamp;
                $timefinish = $session->timefinish+$dailyendtime_timestamp;

                $sessions_validation_start = $this->sessions_validation($session,$timestart,$session->id);
                $session->duration = ($timefinish - $timestart)/60;
            }
            /*print_object($sessions_validation_start);
            print_object($session);exit;*/
            // $session->duration=($session->timefinish - $session->timestart)/60;
            /*if($sessions_validation_start){
                return true;
            }

            $sessions_validation_end=$this->sessions_validation($session->classroomid,$session->timefinish,$session->id);
            if($sessions_validation_end){
                return true;
            }*/
            // if ($sessions_validation_start) {
            //     return true;
            // }
            // $sessions_validation_end = $this->sessions_validation($session->curriculumid,
            //     $session->timefinish, $session->id);
            // if ($sessions_validation_end) {
            //     return true;
            // }
            if ($session->id > 0) {
                $session->oldtrainerid = $DB->get_field('local_cc_course_sessions',
                    'trainerid', array('id' => $session->id));
                $session->timemodified = time();
                $session->usermodified = $USER->id;
                if($session->ccses_action == 'coursesessions'){
                    $this->manage_curriculum_session_trainers($session, 'all');
                }

                $session->trainerid = $session->trainerid;
                $session->instituteid = $session->instituteid;
                $session->institute_type = $session->institute_type;
                $session->roomid = $session->room;
                //$session->capacity = $session->maxcapacity;
                // $session->maxcapacity = $session->maxcapacity;
                // $session->mincapacity = $session->mincapacity;
                $session->timestart = $timestart;
                $session->timefinish = $timefinish;
                if($session->dailysessionstarttime['dailystarttimehours']){
                    $sessionsttimehours = $session->dailysessionstarttime['dailystarttimehours'];
                }else{
                    $sessionsttimehours = '00';
                }
                if($session->dailysessionstarttime['dailystarttimemins']){
                    $sessionsttimemins = $session->dailysessionstarttime['dailystarttimemins'];
                }else{
                    $sessionsttimemins = '00';
                }
                if($session->dailysessionendtime['dailyendtimehours']){
                    $sessionendtimehours = $session->dailysessionendtime['dailyendtimehours'];
                }else{
                    $sessionendtimehours = '00';
                }
                if($session->dailysessionendtime['dailyendtimemins']){
                    $sessionendtimemins = $session->dailysessionendtime['dailyendtimemins'];
                }else{
                    $sessionendtimemins = '00';
                }
                $session->dailysessionstarttime = $sessionsttimehours.':'.$sessionsttimemins;
                $session->dailysessionendtime = $sessionendtimehours.':'.$sessionendtimemins;
                $DB->update_record('local_cc_course_sessions', $session);
                $this->session_set_events($session); //added by sreenivas
                $params = array(
                            'context' => context_system::instance(),
                            'objectid' => $session->id,
                            'other' => array('curriculumid' => $session->curriculumid,
                                             'semesterid' => $session->semesterid,
                                             'bclcid' => $session->bclcid)
                     );

                $event = \local_program\event\bcsemestercourse_session_updated::create($params);
                $event->add_record_snapshot('local_cc_course_sessions', $session);
                $event->trigger();
                if ($session->onlinesession == 1) {
                        $online_sessions_integration = new \local_program\event\online_sessions_integration();
                        $online_sessions_integration->online_sessions_type($session, $session->id,
                            $type = 1,'update');
                }
            } else {
                $session->timecreated = time();
                $session->usercreated = $USER->id;
                if($session->ccses_action == 'semsessions'){
                    $session->sessiontype = 2;
                }elseif($session->ccses_action == 'class_sessions'){
                    $session->sessiontype = 1;
                }else{
                    $session->sessiontype = 0;
                }
                $session->trainerid = $session->trainerid;
                $session->instituteid = $session->instituteid;
                $session->roomid = $session->room;
                // $session->capacity = $session->maxcapacity;
                // $session->maxcapacity = $session->maxcapacity;
                // $session->mincapacity = $session->mincapacity;
                $session->timestart = $timestart;
                $session->timefinish = $timefinish;
                if($session->dailysessionstarttime['dailystarttimehours']){
                    $sessionsttimehours = $session->dailysessionstarttime['dailystarttimehours'];
                }else{
                    $sessionsttimehours = '00';
                }
                if($session->dailysessionstarttime['dailystarttimemins']){
                    $sessionsttimemins = $session->dailysessionstarttime['dailystarttimemins'];
                }else{
                    $sessionsttimemins = '00';
                }
                if($session->dailysessionendtime['dailyendtimehours']){
                    $sessionendtimehours = $session->dailysessionendtime['dailyendtimehours'];
                }else{
                    $sessionendtimehours = '00';
                }
                if($session->dailysessionendtime['dailyendtimemins']){
                    $sessionendtimemins = $session->dailysessionendtime['dailyendtimemins'];
                }else{
                    $sessionendtimemins = '00';
                }
                $session->dailysessionstarttime = $sessionsttimehours.':'.$sessionsttimemins;
                $session->dailysessionendtime = $sessionendtimehours.':'.$sessionendtimemins;
                $session->id = $DB->insert_record('local_cc_course_sessions', $session);
                $userstoassign = $this->userenrolments_tosessions($session, $session->id);
                if($session->ccses_action == 'coursesessions'){
                    $this->manage_curriculum_session_trainers($session, 'insert');
                }
                $this->session_set_events($session); // added by sreenivas
                $params = array(
                            'context' => context_system::instance(),
                            'objectid' => $session->id,
                            'other' => array('curriculumid' => $session->curriculumid,
                                             'semesterid' => $session->semesterid,
                                             'bclcid' => $session->bclcid)
                     );

                $event = \local_program\event\bcsemestercourse_session_created::create($params);
                $event->add_record_snapshot('local_cc_course_sessions', $session);
                $event->trigger();
                if ($session->id) {
                    if ($session->onlinesession == 1) {
                        $online_sessions_integration = new \local_program\event\online_sessions_integration();
                        $online_sessions_integration->online_sessions_type($session,
                            $session->id, $type = 1, 'create');
                    }
                }
            }
            $bccourse = new stdClass();
            /*$bccourse->totalsessions = $DB->count_records('local_cc_course_sessions',
                    array('curriculumid' => $session->curriculumid, 'semesterid' => $session->semesterid,
                        'bclcid' => $session->bclcid));*/
            if($session->ccses_action == 'class_sessions'){
                $bccourse->id = $session->bclcid;
                $bccourse->totalsessions = $DB->count_records('local_cc_course_sessions',
                    array('curriculumid' => $session->curriculumid, 'semesterid' => $session->semesterid,
                        'bclcid' => $session->bclcid, 'courseid' => 0, 'sessiontype' => 1));
                $DB->update_record('local_cc_semester_classrooms', $bccourse);
            }else{
                $bccourse->id = $session->bclcid;
                $bccourse->totalsessions = $DB->count_records('local_cc_course_sessions',
                    array('curriculumid' => $session->curriculumid, 'semesterid' => $session->semesterid,
                        'bclcid' => $session->bclcid, 'sessiontype' => 0));
                $DB->update_record('local_cc_semester_courses', $bccourse);
            }
            $semester = new stdClass();
            $semester->id = $session->semesterid;
            $semester->totalsessions = $DB->count_records('local_cc_course_sessions',
                    array('curriculumid' => $session->curriculumid, 'semesterid' => $session->semesterid));
            $DB->update_record('local_curriculum_semesters', $semester);
            $curriculum = new stdClass();
            $curriculum->id = $session->curriculumid;
            $curriculum->totalsessions = $DB->count_records('local_cc_course_sessions',
                        array('curriculumid' => $session->curriculumid));
            $DB->update_record('local_curriculum', $curriculum);
        } catch (dml_exception $ex) {
            print_error($ex);
        }
        return $session->id;
    }

    /**
    * This creates new events given as timeopen and timeclose by session.
    *
    * @global object
    * @param object session
    * @return void
    */

    public function userenrolments_tosessions($enroldata, $sessionid){
        global $DB;
        $curriculumid = $enroldata->curriculumid;
        $programid = $enroldata->programid;
        $yearid = $enroldata->yearid;
        $semesterid = $enroldata->semesterid;
        $bclcid = $enroldata->bclcid;
        $ccses_action = $enroldata->ccses_action;
        /*$curriculumuserssql = "SELECT u.id, u.id as userid 
                              FROM {user} AS u
                              JOIN {local_curriculum_users} as bcu
                             WHERE  u.id > 2 AND bcu.curriculumid = $curriculumid AND u.id = bcu.userid AND u.suspended = :suspended
                                     AND u.deleted = :deleted";*/
        $curriculumuserssql = "SELECT u.id, u.id as userid 
                                 FROM {user} AS u
                                 JOIN {local_ccuser_year_signups} as yearsignup ON yearsignup.userid = u.id
                                WHERE  u.id > 2 AND yearsignup.curriculumid = $curriculumid 
                                  AND yearsignup.programid = $programid 
                                  AND yearsignup.yearid = $yearid 
                                  AND u.suspended = :suspended
                                  AND u.deleted = :deleted";// ODL-403 issue fixing
        $params = array('deleted' => 0, 'suspended' => 0);
        $curriculumusers = $DB->get_records_sql_menu($curriculumuserssql, $params);
        $enrolusers = $this->session_add_assignusers($curriculumid, $programid, $yearid, $semesterid, $bclcid, $sessionid, $ccses_action, $curriculumusers);
        return $enrolusers;
    }
    public function session_set_events($session) {
        global $DB, $CFG, $USER;
        // Include calendar/lib.php.
        require_once($CFG->dirroot.'/calendar/lib.php');

        // session start calendar events.
        $eventid = $DB->get_field('event', 'id',
               array('modulename' => '0', 'instance' => 0, 'plugin' => 'local_curriculum',
                'plugin_instance' => $session->curriculumid, 'plugin_itemid' => $session->id,
                'eventtype' => 'open', 'local_eventtype' => 'session_open'));

        if (isset($session->timestart) && $session->timestart > 0) {
            $event = new stdClass();
            $event->eventtype    = 'open';
            $event->type         = empty($session->timefinish) ? CALENDAR_EVENT_TYPE_ACTION : CALENDAR_EVENT_TYPE_STANDARD;
            $event->name         = $session->name;
            $event->description  = $session->name;
            $event->timestart    = $session->timestart;
            $event->timesort     = $session->timestart;
            $event->visible      = 1;
            $event->timeduration = 0;
            $event->plugin_instance = $session->curriculumid;
            $event->plugin_itemid = $session->id;
            $event->plugin = 'local_curriculum';
            $event->local_eventtype = 'session_open';
            $event->relateduserid = $USER->id;
            if ($eventid) {
               // Calendar event exists so update it.
               $event->id = $eventid;
               $calendarevent = \calendar_event::load($event->id);
               $calendarevent->update($event);
            } else {
               // Event doesn't exist so create one.
               $event->courseid     = 0;
               $event->groupid      = 0;
               $event->userid       = 0;
               $event->modulename   = 0;
               $event->instance     = 0;
               $event->eventtype    = 'open';
               \calendar_event::create($event);
            }
        } else if ($eventid) {
            // Calendar event is on longer needed.
            $calendarevent = \calendar_event::load($eventid);
            $calendarevent->delete();
        }

        // session close calendar events.
        $eventid = $DB->get_field('event', 'id',
               array('modulename' => '0', 'instance' => 0, 'plugin' => 'local_curriculum',
                'plugin_instance' => $session->curriculumid, 'plugin_itemid' => $session->id,
                'eventtype' => 'close', 'local_eventtype' => 'session_close'));

        if (isset($session->timefinish) && $session->timefinish > 0) {
            $event = new stdClass();
            $event->type         = CALENDAR_EVENT_TYPE_ACTION;
            $event->eventtype    = 'close';
            $event->name         = $session->name;
            $event->description  = $session->name;
            $event->timestart    = $session->timefinish;
            $event->timesort     = $session->timefinish;
            $event->visible      = 1;
            $event->timeduration = 0;
            $event->plugin_instance = $session->curriculumid;
            $event->plugin_itemid = $session->id;
            $event->plugin = 'local_curriculum';
            $event->local_eventtype = 'session_close';
            $event->relateduserid = $USER->id;
            if ($eventid) {
               // Calendar event exists so update it.
               $event->id = $eventid;
               $calendarevent = \calendar_event::load($event->id);
               $calendarevent->update($event);
            } else {
                // Event doesn't exist so create one.
                $event->courseid     = 0;
                $event->groupid      = 0;
                $event->userid       = 0;
                $event->modulename   = 0;
                $event->instance     = 0;
                \calendar_event::create($event);
            }
        } else if ($eventid) {
            // Calendar event is on longer needed.
            $calendarevent = \calendar_event::load($eventid);
            $calendarevent->delete();
        }
    }

    public function manage_curriculum_semester_completions($curriculumid, $semesterid, $yearid = 0) {
        global $DB, $USER;

        $courses = $DB->get_records_menu('local_cc_semester_courses',
            array('curriculumid' => $curriculumid, 'semesterid' => $semesterid), '', 'id, courseid');
        $bclcomptlcheck = $DB->record_exists('local_ccs_cmplt_criteria',
            array('curriculumid' => $curriculumid, 'semesterid' => $semesterid));
        if ($bclcomptlcheck) {
            $completions = $DB->get_record('local_ccs_cmplt_criteria',
                array('curriculumid' => $curriculumid, 'semesterid' => $semesterid));
        } else {
            $completions = new stdClass();
            $completions->curriculumid = $curriculumid;
            $completions->semesterid = $semesterid;
        }
        $completions->sessionids = null;

        if (!empty($courses) && is_array($courses)) {
            $completions->courseids = implode(', ', array_values($courses));

        } else {
            $completions->courseids = null;
        }

        $completions->sessiontracking = null;
        $completions->coursetracking = 'AND';
        try {
            if ($completions->id > 0) {
                $completions->timemodified = time();
                $completions->usermodified = $USER->id;
                $DB->update_record('local_ccs_cmplt_criteria', $completions);
                $params = array(
                    'context' => context_system::instance(),
                    'objectid' => $completions->id,
                    'other' => array('curriculumid' => $completions->curriculumid,
                                             'semesterid' => $completions->semesterid)
                );
                $event = \local_program\event\program_completions_settings_updated::create($params);
                $event->add_record_snapshot('local_ccs_cmplt_criteria', $completions->curriculumid);
                $event->trigger();
            } else {
                $completions->timecreated = time();
                $completions->usercreated = $USER->id;
                $completions->yearid = $yearid;

                $completions->id = $DB->insert_record('local_ccs_cmplt_criteria', $completions);
                $params = array(
                    'context' => context_system::instance(),
                    'objectid' => $completions->id,
                    'other' => array('curriculumid' => $completions->curriculumid,
                                             'semesterid' => $completions->semesterid)
                );
                $event = \local_program\event\program_completions_settings_created::create($params);
                $event->add_record_snapshot('local_ccs_cmplt_criteria', $completions);
                $event->trigger();
            }
        } catch (dml_exception $ex) {
            print_error($ex);
        }
        return $completions->id;
    }
    /**
     * [curriculum_sessions_delete description]
     * @param  [type] $curriculumid [description]
     * @return [type]              [description]
     */
    public function curriculum_sessions_delete($curriculumid){
        global $DB, $USER;
        $curriculum_sessions = $DB->get_records_sql_menu("SELECT *
                                                FROM {local_cc_course_sessions}
                                                where curriculumid = $curriculumid");
         foreach ($curriculum_sessions as $curriculum_session) {
            $id = $curriculum_session->id;
            $DB->delete_records('local_curriculum_attendance', array('sessionid' => $id));
            $params = array(
                'context' => context_system::instance(),
                'objectid' => $id,
                'other' => array('curriculumid' => $curriculum_session->curriculumid,
                                 'semesterid' => $curriculum_session->semesterid,
                                 'bclcid' => $curriculum_session->bclcid)
            );
            $event = \local_program\event\bcsemestercourse_session_deleted::create($params);
            $event->add_record_snapshot('local_cc_course_sessions', $curriculum_session);
            $event->trigger();

            $DB->delete_records('local_cc_course_sessions', array('id' => $id));

            $curriculum = new stdClass();
            $curriculum->id = $curriculumid;
            $curriculum->totalsessions = $DB->count_records('local_cc_course_sessions',
                array('curriculumid' => $curriculumid));
            $curriculum->activesessions = $DB->count_records('local_cc_course_sessions',
                array('curriculumid' => $curriculumid, 'attendance_status' => 1));
            $DB->update_record('local_curriculum', $curriculum);
         }
        $curriculum_users=$DB->get_records_menu('local_curriculum_users',
                                        array('curriculumid' =>$curriculumid), 'id', 'id, userid');

        foreach ($curriculum_users as $curriculum_user) {
            $attendedsessions = $DB->count_records('local_curriculum_attendance',
                        array('curriculumid' => $curriculumid,
                            'userid' => $curriculum_user, 'status' => SESSION_PRESENT));

            $attendedsessions_hours=$DB->get_field_sql("SELECT ((sum(lcs.duration))/60) AS hours
                                                FROM {local_cc_course_sessions} AS lcs
                                                WHERE  lcs.curriculumid =$curriculumid
                                                AND lcs.id IN (SELECT sessionid  FROM {local_curriculum_attendance} where curriculumid =
                                                $curriculumid AND userid = $curriculum_user AND status=1)");

            if (empty($attendedsessions_hours)) {
                $attendedsessions_hours = 0;
            }

            $DB->execute('UPDATE {local_curriculum_users} SET attended_sessions = ' .
                        $attendedsessions . ',hours = ' .
                        $attendedsessions_hours . ', timemodified = ' . time() . ',
                        usermodified = ' . $USER->id . ' WHERE curriculumid = ' .
                        $curriculumid . ' AND userid = ' . $curriculum_user);
        }
    }
    /**
     * Update curriculum Location and Date
     * @method location_date
     * @param  Object        $data curriculum Location and Nomination Data
     * @return Integer        curriculum ID
     */
    public function location_date($data) {
        global $DB, $USER;
        $location = new stdClass();
        $location->institute_type = $data->institute_type;
        $location->instituteid = $data->instituteid;
        $location->nomination_startdate = $data->nomination_startdate;
        $location->nomination_enddate = $data->nomination_enddate;
        try {
            $local_curriculum = $DB->get_record_sql("SELECT id,instituteid FROM {local_curriculum} where id=$data->id");
            if (isset($location->instituteid) && ($location->instituteid != $local_curriculum->instituteid) && ($local_curriculum->instituteid != 0)) {
                $DB->execute('UPDATE {local_cc_course_sessions} SET roomid = 0,
                    timemodified = ' . time() . ',
                   usermodified = ' . $USER->id . ' WHERE curriculumid = ' .
                   $data->id. '');
            }
            $location->id = $data->id;
            $location->timemodified = time();
            $location->usermodified = $USER->id;
            $DB->update_record('local_curriculum', $location);
        } catch (dml_exception $ex) {
            print_error($ex);
        }
        return $data->id;
    }
    /**
     * curriculums
     * @method curriculums
     * @param  Object     $stable Datatable fields
     * @return Array  curriculums and totalcurriculumcount
     */
    public function curriculums($stable, $request = false) {
        global $DB, $USER;
        $params = array();
        $curriculums = array();
        $curriculumscount = 0;
        $concatsql = '';
        if (!empty($stable->search)) {
            $fields = array("bc.name");
            $fields = implode(" LIKE :search1 OR ", $fields);
            $fields .= " LIKE :search2 ";
            $params['search1'] = '%' . $stable->search . '%';
            $params['search2'] = '%' . $stable->search . '%';
            $concatsql .= " AND ($fields) ";
        }
        if (!is_siteadmin()&&!has_capability('local/program:manage_multiorganizations', context_system::instance())&&(has_capability('local/program:manageprogram', context_system::instance())) &&
            ((has_capability('local/program:manage_ownorganization', context_system::instance())))) {
                    /*$colleges=$DB->get_field_sql("SELECT GROUP_CONCAT(id) FROM {local_costcenter} where parentid=:parentid",  array('parentid'=>$USER->open_costcenterid),  $strictness=IGNORE_MISSING);

                    $condition = " AND (ps.costcenter = :costcenter OR ps.costcenter = :parentcostcenter)";
                    $params['parentcostcenter'] = $colleges;*/// Commented by Harish//
                    $params['costcenter'] = $USER->open_costcenterid;
                    /*$colleges = $DB->get_records_sql_menu("SELECT id,id as cid FROM {local_costcenter} where parentid=:parentid",  array('parentid'=>$USER->open_costcenterid),  $strictness=IGNORE_MISSING);

                    list($relatedprogramyearysql, $relatedprogramyearparams) = $DB->get_in_or_equal($colleges, SQL_PARAMS_NAMED);

                    $condition.= " AND ( ps.costcenter $relatedprogramyearysql)";

                    $params = $params+$relatedprogramyearparams;

                $concatsql .= $condition;*/
        } else if (!is_siteadmin()&&!has_capability('local/program:manage_multiorganizations', context_system::instance())&&(has_capability('local/program:manage_owndepartments', context_system::instance()))) {

//                $condition .= " AND (ps.costcenter = :department )";
//                $params['department'] = $USER->open_departmentid;
                $condition .= " AND (ps.costcenter = :costcenter)";
                $params['costcenter'] = $USER->open_costcenterid;
                $concatsql .= $condition;

        } else if (!is_siteadmin() && !has_capability('local/program:manage_multiorganizations', context_system::instance())&&has_capability('local/program:trainer_viewcurriculum', context_system::instance())) {
                $mycurriculums = $DB->get_records_menu('local_cc_session_trainers',
                    array('trainerid' => $USER->id), 'programid', 'programid, programid as ps');

                $trainerclroomprograms = $DB->get_records_menu('local_cc_course_sessions',
                    array('trainerid' => $USER->id), 'programid', 'programid, programid as ps');
                if (!empty($mycurriculums)) {
                    if(!empty($trainerclroomprograms)){
                        $mycurriculums = array_merge($mycurriculums, $trainerclroomprograms);  
                    }
                    $mycurriculums = implode(',', array_unique($mycurriculums));
                    $concatsql .= " AND ps.id IN ( $mycurriculums )";
                } else {
                    return compact('curriculums', 'curriculumscount');
                }
        } else if (!is_siteadmin() && (!has_capability('local/program:manage_multiorganizations', context_system::instance()) && !has_capability('local/costcenter:manage_multiorganizations', context_system::instance()))) {

              $mycurriculums = $DB->get_records_menu('local_ccuser_year_signups',
                  array('userid' => $USER->id), 'programid', 'programid, programid as ps');

                  if (!empty($mycurriculums)) {
                      $mycurriculums = implode(', ', $mycurriculums);
                      $concatsql .= " AND ps.id IN ( $mycurriculums ) ";
                  } else {
                      return compact('curriculums', 'curriculumscount');
                  }

          }

        // if ((has_capability('local/program:managecurriculum', context_system::instance())) &&
        //     (!is_siteadmin() && (!has_capability('local/program:manage_multiorganizations', context_system::instance()) && !has_capability('local/costcenter:manage_multiorganizations', context_system::instance())))) {

        //         $colleges=$DB->get_field_sql("SELECT GROUP_CONCAT(id) FROM `mdl_local_costcenter` where parentid=:parentid",  array('parentid'=>$USER->open_costcenterid),  $strictness=IGNORE_MISSING);

        //         $condition = " AND (cc.id = :costcenter OR cc.id = :parentcostcenter)";
        //         $params['costcenter'] = $USER->open_costcenterid;
        //         $params['parentcostcenter'] =$colleges;

        //     if ((has_capability('local/program:manage_owndepartments', context_system::instance())
        //             || has_capability('local/costcenter:manage_owndepartments', context_system::instance()))) {
        //         $condition .= " AND (bc.department = :department )";
        //         $params['department'] = $USER->open_departmentid;
        //     }
        //     $concatsql .= $condition;
        //     if (has_capability('local/program:trainer_viewcurriculum', context_system::instance())) {
        //         $mycurriculums = $DB->get_records_menu('local_cc_course_sessions',
        //             array('trainerid' => $USER->id), 'id', 'id, curriculumid');
        //         if (!empty($mycurriculums)) {
        //             $mycurriculums = implode(', ', $mycurriculums);
        //             $concatsql .= " AND bc.id IN ( $mycurriculums )";
        //         } else {
        //             return compact('curriculums', 'curriculumscount');
        //         }
        //     }
        // } else if (!is_siteadmin() && (!has_capability('local/program:manage_multiorganizations', context_system::instance()) && !has_capability('local/costcenter:manage_multiorganizations', context_system::instance()))) {

        //     $mycurriculums = $DB->get_records_menu('local_curriculum_users',
        //         array('userid' => $USER->id), 'id', 'id, curriculumid');

        //     if (isset($stable->curriculumid) && !empty($stable->curriculumid)) {
        //         $userenrolstatus = $DB->record_exists('local_curriculum_users',
        //             array('curriculumid' => $stable->curriculumid, 'userid' => $USER->id));
        //         $status = $DB->get_field('local_curriculum', 'status',
        //             array('id' => $stable->curriculumid));
        //          $curriculum_costcenter = $DB->get_field('local_curriculum', 'costcenter',
        //             array('id' => $stable->curriculumid));
        //         if ($status == 1 && !$userenrolstatus &&
        //             $curriculum_costcenter == $USER->open_costcenterid) {
        //         } else {
        //             if (!empty($mycurriculums)) {
        //                 $mycurriculums = implode(', ', $mycurriculums);
        //                 $concatsql .= " AND bc.id IN ( $mycurriculums )";
        //             } else {
        //                 return compact('curriculums', 'curriculumscount');
        //             }
        //         }
        //     } else {
        //         if (!empty($mycurriculums)) {
        //             $mycurriculums = implode(', ', $mycurriculums);
        //             $concatsql .= " AND bc.id IN ( $mycurriculums ) ";
        //         } else {
        //             return compact('curriculums', 'curriculumscount');
        //         }
        //     }
        // }
        if (isset($stable->curriculumid) && $stable->curriculumid > 0) {
            $concatsql .= " AND bc.id = :curriculumid";
            $params['curriculumid'] = $stable->curriculumid;
            //$params['programid'] = $stable->programid;
            
        }

        if (isset($stable->programid) && $stable->programid > 0) {
            $concatsql .= " AND ps.id = :programid";
            $params['programid'] = $stable->programid;
        }

        $countsql = "SELECT COUNT(bc.id) ";
        if ($request == true) {
            $fromsql = "SELECT group_concat(bc.id) AS curriculumids";
        } else {
            $fromsql = "SELECT bc.*, ps.parentid, ps.description, ps.admissionenddate, (SELECT COUNT(DISTINCT cu.userid)
                                  FROM {local_curriculum_users} AS cu
                                  WHERE cu.curriculumid = bc.id
                              ) AS enrolled_users, (SELECT COUNT(DISTINCT bu.userid)
                                  FROM {local_curriculum_users} AS bu
                                  WHERE bu.curriculumid = bc.id AND bu.completion_status = 1 AND bu.completiondate > 0
                              ) AS completed_users";
        }
        // if ((has_capability('local/program:managecurriculum', context_system::instance())) &&
        //     (!is_siteadmin() && (!has_capability('local/program:manage_multiorganizations', context_system::instance()) && !has_capability('local/costcenter:manage_multiorganizations', context_system::instance())))) {
        //         $joinon = "cc.id = bc.costcenter";
        //     if ((has_capability('local/program:manage_owndepartments', context_system::instance()) || has_capability('local/costcenter:manage_owndepartments', context_system::instance()))) {
        //         $joinon = "cc.id = bc.department OR cc.id = bc.costcenter";
        //     }
        // } else {
        //     $joinon = "cc.id = bc.costcenter";
        // }
        $sql = " FROM {local_curriculum} AS bc
                 JOIN {local_program} ps ON ps.curriculumid = bc.id
                 JOIN {local_costcenter} AS cc ON cc.id=ps.costcenter
                WHERE 1 = 1 ";
        $sql .= $concatsql;
       // print_r($stable);
        if (isset($stable->curriculumid) && $stable->curriculumid > 0) {
          // echo "test1";
          // print_r($params);
          // echo $fromsql . $sql;
            $curriculums = $DB->get_record_sql($fromsql . $sql, $params);
        } else {
          //echo "test2";
            try {
                $curriculumscount = $DB->count_records_sql($countsql . $sql, $params);
                if ($stable->thead == false) {
                    $sql .= " ORDER BY bc.id DESC";
                    if ($request == true) {
                        $curriculums = $DB->get_record_sql($fromsql . $sql, $params, $stable->start, $stable->length);
                    } else {
                        $curriculums = $DB->get_records_sql($fromsql . $sql, $params, $stable->start, $stable->length);
                    }
                }
            } catch (dml_exception $ex) {
                $curriculumscount = 0;
            }
        }
         
        // exit;
        if (isset($stable->curriculumid) && $stable->curriculumid > 0) {
         // echo "hlo";
          //print_r($curriculums);
            return $curriculums;
        } else {
            return compact('curriculums', 'curriculumscount');
        }
    }
    /**
     * curriculum sessions
     * @method sessions
     * @param  Integer   $bclcid bclcid ID
     * @param  Object   $stable      Datatable fields
     * @return Array    Sessions and total session count for the perticular curriculum
     */
    public function curriculumsessions($bclcdata, $stable, $userview = false, $tab=null) {
        global $DB, $USER;
        $systemcontext = context_system::instance();
        $curriculumid = $bclcdata->curriculumid;
        $semesterid = $bclcdata->semesterid;
        $bclcid = $bclcdata->bclcid;
        if($bclcdata->ccses_action == 'semsessions'){
            $bccourse = $DB->get_record('local_cc_semester_courses', array('semesterid' => $semesterid, 'curriculumid' => $curriculumid, 'yearid' => $bclcdata->yearid));
        }elseif ($bclcdata->ccses_action == 'class_sessions') {
            $bccourse = $DB->get_record('local_cc_semester_classrooms', array('id' => $bclcid, 'semesterid' => $semesterid, 'curriculumid' => $curriculumid, 'yearid' => $bclcdata->yearid));
        }else{
            $bccourse = $DB->get_record('local_cc_semester_courses', array('id' => $bclcid));
        }
        if (empty($bccourse)) {
            print_error('curriculum data missing');
        }
        $concatsql = '';
        if (!empty($stable->search)) {
            $fields = array (0 => 'bcs.name',
                            1 => 'lr.name'
                        );
            $fields = implode(" LIKE '%" . $stable->search . "%' OR ", $fields);
            $fields .= " LIKE '%" .$stable->search. "%' ";
            $concatsql .= " AND ($fields) ";
        }
        $params = array();
        $curriculums = array();

        $countsql = "SELECT COUNT(bcs.id) ";
        $fromsql = "SELECT bcs.*, lr.name as room";
        if ($userview) {
            /*$fromsql .= ", bss.id as signupid, bss.completion_status,
                    (SELECT COUNT(id) FROM {local_cc_session_signups} WHERE bclcid = bcs.bclcid AND sessionid = bcs.id) signups, (SELECT COUNT(id) FROM {local_cc_session_signups} WHERE bclcid = bcs.bclcid AND userid = $USER->id) as mysignupstatus ";*/
              $fromsql .= ", bss.id as signupid, bss.completion_status,";
            if($bclcdata->ccses_action == 'semsessions') {
                $fromsql .= " (SELECT COUNT(id) FROM {local_cc_session_signups} WHERE semesterid = bcs.semesterid AND sessionid = bcs.id) signups, (SELECT COUNT(id) FROM {local_cc_session_signups} WHERE semesterid = bcs.semesterid AND bclcid = 0 AND userid = $USER->id) as mysignupstatus ";
            }else{
                $fromsql .= " (SELECT COUNT(id) FROM {local_cc_session_signups} WHERE bclcid = bcs.bclcid AND sessionid = bcs.id) signups, (SELECT COUNT(id) FROM {local_cc_session_signups} WHERE bclcid = bcs.bclcid AND userid = $USER->id) as mysignupstatus ";
            }
                /*$fromsql .= " (SELECT COUNT(id) FROM {local_cc_session_signups} WHERE bclcid = bcs.bclcid AND userid = $USER->id) as mysignupstatus ";*/
        }

        $sql = " FROM {local_cc_course_sessions} AS bcs
                LEFT JOIN {user} AS u ON u.id = bcs.trainerid
                LEFT JOIN {local_location_room} AS lr ON lr.id = bcs.roomid ";
        if ($userview) {
            $sql .= " LEFT JOIN {local_cc_session_signups} AS bss ON bss.sessionid = bcs.id  AND bss.userid = $USER->id";
        }

        $sql .= " WHERE 1 = 1 AND bcs.curriculumid = $curriculumid AND bcs.semesterid = $semesterid";
        if($bclcdata->ccses_action == 'coursesessions') {
            $sql .= " AND bcs.bclcid = $bclcid AND bcs.sessiontype = 0";
        }else if($bclcdata->ccses_action == 'class_sessions') {
            $sql .= " AND bcs.bclcid = $bclcid AND bcs.sessiontype = 1";
        }else{
            $sql .= " AND bcs.bclcid = 0 AND bcs.courseid = 0 AND bcs.sessiontype = 2";
        }
        $sql .= $concatsql;

        /*if ($userview) {
            $time = time();
            $sql .= " AND (bcs.timefinish > $time OR bcs.id IN (SELECT sessionid
                    FROM {local_cc_course_sessions} WHERE curriculumid = $curriculumid AND semesterid = $semesterid AND bclcid = $bclcid AND userid = $USER->id ) )";// Commented by Harish //
            $sql .= " AND bcs.timefinish > $time";
        } else*/ 
        /*$isStudent = current(get_user_roles($systemcontext, $USER->id))->shortname=='student'? true : false;*/
        $studentroleid = $DB->get_field('role', 'id', array('shortname' => 'student'));
        $isStudent = user_has_role_assignment($USER->id,$studentroleid);
        if (has_capability('local/program:takesessionattendance', $systemcontext) && !is_siteadmin() && !has_capability('local/program:manageprogram', $systemcontext) && !has_capability('local/costcenter:manage_owndepartments', $systemcontext)){
            $sql .= " AND bcs.trainerid = $USER->id ";
        }
        if($tab == 'upcomingsessions'){
            $currtime = time();
            $currdate = date("d-m-y",$currtime);
            $date = explode('-', $currdate);
            $starttime = mktime(0,0,0,$date[1],$date[0],$date[2]);
            $endtime = mktime(23,59,59,$date[1],$date[0],$date[2]);
            if(!$isStudent){
                $sql .= " AND bcs.timestart >= $starttime";
            }
        }
        if($tab == 'completedsessions'){
            $currtime = time();
            $currdate = date("d-m-y",time());
            $date = explode('-', $currdate);
            $starttime = mktime(0,0,0,$date[1],$date[0],$date[2]);
            $endtime = mktime(23,59,59,$date[1],$date[0],$date[2]);
            $sql .= " AND bcs.timestart < $starttime ";

        }
        // echo $tab;
        // echo $fromsql . $sql;
        try {
            $sessionscount = $DB->count_records_sql($countsql . $sql, $params);
            if ($stable->thead == false) {
                if (!$userview) {
                    $sql .= " ORDER BY bcs.id ASC";
                }
                $sessions = $DB->get_records_sql($fromsql . $sql, $params, $stable->start,
                             $stable->length);

            }
        } catch (dml_exception $ex) {
            $sessionscount = 0;
            $sessions = array();
        }
        // print_object($sessions);
        return compact('sessions', 'sessionscount');
    }
    
    public function classroom_offlinesessions($tab=null, $stable) {
        global $DB, $USER;
        // print_object($stable);
        $systemcontext = context_system::instance();
        /*$bccourse = $DB->get_record('local_cc_semester_courses', array('id' => $bclcid));
        if (empty($bccourse)) {
            print_error('curriculum data missing');
        }*/
        $concatsql = '';
        if (!empty($stable->search)) {
            $fields = array (0 => 'bcs.name',
                            1 => 'lr.name'
                        );
            $fields = implode(" LIKE '%" . $stable->search . "%' OR ", $fields);
            $fields .= " LIKE '%" .$stable->search. "%' ";
            $concatsql .= " AND ($fields) ";
        }
        $params = array();
        $curriculums = array();

        $countsql = "SELECT COUNT(bcs.id) ";
        $fromsql = "SELECT bcs.*, lr.name as room, class.classname";

        $sql = " FROM {local_cc_course_sessions} AS bcs
                LEFT JOIN {user} AS u ON u.id = bcs.trainerid
                LEFT JOIN {local_location_room} AS lr ON lr.id = bcs.roomid 
                LEFT JOIN {local_cc_semester_classrooms} AS class ON class.id = bcs.bclcid";
        if ($userview) {
            $sql .= " LEFT JOIN {local_cc_session_signups} AS bss ON bss.sessionid = bcs.id  AND bss.userid = $USER->id";
        }

        $sql .= " WHERE 1 = 1 AND bcs.courseid = 0";
        $sql .= $concatsql;

        /*if ($userview) {
            $time = time();
            $sql .= " AND (bcs.timefinish > $time OR bcs.id IN (SELECT sessionid
                    FROM {local_cc_course_sessions} WHERE curriculumid = $curriculumid AND semesterid = $semesterid AND bclcid = $bclcid AND userid = $USER->id ) )";// Commented by Harish //
            $sql .= " AND bcs.timefinish > $time";
        } else*/ 
        /*$isStudent = current(get_user_roles($systemcontext, $USER->id))->shortname=='student'? true : false;*/
        $studentroleid = $DB->get_field('role', 'id', array('shortname' => 'student'));
        $isStudent = user_has_role_assignment($USER->id,$studentroleid);
        if (has_capability('local/program:takesessionattendance', $systemcontext) && !is_siteadmin() && !has_capability('local/program:manageprogram', $systemcontext) && !has_capability('local/costcenter:manage_owndepartments', $systemcontext)){
            $sql .= " AND bcs.trainerid = $USER->id ";
        }
        if($tab == 'upcomingsessions'){
            $currtime = time();
            $currdate = date("d-m-y",$currtime);
            $date = explode('-', $currdate);
            $starttime = mktime(0,0,0,$date[1],$date[0],$date[2]);
            $endtime = mktime(23,59,59,$date[1],$date[0],$date[2]);
            if(!$isStudent){
                $sql .= " AND bcs.timestart >= $starttime";
            }
        }
        if($tab == "previoussessions"){
            $currtime = time();
            $currdate = date("d-m-y",time());
            $date = explode('-', $currdate);
            $starttime = mktime(0,0,0,$date[1],$date[0],$date[2]);
            $endtime = mktime(23,59,59,$date[1],$date[0],$date[2]);
            $sql .= " AND bcs.timestart < $starttime ";
        }
        // echo $tab;
        // echo $fromsql . $sql;
        try {
            $sessionscount = $DB->count_records_sql($countsql . $sql, $params);
            if ($stable->thead == false) {
                if (!$userview) {
                    $sql .= " ORDER BY bcs.id DESC";
                }
                $sessions = $DB->get_records_sql($fromsql . $sql, $params, $stable->start,
                             $stable->length);
            }
        } catch (dml_exception $ex) {
            $sessionscount = 0;
            $sessions = array();
        }
        return compact('sessions', 'sessionscount');
    }

    public function sessions_validation($data, $sessiondate, $sessionid=0) {
        global $DB;
        $return = 0;
        if ($data && $sessiondate) {
            $params = array();
            $params['curriculumid'] = $data->curriculumid;
            $params['bclcid'] = $data->bclcid;
            $params['programid'] = $data->programid;
            $params['semesterid'] = $data->semesterid;

            if($data->ccses_action == 'class_sessions'){
                $params['sessiontype'] = 1;
            }else if($data->ccses_action == 'course_sessions'){
                $params['sessiontype'] = 0;
            }else{
                $params['sessiontype'] = 2;
            }
            /*$params['sessiondate_start'] = date('Y-m-d H:i', $sessiondate);
            $params['sessiondate_end'] = date('Y-m-d H:i', $sessiondate);*/
            $params['sessiondate_start'] = $sessiondate;
            $params['sessiondate_end'] = $sessiondate;
            // print_object($params);
            /*$sql = "SELECT * FROM {local_cc_course_sessions} WHERE curriculumid = :curriculumid AND programid = :programid AND semesterid = :semesterid AND bclcid = :bclcid AND sessiontype = :sessiontype AND (timestart = :sessiondate_start OR timefinish = :sessiondate_end)";*/
            $sql = "SELECT * FROM {local_cc_course_sessions} WHERE curriculumid = :curriculumid AND programid = :programid AND semesterid = :semesterid AND bclcid = :bclcid AND sessiontype = :sessiontype AND '{$sessiondate}' BETWEEN timestart AND timefinish";
            if ($sessionid > 0) {
                $sql .= " AND id != :sessionid ";
                $params['sessionid'] = $sessionid;
            }
            $return = $DB->record_exists_sql($sql,$params);
        }
        return $return;
     }
    /**
     * [add_curriculum_signups description]
     * @method add_curriculum_signups
     * @param  [type]                $curriculumid [description]
     * @param  [type]                $userid      [description]
     * @param  integer               $sessionid   [description]
     */
    public function add_curriculum_signups($curriculumid, $userid, $sessionid = 0) {
        global $DB, $USER;
        $curriculum = $DB->record_exists('local_curriculum',  array('id' => $curriculumid));
        if (!$curriculum) {
            print_error("curriculum Not Found!");
        }
        $user = $DB->record_exists('user', array('id' => $userid));
        if (!$user) {
            print_error("User Not Found!");
        }
        if ($sessionid > 0) {
            $session = $DB->record_exists('local_cc_course_sessions', array('id' => $sessionid, 'curriculumid' => $curriculumid));
            if (!$session) {
                print_error("Session Not Found!");
            }
        }
        $sessions = $DB->get_records('local_cc_course_sessions', array('curriculumid' => $curriculumid));
        foreach ($sessions as $session) {
            $checkattendeesignup = $DB->get_record('local_curriculum_attendance',
            array('curriculumid' => $curriculumid, 'sessionid' => $session->id, 'userid' => $userid));
            if (!empty($checkattendeesignup)) {
                continue;
            } else {
                $attendeesignup = new stdClass();
                $attendeesignup->curriculumid = $curriculumid;
                $attendeesignup->sessionid = $session->id;
                $attendeesignup->userid = $userid;
                $attendeesignup->status = 0;
                $attendeesignup->usercreated = $USER->id;
                $attendeesignup->timecreated = time();
                $id = $DB->insert_record('local_curriculum_attendance',  $attendeesignup);
                $params = array(
                    'context' => context_system::instance(),
                    'objectid' => $id
                );
                $event = \local_program\event\program_attendance_created_updated::create($params);
                $event->add_record_snapshot('local_program', $curriculumid);
                $event->trigger();
            }
        }
        return true;
    }
    /**
     * [remove_curriculum_signups description]
     * @method remove_curriculum_signups
     * @param  [type]                   $curriculumid [description]
     * @param  [type]                   $userid      [description]
     * @param  integer                  $sessionid   [description]
     * @return [type]                                [description]
     */
    public function remove_curriculum_signups($curriculumid, $userid, $sessionid = 0) {
        global $DB, $USER;
        if ($sessionid > 0) {
            $sessions = $DB->get_records('local_cc_course_sessions',
             array('curriculumid' => $curriculumid, 'id' => $sessionid));
        } else {
            $sessions = $DB->get_records('local_cc_course_sessions',
                array('curriculumid' => $curriculumid));
        }
        foreach ($sessions as $session) {
            $checkattendeesignup = $DB->get_record('local_curriculum_attendance',
                array('curriculumid' => $curriculumid, 'sessionid' => $session->id,
                    'userid' => $userid));
            if (!empty($checkattendeesignup)) {
                $DB->delete_records('local_curriculum_attendance',
                    array('curriculumid' => $curriculumid, 'sessionid' => $session->id,
                        'userid' => $userid));
            }
        }
        return true;
    }
    /**
     * [curriculum_get_attendees description]
     * @method curriculum_get_attendees
     * @param  [type]                  $sessionid [description]
     * @return [type]                             [description]
     */
    public function curriculum_get_attendees($curriculumid, $sessionid) {
        global $DB, $OUTPUT;
        $concatsql = "";
        $selectfileds = '';
        $whereconditions = '';
        // if ($sessionid > 0) {
        //     $selectfileds = ", ca.id as attendanceid, ca.completion_status";
        //     $concatsql .= " JOIN {local_cc_course_sessions} AS bcs ON bcs.curriculumid = bss.curriculumid AND bcs.curriculumid = $curriculumid
        //     JOIN {local_cc_session_signups} AS ca ON ca.curriculumid = bss.curriculumid
        //       AND ca.sessionid = bcs.id AND ca.userid = bss.userid";
        //     $whereconditions = " AND bcs.id = $sessionid";
        // }
        $signupssql = "SELECT DISTINCT u.id, u.firstname, u.lastname,
                              u.email, u.picture, u.firstnamephonetic, u.lastnamephonetic,
                              u.middlename, u.alternatename, u.imagealt, ca.id as attendanceid, ca.completion_status
                        FROM {user} AS u
                        JOIN {local_cc_session_signups} AS bss ON
                                (bss.userid = u.id AND bss.curriculumid = $curriculumid)
                        JOIN {local_cc_course_sessions} AS bcs ON
                        bcs.curriculumid = bss.curriculumid AND bcs.curriculumid = $curriculumid
                        JOIN {local_cc_session_signups} AS ca ON ca.curriculumid = bss.curriculumid
                            AND ca.sessionid = bcs.id AND ca.userid = bss.userid
                       WHERE bss.curriculumid = $curriculumid AND bcs.id = $sessionid";
        $signups = $DB->get_records_sql($signupssql);
        return $signups;
    }
    /**
     * [curriculum_add_assignusers description]
     * @method curriculum_add_assignusers
     * @param  [type]                    $curriculumid   [description]
     * @param  [type]                    $userstoassign [description]
     * @return [type]                                   [description]
     */
    public function curriculum_add_assignusers($curriculumid, $userstoassign) {
        global $DB, $USER,$CFG;
        if (file_exists($CFG->dirroot . '/local/lib.php')) {
            require_once($CFG->dirroot . '/local/lib.php');
        }
        // require_once($CFG->dirroot . '/local/program/notifications_emails.php');
        $emaillogs = new programnotifications_emails();
        $allow = true;
        $type = 'curriculum_enrol';
        $dataobj = $curriculumid;
        $fromuserid = $USER->id;
        if ($allow) {
            foreach ($userstoassign as $key => $adduser) {
                if (true) {
                    $curriculumuser = new stdClass();
                    $curriculumuser->curriculumid = $curriculumid;
                    $curriculumuser->courseid = 0;
                    $curriculumuser->userid = $adduser;
                    $curriculumuser->supervisorid = 0;
                    $curriculumuser->prefeedback = 0;
                    $curriculumuser->postfeedback = 0;
                    $curriculumuser->trainingfeedback = 0;
                    $curriculumuser->confirmation = 0;
                    $curriculumuser->attended_sessions = 0;
                    $curriculumuser->hours = 0;
                    $curriculumuser->completion_status = 0;
                    $curriculumuser->completiondate = 0;
                    $curriculumuser->usercreated = $USER->id;
                    $curriculumuser->timecreated = time();
                    $curriculumuser->usermodified = $USER->id;
                    $curriculumuser->timemodified = time();
                    try {
                        $curriculumuser->id = $DB->insert_record('local_curriculum_users',
                            $curriculumuser);
                        $local_curriculum = $DB->get_record_sql("SELECT * FROM {local_curriculum} where id = $curriculumid");

                        $params = array(
                            'context' => context_system::instance(),
                            'objectid' => $curriculumuser->id,
                            'other' => array('curriculumid' => $curriculumid)
                        );

                        $event = \local_program\event\program_users_enrol::create($params);
                        $event->add_record_snapshot('local_curriculum_users', $curriculumuser);
                        $event->trigger();

                        if ($local_curriculum->status == 0) {
                            $email_logs = $emaillogs->curriculum_emaillogs($type, $dataobj, $curriculumuser->userid, $fromuserid);
                        }
                    } catch (dml_exception $ex) {
                        print_error($ex);
                    }
                } else {
                    break;
                }
            }
            $curriculum = new stdClass();
            $curriculum->id = $curriculumid;
            $curriculum->totalusers = $DB->count_records('local_curriculum_users',
                array('curriculumid' => $curriculumid));
            $DB->update_record('local_curriculum', $curriculum);
        }
        return true;
    }

    /**
     * [programyear_unassignusers description]
     * @method 
     * @param  [type]                       $programid      [description]
     * @param  [type]                       $curriculumid   [description]
     * @param  [type]                       $yearid         [description]
     * @param  [type]                       $userid         [description]
     * @return [type]                                       [description]
     */
    public function programyear_unassignusers($programid, $curriculumid, $yearid, $userid) {
        global $DB, $USER,$CFG;
        if (file_exists($CFG->dirroot . '/local/lib.php')) {
            require_once($CFG->dirroot . '/local/lib.php');
        }
        require_once("$CFG->dirroot/group/lib.php");
       $emaillogs = new programnotifications_emails();
        $courses = $DB->get_records_menu('local_cc_semester_courses',
            array('curriculumid' => $curriculumid,'programid' => $programid, 'yearid' => $yearid), 'id', 'id, courseid');
        $dataobj = $curriculumid;
        $fromuserid = $USER->id;
        $removeuser = $DB->get_record('user',array('id' => $userid));
        $local_curriculum = $DB->get_record_sql("SELECT id, status FROM {local_curriculum} WHERE id = $curriculumid");
        
        if (!empty($courses)) {
            foreach ($courses as $course) {
               
                if ($course > 0) {
                    $courseid = $course;
                    $this->manage_curriculum_course_enrolments(
                                        $course, $userid, 'student', 'unenrol','program');
                }
                
            }
           
        }
       

        if($DB->record_exists('local_cc_semester_cmptl',array('programid' => $programid,'curriculumid' => $curriculumid, 'yearid' => $yearid,'userid' => $userid))){
          $DB->delete_records('local_cc_semester_cmptl', array('programid' => $programid,'curriculumid' => $curriculumid, 'yearid' => $yearid,'userid' => $userid));
        }
        if($DB->record_exists('local_cc_session_signups',array('programid' => $programid,'curriculumid' => $curriculumid, 'yearid' => $yearid,'userid' => $userid))){
            $DB->delete_records('local_cc_session_signups', array('programid' => $programid,'curriculumid' => $curriculumid, 'yearid' => $yearid,'userid' => $userid));
        }
        if($DB->record_exists('local_ccuser_year_signups',array('programid' => $programid,'curriculumid' => $curriculumid, 'yearid' => $yearid,'userid' => $userid))){
            $DB->delete_records('local_ccuser_year_signups', array('programid' => $programid,'curriculumid' => $curriculumid, 'yearid' => $yearid,'userid' => $userid));
        }
 

        $yearenrolmentcount = $DB->count_records('local_ccuser_year_signups',array('programid' => $programid,'curriculumid' => $curriculumid,'userid' => $userid));
        if($yearenrolmentcount == 0){
            $params = array(
               'context' => context_system::instance(),
               'objectid' => $curriculumid
            );

            $event = \local_program\event\program_users_unenrol::create($params);
            $event->add_record_snapshot('local_curriculum_users', $curriculumid);
            $event->trigger();
            $DB->delete_records('local_curriculum_users',  array('curriculumid' => $curriculumid,
                'userid' => $userid));
            if ($local_curriculum->status == 0) {
                $email_logs = $emaillogs->curriculum_emaillogs($type ,$dataobj, $userid, $fromuserid);
            }
                
            $curriculum = new stdClass();
            $curriculum->id = $curriculumid;
            $curriculum->totalusers = $DB->count_records('local_curriculum_users',
                array('curriculumid' => $curriculumid));
            $DB->update_record('local_curriculum', $curriculum);
        }
        return true;
    }


    /**
     * [curriculum_remove_assignusers description]
     * @method curriculum_remove_assignusers
     * @param  [type]                       $curriculumid     [description]
     * @param  [type]                       $userstounassign [description]
     * @return [type]                                        [description]
     */
    public function curriculum_remove_assignusers($curriculumid, $userstounassign) {
        global $DB, $USER,$CFG;
        if (file_exists($CFG->dirroot . '/local/lib.php')) {
            require_once($CFG->dirroot . '/local/lib.php');
        }
        // require_once($CFG->dirroot . '/local/program/notifications_emails.php');
        $emaillogs = new programnotifications_emails();
        $curriculumenrol = enrol_get_plugin('curriculum');
        //$studentroleid = $DB->get_field('role', 'id', array('shortname' => 'student'));
        $courses = $DB->get_records_menu('local_cc_semester_courses',
            array('curriculumid' => $curriculumid), 'id', 'id, courseid');
        $type = 'curriculum_unenroll';
        $dataobj = $curriculumid;
        $fromuserid = $USER->id;
        try {
            foreach ($userstounassign as $key=>$removeuser) {
                $local_curriculum = $DB->get_record_sql("SELECT id, status FROM {local_curriculum} WHERE id = $curriculumid");
                    if ($local_curriculum->status != 0) {
                        if (!empty($courses)) {
                            foreach ($courses as $course) {
                                if ($course > 0) {
                                    //$instance = $DB->get_record('enrol', array('courseid' => $course, 'enrol'=>'curriculum'), '*', MUST_EXIST);
                                    //$curriculumenrol->unenrol_user($instance, $removeuser, $instance->roleid, time());
                                    $unenrolcurriculumuser = $this->manage_curriculum_course_enrolments(
                                        $course, $removeuser, 'employee', 'unenrol');
                                }
                            }
                        }
                    }
                $params = array(
                   'context' => context_system::instance(),
                   'objectid' => $curriculumid
                );

                $event = \local_program\event\program_users_unenrol::create($params);
                $event->add_record_snapshot('local_curriculum_users', $curriculumid);
                $event->trigger();
                $DB->delete_records('local_curriculum_users',  array('curriculumid' => $curriculumid,
                    'userid' => $removeuser));
                if ($local_curriculum->status == 0) {
                    $email_logs = $emaillogs->curriculum_emaillogs($type ,$dataobj, $removeuser, $fromuserid);
                }
            }
            $curriculum = new stdClass();
            $curriculum->id = $curriculumid;
            $curriculum->totalusers = $DB->count_records('local_curriculum_users',
                array('curriculumid' => $curriculumid));
            $DB->update_record('local_curriculum', $curriculum);
        } catch (dml_exception $ex) {
            print_error($ex);
        }
        return true;
    }
    // OL-1042 Add Target Audience to curriculums//
    /**
     * [curriculum_logo description]
     * @method curriculum_logo
     * @param  integer        $curriculumlogo [description]
     * @return [type]                        [description]
     */
    public function curriculum_logo($curriculumlogo = 0) {
        global $DB;
        $curriculumlogourl = false;
        if ($curriculumlogo > 0){
            $sql = "SELECT * FROM {files} WHERE itemid = $curriculumlogo AND filename != '.'
            ORDER BY id DESC LIMIT 1";
            $curriculumlogorecord = $DB->get_record_sql($sql);
        }
        if (!empty($curriculumlogorecord)) {
          if ($curriculumlogorecord->filearea == "curriculumlogo") {
            $curriculumlogourl = moodle_url::make_pluginfile_url($curriculumlogorecord->contextid,
                $curriculumlogorecord->component, $curriculumlogorecord->filearea,
                $curriculumlogorecord->itemid, $curriculumlogorecord->filepath,
                $curriculumlogorecord->filename);
          }
        }
        return $curriculumlogourl;
    }
    /**
     * [manage_curriculum_courses description]
     * @method manage_curriculum_courses
     * @param  [type]                   $courses [description]
     * @return [type]                            [description]
     */
    public function manage_curriculum_courses($courses) {
        global $DB, $USER, $CFG;
        require_once($CFG->dirroot. '/course/lib.php');
            $curriculumcourseexists = $DB->record_exists('local_cc_semester_courses',
                array('curriculumid' => $courses->curriculumid, 'semesterid' => $courses->semesterid, 'open_parentcourseid' => $courses->course));

            $mastercourse = $DB->get_record('course',  array('id' => $courses->course));

            if($mastercourse){
                $mastercourse->shortname = $mastercourse->shortname . '_' . $courses->programid . '_' . $courses->yearid;
                $mastercourse->open_parentcourseid = $mastercourse->id;
                $mastercourse->id = 0;
                $clonedcourse = create_course($mastercourse);
                insert::add_enrol_meathod_tocourse($clonedcourse, 1);

                $curriculumcourse = new stdClass();
                $curriculumcourse->programid = $courses->programid;
                $curriculumcourse->curriculumid = $courses->curriculumid;
                $curriculumcourse->yearid = $courses->yearid;
                $curriculumcourse->semesterid = $courses->semesterid;
                $curriculumcourse->open_parentcourseid = $courses->course;
                $curriculumcourse->courseid = $clonedcourse->id;
                $curriculumcourse->coursetype = $courses->coursetype;
                $curriculumcourse->timecreated = time();
                $curriculumcourse->usercreated = $USER->id;
                $curriculumcourse->id = $DB->insert_record('local_cc_semester_courses',
                    $curriculumcourse);
                $sql = "SELECT userid FROM {local_cc_session_signups} WHERE semesterid = $courses->semesterid";
                $classroomusers = $DB->get_fieldset_sql($sql);
                $params = array(
                    'context' => context_system::instance(),
                    'objectid' => $curriculumcourse->id,
                    'other' => array('programid' => $courses->programid,
                                     'curriculumid' => $courses->curriculumid,
                                     'semesterid' => $courses->semesterid,
                                     'yearid' => $courses->yearid)
                );

                $event = \local_program\event\bcsemestercourse_created::create($params);
                $event->add_record_snapshot('local_cc_semester_courses', $curriculumcourse);
                $event->trigger();

                $this->manage_curriculum_semester_completions($courses->curriculumid, $courses->semesterid, $courses->yearid);
                 # AM to enrolled classroomuser to enroll for course
                if(!empty($classroomusers)){
                  $instance = $DB->get_record('enrol', array('courseid' => $clonedcourse->id, 'enrol' => 'program'), '*', MUST_EXIST);
                    if (!empty($instance)) {
                        foreach($classroomusers as $classroomuser){
                          $enrolmethod = enrol_get_plugin('program');
                          $roleid = $DB->get_field('role', 'id', array('shortname' => 'student'));
                         $enrolmethod->enrol_user($instance, $classroomuser, $roleid, time());
                        }
                    }
                }
                # AM ends here
            }
            $totalcourses = $DB->count_records('local_cc_semester_courses',
                array('curriculumid' => $courses->curriculumid, 'semesterid' => $courses->semesterid, 'yearid' => $courses->yearid));
            $semesterdata = new stdClass();
            $semesterdata->id = $courses->semesterid;
            $semesterdata->programid = $courses->programid;
            $semesterdata->curriculumid = $courses->curriculumid;
            $semesterdata->totalcourses = $totalcourses;
            $semesterdata->timemodified = time();
            $semesterdata->usermodified = $USER->id;
            $DB->update_record('local_curriculum_semesters', $semesterdata);
            $totalbccourses = $DB->count_records('local_cc_semester_courses',
                array('curriculumid' => $courses->curriculumid));
            $curriculumdata = new stdClass();
            $curriculumdata->programid = $courses->programid;
            $curriculumdata->id = $courses->curriculumid;
            $curriculumdata->totalcourses = $totalbccourses;
            $curriculumdata->timemodified = time();
            $curriculumdata->usermodified = $USER->id;
            $DB->update_record('local_curriculum', $curriculumdata);
        //}
        return true;
    }
    /**
     * [manage_curriculum_course_enrolments description]
     * @method manage_curriculum_course_enrolments
     * @param  [type]                             $cousre        [description]
     * @param  [type]                             $user          [description]
     * @param  string                             $roleshortname [description]
     * @param  string                             $type          [description]
     * @param  string                             $pluginname    [description]
     * @return [type]                                            [description]
     */
    public function manage_curriculum_course_enrolments($cousre, $user, $roleshortname = 'employee',
        $type = 'enrol', $pluginname = 'curriculum') {
        global $DB;
       $enrolmethod = enrol_get_plugin($pluginname);
        $roleid = $DB->get_field('role', 'id', array('shortname' => $roleshortname));
        $instance = $DB->get_record('enrol', array('courseid' => $cousre, 'enrol' => $pluginname), '*', MUST_EXIST);
        
        if (!empty($instance)) {
            if ($type == 'enrol') {
                $enrolmethod->enrol_user($instance, $user, $roleid, time());
            } else if ($type == 'unenrol'){
                $enrolmethod->unenrol_user($instance, $user,$roleid, time());
               
            }
        }
       /* if(is_siteadmin()){
            
                    echo '$instance';exit;
                }*/
        return true;
    }
    public function curriculum_semesteryears($curriculumid, $yearid = 0) {
        global $DB, $USER;
        $params = array();
        $curriculumsemesteryearssql = "SELECT pcy.id, pcy.year, pcy.programid, pcy.curriculumid, pcy.programid, pcy.cost
                                     FROM {local_program_cc_years} pcy
                                     JOIN {local_curriculum} cc ON cc.id = pcy.curriculumid
                                    WHERE cc.id = :curriculumid";
          $params['curriculumid'] = $curriculumid;
        if ($yearid) {
          $curriculumsemesteryearssql .= " AND pcy.id = :yearid ";
          $params['yearid'] = $yearid;
        }

        $curriculumsemesteryears = $DB->get_records_sql($curriculumsemesteryearssql, $params);
        return $curriculumsemesteryears;
    }
    public function curriculum_semesters($curriculumid) {
        global $DB, $USER;
        $curriculumsemesterssql = "SELECT bcl.id, bcl.semester, bcl.position
                                FROM {local_curriculum_semesters} bcl
                                JOIN {local_curriculum} bc ON bc.id = bcl.curriculumid
                                WHERE bc.id = :curriculumid";
        $curriculumsemesters = $DB->get_records_sql($curriculumsemesterssql,
            array('curriculumid' => $curriculumid));
        return $curriculumsemesters;
    }
    /**
     * [curriculum_courses description]
     * @method curriculum_courses
     * @param  [type]            $curriculumid [description]
     * @return [type]                         [description]
     */
    public function curriculum_semester_courses($curriculumid, $semesterid, $userview = false) {
        global $DB, $USER;
        $context = context_system::instance();
        if ($semesterid > 0) {
            $params = array();
            $curriculumcourses = array();
            $semestercousrsesselect = '';
            $semestercousrsessql = '';
            if ($userview && !is_siteadmin() && !has_capability('local/program:createprogram', $context)) {
                $semestercousrsesselect = ", bss.id AS signupid";
                $semestercousrsessql = " LEFT JOIN {local_cc_session_signups} bss ON bss.sessionid = bcs.id AND userid = $USER->id";
            }
            $curriculumcoursesssql = "SELECT bclc.id AS bcsemestercourseid, bclc.curriculumid,
                                    bclc.semesterid, c.fullname AS course, c.*, bcs.id AS sessionid,
                                    bcs.name AS session, bcs.timestart, bcs.timefinish

                                    $semestercousrsesselect
                                      FROM {local_cc_semester_courses} bclc
                                      JOIN {course} c ON c.id = bclc.courseid
                                 LEFT JOIN {local_cc_course_sessions} bcs ON bcs.bclcid = bclc.id
                                      $semestercousrsessql
                                     WHERE bclc.curriculumid = :curriculumid
                                     AND bclc.semesterid = $semesterid";
            if ($userview && !is_siteadmin() && !has_capability('local/program:createprogram', $context)) {
                $curriculumcoursesssql .= " ORDER BY bcsemestercourseid, signupid ASC";
            }
            $curriculumsemestercourses = $DB->get_records_sql($curriculumcoursesssql,
                array('curriculumid' => $curriculumid));
        }
        return $curriculumsemestercourses;
    }
    /**
     * [update_curriculum_status description]
     * @method update_curriculum_status
     * @param  [type]                  $curriculumid     [description]
     * @param  [type]                  $curriculumstatus [description]
     * @return [type]                                   [description]
     */
    public function update_curriculum_status($curriculumid, $curriculumstatus) {
        global $DB, $USER;
        $curriculum = new stdClass();
        $curriculum->id = $curriculumid;
        $curriculum->status = $curriculumstatus;
        if($curriculumstatus == curriculum_COMPLETED) {
            $activeusers = $DB->count_records('local_curriculum_users', array('curriculumid' => $curriculumid,
                'completion_status' => 1));
            $curriculum->activeusers = $activeusers;
            $totalusers = $DB->count_records('local_curriculum_users', array('curriculumid' => $curriculumid));
            $curriculum->totalusers = $totalusers;
            $activesessions = $DB->count_records('local_cc_course_sessions', array('curriculumid' => $curriculumid,
                'attendance_status' => 1));
            $curriculum->activesessions = $activesessions;
            $totalsessions = $DB->count_records('local_cc_course_sessions', array('curriculumid' => $curriculumid));
            $curriculum->totalsessions = $totalsessions;
        }
        $curriculum->usermodified = $USER->id;
        $curriculum->timemodified = time();
        $curriculum->completiondate = time();
        try {
            $DB->update_record('local_curriculum', $curriculum);
            $params = array(
                    'context' => context_system::instance(),
                    'objectid' => $curriculumid
                );
            $event  = \local_program\event\program_completed::create($params);
            $event->add_record_snapshot('local_program', $curriculumid);
            $event->trigger();
           //  $params = array(
           //     'context' => context_system::instance(),
           //     'objectid' => $curriculum->id
           //  );

           // $event = \local_program\event\program_updated::create($params);
           // $event->add_record_snapshot('local_program', $curriculum->id);
           // $event->trigger();
        } catch (dml_exception $ex) {
            print_error($ex);
        }
        return true;
    }
    /**
     * [curriculumusers description]
     * @method curriculumusers
     * @param  [type]         $curriculumid [description]
     * @param  [type]         $stable      [description]
     * @return [type]                      [description]
     */
    public function curriculumusers($curriculumid, $stable) {
        global $DB, $USER;
        $params = array();
        $curriculumusers = array();
        $concatsql = '';
        if (!empty($stable->search)) {
            $fields = array(0 => 'u.firstname',
                            1 => 'u.lastname',
                            2 => 'u.email',
                            3 => 'u.idnumber',
                            4 => 'CONCAT(u.firstname," ",u.lastname)'
                            );
                $fields = implode(" LIKE '%" .$stable->search. "%' OR ", $fields);
                $fields .= " LIKE '%" .$stable->search. "%' ";
                $concatsql .= " AND ($fields) ";
        }
        $countsql = "SELECT COUNT(cu.id) ";
        $fromsql = "SELECT u.*, cu.attended_sessions, cu.hours, cu.completion_status, c.totalsessions,
        c.activesessions,ccss.yearid";
        $sql = " FROM {user} AS u
                 JOIN {local_curriculum_users} AS cu ON cu.userid = u.id ";
        if ($stable->yearid > 0) {
          $sql .= " JOIN {local_ccuser_year_signups} ccss ON ccss.userid = cu.userid ";
          $concatsql .= " AND ccss.yearid = :yearid ";
          $params['yearid'] = $stable->yearid;
        }
        $sql .= " JOIN {local_curriculum} AS c ON c.id = cu.curriculumid
                  WHERE c.id = $curriculumid AND u.confirmed = 1 AND u.suspended = 0 AND u.deleted = 0 AND u.id > 2";
        $sql .= $concatsql;
        try {
            $curriculumuserscount = $DB->count_records_sql($countsql . $sql, $params);
            if ($stable->thead == false) {
                $sql .= " ORDER BY id ASC";
                //$curriculumusers = $DB->get_records_sql($fromsql . $sql, $params);
                //Revathi Issue no 770 starts
                $curriculumusers = $DB->get_records_sql($fromsql . $sql, $params, $stable->start, $stable->length);
                 //Revathi Issue no 770 ends
            }
        } catch (dml_exception $ex) {
            $curriculumuserscount = 0;
        }
        return compact('curriculumusers', 'curriculumuserscount');
    }

    /**
     * [completionchecking description]
     * @method completionchecking
     * @param  [type]         $curriculumid [description]
     * @return [type]                      [description]
     */
    public function completionchecking($programid,$curriculumid,$yearid,$userid) {
        global $DB, $USER;
        $params = array();

        //checking course completions
        $courses = $DB->get_records_sql_menu("SELECT id,courseid FROM {local_cc_semester_courses} WHERE programid = :programid AND curriculumid = :curriculumid AND yearid = :yearid",array('programid' => $programid,'curriculumid' => $curriculumid, 'yearid' =>$yearid));
        if($courses){
            $courseids = implode(',',$courses);
            $coursecompletions = $DB->get_records_sql("SELECT id FROM {course_completions} WHERE course IN ($courseids) AND timecompleted IS NOT NULL AND userid = :userid",array('userid' => $userid));
            if(!empty($coursecompletions)){
              return true;
            }
        }

        //checking semester completions
        $semesters = $DB->get_records_sql_menu("SELECT id,id as semesterid FROM {local_curriculum_semesters} WHERE programid = :programid AND curriculumid = :curriculumid AND yearid = :yearid",array('programid' => $programid,'curriculumid' => $curriculumid, 'yearid' =>$yearid));
        if($semesters){
            $semesterids = implode(',',$semesters);
            $semestercompletions = $DB->get_records_sql("SELECT id FROM {local_cc_semester_cmptl} WHERE programid = :programid AND curriculumid = :curriculumid AND yearid = :yearid AND semesterid IN ($semesterids) AND completion_status = 1 AND userid =:userid", array('programid' => $programid,'curriculumid' => $curriculumid, 'yearid' =>$yearid,'userid' => $userid));
            if(!empty($semestercompletions)){
              return true;
            }

            //checking for sessions completions
            $sessioncompletions = $DB->get_records_sql("SELECT id FROM {local_cc_session_signups} WHERE programid = :programid AND curriculumid = :curriculumid AND yearid = :yearid AND semesterid IN ($semesterids) AND completion_status = 1 AND userid =:userid", array('programid' => $programid,'curriculumid' => $curriculumid, 'yearid' =>$yearid,'userid' => $userid));

            if(!empty($sessioncompletions)){
              return true;
            }
        }

        //checking year completions
          $yearcompletions = $DB->get_records_sql("SELECT id FROM {local_ccuser_year_signups} WHERE programid = :programid AND curriculumid = :curriculumid AND yearid = :yearid AND userid = :userid AND completion_status = 1",array('programid' => $programid,'curriculumid' => $curriculumid, 'yearid' =>$yearid,'userid' => $userid));
          if(!empty($yearcompletions)){
            return true;
          }

        return false;
    }

    /**
     * [yearwisecompletionchecking description]
     * @method yearwisecompletionchecking
     * @param  [type]         $curriculumid [description]
     * @return [type]                      [description]
     */
    public function yearwisecompletionchecking($programid,$curriculumid,$yearid) {
        global $DB, $USER;
        $params = array();

        //checking course completions for year users
        $courses = $DB->get_records_sql_menu("SELECT id,courseid FROM {local_cc_semester_courses} WHERE programid = :programid AND curriculumid = :curriculumid AND yearid = :yearid",array('programid' => $programid,'curriculumid' => $curriculumid, 'yearid' =>$yearid));
        if($courses){
            $courseids = implode(',',$courses);
            $coursecompletions = $DB->get_records_sql_menu("SELECT id,userid FROM {course_completions} WHERE course IN ($courseids) AND timecompleted IS NOT NULL",array());
            if(!empty($coursecompletions)){
              $params = array_merge($params, $coursecompletions);
            }
        }

        //checking semester completions for year users
        $semesters = $DB->get_records_sql_menu("SELECT id,id as semesterid FROM {local_curriculum_semesters} WHERE programid = :programid AND curriculumid = :curriculumid AND yearid = :yearid",array('programid' => $programid,'curriculumid' => $curriculumid, 'yearid' =>$yearid));
        if($semesters){
            $semesterids = implode(',',$semesters);
            $semestercompletions = $DB->get_records_sql_menu("SELECT id,userid FROM {local_cc_semester_cmptl} WHERE programid = :programid AND curriculumid = :curriculumid AND yearid = :yearid AND semesterid IN ($semesterids) AND completion_status = 1", array('programid' => $programid,'curriculumid' => $curriculumid, 'yearid' =>$yearid));
            if(!empty($semestercompletions)){
              $params = array_merge($params, $semestercompletions);
            }

            //checking for sessions completions for year users
            $sessioncompletions = $DB->get_records_sql_menu("SELECT id,userid FROM {local_cc_session_signups} WHERE programid = :programid AND curriculumid = :curriculumid AND yearid = :yearid AND semesterid IN ($semesterids) AND completion_status = 1", array('programid' => $programid,'curriculumid' => $curriculumid, 'yearid' =>$yearid));

            if(!empty($sessioncompletions)){
              $params = array_merge($params, $sessioncompletions);
            }
        }

        //checking year completions for year users
          $yearcompletions = $DB->get_records_sql_menu("SELECT id,userid FROM {local_ccuser_year_signups} WHERE programid = :programid AND curriculumid = :curriculumid AND yearid = :yearid AND completion_status = 1",array('programid' => $programid,'curriculumid' => $curriculumid, 'yearid' =>$yearid));
          if(!empty($yearcompletions)){
            $params = array_merge($params, $yearcompletions);
          }
          if($params){
              $params = array_unique($params);
          }
        return $params;
    }

    /**
     * [curriculum_completions description]
     * @method curriculum_completions
     * @param  [type]                $curriculumid [description]
     * @return [type]                             [description]
     */
    public function curriculum_completions($programid, $curriculumid, $userid,$bclcid) {
        global $DB, $USER, $CFG;
       
        $totalyears = $DB->count_records('local_program_cc_years', array('programid' => $programid, 'curriculumid' => $curriculumid));

        if($totalyears == 1){
            $completedyears = $DB->count_records('local_ccuser_year_signups', array('programid' => $programid, 'curriculumid' => $curriculumid, 'completion_status' => 1, 'userid' => $userid));
        }else{
            $completedyears = $DB->count_records_sql('SELECT count(*) FROM {local_ccuser_year_signups} WHERE programid ='.$programid.' AND curriculumid ='.$curriculumid.' AND completion_status = 1 AND userid ='.$userid.'');
       }

        if ($totalyears == $completedyears) {
            $curriculumuser = $DB->get_record('local_curriculum_users', array('programid' => $programid, 'curriculumid' => $curriculumid, 'userid' => $userid));

            if (!empty($curriculumuser)) {

                    $curriculumuser->completion_status = 1;
                    $curriculumuser->completiondate = time();
                    $curriculumuser->usermodified = $USER->id;
                    $curriculumuser->timemodified = time();

                    $DB->update_record('local_curriculum_users', $curriculumuser);
                    $params = array(
                        'context' => context_system::instance(),
                        'objectid' => $curriculumuser->id
                    );
                    $notifications_exists = $DB->record_exists('local_notification_type', array('shortname' => 'program_completion'));
                    if($notifications_exists){
                        $emaillogs = new programnotifications_emails();
                        $email_logs = $emaillogs->curriculum_emaillogs('program_completion', $curriculumuser, $userid, $USER->id);
                    }
                    $event = \local_program\event\program_users_updated::create($params);
                    $event->add_record_snapshot('local_curriculum_users', $curriculumuser);
                    $event->trigger();
            }
        }
        return true;
    }
    public function curriculumcategories($formdata){
        global $DB;
        if ($formdata->id) {
            $DB->update_record('local_curriculum_categories', $formdata);
        } else {
            $DB->insert_record('local_curriculum_categories', $formdata);
        }
    }
    /**
     * [select_to_and_from_users description]
     * @param  [type]  $type       [description]
     * @param  integer $curriculumid [description]
     * @param  [type]  $params     [description]
     * @param  integer $total      [description]
     * @param  integer $offset1    [description]
     * @param  integer $perpage    [description]
     * @param  integer $lastitem   [description]
     * @return [type]              [description]
     */
    public function select_to_and_from_users($type = null, $curriculumid = 0, $params, $total = 0, $offset1 = -1, $perpage = -1, $lastitem = 0) {

        global $DB, $USER;
        $curriculum = $DB->get_record('local_curriculum', array('id' => $curriculumid));

        $params['suspended'] = 0;
        $params['deleted'] = 0;

        if ($total == 0) {
            $sql = "SELECT u.id, concat(u.firstname,' ',u.lastname,' ','(',u.email,')',' ','(',u.open_employeeid,')') as fullname";
        } else {
            $sql = "SELECT count(u.id) as total";
        }
        $sql .= " FROM {user} AS u
                 WHERE  u.id > 2 AND u.suspended = :suspended
                                     AND u.deleted = :deleted ";
        if ($lastitem != 0) {
            $sql.=" AND u.id > $lastitem";
        }
        if ((has_capability('local/program:manageprogram', context_system::instance())) && (!is_siteadmin() && (!has_capability('local/program:manage_multiorganizations', context_system::instance()) && !has_capability('local/costcenter:manage_multiorganizations', context_system::instance())))) {
            $sql .= " AND u.open_costcenterid = :costcenter";
            $params['costcenter'] = $USER->open_costcenterid;
            if ((has_capability('local/program:manage_owndepartments', context_system::instance()) || has_capability('local/costcenter:manage_owndepartments', context_system::instance()))) {
                $sql .= " AND u.open_departmentid = :department";
                $params['department'] = $USER->open_departmentid;
            }
        }
        $sql .= " AND u.id <> $USER->id ";
        if (!empty($params['email'])) {
            $sql .= " AND u.id IN ({$params['email']})";
        }
        if (!empty($params['uname'])) {
            $sql .= " AND u.id IN ({$params['uname']})";
        }
        if (!empty($params['department'])) {
            $sql .= " AND u.open_departmentid IN ({$params['department']})";
        }
        if (!empty($params['organization'])) {
            $sql .= " AND u.open_costcenterid IN ({$params['organization']})";
        }
        if (!empty($params['idnumber'])) {
            $sql .= " AND u.id IN ({$params['idnumber']})";
        }

        if (!empty($params['groups'])) {
            $sql .= " AND u.id IN (select cm.userid from {cohort_members} cm, {user} u where u.id = cm.userid AND u.deleted = 0 AND u.suspended = 0 AND cm.cohortid IN ({$params['groups']}))";
        }

        if ($type == 'add') {
            $sql .= " AND u.id NOT IN (SELECT lcu.userid as userid
                                       FROM {local_curriculum_users} AS lcu
                                       WHERE lcu.curriculumid = $curriculumid)";
        } else if ($type == 'remove') {
            $sql .= " AND u.id IN (SELECT lcu.userid as userid
                                       FROM {local_curriculum_users} AS lcu
                                       WHERE lcu.curriculumid = $curriculumid)";
        }

        $sql .= " AND u.id NOT IN (SELECT lcu.trainerid as userid
                                       FROM {local_curriculum_trainers} AS lcu
                                       WHERE lcu.curriculumid = $curriculumid)";

        $order = ' ORDER BY u.id ASC ';
        if ($perpage != -1) {
            $order .= "LIMIT $perpage";
        }

        if ($total == 0) {
            $availableusers = $DB->get_records_sql_menu($sql . $order, $params);
        } else {
            $availableusers = $DB->count_records_sql($sql, $params);
        }
        return $availableusers;
    }
    /**
     * [curriculum_self_enrolment description]
     * @param  [type] $curriculumid   [description]
     * @param  [type] $curriculumuser [description]
     * @return [type]                [description]
     */
    public function curriculum_self_enrolment($curriculumid,$curriculumuser){
        global $DB;
        $curriculum_capacity_check=$this->curriculum_capacity_check($curriculumid);
        if (!$curriculum_capacity_check) {
            $this->curriculum_add_assignusers($curriculumid,array($curriculumuser));
            // $curriculumcourses = $DB->get_records_menu('local_cc_semester_courses', array('curriculumid' => $curriculumid), 'id', 'id, courseid');
            // foreach($curriculumcourses as $curriculumcourse) {
            //    $this->manage_curriculum_course_enrolments($curriculumcourse, $curriculumuser);
            // }
        }
    }

    public function curriculum_capacity_check($curriculumid) {
        global $DB;
        $return             = false;
        $curriculumcapacity = $DB->get_field('local_curriculum', 'capacity', array(
            'id' => $curriculumid
        ));
        $enrolledusers     = $DB->count_records('local_curriculum_users', array(
            'curriculumid' => $curriculumid
        ));
        if ($curriculumcapacity <= $enrolledusers && !empty($curriculumcapacity) && $classroomcapacity != 0) {
            $return = true;
        }
        return $return;
    }

    /**
     * [function to get user enrolled curriculums count]
     * @param  [INT] $userid [id of the user]
     * @return [INT]         [count of the curriculums enrolled]
     */
    public function enrol_get_users_curriculums_count($userid) {
        global $DB;
        $curriculum_sql = "SELECT count(id)
                           FROM {local_curriculum_users}
                          WHERE userid = :userid";
        $curriculum_count = $DB->count_records_sql($curriculum_sql, array('userid' => $userid));
        return $curriculum_count;
    }
    /**
     * [function to get user enrolled curriculums ]
     * @param  [int] $userid [id of the user]
     * @return [object]         [curriculums object]
     */
    public function enrol_get_users_curriculums($userid) {
        global $DB;
        $curriculum_sql = "SELECT lc.id, lc.name, lc.description
                           FROM {local_curriculum} AS lc
                           JOIN {local_curriculum_users} AS lcu ON lcu.curriculumid = lc.id
                          WHERE userid = :userid";
        $curriculums = $DB->get_records_sql($curriculum_sql, array('userid' => $userid));
        return $curriculums;
    }
    public function manage_program_curriculum_years($semesteryears, $autocreate = false) {
        global $DB, $USER;

        try {
            if ($semesteryears->id > 0) {
                $semesteryears->usermodified = $USER->id;
                $semesteryears->timemodified = time();
                $DB->update_record('local_program_cc_years', $semesteryears);
                $params = array(
                    'context' => context_system::instance(),
                    'objectid' => $semesteryears->id,
                    'other' =>array('programid' => $semesteryears->programid, 'curriculumid' => $semesteryears->curriculumid)
                );

                $event = \local_program\event\year_updated::create($params);
                $event->add_record_snapshot('local_program_cc_years', $semesteryears);
                $event->trigger();
            } else {
                if ($autocreate) {
                    $records = array();
                    $durationdata = $DB->get_record_sql('SELECT duration, duration_format FROM {local_program} WHERE id = :id ', array('id' => $semesteryears->programid));
                    if ($durationdata->duration_format == 'Y') {
                        $years = $durationdata->duration;
                    } else if ($durationdata->duration_format == 'M') {
                        $years = ceil($durationdata->duration/12);
                    }

                    for ($i = 1; $i <= $years; $i++) {
                        ${'record' . $i} = new stdClass();
                        ${'record' . $i}->curriculumid = $semesteryears->curriculumid;
                        ${'record' . $i}->year = 'Year ' . $i;
                        ${'record' . $i}->cost = 0;
                        ${'record' . $i}->programid = $semesteryears->programid;
                        ${'record' . $i}->usercreated = $USER->id;
                        ${'record' . $i}->timecreated = time();
                        $records[$i] = ${'record' . $i};
                    }
                    $DB->insert_records('local_program_cc_years', $records);
                    return true;
                } else {
                    $semesteryears->usercreated = $USER->id;
                    $semesteryears->timecreated = time();
                    $semesteryears->id = $DB->insert_record('local_program_cc_years', $semesteryears);

                    $params = array(
                        'context' => context_system::instance(),
                        'objectid' => $semesteryears->id,
                        'other' =>array('programid' => $semesteryears->programid, 'curriculumid' => $semesteryears->curriculumid)
                    );

                    $event = \local_program\event\year_created::create($params);
                    $event->add_record_snapshot('local_program_cc_years', $semesteryears);
                    $event->trigger();
                }
            }
        } catch (dml_exception $ex) {
            print_error($ex);
        }
        return $semesteryears->id;
    }
    public function manage_curriculum_program_semesters($semester, $autocreate = false) {
        global $DB, $USER;
        $semester->description = $semester->semester_description['text'];
        try {
            if ($semester->id > 0) {
                $semester->usermodified = $USER->id;
                $semester->timemodified = time();
                $DB->update_record('local_curriculum_semesters', $semester);
                $params = array(
                    'context' => context_system::instance(),
                    'objectid' => $semester->id,
                    'other' =>array('curriculumid' => $semester->curriculumid)
                );

                $event = \local_program\event\semester_updated::create($params);
                $event->add_record_snapshot('local_curriculum_semesters', $semester);
                $event->trigger();
            } else {
                if ($autocreate) {
                    $records = array();
                    for ($i = 0; $i < 7; $i++) {
                        ${'record' . $i} = new stdClass();
                        ${'record' . $i}->curriculumid = $semester->curriculumid;
                        ${'record' . $i}->semester = 'Semester ' . $i;
                        ${'record' . $i}->description = '';
                        ${'record' . $i}->programid = $semester->programid;
                        ${'record' . $i}->usercreated = $USER->id;
                        ${'record' . $i}->timecreated = time();
                        $records[$i] = ${'record' . $i};
                    }
                    $DB->insert_records('local_curriculum_semesters', $records);
                    return true;
                } else {
                    $semester->usercreated = $USER->id;
                    $semester->timecreated = time();
                    $semester->id = $DB->insert_record('local_curriculum_semesters', $semester);

                    $params = array(
                        'context' => context_system::instance(),
                        'objectid' => $semester->id,
                        'other' =>array('curriculumid' => $semester->curriculumid)
                    );

                    $event = \local_program\event\semester_created::create($params);
                    $event->add_record_snapshot('local_curriculum_semesters', $semester);
                    $event->trigger();
                }
            }
        } catch (dml_exception $ex) {
            print_error($ex);
        }
        return $semester->id;
    }
    public function bc_session_enrolments($enroldata) {
        global $DB, $CFG, $USER;
        // require_once($CFG->dirroot . '/local/program/notifications_emails.php');
        $sessionenroldatasql = "SELECT bss.*, bcs.timestart, bcs.timefinish, bcs.attendance_status, bcs.totalusers, bcs.mincapacity, bcs.maxcapacity
                                  FROM {local_cc_course_sessions} bcs
                                  JOIN {local_cc_session_signups} bss ON bss.sessionid = bcs.id
                                 WHERE bss.curriculumid = :curriculumid AND bss.semesterid = :semesterid
                                 AND bss.bclcid = :bclcid
                                 AND bss.sessionid = :sessionid
                                 AND bss.userid = :userid ";
        $sessionenroldata = $DB->get_record_sql($sessionenroldatasql,
            array('curriculumid' => $enroldata->curriculumid,
                'semesterid' => $enroldata->semesterid,
                'bclcid' => $enroldata->bclcid,
                'sessionid' => $enroldata->sessionid,
                'userid' => $enroldata->userid));
        if (!empty($sessionenroldata) && $enroldata->enrol == 3) {
           $params = array(
                   'context' => context_system::instance(),
                   'objectid' => $enroldata->bclcid
                );

            $event = \local_program\event\session_users_unenrol::create($params);
            $event->add_record_snapshot('local_cc_session_signups', $enroldata);
            $event->trigger();

            if($enroldata->ccses_action == "semsessions"){
                $DB->delete_records('local_cc_session_signups',
                    array('curriculumid' => $enroldata->curriculumid, 'semesterid' => $enroldata->semesterid, 'bclcid' => 0, 'sessionid' => $enroldata->sessionid, 'userid' => $enroldata->userid, 'completion_status' => 0));
            }else{
                $DB->delete_records('local_cc_session_signups',
                    array('curriculumid' => $enroldata->curriculumid, 'semesterid' => $enroldata->semesterid, 'bclcid' => $enroldata->bclcid, 'sessionid' => $enroldata->sessionid, 'userid' => $enroldata->userid, 'completion_status' => 0));
            }
            $totaluserssql = "SELECT COUNT(DISTINCT id) FROM {local_cc_session_signups} WHERE sessionid = $enroldata->sessionid";
            $totalusers = $DB->count_records_sql($totaluserssql);
            $sessiondata = new stdClass();
            $sessiondata->id = $enroldata->sessionid;
            $sessiondata->totalusers = $totalusers;
            $DB->update_record('local_cc_course_sessions', $sessiondata);
            if($enroldata->ccses_action == 'coursesessions'){
                if ($enroldata->signupid) {
                    $courseid = $DB->get_field('local_cc_semester_courses', 'courseid',
                            array('id' => $enroldata->bclcid));
                    $this->manage_bcsemester_course_enrolments($courseid, $USER->id, 'employee', 'unenrol');
                }              
            }
            //cancel session
            $emaillogs = new programnotifications_emails();
            /*$email_logs = $emaillogs->curriculum_emaillogs('curriculum_session_cancel', $enroldata, $enroldata->userid,
                                $USER->id);*/
        } else if (!empty($sessionenroldata) && $enroldata->enrol == 2) {
            $allsessions =  $DB->get_records('local_cc_session_signups', array('bclcid' => $enroldata->bclcid, 'userid' => $USER->id, 'completion_status' => 0));
            foreach ($allsessions as $res) {
                $DB->delete_records('local_cc_session_signups',
                array('bclcid' => $enroldata->bclcid, 'userid' => $USER->id, 'sessionid'=>$res->sessionid, 'completion_status' => 0));

                $totaluserssql = "SELECT COUNT(DISTINCT id) FROM {local_cc_session_signups} WHERE sessionid = $res->sessionid";
                $totalusers = $DB->count_records_sql($totaluserssql);
                $sessiondata = new stdClass();
                $sessiondata->id = $res->sessionid;
                $sessiondata->totalusers = $totalusers;
                $res = $DB->update_record('local_cc_course_sessions', $sessiondata);
            }
            $enroldata->userid = $USER->id;
            // $enroldata->supervisorid = $USER->open_supervisorid;
            $enroldata->hours = 0;
            $enroldata->usercreated = $USER->id;
            $enroldata->timecreated = time();
            $signupid = $DB->insert_record('local_cc_session_signups', $enroldata);
            $params = array(
                            'context' => context_system::instance(),
                            'objectid' => $signupid->id,
                            'other' => array('curriculumid' => $curriculumid,
                              'semesterid' => $enroldata->semesterid,
                              'bclcid' => $enroldata->bclcid)
                        );

            $event = \local_program\event\session_users_enrol::create($params);
            $event->add_record_snapshot('local_cc_session_signups', $signupid);
            $event->trigger();

            $totaluserssql = "SELECT COUNT(DISTINCT id) FROM {local_cc_session_signups} WHERE sessionid = $enroldata->sessionid";
            $totalusers = $DB->count_records_sql($totaluserssql);
            $sessiondata = new stdClass();
            $sessiondata->id = $enroldata->sessionid;
            $sessiondata->totalusers = $totalusers;
            $DB->update_record('local_cc_course_sessions', $sessiondata);
            //reschedule session
            $emaillogs = new programnotifications_emails();
            $email_logs = $emaillogs->curriculum_emaillogs('curriculum_session_reschedule', $enroldata, $enroldata->userid, $USER->id);
        } else {
            if ($enroldata->enrol == 1) {
                // print_object($enroldata);
                /*$supervisorid = $DB->get_field('user', 'open_supervisorid', array('id'=>$enroldata->userid));*/
                // $enroldata->userid = $USER->id;
                // $enroldata->supervisorid = $supervisorid;
                if($enroldata->sessionid){  
                    $sessionenroldatasql = "SELECT bss.id, bss.userid 
                                        FROM {local_cc_session_signups} bss 
                                       WHERE bss.curriculumid = :curriculumid AND bss.semesterid = :semesterid
                                       AND bss.bclcid = :bclcid
                                       AND bss.sessionid = :sessionid
                                       AND bss.userid = :userid ";
                    $sessionenroldata = $DB->get_record_sql($sessionenroldatasql,
                                                array('curriculumid' => $enroldata->curriculumid,
                                                    'semesterid' => $enroldata->semesterid,
                                                    'bclcid' => $enroldata->bclcid,
                                                    'sessionid' => $enroldata->sessionid,
                                                    'userid' => $enroldata->userid));
                }
                if(empty($sessionenroldata)){
                    $enroldata->hours = 0;
                    $enroldata->usercreated = $USER->id;
                    $enroldata->timecreated = time();
                    $signupid = $DB->insert_record('local_cc_session_signups', $enroldata);
                    $params = array(
                                'context' => context_system::instance(),
                                'objectid' => $signupid->id,
                                'other' => array('curriculumid' => $enroldata->curriculumid,
                                  'semesterid' => $enroldata->semesterid,
                                  'bclcid' => $enroldata->bclcid, 'ccses_action' => $enroldata->ccses_action)
                            );

                    $event = \local_program\event\session_users_enrol::create($params);
                    $event->add_record_snapshot('local_cc_session_signups', $signupid);
                    $event->trigger();
                }

                $totaluserssql = "SELECT COUNT(DISTINCT id) FROM {local_cc_session_signups} WHERE sessionid = $enroldata->sessionid";
                $totalusers = $DB->count_records_sql($totaluserssql);
                $sessiondata = new stdClass();
                $sessiondata->id = $enroldata->sessionid;
                $sessiondata->totalusers = $totalusers;
                $DB->update_record('local_cc_course_sessions', $sessiondata);

                //enroll session
                $emaillogs = new programnotifications_emails();
                /*$email_logs = $emaillogs->curriculum_emaillogs('curriculum_session_enrol', $enroldata, $enroldata->userid,
                                $USER->id);*/
                if($enroldata->ccses_action == 'coursesessions'){
                    if ($signupid) {
                        $courseid = $DB->get_field('local_cc_semester_courses', 'courseid',
                            array('id' => $enroldata->bclcid));
                        $this->manage_bcsemester_course_enrolments($courseid, $USER->id);
                    }
                }
                return $signupid;
            }
        }
        $totaluserssql = "SELECT COUNT(DISTINCT id) FROM {local_cc_session_signups} WHERE sessionid = $enroldata->sessionid";
        $totalusers = $DB->count_records_sql($totaluserssql);
        $sessiondata = new stdClass();
        $sessiondata->id = $enroldata->sessionid;
        $sessiondata->totalusers = $totalusers;
        $DB->update_record('local_cc_course_sessions', $sessiondata);
        return true;
    }
    /**
     * [unassign_courses_to_bcsemester description]
     * @method unassign_courses_to_bcsemester
     * @param  [type]                      $curriculumid [description]
     * @param  [type]                      $semesterid    [description]
     * @param  [type]                      $bclcid     [description]
     * @return [type]                                  [description]
     */
    public function unassign_courses_from_semester($programid, $curriculumid, $yearid, $semesterid, $courseid) {
        global $DB, $CFG;
        require_once($CFG->dirroot. '/course/lib.php');
        $signups = $DB->get_records('local_cc_session_signups', array('programid' => $programid, 'curriculumid' => $curriculumid, 'yearid' => $yearid));
        if (!empty($signups)) {
          print_error("please unassign students");
        }

        \core_php_time_limit::raise();
        // We do this here because it spits out feedback as it goes.
        delete_course($courseid, false);
        // Update course count in categories.
        fix_course_sortorder();

        $DB->delete_records('local_cc_semester_courses', array('programid' => $programid, 'curriculumid' => $curriculumid, 'yearid' => $yearid, 'courseid' => $courseid));

        $totalcourses = $DB->count_records('local_cc_semester_courses',
            array('curriculumid' => $curriculumid, 'yearid' => $yearid, 'semesterid' => $semesterid));
        $semesterdata = new stdClass();
        $semesterdata->id = $semesterid;
        $semesterdata->curriculumid = $curriculumid;
        $semesterdata->totalcourses = $totalcourses;
        $semesterdata->timemodified = time();
        $semesterdata->usermodified = $USER->id;
        $DB->update_record('local_curriculum_semesters', $semesterdata);
        $totalbccourses = $DB->count_records('local_cc_semester_courses',
            array('curriculumid' => $curriculumid));
        $curriculumdata = new stdClass();
        $curriculumdata->id = $curriculumid;
        $curriculumdata->totalcourses = $totalbccourses;
        $curriculumdata->timemodified = time();
        $curriculumdata->usermodified = $USER->id;
        $DB->update_record('local_curriculum', $curriculumdata);

        return true;
    }
    /**
     * [manage_bcsemester_course_enrolments description]
     * @method manage_bcsemester_course_enrolments
     * @param  [type]                           $course     [description]
     * @param  [type]                           $user       [description]
     * @param  string                           $role       [description]
     * @param  string                           $type       [description]
     * @param  string                           $pluginname [description]
     * @return [type]                                       [description]
     */
    public function manage_bcsemester_course_enrolments($course, $user, $role = 'employee',
        $type = 'enrol', $pluginname = 'program') {
        global $DB;
        $enrolmethod = enrol_get_plugin($pluginname);
        // print_object($course);
        $roleid = $DB->get_field('role', 'id', array('shortname' => $role));
        // print_object($user);
        // print_object($pluginname);exit;
        $instance = $DB->get_record('enrol', array('courseid' => $course, 'enrol' => $pluginname), '*', MUST_EXIST);
        // print_object($instance);
        if (!empty($instance)) {
            if ($type == 'enrol') {
                $enrolmethod->enrol_user($instance, $user, $roleid, time());
            } else if ($type == 'unenrol') {
                $enrolmethod->unenrol_user($instance, $user, $roleid, time());
            }
        }
        return true;
    }
    /**
     * [curriculum_semester_completions description]
     * @method curriculum_semester_completions
     * @param  [type]              $bcsemester [description]
     * @param  [type]              $user    [description]
     * @return [type]                       [description]
     */
    public function curriculum_semester_completions($bcsemester, $userid) {
        global $DB;
        // $bccoursessql = "SELECT bclc.id as bclcid, bclc.curriculumid, bclc.semesterid, bclc.courseid, bclc.totalsessions,
        //                  (SELECT COUNT(bss.id) FROM {local_cc_session_signups} bss WHERE bss.bclcid = bclc.id
        //                   AND bss.completion_status = 1 AND bss.userid = :userid) AS completedsessions
        //                  FROM {local_cc_semester_courses} bclc
        //                 WHERE bclc.semesterid = :semesterid";
        // $bccourses = $DB->get_records_sql($bccoursessql,
        //     array('semesterid' => $bcsemester->semesterid, 'userid' => $user));
        // $completedsessions = 0;
        // foreach ($bccourses as $bccourse) {
        //     if ($bccourse->completedsessions > 0) {
        //         $completedsessions++;
        //     }
        // }
        // if ($completedsessions == count($bccourses)) {
        //     return true;
        // }
        // return false;

        /*$semestercourses = $DB->count_records('local_cc_semester_courses', array('semesterid' => $bcsemester->semesterid, 'coursetype' => 1));*/
        $semestercourses = $DB->get_records('local_cc_semester_courses', array('semesterid' => $bcsemester->semesterid, 'coursetype' => 1));
        $mancoursescmpltion = 0;
        foreach($semestercourses as $semcourse){
            $coursecompleted = $DB->get_field_sql("SELECT id FROM {course_completions} WHERE course = $semcourse->courseid AND userid = $userid AND timecompleted IS NOT NULL");
            if($coursecompleted){
                $mancoursescmpltion++;
            }
        }
        $completedcourses = $DB->get_field_sql("SELECT length(courseids)
    - length(replace(courseids, ',', '')) + 1 as coursecount FROM {local_cc_semester_cmptl} WHERE semesterid = :semesterid AND userid = :userid", array('semesterid' => $bcsemester->semesterid, 'userid' => $userid));
        /*$completedcourses = $DB->get_record_sql('SELECT GROUP_CONCAT(id, ",") AS ids, count(*) AS completedcourses FROM {local_cc_semester_cmptl} WHERE semesterid = :semesterid AND userid = :userid ',  array());*/// Commented existing code by Harish//
        $classroomcount = $DB->count_records('local_cc_semester_classrooms',array('programid' => $bcsemester->programid, 'curriculumid' => $bcsemester->curriculumid, 'yearid' => $bcsemester->yearid, 'semesterid' => $bcsemester->semesterid));
        if($classroomcount > 0){
          $semclassroom_sessions = $this->curriculum_semester_classroomsessioncompletions($bcsemester, $userid);
          if ($mancoursescmpltion == $completedcourses && $semclassroom_sessions) {
              return true;
          }
        }else{
          if ($mancoursescmpltion == $completedcourses) {
              return true;
          }
        }
        return false;
    }

    public function curriculum_semester_classroomsessioncompletions($bcsemester, $userid){
        global $DB;
        $classrooms = $DB->get_records('local_cc_semester_classrooms', array('programid' => $bcsemester->programid, 'curriculumid' => $bcsemester->curriculumid, 'semesterid' => $bcsemester->semesterid), '', 'id, id as classroomid');
        
        /*$sql = "SELECT lcc.* FROM {local_cc_semester_classrooms} AS lcc JOIN {local_cc_course_sessions} AS lcs ON lcc.id = lcs.bclcid AND lcs.sessiontype = :sessiontype WHERE lcs.programid = :programid AND lcs.curriculumid = :curriculumid AND lcs.semesterid = :semesterid";*/
        $totalsessions = 0;
        $presentsessions = 0;
        foreach ($classrooms as $key => $value) {
            $complsettings = $DB->get_record('local_classroom_completion', array('classroomid' => $value->classroomid), 'id, classroomid, sessiontracking, sessionids, requiredsessions');
                // print_object($complsettings);exit;
            if($complsettings){
                if($complsettings->sessiontracking == 'AND'){
                    $sessions = $DB->count_records('local_cc_course_sessions', array('bclcid' => $value->classroomid, 'curriculumid' => $bcsemester->curriculumid, 'programid' => $bcsemester->programid, 'semesterid' => $bcsemester->semesterid));
                    $totalsessions = $totalsessions+$sessions;
                    $sessionidsql = "SELECT id, id as sessionid FROM {local_cc_course_sessions} WHERE bclcid = $value->classroomid AND curriculumid = $bcsemester->curriculumid AND programid = $bcsemester->programid AND semesterid = $bcsemester->semesterid";
                    $sessioniddata = $DB->get_records_sql_menu($sessionidsql);
                    if($sessioniddata){
                        $sessionids = implode(',', $sessioniddata);
                        $sessionsattended = $DB->count_records_sql("SELECT count(id) FROM {local_cc_session_signups} WHERE sessionid IN ($sessionids) AND userid = :userid AND completion_status = :status", array('userid' => $userid, 'status' => 1));
                    }else{
                        $sessionsattended = 0;
                    }
                    $presentsessions = $presentsessions+$sessionsattended; 
                }else if($complsettings->sessiontracking == 'OR'){
                    $params = array();
                    $sessionids = explode(',', $complsettings->sessionids);
                    $atleastonesession_sql = "SELECT count(id) FROM {local_cc_session_signups} WHERE sessionid IN ($complsettings->sessionids) AND userid = :userid AND completion_status = :status";
                    $params['status'] = 1;
                    $params['userid'] = $userid;
                    $atleastonepresent = $DB->count_records_sql($atleastonesession_sql, $params);
                    $totalsessions = $totalsessions+count($sessionids);
                    if($atleastonepresent){
                        $presentsessions = $presentsessions+$atleastonepresent; 
                    }
                }else{
                    $params = array();
                    $requiredsessions_sql = "SELECT count(id) FROM {local_cc_session_signups} WHERE bclcid = $complsettings->classroomid AND userid = :userid AND completion_status = :status";
                    $params['status'] = 1;
                    $params['userid'] = $userid;
                    $requiredsessions = $DB->count_records_sql($requiredsessions_sql, $params);
                    $totalsessions = $totalsessions+$requiredsessions;
                    if($requiredsessions == $complsettings->requiredsessions){
                        $presentsessions = $presentsessions+$requiredsessions; 
                    }
                }
            }else{
                    // logic for atleast one present session starts here //
                    $atleastonesession_sql = "SELECT id FROM {local_cc_session_signups}   WHERE bclcid = :classroomid AND curriculumid = :curriculumid AND programid = :programid AND semesterid = :semesterid AND userid = :userid AND completion_status = :status";
                    $params = array();
                    $params['classroomid'] = $value->classroomid;
                    $params['curriculumid'] = $bcsemester->curriculumid;
                    $params['programid'] = $bcsemester->programid;
                    $params['semesterid'] = $bcsemester->semesterid;
                    $params['userid'] = $userid;
                    $params['status'] = 1;
                    $atleastonepresent = $DB->get_record_sql($atleastonesession_sql, $params);
                    $totalsessions = $totalsessions+1;
                    if($atleastonepresent){
                        $presentsessions = $presentsessions+1; 
                    }
                    // logic for atleast one present session ends here //
                    
                    // logic for required sessions starts here //
                    /*$presentsessions_sql = "SELECT count(id) FROM {local_cc_session_signups} WHERE bclcid = :classroomid AND curriculumid = :curriculumid AND programid = :programid AND semesterid = :semesterid AND userid = :userid AND completion_status = :status";
                    $params = array();
                    $params['classroomid'] = $value->classroomid;
                    $params['curriculumid'] = $bcsemester->curriculumid;
                    $params['programid'] = $bcsemester->programid;
                    $params['semesterid'] = $bcsemester->semesterid;
                    $params['userid'] = $userid;
                    $params['status'] = 1;
                    $sessionsattended = $DB->count_records_sql($presentsessions_sql, $params);
                    $totalsessions = $totalsessions+$value->requiredsessions;
                    if($sessionsattended){
                        $presentsessions = $presentsessions+$sessionsattended;
                    }*/
                    // logic for required sessions ends here //
            }
        }
        if(!empty($totalsessions) && $totalsessions == $presentsessions){
            $return = true;
        }else{
            $return = false;
        }
        return $return;
    }

    /**
     * [bccourse_sessions_completions description]
     * @method bccourse_sessions_completions
     * @param  [type]                        $bccourse [description]
     * @return [type]                                  [description]
     */
    public function bccourse_sessions_completions($bccourse) {
        global $DB, $USER;
        $bcsessionssql = "SELECT bccs.id as sessionid, bccs.curriculumid, bccs.semesterid,
                            bccs.bclcid,
                         (SELECT COUNT(bss.id) FROM {local_cc_session_signups} bss WHERE bss.bclcid = bccs.bclcid
                          AND bss.completion_status = 1 AND bss.userid = :userid) AS completedsessions
                         FROM {local_cc_course_sessions} bccs
                        WHERE bccs.semesterid = :semesterid AND bccs.bclcid = :bclcid";
        $bcsessions = $DB->get_records_sql($bcsessionssql,
            array('semesterid' => $bccourse->semesterid,
                    'userid' => $USER->id, 'bclcid' => $bccourse->bclcid));
        $completedsessions = false;
        foreach ($bcsessions as $bcsessions) {
            if ($bcsessions->completedsessions > 0) {
                $completedsessions = true;
            }
        }
    }
    /**
     * [bc_semester_courses_completions description]
     * @method bc_semester_courses_completions
     * @param  [type]                       $userdata [description]
     * @return [type]                                 [description]
     */
    public function bc_semester_courses_completions($userdata) {
        global $DB, $USER, $CFG;
        // require_once($CFG->dirroot . '/local/program/notifications_emails.php');
        $userid = $userdata->userid;

        if ($userdata->curriculumid > 0) {
          if(!empty($userdata->sessionid)){
            $sid = $DB->get_field('local_cc_course_sessions','semesterid',array('id' => $userdata->sessionid));
            $coursedata = $DB->get_record_select('local_cc_semester_courses', 'curriculumid = :curriculumid AND semesterid = :semesterid', array('curriculumid' => $userdata->curriculumid,'semesterid' => $sid));
          }

          if(!empty($userdata->semesterid)){
            $coursedata = $DB->get_record_select('local_cc_semester_courses', 'curriculumid = :curriculumid AND semesterid = :semesterid', array('curriculumid' => $userdata->curriculumid,'semesterid' => $userdata->semesterid));
          }

          // issue 725 attendance issue - starts
          if(!empty($userdata->bclcid)){
            $coursedata = $DB->get_record_select('local_cc_semester_classrooms', 'curriculumid = :curriculumid AND semesterid = :semesterid', array('curriculumid' => $userdata->curriculumid,'semesterid' => $userdata->semesterid));
          }
          // issue 725 attendance issue - ends  
            if($coursedata){

                $getsemester_courses = $DB->get_records_sql_menu("SELECT id, courseid FROM {local_cc_semester_courses} WHERE semesterid = $coursedata->semesterid AND coursetype = 1");
                $mandatorycourses = implode(',', $getsemester_courses);
            }
            $semesterid = $coursedata->semesterid;
            $yearid = $coursedata->yearid;
            $curriculumid = $coursedata->curriculumid;
            $programid = $coursedata->programid;
            $checkcousrecmptlsql = "SELECT *
                                      FROM {local_cc_semester_cmptl}
                                      WHERE userid = :userid
                                      AND curriculumid = :curriculumid
                                      AND yearid = :yearid
                                      AND semesterid = :semesterid";
           $checkcousrecmptl = $DB->get_record_sql($checkcousrecmptlsql, array('userid' => $userid, 'curriculumid' => $curriculumid, 'yearid' => $yearid, 'semesterid' => $semesterid));

            if (empty($checkcousrecmptl)) {
                // $programid = $DB->get_field('local_curriculum', 'program',
                //     array('id' => $userdata->curriculumid));
                $bcsemestercmptl = new stdClass();
                $bcsemestercmptl->curriculumid = $coursedata->curriculumid;
                $bcsemestercmptl->programid = $programid;
                $bcsemestercmptl->yearid = $yearid;
                $bcsemestercmptl->type = 0;
                $bcsemestercmptl->semesterid = $coursedata->semesterid;
                $bcsemestercmptl->userid = $userdata->userid;
                if($mandatorycourses){
                    /*$completedcourses = $DB->get_records_sql("SELECT id, course FROM {course_completions} WHERE course IN ($mandatorycourses) AND userid = 71 AND timecompleted IS NOT NULL");
                    print_object($completedcourses);exit;*/
                    $bcsemestercmptl->courseids = $mandatorycourses;
                }
                $completionstatus = 0;
                $bcsemestercmptl->completion_status = $completionstatus;
                $bcsemestercmptl->completiondate = time();
                $bcsemestercmptl->usercreated = $USER->id;
               $bcsemestercmptl->timecreated = time();

                $bcsemestercmptl->id = $DB->insert_record('local_cc_semester_cmptl',
                    $bcsemestercmptl);
                $completion_status = $this->curriculum_semester_completions($coursedata, $userdata->userid);
                if ($completion_status) {
                    $bcsemestercmptl->completion_status = 1;
                    $bcsemestercmptl->completiondate = time();
                }
                $bcsemestercmptl->usermodified = $USER->id;
                $bcsemestercmptl->timemodified = time();
                $DB->update_record('local_cc_semester_cmptl', $bcsemestercmptl);
                $semestercompletion = $completion_status;
                //semester completions $completionstatus=1
                if($bcsemestercmptl->completion_status == 1){
                     $notifications_exists = $DB->record_exists('local_notification_type', array('shortname' => 'program_semester_completion'));
                    if($notifications_exists){
                      $type = 'program_semester_completion';
                      $emaillogs = new programnotifications_emails();
                      $email_logs = $emaillogs->curriculum_emaillogs($type, $coursedata, $userdata->userid, $USER->id);
                  }
                }
            } else {
                $courseids = $checkcousrecmptl->courseids;
                if (!empty($checkcousrecmptl->courseids)) {
                    $courseidslist = explode(',', $checkcousrecmptl->courseids);
                    if($coursedata->coursetype == 1){
                        if (!in_array($coursedata->courseid, $courseidslist)) {
                            $courseidslist[] = $coursedata->courseid;
                        }
                    }
                    $courseids = implode(',', $courseidslist);
                } else {
                  $courseids = $coursedata->courseid;
                }
                $checkcousrecmptl->courseids = $courseids;

                $checkcousrecmptl->usermodified = $USER->id;
                $checkcousrecmptl->timemodified = time();
                $DB->update_record('local_cc_semester_cmptl', $checkcousrecmptl);
                $completionstatus = $this->curriculum_semester_completions($coursedata, $userdata->userid);
                $semestercompletion = $completionstatus;
                if ($completionstatus) {
                    $checkcousrecmptl->completion_status = 1;
                    $checkcousrecmptl->completiondate = time();
                }
                $checkcousrecmptl->usermodified = $USER->id;
                $checkcousrecmptl->timemodified = time();
                $DB->update_record('local_cc_semester_cmptl', $checkcousrecmptl);
                //semester completions $completionstatus=1
                if ($completionstatus == 1) {
                  $notifications_exists = $DB->record_exists('local_notification_type', array('shortname' => 'program_semester_completion'));
                    if($notifications_exists){
                      $type = 'program_semester_completion';
                      $emaillogs = new programnotifications_emails();
                      $email_logs = $emaillogs->curriculum_emaillogs($type, $coursedata, $userdata->userid,
                                $USER->id);
                  }
                }
                //semester course completions $checkcousrecmptl->bclcids
            }

            if ($semestercompletion) {

                $ccyearcompletionstatus = $this->curriculum_year_completions($programid, $curriculumid, $yearid, $userid, $semesterid ,$userdata->bclcid);

                $cccompletionstatus = $this->curriculum_completions($programid, $curriculumid, $userid,$userdata->bclcid);
                // $bcuser = $DB->get_record('local_curriculum_users',
                //     array('curriculumid' => $userdata->curriculumid,
                //         'userid' => $userdata->userid, 'completion_status' => 0));
                // if (!empty($bcuser)) {
                //     $bcsemesters = $DB->get_records_menu('local_curriculum_semesters',
                //         array('curriculumid' => $coursedata->curriculumid), 'id',
                //         'id, id AS semester');
                //     $bcusercmptlsemesterids = $bcuser->semesterids;
                //     if (empty($bcusercmptlsemesterids)) {
                //         $bcuser->semesterids = $coursedata->semesterid;
                //         $semesterids = array($coursedata->semesterid);
                //     } else {
                //         $semesterids = explode(',', $bcusercmptlsemesterids);
                //         if (!in_array($coursedata->semesterid, $semesterids)) {
                //            $semesterids[] = $coursedata->semesterid;
                //         }
                //         array_unique($semesterids);
                //         $bcuser->semesterids = implode(',', $semesterids);;
                //     }
                //     $bcsemestercompletionstatus = array_diff($bcsemesters, $semesterids);
                //     if (empty($bcsemestercompletionstatus)) {
                //         $bcuser->completion_status = 1;
                //         $bcuser->completiondate = time();
                //     }
                //     $DB->update_record('local_curriculum_users', $bcuser);
                //     //curriculum completions $bcuser->completion_status=1
                //     if($bcuser->completion_status == 1){
                //       $type = 'curriculum_completion';
                //       $emaillogs = new programnotifications_emails();
                //       $email_logs = $emaillogs->curriculum_emaillogs($type, $bcuser->curriculumid, $bcuser->userid,
                //                 $USER->id);
                //     }

                // }
            }
            return true;
        }
        return false;
    }
    public function curriculum_year_completions($programid, $curriculumid, $yearid, $userid, $semesterid, $bclcid) {
        global $DB, $USER;
        $ccyearcompletionstatussql = 'SELECT * FROM ((SELECT COUNT(cs.id) AS totalsemesters
                                   FROM {local_curriculum_semesters} cs
                                  WHERE cs.yearid = :yearid) AS totalsemesters,
                                (SELECT COUNT(csc.id) AS completedsemesters
                                   FROM {local_cc_semester_cmptl} csc
                                  WHERE csc.yearid = :yearid1 AND csc.userid = :userid ) AS completedsemesters  )';
        $ccyearcompletionstatus = $DB->get_record_sql($ccyearcompletionstatussql, array('yearid' => $yearid, 'yearid1' => $yearid, 'userid' => $userid));
        // if ($ccyearcompletionstatus->totalsemesters == $ccyearcompletionstatus->completedsemesters) {
        //     return true;
        // }
        // return false;
        
        if ($ccyearcompletionstatus->totalsemesters == $ccyearcompletionstatus->completedsemesters) {
            $yearcompletionstatus = $DB->get_record('local_ccuser_year_signups', array('programid' => $programid, 'curriculumid' => $curriculumid, 'yearid' => $yearid, 'userid' => $userid));

            if (!empty($yearcompletionstatus)) {
                $yearcompletionstatus->completion_status = 1;
                $yearcompletionstatus->completiondate = time();
                $yearcompletionstatus->usermodified = $USER->id;
                $yearcompletionstatus->timemodified = time();
                $DB->update_record('local_ccuser_year_signups', $yearcompletionstatus);
                $notifications_exists = $DB->record_exists('local_notification_type', array('shortname' => 'program_year_completion'));
                if($notifications_exists){
                  $emaillogs = new programnotifications_emails();
                  $email_logs = $emaillogs->curriculum_emaillogs('program_year_completion', $yearcompletionstatus, $userid, $USER->id);
                }
            }
        }
        return true;
    }
    /**
     * [mycompletedsemesters description]
     * @method mycompletedsemesters
     * @param  [type]            $curriculumid [description]
     * @param  [type]            $userid     [description]
     * @return [type]                        [description]
     */
    public function mycompletedsemesters($curriculumid, $userid) {
        global $DB;
        $mycompletedsemesters = array();
        $mycompletedsemesterssql = "SELECT semesterids
                                   FROM {local_curriculum_users}
                                  WHERE curriculumid = :curriculumid AND userid = :userid ";
        $mycompletedsemesterslist = $DB->get_field_sql($mycompletedsemesterssql,
            array('curriculumid' => $curriculumid, 'userid' => $userid));
        if (!empty($mycompletedsemesterslist)) {
            $mycompletedsemesters = explode(',', $mycompletedsemesterslist);
        }
        return $mycompletedsemesters;
    }
    /**
     * [mycompletedsemesters description]
     * @method mycompletedsemesters
     * @param  [type]            $curriculumid [description]
     * @param  [type]            $userid     [description]
     * @return [type]                        [description]
     */
    public function mycompletedsemesteryears($curriculumid, $userid) {
        global $DB;
        $mycompletedsemesteryears = array();
        $mycompletedsemesteryearssql = "SELECT years
                                   FROM {local_curriculum_users}
                                  WHERE curriculumid = :curriculumid AND userid = :userid ";
        $mycompletedsemesteryearslist = $DB->get_field_sql($mycompletedsemesteryearssql,
            array('curriculumid' => $curriculumid, 'userid' => $userid));
        if (!empty($mycompletedsemesteryearslist)) {
            $mycompletedsemesteryears = explode(',', $mycompletedsemesteryearslist);
        }
        return $mycompletedsemesteryears;
    }
    /**
     * [mycompletedsemestercourses description]
     * @method mycompletedsemestercourses
     * @param  [type]                  $bcsemester [description]
     * @return [type]                           [description]
     */
    public function mycompletedsemestercourses($bcsemester) {
        global $DB, $USER;
        $curriculumid = $bcsemester->curriculumid;
        $semesterid = $bcsemester->semesterid;
        $courses = $DB->get_fieldset_select('local_cc_semester_courses', 'id', 'curriculumid = :curriculumid AND semesterid = :semesterid ORDER BY position ASC',
            array('curriculumid' => $curriculumid, 'semesterid' => $semesterid));
        $mycoursecomptllist = $DB->get_field('local_cc_semester_cmptl', '*',
            array('curriculumid' => $curriculumid, 'semesterid' => $semesterid, 'userid' => $USER->id));
        $mycoursecomptl = explode(',', $mycoursecomptllist);
        return array($courses, $mycoursecomptl);
    }
    /**
     * [mynextsemestercourses description]
     * @method mynextsemestercourses
     * @param  [type]             $bcsemester [description]
     * @return [type]                      [description]
     */
    public function mynextsemestercourses($bcsemester){
        list($courses, $mycoursecomptl) = $this->mycompletedsemestercourses($bcsemester);
        $notcmptlcourses = array_values(array_diff($courses, $mycoursecomptl));
        return $notcmptlcourses;
    }
    /**
     * [mycompletedveles description]
     * @method mycompletedveles
     * @param  [type]           $curriculumid [description]
     * @return [type]                       [description]
     */
    public function mysemestersandcompletedsemesters($curriculumid) {
        global $DB, $USER;
        $semesters = $DB->get_fieldset_select('local_curriculum_semesters', 'id', 'curriculumid = :curriculumid ORDER BY id ASC',
            array('curriculumid' => $curriculumid));
        $mysemestercomptllist = $DB->get_field('local_curriculum_users', 'semesterids',
            array('curriculumid' => $curriculumid, 'userid' => $USER->id));
        $mysemestercomptl = explode(',', $mysemestercomptllist);
        return array($semesters, $mysemestercomptl);

    }
    /**
     * [mynextsemesters description]
     * @method mynextsemesters
     * @param  [type]       $curriculumid [description]
     * @return [type]                   [description]
     */
    public function mynextsemesters($curriculumid) {
        global $DB, $USER;
        list($semesters, $mysemestercomptl) = $this->mysemestersandcompletedsemesters($curriculumid, $USER->id);
        $notcmptlsemesters = array_values(array_diff($semesters, $mysemestercomptl));
        return $notcmptlsemesters;
    }
    /**
     * [mynextsemesters description]
     * @method mynextsemesters
     * @param  [type]       $curriculumid [description]
     * @return [type]                   [description]
     */
    public function mynextsemesteryears($curriculumid) {
        global $DB, $USER;
    }

    /**
     * [myattendedsessions description]
     * @method myattendedsessions
     * @param  [type]             $bclcdata [description]
     * @return [type]                       [description]
     */
    public function myattendedsessions($bclcdata, $lastsession = false) {
        global $DB, $USER;
        // print_object($bclcdata);
        $myattendedsessionssql = "SELECT bss.*, bcs.timestart, bcs.timefinish
                                    FROM {local_cc_session_signups} bss
                                    JOIN {local_cc_course_sessions} bcs ON bcs.id = bss.sessionid
                                   WHERE bss.curriculumid = :curriculumid AND bss.semesterid = :semesterid AND bss.userid = :userid
                                   AND bss.completion_status IN (1,2)
                                   AND bss.enrolstatus = :enrolstatus";
        if($bclcdata->ccses_action == "semsessions"){
            $myattendedsessionssql .= " AND bss.bclcid = 0";
        }
        if ($lastsession) {
            $myattendedsessionssql .= " ORDER BY bss.id DESC LIMIT 0, 1";
        }
        if ($lastsession) {
            $mylastattendedsession = $DB->get_record_sql($myattendedsessionssql, array('curriculumid' => $bclcdata->curriculumid, 'semesterid' => $bclcdata->semesterid,
                'userid' => $USER->id, 'enrolstatus' => 1));
            return $mylastattendedsession;

        } else {
            $myattendedsessions = $DB->get_records_sql($myattendedsessionssql, array('curriculumid' => $bclcdata->curriculumid, 'semesterid' => $bclcdata->semesterid,
                'userid' => $USER->id, 'enrolstatus' => 1));
            return $myattendedsessions;
        }
    }
    /**
     * [manage_curriculum_session_trainers description]
     * @method manage_curriculum_session_trainers
     * @param  [type]                           $bcsession [description]
     * @param  [type]                           $action    [description]
     * @return [type]                                      [description]
     */
    public function manage_curriculum_session_trainers($bcsession, $action) {
        global $DB, $USER,$CFG;
        if (file_exists($CFG->dirroot . '/local/lib.php')) {
            require_once($CFG->dirroot . '/local/lib.php');
        }
        // print_object($bcsession);
        /*$bcsemestercourse = $DB->get_field('local_cc_semester_courses', 'courseid',
            array('curriculumid' => $bcsession->curriculumid, 'semesterid' => $bcsession->semesterid, 'id' => $bcsession->bclcid));*/
          $bcsemestercourse = $bcsession->courseid;
          // print_object($bcsemestercourse);
        switch ($action) {
            case 'insert':
                $type = 'curriculum_enrol';
                $fromuserid = $USER->id;
                $string = 'trainer';
                $enrolbcsessionuser = $this->manage_bcsemester_course_enrolments(
                    $bcsemestercourse, $bcsession->trainerid,
                    'editingteacher', 'enrol');
                // $email_logs = emaillogs($type, $curriculumid, $bcsession->trainerid,
                //     $fromuserid, $string);
            break;
            case 'update':
            break;
            case 'delete';
                $enrolsamecoursessql = "SELECT *
                                          FROM {local_cc_course_sessions}
                                         WHERE trainerid = :oldtrainerid
                                         AND id != :sessionid";

                $enrolsamecourses = $DB->get_record_sql($enrolsamecoursessql,
                    array('oldtrainerid' => $session->oldtrainerid,
                        'sessionid' => $session->id));
                if (!empty($enrolsamecourses) || $session->oldtrainerid == $session->trainerid) {
                    continue;
                }
                $type = 'curriculum_unenroll';
                $fromuserid = $USER->id;
                $string = 'trainer';
                $enrolbcsessionuser = $this->manage_bcsemester_course_enrolments(
                    $bcsemestercourse, $bcsession->trainerid, 'editingteacher',
                    'unenrol');
                // $email_logs = emaillogs($type, $curriculumid, $bcsession->oldtrainerid,
                //     $fromuserid, $string);
            break;
            case 'all':
                $this->manage_curriculum_session_trainers($bcsession, 'insert');
                $this->manage_curriculum_session_trainers($bcsession, 'update');
                $this->manage_curriculum_session_trainers($bcsession, 'delete');
            break;
            case 'default':
            break;
        }
        return true;
    }
    public function manage_curriculum_programs($program, $copyprogram=false) {
        global $DB, $USER;
        $systemcontext = context_system::instance();
        $program->description = $program->program_description['text'];
        try {
            if ($program->id > 0) {
                $program->usermodified = $USER->id;
                $program->timemodified = time();
                $program->program_logo = $program->program_logo;
                file_save_draft_area_files($program->program_logo, $systemcontext->id, 'local_program', 'program_logo', $program->program_logo);
                $program->short_description = $program->short_description;
                $programcurr = $DB->get_field('local_program','curriculumid',array('id'=>$program->id));
                if(empty($program->curriculumid)){
                   $program->curriculumid = $programcurr;
                }
                $parentid=$DB->update_record('local_program', $program);

               $DB->execute("UPDATE {local_program} SET admissionstartdate='$program->admissionstartdate',admissionenddate='$program->admissionenddate',validtill='$program->validtill',pre_requisites='$program->pre_requisites' WHERE parentid = $program->id");

                if($program->editabel==0){
                    $DB->execute("UPDATE {local_program} SET fullname = :fullname,shortname=:shortname,shortcode=:shortcode,year=:year,admissionstartdate = :admissionstartdate,admissionenddate=:admissionenddate,validtill=:validtill,description=:description WHERE parentid=:parentid and costcenter <> :costcenter", array('fullname' =>$program->fullname,'shortname'=>$program->shortname,'shortcode'=>$program->shortcode,'year'=>$program->year,'admissionstartdate' =>$program->admissionstartdate,'admissionenddate'=>$program->admissionenddate,'validtill'=>$program->validtill,'description'=>$program->description,'parentid'=>$program->id,'costcenter'=>$program->costcenter));
                }
                /*$curriculumid=$DB->get_field('local_curriculum','id',  array('program'=>$program->id));
                if($curriculumid){*/
                    $program->id = $program->id;
                    $program->costcenter = $program->costcenter;
                    $DB->update_record('local_curriculum', $program);
                // }
                $programid = $program->id;
                $params = array(
                'context' => context_system::instance(),
                'objectid' => $program->id
                );
                // Trigger curriculum updated event.

                $event = \local_program\event\program_updated::create($params);
                $event->add_record_snapshot('local_program', $program);
                $event->trigger();

            } else {
                $program->usercreated = $USER->id;
                $program->timecreated = time();
                if($program->parentid != 0 || $program->parentid != null){
                    $program->publishstatus = 0;
                }
                $program->program_logo = $program->program_logo;
                file_save_draft_area_files($program->program_logo, $systemcontext->id, 'local_program', 'program_logo', $program->program_logo);
                $program->short_description = $program->short_description;
// <mallikarjun> - ODL-711 insert college id in programs -- starts
        if($program->open_univdept_status == 1){
            $program->departmentid = $program->open_collegeid;
//        }else{
//            $program->departmentid = $program->departmentid;
        }
// <mallikarjun> - ODL-711 insert college id in programs -- ends
                $programid = $DB->insert_record('local_program', $program);

                $params = array(
                    'context' => context_system::instance(),
                    'objectid' => $programid
                );

                $event = \local_program\event\program_created::create($params);
                $event->add_record_snapshot('local_program', $program);
                $event->trigger();
                $oldcurriculumid = $program->curriculumid;
                $departmentid = $program->departmentid;
                if(!$copyprogram){
                    $newcurriculumid = $this->copy_curriculum_instance($programid,$oldcurriculumid,$departmentid);
                }
            }
        } catch (dml_exception $ex) {
            print_error($ex);
        }
        return $programid;
    }
    public function curriculumprograms($stable,$mode=0,$filterparams=array(),$masterprogramid=0) {
        global $DB, $USER;
        $filterparams=(array)json_decode($filterparams,false);

        $params=array();
        $curriculumprograms = array();
        $concatsql = '';
        if (!empty($stable->search)) {
            $fields = array('ps.fullname','ps.shortname','ps.shortcode','ps.year');
            $fields = implode(" LIKE '%" .$stable->search. "%' OR ", $fields);
            $fields .= " LIKE '%" .$stable->search. "%' ";
            $concatsql .= " AND ($fields) ";
        }
        $countsql = "SELECT COUNT(ps.id) ";
        $fromsql = "SELECT ps.*,cc.fullname as costcenterfullname ";
        $sql = " FROM {local_program} as ps
                 JOIN {local_costcenter} cc ON cc.id=ps.costcenter JOIN {local_curriculum} lc ON lc.id = ps.curriculumid WHERE 1=1 ";
        $condition="";
        // Amulya issue 717 filter not working - starts
        $filterparams['organizations'] = ($filterparams['organizations'] == '_qf__force_multiselect_submission') ? [] : $filterparams['organizations'];
        $filterparams['departments'] = ($filterparams['departments'] == '_qf__force_multiselect_submission') ? []: $filterparams['departments'];
        $filterparams['programfaculty'] = ($filterparams['programfaculty'] == '_qf__force_multiselect_submission') ? []: $filterparams['programfaculty'];
        $filterparams['programlevel'] = ($filterparams['programlevel'] == '_qf__force_multiselect_submission') ? []: $filterparams['programlevel'];
        $filterparams['programs'] = ($filterparams['programs'] == '_qf__force_multiselect_submission') ? []: $filterparams['programs'];
        $filterparams['programshortcode'] = ($filterparams['programshortcode'] == '_qf__force_multiselect_submission') ? []: $filterparams['programshortcode'];
        $filterparams['programshortname'] = ($filterparams['programshortname'] == '_qf__force_multiselect_submission') ? []: $filterparams['programshortname'];
        $filterparams['programyear'] = ($filterparams['programyear'] == '_qf__force_multiselect_submission') ? []: $filterparams['programyear'];
        // Amulya issue 717 filter not working - ends
         // Revathi issue 755 filter for college - starts
         $filterparams['colleges'] = ($filterparams['colleges'] == '_qf__force_multiselect_submission') ? []: $filterparams['colleges'];
         // Revathi issue 755 filter for college - ends
        if($masterprogramid){

          $mastercostcenter=$DB->get_field('local_program','costcenter',  array('id'=>$masterprogramid));
          $sql .= " AND ps.parentid =:masterprogramid  AND ps.costcenter =:costcenter";
          $params['masterprogramid'] = $masterprogramid;
          $params['costcenter'] = $mastercostcenter;

        }else{
          if(isset($filterparams['duration'])&&$filterparams['duration']!='-1' && !empty($filterparams['duration'])){

             $sql .= " AND lc.duration =:duration  ";
             $params['duration'] = $filterparams['duration'];

          }if(isset($filterparams['duration_format'])&&$filterparams['duration_format']!='-1' && !empty($filterparams['duration_format'])){

             $sql .= " AND lc.duration_format =:duration_format  ";
             $params['duration_format'] = $filterparams['duration_format'];

          }if(isset($filterparams['departments']) && !empty($filterparams['departments'])){

            list($relateddepartmentssql, $relateddepartmentsparams) = $DB->get_in_or_equal($filterparams['departments'], SQL_PARAMS_NAMED);

            $sql .= " AND ps.departmentid $relateddepartmentssql ";

            $params = $params+$relateddepartmentsparams;
            // Revathi issue 755 filter for college - starts
          }if(isset($filterparams['colleges']) && !empty($filterparams['colleges'])){

            list($relatedcollegessql, $relatedcollegesparams) = $DB->get_in_or_equal($filterparams['colleges'], SQL_PARAMS_NAMED);

            $sql .= " AND ps.departmentid $relatedcollegessql ";

            $params = $params+$relatedcollegesparams;
            // Revathi issue 755 filter for college - ends

          }if(isset($filterparams['organizations']) && !empty($filterparams['organizations'])){

              if($mode==2){

                list($relatedorganizationssql, $relatedorganizationsparams) = $DB->get_in_or_equal($filterparams['organizations'], SQL_PARAMS_NAMED);

                $subparams=array();
                $subparams = $subparams+$relatedorganizationsparams;
                $collegeids = $DB->get_records_sql_menu("SELECT id, id as collegeids FROM {local_costcenter} where parentid $relatedorganizationssql", $subparams);
                if($collegeids){

                    list($relatedcollegessql, $relatedcollegesparams) = $DB->get_in_or_equal($collegeids, SQL_PARAMS_NAMED);

                    $sql .= " AND ps.costcenter $relatedcollegessql ";

                    $params = $params+$relatedcollegesparams;

                }else{
                  $sql .= " AND ps.costcenter=0 ";
                }

              }else{
                list($relatedorganizationssql, $relatedorganizationsparams) = $DB->get_in_or_equal($filterparams['organizations'], SQL_PARAMS_NAMED);

                $sql .= " AND ps.costcenter $relatedorganizationssql ";

                $params = $params+$relatedorganizationsparams;
              }



          }if(isset($filterparams['programfaculty']) && !empty($filterparams['programfaculty'])){

            list($relatedprogramfacultysql, $relatedprogramfacultyparams) = $DB->get_in_or_equal($filterparams['programfaculty'], SQL_PARAMS_NAMED);

            $sql .= " AND ps.facultyid $relatedprogramfacultysql ";

            $params = $params+$relatedprogramfacultyparams;

          }if(isset($filterparams['programlevel']) && !empty($filterparams['programlevel'])){

            list($relatedprogramprogramlevelysql, $relatedprogramprogramlevelparams) = $DB->get_in_or_equal($filterparams['programlevel'], SQL_PARAMS_NAMED);

            $sql .= " AND ps.curriculumsemester $relatedprogramprogramlevelysql ";

            $params = $params+$relatedprogramprogramlevelparams;

          }if(isset($filterparams['programs']) && !empty($filterparams['programs'])){

            list($relatedprogramssql, $relatedprogramsparams) = $DB->get_in_or_equal($filterparams['programs'], SQL_PARAMS_NAMED);

            $sql .= " AND ps.fullname $relatedprogramssql ";

            $params = $params+$relatedprogramsparams;
            // print_r($params);
            // echo "hlo";
            // print_r($filterparams['programs']);
            // echo "test";
            // print_r($filterparams->programs);
            // print_r($relatedprogramssql);
            // print_r($relatedprogramssql);
            // echo $sql; 
            // exit;
          }if(isset($filterparams['programshortcode']) && !empty($filterparams['programshortcode'])){

            list($relatedprogramshortcodeysql, $relatedprogramshortcodeparams) = $DB->get_in_or_equal($filterparams['programshortcode'], SQL_PARAMS_NAMED);

            $sql .= " AND ps.shortcode $relatedprogramshortcodeysql ";

            $params = $params+$relatedprogramshortcodeparams;

          }if(isset($filterparams['programshortname']) && !empty($filterparams['programshortname'])){

            list($relatedprogramshortnameysql, $relatedprogramshortnameparams) = $DB->get_in_or_equal($filterparams['programshortname'], SQL_PARAMS_NAMED);

            $sql .= " AND ps.shortname $relatedprogramshortnameysql ";

            $params = $params+$relatedprogramshortnameparams;

          }if(isset($filterparams['programyear']) && !empty($filterparams['programyear'])){

            list($relatedprogramyearysql, $relatedprogramyearparams) = $DB->get_in_or_equal($filterparams['programyear'], SQL_PARAMS_NAMED);

            $sql .= " AND ps.year $relatedprogramyearysql ";

            $params = $params+$relatedprogramyearparams;

          }if(isset($filterparams['admissionstartdate']) && !empty($filterparams['admissionstartdate'])){

                $start_year=$filterparams['admissionstartdate']->year;
                $start_month=$filterparams['admissionstartdate']->month;
                $start_day=$filterparams['admissionstartdate']->day;
                $start_hour=$filterparams['admissionstartdate']->hour;
                $start_minute=$filterparams['admissionstartdate']->minute;
                $start_second=0;
                $filter_starttime_con=mktime($start_hour, $start_minute, 0, $start_month, $start_day, $start_year);
                $sql.=" AND ps.admissionstartdate >= '$filter_starttime_con' ";

          }if(isset($filterparams['admissionenddate']) && !empty($filterparams['admissionenddate'])){
                $start_year=$filterparams['admissionenddate']->year;
                $start_month=$filterparams['admissionenddate']->month;
                $start_day=$filterparams['admissionenddate']->day;
                $start_hour=$filterparams['admissionenddate']->hour;
                $start_minute=$filterparams['admissionenddate']->minute;
                $start_second=0;
                $filter_starttime_con=mktime($start_hour, $start_minute, 0, $start_month, $start_day, $start_year);
                $sql.=" AND ps.admissionenddate <= '$filter_starttime_con' ";

          }if(isset($filterparams['validstartdate']) && !empty($filterparams['validstartdate'])){

                $start_year=$filterparams['validstartdate']->year;
                $start_month=$filterparams['validstartdate']->month;
                $start_day=$filterparams['validstartdate']->day;
                $start_hour=$filterparams['validstartdate']->hour;
                $start_minute=$filterparams['validstartdate']->minute;
                $start_second=0;
                $filter_starttime_con=mktime($start_hour, $start_minute, 0, $start_month, $start_day, $start_year);
                $sql.=" AND ps.validtill >= '$filter_starttime_con' ";

          }if(isset($filterparams['validenddate']) && !empty($filterparams['validenddate'])){

                $start_year=$filterparams['validenddate']->year;
                $start_month=$filterparams['validenddate']->month;
                $start_day=$filterparams['validenddate']->day;
                $start_hour=$filterparams['validenddate']->hour;
                $start_minute=$filterparams['validenddate']->minute;
                $start_second=0;
                $filter_starttime_con=mktime($start_hour, $start_minute, 0, $start_month, $start_day, $start_year);
                $sql.=" AND ps.validtill <= '$filter_starttime_con' ";
          }

          if (is_siteadmin() || has_capability('local/program:manage_multiorganizations', context_system::instance())){
            if($mode==2){
              $sql .= " AND cc.parentid >0  ";
            }else{
              $sql .= " AND cc.parentid=0 ";
            }

          }else if (!is_siteadmin()&&!has_capability('local/program:manage_multiorganizations', context_system::instance())&&(has_capability('local/program:manageprogram', context_system::instance())) &&
            ((has_capability('local/program:manage_ownorganization', context_system::instance())))) {
                if($mode==2){
                    /*$colleges=$DB->get_field_sql("SELECT GROUP_CONCAT(id) FROM {local_costcenter} where parentid=:parentid",  array('parentid'=>$USER->open_costcenterid),  $strictness=IGNORE_MISSING);
                    $condition.= " AND ( ps.costcenter = :parentcostcenter)";
                    $params['parentcostcenter'] = $colleges;*/// Existing code commented by Harish //

                    $colleges = $DB->get_records_sql_menu("SELECT id,id as cid FROM {local_costcenter} where parentid=:parentid",  array('parentid'=>$USER->open_costcenterid),  $strictness=IGNORE_MISSING);
                    if(!empty($colleges)){
                        list($relatedprogramyearysql, $relatedprogramyearparams) = $DB->get_in_or_equal($colleges, SQL_PARAMS_NAMED);
                        $condition.= " AND ( ps.costcenter $relatedprogramyearysql)";
                        $params = $params+$relatedprogramyearparams;
                    }else{
                        $sql .= " AND (ps.costcenter = :costcenter)";
                        $params['costcenter'] = $USER->open_costcenterid;
                    }

                    $sql .= " AND cc.parentid > 0 ";
                }else{
                    $sql .= " AND cc.parentid = 0 ";
                    $condition.= " AND (ps.costcenter = :costcenter)";
                    $params['costcenter'] = $USER->open_costcenterid;
                }
                $concatsql .= $condition;

            }elseif (!is_siteadmin()&&!has_capability('local/program:manage_multiorganizations', context_system::instance())&&(has_capability('local/program:manage_owndepartments', context_system::instance()))) {

//                $condition.= " AND (ps.costcenter = :department )";
                $condition.= " AND (ps.departmentid = :department )";
                $params['department'] = $USER->open_departmentid;
                $concatsql .= $condition;

            }elseif (!is_siteadmin()&&!has_capability('local/program:manage_multiorganizations', context_system::instance())&&has_capability('local/program:trainer_viewprogram', context_system::instance())) {
                $mycurriculums = $DB->get_records_menu('local_cc_session_trainers',
                    array('trainerid' => $USER->id), 'programid', 'programid, programid as ps');
                $trainerclroomprograms = $DB->get_records_menu('local_cc_course_sessions',
                    array('trainerid' => $USER->id), 'programid', 'programid, programid as ps');
                if (!empty($mycurriculums)) {
                    if(!empty($trainerclroomprograms)){
                      /*print_object($mycurriculums);
                      print_object($trainerclroomprograms);*/
                        $mycurriculums = array_merge($mycurriculums, $trainerclroomprograms);  
                    }
                    $mycurriculums = implode(',', array_unique($mycurriculums));
                    $concatsql .= " AND ps.id IN ( $mycurriculums )";
                } else {
                    return compact('curriculums', 'curriculumscount');
                }
            }else if (!is_siteadmin() && (has_capability('local/program:viewprogram', context_system::instance()))) {
                
              $mycurriculums = $DB->get_records_menu('local_cc_session_trainers',
                  array('trainerid' => $USER->id), 'programid', 'programid, programid as ps');

                  if (!empty($mycurriculums)) {
                      $mycurriculums = implode(', ', $mycurriculums);
                      $concatsql .= " AND ps.id IN ( $mycurriculums ) ";
                  } else {
                      return compact('curriculums', 'curriculumscount');
                  }

          }
        }
        $sql .= $concatsql;
        try {
            $curriculumprogramscount = $DB->count_records_sql($countsql . $sql, $params);
            if ($stable->thead == false) {
                $sql .= " ORDER BY ps.id DESC";
                $curriculumprograms = $DB->get_records_sql($fromsql . $sql, $params, $stable->start, $stable->length);
            }
        } catch (dml_exception $ex) {
            $curriculumprogramscount = 0;
        }
        // echo $fromsql . $sql;
        // exit;
        return compact('curriculumprograms', 'curriculumprogramscount');
    }

     /**
     * [curriculumsession_capacity_check description]
     * @param  [type] $classroomid [description]
     * @return [type]              [description]
     */
    public function session_capacity_check($curriculumid, $semesterid, $bclcid, $sessionid){
        global $DB;
        $return =false;
        $session_capacity=$DB->get_field('local_cc_course_sessions','maxcapacity',array('curriculumid'=>$curriculumid, 'semesterid' => $semesterid, 'bclcid' => $bclcid));
        $sessionenrolledusers=$DB->count_records('local_cc_session_signups',array('curriculumid'=>$curriculumid, 'semesterid' => $semesterid, 'bclcid' => $bclcid, 'sessionid' => $sessionid));
        //if($classroom_capacity <= $enrolled_users){
        //    $return =true;
        //}
        if($session_capacity <= $sessionenrolledusers && !empty($session_capacity) && $session_capacity!=0){
            $return =true;
        }
        return $return;
    }

    /**
     * [curriculum_add_assignusers description]
     * @method curriculum_add_assignusers
     * @param  [type]                    $curriculumid   [description]
     * @param  [type]                    $userstoassign [description]
     * @return [type]                                   [description]
     */
    public function session_add_assignusers($curriculumid, $programid, $yearid, $semesterid, $bclcid, $sessionid, $ccses_action = null, $userstoassign) {
        global $DB, $USER,$CFG;
        if (file_exists($CFG->dirroot . '/local/lib.php')) {
            require_once($CFG->dirroot . '/local/lib.php');
        }
        $allow = true;
        // print_object($programid);
        // $type = 'session_enrol';
        // $dataobj = $curriculumid;
        // $sessionid = $sessionid;
        // $fromuserid = $USER->id;
        // print_object($userstoassign);exit;
        if ($allow) {
            foreach ($userstoassign as $key => $adduser) {
                $session_capacity_check=$this->session_capacity_check($curriculumid, $semesterid, $bclcid, $sessionid);
                if(!$session_capacity_check){
                    $curriculumuser = new stdClass();
                    $curriculumuser->curriculumid = $curriculumid;
                    $curriculumuser->programid = $programid;
                    $curriculumuser->yearid = $yearid;
                    $curriculumuser->semesterid = $semesterid;
                    $curriculumuser->bclcid = $bclcid;
                    $curriculumuser->sessionid = $sessionid;
                    $curriculumuser->enrol = 1;
                    $curriculumuser->userid = $adduser;
                    $curriculumuser->ccses_action = $ccses_action;
                    try {
                        $this->bc_session_enrolments($curriculumuser);
                    }catch (dml_exception $ex) {
                        print_error($ex);
                    }
                } else {
                    break;
                }
            }
        }
        return true;
    }

    /**
     * [curriculum_add_assignusers description]
     * @method curriculum_add_assignusers
     * @param  [type]                    $curriculumid   [description]
     * @param  [type]                    $userstoassign [description]
     * @return [type]                                   [description]
     */
    public function session_remove_assignusers($curriculumid, $programid, $yearid, $semesterid, $bclcid, $sessionid, $ccses_action = null, $userstoassign) {
        global $DB, $USER,$CFG;
        if (file_exists($CFG->dirroot . '/local/lib.php')) {
            require_once($CFG->dirroot . '/local/lib.php');
        }
        $allow = true;
        // $type = 'session_enrol';
        // $dataobj = $curriculumid;
        // $sessionid = $sessionid;
        // $fromuserid = $USER->id;
        if ($allow) {
            foreach ($userstoassign as $key => $adduser) {
                if (true) {
                    $curriculumuser = new stdClass();
                    $curriculumuser->curriculumid = $curriculumid;
                    $curriculumuser->programid = $programid;
                    $curriculumuser->yearid = $yearid;
                    $curriculumuser->semesterid = $semesterid;
                    $curriculumuser->bclcid = $bclcid;
                    $curriculumuser->sessionid = $sessionid;
                    $curriculumuser->enrol = 3;
                    $curriculumuser->userid = $adduser;
                    $curriculumuser->ccses_action = $ccses_action;
                    try {
                        $this->bc_session_enrolments($curriculumuser);
                    }catch (dml_exception $ex) {
                        print_error($ex);
                    }
                } else {
                    break;
                }
            }
        }
        return true;
    }

    /**
     * [select_to_and_from_users description]
     * @param  [type]  $type       [description]
     * @param  integer $curriculumid [description]
     * @param  [type]  $params     [description]
     * @param  integer $total      [description]
     * @param  integer $offset1    [description]
     * @param  integer $perpage    [description]
     * @param  integer $lastitem   [description]
     * @return [type]              [description]
     */
    public function select_to_and_from_users_sessions($type = null, $curriculumid = 0, $programid = 0, $semesterid=0, $bclcid,  $sessionid = 0, $ccses_action = null, $params, $total = 0, $offset1 = -1, $perpage = -1, $lastitem = 0) {
        // print_object($params);exit;
        global $DB, $USER;
        // $curriculumusers = $DB->get_records('local_curriculum_users', array('curriculumid' => $curriculumid));
        // $curriculumusersids = array();
        // foreach($curriculumusers as $curriculumuser){
        //   $curriculumusersids[] = $curriculumuser->userid;
        // }
        // $bcuserids = implode(',', $curriculumusersids);
        $params['suspended'] = 0;
        $params['deleted'] = 0;

        if ($total == 0) {
            $sql = "SELECT u.id, concat(u.firstname,' ',u.lastname,' ','(',u.email,')',' ','(',u.open_employeeid,')') as fullname";
        } else {
            $sql = "SELECT count(u.id) as total";
        }
        $sql .= " FROM {user} AS u
                  JOIN {local_curriculum_users} as bcu
                 WHERE  u.id > 2 AND bcu.curriculumid = $curriculumid AND u.id = bcu.userid AND u.suspended = :suspended
                                     AND u.deleted = :deleted ";
        if ($lastitem != 0) {
            $sql.=" AND u.id > $lastitem";
        }
        // if ((has_capability('local/program:manageprogram', context_system::instance())) && (!is_siteadmin() && (!has_capability('local/program:manage_multiorganizations', context_system::instance()) && !has_capability('local/costcenter:manage_multiorganizations', context_system::instance())))) {
        //     $sql .= " AND u.open_costcenterid = :costcenter";
        //     $params['costcenter'] = $USER->open_costcenterid;
        //     if ((has_capability('local/program:manage_owndepartments', context_system::instance()) || has_capability('local/costcenter:manage_owndepartments', context_system::instance()))) {
        //         $sql .= " AND u.open_departmentid = :department";
        //         $params['department'] = $USER->open_departmentid;
        //     }
        // }
        $sql .= " AND u.id <> $USER->id ";
        if (!empty($params['email'])) {
            $sql .= " AND u.id IN ({$params['email']})";
        }
        if (!empty($params['uname'])) {
            $sql .= " AND u.id IN ({$params['uname']})";
        }
        // if (!empty($params['department'])) {
        //     $sql .= " AND u.open_departmentid IN ({$params['department']})";
        // }
        // if (!empty($params['organization'])) {
        //     $sql .= " AND u.open_costcenterid IN ({$params['organization']})";
        // }
        if (!empty($params['idnumber'])) {
            $sql .= " AND u.id IN ({$params['idnumber']})";
        }

        if (!empty($params['groups'])) {
            $sql .= " AND u.id IN (select cm.userid from {cohort_members} cm, {user} u where u.id = cm.userid AND u.deleted = 0 AND u.suspended = 0 AND cm.cohortid IN ({$params['groups']}))";
        }
        if ($type == 'add') {
            $sql .= " AND u.id NOT IN (SELECT lcu.userid as userid
                                       FROM {local_cc_session_signups} AS lcu
                                       WHERE lcu.curriculumid = $curriculumid AND lcu.bclcid = $bclcid AND lcu.sessionid=$sessionid)";
              // echo $sql;exit;
        } else if ($type == 'remove') {
            $sql .= " AND u.id IN (SELECT lcu.userid as userid
                                       FROM {local_cc_session_signups} AS lcu
                                       WHERE lcu.curriculumid = $curriculumid AND lcu.sessionid = $sessionid)";
        }

        // $sql .= " AND u.id NOT IN (SELECT lcu.trainerid as userid
        //                                FROM {local_cc_session_signups} AS lcu
        //                                WHERE lcu.curriculumid = $curriculumid)";

        $order = ' ORDER BY u.id ASC ';
        if ($perpage != -1) {
            $order .= "LIMIT $perpage";
        }
        /*print_object($sql . $order);
        print_object($params);exit;*/
        if ($total == 0) {
            $availableusers = $DB->get_records_sql_menu($sql . $order, $params);
        } else {
            $availableusers = $DB->count_records_sql($sql, $params);
        }
        // print_object($availableusers);
        return $availableusers;
    }
    public function curriculumsemesteryear($curriculumid, $yearid, $userid = null) {
        global $DB, $USER;
        // changes by harish for IUMS-377 starts here //
        if($userid){
            $curriculumsemesters = $DB->get_records('local_curriculum_semesters', array('curriculumid' => $curriculumid, 'yearid' => $yearid), '', '*, id as semesterid');
            $semesters = array();
            foreach ($curriculumsemesters as $key => $value) {
                $semcompletionstatus = $DB->get_field('local_cc_semester_cmptl', 'completion_status', array('curriculumid' => $curriculumid, 'yearid' => $yearid, 'semesterid' => $key, 'userid' => $userid));
                $value->semcompletionstatus = ($semcompletionstatus) ? $semcompletionstatus : 0;
                $semesters[$key] = $value;
            }
        }else{
            $semesters = $DB->get_records('local_curriculum_semesters', array('curriculumid' => $curriculumid, 'yearid' => $yearid), '', '*, id as semesterid');
        }
        // changes by harish for IUMS-377 ends here //
        return $semesters;

    }
    public function curriculumsemesteryearcourses($curriculumid, $yearid) {
        global $DB, $USER;
        $semestercoursess = $DB->get_records_sql('SELECT lcs.*, lcs.id as semesterid, ccsc.courseid, c.fullname
                                             FROM {local_curriculum_semesters} lcs
                                        LEFT JOIN {local_cc_semester_courses} ccsc ON ccsc.semesterid = lcs.id
                                        LEFT JOIN {course} c ON c.id = ccsc.courseid
                                        WHERE lcs.curriculumid = :curriculumid AND lcs.yearid = :yearid ', array('curriculumid' => $curriculumid, 'yearid' => $yearid));
        return $semestercoursess;

    }
    public function addfaculty($facultydata) {
      global $DB, $CFG, $USER;
      $pluginname = 'program';
      $enrolmethod = enrol_get_plugin($pluginname);
      $roleid = $DB->get_field('role', 'id', array('shortname' => 'faculty'));
      $instance = $DB->get_record('enrol', array('courseid' => $facultydata->courseid, 'enrol' => $pluginname), '*', MUST_EXIST);
      foreach ($facultydata->faculty as $faculty) {
        $facultydata->trainerid = $faculty;
        $this->cc_course_faculty_enrolments($facultydata);
        $this->manage_program_curriculum_course_enrolments($faculty, $roleid, 'enrol', $enrolmethod, $instance);
      }
      return true;
    }

    public function addclassroom($classroomdata){
        global $DB, $CFG, $USER;
        try {
            if ($classroomdata->id > 0) {
                $classroomdata->usermodified = $USER->id;
                $classroomdata->timemodified = time();
                if($classroomdata->nomination_startdate){
                    $classroomdata->startdate = $classroomdata->nomination_startdate;
                }
                if($classroomdata->nomination_enddate){
                    $classroomdata->enddate = $classroomdata->nomination_enddate;
                }
                $res = $DB->update_record('local_cc_semester_classrooms', $classroomdata);
                
                if($classroomdata->existingclassroomtype != $classroomdata->classroom_type){
                    $deleteexistingsessions = $this->deleteclassroomsession($classroomdata);
                }
                if($classroomdata->classroom_type == 1 && $classroomdata->id && empty($classroomdata->attendancemapped)){
                    if($classroomdata->existingdailystartdate != $classroomdata->nomination_startdate || $classroomdata->existingdailyenddate != $classroomdata->nomination_enddate){
                        $deleteexistingsessions = $this->deleteclassroomsession($classroomdata);
                    }
                    $fixedsessions = $this->manage_classroom_automatic_sessions($classroomdata);
                }else{
                    if($classroomdata->classroom_type == 1 && $res){
                        /*$DB->execute("UPDATE {local_cc_course_sessions} SET 
                                        trainerid = ".$classroomdata->trainerid.",
                                        instituteid = ".$classroomdata->instituteid.",
                                        institute_type = ".$classroomdata->institute_type.",
                                        roomid = ".$classroomdata->room.",
                                        capacity = ".$classroomdata->maxcapacity.",
                                        maxcapacity = ".$classroomdata->maxcapacity.",
                                        mincapacity = ".$classroomdata->mincapacity." WHERE bclcid = ".$classroomdata->id);*///Existing query before removing max & min capacity//
                        $DB->execute("UPDATE {local_cc_course_sessions} SET 
                                        trainerid = ".$classroomdata->trainerid.",
                                        instituteid = ".$classroomdata->instituteid.",
                                        institute_type = ".$classroomdata->institute_type.",
                                        roomid = ".$classroomdata->room." WHERE bclcid = ".$classroomdata->id);

                    }
                }

                $params = array(
                    'context' => context_system::instance(),
                    'objectid' => $classroomdata->id,
                    'other' =>array('programid' => $classroomdata->programid, 'curriculumid' => $classroomdata->curriculumid)
                );

                /*$event = \local_program\event\classroom_updated::create($params);
                $event->add_record_snapshot('local_cc_semester_classrooms', $classroomdata);
                $event->trigger();*/
            } else {
                    if($classroomdata->nomination_startdate){
                        $classroomdata->startdate = $classroomdata->nomination_startdate;
                    }
                    if($classroomdata->nomination_enddate){
                        $classroomdata->enddate = $classroomdata->nomination_enddate;
                    }
                    $classroomdata->usercreated = $USER->id;
                    $classroomdata->timecreated = time();
                    $classroomdata->id = $DB->insert_record('local_cc_semester_classrooms', $classroomdata);
                    // $classroomdata->id = 8;
                    if($classroomdata->classroom_type == 1 && $classroomdata->id && empty($classroomdata->attendancemapped)){
                        $fixedsessions = $this->manage_classroom_automatic_sessions($classroomdata);
                    }
                    $params = array(
                        'context' => context_system::instance(),
                        'objectid' => $classroomdata->id,
                        'other' =>array('programid' => $classroomdata->programid, 'curriculumid' => $classroomdata->curriculumid)
                    );

                    /*$event = \local_program\event\classroom_created::create($params);
                    $event->add_record_snapshot('local_cc_semester_classrooms', $classroomdata);
                    $event->trigger();*/
            }
        } catch (dml_exception $ex) {
            print_error($ex);
        }
        return $classroomdata->id;
        // return true;
    }

    public function deleteclassroomsession($classroomdata){
        global $DB;
        $deleteccsessions = $DB->execute("DELETE FROM {local_cc_course_sessions} WHERE curriculumid = $classroomdata->curriculumid AND programid = $classroomdata->programid AND yearid = $classroomdata->yearid AND semesterid = $classroomdata->semesterid AND bclcid = $classroomdata->id");
        /*$deleteccsignups = $DB->execute("DELETE FROM {local_cc_session_signups} WHERE curriculumid = $classroomdata->curriculumid AND programid = $classroomdata->programid AND yearid = $classroomdata->yearid AND semesterid = $classroomdata->semesterid AND bclcid = $classroomdata->id");*/
        $deleteccsignups = $DB->execute("DELETE FROM {local_cc_session_signups} WHERE curriculumid = $classroomdata->curriculumid AND programid = $classroomdata->programid AND semesterid = $classroomdata->semesterid AND bclcid = $classroomdata->id");
        return true;
    }

    // Automatic session creations starts here by Harish //
    public function manage_classroom_automatic_sessions($classroomdata /*$classroomid,$classroomstartdate,$classroomenddate*/) {
        global $DB;
        $classroomid = $classroomdata->id;
        $ccses_action = $classroomdata->ccses_action;
        $classroomstartdate = $classroomdata->nomination_startdate;
        $classroomenddate = $classroomdata->nomination_enddate;
        $i=1;

        if($classroomdata->attendancemapped == null || $classroomdata->attendancemapped == 0){
            $start_hours_minutes = $classroomdata->dailysessionstarttime['dailystarttimehours'].':'.$classroomdata->dailysessionstarttime['dailystarttimemins'];
            $finish_hours_minutes = $classroomdata->dailysessionendtime['dailyendtimehours'].':'.$classroomdata->dailysessionendtime['dailyendtimemins'];
            $dailysttime_timestamp = $classroomdata->dailysessionstarttime['dailystarttimehours']*60*60 + $classroomdata->dailysessionstarttime['dailystarttimemins']*60;
            $dailyendtime_timestamp = $classroomdata->dailysessionendtime['dailyendtimehours']*60*60 + $classroomdata->dailysessionendtime['dailyendtimemins']*60;
            $timestart = $classroomdata->timestart+$dailysttime_timestamp;
            $timefinish = $classroomdata->timefinish+$dailyendtime_timestamp;
                
            $first_time = $classroomdata->dailysessionstarttime['dailystarttimehours'].':'.$classroomdata->dailysessionstarttime['dailystarttimemins'];
              
            if($first_time >= $finish_hours_minutes){
                $classroomstartdate=strtotime('+1 day',strtotime(date("Y-m-d",$classroomstartdate)));
                $classroomstartdate=strtotime(date('Y-m-d',$classroomstartdate).' '. $start_hours_minutes);
            }
        }
        $first=strtotime(date("Y-m-d",$classroomstartdate));
        $last=strtotime(date("Y-m-d",$classroomenddate));

        while( $first <= $last ) { 
            $session=new stdClass();
            $session->id=0;
            $session->datetimeknown=1;
            $session->bclcid=$classroomid;
           // $session->nomination_startdate = $classroomdata->nomination_startdate;
           // $session->nomination_enddate = $classroomdata->nomination_enddate;
            // $session->mincapacity=$classroomdata->mincapacity;
            // $session->maxcapacity = $classroomdata->maxcapacity;
            $session->onlinesession=0;
            $session->institute_type = $classroomdata->institute_type;
            $session->instituteid = $classroomdata->instituteid;
            $session->room = $classroomdata->room;
            $session->programid = $classroomdata->programid;
            $session->curriculumid = $classroomdata->curriculumid;
            $session->yearid = $classroomdata->yearid;
            $session->semesterid = $classroomdata->semesterid;
            $session->ccses_action = $ccses_action;
            /*$session->trainerid=$DB->get_field('local_classroom_trainers','trainerid',array('classroomid'=>$classroomid));*/
            $session->trainerid = $classroomdata->trainerid;
            $session->cs_description=array
                                        (
                                        'text' =>"", 
                                        'format' =>1
                                        );

            $date= date('Y-m-d', $first);

            $session->name="Session$i";

            /*$session->timestart=strtotime($date.' '.$start_hours_minutes);
            $session->timefinish=strtotime($date.' '.$finish_hours_minutes);*/
            $session->timestart=$first;
            $session->timefinish=$first;

            if($first==$last){
                // $session->timefinish=strtotime($date.' '.$finish_hours_minutes);
                $session->timefinish=$first;
            }

            $condition = strtotime('+1 day', $first);

            if($i==1){
                // $session->timestart=strtotime($date.' '.$start_hours_minutes);
                $session->timestart=$first;
            }elseif($condition > $last){
                // $session->timefinish=strtotime($date.' '.$finish_hours_minutes);
                $session->timefinish=$first;
            }
            if($classroomdata->attendancemapped == null || $classroomdata->attendancemapped == 0){
                $session->dailysessionstarttime = $classroomdata->dailysessionstarttime;
                $session->dailysessionendtime = $classroomdata->dailysessionendtime;
            }
            $this->manage_bc_courses_sessions($session);
            // $this->manage_classroom_sessions($session);
            $first = strtotime('+1 day', $first );
            $i++;
        }
    }

    public function manage_classroom_sessions($session) {
        global $DB, $USER;
         
        $session->description = $session->cs_description['text'];
        try {
            $sessions_validation_start=$this->sessions_validation($session->classroomid,$session->timestart,$session->id);
             $session->duration=($session->timefinish - $session->timestart)/60;
            if($sessions_validation_start){
                return true;
            }
            $sessions_validation_end=$this->sessions_validation($session->classroomid,$session->timefinish,$session->id);
            if($sessions_validation_end){
                return true;
            }
            if ($session->id > 0) {
                $session->timemodified = time();
                $session->usermodified = $USER->id;
                //print_object($session);exit;
                $DB->update_record('local_classroom_sessions', $session);
                $params = array(
                            'context' => context_system::instance(),
                            'objectid' => $session->id
                     );
                    
                $event = \local_classroom\event\classroom_sessions_updated::create($params);
                $event->add_record_snapshot('local_classroom', $session->classroomid);
                $event->trigger();
                if($session->onlinesession ==1){
                       $online_sessions_integration=new \local_classroom\event\online_sessions_integration();
                         $online_sessions_integration->online_sessions_type($session, $session->id,$type=1,'update');
                }
                $classroom = new stdClass();
                $classroom->id = $session->classroomid;
                $classroom->totalsessions = $DB->count_records('local_classroom_sessions', array('classroomid' => $session->classroomid));
                // $classroom->activesessions = $DB->count_records('local_classroom', array('id' => $classroomid));
                $DB->update_record('local_classroom', $classroom);
                
                //$params = array(
                //    'context' => context_system::instance(),
                //    'objectid' => $session->classroomid
                //);
                //
                //$event = \local_classroom\event\classroom_updated::create($params);
                //$event->add_record_snapshot('local_classroom',$session->classroomid);
                //$event->trigger();
            } else {
                $session->timecreated = time();
                $session->usercreated = $USER->id;
          
                $session->id = $DB->insert_record('local_classroom_sessions', $session);
                $params = array(
                            'context' => context_system::instance(),
                            'objectid' => $session->id
                     );
                    
                $event = \local_classroom\event\classroom_sessions_created::create($params);
                $event->add_record_snapshot('local_classroom', $session->classroomid);
                $event->trigger();
                if ($session->id) {
                    if($session->onlinesession ==1){
                        $online_sessions_integration=new \local_classroom\event\online_sessions_integration();
                         $online_sessions_integration->online_sessions_type($session, $session->id,$type=1,'create');
                    }
                    $classroom = new stdClass();
                    $classroom->id = $session->classroomid;
                    $classroom->totalsessions = $DB->count_records('local_classroom_sessions', array('classroomid' => $session->classroomid));
                    $classroom->activesessions = $DB->count_records('local_classroom_sessions', array('classroomid' => $session->classroomid,'attendance_status'=>1));
                    $DB->update_record('local_classroom', $classroom);
                //$params = array(
                //    'context' => context_system::instance(),
                //    'objectid' => $session->classroomid
                //);
                //
                //$event = \local_classroom\event\classroom_updated::create($params);
                //$event->add_record_snapshot('local_classroom',$session->classroomid);
                //$event->trigger();
                }
            }
        } catch (dml_exception $ex) {
            print_error($ex);
        }
        return $session->id;
    }

    /*public function sessions_validation($classroomid,$sessiondate,$sessionid=0) {
        global $DB;
        $return=false;
        if($classroomid && $sessiondate){
            $params = array();
            $params['classroomid'] = $classroomid;
            $params['sessiondate_start'] = date('Y-m-d H:i',$sessiondate);
            $params['sessiondate_end'] = date('Y-m-d H:i',$sessiondate);

            $sql="SELECT * FROM {local_classroom_sessions} where classroomid=:classroomid and (from_unixtime(timestart,'%Y-%m-%d %H:%i')=:sessiondate_start or from_unixtime(timefinish,'%Y-%m-%d %H:%i')=:sessiondate_end)";
            if($sessionid>0){
                 $sql.=" AND id !=:sessionid ";
                 $params['sessionid'] = $sessionid;
            }
            // print_object($params);
            // echo $sql;
            $return=$DB->record_exists_sql($sql,$params); 

        }

        return $return;
    }*/
    // Automatic session creations ends here by Harish //

    public function cc_course_faculty_enrolments($enroldata) {
      global $DB, $CFG, $USER;
      $studentsignup = $DB->get_record('local_cc_session_trainers', array('programid' => $enroldata->programid, 'curriculumid' => $enroldata->curriculumid, 'yearid' => $enroldata->yearid, 'semesterid' => $enroldata->semesterid, 'courseid' => $enroldata->courseid, 'trainerid' => $enroldata->trainerid));
      if (empty($studentsignup)) {
        $signupdata = new stdClass();
        $signupdata->trainerid = $enroldata->trainerid;
        $signupdata->courseid = $enroldata->courseid;
        $signupdata->semesterid = $enroldata->semesterid;
        $signupdata->yearid = $enroldata->yearid;
        $signupdata->curriculumid = $enroldata->curriculumid;
        $signupdata->programid = $enroldata->programid;
        $signupdata->feedback_id = 0;
        $signupdata->usercreated = $USER->id;
        $signupdata->timecreated = time();
        $DB->insert_record('local_cc_session_trainers', $signupdata);
        $notifications_exists = $DB->record_exists('local_notification_type', array('shortname' => 'program_cc_year_faculty_enrol'));
        if($notifications_exists){
          $emaillogs = new programnotifications_emails();
          $email_logs = $emaillogs->curriculum_emaillogs('program_cc_year_faculty_enrol', $signupdata, $signupdata->trainerid, $USER->id);
        }
      }
      return true;
    }
    public function manage_program_curriculum_course_enrolments($user, $roleid,
        $type, $enrolmethod, $instance) {
        global $DB;
        if (!empty($instance)) {
            if ($type == 'enrol') {
                $enrolmethod->enrol_user($instance, $user, $roleid, time());
            } else if ($type == 'unenrol') {
                $enrolmethod->unenrol_user($instance, $user, $roleid, time());
            }
        }
        return true;
    }
    public function addstudent($studentdata) {
      global $DB, $CFG, $USER;
      $pluginname = 'program';
      $params = array();
      $semestercoursessql = 'SELECT c.id, c.id as courseid
                               FROM {course} c
                               JOIN {local_cc_semester_courses} ccsc ON ccsc.courseid = c.id
                              WHERE ccsc.yearid = :yearid ';
      $params['yearid'] = $studentdata->yearid;
      $semestercourses = $DB->get_records_sql($semestercoursessql, $params);
      $enrolmethod = enrol_get_plugin($pluginname);
      $roleid = $DB->get_field('role', 'id', array('shortname' => 'student'));
      foreach ($semestercourses as $semestercourse) {
        $instance = $DB->get_record('enrol', array('courseid' => $semestercourse->courseid, 'enrol' => $pluginname), '*', MUST_EXIST);
        if (!empty($instance)) {
          foreach ($studentdata->students as $student) {
            $studentdata->student = $student;
            $programuser = $DB->record_exists('local_curriculum_users', array('curriculumid' => $studentdata->curriculumid, 'userid' => $student));
            if (!$programuser) {
            
              $programuser = $this->enrolusertoprogram($studentdata->programid, $studentdata->curriculumid, $student);
            }
            if ($programuser) {
              $this->cc_year_enrolments($studentdata);
            }
            $this->manage_program_curriculum_course_enrolments($student, $roleid, 'enrol', $enrolmethod, $instance);
          }
        }
      }

      $this->manage_program_curriculum_classroomsession_enrolments($studentdata);// Added by harish for ODL-316
      return true;
    }
    public function cc_year_enrolments($enroldata) {
      global $DB, $CFG, $USER;
      $studentsignup = $DB->get_record('local_ccuser_year_signups', array('programid' => $enroldata->programid, 'curriculumid' => $enroldata->curriculumid, 'yearid' => $enroldata->yearid, 'userid' => $enroldata->student));
      if(empty($studentsignup)) {
        $signupdata = new stdClass();
        $signupdata->userid = $enroldata->student;
        $signupdata->yearid = $enroldata->yearid;
        $signupdata->curriculumid = $enroldata->curriculumid;
        $signupdata->programid = $enroldata->programid;
        $signupdata->usercreated = $USER->id;
        $signupdata->timecreated = time();
        $DB->insert_record('local_ccuser_year_signups', $signupdata);
        $notifications_exists = $DB->record_exists('local_notification_type',
            array('shortname' => 'program_cc_year_enrol'));
        if($notifications_exists){
          // < Mallikarjun ODL-715 Not getting program enrollment notification --starts>
          $emaillogs = new programnotifications_emails();
          $email_logs = $emaillogs->curriculum_emaillogs('program_cc_year_enrol', $signupdata, $signupdata->userid, $USER->id);
          // < Mallikarjun ODL-715 Not getting program enrollment notification --starts>
        }
      }
      return true;
    }

    public function manage_program_curriculum_classroomsession_enrolments($studentdata){
        global $DB;
        $programid = $studentdata->programid;
        $curriculumid = $studentdata->curriculumid;
        $yearid = $studentdata->yearid;
        
        $semesterclassroomssql = 'SELECT id, id as classroomidd 
                               FROM {local_cc_semester_classrooms}
                              WHERE yearid = :yearid AND programid = :programid AND curriculumid = :curriculumid';
        $params['yearid'] = $yearid;
        $params['programid'] = $programid;
        $params['curriculumid'] = $curriculumid;
        $semesterclassrooms = $DB->get_records_sql_menu($semesterclassroomssql, $params);
        if($semesterclassrooms){
            $classids = implode(',', $semesterclassrooms);
        }
        if($classids){
            $classroomsessions_sql = "SELECT id, bclcid, semesterid
                                        FROM {local_cc_course_sessions}
                                       WHERE bclcid IN ($classids)
                                         AND programid = $programid
                                         AND curriculumid = $curriculumid
                                         AND yearid = $yearid";
            $classroomsessions = $DB->get_records_sql($classroomsessions_sql);
        }
        if($classroomsessions){
          foreach($classroomsessions as $session){
              // foreach($studentdata->students as $student) {
              $enrolusers = $this->session_add_assignusers($curriculumid, $programid, $yearid, $session->semesterid, $session->bclcid, $session->id, $ccses_action, $studentdata->students);
              // }
          }
        }
        return true;
    }
    public function move_universityprogram_instance($programs,$url){
      global $DB, $CFG, $USER,$OUTPUT;

        $progressbar = new \core\progress\display_if_slow(get_string('copyprogramsprogress', 'local_program'));

        $transaction = $DB->start_delegated_transaction();

        $progressbar->start_html();

        $progressbar->start_progress('', count($programs));
        $reurn='';

        $data=array();
        foreach ($programs as $program => $key) {
            $row=array();
            $outreturn=$this->copy_program_instance($program,$collegeid=0,$showfeedback = true,$url,$returntwo=true);

          $localcostcenter=$DB->get_field('local_program','costcenter',array('id'=>$program));

           $collegestoassign=$DB->get_field_sql('SELECT count(id) FROM {local_program} where parentid=:programid AND costcenter <>:costcenter',  array('programid'=>$program,'costcenter'=>$localcostcenter));

            if($outreturn && $collegestoassign){
              $reurn.=$outreturn['programnotification'];
              if($outreturn['programid']){
                $newprogramid=$outreturn['programid'];
                $row[]=html_writer::empty_tag('input', array('type' => 'checkbox', 'name' => 'collegeprogram['.$program.']','value'=>$newprogramid));

                $row[]= $DB->get_field('local_program','fullname',  array('id'=>$newprogramid));

                $data[]=$row;
              }
            }
        }

      $progressbar->end_html();

      $transaction->allow_commit();

      $result=new stdClass();
      $result->changecount=count($programs);

      $returnout=$reurn;
      $returnout.=$OUTPUT->notification(get_string('copyprogramssuccess', 'local_program',$result),'success');

      if(!empty($data)){
        $table = new html_table();
        $table->data =$data;
        $table->id ='copyprogramcollege';
        $table->head = array(get_string("collegeselection", "local_program"),get_string('program', 'local_program'));

        $returnout .= html_writer::table($table);
      }else{

          $button = new \single_button($url, get_string('click_continue','local_program'), 'get', true);
          $button->class = 'continuebutton';
          $returnout.=$OUTPUT->render($button);

          echo $returnout;
          die();
      }

      return $returnout;

    }

    public function copy_curriculum_instance($programid,$oldcurriculumid,$departmentid){
      global $USER,$DB, $CFG;
      require_once($CFG->dirroot. '/course/lib.php');
     // echo "testing";
      if(empty($oldcurriculumid)){
       $programs = $DB->get_record_sql("SELECT id,fullname,shortname,costcenter,admissionstartdate,admissionenddate,departmentid FROM {local_program} WHERE id = $programid");
            $programsdata = new stdClass();
            $programsdata->name = $programs->fullname;
            $programsdata->shortname = $programs->shortname;
            $programsdata->program = $programs->id;
            $programsdata->costcenter = $programs->costcenter;
            $programsdata->department = $programs->departmentid;
            $programsdata->status = 0;
            $programsdata->startdate = $programs->admissionstartdate;
            $programsdata->enddate = $programs->admissionenddate;
            $programsdata->usercreated = $USER->id;
            $programsdata->timecreated = time();
            $progradatacurriculum = $DB->insert_record('local_curriculum',$programsdata);
            //update curriculumid in program table
            $updatecurriculum = new stdClass();
            $updatecurriculum->curriculumid = $progradatacurriculum;
            $updatecurriculum->id= $programs->id;
            $updatedcurriculum = $DB->update_record('local_program',$updatecurriculum);
            
             $params = array(
                        'context' => context_system::instance(),
                        'objectid' => $curriculum->id
                    );

                    $event = \local_curriculum\event\curriculum_created::create($params);
                    $event->add_record_snapshot('local_curriculum', $curriculum);
                    $event->trigger();

      }else{
        $conlecurriculum = $DB->get_record_sql("SELECT lc.* FROM {local_curriculum} AS lc WHERE lc.id = $oldcurriculumid");
          if($conlecurriculum){
                $conlecurriculum->costcenter=$conlecurriculum->costcenter;
                $conlecurriculum->department=$conlecurriculum->department;
                $conlecurriculum->cr_description['text'] = $conlecurriculum->description;
                $conlecurriculum->program=$programid;
                $oldcurriculumid=$conlecurriculum->id;
                $conlecurriculum->id=0;
                $newcurriculumid = $this->manage_curriculum($conlecurriculum,$copy=true);
                if($newcurriculumid){
                  $conleprogramccyears=$DB->get_records('local_program_cc_years',  array(/*'programid'=>$oldprogramid,*/'curriculumid'=>$oldcurriculumid),$sort='',$fields='*',  $strictness=IGNORE_MISSING);

                   if($conleprogramccyears){
                    // $sequence = 1;
                       foreach ($conleprogramccyears as $key => $conleprogramccyear) {
                          $conleprogramccyear->programid = $programid;
                          $conleprogramccyear->curriculumid = $newcurriculumid;
                          $oldcurriculumyearid=$conleprogramccyear->id;
                          $conleprogramccyear->id=0;

                          $conleprogramccyear->usercreated = $USER->id;
                          $conleprogramccyear->timecreated = time();
                          // $conleprogramccyear->sequence = $sequence;
                          $newconleprogramccyearid = $DB->insert_record('local_program_cc_years', $conleprogramccyear);
                          //$sequence++;
                          $params = array(
                          'context' => context_system::instance(),
                          'objectid' => $newconleprogramccyearid,
                          'other' =>array('curriculumid' => $newcurriculumid)
                          );

                          $event = \local_program\event\year_created::create($params);
                          $event->add_record_snapshot('local_program_cc_years', $newconleprogramccyearid);
                          $event->trigger();

                          if($newconleprogramccyearid){

                              $conleprogramsemesters=$DB->get_records('local_curriculum_semesters',  array(/*'programid'=>$oldprogramid,*/'curriculumid'=>$oldcurriculumid,'yearid'=>$oldcurriculumyearid),$sort='', $fields='*',  $strictness=IGNORE_MISSING);

                            if($conleprogramsemesters){
                                foreach ($conleprogramsemesters as $key => $conleprogramsemester) {
                                    $conleprogramsemester->programid = $programid;
                                    $conleprogramsemester->curriculumid = $newcurriculumid;
                                    $conleprogramsemester->yearid = $newconleprogramccyearid;
                                    $oldsemesterid=$conleprogramsemester->id;
                                    $conleprogramsemester->id=0;

                                   $newcurriculumsemesterid =$this->manage_curriculum_program_semesters($conleprogramsemester);

                                   if($newcurriculumsemesterid){
                                    $conleprogramsemestercourses=$DB->get_records('local_cc_semester_courses',  array(/*'programid'=>$oldprogramid,*/'curriculumid'=>$oldcurriculumid,'yearid'=>$oldcurriculumyearid,'semesterid'=>$oldsemesterid),$sort='',  $fields='*',  $strictness=IGNORE_MISSING);

                                      if($conleprogramsemestercourses){
                                        foreach ($conleprogramsemestercourses as $key => $conleprogramsemestercourse) { 
                                            $conlesemestercourse=$DB->get_record('course',  array('id'=>$conleprogramsemestercourse->courseid),  $fields='*',  $strictness=IGNORE_MISSING);


                                            if($conlesemestercourse){

                                              $conleprogramsemestercourse->programid = $programid;
                                              $conleprogramsemestercourse->curriculumid = $newcurriculumid;
                                              $conleprogramsemestercourse->yearid = $newconleprogramccyearid;
                                              $conleprogramsemestercourse->semesterid = $newcurriculumsemesterid;
                                              $oldcourseid=$conleprogramsemestercourse->courseid;

                                              $conlesemestercourse->shortname=$conlesemestercourse->shortname.'_'.$programid;
                                              $conlesemestercourse->id=0;
                                              $conlesemestercourse->open_parentcourseid=$oldcourseid;
                                              $categoryid = $DB->get_field('local_costcenter','category',array('id' => $departmentid));
                                              $conlesemestercourse->category = $categoryid;
                                              $courseid = create_course($conlesemestercourse);
                                              $parentcourseid = $oldcourseid;
                                              $clonedcourse = $courseid->id;
                                              shell_exec('rm -rf ' . $CFG->dataroot . '/courseimport/' . $clonedcourse . '.mbz ');
                                              $command = 'moosh -n course-backup -f ' . $CFG->dataroot . '/courseimport/' . $clonedcourse . '.mbz ' . $parentcourseid ;
                                              $output = shell_exec($command);
                                              $command1 = 'moosh -n course-restore -e ' . $CFG->dataroot . '/courseimport/' . $clonedcourse . '.mbz ' . $clonedcourse;
                                              $output1 = shell_exec($command1);
                                              shell_exec('rm -rf ' . $CFG->dataroot . '/courseimport/' . $clonedcourse . '.mbz ');
                                              // Course content duplication by Harish ends here //
                                              $conlesemestercourse->id= $clonedcourse;
                                              insert::add_enrol_meathod_tocourse($conlesemestercourse,1);
                                              $conleprogramsemestercourse->courseid = $courseid->id;

                                              $conleprogramsemestercourse->timecreated = time();
                                              $conleprogramsemestercourse->usercreated = $USER->id;

                                              $oldsemestercourseid=$conleprogramsemestercourse->id;
                                              $conleprogramsemestercourse->id=0;
                                              $conleprogramsemestercourse->open_parentcourseid = $oldcourseid;
                                              $conleprogramsemestercourse->importstatus=0;

                                              $newprogramsemestercourseid = $DB->insert_record('local_cc_semester_courses',
                                                  $conleprogramsemestercourse);
                                              $params = array(
                                                  'context' => context_system::instance(),
                                                  'objectid' => $newprogramsemestercourseid,
                                                  'other' => array('curriculumid' => $conleprogramsemestercourse->curriculumid,
                                                                   'semesterid' => $conleprogramsemestercourse->semesterid,
                                                                   'yearid' => $conleprogramsemestercourse->yearid)
                                              );

                                              $event = \local_program\event\bcsemestercourse_created::create($params);
                                              $event->add_record_snapshot('local_cc_semester_courses', $conleprogramsemestercourse);
                                              $event->trigger();

                                              $this->manage_curriculum_semester_completions($conleprogramsemestercourse->curriculumid, $conleprogramsemestercourse->semesterid);

                                              if($newprogramsemestercourseid){
                                                  $conlecoursefaculties=$DB->get_records('local_cc_session_trainers',  array(/*'programid'=>$oldprogramid,*/'curriculumid'=>$oldcurriculumid,'yearid'=>$oldcurriculumyearid,'semesterid'=>$oldsemesterid,'courseid'=>$oldcourseid),$sort='',  $fields='*',  $strictness=IGNORE_MISSING);

                                                  foreach ($conlecoursefaculties as $key => $conlecoursefaculty) {
                                                      $facultyexist=$DB->get_field('user','firstname',array('id'=>$conlecoursefaculty->trainerid,'suspended'=>0,'deleted'=>0));
                                                      if($facultyexist){
                                                        $conlecoursefaculty->programid = $programid;
                                                        $conlecoursefaculty->curriculumid = $newcurriculumid;
                                                        $conlecoursefaculty->yearid = $newconleprogramccyearid;
                                                        $conlecoursefaculty->semesterid = $newcurriculumsemesterid;
                                                        $conlecoursefaculty->courseid = $conleprogramsemestercourse->courseid;
                                                        $conlecoursefaculty->id=0;
                                                        $conlecoursefaculty->faculty=array($conlecoursefaculty->trainerid);
                                                        $this->addfaculty($conlecoursefaculty);
                                                    }
                                                  }
                                              }
                                            }
                                        }
                                          $totalcourses = $DB->count_records('local_cc_semester_courses',
                                          array('curriculumid' => $newcurriculumid, 'semesterid' =>  $newcurriculumsemesterid, 'yearid' => $newconleprogramccyearid));

                                          $semesterdata = new stdClass();
                                          $semesterdata->id =  $newcurriculumsemesterid;
                                          $semesterdata->programid = $programid;
                                          $semesterdata->curriculumid = $newcurriculumid;
                                          $semesterdata->totalcourses = $totalcourses;
                                          $semesterdata->timemodified = time();
                                          $semesterdata->usermodified = $USER->id;
                                          $DB->update_record('local_curriculum_semesters', $semesterdata);
                                          $totalbccourses = $DB->count_records('local_cc_semester_courses',
                                          array('curriculumid' => $newcurriculumid));
                                          $curriculumdata = new stdClass();
                                          $curriculumdata->programid = $programid;
                                          $curriculumdata->id = $newcurriculumid;
                                          $curriculumdata->totalcourses = $totalbccourses;
                                          $curriculumdata->timemodified = time();
                                          $curriculumdata->usermodified = $USER->id;
                                          $DB->update_record('local_curriculum', $curriculumdata);
                                          $updateccid = $this->updateprogram_curriculumid($programid, $newcurriculumid);
                                      }
                                   }
                                }
            
                            }
                          }

                       }
                  }
                }
          }
        }  
              return $newcurriculumid;
    }
    public function copy_program_instance($programid,$collegeid=0,$showfeedback = true,$url,$returntwo=false){
      global $DB, $CFG, $USER,$OUTPUT;
        $reurn='';

        $retrunprogramid=0;
        $oldprogramid=$programid;
        $conleprogram=$DB->get_record('local_program',  array('id'=>$oldprogramid),  $fields='*',  $strictness=IGNORE_MISSING);
        if($conleprogram){
            if (!is_dir($CFG->dataroot . DIRECTORY_SEPARATOR . 'courseimport')) {
                @mkdir($CFG->dataroot . DIRECTORY_SEPARATOR . 'courseimport', 0777, true);
            }//Added by Harish to create a folder for course content import using moosh
            if($collegeid){
              $conleprogram->costcenter=$collegeid->id;
              $stradded = 'Added - ';
              $resultstring='affiliatcolleges';
              $title=$collegeid->fullname." College";
              $progressbartittle = get_string('affiliatcollegeprogress', 'local_program',$title);
            }else{
              $conleprogram->year=date('Y',time());
              $stradded = 'Copied - ';
              $resultstring='copyprograms';
              $title=$conleprogram->fullname;
              $progressbartittle = get_string('copyprogramprogress', 'local_program',$title);
            }

          $conleprogram->program_description['text'] = $conleprogram->description;
          $conleprogram->parentid=$conleprogram->id;
          $conleprogram->id = 0;

          $newprogramid = $this->manage_curriculum_programs($conleprogram, $copyprogram = true);

          if($newprogramid){
              $retrunprogramid = $newprogramid;
              if ($showfeedback) {
                $reurn.=$OUTPUT->notification($stradded.' Program <b>'.$title.'</b>', 'notifysuccess');
              }

              /*$conlecurriculum=$DB->get_record('local_program',  array('id'=>$oldprogramid),  $fields='*',  $strictness=IGNORE_MISSING);*/
              $conlecurriculum = $DB->get_record_sql("SELECT lc.* FROM {local_program} as lp JOIN {local_curriculum} AS lc on lp.curriculumid = lc.id AND lp.id = $oldprogramid");
              if($conlecurriculum){
                  $conlecurriculum->costcenter=$conleprogram->costcenter;
                  $conlecurriculum->cr_description['text'] = $conlecurriculum->description;
                  $conlecurriculum->program=$newprogramid;
                  $oldcurriculumid=$conlecurriculum->id;
                  $conlecurriculum->id=0;
                  $newcurriculumid = $this->manage_curriculum($conlecurriculum,$copy=true);
                  if($newcurriculumid){

                    if ($showfeedback) {
                      $reurn.=$OUTPUT->notification($stradded.'<b>'.$conlecurriculum->name.'</b> curriculum in this <b>'.$title.'</b>', 'notifysuccess');
                    }

                    $conleprogramccyears=$DB->get_records('local_program_cc_years',  array(/*'programid'=>$oldprogramid,*/'curriculumid'=>$oldcurriculumid),$sort='',$fields='*',  $strictness=IGNORE_MISSING);

                     if($conleprogramccyears){
                        $progressbarone = new \core\progress\display_if_slow($stradded.'Years in this <b>'.$conlecurriculum->name.'</b> curriculum and <b>'.$title.'</b>');
                        $progressbarone->start_html();
                        $progressbarone->start_progress('', count($conleprogramccyears));
                         foreach ($conleprogramccyears as $key => $conleprogramccyear) {

                            $progressbarone->increment_progress();

                            $conleprogramccyear->programid = $newprogramid;
                            $conleprogramccyear->curriculumid = $newcurriculumid;
                            $oldcurriculumyearid=$conleprogramccyear->id;
                            $conleprogramccyear->id=0;

                            $conleprogramccyear->usercreated = $USER->id;
                            $conleprogramccyear->timecreated = time();
                            $newconleprogramccyearid = $DB->insert_record('local_program_cc_years', $conleprogramccyear);

                            $params = array(
                            'context' => context_system::instance(),
                            'objectid' => $newconleprogramccyearid,
                            'other' =>array('curriculumid' => $newcurriculumid)
                            );

                            $event = \local_program\event\year_created::create($params);
                            $event->add_record_snapshot('local_program_cc_years', $newconleprogramccyearid);
                            $event->trigger();

                            if($newconleprogramccyearid){

                                if ($showfeedback) {
                                    $reurn.=$OUTPUT->notification($stradded.'<b>'.$conleprogramccyear->year.'</b> year in this <b>'.$conlecurriculum->name.'</b> curriculum for this <b>'.$title.'</b>', 'notifysuccess');
                                }
;
                                $conleprogramsemesters=$DB->get_records('local_curriculum_semesters',  array(/*'programid'=>$oldprogramid,*/'curriculumid'=>$oldcurriculumid,'yearid'=>$oldcurriculumyearid),$sort='', $fields='*',  $strictness=IGNORE_MISSING);

                              if($conleprogramsemesters){
                                  $progressbartwo = new \core\progress\display_if_slow($stradded.'Semesters in this <b>'.$conleprogramccyear->year.'</b> year and <b>'.$conlecurriculum->name.'</b> curriculum for this <b>'.$title.'</b>');
                                  $progressbartwo->start_html();
                                  $progressbartwo->start_progress('', count($conleprogramsemesters));

                                  foreach ($conleprogramsemesters as $key => $conleprogramsemester) {

                                      $progressbartwo->increment_progress();

                                      $conleprogramsemester->programid = $newprogramid;
                                      $conleprogramsemester->curriculumid = $newcurriculumid;
                                      $conleprogramsemester->yearid = $newconleprogramccyearid;
                                      $oldsemesterid=$conleprogramsemester->id;
                                      $conleprogramsemester->id=0;

                                     $newcurriculumsemesterid =$this->manage_curriculum_program_semesters($conleprogramsemester);

                                     if($newcurriculumsemesterid){

                                      if ($showfeedback) {
                                          $reurn.=$OUTPUT->notification($stradded.'<b>'.$conleprogramsemester->semester.'</b> semester in this <b>'.$conleprogramccyear->year.'</b> year and <b>'.$conlecurriculum->name.'</b> curriculum for this <b>'.$title.'</b>', 'notifysuccess');
                                      }

                                      $conleprogramsemestercourses=$DB->get_records('local_cc_semester_courses',  array(/*'programid'=>$oldprogramid,*/'curriculumid'=>$oldcurriculumid,'yearid'=>$oldcurriculumyearid,'semesterid'=>$oldsemesterid),$sort='',  $fields='*',  $strictness=IGNORE_MISSING);

                                        if($conleprogramsemestercourses){
                                          $progressbarthree = new \core\progress\display_if_slow($stradded.'Couress in this <b>'.$conleprogramsemester->semester.'</b> semester and <b>'.$conleprogramccyear->year.'</b> year and <b>'.$conlecurriculum->name.'</b> curriculum for this <b>'.$title.'</b>');
                                          $progressbarthree->start_html();
                                          $progressbarthree->start_progress('', count($conleprogramsemestercourses));
                                          foreach ($conleprogramsemestercourses as $key => $conleprogramsemestercourse) {

                                              $progressbarthree->increment_progress();

                                              $conlesemestercourse=$DB->get_record('course',  array('id'=>$conleprogramsemestercourse->courseid),  $fields='*',  $strictness=IGNORE_MISSING);


                                              if($conlesemestercourse){

                                                $conleprogramsemestercourse->programid = $newprogramid;
                                                $conleprogramsemestercourse->curriculumid = $newcurriculumid;
                                                $conleprogramsemestercourse->yearid = $newconleprogramccyearid;
                                                $conleprogramsemestercourse->semesterid = $newcurriculumsemesterid;
                                                $oldcourseid=$conleprogramsemestercourse->courseid;

                                                $conlesemestercourse->shortname=$conlesemestercourse->shortname.'_'.$newprogramid;
                                                $conlesemestercourse->id=0;
                                                $conlesemestercourse->open_parentcourseid=$oldcourseid;
                                                $conlesemestercourse->category = $collegeid->category;
                                                /*$options = array(array('name' => 'blocks', 'value' => 1),
                                                array('name' => 'activities', 'value' => 1),
                                                array('name' => 'filters', 'value' => 1),
                                                array('name' => 'users', 'value' => 1)
                                                );
                                                // $externalObj = new \core_course_external();
                                                $courseid = \core_course_external::duplicate_course($oldcourseid, $conlesemestercourse->fullname, $conlesemestercourse->shortname, $conlesemestercourse->open_costcenterid, '0', $options);
                                                print_object($courseid);exit;*/
                                                // Course content duplication by Harish starts here //
                                                $courseid = create_course($conlesemestercourse);
                                                $parentcourseid = $oldcourseid;
                                                $clonedcourse = $courseid->id;
                                                shell_exec('rm -rf ' . $CFG->dataroot . '/courseimport/' . $clonedcourse . '.mbz ');
                                                $command = 'moosh -n course-backup -f ' . $CFG->dataroot . '/courseimport/' . $clonedcourse . '.mbz ' . $parentcourseid ;
                                                $output = shell_exec($command);
                                                $command1 = 'moosh -n course-restore -e ' . $CFG->dataroot . '/courseimport/' . $clonedcourse . '.mbz ' . $clonedcourse;
                                                $output1 = shell_exec($command1);
                                                shell_exec('rm -rf ' . $CFG->dataroot . '/courseimport/' . $clonedcourse . '.mbz ');
                                                // Course content duplication by Harish ends here //
                                                $conlesemestercourse->id= $clonedcourse;
                                                insert::add_enrol_meathod_tocourse($conlesemestercourse,1);
                                                $conleprogramsemestercourse->courseid = $courseid->id;

                                                $conleprogramsemestercourse->timecreated = time();
                                                $conleprogramsemestercourse->usercreated = $USER->id;

                                                $oldsemestercourseid=$conleprogramsemestercourse->id;
                                                $conleprogramsemestercourse->id=0;
                                                $conleprogramsemestercourse->open_parentcourseid = $oldcourseid;
                                                $conleprogramsemestercourse->importstatus=0;

                                                $newprogramsemestercourseid = $DB->insert_record('local_cc_semester_courses',
                                                    $conleprogramsemestercourse);
                                                $params = array(
                                                    'context' => context_system::instance(),
                                                    'objectid' => $newprogramsemestercourseid,
                                                    'other' => array('curriculumid' => $conleprogramsemestercourse->curriculumid,
                                                                     'semesterid' => $conleprogramsemestercourse->semesterid,
                                                                     'yearid' => $conleprogramsemestercourse->yearid)
                                                );

                                                $event = \local_program\event\bcsemestercourse_created::create($params);
                                                $event->add_record_snapshot('local_cc_semester_courses', $conleprogramsemestercourse);
                                                $event->trigger();

                                                $this->manage_curriculum_semester_completions($conleprogramsemestercourse->curriculumid, $conleprogramsemestercourse->semesterid);

                                                if($newprogramsemestercourseid && !$collegeid){

                                                    if ($showfeedback) {
                                                        $reurn.=$OUTPUT->notification($stradded.'<b>'.$conlesemestercourse->fullname.'</b> course  in this <b>'.$conleprogramsemester->semester.'</b> semester and <b>'.$conleprogramccyear->year.'</b> year and <b>'.$conlecurriculum->name.'</b> curriculum for this <b>'.$title.'</b>', 'notifysuccess');
                                                    }
                                                    $conlecoursefaculties=$DB->get_records('local_cc_session_trainers',  array(/*'programid'=>$oldprogramid,*/'curriculumid'=>$oldcurriculumid,'yearid'=>$oldcurriculumyearid,'semesterid'=>$oldsemesterid,'courseid'=>$oldcourseid),$sort='',  $fields='*',  $strictness=IGNORE_MISSING);
                                                   $progressbarfour = new \core\progress\display_if_slow($stradded.'faculties  in this <b>'.$conlesemestercourse->fullname.'</b> course and <b>'.$conleprogramsemester->semester.'</b> semester and <b>'.$conleprogramccyear->year.'</b> year and <b>'.$conlecurriculum->name.'</b> curriculum for this <b>'.$title.'</b>');
                                                    $progressbarfour->start_html();
                                                    $progressbarfour->start_progress('', count($conlecoursefaculties));

                                                    foreach ($conlecoursefaculties as $key => $conlecoursefaculty) {
                                                        $progressbarfour->increment_progress();
                                                        $facultyexist=$DB->get_field('user','firstname',array('id'=>$conlecoursefaculty->trainerid,'suspended'=>0,'deleted'=>0));
                                                        if($facultyexist){
                                                          $conlecoursefaculty->programid = $newprogramid;
                                                          $conlecoursefaculty->curriculumid = $newcurriculumid;
                                                          $conlecoursefaculty->yearid = $newconleprogramccyearid;
                                                          $conlecoursefaculty->semesterid = $newcurriculumsemesterid;
                                                          $conlecoursefaculty->courseid = $conleprogramsemestercourse->courseid;
                                                          $conlecoursefaculty->id=0;
                                                          $conlecoursefaculty->faculty=array($conlecoursefaculty->trainerid);
                                                          $this->addfaculty($conlecoursefaculty);
                                                        if ($showfeedback) {
                                                          $reurn.=$OUTPUT->notification($stradded.'<b> '.$facultyexist.'</b> faculty  in this <b>'.$conlesemestercourse->fullname.'</b> course  and <b>'.$conleprogramsemester->semester.'</b> semester and <b>'.$conleprogramccyear->year.'</b> year and <b>'.$conlecurriculum->name.'</b> curriculum for this <b>'.$title.'</b>', 'notifysuccess');
                                                        }
                                                      }
                                                    }
                                                    // $progressbarfour->end_progress();
                                                    $progressbarfour->end_html();
                                                }
                                              }
                                          }
                                            $totalcourses = $DB->count_records('local_cc_semester_courses',
                                            array('curriculumid' => $newcurriculumid, 'semesterid' =>  $newcurriculumsemesterid, 'yearid' => $newconleprogramccyearid));

                                            $semesterdata = new stdClass();
                                            $semesterdata->id =  $newcurriculumsemesterid;
                                            $semesterdata->programid = $newprogramid;
                                            $semesterdata->curriculumid = $newcurriculumid;
                                            $semesterdata->totalcourses = $totalcourses;
                                            $semesterdata->timemodified = time();
                                            $semesterdata->usermodified = $USER->id;
                                            $DB->update_record('local_curriculum_semesters', $semesterdata);
                                            $totalbccourses = $DB->count_records('local_cc_semester_courses',
                                            array('curriculumid' => $newcurriculumid));
                                            $curriculumdata = new stdClass();
                                            $curriculumdata->programid = $newprogramid;
                                            $curriculumdata->id = $newcurriculumid;
                                            $curriculumdata->totalcourses = $totalbccourses;
                                            $curriculumdata->timemodified = time();
                                            $curriculumdata->usermodified = $USER->id;
                                            $DB->update_record('local_curriculum', $curriculumdata);
                                          // $progressbarthree->end_progress();
                                            $updateccid = $this->updateprogram_curriculumid($retrunprogramid, $newcurriculumid);
                                          $progressbarthree->end_html();
                                        }
                                     }
                                  }
                                // $progressbartwo->end_progress();
                                $progressbartwo->end_html();
                              }
                            }

                         }
                         // $progressbarone->end_progress();
                         $progressbarone->end_html();
                    }
                  }
              }
          }
        }
      if($returntwo){
        return array('programnotification'=>$reurn,'programid'=>$retrunprogramid);
      }else{
        return $reurn;
      }
    }

    public function updateprogram_curriculumid($programid, $curriculumid){
        global $DB;
        $updatesql = "UPDATE {local_program} SET curriculumid = :curriculumid
                       WHERE id = :programid";
        $params = array();
        $params['programid'] = $programid;
        $params['curriculumid'] = $curriculumid;
        $updatecurriculum = $DB->execute($updatesql, $params);
        return $updatecurriculum;
    }

    public function uncopy_program_instance($programid,$showfeedback = true,$progressbar=true,$removecollege=null){
      global $DB, $CFG, $USER,$OUTPUT;

      $reurn='';

      if($removecollege){

        $stratdeleted="Removed -";

        $programfullname=$removecollege->fullname;
      }else{
        $stratdeleted="Deleted ";

        $programfullname=$DB->get_field('local_program','fullname',array('id'=>$programid));
      }

      if($programfullname){

          $localccsessionsignups=$DB->record_exists('local_ccuser_year_signups',array('programid'=>$programid));
          if($localccsessionsignups){

                $signups=$DB->delete_records('local_ccuser_year_signups',array('programid'=>$programid));
              if ($showfeedback&&$signups) {
                $reurn.=$OUTPUT->notification($stratdeleted.' Students  for this <b>'.$programfullname.'</b>', 'notifysuccess');
              }
          }

          $localccsessiontrainers=$DB->record_exists('local_cc_session_trainers',array('programid'=>$programid));
          if($localccsessiontrainers){

              $trainers=$DB->delete_records('local_cc_session_trainers',array('programid'=>$programid));

              if ($showfeedback&&$trainers) {
                $reurn.=$OUTPUT->notification($stratdeleted.' Faculties  for this <b>'.$programfullname.'</b>', 'notifysuccess');
              }
          }

          $courses=$DB->get_records('local_cc_semester_courses',  array('programid'=>$programid),  $sort='',  $fields='id,courseid');

          if($courses){
             $progressbarone = new \core\progress\display_if_slow($stratdeleted.' Courses unassign is in process...');
            $progressbarone->start_html();

             $progressbarone->start_progress('', count($courses));
              foreach ($courses as $key => $course) {

                $localcourse=$DB->get_field('course','fullname',array('id'=>$course->courseid));
                $programcourses[] = $localcourse;// Added by harish for displaying courses(IUMS-376) in comma separated while un affiliating college from program //
                if($localcourse){

                    if($progressbar){
                        $progressbarone->increment_progress();
                    }

                    delete_course($course->courseid,false);

                    $localccsemestercourses=$DB->record_exists('local_cc_semester_courses',array('id'=>$course->id));
                    if($localccsemestercourses){
                        $semestercourses=$DB->delete_records('local_cc_semester_courses',array('id'=>$course->id));
                    }
                }

              }
              // Added by harish for displaying courses(IUMS-376) in comma separated while un affiliating college from program starts here//
              if($programcourses){
                  $programcourses = implode(' , ', $programcourses);
              }else{
                  $programcourses = $localcourse;
              }
              // Added by harish for displaying courses(IUMS-376) in comma separated while un affiliating college from program ends here//
              $progressbarone->end_html();

              if ($showfeedback&&$courses) {
                $reurn.=$OUTPUT->notification($stratdeleted.' <b>'.$programcourses.'</b> courses  for this <b>'.$programfullname.'</b>', 'notifysuccess');
              }
          }
          $localcurriculumsemesters=$DB->record_exists('local_curriculum_semesters',array('programid'=>$programid));
          if($localcurriculumsemesters){
              $semesters=$DB->delete_records('local_curriculum_semesters',array('programid'=>$programid));
              if ($showfeedback&&$semesters) {
                $reurn.=$OUTPUT->notification($stratdeleted.' Semesters  for this <b>'.$programfullname.'</b>', 'notifysuccess');
              }
          }

          $localprogramccyears=$DB->record_exists('local_program_cc_years',array('programid'=>$programid));
          if($localprogramccyears){

              $ccyears=$DB->delete_records('local_program_cc_years',array('programid'=>$programid));

              if ($showfeedback&&$ccyears) {
                $reurn.=$OUTPUT->notification($stratdeleted.' Years  for this <b>'.$programfullname.'</b>', 'notifysuccess');
              }
          }
          
           $localcurriculumusers=$DB->record_exists('local_curriculum_users',array('programid'=>$programid));
          if($localcurriculumusers){
              $curriculumusers=$DB->delete_records('local_curriculum_users',array('programid'=>$programid));
              if ($showfeedback&&$curriculumusers) {
                $reurn.=$OUTPUT->notification($stratdeleted.' Curriculum users  for this <b>'.$programfullname.'</b>', 'notifysuccess');
              }
          }

          $localcurriculum=$DB->record_exists('local_curriculum',array('program'=>$programid));
          if($localcurriculum){
              $curriculum=$DB->delete_records('local_curriculum',array('program'=>$programid));
              if ($showfeedback&&$curriculum) {
                $reurn.=$OUTPUT->notification($stratdeleted.' Curriculum  for this <b>'.$programfullname.'</b>', 'notifysuccess');
              }
          }
          $localprogram=$DB->record_exists('local_program',array('id'=>$programid));
          if($localprogram){

              $program=$DB->delete_records('local_program',array('id'=>$programid));
              if ($showfeedback&&$program) {
                $reurn .= $OUTPUT->notification($stratdeleted.' <b>'.$programfullname.'</b> Program', 'notifysuccess');
              }
          }
    }
     return $reurn;
    }
    public function addyearcost($yearcost) {
      global $DB, $USER;
      $costexists = $DB->get_record('local_program_cc_year_cost', array('yearid' => $yearcost->yearid, 'curriculumid' => $yearcost->curriculumid, 'programid' => $yearcost->programid));
      if (empty($costexists)) {
        $yearcost->usercreated = $USER->id;
        $yearcost->timecreated = time();
        $return = $DB->insert_record('local_program_cc_year_cost', $yearcost);
      } else {
        $costexists->cost = $yearcost->cost;
        $costexists->usermodified = $USER->id;
        $costexists->timemodified = time();
        $DB->update_record('local_program_cc_year_cost', $costexists);
        $return = $costexists->id;
      }
      return $return;
    }
    public function maximum_programsections($programs,$typemode='add')
    {
      global $DB, $USER;
      if($typemode=='delete'){

        $sql="SELECT (SELECT count(id) FROM {local_cc_semester_courses} where programid in(:programidd)) as courses FROM {local_program} as pr LIMIT 1";

      }else{

        $sql="SELECT (SELECT count(id) FROM {local_curriculum} where program in(:programida)) as curriculum,(SELECT count(id) FROM {local_program_cc_years} where programid in(:programidb)) as years,(SELECT count(id) FROM {local_curriculum_semesters} where programid in(:programidc)) as semesters,(SELECT count(id) FROM {local_cc_semester_courses} where programid in(:programidd)) as courses,(SELECT count(id) FROM {local_cc_session_trainers} where programid in(:programide)) as trainers FROM {local_program} as pr LIMIT 1";
      }

        if(is_array($programs)){
            $programs=implode(',', $programs);
        }
        $params=array('programida'=>$programs,'programidb'=>$programs,'programidc'=>$programs,'programidd'=>$programs,'programide'=>$programs);

        $maximumprogramsections=$DB->get_record_sql($sql,$params);

        if($maximumprogramsections&&$typemode=='add'){
          $arrayobject=array_flip((array)$maximumprogramsections);
          krsort($arrayobject);

          $currentvalue=current($arrayobject);
          $currentvaluekey=array_search($currentvalue,$arrayobject);

          $return=array($currentvalue,$currentvaluekey);
        }elseif($maximumprogramsections&&$typemode=='delete'){
            $return=array($maximumprogramsections->courses,'courses');
        }else{
          $return=false;
        }
        return $return;
    }
    /**
     * [curriculumusers description]
     * @method curriculumusers
     * @param  [type]         $curriculumid [description]
     * @param  [type]         $stable      [description]
     * @return [type]                      [description]
     */
    public function coursefaculty($yearid, $semesterid, $courseid, $stable) {
        global $DB, $USER;
        $params = array();
        $coursefaculty = array();
        $concatsql = '';
        if (!empty($stable->search)) {
            $fields = array(0 => 'u.firstname',
                            1 => 'u.lastname',
                            2 => 'u.email',
                            3 => 'u.idnumber'
                            );
                $fields = implode(" LIKE '%" .$stable->search. "%' OR ", $fields);
                $fields .= " LIKE '%" .$stable->search. "%' ";
                $concatsql .= " AND ($fields) ";
        }
        $countsql = "SELECT COUNT(u.id) ";
        $fromsql = "SELECT u.*, ccst.trainerid, ccst.yearid, ccst.semesterid, ccst.courseid ";
        $sql = " FROM {user} AS u
                 JOIN {local_cc_session_trainers} AS ccst ON ccst.trainerid = u.id
                WHERE ccst.yearid = :yearid AND ccst.semesterid = :semesterid AND ccst.courseid = :courseid AND u.confirmed = 1 AND u.suspended = 0 AND u.deleted = 0 AND u.id > 2";
        $sql .= $concatsql;
        $params['yearid'] = $yearid;
        $params['semesterid'] = $semesterid;
        $params['courseid'] = $courseid;
        try {
            $coursefacultycount = $DB->count_records_sql($countsql . $sql, $params);
            if ($stable->thead == false) {
                $sql .= " ORDER BY u.id ASC";
                $coursefaculty = $DB->get_records_sql($fromsql . $sql, $params, $stable->start, $stable->length);
            }
        } catch (dml_exception $ex) {
            $coursefacultycount = 0;
        }

        return compact('coursefaculty', 'coursefacultycount');
    }
    public function cc_course_faculty_unenrol($facultydata) {
      global $DB, $CFG, $USER;
      $trainerexist = $DB->get_record('local_cc_session_trainers', array('trainerid' => $facultydata->trainerid, 'courseid' => $facultydata->courseid, 'semesterid' => $facultydata->semesterid, 'yearid' => $facultydata->yearid));
      if (empty($trainerexist)) {
        print_error('trainer not found!');
      } else {
        $DB->delete_records('local_cc_session_trainers', array('trainerid' => $facultydata->trainerid, 'courseid' => $facultydata->courseid, 'semesterid' => $facultydata->semesterid, 'yearid' => $facultydata->yearid));
      }
      return true;
    }
    public function unassignfaculty($facultydata) {
      global $DB, $CFG, $USER;
      $pluginname = 'program';
      $enrolmethod = enrol_get_plugin($pluginname);
      $roleid = $DB->get_field('role', 'id', array('shortname' => 'faculty'));
      $instance = $DB->get_record('enrol', array('courseid' => $facultydata->courseid, 'enrol' => $pluginname), '*', MUST_EXIST);
      if ($this->manage_program_curriculum_course_enrolments($facultydata->trainerid, $roleid, 'unenrol', $enrolmethod, $instance)) {
        $this->cc_course_faculty_unenrol($facultydata);
      }
      return true;
    }
  public function enrolusertoprogram($programid, $curriculumid, $userid) {
    global $DB, $USER, $CFG;
    $curriculumuser = new stdClass();
    $curriculumuser->programid = $programid;
    $curriculumuser->curriculumid = $curriculumid;
    $curriculumuser->courseid = 0;
    $curriculumuser->userid = $userid;
    $curriculumuser->supervisorid = 0;
    $curriculumuser->prefeedback = 0;
    $curriculumuser->postfeedback = 0;
    $curriculumuser->trainingfeedback = 0;
    $curriculumuser->confirmation = 0;
    $curriculumuser->attended_sessions = 0;
    $curriculumuser->hours = 0;
    $curriculumuser->completion_status = 0;
    $curriculumuser->completiondate = 0;
    $curriculumuser->usercreated = $USER->id;
    $curriculumuser->timecreated = time();
    $curriculumuser->usermodified = $USER->id;
    $curriculumuser->timemodified = time();
    try {
      $curriculumuser->id = $DB->insert_record('local_curriculum_users',
                            $curriculumuser);
      $local_curriculum = $DB->get_record_sql("SELECT * FROM {local_curriculum} where id = $curriculumid");

      $params = array(
        'context' => context_system::instance(),
        'objectid' => $curriculumuser->id,
        'other' => array('curriculumid' => $curriculumid)
      );

      $event = \local_program\event\program_users_enrol::create($params);
      $event->add_record_snapshot('local_curriculum_users', $curriculumuser);
      $event->trigger();

      if ($local_curriculum->status == 0) {
        // $email_logs = $emaillogs->curriculum_emaillogs($type, $dataobj, $curriculumuser->userid, $fromuserid);
      }
    } catch (dml_exception $ex) {
      print_error($ex);
    }
    return true;
  }
    public function programtemplatestatus($programid) {
        global $DB, $CFG, $USER;
        $program = $DB->get_record('local_program', array('id' => $programid));

        $checkcostcentersql = 'SELECT id, parentid
                                 FROM {local_costcenter}
                                WHERE id = :costcenter ';
        $checkcostcenter = $DB->get_record_sql($checkcostcentersql, array('costcenter' => $program->costcenter));

        $checkparent = $DB->count_records_sql('SELECT COUNT(id) FROM {local_program} WHERE parentid = :parentid',  array('parentid' => $program->id));

        $affiliatecolleges = $DB->count_records_sql('SELECT COUNT(id) FROM {local_program} WHERE parentid = :parentid AND costcenter <> :costcenter',  array('parentid' => $program->id, 'costcenter' => $program->costcenter));

        $copiedprograms = $DB->count_records_sql("SELECT COUNT(id) FROM {local_program} WHERE parentid = :parentid AND costcenter = :costcenter",  array('parentid' => $program->id, 'costcenter' => $program->costcenter));


        if ($checkcostcenter->parentid == 0 && $affiliatecolleges == 0) {
            return true;
        } else if ($program->parentid == 0 && $checkparent > 0 ) {
            return false;
        } else {
            return false;
        }

    }

    public function checkcopyprogram($programid) {
        global $DB;
        $currentprogramsql = 'SELECT p.id, p.costcenter, p.parentid
                FROM {local_program} p
                WHERE p.id = :programid ';
        $currentprogram = $DB->get_record_sql($currentprogramsql, array('programid' => $programid));
        $programsql = 'SELECT pp.id, pp.costcenter, pp.parentid
                FROM {local_program} pp
                WHERE pp.id = :programid ';
        $program = $DB->get_record_sql($programsql, array('programid' => $currentprogram->parentid));
        //condition for collegeprogram is removed added for all
        //if ($currentprogram->parentid > 0 &&($currentprogram->costcenter != $program->costcenter)) {
          if ($currentprogram->costcenter != $program->costcenter) {
            return false;
        }
        return true;
    }

    public function deletesemonlinecourses($semesterid = null, $curriculumid = null, $yearid = null){
        global $DB, $CFG;
        require_once($CFG->dirroot. '/course/lib.php');
        $params = array();
        $onlinecourses_sql = "SELECT courseid as id, courseid as courseid
                                FROM {local_cc_semester_courses}
                               WHERE 1 = 1";
        if($semesterid){
            $onlinecourses_sql .= " AND semesterid = :semester";
            $params['semester'] = $semesterid;
        }
        if($yearid){
            $onlinecourses_sql .= " AND yearid = :year";
            $params['year'] = $yearid;
        }
        if($curriculumid){
            $onlinecourses_sql .= " AND curriculumid = :curriculum";
        }
            $params['curriculum'] = $curriculumid;

        $onlinecourseslist = $DB->get_records_sql_menu($onlinecourses_sql, $params);
        if($onlinecourseslist){
            foreach($onlinecourseslist as $key => $value){
                \core_php_time_limit::raise();
                // We do this here because it spits out feedback as it goes.
                delete_course($value, false);
                // Update course count in categories.
                fix_course_sortorder();
            }
        }
        return true;
    }

    public function manage_classroom_completionsettings($completions){
        global $DB, $USER;
        //print_object($completions);
        if(!empty($completions->sessionids)&&is_array ($completions->sessionids)){
            $completions->sessionids=implode(',',$completions->sessionids);
        }else{
            $completions->sessionids=null;
        }
        if(!empty($completions->courseids)&&is_array ($completions->courseids)){
            $completions->courseids=implode(',',$completions->courseids);
        }else{
            $completions->courseids=null;
        }
        if(empty($completions->sessiontracking)){
           $completions->sessiontracking=null;
        }
        if(empty($completions->coursetracking)){
           $completions->coursetracking=null;
        }
        try { 
            if ($completions->id > 0) {
                $completions->timemodified = time();
                $completions->usermodified = $USER->id;
                $completions->classroomid = $completions->bclcid;

                if($completions->sessiontracking != 'REQ'){
                    $completions->requiredsessions = NULL;
                }
                unset($completions->bclcid);
                $DB->update_record('local_classroom_completion', $completions);
                $params = array(
                    'context' => context_system::instance(),
                    'objectid' => $completions->id
                );
            
                $event = \local_program\event\classroom_completions_settings_updated::create($params);
                $event->add_record_snapshot('local_classroom',$completions->classroomid);
                $event->trigger();
            } else {
                $completions->timecreated = time();
                $completions->usercreated = $USER->id;
                $completions->classroomid = $completions->bclcid;
                unset($completions->bclcid);
                // print_object($completions);exit;
                $completions->id = $DB->insert_record('local_classroom_completion', $completions);
                $params = array(
                    'context' => context_system::instance(),
                    'objectid' => $completions->id
                );
            
                $event = \local_program\event\classroom_completions_settings_created::create($params);
                $event->add_record_snapshot('local_classroom', $completions->classroomid);
                $event->trigger();
            }
        } catch (dml_exception $ex) {
            print_error($ex);
        }
        return $completions->id;
        
    }

    /**
     * Display the curriculum view
     * @return string The text to render
     */
    public function programcontent($curriculumid, $programid = null) {
        global $CFG, $DB, $USER, $PAGE;
        $systemcontext = context_system::instance();
        $stable = new stdClass();
        $stable->curriculumid = $curriculumid;
        $stable->programid = $programid;
        $stable->thead = false;
        $stable->start = 0;
        $stable->length = 1;
        $curriculum = $this->curriculums($stable);

        if (empty($curriculum)) {
            print_error("curriculum Not Found!");
        }
        $programtemplatestatus = $this->programtemplatestatus($curriculum->program);

        $checkcopyprogram = (new program)->checkcopyprogram($curriculum->program);
        $curriculumcompletion = false;

        if ((has_capability('local/program:programcompletion', context_system::instance()) || is_siteadmin())) {
            $curriculumcompletion = false;
        }

        $bulkenrollusers = false;
        $bulkenrollusersurl = false;
        if ((has_capability('local/program:viewusers', context_system::instance()) || is_siteadmin()) && !$checkcopyprogram) {
            $assignusers = true;
            $assignusersurl = new moodle_url("/local/program/enrollusers.php?ccid=" .
                $curriculumid . "");
            $bulkenrollusers = true;
            $bulkenrollusersurl = new moodle_url("/local/program/mass_enroll.php?ccid=" .
                $curriculumid . "");
        }
        $selfenrolmenttabcap = true;
        if (!has_capability('local/program:manageprogram', context_system::instance())) {
            $selfenrolmenttabcap = false;
        }

        if (!empty($curriculum->description)) {
            $description = strip_tags(html_entity_decode($curriculum->description));
        } else {
            $description = "";
        }
        $isdescription = '';
        if (empty($description)) {
            $isdescription = false;
            $descriptionstring = "";
        } else {
            $isdescription = true;
            if (strlen($description) > 540) {
                $first540Char = substr($description, 0, 540);
                $theRest = substr($description, 540);
                // $decsriptionCut = substr($description, 0, 540);
                $descriptionfirstChar = strip_tags(html_entity_decode($first540Char));
                $descriptiontheRest = strip_tags(html_entity_decode($theRest));

            } else {
                $descriptionstring = $description;
            }
        }
        $bcuser = $DB->record_exists('local_curriculum_users',
            array('curriculumid' => $curriculumid, 'userid' => $USER->id));
        $userview = $bcuser && !is_siteadmin() && !has_capability('local/program:createprogram', $systemcontext) ? true : false;

        if ($userview) {
            $mycompletedsemesters = (new program)->mycompletedsemesteryears($curriculumid, $USER->id);
            $notcmptlsemesters = (new program)->mynextsemesteryears($curriculumid);
            if (!empty($notcmptlsemesters)) {
                $yearid = $notcmptlsemesters[0];
            } else {
                $yearid = $DB->get_field_select('local_program_cc_years', 'id',
                'curriculumid = :curriculumid AND status = :status ORDER BY id ASC LIMIT 0, 1 ',
                array('curriculumid' => $curriculumid, 'status' => 1));
            }
        } else {
            $yearid = $DB->get_field_select('local_program_cc_years', 'id',
            'curriculumid = :curriculumid AND status = :status ORDER BY id ASC LIMIT 0, 1 ',
            array('curriculumid' => $curriculumid, 'status' => 1));
        }

        $completionstatus = $DB->get_field('local_curriculum_users', 'completion_status', array('curriculumid' => $curriculumid, 'userid' => $USER->id));
        if ($completionstatus == 1) {
            $curriculumcompletionstatus = true;
        } else {
            $curriculumcompletionstatus = false;
        }
        $program = $DB->get_record('local_program', array('id' => $curriculum->program));
        $curriculumcontext = [
            'curriculum' => $curriculum,
            'curriculumcompletion' => $curriculumcompletion,
            'programdata' => $program,
            'curriculumcompletionstatus' => $curriculumcompletionstatus,
            'yearid' => $yearid,
            'curriculumsemesteryearscontent' => $this->viewcurriculumsemesteryears($curriculumid, $yearid),
        ];

        return $curriculumcontext;
    }
    public function viewcurriculumsemesteryears($curriculumid, $yearid) {
        global $OUTPUT, $CFG, $DB, $USER;
        $systemcontext = context_system::instance();

        $ccuser = $DB->record_exists('local_curriculum_users',
            array('curriculumid' => $curriculumid, 'userid' => $USER->id));
        $userview = $ccuser && !is_siteadmin() && !has_capability('local/program:createprogram', $systemcontext) ? true : false;

        $stable = new stdClass();
        $stable->curriculumid = $curriculumid;
        $stable->thead = false;
        $stable->start = 0;
        $stable->length = 1;
        $curriculum = (new program)->curriculums($stable);

        $curriculumsemesteryears = (new program)->curriculum_semesteryears($curriculumid);
        $programtemplatestatus = (new program)->programtemplatestatus($curriculum->program);
        $affiliatecolleges = $DB->count_records_sql('SELECT COUNT(id) FROM {local_program} WHERE parentid = :parentid AND costcenter <> :costcenter',  array('parentid' => $curriculum->id, 'costcenter' => $curriculum->costcenter));

        $copiedprograms = $DB->count_records_sql("SELECT COUNT(id) FROM {local_program} WHERE parentid = :parentid AND costcenter = :costcenter",  array('parentid' => $curriculum->id, 'costcenter' => $curriculum->costcenter));

        $managemyprogram = false;

        if ($curriculum->costcenter == $USER->open_costcenterid) {
            $managemyprogram = true;
        }

        if ($userview) {
            $mycompletedsemesters = (new program)->mycompletedsemesters($curriculumid, $USER->id);
            $notcmptlsemesters = (new program)->mynextsemesters($curriculumid);
            if (!empty($notcmptlsemesters)) {
                $nextsemester = $notcmptlsemesters[0];
            } else {
                $nextsemester = 0;
            }
        }
        if (!empty($curriculumsemesteryears)) {
          $i = 0;
            foreach ($curriculumsemesteryears as $k => $curriculumsemesteryear) {
                $activeclass = 0;
                $disabled = 0;
                $yearname = strlen($curriculumsemesteryear->year) > 11 ? substr($curriculumsemesteryear->year, 0, 11) ."<span class='semesterrestrict'>..</span>": $curriculumsemesteryear->year;
                // $curriculumsemesteryear->yearcontent = array();
                $curriculumsemesteryear->year = $yearname;
                if ($curriculumsemesteryear->id == $yearid) {
                    $activeclass = 1;
                    $activetab = $i;
                }
                $i++;
                    $curriculumsemesteryear->yearcontent = $this->viewcurriculumsemesteryear($curriculumid, $curriculumsemesteryear->id);
                if ($userview && !is_siteadmin() && !has_capability('local/program:createprogram', $systemcontext)) {
                    $yearrecordexists = $DB->record_exists('local_ccuser_year_signups',  array('userid' => $USER->id, 'yearid' => $curriculumsemesteryear->id, 'programid' => $curriculumsemesteryear->programid));
                    if (!$yearrecordexists) {
                        $disabled = 1;
                    }

                    $completion_status = $DB->get_field_sql('SELECT completion_status FROM {local_ccuser_year_signups} WHERE curriculumid ='.$curriculumid.' AND yearid ='.$curriculumsemesteryear->id.' AND userid ='.$USER->id.' AND programid ='.$curriculumsemesteryear->programid);
                    $curriculumsemesteryear->mycompletionstatus = 0;
                    if ($userview && $completion_status == 1) {
                        $curriculumsemesteryear->mycompletionstatus = 1;
                    }

                }
                $curriculumsemesteryear->progressstatus = 0;
                $curriculumsemesteryear->myinprogressstatus = 0;
                if ($userview && $completion_status == 0) {
                    $curriculumsemesteryear->myinprogressstatus = 1;
                    $curriculumsemesteryear->progressstatus = 0;
                } else if ($userview && $completion_status == 1) {
                    $curriculumsemesteryear->progressstatus = 1;
                }
                $curriculumsemesteryear->active = $activeclass;
                $curriculumsemesteryear->disabled = $disabled;

                $semestercount_records = $DB->count_records('local_curriculum_semesters',
                array('curriculumid' => $curriculumid, 'yearid' => $curriculumsemesteryear->id));

                $curriculumsemesteryears[$k] = $curriculumsemesteryear;
            }
        }

        $curriculumsemesterscontext = [
            'canviewsemesteryear' => has_capability('local/program:viewsemesteryear', $systemcontext),
            'yearid' => $yearid,
            'userview' => $userview,
            'activetab' => $activetab,
            'curriculumsemesteryears' => array_values($curriculumsemesteryears),
            // 'curriculumsemesteryear' => $this->viewcurriculumsemesteryear($curriculumid, $yearid)
        ];

        return $curriculumsemesterscontext;
    }
    public function viewcurriculumsemesteryear($curriculumid, $yearid) {
        global $OUTPUT, $CFG, $DB, $USER;
        $systemcontext = context_system::instance();

        $stable = new stdClass();
        $stable->curriculumid = $curriculumid;
        $stable->thead = false;
        $stable->start = 0;
        $stable->length = 1;
        $curriculum = (new program)->curriculums($stable);
        $programtemplatestatus = (new program)->programtemplatestatus($curriculum->program);
        $checkcopyprogram = (new program)->checkcopyprogram($curriculum->program);
        $ccuser = $DB->record_exists('local_curriculum_users',
            array('curriculumid' => $curriculumid, 'userid' => $USER->id));
        $userview = $ccuser && !is_siteadmin() && !has_capability('local/program:createprogram', $systemcontext) ? true : false;

        $semestercount_records = $DB->count_records('local_curriculum_semesters',
                array('curriculumid' => $curriculumid, 'yearid' => $yearid));

        $curriculumsemesteryears = (new program)->curriculum_semesteryears($curriculumid, $yearid);

        if ($userview) {
            $mycompletedsemesteryears = (new program)->mycompletedsemesteryears($curriculumid, $USER->id);
            $notcmptlsemesteryears = (new program)->mynextsemesteryears($curriculumid);
            if (!empty($notcmptlsemesteryears)) {
                $nextsemester = $notcmptlsemesteryears[0];
            } else {
                $nextsemester = 0;
            }
        }

        if (!empty($curriculumsemesteryears)) {
            foreach ($curriculumsemesteryears as $k => $curriculumsemesteryear) {
                $activeclass = '';
                $disabled = '';
                $semestername = strlen($curriculumsemesteryear->year) > 11 ? substr($curriculumsemesteryear->year, 0, 11) ."<span class='semesterrestrict'>..</span>": $curriculumsemesteryear->year;

                $curriculumsemesteryear->year = "<span title='".$curriculumsemesteryear->year."'>".$semestername."</span>";
                if ($curriculumsemesteryear->id == $yearid) {
                    $activeclass = 'active';
                }

                if ($userview && !is_siteadmin() && !has_capability('local/program:createprogram', $systemcontext)) {
                    $yearrecordexists = $DB->record_exists('local_ccuser_year_signups',  array('userid' => $USER->id, 'yearid' => $curriculumsemesteryear->id, 'programid' => $curriculumsemesteryear->programid));
                    if (!$yearrecordexists) {
                        $disabled = 'disabled';
                    }

                }

            if ($userview && !is_siteadmin() && !has_capability('local/program:createprogram', $systemcontext)) {
                $completion_status = $DB->get_field_sql('SELECT completion_status FROM {local_ccuser_year_signups} WHERE curriculumid ='.$curriculumid.' AND yearid ='.$curriculumsemesteryear->id.' AND userid ='.$USER->id.' AND programid ='.$curriculumsemesteryear->programid);
            } else {
                $completion_status = $DB->get_field_sql('SELECT completion_status FROM {local_ccuser_year_signups} WHERE curriculumid ='.$curriculumid.' AND yearid ='.$curriculumsemesteryear->id.' AND userid ='.$USER->id.' AND programid ='.$curriculum->program);
            }

                $curriculumsemesteryear->mycompletionstatus = '';
                if ($userview && $completion_status == 1) {
                    $curriculumsemesteryear->mycompletionstatus = 'Completed';
                }

                $curriculumsemesteryear->myinprogressstatus = '';
                if ($userview && $completion_status == 0) {
                    $curriculumsemesteryear->myinprogressstatus = 'Inprogress';
                }

                $curriculumsemesteryear->active = $activeclass;
                $curriculumsemesteryear->disabled = $disabled;
                $semestercount_records = $DB->get_records('local_curriculum_semesters',
                array('curriculumid' => $curriculumid));
                $candeletesemester = false;
                if (count($semestercount_records) > 0 && has_capability('local/program:deletesemester',
                    $systemcontext)) {
                    $candeletesemester = false;
                } else if (has_capability('local/program:deletesemester', $systemcontext)) {
                    $candeletesemester = true;
                }
                $curriculumsemesteryear->candeletesemester = $candeletesemester;
                $curriculumsemesteryears[$k] = $curriculumsemesteryear;
            }
        }

        $yearsemestercontentcontext = array();

        $yearsemestercontentcontext['canviewsemester'] = has_capability('local/program:viewsemester', $systemcontext);

        $yearsemestercontentcontext['yearid'] = $yearid;
        $yearsemestercontentcontext['userview'] = $userview;

        if($userview){
            $curriculumsemesters = (new program)->curriculumsemesteryear($curriculumid, $yearid, $USER->id);
        }else{
            $curriculumsemesters = (new program)->curriculumsemesteryear($curriculumid, $yearid);
        }

        $semesters = false;
        if(count($curriculumsemesters) > 1){
            $semesters = true;
        }
        $uhroleid = $DB->get_field('role', 'id', array('shortname' => 'university_head'));
        if ($ccuser && has_capability('local/program:viewprogram', $systemcontext) && !is_siteadmin() && !has_capability('local/program:trainer_viewprogram', $systemcontext) && !has_capability('local/program:viewusers', $systemcontext)) {
            foreach ($curriculumsemesters as $curriculumsemester) {
                $courses = $DB->get_records_sql('SELECT c.id as courseid, c.fullname as course, ccsc.coursetype, ccsc.id AS cc_courseid
                                                   FROM {course} c
                                                   JOIN {local_cc_semester_courses} ccsc ON ccsc.courseid = c.id
                                                   JOIN {local_curriculum_semesters} lcs ON lcs.id = ccsc.semesterid
                                                   JOIN {local_cc_semester_cmptl} ccss ON ccsc.yearid = ccss.yearid
                                                   WHERE ccsc.semesterid = :semesterid AND ccss.userid = :userid AND ccss.yearid = :yearid ', array('semesterid' => $curriculumsemester->semesterid, 'userid' => $USER->id, 'yearid' => $yearid));
                $curriculumsemester->courses = array_values($courses);
            }
        } else if (has_capability('local/program:trainer_viewprogram', $systemcontext) && !is_siteadmin() && !(user_has_role_assignment($USER->id, $uhroleid, $systemcontext->id))) {
            $yearsemestercontentcontext['istrainer'] = true;
            foreach ($curriculumsemesters as $curriculumsemester) {
                $courses = $DB->get_records_sql('SELECT c.id as courseid, c.fullname as course, ccsc.importstatus, ccsc.coursetype, ccsc.id AS cc_courseid, ccst.courseid as trainerenrolstatus
                                                   FROM {course} c
                                                   JOIN {local_cc_semester_courses} ccsc ON ccsc.courseid = c.id
                                                   JOIN {local_curriculum_semesters} lcs ON lcs.id = ccsc.semesterid
                                                   JOIN {local_cc_session_trainers} ccst ON ccst.courseid = ccsc.courseid AND ccst.yearid = lcs.yearid
                                                   WHERE ccsc.semesterid = :semesterid AND ccst.trainerid = :trainerid AND ccst.yearid = :yearid ', array('semesterid' => $curriculumsemester->semesterid, 'trainerid' => $USER->id, 'yearid' => $yearid));
                $curriculumsemester->courses = array_values($courses);
            }
                $coursetypes = array();
                foreach($courses as $course){
                    $coursescheck = array();
                    if($course->coursetype == 1){
                        $courses[$course->courseid]->coursetype = true;
                    }else{
                        $courses[$course->courseid]->coursetype = false;
                    }
                }
        } else {
            $parentsemcmplstatus = 0;
            $ccyearfirstsem = 1;
            foreach ($curriculumsemesters as $curriculumsemester) {
                $isStudent = !has_capability ('moodle/course:update', $systemcontext) ? true : false;

                if($isStudent){
                    $curriculumsemester->parentsemcmplstatus = ($parentsemcmplstatus) ? true : false;
                    $curriculumsemester->ccyearfirstsem = ($ccyearfirstsem) ? true : false;
                }else{
                $curriculumsemester->parentsemcmplstatus = true;
                    $curriculumsemester->ccyearfirstsem = true;
                }
                $ccyearfirstsem = 0;

                $courses = $DB->get_records_sql('SELECT c.id, c.id as courseid, c.fullname as course, ccsc.importstatus, ccsc.coursetype, ccsc.id AS cc_courseid
                                                   FROM {course} c
                                                   JOIN {local_cc_semester_courses} ccsc ON ccsc.courseid = c.id
                                                   WHERE ccsc.semesterid = :semesterid AND ccsc.yearid = :yearid', array('semesterid' => $curriculumsemester->semesterid, 'yearid' => $yearid));
                $curriculumsemester->courses = array_values($courses);

                $offlineclassrooms = $DB->get_records_sql("SELECT id AS cc_courseid, classname, requiredsessions, courseid
                                                             FROM {local_cc_semester_classrooms}
                                                             WHERE curriculumid = $curriculumsemester->curriculumid
                                                               AND semesterid = $curriculumsemester->semesterid
                                                               AND yearid = $curriculumsemester->yearid");

                if($offlineclassrooms){
                    $offlineclrooms = array();
                    foreach ($offlineclassrooms as $classroom) {

                        $attendancetaken = $DB->count_records_sql("SELECT count(id)
                                                                     FROM {local_cc_session_signups}
                                                                    WHERE curriculumid = $curriculumsemester->curriculumid
                                                                      AND semesterid = $curriculumsemester->semesterid
                                                                      AND yearid = $curriculumsemester->yearid
                                                                      AND bclcid = $classroom->cc_courseid
                                                                      AND completion_status != 0");
                        if($attendancetaken > 0){
                            $classroom->attendancecount = $attendancetaken;
                        }else{
                            $classroom->attendancecount = 0;
                        }
                        $offlineclrooms[] = $classroom;
                    }
                $curriculumsemester->offlineclassrooms = array_values($offlineclrooms);
                }

                $coursetypes = array();
                foreach($courses as $course){
                    $coursescheck = array();
                    if($course->coursetype == 1){
                        $courses[$course->courseid]->coursetype = true;
                        $exists = $DB->record_exists('course_completion_criteria',array('course' => $course->courseid));
                        if($exists){
                            $courses[$course->courseid]->completioncriteria = true;
                        }
                    }else{

                        $courses[$course->courseid]->coursetype = false;
                        $courses[$course->courseid]->completioncriteria = false;
                    }
                }

                $semesteruserscount = $DB->count_records('local_cc_semester_cmptl', array('curriculumid' => $curriculumid, 'yearid' => $yearid));
                $curriculumsemester->usersemcompletionstatus = ($curriculumsemester->semcompletionstatus) ? true : false;
                $parentsemcmplstatus = ($curriculumsemester->semcompletionstatus) ? true : false;
            }
        }
        $coursesadded = $DB->record_exists('local_cc_semester_courses', array('curriculumid' => $curriculumid, 'yearid' => $yearid));
        $yearsemestercontentcontext['coursesadded'] = $coursesadded;
        $yearsemestercontentcontext['curriculumsemesters'] = array_values($curriculumsemesters);
        // $yearsemestercontentcontext['curriculumsemesteryears'] = array_values($curriculumsemesteryears);
        $yearsemestercontentcontext['semesters'] = $semesters;

        return $yearsemestercontentcontext;
    }
     public function addallstudent($studentdata) {
      global $DB, $CFG, $USER;
      $pluginname = 'program';
      $params = array();
      $semestercoursessql = 'SELECT ccsc.id, ccsc.courseid as courseid,ccsc.yearid
                             FROM {local_cc_semester_courses} ccsc 
                             WHERE  ccsc.programid = :programid  ';
      $params['programid'] = $studentdata->programid;
      $semestercourses = $DB->get_records_sql($semestercoursessql , $params);
      $enrolmethod = enrol_get_plugin($pluginname);
      $roleid = $DB->get_field('role', 'id', array('shortname' => 'student'));
      foreach ($semestercourses as $semestercourse) {
        $instance = $DB->get_record('enrol', array('courseid' => $semestercourse->courseid, 'enrol' => $pluginname), '*', MUST_EXIST);
        if (!empty($instance)) {
          foreach ($studentdata->students as $student) {
            $studentdata->yearid = $semestercourse->yearid;

            $studentdata->student = $student;
            $programuser = $DB->get_record('local_curriculum_users', array('curriculumid' => $studentdata->curriculumid, 'userid' => $student));
            $programusersignups = $DB->get_record('local_ccuser_year_signups', array('curriculumid' => $studentdata->curriculumid, 'userid' => $student,'programid' => $studentdata->programid,'yearid'=>$semestercourse->yearid));
            if($programuser && $programusersignups){
              continue;
            }
            if (!$programuser) {
            
              $programuser = $this->enrolusertoprogram($studentdata->programid, $studentdata->curriculumid, $student);
            }
            if ($programuser) {
              $this->cc_year_enrolments($studentdata);
            }
            $this->manage_program_curriculum_course_enrolments($student, $roleid, 'enrol', $enrolmethod, $instance);
          }
        }
      }

      $this->manage_program_curriculum_classroomsession_enrolments($studentdata);// Added by harish for ODL-316
      return true;
    }
  // AM ODL-713 to enroll classroom assign users to programs - starts
  public function addstudenttoclassroom($studentdata) {
      global $DB, $CFG, $USER;
      $pluginname = 'program';
      $params = array();
      $semestercoursessql = 'SELECT c.id, c.id as courseid
                               FROM {course} c
                               JOIN {local_cc_semester_courses} ccsc ON ccsc.courseid = c.id
                              WHERE ccsc.yearid = :yearid ';
      $params['yearid'] = $studentdata->yearid;
      $semestercourses = $DB->get_records_sql($semestercoursessql, $params);
      $enrolmethod = enrol_get_plugin($pluginname);
      $roleid = $DB->get_field('role', 'id', array('shortname' => 'student'));
          foreach ($studentdata->students as $student) {
            $studentdata->student = $student;
            $programuser = $DB->record_exists('local_curriculum_users', array('curriculumid' => $studentdata->curriculumid, 'userid' => $student));
            if (!$programuser) {
              $programuser = $this->enrolusertoprogram($studentdata->programid, $studentdata->curriculumid, $student);
            }
            if ($programuser) {
              $this->cc_year_enrolments($studentdata);
            }
          }
          $this->manage_program_curriculum_classroomsession_enrolments($studentdata);// Added by harish for ODL-316
          return true;
    }
  // AM ODL-713 to enroll classroom assign users to programs - ends
    public function programenrolusers($curriculumid, $stable) {
        global $DB, $USER;
        $params = array();
        $curriculumusers = array();
        $yearid = array();
        $concatsql = '';
        if (!empty($stable->search)) {
            $fields = array(0 => 'u.firstname',
                            1 => 'u.lastname',
                            2 => 'u.email',
                            3 => 'u.idnumber'
                            );
                $fields = implode(" LIKE '%" .$stable->search. "%' OR ", $fields);
                $fields .= " LIKE '%" .$stable->search. "%' ";
                $concatsql .= " AND ($fields) ";
        }
        $countsql = "SELECT COUNT(cu.id) ";
        $fromsql = "SELECT u.*, cu.attended_sessions, cu.hours, cu.completion_status, c.totalsessions,
        c.activesessions";
        $sql = " FROM {user} AS u
                 JOIN {local_curriculum_users} AS cu ON cu.userid = u.id 
                 JOIN {local_program} as lp ON lp.id = cu.programid";
        if ($stable->yearid > 0) {
          $sql .= " JOIN {local_ccuser_year_signups} ccss ON ccss.userid = cu.userid ";
          $concatsql .= " AND ccss.yearid = :yearid ";
          $params['yearid'] = $stable->yearid;
        }
        $sql .= " JOIN {local_curriculum} AS c ON c.id = cu.curriculumid
                  WHERE c.id = $curriculumid AND u.confirmed = 1 AND u.suspended = 0 AND u.deleted = 0 AND u.id > 2";
        $sql .= $concatsql;
        try {
            $programuserscount = $DB->count_records_sql($countsql . $sql, $params);
            if ($stable->thead == false) {
                $sql .= " ORDER BY id ASC";
                //$programusers = $DB->get_records_sql($fromsql . $sql, $params);
                //Revathi Issue no 770 starts
                $programusers = $DB->get_records_sql($fromsql . $sql, $params, $stable->start, $stable->length);
                 //Revathi Issue no 770 ends
            }
        } catch (dml_exception $ex) {
            $programuserscount = 0;
        }
        return compact('programusers', 'programuserscount');
    }
}

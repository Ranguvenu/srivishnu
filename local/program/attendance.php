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
 * Take attendance in curriculum.
 *
 * @package    local_curriculum
 * @copyright  2017 M Arun Kumar <arun@eabyas.in>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once(dirname(dirname(dirname(__FILE__))).'/config.php');

require_once($CFG->dirroot.'/local/program/lib.php');
use \local_curriculum\notifications_emails as curriculumnotifications_emails;
// require_once($CFG->dirroot . '/local/program/notifications_emails.php');
require_login();
use local_program\program;
$curriculumid = required_param('ccid', PARAM_INT);
$sessionid = required_param('sid', PARAM_INT);
$programid = optional_param('programid', 0, PARAM_INT);
$yearid = optional_param('yearid', 0, PARAM_INT);
$bclcid = optional_param('bclcid', 0, PARAM_INT);
$semesterid = optional_param('semesterid', 0, PARAM_INT);
$ccses_action = optional_param('ccses_action', '', PARAM_RAW);
require_login();
$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->requires->jquery();
$PAGE->requires->jquery_plugin('ui');
$url = new moodle_url($CFG->wwwroot . '/local/program/attendance.php',
    array('ccid' => $curriculumid));
if ($sessionid > 0) {
    $url->param('sid', $sessionid);
}
if ($bclcid > 0) {
    $url->param('bclcid', $bclcid);
}
if ($programid > 0) {
    $url->param('programid', $programid);
}
if ($yearid > 0) {
    $url->param('yearid', $yearid);
}
if ($semesterid > 0) {
    $url->param('semesterid', $semesterid);
}
if($ccses_action){
    $url->param('ccses_action', $ccses_action);   
}
if($curriculumid > 0){
    $url->param('ccid', $curriculumid);
}

$PAGE->set_url($url);
$PAGE->set_pagelayout('admin');

$curriculum_name = $DB->get_field('local_curriculum','name',array('id' => $curriculumid));// Fetching curriculum name//
if($programid){
    $programname = $DB->get_field('local_program', 'fullname', array('id' => $programid));//Fetching program name//
}
if($bclcid){
    $classroomname = $DB->get_field('local_cc_semester_classrooms', 'classname', array('id' => $bclcid, 'programid' => $programid, 'yearid' => $yearid, 'semesterid' => $semesterid));//Fetching offline classroom name//
}
if($sessionid){
    $sessionname = $DB->get_field('local_cc_course_sessions', 'name', array('id' => $sessionid, 'bclcid' => $bclcid, 'programid' => $programid, 'yearid' => $yearid, 'semesterid' => $semesterid));//Fetching offline classroom session name//
}

$PAGE->navbar->add(get_string("pluginname", 'local_program'), new moodle_url($CFG->wwwroot . '/local/program/index.php', array('type' => 2)));
$PAGE->navbar->add($programname, new moodle_url('/local/program/view.php', array('ccid' => $curriculumid,'prgid' => $programid)));
$PAGE->navbar->add($classroomname, new moodle_url('/local/program/sessions.php', array('ccid' => $curriculumid, 'programid' => $programid, 'yearid' => $yearid, 'semesterid' => $semesterid, 'bclcid' => $bclcid, 'ccses_action' => $ccses_action, 'courseid' => 0)));
$PAGE->navbar->add($sessionname);
$PAGE->set_title($sessionname);
$PAGE->set_heading(get_string('session_attendance_heading', 'local_program', $sessionname));
$renderer = $PAGE->get_renderer('local_program');
$curriculum = new program();
$attendancedata = data_submitted();
$emaillogs = new \local_program\programnotifications_emails();

if (!empty($attendancedata)) {
    if (isset($attendancedata->reset) && $attendancedata->reset == 'Reset Selected') {
        $DB->execute("UPDATE {local_cc_session_signups} SET completion_status = 0
            WHERE curriculumid = :curriculumid AND sessionid = :sessionid",
            array('curriculumid' => $attendancedata->ccid,
                'sessionid' => $attendancedata->sid));
        redirect($PAGE->url);
    } else if ($attendancedata->action == 'attendance') {
        foreach ($attendancedata->attendeedata as $k => $attendancesignup) {
            $decodeddata = json_decode(base64_decode($attendancesignup));

            if ($decodeddata->attendanceid > 0) {
                $checkattendeestatus = new stdClass();
                $checkattendeestatus->id = $decodeddata->attendanceid;
                if (isset($attendancedata->status[$attendancesignup]) && $attendancedata->status[$attendancesignup]) {
                    $checkattendeestatus->completion_status = SESSION_PRESENT;
                    $checkattendeestatus->completiondate = time();
                } else {
                    $checkattendeestatus->completion_status = SESSION_ABSENT;
                }
                $completionstatus = $checkattendeestatus->completion_status;
                $checkattendeestatus->timemodified = time();
                $checkattendeestatus->usermodified = $USER->id;
                $DB->update_record('local_cc_session_signups', $checkattendeestatus);
                if ($checkattendeestatus->completion_status == SESSION_PRESENT) {
                    /*$email_logs = $emaillogs->curriculum_emaillogs('curriculum_session_completion', $decodeddata->sessionid, $decodeddata->userid, $USER->id);*/
                }
                
                /*$email_logs = $emaillogs->curriculum_emaillogs('curriculum_session_attendance', $decodeddata->sessionid, $decodeddata->userid, $USER->id);*/
                $params = array(
                    'context' => context_system::instance(),
                    'objectid' => $checkattendeestatus->id
                );

                // $event = \local_program\event\program_attendance_created_updated::create($params);
                // $event->add_record_snapshot('local_program', $decodeddata->curriculumid);
                // $event->trigger();
            } else {
                $userattendance = new stdClass();
                $userattendance->curriculumid = $decodeddata->curriculumid;
                $userattendance->sessionid = $decodeddata->sessionid;
                $userattendance->userid = $decodeddata->userid;
                if (isset($attendancedata->status[$attendancesignup]) && $attendancedata->status[$attendancesignup]) {
                    $userattendance->completion_status = SESSION_PRESENT;
                } else {
                    $userattendance->completion_status = SESSION_ABSENT;
                }
                $completionstatus = $userattendance->completion_status;
                $userattendance->usercreated = $USER->id;
                $userattendance->timecreated = time();
                $record_exist=$DB->record_exists_sql("SELECT id FROM {local_cc_session_signups}
                                                    where curriculumid=$decodeddata->curriculumid
                                                    and userid=$decodeddata->userid and sessionid=$decodeddata->sessionid");
                $params = array(
                    'context' => context_system::instance(),
                    'objectid' => $id
                );

                // $event = \local_program\event\program_attendance_created_updated::create($params);
                // $event->add_record_snapshot('local_program', $decodeddata->curriculumid);
                // $event->trigger();
            }
            $attendedsessions = $DB->count_records('local_cc_session_signups',
                array('curriculumid' => $decodeddata->curriculumid,
                    'userid' => $decodeddata->userid, 'completion_status' => SESSION_PRESENT));

            $attendedsessions_hours = $DB->get_field_sql("SELECT ((sum(lcs.duration))/60) AS hours
                                        FROM {local_cc_course_sessions} as lcs
                                        WHERE lcs.curriculumid = $curriculumid
                                        and lcs.id IN (SELECT sessionid  FROM {local_cc_session_signups} WHERE curriculumid = $curriculumid AND userid = $decodeddata->userid AND completion_status=1)");

            if (empty($attendedsessions_hours)) {
                $attendedsessions_hours = 0;
            }
             //issue 725 attendance issue - starts
            if(!empty($decodeddata->curriculumid)){
                $semesterid = $DB->get_field('local_curriculum_semesters','id',array('curriculumid'=>$decodeddata->curriculumid));
            }
            //issue 725 attendance issue - ends
            $DB->execute('UPDATE {local_curriculum_users} SET attended_sessions = ' .
                $attendedsessions . ', hours = ' .
                $attendedsessions_hours . ', timemodified = ' . time() . ',
                usermodified = ' . $USER->id . ' WHERE curriculumid = ' .
                $decodeddata->curriculumid . ' AND userid = ' . $decodeddata->userid);
            if ($completionstatus == SESSION_PRESENT) {
                $userdata = new stdClass();
                $userdata->curriculumid = $decodeddata->curriculumid;
                $userdata->sessionid = $decodeddata->sessionid;
                $userdata->userid = $decodeddata->userid;
                $userdata->bclcid = $bclcid;
                $userdata->semesterid = $semesterid;
                (new program)->bc_semester_courses_completions($userdata);

                // $bccourse = $DB->get_record('local_cc_course_sessions',
                //     array('id' => $decodeddata->sessionid));
                // (new program)->bccourse_sessions_completions($bccourse);
            }

            if($completionstatus == SESSION_ABSENT){
                $session = $DB->get_record_select('local_cc_course_sessions', 'id = :id', array('id' => $decodeddata->sessionid));
                $semesterid= $DB->get_field('local_cc_session_signups', 'semesterid', array('curriculumid'=>$decodeddata->curriculumid, 'sessionid'=>$decodeddata->sessionid));
                $checkcousrecmptlsql = "SELECT *
                                      FROM {local_cc_semester_cmptl}
                                      WHERE userid = $decodeddata->userid
                                      AND curriculumid = $decodeddata->curriculumid
                                      AND semesterid = $semesterid";
                $checkcousrecmptl = $DB->get_record_sql($checkcousrecmptlsql);
                $bclcids = $checkcousrecmptl->bclcids;
                if (!empty($checkcousrecmptl->bclcids)) {
                    $bclcidslist = explode(',', $checkcousrecmptl->bclcids);
                    if (in_array($session->bclcid, $bclcidslist)) {
                        $index = array_search($session->bclcid, $bclcidslist);
                        if ( $index !== false ) {
                            unset( $bclcidslist[$index] );
                        }
                    }
                    $bclcids = implode(',', $bclcidslist);
                    $checkcousrecmptl->bclcids = $bclcids;
                    $checkcousrecmptl->usermodified = $USER->id;
                    $checkcousrecmptl->timemodified = time();
                    $DB->update_record('local_cc_semester_cmptl', $checkcousrecmptl);
                }
                

                $bcuser = $DB->get_record('local_curriculum_users',
                    array('curriculumid' => $decodeddata->curriculumid,
                        'userid' => $decodeddata->userid, 'completion_status' => 0));
                if (!empty($bcuser)) {
                    $bcsemesters = $DB->get_records_menu('local_curriculum_semesters',
                        array('curriculumid' => $decodeddata->curriculumid), 'id',
                        'id, id AS semester');
                    $bcusercmptlsemesterids = $bcuser->semesterids;
                    if (!empty($bcusercmptlsemesterids)) {
                        $semesterids = explode(',', $bcusercmptlsemesterids);
                        if (in_array($session->semesterid, $semesterids)) {
                            $index1 = array_search($session->semesterid, $semesterids);
                            if ( $index1 !== false ) {
                                unset( $semesterids[$index1] );
                            }
                        }
                        // $semesterids[] = $session->semesterid;
                        array_unique($semesterids);
                        $bcuser->semesterids = implode(',', $semesterids);;
                    }
                    
                    $DB->update_record('local_curriculum_users', $bcuser);
                    //curriculum completions $bcuser->completion_status=1
                    if($bcuser->completion_status == 1){
                      $type = 'curriculum_completion';
                      $email_logs = $emaillogs->curriculum_emaillogs($type, $bcuser->curriculumid, $bcuser->userid,
                                $USER->id);
                    }

                }
            }
        }
        if ($sessionid > 0) {
            $activeusers = $DB->count_records('local_cc_session_signups', array('sessionid' => $sessionid, 'completion_status' => 1));
            $DB->execute("UPDATE {local_cc_course_sessions} SET attendance_status = 1
                , activeusers = $activeusers
                WHERE id = :id ", array('id' => $sessionid));
        } else {
            $DB->execute("UPDATE {local_cc_course_sessions} SET attendance_status = 1,
            timemodified = :timemodified, usermodified = :usermodified WHERE
            curriculumid = :curriculumid ", array('curriculumid' => $curriculumid,
                'usermodified' => $USER->id, 'timemodified' => time()));
        }

        redirect($PAGE->url);
    }
}

echo $OUTPUT->header();
/*$backto = '<div class="col-md-1 pull-right">';
$backto .= '<a href='.$CFG->wwwroot.'/local/program/sessions.php?ccses_action=class_sessions&bclcid='.$bclcid.'&programid='.$programid.'&ccid='.$curriculumid.'&yearid='.$yearid.'&semesterid='.$semesterid.'&courseid=0 class="singlebutton" title="Back to '.$classroomname.' Sessions"><button title="Back to '.$classroomname.' Sessions">'.get_string('back').'</button></a>';
$backto .= '</div>';
echo $backto;*/
if ($sessionid > 0) {
    $sessionattendance = new \local_program\output\session_attendance($sessionid);
    echo $renderer->render($sessionattendance);
}
echo $renderer->viewcurriculumattendance($curriculumid, $sessionid);
$continue = '<div class="col-md-1 pull-right">';
$continue .= '<a href='.$CFG->wwwroot.'/local/program/sessions.php?ccses_action=class_sessions&bclcid='.$bclcid.'&programid='.$programid.'&ccid='.$curriculumid.'&yearid='.$yearid.'&semesterid='.$semesterid.'&courseid=0 class="singlebutton"><button>'.get_string('continue', 'local_program').'</button></a>';
$continue .= '</div>';
echo $continue;
echo $OUTPUT->footer();

<?php

define('AJAX_SCRIPT', true);
use local_program\program;
require_once(dirname(__FILE__) . '/../../config.php');
global $DB, $OUTPUT;
$draw = optional_param('draw', 1, PARAM_INT);
/*$start = optional_param('start', 0, PARAM_INT);
$length = optional_param('length', 5, PARAM_INT);
$search = optional_param_array('search', '', PARAM_RAW);*/
$action = optional_param('action', '', PARAM_RAW);


$systemcontext = context_system::instance();
$PAGE->set_context($systemcontext);

$requestData= $_REQUEST;
$aColumns = array('');
$sIndexColumn = "id";
$sTable = "ajax";
$input =& $_GET;
$sLimit = "";
$totalprograms =0;
if($DB->record_exists('local_cc_course_sessions', ['trainerid' => $USER->id])){
    $stable = new stdClass();
    $stable->search = $requestData['sSearch'];
    $stable->start = $requestData['iDisplayStart'];
    $stable->length = $requestData['iDisplayLength'];
 	$sessions = (new program)->classroom_offlinesessions($action, $stable);
}
 // print_object($sessions);
 $data = array();    
 if(!empty($sessions)){
            foreach ($sessions['sessions'] as $sdata) {
                $line = array();
                $line[] = $sdata->classname;
                $line[] = $sdata->name;
                $starttimehour_mins = ($sdata->dailysessionstarttime) ? $sdata->dailysessionstarttime : 00;
                $endtimehour_mins = ($sdata->dailysessionendtime) ? $sdata->dailysessionendtime : 00;
                $line[] = '<i class="fa fa-calendar" aria-hidden="true"></i> ' .
                            date("d/m/Y", $sdata->timestart).' : '.'<i class="fa fa-clock-o"></i> ' . $starttimehour_mins . ' <b> - </b> ' . $endtimehour_mins;
                // $line[] = '<i class="fa fa-clock-o"></i> ' . $starttimehour_mins . ' <b> - </b> ' . $endtimehour_mins;
                $link = get_string('pluginname', 'local_program');
                if ($sdata->onlinesession == 1) {
                    $moduleids = $DB->get_field('modules', 'id',
                        array('name' => $sdata->moduletype));
                    if ($moduleids) {
                        $moduleid = $DB->get_field('course_modules', 'id',
                                array('instance' => $sdata->moduleid, 'module' => $moduleids));
                        if ($moduleid) {
                            $link = html_writer::link($CFG->wwwroot . '/mod/' .$sdata->moduletype. '/view.php?id=' . $moduleid,
                                    get_string('join', 'local_program'),
                                    array('title' => get_string('join', 'local_program')));
                            if (!is_siteadmin() && !has_capability('local/program:manageprogram', context_system::instance())) {
                                $userenrolstatus = $DB->record_exists('local_curriculum_users', array('curriculumid' => $sdata->curriculumid, 'userid' => $USER->id));
                                if (!$userenrolstatus) {
                                    $link = get_string('join', 'local_program');
                                }
                            }
                        }
                    }
                }
                $line[] = $sdata->room ? $sdata->room : 'N/A';

                $curriculum_totalusers = $DB->count_records('local_cc_session_signups',
                    array('yearid' => $yearid, 'sessionid' => $sdata->id));
                if ((has_capability('local/program:manageprogram', context_system::instance()) || is_siteadmin())) {
                    $attendedsessions_users = $DB->count_records('local_cc_session_signups',
                        array('yearid' => $yearid, 'sessionid' => $sdata->id,
                            'completion_status' => SESSION_PRESENT));
                }
                if (has_capability('local/program:manageprogram', context_system::instance()) || has_capability('local/program:takesessionattendance', context_system::instance())) {
                    if ($sdata->timefinish <= time() && $sdata->attendance_status == 1) {
                        $line[] = get_string('completed', 'local_program');
                    } else {
                        $line[] = get_string('pending', 'local_program');
                    }
                } 

                if (has_capability('local/program:manageprogram', context_system::instance()) || is_siteadmin() || has_capability('local/program:manage_owndepartments', context_system::instance()) || has_capability('local/program:takesessionattendance', context_system::instance())) {
                    $line[] = $sdata->activeusers . '/' . $sdata->totalusers;
                }

                $action = '';
                if ((has_capability('local/program:editsession', context_system::instance()) || is_siteadmin())&&(has_capability('local/program:manageprogram', context_system::instance())) || (has_capability('local/program:editsession', context_system::instance()) && has_capability('local/program:manage_owndepartments', context_system::instance()))) {
                    $action .= '<a href="javascript:void(0);" alt = ' . get_string('edit') . ' title = ' . get_string('edit') . ' onclick="(function(e){ require(\'local_program/ajaxforms\').init({contextid:1, component:\'local_program\', callback:\'session_form\', form_status:0, plugintype: \'local_program\', pluginname: \'session\', id: ' . $sdata->id . ', ccid: ' . $curriculumid .', yearid:'.$sdata->yearid.', programid:'.$sdata->programid.', semesterid: '. $sdata->semesterid .', bclcid: '.$sdata->bclcid.', ccses_action: \'class_sessions\', title: \'updatesession\'}) })(event)" ><img src="' . $OUTPUT->image_url('t/edit') . '" alt = ' . get_string('edit') . ' title = ' . get_string('edit') . ' class="icon"/></a>';
                }
                if (has_capability('local/program:deletesession', context_system::instance())
                    || is_siteadmin()/*&&(has_capability('local/program:manageprogram', context_system::instance()))*/) {
                    $count_records = $DB->get_records_sql('SELECT * FROM {local_cc_session_signups} WHERE sessionid = :sessionid AND completion_status > :completion_status',
                        array('sessionid' => $sdata->id,'completion_status' => 0));

                    //Added by Harish for ODL-447 starts here// 
                    $clroomsessionscount = $DB->count_records_sql("SELECT count(session.id) FROM {local_cc_course_sessions} AS session JOIN {local_cc_semester_classrooms} AS clroom ON clroom.id = session.bclcid WHERE session.bclcid = :bclcid AND session.programid = :programid AND clroom.classroom_type = :cltype", array('bclcid' => $sdata->bclcid, 'programid' => $sdata->programid, 'cltype' => 1));
                    //Added by Harish for ODL-447 ends here//
                    $completionsetting = $DB->get_record_sql('SELECT * FROM {local_classroom_completion} WHERE classroomid = :classroomid AND FIND_IN_SET("$sdata->id", sessionids)',
                        array('classroomid' => $sdata->bclcid));
                    if (count($count_records) > 0 || $clroomsessionscount == 1 || $completionsetting) {
                         $action .= '<a href="javascript:void(0);" alt = '.get_string('delete').' title = '.get_string('delete').' onclick="(function(e){ require(\'local_program/program\').deleteConfirm({action:\'cannotdeletesession\', curriculumid: '.$sdata->curriculumid.', semesterid: '.$sdata->semesterid.', bclcid: '.$sdata->bclcid.', sessioncount: '.$clroomsessionscount.', id: '.$sdata->id.' }) })(event)" ><img src="' .$OUTPUT->image_url('t/delete').'" alt = '.get_string('delete').' title = '.get_string('delete').' class="icon"/></a>';
                    } else {
                         $action .= '<a href="javascript:void(0);" alt = '.get_string('delete').' title = '.get_string('delete').' onclick="(function(e){ require(\'local_program/program\').deleteConfirm({action:\'deletesession\', curriculumid: '.$sdata->curriculumid.', semesterid: '.$sdata->semesterid.', bclcid: '.$sdata->bclcid.', id: '.$sdata->id.' }) })(event)" ><img src="'.$OUTPUT->image_url('t/delete').'" alt = '.get_string('delete').' title = '.get_string('delete').' class="icon"/></a>';
                    }
                }
                if ((has_capability('local/program:takesessionattendance', context_system::instance()) || is_siteadmin())/*&&(has_capability('local/program:manageprogram', context_system::instance()))*/) {
                    $action .= '<a href="' . $CFG->wwwroot . '/local/program/attendance.php?ccid=' . $sdata->curriculumid . '&semesterid=' . $sdata->semesterid . '&bclcid=' . $sdata->bclcid . '&sid=' . $sdata->id . '&programid='.$sdata->programid.'&yearid='.$sdata->yearid.'&ccses_action=class_sessions" target="_blank"><img src="' . $OUTPUT->image_url('t/assignroles') . '" alt = ' . get_string('attendace', 'local_program') . ' title = ' . get_string('attendace', 'local_program') . ' class="icon"/></a>';
                }
                if ((has_capability('local/program:editsession', context_system::instance()) || has_capability('local/program:deletesession', context_system::instance()) || has_capability('local/program:takesessionattendance', context_system::instance())) /*&& (has_capability('local/program:manageprogram', context_system::instance()))*/) {
                    $line[] = $action;
                } else {
                    if ((($userview && !$enrolmentpending) || ($userview && !$curriculumcompletionstatus))) {
                        $sessiondate = date_create(date('Y-m-d', $sdata->timestart));
                        $currentdate = date_create(date('Y-m-d'));
                        $diff = date_diff( $sessiondate, $currentdate );
                        $sessionactionstatus = true;
                        if($sdata->sessiondays > 0){
                            if ($diff->days < $sdata->sessiondays) {
                                $sessionactionstatus = false;
                            }
                        }
                        if ($enrolmentpending) {
                            $line[] = 'Enrolment Not Yet Started.';
                        } else if (time() > $sdata->timefinish && $sdata->signupid == 0) {
                            $line[] = 'Closed';
                        } else if ($sdata->attendance_status && $sdata->completion_status == 2) {
                            $line[] = 'Absent';
                        } else if ($sdata->signupid > 0 && $sdata->completion_status == 1) {
                            $line[] = 'Present';
                        } /*else if (time() < $sdata->timestart && $sdata->signupid > 0 && $sdata->mysignupstatus > 0 && $presentsessions == 0 && $sessionactionstatus && $curriculumcompletionstatus == 0) {
                            $line[] = '<a href="javascript:void(0);" class="sessionenrol" data-curriculumid='.$sdata->curriculumid.' data-semesterid='.$sdata->semesterid.' data-bclcid='.$bclcid.' data-programid='.$sdata->programid.' data-yearid='.$sdata->yearid.' data-sessionid='.$sdata->id.' data-signupid='.$sdata->signupid.' data-ccses_action='.$bclcdata->ccses_action.' data-enrol=3 >Cancel</a>';
                        }*/ else if ($sdata->mysignupstatus > 0 && !$sdata->signupid /*&& $sdata->attendance_status == 0*/ && $presentsessions == 0 && $activesessions > 0 && $curriculumcompletionstatus == 0 && ($sdata->timestart > $mylastattendedsession->timefinish) && $sessionactionstatus) {
                            $line[] = '<a href="javascript:void(0);" class="sessionenrol" data-curriculumid='.$sdata->curriculumid.' data-semesterid='.$sdata->semesterid.' data-bclcid='.$sdata->bclcid.' data-programid='.$sdata->programid.' data-yearid='.$sdata->yearid.' data-sessionid='.$sdata->id.' data-signupid=0 data-ccses_action="class_sessions" data-enrol=2 >Re Schedule</a>';
                        } else if ((($sdata->signups < $sdata->maxcapacity) && ($sdata->timestart > $mylastattendedsession->timefinish) && ($sdata->signupid == 0 || $sdata->signupid == null) && $presentsessions == 0 && $curriculumcompletionstatus == 0 && $sessionactionstatus) && ($sdata->mysignupstatus == 0 || $absentsessions)) {
                            $line[] = '<a href="javascript:void(0);" class="sessionenrol" data-curriculumid='.$sdata->curriculumid.' data-semesterid='.$sdata->semesterid.' data-bclcid='.$sdata->bclcid.' data-programid='.$sdata->programid.' data-yearid='.$sdata->yearid.' data-ccses_action="class_sessions" data-sessionid='.$sdata->id.'
                            data-signupid=0 data-enrol=1 >Enrol</a>';
                        } else if ($sdata->signups == $sdata->maxcapacity && !$sdata->signupid){
                            $line[] = 'Max Seats filled.';
                        } else if ($sdata->signupid) {
                            $line[] = 'Enrolled';
                        } else {
                            $line[] = 'Enrollment Closed';
                        }
                    }
                }
                $data[] = $line;
            }
        }

$iTotal = $totalprograms; 
$iFilteredTotal = $iTotal;
$output = array(
    "sEcho"                => intval($input['sEcho']),
    "iTotalRecords"        => ($sessions['sessionscount']) ? $sessions['sessionscount'] : 0,
    "iTotalDisplayRecords" => ($sessions['sessionscount']) ? $sessions['sessionscount'] : 0,
    "aaData"               => $data
);
echo json_encode($output);
 ?>

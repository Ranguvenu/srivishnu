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
 * curriculum Render
 *
 * @package    local_curriculum
 * @copyright  2018 Arun Kumar M <arun@eabyas.in>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace local_program\output;

require_once($CFG->dirroot . '/local/program/lib.php');
require_once($CFG->dirroot . '/user/lib.php');
defined('MOODLE_INTERNAL') || die;

if (file_exists($CFG->dirroot . '/local/includes.php')) {
    require_once($CFG->dirroot . '/local/includes.php');
}

use context_system;
use html_table;
use html_writer;
use local_program\program;
use plugin_renderer_base;
use user_course_details;
use moodle_url;
use stdClass;
use single_button;
use tabobject;
use core_completion\progress;

class renderer extends plugin_renderer_base {
    /**
     * [render_curriculum description]
     * @method render_curriculum
     * @param  \local_program\output\curriculum $page [description]
     * @return [type]                                  [description]
     */
    public function render_curriculum(\local_program\output\curriculum $page) {
        $data = $page->export_for_template($this);
        return parent::render_from_template('local_program/program', $data);
    }
    /**
     * [render_form_status description]
     * @method render_form_status
     * @param  \local_program\output\form_status $page [description]
     * @return [type]                                    [description]
     */
    public function render_form_status(\local_program\output\form_status $page) {
        $data = $page->export_for_template($this);
        return parent::render_from_template('local_program/form_status', $data);
    }
    /**
     * [render_session_attendance description]
     * @method render_session_attendance
     * @param  \local_program\output\session_attendance $page [description]
     * @return [type]                                           [description]
     */
    public function render_session_attendance(\local_program\output\session_attendance $page) {
        $data = $page->export_for_template($this);
        return parent::render_from_template('local_program/session_attendance', $data);
    }
    /**
     * Display the curriculum tabs
     * @return string The text to render
     */
    public function get_curriculum_tabs() {
        global $CFG, $OUTPUT;
        $stable = new stdClass();
        $stable->thead = true;
        $stable->start = 0;
        $stable->length = -1;
        $stable->search = '';
        $curriculumscontent = $this->viewcurriculums($stable);
        $context = context_system::instance();

        $curriculumtabslist = [
            'curriculumslist' => $curriculumscontent,
            'contextid' => $context->id,
            'plugintype' => 'local',
            'plugin_name' =>'program',
            'is_siteadmin' => is_siteadmin(),
            'creatacurriculum' => ((has_capability('local/program:managecurriculum',
            context_system::instance()) && has_capability('local/program:createcurriculum',
            context_system::instance())) || is_siteadmin()) ? true : false,
        ];
        if ((has_capability('local/location:manageinstitute', context_system::instance()) || has_capability('local/location:viewinstitute', context_system::instance())) && (has_capability('local/program:manageprogram', context_system::instance()))) {
            $curriculumtabslist['location_url'] = $CFG->wwwroot . '/local/location/index.php';

        }
        return $this->render_from_template('local_program/programtabs', $curriculumtabslist);
    }
    /**
     * [viewcurriculums description]
     * @method viewcurriculums
     * @param  [type]         $stable [description]
     * @return [type]                 [description]
     */
    public function viewcurriculums($stable) {
        global $OUTPUT, $CFG, $DB;
        $systemcontext = context_system::instance();
        if (file_exists($CFG->dirroot . '/local/includes.php')) {
            require_once($CFG->dirroot . '/local/includes.php');
            $includes = new user_course_details();
        }
        if ($stable->thead) {
            $curriculums = (new program)->curriculums($stable);
            if ($curriculums['curriculumscount'] > 0) {
                $table = new html_table();
                $table->head = array('', '', '');
                $table->id = 'viewcurriculums';
                $return = html_writer::table($table);
            } else {
                $return = "<div class='alert alert-info'>" .
                        get_string('nocurriculums', 'local_program') . "</div>";
            }
        } else {
            $curriculums = (new program)->curriculums($stable);
            $data = array();
            $curriculumchunks = array_chunk($curriculums['curriculums'], 3);
            foreach ($curriculumchunks as $bc_data) {
                $row = [];
                foreach ($bc_data as $sdata) {
                    $line = array();
                    $curriculum = $sdata->name;
                    $curriculumname = strlen($curriculum) > 19 ? substr($curriculum, 0, 19) . "..." : $curriculum;
                    $description = strip_tags(html_entity_decode($sdata->description));
                    $isdescription = '';
                    if (empty($description)) {
                        $isdescription = false;
                    } else {
                        $isdescription = true;
                        if (strlen($description) > 130) {
                            $decsriptionCut = substr($description, 0, 130);
                            $decsriptionstring = strip_tags(html_entity_decode($decsriptionCut));
                        } else{
                            $decsriptionstring = "";
                        }
                    }

                    if ($sdata->program) {
                        $program =  $DB->get_field('local_program', 'fullname',
                            array('id' => $sdata->program));
                    }
                    $programname = strlen($program) > 15 ? substr($program, 0, 15) . ".." : $program;
                    $bcshortname = strlen($sdata->shortname) > 15 ? substr($sdata->shortname, 0, 15) . ".." : $sdata->shortname;
                    $line['curriculum'] = $curriculum;
                    $line['curriculumname'] = $curriculumname;
                    $line['program'] = $program;
                    $line['programname'] = $programname;
                    $line['shortname'] = $sdata->shortname;
                    $line['bcshortname'] = $bcshortname;
                    $line['curriculumicon'] = $OUTPUT->image_url('program_icon', 'local_program');
                    $line['description'] =  strip_tags(html_entity_decode($sdata->description));
                    $line['descriptionstring'] = $decsriptionstring;
                    $line['isdescription'] = $isdescription;
                    $line['enrolled_users'] = $sdata->enrolled_users;
                    $line['completed_users'] = $sdata->completed_users;
                    $line['curriculumid'] = $sdata->id;
                    $line['editicon'] = $OUTPUT->image_url('t/edit');
                    $line['deleteicon'] = $OUTPUT->image_url('t/delete');
                    $line['assignusersicon'] = $OUTPUT->image_url('t/assignroles');
                    $line['curriculumcompletion'] = false;
                    $mouseovericon = false;

                     if (is_siteadmin() || (has_capability('local/program:managecurriculum', context_system::instance()) || has_capability('local/program:editcurriculum', context_system::instance()))) {
                        $line['edit'] = true;
                        $mouseovericon = true;
                    }

                    if (is_siteadmin() || (has_capability('local/program:managecurriculum', context_system::instance()) || has_capability('local/program:deletecurriculum', context_system::instance()))) {                        $count_records = $DB->get_records('local_cc_session_signups', array('curriculumid'=>$sdata->id));
                        if(count($count_records) > 0) {
                            $line['cannotdelete'] = true;
                            $mouseovericon = true;
                        } else {
                            $line['delete'] = true;
                            $mouseovericon = true;
                        }
                    }
                    if (is_siteadmin() || (has_capability('local/program:managecurriculum', context_system::instance()) || has_capability('local/program:manageusers', context_system::instance()))) {
                        $line['assignusers'] = true;
                        $line['assignusersurl'] = new moodle_url("/local/program/enrollusers.php?ccid=" . $sdata->id . "");
                        $mouseovericon = true;
                    }
                    if ($mouseovericon) {
                        $line['action'] = true;
                    }
                    $completionstatus = $DB->get_field('local_curriculum_users', 'completion_status', array('curriculumid'=>$sdata->id, 'userid'=>$USER->id));
                    if($completionstatus == 1){
                        $line['curriculumcompletionstatus'] = true;
                    } else {
                        $line['curriculumcompletionstatus'] = false;

                    }
					$line['curriculumcompletion_id'] = $curriculumcompletion_id;
                    $line['mouse_overicon'] = $mouseovericon;
                    $row[] = $this->render_from_template('local_program/browseprogram', $line);
                }
                if (!isset($row[1])) {
                    $row[1] = '';
                    $row[2] = '';
                } else if (!isset($row[2])) {
                    $row[2] = '';
                }
                $data[] = $row;
            }
            $return = array(
                "recordsTotal" => $curriculums['curriculumscount'],
                "recordsFiltered" => $curriculums['curriculumscount'],
                "data" => $data
            );
        }
        return $return;
    }
    /**
     * [viewcurriculumsessions description]
     * @method viewcurriculumsessions
     * @param  [type]                $bclcid [description]
     * @param  [type]                $stable      [description]
     * @return [type]                             [description]
     */
    public function viewcurriculumsessions($bclcdata, $stable, $userview = false, $enrolmentpending = false,$tab = null) {
        global $OUTPUT, $CFG, $DB, $USER;
        $curriculumid = $bclcdata->curriculumid;
        $semesterid = $bclcdata->semesterid;
        $bclcid = $bclcdata->bclcid;
        $programid = $bclcdata->programid;
        $yearid = $bclcdata->yearid;
        $courseid = $bclcdata->courseid;
        $context = context_system::instance();
        
        $curriculumcompletionstatus = $DB->get_field('local_curriculum_users', 'completion_status', array('curriculumid' => $bclcdata->curriculumid, 'userid' => $USER->id));
        if ($stable->thead) {
            $return = '';
            $lc_course = $DB->get_record('local_cc_semester_courses', array('id' => $bclcid));
            $completionid  = $DB->get_field('local_classroom_completion', 'id', array('classroomid' => $bclcid));
            $id = !empty($completionid) ? $completionid : 0;
            if ((has_capability('local/program:createsession', $context) && (has_capability('local/program:manageprogram', $context))) || (has_capability('local/program:createsession', $context) && has_capability('local/costcenter:manage_owndepartments', $context))) {
                $return .= '<ul class="course_extended_menu_list">
                                <li>
                                    <div class="createicon course_extended_menu_itemlink">
                                        <a class="create_session createpopicon" title="Create Session" onclick="(function(e){ require(\'local_program/ajaxforms\').init({contextid:' . $context->id . ', component:\'local_program\', callback:\'session_form\', form_status:0, plugintype: \'local_program\', pluginname: \'session\', id:0, ccid: ' . $curriculumid . ', semesterid: '. $semesterid .', ccses_action: \''.$bclcdata->ccses_action.'\', bclcid: ' . $bclcid . ',programid: ' . $programid . ',yearid: ' . $yearid . ',courseid: ' . $courseid . ', title: \'addsession\' }) })(event)">
                                            <i class="fa fa-plus icon" aria-hidden="true"></i>
                                        </a>
                                    </div>
                                </li>';
            }
            if ((has_capability('local/program:createsession', $context) && (has_capability('local/program:manageprogram', $context))) || (has_capability('local/program:createsession', $context) && has_capability('local/costcenter:manage_owndepartments', $context))) {
                $return .= '<li>
                                    <div class="createicon course_extended_menu_itemlink">
                                        <a class="" href="javascript:void(0)" title = "Classroom completion Settings" onclick="(function(e){ require(\'local_program/ajaxforms\').init({contextid:1, component:\'local_program\', callback:\'classroom_completion_form\', form_status:0, plugintype: \'local_program\', pluginname: \'classroom_completion_settings\', id:'.$id.', ccid: ' . $curriculumid . ', semesterid: '. $semesterid .', ccses_action: \''.$bclcdata->ccses_action.'\', bclcid: ' . $bclcid . ',programid: ' . $programid . ',yearid: ' . $yearid . ',courseid: ' . $courseid . ', title: \'classroomcompletion\'}) })(event)"><i class="icon fa fa-tags fa-fw" aria-hidden="true" aria-label=""></i>
                                        </a> 
                                    </div>
                                </li>';
            }
            // "{{#str}}addfaculty, local_program {{/ str }}"
            $return .= '</ul>';
            $sessions = (new program)->curriculumsessions($bclcdata, $stable, $userview,$tab);
            
            if ($sessions['sessionscount'] > 0) {
                $table = new html_table();
                $table->head = array(get_string('name'), get_string('date'));
                $table->head[] = get_string('time');
                $table->head[] = get_string('room', 'local_program');
                if ((has_capability('local/program:manageprogram', context_system::instance()) || is_siteadmin() || has_capability('local/program:takesessionattendance', context_system::instance()))) {
                    $table->head[] = get_string('status', 'local_program');
                    $table->head[] = get_string('attended_sessions_users', 'local_program');
                }
                if(has_capability('local/program:manageprogram', context_system::instance()) || is_siteadmin() || has_capability('local/program:manage_owndepartments', context_system::instance()) || $userview) {
                    $table->head[] = get_string('faculty', 'local_program');
                }
                if ($userview) {
                    $table->head[] = get_string('seats', 'local_program');
                }

                if ((($userview && !$enrolmentpending) || ($userview && !$curriculumcompletionstatus)) || (has_capability('local/program:manageprogram', context_system::instance()) || is_siteadmin() || has_capability('local/program:takesessionattendance', context_system::instance()))) {
                    $table->head[] = get_string('actions');
                }
                $table->id = 'viewcurriculumsessions';
                $table->attributes['data-bclcid'] = $bclcid;
                $return .= html_writer::table($table);
            } else {
                $return .= "<div class='mt-15 text-xs-center alert alert-info w-full pull-left'>" . get_string('nosessions', 'local_program') . "</div>";
            }
        } else {
            $curriculumuser = $DB->record_exists('local_curriculum_users', array('curriculumid' => $bclcdata->curriculumid, 'userid' => $USER->id));
            $userview = false;
            if ($curriculumuser && !is_siteadmin() && !has_capability('local/program:createprogram', $context)) {
                $bcsemester = new stdClass();
                $bcsemester->curriculumid = $bclcdata->curriculumid;
                $bcsemester->semesterid = $bclcdata->semesterid;
                $notcmptlcourses = (new program)->mynextsemestercourses($bcsemester);
                unset($notcmptlcourses[0]);
                $enrolmentpending = false;
                if (!empty($notcmptlcourses) && array_search($bclcid, $notcmptlcourses) !== false) {
                    $enrolmentpending = true;
                }

                $lastcompletiondate = $DB->get_field('local_cc_semester_cmptl',
                    'completiondate', array('curriculumid' => $bclcdata->curriculumid,
                        'semesterid' => $bclcdata->semesterid, 'userid' => $USER->id));

                $mylastattendedsession = (new program)->myattendedsessions($bclcdata, true);
                if (empty($mylastattendedsession)) {
                    $mylastattendedsession = new stdClass();
                    $mylastattendedsession->timefinish = 0;
                }
                $userview = true;
            }
            $sessions = (new program)->curriculumsessions($bclcdata, $stable, $userview, $tab);
            $data = array();
            $absentsessions = false;
            if ($curriculumuser && !is_siteadmin() && !has_capability('local/program:createprogram', $context)) {
                if($bclcdata->ccses_action == "semssessions"){
                    $absentsessions = $DB->count_records('local_cc_session_signups',
                        array('curriculumid' => $bclcdata->curriculumid,
                            'semesterid' => $bclcdata->semesterid, 'yearid' => $bclcdata->yearid,
                            'userid' => $USER->id, 'completion_status' => 2));
                    $presentsessions = $DB->count_records('local_cc_session_signups',
                        array('curriculumid' => $bclcdata->curriculumid,
                            'semesterid' => $bclcdata->semesterid, 'yearid' => $bclcdata->yearid,
                            'userid' => $USER->id, 'completion_status' => 1));
                    $activesessions = $DB->count_records('local_cc_session_signups',
                        array('curriculumid' => $bclcdata->curriculumid,
                            'semesterid' => $bclcdata->semesterid, 'yearid' => $bclcdata->yearid,
                            'userid' => $USER->id, 'completion_status' => 0));                    
                }else{
                    $absentsessions = $DB->count_records('local_cc_session_signups',
                        array('curriculumid' => $bclcdata->curriculumid,
                            'semesterid' => $bclcdata->semesterid, 'yearid' => $bclcdata->yearid,
                            'userid' => $USER->id, 'bclcid' => $bclcdata->bclcid, 'completion_status' => 2));
                    $presentsessions = $DB->count_records('local_cc_session_signups',
                        array('curriculumid' => $bclcdata->curriculumid,
                            'semesterid' => $bclcdata->semesterid, 'yearid' => $bclcdata->yearid,
                            'userid' => $USER->id, 'bclcid' => $bclcdata->bclcid, 'completion_status' => 1));
                    $activesessions = $DB->count_records('local_cc_session_signups',
                        array('curriculumid' => $bclcdata->curriculumid,
                            'semesterid' => $bclcdata->semesterid, 'yearid' => $bclcdata->yearid,
                            'userid' => $USER->id, 'bclcid' => $bclcdata->bclcid, 'completion_status' => 0));
                }
            }

            foreach ($sessions['sessions'] as $sdata) {
                
                $line = array();
                $line[] = $sdata->name;
                $line[] = '<i class="fa fa-calendar" aria-hidden="true"></i> ' .
                            date("d/m/Y", $sdata->timestart);
                /*$line[] = '<i class="fa fa-clock-o"></i> ' . date("H:i:s", $sdata->timestart) . ' <b> - </b> ' . date("H:i:s", $sdata->timefinish);*/
                $starttimehour_mins = ($sdata->dailysessionstarttime) ? $sdata->dailysessionstarttime : 00;
                $endtimehour_mins = ($sdata->dailysessionendtime) ? $sdata->dailysessionendtime : 00;
                $line[] = '<i class="fa fa-clock-o"></i> ' . $starttimehour_mins . ' <b> - </b> ' . $endtimehour_mins;
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
                } /*else{
                    if ($sdata->timefinish <= time() && $attendance_status == 1) {
                        $line[] = get_string('completed', 'local_program');
                    } else {
                        $line[] = get_string('pending', 'local_program');
                    }
                }*/
                if (has_capability('local/program:manageprogram', context_system::instance()) || is_siteadmin() || has_capability('local/program:manage_owndepartments', context_system::instance()) || has_capability('local/program:takesessionattendance', context_system::instance())) {
                    $line[] = $sdata->activeusers . '/' . $sdata->totalusers;
                }
                if(has_capability('local/program:manageprogram', context_system::instance()) || is_siteadmin() || has_capability('local/program:manage_owndepartments', context_system::instance()) || $userview) {
                    if($sdata->trainerid) {
                        $trainer = $DB->get_record('user', array('id' => $sdata->trainerid));
                        $line[] = $OUTPUT->user_picture($trainer, array('size' => 30)) . fullname($trainer);
                    }else{
                        $line[] = "N/A";
                    }
                }
                if ($userview) {
                    $line[] = $sdata->signups . ' / ' . $sdata->totalusers;
                }
                $action = '';
                if ((has_capability('local/program:editsession', context_system::instance()) || is_siteadmin())&&(has_capability('local/program:manageprogram', context_system::instance())) || (has_capability('local/program:editsession', context_system::instance()) && has_capability('local/program:manage_owndepartments', context_system::instance()))) {
                    $action .= '<a href="javascript:void(0);" alt = ' . get_string('edit') . ' title = ' . get_string('edit') . ' onclick="(function(e){ require(\'local_program/ajaxforms\').init({contextid:1, component:\'local_program\', callback:\'session_form\', form_status:0, plugintype: \'local_program\', pluginname: \'session\', id: ' . $sdata->id . ', ccid: ' . $curriculumid .', yearid:'.$sdata->yearid.', programid:'.$sdata->programid.', semesterid: '. $semesterid .', bclcid: '.$bclcid.', ccses_action: \''.$bclcdata->ccses_action.'\', title: \'updatesession\'}) })(event)" ><img src="' . $OUTPUT->image_url('t/edit') . '" alt = ' . get_string('edit') . ' title = ' . get_string('edit') . ' class="icon"/></a>';
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
                         $action .= '<a href="javascript:void(0);" alt = '.get_string('delete').' title = '.get_string('delete').' onclick="(function(e){ require(\'local_program/program\').deleteConfirm({action:\'cannotdeletesession\', curriculumid: '.$sdata->curriculumid.', semesterid: '.$semesterid.', bclcid: '.$bclcid.', sessioncount: '.$clroomsessionscount.', id: '.$sdata->id.' }) })(event)" ><img src="' .$OUTPUT->image_url('t/delete').'" alt = '.get_string('delete').' title = '.get_string('delete').' class="icon"/></a>';
                    } else {
                         $action .= '<a href="javascript:void(0);" alt = '.get_string('delete').' title = '.get_string('delete').' onclick="(function(e){ require(\'local_program/program\').deleteConfirm({action:\'deletesession\', curriculumid: '.$sdata->curriculumid.', semesterid: '.$semesterid.', bclcid: '.$bclcid.', id: '.$sdata->id.' }) })(event)" ><img src="'.$OUTPUT->image_url('t/delete').'" alt = '.get_string('delete').' title = '.get_string('delete').' class="icon"/></a>';
                    }
                }
                /*if(has_capability('local/program:manageusers', context_system::instance()) || is_siteadmin() || has_capability('local/program:manage_owndepartments', context_system::instance())) {
                    $action .= '<a href="' . $CFG->wwwroot . '/local/program/session_enrolusers.php?ccid=' . $sdata->curriculumid . '&semesterid=' . $semesterid . '&bclcid=' . $bclcid . '&sid=' . $sdata->id . '&programid='.$sdata->programid.'&yearid='.$sdata->yearid.'&ccses_action='.$bclcdata->ccses_action.'"><img src="' . $OUTPUT->image_url('t/groups') . '"  alt = "'.get_string('sessionenrolments', 'local_program').'"  title = "'.get_string('sessionenrolments', 'local_program').'" class="icon"/></a>';
                }*/
                if ((has_capability('local/program:takesessionattendance', context_system::instance()) || is_siteadmin())/*&&(has_capability('local/program:manageprogram', context_system::instance()))*/) {
                    $action .= '<a href="' . $CFG->wwwroot . '/local/program/attendance.php?ccid=' . $sdata->curriculumid . '&semesterid=' . $semesterid . '&bclcid=' . $bclcid . '&sid=' . $sdata->id . '&programid='.$sdata->programid.'&yearid='.$sdata->yearid.'&ccses_action='.$bclcdata->ccses_action.'" ><img src="' . $OUTPUT->image_url('t/assignroles') . '" alt = ' . get_string('attendace', 'local_program') . ' title = ' . get_string('attendace', 'local_program') . ' class="icon"/></a>';
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
                        // if ($diff->days < 3) {
                        //     $sessionactionstatus = false;
                        // }
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
                            $line[] = '<a href="javascript:void(0);" class="sessionenrol" data-curriculumid='.$sdata->curriculumid.' data-semesterid='.$sdata->semesterid.' data-bclcid='.$bclcid.' data-programid='.$sdata->programid.' data-yearid='.$sdata->yearid.' data-sessionid='.$sdata->id.' data-signupid=0 data-ccses_action='.$bclcdata->ccses_action.' data-enrol=2 >Re Schedule</a>';
                        } else if ((($sdata->signups < $sdata->maxcapacity) && ($sdata->timestart > $mylastattendedsession->timefinish) && ($sdata->signupid == 0 || $sdata->signupid == null) && $presentsessions == 0 && $curriculumcompletionstatus == 0 && $sessionactionstatus) && ($sdata->mysignupstatus == 0 || $absentsessions)) {
                            $line[] = '<a href="javascript:void(0);" class="sessionenrol" data-curriculumid='.$sdata->curriculumid.' data-semesterid='.$sdata->semesterid.' data-bclcid='.$bclcid.' data-programid='.$sdata->programid.' data-yearid='.$sdata->yearid.' data-ccses_action='.$bclcdata->ccses_action.' data-sessionid='.$sdata->id.'
                            data-signupid=0 data-enrol=1 >Enrol</a>';
                        } /*else if ($presentsessions > 0) {
                            $line[] = 'Already one session completed in this course.';
                        } */else if ($sdata->signups == $sdata->maxcapacity && !$sdata->signupid){
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
            $return = array(
                "recordsTotal" => $sessions['sessionscount'],
                "recordsFiltered" => $sessions['sessionscount'],
                "data" => $data
            );
        }
        // print_object($data);exit;
        return $return;
    }

    /**
     * [viewcurriculumsessionstabs description]
     * @method viewcurriculumsessions
     * @param  [type]                $bclcid [description]
     * @param  [type]                $stable      [description]
     * @return [type]                             [description]
     */
    public function viewcurriculumsessionstabs($bclcdata, $stable, $userview = false, $enrolmentpending = false, $tab) {
        /*print_object($bclcdata);
        print_object($tab);*/
        global $CFG, $OUTPUT;
        $systemcontext = context_system::instance();
        $toprow = array();
        if ($tab) {
            $toprow[] = new tabobject('upcomingsessions', new moodle_url("/local/program/sessions.php?action=upcomingsessions&ccses_action=$bclcdata->ccses_action&ccid=$bclcdata->curriculumid&semesterid=$bclcdata->semesterid&bclcid=$bclcdata->bclcid&yearid=$bclcdata->yearid&courseid=$bclcdata->courseid&programid=$bclcdata->programid"), get_string('upcomingsessions', 'local_program'));
            $toprow[] = new tabobject('completedsessions', new moodle_url("/local/program/sessions.php?action=completedsessions&ccses_action=$bclcdata->ccses_action&ccid=$bclcdata->curriculumid&semesterid=$bclcdata->semesterid&bclcid=$bclcdata->bclcid&yearid=$bclcdata->yearid&courseid=$bclcdata->courseid&programid=$bclcdata->programid"), get_string('completedsessions', 'local_program'));

        }
       echo $OUTPUT->tabtree($toprow, $tab);
    }
    public function viewcurriculumsemesteryears($curriculumid, $yearid) {
        global $OUTPUT, $CFG, $DB, $USER;
        $systemcontext = context_system::instance();
        $assign_courses = '';
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
            foreach ($curriculumsemesteryears as $k => $curriculumsemesteryear) {
                $activeclass = '';
                $disabled = '';
                $yearname = strlen($curriculumsemesteryear->year) > 11 ? substr($curriculumsemesteryear->year, 0, 11) ."<span class='semesterrestrict'>..</span>": $curriculumsemesteryear->year;

                $curriculumsemesteryear->year = "<span title='".$curriculumsemesteryear->year."'>".$yearname."</span>";
                if ($curriculumsemesteryear->id == $yearid) {
                    $activeclass = 'active';
                }
                // if ($userview && !is_siteadmin() && !has_capability('local/program:createprogram', $systemcontext) && array_search($curriculumsemesteryear->id, $mycompletedsemesters) === false
                //     && $nextsemester != $curriculumsemesteryear->id) {
                //     $disabled = 'disabled';
                // }

                $canmanagesemesteryear = false;
                if ($userview && !is_siteadmin() && !has_capability('local/program:createprogram', $systemcontext)) {
                    $yearrecordexists = $DB->record_exists('local_ccuser_year_signups',  array('userid' => $USER->id, 'yearid' => $curriculumsemesteryear->id, 'programid' => $curriculumsemesteryear->programid));
                    if (!$yearrecordexists) {
                        $disabled = 'disabled';
                    }
                    // $completion_status = $DB->get_field('local_cc_semester_cmptl', 'completion_status', array('curriculumid'=>$curriculumid, 'yearid'=> $curriculumsemesteryear->id, 'userid' => $USER->id));
                    $completion_status = $DB->get_field_sql('SELECT completion_status FROM {local_ccuser_year_signups} WHERE curriculumid ='.$curriculumid.' AND yearid ='.$curriculumsemesteryear->id.' AND userid ='.$USER->id.' AND programid ='.$curriculumsemesteryear->programid);
                    $curriculumsemesteryear->mycompletionstatus = '';
                    if ($userview && $completion_status == 1) {
                        $curriculumsemesteryear->mycompletionstatus = 'Completed';
                    }

                } else {
                    if (has_capability('local/program:managesemesteryear', $systemcontext) || is_siteadmin()) {
                        $checkstudents = $DB->record_exists('local_cc_session_signups', array('curriculumid' => $curriculumid, 'yearid' => $curriculumsemesteryear->id));

                        if (($checkstudents || !$programtemplatestatus)) {
                            $canmanagesemesteryear = false;
                        } else {
                            $canmanagesemesteryear = true;
                        }
                    }
                }

                // $session = $DB->get_record('local_cc_session_signups', array('curriculumid'=>$curriculumid, 'completion_status'=>0));
                $curriculumsemesteryear->myinprogressstatus = '';
                if ($userview && $completion_status == 0) {
                    $curriculumsemesteryear->myinprogressstatus = 'Inprogress';
                }
                $curriculumsemesteryear->active = $activeclass;
                $curriculumsemesteryear->disabled = $disabled;

                $semestercount_records = $DB->count_records('local_curriculum_semesters',
                array('curriculumid' => $curriculumid, 'yearid' => $curriculumsemesteryear->id));

                $candeletesemesteryear = false;
                if ($semestercount_records > 0 && has_capability('local/program:deletesemesteryear',
                    $systemcontext)) {
                    $candeletesemesteryear = false;
                } else if (has_capability('local/program:deletesemesteryear', $systemcontext)) {
                    $candeletesemesteryear = true;
                }
                $curriculumsemesteryear->candeletesemesteryear = $candeletesemesteryear;
                $curriculumsemesteryear->canmanagesemesteryear = $canmanagesemesteryear;
                $curriculumsemesteryears[$k] = $curriculumsemesteryear;
            }
        }
         // Amulya 705 issue year display for user - starts
        $sql = "SELECT yearid FROM {local_ccuser_year_signups} WHERE curriculumid = $curriculumid AND userid = $USER->id";
        $useryearids = $DB->get_field_sql($sql);
        // Amulya 705 issue year display for user - ends
        $curriculumsemesterscontext = [
            'contextid' => $systemcontext->id,
            'curriculumid' => $curriculumid,
            'cancreatesemesteryear' => has_capability('local/program:createsemesteryear', $systemcontext),
            'canviewsemesteryear' => has_capability('local/program:viewsemesteryear', $systemcontext),
            'canaddsemesteryear' => has_capability('local/program:createsemesteryear', $systemcontext) || is_siteadmin(),
            'caneditsemesteryear' => has_capability('local/program:editsemesteryear', $systemcontext) || is_siteadmin(),
            'cancreatesemester' => has_capability('local/program:createsemester', $systemcontext) || is_siteadmin(),
            'canenrolcourse' => has_capability('local/program:enrolcourse', $systemcontext) && !is_siteadmin(),
            'cfg' => $CFG,
            'yearid' => $yearid,
            'useryearid'=>$useryearids,
            'cantakeattendance' => has_capability('local/program:takesessionattendance',
                $systemcontext) && !is_siteadmin(),

            'cansetcost' => has_capability('local/program:cansetcost',
                $systemcontext) || is_siteadmin(),
            'userview' => $userview,
            'curriculumsemesteryears' => array_values($curriculumsemesteryears),
            'curriculumsemesteryear' => $this->viewcurriculumsemesteryear($curriculumid, $yearid)
        ];
        // print_object($curriculumsemesterscontext);
        $return = $this->render_from_template('local_program/yearstab_content',
            $curriculumsemesterscontext);
        return $return;
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
        $affiliatecolleges = $DB->count_records_sql('SELECT COUNT(id) FROM {local_program} WHERE parentid = :parentid AND costcenter <> :costcenter',  array('parentid' => $curriculum->id, 'costcenter' => $curriculum->costcenter));

        $copiedprograms = $DB->count_records_sql("SELECT COUNT(id) FROM {local_program} WHERE parentid = :parentid AND costcenter = :costcenter",  array('parentid' => $curriculum->id, 'costcenter' => $curriculum->costcenter));
        // $costcenterprogram = $DB->get_record_sql("SELECT lc.id FROM {local_costcenter} as lc WHERE lc.id = $curriculum->costcenter ");

       //  $roleid = $DB->get_record_sql("SELECT id FROM {role} WHERE shortname = 'student'");
       //  $sql = "SELECT courseid FROM {local_cc_semester_courses} WHERE programid = $curriculum->program";
       //  $programcourses = $DB->get_fieldset_sql($sql);
       // // $programcourses = implode(',',$programcou);
       //  foreach($programcourses as $programcourse){
       //                  $instance = $DB->get_record('enrol', array('courseid'=> $programcourse, 'enrol'=>'manual'), '*', MUST_EXIST);
       //                  // $enrol = enrol_get_plugin('manual');
       //                  // $enrol->enrol_user($instance, $roleid);
       //  }
        $studetroleid = $DB->get_field('role', 'id', array('shortname' => 'student'));
        $managemyprogram = false;

        if (($curriculum->costcenter == $USER->open_costcenterid) || is_siteadmin()) {
            $managemyprogram = true;
        }

        if ($userview) {
            $mycompletedsemesteryears = (new program)->mycompletedsemesteryears($curriculumid, $USER->id);
            $notcmptlsemesteryears = (new program)->mynextsemesteryears($curriculumid);
            if (!empty($notcmptlsemesteryears)) {
                $nextsemester = $notcmptlsemesteryears[0];
            } else {
                $nextsemester = 0;
            }
        }
        $caneditsemester = ((has_capability('local/program:editsemester', $systemcontext) || is_siteadmin()) && $programtemplatestatus);
        if (!empty($curriculumsemesteryears)) {
            foreach ($curriculumsemesteryears as $k => $curriculumsemesteryear) {
                $activeclass = '';
                $disabled = '';
                $semestername = strlen($curriculumsemesteryear->year) > 11 ? substr($curriculumsemesteryear->year, 0, 11) ."<span class='semesterrestrict'>..</span>": $curriculumsemesteryear->year;

                $curriculumsemesteryear->year = "<span title='".$curriculumsemesteryear->year."'>".$semestername."</span>";
                if ($curriculumsemesteryear->id == $yearid) {
                    $activeclass = 'active';
                }
                // if ($userview && !is_siteadmin() && !has_capability('local/program:createprogram', $systemcontext) && array_search($curriculumsemesteryear->id, $mycompletedsemesteryears) === false
                //     && $nextsemester != $curriculumsemesteryear->id) {
                //     $disabled = 'disabled';
                // }

                if ($userview && !is_siteadmin() && !has_capability('local/program:createprogram', $systemcontext)) {
                    $yearrecordexists = $DB->record_exists('local_ccuser_year_signups',  array('userid' => $USER->id, 'yearid' => $curriculumsemesteryear->id, 'programid' => $curriculumsemesteryear->programid));
                    if (!$yearrecordexists) {
                        $disabled = 'disabled';
                    }

                }
               // echo $curriculumid.'........'.$yearid.'...'.$USER->id;
            /*    $completion_status = $DB->get_field('local_cc_session_signups', 'completion_status', array('curriculumid' => $curriculumid, 'yearid' => $curriculumsemesteryear->id, 'userid' => $USER->id));*/
            if ($userview && !is_siteadmin() && !has_capability('local/program:createprogram', $systemcontext)) {
                $completion_status = $DB->get_field_sql('SELECT completion_status FROM {local_ccuser_year_signups} WHERE curriculumid ='.$curriculumid.' AND yearid ='.$curriculumsemesteryear->id.' AND userid ='.$USER->id.' AND programid ='.$curriculumsemesteryear->programid);
            }/*else{
                $completion_status = $DB->get_field_sql('SELECT completion_status FROM {local_ccuser_year_signups} WHERE curriculumid ='.$curriculumid.' AND yearid ='.$curriculumsemesteryear->id.' AND userid ='.$USER->id.' AND programid ='.$curriculum->program);
            }*/// Commented by Harish to fix ERD issue for Faculty role, when faculty enrolled only for classrooms and completion status is required only for student
         
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

        // commented by Harish for removing edit option for Program curriculum view starts here //
        $signupscount = $DB->count_records('local_cc_session_signups',
                array('curriculumid' => $curriculumid, 'yearid' => $yearid));
        $cancreatesemester = false;
        if (($signupscount > 0) && (has_capability('local/program:createsemester', $systemcontext) || is_siteadmin()) && $programtemplatestatus) {
            $cancreatesemester = false;
        } else if ((has_capability('local/program:createsemester', $systemcontext) || is_siteadmin()) && $programtemplatestatus) {
            $cancreatesemester = true;
        }
        // $candoactions = false;
        // if ($signupscount > 0 ) {
        //     $candoactions = false;
        // } else {
        //     $candoactions = true;
        // }
        # AM to get action buttons for semester only classrooms are available - starts
            $candoactions = true;
        # AM to get action buttons for semester only classrooms are available - ends

        // AM added to get assign course icons after the classroom creation - starts
        if((has_capability('local/program:addcourse', $systemcontext) || is_siteadmin())){
           $canaddcourse = true;
        }
        // AM added to get assign course icons after the classroom creation - ends
        // $canaddcourse = false;
        // if (($signupscount > 0) && (has_capability('local/program:addcourse', $systemcontext) || is_siteadmin())) {
        //     $canaddcourse = false;
        // } else if ((has_capability('local/program:addcourse', $systemcontext) || is_siteadmin()) && $programtemplatestatus) {
        //     $canaddcourse = true;
        // }

        $caneditcourse = false;
        if (($signupscount > 0) && (has_capability('local/program:editcourse', $systemcontext) || is_siteadmin())) {
            $caneditcourse = false;
        } else {
            $caneditcourse = true;
        }

        $canmanagecourse = false;
        if (($signupscount > 0) && (has_capability('local/program:managecourse', $systemcontext) || is_siteadmin())) {
            $canmanagecourse = false;
        } else {
            $canmanagecourse = true;
        }

        $signups = $DB->count_records('local_cc_session_signups',
                array('curriculumid' => $curriculumid, 'yearid' => $yearid));
        $canremovecourse = false;
        $cannotremovecourse = false;
        if ((($signups > 0 && has_capability('local/program:removecourse',
                $systemcontext)) || $affiliatecolleges > 0) && $programtemplatestatus) {
            $canremovecourse = false;
            $cannotremovecourse = true;
        } else if (has_capability('local/program:removecourse', $systemcontext) && $programtemplatestatus) {
            $canremovecourse = true;
            $cannotremovecourse = false;
        }
        // commented by Harish for removing edit option for Program curriculum view ends here //
        $canaddclassroom = false;
        if(has_capability('local/program:manage_offlineclassroom', $systemcontext)){
            $canaddclassroom = true;
        }

        $yearsemestercontentcontext = array();

        $yearsemestercontentcontext['contextid'] = $systemcontext->id;
        $yearsemestercontentcontext['candoactions'] = $candoactions;
        $yearsemestercontentcontext['canaddclassroom'] = $canaddclassroom;
        $yearsemestercontentcontext['curriculumid'] = $curriculumid;
        $yearsemestercontentcontext['cancreatesemester'] = $cancreatesemester;
        $yearsemestercontentcontext['canviewsemester'] = has_capability('local/program:viewsemester', $systemcontext);
        $yearsemestercontentcontext['caneditsemester'] = $caneditsemester;
        $yearsemestercontentcontext['canaddcourse'] = $canaddcourse;
        $yearsemestercontentcontext['caneditcourse'] = $caneditcourse;
        $yearsemestercontentcontext['canmanagecourse'] = $canmanagecourse;

        $yearsemestercontentcontext['canremovecourse'] = $canremovecourse;
        $yearsemestercontentcontext['cannotremovecourse'] = $cannotremovecourse;

        $canaddfaculty = false;
        if (((has_capability('local/program:canaddfaculty', $systemcontext)) || is_siteadmin()) /*&& !$checkcopyprogram*/) {
            $canaddfaculty = true;
        }

        $yearsemestercontentcontext['canaddfaculty'] = $canaddfaculty;

        $canmanagefaculty = false;
        if ((has_capability('local/program:canmanagefaculty', $systemcontext) || is_siteadmin()) && !$checkcopyprogram) {
            $canmanagefaculty = true;
        }

        $yearsemestercontentcontext['canmanagefaculty'] = $canmanagefaculty;
        $yearsemestercontentcontext['canenrolcourse'] = ((has_capability('local/program:enrolcourse', $systemcontext) || is_siteadmin()) && !$checkcopyprogram);
        $yearsemestercontentcontext['cancreatesession'] = has_capability('local/program:takesessionattendance', $systemcontext);
        $yearsemestercontentcontext['canenrolsession'] = has_capability('local/program:enrolcourse', $systemcontext) && !is_siteadmin();
        $yearsemestercontentcontext['cantakeattendance'] = has_capability('local/program:takesessionattendance',
                $systemcontext) && !is_siteadmin();
        $yearsemestercontentcontext['cfg'] = $CFG;
        $yearsemestercontentcontext['yearid'] = $yearid;
        $yearsemestercontentcontext['cantakeattendance'] = has_capability('local/program:takesessionattendance',
                $systemcontext) && !is_siteadmin();
        $yearsemestercontentcontext['userview'] = $userview;
        // changes by harish for IUMS-377 starts here //
        if($userview){
            $curriculumsemesters = (new program)->curriculumsemesteryear($curriculumid, $yearid, $USER->id);
        }else{
            $curriculumsemesters = (new program)->curriculumsemesteryear($curriculumid, $yearid);
        }
        // changes by harish for IUMS-377 ends here //
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
                //Changes by Harish for displaying offline Classrooms based on Sessions created under classroom and mapped to Faculty  
                $clroomsql = "SELECT session.bclcid AS cc_courseid, clroom.classname, clroom.requiredsessions, clroom.courseid 
                            FROM {local_cc_course_sessions} AS session
                            JOIN {local_cc_semester_classrooms} AS clroom ON clroom.id = session.bclcid
                           WHERE session.programid = $curriculumsemester->programid
                             AND session.curriculumid = $curriculumsemester->curriculumid
                             AND session.semesterid = $curriculumsemester->semesterid
                             AND session.yearid = $curriculumsemester->yearid
                             AND session.trainerid = $USER->id
                             GROUP BY session.bclcid";
                $offlineclassrooms = $DB->get_records_sql($clroomsql);
                if($offlineclassrooms){
                    $offlineclrooms = array();
                        foreach ($offlineclassrooms as $classroom) {
                            $offlineclrooms[] = $classroom; 
                        }
                    $curriculumsemester->offlineclassrooms = array_values($offlineclrooms);
                }
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
            $curriculumsemester->caneditcurrentsemester = false;
            $curriculumsemester->candeletecurrentsemester = false;
        } else {
            $parentsemcmplstatus = 0;
            $ccyearfirstsem = 1;
            foreach ($curriculumsemesters as $curriculumsemester) {
                $isStudent = !has_capability ('moodle/course:update', $systemcontext) ? true : false;
                // $isStudent = user_has_role_assignment($USER->id,5);
                if($isStudent){
                    $curriculumsemester->parentsemcmplstatus = ($parentsemcmplstatus) ? true : false;
                    $curriculumsemester->ccyearfirstsem = ($ccyearfirstsem) ? true : false;
                }else{
		    $curriculumsemester->parentsemcmplstatus = true;
                    $curriculumsemester->ccyearfirstsem = true;
                }
                $ccyearfirstsem = 0;
                
                // $courses = array();
                $courses = $DB->get_records_sql('SELECT c.id as courseid, c.fullname as course, ccsc.importstatus, ccsc.coursetype, ccsc.id AS cc_courseid 
                                                   FROM {course} c
                                                   JOIN {local_cc_semester_courses} ccsc ON ccsc.courseid = c.id
                                                   WHERE ccsc.semesterid = :semesterid AND ccsc.yearid = :yearid', array('semesterid' => $curriculumsemester->semesterid, 'yearid' => $yearid));
                $curriculumsemester->courses = array_values($courses);
                $offlineclassrooms = $DB->get_records_sql("SELECT id AS cc_courseid, classname, requiredsessions, courseid 
                                                             FROM {local_cc_semester_classrooms} 
                                                             WHERE curriculumid = $curriculumsemester->curriculumid 
                                                               AND semesterid = $curriculumsemester->semesterid 
                                                               AND yearid = $curriculumsemester->yearid");
                /*if(!empty($offlineclassrooms)){
                    foreach($offlineclassrooms as $class){
                        $courses[$class->cc_courseid] = $class;
                    }
                }*/
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
                        /*$courses['coursetype'] = false;
                        $courses['completioncriteria'] = false;*/
                        $courses[$course->courseid]->coursetype = false;
                        $courses[$course->courseid]->completioncriteria = false;
                    }
                }

                /*if($courseids){
                    $curriculumsemester->coursecompletions = $DB->get_records_sql("SELECT ccc.id, ccc.course FROM {course_completion_criteria} ccc JOIN {local_cc_semester_courses} lcsc ON ccc.course = lcsc.courseid WHERE course IN ($courseids)");
                unset($courseids);
                }else{
                    $curriculumsemester->coursecompletions = '';
                }*/
                $semesteruserscount = $DB->count_records('local_cc_semester_cmptl', array('curriculumid' => $curriculumid, 'yearid' => $yearid));
                $curriculumsemester->usersemcompletionstatus = ($curriculumsemester->semcompletionstatus) ? true : false;
                $parentsemcmplstatus = ($curriculumsemester->semcompletionstatus) ? true : false;
                $curriculumsemester->caneditcurrentsemester = true;
                $curriculumsemester->candeletecurrentsemester = true;
                if ($semesteruserscount > 0) {
                    $curriculumsemester->caneditcurrentsemester = false;
                    $curriculumsemester->candeletecurrentsemester = false;
                }
            }
        }
    
        $coursesadded = $DB->record_exists('local_cc_semester_courses', array('curriculumid' => $curriculumid, 'yearid' => $yearid));
        $classroomadded = $DB->record_exists('local_cc_semester_classrooms',array('curriculumid' => $curriculumid, 'yearid' => $yearid));
        $yearsemestercontentcontext['coursesadded'] = (!empty($coursesadded) || !empty($classroomadded));
        $yearsemestercontentcontext['curriculumsemesters'] = array_values($curriculumsemesters);
        $yearsemestercontentcontext['curriculumsemesteryears'] = array_values($curriculumsemesteryears);
        $yearsemestercontentcontext['semesters'] = $semesters;
        $yearsemestercontentcontext['programid'] = $curriculum->program;
        // $yearsemestercontentcontext['costcenter'] = $costcenterprogram;
        $yearsemestercontentcontext['roleid'] = $studentrole;
        $yearsemestercontentcontext['useryearid'] = $useryearid;
        $yearsemestercontentcontext['canimportcoursecontent'] = (has_capability('local/program:importcoursecontent', $systemcontext) || is_siteadmin()) && $curriculum->admissionenddate < time();
        $yearsemestercontentcontext['issiteadmin'] = (has_capability('local/program:manageprogram', $systemcontext) || is_siteadmin());
        //print_object($yearsemestercontentcontext);exit;
        $return = $this->render_from_template('local_program/yearsemestercontent',
            $yearsemestercontentcontext);
        return $return;
    }
    public function viewcurriculumsemesters($curriculumid, $yearid) {
        global $OUTPUT, $CFG, $DB, $USER;
        $systemcontext = context_system::instance();
        $assign_courses = '';
        $ccuser = $DB->record_exists('local_curriculum_users',
            array('curriculumid' => $curriculumid, 'userid' => $USER->id));
        $userview = $ccuser && !is_siteadmin() && !has_capability('local/program:createprogram', $systemcontext) ? true : false;

            $signups = $DB->count_records('local_cc_session_signups',
                array('curriculumid' => $curriculumid, 'semesterid' => $semesterid,
                    'yearid' => $bcsemestercourse->yearid));
            $canremovecourse = false;
            $cannotremovecourse = false;
            if ($signups > 0 && has_capability('local/program:removecourse',
                $systemcontext)) {
                $canremovecourse = false;
                $cannotremovecourse = true;
            } else if (has_capability('local/program:removecourse', $systemcontext)) {
                $canremovecourse = true;
                $cannotremovecourse = false;
            }
            $bcsemestercourse->canremovecourse = $canremovecourse;
            $bcsemestercourse->cannotremovecourse = $cannotremovecourse;


        $curriculumsemesterscontext = [
            'contextid' => $systemcontext->id,
            'curriculumid' => $curriculumid,
            'cancreatesemester' => has_capability('local/program:createsemester', $systemcontext) || is_siteadmin(),
            'canviewsemester' => has_capability('local/program:viewsemester', $systemcontext),
            'caneditsemester' => has_capability('local/program:editsemester', $systemcontext),
            'canaddcourse' => has_capability('local/program:addcourse', $systemcontext),
            'caneditcourse' => has_capability('local/program:editcourse', $systemcontext),
            'canmanagecourse' => has_capability('local/program:managecourse', $systemcontext),
            'cancreatesession' => has_capability('local/program:createsession', $systemcontext),
            'canenrolsession' => has_capability('local/program:enrolcourse', $systemcontext) && !is_siteadmin(),
            'cfg' => $CFG,
            'yearid' => $yearid,
            'cantakeattendance' => has_capability('local/program:takesessionattendance',
                $systemcontext) && !is_siteadmin(),
            'curriculumsemester' => $curriculumsemester,
            'userview' => $userview,
            'curriculumsemesters' => array_values($curriculumsemesters)
            // 'semestercourses' => $this->viewcurriculumcourses($curriculumid, $yearid)
        ];
        $return = $this->render_from_template('local_program/yearsemestercontent',
            $curriculumsemesterscontext);
        return $return;
    }
    /**
     * [viewcurriculumcourses description]
     * @method viewcurriculumcourses
     * @param  [type]               $curriculumid [description]
     * @return [type]                            [description]
     */
    public function viewcurriculumcourses($curriculumid, $semesterid) {
        global $OUTPUT, $CFG, $DB, $USER;
        $systemcontext = context_system::instance();
        $assign_courses = '';
        $bcuser = $DB->record_exists('local_curriculum_users',
            array('curriculumid' => $curriculumid, 'userid' => $USER->id));
        $userview = $bcuser && !is_siteadmin() && !has_capability('local/program:createprogram', $systemcontext) ? true : false;
        $bcsemester = new stdClass();
        $bcsemester->curriculumid = $curriculumid;
        $bcsemester->semesterid = $semesterid;

        if ($userview) {
            $mycompletedsemesters = (new program)->mycompletedsemesters($curriculumid, $USER->id);
            $notcmptlsemesters = (new program)->mynextsemesters($curriculumid);
            if (!empty($notcmptlsemesters)) {
                $nextsemester = $notcmptlsemesters[0];
            } else {
                $nextsemester = 0;
            }
        }



        $curriculumsemestercourses =
            (new program)->curriculum_semester_courses($curriculumid, $semesterid, $userview);
        if ($userview) {
            $notcmptlcourses = (new program)->mynextsemestercourses($bcsemester);
        }
        $curriculumsemester = $DB->get_record('local_curriculum_semesters', array('curriculumid' => $curriculumid, 'id' => $semesterid));

        $curriculumsemester->mycompletionstatus = '';
        if ($userview && array_search($curriculumsemester->id, $mycompletedsemesters) !== false) {
            $curriculumsemester->mycompletionstatus = 'Completed';
        }
        $session = $DB->get_record('local_cc_session_signups', array('curriculumid'=>$curriculumid, 'semesterid'=>$curriculumsemester->id, 'completion_status'=>0));
        $curriculumsemester->myinprogressstatus = '';
        if ($userview && array_search($curriculumsemester->id, $mycompletedsemesters) === false && !empty($session)) {
            $curriculumsemester->myinprogressstatus = 'Inprogress';
        }

        foreach ($curriculumsemestercourses as $i => $bcsemestercourse) {
            $bcsemestercourses = array();
            $courseurl = new \moodle_url('/course/view.php', array('id' => $bcsemestercourse->id));
            $courselink = strlen($bcsemestercourse->course) > 25 ? substr($bcsemestercourse->course, 0, 25) . "..." : $bcsemestercourse->course;
            $bcsemestercourse->course = html_writer::link($courseurl, $courselink,
                    array('title' => $bcsemestercourse->course));
            if ($userview) {
                $bcsemestercourse->sessionenabled = true;
                if (array_search($bcsemestercourse->bcsemestercourseid, $notcmptlcourses) !== false) {
                    $bcsemestercourse->sessionenabled = false;
                    $bcsemestercourse->coursecompletionstatus = '';
                } else {
                    $bcsemestercourse->coursecompletionstatus = 'Completed';
                }
            }

            $curriculumsemestercourses[$i] = $bcsemestercourse;
            $count_records = $DB->get_records('local_cc_session_signups',
                array('curriculumid' => $curriculumid, 'semesterid' => $semesterid,
                    'yearid' => $bcsemestercourse->yearid));
            $canremovecourse = false;
            $cannotremovecourse = false;
            if (count($count_records) > 0 && has_capability('local/program:removecourse',
                $systemcontext)) {
                $canremovecourse = false;
                $cannotremovecourse = true;
            } else if (has_capability('local/program:removecourse', $systemcontext)) {
                $canremovecourse = true;
                $cannotremovecourse = false;
            }
            $bcsemestercourse->canremovecourse = $canremovecourse;
            $bcsemestercourse->cannotremovecourse = $cannotremovecourse;
            $curriculumsemestercourses[$i] = $bcsemestercourse;
        }
        $systemcontext = context_system::instance();
        $curriculumcoursescontext = [
            'contextid' => $systemcontext->id,
            'curriculumid' => $curriculumid,
            'canaddcourse' => has_capability('local/program:addcourse', $systemcontext),
            'caneditcourse' => has_capability('local/program:editcourse', $systemcontext),
            'canmanagecourse' => has_capability('local/program:managecourse', $systemcontext),
            'cancreatesession' => has_capability('local/program:createsession', $systemcontext),
            'canenrolsession' => has_capability('local/program:enrolsession', $systemcontext) && !is_siteadmin(),
            'cfg' => $CFG,
            'semesterid' => $semesterid,
            'cantakeattendance' => has_capability('local/program:takesessionattendance',
                $systemcontext) && !is_siteadmin(),
            'curriculumsemester' => $curriculumsemester,
            'userview' => $userview,
            'curriculumsemestercourses' => array_values($curriculumsemestercourses)
        ];
        $return = $this->render_from_template('local_program/levelcoursescontent',
            $curriculumcoursescontext);
        return $return;
    }
    /**
     * Display the curriculum view
     * @return string The text to render
     */
    public function viewcurriculum($curriculumid, $programid = null) {
        global $OUTPUT, $CFG, $DB, $USER, $PAGE;
        // print_object($curriculumid);
        // print_object($programid);
        // exit;
        $systemcontext = context_system::instance();
        $stable = new stdClass();
        $stable->curriculumid = $curriculumid;
        $stable->programid = $programid;
        $stable->thead = false;
        $stable->start = 0;
        $stable->length = 1;
        $curriculum = (new program)->curriculums($stable);
        //print_object($curriculum);exit;
        if (empty($curriculum)) {
            print_error("curriculum Not Found!");
        }
        $programtemplatestatus = (new program)->programtemplatestatus($curriculum->program);

        $managemyprogram = false;
        if ($curriculum->costcenter == $USER->open_costcenterid) {
            $managemyprogram = true;
        }

        $includesfile = false;
        if (file_exists($CFG->dirroot . '/local/includes.php')) {
            $includesfile = true;
            require_once($CFG->dirroot . '/local/includes.php');
            $includes = new user_course_details();
        }

        $return = "";

        $affiliatecolleges = $DB->count_records_sql('SELECT COUNT(id) FROM {local_program} WHERE parentid = :parentid AND costcenter <> :costcenter',  array('parentid' => $curriculum->id, 'costcenter' => $curriculum->costcenter));

        $copiedprograms = $DB->count_records_sql("SELECT COUNT(id) FROM {local_program} WHERE parentid = :parentid AND costcenter = :costcenter",  array('parentid' => $curriculum->id, 'costcenter' => $curriculum->costcenter));
        $checkcopyprogram = (new program)->checkcopyprogram($curriculum->program);
        $curriculumcompletion = $action = $edit = $delete = $assignusers = $assignusersurl = false;
        if ((has_capability('local/program:manageprogram', context_system::instance()) || is_siteadmin())) {
            $action = true;
        }
        if ((has_capability('local/program:programcompletion', context_system::instance()) || is_siteadmin())) {
            $curriculumcompletion = false;
        }
        if (((has_capability('local/program:editprogram', context_system::instance()) || is_siteadmin())) && $programtemplatestatus) {
            $edit = true;
        }

        $cannotdelete = true;
        $delete = false;
        if ((has_capability('local/program:deleteprogram', context_system::instance()) || is_siteadmin())) {
            $count_records = $DB->count_records('local_curriculum_users', array('curriculumid' => $curriculumid));
            if ($count_records > 0 || !$programtemplatestatus) {
                $cannotdelete = true;
                $delete  = false;
            } else {
                $cannotdelete = false;
                $delete = true;
            }
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
        //$curriculum->description =  $DB->get_field('local_program','description',array('id' => $programid));
     
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

        // if ($userview) {
        //     $mycompletedsemesters = (new program)->mycompletedsemesters($curriculumid, $USER->id);
        //     $notcmptlsemesters = (new program)->mynextsemesters($curriculumid);
        //     if (!empty($notcmptlsemesters)) {
        //         $semesterid = $notcmptlsemesters[0];
        //     } else {
        //         $semesterid = $DB->get_field_select('local_curriculum_semesters', 'id',
        //         'curriculumid = :curriculumid ORDER BY id ASC LIMIT 0, 1 ',
        //         array('curriculumid' => $curriculumid));
        //     }
        // } else {
        //     $semesterid = $DB->get_field_select('local_curriculum_semesters', 'id',
        //     'curriculumid = :curriculumid ORDER BY id ASC LIMIT 0, 1 ',
        //     array('curriculumid' => $curriculumid));
        // }

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
        $curriculumcontext = [
            'curriculum' => $curriculum,
            'curriculumid' => $curriculumid,
            'action' => $action,
            'edit' => $edit,
            'curriculumcompletion' => $curriculumcompletion,
            'cannotdelete' => $cannotdelete,
            'delete' => $delete,
            'assignusers' => $assignusers,
            'assignusersurl' => $assignusersurl,
            'bulkenrollusers' => $bulkenrollusers,
            'bulkenrollusersurl' => $bulkenrollusersurl,
            'descriptionstring' => $descriptionstring,
            'description' => $description,
            'descriptionfirstChar' => $descriptionfirstChar,
            'descriptiontheRest' => $descriptiontheRest,
            'isdescription' => $isdescription,
            'curriculumname' => $curriculum->name,
            'cfg' => $CFG,
            'program' => $curriculum->program,
            'curriculumcompletionstatus' => $curriculumcompletionstatus,
            'cancreatesemesteryear' => (has_capability('local/program:createsemesteryear', $systemcontext) && ($programtemplatestatus)),
            'seats_image' => $OUTPUT->image_url('GraySeatNew', 'local_program'),
            'yearid' => $yearid,
            'curriculumsemesteryears' => $this->viewcurriculumsemesteryears($curriculumid, $yearid),
        ];

        return $this->render_from_template('local_program/curriculumContent', $curriculumcontext);
    }
    /**
     * [viewcurriculumusers description]
     * @method viewcurriculumusers
     * @param  [type]             $curriculumid [description]
     * @param  [type]             $stable      [description]
     * @return [type]                          [description]
     */
    public function viewcurriculumusers($stable) {
        global $OUTPUT, $CFG, $DB;
        $curriculumid = $stable->curriculumid;
        $yearid = $stable->yearid;
        // if (has_capability('local/program:manageusers', context_system::instance()) && has_capability('local/program:manageprogram', context_system::instance())) {
        //     $url = new moodle_url('/local/program/enrollusers.php', array('ccid' => $curriculumid));
        //     $assign_users ='<ul class="course_extended_menu_list">
        //                         <li>
        //                             <div class="createicon course_extended_menu_itemlink"><a href="'.$url.'"><i class="icon fa fa-users fa-fw add_curriculumcourse createpopicon cr_usericon" aria-hidden="true" title="'.get_string('viewcurriculum_assign_users', 'local_program').'"></i></a>
        //                             </div>
        //                         </li>
        //                         <li>
        //                             <div class="createicon course_extended_menu_itemlink"><a href="' . $CFG->wwwroot . '/local/program/users.php?download=1&amp;format=xls&amp;type=curriculumwise&amp;ccid='.$curriculumid.'&amp;search='.$search.'" target ="_blank"><i class="icon fa fa-download" aria-hidden="true" title="'.get_string('curriculumdownloadreport', 'local_program').'"></i></a>
        //                             </div>
        //                         </li>
        //                         <li>
        //                             <div class="createicon course_extended_menu_itemlink"><a href="' . $CFG->wwwroot . '/local/program/users.php?download=1&amp;format=xls&amp;type=coursewise&amp;bcid='.$curriculumid.'&amp;search='.$search.'" target ="_blank"><i class="icon fa fa-download" aria-hidden="true" title="'.get_string('coursedownloadreport', 'local_program').'"></i></a>
        //                             </div>
        //                         </li>
        //                     </ul>';
        // } else {
        //     $assign_users = "";
        // }
        $systemcontext = context_system::instance();
        $assign_users = "";
        if ($stable->thead) {
            $curriculumusers = (new program)->curriculumusers($curriculumid, $stable);
            // if ($curriculumusers['curriculumuserscount'] > 0) {// Commented by Harish
                $table = new html_table();
                $table->head = array(get_string('username', 'local_users'), get_string('employeeid', 'local_users'), get_string('email'), get_string('university', 'local_courses'),get_string('status'));
                if(is_siteadmin() || has_capability('local/costcenter:manage_ownorganization',$systemcontext)){
                    $table->head[] =  get_string('actions','local_program');
                }
                $table->id = 'viewcurriculumusers';
                $table->attributes['data-curriculumid'] = $curriculumid;
                $table->attributes['data-yearid'] = $yearid;
                $table->align = array('left', 'left', 'left', 'center');
                $return = $assign_users.html_writer::table($table);
            // } else {
            //     $return = $assign_users."<div class='mt-15 alert alert-info w-full pull-left'>ZZ" . get_string('nocurriculumusers', 'local_program') . "</div>";
            // }// Commented by Harish
        } else {
            $programid = $DB->get_field('local_program','id',array('curriculumid' => $curriculumid));
            $curriculumusers = (new program)->curriculumusers($curriculumid, $stable);
            $data = array();
            foreach ($curriculumusers['curriculumusers'] as $sdata) {
                $university = $DB->get_field('local_costcenter', 'fullname', array('id'=>$sdata->open_costcenterid));
                $userpicture = $OUTPUT->user_picture($sdata, array('size' => 35, 'link' => false));
                $userurl = new moodle_url('/local/users/profile.php', array('id' => $sdata->id));
                $userlink = html_writer::link($userurl, $userpicture .' '. fullname($sdata));
                # AM issue 709 to get year completed status
                $sql = "SELECT lpy.year FROM {local_ccuser_year_signups} AS lcys 
                        JOIN {local_cc_semester_cmptl} AS lsc ON lcys.yearid = lsc.yearid
                        JOIN {local_program_cc_years} as lpy ON lpy.id = lcys.yearid
                        WHERE lcys.programid = $programid AND lcys.userid = $sdata->id
                        AND lcys.completion_status = 1 AND lsc.completion_status = 1 AND lsc.yearid = $sdata->yearid" ;
                $completedyears = $DB->get_field_sql($sql);
                # AM ends here
                $line = array();
                $line[] = $userlink; //$OUTPUT->user_picture($sdata) . ' ' . fullname($sdata);
                $line[] = $sdata->open_employeeid;
                $line[] = $sdata->email;
                $line[] = $university ? $university : 'N/A';
                $line[] = $completedyears ?'<span class="tag tag-success" title="'. $completedyears .' Completed">&#10004;</span>' : '<span class="tag tag-danger" title="Not Completed">&#10006;</span>';
                // $line[] = $sdata->completion_status == 1 ?'<span class="tag tag-success" title="Completed">&#10004;</span>' : '<span class="tag tag-danger" title="Not Completed">&#10006;</span>';
                if(is_siteadmin() || has_capability('local/costcenter:manage_ownorganization',$systemcontext)){
                    $return = (new program)->completionchecking($programid,$curriculumid,$yearid,$sdata->id);
                    $logexist = $DB->get_field('local_userenrolments_log','id',array('programid' => $programid,'curriculumid' => $curriculumid,'yearid' => $yearid,'userid' => $sdata->id));
                    if($logexist){
                        $line[] = '<span class="tag tag-success" title="Completed">Cannot Unassign</span>';
                    }else{
                        if($return){
                            $line[] =  '<a href="javascript:void(0);" alt = ' . get_string('unenrol','local_program') . ' title = ' . get_string('unenrol','local_program') . ' onclick="(function(e){ require(\'local_program/program\').deleteConfirm({action:\'cannotunassignuser\', curriculumid: ' . $curriculumid . ', programid: '.$programid.', yearid: '.$yearid.', userid: ' . $sdata->id . ' }) })(event)" ><img src="' . $OUTPUT->image_url('t/delete') . '" alt = ' . get_string('unenrol','local_program') . ' title = ' . get_string('unenrol','local_program'). ' class="icon"/></a>';
                        }else{
                            $line[] = '<a href="javascript:void(0);" alt = ' . get_string('unenrol','local_program') . ' title = ' . get_string('unenrol','local_program') . ' onclick="(function(e){ require(\'local_program/program\').deleteConfirm({action:\'unassignuser\', curriculumid: ' . $curriculumid . ', programid: '.$programid.', yearid: ' . $yearid . ', userid: ' . $sdata->id . ' }) })(event)" ><img src="' . $OUTPUT->image_url('t/delete') . '" alt = ' . get_string('unenrol','local_program') . ' title = ' . get_string('unenrol','local_program') . ' class="icon"/></a>';
                        }
                    }
                }
                
                $data[] = $line;
            }
            $return = array(
                "recordsTotal" => $curriculumusers['curriculumuserscount'],
                "recordsFiltered" => $curriculumusers['curriculumuserscount'],
                "data" => $data,
            );
        }
        return $return;
    }
    /**
     * [viewcurriculumattendance description]
     * @method viewcurriculumattendance
     * @param  [type]                 $curriculumid [description]
     * @param  integer                $sessionid  [description]
     * @return [type]                             [description]
     */
    public function viewcurriculumattendance($curriculumid, $sessionid = 0) {
        global $PAGE, $OUTPUT, $DB;
        $curriculum = new program();
        // print_object($PAGE);exit;
        $attendees = $curriculum->curriculum_get_attendees($curriculumid, $sessionid);
        $return = '';
        if (empty($attendees)) {
            $return .= "<div class='w-full pull-left text-xs-center alert alert-info'>" . get_string('nocurriculumsessionusers', 'local_program') . "</div>";
        } else {
            $return .= '<form method="post" id="formattendance" action="' . $PAGE->url . '">';
            $return .= '<input type="hidden" name="action" value="attendance" />';
            $params = array();
            $params['curriculumid'] = $curriculumid;
            $sqlsessionconcat = '';
            if ($sessionid > 0) {
                $sqlsessionconcat = " AND id = :sessionid";
                $params['sessionid'] = $sessionid;
            }
            $sessions = $DB->get_fieldset_select('local_cc_course_sessions', 'id',
                'curriculumid = :curriculumid ' . $sqlsessionconcat, $params);
            foreach ($attendees as $attendee) {
                if (!$sessionid) {
                    $attendancestatuslist = $DB->get_records_sql('SELECT sessionid, id AS attendanceid, sessionid, status, userid FROM {local_cc_session_signups} WHERE curriculumid = :curriculumid AND userid = :userid', array('curriculumid' => $curriculumid, 'userid' => $attendee->id));
                }
                $list = array();
                $list[] = $OUTPUT->user_picture($attendee, array('size' => 30)) . fullname($attendee);
                foreach ($sessions as $session) {
                    if ($sessionid > 0) {
                        $attendanceid = $attendee->attendanceid;
                        $attendancestatus = $attendee->completion_status;
                    } else {
                        $attendanceid = isset($attendancestatuslist[$session]->attendanceid) && $attendancestatuslist[$session]->attendanceid > 0 ? $attendancestatuslist[$session]->attendanceid : 0;
                        $attendancestatus = isset($attendancestatuslist[$session]->status) && $attendancestatuslist[$session]->status > 0 ? $attendancestatuslist[$session]->status : 0;
                    }

                    $encodeddata = base64_encode(json_encode(array(
                            'curriculumid' => $curriculumid, 'sessionid' => $session,
                            'userid' => $attendee->id, 'attendanceid' => $attendanceid)));
                    $radio = '<input type="hidden" value="' . $encodeddata . '"
                    name="attendeedata[]">';

                    $check_exist = $DB->get_field('local_cc_session_signups', 'id',
                        array('sessionid' => $session, 'userid' => $attendee->id));
                    if ($check_exist) {
                        $checked = '';
                    } else {
                        $checked = 'checked';
                    }

                    if ($attendancestatus == 2) {
                        $checked = '';
                        $status = $sessionid > 0 ? "Absent" : "A";
                        $status = '<span class="tag tag-danger">'.$status.'</span>';
                    } else if ($attendancestatus == 1) {
                        $status = $sessionid > 0 ? "Present" : "P";
                        $checked = 'checked';
                        $status = '<span class="tag tag-success">'.$status.'</span>';
                    } else {
                        $status = $sessionid > 0 ? "Not yet given" : "NY";
                        $status = '<span class="tag tag-warning">'.$status.'</span>';
                    }
                    $radio .= '<input type="checkbox" name="status[' . $encodeddata .']"
                         ' . $checked  .' class="checksingle'.$session.'">';
                    if ($sessionid > 0) {
                        $list[] = $status;
                    }
                    $list[] = $radio;
                }
                $data[] = $list;
            }
            $table = new html_table();
            $script = "";
            if ($sessionid > 0) {
                $table->head = array('Employee', 'Status', 'Attendance<p><input type=checkbox name=checkAll id=checkAll'.$sessionid.'> Select All</p>');
                $script .= html_writer::script("
                        $('#checkAll$sessionid').change(function () {
                                $('.checksingle$sessionid').prop('checked', $(this).prop('checked'));
                         });
                     ");
            } else {
                $table->head[] = 'Employee';
                foreach ($sessions as $session) {
                    $table->head[] = 'Session ' . $session.'<p><input type=checkbox name=checkAll id=checkAll'.$session.'> Select All</p>';
                    $script .= html_writer::script("
                        $('#checkAll$session').change(function () {
                                $('.checksingle$session').prop('checked', $(this).prop('checked'));
                         });
                     ");
                }
            }
            $table->data = $data;
            $return .= html_writer::table($table);
            $return .= '<input type="submit" name="submit" value="Submit">';
            $return .= '<input type="submit" name="reset" value="Reset Selected">';
            $return .= '</form>';
            $return .= "<div id='result'></div>" . $script;
        }
        return $return;
    }

    public function viewcurriculumlastchildpopup($curriculumid){
        global $OUTPUT, $CFG, $DB, $USER, $PAGE;
        $stable = new stdClass();
        $stable->curriculumid = $curriculumid;
        $stable->thead = false;
        $stable->start = 0;
        $stable->length = 1;
        $curriculum = (new program)->curriculums($stable);
        $context = context_system::instance();
        $curriculum_status = $DB->get_field('local_curriculum', 'status', array('id' => $curriculumid));
        if (!has_capability('local/program:view_newprogramtab', context_system::instance()) && $curriculum_status== 0) {
            print_error("You don't have permissions to view this page.");
        } else if (!has_capability('local/program:view_holdprogramtab', context_system::instance()) &&
            $curriculum_status == 2) {
            print_error("You don't have permissions to view this page.");
        }
        if (empty($curriculum)) {
            print_error("curriculum Not Found!");
        }
        $includesfile = false;
        if (file_exists($CFG->dirroot . '/local/includes.php')) {
            $includesfile = true;
            require_once($CFG->dirroot . '/local/includes.php');
            $includes = new user_course_details();
        }

        $return = "";
        $curriculum->userenrolmentcap = (has_capability('local/program:manageusers', context_system::instance())
            && has_capability('local/program:manageprogram', context_system::instance())
            && $curriculum->status == 0) ? true : false;

        $stable = new stdClass();
        $stable->thead = true;
        $stable->start = 0;
        $stable->length = -1;
        $stable->search = '';


        $allocatedseats = $DB->count_records('local_curriculum_users',
            array('curriculumid' => $curriculumid)) ;
        $coursesummary = strip_tags($course->summary,
                    array('overflowdiv' => false, 'noclean' => false, 'para' => false));
        $description = strip_tags(html_entity_decode($curriculum->description));
        $isdescription = '';
        if (empty($description)) {
           $isdescription = false;
        } else {
            $isdescription = true;
            if (strlen($description) > 250) {
                $decsriptionCut = substr($description, 0, 250);
                $decsriptionstring = strip_tags(html_entity_decode($decsriptionCut));
            } else {
                $decsriptionstring = "";
            }
        }

        $curriculumcontext = [
            'curriculum' => $curriculum,
            'curriculumid' => $curriculumid,
            'allocatedseats' => $allocatedseats,
            'description' => $description,
            'descriptionstring' => $decsriptionstring,
            'isdescription' => $isdescription,
            'contextid' => $context->id,
            'cfg' => $CFG,
            'linkpath' => "$CFG->wwwroot/local/program/view.php?ccid=$curriculumid&prgid=$curriculum->program"
        ];
        return $this->render_from_template('local_program/programview', $curriculumcontext);
    }
    /**
     * [view_curriculum_sessions description]
     * @method view_curriculum_sessions
     * @param  [type]                 $bclcid [description]
     * @param  [type]                 $stable [description]
     * @return [type]                         [description]
     */
    public function view_curriculum_sessions($bclcid, $stable) {
        global $OUTPUT, $CFG, $DB, $USER;
        $context = context_system::instance();
        if ($stable->thead) {
            $return = '';
            $sessions = (new program)->curriculumsessions($bclcid, $stable);
            if ($sessions['sessionscount'] > 0) {
                $table = new html_table();
                if ((has_capability('local/program:manageprogram', context_system::instance()) || is_siteadmin())) {
                    $table->head = array(get_string('name'), get_string('date'));
                    $table->head[] = get_string('type', 'local_program');
                    $table->head[] = get_string('room', 'local_program');
                    $table->head[] = get_string('status', 'local_program');
                    $table->head[] = get_string('trainer', 'local_program');
                } else {
                    $table->head = array(get_string('name'), get_string('date'));
                    $table->head[] = get_string('type', 'local_program');
                    $table->head[] = get_string('room', 'local_program');
                    $table->head[] = get_string('status', 'local_program');
                    $table->head[] = get_string('trainer', 'local_program');
                }

                $table->id = 'viewbcsessions';
                $table->attributes['data-bclcid'] = $bclcid;
                $return .= html_writer::table($table);
            } else {
                $return .= "<div class='mt-15 alert alert-info w-full pull-left text-xs-center'>jklajskaJKL" .
                get_string('nosessions', 'local_program') . "</div>";
            }
        } else {
            $sessions = (new program)->curriculumsessions($bclcid, $stable);
            $data = array();
            foreach ($sessions['sessions'] as $sdata) {
                $line = array();
                $line[] = $sdata->name;
                $line[] = date("Y-m-d H:i:s", $sdata->timestart) . ' to ' .
                                    date("Y-m-d H:i:s", $sdata->timefinish);

                $link = get_string('pluginname', 'local_program');
                if ($sdata->onlinesession == 1) {
                    $moduleids = $DB->get_field('modules', 'id',
                        array('name' => $sdata->moduletype));
                    if ($moduleids) {
                        $moduleid = $DB->get_field('course_modules', 'id',
                            array('instance' => $sdata->moduleid, 'module' => $moduleids));
                        if ($moduleid) {
                            $link = html_writer::link($CFG->wwwroot . '/mod/' . $sdata->moduletype. '/view.php?id=' . $moduleid,
                                get_string('join', 'local_program'),
                                array('title' => get_string('join', 'local_program')));
                            if (!has_capability('local/program:manageprogram', context_system::instance())) {
                                $userenrolstatus = $DB->record_exists('local_curriculum_users', array('curriculumid' => $curriculumid, 'userid' => $USER->id));
                                if (!$userenrolstatus) {
                                    $link = get_string('join', 'local_program');
                                }
                            }
                        }
                    }
                }
                $line[] = $link;
                $line[] = $sdata->room ? $sdata->room : 'N/A';
                if ($sdata->timefinish <= time() && $sdata->attendance_status == 1) {
                    $line[] = get_string('completed', 'local_program');
                } else {
                    $line[] = get_string('pending', 'local_program');
                }
                $trainer = $DB->get_record('user', array('id' => $sdata->trainerid));
                $line[] =  fullname($trainer);
                $data[] = $line;
            }
            $return = $data;
        }
        return $return;
    }
    public function viewcurriculumprograms($stable,$mode,$options) {
        global $OUTPUT, $CFG, $DB, $USER;
        $systemcontext = context_system::instance();

        if ((has_capability('local/program:manageprogram', context_system::instance())) &&
            (is_siteadmin() || (has_capability('local/program:manage_multiorganizations', context_system::instance()) ||has_capability('local/program:manage_ownorganization', context_system::instance())))) {

            $tabs = array();

            
            // $tabs[] = new tabobject(1, new moodle_url($CFG->wwwroot . '/local/program/index.php',array('type'=>1)),'<span title="University Programs">University Programs</span>','University Programs');

            // $tabs[] = new tabobject(2, new moodle_url($CFG->wwwroot . '/local/program/index.php',array('type'=>2)),'<span title="College Programs">College Programs</span>','College Programs');

            $return = $OUTPUT->tabtree($tabs, $mode);
        }else{
            $return='';
        }

        if ((is_siteadmin() || (has_capability('local/program:manageprogram', context_system::instance()) || has_capability('local/program:createprogram', context_system::instance())))&&$mode==1) {

            /*$return .= '<ul class="course_extended_menu_list">
                            <li>
                                <div class="course_extended_menu_itemcontainer">
                                    <a class="course_extended_menu_itemlink create_ilt" title=" '. get_String("create_programs", "local_program") .'" onclick="(function(e){ require(\'local_program/ajaxforms\').init({contextid:'. $systemcontext->id .', component:\'local_program\', callback:\'program_manageprogram_form\', form_status:0, plugintype: \'local_program\', pluginname: \'program\', id:0, title: \'createprogram\' ,editabel:1}) })(event)">
                                        <i class="icon fa fa-columns" aria-hidden="true"></i>
                                    </a>
                                </div>
                            </li>
                        </ul>';*/
            $return .= '<ul class="course_extended_menu_list">
                            <li>
                                <div class="coursebackup course_extended_menu_itemcontainer">
                                    <a class="course_extended_menu_itemlink create_ilt" title=" '. get_String("create_programs", "local_program") .'" onclick="(function(e){ require(\'local_program/ajaxforms\').init({contextid:'. $systemcontext->id .', component:\'local_program\', callback:\'program_manageprogram_form\', form_status:0, plugintype: \'local_program\', pluginname: \'program\', id:0, title: \'createprogram\' ,editabel:1}) })(event)">
                                        <span class="createicon">
                                        <i class="icon fa fa-tasks" aria-hidden="true"></i>
                                        <i class="createiconchild fa fa-plus" aria-hidden="true"></i></span>
                                    </a>
                                </div>
                            </li>
                        </ul>';
        }
  
        if ($stable->thead) {

            $curriculumprograms = (new program)->curriculumprograms($stable,$mode,$options);

            //--Added by Yamini for displaying table structure if it doesn't contain data--//
               $table = new html_table();
                $table->data =array();
                $table->id = 'viewcurriculumprograms';
                $table->align = array('center','left', 'center', 'left', 'left', 'left','center','center','center','center','center');
                $table->size = array('8%','9%', '9%', '9%', '7%', '7%','7%','7%','9%','8%','20%');
                $head = array();
                $table->head = array_merge($head,array(get_string("program", "local_program"),get_string('shortcode', 'local_program')));
               if ((is_siteadmin() || (has_capability('local/program:manage_multiorganizations', context_system::instance()) ||has_capability('local/program:manage_ownorganization', context_system::instance())))) {

                    // if($mode==1){
                        $table->head[] = get_string('costcenter', 'local_program');
                    /*}else{
                        $table->head[] = get_string('college', 'local_program');
                    }*/

                }else{
                    if (!is_siteadmin()&&!has_capability('local/program:manage_multiorganizations', context_system::instance())&&(has_capability('local/program:manage_owndepartments', context_system::instance()))) {
                      
                        $univ_dept_status = $DB->get_field('local_costcenter', 'univ_dept_status' , array('id' => $USER->open_departmentid, 'parentid' => $USER->open_costcenterid));
                        if($univ_dept_status == 1){
                            $table->head[] = get_string('college', 'local_program');
                        }else{
                            $table->head[] = get_string('department', 'local_program');
                        }
                    }else{
                        $table->head[] = get_string('costcenter_department', 'local_program');
                    }
                }
                // print_r($mode);
                // exit;
                if ((is_siteadmin() OR has_capability('local/program:manage_multiorganizations', context_system::instance()) OR has_capability('local/program:manage_ownorganization', context_system::instance()))&&$mode==2) {
                    $table->head[]=get_string('deptcollege', 'local_program');
                }
                if ((is_siteadmin() || (has_capability('local/program:manage_multiorganizations', context_system::instance()) || has_capability('local/program:manage_ownorganization', context_system::instance())))) {

                    $table->head[]=get_string('deptcollege', 'local_program');
                }
                $table->head = array_merge($table->head,array(/*get_string($costcenter, 'local_program'),*/get_string('year', 'local_program'),get_string('curriculumduration', 'local_program')/*, get_string('validtill', 'local_program')*/));
      		
                // if ((is_siteadmin() || (has_capability('local/program:manageprogram', context_system::instance())))&&$mode==1) {

                //     $table->head[]=get_string('collegescount','local_program');
                //     // $table->head[]=get_string('copiedprogramscount','local_program');
                // }

                $table->head[]= get_string('noofstudents','local_program');
                $table->head[]= get_string('admissionstartdate','local_program');
                $table->head[]= get_string('admissionenddate','local_program');
                $table->head[]=get_string('actions');
            if ($curriculumprograms['curriculumprogramscount'] > 0) {
               /* $table = new html_table();
                $table->data =array();
                $head=array();*/
            if ((is_siteadmin() || (has_capability('local/program:manage_multiorganizations', context_system::instance()) ||has_capability('local/program:manage_ownorganization', context_system::instance())))&&$mode==1) {
                   /* $head[]=''.$OUTPUT->help_icon('localselectcopyprogram','local_program').' Select  <br/><input name="select_all" value="1" type="checkbox" class="programcheckboxhead">';*/
            }
        //---Commented by Yamini for displaying table structure if it doesn't contain data//
            /*if ((is_siteadmin() || (has_capability('local/program:manage_multiorganizations', context_system::instance()) ||has_capability('local/program:manage_ownorganization', context_system::instance())))) {

                    if($mode==1){
                        $costcenter='costcenter';
                    }else{

                        $costcenter='department';
                    }
                }else{
                    if (!is_siteadmin()&&!has_capability('local/program:manage_multiorganizations', context_system::instance())&&(has_capability('local/program:manage_owndepartments', context_system::instance()))) {
                        $costcenter='department';

                    }else{
                        $costcenter='costcenter_department';
                    }

                }

                $table->head = array_merge($head,array(get_string("program", "local_program"),get_string('shortcode', 'local_program')));

                if ((is_siteadmin() OR has_capability('local/program:manage_multiorganizations', context_system::instance()))&&$mode==2) {
                    $table->head[]=get_string('costcenter', 'local_program');
                }
                $table->head = array_merge($table->head,array(get_string($costcenter, 'local_program'),get_string('year', 'local_program'),get_string('curriculumduration', 'local_program')/*, get_string('validtill', 'local_program')*///));

               /* if ((is_siteadmin() || (has_capability('local/program:manageprogram', context_system::instance())))&&$mode==1) {
                    $table->head[]=get_string('collegescount','local_program');
                    // $table->head[]=get_string('copiedprogramscount','local_program');
                }

                // $table->head[]= get_string('shortname','local_program');// Commented by Harish to visible Action Icons //
                $table->head[]= get_string('admissionstartdate','local_program');
                $table->head[]= get_string('admissionenddate','local_program');
                $table->head[]=get_string('actions');
                $table->id = 'viewcurriculumprograms';*/
        //Ends--Yamini
                if (is_siteadmin() || (has_capability('local/program:manageprogram', context_system::instance()) || has_capability('local/program:copyprogram', context_system::instance()))) {
                    $return.='<form id="frm-viewcurriculumprograms" action="'.$CFG->wwwroot.'/local/program/index.php" method="POST">';
                }
                $return .= html_writer::table($table);
              /*  if ((is_siteadmin() || (has_capability('local/program:manage_multiorganizations', context_system::instance()) ||has_capability('local/program:manage_ownorganization', context_system::instance())))&&$mode==1) {
                 //   $return.='<div class="duplicateprogrambtn mt-15"><input type="submit" value="Copy Program(s)">'.$OUTPUT->help_icon('localcopyprogram','local_program').'</div></form>';
                }*/
            } else {
                $return .= html_writer::table($table);
               // $return .= "<div class='alert alert-info'>" .
                  //      get_string('nocurriculumprograms', 'local_program') . "</div>";
            }
        } else {
            $curriculumprograms = (new program)->curriculumprograms($stable,$mode,$options);
           
            $data = array();
            foreach ($curriculumprograms['curriculumprograms'] as $curriculumprogram) {
                $curriculumview='';
                // print_object($curriculumprogram);exit;
                $programuserexist=$DB->record_exists('local_cc_session_signups',array('programid'=>$curriculumprogram->id));

                $programduplicateexist=$DB->record_exists('local_program',array('parentid'=>$curriculumprogram->id));

                $localcostcenter=$DB->get_field('local_costcenter','parentid',array('id'=>$curriculumprogram->costcenter));

                $yearid = $DB->get_field('local_program_cc_years','id',array('programid'=>$curriculumprogram->id));

                $programcurriculums = $DB->get_field('local_curriculum','id',array('program'=>$curriculumprogram->id));

                /*$curriculumexistid = $DB->get_field('local_curriculum','id',array('program'=>$curriculumprogram->id));
                print_object($curriculumexistid);exit;*/
                if(!empty($curriculumprogram->curriculumid)){
                    $ccduration = $DB->get_record_sql("SELECT duration, duration_format FROM {local_curriculum} WHERE id = $curriculumprogram->curriculumid");
                }


                $row = array();
                // print_object($mode);
                /*if ((is_siteadmin() || (has_capability('local/program:manage_multiorganizations', context_system::instance()) ||has_capability('local/program:manage_ownorganization', context_system::instance())))&&$mode==1) {*/
                    // $row[] = $curriculumprogram->id;
                    // if($curriculumprogram->publishstatus == 1 && $curriculumexistid){
                       // $row[] = '<input type="checkbox" class="programcheckbox" name = "programid['.$curriculumprogram->id.']">';
                    /*}else{
                        $row[] = '<input type="checkbox" class="programcheckbox" disabled>';
                    }*/
               // }
                $copyinfocheck="";

                $programshortname = $DB->get_field_sql('SELECT id FROM {local_program} WHERE shortname = ? AND costcenter = ? AND year = ? AND id <> ? AND parentid = ?', array($curriculumprogram->shortname, $curriculumprogram->costcenter,$curriculumprogram->year,$curriculumprogram->id,0));

                $programshortcode = $DB->get_field_sql('SELECT id FROM {local_program} WHERE shortcode = ? AND costcenter = ? AND year = ? AND id <> ? AND parentid = ?', array($curriculumprogram->shortname, $curriculumprogram->costcenter,$curriculumprogram->year,$curriculumprogram->id,0));


                $programname = $DB->get_field_sql('SELECT id FROM {local_program} WHERE fullname = ? AND costcenter = ? AND year = ? AND id <> ? AND parentid = ?', array($curriculumprogram->shortname, $curriculumprogram->costcenter,$curriculumprogram->year,$curriculumprogram->id,0));

                if($programname || $programshortcode || $programshortname){
                    $copyinfocheck=$OUTPUT->help_icon('copyprograminfo','local_program');
                }
                if(/*$curriculumexistid && */(is_siteadmin() || (has_capability('local/program:manageprogram', context_system::instance()) ||has_capability('local/program:viewprogram', context_system::instance()) || has_capability('local/program:viewcurriculum', context_system::instance()) || has_capability('local/program:managecurriculum', context_system::instance())))){
                    if(empty($curriculumprogram->curriculumid)){
                        $curriculums_url = new moodle_url('/local/program/view.php',array('ccid'=>$programcurriculums, 'prgid'=>$curriculumprogram->id, 'year'=>$yearid,'type'=>$mode));
                    }else{
                        $curriculums_url = new moodle_url('/local/program/view.php',array('ccid'=>$curriculumprogram->curriculumid, 'prgid'=>$curriculumprogram->id, 'year'=>$yearid,'type'=>$mode));
                    }   
                    $curriculumview= '<a href="'.$curriculums_url.'" alt = "' . get_string('view') . '" target="_blank" title = "' . get_string('view') . '" ><i class="icon fa fa-tasks fa-fw"></i>
                   </a>';
                   
                    $row[] = $copyinfocheck.'<a href="'.$curriculums_url.'" alt = "' . get_string('view') . '" target="_blank" title = "' . get_string('view') . '" >' .$curriculumprogram->fullname. '
                   </a>';
                }else{
                    $row[] = $copyinfocheck.$curriculumprogram->fullname;
                }
                $row[] = $curriculumprogram->shortcode;
                if ((is_siteadmin() OR has_capability('local/program:manage_multiorganizations', context_system::instance()) OR has_capability('local/program:manage_ownorganization', context_system::instance())) && $mode==2) {
                    $costcenterfullname=$DB->get_field('local_costcenter','fullname',array('id'=>$localcostcenter));
                    $row[] = $costcenterfullname;
                }
                $row[] = $curriculumprogram->costcenterfullname;
                if ((is_siteadmin() OR has_capability('local/costcenter:manage_multiorganizations', context_system::instance()) OR has_capability('local/costcenter:manage_ownorganization', context_system::instance()))) {
                    $deptname=$DB->get_field('local_costcenter','fullname',array('id'=>$curriculumprogram->departmentid, 'univ_dept_status' => 0));
// <mallikarjun> - ODL-711 get college name in programs -- starts
                    if(!$deptname){
                    $deptname=$DB->get_field('local_costcenter','fullname',array('id'=>$curriculumprogram->departmentid, 'univ_dept_status' => 1));
                    }
// <mallikarjun> - ODL-711 get college name in programs -- starts
                    $row[] = $deptname;
                }
                
                $row[] = $curriculumprogram->year;
                /*$ccdurationsql = "SELECT id, duration, duration_format FROM {local_curriculum} WHERE id = :curriculumid";
                $ccduration = $DB->get_record_sql($ccdurationsql, array('id' => $curriculumprogram->curriculumid));*/
                if($ccduration->duration_format == 'Y'){
                    $duration_format=' Years';
                }
                else if ($ccduration->duration_format == 'M'){
                    $duration_format=' Months';
                }
                if($ccduration->duration > 0){
                $row[] = $ccduration->duration . $duration_format ? $ccduration->duration . $duration_format : 'N/A';
                }else{
                $row[] = 'N/A';
                }
                // $row[] = date('Y-m-d',$curriculumprogram->validtill);// Commented by Harish to visible Action Icons //
                // if ((is_siteadmin() || (has_capability('local/program:manageprogram', context_system::instance())))&&$mode==1) {
                //     $totalcolleges=$DB->count_records_sql("SELECT count(id) as total FROM {local_program}  where parentid=:parentid and costcenter <> :costcenter",  array('parentid'=>$curriculumprogram->id,'costcenter'=>$curriculumprogram->costcenter));
                    /*if($curriculumexistid){

                        $affiliateprograms_url = new moodle_url('/local/program/affiliateprograms.php',array('pid'=>$curriculumprogram->id,'uid'=>$curriculumprogram->costcenter,'type'=>$mode));
                        $row[] ='<a href="'.$affiliateprograms_url.'" alt = "' . $totalcolleges. '" title = "'.$totalcolleges. '" >'.$totalcolleges. '</a>';
                        $row[] =$totalcolleges;
                    }else{
                        $row[] =$totalcolleges;
                    }*/
                   // $row[] =$totalcolleges;

                    /*$totalcopiedprograms=$DB->count_records_sql("SELECT count(id) as total FROM {local_program}  where parentid=:parentid and costcenter = :costcenter",  array('parentid'=>$curriculumprogram->id,'costcenter'=>$curriculumprogram->costcenter));

                    $copyinfohead=get_string('programcopyinfohead','local_program',$curriculumprogram->fullname);
                    $row[] ='<a href="javascript:void(0);" alt = "' . $totalcopiedprograms. '" title = "' . $totalcopiedprograms. '" onclick="(function(e){ require(\'local_program/program\').masterprogramchildpopup({title:\''.$copyinfohead.'\',id:'.$curriculumprogram->id.'}) })(event)" >'.$totalcopiedprograms.'</a>';*/


               // }
                // $row[] = $curriculumprogram->shortname;// Commented by Harish to visible Action Icons //
                $noofstudentscount = $DB->count_records('local_curriculum_users',array('programid' => $curriculumprogram->id));
                $row[] =  ($noofstudentscount) ? $noofstudentscount : 0;
                // $row[] =  date('Y-m-d H:i',$curriculumprogram->admissionstartdate);
                // $row[] =  date('Y-m-d H:i',$curriculumprogram->admissionenddate);
                
                # RM Issue ODL-739 removed time in view page
                $row[] =  date('Y-m-d',$curriculumprogram->admissionstartdate);
                $row[] =  date('Y-m-d',$curriculumprogram->admissionenddate);
                 #end RM ODL-739
                $action = '';
                if ((is_siteadmin() || (has_capability('local/program:manageprogram', context_system::instance()) || has_capability('local/program:editprogram', context_system::instance())))&&$mode==1) {

                    if($totalcolleges){
                        $editabel=0;
                    }else{
                        $editabel=1;
                    }
                    $action .= '<a href="javascript:void(0);" alt = ' . get_string('edit') . ' title = ' . get_string('edit') . ' onclick="(function(e){ require(\'local_program/ajaxforms\').init({contextid:1, component:\'local_program\', callback:\'program_manageprogram_form\', form_status:0, plugintype:\'local\', pluginname:\'program_program\', id: ' . $curriculumprogram->id . ', title: \'updatesession\',editabel:'.$editabel.'}) })(event)" ><i class="icon fa fa-cog fa-fw"></i></a>';
                }
                // if ((is_siteadmin() || (has_capability('local/program:manageprogram', context_system::instance()) || has_capability('local/program:deleteprogram', context_system::instance())))&&(!$programuserexist&& !$programduplicateexist)&&$mode==1) {
                if ((is_siteadmin() || (has_capability('local/program:manageprogram', context_system::instance()) || has_capability('local/program:deleteprogram', context_system::instance())))&&(!$programuserexist)&&$mode==1) {

                    $deleteprogramurl = new moodle_url('/local/program/index.php',array('deleteprogramid'=>$curriculumprogram->id));

                    $action .= '<a href="'.$deleteprogramurl.'" alt = ' . get_string('delete') . ' title = ' . get_string('delete') . '  ><i class="icon fa fa-trash fa-fw"></i></a>';
                }elseif(($totalcolleges||$totalcopiedprograms|| $programuserexist)&&$mode==1){
                    $deleteinfohead=get_string('programdeleteinfohead','local_program');
                    $deleteinfo=get_string('programdeleteinfocol','local_program',$curriculumprogram->fullname);
                    $action .= '<a href="javascript:void(0);" alt = ' . get_string('delete') . ' title = ' . get_string('delete') . ' onclick="(function(e){ require(\'local_program/program\').viewinfoConfirm({title:\''.$deleteinfohead.'\',body:  \''.$deleteinfo.'\'}) })(event)" ><i class="icon fa fa-trash fa-fw"></i></a>';
                }
                    if($curriculumprogram->publishstatus == 0){
                        if((($curriculumexistid && $curriculumprogram->publishstatus == 0) && (is_siteadmin() || (has_capability('local/program:manageprogram', context_system::instance()) || has_capability('local/program:affiliateprograms', context_system::instance()))))&&(!$programuserexist && !$localcostcenter&&$mode==1)){

                        $affiliateprograms_url = new moodle_url('/local/program/affiliateprograms.php',array('pid'=>$curriculumprogram->id,'uid'=>$curriculumprogram->costcenter,'type'=>$mode));
                        /*$prgmpublisharray = array('pid' => $curriculumprogram->id, 'costcenter' =>  $curriculumprogram->costcenter, 'programname' => $curriculumprogram->fullname);
                        // print_object($prgmpublisharray);
                        $prgmencode = json_encode($prgmpublisharray, true);
                        // print_object($prgmencode);exit;
                        $action .= '<a href="javascript:void(0);" id = "programpublish_icon'.$curriculumprogram->id.'" alt = "' . get_string('publishprogram','local_program') . '" onclick="(function(e){ require(\'local_program/program\').checkProgramStatus({pid:\''.$curriculumprogram->id.'\',costcenter : \''.$curriculumprogram->costcenter.'\', programname : '.$curriculumprogram->fullname.', ccid : \''.$curriculumexistid.'\'}) })(event)" title = "' . get_string('publishprogram','local_program') . '" ><i class="icon fa fa-newspaper fa-fw"></i></a>';
                        $action .= '<a href="javascript:void(0);" id = "programpublish_icon'.$curriculumprogram->id.'" alt = "' . get_string('publishprogram','local_program') . '" onclick="(function(e){ require(\'local_program/program\').checkProgramStatus('.$prgmencode.') })(event)" title = "' . get_string('publishprogram','local_program') . '" ><i class="icon fa fa-newspaper fa-fw"></i></a>';*/
                        $action .= '<a href="javascript:void(0);" id = "programpublish_icon'.$curriculumprogram->id.'" alt = "' . get_string('publishprogram','local_program') . '" onclick="(function(e){ require(\'local_program/program\').checkProgramStatus({pid:\''.$curriculumprogram->id.'\',costcenter : \''.$curriculumprogram->costcenter.'\', programname : \''.$curriculumprogram->fullname.'\', ccid : \''.$curriculumexistid.'\'}) })(event)" title = "' . get_string('publishprogram','local_program') . '" ><i class="icon fa fa-newspaper fa-fw"></i></a>';
                        }
                    }
                    // if($curriculumprogram->publishstatus == 1){
                        // if((/*$curriculumexistid && */(is_siteadmin() || (has_capability('local/program:manageprogram', context_system::instance()) || has_capability('local/program:affiliateprograms', context_system::instance()))))&&(!$programuserexist && !$localcostcenter&&$mode==1)){

                        // $affiliateprograms_url = new moodle_url('/local/program/affiliateprograms.php',array('pid'=>$curriculumprogram->id,'uid'=>$curriculumprogram->costcenter,'type'=>$mode));

                        // $action .= '<a href="'.$affiliateprograms_url.'" id = "affiliateprograms_icon'.$curriculumprogram->id.'" alt = "' . get_string('affiliateprograms','local_program') . '" title = "' . get_string('affiliateprograms','local_program') . '" ><i class="icon fa fa-university fa-fw"></i></a>';
                        // }
                    // }

                if($curriculumview){
                    $action .= $curriculumview;
                }elseif(!$curriculumexistid && (is_siteadmin() || (has_capability('local/program:manageprogram', context_system::instance()) || has_capability('local/program:createcurriculum', context_system::instance())|| has_capability('local/program:managecurriculum', context_system::instance())))&&$mode==1){

                    $action .= '<a href="javascript:void(0);" alt = "' . get_string('create_curriculum','local_program') . '" title = "' . get_string('create_curriculum','local_program') . '" onclick="(function(e){ require(\'local_program/ajaxforms\').init({contextid:1, component:\'local_program\', callback:\'program_form\', form_status:0, plugintype: \'local\', pluginname: \'program\', id:0, title: \'createcurriculum\',program:' . $curriculumprogram->id . ' }) })(event)">
                        <i class="icon fa fa-plus fa-fw"></i></a>';
                }
// <mallikarjun> - ODL-777 comment removed to active/ inactive program -- starts
                if($curriculumprogram->status == 1){
                    $visibleurl = html_writer::link('javascript:void(0)', $OUTPUT->pix_icon('t/hide', get_string('inactive'), 'moodle', array('')), array('title' => get_string('inactive'), 'id' => $course->id, 'onclick'=>'(function(e){ require(\'local_program/program\').programSuspend({id: '.$curriculumprogram->id.', context:1,action :"inactive", fullname:"'.$curriculumprogram->fullname.'" }) })(event)'));
                    $action .= $visibleurl;
                }else{
                    $hideurl = html_writer::link('javascript:void(0)', $OUTPUT->pix_icon('t/show', get_string('active'), 'moodle', array('')), array('title' => get_string('active'), 'id' => $curriculumprogram->id, 'onclick'=>'(function(e){ require(\'local_program/program\').programSuspend({id: '.$curriculumprogram->id.', context:1, action :"active", fullname:"'.$curriculumprogram->fullname.'" }) })(event)'));
                    $action .= $hideurl;
                }
// <mallikarjun> - ODL-777 comment removed to active/ inactive program -- ends


                $row[] = $action;//'Edit, Delete';
                $data[] = $row;
                
            }
            $return = array(
                "recordsTotal" => ($curriculumprograms['curriculumprogramscount']) ? $curriculumprograms['curriculumprogramscount'] : 0,
                "recordsFiltered" => ($curriculumprograms['curriculumprogramscount']) ? $curriculumprograms['curriculumprogramscount'] : 0,
                "data" => $data
            );
        }
        return $return;
    }

    public function assignaffiliateprograms($pid, $uid, $currentcollegeselector, $potentialcollegeselector) {
        global  $DB,$PAGE, $OUTPUT, $CFG;
        $program = $DB->get_record('local_program', array('id' => $pid));

        $admissionenddate=date('d-m-Y H:i',$program->admissionenddate);

        $footertitle=$OUTPUT->notification(get_string('footertitle','local_program', $program->fullname), 'notifymessage');

        if($program->admissionenddate >= time()){

            $headertitle=$OUTPUT->notification(get_string('headertitle','local_program',$admissionenddate), 'notifysuccess');

            $add='<input name="add" id="add" type="submit" value="' . $OUTPUT->larrow() . '&nbsp;' . get_string('add') . '" title="' . get_string('add') . '" />';

            $remove='<input name="remove" id="remove" type="submit" value="' . get_string('remove') . '&nbsp;' . $OUTPUT->rarrow() . '" title="' . get_string('remove') . '" />';
        }else{

            $headertitle=$OUTPUT->notification(get_string('headertitle','local_program',$admissionenddate), 'notifyerror');

            $add='<input name="add" id="add" type="button" value="' . $OUTPUT->larrow() . '&nbsp;' . get_string('add') . '" title="' . get_string('add') . '" disabled class="btn btn-secondary"/>';

            $remove='<input name="remove" id="remove" type="button" value="' . get_string('remove') . '&nbsp;' . $OUTPUT->rarrow() . '" title="' . get_string('remove') . '" disabled class="btn btn-secondary"/>';
        }
        $assignurl = new moodle_url($PAGE->url, array('pid' => $pid));
        $return = ''.$headertitle.''.$footertitle.'';
        $return .= '<form id="assignform" method="post" action="' . $assignurl . '">
                        <div>
                            <input type="hidden" name="sesskey" value="' . sesskey() . '" />
                            <table id="assigningrole" summary="" class="admintable roleassigntable generaltable" cellspacing="0">
                                <tr>
                                    <td id="existingcell">
                                        <p><label for="removeselect">' . get_string('extcolleges', 'local_program') . '</label></p>
                                        ' . $currentcollegeselector->display(true) . '
                                    </td>
                                    <td id="buttonscell">
                                        <div id="addcontrols">
                                        '.$add.'<br />
                                        </div>
                                        <div id="removecontrols">
                                            '.$remove.'
                                        </div>
                                    </td>
                                    <td id="potentialcell">
                                        <p><label for="addselect">' . get_string('potcolleges', 'local_program') . '</label></p>
                                        ' . $potentialcollegeselector->display(true) . '
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </form>';
        return $return;
    }
    /**
     * [viewcurriculumusers description]
     * @method viewcurriculumusers
     * @param  [type]             $curriculumid [description]
     * @param  [type]             $stable      [description]
     * @return [type]                          [description]
     */
    public function viewcoursefaculty($stable) {
        global $OUTPUT, $CFG, $DB;
        $curriculumid = $stable->curriculumid;
        $yearid = $stable->yearid;
        $semesterid = $stable->semesterid;
        $courseid = $stable->courseid;
        $search = $stable->search;
        $programid = $DB->get_field('local_curriculum', 'program', array('id' => $curriculumid));

        if (has_capability('local/program:canmanagefaculty', context_system::instance())) {
            $url = new moodle_url('/local/program/coursefaculty.php', array('yearid' => $yearid, 'semesterid' => $semesterid, 'courseid' => $courseid));
            $assign_users ='<ul class="course_extended_menu_list">
                                <li>
                                    <div class="createicon course_extended_menu_itemlink">
                                        <i href="javascript:void(0)" class="addfaculty icon fa fa-user-plus" title="Asign Faculty" onclick="(function(e){ require(\'local_program/ajaxforms\').init({contextid:1, component:\'local_program\', callback:\'curriculum_managefaculty_form\', form_status:0, plugintype: \'local_program\', pluginname: \'addfaculty\', id:0, curriculumid: ' . $curriculumid .', yearid: ' . $yearid .', programid: ' . $programid . ', semesterid : ' . $semesterid . ', courseid: ' . $courseid .' }) })(event)"> </i>
                                    </div>
                                </li>
                            </ul>';
        } else {
            $assign_users = "";
        }
        // $assign_users = "";
        if ($stable->thead) {
            $coursefaculty = (new program)->coursefaculty($yearid, $semesterid, $courseid, $stable);
            // if ($coursefaculty['coursefacultycount'] > 0) {// Commented by Harish
                $table = new html_table();
                $table->head = array(get_string('faculty', 'local_program'), get_string('email'), get_string('action'));
                $table->id = 'viewcoursefaculty';
                $table->attributes['data-yearid'] = $yearid;
                $table->attributes['data-semesterid'] = $semesterid;
                $table->attributes['data-courseid'] = $courseid;

                $return = $assign_users.html_writer::table($table);
            // } else {
            //     $return = $assign_users."<div class='mt-15 alert alert-info w-full pull-left'>" . get_string('nocoursetrainers', 'local_program') . "</div>";
            // }// Commented by Harish
        } else {
            $curriculumusers = (new program)->coursefaculty($yearid, $semesterid, $courseid, $stable);
            $data = array();
            foreach ($curriculumusers['coursefaculty'] as $sdata) {
$userpicture = $OUTPUT->user_picture($sdata, array('size' => 35, 'link' => false));
$userurl = new moodle_url('/local/users/profile.php', array('id' => $sdata->id));
$userlink = html_writer::link($userurl, $userpicture .' '. fullname($sdata));
                $line = array();
                $line[] = '<div>
                                <span>' . $userlink . '</span>
                            </div>';
/*                $line[] = '<div>
                                <span>' . $OUTPUT->user_picture($sdata) . ' ' . fullname($sdata) . '</span>
                            </div>';*/
                $line[] = '<span> <label for="email">' . $sdata->email . '</lable></span>';

                $line[] = '<a href="javascript:void(0);" alt = ' . get_string('delete') . ' title = ' . get_string('delete') . ' onclick="(function(e){ require(\'local_program/program\').deleteConfirm({action:\'unassignfaculty\', programid: ' . $programid . ', curriculumid: ' . $curriculumid . ', yearid: ' . $yearid . ', semesterid: ' . $semesterid . ', courseid: ' . $courseid . ', trainerid: ' . $sdata->trainerid . ' }) })(event)" ><img src="' . $OUTPUT->image_url('t/delete') . '" alt = ' . get_string('delete') . ' title = "' . get_string('unassignfaculty', 'local_program') . '" class="icon"/></a>';
                $data[] = $line;
            }
            $return = array(
                "recordsTotal" => $curriculumusers['coursefacultycount'],
                "recordsFiltered" => $curriculumusers['coursefacultycount'],
                "data" => $data,
            );
        }
        return $return;
    }
    public function masterprogramchildpopup($stable,$mode,$options,$masterprogramid) {
        global $OUTPUT, $CFG, $DB;
        $systemcontext = context_system::instance();

        $return='';

        if ($stable->thead) {
            $curriculumprograms = (new program)->curriculumprograms($stable,$mode,$options,$masterprogramid);
            if ($curriculumprograms['curriculumprogramscount'] > 0) {
                $table = new html_table();
                $table->data =array();
                $head=array();
                $table->head = array(get_string("program", "local_program"),get_string('shortcode', 'local_program'));

                $table->head = array_merge($table->head,array(get_string('year', 'local_program'),get_string('curriculumduration', 'local_program'), get_string('validtill', 'local_program')));

                $table->head[]= get_string('shortname','local_program');
                $table->head[]= get_string('admissionstartdate','local_program');
                $table->head[]= get_string('admissionenddate','local_program');
                $table->id = 'chlidprograms';
                $return .= html_writer::table($table);
            } else {
                $return .= "<div class='alert alert-info'>" .
                        get_string('nocurriculumprograms', 'local_program') . "</div>";
            }
        } else {
            $curriculumprograms = (new program)->curriculumprograms($stable,$mode,$options,$masterprogramid);
            $data = array();
            // print_object($curriculumprograms);exit;
            foreach ($curriculumprograms['curriculumprograms'] as $curriculumprogram) {
                $curriculumview='';
                $programuserexist=$DB->record_exists('local_cc_session_signups',array('programid'=>$curriculumprogram->id));

                $localcostcenter=$DB->get_field('local_costcenter','parentid',array('id'=>$curriculumprogram->costcenter));

                $curriculumexistid=$DB->get_field('local_curriculum','id',array('program'=>$curriculumprogram->id));

                $row = array();
                $row[] = $curriculumprogram->fullname;

                $row[] = $curriculumprogram->shortcode;
                $row[] = $curriculumprogram->year;
                if($curriculumprogram->duration_format == 'Y'){
                    $duration_format=' Years';
                }
                else
                if ($curriculumprogram->duration_format == 'M'){
                    $duration_format=' Months';
                }
                $row[] = $curriculumprogram->duration . $duration_format;
                $row[] = date('Y-m-d',$curriculumprogram->validtill);

                $row[] = $curriculumprogram->shortname;
                $row[] =  date('Y-m-d H:i',$curriculumprogram->admissionstartdate);
                $row[] =  date('Y-m-d H:i',$curriculumprogram->admissionenddate);

                $data[] = $row;
            }
            $return = array(
                "recordsTotal" => $curriculumprograms['curriculumprogramscount'],
                "recordsFiltered" => $curriculumprograms['curriculumprogramscount'],
                "data" => $data
            );
        }
        return $return;
    }
    public function college_affliated_program($collegeprograms,$url){
        global $OUTPUT, $CFG, $DB;

        $progressbar = new \core\progress\display_if_slow(get_string('affiliatecollegessprogress', 'local_program'));

        $progressbar->start_html();

        $transaction = $DB->start_delegated_transaction();

        $progressbar->start_progress('', count($collegeprograms->collegeprogram));
        $return='';

        foreach ($collegeprograms->collegeprogram as $parentprogram => $newprogram) {

            $newprogramdetails=$DB->get_record_sql('SELECT id,fullname,costcenter FROM {local_program} where id=:programid',  array('programid'=>$parentprogram));

            $progressbar->increment_progress();

            $progressbarone = new \core\progress\display_if_slow(get_string('affiliateprogramsprogress', 'local_program',$newprogramdetails->fullname));

            $progressbarone->start_html();


            $collegestoassign=$DB->get_records_sql_menu('SELECT id,costcenter FROM {local_program} where parentid=:programid AND costcenter <>:costcenter',  array('programid'=>$parentprogram,'costcenter'=>$newprogramdetails->costcenter));

            $progressbarone->start_progress('', count($collegestoassign));

            foreach ($collegestoassign as $college) {

                $addcollege=$DB->get_record('local_costcenter',  array('id'=>$college));
                $progressbarone->increment_progress();

                $return.=(new program)->copy_program_instance($newprogram,$addcollege,$showfeedback = true,$url);
            }

            $progressbarone->end_html();
        }
        $transaction->allow_commit();
        $progressbar->end_html();
        $result=new stdClass();
        $result->changecount=count($collegeprograms);

        $return.=$OUTPUT->notification(get_string('affiliatecollegessuccess', 'local_program',$result),'success');
        $button = new single_button($url, get_string('click_continue','local_program'), 'get', true);
        $button->class = 'continuebutton';
        $return.=$OUTPUT->render($button);

        return $return;
    }

    public function programstatusvalidation($programid, $curriculumid, $costcenter){
        global $DB, $CFG;
        $programcurriculum_sql = "SELECT lcs.id as semesterid, lpccy.id, lpccy.year, lpccy.id as yearid, lcs.semester, lcs.totalcourses, lcs.programid
                                    FROM {local_program_cc_years} lpccy
                                    LEFT JOIN {local_curriculum_semesters} lcs
                                      ON lpccy.id = lcs.yearid
                                   WHERE lpccy.curriculumid = $curriculumid
                                     AND lpccy.programid = $programid";

        $programcurriculum = $DB->get_records_sql($programcurriculum_sql);
        $warningmessage = array();

        if(count($programcurriculum) > 0){
            $semcourses = array();
            $semcount = 0;
            $completionnotsetalert = array();
            $completionset = array();
            $completionnotset = array();
            foreach ($programcurriculum as $value) {
            // print_object($value);
            // $checkyear_existssql = "SELECT id, yearid
            //                           FROM {local_curriculum_semesters}
            //                          WHERE curriculumid = :curriculum
            //                            AND programid = :program
            //                            AND yearid = :year";
            // $checkyear_exists = $DB->record_exists_sql($checkyear_existssql,  array('curriculum' => $curriculumid, 'program' => $programid, 'year' => $value->yearid));
            if($value->totalcourses != 0){
                $semcount++;
                $ccsemcourses_sql = "SELECT ccsc.id, ccsc.courseid, ccsc.coursetype, c.fullname FROM {local_cc_semester_courses} ccsc JOIN {course} c ON c.id = ccsc.courseid
                                               WHERE semesterid = :semester
                                                 AND curriculumid = :curriculumid
                                                 AND programid = :programid";
                $ccsemcourses = $DB->get_records_sql($ccsemcourses_sql, array('curriculumid' => $curriculumid, 'programid' => $programid, 'semester' => $value->semesterid));
                $completionnotset = array();
                $mandatorycount_sql = "SELECT count(id)
                                         FROM {local_cc_semester_courses}
                                        WHERE semesterid = :semester
                                          AND curriculumid = :curriculumid
                                          AND programid = :programid
                                          AND coursetype = :coursetype";
                $mandatorycount = $DB->count_records_sql($mandatorycount_sql, array('curriculumid' => $curriculumid, 'programid' => $programid, 'semester' => $value->semesterid, 'coursetype'=>1));
                if($ccsemcourses){
                    // $nomandatories = 0;
                    foreach ($ccsemcourses as $course) {
                    if($mandatorycount){
                        if($course->coursetype == 1){
                            $semcourses[$course->courseid] = $course->courseid;
                            $totalcourses[$course->courseid] = $course->courseid;
                            $completionstatus_sql = "SELECT id, course
                                                                   FROM {course_completion_criteria}
                                                                  WHERE course = :course";
                            $completion = $DB->get_record_sql($completionstatus_sql, array('course' => $course->courseid));
                            if($completion){
                                $completionset[$course->courseid] = $course->fullname;
                            }else{
                                // $completionnotsetalert[$value->semester] = $course->courseid;
                                // $completionnotset[$course->courseid] = $course->fullname;
                                $button = html_writer::tag('button',$course->fullname,array('class'=>'btn btn-primary'));
                                $completionnotset[$course->courseid] = html_writer::tag('a', $button, array('href' =>$CFG->wwwroot. '/course/view.php?id='.$course->courseid, 'target' => '_blank'));
                            }
                        }
                    }else{
                        $emptysem_mandatorycourses[$value->semesterid] = $value->semester;
                        $completionnotsetalert[$value->year.' / '.$value->semester] = 'Please add atleast one Mandatory course';
                    }
                    }
                }else{
                   $warningmessage['message'] = "Program is not ready to Publish. Please complete the Program template";
                   $warningmessage['finalstatus'] = 'false';
                   return $warningmessage;
                }

                if(!empty($completionnotset)){
                    $completionnotsetalert[$value->year.' / '.$value->semester] = implode(' ', $completionnotset);
                }elseif($mandatorycount == 0){
                    $completionnotsetalert[$value->year.' / '.$value->semester] = 'Please add atleast one Mandatory course';
                }
            }else{
               /*$warningmessage['message'] = "No Semesters are added under <b>".$value->year."</b>. Please complete the Program template";*/
               $warningmessage['message'] = "Program is not ready to Publish. Please complete the Program template";
               $warningmessage['finalstatus'] = 'false';
               return $warningmessage;
            }
        }

            if(empty($emptysem_mandatorycourses) && (count($semcourses) == count($completionset))){
                $warningmessage['finalstatus'] = 'true';
            }else{
                $warningmessage['finalstatus'] = 'false';
                $output = '';
                $output .= '<div>';
                foreach($completionnotsetalert as $key => $value){
                    $output .= "<div class='card'>
                                <div class='container'>
                                <h4>".$key."</h4>
                                <p>".$value."</p>
                                </div></div>";
                }
                $output .= '<div class="alert alert-info">
                            <strong>Info!</strong> Please set the completion criteria for all Mandatory courses.</div>';
                $output .= '</div>';
                $warningmessage['message'] = $output;
            }
        }else{
                $warningmessage['message'] = "Program is not ready to Publish. Please complete the Program template.";
                $warningmessage['finalstatus'] = 'false';
        }

        return $warningmessage;
    }
    public function viewprogramenrolsers($stable) {
        global $OUTPUT, $CFG, $DB;
        $curriculumid = $stable->curriculumid;
        $yearid = $stable->yearid;
        $systemcontext = context_system::instance();
        if ($stable->thead) {
            $programusers = (new program)->programenrolusers($curriculumid, $stable);
                $table = new html_table();
                $table->head = array(get_string('username', 'local_users'), get_string('employeeid', 'local_users'), get_string('email')/*, get_string('university', 'local_courses')*/,get_string('status'));
                $table->id = 'viewprogramenrolsers';
                $table->attributes['data-curriculumid'] = $curriculumid;
                $table->align = array('left', 'left', 'left', 'center');
                $return = html_writer::table($table);
        } else {
            $programid = $DB->get_field('local_program','id',array('curriculumid' => $curriculumid));
            $programusers = (new program)->programenrolusers($curriculumid, $stable);
            $data = array();
            foreach ($programusers['programusers'] as $sdata) {
$userpicture = $OUTPUT->user_picture($sdata, array('size' => 35, 'link' => false));
$userurl = new moodle_url('/local/users/profile.php', array('id' => $sdata->id));
$userlink = html_writer::link($userurl, $userpicture .' '. fullname($sdata));
            // $university = $DB->get_field('local_costcenter', 'fullname', array('id'=>$sdata->open_costcenterid));
                $sql = "SELECT distinct(lpy.year) FROM {local_ccuser_year_signups} AS lcys 
                        JOIN {local_cc_semester_cmptl} AS lsc ON lcys.yearid = lsc.yearid
                        JOIN {local_program_cc_years} as lpy ON lpy.id = lcys.yearid
                        WHERE lcys.programid = $programid AND lcys.userid = $sdata->id
                        AND lcys.completion_status = 1 AND lsc.completion_status = 1" ;
                $completedyears = $DB->get_fieldset_sql($sql);
                $completedyear = implode(' & ', $completedyears);
                $line = array();
                $line[] = $userlink; //$OUTPUT->user_picture($sdata) . ' ' . fullname($sdata);
                $line[] = $sdata->open_employeeid;
                $line[] = $sdata->email;
                $line[] = $completedyears ? $completedyear . '  ' . 'Completed' : 'Not Completed';
                if(is_siteadmin() || has_capability('local/costcenter:manage_ownorganization',$systemcontext)){
                    $return = (new program)->completionchecking($programid,$curriculumid,$yearid,$sdata->id);
                    $logexist = $DB->get_field('local_userenrolments_log','id',array('programid' => $programid,'curriculumid' => $curriculumid,'yearid' => $yearid,'userid' => $sdata->id));
                }
                
                $data[] = $line;
            }
            $return = array(
                "recordsTotal" => $programusers['programuserscount'],
                "recordsFiltered" => $programusers['programuserscount'],
                "data" => $data,
            );
        }
        return $return;
    }
}

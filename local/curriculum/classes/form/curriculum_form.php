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
 * Manage curriculum Form.
 *
 * @package    local_curriculum
 * @copyright  2018 Arun Kumar M <arun@eabyas.in>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_curriculum\form;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');
require_once($CFG->libdir . '/completionlib.php');
use context_system;
use local_program\local\querylib;
use moodleform;
use core_component;
use local_users\functions\userlibfunctions as userlib;

class curriculum_form extends moodleform {
    public $formstatus;
    public function __construct($action = null, $customdata = null, $method = 'post',
        $target = '', $attributes = null, $editable = true, $formdata = null) {
        parent::__construct($action, $customdata, $method, $target, $attributes, $editable,
            $formdata);
    }

    public function definition() {
        global $CFG, $USER, $PAGE, $DB;
        $querieslib = new querylib();
        $mform = &$this->_form;
        $systemcontext = context_system::instance();
        $renderer = $PAGE->get_renderer('local_curriculum');
        $context = context_system::instance();
        $formstatus = $this->_customdata['form_status'];
        $formdata = $this->_customdata['formdata'];
        $costcenterid = $this->_customdata['costcenter'];
        $id = $this->_customdata['id'] > 0 ? $this->_customdata['id'] : 0;
        $programid= $this->_customdata['program'] > 0 ? $this->_customdata['program'] : 0;
        $formheaders = array_keys($this->formstatus);
        $formheader = $formheaders[$formstatus];

        $mform->addElement('hidden', 'id', $id);
        $mform->setType('id', PARAM_INT);

        $querieslib = new querylib();

        $program = $querieslib->get_curriculum_programlist($programid);

        $costcentername = $DB->get_field('local_costcenter','fullname',array('id'=>$program->costcenter));

        $costcenterselect = array('null' => 'Select University');
        $costcenter = $DB->get_records_sql_menu("SELECT id, fullname FROM {local_costcenter} WHERE visible = 1 AND univ_dept_status IS NULL AND parentid = 0 AND visible = 1");
        if($id)
        $assignedcoursescount = $DB->count_records_sql("SELECT count(lcc.id) FROM {local_cc_semester_courses} as lcc JOIN {local_curriculum_semesters} as lcs ON lcs.id = lcc.semesterid AND lcs.curriculumid = lcc.curriculumid WHERE lcc.curriculumid = :curriculumid",array('curriculumid' => $id));
        $programscount = $DB->count_records("local_program",array('curriculumid' => $id));
        if (is_siteadmin() || has_capability('local/costcenter:manage_multiorganizations', $context)) {
            $costcenterid = $DB->get_field('local_curriculum','costcenter',array('id' => $id));
            $universityname = $DB->get_record('local_costcenter',array('id' => $costcenterid));
            if($id > 0){
                if($programscount > 0){
                    $msg = '<br><div class="usermessage">Programs are created by using this university</div>';
                }else if($assignedcoursescount > 0){
                    $msg = '<br><div class="usermessage">Courses are assigned to semester in this curriculum</div>';
                }
            }    
            if (is_siteadmin() || has_capability('local/costcenter:manage_multiorganizations', $context)){
            if($id >0 && ($programscount > 0 || $assignedcoursescount > 0)){
                $mform->addElement('static', 'costcentername', get_string('university', 'local_boards'));                    
                $mform->setDefault('costcentername',$universityname->fullname.$msg);
                $mform->addElement('hidden', 'costcenter',$costcenterid);
            }else{
                $mform->addElement('select', 'costcenter', get_string('department', 'local_location'), $costcenterselect+$costcenter);
            }
                $mform->addRule('costcenter', null, 'required', null, 'client');
            }
        }else{
            $mform->addElement('hidden', 'costcenter',$USER->open_costcenterid);
        }
       
        /*$open_costcenterid = $this->_ajaxformdata['costcenter'];
         if(!empty($open_costcenterid)){
              $departments = find_departments($open_costcenterid);
              foreach($departments as $depart){
                  $departmentslist[$depart->id] = $depart->fullname;
              }   
          }
        if($id > 0){
              $open_costcenterid = $DB->get_field('local_curriculum','costcenter',array('id' => $id));
              $departments = find_departments($open_costcenterid);
              $dept_select = array('null' => '--select--');
              foreach($departments as $depart){
                  $departmentslist[$depart->id] = $depart->fullname;
              }   
           $select = $mform->addElement('select', 'departmentid', get_string('department'),$dept_select+$departmentslist);    
        }
        else{     
         $select = $mform->addElement('select', 'departmentid', get_string('department'),$departmentslist);
        }*/
        $departments = array(null => get_string('select_departmentlist',
                        'local_program'));
        if($id > 0){
            $subsql = "SELECT id, fullname FROM {local_costcenter} WHERE 1 = 1 AND univ_dept_status = 0 AND visible = 1";
            if($costcenterid){
                $subsql .= " AND parentid = ".$costcenterid."";
            }
                $departmentslist = $DB->get_records_sql_menu($subsql);
            }elseif($this->_ajaxformdata['costcenter'] > 0){
                $subsql = "SELECT id, fullname FROM {local_costcenter} WHERE 1=1 AND univ_dept_status = 0 AND visible = 1";
                if(!empty($this->_ajaxformdata['costcenter'])){
                    $subsql .= " AND parentid = ".$this->_ajaxformdata['costcenter']."";
                }
                $departmentslist = $DB->get_records_sql_menu($subsql);
            }
            elseif($USER->open_costcenterid){
            $departmentslist = $DB->get_records_sql_menu("SELECT id, fullname FROM {local_costcenter} WHERE univ_dept_status = 0 AND visible = 1 AND parentid = $USER->open_costcenterid");
        }
        if($departmentslist){
            $departments = $departments+$departmentslist;
        }
        $departmentid = $DB->get_field('local_curriculum','department',array('id' => $id));
        $departmentname = $DB->get_record('local_costcenter',array('id' => $departmentid));

// <mallikarjun> - ODL-763 Unable to create curriculum under college -- starts
        if($id >0 && ($programscount > 0 || $assignedcoursescount)){
            $currid = $DB->get_record('local_curriculum',array('id' => $id));
            if($currid->open_univdept_status == 1){
             $mform->addElement('static', 'dipartment', get_string('college', 'local_program'));   
             $mform->setDefault('dipartment',$departmentname->fullname.$msg);
             $mform->addElement('hidden', 'open_collegeid',$currid->department);  
             $mform->addElement('hidden', 'open_univdept_status',1);  
            }else{
             $mform->addElement('static', 'dipartment', get_string('departments', 'local_program'));  
             $mform->setDefault('dipartment',$departmentname->fullname.$msg);
             $mform->addElement('hidden', 'open_departmentid',$currid->department);
             $mform->addElement('hidden', 'open_univdept_status',0);
            }                
         }else{
                $attributes = array('1' => 'university departments','2' => 'Non university departments');
                $radioarray=array();
                if($id > 0){
                    $radioarray[] = $mform->createElement('radio', 'open_univdept_status', '', get_string('univ_depart','local_costcenter'), 0, $attributes);
                    $radioarray[] = $mform->createElement('radio', 'open_univdept_status', '', get_string('non_univ_depart','local_costcenter'), 1, $attributes);
                        $mform->addGroup($radioarray, 'univdept_statusclass', get_string('customreq_selectdeptcoll', 'local_users'), array('class' => 'univdept_statusclass'), false);
                }else{
                        $radioarray[] = $mform->createElement('radio', 'open_univdept_status', '', get_string('univ_depart','local_costcenter'), 0, $attributes);
                        $radioarray[] = $mform->createElement('radio', 'open_univdept_status', '', get_string('non_univ_depart','local_costcenter'), 1 , $attributes);
                        $mform->addGroup($radioarray, 'univdept_statusclass', get_string('customreq_selectdeptcoll', 'local_users') , array('class' => 'univdept_statusclass'), false);
                }
                
                    // Fetching college list mapped under university starts here //
                    $departmentslist = array(null => '--Select College--');
                    if($id > 0){
                        $existing_costcenter = $DB->get_field('local_curriculum', 'costcenter',array('id' => $id));
                    }
                    if($id > 0 && $existing_costcenter && !isset($this->_ajaxformdata['costcenter'])){
                        $open_costcenterid = $existing_costcenter;
                    } else{
                        $open_costcenterid = $this->_ajaxformdata['costcenter'];
                    }
                    if(!empty($open_costcenterid) && is_siteadmin()){
                        $departments = userlib::find_departments_list($open_costcenterid);
                        foreach($departments['nonuniv_dep'] as $depart){
                            $departmentslist[$depart->id]=$depart->fullname;
                        }
                    }else if(!is_siteadmin() && has_capability('local/costcenter:view', $systemcontext)){
                        $departments = userlib::find_departments_list($USER->open_costcenterid);
                        foreach($departments['nonuniv_dep'] as $depart){
                            $departmentslist[$depart->id]=$depart->fullname;
                        }
                    }
                     // print_r($departmentslist);
                     // exit;
                    $mform->addElement('select', 'open_collegeid', get_string('collegelabel','local_users'),$departmentslist, array('class' => 'college_univ'));
                    /*$mform->addRule('open_collegeid', get_string('miisingcollegeid','local_users'), 'required', null, 'client');*/// Commented by Harish
                    $mform->hideIf('open_collegeid', 'open_univdept_status', 'eq', 0);
                    if($id > 0){
                    $currid = $DB->get_record('local_curriculum',array('id' => $id));
                    $mform->setDefault('open_collegeid',$currid->department);
                    }
                    // Fetching college list mapped under university ends here //

                    // Fetching departments list mapped under university starts here //
                    $departmentslist = array(null => '--Select Department--');
                    if($id > 0){
                        $existing_costcenter = $DB->get_field('local_curriculum', 'costcenter',array('id' => $id));
                    }
                    if($id > 0 && $existing_costcenter && !isset($this->_ajaxformdata['costcenter'])){
                        $open_costcenterid = $existing_costcenter;
                    } else{
                        $open_costcenterid = $this->_ajaxformdata['costcenter'];
                    }
                    if(!empty($open_costcenterid) && is_siteadmin()){
                        $departments = userlib::find_departments_list($open_costcenterid);
                        foreach($departments['univ_dep'] as $depart){
                            $departmentslist[$depart->id]=$depart->fullname;
                        }
                    }else if(!is_siteadmin() && has_capability('local/costcenter:view', $systemcontext)){
                        $departments = userlib::find_departments_list($USER->open_costcenterid);
                        foreach($departments['univ_dep'] as $depart){
                            $departmentslist[$depart->id]=$depart->fullname;
                        }
                    }
                $mform->addElement('select', 'open_departmentid', get_string('departments','local_users'),$departmentslist, array('class' => 'department_univ'));
                    /*$mform->addRule('departmentid', get_string('missing_departments','local_users'), 'required', null, 'client');*/// Commented by Harish
                    $mform->hideIf('open_departmentid', 'open_univdept_status', 'eq', 1);
                    if($id > 0){
                       $currid = $DB->get_record('local_curriculum',array('id' => $id));
                       $mform->setDefault('open_departmentid',$currid->department);
                    }
// <mallikarjun> - ODL-763 Unable to create curriculum under college -- ends
    }
        $mform->addElement('hidden', 'program',
        get_string('program', 'local_curriculum'));
        $mform->setType('program', PARAM_INT);
        $mform->setDefault('program', $programid);

        $mform->addElement('text', 'name', get_string('curriculum_name', 'local_curriculum'), array());
        $mform->addRule('name', get_string('curriculum_name', 'local_curriculum'), 'required', null, 'client');
        if (!empty($CFG->formatstringstriptags)) {
            $mform->setType('name', PARAM_TEXT);
        } else {
            $mform->setType('name', PARAM_CLEANHTML);
        }
    

        if($id>0){
           $curriculum_publish_status = $DB->get_field('local_curriculum','curriculum_publish_status',array('id' => $id));
           $curriculumdata = $DB->get_record('local_curriculum',array('id' => $id));

            if($assignedcoursescount > 0 || $programscount > 0){
                if($curriculumdata->duration_format == 'M'){
                    $duration = 'Months';
                }else{
                    $duration = 'Years';
                }
                if($programscount > 0){
                    $msg = '<br><div class="usermessage">Programs are created by using this university</div>';
                }else if($assignedcoursescount > 0){
                    $msg = '<br><div class="usermessage">Courses are assigned to semester in this curriculum</div>';
                }
                $mform->addElement('hidden', 'duration',$curriculumdata->duration);
                $mform->addElement('hidden', 'duration_format',$curriculumdata->duration_format);
                $mform->addElement('static', 'durationname', get_string('curriculumduration', 'local_curriculum'));                    
                $mform->setDefault('durationname',$curriculumdata->duration.'&nbsp'.$duration.$msg);
            }else{
                if($curriculum_publish_status != 1){
                    $duration = array();
                    $duration[] = & $mform->createElement('text', 'duration');
                    $duration_format = array('Y' => 'Years');
                    $duration[] = & $mform->createElement('select', 'duration_format', get_string('forumtype', 'forum'), $duration_format);
                    $myduration = $mform->addElement('group', 'durationfield', get_string('curriculumduration', 'local_curriculum'), $duration, '  ', false);
                    $mform->addRule('durationfield', null, 'required', null, 'client');
                }else{
                    $mform->addElement('hidden', 'duration');
                    $mform->setType('duration', PARAM_INT);
                    $mform->addElement('hidden', 'duration_format');
                    $mform->setType('duration_format', PARAM_INT);
                }
            }
         }
         else{
                    $duration = array();
                    $duration[] = & $mform->createElement('text', 'duration');
                    $duration_format = array('Y' => 'Years');
                    $duration[] = & $mform->createElement('select', 'duration_format', get_string('forumtype', 'forum'), $duration_format);
                    $myduration = $mform->addElement('group', 'durationfield', get_string('curriculumduration', 'local_curriculum'), $duration, '  ', false);
                    $mform->addRule('durationfield', null, 'required', null, 'client');
          }
         
        $editoroptions = array(
            'noclean' => false,
            'autosave' => false
        );
        $mform->addElement('editor', 'cr_description',
                get_string('description', 'local_curriculum'), null, $editoroptions);
        $mform->setType('cr_description', PARAM_RAW);
        $mform->addHelpButton('cr_description', 'description', 'local_curriculum');

        $mform->disable_form_change_checker();
    }
    public function validation($data, $files) {
        global $CFG, $DB, $USER;
        $errors = array();
        $costcenter = $data['costcenter'];
        if(isset($data['costcenter']) && ($data['costcenter'] == 'null')){
            $errors['costcenter'] = get_string('missingcostcenter', 'local_curriculum');
        }

        if (isset($data['name']) && empty(trim($data['name']))) {
            $errors['name'] = get_string('valnamerequired', 'local_curriculum');
        }
// <mallikarjun> - ODL-763 Unable to create curriculum under college -- starts
        if($data['open_univdept_status'] == 0){
                if($data['open_departmentid'] == null){
                        $errors['open_departmentid'] = get_string('missing_departments', 'local_users');
                }
        }else{
                if($data['open_collegeid'] == null){
                        $errors['open_collegeid'] = get_string('miisingcollegeid', 'local_users');
                }
       }
//        if (!array_key_exists('departmentid',$data)) {
//            $errors['departmentid'] = get_string('missingdepartment', 'local_curriculum');
//        }
// <mallikarjun> - ODL-763 Unable to create curriculum under college -- ends
        if(empty($data['duration'])){
            $errors['durationfield'] = get_string('missingduration', 'local_curriculum');
        }
        if($data['duration_format'] == 'M' && $data['duration'] > 12){
             $errors['durationfield'] = get_string('exceed12months', 'local_curriculum');
        }
         if(isset($data['duration']) && empty($data['duration'])){
             $errors['duration'] = get_string('missingyear', 'local_curriculum');
        }
//        print_r($errors);
//        exit;
        return $errors;
    }

    public function set_data($components) {
        global $DB;
        $context = context_system::instance();
        $data = $DB->get_record('local_curriculum', array('id' => $components->id));
        $data->cr_description = array();
        $data->cr_description['text'] = $data->description;
        $draftitemid = file_get_submitted_draft_itemid('curriculumlogo');
        file_prepare_draft_area($draftitemid, $context->id, 'local_curriculum', 'curriculumlogo',
            $data->curriculumlogo, null);
        $data->curriculumlogo = $draftitemid;
        $data->departmentid = $data->department;
    //    print_object($data);
        parent::set_data($data);
    }
}

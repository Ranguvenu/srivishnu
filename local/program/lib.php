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
 * local_curriculum LIB
 *
 * @package    local_curriculum
 * @copyright  2018 Arun Kumar M <arun@eabyas.in>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die;
require_once($CFG->dirroot.'/user/selector/lib.php');
require_once($CFG->libdir . '/formslib.php');
use \local_program\form\program_form as program_form;
use local_program\local\querylib;
use local_program\program;
use \local_curriculum\notifications_emails as curriculumnotifications_emails;
use local_users\functions\userlibfunctions as userlib;
global $PAGE;
$PAGE->requires->jquery();
$PAGE->requires->js('/local/program/js/custom.js',true);
//$PAGE->requires->js('/local/program/js/jquery.min.js',true);

function local_curriculum_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options=array()) {
    // Check the contextlevel is as expected - if your plugin is a block, this becomes CONTEXT_BLOCK, etc.

    // Make sure the filearea is one of those used by the plugin.
    if ($filearea !== 'curriculumlogo') {
        return false;
    }

    $itemid = array_shift($args);

    $filename = array_pop($args);
    if (!$args) {
        $filepath = '/';
    } else {
        $filepath = '/'.implode('/', $args).'/';
    }

    // Retrieve the file from the Files API.
    $fs = get_file_storage();
    $file = $fs->get_file($context->id, 'local_program', $filearea, $itemid, $filepath, $filename);
    if (!$file) {
        return false;
    }
    send_file($file, $filename, 0, $forcedownload, $options);
}

/**
 * Serve the new group form as a fragment.
 *
 * @param array $args List of named arguments for the fragment loader.
 * @return string
 */
function local_program_output_fragment_program_form($args) {
    global $CFG, $PAGE, $DB;
    $args = (object) $args;
    $context = $args->context;
    $return = '';
    $renderer = $PAGE->get_renderer('local_program');
    $formdata = [];
    if (!empty($args->jsonformdata)) {
        $serialiseddata = json_decode($args->jsonformdata);
        parse_str($serialiseddata, $formdata);
    }
    $formdata['id'] = $args->id;
    $formdata['program'] = $args->program;

    $mform = new program_form(null, array('id' => $args->id,'program' => $args->program,
        'form_status' => $args->form_status), 'post', '', null, true, $formdata);
    $curriculumdata = new stdClass();
    $curriculumdata->id = $args->id;
    $curriculumdata->program = $args->program;
    $curriculumdata->form_status = $args->form_status;
    $mform->set_data($curriculumdata);

    if (!empty((array) $serialiseddata)) {
        // If we were passed non-empty form data we want the mform to call validation functions and show errors.
        $mform->is_validated();
    }
    $formheaders = array_keys($mform->formstatus);
    $nextform = array_key_exists($args->form_status, $formheaders);
    if ($nextform === false) {
        return false;
    }
    ob_start();
    $formstatus = array();
    foreach (array_values($mform->formstatus) as $k => $mformstatus) {
        $activeclass = $k == $args->form_status ? 'active' : '';
        $formstatus[] = array('name' => $mformstatus, 'activeclass' => $activeclass);
    }
    $formstatusview = new \local_program\output\form_status($formstatus);
    $return .= $renderer->render($formstatusview);
    $mform->display();
    $return .= ob_get_contents();
    ob_end_clean();

    return $return;
}
function local_program_output_fragment_session_form($args) {
    global $CFG, $DB;
    $args = (object) $args;
    $context = $args->context;
    $return = '';
    $formdata = [];
    if (!empty($args->jsonformdata)) {
        $serialiseddata = json_decode($args->jsonformdata);
        parse_str($serialiseddata, $formdata);
    }
    $formdata['id'] = $args->id;
    $formdata['ccid'] = $args->ccid;
    $formdata['semesterid'] = $args->semesterid;
    $formdata['bclcid'] = $args->bclcid;
    $formdata['programid'] = $args->programid;
    $formdata['yearid'] = $args->yearid;
    $formdata['courseid'] = $args->courseid;
    $formdata['ccses_action'] = $args->ccses_action;
    // print_object($args);exit;
    if($args->id > 0){
        $sessiondata = $DB->get_record('local_cc_course_sessions', array('id' => $args->id));
        $sessionstarttime = explode(':', $sessiondata->dailysessionstarttime);
        $sessionendtime = explode(':', $sessiondata->dailysessionendtime);
        $dailysessionsttime = array('dailystarttimehours' => $sessionstarttime[0], 'dailystarttimemins' => $sessionstarttime[1]);
        $dailysessionendtime = array('dailyendtimehours' => $sessionendtime[0], 'dailyendtimemins' => $sessionendtime[1]);
        $sessiondata->dailysessionstarttime = $dailysessionsttime;
        $sessiondata->dailysessionendtime = $dailysessionendtime;

        $sessiondata->form_status = $args->form_status;
        $sessiondata->cs_description['text'] = $sessiondata->description;
	$sessiondata->location = $sessiondata->instituteid;        
	$sessiondata->room = $sessiondata->roomid;
	$sessiondata->faculty = $sessiondata->trainerid;
        if ($sessiondata->trainerid == 0) {
            $sessiondata->trainerid = null;
        }
    }
    if($args->programid){
        $sessiondata->programid = $args->programid;
    }
    if($args->yearid){
        $sessiondata->yearid = $args->yearid;
    }
    $mform = new \local_program\form\session_form(null, array('id' => $args->id,
        'ccid' => $args->ccid, 'semesterid' => $args->semesterid, 'bclcid' => $args->bclcid,'programid' => $sessiondata->programid, 'yearid' => $sessiondata->yearid, 'courseid' => $sessiondata->courseid,'ccses_action' => $args->ccses_action,'costcenter' => $sessiondata->costcenter, 'location' => $sessiondata->instituteid,'institute_type' => $sessiondata->institute_type,'room' => $sessiondata->roomid,'trainerid'=>$sessiondata->trainerid,'form_status' => $args->form_status), 'post', '', null, true, $formdata);
    if ($args->id > 0) {
        $mform->set_data($sessiondata);
    }

    if (!empty((array) $serialiseddata)) {
        // If we were passed non-empty form data we want the mform to call validation functions and show errors.
        $mform->is_validated();
    }
    $formheaders = array_keys($mform->formstatus);
    $nextform = array_key_exists($args->form_status, $formheaders);
    if ($nextform === false) {
        return false;
    }
    ob_start();
    $mform->display();
    $return .= ob_get_contents();
    ob_end_clean();

    return $return;
}
function local_program_output_fragment_program_completion_form($args) {
    global $CFG, $DB;
    $args = (object) $args;
    $context = $args->context;
    $return = '';
    $formdata = [];
    if (!empty($args->jsonformdata)) {
        $serialiseddata = json_decode($args->jsonformdata);
        parse_str($serialiseddata, $formdata);
    }
    $formdata['id'] = $args->id;
    $formdata['ccid'] = $args->ccid;
    $mform = new \local_program\form\program_completion_form(null, array('id' => $args->id,
        'ccid' => $args->cid, 'form_status' => $args->form_status), 'post', '', null, true, $formdata);
    if ($args->id > 0) {
        $curriculum_completiondata = $DB->get_record('local_curriculum_completion', array('id' => $args->id));
        $curriculum_completiondata->form_status = $args->form_status;

        if ($curriculum_completiondata->sessionids == "NULL") {
            $curriculum_completiondata->sessionids = null;
        }
        if ($curriculum_completiondata->courseids == "NULL") {
            $curriculum_completiondata->courseids = null;
        }

        $mform->set_data($curriculum_completiondata);
    }

    if (!empty((array) $serialiseddata)) {
        // If we were passed non-empty form data we want the mform to call validation functions and show errors.
        $mform->is_validated();
    }
    $formheaders = array_keys($mform->formstatus);
    $nextform = array_key_exists($args->form_status, $formheaders);
    if ($nextform === false) {
        return false;
    }
    ob_start();
    $mform->display();
    $return .= ob_get_contents();
    ob_end_clean();

    return $return;
}
function local_program_output_fragment_course_form($args) {
    global $CFG, $PAGE, $DB;
    //require_once($CFG->dirroot.'/local/curriculum/classes/form/programcourse_form.php');
    require_once($CFG->dirroot.'/local/curriculum/lib.php');
    $args = (object) $args;
    $context = $args->context;
    $return = '';
    $renderer = $PAGE->get_renderer('local_program');
    $formdata = [];
    if (!empty($args->jsonformdata)) {
        $serialiseddata = json_decode($args->jsonformdata);
        parse_str($serialiseddata, $formdata);
    }
    $formdata['programid'] = $args->programid;
    $formdata['curriculumid'] = $args->curriculumid;
    $formdata['yearid'] = $args->yearid;
    $formdata['semesterid'] = $args->semesterid;
    $mform = new programcourse_form(null, array('programid' => $args->programid,'curriculumid' => $args->curriculumid, 'yearid' => $args->yearid, 'semesterid' => $args->semesterid,
        'form_status' => $args->form_status), 'post', '', null, true, $formdata);
    $coursedata = new stdClass();
    $coursedata->id = $args->id;
    $coursedata->form_status = $args->form_status;
    $mform->set_data($coursedata);

    if (!empty((array) $serialiseddata)) {
        // If we were passed non-empty form data we want the mform to call validation functions and show errors.
        $mform->is_validated();
    }
    $formheaders = array_keys($mform->formstatus);
    $nextform = array_key_exists($args->form_status, $formheaders);
    if ($nextform === false) {
        return false;
    }
    ob_start();
    $formstatus = new \local_program\output\form_status(array_values($mform->formstatus));
    $return .= $renderer->render($formstatus);
    $mform->display();
    $return .= ob_get_contents();
    ob_end_clean();

    return $return;
}

class programcourse_form extends moodleform {

    public function definition() {
        global $CFG, $DB, $USER;
       // require_once($CFG->dirroot . '/local/curriculum/lib.php');
        $querieslib = new querylib();
        $mform = &$this->_form;
        $programid = $this->_customdata['programid'];
        $curriculumid = $this->_customdata['curriculumid'];
        $yearid = $this->_customdata['yearid'];
        $semesterid = $this->_customdata['semesterid'];

        $context = context_system::instance();

        //$mform->addElement('header', 'general', get_string('addcourses', 'local_program'));

        $mform->addElement('hidden', 'programid', $programid);
        $mform->setType('programid', PARAM_INT);

        $mform->addElement('hidden', 'curriculumid', $curriculumid);
        $mform->setType('curriculumid', PARAM_INT);

        $mform->addElement('hidden', 'yearid', $yearid);
        $mform->setType('yearid', PARAM_INT);

        $mform->addElement('hidden', 'semesterid', $semesterid);
        $mform->setType('semesterid', PARAM_INT);


         $universityid = $DB->get_field_sql('SELECT p.costcenter
                                              FROM {local_program} p
                                            WHERE p.id = :programid ',
                                            array('programid' => $programid));
        $departments = array();
        $departmentid = $DB->get_field('local_curriculum','department',array('id' => $curriculumid));

        $mform->addElement('hidden', 'department', $departmentid);
        $mform->setType('department', PARAM_INT);
       
       if($departmentid == '-1'){
            $departmentid = $DB->get_field('local_program','departmentid',array('id' => $programid));
         }
        $courses = find_existingcourses($departmentid,$semesterid,$yearid,$curriculumid);
        foreach($courses as $course){
            //added by yamini to display courses which have program enrolment
           $courseenrol = $DB->get_field('enrol','id',array('enrol' => 'program','courseid' => $course->id));          
            if(!empty($courseenrol)){
              $courselists[$course->id] = $course->fullname;           
            }         
        }
        $course_select = array('null' => '--Select Courses--');  
        if($courselists){
            $courselist = $course_select+$courselists; 
        }else{
            $courselist = $course_select;
        }
        $mform->addElement('select', 'course', get_string('course', 'local_curriculum'),$courselist);
        $mform->addRule('course', get_string('missingcourse', 'local_curriculum'), 'required', null, 'client');

        $coursetype = array(null => get_string('selectcoursetype', 'local_curriculum'), '0' => 'Optional', '1' => 'Mandatory');

        $mform->addElement('select', 'coursetype', get_string('coursetype', 'local_curriculum'), $coursetype);
        $mform->addRule('coursetype', get_string('missingcoursetype', 'local_curriculum'), 'required', null, 'client');
        $mform->disable_form_change_checker();
    }
    public function validation($data){
        $errors = array();
      //  print_object($data);
        if($data['course'] == 'null'){
            $errors['course'] = get_string('missingcourse', 'local_curriculum');
        }
       /* if(empty($data['coursetype'])){
            $errors['coursetype'] = get_string('missingcoursetype', 'local_curriculum');
        }*/
        return $errors;
    }
}

/**
 * User selector subclass for the list of potential users on the assign roles page,
 * when we are assigning in a context below the course semester. (CONTEXT_MODULE and
 * some CONTEXT_BLOCK).
 *
 * This returns only enrolled users in this context.
 */
class local_curriculum_potential_users extends user_selector_base {
    protected $curriculumid;
    protected $context;
    protected $courseid;
    /**
     * @param string $name control name
     * @param array $options should have two elements with keys groupid and courseid.
     */
    public function __construct($name, $options) {
        global $CFG;
        if (isset($options['context'])) {
            $this->context = $options['context'];
        } else {
            $this->context = context::instance_by_id($options['contextid']);
        }
        $options['accesscontext'] = $this->context;
        parent::__construct($name, $options);
        $this->curriculumid = $options['curriculumid'];
        $this->organization = $options['organization'];
        $this->department = $options['department'];
        $this->email = $options['email'];
        $this->idnumber = $options['idnumber'];
        $this->uname = $options['uname'];
        $this->searchanywhere = true;
        require_once($CFG->dirroot . '/group/lib.php');
    }

    protected function get_options() {
        global $CFG;
        $options = parent::get_options();
        $options['file'] = 'local/program/lib.php';
        $options['curriculumid'] = $this->curriculumid;
        $options['contextid'] = $this->context->id;
        return $options;
    }

    public function find_users($search) {
        global $DB;
        $params = array();
        $curriculum = $DB->get_record('local_curriculum', array('id' => $this->curriculumid));
        if (empty($curriculum)) {
            print_error('curriculum not found!');
        }

        // Now we have to go to the database.
        list($wherecondition, $params) = $this->search_sql($search, 'u');

        if ($wherecondition) {
            $wherecondition = ' AND ' . $wherecondition;
        }

        $fields      = 'SELECT ' . $this->required_fields_sql('u');
        $countfields = 'SELECT COUNT(u.id)';
        $params['confirmed'] = 1;
        $params['suspended'] = 0;
        $params['deleted'] = 0;

        $sql   = " FROM {user} AS u
                  WHERE 1 = 1
                        $wherecondition
                    AND u.id > 2 AND u.confirmed = :confirmed AND u.suspended = :suspended
                    AND u.deleted = :deleted
                        ";
        if ($curriculum->costcenter && (has_capability('local/program:manageprogram', context_system::instance())) && ( !is_siteadmin() && (!has_capability('local/program:manage_multiorganizations', context_system::instance()) && !has_capability('local/costcenter:manage_multiorganizations', context_system::instance())))) {
            $sql .= " AND u.open_costcenterid = :costcenter";
            $params['costcenter'] = $curriculum->costcenter;

            if ($curriculum->department && (has_capability('local/program:manage_owndepartments', context_system::instance()) || has_capability('local/costcenter:manage_owndepartments', context_system::instance()))) {
               $sql .= " AND u.open_departmentid = :department";
               $params['department'] = $curriculum->department;
            }
        }

        if (!empty($this->email)) {
            $sql .= " AND u.id IN ({$this->email})";
        }
        if (!empty($this->uname)) {
            $sql .= " AND u.id IN ({$this->uname})";
        }
        if (!empty($this->department)) {
            $sql .= " AND u.open_departmentid IN ($this->department)";
        }
        if (!empty($this->idnumber)) {
            $sql .= " AND u.id IN ($this->idnumber)";
        }

        $options = array('contextid' => $this->context->id, 'curriculumid' => $this->curriculumid, 'email' => $this->email, 'uname' => $this->uname, 'department' => $this->department, 'idnumber' => $this->idnumber, 'organization' => $this->organization);
        $local_curriculum_existing_users = new local_curriculum_existing_users('removeselect', $options);
        $enrolleduerslist = $local_curriculum_existing_users->find_users('', true);
        if (!empty($enrolleduerslist)) {
            $enrolleduers = implode(',', $enrolleduerslist);
            $sql .= " AND u.id NOT IN ($enrolleduers)";
        }

        list($sort, $sortparams) = users_order_by_sql('u', $search, $this->accesscontext);
        $order = ' ORDER BY ' . $sort;

        // Check to see if there are too many to show sensibly.
        if (!$this->is_validating()) {
            $potentialmemberscount = $DB->count_records_sql($countfields . $sql, $params);
            if ($potentialmemberscount > $this->maxusersperpage) {
                return $this->too_many_results($search, $potentialmemberscount);
            }
        }
        // If not, show them.
        $availableusers = $DB->get_records_sql($fields . $sql . $order, array_merge($params, $sortparams));

        if (empty($availableusers)) {
            return array();
        }

        if ($search) {
            $groupname = get_string('potusersmatching', 'local_program', $search);
        } else {
            $groupname = get_string('potusers', 'local_program');
        }

        return array($groupname => $availableusers);
    }
}

/**
 * User selector subclass for the list of users who already have the role in
 * question on the assign roles page.
 */
class local_curriculum_existing_users extends user_selector_base {
    protected $curriculumid;
    protected $context;
    // protected $courseid;
    /**
     * @param string $name control name
     * @param array $options should have two elements with keys groupid and courseid.
     */
    public function __construct($name, $options) {
        global $CFG;
        $this->searchanywhere = true;
        if (isset($options['context'])) {
            $this->context = $options['context'];
        } else {
            $this->context = context::instance_by_id($options['contextid']);
        }
        $options['accesscontext'] = $this->context;
        parent::__construct($name, $options);
        $this->curriculumid = $options['curriculumid'];
        $this->organization = $options['organization'];
        $this->department = $options['department'];
        $this->email = $options['email'];
        $this->idnumber = $options['idnumber'];
        $this->uname = $options['uname'];
        require_once($CFG->dirroot . '/group/lib.php');
    }

    protected function get_options() {
        global $CFG;
        $options = parent::get_options();
        $options['file'] = 'local/program/lib.php';
        $options['curriculumid'] = $this->curriculumid;
        // $options['courseid'] = $this->courseid;
        $options['contextid'] = $this->context->id;
        return $options;
    }
    public function find_users($search, $idsreturn = false) {
        global $DB;

        list($wherecondition, $params) = $this->search_sql($search, 'u');

        $params['curriculumid'] = $this->curriculumid;
        $fields = "SELECT DISTINCT u.id, " . $this->required_fields_sql('u') ;
        $countfields = "SELECT COUNT(DISTINCT u.id) ";
        $params['confirmed'] = 1;
        $params['suspended'] = 0;
        $params['deleted'] = 0;
        $sql = " FROM {user} AS u
                JOIN {local_curriculum_users} AS cu ON cu.userid = u.id
                 WHERE $wherecondition
                AND u.id > 2 AND u.confirmed = :confirmed AND u.suspended = :suspended
                    AND u.deleted = :deleted AND cu.curriculumid = :curriculumid";
        if (!empty($this->email)) {
            $sql.=" AND u.id IN ({$this->email})";
        }
       if (!empty($this->uname)) {
            $sql .=" AND u.id IN ({$this->uname})";
        }
        if (!empty($this->department)) {
            $sql .=" AND u.open_departmentid IN ($this->department)";
        }
        if (!empty($this->idnumber)) {
            $sql .=" AND u.id IN ($this->idnumber)";
        }
        if (!$this->is_validating()) {
            $existinguserscount = $DB->count_records_sql($countfields . $sql, $params);
            if ($existinguserscount > $this->maxusersperpage) {
                return $this->too_many_results($search, $existinguserscount);
            }
        }
        if ($idsreturn) {
            $contextusers = $DB->get_records_sql_menu('SELECT DISTINCT u.id, u.id as userid ' . $sql, $params);
            return $contextusers;
        } else {
            $order = " ORDER BY u.id DESC";
            $contextusers = $DB->get_records_sql($fields . $sql . $order, $params);
        }

        // No users at all.
        if (empty($contextusers)) {
            return array();
        }

        if ($search) {
            $groupname = get_string('enrolledusersmatching', 'enrol', $search);
        } else {
            $groupname = get_string('enrolledusers', 'enrol');
        }
        return array($groupname => $contextusers);
    }

    protected function this_con_group_name($search, $numusers) {
        if ($this->context->contextlevel == CONTEXT_SYSTEM) {
            // Special case in the System context.
            if ($search) {
                return get_string('extusersmatching', 'local_program', $search);
            } else {
                return get_string('extusers', 'local_program');
            }
        }
        $contexttype = context_helper::get_semester_name($this->context->contextlevel);
        if ($search) {
            $a = new stdClass;
            $a->search = $search;
            $a->contexttype = $contexttype;
            if ($numusers) {
                return get_string('usersinthisxmatching', 'core_role', $a);
            } else {
                return get_string('noneinthisxmatching', 'core_role', $a);
            }
        } else {
            if ($numusers) {
                return get_string('usersinthisx', 'core_role', $contexttype);
            } else {
                return get_string('noneinthisx', 'core_role', $contexttype);
            }
        }
    }

    protected function parent_con_group_name($search, $contextid) {
        $context = context::instance_by_id($contextid);
        $contextname = $context->get_context_name(true, true);
        if ($search) {
            $a = new stdClass;
            $a->contextname = $contextname;
            $a->search = $search;
            return get_string('usersfrommatching', 'core_role', $a);
        } else {
            return get_string('usersfrom', 'core_role', $contextname);
        }
    }
}

function local_program_output_fragment_new_catform($args) {
    global $CFG, $DB;

    $args = (object) $args;
    $context = $args->context;
    $categoryid = $args->categoryid;
    $o = '';
    $formdata = [];
    if (!empty($args->jsonformdata)) {
        $serialiseddata = json_decode($args->jsonformdata);
        parse_str($serialiseddata, $formdata);
    }

    if ($args->categoryid > 0) {
        $heading = 'Update category';
        $collapse = false;
        $data = $DB->get_record('local_curriculum_categories', array('id' => $categoryid));
    }
    $editoroptions = [
        'maxfiles' => EDITOR_UNLIMITED_FILES,
        'maxbytes' => $course->maxbytes,
        'trust' => false,
        'context' => $context,
        'noclean' => true,
        'subdirs' => false,
    ];
    $group = file_prepare_standard_editor($group, 'description', $editoroptions, $context, 'group', 'description', null);

    $mform = new local_curriculum\form\catform(null, array('editoroptions' => $editoroptions), 'post', '', null, true, $formdata);

    $mform->set_data($data);

    if (!empty($formdata)) {
        // If we were passed non-empty form data we want the mform to call validation functions and show errors.
        $mform->is_validated();
    }

    ob_start();
    $mform->display();
    $o .= ob_get_contents();
    ob_end_clean();
    return $o;
}
function curriculum_filter($mform){
    global $DB,$USER;
    $stable = new stdClass();
    $stable->thead = false;
    $stable->start = 0;
    $stable->length = -1;
    $stable->search = '';
    $systemcontext = context_system::instance();
    $sql = "SELECT id, name FROM {local_curriculum} WHERE id > 1";
    if ((has_capability('local/request:approverecord', context_system::instance()) || is_siteadmin())) {
        $curriculums = (new program)->curriculums($stable,true);
        $componentid=$curriculums['curriculums']->curriculumids;
        if (!empty($componentid)) {
            $courseslist = $DB->get_records_sql_menu("SELECT id, name FROM {local_curriculum}
                WHERE id IN ($componentid)");
        } else {
            $courseslist = $DB->get_records_sql_menu("SELECT id, name FROM {local_curriculum} ");
        }
    }
    $select = $mform->addElement('autocomplete', 'curriculum', '', $courseslist,
        array('placeholder' => get_string('curriculum_name', 'local_program')));
    $mform->setType('curriculum', PARAM_RAW);
    $select->setMultiple(true);
}
function get_user_curriculum($userid) {
    global $DB;
    $sql = "SELECT lc.id, lc.name, lc.description
                FROM {local_curriculum} AS lc
                JOIN {local_curriculum_users} AS lcu ON lcu.curriculumid = lc.id
                WHERE userid = :userid AND lc.status IN (1, 4)";
    $curriculums = $DB->get_records_sql($sql, array('userid' => $userid));
    return $curriculums;
}

//<revathi> issue 818 geting all users in mass enroll filters starts
function departmentcourseusers1_filter($mform,$query='',$searchanywhere=false, $page=0, $perpage=25,$ccid, $programid){
    global $DB, $USER, $COURSE;
    $systemcontext = context_system::instance();
    $departmentuserlist=array();
    $data=data_submitted();
    
     $roleid = $DB->get_field('role','id',array('shortname' => 'student'));
    $userslistsql = "SELECT u.id, concat(u.firstname,' ',u.lastname) as fullname
                    FROM {user} AS u 
                    JOIN {local_costcenter} cc ON cc.id = u.open_departmentid
                    JOIN {local_program} as p ON p.departmentid = u.open_departmentid AND p.id= $programid
                    JOIN {role} as r ON r.id = u.open_role AND r.id = $roleid 
                    WHERE  u.id > 2 AND u.suspended = 0 AND u.deleted = 0 ";

    if (!is_siteadmin() && has_capability('local/costcenter:manage_ownorganization',$systemcontext)) {
        $userslistsql .= " AND u.open_costcenterid = " . $USER->open_costcenterid;
    }
    $departmentuserlist = $DB->get_records_sql_menu($userslistsql);
    
    $select = $mform->addElement('autocomplete', 'users', '',$departmentuserlist,array('placeholder' => get_string('users','local_program')));
    $mform->setType('users', PARAM_RAW);
    $select->setMultiple(true);
}

function departmentcourseusersemail1_filter($mform,$query='',$searchanywhere=false, $page=0, $perpage=25,$ccid, $programid){
    global $DB, $USER, $COURSE;
    $systemcontext = context_system::instance();
    $departmentuserlist=array();
    $data=data_submitted();
    
     $roleid = $DB->get_field('role','id',array('shortname' => 'student'));
    $userslistsql = "SELECT u.id, u.email as fullname
                    FROM {user} AS u 
                    JOIN {local_costcenter} cc ON cc.id = u.open_departmentid
                    JOIN {local_program} as p ON p.departmentid = u.open_departmentid AND p.id= $programid
                    JOIN {role} as r ON r.id = u.open_role AND r.id = $roleid 
                    WHERE  u.id > 2 AND u.suspended = 0 AND u.deleted = 0 ";

    if (!is_siteadmin() && has_capability('local/costcenter:manage_ownorganization',$systemcontext)) {
        $userslistsql .= " AND u.open_costcenterid = " . $USER->open_costcenterid;
    }
    $departmentuserlist = $DB->get_records_sql_menu($userslistsql);
    
    $select = $mform->addElement('autocomplete', 'email', '',$departmentuserlist,array('placeholder' => get_string('email','local_users')));
    $mform->setType('email', PARAM_RAW);
    $select->setMultiple(true);
}


//<revathi> issue 818 geting all users in mass enroll filters ends

class program_managesemester_form extends moodleform {

    public function definition() {
        global $CFG, $DB, $USER;
        $querieslib = new querylib();
        $mform = &$this->_form;
        $id = $this->_customdata['id'];
        $programid = $this->_customdata['programid'];
        $curriculumid = $this->_customdata['curriculumid'];
        $yearid = $this->_customdata['yearid'];
        $context = context_system::instance();

        //$mform->addElement('header', 'general', get_string('addcourses', 'local_program'));

        $mform->addElement('hidden', 'id', $id);
        $mform->setType('id', PARAM_INT);

        $mform->addElement('hidden', 'programid', $programid);
        $mform->setType('programid', PARAM_INT);

        $mform->addElement('hidden', 'curriculumid', $curriculumid);
        $mform->setType('curriculumid', PARAM_INT);

        $mform->addElement('hidden', 'yearid', $yearid);
        $mform->setType('yearid', PARAM_INT);

        $mform->addElement('text', 'semester', get_string('semester', 'local_program'));
        $mform->addRule('semester', null, 'required', null, 'client');

        $mform->addElement('editor', 'semester_description', get_string('description', 'local_program'), null, array('autosave' => false));
        $mform->setType('semester_description', PARAM_RAW);
        // $mform->addRule('description', null, 'required', null, 'client');

        $mform->disable_form_change_checker();
    }
}

function local_program_output_fragment_curriculum_managesemester_form($args) {
    global $CFG, $PAGE, $DB;
    $args = (object) $args;
    $context = $args->context;
    $return = '';
    $renderer = $PAGE->get_renderer('local_program');
    $formdata = [];
    if (!empty($args->jsonformdata)) {
        $serialiseddata = json_decode($args->jsonformdata);
        parse_str($serialiseddata, $formdata);
    }
    $formdata['id'] = $args->id;
    $formdata['curriculumid'] = $args->curriculumid;
    $formdata['programid'] = $args->programid;

    $mform = new program_managesemester_form(null, array('id' => $args->id,
        'curriculumid' => $args->curriculumid, 'programid' => $args->programid, 'yearid' => $args->yearid, 'form_status' => $args->form_status), 'post', '', null,
        true, $formdata);
    $bcsemester = new stdClass();
    $bcsemester->curriculumid = $args->curriculumid;
    $bcsemester->programid = $args->programid;
    if ($args->id > 0) {
        $bcsemester = $DB->get_record('local_curriculum_semesters', array('id' => $args->id));
    }

    $bcsemester->form_status = $args->form_status;
    $bcsemester->semester_description['text'] = $bcsemester->description;
    $mform->set_data($bcsemester);

    if (!empty((array) $serialiseddata)) {
        // If we were passed non-empty form data we want the mform to call validation functions and show errors.
        $mform->is_validated();
    }
    $formheaders = array_keys($mform->formstatus);
    $nextform = array_key_exists($args->form_status, $formheaders);
    if ($nextform === false) {
        return false;
    }
    ob_start();
    $formstatus = array();
    foreach (array_values($mform->formstatus) as $k => $mformstatus) {
        $activeclass = $k == $args->form_status ? 'active' : '';
        $formstatus[] = array('name' => $mformstatus, 'activeclass' => $activeclass);
    }
    $formstatusview = new \local_program\output\form_status($formstatus);
    $return .= $renderer->render($formstatusview);
    $mform->display();
    $return .= ob_get_contents();
    ob_end_clean();

    return $return;
}


class program_manageprogram_form extends moodleform {

    public function definition() {
        global $CFG, $DB, $USER;
        $querieslib = new querylib();
        $mform = &$this->_form;
        $id = $this->_customdata['id'];
        $costcenter = $this->_customdata['costcenter'];
        $ccdepartment = $this->_customdata['ccdepartment'];
        $editabel = $this->_customdata['editabel'];
        $copyeditabel = $this->_customdata['copyeditabel'];

        $context = context_system::instance();

        $mform->addElement('hidden', 'id', $id);
        $mform->setType('id', PARAM_INT);

        $mform->addElement('hidden', 'editabel', $editabel);
        $mform->setType('editabel', PARAM_INT);

        $mform->addElement('hidden', 'copyeditabel', $copyeditabel);
        $mform->setType('copyeditabel', PARAM_INT);

        if($editabel){

            if($copyeditabel){
                $programinfo=$DB->get_record_sql("SELECT * FROM {local_program} WHERE id=:id ",  array('id'=>$id));
                $costcentername=$DB->get_field('local_costcenter','fullname',array('id'=>$programinfo->costcenter));
                $mform->addElement('static', 'costcentername', get_string('costcenter', 'local_program'));
                $mform->setDefault('costcentername',$costcentername);
                $mform->addElement('hidden', 'costcenter',
                get_string('costcenter', 'local_program'));
                $mform->setType('costcenter', PARAM_INT);
                $mform->setDefault('costcenter', $programinfo->costcenter);
            }else{

                if (is_siteadmin() || ((has_capability('local/program:manage_multiorganizations', context_system::instance()) ||has_capability('local/costcenter:manage_multiorganizations', context_system::instance()))) ) {
                    $costcenters = array();

                    $costcenterslist = $DB->get_records_menu('local_costcenter',
                            array('visible' => 1, 'parentid' => 0),
                            'id', 'id, fullname');
                    $costcenters = array(null => get_string('select_costcenter',
                            'local_program')) + $costcenterslist;

                    if($id>0){
                        $text="<div class='note_text'>*Note: Updating some fields of the form is not allowed. Instead, please delete and create a new program as required.</div>";
                        $mform->addElement('static','text', '');
                        $mform->setDefault('text',$text);
                    }
                    if($id>0){
                    

                       $programinfo=$DB->get_record_sql("SELECT * FROM {local_program} WHERE id=:id ",  array('id'=>$id));
                        $costcentername=$DB->get_field('local_costcenter','fullname',array('id'=>$programinfo->costcenter));
                        $mform->addElement('static', 'costcentername', get_string('costcenter', 'local_program'));
                        $mform->setDefault('costcentername',$costcentername);
                        $mform->addElement('hidden', 'costcenter',
                        get_string('costcenter', 'local_program'));
                        $mform->setType('costcenter', PARAM_INT);
                        $mform->setDefault('costcenter', $programinfo->costcenter);
                    }else{

                        $mform->addElement('select', 'costcenter',
                                get_string('costcenter', 'local_program'), $costcenters,
                                array('data-class' => 'organizationselect'));
                        $mform->addRule('costcenter', get_string('missingcostcenter', 'local_program'), 'required', null, 'client');
                        $mform->setType('costcenter', PARAM_INT);
                    }
                } else {
                    $mform->addElement('hidden', 'costcenter',
                            get_string('costcenter', 'local_program'),
                            array('data-class' => 'organizationselect', 'id' => 'id_costcenter'));
                    $mform->setType('costcenter', PARAM_INT);
                    $mform->setDefault('costcenter', $USER->open_costcenterid);
                }
            }
            if($id>0){
                $departmentid = $DB->get_field('local_program','departmentid',array('id' => $id));
                $departmentname = $DB->get_record('local_costcenter',array('id' => $departmentid));
// <mallikarjun> - ODL-745 labels display -- starts
                if($departmentname->univ_dept_status == '1'){
                $mform->addElement('static', 'departmentname', get_string('college', 'local_program')); 
                }else{
                $mform->addElement('static', 'departmentname', get_string('departments', 'local_program'));
				}
// <mallikarjun> - ODL-745 labels display -- ends
                $mform->setDefault('departmentname',$departmentname->fullname);
                $mform->addElement('hidden', 'departmentid',$departmentid);
            }else{
// <mallikarjun> - ODL-711 adding college programs -- starts
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
			  			$existing_costcenter = $DB->get_field('user', 'open_costcenterid',array('id' => $id));
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
					}else if(!is_siteadmin() && has_capability('local/costcenter:view', $context)){
						$departments = userlib::find_departments_list($USER->open_costcenterid);
						foreach($departments['nonuniv_dep'] as $depart){
							$departmentslist[$depart->id]=$depart->fullname;
						}
					}
					// print_object($departmentslist);
				$mform->addElement('select', 'open_collegeid', get_string('collegelabel','local_users'),$departmentslist, array('class' => 'college_univ'));
			        /*$mform->addRule('open_collegeid', get_string('miisingcollegeid','local_users'), 'required', null, 'client');*/// Commented by Harish
			        $mform->hideIf('open_collegeid', 'open_univdept_status', 'eq', 0);
			        // Fetching college list mapped under university ends here //

			        // Fetching departments list mapped under university starts here //
			        $departmentslist = array(null => '--Select Department--');
			  		if($id > 0){
			  			$existing_costcenter = $DB->get_field('user', 'open_costcenterid',array('id' => $id));
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
					}else if(!is_siteadmin() && has_capability('local/costcenter:view', $context)){
						$departments = userlib::find_departments_list($USER->open_costcenterid);
						foreach($departments['univ_dep'] as $depart){
							$departmentslist[$depart->id]=$depart->fullname;
						}
					}
				$mform->addElement('select', 'departmentid', get_string('departments','local_users'),$departmentslist, array('class' => 'department_univ'));
			        /*$mform->addRule('departmentid', get_string('missing_departments','local_users'), 'required', null, 'client');*/// Commented by Harish
			        $mform->hideIf('departmentid', 'open_univdept_status', 'eq', 1);
// <mallikarjun> - ODL-711 adding college programs -- ends
            }
            $faculties =  array(null => get_string('select_facultieslist',
                        'local_program'));
            $facultylist = array();
            if($id > 0){
                $subsql = "SELECT lf.id, lf.facultyname FROM {local_faculties} AS lf 
                             WHERE 1=1";
                if($costcenter){
                    $subsql .= " AND lf.university = ".$costcenter."";
                }
                $facultylist = $DB->get_records_sql_menu($subsql);
            }elseif($this->_ajaxformdata['costcenter'] > 0){
                $subsql = "SELECT id, facultyname FROM {local_faculties} WHERE 1=1";
                if(!empty($this->_ajaxformdata['costcenter'])){
                    $subsql .= " AND university = ".$this->_ajaxformdata['costcenter']."";
                }
                $facultylist = $DB->get_records_sql_menu($subsql);
            }
            elseif($USER->open_costcenterid){
                $facultylist = $DB->get_records_sql_menu("SELECT id, facultyname FROM {local_faculties} WHERE university = $USER->open_costcenterid");
            }
            if($facultylist){
                $facultieslist = $faculties+$facultylist;
            }else{
                $facultieslist = $faculties;
            }

            /*$options = array(
                'ajax' => 'local_program/form-options-selector',
                'data-contextid' => $context->id,
                'data-action' => 'program_facultie_selector',
                'data-options' => json_encode(array('id' => $id)),
                'class' => 'facultieselect',
                'data-class' => 'facultieselect'
            );*/
// <mallikarjun> - ODL-711 removed faculty from programs -- starts
//            if($id>0){
//                $programinfo=$DB->get_record_sql("SELECT * FROM {local_program} WHERE id=:id ",  array('id'=>$id));
             
//                $facultyname = $DB->get_record('local_faculties',array('id' => $programinfo->facultyid));
//                $mform->addElement('static', 'facultyname', get_string('facultyid', 'local_program'));                    
//                $mform->setDefault('facultyname',$facultyname->facultyname);
//                $mform->addElement('hidden', 'facultyid',$facultyid);
//           }else{
//                $mform->addElement('select', 'facultyid',
//                        get_string('facultyid', 'local_program'), $facultieslist);
//               $mform->addRule('facultyid', get_string('missingfacultyid', 'local_program'), 'required', null, 'client');
//                $mform->setType('facultyid', PARAM_INT);
//            }
// <mallikarjun> - ODL-711 removed faculty from programs -- ends

            // Added department field for Program by Harish starts here //
//            $departments =  array(null => get_string('select_departmentlist',
//                        'local_program'));
            // $departmentslist = $this->_ajaxformdata['departmentid'];
            /*if (!empty($departmentslist)) {
                $departmentslist = $departmentslist;
            } else if ($departmentid > 0) {
                $departmentslist = $departmentid;
            }
            else if ($id > 0) {
                $departmentslistsql = "SELECT lp.departmentid
                                             FROM {local_program} as lp
                                             WHERE lp.id = :program ";
                $departmentslist = $DB->get_field_sql($departmentslistsql, array('program' => $id));
                // print_object($departmentslist);
            }elseif($this->_ajaxformdata['university'] > 0){
                $subsql = "SELECT id, fullname FROM {local_costcenter} WHERE 1 = 1";
                if(!empty($this->_ajaxformdata['university'])){
                    $subsql .= " AND university = ".$this->_ajaxformdata['university']."";
                }
                $departments = $departments+ $departmentslist;
            }
            if (!empty($departmentslist)) {
                $departmentslist = $DB->get_records_menu('local_costcenter', array('id' => $departmentslist, 'univ_dept_status' => 0),'id', 'id, fullname');
                $departments = $departments+ $departmentslist;
            }*/
            /*$ccoptions = array(
                'ajax' => 'local_program/form-options-selector',
                'data-contextid' => $context->id,
                'data-action' => 'program_department_selector',
                'data-options' => json_encode(array('id' => $id)),
                'class' => 'departmentselect',
                'data-class' => 'departmentselect'
            );*/
//            if($id > 0){
//                $subsql = "SELECT id, fullname FROM {local_costcenter} WHERE 1 = 1 AND univ_dept_status = 0 AND visible = 1";
//                if($costcenter){
//                    $subsql .= " AND parentid = ".$costcenter."";
//                }
//                $departmentslist = $DB->get_records_sql_menu($subsql);
//            }elseif($this->_ajaxformdata['costcenter'] > 0 && $this->_ajaxformdata['facultyid'] > 0){
//                $subsql = "SELECT id, fullname FROM {local_costcenter} WHERE 1=1 AND univ_dept_status = 0 AND visible = 1";
//                if(!empty($this->_ajaxformdata['costcenter'])){
//                    $subsql .= " AND parentid = ".$this->_ajaxformdata['costcenter']."";
//                }
//                if(!empty($this->_ajaxformdata['facultyid'])){
//                    $subsql .= " AND faculty = ".$this->_ajaxformdata['facultyid']."";
//                }
//                $departmentslist = $DB->get_records_sql_menu($subsql);
//            }
//            elseif($USER->open_costcenterid){
//                if(!empty($this->_ajaxformdata['facultyid'])){
//                    $facultyid = $this->_ajaxformdata['facultyid'];
//                    $departmentslist = $DB->get_records_sql_menu("SELECT id, fullname FROM {local_costcenter} WHERE univ_dept_status = 0 AND visible = 1 AND parentid = $USER->open_costcenterid AND faculty = $facultyid");
//                }
//            }
//            if($departmentslist){
//                $departments = $departments+$departmentslist;
//            }
//            if($id>0){
//                $departmentid = $DB->get_field('local_program','departmentid',array('id' => $id));
//                $departmentname = $DB->get_record('local_costcenter',array('id' => $departmentid));
//                $mform->addElement('static', 'departmentname', get_string('departments', 'local_program'));                    
//                $mform->setDefault('departmentname',$departmentname->fullname);
//                $mform->addElement('hidden', 'departmentid',$departmentid);
//            }else{
//				$mform->addElement('select', 'open_collegeid', get_string('collegelabel','local_users'),$departmentslist, array('class' => 'college_univ'));
//			        /*$mform->addRule('open_collegeid', get_string('miisingcollegeid','local_users'), 'required', null, 'client');*/// Commented by Harish
//			        $mform->hideIf('open_collegeid', 'open_univdept_status', 'eq', 0);
//				$mform->addElement('select', 'departmentid', get_string('departments','local_users'),$departmentslist, array('class' => 'department_univ'));
//			        /*$mform->addRule('departmentid', get_string('missing_departments','local_users'), 'required', null, 'client');*/// Commented by Harish
//			        $mform->hideIf('departmentid', 'open_univdept_status', 'eq', 1);
 //               $mform->addElement('select', 'departmentid',
 //                           get_string('departments', 'local_program'), $departments/*, $ccoptions*/);
//                $mform->addRule('departmentid', get_string('missingdepartmentid', 'local_program'), 'required', null, 'client');
//                $mform->setType('departmentid', PARAM_INT);
//            }
            // Added department field for Program by Harish ends here //

            // Added curriculum field for Program by Harish starts here //
            $curriculums =  array(null => get_string('select_curriculumlist',
                        'local_program'));
            // $curriculumlist = $this->_ajaxformdata['curriculumid'];
            /*if (!empty($curriculumlist)) {
                $curriculumlist = $curriculumlist;
            } else if ($curriculumid > 0) {
                $curriculumlist = $curriculumid;
            }
            else if ($id > 0) {
                    $curriculumlistsql = "SELECT cc.id
                                             FROM {local_curriculum} cc
                                             JOIN {local_program} c ON c.curriculumid = cc.id
                                                 AND
                                                 c.id = :curriculumid";
                    $curriculumlist = $DB->get_field_sql($curriculumlistsql, array('curriculumid' => $id));
            }elseif($this->_ajaxformdata['university'] > 0){
                $subsql = "SELECT id, name FROM {local_curriculum} WHERE 1 = 1 AND program = 0";
                if(!empty($this->_ajaxformdata['university'])){
                    $subsql .= " AND university = ".$this->_ajaxformdata['university']."";
                }
                $departments = $departments+ $departmentslist;
            }
            if (!empty($curriculumlist)) {
                $curriculumlist = $DB->get_records_menu('local_curriculum', array('id' => $curriculumlist),'id', 'id, name');
                $curriculums = $curriculums+ $curriculumlist;
            }*/
            /*$ccoptions = array(
                'ajax' => 'local_program/form-options-selector',
                'data-contextid' => $context->id,
                'data-action' => 'program_curriculum_selector',
                'data-options' => json_encode(array('id' => $id)),
                'class' => 'curriculumselect',
                'data-class' => 'curriculumselect'
            );*/
            if($id > 0){
                 $subsql = "SELECT id, name FROM {local_curriculum} WHERE program = 0 AND curriculum_publish_status = 1";
                // $subsql = "SELECT id, name FROM {local_curriculum} WHERE program = ".$id." AND curriculum_publish_status = 1";
                if($costcenter){
                    $subsql .= " AND costcenter = ".$costcenter."";
                }
                if($ccdepartment){
                    $subsql .= " AND department = ".$ccdepartment."";   
                }
                $curriculumslist = $DB->get_records_sql_menu($subsql);
// <mallikarjun> - ODL-810 adding college programs -- starts
            }elseif($this->_ajaxformdata['costcenter'] > 0 && ($this->_ajaxformdata['departmentid'] > 0 || $this->_ajaxformdata['open_collegeid'] > 0)){
                $subsql = "SELECT id, name FROM {local_curriculum} WHERE  program = 0 AND curriculum_publish_status = 1";
                if(!empty($this->_ajaxformdata['costcenter'])){
                    $subsql .= " AND costcenter = ".$this->_ajaxformdata['costcenter']."";
                }
                if(!empty($this->_ajaxformdata['departmentid'])){
                    $subsql .= " AND department = ".$this->_ajaxformdata['departmentid']."";
                }
                if(!empty($this->_ajaxformdata['open_collegeid'])){
                    $subsql .= " AND department = ".$this->_ajaxformdata['open_collegeid']."";
                }
                $curriculumslist = $DB->get_records_sql_menu($subsql);
            }elseif($USER->open_costcenterid){
                if(!empty($this->_ajaxformdata['departmentid'])){
                    $cclistsql = "SELECT id, name FROM {local_curriculum} WHERE program = 0 AND curriculum_publish_status = 1 AND costcenter = $USER->open_costcenterid"; 
                    $cclistsql .=" AND department = ".$this->_ajaxformdata['departmentid']."";
                    $curriculumslist = $DB->get_records_sql_menu($cclistsql);
                }
// <mallikarjun> - ODL-810 adding college programs -- ends
                if(!empty($this->_ajaxformdata['open_collegeid'])){
                    $cclistsql = "SELECT id, name FROM {local_curriculum} WHERE program = 0 AND curriculum_publish_status = 1 AND costcenter = $USER->open_costcenterid"; 
                    $cclistsql .=" AND department = ".$this->_ajaxformdata['open_collegeid']."";
                    $curriculumslist = $DB->get_records_sql_menu($cclistsql);
                }
            }
            // print_r($curriculumslist);
            // exit;
            if($curriculumslist){
                $curriculums = $curriculums+$curriculumslist;
            }
            // print_r($curriculums);
            // exit;
            if($id>0){
                $programname = $DB->get_field('local_program','fullname',array('id' => $id));
                 $curriculumid = $DB->get_field('local_program','curriculumid',array('id' => $id));
                 $curriculumname=$DB->get_field('local_curriculum','name',array('id'=>$curriculumid));
                if($programname!=$curriculumname){
                    $mform->addElement('static', 'curriculumname', get_string('curriculum', 'local_program'));
                    $mform->setDefault('curriculumname',$curriculumname);
                }else{
                    $curriculumname ="No curriculum template used";
                    $mform->addElement('static', 'curriculumname', get_string('curriculum', 'local_program'));
                    $mform->setDefault('curriculumname',$curriculumname);
                }

            }else{
                $mform->addElement('select', 'curriculumid',
                        get_string('curriculum', 'local_program'), $curriculums/*, $ccoptions*/);
           // $mform->addRule('curriculumid', get_string('missingcurriculum', 'local_program'), 'required', null, 'client');
            //$mform->setType('curriculumid', PARAM_RAW);
            }
            // Added curriculum field for Program by Harish ends here //
        }else{
            $programinfo=$DB->get_record_sql("SELECT * FROM {local_program} WHERE id=:id ",  array('id'=>$id));

            $costcentername=$DB->get_field('local_costcenter','fullname',array('id'=>$programinfo->costcenter));

            $mform->addElement('static', 'costcentername', get_string('costcenter', 'local_program'));
            $mform->setDefault('costcentername',$costcentername);

            $mform->addElement('hidden', 'costcenter',
            get_string('costcenter', 'local_program'));
            $mform->setType('costcenter', PARAM_INT);
            $mform->setDefault('costcenter', $programinfo->costcenter);

            $facultyname=$DB->get_field('local_faculties','facultyname',array('id'=>$programinfo->facultyid));

            $mform->addElement('static', 'facultyname', get_string('facultyid', 'local_program'));
            $mform->setDefault('facultyname',$facultyname);

            $mform->addElement('hidden', 'facultyid',
            get_string('facultyid', 'local_program'));
            $mform->setType('facultyid', PARAM_INT);
            $mform->setDefault('facultyid', $programinfo->facultyid);


            // $mform->addElement('static', 'programname', get_string('program', 'local_program'));
            // $mform->setDefault('programname',$programinfo->fullname);

            // $mform->addElement('hidden', 'fullname',
            // get_string('program', 'local_program'));
            // $mform->setType('fullname', PARAM_RAW);
            // $mform->setDefault('fullname', $programinfo->fullname);


            // $mform->addElement('static', 'programshortname', get_string('shortname', 'local_program'));
            // $mform->setDefault('programshortname',$programinfo->shortname);

            // $mform->addElement('hidden', 'shortname',
            // get_string('shortname', 'local_program'));
            // $mform->setType('shortname', PARAM_RAW);
            // $mform->setDefault('shortname', $programinfo->shortname);


            // $mform->addElement('static', 'programshortcode', get_string('shortcode', 'local_program'));
            // $mform->setDefault('programshortcode',$programinfo->shortcode);

            // $mform->addElement('hidden', 'shortcode',
            // get_string('shortcode', 'local_program'));
            // $mform->setType('shortcode', PARAM_RAW);
            // $mform->setDefault('shortcode', $programinfo->shortcode);

            $mform->addElement('hidden', 'type',get_string('curriculumtype', 'local_program'));
            $mform->setType('type', PARAM_INT);
            $mform->setDefault('type',1);


            $semester = array('1' => 'Undergraduate', '2' => 'Post Graduate');

            $mform->addElement('static', 'curriculumsemestername', get_string('curriculumsemester', 'local_program'));
            $mform->setDefault('curriculumsemestername',$semester[$programinfo->curriculumsemester]);

            $mform->addElement('hidden', 'curriculumsemester',
            get_string('curriculumsemester', 'local_program'));
            $mform->setType('curriculumsemester', PARAM_INT);
            $mform->setDefault('curriculumsemester', $programinfo->curriculumsemester);


            $departmentname=$DB->get_field('local_costcenter','fullname',array('id'=>$programinfo->departmentid));

            $mform->addElement('static', 'departmentname', get_string('departments', 'local_program'));
            $mform->setDefault('departmentname',$departmentname);

            $mform->addElement('hidden', 'departmentid',
            get_string('departmentid', 'local_program'));
            $mform->setType('departmentid', PARAM_INT);
            $mform->setDefault('departmentid', $programinfo->departmentid);

            $curriculumname=$DB->get_field('local_curriculum','name',array('id'=>$programinfo->curriculumid));

            $mform->addElement('static', 'curriculumname', get_string('curriculum', 'local_program'));
            $mform->setDefault('curriculumname',$curriculumname);

            $mform->addElement('hidden', 'curriculumid',
            get_string('curriculumid', 'local_program'));
            $mform->setType('curriculumid', PARAM_INT);
            $mform->setDefault('curriculumid', $programinfo->curriculumid);
        }
        
        $mform->addElement('text', 'fullname', get_string('program', 'local_program'));
        $mform->addRule('fullname', get_string('missingprogramfullname', 'local_program'), 'required', null, 'client');

        $mform->addElement('text', 'shortname', get_string('shortname', 'local_program'));
        $mform->addRule('shortname', get_string('missingshortname', 'local_program'), 'required', null, 'client');

        $mform->addElement('text', 'shortcode', get_string('shortcode', 'local_program'));
        $mform->addRule('shortcode', get_string('missingshortcode', 'local_program'), 'required', null, 'client');


        //$type = array('1' => 'Online', '2' => 'Offline');
        //$mform->addElement('autocomplete', 'type', get_string('curriculumtype', 'local_program'), $type);
        //$mform->addElement('static', 'curriculumtypeinfo', '', get_string('static_prgtypeinfo','local_program'));

        if($editabel){
            $mform->addElement('hidden', 'type',get_string('curriculumtype', 'local_program'));
            $mform->setType('type', PARAM_INT);
            $mform->setDefault('type',1);


            $semester = array('1' => 'Undergraduate', '2' => 'Post Graduate');
            $mform->addElement('autocomplete', 'curriculumsemester', get_string('curriculumsemester', 'local_program'), $semester);
        }
            /*-- Added Applications Startdate and Enddate for Curriculum -- RangaReddy-- 05-02-2019----Starts---*/
            $mform->addElement('date_selector', 'admissionstartdate', get_string('admissionstartdate', 'local_program'),array('startyear'=>date('Y',time())));
            $mform->addRule('admissionstartdate', get_string('missingadmissionstartdate', 'local_program'), 'required', null, 'client');

            $mform->addElement('date_selector', 'admissionenddate', get_string('admissionenddate', 'local_program'),array('startyear'=>date('Y',time())));
            $mform->addRule('admissionenddate', get_string('missingadmissionenddate', 'local_program'), 'required', null, 'client');

        /*if($editabel){

            $duration = array();
            $duration[] = & $mform->createElement('text', 'duration');
            $duration_format = array('Y' => 'Years', 'M' => 'Months');
            $duration[] = & $mform->createElement('select', 'duration_format', get_string('forumtype', 'forum'), $duration_format);


            $myduration = $mform->addElement('group', 'durationfield', get_string('curriculumduration', 'local_program'), $duration, '  ', false);
            $mform->addRule('durationfield', null, 'required', null, 'client');

            // Add numeric rule to text field.
            $wordlimitgrprules = array();
            $wordlimitgrprules['duration'][] = array(null, 'required', null, 'client');
            $wordlimitgrprules['duration'][] = array(get_string('possitiveonly','local_program'), 'regex', '#^(0|[1-9][0-9]*)$#');
            $mform->addGroupRule('durationfield', $wordlimitgrprules);
        }else{

            $duration = array();
            $duration[] = & $mform->createElement('static', 'durationname');
            $duration_format = array('Y' => 'Years', 'M' => 'Months');
            $duration[] = & $mform->createElement('static', 'durationformat');

            $myduration = $mform->addElement('group', 'customdurationfield', get_string('curriculumduration', 'local_program'), $duration, '  ', false);
            $mform->setDefault('durationname',$programinfo->duration);
            $mform->setDefault('durationformat', $duration_format[$programinfo->duration_format]);


            $duration = array();
            $duration[] = & $mform->createElement('hidden', 'duration');
            $duration_format = array('Y' => 'Years', 'M' => 'Months');
            $duration[] = & $mform->createElement('hidden', 'duration_format');

            $myduration = $mform->addElement('group', 'durationfield','', $duration, '  ', false);
            $mform->setDefault('duration',$programinfo->duration);
            $mform->setDefault('duration_format',$programinfo->duration_format);


        }*/

            $mform->addElement('date_selector', 'validtill', get_string('validtill', 'local_program'),array('startyear'=>date('Y',time())));
            $mform->addRule('validtill', get_string('missingvalidtilldate', 'local_program'), 'required', null, 'client');
            $mform->setType('validtill', PARAM_RAW);
        // if($editabel){
            $options = array_combine(range(2018,2100), range(2018,2100));
            $mform->addElement('select', 'year', get_string('year', 'local_program'), $options);
            $mform->setType('year', PARAM_INT);
            if ($id == 0) {
                $mform->setDefault('year',date("Y"));
            }
        // }else{
        //     $mform->addElement('static', 'yearname', get_string('year', 'local_program'));
        //     $mform->setDefault('yearname',$programinfo->year);

        //     $mform->addElement('hidden', 'year',
        //     get_string('year', 'local_program'));
        //     $mform->setType('year', PARAM_INT);
        //     $mform->setDefault('year', $programinfo->year);
        // }
            // Condition to check if any colleges are affiliated for current program - IUMS-441 starts here//
            if($id > 0){
                $checkaffsql = "SELECT COUNT(lc.id) FROM {local_costcenter} AS lc JOIN {local_program} as lp ON lc.id = lp.costcenter WHERE lp.parentid = $id";
                $affiliatedcount = $DB->count_records_sql($checkaffsql);
            }
            if($affiliatedcount == 0){
                $mform->addElement('advcheckbox', 'program_approval', get_string('program_approval', 'local_program','',''));
                $mform->setType("program_approval", PARAM_BOOL);

                if($id == 0){
                    $mform->addElement('textarea','pre_requisites','',array("id"=>"status_block","style"=>"display:none"));
                    $mform->setType('pre_requisites', PARAM_RAW);
                }
                else{
                    $result = $DB->get_record_sql("SELECT program_approval,pre_requisites FROM {local_program} WHERE id = $id");
                        if(empty($result->pre_requisites))
                      {
                          $result->program_approval = 0;
                          $DB->execute("UPDATE {local_program} SET program_approval=0 WHERE id=$id");
                      }
                      if($result->program_approval == 1){
                          $mform->addElement('textarea','pre_requisites','',array("id"=>"status_block","style"=>"display:block"));
                          $mform->setType('pre_requisites', PARAM_RAW);
                      }
                      else{
                          $mform->addElement('textarea','pre_requisites','',array("id"=>"status_block","style"=>"display:none"));
                          $mform->setType('pre_requisites', PARAM_RAW);
                        }
                }
            }
            // Condition to check if any colleges are affiliated for current program - IUMS-441 ends here//
            $logoupload = array('maxbytes'       => $CFG->maxbytes,
                              'subdirs'        => 0,                             
                              'maxfiles'       => 1,                             
                              'accepted_types' => 'web_image');
            $mform->addElement('filemanager', 'program_logo', get_string('program_logo', 'local_program'), '', $logoupload);
            $mform->addRule('program_logo', get_string('missingprogram_logo', 'local_program'), 'required', null, 'client');
            $mform->addElement('textarea', 'short_description', get_string('program_shortdescription', 'local_program'),null,array('autosave' => false));
            $mform->addRule('short_description', get_string('missingshortdescription', 'local_program'), 'required', null, 'client');
            $mform->addRule('short_description', get_string('shortdescription_maxlengtherror', 'local_program'), 'maxlength', '200', 'client');
            $mform->setType('short_description', PARAM_RAW);
            $mform->addElement('editor', 'program_description', get_string('description', 'local_program'),null,array('autosave' => false));
            $mform->setType('program_description', PARAM_RAW);

            $mform->disable_form_change_checker();
    }
    public function validation($data, $files) {
        $errors = array();
        global $DB, $CFG;
        //print_r($data);
        $errors = parent::validation($data, $files);
        $costcenterid = $data['costcenter'];
        $fullname = $data['fullname'];
        $shortname = $data['shortname'];
        $shortcode = $data['shortcode'];
        $year = $data['year'];
        $id = $data['id'];

        if(!empty($shortname)){
           $shortname = preg_match('/^\S*$/', $shortname); 
           if(!$shortname){
            $errors['shortname'] = get_string('spacesnotallowed', 'local_program');
           }

        }
        if(!empty($shortcode)){
           $shortcode = preg_match('/^\S*$/', $shortcode); 
           if(!$shortcode){
            $errors['shortcode'] = get_string('spacesnotallowed', 'local_program');
           }

        }
        
//       $facultyname=$DB->get_field('local_faculties','facultyname',array('id'=>$data['facultyid'],'university'=>$data['costcenter']));

        if(!isset($data['costcenter'])){
            $errors['costcenter'] = get_string('missingcostcenter', 'local_program');
        }

//        if(!isset($data['facultyid'])||$data['facultyid']==null){
//            $errors['facultyid'] = get_string('missingfacultyid', 'local_program');
//        }
//        if(!isset($data['departmentid'])){
//            $errors['departmentid'] = get_string('missingdepartmentid', 'local_program');
//        }
        // if(!isset($data['curriculumid'])){
        //     $errors['curriculumid'] = get_string('missingcurriculum', 'local_program');
        // }
//        else if(!$facultyname){
//            $errors['facultyid'] = 'Mismatching faculty for selected university.';
//        }
         //Added by Revathi issue ODL-744 warning message starts
      
                if($data['open_univdept_status'] == 0){
                    if($data['departmentid'] == null){
                        $errors['departmentid'] = get_string('missing_departments', 'local_users');
                    }
                }else{
                    if($data['open_collegeid'] == null){
                        $errors['open_collegeid'] = get_string('miisingcollegeid', 'local_users');
                    }
               }
       // Revathi issue ODL-744 warning message ends


        if($data['admissionstartdate'] >= $data['admissionenddate']){
            $errors['admissionenddate'] = get_string('admissionenddate_error', 'local_program');
        }
        //Added by Harish for issue no. IUMS-312 starts here//
        if($data['validtill'] <= $data['admissionenddate']){
            $errors['validtill'] = get_string('validtill_error', 'local_program');
        }
        //Added by Harish for issue no. IUMS-312 ends here//
        $programshortname = $DB->get_field_sql('SELECT id FROM {local_program} WHERE shortname = ? AND costcenter = ? AND year = ? AND id <> ? AND parentid = ?', array($shortname, $costcenterid,$year,$id,0));

        if (!empty($programshortname)) {
            $errors['shortname'] = get_string('shortnameexists', 'local_program');
        }
        $programshortcode = $DB->get_field_sql('SELECT id FROM {local_program} WHERE shortcode = ? AND costcenter = ? AND year = ? AND id <> ? AND parentid = ?', array($shortcode, $costcenterid,$year,$id,0));

        if (!empty($programshortcode)) {
            $errors['shortcode'] = get_string('shortcodeexists', 'local_program');
        }
        $programname = $DB->get_field_sql('SELECT id FROM {local_program} WHERE fullname = ? AND costcenter = ? AND year = ? AND id <> ? AND parentid = ?', array($fullname, $costcenterid,$year,$id,0));

        if (!empty($programname)) {
            $errors['fullname'] = get_string('fullnameexists', 'local_program');
        }
      /*  if (isset($program_approval->_attributes[‘checked’])) {
         $myselect = $mform->createElement('text', 'status', 'status');
         $mform->insertElementBefore($myselect, 'status');
        }*/
         // echo "test";
       //    print_r($errors);
       // exit;
        return $errors;
    }
}

function local_program_output_fragment_program_manageprogram_form($args) {
    global $CFG, $PAGE, $DB;
    $args = (object) $args;
    $context = $args->context;
    $return = '';
    $renderer = $PAGE->get_renderer('local_program');
    $formdata = [];
    if (!empty($args->jsonformdata)) {
        $serialiseddata = json_decode($args->jsonformdata);
        parse_str($serialiseddata, $formdata);
    }
    $formdata['id'] = $args->id;
    $bcprogram = new stdClass();
    if ($args->id > 0) {
        $bcprogram = $DB->get_record('local_program', array('id' => $args->id));
    }
    // if ($args->id > 0) {
    //     $curriculum = $DB->get_field('local_curriculum','name', array('id' => $bcprogram->curriculumid));
    // }
    $mform = new program_manageprogram_form(null, array('id' => $args->id,
        'form_status' => $args->form_status,'costcenter' => $bcprogram->costcenter,'ccdepartment' => $bcprogram->departmentid,'editabel'=>$args->editabel,'copyeditabel'=>$args->copyeditabel), 'post', '', null,
        true, $formdata);
      
    $bcprogram->form_status = $args->form_status;
    $bcprogram->program_description['text'] = $bcprogram->description;
   // $bcprogram->curriculumid = $bcprogram->curriculumid;
     // print_r($bcprogram);
     //  exit;
    $mform->set_data($bcprogram);
   
    // print_r($mform);
    
    if (!empty((array) $serialiseddata)) {
        // If we were passed non-empty form data we want the mform to call validation functions and show errors.
        $mform->is_validated();
    }
    $formheaders = array_keys($mform->formstatus);
    $nextform = array_key_exists($args->form_status, $formheaders);
    if ($nextform === false) {
        return false;
    }
    ob_start();
    $formstatus = array();
    foreach (array_values($mform->formstatus) as $k => $mformstatus) {
        $activeclass = $k == $args->form_status ? 'active' : '';
        $formstatus[] = array('name' => $mformstatus, 'activeclass' => $activeclass);
    }
    $formstatusview = new \local_program\output\form_status($formstatus);
    $return .= $renderer->render($formstatusview);
    $mform->display();
    $return .= ob_get_contents();
    ob_end_clean();

    return $return;
}
/*
* Author Rizwana
* Displays a node in left side menu
* @return  [type] string  link for the leftmenu
*/
function local_program_leftmenunode(){
    $systemcontext = context_system::instance();
    $curriculumnode = '';
    if(is_siteadmin() || has_capability('local/program:manageprogram', context_system::instance())){
        $labelname=get_string('manage_programs','local_program');
    }else{
        $labelname=get_string('view_programs','local_program');
    }
    if(has_capability('local/program:manageprogram', context_system::instance()) ||has_capability('local/program:viewprogram', context_system::instance()) ||
        is_siteadmin()) {
        $curriculumnode .= html_writer::start_tag('li', array('id'=> 'id_leftmenu_browsecurriculums', 'class'=>'pull-left user_nav_div browsecurriculums dropdown-item'));
            $curriculums_url = new moodle_url('/local/program/index.php',array('type'=>1));
            $curriculum_icon = '<i class="fa fa-tasks"></i>';
            $curriculums = html_writer::link($curriculums_url, $curriculum_icon.'<span class="user_navigation_link_text">'.$labelname.'</span>',array('class'=>'user_navigation_link'));
            $curriculumnode .= $curriculums;
        $curriculumnode .= html_writer::end_tag('li');
    }
    return array('9' => $curriculumnode);
}
function local_program_quicklink_node(){
    global $CFG, $DB;

    $stable = new \stdClass();
    $stable->thead = true;
    $curriculumprograms = (new program)->curriculumprograms($stable);
    $programscount = $curriculumprograms['curriculumprogramscount'];

    $programs_content .= '<div class="w-full pull-left list_wrapper cyan_block">
                                <div class="w-full pull-left top_content">
                                    <span class="pull-left quick_links_icon program_icon"></span>
                                    <span class="pull-right quick_links_count"><a href="'.$CFG->wwwroot.'/local/program/index.php">'.$programscount.'</a></span>
                                </div>
                                <div class="w-full pull-left pl-15px pr-15px"><a class="quick_link" href="'.$CFG->wwwroot.'/local/program/index.php">'.get_string('browse_programs', 'local_program').'</a></div>
                            </div>';
    return array('3' => $programs_content);
}


/**
 * process the bootcamp_mass_enroll
 * @param csv_import_reader $cir  an import reader created by caller
 * @param Object $bootcamp  a bootcamp record from table mdl_local_bootcamp
 * @param Object $context  course context instance
 * @param Object $data    data from a moodleform
 * @return string  log of operations
 */
function curriculum_mass_enroll($cir, $curriculum, $context, $data) {
    global $CFG,$DB, $USER;
    require_once ($CFG->dirroot . '/group/lib.php');
    // require_once($CFG->dirroot . '/local/program/notifications_emails.php');
    //$emaillogs = new programnotifications_emails();
    // init csv import helper
    $useridfield = $data->firstcolumn;
    $cir->init();
    $enrollablecount = 0;
    while ($fields = $cir->next()) {
        $a = new stdClass();
        if (empty ($fields))
            continue;
        $fields[0]= str_replace('"', '', trim($fields[0]));
        /*First Condition To validate users*/
        $sql="select u.* from {user} u where u.deleted=0 and u.suspended=0 and u.$useridfield='$fields[0]'";

        if (!$user = $DB->get_record_sql($sql)) {
            $result .= '<div class="alert alert-error">'.get_string('im:user_unknown', 'local_courses', $fields[0] ). '</div>';
            continue;
        } else {
            // if (file_exists($CFG->dirroot . '/local/lib.php')) {
            //     require_once($CFG->dirroot . '/local/lib.php');
            // }
            $allow = true;
            $type = 'curriculum_enrol';
            $dataobj = $curriculum->id;
            $fromuserid = $USER->id;
            if ($allow) {
                // foreach ($userstoassign as $key => $adduser) {
                    if (true) {
                        $curriculumuser = new stdClass();
                        $curriculumuser->curriculumid = $curriculum->id;
                        $curriculumuser->courseid = 0;
                        $curriculumuser->userid = $user->id;
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
                            $local_curriculum = $DB->get_record_sql("SELECT * FROM {local_curriculum} where id = $curriculum->id");

                            $params = array(
                                'context' => context_system::instance(),
                                'objectid' => $curriculumuser->id,
                                'other' => array('curriculumid' => $curriculum->id)
                            );

                            $event = \local_program\event\program_users_enrol::create($params);
                            $event->add_record_snapshot('local_curriculum_users', $curriculumuser);
                            $event->trigger();

                            if ($local_curriculum->status == 0) {
                                $email_logs = $emaillogs->curriculum_emaillogs($type, $dataobj, $curriculumuser->userid, $fromuserid);
                            }
                            $result .= '<div class="alert alert-success">'.get_string('im:enrolled_ok', 'local_courses', fullname($user)).'</div>';
                            $enrollablecount ++;
                        } catch (dml_exception $ex) {
                            print_error($ex);
                        }
                    } else {
                        break;
                    }
                // }
                $curriculumid = $curriculum->id;
                $curriculum = new stdClass();
                $curriculum->id = $curriculumid;
                $curriculum->totalusers = $DB->count_records('local_curriculum_users',
                    array('curriculumid' => $curriculumid));
                $DB->update_record('local_curriculum', $curriculum);
            }
        }
    }
    $result .= '<br />';//exit;
    $result .= get_string('im:stats_i', 'local_program', $enrollablecount) . "";
    return $result;
}
function get_user_program_syllabus($programid){
    global $DB, $USER;
    $sql = "SELECT y.id as yearid, y.year,p.id FROM {local_program_cc_years} AS y
            JOIN {local_program} AS p ON p.id = y.programid
            JOIN {local_ccuser_year_signups} AS lccss ON lccss.programid = p.id WHERE lccss.userid = $USER->id and p.id=$programid";
    $years = $DB->get_records_sql($sql);

    $yearslist = array();
    foreach($years as $year){
        $yearrow = array();
        $yearrow['yearnames'] = $year->year;
        $semsters = $DB->get_records_sql("SELECT id, semester FROM {local_curriculum_semesters} WHERE yearid = $year->yearid AND programid=$programid");
        $semesterslist = array();
        foreach($semsters as $semester){
            $semsterrow = array();
            $semsterrow['semesternames'] = $semester->semester;


            $semcourses = $DB->get_records_sql("SELECT c.id, c.fullname
                    FROM {course} AS c
                    JOIN {local_cc_semester_courses} AS lcsc ON lcsc.semesterid = $semester->id
                    WHERE lcsc.courseid = c.id");
            $courseslist = array();
            foreach($semcourses as $semcourse){
                $coursesrow = array();
                $coursesrow['coursenames'] = $semcourse->fullname;
                $courseslist[] = $coursesrow;
            }
            $semsterrow['courses'] = $courseslist;
            $semesterslist[] = $semsterrow;
        }
        $yearrow['semesters'] = $semesterslist;
        $yearslist[] = $yearrow;
    }
    return $yearslist;
}
/*
* Author Sarath
* return count of curriculums under selected costcenter
* @return  [type] int count of curriculums
*/
function costcenterwise_program_count($costcenter,$department = false){
    global $USER, $DB;
        $params = array();
        $params['costcenter'] = $costcenter;
        $countcurriculumql = "SELECT count(id) FROM {local_program} WHERE costcenter = :costcenter  AND parentid = 0";
        if($department){
            $countcurriculumql .= " AND departmentid = :department ";
            $params['department'] = $department;
        }
         $activesql = " AND status = 1 ";
        $inactivesql = " AND status = 0 ";
         $countcurriculumql.$activesql;
        $countcurriculums = $DB->count_records_sql($countcurriculumql, $params);
        $activecurriculums = $DB->count_records_sql($countcurriculumql.$activesql, $params);
        $inactivecurriculums = $DB->count_records_sql($countcurriculumql.$inactivesql, $params);
    return array('program_plugin_exist' => true,'allprogramcount' => $countcurriculums,'activeprogramcount' => $activecurriculums,'inactiveprogramcount' => $inactivecurriculums);
}
class local_programs_potential_colleges extends user_selector_base {
    protected $pid;
    protected $uid;
    protected $context;
    /**
     * @param string $name control name
     * @param array $options should have two elements with keys groupid and courseid.
     */
    public function __construct($name, $options) {
        global $CFG;
        $this->jsmodule1 = array(
                'name' => 'local_program',
                'fullpath' => '/local/program/module.js',
                'requires'  => array('node', 'event-custom', 'datasource', 'json', 'moodle-core-notification'),
                'strings' => array(
                    array('previouslyselectedcolleges', 'local_program', '%%SEARCHTERM%%'),
                    array('nomatchingcolleges', 'local_program', '%%SEARCHTERM%%'),
                    array('none', 'moodle')
                ));
        if (isset($options['context'])) {
            $this->context = context_system::instance();
        } else {
            $this->context = context_system::instance();
        }
        $options['accesscontext'] = context_system::instance();
        $options['extrafields'] = array();
        parent::__construct($name, $options);
        $this->pid = $options['pid'];
        $this->uid = $options['uid'];
        $this->searchanywhere = true;
        require_once($CFG->dirroot . '/group/lib.php');
        $this->maxusersperpage = 10;
    }

    protected function get_options() {
        global $CFG;
        $options = parent::get_options();
        $options['file'] = 'local/program/lib.php';
        $options['pid'] = $this->pid;
        $options['uid'] = $this->uid;
        $options['contextid'] = $this->context->id;
        return $options;
    }
    public function output_user($user) {
        $out = $user->fullname.' ('.$user->shortname.')';
        return $out;
    }
    public function display($return = false) {
        global $PAGE;

        // Get the list of requested users.
        $search = optional_param($this->name . '_searchtext', '', PARAM_RAW);
        if (optional_param($this->name . '_clearbutton', false, PARAM_BOOL)) {
            $search = '';
        }
        $groupedusers = $this->find_users($search);

        // Output the select.
        $name = $this->name;
        $multiselect = '';
        if ($this->multiselect) {
            $name .= '[]';
            $multiselect = 'multiple="multiple" ';
        }
        $output = '<div class="userselector" id="' . $this->name . '_wrapper">' . "\n" .
                '<select name="' . $name . '" id="' . $this->name . '" ' .
                $multiselect . 'size="' . $this->rows . '" class="form-control no-overflow">' . "\n";

        // Populate the select.
        $output .= $this->output_options($groupedusers, $search);

        // Output the search controls.
        $output .= "</select>\n<div class=\"form-inline\">\n";
        $output .= '<input type="text" name="' . $this->name . '_searchtext" id="' .
                $this->name . '_searchtext" size="15" value="' . s($search) . '" class="form-control"/>';
        $output .= '<input type="submit" name="' . $this->name . '_searchbutton" id="' .
                $this->name . '_searchbutton" value="' . $this->search_button_caption() . '" class="btn btn-secondary"/>';
        $output .= '<input type="submit" name="' . $this->name . '_clearbutton" id="' .
                $this->name . '_clearbutton" value="' . get_string('clear') . '" class="btn btn-secondary"/>';

        // And the search options.
        $optionsoutput = false;
        if (true) {
            // $output .= print_collapsible_region_start('', 'userselector_options',
            //     get_string('searchoptions'), 'userselector_optionscollapsed', true, true);
            // $output .= $this->option_checkbox('preserveselected', $this->preserveselected,
            //     get_string('collegeselectorpreserveselected', 'local_program'));
            // $output .= $this->option_checkbox('autoselectunique', $this->autoselectunique,
            //     get_string('collegeselectorautoselectunique', 'local_program'));
            // $output .= $this->option_checkbox('searchanywhere', $this->searchanywhere,
            //     get_string('collegeselectorsearchanywhere', 'local_program'));
            // $output .= print_collapsible_region_end(true);

            $PAGE->requires->js_init_call('M.local_program.init_user_selector_options_tracker', array(), false, $this->jsmodule1);
            // user_selector_base::$searchoptionsoutput = true;
        }
        $output .= "</div>\n</div>\n\n";

        // Initialise the ajax functionality.
        $output .= $this->initialise_javascript($search);

        // Return or output it.
        if ($return) {
            return $output;
        } else {
            echo $output;
        }
    }

    protected function initialise_javascript($search) {
        global $USER, $PAGE, $OUTPUT;
        $output = '';

        // Put the options into the session, to allow search.php to respond to the ajax requests.
        $options = $this->get_options();
        $hash = md5(serialize($options));
        $USER->userselectors[$hash] = $options;
        // Initialise the selector.
        $PAGE->requires->js_init_call(
            'M.local_program.init_user_selector',
            array($this->name, $hash, $this->extrafields, $search),
            false,
            $this->jsmodule1
        );
        return $output;
    }

    private function option_checkbox($name, $on, $label) {
        if ($on) {
            $checked = ' checked="checked"';
        } else {
            $checked = '';
        }
        $name = 'userselector_' . $name;
        // For the benefit of brain-dead IE, the id must be different from the name of the hidden form field above.
        // It seems that document.getElementById('frog') in IE will return and element with name="frog".
        $output = '<div class="form-check"><input type="hidden" name="' . $name . '" value="0" />' .
                    '<label class="form-check-label" for="' . $name . 'id">' .
                        '<input class="form-check-input" type="checkbox" id="' . $name . 'id" name="' . $name .
                            '" value="1"' . $checked . ' /> ' . $label .
                    "</label>
                   </div>\n";
        user_preference_allow_ajax_update($name, PARAM_BOOL);
        return $output;
    }
    protected function output_options($groupedusers, $search) {
        $output = '';

        // Ensure that the list of previously selected users is up to date.
        $this->get_selected_users();

        // If $groupedusers is empty, make a 'no matching users' group. If there is
        // only one selected user, set a flag to select them if that option is turned on.
        $select = false;
        if (empty($groupedusers)) {
            if (!empty($search)) {
                $groupedusers = array(get_string('nomatchingcolleges','local_program', $search) => array());
            } else {
                $groupedusers = array(get_string('none') => array());
            }
        } else if ($this->autoselectunique && count($groupedusers) == 1 &&
                count(reset($groupedusers)) == 1) {
            $select = true;
            if (!$this->multiselect) {
                $this->selected = array();
            }
        }

        // Output each optgroup.
        foreach ($groupedusers as $groupname => $users) {
            $output .= $this->output_optgroup($groupname, $users, $select);
        }

        // If there were previously selected users who do not match the search, show them too.
        if ($this->preserveselected && !empty($this->selected)) {
            $output .= $this->output_optgroup(get_string('previouslyselectedcolleges', 'local_program', $search), $this->selected, true);
        }

        // This method trashes $this->selected, so clear the cache so it is rebuilt before anyone tried to use it again.
        $this->selected = null;

        return $output;
    }
    public function find_users($search) {
        global $DB;
        $params = array();
        $colleges = $DB->record_exists('local_costcenter',array('id'=>$this->uid));
        if (!$colleges) {
            print_error('Colleges not found!');
        }
        $deptid = $DB->get_field('local_program', 'departmentid', array('id'=>$this->pid));
        $collegeids = $DB->get_records_sql_menu("SELECT id as id, id as clgid FROM {local_costcenter} WHERE parentid = $this->uid AND visible = 1 AND univ_dept_status = 1");
        if($deptid){
            $departmentids = $DB->get_records_sql_menu("SELECT id as id, id as deptid FROM {local_costcenter} WHERE id = $deptid AND visible = 1 AND univ_dept_status = 0");
        }
        $temparray = array_merge($collegeids, $departmentids);
        $clg_deptids = implode(',', $temparray);
        $fields      = 'SELECT * ';
        $countfields = 'SELECT COUNT(id)';
        $sql   = " FROM {local_costcenter}
                    WHERE 1 = 1 AND parentid = :uid AND visible = :status"; 
        if($clg_deptids){
            $sql .= " AND id IN ($clg_deptids)";
        }
        $params['uid'] = $this->uid;
        $params['status'] = 1;
        if ($search) {
            $sql .= " AND (fullname LIKE '%$search%' OR shortname LIKE '%$search%')";
        }

        $options = array('contextid' => $this->context->id, 'pid' => $this->pid, 'uid' => $this->uid);
        $local_programs_existing_colleges = new local_programs_existing_colleges('removeselect', $options);
        $enrolledcollegeslist = $local_programs_existing_colleges->find_users('', true);

        if (!empty($enrolledcollegeslist)) {
            $enrolledcolleges = implode(',', $enrolledcollegeslist);
            $sql .= " AND id NOT IN ($enrolledcolleges)";
        }

        $availablecolleges = $DB->get_records_sql($fields . $sql , $params, 0, $this->maxusersperpage);
        if (empty($availablecolleges)) {
            return array();
        }

        if ($search) {
            $groupname = get_string('potcollegesmatching', 'local_program', $search);
        } else {
            $groupname = get_string('potcolleges', 'local_program');
        }
        return array($groupname => $availablecolleges);
    }
}



class local_programs_existing_colleges extends user_selector_base {
    protected $pid;
    protected $uid;
    protected $context;
    /**
     * @param string $name control name
     * @param array $options should have two elements with keys groupid and courseid.
     */
    public function __construct($name, $options) {
        global $CFG;
        $this->jsmodule1 = array(
                'name' => 'local_program',
                'fullpath' => '/local/program/module.js',
                'requires'  => array('node', 'event-custom', 'datasource', 'json', 'moodle-core-notification'),
                'strings' => array(
                    array('previouslyselectedcolleges', 'local_program', '%%SEARCHTERM%%'),
                    array('nomatchingcolleges', 'local_program', '%%SEARCHTERM%%'),
                    array('none', 'moodle')
                ));
        $this->searchanywhere = true;
        if (isset($options['context'])) {
            $this->context = context_system::instance();
        } else {
            $this->context = context_system::instance();
        }
        $options['extrafields'] = array();
        $options['accesscontext'] = context_system::instance();
        parent::__construct($name, $options);
        $this->pid = $options['pid'];
        $this->uid = $options['uid'];
        require_once($CFG->dirroot . '/group/lib.php');
        $this->maxusersperpage = 10;
    }

    protected function get_options() {
        global $CFG;
        $options = parent::get_options();
        $options['file'] = 'local/program/lib.php';
        $options['pid'] = $this->pid;
        $options['uid'] = $this->uid;
        $options['contextid'] = $this->context->id;
        return $options;
    }
    public function find_users($search, $idsreturn = false) {
        global $DB;

        list($wherecondition, $params) = $this->search_sql($search, '');

        $params['pid'] = $this->pid;
        $params['uid'] = $this->uid;
        $params['programid'] = $this->pid;
        $fields = "SELECT s.*, (SELECT COUNT(id) FROM {local_curriculum_users} WHERE programid = lp.id ) AS disabled ";
        $countfields = "SELECT COUNT(DISTINCT s.id) ";

        $sql = " FROM {local_costcenter} s
                 JOIN {local_program} lp ON lp.costcenter = s.id
                WHERE lp.parentid = :pid AND s.parentid = :uid";

        if ($search) {
            $sql .= " AND (s.fullname LIKE '%$search%' OR s.shortname LIKE '%$search%')";
        }

        if ($idsreturn) {
            $contextusers = $DB->get_records_sql_menu('SELECT DISTINCT s.id, s.id as collegeid ' . $sql, $params);
            return $contextusers;
        } else {
            $contextusers = $DB->get_records_sql($fields . $sql , $params, 0, $this->maxusersperpage);
        }

        // No users at all.
        if (empty($contextusers)) {
            return array();
        }

        if ($search) {
            $groupname = get_string('enrolledcollegesmatching', 'local_program', $search);
        } else {
            $groupname = get_string('enrolledcolleges', 'local_program');
        }
        return array($groupname => $contextusers);
    }
    public function display($return = false) {
        global $PAGE;

        // Get the list of requested users.
        $search = optional_param($this->name . '_searchtext', '', PARAM_RAW);
        if (optional_param($this->name . '_clearbutton', false, PARAM_BOOL)) {
            $search = '';
        }
        $groupedusers = $this->find_users($search);

        // Output the select.
        $name = $this->name;
        $multiselect = '';
        if ($this->multiselect) {
            $name .= '[]';
            $multiselect = 'multiple="multiple" ';
        }
        $output = '<div class="userselector" id="' . $this->name . '_wrapper">' . "\n" .
                '<select name="' . $name . '" id="' . $this->name . '" ' .
                $multiselect . 'size="' . $this->rows . '" class="form-control no-overflow">' . "\n";

        // Populate the select.
        $output .= $this->output_options($groupedusers, $search);

        // Output the search controls.
        $output .= "</select>\n<div class=\"form-inline\">\n";
        $output .= '<input type="text" name="' . $this->name . '_searchtext" id="' .
                $this->name . '_searchtext" size="15" value="' . s($search) . '" class="form-control"/>';
        $output .= '<input type="submit" name="' . $this->name . '_searchbutton" id="' .
                $this->name . '_searchbutton" value="' . $this->search_button_caption() . '" class="btn btn-secondary"/>';
        $output .= '<input type="submit" name="' . $this->name . '_clearbutton" id="' .
                $this->name . '_clearbutton" value="' . get_string('clear') . '" class="btn btn-secondary"/>';

        // And the search options.
        $optionsoutput = false;
        if (true) {
            $output .= print_collapsible_region_start('', 'userselector_options',
                get_string('searchoptions'), 'userselector_optionscollapsed', true, true);
            $output .= $this->option_checkbox('preserveselected', $this->preserveselected,
                get_string('collegeselectorpreserveselected', 'local_program'));
            $output .= $this->option_checkbox('autoselectunique', $this->autoselectunique,
                get_string('collegeselectorautoselectunique', 'local_program'));
            $output .= $this->option_checkbox('searchanywhere', $this->searchanywhere,
                get_string('collegeselectorsearchanywhere', 'local_program'));
            $output .= print_collapsible_region_end(true);
            $PAGE->requires->js_init_call('M.local_program.init_user_selector_options_tracker', array(), false, $this->jsmodule1);
            // user_selector_base::$searchoptionsoutput = true;
        }
        $output .= "</div>\n</div>\n\n";

        // Initialise the ajax functionality.
        $output .= $this->initialise_javascript($search);

        // Return or output it.
        if ($return) {
            return $output;
        } else {
            echo $output;
        }
    }
    protected function initialise_javascript($search) {
        global $USER, $PAGE, $OUTPUT;
        $output = '';

        // Put the options into the session, to allow search.php to respond to the ajax requests.
        $options = $this->get_options();
        $hash = md5(serialize($options));
        $USER->userselectors[$hash] = $options;
        // Initialise the selector.
        $PAGE->requires->js_init_call(
            'M.local_program.init_user_selector',
            array($this->name, $hash, $this->extrafields, $search),
            false,
            $this->jsmodule1
        );
        return $output;
    }

    private function option_checkbox($name, $on, $label) {
        if ($on) {
            $checked = ' checked="checked"';
        } else {
            $checked = '';
        }
        $name = 'userselector_' . $name;
        // For the benefit of brain-dead IE, the id must be different from the name of the hidden form field above.
        // It seems that document.getElementById('frog') in IE will return and element with name="frog".
        $output = '<div class="form-check"><input type="hidden" name="' . $name . '" value="0" />' .
                    '<label class="form-check-label" for="' . $name . 'id">' .
                        '<input class="form-check-input" type="checkbox" id="' . $name . 'id" name="' . $name .
                            '" value="1"' . $checked . ' /> ' . $label .
                    "</label>
                   </div>\n";
        user_preference_allow_ajax_update($name, PARAM_BOOL);
        return $output;
    }
    protected function output_options($groupedusers, $search) {
        $output = '';

        // Ensure that the list of previously selected users is up to date.
        $this->get_selected_users();

        // If $groupedusers is empty, make a 'no matching users' group. If there is
        // only one selected user, set a flag to select them if that option is turned on.
        $select = false;
        if (empty($groupedusers)) {
            if (!empty($search)) {
                $groupedusers = array(get_string('nomatchingcolleges','local_program', $search) => array());
            } else {
                $groupedusers = array(get_string('none') => array());
            }
        } else if ($this->autoselectunique && count($groupedusers) == 1 &&
                count(reset($groupedusers)) == 1) {
            $select = true;
            if (!$this->multiselect) {
                $this->selected = array();
            }
        }

        // Output each optgroup.
        foreach ($groupedusers as $groupname => $users) {
            $output .= $this->output_optgroup($groupname, $users, $select);
        }

        // If there were previously selected users who do not match the search, show them too.
        if ($this->preserveselected && !empty($this->selected)) {
            $output .= $this->output_optgroup(get_string('previouslyselectedcolleges', 'local_program', $search), $this->selected, true);
        }

        // This method trashes $this->selected, so clear the cache so it is rebuilt before anyone tried to use it again.
        $this->selected = null;

        return $output;
    }
    public function output_user($user) {
        $out = $user->fullname.' ('.$user->shortname.')';
        return $out;
    }
    protected function this_con_group_name($search, $numusers) {
        if ($this->context->contextlevel == CONTEXT_SYSTEM) {
            // Special case in the System context.
            if ($search) {
                return get_string('affliatedmatching', 'local_program', $search);
            } else {
                return get_string('affliated', 'local_program');
            }
        }
        $contexttype = context_helper::get_level_name($this->context->contextlevel);
        if ($search) {
            $a = new stdClass;
            $a->search = $search;
            $a->contexttype = $contexttype;
            if ($numusers) {
                return get_string('collegesinthisxmatching', 'local_program', $a);
            } else {
                return get_string('noneinthisxmatching', 'local_program', $a);
            }
        } else {
            if ($numusers) {
                return get_string('collegesinthisx', 'local_program', $contexttype);
            } else {
                return get_string('noneinthisx', 'local_program', $contexttype);
            }
        }
    }

    protected function parent_con_group_name($search, $contextid) {
        $context = context::instance_by_id($contextid);
        $contextname = $context->get_context_name(true, true);
        if ($search) {
            $a = new stdClass;
            $a->contextname = $contextname;
            $a->search = $search;
            return get_string('collegesfrommatching', 'local_program', $a);
        } else {
            return get_string('collegesfrom', 'local_program', $contextname);
        }
    }

}
function local_program_output_fragment_curriculum_manageyear_form($args) {
    global $CFG, $PAGE, $DB;
    $args = (object) $args;
    $context = $args->context;
    $return = '';
    $renderer = $PAGE->get_renderer('local_program');
    $formdata = [];
    if (!empty($args->jsonformdata)) {
        $serialiseddata = json_decode($args->jsonformdata);
        parse_str($serialiseddata, $formdata);
    }
    $formdata['id'] = $args->id;
    $formdata['curriculumid'] = $args->curriculumid;
    $formdata['programid'] = $args->programid;

    $mform = new program_manageyear_form(null, array('id' => $args->id,
        'curriculumid' => $args->curriculumid, 'programid' => $args->programid, 'form_status' => $args->form_status), 'post', '', null,
        true, $formdata);
    $bcsemester = new stdClass();
    $bcsemester->curriculumid = $args->curriculumid;
    $bcsemester->programid = $args->programid;
    if ($args->id > 0) {
        $bcsemester = $DB->get_record('local_program_cc_years', array('id' => $args->id));
    }

    $bcsemester->form_status = $args->form_status;
    $mform->set_data($bcsemester);

    if (!empty((array) $serialiseddata)) {
        // If we were passed non-empty form data we want the mform to call validation functions and show errors.
        $mform->is_validated();
    }
    $formheaders = array_keys($mform->formstatus);
    $nextform = array_key_exists($args->form_status, $formheaders);
    if ($nextform === false) {
        return false;
    }
    ob_start();
    $formstatus = array();
    foreach (array_values($mform->formstatus) as $k => $mformstatus) {
        $activeclass = $k == $args->form_status ? 'active' : '';
        $formstatus[] = array('name' => $mformstatus, 'activeclass' => $activeclass);
    }
    $formstatusview = new \local_program\output\form_status($formstatus);
    $return .= $renderer->render($formstatusview);
    $mform->display();
    $return .= ob_get_contents();
    ob_end_clean();

    return $return;
}

class program_manageyear_form extends moodleform {

    public function definition() {
        global $CFG, $DB, $USER;
        $querieslib = new querylib();
        $mform = &$this->_form;
        $id = $this->_customdata['id'];
        $programid = $this->_customdata['programid'];
        $curriculumid = $this->_customdata['curriculumid'];
        $context = context_system::instance();

        //$mform->addElement('header', 'general', get_string('addcourses', 'local_program'));

        $mform->addElement('hidden', 'id', $id);
        $mform->setType('id', PARAM_INT);

        $mform->addElement('hidden', 'curriculumid', $curriculumid);
        $mform->setType('curriculumid', PARAM_INT);

        $mform->addElement('hidden', 'programid', $programid);
        $mform->setType('programid', PARAM_INT);

        $mform->addElement('text', 'year', get_string('year', 'local_program'));
        $mform->addRule('year', null, 'required', null, 'client');
        $mform->setType('year', PARAM_NOTAGS);

        $mform->addElement('text', 'cost', get_string('cost', 'local_program'));
        $mform->addRule('cost', null, 'required', null, 'client');
        $mform->addRule('cost', null, 'numeric', null, 'client');
        $mform->addRule('cost', null, 'nonzero', null, 'client');
        $mform->setType('cost', PARAM_FLOAT);

        $mform->disable_form_change_checker();
    }

    public function validation($data, $files) {
        $errors = array();
        global $DB, $CFG;
        $errors = parent::validation($data, $files);

        $cost = $data['cost'];

        if(!is_numeric($cost)){
            $errors['cost'] = get_string('costshouldinteger', 'local_program');
        } else if($cost <= 0){
            $errors['cost'] = get_string('costshouldpositive', 'local_program');
        }

        return $errors;
    }

}
/**
 * Serve the new group form as a fragment.
 *
 * @param array $args List of named arguments for the fragment loader.
 * @return string
 */
function local_program_output_fragment_curriculum_managefaculty_form($args) {
    global $CFG, $PAGE, $DB;
    $args = (object) $args;
    $context = $args->context;
    $return = '';
    $renderer = $PAGE->get_renderer('local_program');
    $formdata = [];
    if (!empty($args->jsonformdata)) {
        $serialiseddata = json_decode($args->jsonformdata);
        parse_str($serialiseddata, $formdata);
    }
    $formdata['yearid'] = $args->yearid;
    $formdata['programid'] = $args->programid;
    $formdata['curriculumid'] = $args->curriculumid;
    $formdata['semesterid'] = $args->semesterid;
    $formdata['courseid'] = $args->courseid;


    $mform = new managefaculty_form(null, array('programid' => $args->programid, 'curriculumid' => $args->curriculumid,'yearid' => $args->yearid, 'semesterid' => $args->semesterid, 'courseid' => $args->courseid,
        'form_status' => $args->form_status), 'post', '', null, true, $formdata);
    $curriculumdata = new stdClass();
    $curriculumdata->id = $args->id;
    $curriculumdata->programid = $args->programid;
    $curriculumdata->curriculumid = $args->curriculumid;
    $curriculumdata->form_status = $args->form_status;
    $mform->set_data($curriculumdata);

    if (!empty((array) $serialiseddata)) {
        // If we were passed non-empty form data we want the mform to call validation functions and show errors.
        $mform->is_validated();
    }
    $formheaders = array_keys($mform->formstatus);
    $nextform = array_key_exists($args->form_status, $formheaders);
    if ($nextform === false) {
        return false;
    }
    ob_start();
    $formstatus = array();
    foreach (array_values($mform->formstatus) as $k => $mformstatus) {
        $activeclass = $k == $args->form_status ? 'active' : '';
        $formstatus[] = array('name' => $mformstatus, 'activeclass' => $activeclass);
    }
    $formstatusview = new \local_program\output\form_status($formstatus);
    $return .= $renderer->render($formstatusview);
    $mform->display();
    $return .= ob_get_contents();
    ob_end_clean();

    return $return;
}

function local_program_output_fragment_curriculum_manageclassroom_form($args) {
    global $CFG, $PAGE, $DB;
    $args = (object) $args;
    $context = $args->context;
    $return = '';
    $renderer = $PAGE->get_renderer('local_program');
    $formdata = [];
    if (!empty($args->jsonformdata)) {
        $serialiseddata = json_decode($args->jsonformdata);
        parse_str($serialiseddata, $formdata);
    }
    $formdata['id'] = $args->id;
    $formdata['yearid'] = $args->yearid;
    $formdata['programid'] = $args->programid;
    $formdata['curriculumid'] = $args->curriculumid;
    $formdata['semesterid'] = $args->semesterid;
    $formdata['ccses_action'] = $args->ccses_action;
    // $formdata['courseid'] = $args->courseid;
    if($args->id > 0){
        $classroom_type = $DB->get_field('local_cc_semester_classrooms', 'classroom_type', array('id' => $args->id));
        $sessiondata = $DB->get_record_sql('SELECT id,trainerid,roomid,instituteid,institute_type,maxcapacity,mincapacity,timestart,timefinish,dailysessionstarttime,dailysessionendtime FROM {local_cc_course_sessions} WHERE bclcid = '.$args->id);
        $sessionenddate = $DB->get_record_sql('SELECT id,timefinish FROM {local_cc_course_sessions} WHERE bclcid = '.$args->id.' ORDER BY timefinish desc LIMIT 1');
        $attendancemappedcount = $DB->count_records_sql("SELECT count(id) 
                                                                     FROM {local_cc_session_signups} 
                                                                    WHERE curriculumid = $args->curriculumid 
                                                                      AND semesterid = $args->semesterid 
                                                                      AND yearid = $args->yearid
                                                                      AND programid = $args->programid
                                                                      AND bclcid = $args->id
                                                                      AND completion_status != 0");
    }

    $mform = new manageclassroom_form(null, array('id' => $args->id,'programid' => $args->programid, 'curriculumid' => $args->curriculumid,'yearid' => $args->yearid, 'semesterid' => $args->semesterid,'instituteid' => $sessiondata->instituteid,'room' => $sessiondata->roomid,'institute_type' => $sessiondata->institute_type,'classroom_type'=>$classroom_type,'trainerid'=>$sessiondata->trainerid,'timestart' => $sessiondata->timestart,'timefinish' => $sessionenddate->timefinish, 'attendancemapped' => $attendancemappedcount,'ccses_action' => 'class_sessions',/*'courseid' => $args->courseid,*/
        'form_status' => $args->form_status), 'post', '', null, true, $formdata);
    $curriculumdata = new stdClass();
    $curriculumdata->id = $args->id;
    if ($args->id > 0) {
        $curriculumdata = $DB->get_record('local_cc_semester_classrooms', array('id' => $args->id));
        // print_object($curriculumdata);exit;
        if($curriculumdata->classroom_type == 1){
            $room = $DB->get_field('local_location_room','name',array('id' => $sessiondata->roomid));
            $location = $DB->get_field('local_location_institutes','fullname',array('id' => $sessiondata->instituteid));
            $trainername = $DB->get_field('user','firstname',array('id' => $sessiondata->trainerid));
            $curriculumdata->instituteid = $sessiondata->instituteid;
            $curriculumdata->roomid = $sessiondata->roomid;   
            $curriculumdata->room = $sessiondata->roomid;   
            $curriculumdata->trainerid = $sessiondata->trainerid;
            // $curriculumdata->maxcapacity = $sessiondata->maxcapacity;
            // $curriculumdata->mincapacity = $sessiondata->mincapacity;
            $curriculumdata->nomination_startdate = $sessiondata->timestart;
            $curriculumdata->nomination_enddate = $sessionenddate->timefinish;
            $curriculumdata->institute_type = $sessiondata->institute_type;
            $sessionstarttime = explode(':', $sessiondata->dailysessionstarttime);
            $sessionendtime = explode(':', $sessiondata->dailysessionendtime);
            $dailysessionsttime = array('dailystarttimehours' => $sessionstarttime[0], 'dailystarttimemins' => ($sessionstarttime[1]) ? $sessionstarttime[1] : '00');
            $dailysessionendtime = array('dailyendtimehours' => $sessionendtime[0], 'dailyendtimemins' => ($sessionendtime[1]) ? $sessionendtime[1] : '00');
            $curriculumdata->dailysessionstarttime = $dailysessionsttime;
            $curriculumdata->dailysessionendtime = $dailysessionendtime;
        }
    }
    $curriculumdata->programid = $args->programid;
    $curriculumdata->curriculumid = $args->curriculumid;
    $curriculumdata->semesterid = $args->semesterid;
    $curriculumdata->yearid = $args->yearid;
    $curriculumdata->ccses_action = $args->ccses_action;
    $curriculumdata->form_status = $args->form_status;
    $curriculumdata->shortname = $curriculumdata->shortname;
    $mform->set_data($curriculumdata);

    if (!empty((array) $serialiseddata)) {
        // If we were passed non-empty form data we want the mform to call validation functions and show errors.
        $mform->is_validated();
    }
    $formheaders = array_keys($mform->formstatus);
    $nextform = array_key_exists($args->form_status, $formheaders);
    if ($nextform === false) {
        return false;
    }
    ob_start();
    $formstatus = array();
    foreach (array_values($mform->formstatus) as $k => $mformstatus) {
        $activeclass = $k == $args->form_status ? 'active' : '';
        $formstatus[] = array('name' => $mformstatus, 'activeclass' => $activeclass);
    }
    $formstatusview = new \local_program\output\form_status($formstatus);
    $return .= $renderer->render($formstatusview);
    $mform->display();
    $return .= ob_get_contents();
    ob_end_clean();

    return $return;
}

function local_program_output_fragment_classroom_completion_form($args) {
    global $CFG, $DB;
    $args = (object) $args;
    $context = $args->context;
    $return = '';
    $formdata = [];
    if (!empty($args->jsonformdata)) {
        $serialiseddata = json_decode($args->jsonformdata);
        parse_str($serialiseddata, $formdata);
    }
    $formdata['id'] = $args->id;
    $formdata['ccid'] = $args->ccid;
    $formdata['semesterid'] = $args->semesterid;
    $formdata['bclcid'] = $args->bclcid;
    $formdata['programid'] = $args->programid;
    $formdata['yearid'] = $args->yearid;
    $formdata['courseid'] = $args->courseid;
    $formdata['ccses_action'] = $args->ccses_action;
    // print_object($formdata);exit;  
    /*$mform = new \local_program\form\classroom_completion_form(null, array('id' => $args->id,
        'cid' => $args->cid, 'form_status' => $args->form_status), 'post', '',
            null, true, $formdata);*/
    $mform = new \local_program\form\classroom_completion_form(null, array('id' => $args->id, 'ccid' => $args->ccid, 'semesterid' => $args->semesterid, 'bclcid' => $args->bclcid, 'programid' => $args->programid, 'yearid' => $args->yearid, 'courseid' => $args->courseid,'ccses_action' => $args->ccses_action, 'form_status' => $args->form_status), 'post', '', null, true, $formdata);
    if ($args->id > 0) {
        $classroom_completiondata = $DB->get_record('local_classroom_completion', array('id' => $args->id));
        $classroom_completiondata->form_status = $args->form_status;
       
        if($classroom_completiondata->sessionids=="NULL"){
            $classroom_completiondata->sessionids=null;
        }
        if($classroom_completiondata->courseids=="NULL"){
            $classroom_completiondata->courseids=null;
        }

        $mform->set_data($classroom_completiondata);
    }

    if (!empty((array) $serialiseddata)) {
        // If we were passed non-empty form data we want the mform to call validation functions and show errors.
        $mform->is_validated();
    }
    $formheaders = array_keys($mform->formstatus);
    $nextform = array_key_exists($args->form_status, $formheaders);
    if ($nextform === false) {
        return false;
    }
    ob_start();
    $mform->display();
    $return .= ob_get_contents();
    ob_end_clean();

    return $return;
}

class managefaculty_form extends moodleform {

    public function definition() {
        global $CFG, $DB, $USER;
        $querieslib = new querylib();
        $mform = &$this->_form;
        $id = $this->_customdata['id'];
        $programid = $this->_customdata['programid'];
        $curriculumid = $this->_customdata['curriculumid'];
        $yearid = $this->_customdata['yearid'];
        $semesterid = $this->_customdata['semesterid'];
        $courseid = $this->_customdata['courseid'];
        $context = context_system::instance();

        $mform->addElement('hidden', 'programid', $programid);
        $mform->setType('programid', PARAM_INT);

        $mform->addElement('hidden', 'curriculumid', $curriculumid);
        $mform->setType('curriculumid', PARAM_INT);

        $mform->addElement('hidden', 'yearid', $yearid);
        $mform->setType('yearid', PARAM_INT);

        $mform->addElement('hidden', 'semesterid', $semesterid);
        $mform->setType('semesterid', PARAM_INT);

        $mform->addElement('hidden', 'courseid', $courseid);
        $mform->setType('courseid', PARAM_INT);

        // $programid = $DB->get_field_sql('SELECT p.costcenter
        //                                       FROM {local_program} p
        //                                       JOIN {local_curriculum} cc ON cc.program = p.id
        //                                     WHERE cc.id = :curriculumid ',
        //                                     array('curriculumid' => $curriculumid));

        $faculties = array();
        $faculty = $this->_ajaxformdata['faculty'];
        if (!empty($faculty) && is_array($faculty)) {
            $faculty = implode(',', $faculty);
            $facultysql = "SELECT u.id, CONCAT(u.firstname, ' ', u.lastname) AS fullname
                             FROM {user} AS u
                            WHERE u.id IN ($faculty) AND u.id > 2 AND u.confirmed = 1";
            $faculties = $DB->get_records_sql_menu($facultysql);
        }
        $options = array(
            'ajax' => 'local_program/form-options-selector',
            'multiple' => true,
            'data-action' => 'program_course_faculty_selector',
            'data-contextid' => $context->id,
            'data-options' => json_encode(array('courseid' => $courseid, 'programid' => $programid, 'yearid' => $yearid, 'semesterid' => $semesterid, 'curriculumid' => $curriculumid))
        );
        $mform->addElement('autocomplete', 'faculty', get_string('faculty', 'local_program'), $faculties, $options);
        $mform->addRule('faculty', get_string('missingfaculty','local_program'), 'required', null, 'client');

        $mform->disable_form_change_checker();
    }
    public function validation($data, $files) {
        $errors = array();
        global $DB, $CFG;
        $errors = parent::validation($data, $files);
        // <Mallikarjun> - ODL-721 Warning message is not displaying -- starts
        $faculty = $data['faculty'];
         if(empty($faculty)){
                    $errors['faculty'] = get_string('missingfaculty', 'local_program');
                }
         // <Mallikarjun> - ODL-721 Warning message is not displaying -- ends

        $instance = $DB->get_record('enrol', array('courseid' => $data['courseid'], 'enrol' => 'program'));

        if (empty($instance) || $instance->status != ENROL_INSTANCE_ENABLED) {
            $errors['faculty'] = get_string('canntenrol', 'enrol_program');
        }
        
        return $errors;
    }
}

class manageclassroom_form extends moodleform {

    public function definition() {
        global $CFG, $DB, $USER;
        // $querieslib = new querylib();
        $mform = &$this->_form;
        // print_object($this->_form);
        $id = $this->_customdata['id'];
        $programid = $this->_customdata['programid'];
        $curriculumid = $this->_customdata['curriculumid'];
        $yearid = $this->_customdata['yearid'];
        $semesterid = $this->_customdata['semesterid'];
        $ccses_action = $this->_customdata['ccses_action'];
        $instituteid = $this->_customdata['instituteid'];
        $institute_type = $this->_customdata['institute_type'];
        $existingdailystartdate = $this->_customdata['timestart'];
        $existingdailyenddate = $this->_customdata['timefinish'];
        $attendancemapped = $this->_customdata['attendancemapped'];
        $roomid = $this->_customdata['room'];
        $existingclassroomtype = $this->_customdata['classroom_type'];
        // $courseid = $this->_customdata['courseid'];
        $context = context_system::instance();
        if($id > 0){
            $this->_form->_attributes['id'] ='editclassroom'.$id;
        }else{
            $this->_form->_attributes['id'] ='createclassroom';
        }

        $mform->addElement('hidden', 'id', $id);
        $mform->setType('id', PARAM_INT);

        $mform->addElement('hidden', 'programid', $programid);
        $mform->setType('programid', PARAM_INT);

        $mform->addElement('hidden', 'curriculumid', $curriculumid);
        $mform->setType('curriculumid', PARAM_INT);

        $mform->addElement('hidden', 'yearid', $yearid);
        $mform->setType('yearid', PARAM_INT);

        $mform->addElement('hidden', 'semesterid', $semesterid);
        $mform->setType('semesterid', PARAM_INT);

        $mform->addElement('hidden', 'ccses_action', $ccses_action);
        $mform->setType('ccses_action', PARAM_RAW);

        $mform->addElement('hidden', 'existingdailystartdate', $existingdailystartdate);
        $mform->setType('existingdailystartdate', PARAM_INT);

        $mform->addElement('hidden', 'existingdailyenddate', $existingdailyenddate);
        $mform->setType('existingdailyenddate', PARAM_INT);

        $mform->addElement('hidden', 'attendancemapped', $attendancemapped);
        $mform->setType('attendancemapped', PARAM_INT);

        $mform->addElement('hidden', 'existingclassroomtype', $existingclassroomtype);
        $mform->setType('existingclassroomtype', PARAM_INT);

        if($id > 0){
            $classroomdata = $DB->get_record('local_cc_semester_classrooms', array('id' => $id));
            $sessiondata = $DB->get_record_sql('SELECT id,trainerid,roomid,instituteid,institute_type,maxcapacity,mincapacity,timestart,timefinish,dailysessionstarttime,dailysessionendtime FROM {local_cc_course_sessions} WHERE bclcid = '.$id);
            $sessionenddate = $DB->get_record_sql('SELECT id,timefinish FROM {local_cc_course_sessions} WHERE bclcid = '.$id.' ORDER BY timefinish desc LIMIT 1');
        }
        
        $mform->addElement('text', 'classname', get_string('classroom_name', 'local_program'), array());
        $mform->addRule('classname', get_string('classname_error','local_program'), 'required', null, 'client');
        $mform->addElement('text', 'shortname', get_string('shortname', 'local_program'), array());
        $mform->addRule('shortname', get_string('shortname_error','local_program'), 'required', null, 'client');

        /*$mform->addElement('text', 'requiredsessions', get_string('requiredsessions', 'local_program'), array());
        $mform->addRule('requiredsessions', null, 'required', null, 'client');
        $mform->addRule('requiredsessions', null, 'numeric', null, 'client');
        $mform->addRule('requiredsessions', null, 'nonzero', null, 'client');
        $mform->setType('requiredsessions', PARAM_FLOAT);*/
        if($attendancemapped > 0){
            $allowmultisession = array();
            $allowmultisession[] = $mform->createElement('radio', 'classroom_type', '', get_string('fixed', 'local_program'), 1, 'disabled');
            $allowmultisession[] = $mform->createElement('radio', 'classroom_type', '', get_string('custom', 'local_program'), 0, 'disabled');
            $allowmultisession[] = $mform->createElement('static', 'staticclroomtype', '');
            $mform->setDefault('staticclroomtype','<br><div class="usermessage">You cannot change Classroom type, Since attendance already mapped to sessions created under this Classroom </div>');
            $mform->addGroup($allowmultisession, 'staticclroomtype', get_string('classroom_type', 'local_program'), array('&nbsp;&nbsp;'), false);
            $mform->addRule('staticclroomtype', null, 'required');
            $mform->addElement('hidden', 'classroom_type', $classroomdata->classroom_type);
            $mform->setType('classroom_type', PARAM_INT);
        }else{
            $allowmultisession = array();
            $allowmultisession[] = $mform->createElement('radio', 'classroom_type', '', get_string('fixed', 'local_program'), 1, $attributes);
            $allowmultisession[] = $mform->createElement('radio', 'classroom_type', '', get_string('custom', 'local_program'), 0, $attributes);
            $mform->addGroup($allowmultisession, 'radioar_classtype', get_string('classroom_type', 'local_program'), array('&nbsp;&nbsp;'), false);
            $mform->addRule('radioar_classtype', null, 'required');    
        }

        /*if($attendancemapped > 0 && $classroomdata->classroom_type == 1){
            if($sessiondata->institute_type == 1){
                $instituetype = get_string('internal', 'local_program');
            }else{
                $instituetype = get_string('external', 'local_program');
            }
            $mform->addElement('static', 'staticinstitute_type', get_string('bc_location_type', 'local_program'));
            $mform->setDefault('staticinstitute_type',$instituetype);
            $mform->addElement('hidden', 'institute_type',$sessiondata->institute_type);
            $mform->setType('institute_type', PARAM_INT);
        }else{*/
            $institutetypes = array();
            $institutetypes[] = $mform->createElement('radio', 'institute_type', '',
                    get_string('internal', 'local_program'), 1, $attributes);
            $institutetypes[] = $mform->createElement('radio', 'institute_type', '',
                    get_string('external', 'local_program'), 2, $attributes);
            $mform->addGroup($institutetypes, 'radioar', get_string('customreq_location_type',
                    'local_program'), array(' '), false);
            //$mform->addRule('radioar', get_string('missinglocationteype','local_program'), 'required', null, 'client');
            $mform->hideIf('radioar', 'classroom_type', 'eq', 0);    
        // }
        
        //----Changes by Yamini---//
        $nulllocationtype = array(null=>get_string('selectlocation','local_program'));
        if($id > 0 && $classroomdata->classroom_type == 1){
            $institute_type = $this->_ajaxformdata['institute_type'] ? $this->_ajaxformdata['institute_type'] : $institute_type;
            $locations = find_locations_basedon_type($institute_type,$programid);     
            foreach($locations as $location){
                $curriculumlocations[$location->id] = $location->fullname;
            }     
        }else{
            $institute_type = $this->_ajaxformdata['institute_type'];
            if(!empty($institute_type)){
                $locations = find_locations_basedon_type($institute_type,$programid); 
                foreach($locations as $location){
                    $curriculumlocations[$location->id] = $location->fullname;
                }
            }
        }
        if(!empty($curriculumlocations)){
            $curriculumlocations = $nulllocationtype+$curriculumlocations;
        }else{
            $curriculumlocations = $nulllocationtype;
        }
        $mform->addElement('select', 'instituteid', get_string('curriculum_locations','local_program'), $curriculumlocations);
     
        $mform->hideIf('instituteid', 'classroom_type', 'eq', 0);

        /*$locationrooms =  array(null => get_string('select_rooms', 'local_program'));
        $roomid = $this->_ajaxformdata['instituteid'];
        if (!empty($roomid)) {
            $roomid = $roomid;
        }else if ($sid > 0) {
            $roomid = $DB->get_field('local_cc_course_sessions', 'roomid', array('id' => $sid));
        }
        if (!empty($roomid)) {
            $locationrooms = $DB->get_records_menu('local_location_room',
                array('id' => $roomid), 'id', 'id, name');
        }*/
        $roomslistnull = array(null => get_string('selectroom', 'local_program'));
        if($id > 0 && $classroomdata->classroom_type == 1){
            $rooms = find_rooms($instituteid);                             
            foreach($rooms as $room){
                $roomslist[$room->id] = $room->name;
            } 
        }else{
            $location = $this->_ajaxformdata['instituteid'];
            if(!empty($location)){
                $rooms = find_rooms($location);              
                foreach($rooms as $room){
                    $roomslist[$room->id] = $room->name;
                }
            }
        }
        if(!empty($roomslist)){
            $roomslist = $roomslistnull+$roomslist;
        }else{
            $roomslist = $roomslistnull;
        }
            $mform->addElement('select', 'room', get_string('select_rooms','local_program'),$roomslist);
            $mform->disabledIf('room', 'onlinesession', 'checked');
            $mform->hideIf('room', 'classroom_type', 'eq', 0);
            $roomid = $this->_ajaxformdata['room'];
        
        if(!empty($programid)){            
            $parentid = $DB->get_record_sql('SELECT id,costcenter,parentid,departmentid FROM {local_program} WHERE id = '.$programid);
            $costcenter = $DB->get_record_sql('SELECT id,costcenter FROM {local_program} WHERE id = '.$parentid->id);
            $role = $DB->get_field('role','id',array('shortname' => 'faculty'));
            $faculties = $DB->get_records_sql_menu('SELECT id,CONCAT( firstname, " ", lastname ) AS fullname FROM {user} WHERE deleted = 0 AND suspended =0 AND open_role ='.$role.' AND open_costcenterid = '.$costcenter->costcenter.' AND open_departmentid = '.$parentid->departmentid);
        
            $faculty_select = array(null => '--Select Faculty--');       
            $facultylist = $faculty_select+$faculties;     
        }
        $mform->addElement('select', 'trainerid', get_string('trainers','local_program'),$facultylist);
        $mform->hideIf('trainerid', 'classroom_type', 'eq', 0);       
        
        //changes by harish for commenting room capacity fields starts here//
        /*$mform->addElement('text', 'maxcapacity', get_string('maxcapacity', 'local_program'), 'readonly');
        $mform->setType('maxcapacity', PARAM_RAW);
        $mform->hideIf('maxcapacity', 'classroom_type', 'eq', 0);

        $mform->addElement('text', 'mincapacity', get_string('mincapacity', 'local_program'));
        // $mform->addRule('mincapacity', null, 'required', null, 'client');
        $mform->addRule('mincapacity', null, 'numeric', null, 'client');
        $mform->addRule('mincapacity', null, 'nonzero', null, 'client');
        $mform->setType('mincapacity', PARAM_RAW);
        $mform->hideIf('mincapacity', 'classroom_type', 'eq', 0);*///changes by harish for commenting room capacity fields ends here//

        if($id > 0 && $attendancemapped > 0 && $classroomdata->classroom_type == 1){
            $mform->addElement('static', 'staticstdatelabel', get_string('cs_timestarts', 'local_program'));
            $mform->setDefault('staticstdatelabel',date('d-M-Y',$sessiondata->timestart).'<br><div class="usermessage">Attendance already mapped to sessions created under this Classroom</div>');
            $mform->hideIf('staticstdatelabel', 'classroom_type', 'eq', 0);
            $mform->addElement('hidden', 'nomination_startdate', $sessiondata->timestart);
            $mform->setType('nomination_startdate', PARAM_INT);

            $mform->addElement('static', 'staticenddatelabel', get_string('cs_timefinishs', 'local_program'));
            $mform->setDefault('staticenddatelabel',date('d-M-Y',$sessionenddate->timefinish).'<br><div class="usermessage">Attendance already mapped to sessions created under this Classroom</div>');
            $mform->hideIf('staticenddatelabel', 'classroom_type', 'eq', 0);
            $mform->addElement('hidden', 'nomination_enddate', $sessionenddate->timefinish);
            $mform->setType('nomination_enddate', PARAM_INT);
            
            $mform->addElement('static', 'staticdailysessionsttimelabel', get_string('dailysessionstarttimes', 'local_program'));
            $mform->setDefault('staticdailysessionsttimelabel',$sessiondata->dailysessionstarttime.'<br><div class="usermessage">Attendance already mapped to sessions created under this Classroom</div>');
            $mform->hideIf('staticdailysessionsttimelabel', 'classroom_type', 'eq', 0);
            /*$mform->addElement('hidden', 'nomination_enddate', $sessionenddate->timefinish);
            $mform->setType('nomination_enddate', PARAM_INT);*/

            $mform->addElement('static', 'staticdailysessionendtimelabel', get_string('dailysessionendtimes', 'local_program'));
            $mform->setDefault('staticdailysessionendtimelabel',$sessiondata->dailysessionendtime.'<br><div class="usermessage">Attendance already mapped to sessions created under this Classroom</div>');
            $mform->hideIf('staticdailysessionendtimelabel', 'classroom_type', 'eq', 0);
            /*$mform->addElement('hidden', 'nomination_enddate', $sessionenddate->timefinish);
            $mform->setType('nomination_enddate', PARAM_INT);*/
        }else{
            $mform->addElement('date_selector', 'nomination_startdate',
                get_string('cs_timestarts', 'local_program'),
                array('optional' => true));
            // $mform->hideIf('nomination_startdate', 'classroom_type', 'eq', 0);
            //$mform->addRule('nomination_startdate', null, 'required', null, 'client');

            $mform->addElement('date_selector', 'nomination_enddate',
             get_string('cs_timefinishs', 'local_program'),
             array('optional' => true));
            // $mform->hideIf('nomination_enddate', 'classroom_type', 'eq', 0);
            $starttimearray = array();
            $endtimearray = array();
            $hoursattr = array();
            $minsattr = array();
            $hoursattr[null] = get_string('hours');
            for ($hours=0; $hours < 24; $hours++) {
                if($hours < 10){
                    $hoursattr['0'.$hours] = '0'.$hours;
                }else{
                    $hoursattr[$hours] = $hours;
                }
            }
            $minsattr[null] = get_string('minutes');
            for ($mins=0; $mins < 60; $mins++) { 
                if($mins < 10){
                    $minsattr['0'.$mins] = '0'.$mins;
                }else{
                    $minsattr[$mins] = $mins;
                }
            }
            $starttimearray[] = $mform->createElement('select', 'dailystarttimehours', get_string('hours'), $hoursattr);
            $starttimearray[] = $mform->createElement('select', 'dailystarttimemins', get_string('minutes'), $minsattr);
            $mform->addGroup($starttimearray, 'dailysessionstarttime', get_string('dailysessionstarttimes', 'local_program'), array('class' => 'dailysessionstarttime'));
            $mform->hideIf('dailysessionstarttime', 'classroom_type', 'eq', 0);

            $endtimearray[] = $mform->createElement('select', 'dailyendtimehours', get_string('hours'), $hoursattr);
            $endtimearray[] = $mform->createElement('select', 'dailyendtimemins', get_string('minutes'), $minsattr);
            $mform->addGroup($endtimearray, 'dailysessionendtime', get_string('dailysessionendtimes', 'local_program'), array('class' => 'dailysessionendtime'));
            $mform->hideIf('dailysessionendtime', 'classroom_type', 'eq', 0);
        }

        $mform->disable_form_change_checker();
    }
    public function validation($data, $files) {
        $errors = array();
        global $DB, $CFG;
        $errors = parent::validation($data, $files);
        $classname = $data['classname'];
        $shortname = trim($data['shortname']);
        $programid = $data['programid'];
        $curriculumid = $data['curriculumid'];
        $yearid = $data['yearid'];
        $semesterid = $data['semesterid'];
        // $mincapacity = $data['mincapacity'];
        $attendancemapped = $data['attendancemapped'];

        // $requiredsessions = $data['requiredsessions'];
        $compare_scale_clause_sh = $DB->sql_compare_text("shortname")  . ' = ' . $DB->sql_compare_text(":shortname");
        if($data['id'] > 0 ){
            if($shortname){
                $shortname_existsql = "SELECT * FROM {local_cc_semester_classrooms} WHERE id != :id AND programid = :programid AND curriculumid = :curriculumid AND $compare_scale_clause_sh";
                $shortname_exist = $DB->get_record_sql($shortname_existsql, array('shortname' => $shortname, 'id' => $data['id'], 'programid' => $programid, 'curriculumid' => $curriculumid));
                if($shortname_exist){
                    $errors['shortname'] = get_string('shortnamealreadyexists', 'local_program');
                }
            }
        }
        else{
            $shortname_existsql = "SELECT * FROM {local_cc_semester_classrooms} WHERE programid = :programid AND curriculumid = :curriculumid AND $compare_scale_clause_sh";
            $shortname_exist = $DB->get_record_sql($shortname_existsql, array('shortname' => $shortname, 'programid' => $programid, 'curriculumid' => $curriculumid));
        if($shortname_exist){
             $errors['shortname'] = get_string('shortnamealreadyexists', 'local_program');
        }
        }
        if($data['classroom_type'] == 1 && (/*$attendancemapped == null || */$attendancemapped == 0)){
            $timestarthours = $data['dailysessionstarttime']['dailystarttimehours']*60*60 + $data['dailysessionstarttime']['dailystarttimemins']*60;
            $timefinishhours = $data['dailysessionendtime']['dailyendtimehours']*60*60 + $data['dailysessionendtime']['dailyendtimemins']*60;
            if($data['nomination_startdate'] == 0 || $data['nomination_enddate'] == 0){
                if($data['nomination_startdate'] == 0 ){
                    $errors['nomination_startdate'] = get_string('missingdailystarttime', 'local_program');
                }
                if($data['nomination_enddate'] == 0 ){
                    $errors['nomination_enddate'] = get_string('missingdailystarttime', 'local_program');
                }
            }
            if($timestarthours > $timefinishhours){
                $errors['dailysessionstarttime'] = get_string('starttimelessthanendtime', 'local_program');
            }
            if($data['dailysessionstarttime']['dailystarttimehours'] != null && $data['dailysessionendtime']['dailyendtimehours'] != null && $timestarthours == $timefinishhours){
                $errors['dailysessionstarttime'] = get_string('starttimelessthanendtime', 'local_program');
            }
            if($data['dailysessionstarttime']['dailystarttimehours'] == null || $data['dailysessionendtime']['dailyendtimehours'] == null){
                if($data['dailysessionstarttime']['dailystarttimehours'] == null){
                    $errors['dailysessionstarttime'] = get_string('pleaseselectstarttime', 'local_program');
                }
                if($data['dailysessionstarttime']['dailystarttimehours'] == null){
                    $errors['dailysessionendtime'] = get_string('pleaseselectendtime', 'local_program');
                }
            }
            // if(empty($mincapacity)){
            //     $errors['mincapacity'] = get_string('validnumbererror','local_program');
            // }
            if(empty($data['institute_type'])){
                $errors['institute_type'] = get_string('selectbc_location_type', 'local_program');
            }
        }
        //if(!empty($data['nomination_startdate']) || !empty($data['nomination_enddate'])){
            if(empty($data['nomination_startdate'])){
                $errors['nomination_startdate'] = get_string('missingdailystarttime', 'local_program');
            }
            if(empty($data['nomination_enddate'])){
                $errors['nomination_enddate'] = get_string('missingdailyendtime', 'local_program');
            }
        //}
        /*if($data['id'] > 0){
            if ($data['nomination_enddate'] < $data['nomination_startdate']) {
                $errors['nomination_enddate'] = get_string('startdatelessthanenddate', 'local_program');
            }
        }*/
        
        if($data['nomination_startdate'] != null && $data['nomination_enddate'] != null){
            if($data['nomination_startdate'] > $data['nomination_enddate'] /*|| $data['nomination_startdate'] == $data['nomination_enddate']*/){
                $errors['nomination_startdate'] = get_string('startdatelessthanenddate', 'local_program');
            }
        }
        /*if($data['classroom_type'] == 1){
            if ((isset($data['nomination_startdate']) && $data['nomination_startdate'])||
                     (isset($data['nomination_enddate']) && $data['nomination_enddate'])) {
                global $DB;
                $timestart = $data['nomination_startdate'];
                $res = $DB->get_record_sql("SELECT FROM_UNIXTIME(".$timestart.") as nomination_startdate");
                $startdate = $DB->get_record_sql("SELECT FROM_UNIXTIME(".$timestart.") as timestart FROM {local_cc_course_sessions} WHERE timestart = ".$timestart." or timefinish = ".$timestart);
                if($res->nomination_startdate == $startdate->timestart){
                   $errors['nomination_startdate'] = get_string('timestart_error', 'local_program');  
                }
                if($data['nomination_enddate'] < $data['nomination_startdate']) {
                    $errors['nomination_enddate'] = get_string('nomination_error', 'local_program');
                }
            }
        }*/// Commented by Harish on 01/04/2020//
            /*if(!empty($mincapacity)){
                if(preg_match("/[^0-9]/", $mincapacity)){
                    $errors['mincapacity'] = get_string('validnumbererror','local_program');
                }
            }*/// Commenting room capacity fields by Harish
        if($data['classroom_type'] == 1){
            $institute_type = $data['institute_type'];
            $room = $data['room'];
            $instituteid = $data['instituteid'];
            $trainerid = $data['trainerid'];
            if(empty($room)){
                $errors['room'] = get_string('room_error','local_program');
            }
            if(empty($institute_type)){
                $errors['radioar'] = get_string('missinglocationteype','local_program');
            }
            if(empty($instituteid)){
                $errors['instituteid'] = get_string('instituteid_error','local_program');
            }
            if(empty($trainerid)){
                $errors['trainerid'] = get_string('trainerid_error','local_program');
            }
            //  if(empty($instituteid)){
            //     if(empty($institute_type)){
            //         $errors['radioar'] = get_string('institute_type_error','local_program');
            //     }
            //     $errors['radioar'] = get_string('instituteid_error','local_program');
            // }
        }
        return $errors;
    }
}
/**
 * Serve the new group form as a fragment.
 *
 * @param array $args List of named arguments for the fragment loader.
 * @return string
 */
function local_program_output_fragment_curriculum_managestudent_form($args) {
    global $CFG, $PAGE, $DB;
    $args = (object) $args;
    $context = $args->context;
    $return = '';
    $renderer = $PAGE->get_renderer('local_program');
    $formdata = [];
    if (!empty($args->jsonformdata)) {
        $serialiseddata = json_decode($args->jsonformdata);
        parse_str($serialiseddata, $formdata);
    }
    $formdata['yearid'] = $args->yearid;
    $formdata['curriculumid'] = $args->curriculumid;
    $formdata['programid'] = $args->programid;

    $mform = new managestudent_form(null, array('curriculumid' => $args->curriculumid,'yearid' => $args->yearid, 'programid' => $args->programid,
        'form_status' => $args->form_status), 'post', '', null, true, $formdata);
    $curriculumdata = new stdClass();
    $curriculumdata->id = $args->id;
    $curriculumdata->curriculumid = $args->curriculumid;
    $curriculumdata->form_status = $args->form_status;
    $mform->set_data($curriculumdata);

    if (!empty((array) $serialiseddata)) {
        // If we were passed non-empty form data we want the mform to call validation functions and show errors.
        $mform->is_validated();
    }
    $formheaders = array_keys($mform->formstatus);
    $nextform = array_key_exists($args->form_status, $formheaders);
    if ($nextform === false) {
        return false;
    }
    ob_start();
    $formstatus = array();
    foreach (array_values($mform->formstatus) as $k => $mformstatus) {
        $activeclass = $k == $args->form_status ? 'active' : '';
        $formstatus[] = array('name' => $mformstatus, 'activeclass' => $activeclass);
    }
    $formstatusview = new \local_program\output\form_status($formstatus);
    $return .= $renderer->render($formstatusview);
    $mform->display();
    $return .= ob_get_contents();
    ob_end_clean();

    return $return;
}

class managestudent_form extends moodleform {

    public function definition() {
        global $CFG, $DB, $USER;
        $querieslib = new querylib();
        $mform = &$this->_form;
        $programid = $this->_customdata['programid'];
        $curriculumid = $this->_customdata['curriculumid'];
        $yearid = $this->_customdata['yearid'];

        $context = context_system::instance();

        $mform->addElement('hidden', 'programid', $programid);
        $mform->setType('programid', PARAM_INT);

        $mform->addElement('hidden', 'curriculumid', $curriculumid);
        $mform->setType('curriculumid', PARAM_INT);

        $mform->addElement('hidden', 'yearid', $yearid);
        $mform->setType('yearid', PARAM_INT);

        $students = array();
        $student = $this->_ajaxformdata['students'];
        if (!empty($student)&&is_array($student)) {
            $student = implode(',', $student);
            $studentssql = "SELECT u.id, CONCAT(u.firstname, ' ', u.lastname) AS fullname
                             FROM {user} AS u
                            WHERE u.id IN ($student) AND u.id > 2 AND u.confirmed = 1";
            $students = $DB->get_records_sql_menu($studentssql);
        }
        $options = array(
            'ajax' => 'local_program/form-options-selector',
            'multiple' => true,
            'data-action' => 'program_course_student_selector',
            'data-contextid' => $context->id,
            'data-options' => json_encode(array('programid' => $programid, 'curriculumid' => $curriculumid, 'yearid' => $yearid))
        );
        $mform->addElement('autocomplete', 'students', get_string('students', 'local_program'), $students, $options);
        $mform->addRule('students', get_string('missingstudent','local_program'), 'required', null, 'client');

        $mform->disable_form_change_checker();
    }

    public function validation($data, $files) {
        $errors = array();
        global $DB, $CFG;
        $errors = parent::validation($data, $files);
        //<revathi> Warning message is not displaying for students starts
        $students = $data['students'];
        if(empty($students)){
            $errors['students'] = get_string('missingstudent', 'local_program');
        }
        //<revathi> Warning message is not displaying for students ends
        $pluginname = 'program';
        $params = array();
        $semestercoursessql = 'SELECT c.id, c.id as courseid
                                   FROM {course} c
                                   JOIN {local_cc_semester_courses} ccsc ON ccsc.courseid = c.id
                                  WHERE ccsc.yearid = :yearid ';
        $params['yearid'] = $data['yearid'];
        $semestercourses = $DB->get_records_sql($semestercoursessql, $params);
        $enrolmethod = enrol_get_plugin($pluginname);
        foreach ($semestercourses as $semestercourse) {
            $instance = $DB->get_record('enrol', array('courseid' => $semestercourse->courseid, 'enrol' => $pluginname));
            if (empty($instance) || $instance->status != ENROL_INSTANCE_ENABLED) {
                $errors['students'] = get_string('canntenrol', 'enrol_program');
                break;
            }
        }
        return $errors;
    }

}

/**
 * Serve the new group form as a fragment.
 *
 * @param array $args List of named arguments for the fragment loader.
 * @return string
 */
function local_program_output_fragment_curriculum_setyearcost_form($args) {
    global $CFG, $PAGE, $DB;
    $args = (object) $args;
    $context = $args->context;
    $return = '';
    $renderer = $PAGE->get_renderer('local_program');
    $formdata = [];
    if (!empty($args->jsonformdata)) {
        $serialiseddata = json_decode($args->jsonformdata);
        parse_str($serialiseddata, $formdata);
    }

    $formdata['yearid'] = $args->yearid;
    $formdata['programid'] = $args->programid;
    $formdata['curriculumid'] = $args->curriculumid;
    $formdata['cost'] = $formdata['cost'];

    if (!$formdata['cost']) {
        $recordexists = $DB->get_record('local_program_cc_year_cost', array('yearid' => $args->yearid, 'curriculumid' => $args->curriculumid, 'programid' => $args->programid));
        if (!empty($recordexists)) {
            $formdata['cost'] = $recordexists->cost;
        }
    }

    $mform = new yearcost_form(null, array('programid' => $args->programid,
        'curriculumid' => $args->curriculumid,
        'yearid' => $args->yearid, 'cost' => $args->cost,
        'form_status' => $args->form_status), 'post', '', null, true, $formdata);
    $curriculumdata = new stdClass();
    $curriculumdata->id = $args->id;
    $curriculumdata->programid = $args->programid;
    $curriculumdata->curriculumid = $args->curriculumid;
    $curriculumdata->cost = $formdata['cost'];
    $curriculumdata->form_status = $args->form_status;
    $mform->set_data($curriculumdata);

    if (!empty((array) $serialiseddata)) {
        // If we were passed non-empty form data we want the mform to call validation functions and show errors.
        $mform->is_validated();
    }
    $formheaders = array_keys($mform->formstatus);
    $nextform = array_key_exists($args->form_status, $formheaders);
    if ($nextform === false) {
        return false;
    }
    ob_start();
    $formstatus = array();
    foreach (array_values($mform->formstatus) as $k => $mformstatus) {
        $activeclass = $k == $args->form_status ? 'active' : '';
        $formstatus[] = array('name' => $mformstatus, 'activeclass' => $activeclass);
    }
    $formstatusview = new \local_program\output\form_status($formstatus);
    $return .= $renderer->render($formstatusview);
    $mform->display();
    $return .= ob_get_contents();
    ob_end_clean();

    return $return;
}

class yearcost_form extends moodleform {

    public function definition() {
        global $CFG, $DB, $USER;
        $querieslib = new querylib();
        $mform = &$this->_form;
        $programid = $this->_customdata['programid'];
        $curriculumid = $this->_customdata['curriculumid'];
        $yearid = $this->_customdata['yearid'];
        $cost = $this->_customdata['cost'];
        $context = context_system::instance();

        $mform->addElement('hidden', 'programid', $programid);
        $mform->setType('programid', PARAM_INT);

        $mform->addElement('hidden', 'curriculumid', $curriculumid);
        $mform->setType('curriculumid', PARAM_INT);

        $mform->addElement('hidden', 'yearid', $yearid);
        $mform->setType('yearid', PARAM_INT);

        $mform->addElement('text', 'cost', get_string('cost', 'local_program'));
        $mform->addRule('cost', null, 'required', null, 'client');
        $mform->addRule('cost', null, 'numeric', null, 'client');
        $mform->addRule('cost', null, 'nonzero', null, 'client');

        $mform->disable_form_change_checker();
    }
    public function validation($data, $files) {
        $errors = array();
        global $DB, $CFG;
        $errors = parent::validation($data, $files);

        $cost = $data['cost'];

        if(!is_numeric($cost)){
            $errors['cost'] = get_string('costshouldinteger', 'local_program');
        } else if($cost <= 0){
            $errors['cost'] = get_string('costshouldpositive', 'local_program');
        }

        return $errors;
    }
}
function programname_filter($mform){
    global $DB,$USER;
    $type = optional_param('type',1, PARAM_RAW);

    $programslist=array();

    $systemcontext = context_system::instance();
    $sql = "SELECT fullname as id, fullname FROM {local_program} WHERE 1=1 ";

    $grsql=" group by fullname";

    if(is_siteadmin()){

        if($type==1){
            $sql.= " AND costcenter in (SELECT id FROM {local_costcenter} where parentid=0) ";
        }else if ($type==2){
            $sql.= " AND costcenter in (SELECT id FROM {local_costcenter} where parentid >0) ";
        }

       $programslist = $DB->get_records_sql_menu($sql.$grsql);

    }else if(has_capability('local/program:manage_ownorganization',$systemcontext)){


        if($type==1){
             $sql.= " AND costcenter = $USER->open_costcenterid";
        }else if ($type==2){
            $sql.= " AND costcenter in (SELECT id FROM {local_costcenter} where parentid =$USER->open_costcenterid) ";
        }
        $programslist = $DB->get_records_sql_menu($sql.$grsql);

    }else if(has_capability('local/program:manage_owndepartments',$systemcontext)){

        $sql.= " AND costcenter = $USER->open_departmentid";

        $programslist = $DB->get_records_sql_menu($sql.$grsql);
    }else if(has_capability('local/program:trainer_viewprogram',$systemcontext)){

        $mycurriculums = $DB->get_records_menu('local_cc_session_trainers',
        array('trainerid' => $USER->id), 'programid', 'programid, programid as ps');

        if (!empty($mycurriculums)) {
            $mycurriculums = implode(', ', $mycurriculums);
            $sql .= " AND id IN ( $mycurriculums )";
            $programslist = $DB->get_records_sql_menu($sql.$grsql);
        }

    }else if(has_capability('local/program:viewprogram',$systemcontext)){
           $mycurriculums = $DB->get_records_menu('local_cc_session_signups',
                  array('userid' => $USER->id), 'programid', 'programid, programid as ps');
          if (!empty($mycurriculums)) {
              $mycurriculums = implode(', ', $mycurriculums);
              $sql .= " AND id IN ( $mycurriculums ) ";
              $programslist = $DB->get_records_sql_menu($sql.$grsql);
          }
    }

    $select = $mform->addElement('autocomplete', 'programs', get_string('masterprograms','local_program'), $programslist, array('placeholder' => get_string('masterprograms','local_program')));
    $mform->setType('programs', PARAM_RAW);
    $select->setMultiple(true);
}
function programshortcode_filter($mform){
    global $DB,$USER;

    $type = optional_param('type',1, PARAM_RAW);

    $programslist=array();

    $systemcontext = context_system::instance();
    $sql = "SELECT shortcode as id, shortcode FROM {local_program} WHERE shortcode IS NOT NULL  ";

    $grsql=" group by shortcode";

    if(is_siteadmin()){

        if($type==1){
            $sql.= " AND costcenter in (SELECT id FROM {local_costcenter} where parentid=0) ";
        }else if ($type==2){
            $sql.= " AND costcenter in (SELECT id FROM {local_costcenter} where parentid >0) ";
        }

       $programslist = $DB->get_records_sql_menu($sql.$grsql);

    }else if(has_capability('local/program:manage_ownorganization',$systemcontext)){

        if($type==1){
             $sql.= " AND costcenter = $USER->open_costcenterid";
        }else if ($type==2){
            $sql.= " AND costcenter in (SELECT id FROM {local_costcenter} where parentid =$USER->open_costcenterid) ";
        }
        $programslist = $DB->get_records_sql_menu($sql.$grsql);

    }else if(has_capability('local/program:manage_owndepartments',$systemcontext)){

        $sql.= " AND costcenter = $USER->open_departmentid";

        $programslist = $DB->get_records_sql_menu($sql.$grsql);
    }else if(has_capability('local/program:trainer_viewprogram',$systemcontext)){

        $mycurriculums = $DB->get_records_menu('local_cc_session_trainers',
        array('trainerid' => $USER->id), 'programid', 'programid, programid as ps');

        if (!empty($mycurriculums)) {
            $mycurriculums = implode(', ', $mycurriculums);
            $sql .= " AND id IN ( $mycurriculums )";
            $programslist = $DB->get_records_sql_menu($sql.$grsql);
        }

    }else if(has_capability('local/program:viewprogram',$systemcontext)){
           $mycurriculums = $DB->get_records_menu('local_cc_session_signups',
                  array('userid' => $USER->id), 'programid', 'programid, programid as ps');
          if (!empty($mycurriculums)) {
              $mycurriculums = implode(', ', $mycurriculums);
              $sql .= " AND id IN ( $mycurriculums ) ";
              $programslist = $DB->get_records_sql_menu($sql.$grsql);
          }
    }

    $select = $mform->addElement('autocomplete', 'programshortcode',get_string('programshortcode','local_program'), $programslist, array('placeholder' => get_string('programshortcode','local_program')));
    $mform->setType('programshortcode', PARAM_RAW);
    $select->setMultiple(true);
}
function programshortname_filter($mform){
    global $DB,$USER;
    $type = optional_param('type',1, PARAM_RAW);
    $programslist=array();

    $systemcontext = context_system::instance();
    $sql = "SELECT shortname as id, shortname FROM {local_program} WHERE shortname IS NOT NULL ";

    $grsql=" group by shortname";

    if(is_siteadmin()){

        if($type==1){
            $sql.= " AND costcenter in (SELECT id FROM {local_costcenter} where parentid=0) ";
        }else if ($type==2){
            $sql.= " AND costcenter in (SELECT id FROM {local_costcenter} where parentid >0) ";
        }

       $programslist = $DB->get_records_sql_menu($sql.$grsql);

    }else if(has_capability('local/program:manage_ownorganization',$systemcontext)){


        if($type==1){
             $sql.= " AND costcenter = $USER->open_costcenterid";
        }else if ($type==2){
            $sql.= " AND costcenter in (SELECT id FROM {local_costcenter} where parentid =$USER->open_costcenterid) ";
        }
        $programslist = $DB->get_records_sql_menu($sql.$grsql);

    }else if(has_capability('local/program:manage_owndepartments',$systemcontext)){

        $sql.= " AND costcenter = $USER->open_departmentid";

        $programslist = $DB->get_records_sql_menu($sql.$grsql);
    }else if(has_capability('local/program:trainer_viewprogram',$systemcontext)){

        $mycurriculums = $DB->get_records_menu('local_cc_session_trainers',
        array('trainerid' => $USER->id), 'programid', 'programid, programid as ps');

        if (!empty($mycurriculums)) {
            $mycurriculums = implode(', ', $mycurriculums);
            $sql .= " AND id IN ( $mycurriculums )";
            $programslist = $DB->get_records_sql_menu($sql.$grsql);
        }

    }else if(has_capability('local/program:viewprogram',$systemcontext)){
           $mycurriculums = $DB->get_records_menu('local_cc_session_signups',
                  array('userid' => $USER->id), 'programid', 'programid, programid as ps');
          if (!empty($mycurriculums)) {
              $mycurriculums = implode(', ', $mycurriculums);
              $sql .= " AND id IN ( $mycurriculums ) ";
              $programslist = $DB->get_records_sql_menu($sql.$grsql);
          }
    }

    $select = $mform->addElement('autocomplete', 'programshortname', get_string('programshortname','local_program'), $programslist, array('placeholder' => get_string('programshortname','local_program')));
    $mform->setType('programshortname', PARAM_RAW);
    $select->setMultiple(true);
}
function programyear_filter($mform){
    global $DB,$USER;

    $type = optional_param('type',1, PARAM_RAW);

    $programslist=array();

    $systemcontext = context_system::instance();
    $sql = "SELECT year, year as fullname FROM {local_program} WHERE year IS NOT NULL ";

    $grsql=" group by year";

    if(is_siteadmin()){

        if($type==1){
            $sql.= " AND costcenter in (SELECT id FROM {local_costcenter} where parentid=0) ";
        }else if ($type==2){
            $sql.= " AND costcenter in (SELECT id FROM {local_costcenter} where parentid >0) ";
        }

       $programslist = $DB->get_records_sql_menu($sql.$grsql);

    }else if(has_capability('local/program:manage_ownorganization',$systemcontext)){


        if($type==1){
             $sql.= " AND costcenter = $USER->open_costcenterid";
        }else if ($type==2){
            $sql.= " AND costcenter in (SELECT id FROM {local_costcenter} where parentid =$USER->open_costcenterid) ";
        }
        $programslist = $DB->get_records_sql_menu($sql.$grsql);

    }else if(has_capability('local/program:manage_owndepartments',$systemcontext)){

        $sql.= " AND costcenter = $USER->open_departmentid";

        $programslist = $DB->get_records_sql_menu($sql.$grsql);
    }else if(has_capability('local/program:trainer_viewprogram',$systemcontext)){

        $mycurriculums = $DB->get_records_menu('local_cc_session_trainers',
        array('trainerid' => $USER->id), 'programid', 'programid, programid as ps');

        if (!empty($mycurriculums)) {
            $mycurriculums = implode(', ', $mycurriculums);
            $sql .= " AND id IN ( $mycurriculums )";
            $programslist = $DB->get_records_sql_menu($sql.$grsql);
        }

    }else if(has_capability('local/program:viewprogram',$systemcontext)){
           $mycurriculums = $DB->get_records_menu('local_cc_session_signups',
                  array('userid' => $USER->id), 'programid', 'programid, programid as ps');
          if (!empty($mycurriculums)) {
              $mycurriculums = implode(', ', $mycurriculums);
              $sql .= " AND id IN ( $mycurriculums ) ";
              $programslist = $DB->get_records_sql_menu($sql.$grsql);
          }
    }

    $select = $mform->addElement('autocomplete', 'programyear', get_string('year','local_program'), $programslist, array('placeholder' => get_string('year','local_program')));
    $mform->setType('programyear', PARAM_RAW);
    $select->setMultiple(true);
}
function programduration_filter($mform){
    global $DB,$USER;
    $type = optional_param('type',1, PARAM_RAW);
    $programslist=array();
    $systemcontext = context_system::instance();
    $sql = "SELECT duration, duration as fullname FROM {local_curriculum} WHERE duration IS NOT NULL ";

    $grsql=" group by duration";
    if(is_siteadmin()){

        if($type==1){
            $sql.= " AND costcenter in (SELECT id FROM {local_costcenter} where parentid=0) ";
        }else if ($type==2){
            $sql.= " AND costcenter in (SELECT id FROM {local_costcenter} where parentid >0) ";
        }

       $programslist = $DB->get_records_sql_menu($sql.$grsql);

    }else if(has_capability('local/program:manage_ownorganization',$systemcontext)){


        if($type==1){
             $sql.= " AND costcenter = $USER->open_costcenterid";
        }else if ($type==2){
            $sql.= " AND costcenter in (SELECT id FROM {local_costcenter} where parentid =$USER->open_costcenterid) ";
        }
        $programslist = $DB->get_records_sql_menu($sql.$grsql);

    }else if(has_capability('local/program:manage_owndepartments',$systemcontext)){

        $sql.= " AND costcenter = $USER->open_departmentid";

        $programslist = $DB->get_records_sql_menu($sql.$grsql);

    }else if(has_capability('local/program:trainer_viewprogram',$systemcontext)){

        $mycurriculums = $DB->get_records_menu('local_cc_session_trainers',
        array('trainerid' => $USER->id), 'programid', 'programid, programid as ps');

        if (!empty($mycurriculums)) {
            $mycurriculums = implode(', ', $mycurriculums);
            $sql .= " AND id IN ( $mycurriculums )";
            $programslist = $DB->get_records_sql_menu($sql.$grsql);
        }

    }else if(has_capability('local/program:viewprogram',$systemcontext)){
           $mycurriculums = $DB->get_records_menu('local_cc_session_signups',
                  array('userid' => $USER->id), 'programid', 'programid, programid as ps');
          if (!empty($mycurriculums)) {
              $mycurriculums = implode(', ', $mycurriculums);
              $sql .= " AND id IN ( $mycurriculums ) ";
              $programslist = $DB->get_records_sql_menu($sql.$grsql);
          }
    }

    $programslist=array('-1'=>get_string('all'))+$programslist;
    $duration = array();
    $duration[] = & $mform->createElement('select', 'duration','',$programslist);
    $duration_format = array('-1'=>get_string('all'),'Y' => 'Years'/*, 'M' => 'Months'*/);
    $duration[] = & $mform->createElement('select', 'duration_format','', $duration_format);

    $myduration = $mform->addElement('group', 'durationfield', get_string('curriculumduration', 'local_program'), $duration, '', false);

}
function programadmission_filter($mform){
    global $DB,$USER;
    $systemcontext = context_system::instance();

    $mform->addElement('date_selector', 'admissionstartdate', get_string('admissionstartdate', 'local_program'),array('optional'=>true));

    $mform->setType('admissionstartdate', PARAM_RAW);

    $mform->addElement('date_selector', 'admissionenddate', get_string('admissionenddate', 'local_program'),array('optional'=>true));

    $mform->setType('admissionenddate', PARAM_RAW);

}
function programvaliddates_filter($mform){
    global $DB,$USER;
    $systemcontext = context_system::instance();

    $mform->addElement('date_selector', 'validstartdate', get_string('validstartdate', 'local_program'),array('optional'=>true));

    $mform->setType('validstartdate', PARAM_RAW);

    $mform->addElement('date_selector', 'validenddate', get_string('validenddate', 'local_program'),array('optional'=>true));

    $mform->setType('validenddate', PARAM_RAW);

}
function programlevel_filter($mform){
    global $DB,$USER;
    $systemcontext = context_system::instance();

    $semester = array('1' => 'Undergraduate', '2' => 'Post Graduate');
    $select =$mform->addElement('autocomplete', 'programlevel', get_string('curriculumsemester', 'local_program'), $semester);
    $mform->setType('programlevel', PARAM_RAW);
    $select->setMultiple(true);

}
function programfaculty_filter($mform){
    global $DB,$USER;

    $programslist=array();

    $type = optional_param('type',1, PARAM_RAW);


    $systemcontext = context_system::instance();
    $sql = "SELECT lf.id,lf.facultyname FROM {local_faculties} as lf JOIN {local_program} as lp on lp.facultyid=lf.id ";

    $grsql=" group by lf.id";

    if(is_siteadmin()){

       $programslist = $DB->get_records_sql_menu($sql.$grsql);

    }else if(has_capability('local/program:manage_ownorganization',$systemcontext)){

        $sql.= " AND lp.costcenter = $USER->open_costcenterid";
        $programslist = $DB->get_records_sql_menu($sql.$grsql);

    }else if(has_capability('local/program:manage_owndepartments',$systemcontext)){

        $sql.= " AND lp.costcenter = $USER->open_departmentid";

        $programslist = $DB->get_records_sql_menu($sql.$grsql);
    }

    $select = $mform->addElement('autocomplete', 'programfaculty',get_string('programfaculty','local_program'), $programslist, array('placeholder' => get_string('programfaculty','local_program')));
    $mform->setType('programfaculty', PARAM_RAW);
    $select->setMultiple(true);

    $mform->addElement('hidden', 'type',$type);
    $mform->setType('type', PARAM_INT);
}
function programorganizations_filter($mform,$query='',$searchanywhere=false, $page=0, $perpage=25){
    global $DB,$USER;
    $systemcontext = context_system::instance();
    $organizationlist=array();
    $data=data_submitted();

    if(is_siteadmin() || has_capability('local/costcenter:manage_multiorganizations', $systemcontext)){
       // echo "test";
        $organizationlist_sql="SELECT id, fullname FROM {local_costcenter} WHERE depth =1";
    }else{
       // echo "test1";
        $organizationlist_sql="SELECT id, fullname FROM {local_costcenter} WHERE depth =1 AND id = $USER->open_costcenterid";
    }
   // exit;
    if(!empty($query)){
        if ($searchanywhere) {
            $organizationlist_sql.=" AND fullname LIKE '%$query%' ";
        } else {
            $organizationlist_sql.=" AND fullname LIKE '$query%' ";
        }
    }
    if(isset($data->organizations)&&!empty(($data->organizations))){

        $implode=implode(',',$data->organizations);
        if(!empty($implode)){
         $organizationlist_sql.=" AND id in ($implode)";
        }
    }
    $organizationlist_sql.="  LIMIT $page, $perpage";
    if(!empty($query)||empty($mform)){
        $organizationlist = $DB->get_records_sql($organizationlist_sql);
        return $organizationlist;
    }
    if((isset($data->organizations)&&!empty($data->organizations))){
        $organizationlist = $DB->get_records_sql_menu($organizationlist_sql);
    }

    $options = array(
        'ajax' => 'local_courses/form-options-selector',
        'multiple' => true,
        'data-action' => 'organizations',
        'data-options' => json_encode(array('id' => 0)),
        'placeholder' => 'organizations',
    );
    $select = $mform->addElement('autocomplete', 'organizations', get_string('organisations','local_costcenter'), $organizationlist,$options);
    $mform->setType('organizations', PARAM_RAW);
}
/**
  * Description: [departments_filter code]
 * @param  [mform]  $mform          [form where the filetr is initiated]
 * @param  string  $query          [description]
 * @param  boolean $searchanywhere [description]
 * @param  integer $page           [description]
 * @param  integer $perpage        [description]
 * @return [type]                  [description]
 */
 function programdepartments_filter($mform,$query='',$searchanywhere=false, $page=0, $perpage=25){
    global $DB,$USER;
    $systemcontext = context_system::instance();
    $departmentslist=array();
    $data=data_submitted();

    if(is_siteadmin() || has_capability('local/costcenter:manage_multiorganizations', $systemcontext)){
        $departmentslist_sql="SELECT id, fullname FROM {local_costcenter} WHERE depth!=1 AND visible = 1 AND univ_dept_status = 0";
    }else{
        $departmentslist_sql="SELECT id, fullname FROM {local_costcenter} WHERE depth!=1 AND visible = 1 AND univ_dept_status = 0 AND parentid = $USER->open_costcenterid";
    }
    if(!empty($query)){
        if ($searchanywhere) {
            $departmentslist_sql.=" AND fullname LIKE '%$query%' ";
        } else {
            $departmentslist_sql.=" AND fullname LIKE '$query%' ";
        }
    }
    if(isset($data->departments)&&!empty(($data->departments))&&is_array($data->departments)){

        $implode=implode(',',$data->departments);

        $departmentslist_sql.=" AND id in ($implode)";
    }
    $departmentslist_sql.="  LIMIT $page, $perpage";
    if(!empty($query)||empty($mform)){
        $departmentslist = $DB->get_records_sql($departmentslist_sql);
        return $departmentslist;
    }
    if((isset($data->departments)&&!empty($data->departments))){
        $departmentslist = $DB->get_records_sql_menu($departmentslist_sql);
    }

    $options = array(
            'ajax' => 'local_courses/form-options-selector',
            'multiple' => true,
            // 'data-action' => 'departments',
            'data-action' => 'programdepartments', //added department Filter issue ODL-755
            'data-options' => json_encode(array('id' => 0)),
        'placeholder' => 'departments',
           
    );

    // $select = $mform->addElement('autocomplete', 'departments',get_string('department','local_program'), $departmentslist,$options);
    $select = $mform->addElement('autocomplete', 'departments',get_string('department','local_program'), $departmentslist,$options);
    $mform->setType('departments', PARAM_RAW);
}
//Revathi added College Filter issue ODL-755

function programcolleges_filter($mform,$query='',$searchanywhere=false, $page=0, $perpage=25){
    global $DB,$USER;
    $systemcontext = context_system::instance();
    $collegeslist=array();
    $data=data_submitted();

    if(is_siteadmin() || has_capability('local/costcenter:manage_multiorganizations', $systemcontext)){
        $collegeslist_sql="SELECT id, fullname FROM {local_costcenter} WHERE depth!=1 AND visible = 1 AND univ_dept_status = 1";

    }else{
        $collegeslist_sql="SELECT id, fullname FROM {local_costcenter} WHERE depth!=1 AND visible = 1 AND univ_dept_status = 1 AND parentid = $USER->open_costcenterid";

    }
    if(!empty($query)){
        if ($searchanywhere) {
            $collegeslist_sql.=" AND fullname LIKE '%$query%' ";
        } else {
            $collegeslist_sql.=" AND fullname LIKE '$query%' ";
        }
    }

    if(isset($data->colleges)&&!empty(($data->colleges))&&is_array($data->colleges)){


        $implode=implode(',',$data->colleges);

        $collegeslist_sql.=" AND id in ($implode)";
    }
    $collegeslist_sql.="  LIMIT $page, $perpage";
    if(!empty($query)||empty($mform)){
        $collegeslist = $DB->get_records_sql($collegeslist_sql);
        //print_object($collegeslist);
        return $collegeslist;
    }
    if((isset($data->colleges)&&!empty($data->colleges))){

        $collegeslist = $DB->get_records_sql_menu($collegeslist_sql);
    }

    $options = array(
            'ajax' => 'local_courses/form-options-selector',
            'multiple' => true,
            'data-action' => 'programcolleges',
            'data-options' => json_encode(array('id' => 0)),
        'placeholder' => 'colleges',
    );

// print_object($options);
// exit;
    $select = $mform->addElement('autocomplete', 'colleges',get_string('college','local_program'), $collegeslist,$options);
    $mform->setType('colleges', PARAM_RAW);
    
}
//Revathi end College Filter issue ODL-755
function get_programfilterslist($type =1) {
    $context = context_system::instance();
    if (is_siteadmin() OR has_capability('local/program:manage_multiorganizations', $context )) {
        $filterlist = array();

        $filterlist[] = 'programorganizations';

        /*if($type==2){
            $filterlist[] = 'programdepartments';
        }*/
        //#revathi Issue ODL-759 removed faculty filter
        
        $filterlist = array_merge_recursive($filterlist,array('programdepartments'/*,'programfaculty'*/,'programcolleges','programlevel','programname','programshortcode','programshortname','programyear','programduration','programadmission','programvaliddates'));
    }
    else if (has_capability('local/program:manage_ownorganization',$context) ) {
        $filterlist = array();
        /*if($type==2){
            $filterlist[] = 'programdepartments';
        }*/
        $filterlist = array_merge_recursive($filterlist,array('programdepartments'/*,'programfaculty'*/,'programcolleges','programlevel','programname','programshortcode','programshortname','programyear','programduration','programadmission','programvaliddates'));
    }
    else if (has_capability('local/program:manage_owndepartments',$context) ) {
        $filterlist = array('programfaculty','programlevel','programname','programshortcode','programshortname','programyear','programduration','programadmission','programvaliddates');
    }else if (has_capability('local/program:trainer_viewprogram',$context)||has_capability('local/program:viewprogram',$context)) {

        $filterlist = array('programname','programshortcode','programshortname','programyear','programduration','programadmission','programvaliddates');
    }
    return $filterlist;
}
class program_collegeapproval_form extends moodleform {

    public function definition() {
        global $CFG, $DB, $USER;
        $mform = &$this->_form;
        $copiedlist = $this->_customdata['copiedlist'];

        $mform->addElement('hidden', 'confirmid', md5($USER->username));
        $mform->setType('confirmid', PARAM_RAW);

        $mform->addElement('html', $copiedlist);

        // When two elements we need a group.
        $buttonarray = array();
        $classarray = array('class' => 'form-submit');
        $buttonarray[] = &$mform->createElement('submit', 'saveanddisplay', get_string('confirm'), $classarray);
        $buttonarray[] = &$mform->createElement('cancel', 'cancel', get_string('cancel'), $classarray);
        $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);
    }
}
function find_location($costcenter){
    global $DB;
    if($costcenter) {           
            $institues = "select id,fullname from {local_location_institutes} where costcenter = $costcenter AND visible = 1";
            $institue = $DB->get_records_sql($institues);
            return $costcenter =  $institue;
        }else {
            return $costcenter;
        }

}
function find_faculties($costcenter){
    global $DB;
    if($costcenter) {        
           $facultyroleid = $DB->get_field('role', 'id', array('shortname' => 'faculty'));   
           $faculties = "select id, CONCAT(firstname, ' ', lastname) as fullname from {user} where open_costcenterid = $costcenter and open_role = $facultyroleid";
            $facultylist = $DB->get_records_sql($faculties);
            return $costcenter =  $facultylist;
        }else {
            return $costcenter;
        }
}
function find_costcenterfaculties($costcenter){
    global $DB;
    if($costcenter) {        
           $univfacultiessql = "select id, facultyname as fullname from {local_faculties} where university = $costcenter";
            $facultieslist = $DB->get_records_sql($univfacultiessql);
            return $costcenter =  $facultieslist;
        }else {
            return $costcenter;
        }
}
function find_facultydepartments($costcenter, $faculty){
    global $DB;
    if($costcenter) {
        $departmentssql = "SELECT id, fullname FROM {local_costcenter} WHERE parentid = $costcenter AND faculty = $faculty AND univ_dept_status = 0";
        $departments = $DB->get_records_sql($departmentssql);
        return $departments;
    }else{
        return $costcenter;
    }
}
function find_curriculums($costcenter,$department){
    global $DB;
    if($department) {         
           $curriculumssql = "select id,name from {local_curriculum} where costcenter = $costcenter AND program = 0 AND curriculum_publish_status = 1 AND department = $department";
            $curriculums = $DB->get_records_sql($curriculumssql);
            return $costcenter =  $curriculums;
        }else {
            return $costcenter;
        }
}
function find_rooms($location){
    global $DB;
    if($location) {           
            $rooms = "select id,name from {local_location_room} where instituteid = $location AND visible = 1";
            $rooms = $DB->get_records_sql($rooms);
            return $location =  $rooms;
        }else {
            return $location;
        }

}
function find_max_capacity($room){
    global $DB;

    if($room) {           
            $rooms = "select id,capacity from {local_location_room} where id = $room AND visible = 1";
            $rooms = $DB->get_records_sql($rooms);
            return $location =  $rooms;
        }else {
            return $location;
        }

}
function find_locations_basedon_type($locationtype,$programid){
    global $DB;
    if($locationtype) { 
           $parentid = $DB->get_field('local_program','parentid',array('id' => $programid));
           $subcostcenter = $DB->get_field('local_program','costcenter',array('id' => $programid));
          // $costcenter = $DB->get_field('local_program','costcenter',array('id' => $parentid));
           // $curriculumlocations = $DB->get_records_sql('SELECT id,fullname FROM {local_location_institutes} WHERE institute_type = '.$locationtype.' AND costcenter ='.$costcenter.' AND subcostcenter ='.$subcostcenter);
           //changed to get location based on costcenter
           $curriculumlocations = $DB->get_records_sql('SELECT id,fullname FROM {local_location_institutes} WHERE institute_type = '.$locationtype.' AND costcenter ='.$subcostcenter);
            return $location =  $curriculumlocations;
        }else {
            return $location;
        }

}
function findfaculty($programid){
    global $DB;

    if($programid) { 
        $parentid = $DB->get_record_sql('SELECT departmentid,costcenter,parentid FROM {local_program} WHERE id = '.$programid);
      //$costcenter = $DB->get_record_sql('SELECT id,costcenter FROM {local_program} WHERE id = '.$parentid->parentid);
        $role = $DB->get_field('role','id',array('shortname' => 'faculty'));
      // $faculties = $DB->get_records_sql('SELECT id,CONCAT( firstname, " ", lastname ) AS username FROM {user} WHERE deleted= 0 AND suspended = 0 AND open_role ='.$role.' AND open_departmentid = '.$parentid->costcenter);
        //changed to get faculties based costcenter
//        $faculties = $DB->get_records_sql('SELECT id,CONCAT( firstname, " ", lastname ) AS username FROM {user} WHERE deleted= 0 AND suspended = 0 AND open_role ='.$role.' AND open_costcenterid = '.$parentid->costcenter);

// <Mallikarjun> - ODL-751 changed to get faculties based department -- starts
        $faculties = $DB->get_records_sql('SELECT id,CONCAT( firstname, " ", lastname ) AS username FROM {user} WHERE deleted= 0 AND suspended = 0 AND open_role ='.$role.' AND open_departmentid = '.$parentid->departmentid);
        //print_object($faculties);
            return $programid =  $faculties;
        }else {
            return $programid;
        }

}

function local_program_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options=array()) {
        // Check the contextlevel is as expected - if your plugin is a block, this becomes CONTEXT_BLOCK, etc.

        // Make sure the filearea is one of those used by the plugin.
        if ($filearea !== 'program_logo') {
            return false;
        }

        $itemid = array_shift($args);

        $filename = array_pop($args);
        if (!$args) {
            $filepath = '/';
        } else {
            $filepath = '/'.implode('/', $args).'/';
        }

        // Retrieve the file from the Files API.
        $fs = get_file_storage();
        $file = $fs->get_file($context->id, 'local_program', $filearea, $itemid, $filepath, $filename);
        if (!$file) {
            return false;
        }
        send_file($file, $filename, 0, $forcedownload, $options);
    }

function program_logo($program_logo) {
        global $DB;
        $program_logourl = false;

        $sql = "SELECT * FROM {files} WHERE itemid = $program_logo AND filename != '.' ORDER BY id DESC LIMIT 1";
        $programlogorecord = $DB->get_record_sql($sql);

        if (!empty($programlogorecord)){
            if($programlogorecord->filearea=="program_logo"){
                $program_logourl = moodle_url::make_pluginfile_url($programlogorecord->contextid, $programlogorecord->component, $programlogorecord->filearea, $programlogorecord->itemid, $programlogorecord->filepath, $programlogorecord->filename);
            }
        }
        return $program_logourl;
}
function programcourse_enrolled_users($type = null, $course_id = 0, $params, $total=0, $offset1=-1, $perpage=-1, $lastitem=0,$programid = 0){

    global $DB, $USER;
    $context = context_system::instance();
    $course = $DB->get_record('course', array('id' => $course_id));
  
    $params['suspended'] = 0;
    $params['deleted'] = 0;
    
    $enrol = "SELECT courseid FROM {local_cc_semester_courses} WHERE programid = $programid";
    $enrolids = $DB->get_fieldset_sql($enrol);
    $enrolusers = implode(',',$enrolids);

    $classroom ="SELECT id FROM {local_cc_semester_classrooms} WHERE programid = $programid";
    $classroomid = $DB->get_field_sql($classroom);
if(!empty($enrolusers) || !empty($classroomid)){
    if($total==0){
         $sql = "SELECT u.id,concat(u.firstname,' ',u.lastname,' ','(',u.email,')') as fullname";
    }else{
         $sql = "SELECT count(u.id) as total";
    }
    # Amulya issue 684 for gatting same department users - starts
    $sql.=" FROM {user} AS u 
            JOIN {local_costcenter} cc ON cc.id = u.open_departmentid
            JOIN {local_program} as p ON p.departmentid = u.open_departmentid AND p.id=$programid
            JOIN {role} as r ON r.id = u.open_role WHERE u.id > 2 AND u.suspended = :suspended AND u.deleted = :deleted 
            AND r.shortname = 'student'";
        if($lastitem!=0){
           $sql.=" AND u.id > $lastitem";
    }
    # Amulya issue 684 for gatting same department users - ends
    if (!is_siteadmin()) {
        $user_detail = $DB->get_record('user', array('id'=>$USER->id));
        $sql .= " AND u.open_costcenterid = :costcenter";
        $params['costcenter'] = $user_detail->open_costcenterid;
        if (has_capability('local/costcenter:manage_owndepartments',$context) AND !has_capability('local/costcenter:manage_ownorganization',$context)) {
            $sql .=" AND u.open_departmentid = :department";
            $params['department'] = $user_detail->open_departmentid;
        }
    }
    $sql .=" AND u.id <> $USER->id";
    if (!empty($params['email'])) {
         $sql.=" AND u.id IN ({$params['email']})";
    }
    if (!empty($params['uname'])) {
         $sql .=" AND u.id IN ({$params['uname']})";
    }
    if (!empty($params['department'])) {
         $sql .=" AND u.open_departmentid IN ({$params['department']})";
    }
    if (!empty($params['organization'])) {
         $sql .=" AND u.open_costcenterid IN ({$params['organization']})";
    }
    if (!empty($params['idnumber'])) {
         $sql .=" AND u.id IN ({$params['idnumber']})";
    }
    if (!empty($params['groups'])) {
         $group_list = $DB->get_records_sql_menu("select cm.id, cm.userid from {cohort_members} cm, {user} u where u.id = cm.userid AND u.deleted = 0 AND u.suspended = 0 AND cm.cohortid IN ({$params['groups']})");
         
         $groups_members = implode(',', $group_list);
         if (!empty($groups_members))
         $sql .=" AND u.id IN ({$groups_members})";
         else
         $sql .=" AND u.id =0";
    }
  
   // Amulya issue 713 to get massenroll icon - starts
    if(!empty($enrolusers)){
        if ($type=='add') {
            $sql .= " AND u.id NOT IN (SELECT ue.userid
                                 FROM {user_enrolments} AS ue 
                                 JOIN {enrol} e ON (e.id = ue.enrolid and e.courseid IN ($enrolusers) and (e.enrol='manual' OR e.enrol='self' OR e.enrol='program')))";
        }elseif ($type=='remove') {
            $sql .= " AND u.id IN (SELECT ue.userid
                                 FROM {user_enrolments} AS ue 
                                 JOIN {enrol} e ON (e.id = ue.enrolid and e.courseid IN ($enrolusers) and (e.enrol='manual' OR e.enrol='self'OR e.enrol='program')))";
        }
    }
    else{
        if ($type=='add') {
            $sql .= " AND u.id NOT IN (SELECT css.userid
                                 FROM {local_cc_session_signups} AS css
                                 JOIN {local_ccuser_year_signups} AS csu ON (css.userid = csu.userid
                                 AND css.bclcid = $classroomid))";
        }elseif ($type=='remove') {
            $sql .= " AND u.id IN (SELECT css.userid
                                 FROM {local_cc_session_signups} AS css 
                                 JOIN {local_ccuser_year_signups} AS csu ON (css.userid = csu.userid
                                 AND css.bclcid = $classroomid))";
        }
    }
    // Amulya issue 713 to get massenroll icon - starts
    $order = ' ORDER BY u.id ASC ';
    if($perpage!=-1){
        $order.="LIMIT $perpage";
    }
    if($total==0){
        $availableusers = $DB->get_records_sql_menu($sql .$order,$params);
    }else{
        $availableusers = $DB->count_records_sql($sql,$params);
    }
}    
    return $availableusers;
}
// AM issue 710 & 723 assign course issue - starts
function find_existingcourses($department,$semesterid,$yearid,$curriculumid){
        global $DB,$USER;
        $context = context_system::instance();
        $params = array();
        if($department) {
            
           $catids = $DB->get_records_sql_menu("SELECT id,category FROM {local_costcenter} WHERE id IN ($department)");
          
           $categoryid = implode(',', $catids);
            if(!empty($catids)){
              $concatsql = " AND c.category IN ($categoryid)";
            }else{
              $concatsql = " ";
            } 
            $existedcourses = array();                       
            $cousresql = "SELECT c.id, c.fullname
                          FROM {course} c                                      
                          WHERE c.visible = 1 AND c.open_parentcourseid = 0
                          AND c.fullname LIKE '%$query%' AND c.id <> " . SITEID . $concatsql;
            $parentcourseids = array();
            $sql1 = "SELECT open_parentcourseid FROM {local_cc_semester_courses} WHERE curriculumid = $curriculumid AND yearid = $yearid";
            $assignedcourse = $DB->get_fieldset_sql($sql1);
            $assignedcourses = implode(',',$assignedcourse);

            if(!empty($assignedcourse)){
                $sql2 = "SELECT open_parentcourseid FROM {course} WHERE id IN ($assignedcourses)";
                $parentcourseid = $DB->get_fieldset_sql($sql2);
                $parentcourseids = implode(',',$parentcourseid);
                $courseids = !empty($assignedcourses) ? $assignedcourses . ',' . $parentcourseids : $parentcourseids ;
            } 
            if($courseids){
              $sql = " AND c.id NOT IN ($courseids)";
            }   
            // if (!is_siteadmin()) {
            //     $user_detail = $DB->get_record('user', array('id'=>$USER->id));
            //     $sql .= " AND u.open_costcenterid = :costcenter";
            //     $params['costcenter'] = $user_detail->open_costcenterid;
            // if (has_capability('local/costcenter:manage_owndepartments',$context) AND !has_capability('local/costcenter:manage_ownorganization',$context)) {
            //      $sql .=" AND u.open_departmentid = :department";
            //      $params['department'] = $user_detail->open_departmentid;
            //     }
            // }
            $courses = $DB->get_records_sql($cousresql.$sql,$params);
            return $department =  $courses;
        }else {
            return $department;
        }
    }
// AM issue 710 & 723 assign course issue - ends

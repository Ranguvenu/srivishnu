<?php
namespace local_departments\form;
use core;
use moodleform;
use context_system;
use coursecat;
require_once($CFG->dirroot . '/lib/formslib.php');
require_once($CFG->libdir.'/coursecatlib.php');
class department_form extends moodleform {
    /**
     * The form definition.
     */
    public function definition() {
        global $CFG, $DB, $USER;
        $mform = $this->_form;
        // $id = $this->_customdata['id'];
        $departmentid = $this->_customdata['id'];
        $university = $this->_customdata['parentid'];
        $faculty = $this->_customdata['faculty'];
        $underuniversity = $this->_customdata['underuniversity'];
        $editoroptions = $this->_customdata['editoroptions'];
        $univ_dept_status = 0;
        $context = context_system::instance();
        // Get list of categories to use as parents, with site as the first one.
        $options = array();
        //=================================================
        if(is_siteadmin() && has_capability('local/costcenter:manage_multiorganizations',$context)){
            if($departmentid > 0){
                $checkcostcenter = new \local_costcenter\local\checkcostcenter();
                $modulecount = $checkcostcenter->costcenter_modules_exist($university,$departmentid);
                if(!$modulecount['userscount'] && !$modulecount['coursescount'] && !$modulecount['programscount'] && !$underuniversity){
                    $universities_sql = "SELECT id, fullname FROM {local_costcenter} WHERE parentid = 0 AND visible = 1";
                    $univ = array(null=>'Select University');
                    $universities = $DB->get_records_sql_menu($universities_sql);
                    $universities = $univ + $universities;
                    $mform->addElement('select', 'parentid', 'University', $universities);
                    $mform->addRule('parentid', get_string('emptyuniversity', 'local_colleges'), 'required', null, 'client');
                }else{
                    $parentid = $DB->get_field('local_costcenter','parentid',array('id' => $departmentid));
                    $universities_sql = "SELECT id, fullname FROM {local_costcenter} WHERE id = $parentid";
                    $universityname = $DB->get_record_sql($universities_sql);
                    $mform->addElement('static', 'universityname', get_string('university', 'local_boards'));
                    if($underuniversity){
                        $mform->setDefault('universityname',$universityname->fullname);
                    }else{
                        $mform->setDefault('universityname',$universityname->fullname.'<br><div class="usermessage">Users or Courses Programs are created uder this university</div>');
                    }
                    
                    $mform->addElement('hidden', 'parentid',get_string('university', 'local_boards'));
                    $mform->setType('parentid', PARAM_INT);
                    $mform->setDefault('parentid', $universityname->id);
                }
            }else{
                if($underuniversity){
                    $universities_sql = "SELECT id, fullname FROM {local_costcenter} WHERE id = $underuniversity";
                    $universityname = $DB->get_record_sql($universities_sql);
                    $mform->addElement('static', 'universityname', get_string('university', 'local_boards'));
                    $mform->setDefault('universityname',$universityname->fullname);
                    $mform->addElement('hidden', 'parentid',get_string('university', 'local_boards'));
                    $mform->setType('parentid', PARAM_INT);
                    $mform->setDefault('parentid', $universityname->id);
                }else{
                    $universities_sql = "SELECT id, fullname FROM {local_costcenter} WHERE parentid = 0 AND visible = 1";
                    $univ = array(null=>'Select University');
                    $universities = $DB->get_records_sql_menu($universities_sql);
                    $universities = $univ + $universities;
                    $mform->addElement('select', 'parentid', 'University', $universities);
                    $mform->addRule('parentid', get_string('emptyuniversity', 'local_colleges'), 'required', null, 'client');
                }
            }
        }elseif(has_capability('local/costcenter:manage_ownorganization',$context) || has_capability('local/costcenter:manage_owndepartments',$context)){
            $universities_sql = "SELECT id, fullname FROM {local_costcenter} WHERE id = $USER->open_costcenterid";
            $universityname = $DB->get_record_sql($universities_sql);
            // Amulya changed to get error message only when course and programs are available under university -- starts

            $programs_sql = "SELECT id FROM {local_program} WHERE costcenter = $universityname->id";
            $programs = $DB->get_record_sql($programs_sql);

            $courses_sql = "SELECT id FROM {course} WHERE open_costcenterid = $universityname->id";
            $courses = $DB->get_record_sql($courses_sql);
            
            $mform->addElement('static', 'universityname', get_string('university', 'local_boards'));
            if($underuniversity){
                $mform->setDefault('universityname',$universityname->fullname);
            }elseif(!empty($programs) || !empty($courses)){
                $mform->setDefault('universityname',$universityname->fullname.'<br><div class="usermessage">Users or Courses Programs are created uder this university</div>');
            }else{
                $mform->setDefault('universityname',$universityname->fullname);
            }
          //  $mform->setDefault('universityname',$universityname->fullname);
            $mform->addElement('hidden', 'parentid',get_string('university', 'local_boards'));
            $mform->setType('parentid', PARAM_INT);
            $mform->setDefault('parentid', $universityname->id);
        }
        
        if (is_siteadmin($USER->id) || has_capability('local/faculties:manage',$context) || has_capability('local/costcenter:manage_ownorganization',$context)) {
            $faculties = array(null=>get_string('selectfaculty','local_departments'));
            $facultylist = array();
            if($departmentid > 0){
                $subsql = "SELECT lf.id, lf.facultyname FROM {local_faculties} AS lf 
                             WHERE 1=1";
                if($university){
                    $subsql .= " AND lf.university = ".$university."";
                }
                if($this->_ajaxformdata['parentid'] > 0){
                    $subsql .= " AND lf.university = ".$this->_ajaxformdata['parentid']."";
                }
                $facultylist = $DB->get_records_sql_menu($subsql);
            }elseif($this->_ajaxformdata['parentid'] > 0 || $underuniversity){
                $subsql = "SELECT id, facultyname FROM {local_faculties} WHERE 1=1";
                if(!empty($this->_ajaxformdata['parentid'])){
                    $subsql .= " AND university = ".$this->_ajaxformdata['parentid']."";
                }
                if(!empty($underuniversity)){
                    $subsql .= " AND university = ".$underuniversity."";
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
            // if(isset($this->_ajaxformdata['university'])){
            //     $boards = $boards+$DB->get_records_sql_menu("SELECT id, fullname FROM {local_boards} WHERE university = ".$this->_ajaxformdata['university']."");
            // }
            if($departmentid > 0){
                $dept = $DB->get_record('local_costcenter',array('id' => $departmentid));
                $checkcostcenter = new \local_costcenter\local\checkcostcenter();
                $modulecount = $checkcostcenter->costcenter_modules_exist($dept->parentid,$dept->id);
                if(!$modulecount['userscount'] && !$modulecount['coursescount'] && !$modulecount['programscount']){
//                     $mform->addElement('select', 'faculty',get_string('faculties','local_departments'), $facultieslist);

//                    $mform->addRule('faculty', get_string('emptyfaculties', 'local_departments'), 'required', null, 'client');
                    // $mform->setType('faculty', PARAM_INT);
                }else{
//                    $facultyname = $DB->get_record('local_faculties',array('id' => $dept->faculty));
//                    $mform->addElement('static', 'facultyname', get_string('faculties', 'local_departments'));
//                    $mform->setDefault('facultyname',$facultyname->facultyname.'<br><div class="usermessage">Users or Courses or programs are created uder this department</div>');
//                    $mform->addElement('hidden', 'faculty',get_string('faculties', 'local_departments'));
//                    $mform->setType('faculty', PARAM_INT);
//                    $mform->setDefault('faculty', $facultyname->id);
                    // $mform->addElement('static', 'usermessage', '');
                    // $mform->setDefault('usermessage','<div class="usermessage">Users or Courses or programs are created uder this department</div>');
                }
            }else{
//                $mform->addElement('select', 'faculty',get_string('faculties','local_departments'), $facultieslist);
//                $mform->addRule('faculty', get_string('emptyfaculties', 'local_departments'), 'required', null, 'client');
                // $mform->setType('faculty', PARAM_INT);
            }
            
            // $mform->hideIf('board', 'university', 'neq', 1);
            // $mform->setConstant('board', $this->_ajaxformdata['board']);
        }
        
        $mform->addElement('text', 'fullname', get_string('departmentname','local_departments'), array('size' => '30'));
        $mform->addRule('fullname', get_string('emptydepartmentname', 'local_departments'), 'required', null, 'client');
        $mform->setType('fullname', PARAM_TEXT);

        $mform->addElement('text', 'shortname', get_string('idnumbercoursedep','local_departments'), 'maxlength="100" size="10"');
        $mform->addRule('shortname', get_string('emptydepartmentidnumber', 'local_departments'), 'required', null, 'client');
        $mform->setType('shortname', PARAM_RAW);

        $editoroptions = array(
            'noclean' => false,
            'autosave' => false
        );
        $mform->addElement('editor', 'description_editor', get_string('description'), null,
        $this->get_description_editor_options(), $editoroptions);
        
        if (!empty($CFG->allowcategorythemes)) {
            $themes = array(''=>get_string('forceno'));
            $allthemes = get_list_of_themes();
            foreach ($allthemes as $key => $theme) {
                if (empty($theme->hidefromselector)) {
                    $themes[$key] = get_string('pluginname', 'theme_'.$theme->name);
                }
            }
            $mform->addElement('select', 'theme', get_string('forcetheme'), $themes);
        }

        $mform->addElement('hidden', 'id', 0);
        $mform->setType('id', PARAM_INT);
        $mform->setDefault('id', $departmentid);

        $mform->addElement('hidden', 'univ_dept_status', 0);
        $mform->setType('univ_dept_status', PARAM_INT);
        $mform->setDefault('univ_dept_status', $univ_dept_status);

         $mform->disable_form_change_checker();
    }

    /**
     * Returns the description editor options.
     * @return array
     */
    public function get_description_editor_options() {
        global $CFG;
        
        $context = $this->_customdata['context'];
        if(empty($context)){
            $context =  context_system::instance();
        }
        $itemid = $this->_customdata['itemid'];
        return array(
            'autosave' => false,
            'maxfiles'  => EDITOR_UNLIMITED_FILES,
            'maxbytes'  => $CFG->maxbytes,
            'trusttext' => true,
            'context'   => $context,
            'subdirs'   => file_area_contains_subdirs($context, 'coursecat', 'description', $itemid),
        );
    }

    /**
     * Validates the data submit for this form.
     *
     * @param array $data An array of key,value data pairs.
     * @param array $files Any files that may have been submit as well.
     * @return array An array of errors.
     */
    public function validation($data, $files) {
        global $DB;
        $errors = array();
        $errors = parent::validation($data, $files);
        $id = $data['id'];
        $catid = $data['catid'];
        $depttype  = $data['deptype']; 
        $college = $data['college'];
        $deptid = $data['shortname'];
        $university = $data['parentid'];
        $faculty = $data['faculty'];
        if(!empty($deptid)){
           $idnumber = preg_match('/^\S*$/', $deptid); 
           if(!$idnumber){
            $errors['shortname'] = get_string('spacesnotallowed', 'local_departments');
           }
// <mallikarjun> - ODL-780 Able to create department with same short code -- starts
        $subsql1 = "SELECT * FROM {local_costcenter} WHERE shortname = '".$data['shortname']."' AND parentid = $university ";
        $existing = $DB->get_record_sql($subsql1);
// <mallikarjun> - ODL-780 Able to create department with same short code -- ends

        }
            /*if($university > 0){
                if($data['faculty'] == null){
                    $errors['faculty'] = get_string('emptyfaculties', 'local_departments');
                }
            }*/
        if ($id > 0) {
            /*$existing = $DB->get_record('course_categories', array('idnumber' => $data['idnumber'], 'id' => $catid));*/
            $subsql = "SELECT * FROM {local_costcenter} WHERE id != $id AND shortname = '".$data['shortname']."' AND parentid = $university ";
            if(!empty($faculty)){
                $subsql .= " AND faculty = $faculty";
            }
            $existing = $DB->get_record_sql($subsql);
        } elseif($university && !empty($deptid) && $faculty){
            // $existing = $DB->get_record('course_categories', array('idnumber' => $data['idnumber']));
            $existing = $DB->get_record('local_costcenter', array('parentid' => $university, 'faculty' => $faculty,'shortname' => $data['shortname']));
        }

        if(!empty($existing)){
            $errors['shortname'] = get_string('departmentalreadyexists', 'local_departments');
        }
        return $errors;
        }
}

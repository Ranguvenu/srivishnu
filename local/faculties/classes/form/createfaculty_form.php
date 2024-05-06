<?php
namespace local_faculties\form;
use core;
use moodleform;
use context_system;
use coursecat;
require_once($CFG->dirroot . '/lib/formslib.php');
require_once($CFG->libdir.'/coursecatlib.php');

class createfaculty_form extends moodleform {
    
    function definition() {
        global $USER, $CFG, $DB, $PAGE;
        $mform = $this->_form;
        $id = $this->_customdata['id'];
        $universityid = $this->_customdata['university'];
        $mform->addElement('hidden', 'id', $id);
        $mform->setType('id', PARAM_INT);

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);
        
        $systemcontext = context_system::instance();
            // University list
            if (is_siteadmin($USER->id) || has_capability('local/costcenter:manage_multiorganizations', $systemcontext)) {
                if($id > 0){
                    $faculty = $DB->get_record('local_faculties',array('id' => $id));
                    $departsmentcount = $DB->count_records('local_costcenter',array('faculty' => $id));
                    if($departsmentcount > 0){
                      $university = $DB->get_record_sql("SELECT id, fullname FROM {local_costcenter} WHERE id = $faculty->university");
                        $mform->addElement('static', 'universityname', get_string('university', 'local_boards'));
                        $mform->setDefault('universityname',$university->fullname.'<br><div class="usermessage">By using this faculty departments are created</div>');
                        $mform->addElement('hidden', 'university',
                        get_string('university', 'local_boards'));
                        $mform->setType('university', PARAM_INT);
                        $mform->setDefault('university', $university->id); 

                        // $mform->addElement('static', 'usermessage', '');
                        // $mform->setDefault('usermessage','<div class="usermessage">By using this faculty departments are created</div>');
                    }else{
                        $universities = $DB->get_records_sql_menu("SELECT id, fullname FROM {local_costcenter} WHERE parentid = 0 AND visible = 1");
                        $select = $mform->addElement('select', 'university',get_string('university','local_boards'),array(null=>get_string('selectuniversity','local_boards')) + $universities,$array);
                        $mform->addRule('university', null, 'required', null, 'client');  
                    }
                }else{
                    $universities = $DB->get_records_sql_menu("SELECT id, fullname FROM {local_costcenter} WHERE parentid = 0 AND visible = 1");
                    $select = $mform->addElement('select', 'university',get_string('university','local_boards'),array(null=>get_string('selectuniversity','local_boards')) + $universities,$array);
                    $mform->addRule('university', null, 'required', null, 'client');
                }
            }elseif(has_capability('local/boards:manage',$systemcontext)|| has_capability('local/costcenter:manage_ownorganization',$systemcontext)){
                $university = $DB->get_record_sql("SELECT id, fullname FROM {local_costcenter} WHERE id = $USER->open_costcenterid AND visible = 1");
                $mform->addElement('static', 'universityname', get_string('university', 'local_boards'));
                $mform->setDefault('universityname',$university->fullname);
                $mform->addElement('hidden', 'university',
                get_string('university', 'local_boards'));
                $mform->setType('university', PARAM_INT);
                $mform->setDefault('university', $university->id);
            }
       
        $mform->addElement('text', 'facultyname', get_string('facultyname', 'local_faculties'));
        $mform->addRule('facultyname', get_string('missingfacultyname', 'local_faculties'), 'required', null, 'client');
        $mform->setType('facultyname', PARAM_RAW);

        $mform->addElement('text', 'facultycode', get_string('facultycode', 'local_faculties'));
        $mform->addRule('facultycode', get_string('missingfacultycode', 'local_faculties'), 'required', null, 'client');
        $mform->setType('facultycode', PARAM_RAW);

        /*if (is_siteadmin($USER->id) || has_capability('local/faculties:manage',$systemcontext) || has_capability('local/costcenter:manage_ownorganization',$systemcontext)) {
            $boards = array(null=>get_string('selectboard','local_faculties'));
            if($id > 0 || $this->_ajaxformdata['university']){
                $subsql = "SELECT id, fullname FROM {local_boards} WHERE 1=1";
                if($this->_customdata['university']){
                    $subsql .= " AND university = ".$this->_customdata['university']."";
                }
                if(!empty($this->_ajaxformdata['university'])){
                    $subsql .= " AND university = ".$this->_ajaxformdata['university']."";
                }
                $boards = $boards+$DB->get_records_sql_menu($subsql);
            }elseif($USER->open_costcenterid){
                $boards = $boards+$DB->get_records_sql_menu("SELECT id, fullname FROM {local_boards} WHERE university = $USER->open_costcenterid");
            }
            // if(isset($this->_ajaxformdata['university'])){
            //     $boards = $boards+$DB->get_records_sql_menu("SELECT id, fullname FROM {local_boards} WHERE university = ".$this->_ajaxformdata['university']."");
            // }
            $select = $mform->addElement('select', 'board',get_string('boards','local_faculties'), $boards);
            $mform->addRule('board', null, 'required', null, 'client');
            // $mform->hideIf('board', 'university', 'neq', 1);
            // $mform->setConstant('board', $this->_ajaxformdata['board']);
        }*/// Commented by Harish for hiding boards functionality

        $mform->addElement('editor', 'description', get_string('description'), null,
        $this->get_description_editor_options(), $editoroptions);

        $submitlable = ($id > 0) ? get_string('updatefaculty', 'local_faculties') : get_string('createfaculty', 'local_faculties');
       // $this->add_action_buttons($cancel = true, $submitlable);
    }


    public function validation($data, $files) {
        $errors = array();
        global $DB, $CFG;
        $errors = parent::validation($data, $files);
        $id = $data['id'];
        /* Bug -id #269
         * @author hemalatha c arun<hemalatha@eabyas.in>
         * Resolved- providing proper validation, When the user entered exists shortname  */
        // $shortname=mysql_real_escape_string($data['shortname']);
        $university = $data['university'];
        $facultycode = $data['facultyname'];
        $facultycode = $data['facultycode'];

        if(!empty($facultycode)){
           $facultyshortcode = preg_match('/^\S*$/', $facultycode); 
           if(!$facultyshortcode){
            $errors['facultycode'] = get_string('spacesnotallowed', 'local_faculties');
           }

        }
        /*$compare_scale_clause_fn = $DB->sql_compare_text("facultyfullname")  . ' = ' . $DB->sql_compare_text(":fn");*/
        $compare_scale_clause_sh = $DB->sql_compare_text("facultycode")  . ' = ' . $DB->sql_compare_text(":sh");
        if ($id > 0) {
            $sql = "SELECT * FROM {local_faculties} ls WHERE ls.id != {$id}";
            $sql .= " AND ls.university = $university AND $compare_scale_clause_sh";
            $facultyid = $DB->get_record_sql($sql,array('sh'=>$facultycode));
            // $facultyid = $DB->get_record_sql("SELECT * FROM {local_faculties} WHERE id != {$id} AND $compare_scale_clause_sh AND $compare_scale_clause_fn",array('sh'=>$facultycode,'fn'=>$facultyfullname));
        } elseif($university){
            $sql = "SELECT * FROM {local_faculties} ls WHERE 1=1";
            $sql .= " AND ls.university = $university AND $compare_scale_clause_sh";
            $facultyid = $DB->get_record_sql($sql,array('sh'=>$facultycode));
        }
        
        if (!empty($facultyid)) {
            $errors['facultycode'] = get_string('facultycodeexists', 'local_faculties');
        }
        return $errors;
    }

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
}

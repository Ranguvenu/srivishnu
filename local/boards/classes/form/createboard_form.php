<?php
namespace local_boards\form;
use core;
use moodleform;
use context_system;
use coursecat;
require_once($CFG->dirroot . '/lib/formslib.php');
require_once($CFG->libdir.'/coursecatlib.php');
// require_once($CFG->libdir . '/completionlib.php');
// require_once($CFG->dirroot . '/local/boards/lib.php');

class createboard_form extends moodleform {

    function definition() {
        global $USER, $CFG, $DB, $PAGE;
        $mform = $this->_form;
        $id = $this->_customdata['id'];
        $schoolid = $this->_customdata['schoolid'];
        $mform->addElement('hidden', 'id', $id);
        $mform->setType('id', PARAM_INT);

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);
        
        $systemcontext = context_system::instance();
            // organisation list
            if (is_siteadmin()) {
                // parentid = 0 for gettings only organisations in dropdown .
                $universities = $DB->get_records_sql_menu("SELECT id, fullname FROM {local_costcenter} WHERE parentid = 0 AND visible = 1");
                if($id>0){
                $universityid = $DB->get_field('local_boards','university',array('id' => $id));
                $university = $DB->get_record_sql("SELECT id, fullname FROM {local_costcenter} WHERE id = $universityid AND visible = 1");
             /*   $mform->addElement('static', 'universityname', get_string('university', 'local_boards'));
                $mform->setDefault('universityname',$university->fullname);*/
                $mform->addElement('hidden', 'university',
                get_string('university', 'local_boards'));
                $mform->setType('university', PARAM_INT);
                $mform->setDefault('university', $university->id);
                $mform->addElement('text', 'universityname', get_string('university', 'local_boards'),'readonly');
                $mform->setType('universityname', PARAM_TEXT);
                $mform->setDefault('universityname', $university->fullname);

                }else{
                $select = $mform->addElement('select', 'university',get_string('university','local_boards'),array(null=>get_string('selectuniversity','local_boards')) + $universities);
                $mform->addRule('university', null, 'required', null, 'client');
                }
            }elseif(has_capability('local/boards:manage',$systemcontext)|| has_capability('local/costcenter:manage',$systemcontext)){
                $university = $DB->get_record_sql("SELECT id, fullname FROM {local_costcenter} WHERE id = $USER->open_costcenterid AND visible = 1");
                $mform->addElement('static', 'universityname', get_string('university', 'local_boards'));
                $mform->setDefault('universityname',$university->fullname);
                $mform->addElement('hidden', 'university',
                get_string('university', 'local_boards'));
                $mform->setType('university', PARAM_INT);
                $mform->setDefault('university', $university->id);
            }
       
        $mform->addElement('text', 'fullname', get_string('boardname', 'local_boards'));
        $mform->addRule('fullname', get_string('missingboard', 'local_boards'), 'required', null, 'client');
        $mform->setType('fullname', PARAM_RAW);

        $mform->addElement('text', 'shortname', get_string('boardid', 'local_boards'));
        $mform->addRule('shortname', get_string('missingboardcode', 'local_boards'), 'required', null, 'client');
        $mform->setType('shortname', PARAM_RAW);

        $editoroptions = array(
            'noclean' => false,
            'autosave' => false
        );
       
        $mform->addElement('editor', 'description', get_string('description'), null,
        $this->get_description_editor_options());

        $submitlable = ($id > 0) ? get_string('updateboard', 'local_boards') : get_string('createboard', 'local_boards');
        //$this->add_action_buttons($cancel = true, $submitlable);
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
        $fullname = $data['fullname'];
        $shortname = $data['shortname'];
         if(!empty($shortname)){
           $shortname = preg_match('/^\S*$/', $shortname); 
           if(!$shortname){
            $errors['shortname'] = get_string('spacesnotallowed', 'local_boards');
           }

        }

        /*$compare_scale_clause_fn = $DB->sql_compare_text("boardfullname")  . ' = ' . $DB->sql_compare_text(":fn");*/
        $compare_scale_clause_sh = $DB->sql_compare_text("shortname")  . ' = ' . $DB->sql_compare_text(":sh");
        if ($id > 0) {
            $sql = "SELECT * FROM {local_boards} ls WHERE ls.id != {$id}";
            $sql .= " AND ls.university = $university AND $compare_scale_clause_sh";
            $boardid = $DB->get_record_sql($sql,array('sh'=>$shortname));
            // $boardid = $DB->get_record_sql("SELECT * FROM {local_boards} WHERE id != {$id} AND $compare_scale_clause_sh AND $compare_scale_clause_fn",array('sh'=>$boardcode,'fn'=>$boardfullname));
        } elseif($university){
            $sql = "SELECT * FROM {local_boards} ls WHERE 1=1";
            $sql .= " AND ls.university = $university AND $compare_scale_clause_sh";
            $boardid = $DB->get_record_sql($sql,array('sh'=>$shortname));
        }
        if (!empty($boardid)) {
            $errors['shortname'] = get_string('boardcodeexists', 'local_boards');
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

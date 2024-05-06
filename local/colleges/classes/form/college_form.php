<?php
namespace local_colleges\form;
use core;
use moodleform;
use context_system;
use coursecat;
require_once($CFG->dirroot . '/lib/formslib.php');
require_once($CFG->libdir.'/coursecatlib.php');
class college_form extends moodleform {
    /**
     * The form definition.
     */
    public function definition() {
        global $CFG, $DB, $USER;
        $mform = $this->_form;
        // $id = $this->_customdata['id'];
        $collegeid = $this->_customdata['collegeid'];
        $catid = $this->_customdata['catid'];
        $univ_dept_status = 1;
        $underuniversity = $this->_customdata['underuniversity'];
        $context = context_system::instance();
        // Get list of categories to use as parents, with site as the first one.
        $options = array();
        
        //=================================================
        if(is_siteadmin() && has_capability('local/costcenter:manage_multiorganizations',$context)){
            if($collegeid > 0){
                $parentid = $DB->get_field('local_costcenter','parentid',array('id' => $collegeid));
                $checkcostcenter = new \local_costcenter\local\checkcostcenter();
                $modulecount = $checkcostcenter->costcenter_modules_exist($parentid,$collegeid);
                // print_object($underuniversity);
                if(!$modulecount['userscount'] && !$underuniversity){
                    $universities_sql = "SELECT id, fullname FROM {local_costcenter} WHERE parentid = 0 AND visible = 1";
                    $univ = array(null=>'Select University');
                    $universities = $DB->get_records_sql_menu($universities_sql);
                    $universities = $univ + $universities;
                    $mform->addElement('select', 'university', 'University', $universities);
                    $mform->addRule('university', get_string('emptyuniversity', 'local_colleges'), 'required', null);
                }else{
                    $universities_sql = "SELECT id, fullname FROM {local_costcenter} WHERE id = $parentid";
                    $universityname = $DB->get_record_sql($universities_sql);
                    $mform->addElement('static', 'universityname', get_string('university', 'local_boards'));
                    if($underuniversity){
                        $mform->setDefault('universityname',$universityname->fullname);
                    }else{
                        $mform->setDefault('universityname',$universityname->fullname.'<br><div class="usermessage">Users are created uder this college</div>');
                    }
                    $mform->addElement('hidden', 'university',get_string('university', 'local_boards'));
                    $mform->setType('university', PARAM_INT);
                    $mform->setDefault('university', $universityname->id);                     
                }
            }else{
                if($underuniversity){
                    $universities_sql = "SELECT id, fullname FROM {local_costcenter} WHERE id = $underuniversity";
                    $universityname = $DB->get_record_sql($universities_sql);
                    $mform->addElement('static', 'universityname', get_string('university', 'local_boards'));
                    $mform->setDefault('universityname',$universityname->fullname);
                    $mform->addElement('hidden', 'university',get_string('university', 'local_boards'));
                    $mform->setType('university', PARAM_INT);
                    $mform->setDefault('university', $universityname->id);
                }else{
                    $universities_sql = "SELECT id, fullname FROM {local_costcenter} WHERE parentid = 0 AND visible = 1";
                    $univ = array(null=>'Select University');
                    $universities = $DB->get_records_sql_menu($universities_sql);
                    $universities = $univ + $universities;
                    $mform->addElement('select', 'university', 'University', $universities);
                    $mform->addRule('university', get_string('emptyuniversity', 'local_colleges'), 'required', null);
                }
            }
        }elseif(has_capability('local/costcenter:manage_ownorganization',$context) || has_capability('local/costcenter:manage_owncolleges',$context)){
            $universities_sql = "SELECT id, fullname FROM {local_costcenter} WHERE id = $USER->open_costcenterid";
            $universityname = $DB->get_record_sql($universities_sql);
            $users_sql = "SELECT id FROM {user} WHERE open_costcenterid = $universityname->id AND id != $USER->id AND deleted = 0 ";
            $users = $DB->get_record_sql($users_sql);
            $mform->addElement('static', 'universityname', get_string('university', 'local_boards'));
            if($underuniversity){
                        $mform->setDefault('universityname',$universityname->fullname);
            }elseif(!empty($users)){
                        $mform->setDefault('universityname',$universityname->fullname.'<br><div class="usermessage">Users are created uder this college</div>');
            }else{
                    $mform->setDefault('universityname',$universityname->fullname);
            }
           // $mform->setDefault('universityname',$universityname->fullname);
            $mform->addElement('hidden', 'university',get_string('university', 'local_boards'));
            $mform->setType('university', PARAM_INT);
            $mform->setDefault('university', $universityname->id);
        }
        
        // $colleges = array();
        // $colleges[null] = "--Select college --";
        // if($collegeid > 0 && $university){
        //     $colleges_sql = " SELECT id, fullname 
        //                         FROM {local_costcenter} WHERE 
        //                         parentid = $university";
        //     $college_list = $DB->get_records_sql_menu($colleges_sql);
        //     $colleges = $colleges + $college_list;
        // }elseif($this->_ajaxformdata['university']){
        //     $univ = $this->_ajaxformdata['university'];
        //     $colleges_sql = " SELECT id, fullname 
        //                         FROM {local_costcenter} WHERE  parentid = $univ";
        //     $college_list = $DB->get_records_sql_menu($colleges_sql);
        //     $colleges = $colleges + $college_list;
        // }elseif(!is_siteadmin() && (has_capability('local/costcenter:manage_ownorganization',$context) || has_capability('local/costcenter:manage_owncolleges',$context))){ 
        //     $colleges_sql = " SELECT id, fullname 
        //                         FROM {local_costcenter} 
        //                         WHERE parentid = $USER->open_costcenterid";
        //     $college_list = $DB->get_records_sql_menu($colleges_sql);
        //     $colleges = $colleges + $college_list;
        // }
        // $mform->addElement('select', 'college', 'College', $colleges,array('class'=>'college'));
        // $mform->setType('college', PARAM_RAW);
        // // $mform->addRule('college', get_string('required'), 'required', null);
        // // $mform->addElement('select', 'parent', get_string('parentcategory'), $universities);

        $mform->addElement('text', 'fullname', get_string('collegename','local_colleges'), array('size' => '30'));
        $mform->addRule('fullname', get_string('emptycollegefullname', 'local_colleges'), 'required', null);
        $mform->setType('fullname', PARAM_TEXT);

        $mform->addElement('text', 'shortname', get_string('idnumbercoursedep','local_colleges'), 'maxlength="100" size="10"');
        $mform->addRule('shortname', get_string('emptycollegeshortname', 'local_colleges'), 'required', null);
        $mform->setType('shortname', PARAM_RAW);

        $editoroptions = array(
            'noclean' => false,
            'autosave' => false
        );
        $mform->addElement('editor', 'description', get_string('description'), null,
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
        $mform->setDefault('id', $collegeid);

        $mform->addElement('hidden', 'catid', 0);
        $mform->setType('catid', PARAM_INT);
        $mform->setDefault('catid', $catid);

        $mform->addElement('hidden', 'univ_dept_status', 1);
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
        $errors = parent::validation($data, $files);
        $id = $data['id'];
        $fullname = strip_tags($data['fullname']);
        $shortname = strip_tags($data['shortname']);
        $university = $data['university'];
        if(!empty($shortname)){
           $shortname = preg_match('/^\S*$/', $shortname); 
           if(!$shortname){
            $errors['shortname'] = get_string('spacesnotallowed', 'local_colleges');
           }

        }
        if ($id > 0) {
            /*$existing = $DB->get_record('course_categories', array('idnumber' => $data['idnumber'], 'id' => $catid));*/
            if($shortname){
                $subsql = "SELECT * FROM {local_costcenter} WHERE id != $id AND shortname = '".$data['shortname']."' AND parentid = $university ";
                $existing = $DB->get_record_sql($subsql);
                if(!empty($existing)){
                    $errors['shortname'] = get_string('collegealreadyexists', 'local_colleges');
                }
            }
            if($fullname){
                //$subsql = "SELECT * FROM {local_costcenter} WHERE id != $id AND shortname = '".$data['fullname']."' AND parentid = $university ";
                //<revathi> Issue no 820 starts
                $subsql = "SELECT * FROM {local_costcenter} WHERE id != $id AND fullname = '".$data['fullname']."' AND parentid = $university ";
                 //<revathi> Issue no 820 ends
                $existing = $DB->get_record_sql($subsql);
                if(!empty($existing)){
                    $errors['fullname'] = get_string('collegealreadyexists', 'local_colleges');
                }
            }
        } elseif($university && !empty($shortname)){
            $existing = $DB->get_record('local_costcenter', array('parentid' => $university, 'shortname' => $data['shortname']));
            if(!empty($existing)){
                $errors['shortname'] = get_string('collegealreadyexists', 'local_colleges');
            }
        }elseif($university && !empty($fullname)){
            $existing = $DB->get_record('local_costcenter', array('parentid' => $university, 'fullname' => $data['fullname']));
            if(!empty($existing)){
                $errors['fullname'] = get_string('collegealreadyexists', 'local_colleges');
            }
        }
        /*if(!empty($existing)){
                $errors['id_shortname'] = get_string('collegealreadyexists', 'local_colleges');
            }*/
            return $errors;
        }
}

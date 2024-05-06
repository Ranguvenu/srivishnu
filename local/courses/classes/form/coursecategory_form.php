<?php
namespace local_courses\form;
use core;
use moodleform;
use context_system;
use coursecat;
require_once($CFG->dirroot . '/lib/formslib.php');
require_once($CFG->libdir.'/coursecatlib.php');
class coursecategory_form extends moodleform {
    /**
     * The form definition.
     */
    public function definition() {
        global $CFG, $DB, $USER;
        $mform = $this->_form;
        $categoryid = $this->_customdata['categoryid'];
        $parent = $this->_customdata['parent'];
        $context = context_system::instance();
        // Get list of categories to use as parents, with site as the first one.
        $options = array();
        if (has_capability('moodle/category:manage', context_system::instance()) || $parent == 0) {
            $option[0] = get_string('top');

        }
        if(is_siteadmin() || has_capability('local/costcenter:manage_multiorganizations',$context)){
            $sql = "SELECT id,name FROM {course_categories}";
            
            $options = categorylist('moodle/category:manage');
            $options = $option+$options;

        }else if(has_capability('local/costcenter:manage_ownorganization',$context)){
            $orgcategory = $DB->get_field('local_costcenter','category',array('id' => $USER->open_costcenterid));
            $options = categorylist('moodle/category:manage','','/',0,$orgcategory);
        }else if(has_capability('local/costcenter:manage_owndepartments',$context)){
            $deptcategory = $DB->get_field('local_costcenter', 'category', array('id' => $USER->open_departmentid));
            $options = categorylist('moodle/category:manage','','/',$deptcategory);
        }
        if ($categoryid) {
            // Editing an existing category.
            $options = categorylist('moodle/category:manage', $categoryid);
            if (empty($options[$parent])) {
                // Ensure the the category parent has been included in the options.
                $options[$parent] = $DB->get_field('course_categories', 'name', array('id'=>$parent));
            }
        }

        $mform->addElement('select', 'parent', get_string('parentcategory'), $options);

        $mform->addElement('text', 'name', get_string('categoryname'), array('size' => '30'));
        $mform->addRule('name', get_string('required'), 'required', null);
        $mform->setType('name', PARAM_TEXT);

        $mform->addElement('text', 'idnumber', get_string('idnumbercoursecategory'), 'maxlength="100" size="10"');
        $mform->addHelpButton('idnumber', 'idnumbercoursecategory');
        $mform->setType('idnumber', PARAM_RAW);

        $mform->addElement('editor', 'description_editor', get_string('description'), null,
            $this->get_description_editor_options());

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
        $mform->setDefault('id', $categoryid);
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
        if (!empty($data['idnumber'])) {

           $idnumber = preg_match('/^\S*$/', $idnumber); 
           if(!$idnumber){
            $errors['idnumber'] = get_string('spacesnotallowed', 'local_mooccourses');
           }
            if ($existing = $DB->get_record('course_categories', array('idnumber' => $data['idnumber']))) {
                if (!$data['id'] || $existing->id != $data['id']) {
                    $errors['idnumber'] = get_string('categoryidnumbertaken', 'error');
                }
            }
        }
        
        return $errors;
    }
     
}
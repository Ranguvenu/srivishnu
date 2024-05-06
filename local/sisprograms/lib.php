<?php
use \local_courses\action\insert as insert;
class sisprograms {

    private static $_sisprogram;
    private $dbHandle;

    private function __construct() {
        
    }

    public static function getInstance() {
        if (!self::$_sisprogram) {
            self::$_sisprogram = new sisprograms();
        }
        return self::$_sisprogram;
    }


    /**
     * @method createtabview
     * @todo provides the tab view
     * @param  currenttab(string)
     * */
    function createtabview($currenttab, $id = -1) {
        global $OUTPUT;
        $systemcontext = context_system::instance();
        $tabs = array();

        $tabs[] = new tabobject('view', new moodle_url('/local/sisprograms/index.php'), get_string('report', 'local_sisprograms'));
        $tabs[] = new tabobject('courses', new moodle_url('/local/sisprograms/courses.php'), get_string('coursesreport', 'local_sisprograms'));
        $tabs[] = new tabobject('upload', new moodle_url('/local/sisprograms/upload.php'), get_string('uploadcourses', 'local_sisprograms'));
        $tabs[] = new tabobject('uploadenrolment', new moodle_url('/local/sisprograms/uploadusers.php'), get_string('uploadenrolments', 'local_sisprograms'));
        $tabs[] = new tabobject('syncerrors', new moodle_url('/local/sisprograms/sync_errors.php'), get_string('sync_errors', 'local_sisprograms'));
        $tabs[] = new tabobject('masterdatausers', new moodle_url('/local/sisprograms/masterdatausers.php'), get_string('usersdata', 'local_sisprograms'));
        echo $OUTPUT->tabtree($tabs, $currenttab);
    }

    /**
     * @method  program_capabilities
     * @return array capabilities list
     * */
    function sisprogram_capabilities($unsetlist = null) {
        global $DB, $CFG;
        $capabilities_array = array('local/sisprograms:manage', 'local/sisprograms:delete', 'local/sisprograms:update', 'local/sisprograms:visible', 'local/sisprograms:view', 'local/sisprograms:create');
        if ($unsetlist) {
            foreach ($unsetlist as $key => $value)
                $updatedunsetlist[] = 'local/sisprograms:' . $value;
            $capabilities_array = array_diff($capabilities_array, $updatedunsetlist);
        }

        return $capabilities_array;
    }// end of function

    /*insert moodle course for online siscourse*/
    function moodlecourse_create($coursedata){
        global $CFG,$PAGE,$USER,$DB;
        require_once($CFG->dirroot.'/course/lib.php');

        $categoryid = $coursedata->category;
        $category = $DB->get_record('course_categories', array('id' => $categoryid), '*', MUST_EXIST);
        $catcontext = context_coursecat::instance($category->id);

        $sortorderlist = $DB->get_records('course', array('category' => $category->id));
        foreach ($sortorderlist as $sortlist)
            $sortorder = $sortlist->sortorder;
        if ($sortorder)
            $sortorder = $sortorder++;
        else
            $sortorder = 0;
        $data = new stdClass();
        $data->category = $category->id;
        $data->sortorder = $sortorder;
        $data->fullname = $coursedata->fullname;
        $data->shortname = $coursedata->shortname;
        $data->idnumber = 0;
        $data->summary = '';
        $data->summaryformat = 1;
        $data->format = 'weeks';
        $data->showgrades = 1;
        //$data->sectioncache=' ';
        //$data->modinfo=' ';
        $data->newsitems = 5;
        $data->startdate = time();
        $data->marker = 0;
        $data->maxbytes = 0;
        $data->legacyfiles = 0;
        $data->showreports = 0;
        $data->visible = 1;
        $data->visibleold = 1;
        $data->groupmode = 0;
        $data->groupmodeforce = 0;
        $data->defaultgroupingid = 0;
        $data->lang = '';
        $data->theme = '';
        $data->timecreated = time();
        $data->timemodified = time();
        $data->requested = 0;
        $data->enablecompletion = 1;
        $data->completionnotify = 0;
        $data->coursetype = 0;
        $courseid = create_course($data);
        $coursedata = $courseid;
        insert::add_enrol_meathod_tocourse($courseid,$enrol_status);
        return $courseid;
    }


    /*universitycode withe university creation*/
    function university_creation($school){
        global $CFG,$PAGE,$USER,$DB;

        require_once($CFG->dirroot.'/local/lib.php');
        $hierarchy = new hierarchy();
        $school->depth = 1;
        $school->path = '';
    
        /* ---get next child item that need to provide--- */
        if (!$sortorder = $hierarchy->get_next_child_sortthread($school->parentid, 'local_costcenter')) {
            return false;
        }

        $school->sortorder = $sortorder;
        $schools = $DB->insert_record('local_costcenter', $school);
        return $schools;
    }

    /*universitycode with the course category creation*/
    function coursecategory_creation($category){
        global $CFG,$PAGE,$USER,$DB;
        require_once($CFG->dirroot.'/local/lib.php');
        $hierarchy = new hierarchy();
        $category->depth = 1;
        $category->path = '';
    
        /* ---get next child item that need to provide--- */
        if (!$sortorder = $hierarchy->get_next_child_sortthread($category->parent, 'course_categories')) {
            return false;
        }

        $category->sortorder = $sortorder;
        $categories = $DB->insert_record('course_categories', $category);
        return $categories;
    }


    /*sync error preparing errors object function*/
    function syncerrors_preparingobject($enrollment,$errors,$mfields){
        global $CFG,$PAGE,$USER,$DB;
        $syncerrors = new stdclass();
        $syncerrors->date_created = time();
        $errors_list = implode(',',$errors);
        $mandatory_list = implode(',',$mfields);
        $syncerrors->error = $errors_list;
        $syncerrors->modified_by = $USER->id;
        $syncerrors->mandatory_fields = $mandatory_list;
        if (empty($enrollment->email))
            $syncerrors->email = '-';
        else
            $syncerrors->email = $enrollment->email;
                
        if (empty($enrollment->student_prn))
            $syncerrors->idnumber = '-';
        else
            $syncerrors->idnumber =$enrollment->student_prn;

        $syncerrors->firstname =$enrollment->firstname;
        $syncerrors->lastname =$enrollment->lastname;
        $id = $DB->insert_record('local_sissyncerrors', $syncerrors);
        return $id;
    }


}// end of class
    /*function local_sisprograms_leftmenunode(){
        global $USER, $DB;
        $systemcontext = context_system::instance();
        $sisprogramsnode = '';
         if(is_siteadmin() || has_capability('local/costcenter:manage_multiorganizations',$systemcontext) || has_capability('local/costcenter:manage', $systemcontext)) {
            $sisprogramsnode .= html_writer::start_tag('li', array('id'=> 'id_leftmenu_users', 'class'=>'pull-left user_nav_div users row-fluid  dropdown-item'));
                $sisprograms_url = new moodle_url('/local/sisprograms/upload.php');
                $sisprograms = html_writer::link($sisprograms_url, '<i class="fa fa-database" aria-hidden="true"></i><span class="user_navigation_link_text">'.get_string('managemasterdata','local_sisprograms').'</span>',array('class'=>'user_navigation_link'));
                $sisprogramsnode .= $sisprograms;
            $sisprogramsnode .= html_writer::end_tag('li');
        }
        return array('18' => $sisprogramsnode);
    }*/// Commented by harish for hiding Master program functionality
?>

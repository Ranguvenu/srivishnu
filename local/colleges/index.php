<?php
require_once('../../config.php');
// use local_boards\form\filters_form;
global $CFG, $OUTPUT,$PAGE;
require_once($CFG->dirroot.'/course/lib.php');
require_once($CFG->libdir.'/coursecatlib.php');
require_login();
$PAGE->requires->jquery();
$PAGE->requires->js_call_amd('theme_epsilon/quickactions', 'quickactionsCall');
$PAGE->requires->js_call_amd('local_costcenter/costcenterdatatables', 'costcenterDatatable', array());
$categoryid = optional_param('categoryid', 0, PARAM_INT);
$page         = optional_param('page', 0, PARAM_INT);
$perpage      = optional_param('perpage', 10, PARAM_INT);   
$visible = optional_param('visible', -1, PARAM_INT);
$url = new moodle_url('/local/colleges/index.php');
$systemcontext = $context = context_system::instance();
if ($categoryid) {
    $category = coursecat::get($categoryid);
    $context = context_coursecat::instance($category->id);
    $url->param('categoryid', $category->id);
}else{
    $category = coursecat::get_default();
    $categoryid = $category->id;
    $context = context_coursecat::instance($category->id);
    $url->param('categoryid', $category->id);
}
$PAGE->set_context($context);
$PAGE->set_url($url);
$PAGE->set_pagelayout('admin');
$PAGE->set_title(get_string('leftmenu_browsecolleges',  'local_colleges'));
$PAGE->set_heading(get_string('leftmenu_browsecolleges','local_colleges'));
$PAGE->requires->js_call_amd('local_colleges/newcollege', 'load');
$PAGE->requires->js_call_amd('local_departments/newdepartment', 'load');
$PAGE->requires->js_call_amd('local_colleges/deletecollege', 'load');
$PAGE->requires->js('/local/colleges/js/custom.js',true);

$capabilities = array(
    'moodle/site:config',
    'moodle/backup:backupcourse',
    'moodle/category:manage',
    'moodle/course:create',
    'moodle/site:approvecourse'
);
if ($category && !has_any_capability($capabilities, $systemcontext)) {
    // If the user doesn't poses any of these system capabilities then we're going to mark the manage link in the settings block
    // as active, tell the page to ignore the active path and just build what the user would expect.
    // This will at least give the page some relevant navigation.
    navigation_node::override_active_url(new moodle_url('/local/courses/index.php', array('categoryid' => $category->id)));
    $PAGE->set_category_by_id($category->id);
    $PAGE->navbar->ignore_active(true);
    $PAGE->navbar->add(get_string('coursemgmt', 'admin'), $PAGE->url->out_omit_querystring());
} else {
    // If user has system capabilities, make sure the "Manage courses and categories" item in Administration block is active.
    navigation_node::require_admin_tree();
    navigation_node::override_active_url(new moodle_url('/local/courses/index.php'));
}
if($category !== null){
    $parents = coursecat::get_many($category->get_parents());
    $parents[] = $category;
    foreach ($parents as $parent) {
        $PAGE->navbar->add(
            get_string('leftmenu_browsecolleges','local_colleges')
        );
    }
}
echo $OUTPUT->header();
if(is_siteadmin() ||
     has_capability('moodle/category:manage', $systemcontext) || has_capability('local/costcenter:manage_ownorganization',$systemcontext)){
echo '<ul class="course_extended_menu_list">
        <li>
            <div class="coursebackup course_extended_menu_itemcontainer">
                <a id="extended_menu_createcategories" data-action="createcategorymodal"
                class="course_extended_menu_itemlink"
                onclick = "(function(e){ require(\'local_colleges/newcollege\').init({selector:\'createcategorymodal\',
                    contextid:1, id:0,underuniversity:0}) })(event)"
                title="'.get_string('createcollege','local_colleges').'"><span class="createicon"><i class="icon fa fa-university" aria-hidden="true" aria-label=""></i><i class="fa fa-plus createiconchild fa fa-plus" aria-hidden="true"></i></span></a>
            </div>
        </li>
    </ul>';
}
echo '<div class="col-12 page-desc"><b class="page-hrdesc">Description:</b><br>
The page lists various Colleges under a university/universities. Filters provided below can be applied to filter Colleges by university.
</div>';
if($categoryid > 0 && $visible != -1){
        $dataobject=new stdClass();
        $dataobject->id=$categoryid;
        $dataobject->visible=$visible;
        $DB->update_record('course_categories', $dataobject);
        $DB->execute('UPDATE {course} SET visible = ' .
                        $visible . ',visibleold= ' .
                        $visible . ' WHERE category = ' .
                    $categoryid. '');
        redirect(new moodle_url('/local/courses/index.php'));
}

// filters_form();
require_once($CFG->dirroot . '/local/courses/filters_form.php');
if(is_siteadmin() && has_capability('local/costcenter:manage_multiorganizations', $systemcontext)){
    $mform = new filters_form(null, array('filterlist'=>array('organizations','subcollege'), 'courseid' => 1, 'enrolid' => 0,'plugins'=>array('users','costcenter'),'action' => 'user_enrolment'));
   
}else if(has_capability('local/costcenter:manage_ownorganization',$systemcontext)){
    $mform = new filters_form(null, array('filterlist'=>array('subcollege'), 'courseid' => 1, 'enrolid' => 0,'plugins'=>array('users','costcenter'),'action' => 'user_enrolment'));
}
 if($mform->is_cancelled()){
        $filterdata = null;
        redirect(new moodle_url('/local/colleges/index.php'));
    }else{
        $filterdata =  $mform->get_data();
    }
    if($filterdata){
        $collapse = false;
    } else{
        $collapse = true;
    }

    $heading = '<span class="filter-lebel">'.get_string('filters').'</span>';
    print_collapsible_region_start(' ', 'filters_form', ' '.' '.$heading, false, $collapse);
    $mform->display();
    print_collapsible_region_end();

$output = $PAGE->get_renderer('local_colleges');
echo $output->get_colleges($filterdata,$page, $perpage);

$categorycontext = context_coursecat::instance($categoryid);


echo $OUTPUT->footer();

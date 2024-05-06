<?php
require_once('../../config.php');
global $CFG, $OUTPUT,$PAGE;
require_once($CFG->dirroot.'/course/lib.php');
require_once($CFG->libdir.'/coursecatlib.php');
require_login();
$PAGE->requires->jquery();
$PAGE->requires->js_call_amd('theme_epsilon/quickactions', 'quickactionsCall');
$PAGE->requires->js('/local/courses/js/custom.js');
$categoryid = optional_param('categoryid', 0, PARAM_INT);
$visible = optional_param('visible', -1, PARAM_INT);
$url = new moodle_url('/local/courses/index.php');
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
$PAGE->set_title(get_string('coursecat','local_courses'));
$PAGE->set_heading(get_string('leftmenu_browsecategories','local_courses'));
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
            get_string('leftmenu_browsecategories','local_courses')
        );
    }
}
echo $OUTPUT->header();
if(is_siteadmin() ||
     has_capability('moodle/category:manage', $systemcontext)){
echo '<ul class="course_extended_menu_list">
        <li>
            <div class="coursebackup course_extended_menu_itemcontainer">
                <a id="extended_menu_createcategories" data-action="createcategorymodal"
                class="course_extended_menu_itemlink"
                onclick = "(function(e){ require(\'local_courses/newcategory\').init({selector:\'createcategorymodal\',
                    contextid:1, categoryid:0}) })(event)"
                title="'.get_string('createcategory','local_courses').'"><span class="createicon"><i class="icon fa fa-book" aria-hidden="true" aria-label=""></i><i class="fa fa-book secbook catcreateicon" aria-hidden="true" aria-label=""></i><i class="fa fa-plus createiconchild" aria-hidden="true"></i></span></a>
            </div>
        </li>
    </ul>';
}
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

$output = $PAGE->get_renderer('local_courses');
echo $output->get_categories_list();

$categorycontext = context_coursecat::instance($categoryid);

$PAGE->requires->js_call_amd('local_courses/newcategory', 'load');
$PAGE->requires->js_call_amd('local_courses/deletecategory', 'load');
echo $OUTPUT->footer();

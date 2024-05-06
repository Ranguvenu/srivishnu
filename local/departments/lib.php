<?php
use local_departments\form;
/**
 * Serve the new course category form as a fragment.
 *
 * @param array $args List of named arguments for the fragment loader.
 * @return string
 */
function local_courses_output_fragment_department_form($args){
 global $DB,$CFG,$PAGE;
    require_once($CFG->libdir.'/coursecatlib.php');

    $args = (object) $args;
    $context = $args->context;
    $id = $args->categoryid;
    $underuniversity = $args->underuniversity;

    $o = '';

    $formdata = [];
    if (!empty($args->jsonformdata)) {
        $serialiseddata = json_decode($args->jsonformdata);
        parse_str($serialiseddata, $formdata);
    }

    if($id > 0 && empty($formdata)){
        $data = $DB->get_record('local_costcenter', array('id'=>$id));
        $data->description_editor['text'] = $data->description;
        $data->description_editor['format'] = 1;
    }
    $editoroptions = [
        'maxfiles' => EDITOR_UNLIMITED_FILES,
        'maxbytes' => $course->maxbytes,
        'trust' => false,
        'context' => $context,
        'noclean' => true,
        'subdirs' => false
    ];

    $group = file_prepare_standard_editor($group, 'description_editor', $editoroptions, $context, 'group', 'description', null);

    $params = array(
        'editoroptions' => $editoroptions,
    // 'id' => $data->id,
    'id' => $id,
    'parentid' => $data->parentid,
    'faculty' => $data->faculty,
    'context' => $context,
    'underuniversity' => $underuniversity
    );
// print_object($data);exit;
    $mform = new local_departments\form\department_form(null, $params, 'post', '', null, true, $formdata);
    // Used to set the courseid.
    $mform->set_data($data);

    if (!empty($args->jsonformdata)) {
        // If we were passed non-empty form data we want the mform to call validation functions and show errors.
        $mform->is_validated();
    }

    ob_start();
    $mform->display();
    $o .= ob_get_contents();
    ob_end_clean();

    return $o;
}

function local_departments_leftmenunode(){
    global $USER, $DB;
    $systemcontext = context_system::instance();
    $departmentsnode = '';
     if(has_capability('local/costcenter:manage_multiorganizations',$systemcontext) || is_siteadmin() || has_capability('local/costcenter:manage_ownorganization',$systemcontext)) {
        $departmentsnode .= html_writer::start_tag('li', array('id'=> 'id_leftmenu_users', 'class'=>'pull-left user_nav_div users dropdown-item'));
            $departments_url = new moodle_url('/local/departments/index.php');
            $departments = html_writer::link($departments_url, '<i class="fa fa-building-o" aria-hidden="true"></i><span class="user_navigation_link_text">'.get_string('managedepartments','local_departments').'</span>',array('class'=>'user_navigation_link'));
            $departmentsnode .= $departments;
        $departmentsnode .= html_writer::end_tag('li');
    }
    return array('4' => $departmentsnode);
}

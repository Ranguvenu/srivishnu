<?php
use local_colleges\form;
/**
 * Serve the new course category form as a fragment.
 *
 * @param array $args List of named arguments for the fragment loader.
 * @return string
 */
function local_courses_output_fragment_college_form($args){
 global $DB,$CFG,$PAGE;
    require_once($CFG->libdir.'/coursecatlib.php');
    // print_object($args);exit;
    $args = (object) $args;
    $context = $args->context;
    $collegeid = $args->id;
    $underuniversity = $args->underuniversity;
    $o = '';

    $formdata = [];
    if (!empty($args->jsonformdata)) {
        $serialiseddata = json_decode($args->jsonformdata);
        parse_str($serialiseddata, $formdata);
    }
    
    if($collegeid > 0 && empty($formdata)){
        $data = $DB->get_record('local_costcenter', array('id'=>$args->id));
        $formdata = new stdClass();
        $formdata->id = $data->id;
        $formdata->university = $data->parentid;
        $formdata->fullname = $data->fullname;
        $formdata->shortname = $data->shortname;
        $formdata->catid = $data->catid;
        $formdata->description['text'] = $data->description;
    }

    $params = array(
    // 'id' => $data->id,
    'collegeid' => $collegeid,
    'university' => $data->parentid,
    'categoryname' => $data->fullname,
    // 'parent' => $category->parent,
    'context' => $context,
    'itemid' => $itemid,
    'underuniversity' => $underuniversity
    );
// print_object($params);exit;
    $mform = new local_colleges\form\college_form(null, $params, 'post', '', null, true, $formdata);
    // Used to set the courseid.
    $mform->set_data($formdata);

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

function local_colleges_leftmenunode(){
    global $USER, $DB;
    $systemcontext = context_system::instance();
    $collegesnode = '';
     if(has_capability('local/costcenter:manage_multiorganizations',$systemcontext) || is_siteadmin() || has_capability('local/costcenter:manage_ownorganization',$systemcontext)) {
        $collegesnode .= html_writer::start_tag('li', array('id'=> 'id_leftmenu_users', 'class'=>'pull-left user_nav_div users dropdown-item'));
            $colleges_url = new moodle_url('/local/colleges/index.php');
            $colleges = html_writer::link($colleges_url, '<i class="fa fa-university" aria-hidden="true"></i><span class="user_navigation_link_text">'.get_string('managecolleges','local_colleges').'</span>',array('class'=>'user_navigation_link'));
            $collegesnode .= $colleges;
        $collegesnode .= html_writer::end_tag('li');
    }
    return array('5' => $collegesnode);
}

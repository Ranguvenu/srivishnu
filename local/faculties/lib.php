<?php
function local_faculties_output_fragment_new_facultyform($args){
	global $DB;
	$args = (object) $args;

    $context = $args->context;
    $facultyid = $args->facultyid;
    // $parentid = $args->parentid;
    $o = '';
    $formdata = [];
    if (!empty($args->jsonformdata)) {
        $serialiseddata = json_decode($args->jsonformdata);
        parse_str($serialiseddata, $formdata);
    }
   
 	if($facultyid){
 		$data = $DB->get_record('local_faculties',array('id' => $facultyid));
        $description = $data->description;
        $data->description = array();
        $data->description['text'] = $description;
        $data->description['format'] = 1;
 		$mform = new local_faculties\form\createfaculty_form(null, (array) $data, 'post', '', null, true, $formdata);
 		$mform->set_data($data);
 	}else{
    $mform = new local_faculties\form\createfaculty_form(null, array(), 'post', '', null, true, $formdata);
    }
    if (!empty($formdata)) {
        // If we were passed non-empty form data we want the mform to call validation functions and show errors.
        $mform->is_validated();
    }
 
    ob_start();
    $mform->display();
    $o .= ob_get_contents();
    ob_end_clean();
    return $o;
}
//Added by Yamini
function faculties_filter($mform){

      global $DB,$USER;
    $systemcontext = context_system::instance();
    $sql = "SELECT id, facultyname FROM {local_faculties} ";
    $sql .= " ORDER BY facultyname";
    $facultylist = $DB->get_records_sql_menu($sql);   
    $select = $mform->addElement('autocomplete', 'faculties', '', $facultylist, array('placeholder' => get_string('faculties','local_departments')));
    $mform->setType('faculties', PARAM_RAW);
    $select->setMultiple(true);
}

function local_faculties_leftmenunode(){
    global $USER, $DB;
    $systemcontext = context_system::instance();
    $facultiesnode = '';
     if(has_capability('local/costcenter:manage_multiorganizations',$systemcontext) || is_siteadmin() || has_capability('local/costcenter:manage', $systemcontext)) {
        $facultiesnode .= html_writer::start_tag('li', array('id'=> 'id_leftmenu_users', 'class'=>'pull-left user_nav_div users dropdown-item'));
            $faculties_url = new moodle_url('/local/faculties/index.php');
            $faculties = html_writer::link($faculties_url, '<i class="fa fa-cubes" aria-hidden="true"></i><span class="user_navigation_link_text">'.get_string('faculties','local_faculties').'</span>',array('class'=>'user_navigation_link'));
            $facultiesnode .= $faculties;
        $facultiesnode .= html_writer::end_tag('li');
    }
// <mallikarjun> - ODL-774 remove faculty -- starts
//    return array('3' => $facultiesnode);
// <mallikarjun> - ODL-774 remove faculty -- ends
}
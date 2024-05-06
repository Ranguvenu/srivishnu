<?php
function local_boards_output_fragment_new_boardform($args){
	global $DB;
	$args = (object) $args;

    $context = $args->context;
    $boardid = $args->boardid;
    $o = '';
    $formdata = [];
    if (!empty($args->jsonformdata)) {
        $serialiseddata = json_decode($args->jsonformdata);
        parse_str($serialiseddata, $formdata);
    }
   
 	if($boardid){
 		$data = $DB->get_record('local_boards',array('id' => $boardid));
        $description = $data->description;
        $data->description = array();
        $data->description['text'] = $description;
        $data->description['format'] = 1;
        $mform = new local_boards\form\createboard_form(null, (array) $data, 'post', '', null, true, $formdata);
 		$mform->set_data($data);
 	}else{
    $mform = new local_boards\form\createboard_form(null, array(), 'post', '', null, true, $formdata);
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

/*function local_boards_leftmenunode(){
    global $USER, $DB;
    $systemcontext = context_system::instance();
    $boardsnode = '';
     if(has_capability('local/costcenter:manage_multiorganizations',$systemcontext) || is_siteadmin() || has_capability('local/costcenter:manage', $systemcontext)) {
        $boardsnode .= html_writer::start_tag('li', array('id'=> 'id_leftmenu_users', 'class'=>'pull-left user_nav_div users row-fluid  dropdown-item'));
            $boards_url = new moodle_url('/local/boards/index.php');
            $boards = html_writer::link($boards_url, '<i class="fa fa-clipboard" aria-hidden="true"></i><span class="user_navigation_link_text">'.get_string('pluginname','local_boards').'</span>',array('class'=>'user_navigation_link'));
            $boardsnode .= $boards;
        $boardsnode .= html_writer::end_tag('li');
    }
    return array('2' => $boardsnode);
}*/// Commented by Harish for hiding boards functionality

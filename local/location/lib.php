<?php

/**
 * This file is part of eAbyas
 *
 * Copyright eAbyas Info Solutons Pvt Ltd, India
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author eabyas  <info@eabyas.in>
 * @package BizLMS
 * @subpackage local_location
 */


defined('MOODLE_INTERNAL') or die;
/*
 *  @method institute output fragment
 *  @param $args
 */
function local_location_output_fragment_new_instituteform($args) {
	global $CFG, $DB;

	$args = (object) $args;
	$context = $args->context;
	$instituteid = $args->instituteid;
	$o = '';
	$formdata = [];
	if (!empty($args->jsonformdata)) {
		$serialiseddata = json_decode($args->jsonformdata);
		parse_str($serialiseddata, $formdata);
	}

	if ($args->instituteid > 0) {
		$heading = 'Update institute';
		$collapse = false;
		$data = $DB->get_record('local_location_institutes', array('id' => $instituteid));
	}
	$editoroptions = [
		'maxfiles' => EDITOR_UNLIMITED_FILES,
		'maxbytes' => $course->maxbytes,
		'trust' => false,
		'context' => $context,
		'noclean' => true,
		'subdirs' => false,
	];
	$group = file_prepare_standard_editor($group, 'description', $editoroptions, $context, 'group', 'description', null);

	$mform = new local_location\form\instituteform(null, array('instituteid' => $instituteid,'editoroptions' => $editoroptions, 'costcenter' => $data->costcenter), 'post', '', null, true, $formdata);

	$mform->set_data($data);

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
/*
 *  @method room output fragment
 *  @param $args
 */
function local_location_output_fragment_new_roomform($args) {
	global $CFG, $DB;

	$args = (object) $args;
	$context = $args->context;
	$roomid = $args->roomid;
	$o = '';
	$formdata = [];
	if (!empty($args->jsonformdata)) {
		$serialiseddata = json_decode($args->jsonformdata);
		parse_str($serialiseddata, $formdata);
	}

	if ($args->roomid > 0) {
		$heading = 'Update room';
		$collapse = false;
		$data = $DB->get_record('local_location_room', array('id' => $roomid));
	}
	$editoroptions = [
		'maxfiles' => EDITOR_UNLIMITED_FILES,
		'maxbytes' => $course->maxbytes,
		'trust' => false,
		'context' => $context,
		'noclean' => true,
		'subdirs' => false,
	];
	$group = file_prepare_standard_editor($group, 'description', $editoroptions, $context, 'group', 'description', null);
	// print_object($data);exit;
	$mform = new local_location\form\roomform(null, array('editoroptions' => $editoroptions,'id' => $roomid, 'location' => $data->instituteid, 'costcenter' => $data->costcenter, 'subcostcenter' => $data->subcostcenter), 'post', '', null, true, $formdata);
	$data->location = $data->instituteid;

	$mform->set_data($data);

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
function find_locations($costcenter, $subcostcenter = null){
	global $DB;
    if($costcenter) {           
            $institues = "select id,fullname from {local_location_institutes} where costcenter = $costcenter AND visible = 1";
            if($subcostcenter){
            	$institues .= " AND subcostcenter = $subcostcenter";
            }
            $institue = $DB->get_records_sql($institues);
            return $costcenter =  $institue;
        }else {
            return $costcenter;
        }

}

function find_univcolleges($costcenter){
	global $DB;
    if($costcenter) {           
            $collegessql = "select id,fullname from {local_costcenter} where univ_dept_status IS NOT NULL AND parentid = $costcenter AND visible = 1";
            $colleges = $DB->get_records_sql($collegessql);
            return $costcenter =  $colleges;
        }else {
            return $costcenter;
        }

}

function local_location_leftmenunode(){
    global $USER, $DB;
    $systemcontext = context_system::instance();
    $locationsnode = '';
     if(has_capability('local/costcenter:manage_multiorganizations',$systemcontext) || is_siteadmin() || has_capability('local/costcenter:manage_ownorganization',$systemcontext) || has_capability('local/costcenter:manage_owndepartments',$systemcontext)) {
        $locationsnode .= html_writer::start_tag('li', array('id'=> 'id_leftmenu_users', 'class'=>'pull-left user_nav_div users dropdown-item'));
            $locations_url = new moodle_url('/local/location/index.php');
            $locations = html_writer::link($locations_url, '<i class="fa fa-map-marker" aria-hidden="true"></i><span class="user_navigation_link_text">'.get_string('managelocations','local_location').'</span>',array('class'=>'user_navigation_link'));
            $locationsnode .= $locations;
        $locationsnode .= html_writer::end_tag('li');
    }
    $roomsnode = '';
    if(has_capability('local/location:manageroom',$systemcontext) || is_siteadmin() || has_capability('local/costcenter:manage_ownorganization',$systemcontext) || has_capability('local/costcenter:manage_owndepartments',$systemcontext)) {
        $roomsnode .= html_writer::start_tag('li', array('id'=> 'id_leftmenu_users', 'class'=>'pull-left user_nav_div users dropdown-item'));
            $rooms_url = new moodle_url('/local/location/room.php');
            $rooms = html_writer::link($rooms_url, '<i class="fa fa-simplybuilt" aria-hidden="true"></i><span class="user_navigation_link_text">'.get_string('managerooms','local_location').'</span>',array('class'=>'user_navigation_link'));
            $roomsnode .= $rooms;
        $roomsnode .= html_writer::end_tag('li');
    }
    return array('10' => $locationsnode, '11' => $roomsnode);
}

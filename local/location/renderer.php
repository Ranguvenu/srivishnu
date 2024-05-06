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

require_once(dirname(__FILE__) . '/../../config.php');

class local_location_renderer extends plugin_renderer_base {
/*
 *  @method Display institutes
 *  @return institutes information
 */
    public function display_institutes($filterdata) {
    	global $DB, $CFG, $OUTPUT,$USER, $PAGE;
        $params = array();
        $context = context_system::instance();
    	$sql = "SELECT * FROM {local_location_institutes} where 1=1 ";
        if (has_capability('local/costcenter:manage_ownorganization', context_system::instance()) && !is_siteadmin()) {
            $sql .= " AND costcenter = :costcenter";
            $params['costcenter'] = $USER->open_costcenterid;
            // $params['usercreated'] = $USER->id;
        }elseif(has_capability('local/costcenter:manage_owndepartments', context_system::instance()) && !is_siteadmin()){
            $sql .= " AND costcenter = :costcenter AND subcostcenter = :subcostcenter";
            $params['costcenter'] = $USER->open_costcenterid;
            $params['subcostcenter'] = $USER->open_departmentid;
            // $params['usercreated'] = $USER->id;
        }
        if($filterdata->organizations){
               $organizations = implode(',',$filterdata->organizations);
               $sql .= " AND costcenter IN ($organizations)";
        }
        if($filterdata->departments){
            if($filterdata->organizations)
            {
               $organizations = implode(',',$filterdata->organizations);
               $colleges = implode(',',$filterdata->departments);
               $sql .= " AND costcenter IN ($organizations) AND subcostcenter IN ($colleges)";
            }
            else{
               $colleges = implode(',',$filterdata->departments);
               $sql .= " AND subcostcenter IN ($colleges)";
            }
        }
        // elseif($USER->open_costcenterid)
        $sql .= " ORDER BY id DESC ";
        
    	$institutes = $DB->get_records_sql($sql,$params);
    	$table = new html_table();
		$table->id = 'local_institutes';
        $table->attributes['class'] = 'generaltable';
        if (is_siteadmin()) {        
            $table->head = [get_string('institute_name', 'local_location'),
                            get_string('institutetype', 'local_location'),
                            get_string('university', 'local_location'),
                            get_string('college', 'local_location'),
                            get_string('address', 'local_location')];
        }else if (has_capability('local/costcenter:manage_ownorganization', context_system::instance()) && !is_siteadmin()) {        
            $table->head = [get_string('institute_name', 'local_location'),
    						get_string('institutetype', 'local_location'),
                            get_string('college', 'local_location'),
    						get_string('address', 'local_location')];
        }else if (has_capability('local/costcenter:manage_owndepartments', context_system::instance()) && !is_siteadmin()) {        
            $table->head = [get_string('institute_name', 'local_location'),
                            get_string('institutetype', 'local_location'),
                            get_string('address', 'local_location')];
        }
            if ((has_capability('local/location:manageinstitute', context_system::instance()))) {
		      $table->head[] =get_string('actions');
            }

		$table->align = array('' ,'center', 'center', 'center');
        // print_object($institutes);exit;
        if ($institutes) {
            foreach ($institutes as $institute) {
            $id = $institute->id;
             if ((has_capability('local/location:manageinstitute', context_system::instance()))) {
                $actions = html_writer::link('javascript:void(0)', '<i class="fa fa-cog fa-fw" title="Edit"></i>', array('data-action' => 'createinstitutemodal', 'class'=>'createinstitutemodal', 'data-value'=>$institute->id, 'class' => '', 'onclick' =>"(function(e){ require(\"local_location/newinstitute\").init({selector:\"createinstitutemodal\", contextid:1, instituteid:$institute->id}) })(event)",'style'=>'cursor:pointer' , 'title' => 'edit'));

                $locations_countinsessions = $DB->count_records('local_cc_course_sessions', array('instituteid' => $institute->id));
                $actions .= html_writer::link('javascript:void(0)', '<i class="fa fa-trash fa-fw" aria-hidden="true" title="Delete" aria-label="Delete"></i>', array('title' => get_string('delete'), 'onclick' => '(function(e){ require("local_location/newinstitute").deleteConfirm({id:'.$institute->id.',context:'.$context->id.', fullname:"'.$institute->fullname.'", actionstatus:\'Confirmation\', count:'.$locations_countinsessions.', actionstatusmsg:\'Are you sure, you really want to delete this <b>'.$institute->fullname.'?\'}) })(event)'));
            }else{
                $actions="";
            }
          //  $costcenter = $DB->get_record_sql('SELECT cc.fullname FROM {local_costcenter} cc JOIN {local_location_institutes} lli ON cc.id = lli.costcenter WHERE lli.id = '.$room->instituteid);
                $room->costcenter = $DB->get_field('local_costcenter','fullname',array('id' => $institute->costcenter));
                $room->subcostcenter = $DB->get_field('local_costcenter','fullname',array('id' => $institute->subcostcenter));
                $subcostcenter = ($room->subcostcenter) ? $room->subcostcenter : 'N/A';
                if($institute->institute_type == 1){
                    $institute->institute_type = get_string('internal','local_location');
                }else{
                    $institute->institute_type = get_string('external','local_location');
                }
                if ((has_capability('local/location:manageinstitute', context_system::instance()))) {
                    if (has_capability('local/costcenter:manage_ownorganization', context_system::instance()) && !is_siteadmin()) {
                        $table->data[] = [$institute->fullname, $institute->institute_type, $subcostcenter, $institute->address, $actions];
                    }else if (has_capability('local/costcenter:manage_owndepartments', context_system::instance()) && !is_siteadmin()) {
                        $table->data[] = [$institute->fullname, $institute->institute_type, $institute->address, $actions];
                    }else{
                        $table->data[] = [$institute->fullname, $institute->institute_type, $room->costcenter, $subcostcenter, $institute->address, $actions];
                    }
                }else{
                    $table->data[] = [$institute->fullname, $institute->institute_type,$room->costcenter, $subcostcenter, $institute->address];
                }
            }
        }else{
            $table->data = "No records found";
        }
            $institutestable =  html_writer::table($table);
            $institutestable .= html_writer::script('$(document).ready(function() {
								                       	$("#local_institutes").dataTable({
											                "searching": true,
											                "responsive": true,
											                "processing": true,
											                "lengthMenu": [[10, 25,50,100, -1], [10,25, 50,100, "All"]],
											                "language": {
											                    "emptyTable": "No Locations available in table",
											                    "paginate": {
											                        "previous": "<",
											                        "next": ">"
											                    },
									                    	"sProcessing": "<img src= "+ M.cfg.wwwroot + "/local/ajax-loader.svg />"
									                		},
											                "aaSorting": [],
											                "pageLength": 10,
								                        });
								                    });');
        /*} else
            $institutestable = get_string('no_institutes','local_location');*/

			return $institutestable;

    }
/*
 *  @method Display rooms
 *  @return rooms display
 */
     public function display_rooms($filterdata, $page, $perpage) {
    	global $DB, $CFG, $OUTPUT,$USER, $PAGE;
    	$params=array();
        $context = context_system::instance();
    	$sql = "SELECT lcr.*,lci.fullname FROM {local_location_room} as lcr
                JOIN {local_location_institutes} as lci on lci.id=lcr.instituteid WHERE 1=1 ";
        if (has_capability('local/costcenter:manage_ownorganization', context_system::instance()) && !is_siteadmin()) {
            $sql .= " AND lcr.costcenter = :costcenter";
            $params['costcenter'] = $USER->open_costcenterid;
            // $params['usercreated'] = $USER->id;
        }elseif(has_capability('local/costcenter:manage_owndepartments', context_system::instance()) && !is_siteadmin()){
            $sql .= " AND lcr.costcenter = :costcenter AND lcr.subcostcenter = :subcostcenter";
            $params['costcenter'] = $USER->open_costcenterid;
            $params['subcostcenter'] = $USER->open_departmentid;
            // $params['usercreated'] = $USER->id;
        }   
       
        if($filterdata->organizations){
               $organizations = implode(',',$filterdata->organizations);
               $sql .= " AND lcr.costcenter IN ($organizations)";
        }
        if($filterdata->departments){
            if($filterdata->organizations)
            {
               $organizations = implode(',',$filterdata->organizations);
               $colleges = implode(',',$filterdata->departments);
               $sql .= " AND lcr.costcenter IN ($organizations) AND lcr.subcostcenter IN ($colleges)";
            }
            else{
               $colleges = implode(',',$filterdata->departments);
               $sql .= " AND lcr.subcostcenter IN ($colleges)";
            }
        }
        $sql .= " ORDER BY lcr.id DESC ";
    	$rooms = $DB->get_records_sql($sql,$params);
    	$table = new html_table();
		$table->id = 'local_rooms';
        $table->attributes['class'] = 'generaltable';

        if(is_siteadmin()){
            $table->head = [get_string('roomname', 'local_location'),
						get_string('institutename', 'local_location'),
						get_string('university', 'local_location'),get_string('college', 'local_location')/*, get_string('capacity', 'local_location')*/];
        }elseif(has_capability('local/costcenter:manage_ownorganization', context_system::instance()) && !is_siteadmin()){
            $table->head = [get_string('roomname', 'local_location'),
                        get_string('institutename', 'local_location'),
                        get_string('college', 'local_location')/*, get_string('capacity', 'local_location')*/];
        }elseif(has_capability('local/costcenter:manage_owndepartments', context_system::instance()) && !is_siteadmin()){
            $table->head = [get_string('roomname', 'local_location'),
                        get_string('institutename', 'local_location')/*,
                        get_string('capacity', 'local_location')*/];
        }
        if ((has_capability('local/location:manageroom', context_system::instance()))) {
			$table->head[] =	get_string('actions');
        }

		$table->align = array('' ,'center', 'center', 'center');
        if ($rooms) {
            foreach ($rooms as $room) {

            $id = $room->id;
            if ((has_capability('local/location:manageroom', context_system::instance()))) {
                /*$actions =  html_writer::empty_tag('img', array('src' => $OUTPUT->image_url('t/edit'),'title' => get_string('edit'), 'data-action' => 'createroommodal', 'class'=>'createroommodal', 'data-value'=>$id, 'class' => 'iconsmall', 'onclick' =>'(function(e){ require("local_location/newroom").init({selector:"createroommodal",contextid:1, roomid:'.$room->id.'}) })(event)'));
				$actions .= '&nbsp&nbsp';

                $actions .= html_writer::link(new moodle_url('/local/location/room.php', array('id' => $room->id, 'delete' => 1)),
                        html_writer::empty_tag('img', array('src' => $OUTPUT->image_url('t/delete'), 'title' => get_string('delete'), 'alt' => get_string('delete'))),array('id' => "delete".$room->id));
                        $confirmationmsg = "Are you sure you want to delete?";
                $PAGE->requires->event_handler("#delete".$room->id, 'click', 'M.util.moodle_location_confirm_dialog',array('message' => $confirmationmsg,'callbackargs' => array()));*/
                $actions = html_writer::link('javascript:void(0)', '<i class="fa fa-cog fa-fw" title="Edit"></i>', array('data-action' => 'createroommodal', 'class'=>'createroommodal', 'data-value'=>$room->id, 'onclick' =>"(function(e){ require(\"local_location/newroom\").init({selector:\"createroommodal\", contextid:1, roomid:$room->id}) })(event)",'style'=>'cursor:pointer' , 'title' => 'edit'));

                $rooms_countinsessions = $DB->count_records('local_cc_course_sessions', array('roomid' => $room->id));
                $actions .= html_writer::link('javascript:void(0)', '<i class="fa fa-trash fa-fw" aria-hidden="true" title="Delete" aria-label="Delete"></i>', array('title' => get_string('delete'), 'onclick' => '(function(e){ require("local_location/newroom").deleteConfirm({id:'.$room->id.',context:'.$context->id.', fullname:"'.$room->name.'", actionstatus:\'Confirmation\', count:'.$rooms_countinsessions.', actionstatusmsg:\'Are you sure, you really want to delete this <b>'.$room->name.'?\'}) })(event)'));

            }else{
                $actions="";
            }

			    $room->institute = $room->fullname;
                $costcenter = $DB->get_record_sql('SELECT cc.fullname FROM {local_costcenter} cc JOIN {local_location_institutes} lli ON cc.id = lli.costcenter WHERE lli.id = '.$room->instituteid);
                $room->costcenter = $costcenter->fullname;
                $room->subcostcenter = $DB->get_field('local_costcenter','fullname',array('id' => $room->subcostcenter));
                $subcostcenter = ($room->subcostcenter) ? $room->subcostcenter : 'N/A';
                if ((has_capability('local/location:manageroom', context_system::instance()))) {
                    if(is_siteadmin()){    
                        $table->data[] = [$room->name, $room->institute, $room->costcenter, $subcostcenter/*, $room->capacity*/, $actions];
                    }elseif(has_capability('local/costcenter:manage_ownorganization', context_system::instance()) && !is_siteadmin()){
                        $table->data[] = [$room->name, $room->institute, $subcostcenter, /*$room->capacity,*/ $actions];
                    }elseif(has_capability('local/costcenter:manage_owndepartments', context_system::instance()) && !is_siteadmin()){
                        $table->data[] = [$room->name, $room->institute, /*$room->capacity,*/ $actions];
                    }
                }else{
                    $table->data[] = [$room->name, $room->institute, $room->costcenter, $subcostcenter/*, $room->capacity*/];
                }
            }
        }else{
            $table->data = 'No records found';
        }
            $roomstable =  html_writer::table($table);
            $roomstable .= html_writer::script(' $(document).ready(function() {
                        $("#local_rooms").dataTable({
                        "searching": true,
                        "responsive": true,
                        "processing": true,
                        "lengthMenu": [[10, 25,50,100, -1], [10,25, 50,100, "All"]],
                        "language": {
                            "emptyTable": "No Rooms available in table",
                            "paginate": {
                                "previous": "<",
                                "next": ">"
                            },
                            "sProcessing": "<img src= "+ M.cfg.wwwroot + "/local/ajax-loader.svg />"
                        },
                         "aaSorting": [],
                        
                         "pageLength": 10,
                        });
                        });');
        // } else
        //     $roomstable ='<div class="p-15px"><p class="alert alert-info">'.get_string('no_institute_rooms','local_location').'</p></div>';

			return $roomstable;

    }
}

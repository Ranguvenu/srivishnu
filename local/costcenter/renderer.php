<?php

// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 *
 * @package    local
 * @subpackage costcenter
 * @copyright  2018 Eabyas Info Solutions <www.eabyas.in>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
// use core_component;
require_once($CFG->dirroot.'/local/costcenter/lib.php');
if(file_exists($CFG->dirroot.'/local/includes.php')){
    require_once($CFG->dirroot.'/local/includes.php');
}
class local_costcenter_renderer extends plugin_renderer_base {

    /**
     * @method treeview
     * @todo To add action buttons
     */
    public function departments_view() {
        global $DB, $CFG, $OUTPUT, $USER,$PAGE;
        $systemcontext = context_system::instance();
        $costcenter_instance = new costcenter;
        
         if (is_siteadmin() || has_capability('local/costcenter:manage_multiorganizations', $systemcontext)) {
           /*This query executed when the admin or capablity is allowed*/
           $sql = "SELECT distinct(s.id),s.* FROM {local_costcenter} s where parentid=0 ORDER BY s.id desc";
        } else if(has_capability('local/costcenter:view', $systemcontext)){
            $sql = "SELECT distinct(s.id),s.* FROM {local_costcenter} s where parentid=0 AND id = $USER->open_costcenterid ORDER BY s.id desc";
        } 
        $costcenters = $DB->get_records_sql($sql);
        if (!is_siteadmin() && empty($costcenters)) {
            print_error('notassignedcostcenter', 'local_costcenter');
        }
        $data = array();
        // if(!empty($costcenters)){
            foreach ($costcenters as $costcenter) {
                $line = array();
                $showdepth = 1;
                $line[] = $this->display_department_item($costcenter, $showdepth);
                $data[] = $line;
            }
            $table = new html_table();
            if (has_capability('local/costcenter:manage', $systemcontext)){
                $table->head = array('');
                $table->align = array('left');
                $table->width = '100%';
                $table->data = ($data) ? $data : get_string('norecordsfound', 'local_costcenter');
                $table->id = 'department-index';
                $output = html_writer::table($table);

            }
        /*}else{
            $output = html_writer::tag('div', get_string('noorganizationsavailable', 'local_costcenter'), array('class'=>'alert alert-info text-xs-center'));
        }*/
        return $output;
    }

    /**
     * @method display_department_item
     * @todo To display the all costcenter items
     * @param object $record is costcenter  
     * @param boolean $indicate_depth  depth for the costcenter item
     */
    public function display_department_item($record, $indicate_depth = true) {
        /*$record is costcenter record taken from local_costcenter table*/
        global $OUTPUT,$DB,$CFG,$PAGE;
        require_once($CFG->dirroot.'/local/costcenter/lib.php');
        $core_component = new core_component();
        
        $systemcontext = context_system::instance();
        $sql="SELECT id,id as id_val from {local_costcenter} where parentid='".$record->id."'";
        $id=$DB->get_records_sql_menu($sql);
        // $event_view = new local_costcenter\event\view();
        $department = count($id);
        if($department > 0){
            $dept_count_link = new moodle_url("/local/costcenter/costcenterview.php?id=".$record->id."");
        }else{
            $dept_count_link = new moodle_url("/local/costcenter/costcenterview.php?id=".$record->id."");
            
        }
        $costcentername = format_string($record->fullname);
        $dept_id=implode(',',$id);
        $subdepartment = '';
        $subsubdepartment = '';
        if($dept_id){
             $sql="select id,id as id_val from {local_costcenter} where parentid IN($dept_id);";
             $subsubid=$DB->get_records_sql_menu($sql);
             $subdepartment=count($subsubid);
             if($subdepartment > 0){
                $subdepartment = $subdepartment;
             }else{
                $subdepartment = 'N/A';
             }
        
        $sub_sub_id=implode(',',$subsubid);
        if($sub_sub_id){
             $sql="select id,id as id_val from {local_costcenter} where parentid IN($sub_sub_id);";
             $sub_sub_id=$DB->get_records_sql_menu($sql);
             $subsubdepartment=count($sub_sub_id);
             if($subsubdepartment > 0){
                $subsubdepartment = $subsubdepartment;
             }else{
                $subsubdepartment = 'N/A';
             }
            }
        }else{
            $subdepartment = 'N/A';
        }

        // //this is for all plugins count
        $pluginnavs = local_costcenter_plugins_count($record->id);
        
        $itemdepth = ($indicate_depth) ? 'depth' . min(10, $record->depth) : 'depth1';
        // @todo get based on item type or better still, don't use inline styles :-(
        $itemicon = $OUTPUT->image_url('/i/item');
        $cssclass = !$record->visible ? 'dimmed' : '';

        if (has_capability('local/costcenter:manage', $systemcontext)) {
            $id = implode(',', $id);
            $edit = true;
        
            if ($record->visible) {
                //revathi added active/inactive university starts
                // $exists = $DB->get_field('user','id',array('open_costcenterid' => $record->id));
                // if($exists){
                // $hide = false;
                // $show = false;
                // }
                // else{
                // $hide = true;
                // $show = false;
                // }

                $hide = true;
                $show = false;
                //revathi added active/inactive university ends

                $hideurl = 'javascript:void(0)';
                $showurl = 'javascript:void(0)';
            }else{
                $show = true;
                $hide = false;
                $showurl = 'javascript:void(0)';
                $hideurl = 'javascript:void(0)';

            }
            $action_message = get_string('confirmation_to_disable_'.$record->visible, 'local_costcenter', $record->fullname);
            if($department == 0 && $usercount == 0){
                $delete = true;
                $del_confirmationmsg = get_string('confirmationmsgfordel', 'local_costcenter',$record->fullname);
            }else{
                $delete = false;
                $del_confirmationmsg = '';
            }
        }
         $viewdeptContext = [
            "coursefileurl" => $OUTPUT->image_url('/course_images/courseimg', 'local_costcenter'),
            "orgname" => $costcentername,
            "orgnamecut" => strlen($costcentername) > 20 ? substr($costcentername, 0, 20)."..." : $costcentername,
            "dept_count_link" => $dept_count_link,
            "deptcount" => $department,
            "subdeptcount" => $subdepartment,
            "editicon" => $OUTPUT->image_url('t/edit'),
            "hideicon" => $OUTPUT->image_url('t/hide'),
            "showicon" => $OUTPUT->image_url('t/show'),
            "deleteicon" => $OUTPUT->image_url('t/delete'),
            "hideurl" => $hideurl,
            "showurl" => $showurl,
            "edit" => $edit,
            "hide" => $hide,
            "show" => $show,
            "action_message" => $action_message,
            "delete_message" => $del_confirmationmsg,
            "status" => $record->visible,
            "delete" => $delete,
            "id" => $id,
            "recordid" => $record->id,
            "parentid" => $record->parentid,
        ];

        $viewdeptContext = $viewdeptContext+$pluginnavs;

        return $this->render_from_template('local_costcenter/costcenter_view', $viewdeptContext);
    }

    //this is for create department buttons
    public function get_dept_view_btns($id = false) {
        global $PAGE, $USER, $DB;
        $systemcontext = context_system::instance();
        if ((is_siteadmin() || has_capability('local/costcenter:manage_multiorganizations', $systemcontext)) && $PAGE->pagetype == 'local-costcenter-index'){
            $createdeptpopup = "<a class='course_extended_menu_itemlink' data-action='createcostcentermodal' data-value='0' title = 'Create University' onclick ='(function(e){ require(\"local_costcenter/newcostcenter\").init({selector:\"createcostcentermodal\", contextid:$systemcontext->id, costcenterid:0, parentid:0}) })(event)' ><span class='createicon'><i class='fa fa-sitemap icon' aria-hidden='true'></i><i class='createiconchild fa fa-plus' aria-hidden='true'></i></span></a>";
        }else{
            $createdeptpopup = '';
        }
         if(!is_siteadmin() && has_capability('local/costcenter:create',$systemcontext)){

            $visible = $DB->get_field('local_costcenter', 'visible' , array('id' => $USER->open_costcenterid));
            
            /*if($visible){
                $createsubdeptpopup = "<a class='course_extended_menu_itemlink' data-action='createdeptmodal' data-value='0' title = '".get_string('createdepartment','local_costcenter')."' onclick ='(function(e){ require(\"local_costcenter/newsubdept\").init({selector:\"createdeptmodal\", contextid:$systemcontext->id, costcenterid:0, parentid:0,dept:1,subdept:0}) })(event)' ><i class='icon fa fa-plus-square' aria-hidden='true'></i></a>"; 
              

                $createsubsubdeptpopup = "<a class='course_extended_menu_itemlink' data-action='createsubdeptmodal' data-value='0' title = '".get_string('createsubdepartment','local_costcenter')."' onclick ='(function(e){ require(\"local_costcenter/newsubdept\").init({selector:\"createsubdeptmodal\", contextid:$systemcontext->id, costcenterid:0, parentid:0,dept:0,subdept:1}) })(event)' ><i class='icon fa fa-plus-square-o' aria-hidden='true'></i></a>"; 
            }*/
        }else{
            $createsubdeptpopup = '';
            $createsubsubdeptpopup = '';
        }
        $buttons = [
            "gridview" => new moodle_url('/local/costcenter/index.php'),
            "treeview" => new moodle_url('/local/hierarchy/index.php'),
            "createdeptpopup" => $createdeptpopup,
            "createsubdeptpopup" => $createsubdeptpopup,
            "createsubsubdeptpopup" => $createsubsubdeptpopup
        ];
    return $this->render_from_template('local_costcenter/viewbuttons', $buttons);
    }
}

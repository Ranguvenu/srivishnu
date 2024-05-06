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
 * @package   local_notifications
 * @copyright  2018 sreenivas
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_notifications\output;
defined('MOODLE_INTERNAL') || die();
use plugin_renderer_base as mainbase;

if(file_exists($CFG->dirroot . '/local/costcenter/lib.php')){
  require_once($CFG->dirroot . '/local/costcenter/lib.php');
}
/**
 * The renderer for the notifications module.
 *
 * @copyright  2018 sreenivas
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class renderer extends mainbase  {
    
     protected function render_notifications(notifications $renderable) {
      
          $data = $this->display($renderable->id, $renderable->context, $renderable->filterdata);
          $content = ['notificationslist' => $data,
                      'contextid' => $renderable->context,
                      'notificationid'=>$renderable->id
                      ];
          return $this->render_from_template('local_notifications/notifications', $content);
     }
     function display($id, $context, $filterdata) {
          global $DB, $OUTPUT, $PAGE,$USER;
          $lib = new notifications();
          $costcenter = new \costcenter();
          $systemcontext = \context_system::instance();
          if(!empty($filterdata->organizations)){
              $organizations = implode(',',$filterdata->organizations);
          if(!empty($filterdata->organizations)){
            $deptquery = array();
            foreach ($filterdata->organizations as $key => $group) {
                $deptquery[] = " FIND_IN_SET($group,ni.costcenterid) ";
            }
            $groupqueeryparams = implode('OR',$deptquery);
            $formsql = ' AND ('.$groupqueeryparams.')';
            }
          }
         
       if(is_siteadmin()){
            $sql = "SELECT ni.id, nt.name, nt.shortname, ni.subject, costcenterid,
                        ni.courses, lc.fullname as deptname, ni.active
                FROM {local_notification_info} ni
                JOIN {local_notification_type} nt ON ni.notificationid = nt.id
                JOIN {local_costcenter} lc ON ni.costcenterid = lc.id WHERE 1=1";
        } elseif(!is_siteadmin()  && has_capability('local/costcenter:manage_ownorganization', $systemcontext)){
            $costcenter = $DB->get_field_sql("select u.open_costcenterid from {user} u where u.id = $USER->id");
            $sql = "SELECT ni.id, nt.name, nt.shortname, ni.subject, costcenterid,
                        ni.courses, lc.fullname as deptname, ni.active
                FROM {local_notification_info} ni
                JOIN {local_notification_type} nt ON ni.notificationid = nt.id
                JOIN {local_costcenter} lc ON ni.costcenterid = lc.id where ni.costcenterid in ($costcenter) ";
        } else {
          print_error('You dont have permissions to view this page.');
              die();  
        }
        $formsql .= " ORDER BY ni.id DESC";
        $notifications_info = $DB->get_records_sql($sql.$formsql);
        // if($notifications_info){
            $data = array();
                       
            foreach($notifications_info as $each_notification){
                $row = array();
                //if($each_notification->courses){
                //    $r_courses = explode(',', $each_notification->courses);
                //    $notf_curses = array();
                //    foreach($r_courses as $r_course){
                //        if($each_notification->shortname == 'ilt_reminder' || $each_notification->shortname == 'ilt_feedback' ||
                //           $each_notification->shortname == 'ilt_enrol' || $each_notification->shortname == 'ilt_invitation' ||
                //           $each_notification->shortname == 'ilt_hold' || $each_notification->shortname == 'ilt_cancel' ||
                //           $each_notification->shortname == 'ilt_complete' || $each_notification->shortname == 'ilt_nomination'){
                //            
                //                $selected_course = $DB->get_field('facetoface', 'name', array('id'=>$r_course));
                //        }else{
                //            $selected_course = $DB->get_field('course', 'fullname', array('id'=>$r_course));
                //        }
                //        if($selected_course){
                //            $notf_curses[] = $selected_course;
                //        }
                //        
                //    }
                //    $row[] = implode(',', $notf_curses);
                //}else{
                //    $row[] = 'NA';
                //}
                
                $editurl = new \moodle_url('/local/notifications/index.php', array('id'=>$each_notification->id));
                $deleteurl = new \moodle_url('/local/notifications/index.php', array('deleteid'=>$each_notification->id));
                                
                $actions = array();
                
                $actions[] = \html_writer::link('javascript:void(0)', $OUTPUT->pix_icon('t/edit', get_string('edit'), 'moodle', array('class' => 'iconsmall', 'title' => '')), array('data-action' => 'createnotificationmodal', 'class'=>'createnotificationmodal', 'data-value'=>$each_notification->id, 'class' => '', 'onclick' =>'(function(e){ require("local_notifications/notifications").init({selector:"createnotificationmodal", context:'.$systemcontext->id.', id:'.$each_notification->id.', form_status:0}) })(event)','style'=>'cursor:pointer' , 'title' => 'Edit'));    

                $actions[] = \html_writer::link(
						"javascript:void(0)",
						$OUTPUT->pix_icon('i/delete', get_string('delete'), 'moodle', array('class' => 'iconsmall', 'title' => '')),
						array('id' => 'deleteconfirm' . $each_notification->id . '', 'onclick' => '(
							  function(e){
				require("local_notifications/custom").deletenotification("' . $each_notification->id . '")
				})(event)'));
                
                $row[] = $each_notification->name;
                //$row[] = $each_notification->shortname;
                $row[] = $each_notification->subject ? $each_notification->subject : 'N/A' ;
                $row[] = $DB->get_field('local_costcenter', 'fullname', array('id'=>$each_notification->costcenterid));
                $row[] = implode(' &nbsp;', $actions);
                $data[] = $row;
            }
            $table = new \html_table();
            $table->id = 'notification_info';
            $table->size = array('20%', '20%', '20%', '20%', '20%');
            $table->head = array(get_string('notification_type', 'local_notifications'),
                                 /*get_string('code', 'local_notifications'),*/
                                 get_string('subject', 'local_notifications'),
                                 get_string('organization', 'local_users'),
                                 get_string('actions')
                                 //get_string('courses_ilts', 'local_notifications'),
                                 );
            $table->data = ($data) ? $data : get_string('norecordsfound', 'local_costcenter');
            $notfn_types_table = \html_writer::table($table);
            $notfn_types = \html_writer::tag('div',$notfn_types_table,array('class'=>'notification_overflow'));
        /*}else{
            $notfn_types = \html_writer::tag('h5', get_string('no_records', 'local_notifications'), array());
        }*/
        
        return $notfn_types;
     }

     /**
     * [render_form_status description]
     * @method render_form_status
     * @param  \local_notifications\output\form_status $page [description]
     * @return [type]                                    [description]
     */
    public function render_form_status(\local_notifications\output\form_status $page) {
        $data = $page->export_for_template($this);
        return parent::render_from_template('local_notifications/form_status', $data);
    }

}

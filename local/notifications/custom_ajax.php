<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or localify
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
 * Process ajax requests
 *
 * @copyright Sreenivas
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package local_evaluation
 */

if (!defined('AJAX_SCRIPT')) {
    define('AJAX_SCRIPT', true);
}

require(__DIR__.'/../../config.php');
require_once('lib.php');
global $CFG,$DB,$USER, $PAGE;
$notificationid = required_param('notificationid', PARAM_INT);
$costcenterid = optional_param('costcenterid', 0, PARAM_INT);
$page = required_param('page', PARAM_INT);

$PAGE->set_context(context_system::instance());
require_login();
$lib = new \notifications();
$notif_type = $DB->get_field('local_notification_type', 'shortname', array('id'=>$notificationid));
switch($page){
	case 1:		
		$strings = $lib->get_string_identifiers($notif_type);
		//echo json_encode($strings);
		$notif_type_find=explode('_',$notif_type);
		switch(strtolower($notif_type_find[0])){
			case 'course':	
			$sql = "SELECT c.id, c.fullname as name FROM {course} c                           
                            WHERE  c.visible = 1 AND c.open_costcenterid =$costcenterid ";                    
        	$datamoduleids = $DB->get_records_sql($sql);

        	$datamodule_label="Courses";

			break;	
			// case 'classroom':	
			// $sql = "SELECT c.id, c.name FROM {local_classroom} c                           
   //                          WHERE  c.costcenter =$costcenterid ";                    
   //      	$datamoduleids = $DB->get_records_sql($sql);

   //      	$datamodule_label="Classrooms";

			// break;
			// case 'onlinetest':	
			// $sql = "SELECT c.id, c.name FROM {local_onlinetests} c                           
   //                          WHERE  c.visible = 1 AND c.costcenterid	 =$costcenterid ";                    
   //      	$datamoduleids = $DB->get_records_sql($sql);

   //      	$datamodule_label="Onlinetests";

			// break;
			// case 'feedback':	
			// $sql = "SELECT c.id, c.name FROM {local_evaluations} c                           
   //                          WHERE  c.visible = 1 AND c.costcenterid =$costcenterid ";                    
   //      	$datamoduleids = $DB->get_records_sql($sql);

   //      	$datamodule_label="Feedbacks";

			// break;	
			case 'program':	
			$sql = "SELECT c.id, c.fullname AS name FROM {local_program} c                           
                            WHERE c.costcenter =$costcenterid ";                 
        	$datamoduleids = $DB->get_records_sql($sql);

        	$datamodule_label="Programs";

			break;
			// case 'learningplan':	
			// $sql = "SELECT c.id, c.name FROM {local_learningplan} c                           
   //                          WHERE  c.visible = 1 AND c.costcenter =$costcenterid ";                    
   //      	$datamoduleids = $DB->get_records_sql($sql);

   //      	$datamodule_label="Learning Paths";

			// break;	
			
   //      	case 'certification':	
			// $sql = "SELECT c.id, c.name FROM {local_certification} c                           
   //                          WHERE  c.visible = 1 AND c.costcenter =$costcenterid ";                
   //      	$datamoduleids = $DB->get_records_sql($sql);

   //      	$datamodule_label="Certifications";

			// break;
		}
		echo json_encode(['datamodule_label'=>$datamodule_label,'datamoduleids' =>$datamoduleids,'datastrings'=>$strings]);	
	break;
	case 2:
		$sql = "SELECT c.id, c.fullname FROM {course} c                           
                            WHERE  c.visible = 1 AND c.open_costcenterid =$costcenterid ";                    
        $courses = $DB->get_records_sql($sql);
		echo json_encode(['data' =>$courses]);
		break;
	
	case 3:
		$sql = "select id, name from {local_classroom} where costcenter=".$data->costcenterid." and status=1";
        $courses = $DB->get_records_sql($sql);
		echo json_encode(['data' =>$courses]);
		break;
}



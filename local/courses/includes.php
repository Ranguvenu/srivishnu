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
 * @package   define role hr and ast functions
 * @subpackage  local
 * @author  2016 hameed@eabyas.in
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

class user_course_details {
		
	function course_summary_files($courserecord){
        global $DB, $CFG, $OUTPUT;
        if ($courserecord instanceof stdClass) {
            require_once($CFG->libdir . '/coursecatlib.php');
            $courserecord = new course_in_list($courserecord);
        }
        
        // set default course image
        foreach ($courserecord->get_course_overviewfiles() as $file) {
            $isimage = $file->is_valid_image();
            if($isimage){
				$url = file_encode_url("$CFG->wwwroot/pluginfile.php", '/' . $file->get_contextid() . '/' .
					$file->get_component() . '/' .$file->get_filearea() . $file->get_filepath() . $file->get_filename(), !$isimage);
			}else{
				$url = $OUTPUT->pix_url('/course_images/courseimg', 'local_costcenter');
			}
        }
		if(empty($url)){
			$dir = $CFG->wwwroot.'/local/costcenter/pix/course_images/image3.jpg';
			for($i=1; $i<=10; $i++) {
				$image_name = $dir;
				$url = $image_name;
				break;
			}

		}
        return $url;
    }
    function get_classes_summary_files($classrecord){
    		global $OUTPUT;
			$url = $OUTPUT->image_url('classviewnew', 'local_classroom');
        return $url;
    }
}

/*For the security checking up if viewing course of users is acceptable or not acceptable.
 Initially I am checking it for the course view part and including this on view page of course

*/

class has_user_permission{
                
    public function access_courses_permission($courseid){
         global $DB, $USER;
        
         //------userid and courseid validation-------------------    
         if (empty($courseid)) {
             return false;               
         }  //---------end validation------------------
	   if(is_siteadmin() || $courseid==1)
	       return true;
	   
       $check_course_acc=$DB->get_field('course','open_costcenterid',array('id'=>$courseid)); 
	   $course_costcenter=$DB->get_field('local_costcenter','shortname',array('id'=>$check_course_acc));
	 
	 if($course_costcenter =='ACD')
		  return true;
	 
           $usercostcenter=$DB->get_field('user','open_costcenterid',array('id'=>$USER->id));   
              
		 
		 if(!empty($usercostcenter)){
			 
			if($check_course_acc == $usercostcenter)			 
			      return true;
		        else 
                  return false;				 
		 
		 }
    
    } // end of username_andcostcenter_validation function
	
	 public function access_user_permission($userid){
         global $DB, $USER;
		 
        $systemcontext = context_system::instance();
         //------userid and courseid validation-------------------    
         if (empty($userid)) {
             return false;               
         }  //---------end validation------------------
	   if(is_siteadmin() || has_capability('local/assign_multiple_departments:manage', $systemcontext))
		   return true;
           
        $check_course_acc=$DB->get_field('user','open_costcenterid',array('id'=>$userid)); 
	    $usercostcenter=$DB->get_field('user','open_costcenterid',array('id'=>$USER->id));
		 
		 if(!empty($usercostcenter) && !empty($check_course_acc) ){
                     
			 if($usercostcenter == $check_course_acc)
				 return true;
		     else 
                return false;				 
		 
		 }
		 else 
			 return false;
    
    } // end of username_and costcenter_validation function
	
}


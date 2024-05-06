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
 * Classroom View
 *
 * @package    local
 * @subpackage users
 * @copyright  2018 Syed Hameed Ullah <hameed@eabyas.in>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_users\output;

defined('MOODLE_INTERNAL') || die;
use stdClass;

class team_status_lib{

public function departmentusers($limit = 10, $offset = 0, $learningtype = false, $search = false){
		global $DB, $USER;
			if($search){
				$condition = " AND CONCAT(u.firstname, ' ', u.lastname) LIKE '%$search%' ";
			} else {
				$condition = " ";
			}
			$departmentuserssql = "SELECT u.* FROM {user} as u
									JOIN {local_userdata} as ud ON ud.userid = u.id
									WHERE ud.costcenterid = (SELECT costcenterid FROM {local_userdata} WHERE userid = $USER->id) AND u.id != $USER->id
									AND u.confirmed = 1 AND u.suspended = 0 AND u.deleted = 0 AND u.id > 2 $condition";
			$departmentusers = $DB->get_records_sql($departmentuserssql);
		return $departmentusers;
	}
	
	public function get_team_members(){
		global $DB, $USER;
			
		$sql = "SELECT u.* 
				 FROM {user} as u
				WHERE u.open_supervisorid = $USER->id AND u.id != $USER->id
				AND u.confirmed = 1 AND u.suspended = 0 AND u.deleted = 0 AND u.id > 2";
		$get_team_members = $DB->get_records_sql($sql);
		return $get_team_members;
	}
	
	public function get_team_member_lp_status($userid,$completed = false){
		global $DB;
			
		$sql = "SELECT lpu.id,lpu.planid,lpu.status,lpu.userid as userid
				FROM {local_learningplan_user} AS lpu
				JOIN {local_learningplan} AS lp ON lpu.planid = lp.id
				WHERE lpu.userid = $userid AND lp.visible = 1";
		if($completed){
			$sql .= " AND lpu.status=1 AND lpu.completiondate!='' ";
		}
			
		$lpstatus = $DB->get_records_sql($sql);
		return count($lpstatus);
	}
	
	public function get_team_member_ilt_status($userid){

		$return = new stdClass();
		$return->inprogress = self::classrooms_status_count($userid,'1');
		$return->completed = self::classrooms_status_count($userid,'4');
		$return->total = self::classrooms_status_count($userid,'1,4');
		return $return;

	}
    public static function classrooms_status_count($userid,$status='') {
        global $DB;
        $sql = "SELECT count(lc.id) FROM {local_classroom} AS lc 
                JOIN {local_classroom_users} AS lcu ON lc.id=lcu.classroomid
                WHERE lc.status IN ({$status}) AND lcu.userid=$userid ";
        $coursecount = $DB->count_records_sql($sql);
        return $coursecount;
    }


    //sarath added below 5 function for display information based on count in team dashboard

    //1.classrooms details display for each user in teamview dashboard
    public static function classrooms_status_count_eachuser($userid,$status='') {
        global $DB;
        $sql = "SELECT lc.id as id,lc.name as fullname,lc.shortname as code,lcu.timecreated as enrolldate,lcu.completion_status as status FROM {local_classroom} AS lc 
                JOIN {local_classroom_users} AS lcu ON lc.id=lcu.classroomid
                WHERE lc.status IN ({$status}) AND lcu.userid=$userid ";
        $coursecount = $DB->get_records_sql($sql);
        return $coursecount;
    }

    //2.learningplan details display for each user in teamview dashboard
    public function get_team_member_lp_status_display($userid,$completed = false){
		global $DB;
			
		$sql = "SELECT lpu.id,lpu.planid,lpu.status,lpu.userid as userid,lp.name as fullname,lp.shortname as code,lpu.timecreated as enrolldate
				FROM {local_learningplan_user} AS lpu
				JOIN {local_learningplan} AS lp ON lpu.planid = lp.id
				WHERE lpu.userid = $userid AND lp.visible = 1";
		if($completed){
			$sql .= " AND lpu.status=1 AND lpu.completiondate!='' ";
		}
			
		$lpstatus = $DB->get_records_sql($sql);
		return $lpstatus;
	}

	//3.programs details display for each user in teamview dashboard
	public static function programs_status_count_eachuser($userid,$status='') {
        global $DB;
        $sql = "SELECT lpro.id as id,lpro.name as fullname, lpro.shortname as code,lprou.timecreated as enrolldate,lprou.completion_status as status FROM {local_program} AS lpro 
                JOIN {local_program_users} AS lprou ON lpro.id=lprou.programid
                WHERE lprou.completion_status IN ({$status}) AND lprou.userid=$userid ";
        $coursecount = $DB->get_records_sql($sql);
        return $coursecount;
    }

    //4.certifications details display for each user in teamview dashboard
    public static function certification_status_count_display($userid,$status='') {
        global $DB;
        $sql = "SELECT lc.id,lc.name as fullname,lc.shortname as code,lcu.timecreated as enrolldate,lcu.completion_status as status FROM {local_certification} AS lc 
                JOIN {local_certification_users} AS lcu ON lc.id=lcu.certificationid
                WHERE lc.status IN ({$status}) AND lcu.userid=$userid ";
        $coursecount = $DB->get_records_sql($sql);
        return $coursecount;
    }

    //5.onlinetests count in teamview dashboard
    public function get_team_member_onlinetest_status($userid,$completed = false){
		global $DB;
			
		$sql = "SELECT lou.id,lou.onlinetestid,lou.status,lou.userid as userid
				FROM {local_onlinetest_users} AS lou
				JOIN {local_onlinetests} AS lo ON lou.onlinetestid = lo.id
				WHERE lou.userid = $userid AND lo.visible = 1";
		if($completed){
			$sql .= " AND lou.status=1";
		}
			
		$onlineteststatus = $DB->get_records_sql($sql);
		return count($onlineteststatus);
	}


	//4.onlinetests details display for each user in teamview dashboard
	public function get_team_member_onlinetest_status_display($userid,$completed = false){
		global $DB;
			
		$sql = "SELECT lou.id,lou.onlinetestid,lou.status,lou.userid as userid,lo.name as fullname,lo.name as code,lou.timecreated as enrolldate
				FROM {local_onlinetest_users} AS lou
				JOIN {local_onlinetests} AS lo ON lou.onlinetestid = lo.id
				WHERE lou.userid = $userid AND lo.visible = 1";
		if($completed){
			$sql .= " AND lou.status=1";
		}
			
		$onlineteststatus = $DB->get_records_sql($sql);
		return $onlineteststatus;
	}
	//ended here by sharath


    public static function get_team_member_program_status($userid){
    	$return = new stdClass();
		$return->inprogress = self::programs_status_count($userid,'0');
		$return->completed = self::programs_status_count($userid,'1');
		$return->total = self::programs_status_count($userid,'0,1');
		return $return;
    }

    public static function programs_status_count($userid,$status='') {
        global $DB;
        $sql = "SELECT count(lpro.id) FROM {local_program} AS lpro 
                JOIN {local_program_users} AS lprou ON lpro.id=lprou.programid
                WHERE lprou.completion_status IN ({$status}) AND lprou.userid=$userid ";
        $coursecount = $DB->count_records_sql($sql);
        return $coursecount;
    }

    
    public static function get_team_member_certification_status($userid){
    	$return = new stdClass();
		$return->inprogress = self::certification_status_count($userid,'1');
		$return->completed = self::certification_status_count($userid,'4');
		$return->total = self::certification_status_count($userid,'1,4');
		return $return;
    }

    public static function certification_status_count($userid,$status='') {
        global $DB;
        $sql = "SELECT count(lc.id) FROM {local_certification} AS lc 
                JOIN {local_certification_users} AS lcu ON lc.id=lcu.certificationid
                WHERE lc.status IN ({$status}) AND lcu.userid=$userid ";
        $coursecount = $DB->count_records_sql($sql);
        return $coursecount;
    }
	// public static function completed_classrooms_count($userid) {
 //        global $DB;
 //        $sql = "SELECT lc.id FROM {local_classroom} as lc   
 //                JOIN {local_classroom_users} AS lcu ON lc.id=lcu.classroomid
 //                WHERE  lc.status=4 and lcu.userid=$userid ";
 //        $coursenames = $DB->get_records_sql($sql);
 //        return $coursenames;
 //    }
    // public static function inprogress_classrooms_count($userid) {
    //     global $DB;
    //     $sql = "SELECT lc.id FROM {local_classroom} AS lc 
    //             JOIN {local_classroom_users} AS lcu ON lc.id=lcu.classroomid
    //             WHERE lc.status=1 AND lcu.userid=$userid ";
    //     $coursenames = $DB->get_records_sql($sql);
    //     return $coursenames;
    // }
    // public static function gettotal_classrooms_count($userid){
    //     global $DB;
    //     $sql = "SELECT lc.id FROM {local_classroom} AS lc 
    //             JOIN {local_classroom_users} AS lcu ON lc.id=lcu.classroomid
    //             WHERE lc.status IN(1,4) AND lcu.userid=$userid ";
    //     $coursenames = $DB->get_records_sql($sql);
    //     return count($coursenames);
    // }
	
	public function get_team_member_course_status($userid ,$optional = false, $mandatory = false,$totalcourses = false){
		global $DB;
		
		// $sql ="SELECT en.id,course.id as courseid                    
  //               FROM  
  //               {enrol} AS en 
  //               JOIN {user_enrolments} AS ue ON ue.enrolid = en.id 
  //               JOIN {course} AS course ON en.courseid = course.id 
  //               JOIN {user} AS USER ON USER.id = ue.userid
                 
  //               WHERE USER.id  IN (
		// 			SELECT ra.userid
		// 			FROM {course} AS c
		// 			JOIN {context} AS ctx ON c.id = ctx.instanceid
		// 			JOIN {role_assignments} AS ra ON ra.contextid = ctx.id
		// 			WHERE c.id=course.id and ra.roleid=5 AND ra.userid = $userid AND c.visible = 1
		// 		)
				
		// 		AND  en.enrol IN('self','manual','auto') AND course.visible = 1
  //               ";
				
		// $totalenrolled = $DB->get_records_sql_menu($sql);
		
		// $madatorycourses = 0;
		// $madatorycourses_comp = 0;
		// $optionalcourses = 0;
		// $optionalcourses_comp = 0;
		// if($totalenrolled){
		// 	foreach($totalenrolled as $key=>$enrolled){
		// 	    $sql="select enrol from {enrol} where id=$key";
		// 		$coursedetails = $DB->get_record_sql($sql);
					
		// 		if($coursedetails->enrol!="self"){
		// 			$madatorycourses++;
		// 			$mcompletion = $DB->get_record_sql("SELECT id FROM {course_completions}
		// 											  WHERE course = $enrolled AND userid= $userid AND timecompleted!='' ");
		// 			if($mcompletion){
		// 				$madatorycourses_comp++;
		// 			}
		// 		}else{
		// 			$optionalcourses++;
		// 			$ocompletion = $DB->get_record_sql("SELECT id FROM {course_completions}
		// 											  WHERE course = $enrolled AND userid= $userid AND timecompleted!='' ");
		// 			if($ocompletion){
		// 				$optionalcourses_comp++;
		// 			}
		// 		}
		// 	}
		// }
		
		// if($optional){
		// 	$opt = new stdClass();
		// 	$opt->enrolled = $optionalcourses;
		// 	$opt->completed = $optionalcourses_comp;
		// 	return $opt;
		// }elseif($mandatory){
		// 	$mand = new stdClass();
		// 	$mand->enrolled = $madatorycourses;
		// 	$mand->completed = $madatorycourses_comp;
		// 	return $mand;
		// }elseif($totalcourses){
		// 	return $totalenrolled;
		// }else{
		// 	return count($totalenrolled);
		// }
		// $userdashboard_courseslib = block_userdashboard\lib\elearning_courses();
		$inprogress = count(self::inprogress_coursenames($userid));
		$completed = count(self::completed_coursenames($userid));
		$return = new stdClass();
		$return->enrolled = $inprogress+$completed;
		$return->inprogress = $inprogress;
		$return->completed = $completed;
		return $return;

	}
	public static function inprogress_coursenames($userid) {
        global $DB;
            $sql = "SELECT DISTINCT(course.id), course.fullname, course.shortname as code, course.summary,ue.timecreated as enrolldate
						FROM {course} AS course
                    	JOIN {enrol} AS e ON course.id = e.courseid AND e.enrol IN('self','manual','auto')
                    	JOIN {user_enrolments} AS ue ON e.id = ue.enrolid
                    	WHERE ue.userid = ".$userid." AND FIND_IN_SET(3,course.open_identifiedas) AND course.id > 1";

            if(!empty($filter_text)){
               $sql .= " AND course.fullname LIKE '%%$filter_text%%'";
            }
            // echo $sql;
            $completed_courses = self::completed_coursenames($userid);
            if(!empty($completed_courses)){
                $complted_id = array();
                
                foreach($completed_courses as $complted_course){
                    $completed_id[] = $complted_course->id;
                }
                $completed_ids = implode(',', $completed_id);
                $sql .= " AND course.id NOT IN($completed_ids)";
            }

        $inprogress_courses = $DB->get_records_sql($sql);

        return $inprogress_courses;
    }

    public static function completed_coursenames($userid) {
        global $DB;
        $sql = "SELECT distinct(cc.id) as completionid,c.id,c.fullname,c.shortname as code,c.summary,ue.timecreated as enrolldate,cc.timecompleted as completedate FROM {course_completions} cc
		        JOIN {course} c ON c.id = cc.course AND cc.userid = $userid
                JOIN {enrol} e ON c.id = e.courseid AND e.enrol IN('self','manual','auto')
                JOIN {user_enrolments} ue ON e.id = ue.enrolid
              WHERE FIND_IN_SET(3,c.open_identifiedas) AND cc.timecompleted is not NULL
              AND c.visible=1 AND c.id>1
              ";

        $coursenames = $DB->get_records_sql($sql);

        return $coursenames;
    }
	
	public function get_colorcode_tm_dashboard($score, $total){
		
		if($total == 0){
			$total = 1;
		}
		$totalpercentage = ($score/$total)*100;
		
		if($totalpercentage <= 60){
			$color = 'red';
		}elseif(($totalpercentage == 100)){
			$color = 'green';
		}else{
			$color = 'yellow';
		}
		return $color;
	}
	function get_user_certificates($userid=false){
        global $DB,$USER;
		if($userid){
			$userid = $userid;
		}else{
			$userid = $USER->id;
		}
        $etypecoursescomp = $this->get_user_completed_elearningcourses($userid,$xp=false);
		
        $certificates = null;
        if($etypecoursescomp){
            $imp_etypecoursescomp = implode(',',$etypecoursescomp);
            $certificates = $DB->get_records_sql("SELECT c.id,c.name,c.course
												 FROM {certificate} AS c
												 JOIN {certificate_issues} AS ci ON c.id = ci.certificateid
												 WHERE c.course IN ($imp_etypecoursescomp) AND ci.userid = $userid");
        }
        return $certificates;
    }
    function get_user_badges($userid=false){
        global $DB,$USER;
        if($userid){
			$userid = $userid;
		}else{
			$userid = $USER->id;
		}
        $etypecoursescomp = $this->get_user_completed_elearningcourses($userid,$xp=false);
        $badges = null;
        if($etypecoursescomp){
            $imp_etypecoursescomp = implode(',',$etypecoursescomp);
            $badges = $DB->get_records_sql("SELECT b.id,b.name,b.courseid 
											FROM {badge} AS b
											JOIN {badge_issued} AS bi ON b.id = bi.badgeid
											WHERE b.courseid IN ($imp_etypecoursescomp) AND bi.userid = $userid");
            
        }
        return $badges;
    }
    function get_user_credits($creditscount = false,$userid=false){
        global $DB,$USER;
        if($userid){
			$userid = $userid;
		}else{
			$userid = $USER->id;
		}
        $etypecoursescomp = $this->get_user_completed_elearningcourses($userid,$xp=true);
		
        $credits = null;
		if($etypecoursescomp){
			if($creditscount){
				$imp_etypecoursescomp = implode(',',$etypecoursescomp);
				$count = $DB->get_record_sql("SELECT SUM(cd.xp) as count
												FROM {block_xp} AS cd
												WHERE cd.courseid IN ($imp_etypecoursescomp) and cd.userid=$userid");
				
				$credits = $count->count;
			}else{
				$imp_etypecoursescomp = implode(',',$etypecoursescomp);
				$credits = $DB->get_records_sql("SELECT cd.id, cd.courseid, cd.xp
												FROM {block_xp} AS cd
												WHERE cd.courseid IN ($imp_etypecoursescomp) and cd.userid=$userid");
			}
		}
        return $credits;
    }
    function get_user_completed_elearningcourses($userid=false,$xp=false){
        global $DB,$USER;
        if($userid){
			$userid = $userid;
		}else{
			$userid = $USER->id;
		}
        $enrolledcourses = enrol_get_users_courses($userid);
        $usercourses = array();
        if($enrolledcourses){
            foreach($enrolledcourses as $enrolledcourse){
                $usercourses[$enrolledcourse->id] = $enrolledcourse->id;
            }
        }
         $imp_usercourses = implode(',',$usercourses);
        if($usercourses){
			if($xp){
				$table= " {block_xp} ";
				$sql="SELECT cc.id, cd.courseid
                        FROM $table AS cd
                        JOIN {course} AS cc ON cd.courseid = cc.id
                        WHERE cd.userid = $userid
                         ";
			}else{
				$table=" {course} ";
				// $and="AND FIND_IN_SET(3,cd.identifiedas)";
				$sql="SELECT cc.id, cc.course
	                    FROM $table AS c
	                    JOIN {course_completions} AS cc ON c.id = cc.course
	                    WHERE c.id IN ($imp_usercourses) AND cc.timecompleted!='' AND cc.userid = $userid
	                     ";
			}
            $etypecoursescomp = $DB->get_records_sql_menu($sql);
        }
        return $etypecoursescomp;
    }
}

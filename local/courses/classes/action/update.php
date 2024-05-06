<?php

namespace local_courses\action;
defined('MOODLE_INTERNAL') or die;
class update{
	/** @var array of categories in a specific costcenter */
    protected $categories = array();

    public function featured_course($featured_course,$featured){
		global $DB;
		$course_feature = new \stdClass();
		$course_feature->id = $featured_course;
		$course_feature->open_requestcourseid = $featured;
		$update = $DB->update_record('course', $course_feature);
		return $update;
	}

	/**
     * [get_categories description] to get the categories in an org.
     * @param  [int] $costcenter [description]
     * @return [array]             [description]
     */
    public function get_categories($costcenter){
        global $DB;
        $category = $DB->get_field('local_costcenter', 'category', array('id' => $costcenter));
        $data = $DB->get_records('course_categories',array('parent' => $category));
        $this->categories[] = $category;
        $cats = $this->get_lower_cats($data);
        return $cats;
    }
    /**
     * [get_lower_cats description] to get the information of the categories under a specific one.
     * @param  [object] $data [departments data under organisation]
     * @return [array]       [category id's lower the organisation]
     */
    public function get_lower_cats($data){
        global $DB;
        foreach($data as $category){
            $lowercat_exist = $DB->get_records('course_categories', array('parent' => $category->id));
            if($lowercat_exist){
                $info = $this->get_lower_cats($lowercat_exist);
            }
            $this->categories[] = $category->id;
        }
        return $this->categories;
    }
    public function local_enrol_get_users_courses($id){
        global $DB;
        $courses_sql = "SELECT course.id,course.fullname,course.summary FROM {course} AS course
                    JOIN {enrol} AS e ON course.id = e.courseid AND e.enrol IN('self','manual','auto')
                    JOIN {user_enrolments} ue ON e.id = ue.enrolid
                    WHERE ue.userid = $id AND FIND_IN_SET(3,course.open_identifiedas) AND course.id>1";
        $return = $DB->get_records_sql($courses_sql);
        return $return;            
    }

}
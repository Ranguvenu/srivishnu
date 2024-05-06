<?php
function xmldb_local_notifications_install() {
    global $DB;
    
    $time = time();
    $notification_type_data = array(
        /*courses notifications*/
        // array('name' => 'Course','shortname' => 'course','parent_module' => '0','usercreated' => '2','timecreated' => $time,'usermodified' => NULL,'timemodified' => NULL),
        // array('name' => 'Course Enrollment','shortname' => 'course_enrol','parent_module' => '1','usercreated' => '2','timecreated' => $time,'usermodified' => NULL,'timemodified' => NULL),
        // array('name' => 'Course Completion','shortname' => 'course_complete','parent_module' => '1','usercreated' => '2','timecreated' => $time,'usermodified' => NULL,'timemodified' => NULL),
        // array('name' => 'Course Unenrollment','shortname' => 'course_unenroll','parent_module' => '1','usercreated' => '2','timecreated' => $time,'usermodified' => NULL,'timemodified' => NULL),
        // array('name' => 'Course Reminder','shortname' => 'course_reminder','parent_module' => '1','usercreated' => '2','timecreated' => $time,'usermodified' => NULL,'timemodified' => NULL),
        // array('name' => 'Course notification','shortname' => 'course_notification','parent_module' => '1','usercreated' => '2','timecreated' => $time,'usermodified' => NULL,'timemodified' => NULL),

        /*program notifications*/
        array('name' => 'Program','shortname' => 'program','parent_module' => '0','usercreated' => '2','timecreated' => $time,'usermodified' => NULL,'timemodified' => NULL),
        array('name' => 'Year Enrollment','shortname' => 'program_cc_year_enrol','parent_module' => '1','usercreated' => '2','timecreated' => $time,'usermodified' => NULL,'timemodified' => NULL),
        array('name' => 'Program Faculty Enrollment','shortname' => 'program_cc_year_faculty_enrol','parent_module' => '1','usercreated' => '2','timecreated' => $time,'usermodified' => NULL,'timemodified' => NULL),
        array('name' => 'Program Course Completion','shortname' => 'program_course_completion','parent_module' => '1','usercreated' => '2','timecreated' => $time,'usermodified' => NULL,'timemodified' => NULL),
        array('name' => 'Semester Completion','shortname' => 'program_semester_completion','parent_module' => '1','usercreated' => '2','timecreated' => $time,'usermodified' => NULL,'timemodified' => NULL),
        array('name' => 'Year Completion','shortname' => 'program_year_completion','parent_module' => '1','usercreated' => '2','timecreated' => $time,'usermodified' => NULL,'timemodified' => NULL),
        array('name' => 'Program Completion','shortname' => 'program_completion','parent_module' => '1','usercreated' => '2','timecreated' => $time,'usermodified' => NULL,'timemodified' => NULL)
    );
    
    foreach($notification_type_data as $notification_type){
        $notification = (object) $notification_type;
        $DB->insert_record('local_notification_type', $notification);
    }
    
    $strings = array(
        array('name' => '[course_title]','module' => 'course','usercreated' => '2','timecreated' => $time,'usermodified' => NULL,'timemodified' => NULL),
        array('name' => '[course_code]','module' => 'course','usercreated' => '2','timecreated' => $time,'usermodified' => NULL,'timemodified' => NULL),
        array('name' => '[course_enrolstartdate]','module' => 'course','usercreated' => '2','timecreated' => $time,'usermodified' => NULL,'timemodified' => NULL),
        array('name' => '[course_enrolenddate]','module' => 'course','usercreated' => '2','timecreated' => $time,'usermodified' => NULL,'timemodified' => NULL),
        array('name' => '[course_completiondays]','module' => 'course','usercreated' => '2','timecreated' => $time,'usermodified' => NULL,'timemodified' => NULL),
        array('name' => '[course_department]','module' => 'course','usercreated' => '2','timecreated' => $time,'usermodified' => NULL,'timemodified' => NULL),
        array('name' => '[course_link]','module' => 'course','usercreated' => '2','timecreated' => $time,'usermodified' => NULL,'timemodified' => NULL),
        array('name' => '[course_duedate]','module' => 'course','usercreated' => '2','timecreated' => $time,'usermodified' => NULL,'timemodified' => NULL),
        array('name' => '[course_description]','module' => 'course','usercreated' => '2','timecreated' => $time,'usermodified' => NULL,'timemodified' => NULL),
        array('name' => '[course_url]','module' => 'course','usercreated' => '2','timecreated' => $time,'usermodified' => NULL,'timemodified' => NULL),
        array('name' => '[course_description]','module' => 'course','usercreated' => '2','timecreated' => $time,'usermodified' => NULL,'timemodified' => NULL),
        array('name' => '[course_image]','module' => 'course','usercreated' => '2','timecreated' => $time,'usermodified' => NULL,'timemodified' => NULL),
        array('name' => '[course_completiondate]','module' => 'course','usercreated' => '2','timecreated' => $time,'usermodified' => NULL,'timemodified' => NULL),
        array('name' => '[course_reminderdays]','module' => 'course','usercreated' => '2','timecreated' => $time,'usermodified' => NULL,'timemodified' => NULL),
        array('name' => '[enroluser_fullname]','module' => 'user','usercreated' => '2','timecreated' => $time,'usermodified' => NULL,'timemodified' => NULL),
        array('name' => '[enroluser_email]','module' => 'user','usercreated' => '2','timecreated' => $time,'usermodified' => NULL,'timemodified' => NULL),
        array('name' => '[enroluser_username]','module' => 'user','usercreated' => '2','timecreated' => $time,'usermodified' => NULL,'timemodified' => NULL),
        
        array('name' => '[program_name]','module' => 'program','usercreated' => '2','timecreated' => $time,'usermodified' => NULL,'timemodified' => NULL),
        array('name' => '[program_code]','module' => 'program','usercreated' => '2','timecreated' => $time,'usermodified' => NULL,'timemodified' => NULL),
        array('name' => '[program_curriculum_name]','module' => 'program','usercreated' => '2','timecreated' => $time,'usermodified' => NULL,'timemodified' => NULL),
        array('name' => '[program_year_name]','module' => 'program','usercreated' => '2','timecreated' => $time,'usermodified' => NULL,'timemodified' => NULL),
        array('name' => '[program_semester_name]','module' => 'program','usercreated' => '2','timecreated' => $time,'usermodified' => NULL,'timemodified' => NULL),
        array('name' => '[program_course_name]','module' => 'program','usercreated' => '2','timecreated' => $time,'usermodified' => NULL,'timemodified' => NULL),
        array('name' => '[program_course_username]','module' => 'program','usercreated' => '2','timecreated' => $time,'usermodified' => NULL,'timemodified' => NULL),
        // array('name' => '[program_courseinfo]','module' => 'program','usercreated' => '2','timecreated' => $time,'usermodified' => NULL,'timemodified' => NULL),
        array('name' => '[program_enroluserfulname]','module' => 'program','usercreated' => '2','timecreated' => $time,'usermodified' => NULL,'timemodified' => NULL),
        array('name' => '[program_enroluseremail]','module' => 'program','usercreated' => '2','timecreated' => $time,'usermodified' => NULL,'timemodified' => NULL),
         array('name' => '[program_course_facultyname]','module' => 'program','usercreated' => '2','timecreated' => $time,'usermodified' => NULL,'timemodified' => NULL),

        array('name' => '[program_link]','module' => 'program','usercreated' => '2','timecreated' => $time,'usermodified' => NULL,'timemodified' => NULL),
        
        // array('name' => '[program_session_useremail]','module' => 'program','usercreated' => '2','timecreated' => $time,'usermodified' => NULL,'timemodified' => NULL),
       
        array('name' => '[program_course_link]','module' => 'program','usercreated' => '2','timecreated' => $time,'usermodified' => NULL,'timemodified' => NULL),

        array('name' => '[program_admission_startdate]','module' => 'program','usercreated' => '2','timecreated' => $time,'usermodified' => NULL,'timemodified' => NULL),
        array('name' => '[program_admission_enddate]','module' => 'program','usercreated' => '2','timecreated' => $time,'usermodified' => NULL,'timemodified' => NULL),

        array('name' => '[program_organization]','module' => 'program','usercreated' => '2','timecreated' => $time,'usermodified' => NULL,'timemodified' => NULL),
       
        // array('name' => '[program_course]','module' => 'program','usercreated' => '2','timecreated' => $time,'usermodified' => NULL,'timemodified' => NULL),
        array('name' => '[program_creater]','module' => 'program','usercreated' => '2','timecreated' => $time,'usermodified' => NULL,'timemodified' => NULL),

        array('name' => '[program_curriculum_link]','module' => 'program','usercreated' => '2','timecreated' => $time,'usermodified' => NULL,'timemodified' => NULL),

        array('name' => '[program_year_course_link]','module' => 'program','usercreated' => '2','timecreated' => $time,'usermodified' => NULL,'timemodified' => NULL),
        // array('name' => '[program_lc_course_sessions_link]','module' => 'program','usercreated' => '2','timecreated' => $time,'usermodified' => NULL,'timemodified' => NULL),
        array('name' => '[program_year_course_creater]','module' => 'program','usercreated' => '2','timecreated' => $time,'usermodified' => NULL,'timemodified' => NULL),
        array('name' => '[program_year_creater]','module' => 'program','usercreated' => '2','timecreated' => $time,'usermodified' => NULL,'timemodified' => NULL),
        // array('name' => '[program__creater]','module' => 'program','usercreated' => '2','timecreated' => $time,'usermodified' => NULL,'timemodified' => NULL),
        array('name' => '[program_year_course_completiondate]','module' => 'program','usercreated' => '2','timecreated' => $time,'usermodified' => NULL,'timemodified' => NULL),
         array('name' => '[program_semester_completiondate]','module' => 'program','usercreated' => '2','timecreated' => $time,'usermodified' => NULL,'timemodified' => NULL),
        array('name' => '[program_year_completiondate]','module' => 'program','usercreated' => '2','timecreated' => $time,'usermodified' => NULL,'timemodified' => NULL),
        array('name' => '[program_completiondate]','module' => 'program','usercreated' => '2','timecreated' => $time,'usermodified' => NULL,'timemodified' => NULL),
        array('name' => '[program_university]','module' => 'program','usercreated' => '2','timecreated' => $time,'usermodified' => NULL,'timemodified' => NULL),
        
        // array('name' => '[program_lc_course__session_completiondate]','module' => 'program','usercreated' => '2','timecreated' => $time,'usermodified' => NULL,'timemodified' => NULL),
        // array('name' => '[program_session_link]','module' => 'program','usercreated' => '2','timecreated' => $time,'usermodified' => NULL,'timemodified' => NULL),
        // array('name' => '[program_session_name]','module' => 'program','usercreated' => '2','timecreated' => $time,'usermodified' => NULL,'timemodified' => NULL),
        // array('name' => '[program_session_completiondate]','module' => 'program','usercreated' => '2','timecreated' => $time,'usermodified' => NULL,'timemodified' => NULL)
    );
    
    foreach($strings as $string){
        $string_obj = (object)$string;
        $DB->insert_record('local_notification_strings', $string_obj);
    }  
}

<?php
namespace local_program\local;
use local_program\program AS programlib ;
class user{
	public function user_profile_content($userid, $return = false,$start =0,$limit=0){
        global $OUTPUT;
        $returnobj = new \stdClass();
        $returnobj->programexist = 1;
        $user_programs = $this->enrol_get_users_program($userid,false,true,$start,$limit);
        $data = array();
        foreach($user_programs['data'] as $program){
            $programsarray = array();
            $programsarray['id'] = $program->id;
            $programsarray['name'] = $program->fullname;
            $programssummary = strip_tags($program->description);
            $programssummary = strlen($programssummary) > 250 ? substr($programssummary, 0, 250)."..." : $programssummary;
            $programsarray['description'] = $programssummary;
            $programsarray['percentage'] = '';
            $programsarray['url'] = '';
            $programsarray['startdate'] = date('Y/m/d',$program->admissionstartdate);
            $programsarray['enddate'] = date('Y/m/d',$program->admissionenddate);
            $programsarray['validtill'] = date('Y/m/d',$program->validtill);
            $programsarray['status'] = $program->status == 1 ? 'Completed' : 'Not completed';
            $data[] = $programsarray;
        }

        $returnobj->sequence = 1;
        $returnobj->count = $user_programs['count'];
        $returnobj->divid = 'user_programs';
        $returnobj->moduletype = 'program';
        $returnobj->userid = $userid;
        $returnobj->string = get_string('programs', 'local_users');
        
        $returnobj->navdata = $data;
        return $returnobj;
    }
    /**
     * [function to get_enrolled program data and count]
     * @param  [INT] $userid [id of the user]
     * @param  [BOOLEAN] $count [true or false]
     * @param  [BOOLEAN] $limityesorno [true or false]
     * @param  [INT] $start [start]
     * @return [INT] $limit [limit]
     */
    public function enrol_get_users_program($userid,$count = false,$limityesorno = false,$start =0,$limit=5) {
        global $DB;
       /* $countsql = "SELECT count(lc.id)";
        $selectsql = "SELECT lc.id, lc.fullname, lc.admissionstartdate,lc.admissionenddate,lc.validtill, lc.shortname,lc.shortcode,lcu.timecreated as enrolldate,lcu.completion_status as status,lc.description";
        $fromsql = " FROM {local_program} AS lc
                     JOIN {local_curriculum_users} AS lcu ON lcu.programid = lc.id
                     WHERE lcu.userid = :userid
                     ORDER BY lc.id DESC ";*/
                       
       
        $context = get_context_instance (CONTEXT_SYSTEM);
             $roles = get_user_roles($context, $userid, false);
                $role = key($roles);
                $roleid = $roles[$role]->roleid;
                $facultyroleid = $DB->get_field('role','id',array('shortname' => 'faculty'));
                if($roleid == $facultyroleid){
                    $countsql = "SELECT count(DISTINCT(lcu.programid))";
                    $selectsql = "SELECT lc.id, lc.fullname, lc.admissionstartdate,lc.admissionenddate,lc.validtill, lc.shortname,lc.shortcode,lcu.timecreated as enrolldate,lc.description";
                    $fromsql = " FROM {local_program} AS lc
                     JOIN {local_cc_session_trainers} AS lcu ON lcu.programid = lc.id
                     WHERE lcu.trainerid = :userid
                     ORDER BY lc.id DESC ";
                }
                else{
                    $countsql = "SELECT count(lc.id)";
 $selectsql = "SELECT lc.id, lc.fullname, lc.admissionstartdate,lc.admissionenddate,lc.validtill, lc.shortname,lc.shortcode,lcu.timecreated as enrolldate,lcu.completion_status as status,lc.description";
                     $fromsql = " FROM {local_program} AS lc
                     JOIN {local_curriculum_users} AS lcu ON lcu.programid = lc.id
                     WHERE lcu.userid = :userid
                     ORDER BY lc.id DESC ";
                 }
        $params = array();
        $params['userid'] = $userid;


        if($limityesorno){
            $programs = $DB->get_records_sql($selectsql.$fromsql,$params,$start,$limit);
        }else{
            $programs = $DB->get_records_sql($selectsql.$fromsql,$params);
        }
        $programscount = $DB->count_records_sql($countsql.$fromsql,$params);

        return array('count' => $programscount,'data' => $programs);
    }
}

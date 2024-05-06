<?php

class local_curriculum_renderer extends plugin_renderer_base {

    function top_action_buttons(){
    global $CFG;
    $systemcontext = context_system::instance();
    $output = "";
         $output .= "<ul class='course_extended_menu_list'>";
     if(is_siteadmin() || $systemcontext){
            $output .= '<li>
                         <div class="courseedit course_extended_menu_itemcontainer">
                  <a id="extended_menu_createcourses" class="pull-right course_extended_menu_itemlink" title = "'.get_string('create_curriculum','local_curriculum').'" data-action="createcoursemodal" onclick="(function(e){ require(\'local_curriculum/ajaxforms\').init({contextid:'.$systemcontext->id.', component:\'local_curriculum\', callback:\'curriculum_form\', form_status:0,id :0 ,plugintype: \'local\', pluginname: \'curriculum\'}) })(event)">
                    <span class="createicon"><i class="icon fa fa-list-alt"></i><i class="fa fa-plus createiconchild" aria-hidden="true"></i></span>
                  </a>
                </div>
                    </li>';
      }

      $output .= "</ul>";
      echo $output;
    }
    function curriculum_view($filterdata,$page,$perpage){
        global $PAGE;
        $filterjson = json_encode($filterdata);

        $PAGE->requires->js_call_amd('local_curriculum/curriculum_views', 'curriculumdatatable', array('filterdata'=> $filterjson));

        $table = new html_table();
        $table->id = "viewcurriculum";

        $table->head = array(get_string('curriculum_name', 'local_curriculum'),get_string('university', 'local_curriculum'),get_string('deptcollege', 'local_program'),get_string('year', 'local_curriculum'),get_string('action', 'local_curriculum'));

        $table->align = array('center','left', 'center', 'left','left');
        $table->size = array('20%','20%','20%','20%','20%');


        $output = '<div class="w-full pull-left">'. html_writer::table($table).'</div>';
        return $output;
    }
   public function viewcurriculum($curriculumid) {
        global $OUTPUT, $CFG, $DB, $USER, $PAGE;
        require_once($CFG->dirroot.'/local/curriculum/program.php');

         
        $systemcontext = context_system::instance();
        $stable = new stdClass();
        $stable->curriculumid = $curriculumid;
        $stable->thead = false;
        $stable->start = 0;
        $stable->length = 1;
        $result=$DB->get_record_sql("SELECT lp.pre_requisites FROM {local_program} lp JOIN {local_curriculum} lc ON lp.id=lc.program WHERE lc.id=$curriculumid");
      //  $pre_requisites=$result->pre_requisites;

        $curriculum = new program();
        $curriculum_result = $curriculum->curriculums($stable);
        if (empty($curriculum)) {
            print_error("curriculum Not Found!");
        }
        $programtemplatestatus = $curriculum->programtemplatestatus($curriculum_result->program);

        $managemyprogram = false;
        if ($curriculum->costcenter == $USER->open_costcenterid) {
            $managemyprogram = true;
        }

        $includesfile = false;
        if (file_exists($CFG->dirroot . '/local/includes.php')) {
            $includesfile = true;
            require_once($CFG->dirroot . '/local/includes.php');
            $includes = new user_course_details();
        }

        $return = "";

        $affiliatecolleges = $DB->count_records_sql('SELECT COUNT(id) FROM {local_program} WHERE parentid = :parentid AND costcenter <> :costcenter',  array('parentid' => $curriculum->id, 'costcenter' => $curriculum->costcenter));

        $copiedprograms = $DB->count_records_sql("SELECT COUNT(id) FROM {local_program} WHERE parentid = :parentid AND costcenter = :costcenter",  array('parentid' => $curriculum->id, 'costcenter' => $curriculum->costcenter));
        $checkcopyprogram = (new program)->checkcopyprogram($curriculum->program);
        $curriculumcompletion = $action = $edit = $delete = $assignusers = $assignusersurl = false;
        if ((has_capability('local/program:manageprogram', context_system::instance()) || is_siteadmin())) {
            $action = true;
        }
        if ((has_capability('local/program:programcompletion', context_system::instance()) || is_siteadmin())) {
            $curriculumcompletion = false;
        }
        if (((has_capability('local/program:editprogram', context_system::instance()) || is_siteadmin())) && $programtemplatestatus) {
            $edit = true;
        }

        $cannotdelete = true;
        $delete = false;
        if ((has_capability('local/program:deleteprogram', context_system::instance()) || is_siteadmin())) {
            $count_records = $DB->count_records('local_curriculum_users', array('curriculumid' => $curriculumid));
            if ($count_records > 0 || !$programtemplatestatus) {
                $cannotdelete = true;
                $delete  = false;
            } else {
                $cannotdelete = false;
                $delete = true;
            }
        }
        $bulkenrollusers = false;
        $bulkenrollusersurl = false;
   
        $selfenrolmenttabcap = true;
        if (!has_capability('local/program:manageprogram', context_system::instance())) {
            $selfenrolmenttabcap = false;
        }
        if (!empty($curriculum->description)) {
            $description = strip_tags(html_entity_decode($curriculum->description));
        } else {
            $description = "";
        }
        $isdescription = '';
     
        if ($userview) {
            $mycompletedsemesters = (new program)->mycompletedsemesteryears($curriculumid, $USER->id);
            $notcmptlsemesters = (new program)->mynextsemesteryears($curriculumid);
            if (!empty($notcmptlsemesters)) {
                $yearid = $notcmptlsemesters[0];
            } else {
                $yearid = $DB->get_field_select('local_program_cc_years', 'id',
                'curriculumid = :curriculumid AND status = :status ORDER BY id ASC LIMIT 0, 1 ',
                array('curriculumid' => $curriculumid, 'status' => 1));
            }
        } else {
            $yearid = $DB->get_field_select('local_program_cc_years', 'id',
            'curriculumid = :curriculumid AND status = :status ORDER BY id ASC LIMIT 0, 1 ',
            array('curriculumid' => $curriculumid, 'status' => 1));
        }

        $completionstatus = $DB->get_field('local_curriculum_users', 'completion_status', array('curriculumid' => $curriculumid, 'userid' => $USER->id));
        if ($completionstatus == 1) {
            $curriculumcompletionstatus = true;
        } else {
            $curriculumcompletionstatus = false;
        }
        $description = $DB->get_field('local_curriculum','description',array('id' => $curriculumid));
        $isdescription = strip_tags($description);
        // $decsriptionstring = $DB->get_field('local_curriculum','description',array('id' => $curriculumid));

        if($isdescription){
            $decsriptionstring = $isdescription;
        }
        $curriculummappedprogramid = $DB->get_field('local_program','id',array('curriculumid' => $curriculumid,'parentid' => 0));
        if(!empty($curriculummappedprogramid)){
           $checkingafiliate = $DB->get_records_sql('SELECT id FROM {local_program} WHERE parentid = '.$curriculummappedprogramid);
             if(!empty($checkingafiliate)){
             $programtemplatestatus = false;
          //  $canmanagesemesteryear = false;
           }
        }    
        $duration_format = $DB->get_field('local_curriculum','duration_format',array('id' => $curriculumid));  
        $curriculumcontext = [
            'curriculum' => $curriculum,
            'curriculumid' => $curriculumid,
            'action' => $action,
            'edit' => $edit,
            'curriculumcompletion' => $curriculumcompletion,
            'cannotdelete' => $cannotdelete,
            'delete' => $delete,
            'assignusers' => $assignusers,
            'assignusersurl' => $assignusersurl,
            'bulkenrollusers' => $bulkenrollusers,
            'bulkenrollusersurl' => $bulkenrollusersurl,
            'description' => $description,
            'descriptionstring' => $decsriptionstring,
            'isdescription' => $isdescription,
            'curriculumname' => $curriculum->name,
            'cfg' => $CFG,
            'program' => $curriculum_result->program,
            'curriculumcompletionstatus' => $curriculumcompletionstatus,
            'cancreatesemesteryear' => (has_capability('local/curriculum:createsemesteryear', $systemcontext) && ($programtemplatestatus)),
            'seats_image' => $OUTPUT->image_url('GraySeatNew', 'local_program'),
            'yearid' => $yearid,
            'curriculumsemesteryears' => $this->viewcurriculumsemesteryears($curriculumid, $yearid),
          //  'pre_requisites'=>$pre_requisites,
        ];
        if($duration_format == 'M'){
         $curriculumcontext['cancreatesemesteryear'] = 0;    
        }
        return $this->render_from_template('local_curriculum/curriculumContent', $curriculumcontext);
    }
     public function viewcurriculumsemesteryears($curriculumid, $yearid) {
        global $OUTPUT, $CFG, $DB, $USER;
        $systemcontext = context_system::instance();
        $assign_courses = '';
        $ccuser = $DB->record_exists('local_curriculum_users',
            array('curriculumid' => $curriculumid, 'userid' => $USER->id));

        $userview = $ccuser && !is_siteadmin() && !has_capability('local/curriculum:createprogram', $systemcontext) ? true : false;

        $stable = new stdClass();
        $stable->curriculumid = $curriculumid;
        $stable->thead = false;
        $stable->start = 0;
        $stable->length = 1;
        $curriculum = (new program)->curriculums($stable);
        $curriculumsemesteryears = (new program)->curriculum_semesteryears($curriculumid);
        $programtemplatestatus = (new program)->programtemplatestatus($curriculum->program);
        $affiliatecolleges = $DB->count_records_sql('SELECT COUNT(id) FROM {local_program} WHERE parentid = :parentid AND costcenter <> :costcenter',  array('parentid' => $curriculum->id, 'costcenter' => $curriculum->costcenter));
        $copiedprograms = $DB->count_records_sql("SELECT COUNT(id) FROM {local_program} WHERE parentid = :parentid AND costcenter = :costcenter",  array('parentid' => $curriculum->id, 'costcenter' => $curriculum->costcenter));

        $managemyprogram = false;

        if ($curriculum->costcenter == $USER->open_costcenterid) {
            $managemyprogram = true;
        }

        if ($userview) {
            $mycompletedsemesters = (new program)->mycompletedsemesters($curriculumid, $USER->id);
            $notcmptlsemesters = (new program)->mynextsemesters($curriculumid);
            if (!empty($notcmptlsemesters)) {
                $nextsemester = $notcmptlsemesters[0];
            } else {
                $nextsemester = 0;
            }
        }
        if (!empty($curriculumsemesteryears)) {
            foreach ($curriculumsemesteryears as $k => $curriculumsemesteryear) {
                $activeclass = '';
                $disabled = '';
                $yearname = strlen($curriculumsemesteryear->year) > 11 ? substr($curriculumsemesteryear->year, 0, 11) ."<span class='semesterrestrict'>..</span>": $curriculumsemesteryear->year;

                $curriculumsemesteryear->year = "<span title='".$curriculumsemesteryear->year."'>".$yearname."</span>";
                if ($curriculumsemesteryear->id == $yearid) {
                    $activeclass = 'active';
                }
             
                $canmanagesemesteryear = false;
                if ($userview && !is_siteadmin() && !has_capability('local/curriculum:createprogram', $systemcontext)) {
                    $yearrecordexists = $DB->record_exists('local_cc_session_signups',  array('userid' => $USER->id, 'yearid' => $curriculumsemesteryear->id));
                    if (!$yearrecordexists) {
                        $disabled = 'disabled';
                    }
                    $completion_status = $DB->get_field('local_cc_session_signups', 'completion_status', array('curriculumid'=>$curriculumid, 'yearid'=> $curriculumsemesteryear->id, 'userid' => $USER->id));

                    $curriculumsemesteryear->mycompletionstatus = '';
                    if ($userview && $completion_status == 1) {
                        $curriculumsemesteryear->mycompletionstatus = 'Completed';
                    }

                } else {
                    if (has_capability('local/curriculum:managesemesteryear', $systemcontext) || is_siteadmin()) {
                        $checkstudents = $DB->record_exists('local_cc_session_signups', array('curriculumid' => $curriculumid, 'yearid' => $curriculumsemesteryear->id));


                        if (($checkstudents || !$programtemplatestatus)) {
                            $canmanagesemesteryear = false;
                        } else {

               /* $sql = "SELECT * FROM {local_program} WHERE curriculumid = ".$curriculumid.' AND parentid > 0';
                $checkingaffiliate = $DB->get_records_sql($sql);*/
              //  print_object($checkingaffiliate);
                 $curriculummappedprogramid = $DB->get_field('local_program','id',array('curriculumid' => $curriculumid,'parentid' => 0));
                if(!empty($curriculummappedprogramid)){
                   $checkingafiliate = $DB->get_records_sql('SELECT id FROM {local_program} WHERE parentid = '.$curriculummappedprogramid);
                }
       
                if(!empty($checkingafiliate)){
                     $canmanagesemesteryear = false;
                  //  $canmanagesemesteryear = false;
                }
                else{
                     $canmanagesemesteryear = true;
                  //  $canmanagesemesteryear = true;
                }
                           // $canmanagesemesteryear = true;
                        }
                    }
                }
              
                $curriculumsemesteryear->myinprogressstatus = '';
                if ($userview && $completion_status == 0) {
                    $curriculumsemesteryear->myinprogressstatus = 'Inprogress';
                }
                $curriculumsemesteryear->active = $activeclass;
                $curriculumsemesteryear->disabled = $disabled;

                $semestercount_records = $DB->count_records('local_cc_session_signups',
                array('curriculumid' => $curriculumid, 'yearid' => $curriculumsemesteryear->id));

                $candeletesemesteryear = false;
                if ($semestercount_records > 0 && has_capability('local/curriculum:deletesemesteryear',
                    $systemcontext)) {
                    $candeletesemesteryear = false;
                } else if (has_capability('local/curriculum:deletesemesteryear', $systemcontext)) {
                    $candeletesemesteryear = true;
                }
                $curriculumsemesteryear->candeletesemesteryear = $candeletesemesteryear;
                $curriculumsemesteryear->canmanagesemesteryear = $canmanagesemesteryear;
                $curriculumsemesteryears[$k] = $curriculumsemesteryear;
            }
        }
         $duration_format = $DB->get_field('local_curriculum','duration_format',array('id' => $curriculumid));

        $curriculumsemesterscontext = [
            'contextid' => $systemcontext->id,
            'curriculumid' => $curriculumid,
            'cancreatesemesteryear' => has_capability('local/curriculum:createsemesteryear', $systemcontext),
            'canviewsemesteryear' => has_capability('local/curriculum:viewsemesteryear', $systemcontext),
            'canaddsemesteryear' => has_capability('local/curriculum:createsemesteryear', $systemcontext) || is_siteadmin(),
            'caneditsemesteryear' => has_capability('local/curriculum:editsemesteryear', $systemcontext) || is_siteadmin(),
            'cancreatesemester' => has_capability('local/curriculum:createsemester', $systemcontext) || is_siteadmin(),
            'canenrolcourse' => has_capability('local/curriculum:enrolcourse', $systemcontext) && !is_siteadmin(),
            'cfg' => $CFG,
            'yearid' => $yearid,
            'cantakeattendance' => has_capability('local/curriculum:takesessionattendance',
                $systemcontext) && !is_siteadmin(),

            'cansetcost' => has_capability('local/curriculum:cansetcost',
                $systemcontext) || is_siteadmin(),
            'userview' => $userview,
            'curriculumsemesteryears' => array_values($curriculumsemesteryears),
            'curriculumsemesteryear' => $this->viewcurriculumsemesteryear($curriculumid, $yearid)
        ];
      
           if($duration_format == 'M'){        
            $curriculumsemesterscontext['duration_diff'] = '1';
           }
           else{
             $curriculumsemesterscontext['duration_diff'] = '0';
           }
  
        $return = $this->render_from_template('local_curriculum/yearstab_content',
            $curriculumsemesterscontext);
        return $return;
    }
    public function viewcurriculumsemesteryear($curriculumid, $yearid) {
        global $OUTPUT, $CFG, $DB, $USER;
        require_once($CFG->dirroot.'/local/curriculum/program.php');

        $systemcontext = context_system::instance();

        $stable = new stdClass();
        $stable->curriculumid = $curriculumid;
        $stable->thead = false;
        $stable->start = 0;
        $stable->length = 1;
        $curriculum = (new program)->curriculums($stable);
        $programtemplatestatus = (new program)->programtemplatestatus($curriculum->program);
        $checkcopyprogram = (new program)->checkcopyprogram($curriculum->program);
        $ccuser = $DB->record_exists('local_curriculum_users',
            array('curriculumid' => $curriculumid, 'userid' => $USER->id));
        $userview = $ccuser && !is_siteadmin() && !has_capability('local/curriculum:createprogram', $systemcontext) ? true : false;

        $semestercount_records = $DB->count_records('local_curriculum_semesters',
                array('curriculumid' => $curriculumid, 'yearid' => $yearid));

        $curriculumsemesteryears = (new program)->curriculum_semesteryears($curriculumid, $yearid);

        $affiliatecolleges = $DB->count_records_sql('SELECT COUNT(id) FROM {local_program} WHERE parentid = :parentid AND costcenter <> :costcenter',  array('parentid' => $curriculum->id, 'costcenter' => $curriculum->costcenter));

        $copiedprograms = $DB->count_records_sql("SELECT COUNT(id) FROM {local_program} WHERE parentid = :parentid AND costcenter = :costcenter",  array('parentid' => $curriculum->id, 'costcenter' => $curriculum->costcenter));

        $managemyprogram = false;

        if (($curriculum->costcenter == $USER->open_costcenterid) || is_siteadmin()) {
            $managemyprogram = true;
        }

        if ($userview) {
            $mycompletedsemesteryears = (new program)->mycompletedsemesteryears($curriculumid, $USER->id);
            $notcmptlsemesteryears = (new program)->mynextsemesteryears($curriculumid);
            if (!empty($notcmptlsemesteryears)) {
                $nextsemester = $notcmptlsemesteryears[0];
            } else {
                $nextsemester = 0;
            }
        }
        $caneditsemester = ((has_capability('local/curriculum:editsemester', $systemcontext) || is_siteadmin()) && $programtemplatestatus);
        if (!empty($curriculumsemesteryears)) {
            foreach ($curriculumsemesteryears as $k => $curriculumsemesteryear) {
                $activeclass = '';
                $disabled = '';
                $semestername = strlen($curriculumsemesteryear->year) > 11 ? substr($curriculumsemesteryear->year, 0, 11) ."<span class='semesterrestrict'>..</span>": $curriculumsemesteryear->year;

                $curriculumsemesteryear->year = "<span title='".$curriculumsemesteryear->year."'>".$semestername."</span>";
                if ($curriculumsemesteryear->id == $yearid) {
                    $activeclass = 'active';
                }
              
                if ($userview && !is_siteadmin() && !has_capability('local/curriculum:createprogram', $systemcontext)) {
                    $yearrecordexists = $DB->record_exists('local_cc_session_signups',  array('userid' => $USER->id, 'yearid' => $curriculumsemesteryear->id));
                    if (!$yearrecordexists) {
                        $disabled = 'disabled';
                    }
                }

                $completion_status = $DB->get_field('local_cc_session_signups', 'completion_status', array('curriculumid' => $curriculumid, 'yearid' => $curriculumsemesteryear->id, 'userid' => $USER->id));

                $curriculumsemesteryear->mycompletionstatus = '';
                if ($userview && $completion_status == 1) {
                    $curriculumsemesteryear->mycompletionstatus = 'Completed';
                }

                $curriculumsemesteryear->myinprogressstatus = '';
                if ($userview && $completion_status == 0) {
                    $curriculumsemesteryear->myinprogressstatus = 'Inprogress';
                }

                $curriculumsemesteryear->active = $activeclass;
                $curriculumsemesteryear->disabled = $disabled;
                $semestercount_records = $DB->get_records('local_cc_session_signups',
                array('curriculumid' => $curriculumid));
                $candeletesemester = false;
                if (count($semestercount_records) > 0 && has_capability('local/curriculum:deletesemester',
                    $systemcontext)) {
                    $candeletesemester = false;
                } else if (has_capability('local/curriculum:deletesemester', $systemcontext)) {
                    $candeletesemester = true;
                }
                $curriculumsemesteryear->candeletesemester = $candeletesemester;
                $curriculumsemesteryears[$k] = $curriculumsemesteryear;
            }
        }

        $signupscount = $DB->count_records('local_cc_session_signups',
                array('curriculumid' => $curriculumid, 'yearid' => $yearid));
        $cancreatesemester = false;
        $curriculummappedprogramid = $DB->get_field('local_program','id',array('curriculumid' => $curriculumid,'parentid' => 0));
        if(!empty($curriculummappedprogramid)){
           $checkafiliate = $DB->get_records_sql('SELECT id FROM {local_program} WHERE parentid = '.$curriculummappedprogramid);
         }
        if(!empty($checkafiliate)){
             $cancreatesemester = false;
        }
        else{
             if (($signupscount > 0) && (has_capability('local/curriculum:createsemester', $systemcontext) || is_siteadmin()) && $programtemplatestatus) {
                $cancreatesemester = false;
             } else if ((has_capability('local/curriculum:createsemester', $systemcontext) || is_siteadmin()) && $programtemplatestatus) {
                  $cancreatesemester = true;
             }
        }

        $candoactions = false;
        if ($signupscount > 0 ) {
            $candoactions = false;
        } else {
            $candoactions = true;
        }
        $canaddcourse = false;
        $curriculummappedprogramid = $DB->get_field('local_program','id',array('curriculumid' => $curriculumid,'parentid' => 0));
        if(!empty($curriculummappedprogramid)){
           $chckingafiliate = $DB->get_records_sql('SELECT id FROM {local_program} WHERE parentid = '.$curriculummappedprogramid);
        }
        if(!empty($chckingafiliate)){
             $canaddcourse = false;
           }
           else{
        if (($signupscount > 0) && (has_capability('local/curriculum:addcourse', $systemcontext) || is_siteadmin())) {
            $canaddcourse = false;
        } else if ((has_capability('local/curriculum:addcourse', $systemcontext) || is_siteadmin()) && $programtemplatestatus) {
            $canaddcourse = true;
        }
        }

        $caneditcourse = false;
        if (($signupscount > 0) && (has_capability('local/curriculum:editcourse', $systemcontext) || is_siteadmin())) {
            $caneditcourse = false;
        } else {
            $caneditcourse = true;
        }

        $canmanagecourse = false;
        if (($signupscount > 0) && (has_capability('local/curriculum:managecourse', $systemcontext) || is_siteadmin())) {
            $canmanagecourse = false;
        } else {
            $canmanagecourse = true;
        }

        $signups = $DB->count_records('local_cc_session_signups',
                array('curriculumid' => $curriculumid, 'yearid' => $yearid));
        $canremovecourse = false;
        $cannotremovecourse = false;
        if ((($signups > 0 && has_capability('local/curriculum:removecourse',
                $systemcontext)) || $affiliatecolleges > 0) && $programtemplatestatus) {
            $canremovecourse = false;
            $cannotremovecourse = true;
        } else if (has_capability('local/curriculum:removecourse', $systemcontext) && $programtemplatestatus) {
            $canremovecourse = true;
            $cannotremovecourse = false;
        }
        $curriculummappedprogramid = $DB->get_field('local_program','id',array('curriculumid' => $curriculumid,'parentid' => 0));
          if(!empty($curriculummappedprogramid)){
           $checkingafiliate = $DB->get_records_sql('SELECT id FROM {local_program} WHERE parentid = '.$curriculummappedprogramid);
            if(!empty($checkingafiliate)){
            $canremovecourse = false;
          }
        }
       

        $yearsemestercontentcontext = array();

        $yearsemestercontentcontext['contextid'] = $systemcontext->id;
        $yearsemestercontentcontext['candoactions'] = $candoactions;
        $yearsemestercontentcontext['curriculumid'] = $curriculumid;
        $yearsemestercontentcontext['cancreatesemester'] = $cancreatesemester;
        $yearsemestercontentcontext['canviewsemester'] = has_capability('local/curriculum:viewsemester', $systemcontext);
        $yearsemestercontentcontext['caneditsemester'] = $caneditsemester;
        $yearsemestercontentcontext['canaddcourse'] = $canaddcourse;
        $yearsemestercontentcontext['caneditcourse'] = $caneditcourse;
        $yearsemestercontentcontext['canmanagecourse'] = $canmanagecourse;

        $yearsemestercontentcontext['canremovecourse'] = $canremovecourse;
        $yearsemestercontentcontext['cannotremovecourse'] = $cannotremovecourse;

        $canaddfaculty = false;
        if (((has_capability('local/curriculum:canaddfaculty', $systemcontext)) || is_siteadmin()) && !$checkcopyprogram) {
            $canaddfaculty = true;
        }
          
        $yearsemestercontentcontext['canaddfaculty'] = $canaddfaculty;

        $canmanagefaculty = false;
        if (((has_capability('local/curriculum:canmanagefaculty', $systemcontext) || is_siteadmin())) && !$checkcopyprogram) {
            $canmanagefaculty = true;
        }

        $yearsemestercontentcontext['canmanagefaculty'] = $canmanagefaculty;

        $yearsemestercontentcontext['canenrolcourse'] = ((has_capability('local/curriculum:enrolcourse', $systemcontext) || is_siteadmin()) && !$checkcopyprogram);

        $yearsemestercontentcontext['cfg'] = $CFG;
        $yearsemestercontentcontext['yearid'] = $yearid;
        $yearsemestercontentcontext['cantakeattendance'] = has_capability('local/curriculum:takesessionattendance',
                $systemcontext) && !is_siteadmin();
        $yearsemestercontentcontext['userview'] = $userview;
        $curriculumsemesters = (new program)->curriculumsemesteryear($curriculumid, $yearid);
        $semesters = false;
        if(count($curriculumsemesters) > 1){
            $semesters = true;
        }
        if ($ccuser && has_capability('local/curriculum:viewprogram', $systemcontext) && !is_siteadmin() && !has_capability('local/curriculum:trainer_viewprogram', $systemcontext) && !has_capability('local/curriculum:viewusers', $systemcontext)) {
            foreach ($curriculumsemesters as $curriculumsemester) {
                $courses = $DB->get_records_sql('SELECT c.id as courseid, c.fullname as course, ccsc.coursetype
                                                   FROM {course} c
                                                   JOIN {local_cc_semester_courses} ccsc ON ccsc.courseid = c.id
                                                   JOIN {local_curriculum_semesters} lcs ON lcs.id = ccsc.semesterid
                                                   JOIN {local_cc_session_signups} ccss ON ccsc.yearid = ccss.yearid
                                                   WHERE ccsc.semesterid = :semesterid AND ccss.userid = :userid AND ccss.yearid = :yearid ', array('semesterid' => $curriculumsemester->semesterid, 'userid' => $USER->id, 'yearid' => $yearid));
                $curriculumsemester->courses = array_values($courses);
            }
        } else if (has_capability('local/curriculum:trainer_viewprogram', $systemcontext) && !is_siteadmin()) {
            foreach ($curriculumsemesters as $curriculumsemester) {
                $courses = $DB->get_records_sql('SELECT c.id as courseid, c.fullname as course, ccsc.importstatus, ccsc.coursetype
                                                   FROM {course} c
                                                   JOIN {local_cc_semester_courses} ccsc ON ccsc.courseid = c.id
                                                   JOIN {local_curriculum_semesters} lcs ON lcs.id = ccsc.semesterid
                                                   JOIN {local_cc_session_trainers} ccst ON ccst.courseid = ccsc.courseid AND ccst.yearid = lcs.yearid
                                                   WHERE ccsc.semesterid = :semesterid AND ccst.trainerid = :trainerid AND ccst.yearid = :yearid ', array('semesterid' => $curriculumsemester->semesterid, 'trainerid' => $USER->id, 'yearid' => $yearid));
                $curriculumsemester->courses = array_values($courses);
            }
                $coursetypes = array();
                foreach($courses as $course){
                    $coursescheck = array();
                    if($course->coursetype == 1){
                        $courses[$course->courseid]->coursetype = true;
                    }else{
                        $courses[$course->courseid]->coursetype = false;
                    }
                }
            $curriculumsemester->caneditcurrentsemester = false;
            $curriculumsemester->candeletecurrentsemester = false;
        } else {
            foreach ($curriculumsemesters as $curriculumsemester) {
                $courses = $DB->get_records_sql('SELECT c.id as courseid, c.fullname as course, ccsc.importstatus, ccsc.coursetype
                                                   FROM {course} c
                                                   JOIN {local_cc_semester_courses} ccsc ON ccsc.courseid = c.id
                                                   WHERE ccsc.semesterid = :semesterid AND ccsc.yearid = :yearid', array('semesterid' => $curriculumsemester->semesterid, 'yearid' => $yearid));
                $curriculumsemester->courses = array_values($courses);
                
                $coursetypes = array();
                foreach($courses as $course){
                    $coursescheck = array();
                    if($course->coursetype == 1){
                        $courses[$course->courseid]->coursetype = true;
                        $exists = $DB->record_exists('course_completion_criteria',array('course' => $course->courseid));
                        if($exists){
                            $courses[$course->courseid]->completioncriteria = true;
                        }
                    }else{                      
                        $courses[$course->courseid]->coursetype = false;
                        $courses[$course->courseid]->completioncriteria = false;
                    }
                }
                
              
                $semesteruserscount = $DB->count_records('local_cc_session_signups', array('curriculumid' => $curriculumid, 'yearid' => $yearid));
                //print_object($curriculumsemester);

              //  $sql = "SELECT id FROM {local_program} WHERE curriculumid = ".$curriculumsemester->curriculumid.' AND parentid = 0';
                $curriculummappedprogramid = $DB->get_field('local_program','id',array('curriculumid' => $curriculumsemester->curriculumid,'parentid' => 0));
                if(!empty($curriculummappedprogramid)){
                   $checkingaffiliates = $DB->get_records_sql('SELECT id FROM {local_program} WHERE parentid = '.$curriculummappedprogramid);
                   
                }
               // print_object($checkingaffiliate);
                if(!empty($checkingaffiliates)){
                    $curriculumsemester->caneditcurrentsemester = false;
                    $curriculumsemester->candeletecurrentsemester = false;
                }
                else{
                     $curriculumsemester->caneditcurrentsemester = true;
                    $curriculumsemester->candeletecurrentsemester = true;
                }

               
                if ($semesteruserscount > 0) {
                    $curriculumsemester->caneditcurrentsemester = false;
                    $curriculumsemester->candeletecurrentsemester = false;
                }
            }
        }
        $coursesadded = $DB->record_exists('local_cc_semester_courses', array('curriculumid' => $curriculumid, 'yearid' => $yearid));
        $yearsemestercontentcontext['coursesadded'] = $coursesadded;
        $yearsemestercontentcontext['curriculumsemesters'] = array_values($curriculumsemesters);
        $yearsemestercontentcontext['curriculumsemesteryears'] = array_values($curriculumsemesteryears);
        $yearsemestercontentcontext['semesters'] = $semesters;
        $yearsemestercontentcontext['canimportcoursecontent'] = (has_capability('local/curriculum:importcoursecontent', $systemcontext) || is_siteadmin()) && $curriculum->admissionenddate < time();

        $return = $this->render_from_template('local_curriculum/yearsemestercontent',
            $yearsemestercontentcontext);
        return $return;
    }
    public function viewcurriculumusers($stable) {
        global $OUTPUT, $CFG, $DB;
        require_once($CFG->dirroot.'/local/curriculum/program.php');
        $curriculumid = $stable->curriculumid;
        $yearid = $stable->yearid;
       
        $assign_users = "";
        if ($stable->thead) {
            $curriculumusers = (new program)->curriculumusers($curriculumid, $stable);
            if ($curriculumusers['curriculumuserscount'] > 0) {
                $table = new html_table();
                $table->head = array(get_string('username', 'local_users'), get_string('employeeid', 'local_users'), get_string('email'), get_string('university', 'local_courses'),get_string('status'));
                $table->id = 'viewcurriculumusers';
                $table->attributes['data-curriculumid'] = $curriculumid;
                $table->attributes['data-yearid'] = $yearid;
                $table->align = array('left', 'left', 'left', 'center');
                $return = $assign_users.html_writer::table($table);
            } else {
                $return = $assign_users."<div class='mt-15px alert alert-info w-full pull-left'>" . get_string('nocurriculumusers', 'local_program') . "</div>";
            }
        } else {
            $curriculumusers = (new program)->curriculumusers($curriculumid, $stable);
            $data = array();
            foreach ($curriculumusers['curriculumusers'] as $sdata) {
                $university = $DB->get_field('local_costcenter', 'fullname', array('id'=>$sdata->open_costcenterid));

                $line = array();
                $line[] = $OUTPUT->user_picture($sdata) . ' ' . fullname($sdata);
                $line[] = $sdata->open_employeeid;
                $line[] = $sdata->email;
                $line[] = $university ? $university : 'N/A';
                $line[] = $sdata->completion_status == 1 ?'<span class="tag tag-success" title="Completed">&#10004;</span>' : '<span class="tag tag-danger" title="Not Completed">&#10006;</span>';
                $data[] = $line;
            }
            $return = array(
                "recordsTotal" => $curriculumusers['curriculumuserscount'],
                "recordsFiltered" => $curriculumusers['curriculumuserscount'],
                "data" => $data,
            );
        }
        return $return;
    }
     public function viewcoursefaculty($stable) {
        global $OUTPUT, $CFG, $DB;
        require_once($CFG->dirroot.'/local/curriculum/program.php');
        $curriculumid = $stable->curriculumid;
        $yearid = $stable->yearid;
        $semesterid = $stable->semesterid;
        $courseid = $stable->courseid;
        $search = $stable->search;
        $programid = $DB->get_field('local_curriculum', 'program', array('id' => $curriculumid));


       /* if (has_capability('local/curriculum:canmanagefaculty', context_system::instance())) {
            $url = new moodle_url('/local/curriculum/coursefaculty.php', array('yearid' => $yearid, 'semesterid' => $semesterid, 'courseid' => $courseid));
            $assign_users ='<ul class="course_extended_menu_list">
                                <li>
                                    <div class="createicon course_extended_menu_itemlink">
                                        <i href="javascript:void(0)" class="addfaculty icon fa fa-user-plus" title="Add Faculty" onclick="(function(e){ require(\'local_curriculum/ajaxforms\').init({contextid:1, component:\'local_curriculum\', callback:\'curriculum_managefaculty_form\', form_status:0, plugintype: \'local_curriculum\', pluginname: \'addfaculty\', id:0, curriculumid: ' . $curriculumid .', yearid: ' . $yearid .', programid: ' . $programid . ', semesterid : ' . $semesterid . ', courseid: ' . $courseid .' }) })(event)"> </i>
                                    </div>
                                </li>
                            </ul>';
        } else {
            $assign_users = "";
        }*/
        // $assign_users = "";

        if ($stable->thead) {
            $coursefaculty = (new program)->coursefaculty($yearid, $semesterid, $courseid, $stable);
            if ($coursefaculty['coursefacultycount'] > 0) {
                $table = new html_table();
                $table->head = array(get_string('faculty', 'local_curriculum'), get_string('email'), get_string('action'));
                $table->id = 'viewcoursefaculty';
                $table->attributes['data-yearid'] = $yearid;
                $table->attributes['data-semesterid'] = $semesterid;
                $table->attributes['data-courseid'] = $courseid;

                $return = $assign_users.html_writer::table($table);
            } else {
                $return = $assign_users."<div class='mt-15px alert alert-info w-full pull-left'>" . get_string('nocoursetrainers', 'local_program') . "</div>";
            }
        }/* else {
            $curriculumusers = (new program)->coursefaculty($yearid, $semesterid, $courseid, $stable);
            $data = array();
            foreach ($curriculumusers['coursefaculty'] as $sdata) {
                $line = array();
                $line[] = '<div>
                                <span>' . $OUTPUT->user_picture($sdata) . ' ' . fullname($sdata) . '</span>
                            </div>';
                $line[] = '<span> <label for="email">' . $sdata->email . '</lable></span>';

                $line[] = '<a href="javascript:void(0);" alt = ' . get_string('delete') . ' title = ' . get_string('delete') . ' onclick="(function(e){ require(\'local_curriculum/ajaxforms\').deleteConfirm({action:\'unassignfaculty\', programid: ' . $programid . ', curriculumid: ' . $curriculumid . ', yearid: ' . $yearid . ', semesterid: ' . $semesterid . ', courseid: ' . $courseid . ', trainerid: ' . $sdata->trainerid . ' }) })(event)" ><img src="' . $OUTPUT->image_url('t/delete') . '" alt = ' . get_string('delete') . ' title = "' . get_string('unassignfaculty', 'local_curriculum') . '" class="icon"/></a>';
                $data[] = $line;
            }
            $return = array(
                "recordsTotal" => $curriculumusers['coursefacultycount'],
                "recordsFiltered" => $curriculumusers['coursefacultycount'],
                "data" => $data,
            );
        }*/
        return $return;
    }
    function viewcoursefacultydata($stable){
          global $OUTPUT, $CFG, $DB;
        require_once($CFG->dirroot.'/local/curriculum/program.php');
        $curriculumid = $stable->curriculumid;
        $yearid = $stable->yearid;
        $semesterid = $stable->semesterid;
        $courseid = $stable->courseid;
        $search = $stable->search;
        $programid = $DB->get_field('local_curriculum', 'program', array('id' => $curriculumid));

        if(!$stable->head){
         $curriculumusers = (new program)->coursefaculty($yearid, $semesterid, $courseid, $stable);
            $data = array();
            $faculty_deatils = $curriculumusers['coursefaculty'];
            foreach ($faculty_deatils as $sdata) {
                $line = array();
                $line[] = '<div>
                                <span>'  . $sdata->firstname . '</span>
                            </div>';
                $line[] = '<span> <label for="email">' . $sdata->email . '</lable></span>';

                $line[] = '<a href="javascript:void(0);" alt = ' . get_string('delete') . ' title = ' . get_string('delete') . ' onclick="(function(e){ require(\'local_curriculum/ajaxforms\').deleteConfirm({action:\'unassignfaculty\', programid: ' . $programid . ', curriculumid: ' . $curriculumid . ', yearid: ' . $yearid . ', semesterid: ' . $semesterid . ', courseid: ' . $courseid . ', trainerid: ' . $sdata->trainerid . ' }) })(event)" ><img src="' . $OUTPUT->image_url('t/delete') . '" alt = ' . get_string('delete') . ' title = "' . get_string('unassignfaculty', 'local_curriculum') . '" class="icon"/></a>';
                $data[] = $line;
            }
            $return = array(
                "recordsTotal" => $curriculumusers['coursefacultycount'],
                "recordsFiltered" => $curriculumusers['coursefacultycount'],
                "data" => $data,
            );

        }
        return $return;
    }

    public function programstatusvalidation($id, $curriculumid, $costcenter){
        global $DB, $CFG;
        $programcurriculum_sql = "SELECT lcs.id as semesterid, lpccy.id, lpccy.year, lpccy.id as yearid, lcs.semester, lcs.totalcourses,lcs.curriculumid
                                    FROM {local_program_cc_years} lpccy 
                                    LEFT JOIN {local_curriculum_semesters} lcs 
                                      ON lpccy.id = lcs.yearid
                                   WHERE lpccy.curriculumid = $curriculumid" ;

        $programcurriculum = $DB->get_records_sql($programcurriculum_sql);
        $warningmessage = array();
        
        if(count($programcurriculum) > 0){
            $semcourses = array();
            $semcount = 0;
            $completionnotsetalert = array();
            $completionset = array();
            $completionnotset = array();
            foreach ($programcurriculum as $value) {
            if($value->totalcourses != 0){
                $semcount++;
                $ccsemcourses_sql = "SELECT ccsc.id, ccsc.courseid, ccsc.coursetype, c.fullname FROM {local_cc_semester_courses} ccsc JOIN {course} c ON c.id = ccsc.courseid
                                               WHERE semesterid = :semester 
                                                 AND curriculumid = :curriculumid";
                $ccsemcourses = $DB->get_records_sql($ccsemcourses_sql, array('curriculumid' => $curriculumid, 'semester' => $value->semesterid));

                $completionnotset = array();
                $mandatorycount_sql = "SELECT count(id) 
                                         FROM {local_cc_semester_courses} 
                                        WHERE semesterid = :semester 
                                          AND curriculumid = :curriculumid
                                          AND coursetype = :coursetype";
                $mandatorycount = $DB->count_records_sql($mandatorycount_sql, array('curriculumid' => $curriculumid,'semester' => $value->semesterid, 'coursetype'=>1));
                if($ccsemcourses){
                    foreach ($ccsemcourses as $course) {
                    if($mandatorycount){
                        if($course->coursetype == 1){
                            $semcourses[$course->courseid] = $course->courseid;
                            $totalcourses[$course->courseid] = $course->courseid;
                            $completionstatus_sql = "SELECT id, course 
                                                                   FROM {course_completion_criteria} 
                                                                  WHERE course = :course";
                            $completion = $DB->get_record_sql($completionstatus_sql, array('course' => $course->courseid));
                            if($completion){
                                $completionset[$course->courseid] = $course->fullname;
                            }else{
                              
                                $button = html_writer::tag('button',$course->fullname,array('class'=>'btn btn-primary'));
                                $completionnotset[$course->courseid] = html_writer::tag('a', $button, array('href' =>$CFG->wwwroot. '/course/view.php?id='.$course->courseid, 'target' => '_blank'));
                            }
                        } 
                    }else{
                        $emptysem_mandatorycourses[$value->semesterid] = $value->semester;
                        $completionnotset[$value->year.' / '.$value->semester] = 'Please add atleast one Mandatory course';
                    }
                    }
                }else{
                   $warningmessage['message'] = "Curriculum is not ready to Publish. Please assign course to semester";
                   $warningmessage['finalstatus'] = 'false';
                   return $warningmessage;
                }
                
                if(!empty($completionnotset)){
                    $completionnotsetalert[$value->year.' / '.$value->semester] = implode(' ', $completionnotset);
                }elseif($mandatorycount == 0){
                    $completionnotsetalert[$value->year.' / '.$value->semester] = 'Please add atleast one Mandatory course';
                }
            }else{
            
               $warningmessage['message'] = "Curriculum is not ready to Publish.  Please assign atleast one course to semester.";
               $warningmessage['finalstatus'] = 'false';
               return $warningmessage;
            }
        }
            
            if(empty($emptysem_mandatorycourses) && (count($semcourses) == count($completionset))){
                $warningmessage['finalstatus'] = 'true'; 
            }else{
                $warningmessage['finalstatus'] = 'false';
                $output = '';
                $output .= '<div>';
                foreach($completionnotsetalert as $key => $value){
                    $output .= "<div class='card'>
                                <div class='container'>
                                <h4>".$key."</h4> 
                                <p>".$value."</p>
                                </div></div>";
                }
                $output .= '<div class="alert alert-info">
                            <strong>Info!</strong> Please set the completion criteria for all Mandatory courses.</div>';
                $output .= '</div>';            
                $warningmessage['message'] = $output;
            }
        }else{
                $warningmessage['message'] = "Curriculum is not ready to Publish. Please complete the Curriculum template.";
                $warningmessage['finalstatus'] = 'false';
        }
        if($warningmessage['finalstatus'] == 'true'){
             $DB->execute("UPDATE {local_curriculum} SET curriculum_publish_status = 1 WHERE id = ".$curriculumid);
        }
        return $warningmessage;
    }
}

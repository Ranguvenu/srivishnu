<?php

class local_faculties_renderer extends plugin_renderer_base {

	public function get_dept_view_btns() {
        global $PAGE, $USER, $DB, $CFG;
        $systemcontext = context_system::instance();
        if (is_siteadmin() && $PAGE->pagetype == 'local-faculties-index'){
            $createdeptpopup = "<div class='course_contextmenu_extended'>";
            $createdeptpopup = "<a class='course_extended_menu_itemlink' data-action='createfacultymodal' data-value='0' title = 'Create faculty' onclick ='(function(e){ require(\"local_faculties/newfaculty\").init({selector:\"createfacultymodal\", contextid:$systemcontext->id, facultyid:0}) })(event)' ><span class='createicon'><i class='fa fa-sitemap icon' aria-hidden='true'></i><i class='createiconchild fa fa-plus' aria-hidden='true'></i></span></a>";
            
            $createdeptpopup .='<a id="extended_menu_createcourses" class="pull-right course_extended_menu_itemlink" title = "'.get_string('uploadfaculties','local_faculties').'" href = '.$CFG->wwwroot.'/local/faculties/upload/index.php>
                                    <i class="icon fa fa-upload" aria-hidden="true"></i>
                                </a>';
            $createdeptpopup .="</div>";                    
        }else{
            $createdeptpopup = '';
        }
        $buttons = [
            "gridview" => new moodle_url('/local/faculties/index.php'),
            // "treeview" => new moodle_url('/local/hierarchy/index.php'),
            "createdeptpopup" => $createdeptpopup
        ];
    return $this->render_from_template('local_faculties/viewbuttons', $buttons);
    }

    public function faculties_view($filterdata, $page, $perpage) {
        global $DB, $CFG, $OUTPUT, $USER,$PAGE;
        $systemcontext = context_system::instance();
        $formsql = '';
        $sql = "SELECT * FROM {local_faculties} lf WHERE 1=1";
        if(!is_siteadmin() && has_capability('local/costcenter:manage_ownorganization', $systemcontext)){
            $sql .= " AND lf.university = $USER->open_costcenterid";
        }
        if(!empty($filterdata->university)){
            $universityids = implode(',',$filterdata->university);
            $sql .= " AND lf.university IN($universityids)";
        }
        /*if(!empty($filterdata->boards)){
            $boardids = implode(',',$filterdata->boards);
            $sql .= " AND lf.board IN($boardids)";
        }
        if(!empty($filterdata->boardid)){
           // $boardids = implode(',',$filterdata->boards);
            $sql .= " AND lf.board IN($filterdata->boardid)";
        }*/// Commented by Harish for hiding boards functionality

        $recordsperpage = $page*$perpage;
        $formsql .=" ORDER BY lf.id DESC";
        // print_object($sql.$formsql);
        $faculties = $DB->get_records_sql($sql.$formsql);
        $table = new html_table();

        $table->head = array('Faculty Full Name','Faculty Code', /*'Board Name',// Commented by Harish for hiding boards functionality*/ 'Department Count' , 'University','Actions');
        $data = array();
        // if(!empty($faculties)){
            foreach ($faculties as $faculty) {
                $list=array();
              
                $list[] = $faculty->facultyname;
                $list[] = $faculty->facultycode;
                // $list[] = $this->faculty_board($faculty->board);
                $urlparam = array('faculty' => $faculty->id);
                $departmentcount= $DB->count_records_sql('SELECT count(id) FROM {local_costcenter} WHERE faculty ='.$faculty->id.' AND univ_dept_status = 0'); 
                if($departmentcount == 0){
                    $list[] = "<span style = 'color:#2739c1'>".$departmentcount."</span>";
                }
                else{
                $list[] = html_writer::link(new moodle_url('/local/departments/index.php', $urlparam),$departmentcount);  
                }
                $list[] = $this->faculty_university($faculty->university);
                  $list[] = $this->get_actions($faculty);
               	$data[] = $list;

            }
            // if (has_capability('local/faculties:manage', $systemcontext)){
                $table->align = array('left');
                $table->width = '100%';
                $table->data = ($data) ? $data : get_string('norecordsfound', 'local_costcenter');
                $table->id = 'faculty-index';
                $output = html_writer::table($table);
                if($filterdata->boardid){
                    $url = new moodle_url('/local/boards/index.php');
                    $button = new single_button($url, get_string('click','local_boards'), 'get', true);
                    $button->class = 'continuebutton';
                    $output .= $OUTPUT->render($button);
                }
            // }
        /*}else{
        	$output .= html_writer::tag('div', get_string('nofaculties', 'local_faculties'), array('class'=>'alert alert-info text-xs-center'));
        }*/
        return $output;
    }

    function faculty_university($universityid){
        global $DB;
        $university = $DB->get_field('local_costcenter', 'fullname', array('id' => $universityid));
        return $university;
    }

    function faculty_board($boardid){
        global $DB;
        $board = $DB->get_field('local_boards', 'fullname', array('id' => $boardid));
        return $board;
    }

    function get_actions($faculty) {
        global $DB, $USER, $OUTPUT;
        $context = context_system::instance();
            
            $buttons = array();
            if($faculty){
                $facultyprograms = $DB->count_records_sql("SELECT count(id) FROM {local_program} 
                                                        WHERE facultyid = $faculty->id");
            }
            $departsmentcount = $DB->count_records('local_costcenter',array('faculty' => $faculty->id));
            $buttons[] = html_writer::link('javascript:void(0)', '<i class="fa fa-cog fa-fw" title="Edit Faculty"></i>', array('data-action' => 'createfacultymodal', 'class'=>'createfacultymodal', 'data-value'=>$faculty->id, 'class' => '', 'onclick' =>"(function(e){ require(\"local_faculties/newfaculty\").init({selector:\"createfacultymodal\", contextid:$context->id, facultyid:$faculty->id}) })(event)",'style'=>'cursor:pointer' , 'title' => 'edit'));
            if($departsmentcount <= 0){
                $buttons[] = html_writer::link('javascript:void(0)', '<i class="fa fa-trash fa-fw" aria-hidden="true" title="Delete Faculty" aria-label="Delete"></i>', array('title' => get_string('delete'), 'onclick' => '(function(e){ require("local_faculties/newfaculty").deleteConfirm({ action: "delete_faculty" ,id:'.$faculty->id.',context:'.$context->id.',count:'.$facultyprograms.', fullname:"'.$faculty->facultyname.'"}) })(event)'));
            }else{
                $buttons[] = html_writer::link('javascript:void(0)', '<i class="fa fa-trash fa-fw" aria-hidden="true" title="Delete Faculty" aria-label="Delete"></i>', array('title' => get_string('delete'), 'onclick' => '(function(e){ require("local_departments/newdepartment").messageConfirm({ title: "confirm" ,message:"confirmessagedepartment",component:"local_faculties" }) })(event)'));
            }
            
            // OL11 ends here .
            return implode('', $buttons);
        }
}

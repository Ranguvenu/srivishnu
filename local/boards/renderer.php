<?php

class local_boards_renderer extends plugin_renderer_base {

    public function get_dept_view_btns() {
        global $PAGE, $USER, $DB, $CFG;
        $systemcontext = context_system::instance();
        if (is_siteadmin() && $PAGE->pagetype == 'local-boards-index'){
            $createdeptpopup = "<div class='course_contextmenu_extended'>";
            $createdeptpopup = "<a class='course_extended_menu_itemlink' data-action='createboardmodal' data-value='0' title = 'Create board' onclick ='(function(e){ require(\"local_boards/newboard\").init({selector:\"createboardmodal\", contextid:$systemcontext->id, boardid:0}) })(event)' ><span class='createicon'><i class='fa fa-sitemap icon' aria-hidden='true'></i><i class='createiconchild fa fa-plus' aria-hidden='true'></i></span></a>";
            
            $createdeptpopup .='<a id="extended_menu_createcourses" class="pull-right course_extended_menu_itemlink" title = "'.get_string('uploadboards','local_boards').'" href = '.$CFG->wwwroot.'/local/boards/upload/index.php>
                                    <i class="icon fa fa-upload" aria-hidden="true"></i>
                                </a>';
            $createdeptpopup .="</div>";                    
        }else{
            $createdeptpopup = '';
        }
        $buttons = [
            "gridview" => new moodle_url('/local/boards/index.php'),
            // "treeview" => new moodle_url('/local/hierarchy/index.php'),
            "createdeptpopup" => $createdeptpopup
        ];
    return $this->render_from_template('local_boards/viewbuttons', $buttons);
    }

    public function boards_view($filterdata, $page, $perpage) {
        global $DB, $CFG, $OUTPUT, $USER,$PAGE;
        $systemcontext = context_system::instance();
        $formsql = '';
        $sql = "SELECT * FROM {local_boards} lb WHERE 1=1";
        
        if(!is_siteadmin() && has_capability('local/costcenter:manage_ownorganization', $systemcontext)){
            $sql .= " AND lb.university = $USER->open_costcenterid";
        }
           if(!empty($filterdata->university)){
            $universityids = implode(',',$filterdata->university);
            $sql .= " AND lb.university IN($universityids)";
           }
        $recordsperpage = $page*$perpage;
        $formsql .=" ORDER BY lb.id DESC";
        $boards = $DB->get_records_sql($sql.$formsql);
        $table = new html_table();

        $table->head = array('Board Full Name','Board Short Name','Faculties','University','Actions');
        $data = array();
        // if(!empty($boards)){
            foreach ($boards as $board) {
                $list=array();
                $list[] = $board->fullname;
                $list[] = $board->shortname;
                $urlparam = array('id' => $board->id);
                $facultycount= $DB->count_records_sql('SELECT count(id) FROM {local_faculties} WHERE board ='.$board->id); 
                $list[] =  html_writer::link(new moodle_url('/local/faculties/index.php', $urlparam),$facultycount);                
                $list[] = $this->board_university($board->university);
                $list[] = $this->get_different_actions($board);
                $data[] = $list;
            }
            // if (has_capability('local/costcenter:manage', $systemcontext)){
                $table->align = array('left','left','left','left','center');
                $table->width = '100%';
                $table->data = ($data) ? $data : get_string('norecordsfound', 'local_costcenter');
                $table->id = 'board-index';
                $output = html_writer::table($table);
            // }
        /*}else{
            $output .= html_writer::tag('div', get_string('noboards', 'local_boards'), array('class'=>'alert alert-info text-xs-center'));
        }*/
        return $output;
    }

    function board_university($universityid){
        global $DB;
        $university = $DB->get_field('local_costcenter', 'fullname', array('id' => $universityid));
        return $university;
    }

     function get_different_actions($board) {
        global $DB, $USER, $OUTPUT;
        $context = context_system::instance();
            $buttons = array();
            if($board){
            $boardfaculties = $DB->count_records_sql("SELECT count(id) FROM {local_faculties} 
                                                        WHERE board = $board->id");
            }
            $buttons[] = html_writer::link('javascript:void(0)', '<i class="fa fa-cog fa-fw" title="Edit Board"></i>', array('data-action' => 'createboardmodal', 'class'=>'createboardmodal', 'data-value'=>$board->id, 'class' => '', 'onclick' =>"(function(e){ require(\"local_boards/newboard\").init({selector:\"createboardmodal\", contextid:$context->id, boardid:$board->id}) })(event)",'style'=>'cursor:pointer' , 'title' => 'edit'));
            
            $buttons[] = html_writer::link('javascript:void(0)', '<i class="fa fa-trash fa-fw" aria-hidden="true" title="Delete Board" aria-label="Delete"></i>', array('title' => get_string('delete'), 'onclick' => '(function(e){ require("local_boards/newboard").deleteConfirm({ action: "delete_board" ,id:'.$board->id.',context:'.$context->id.', count:'.$boardfaculties.', fullname:"'.$board->fullname.'"}) })(event)'));
            
            // OL11 ends here .
            return implode('', $buttons);
        }
    } 
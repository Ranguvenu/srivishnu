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
 * local courses rendrer
 *
 * @package    local_courses
 * @copyright  2017 eAbyas <eAbyas.in>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;
// use core_component;
class local_courses_renderer extends plugin_renderer_base {

     /**
     * [render_classroom description]
     * @method render_classroom
     * @param  \local_classroom\output\classroom $page [description]
     * @return [type]                                  [description]
     */
    public function render_courses(\local_courses\output\courses $page) {
        $data = $page->export_for_template($this);
        return parent::render_from_template('local_courses/courses', $data);
    }
    /**
     * [render_form_status description]
     * @method render_form_status
     * @param  \local_classroom\output\form_status $page [description]
     * @return [type]                                    [description]
     */
    public function render_form_status(\local_courses\output\form_status $page) {
        $data = $page->export_for_template($this);
        return parent::render_from_template('local_courses/form_status', $data);
    }
     /**
     * Display the avialable courses
     *
     * @return string The text to render
     */
     public function get_catalog_courses($filter = false) {
        $systemcontext = context_system::instance();
        $options = array('targetID' => 'manage_courses','perPage' => 10, 'cardClass' => 'col-md-6 col-12', 'viewType' => 'card');
        $options['methodName']='local_courses_courses_view';
        $options['templateName']='local_courses/catalog';
        $options = json_encode($options);
        $filterdata = json_encode(array());
        $dataoptions = json_encode(array('contextid' => $systemcontext->id));
        // print_r($options);
        // exit;
        $context = [
                'targetID' => 'manage_courses',
                'options' => $options,
                'dataoptions' => $dataoptions,
                'filterdata' => $filterdata
        ];
        // print_r($filterdata);
        // exit;
        if($filter){
            return  $context;
        }else{
            return  $this->render_from_template('local_costcenter/cardPaginate', $context);
        }
    }

    /**
     * Display the avialable categories list
     *
     * @return string The text to render
     */
    public function get_categories_list() {
        global $DB, $CFG, $OUTPUT, $PAGE ,$USER;
        require_once($CFG->dirroot.'/course/lib.php');
        require_once($CFG->libdir.'/coursecatlib.php');
        $PAGE->requires->js_call_amd('local_courses/newcategory', 'load');
        $context = context_system::instance();
        $categorylib = new local_courses\catslib();
        $categories = $categorylib->get_categories();
        $c = implode(',',$categories);
        $table = new html_table();
        $table->id = 'category_tbl';
        $table->head = array('','','','');
        $systemcontext = context_system::instance();
        $sql = "SELECT c.id, c.name, c.parent,c.visible,
        c.coursecount
        FROM {course_categories} as  c
        WHERE c.id IN ($c)";
        $update_lib = new local_courses\action\update();
        if(is_siteadmin()){
            $sql = "SELECT c.id, c.name, c.parent,c.visible,
                c.coursecount
                FROM {course_categories} as  c
                WHERE id > 1";
            
        }else if(has_capability('local/costcenter:manage_ownorganization',$systemcontext)){
            $orgcategories = $update_lib->get_categories($USER->open_costcenterid);
            $orgcategory_ids = implode(',',$orgcategories);
            $sql = "SELECT c.id,c.name,c.parent,c.visible,c.coursecount FROM {course_categories} as c WHERE c.id IN($orgcategory_ids)";
        }elseif(has_capability('local/costcenter:manage_owndepartments',$systemcontext)){
            $deptcategories = $update_lib->get_categories($USER->open_departmentid);
            $deptcategory_ids = implode(',',$deptcategories);
            $sql = "SELECT c.id,c.name,c.parent,c.visible,c.coursecount FROM {course_categories} as c WHERE c.id IN($deptcategory_ids)";
        }
        $sql .= " ORDER BY c.id DESC";
        $allcategories = $DB->get_records_sql($sql);
        $data = array();

        foreach($allcategories as $categories){
            $row = array();
            $categoryname = $categories->name;
            $categorycontext = context_coursecat::instance($categories->id);
            $count = html_writer::tag('a', $categories->coursecount, array('href' => 'javascript:void(0)','data-action' => 'popupmodal'.$categories->id, 'data-value'=>$categories->id));
            if($categories->visible ==0){
                $count =  $categories->coursecount;
            }
            $PAGE->requires->js_call_amd('local_courses/popup', 'init',
            array(array('selector'=>'[data-action=popupmodal'.$categories->id.']','contextid'=>$context->id,'categoryid'=>$categories->id,'categoryname'=>$categories->name)));
            $actions = '';
            if(has_capability('moodle/category:manage',$context)){
                $actions = true;
                if(!empty($categories->visible)){
                    $visible_value = 0;
                    $show = true;
                }else{
                    $visible_value = 1;
                    $show =  false;
                }
            }
            
            $categoryname_str = strlen($categoryname) > 25 ? substr($categoryname, 0, 25)."..." : $categoryname;
            if(!empty($categories->visible)) {
                $line['categoryname_str'] = $categoryname_str;
            } else {
                $line['categoryname_str'] = $categoryname_str;
            }
            if(!empty($categories->visible)){
                $line['catcount'] = $count;
            }else {
                $line['catcount'] = $count;
            }
            $line['categoryname'] = $categoryname;
            $line['catlisticon'] = $OUTPUT->image_url('catlist', 'local_courses');
            $line['catgoryid'] = $categories->id;
            $line['actions'] = $actions;
            $line['contextid'] = $context->id;
            $line['show'] = $show;
            $line['visible_value'] = $visible_value;
            $line['sesskey'] = sesskey();
            $row[] = $this->render_from_template('local_courses/categorylist', $line);
            $data[] = implode(' ',$row);
        }
        $catchunk=array_chunk($data,4);
        $chunk_data=array(""); 
        if(isset($catchunk[count($catchunk)-1])&& count($catchunk[count($catchunk)-1])!=4) { 
            if(count($catchunk[count($catchunk)-1])==1) { 

                $catchunk[count($catchunk)-1]=array_merge($catchunk[count($catchunk)-1],$chunk_data,$chunk_data); 

            }if(count($catchunk[count($catchunk)-1])==2) { 

                $catchunk[count($catchunk)-1]=array_merge($catchunk[count($catchunk)-1],$chunk_data,$chunk_data); 

            }if(count($catchunk[count($catchunk)-1])==3) { 

                $catchunk[count($catchunk)-1]=array_merge($catchunk[count($catchunk)-1],$chunk_data,$chunk_data); 
            }else {  
                $catchunk[count($catchunk)-1]=array_merge($catchunk[count($catchunk)-1],$chunk_data); 
            } 
        }
        $table->data = $catchunk;
		if ($data)
        $PAGE->requires->js_call_amd('local_courses/newcategory', 'catgoriesTableDatatable');
		else
		$out = '<div class="alert-box alert alert-info text-center clear-both">'.get_string('nocategories', 'local_courses').'</div>';
        $out .= html_writer::table($table);
        return $out;
    }
}

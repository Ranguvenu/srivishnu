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
 * General plugin functions.
 *
 * @package    local
 * @subpackage Costcenter
 * @copyright  2018 Eabyas Info Solutions <www.eabyas.in>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') or die;
define('ACTIVE',0);
define('IN_ACTIVE',1);
define('TOTAL',2);
use core_component;
require_once($CFG->dirroot . '/user/selector/lib.php');
require_once($CFG->dirroot . '/enrol/locallib.php');
require_once($CFG->dirroot . '/message/lib.php');

class costcenter {
    
    /*
     * @method get_costcenter_parent Get parent of the costcenter
     * @param object $costcenters costcenter data object
     * @param array $selected Costcenter position
     * @param boolean $inctop Include default value/not
     * @param boolean $all All option to select all values/not
     * @return array List of values
     */
    function get_costcenter_parent($costcenters, $selected = array(), $inctop = true, $all = false) {
        $out = array();

        //if an integer has been sent, convert to an array
        if (!is_array($selected)) {
            $selected = ($selected) ? array(intval($selected)) : array();
        }
        if ($inctop) {
            $out[null] = '---Select---';
        }
        if ($all) {
            $out[0] = get_string('all');
        }
        if (is_array($costcenters)) {
            foreach ($costcenters as $parent) {
                // An item cannot be its own parent and cannot be moved inside itself or one of its own children
                // what we have in $selected is an array of the ids of the parent nodes of selected branches
                // so we must exclude these parents and all their children
                //add using same spacing style as the bulkitems->move available & selected multiselects
                foreach ($selected as $key => $selectedid) {
                    if (preg_match("@/$selectedid(/|$)@", $parent->path)) {
                        continue 2;
                    }
                }
                if ($parent->id != null) {
                    $out[$parent->id] = format_string($parent->fullname);
                }
            }
        }

        return $out;
    }

    /*
    function get_universities(){
     * @method get_costcenter_items Get University list
     * @param boolean $fromcostcenter used to indicate called from costcenter plugin,using while error handling
     * @return list of costcenters
     * */
    function get_universities(){
        global $DB;
        $costcenters = array(null=>get_string('selectuniversity', 'local_costcenter'));
        $costcenters = $costcenters + $DB->get_records_sql_menu("SELECT id, fullname FROM {local_costcenter} WHERE visible = 1 AND parentid = 0");
        return $costcenters;
    }

    /*
     * @method get_costcenter_items Get costcenter list
     * @param boolean $fromcostcenter used to indicate called from costcenter plugin,using while error handling
     * @return list of costcenters
     * */
    function get_costcenter_items($fromcostcenter = NULL) {

        global $DB, $USER;
        $activecostcenterlist = $DB->get_records('local_costcenter', array('visible' => 1), 'sortorder, fullname');

        if (empty($fromcostcenter)) {
            if (empty($activecostcenterlist))
                print_error('notassignedcostcenter', 'local_costcenter');
        }
        
        $assigned_costcenters = costcenter_items();
        
        if (empty($fromcostcenter)) {
            if (empty($assigned_costcenters)) {
                print_error('notassignedcostcenter', 'local_costcenter');
            } else
                return $assigned_costcenters;
        } else
            return $assigned_costcenters;
    }
    /*
     * @method get_next_child_sortthread Get costcenter child list
     * @param  int $parentid which is id of a parent costcenter
     * @param  [string] $table is a table name 
     * @return list of costcenter children
     * */
    function get_next_child_sortthread($parentid, $table) {
        global $DB, $CFG;
        $maxthread = $DB->get_record_sql("SELECT MAX(sortorder) AS sortorder FROM {$CFG->prefix}{$table} WHERE parentid = :parentid", array('parentid' => $parentid));
        
        if (!$maxthread || strlen($maxthread->sortorder) == 0) {
            if ($parentid == 0) {
                // first top level item
                return $this->inttovancode(1);
            } else {
                // parent has no children yet
                return $DB->get_field('local_costcenter', 'sortorder', array('id' => $parentid)) . '.' . $this->inttovancode(1);
            }
        }
        return $this->increment_sortorder($maxthread->sortorder);
    }

    /**
     * Convert an integer to a vancode
     * @param int $int integer to convert.
     * @return vancode The vancode representation of the specified integer
     */
    function inttovancode($int = 0) {
        $num = base_convert((int) $int, 10, 36);
        $length = strlen($num);
        return chr($length + ord('0') - 1) . $num;
    }

    /**
     * Convert a vancode to an integer
     * @param string $char Vancode to convert. Must be <= '9zzzzzzzzzz'
     * @return integer The integer representation of the specified vancode
     */
    function vancodetoint($char = '00') {
        return base_convert(substr($char, 1), 36, 10);
    }

    /**
     * Increment a vancode by N (or decrement if negative)
     *
     */
    function increment_vancode($char, $inc = 1) {
        return $this->inttovancode($this->vancodetoint($char) + (int) $inc);
    }
    /**
     * Increment a sortorder by N (or decrement if negative)
     *
     */
    function increment_sortorder($sortorder, $inc = 1) {
        if (!$lastdot = strrpos($sortorder, '.')) {
            // root level, just increment the whole thing
            return $this->increment_vancode($sortorder, $inc);
        }
        $start = substr($sortorder, 0, $lastdot + 1);
        $last = substr($sortorder, $lastdot + 1);
        // increment the last vancode in the sequence
        return $start . $this->increment_vancode($last, $inc);
    }

    /**
     * @param [object] $costcenter
     */ 
    
    function get_enrolledcoursefilter_users_employeeids($costcenter, $like = false,$page = false, $filterid = false, $filterpage = false){
        global $DB;
        
        
        
        $sql = "SELECT u.idnumber as idnumber_key, u.idnumber as idnumber_value
            from {user} as u
            where u.deleted = 0 and u.suspended = 0";
        
        $systemcontext = context_system::instance();
        if($costcenter && !is_siteadmin() && !has_capability('local/assign_multiple_departments:manage',$systemcontext)) {
            $sql .= " AND u.open_costcenterid = :costcenter";
        }
        if($like){
            $sql .= " AND u.idnumber LIKE '%%$like%%'";
        }
        
        
        $sql .= " GROUP BY u.idnumber";
        $totalids = $DB->get_records_sql($sql, array('costcenter' => $costcenter));
        if($page > 1){
            $page = $page-1;
            $length = $page*50;
            $sql .= " LIMIT :length, 50";
        }else{
            $sql .= " LIMIT 0,50";
        }
        $employeeidslist = $DB->get_records_sql($sql, array('costcenter' => $costcenter , 'length' => $length));
        $allusersemployees = array();
        
        if($employeeidslist){
            foreach($employeeidslist as $employeeids){
                $data_id=preg_replace("/[^0-9,.]/", "", $employeeids->idnumber_value);
               $allusersemployees[] = ['id'=>$employeeids->idnumber_key,'filtername'=>$data_id];
            }
        }
        
        $dataobject = new \stdClass();
        $dataobject->total_count = count($totalids);
        $dataobject->incomplete_results = false;
        $dataobject->items = $allusersemployees;
        
        return $dataobject;
    }
    /**
     * @param [object] $costcenter
     */
    public function get_enrolledcoursefilter_users_emails($costcenter, $like = false,$page = 0, $filterid = false, $filterpage = false){
        global $DB;
        
        
        $sql = "SELECT u.id as uid,u.email
                    FROM {user} as u
                    WHERE u.deleted = 0 and u.suspended = 0 and u.id>2";
        
        $systemcontext = context_system::instance();
        if($costcenter && !is_siteadmin() && !has_capability('local/assign_multiple_departments:manage',$systemcontext)) {
            $sql .= " AND u.open_costcenterid = :costcenter";
        }
        if($like){
            $sql .= " AND u.email LIKE '%%$like%%'";
        }
        
        
        
        $totalemails = $DB->get_records_sql($sql, array('costcenter' => $costcenter));
        if($page){
            $page = $page-1;
            $length = $page*50;
            $sql .= " LIMIT :length, 50";
        }else{
            $sql .= " LIMIT 0,50";
        }
        
        $usersemails = $DB->get_records_sql($sql, array('costcenter' => $costcenters, 'length' => $length));
        $allusersemails = array();
        
        if($usersemails){
            foreach($usersemails as $usersemail){
                $allusersemails[] = ['id'=>$usersemail->uid,'filtername'=>$usersemail->email];
            }
        }
        $dataobject = new \stdClass();
        $dataobject->total_count = count($totalemails);
        $dataobject->incomplete_results = false;
        $dataobject->items = $allusersemails;
        
        return $dataobject;
    }
  

    /**
     * @param [object] $costcenter
     */
    public function get_enrolledcoursefilter_users_departments($costcenter, $like = false,$page = 0, $filterid = false, $filterpage = false){
        global $DB;
        
        $sql = "SELECT u.id as idnumber_value, u.open_department, c.fullname AS departmentname
                from {user} as u
                    JOIN {local_costcenter} AS c ON c.id = u.open_department";
                
        
        $systemcontext = context_system::instance();
        if($costcenter && !is_siteadmin() && !has_capability('local/assign_multiple_departments:manage',$systemcontext)) {
            $sql .= " AND u.open_costcenterid = :costcenter";
        }
        if(!empty($targetaudience)){
            $sql .= " AND u.open_department IN (:targetaudience) ";
        }
       else{
            $sql .= " GROUP by u.open_department ";
        }
        $totaldepartments = $DB->get_records_sql($sql , array('costcenter' => $costcenter, 'targetaudience' => $targetaudience));
        
        if($page){
            $page = $page-1;
            $length = $page*50;
            $sql .= " LIMIT :length, 50";
        }else{
            $sql .= " LIMIT 0,50";
        }
        $usersdepartment = $DB->get_records_sql($sql , array('costcenter' => $costcenter, 'targetaudience' => $targetaudience, 'length' => $length));
        $allusersdepartments = array();
    
        if($usersdepartment){
            foreach($usersdepartment as $usersdepartments){
                
                $allusersdepartments[] = ['id'=>$usersdepartments->open_department,'filtername'=>$usersdepartments->departmentname];
            }
        }
        $dataobject = new \stdClass();
        $dataobject->total_count = count($totaldepartments);
        $dataobject->incomplete_results = false;
        $dataobject->items = $allusersdepartments;
        
        return $dataobject;
    }
    /**
     * @param [object] $costcenter
     */
    public function get_enrolledcoursefilter_users_subdepartments($costcenter, $like = false,$page = 0, $filterid = false, $filterpage = false, $departments = false){
        global $DB;

        
        $sql = "SELECT u.id as idnumber_value, u.open_subdepartment, c.fullname AS departmentname
                    FROM {user} AS u
                    JOIN {local_costcenter} AS c ON c.id = u.open_subdepartment";  
        
        $systemcontext = context_system::instance();
        if($costcenter && !is_siteadmin() && !has_capability('local/assign_multiple_departments:manage',$systemcontext)) {
            $sql .= " AND u.open_costcenterid = :costcenter";
        }
        if(!empty($deptargetaudience)){
            $sql .= " AND u.open_department IN (:deptargetaudience) ";
        }
        if(!empty($departments)){
            $sql .= " AND u.open_department IN (:departments) ";
        }
        if(!empty($targetaudience)){
            $sql .= " AND u.open_subdepartment IN (:targetaudience) ";
        }
        
        $sql .= " GROUP by u.open_subdepartment ";
        
        $totalsubdepartments = $DB->get_records_sql($sql, array('costcenter' => $costcenter, 'deptargetaudience' => $deptargetaudience, 'departments' => $departments, 'targetaudience' => $targetaudience));
        
        if($page){
            $page = $page-1;
            $length = $page*50;
            $sql .= " LIMIT :length, 50";
        }else{
            $sql .= " LIMIT 0,50";
        }
        $userssubdepartments = $DB->get_records_sql($sql, array('costcenter' => $costcenter, 'deptargetaudience' => $deptargetaudience, 'departments' => $departments, 'targetaudience' => $targetaudience, 'length' => $length));
        $alluserssubdepartments = array();
         
        if($userssubdepartments){
            foreach($userssubdepartments as $userssubdepartment){
                
                $alluserssubdepartments[] = ['id'=>$userssubdepartment->open_subdepartment,'filtername'=>$userssubdepartment->departmentname];
            }
        }
        $dataobject = new \stdClass();
        $dataobject->total_count = count($totalsubdepartments);
        $dataobject->incomplete_results = false;
        $dataobject->items = $alluserssubdepartments;
        
        return $dataobject;
    }

        
    public function get_enrolledcoursefilter_users_costcenters($like = false,$page = 0, $filterid = false, $filterpage = false){
        global $DB;
        if($filterid && $filterpage){
            switch ($filterpage){
                case 'lp':
                    $users = $DB->get_record('local_learningplan',array('id'=>$filterid));
                    $targetaudience = $users->costcenter;
            }
        }
        $sql = "SELECT id,fullname from {local_costcenter} where visible =1 and parentid IN(0,1)";
        if(!empty($targetaudience)){
            $sql .= " AND id IN (:targetaudience) ";
        }
        
        if($like){
            $sql .= " AND fullname LIKE '%%$like%%'";
        }
        $total_costcenters = $DB->get_records_sql($sql, array('targetaudience' => $targetaudience));
        
        if($page){
            $page = $page-1;
            $length = $page*50;
            $sql .= " LIMIT :length, 50";
        }else{
            $sql .= " LIMIT 0,50";
        }
        $depts = $DB->get_records_sql($sql, array('targetaudience' => $targetaudience, 'length' => $length));
        
        $departments = array();
        if($depts){
            foreach($depts as $dept){
                $departments[] = ['id'=>$dept->id,'filtername'=>$dept->fullname];
            }
        }
        
        $dataobject = new \stdClass();
        $dataobject->total_count = count($total_costcenters);
        $dataobject->incomplete_results = false;
        $dataobject->items = $departments;
        
        return $dataobject;
    }
    
    /*Get uploaded course summary uploaded file
     * @param $course is an obj Moodle course
     * @return course summary file(img) src url if exists else return default course img url
     * */
    function get_course_summary_file($course){  
        global $DB, $CFG, $OUTPUT;
        if ($course instanceof stdClass) {
            require_once($CFG->libdir . '/coursecatlib.php');
            $course = new course_in_list($course);
        }
        
        // set default course image
        $url = $OUTPUT->image_url('/course_images/courseimg', 'local_costcenter');
        foreach ($course->get_course_overviewfiles() as $file) {
            $isimage = $file->is_valid_image();
            if($isimage)
            $url = file_encode_url("$CFG->wwwroot/pluginfile.php", '/' . $file->get_contextid() . '/' . $file->get_component() . '/' .
            $file->get_filearea() . $file->get_filepath() . $file->get_filename(), !$isimage);
        }
        return $url;
    }


    function get_costcenter_theme(){
        global $USER, $DB;

        if(!empty($costcentertheme = $DB->get_field('local_costcenter', 'theme', array('id' => $USER->open_costcenterid, 'visible' => 1)))){
            return $costcentertheme;
        }else{
            return false;
        }
    }

}
/**
 * Description: local_costcenter_pluginfile for fetching images in costcenter plugin
 * @param  [INT] $course        [course id]
 * @param  [INT] $cm            [course module id]
 * @param  [context] $context       [context of the file]
 * @param  [string] $filearea      [description]
 * @param  [array] $args          [array of ]
 * @param  [boolean] $forcedownload [to download or only view]
 * @param  array  $options       [description]
 * @return [file]                [description]
 */
function local_costcenter_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options=array()) {
        // Check the contextlevel is as expected - if your plugin is a block, this becomes CONTEXT_BLOCK, etc.

        // Make sure the filearea is one of those used by the plugin.
        if ($filearea !== 'costcenter_logo') {
            return false;
        }

        $itemid = array_shift($args);

        $filename = array_pop($args);
        if (!$args) {
            $filepath = '/';
        } else {
            $filepath = '/'.implode('/', $args).'/';
        }

        // Retrieve the file from the Files API.
        $fs = get_file_storage();
        $file = $fs->get_file($context->id, 'local_costcenter', $filearea, $itemid, $filepath, $filename);
        if (!$file) {
            return false;
        }
        send_file($file, $filename, 0, $forcedownload, $options);
    }
    /**
     * Description: get the logo specified to the organization.
     * @param  [INT] $costcenter_logo [item id of the logo]
     * @return [URL]                  [path of the logo]
     */
    function costcenter_logo($costcenter_logo) {
        global $DB;
        $costcenter_logourl = false;

        $sql = "SELECT * FROM {files} WHERE itemid = $costcenter_logo AND filename != '.' ORDER BY id DESC LIMIT 1";
        $costcenterlogorecord = $DB->get_record_sql($sql);

        if (!empty($costcenterlogorecord)){
            if($costcenterlogorecord->filearea=="costcenter_logo"){
                $costcenter_logourl = moodle_url::make_pluginfile_url($costcenterlogorecord->contextid, $costcenterlogorecord->component, $costcenterlogorecord->filearea, $costcenterlogorecord->itemid, $costcenterlogorecord->filepath, $costcenterlogorecord->filename);
            }
        }
        return $costcenter_logourl;
    }
/**
     * @method local_costcenter_output_fragment_new_costcenterform
     * @param  $args is an array   
     */
function local_costcenter_output_fragment_new_costcenterform($args){
 global $CFG,$DB;
    $args = (object) $args;
    $context = $args->context;
    $costcenterid = $args->costcenterid;
    $parentid = $args->parentid;
    if($args->child == 1){
        $child = 1;
    }
    else{
        $child = 0;
    }
    $o = '';
    $formdata = [];
    if (!empty($args->jsonformdata)) {
        $serialiseddata = json_decode($args->jsonformdata);
        parse_str($serialiseddata, $formdata);
    }
     if($costcenterid){
        $data = $DB->get_record('local_costcenter', array('id'=>$costcenterid));
    }
 

    // if ($args->costcenterid > 0) { //comment by revathi
    //     $heading = 'Update costcenter';
    //     $collapse = false;
    //     $data = $DB->get_record('local_costcenter', array('id'=>$costcenterid));
    // }
    // if ($parentid > 0) {
    //     $data->parentid=$parentid;
    // }
    // $editoroptions = [
    //     'maxfiles' => EDITOR_UNLIMITED_FILES,
    //     'maxbytes' => $course->maxbytes,
    //     'trust' => false,
    //     'context' => $context,
    //     'noclean' => true,
    //     'subdirs' => false
    // ];
    // $subdept = $args->subdept;
    // $dept = $args->dept;
    // $cid = $args->cid;
    // $group = file_prepare_standard_editor($group, 'description', $editoroptions, $context, 'group', 'description', null);

    // $mform = new local_costcenter\form\costcenterform(null, array('editoroptions' => $editoroptions,'subdept'=>$subdept,'dept'=>$dept,'cid' => $cid,'univ_dept_status' => $data->univ_dept_status,'parentid' => $parentid ,'child' => $child,'id' => $args->costcenterid), 'post', '', null, true, $formdata); //comment by revathi

//added revathi
    $mform = new local_costcenter\form\costcenterform(null, array(/*'editoroptions' => $editoroptions,'subdept'=>$subdept,'dept'=>$dept,'parentid' => $parentid ,*/ 'id' => $costcenterid), 'post', '', null, true, $formdata);

    //end added
    
    // print_r($data);
    // exit;
    $mform->set_data($data);
 
    if (!empty($formdata)) {
        // If we were passed non-empty form data we want the mform to call validation functions and show errors.
        $mform->is_validated();
    }
 
    ob_start();
    $mform->display();
    $o .= ob_get_contents();
    ob_end_clean();
    return $o;
} 
/**
 * Description: [organizations_filter code]
 * @param  [mform]  $mform          [form where the filetr is initiated]
 * @param  string  $query          [description]
 * @param  boolean $searchanywhere [description]
 * @param  integer $page           [description]
 * @param  integer $perpage        [description]
 * @return [type]                  [description]
 */
function organizations_filter($mform,$query='',$searchanywhere=false, $page=0, $perpage=25){
    global $DB,$USER;
    $systemcontext = context_system::instance();
    $organizationlist=array();
    $data=data_submitted();
    //print_r($data);
     //exit;
    if(is_siteadmin() || has_capability('local/costcenter:manage_multiorganizations', $systemcontext)){
        $organizationlist_sql="SELECT id, fullname FROM {local_costcenter} WHERE depth =1 AND visible= 1";
    }else{
        $organizationlist_sql="SELECT id, fullname FROM {local_costcenter} WHERE depth =1 AND id = $USER->open_costcenterid AND visible= 1";
    }
    if(!empty($query)){ 
        if ($searchanywhere) {
            $organizationlist_sql.=" AND fullname LIKE '%$query%' ";
        } else {
            $organizationlist_sql.=" AND fullname LIKE '$query%' ";
        }
    }
    if(isset($data->organizations)&&!empty(($data->organizations))&&is_array($data->organizations)){
    
        $implode=implode(',',$data->organizations);
        
        $organizationlist_sql.=" AND id in ($implode)";
    }
    // echo $organizationlist_sql;
    // exit;
    $organizationlist_sql.="  LIMIT $page, $perpage";
    if(!empty($query)||empty($mform)){ 
        $organizationlist = $DB->get_records_sql($organizationlist_sql);
        return $organizationlist;
    }
    if((isset($data->organizations)&&!empty($data->organizations))){ 
        $organizationlist = $DB->get_records_sql_menu($organizationlist_sql);
    }
    
    $options = array(
        'ajax' => 'local_courses/form-options-selector',
        'multiple' => true,
        'data-action' => 'organizations',
        'data-options' => json_encode(array('id' => 0)),
        'placeholder' => get_string('organisations','local_costcenter')
    );
    $select = $mform->addElement('autocomplete', 'organizations', '', $organizationlist,$options);
    $mform->setType('organizations', PARAM_RAW);
}
/**
  * Description: [departments_filter code]
 * @param  [mform]  $mform          [form where the filetr is initiated]
 * @param  string  $query          [description]
 * @param  boolean $searchanywhere [description]
 * @param  integer $page           [description]
 * @param  integer $perpage        [description]
 * @return [type]                  [description]
 */
function departments_filter($mform, $query='', $searchanywhere=false, $page=0, $perpage=25, $costcenter=null, $roleid=null){
    global $DB,$USER;
    $systemcontext = context_system::instance();
    $departmentslist=array();
    $data=data_submitted();

    if(is_siteadmin() || has_capability('local/costcenter:manage_multiorganizations', $systemcontext)){
        $departmentslist_sql="SELECT id, fullname FROM {local_costcenter} WHERE depth!=1 AND visible = 1 AND univ_dept_status IS NOT NUll";
    }else{
        $departmentslist_sql="SELECT id, fullname FROM {local_costcenter} WHERE depth!=1 AND visible = 1 AND univ_dept_status IS NOT NUll AND parentid = $USER->open_costcenterid";
    } 
    if(!empty($query)){ 
        if ($searchanywhere) {
            $departmentslist_sql.=" AND fullname LIKE '%$query%' ";
        } else {
            $departmentslist_sql.=" AND fullname LIKE '$query%' ";
        }
    }
    if(isset($data->departments)&&!empty(($data->departments))&&is_array($data->departments)){
        $implode=implode(',',$data->departments);
        $departmentslist_sql.=" AND id in ($implode)";
    }
    if(!empty($costcenter)){
        $departmentslist_sql.=" AND parentid = $costcenter"; 
    }// Added by Harish for fetching within Univ Dept/Colleges in mooc course enrollments functionality
    $departmentslist_sql.="  LIMIT $page, $perpage";
    
    if(!empty($query)||empty($mform)){ 
        $departmentslist = $DB->get_records_sql($departmentslist_sql);
        return $departmentslist;
    }
    if(!empty($costcenter)){
        $departmentslist = $DB->get_records_sql_menu($departmentslist_sql); 
    }// Added by Harish for fetching within Univ Dept/Colleges in mooc course enrollments functionality
    if((isset($data->departments)&&!empty($data->departments))){ 
        $departmentslist = $DB->get_records_sql_menu($departmentslist_sql);
    }
    $options = array(
            'ajax' => 'local_courses/form-options-selector',
            'multiple' => true,
            'data-action' => 'departments',
            'data-options' => json_encode(array('id' => 0, 'costcenter' => $costcenter)),
            'placeholder' => get_string('departmentcolleges','local_costcenter')
    );
        
    $select = $mform->addElement('autocomplete', 'departments', '', $departmentslist,$options);
    $mform->setType('departments', PARAM_RAW);
}
/**
 * Description: [insert costcenter instance ]
 * @param  [OBJECT] $costcenter [costcenter object]
 * @return [INT]             [created costcenter id]
 */
function costcenter_insert_instance($costcenter){
        global $DB, $CFG, $USER;
        require_once("$CFG->libdir/coursecatlib.php");

        $systemcontext = context_system::instance();
        if ($costcenter->parentid == 0) {
            $costcenter->depth = 1;
            $costcenter->path = '';
        } else {
            /* ---parent item must exist--- */
            $parent = $DB->get_record('local_costcenter', array('id' => $costcenter->parentid));
            $costcenter->depth = $parent->depth + 1;
            $costcenter->path = $parent->path;
        }
        /* ---get next child item that need to provide--- */
        $custom = new costcenter();
        if (!$sortorder = $custom->get_next_child_sortthread($costcenter->parentid, 'local_costcenter')) {
            return false;
        }

        if(empty($costcenter->parentid)){
            $costcenter->univ_dept_status = null;
        }elseif($costcenter->univ_dept_status == 0){
            // if($costcenter->univ_dept_status == 0)
            $costcenter->univ_dept_status = 0;
        }elseif($costcenter->univ_dept_status == 1){
            $costcenter->univ_dept_status = 1;
        }else{
           
             $costcenter->univ_dept_status = $costcenter->univ_dept_status;
        }
        
        $costcenter->sortorder = $sortorder;
        $parentid = $costcenter->parentid ?  $costcenter->parentid:0;
        $costcenter->costcenter_logo = $costcenter->costcenter_logo;
            file_save_draft_area_files($costcenter->costcenter_logo, $systemcontext->id, 'local_costcenter', 'costcenter_logo', $costcenter->costcenter_logo);
        $costcenter->id = $DB->insert_record('local_costcenter', $costcenter);
        
        if($costcenter->id) {
            $parentpath = $DB->get_field('local_costcenter', 'path', array('id'=>$parentid));
            $path = $parentpath.'/'.$costcenter->id;
            $datarecord = new stdClass();
            $datarecord->id = $costcenter->id;
            $datarecord->path = $path;
            $DB->update_record('local_costcenter',  $datarecord);
            
            $record = new stdClass();
            $record->name = $costcenter->fullname;
            $record->parent = $DB->get_field('local_costcenter', 'category', array('id'=>$parentid));
            $category = coursecat::create($record);
            $DB->execute("UPDATE {local_costcenter} SET category = $category->id WHERE id = $costcenter->id");

            if($category && $costcenter->univ_dept_status === 1){
                $DB->execute("UPDATE {local_costcenter} SET multipleorg = $costcenter->id  WHERE id = $costcenter->id");

            }
            if($category && $costcenter->univ_dept_status === 0){
                $DB->execute("UPDATE {local_costcenter} SET multipleorg = $costcenter->id  WHERE id = $costcenter->id");

                   //  $department->name = $costcenter->fullname;
                   //  $department->idnumber = $costcenter->shortname;
                   //  $department->university = $costcenter->parentid;
                   //  $department->faculty = $costcenter->faculty;
                   //  $department->description = $costcenter->description_editor['text'];
                   //  $department->descriptionformat = $costcenter->description_editor['format'];
                   // /* print_object($category->id);
                   //  $category = $DB->get_record_sql("SELECT category FROM {local_costcenter} WHERE id = ".$category->id);*/
                   //  $department->catid = $category->id;
                   //  $department->visible = 1 ;
                   //  $department->id = $DB->insert_record('local_departments', $department);
            }
        }
        return $costcenter->id;
    }
    /**
     * Description: [edit costcenter instance ]
     * @param  [INT] $costcenterid  [id of the costcenter]
     * @param  [object] $newcostcenter [update content]
     * @return [BOOLEAN]                [true if updated ]
     */
    function costcenter_edit_instance($costcenterid, $newcostcenter){
        global $DB,$CFG;
        $systemcontext = context_system::instance();
        $oldcostcenter = $DB->get_record('local_costcenter', array('id' => $costcenterid));
        $category = $DB->get_field('local_costcenter','category',array('id' => $newcostcenter->id));
        /* ---check if the parentid is the same as that of new parentid--- */
        if ($newcostcenter->parentid != $oldcostcenter->parentid) {
            $newparentid = $newcostcenter->parentid;
            $newcostcenter->parentid = $newparentid;
        }
        $today = time();
        $today = make_timestamp(date('Y', $today), date('m', $today), date('d', $today), 0, 0, 0);
        $newcostcenter->timemodified = $today;
        $newcostcenter->costcenter_logo = $newcostcenter->costcenter_logo;
            file_save_draft_area_files($newcostcenter->costcenter_logo, $systemcontext->id, 'local_costcenter', 'costcenter_logo', $newcostcenter->costcenter_logo);

        $costercenter = $DB->update_record('local_costcenter', $newcostcenter);
        $course_categories=$DB->record_exists('course_categories',array('id'=>$category));
        if($costercenter && $course_categories){
            $record = new stdClass();
            $record->id = $category;
            $record->name = $newcostcenter->fullname;
            $DB->update_record('course_categories', $record);
        }
        return true;
    }
    /**
     * [costcenter_items description]
     * @return [type] [description]
     */
    function costcenter_items(){
        global $DB, $USER;
        $assigned_costcenters = '';
        $systemcontext = context_system::instance();
        if (is_siteadmin() || has_capability('local/costcenter:manage_multiorganizations', $systemcontext)) {
                       $sql="SELECT * from {local_costcenter} where visible=1 AND depth <3 ORDER by sortorder,fullname ";
            $assigned_costcenters = $DB->get_records_sql($sql);
        } else {
             $sql="SELECT * from {local_costcenter} where visible=1 and (id = $USER->open_costcenterid or parentid=$USER->open_costcenterid) ORDER by sortorder,fullname";
            $assigned_costcenters = $DB->get_records_sql($sql);
        }
        return $assigned_costcenters;
    }
/*
* Author Rizwana
* Displays a node in left side menu
* @return  [type] string  link for the leftmenu
*/
function local_costcenter_leftmenunode(){
    global $USER;
    $systemcontext = context_system::instance();
    $costcenternode = '';
    if(has_capability('local/costcenter:manage', $systemcontext) || is_siteadmin()) {     
        $costcenternode .= html_writer::start_tag('li', array('id'=> 'id_leftmenu_departments', 'class'=>'pull-left user_nav_div departments dropdown-item'));
        if(is_siteadmin() || has_capability('local/costcenter:manage_multiorganizations', $systemcontext)){
            $organization_url = new moodle_url('/local/costcenter/index.php');
            $organization_string = get_string('orgStructure','local_costcenter');
        }else{
            $organization_url = new moodle_url('/local/costcenter/costcenterview.php',array('id' => $USER->open_costcenterid));
            $organization_string = get_string('department_structure','local_costcenter');
        }
        $department = html_writer::link($organization_url, '<i class="fa fa-sitemap" aria-hidden="true" aria-label=""></i><span class="user_navigation_link_text">'.$organization_string.'</span>',array('class'=>'user_navigation_link'));
        $costcenternode .= $department;
        $costcenternode .= html_writer::end_tag('li');
    }

    return array('1' => $costcenternode);
}

/*
* Author sarath
* @return  plugins count with all modules
*/
function local_costcenter_plugins_count($costcenterid,$departmentid=false,$category=false){
    global $CFG;
    $core_component = new core_component();
    $local_pluginlist = $core_component::get_plugin_list('local');
    $deparray = array();
    foreach($local_pluginlist as $key => $local_pluginname){
        if(file_exists($CFG->dirroot.'/local/'.$key.'/lib.php')){
            require_once($CFG->dirroot.'/local/'.$key.'/lib.php');
            $functionname = 'costcenterwise_'.$key.'_count';
            if(function_exists($functionname)){
                $data = $functionname($costcenterid,$departmentid,$category);
                foreach($data as  $key => $val){
                    $deparray[$key] = $val;
                }
            }
        }
    }
    return $deparray;
}

function local_costcenter_quicklink_node(){
    global $DB, $CFG, $USER;
    $systemcontext = context_system::instance();
    $university_content = '';
    if(is_siteadmin()){
        $count = $DB->count_records('local_costcenter', array('parentid'=>0));
        $string = get_string('manage_universities', 'local_costcenter');
        $link = new moodle_url('/local/costcenter/index.php');

    }elseif(has_capability('local/costcenter:manage_ownorganization',$systemcontext)){
        // <mallikarjun> - ODL-859 displaying colleges and departments for university head-- starts
        
        //$count = $DB->count_records('local_costcenter', array('parentid'=>$USER->open_costcenterid, 'univ_dept_status' => 1));
        //$string = get_string('managecostcenters', 'local_costcenter');        
         $count = $DB->count_records('local_costcenter', array('parentid'=>$USER->open_costcenterid));
        $string = get_string('deptcollege', 'local_program');
        // <mallikarjun> - ODL-859 displaying colleges and departments for university head-- ends
        $link = new moodle_url('/local/costcenter/costcenterview.php',array('id' => $USER->open_costcenterid));
    }
    if(is_siteadmin() || has_capability('local/costcenter:manage_ownorganization',$systemcontext)){
        $university_content .= '<div class="w-full pull-left list_wrapper green_block">
                                <div class="w-full pull-left top_content">
                                    <span class="pull-left quick_links_icon"><i class="fa fa-university" aria-hidden="true" aria-label=""></i></span>
                                    <span class="pull-right quick_links_count">
                                    <a href="'.$link.'">'.$count.'</a></span>
                                </div>
                                <div class="w-100 pull-left pl-15px pr-15px"><a class="quick_link" href="'.$link.'">'.$string.'</a></div>
                            </div>';
    }
    return array('0' => $university_content);
}
function subcollege_filter($mform){
   global $DB,$USER;
    $systemcontext = context_system::instance();
    if(is_siteadmin()){
    $collegelist = $DB->get_records_sql_menu("SELECT id, fullname FROM {local_costcenter} WHERE univ_dept_status = 1 AND parentid != 0 AND visible = 1");
    }
    else if($USER->id){
        $collegelist = $DB->get_records_sql_menu("SELECT id, fullname FROM {local_costcenter} WHERE univ_dept_status = 1 AND parentid = $USER->open_costcenterid AND visible = 1");
    }
    $select = $mform->addElement('autocomplete', 'college', '', $collegelist, array('placeholder' => get_string('college', 'local_costcenter')));
    $mform->setType('college', PARAM_RAW);
    $select->setMultiple(true);  
}
function department_filter($mform){
   global $DB,$USER;
    $systemcontext = context_system::instance();
    if(is_siteadmin()){
      $sql = "SELECT id, fullname FROM {local_costcenter} WHERE univ_dept_status = 0 AND parentid != 0 AND visible = 1";
    }
    else{
      $sql = "SELECT id, fullname FROM {local_costcenter} WHERE univ_dept_status = 0 AND parentid = $USER->open_costcenterid AND visible = 1";
    }
    $departmentslist = $DB->get_records_sql_menu($sql);
    $select = $mform->addElement('autocomplete', 'department', '', $departmentslist, array('placeholder' => get_string('departments', 'local_costcenter')));
    $mform->setType('department', PARAM_RAW);
    $select->setMultiple(true);  
}
function subdepartment_filter($mform){
   global $DB,$USER;
    $systemcontext = context_system::instance();
  
        $departmentlist = $DB->get_records_sql_menu("SELECT id, name FROM {local_departments}");
    $select = $mform->addElement('autocomplete', 'department', '', $departmentlist, array('placeholder' => get_string('departments', 'local_costcenter')));
    $mform->setType('department', PARAM_RAW);
    $select->setMultiple(true);  
}

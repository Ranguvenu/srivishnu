<?php
define('AJAX_SCRIPT', true);
require(dirname(__FILE__) . '/../../config.php');
require_once($CFG->dirroot.'/local/courses/lib.php');
global $CFG,$DB,$OUTPUT,$PAGE,$USER;
$cat = optional_param('cat', 0, PARAM_INT); // category id
$orgid = optional_param('prog', 0, PARAM_INT); // costcenterid
$categoryfield = optional_param('category',  0,  PARAM_INT); // category id
$action = optional_param('action',  '',  PARAM_RAW);
$costcenterid = optional_param('costcenter',  0,  PARAM_INT); // costcenter id

if($action=='collegelist' && $costcenterid){
    $college_sql = "SELECT id, fullname FROM {local_costcenter} WHERE parentid =  $costcenterid";
    $data = $DB->get_records_sql($college_sql);
    echo json_encode(['data' => $data]);

}
if (!empty($cat) && $categoryfield) {
    $sql  = "SELECT category FROM {local_costcenter} WHERE  id = ?";
    $costcentercategory = $DB->get_field_sql($sql, array($cat));
	if ($costcentercategory)
	   $data = $DB->get_records_sql_menu("SELECT id,name from {course_categories} where (path like '/$costcentercategory/%' OR id =$costcentercategory) AND visible=1");
	
    echo json_encode($data);
} else if (!empty($orgid)) {
    $sql  = "SELECT id,fullname FROM {local_costcenter} WHERE parentid = $orgid AND univ_dept_status = 0 AND visible = 1 ORDER BY `id` DESC";
    $departmentlist = $DB->get_records_sql_menu($sql);

	/*$courselib = new local_courses\action\update();
    $systemcontext = context_system::instance();
    if(is_siteadmin() OR has_capability('local/costcenter:manage_ownorganizations',$systemcontext)){
        $orgcategories = $courselib->get_categories($orgid);
        $orgcategoryids = implode(',',$orgcategories);
        $sql = "SELECT c.id,c.name FROM {course_categories} as c WHERE c.visible = 1 AND c.id IN ($orgcategoryids)";        
    } else if(has_capability('local/costcenter:manage_ownorganization',$systemcontext)){
        $orgcategories = $courselib->get_categories($USER->open_costcenterid);
        $orgcategoryids = implode(',',$orgcategories);
        $sql = "SELECT c.id,c.name FROM {course_categories} as c WHERE c.visible = 1 AND c.id IN ($orgcategoryids)";
    } elseif(has_capability('local/costcenter:manage_owndepartments',$systemcontext)){
        $deptcategories = $courselib->get_categories($USER->open_departmentid);
        $deptcategoryids = implode(',',$deptcategories);
        $sql = "SELECT c.id,c.name FROM {course_categories} as c WHERE c.visible = 1 AND c.id IN ($deptcategoryids)";
    }
    $sql .= " ORDER BY c.id DESC";
    $allcategories = $DB->get_records_sql_menu($sql);*/

    $return_array = array('department'=>$departmentlist);
    echo json_encode($return_array);
} else if($categoryfield){
    $parentcategory = $DB->get_field('local_costcenter','category',array('id'=>$USER->open_costcenterid));
	if(is_siteadmin())
		$displaylist = $DB->get_records_sql_menu("select id,name from {course_categories} where visible=1"); 
	else
		$displaylist = $DB->get_records_sql_menu("select id,name from {course_categories}
                                                      where (path like '/$parentcategory/%' or path like '%/$parentcategory' or path like '%/$parentcategory/%') AND visible=1");
	echo json_encode($displaylist);
}

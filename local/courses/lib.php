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
 * Code for handling mass enrolment from a cvs file
 *
 *
 * @package local
 * @subpackage courses
 * @copyright eAbyas <www.eabyas.in>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
if(file_exists($CFG->dirroot.'/local/costcenter/lib.php')){
    require_once($CFG->dirroot.'/local/costcenter/lib.php');
}
require_once($CFG->dirroot . '/user/selector/lib.php');
require_once($CFG->libdir.'/completionlib.php');
require_once($CFG->dirroot.'/completion/completion_completion.php');
use \local_courses\form\custom_course_form as custom_course_form;


defined('MOODLE_INTERNAL') || die();


function get_user_courses($userid) {
    global $DB;
    $courses_sql = "SELECT course.id,course.fullname,course.summary FROM {course} AS course
                JOIN {enrol} AS e ON course.id = e.courseid AND e.enrol IN('self','manual','auto')
                JOIN {user_enrolments} ue ON e.id = ue.enrolid
                WHERE ue.userid = {$userid} AND FIND_IN_SET(3,course.open_identifiedas) AND course.id>1";
    $return = $DB->get_records_sql($courses_sql);
    return $return;  
}
/**
 * process the mass enrolment
 * @param csv_import_reader $cir  an import reader created by caller
 * @param Object $course  a course record from table mdl_course
 * @param Object $context  course context instance
 * @param Object $data    data from a moodleform
 * @return string  log of operations
 */
function mass_enroll($cir, $course, $context, $data) {
    global $CFG,$DB;
    require_once ($CFG->dirroot . '/group/lib.php');

    $result = '';

    $courseid=$course->id;
    $roleid = $data->roleassign;
    $useridfield = $data->firstcolumn;

    $enrollablecount = 0;
    $createdgroupscount = 0;
    $createdgroupingscount = 0;
    $createdgroups = '';
    $createdgroupings = '';


    $plugin = enrol_get_plugin('manual');
    //Moodle 2.x enrolment and role assignment are different
    // make sure couse DO have a manual enrolment plugin instance in that course
    //that we are going to use (only one instance is allowed @see enrol/manual/lib.php get_new_instance)
    // thus call to get_record is safe
    $instance = $DB->get_record('enrol', array('courseid' => $course->id, 'enrol' => 'manual'));
    if (empty($instance)) {
        // Only add an enrol instance to the course if non-existent
        $enrolid = $plugin->add_instance($course);
        $instance = $DB->get_record('enrol', array('id' => $enrolid));
    }


    // init csv import helper
    $cir->init();
    while ($fields = $cir->next()) {
        $a = new stdClass();
        if (empty ($fields))
        continue;
        $coscenter=$DB->get_field('course','open_costcenterid',array('id'=>$course->id));
        $coscenter_name=$DB->get_field('local_costcenter','shortname',array('id'=>$coscenter));

        $string=strtolower($coscenter_name);

        // 1st column = id Moodle (idnumber,username or email)
        // get rid on eventual double quotes unfortunately not done by Moodle CSV importer
        /*****Checking with all costcenter*****/

        $fields[0]= str_replace('"', '', trim($fields[0]));
        $fieldcontcat=$string.$fields[0];
        /******The below code is for the AH checking condtion if AH any user can be enrolled else if OH only his costcenter users enrol*****/
        $id=$DB->get_field('course','open_costcenterid',array('id'=>$course->id));
        $systemcontext = context_system::instance();
        if(!is_siteadmin()  && has_capability('local/assign_multiple_departments:manage', $systemcontext)){
            $sql=" ";
        }else{
            $sql=" and u.open_costcenterid=$id ";
        }

        /*First Condition To validate users*/
        $sql="select u.* from {user} u where u.deleted=0 and u.suspended=0 and u.$useridfield='$fields[0]' $sql ";

        if (!$user = $DB->get_record_sql($sql)) {
            $result .= '<div class="alert alert-error">'.get_string('im:user_unknown', 'local_courses', $fields[0] ). '</div>';
            continue;
        }

        $id=$DB->get_field('course','open_costcenterid',array('id'=>$course->id));
        /** The below code is for the AH checking condtion if AH any user can be enrolled else if OH only his costcenter users enrol **/
        if(!is_siteadmin()  && has_capability('local/assign_multiple_departments:manage', $systemcontext)){

            $sql=" ";
        }else{

            $sql=" open_costcenterid=$id AND ";
        }
        /*Second Condition To validate users*/
        if(!$DB->record_exists_sql("select id from {user} where $sql  id=$user->id")){

            $costcentername = $DB->get_field('local_costcenter','fullname',array('id'=>$course->costcenter));
            $cs_object = new stdClass();
            $cs_object->csname = $costcentername;
            $cs_object->user   = fullname($user);
            $result .= '<div class="alert alert-error">'.get_string('im:user_notcostcenter', 'local_courses',$cs_object ). '</div>';
            continue;
        }

        //already enroled ?

        $instance_auto = $DB->get_field('enrol', 'id',array('courseid' => $course->id, 'enrol' => 'auto'));
        $instance_self = $DB->get_field('enrol', 'id',array('courseid' => $course->id, 'enrol' => 'self'));

        if(!$instance_auto){
         $instance_auto=0;

        }
        if(!$instance_self){
            $instance_self=0;
        }

        $enrol_ids=$instance_auto.",".$instance_self.",".$instance->id;

        $sql="select id from {user_enrolments} where enrolid IN ($enrol_ids) and userid=$user->id";
        $enrolormnot=$DB->get_field_sql($sql);

        if (user_has_role_assignment($user->id, $roleid, $context->id)) {
            $result .= '<div class="alert alert-error">'.get_string('im:already_in', 'local_courses', fullname($user)). '</div>';

        } elseif($enrolormnot){
         $result .= '<div class="alert alert-error">'.get_string('im:already_in', 'local_courses', fullname($user)). '</div>';
         continue;
        }else {
            //TODO take care of timestart/timeend in course settings
            // done in rev 1.1
            $timestart=$DB->get_field('course','startdate',array('id'=>$course->id));
            $timeend=0;
            // not anymore so easy in Moodle 2.x
            // Enrol the user with this plugin instance (unfortunately return void, no more status )
            $plugin->enrol_user($instance, $user->id,$roleid,$timestart,$timeend);

            $result .= '<div class="alert alert-success">'.get_string('im:enrolled_ok', 'local_courses', fullname($user)).'</div>';
            $enrollablecount++;
        }

        $group = str_replace('"','',trim($fields[1]));
        // 2nd column ?
        if (empty ($group)) {
            $result .= "";
            continue; // no group for this one
        }

        // create group if needed
        if (!($gid = mass_enroll_group_exists($group, $courseid))) {
            if ($data->creategroups) {
                if (!($gid = mass_enroll_add_group($group, $courseid))) {
                    $a->group = $group;
                    $a->courseid = $courseid;
                    $result .= '<div class="alert alert-error">'.get_string('im:error_addg', 'local_courses', $a) . '</div>';
                    continue;
                }
                $createdgroupscount++;
                $createdgroups .= " $group";
            } else {
                $result .= '<div class="alert alert-error">'.get_string('im:error_g_unknown', 'local_courses', $group) . '</div>';
                continue;
            }
        }

        // if groupings are enabled on the site (should be ?)
        // if ($CFG->enablegroupings) { // not anymore in Moodle 2.x
        if (!($gpid = mass_enroll_grouping_exists($group, $courseid))) {
            if ($data->creategroupings) {
                if (!($gpid = mass_enroll_add_grouping($group, $courseid))) {
                    $a->group = $group;
                    $a->courseid = $courseid;
                    $result .= '<div class="alert alert-error">'.get_string('im:error_add_grp', 'local_courses', $a) . '</div>';
                    continue;
                }
                $createdgroupingscount++;
                $createdgroupings .= " $group";
            } else {
                // don't complains,
                // just do the enrolment to group
            }
        }
        // if grouping existed or has just been created
        if ($gpid && !(mass_enroll_group_in_grouping($gid, $gpid))) {
            if (!(mass_enroll_add_group_grouping($gid, $gpid))) {
                $a->group = $group;
                $result .= '<div class="alert alert-error">'.get_string('im:error_add_g_grp', 'local_courses', $a) . '</div>';
                continue;
            }
        }
        //}

        // finally add to group if needed
        if (!groups_is_member($gid, $user->id)) {
            $ok = groups_add_member($gid, $user->id);
            if ($ok) {
                $result .= '<div class="alert alert-success">'.get_string('im:and_added_g', 'local_courses', $group) . '</div>';
            } else {
                $result .= '<div class="alert alert-error">'.get_string('im:error_adding_u_g', 'local_courses', $group) . '</div>';
            }
        } else {
            $result .= '<div class="alert alert-notice">'.get_string('im:already_in_g', 'local_courses', $group) . '</div>';
        }

    }
    $result .= '<br />';
    //recap final
    $result .= get_string('im:stats_i', 'local_courses', $enrollablecount) . "";
    $a->nb = $createdgroupscount;
    if(!isset($createdgroups) || empty($createdgroups)||$createdgroups='')
    $a->what = '-';
    else
    $a->what = $createdgroups;
    $result .= get_string('im:stats_g', 'local_courses', $a) . "";
    $a->nb = $createdgroupingscount;
    if(!isset($createdgroupings) || empty($createdgroupings)||$createdgroupings='')
    $a->what = '-';
    else
    $a->what = $createdgroupings;
    $result .= get_string('im:stats_grp', 'local_courses', $a) . "";

    return $result;
}


/**
 * Enter description here ...
 * @param string $newgroupname
 * @param int $courseid
 * @return int id   Moodle id of inserted record
 */
function mass_enroll_add_group($newgroupname, $courseid) {
    $newgroup = new stdClass();
    $newgroup->name = $newgroupname;
    $newgroup->courseid = $courseid;
    $newgroup->lang = current_language();
    return groups_create_group($newgroup);
}


/**
 * Enter description here ...
 * @param string $newgroupingname
 * @param int $courseid
 * @return int id Moodle id of inserted record
 */
function mass_enroll_add_grouping($newgroupingname, $courseid) {
    $newgrouping = new StdClass();
    $newgrouping->name = $newgroupingname;
    $newgrouping->courseid = $courseid;
    return groups_create_grouping($newgrouping);
}

/**
 * @param string $name group name
 * @param int $courseid course
 * @return string or false
 */
function mass_enroll_group_exists($name, $courseid) {
    return groups_get_group_by_name($courseid, $name);
}

/**
 * @param string $name group name
 * @param int $courseid course
 * @return string or false
 */
function mass_enroll_grouping_exists($name, $courseid) {
    return groups_get_grouping_by_name($courseid, $name);

}

/**
 * @param int $gid group ID
 * @param int $gpid grouping ID
 * @return mixed a fieldset object containing the first matching record or false
 */
function mass_enroll_group_in_grouping($gid, $gpid) {
     global $DB;
    $sql =<<<EOF
   select * from {groupings_groups}
   where groupingid = ?
   and groupid = ?
EOF;
    $params = array($gpid, $gid);
    return $DB->get_record_sql($sql,$params,IGNORE_MISSING);
}

/**
 * @param int $gid group ID
 * @param int $gpid grouping ID
 * @return bool|int true or new id
 * @throws dml_exception A DML specific exception is thrown for any errors.
 */
function mass_enroll_add_group_grouping($gid, $gpid) {
     global $DB;
    $new = new stdClass();
    $new->groupid = $gid;
    $new->groupingid = $gpid;
    $new->timeadded = time();
    return $DB->insert_record('groupings_groups', $new);
}
/**
 * todo displays the categories
 * @param string $requiredcapability
 * @param int $excludeid
* @param string $separator
* @param int $departmentcat
* @param int $orgcat
 * @param array $args List of named arguments for the fragment loader.
 * @return string
 */
function categorylist($requiredcapability = '', $excludeid = 0, $separator = ' / ',$departmentcat = 0,$orgcat=0) {
        global $DB, $USER;
        $coursecatcache = cache::make('core', 'coursecat');

        // Check if we cached the complete list of user-accessible category names ($baselist) or list of ids
        // with requried cap ($thislist).
        $currentlang = current_language();
        $basecachekey = $currentlang . '_catlist';
        $baselist = $coursecatcache->get($basecachekey);
        $thislist = false;
        $thiscachekey = null;
        if (!empty($requiredcapability)) {
            $requiredcapability = (array)$requiredcapability;
            $thiscachekey = 'catlist:'. serialize($requiredcapability);
            if ($baselist !== false && ($thislist = $coursecatcache->get($thiscachekey)) !== false) {
                $thislist = preg_split('|,|', $thislist, -1, PREG_SPLIT_NO_EMPTY);
            }
        } else if ($baselist !== false) {
            $thislist = array_keys($baselist);
        }

        if ($baselist === false) {
            // We don't have $baselist cached, retrieve it. Retrieve $thislist again in any case.
            $ctxselect = context_helper::get_preload_record_columns_sql('ctx');
            $sql = "SELECT cc.id, cc.sortorder, cc.name, cc.visible, cc.parent, cc.path, $ctxselect
                    FROM {course_categories} cc
                    JOIN {context} ctx ON cc.id = ctx.instanceid AND ctx.contextlevel = :contextcoursecat AND cc.visible = :value
                    ORDER BY cc.sortorder";
            $rs = $DB->get_recordset_sql($sql, array('contextcoursecat' => CONTEXT_COURSECAT,'value' => 1));
            $baselist = array();
            $thislist = array();
            foreach ($rs as $record) {
                // If the category's parent is not visible to the user, it is not visible as well.
                if (!$record->parent || isset($baselist[$record->parent])) {
                    context_helper::preload_from_record($record);
                    $context = context_coursecat::instance($record->id);
                    if (!$record->visible && !has_capability('moodle/category:viewhiddencategories', $context)) {
                        // No cap to view category, added to neither $baselist nor $thislist.
                        continue;
                    }
                    $baselist[$record->id] = array(
                        'name' => format_string($record->name, true, array('context' => $context)),
                        'path' => $record->path
                    );
                    if (!empty($requiredcapability) && !has_all_capabilities($requiredcapability, $context)) {
                        // No required capability, added to $baselist but not to $thislist.
                        continue;
                    }
                    $thislist[] = $record->id;
                }
            }
            $rs->close();
            $coursecatcache->set($basecachekey, $baselist);
            if (!empty($requiredcapability)) {
                $coursecatcache->set($thiscachekey, join(',', $thislist));
            }
        } else if ($thislist === false) {
            // We have $baselist cached but not $thislist. Simplier query is used to retrieve.
            $ctxselect = context_helper::get_preload_record_columns_sql('ctx');
            $sql = "SELECT ctx.instanceid AS id, $ctxselect
                    FROM {context} ctx WHERE ctx.contextlevel = :contextcoursecat ";
            $contexts = $DB->get_records_sql($sql, array('contextcoursecat' => CONTEXT_COURSECAT));
            $thislist = array();
            foreach (array_keys($baselist) as $id) {
                context_helper::preload_from_record($contexts[$id]);
                if (has_all_capabilities($requiredcapability, context_coursecat::instance($id))) {
                    $thislist[] = $id;
                }
            }
            $coursecatcache->set($thiscachekey, join(',', $thislist));
        }

        // Now build the array of strings to return, mind $separator and $excludeid.
        $names = array();
        $category = $DB->get_field('local_costcenter', 'category' ,array('id' => $USER->open_costcenterid));
        foreach ($thislist as $id) {

            $path = preg_split('|/|', $baselist[$id]['path'], -1, PREG_SPLIT_NO_EMPTY);
            if($departmentcat){
                if($path[1] == $departmentcat){
                    if (!$excludeid || !in_array($excludeid, $path)) {
                        $namechunks = array();
                        foreach ($path as $parentid) {
                            $namechunks[] = $baselist[$parentid]['name'];
                        }
                        $names[$id] = join($separator, $namechunks);
                    }
                }
            }else if($orgcat){
                if($path[0] == $orgcat){
                    if (!$excludeid || !in_array($excludeid, $path)) {
                        $namechunks = array();
                        foreach ($path as $parentid) {
                            $namechunks[] = $baselist[$parentid]['name'];
                        }
                        $names[$id] = join($separator, $namechunks);
                    }
                }
            }
            else{
                    if (!$excludeid || !in_array($excludeid, $path)) {
                        $namechunks = array();
                        foreach ($path as $parentid) {
                            $namechunks[] = $baselist[$parentid]['name'];
                        }
                        $names[$id] = join($separator, $namechunks);
                    }
            }
        }
        return $names;
}

/**
 * Serve the new course form as a fragment.
 *
 * @param array $args List of named arguments for the fragment loader.
 * @return string
 */
function local_courses_output_fragment_custom_course_form($args){
    global $DB,$CFG,$PAGE;
    $args = (object) $args;
    $context = $args->context;
    $renderer = $PAGE->get_renderer('local_courses');
        $courseid = $args->courseid;
        $o = '';
        $formdata = [];
        if (!empty($args->jsonformdata)) {
            $serialiseddata = json_decode($args->jsonformdata);
            parse_str($serialiseddata, $formdata);
        }
        if ($courseid) {
            $course = get_course($courseid);
            $course = course_get_format($course)->get_course();
            $category = $DB->get_record('course_categories', array('id'=>$course->category), '*', MUST_EXIST);
            $coursecontext = context_course::instance($course->id);
            require_capability('moodle/course:update', $coursecontext);
        }else{
            $category = $CFG->defaultrequestcategory;
        }

        if ($courseid > 0) {
            $heading = get_string('updatecourse', 'local_courses');
            $collapse = false;
            $data = $DB->get_record('course', array('id'=>$courseid));
        }

        $editoroptions = array('maxfiles' => EDITOR_UNLIMITED_FILES, 'maxbytes'=>$CFG->maxbytes, 'trusttext'=>false, 'noclean'=>true,'autosave'=>false);
        $overviewfilesoptions = course_overviewfiles_options($course);
        if (!empty($course)) {
            // Add context for editor.
            $editoroptions['context'] = $coursecontext;
            $editoroptions['subdirs'] = file_area_contains_subdirs($coursecontext, 'course', 'summary', 0);
            $course = file_prepare_standard_editor($course, 'summary', $editoroptions, $coursecontext, 'course', 'summary', 0);
            if ($overviewfilesoptions) {
                file_prepare_standard_filemanager($course, 'overviewfiles', $overviewfilesoptions, $coursecontext, 'course', 'overviewfiles', 0);
            }
            $get_coursedetails=$DB->get_record('course',array('id'=>$course->id));
        } else {
            // Editor should respect category context if course context is not set.
            $editoroptions['context'] = $catcontext;
            $editoroptions['subdirs'] = 0;
            $course = file_prepare_standard_editor($course, 'summary', $editoroptions, null, 'course', 'summary', null);
            if ($overviewfilesoptions) {
                file_prepare_standard_filemanager($course, 'overviewfiles', $overviewfilesoptions, null, 'course', 'overviewfiles', 0);
            }
        }

        $params = array(
            'course' => $course,
            'category' => $category,
            'editoroptions' => $editoroptions,
            'returnto' => $returnto,
            'get_coursedetails'=>$get_coursedetails,
            // 'form_status' => $args->form_status,
            'costcenterid' => $data->open_costcenterid
        );
        $mform = new custom_course_form(null, $params, 'post', '', null, true, $formdata);
        // Used to set the courseid.
        $mform->set_data($formdata);

        if (!empty($args->jsonformdata) && strlen($args->jsonformdata)>2) {
            // If we were passed non-empty form data we want the mform to call validation functions and show errors.
            $mform->is_validated();
        }
        $formheaders = array_keys($mform->formstatus);
        $nextform = array_key_exists($args->form_status, $formheaders);
        if ($nextform === false) {
            return false;
        }
        ob_start();
        $formstatus = array();
        foreach (array_values($mform->formstatus) as $k => $mformstatus) {
            $activeclass = $k == $args->form_status ? 'active' : '';
            $formstatus[] = array('name' => $mformstatus, 'activeclass' => $activeclass);
        }
        // $formstatusview = new \local_courses\output\form_status($formstatus);
        // $o .= $renderer->render($formstatusview);
        $o .= html_writer::script("$(document).ready(function(){
            $('#id_open_costcenterid').on('change',function(){
            var progID = $(this).find('option:selected').val();
                var progID = $(this).val();
                if(progID){
                    $.ajax({
			method: 'GET',
			dataType: 'json',
			url: M.cfg.wwwroot + '/local/users/ajax.php?action=departmentlist&costcenter='+progID,
      success: function(data){
          var template = '<option value= >--Select College--</option>';
          $.each(data.colleges, function( index, value) {
             template +=  '<option value = ' + value.id + ' >' +value.fullname + '</option>';
          });
          $('#id_open_collegeid').html(template);

          var udept = '<option value=0>--Select Department--</option>';
          $.each(data.departments, function( index, value) {
             udept +=  '<option value = ' + value.id + ' >' +value.fullname + '</option>';
          });
          $('#id_open_departmentid').html(udept);
      }
                    });
                } else {
                    var template =  '<option value=\'\'>--Select Department--</option>';
                    $('#id_open_departmentid').html(template);
                    var cattemplate =  '<option value=\'\'>--Select Department--</option>';
                    $('#id_category').html(cattemplate);
                }
            });


            $('#id_open_departmentid').on('change',function(){
                var catID = $(this).val();
                if(catID !== 'null'){
                    $.ajax({
                        type:'post',
                        dataType:'json',
                        url: M.cfg.wwwroot + '/local/courses/custom_ajax.php?category=1&cat='+catID,
                        success: function(resp){
                            var cattemplate =  '<option value=\'\'>--Select Department--</option>';
                            $.each(resp, function( index, value) {
                                cattemplate += '<option value = ' + index + ' >' +value + '</option>';
                            });
                            $('#id_category').html(cattemplate);
                        }
                    });
                } else {
                    costcenter = $('#id_open_costcenterid').val();
                    
                    if(costcenter){
                    $.ajax({
                        type:'post',
                        dataType:'json',
                        url: M.cfg.wwwroot + '/local/courses/custom_ajax.php?prog='+costcenter,
                        success: function(resp){
                            var template =  '<option value=null>--Select Department--</option>';                                    
                            $.each(resp.department, function( index, value) {
                                template += '<option value = ' + index + ' >' +value + '</option>';
                            });
                            $('#id_open_departmentid').html(template);
                        }
                    });
                }
                 }
            });
        });");
        $mform->display();
        $o .= ob_get_contents();
        ob_end_clean();


    return $o;
}

/**
 * Serve the delete category form as a fragment.
 *
 * @param array $args List of named arguments for the fragment loader.
 * @return string
 */
function local_courses_output_fragment_deletecategory_form($args){
 global $DB,$CFG,$PAGE;
    require_once($CFG->libdir.'/coursecatlib.php');
    require_once($CFG->libdir . '/questionlib.php');

    $args = (object) $args;
    $context = $args->context;
    $categoryid = $args->categoryid;
    $o = '';

    $formdata = [];
    if (!empty($args->jsonformdata)) {
        $serialiseddata = json_decode($args->jsonformdata);
        parse_str($serialiseddata, $formdata);
    }

    if ($categoryid) {
        $category = coursecat::get($categoryid);
        $context = context_coursecat::instance($category->id);
    }else {
        $category = coursecat::get_default();
        $categoryid = $category->id;
        $context = context_coursecat::instance($category->id);
    }

    $mform = new local_courses\form\deletecategory_form(null, $category, 'post', '', null, true, $formdata);
    // Used to set the courseid.

    if (!empty($args->jsonformdata)) {
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
 * Serve the new course category form as a fragment.
 *
 * @param array $args List of named arguments for the fragment loader.
 * @return string
 */
function local_courses_output_fragment_coursecategory_form($args){
 global $DB,$CFG,$PAGE;
    require_once($CFG->libdir.'/coursecatlib.php');

    $args = (object) $args;
    $context = $args->context;
    $categoryid = $args->categoryid;

    $o = '';

    $formdata = [];
    if (!empty($args->jsonformdata)) {
        $serialiseddata = json_decode($args->jsonformdata);
        parse_str($serialiseddata, $formdata);
    }

    if($categoryid){
        $coursecat = coursecat::get($categoryid, MUST_EXIST, true);
        $category = $coursecat->get_db_record();
        $context = context_coursecat::instance($categoryid);

         $itemid = 0;
    }else{
        $parent = optional_param('parent', 0, PARAM_INT);

        if ($parent) {
            $DB->record_exists('course_categories', array('id' => $parent), '*', MUST_EXIST);
            $context = context_coursecat::instance($parent);
        } else {
            $context = context_system::instance();
        }
        $category = new stdClass();
        $category->id = 0;
        $category->parent = $parent;
    }

    if ($categoryid > 0) {
        $heading = get_string('updatecourse', 'local_courses');
        $collapse = false;
        $data = $DB->get_record('course_categories', array('id'=>$categoryid));
        $formdata = new stdClass();
        $formdata->id = $data->id;
        $formdata->parent = $data->parent;
        $formdata->name = $data->name;
        $formdata->idnumber = $data->idnumber;
        $formdata->description_editor['text'] = $data->description;
    }

    $params = array(
    'categoryid' => $id,
    'parent' => $category->parent,
    'context' => $context,
    'itemid' => $itemid
    );

    $mform = new local_courses\form\coursecategory_form(null, $params, 'post', '', null, true, $formdata);
    // Used to set the courseid.
    $mform->set_data($formdata);

    if (!empty($args->jsonformdata)) {
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
 * Serve the table for course categories
 *
 * @param array $args List of named arguments for the fragment loader.
 * @return string 
 */
function local_courses_output_fragment_coursecategory_display($args){
 global $DB,$CFG,$PAGE,$OUTPUT;

    $args = (object) $args;
    $context = $args->context;
    $categoryid = $args->categoryid;

    $formdata = [];
    if (!empty($args->jsonformdata)) {
        $serialiseddata = json_decode($args->jsonformdata);
        parse_str($serialiseddata, $formdata);
    }

    $table = new html_table();
    $table->id = 'popup_category';
    $table->align = ['left','center','center','center','center'];
    $table->head = array(get_string('course_name', 'local_courses'),get_string('enrolledusers', 'local_courses'),get_string('completed_users', 'local_courses'),get_string('type', 'local_courses'),get_string('actions', 'local_courses'));
    $courses = $DB->get_records_sql("SELECT c.id,c.category,c.fullname FROM {course} c WHERE c.id > 1
                                     AND c.category = $categoryid");
    if($courses){
    $data=array();
    foreach($courses as $course){
        $row = array();
        $row[] = html_writer::link(new moodle_url('/course/view.php',array('id'=>$course->id)),$course->fullname);
        $course_sql = "SELECT count(ue.userid) as enrolled,count(cc.course) as completed
                            FROM {user_enrolments} as ue
                            JOIN {enrol} as e ON e.id=ue.enrolid
                            RIGHT JOIN {course} as c ON c.id =e.courseid
                            LEFT JOIN {course_completions} cc ON cc.course=e.courseid and ue.userid=cc.userid and cc.timecompleted IS NOT NULL
                            WHERE c.id=$course->id
                                group by e.courseid";
        $course_stats = $DB->get_record_sql($course_sql);
       if($course_stats->enrolled){
            $row[] = $course_stats->enrolled;
        }else{
             $row[] = "N/A";
        }
        if($course_stats->completed){
            $row[] = $course_stats->completed;
        }else{
             $row[] = "N/A";
        }
        $ilt_sql = "SELECT open_identifiedas from {course}  WHERE id =". $course->id;  $ilt_stats = $DB->get_record_sql($ilt_sql);
        $types = explode(',',$ilt_stats->open_identifiedas);
        $classtype = array();
        foreach($types as $type){

            if($type == 2){
              $classtype[0]= get_string('ilt','local_courses');
            }
            if($type == 3){
             $classtype[2]= get_string('elearning','local_courses');
            }
            if($type == 4){
             $classtype[3]= get_string('learningplan','local_courses');
            }
            if($type == 5){
             $classtype[5]= get_string('program','local_courses');
            }
            if($type == 6){
             $classtype[6]= get_string('certification','local_courses');
            }
        }
        $ctype = implode(',',$classtype);

        if($ctype){

            $row[] = $ctype;
        }else{
             $row[] = "N/A";
        }



        $enrolid = $DB->get_field('enrol','id', array('courseid'=>$course->id, 'enrol'=>'manual'));

        $enrolicon = html_writer::link(new moodle_url('/local/courses/courseenrol.php',array('id'=>$course->id,'enrolid' => $enrolid)),html_writer::tag('i','',array('class'=>'fa fa-user-plus icon text-muted', 'title' => get_string('enrol','local_courses'), 'alt' => get_string('enrol'))));
        $actions = $enrolicon.' '.$editicon;
        $row[] = $actions;

          $data[] = $row;
    }
    $table->data = $data;
    $output = html_writer::table($table);
    $output .= html_writer::script("$('#popup_category').DataTable({
                                            'language': {
                                                  paginate: {
                                                                'previous': '<',
                                                                'next': '>'
                                                            }
                                             },
                                             'bInfo' : false,
                                   lengthMenu: [
                    [5, 10, 25, 50, 100, -1],
                    [5, 10, 25, 50, 100, 'All']
                ]
            });");
    }else{
        $output = "No Courses Available";
    }

    return $output;
}

/**
 * Serve the table for course status
 *
 * @param array $args List of named arguments for the fragment loader.
 * @return string
 */
function local_courses_output_fragment_coursestatus_display($args){
    global $DB,$CFG,$PAGE,$OUTPUT,$USER;
    $args = (object) $args;
    $course = $DB->get_record('course', array('id' => $args->courseid));
    $info = new completion_info($course);
        
    // Is course complete?
    $coursecomplete = $info->is_course_complete($USER->id);

    // Has this user completed any criteria?
    $criteriacomplete = $info->count_course_user_data($USER->id);
    $params = array(
        'userid' => $USER->id,
        'course' => $course->id,
    );
    $completions = $info->get_completions($USER->id);
    $ccompletion = new completion_completion($params);

    $rows = array();
    // Loop through course criteria.
    foreach ($completions as $completion) {
        $criteria = $completion->get_criteria();
        $row = array();
            $row['type'] = $criteria->criteriatype;
            $row['title'] = $criteria->get_title();
            $row['complete'] = $completion->is_complete();
            $row['timecompleted'] = $completion->timecompleted;
            $row['details'] = $criteria->get_details($completion);
            $rows[] = $row;

        }
    // Print table.
    $last_type = '';
    $agg_type = false;
    // $oddeven = 0;

    $table = new html_table();
    $table->head = array(get_string('criteriagroup','format_tabtopics'),get_string('criteria','format_tabtopics'),get_string('requirement','format_tabtopics'),get_string('complete','format_tabtopics'),get_string('completiondate','format_tabtopics'));
    $table->size=array('20%','20%','25%','5%','30%');
    $table->align=array('left','left','left','center','center');
    $table->id = 'scrolltable';
    foreach ($rows as $row) {
        if ($last_type !== $row['details']['type']) {
        $last_type = $row['details']['type'];
        $agg_type = true;
        }else {
        // Display aggregation type.
            if ($agg_type) {
                $agg = $info->get_aggregation_method($row['type']);
                $last_type .= '('. html_writer::start_tag('i');
                if ($agg == COMPLETION_AGGREGATION_ALL) {
                    $last_type .= core_text::strtolower(get_string('all', 'completion'));
                } else {
                    $last_type .= core_text::strtolower(get_string('any', 'completion'));
                }
                $last_type .= html_writer::end_tag('i') .core_text::strtolower(get_string('required')).')';
                $agg_type = false;
            }
        }
        if ($row['timecompleted']) {
            $timecompleted=userdate($row['timecompleted'], get_string('strftimedate', 'langconfig'));
        } else {
            $timecompleted = '-';
        }
        $table->data[] = new html_table_row(array($last_type,$row['details']['criteria'],$row['details']['requirement'],$row['complete'] ? get_string('yes') : get_string('no'),$timecompleted));
    }
    $output = html_writer::table($table);
    $output .= html_writer::script("
         $(document).ready(function(){
            var table_rows = $('#scrolltable tr');
            // if(table_rows.length>6){
                $('#scrolltable').dataTable({
                    'searching': false,
                    'language': {
                        'paginate': {
                            'next': '>',
                            'previous': '<'
                        }
                    },
                    'pageLength': 5,
                });
            // }
        });
    ");
    return $output;
}

/*
* todo provides form element - courses
* @param $mform formobject
* return void
*/
function courses_filter($mform){
    global $DB,$USER;
    $systemcontext = context_system::instance();
    $sql = "SELECT id, fullname FROM {course} WHERE id > 1 AND open_parentcourseid = 0";
    $sql2 = " AND open_costcenterid = $USER->open_costcenterid";
    $sql3 = " AND open_departmentid = $USER->open_departmentid";
    if(is_siteadmin()){
       $courseslist = $DB->get_records_sql_menu($sql);
    }else if(has_capability('local/costcenter:manage_ownorganization',$systemcontext)){
        $courseslist = $DB->get_records_sql_menu($sql.$sql2);
    }else if(has_capability('local/costcenter:manage_owndepartments',$systemcontext)){
        $courseslist = $DB->get_records_sql_menu($sql.$sql2.$sql3);
    }
    $select = $mform->addElement('autocomplete', 'courses', '', $courseslist, array('placeholder' => get_string('course')));
    $mform->setType('courses', PARAM_RAW);
    $select->setMultiple(true);
}
/*
* todo provides form element - courses
* @param $mform formobject
* return void
*/
function elearning_filter($mform){
    global $DB,$USER;
    $systemcontext = context_system::instance();
    if ((has_capability('local/request:approverecord', context_system::instance()) || is_siteadmin())) {
        $courseslist = $DB->get_records_sql_menu("SELECT id, fullname FROM {course} WHERE visible = 1");
    }
    $select = $mform->addElement('autocomplete', 'elearning', '', $courseslist, array('placeholder' => get_string('course_name', 'local_courses')));
    $mform->setType('elearning', PARAM_RAW);
    $select->setMultiple(true);
}

/*
* todo provides form element - categories
* @param $mform formobject
* return void
*/
function categories_filter($mform){
    global $DB,$USER;
    $systemcontext = context_system::instance();
    $update_course_lib = new local_courses\action\update();
    if(is_siteadmin()){
    $categorylist = $DB->get_records_sql_menu("SELECT id, name FROM {local_departments} ");
    }else if(has_capability('local/costcenter:manage_ownorganization',$systemcontext)) {

        /*$categories = $update_course_lib->get_categories($USER->open_costcenterid);
        $categoryids = implode(',',$categories);
        $categorylist = $DB->get_records_sql_menu("SELECT cc.id, cc.name FROM {course_categories} AS cc WHERE cc.id IN($categoryids)");*/
        $categorylist = $DB->get_records_sql_menu("SELECT ld.id, ld.name FROM {local_departments} AS ld WHERE ld.university = $USER->open_costcenterid");
    }else if(has_capability('local/costcenter:manage_owndepartments',$systemcontext)) {
        /*$categories = $update_course_lib->get_categories($USER->open_departmentid);
        $categoryids = implode(',',$categories);
        $categorylist = $DB->get_records_sql_menu("SELECT cc.id, cc.name FROM {course_categories} AS cc WHERE cc.id IN($categoryids)");*/
        $categorylist = $DB->get_records_sql_menu("SELECT ld.id, ld.name FROM {local_departments} AS ld WHERE ld.university = $USER->open_departmentid");
    }

    $select = $mform->addElement('autocomplete', 'universitydepartment', '', $categorylist, array('placeholder' => get_string('department', 'local_departments')));
    $mform->setType('universitydepartment', PARAM_RAW);
    $select->setMultiple(true);
}
function departmentcourseusers_filter($mform,$query='',$searchanywhere=false, $page=0, $perpage=25,$costcenter = null, $roleid = null,$courseid = null){
    global $DB, $USER, $COURSE;
    $systemcontext = context_system::instance();
    $departmentuserlist=array();
    $data=data_submitted();
    //$userslistparams = array('adminuserid' => 2, 'deleted' => 0, 'suspended' => 0, 'userid' => $USER->id);
    $userslistsql = "SELECT u.id, concat(u.firstname,' ',u.lastname) as fullname
                    FROM {user} AS u 
                    JOIN {local_costcenter} cc ON cc.id = u.open_departmentid
                    JOIN {course} as c ON (c.open_departmentid = u.open_departmentid OR c.open_departmentid = 0) AND c.id = $courseid
                    JOIN {role} as r ON r.id = u.open_role AND r.id = $roleid 
                    WHERE  u.id > 2 AND u.suspended = 0 AND u.deleted = 0";

    if (!is_siteadmin() && has_capability('local/costcenter:manage_ownorganization',$systemcontext)) {
        $userslistsql .= " AND u.open_costcenterid = " . $USER->open_costcenterid;
    }
    $departmentuserlist = $DB->get_records_sql_menu($userslistsql);
    $select = $mform->addElement('autocomplete', 'users', '',$departmentuserlist,array('placeholder' => get_string('users','local_program')));
    $mform->setType('users', PARAM_RAW);
    $select->setMultiple(true);
}
function departmentcourseusersemail_filter($mform,$query='',$searchanywhere=false, $page=0, $perpage=25,$costcenter = null, $roleid = null,$courseid = null){
    global $DB, $USER, $COURSE;
    $systemcontext = context_system::instance();
    $departmentuseremaillist=array();
    $data=data_submitted();
    //$userslistparams = array('adminuserid' => 2, 'deleted' => 0, 'suspended' => 0, 'userid' => $USER->id);
    $userslistsql = "SELECT u.id, u.email as email
                    FROM {user} AS u 
                    JOIN {local_costcenter} cc ON cc.id = u.open_departmentid
                    JOIN {course} as c ON (c.open_departmentid = u.open_departmentid OR c.open_departmentid = 0) AND c.id = $courseid
                    JOIN {role} as r ON r.id = u.open_role AND r.id = $roleid 
                    WHERE  u.id > 2 AND u.suspended = 0 AND u.deleted = 0";

    if (!is_siteadmin() && has_capability('local/costcenter:manage_ownorganization',$systemcontext)) {
        $userslistsql .= " AND u.open_costcenterid = " . $USER->open_costcenterid;
    }
    $departmentuseremaillist = $DB->get_records_sql_menu($userslistsql);
    $select = $mform->addElement('autocomplete', 'email', '',$departmentuseremaillist,array('placeholder' => get_string('email','local_users')));
    $mform->setType('users', PARAM_RAW);
    $select->setMultiple(true);
}
/*
* todo prints the filter form
*/
function print_filterform(){
    global $DB, $CFG;
    require_once($CFG->dirroot . '/local/courses/filters_form.php');
    $mform = new filters_form(null, array('filterlist'=>array('courses', 'costcenter', 'categories')));
    if ($mform->is_cancelled()) {
        redirect($CFG->wwwroot . '/local/courses/courses.php');
    } else{
        $filterdata =  $mform->get_data();
        if($filterdata){
            $collapse = false;
        } else{
            $collapse = true;
        }
    }
    $heading = '<button >'.get_string('course_filters', 'local_courses').'</button>';
    print_collapsible_region_start(' ', 'filters_form', ' '.' '.$heading, false, $collapse);
    $mform->display();
    print_collapsible_region_end();
    return $filterdata;
}

/**
* [course_enrolled_users description]
* @param  string  $type       [description]
* @param  integer $evaluationid [description]
* @param  [type]  $params     [description]
* @param  integer $total      [description]
* @param  integer $offset1    [description]
* @param  integer $perpage    [description]
* @param  integer $lastitem   [description]
* @return [type]              [description]
*/
function course_enrolled_users($type = null, $course_id = 0, $params, $total=0, $offset1=-1, $perpage=-1, $lastitem=0){

    global $DB, $USER;
    $context = context_system::instance();
    $course = $DB->get_record('course', array('id' => $course_id));
 
    $params['suspended'] = 0;
    $params['deleted'] = 0;
 
    if($total==0){
         $sql = "SELECT u.id,concat(u.firstname,' ',u.lastname,' ','(',u.email,')') as fullname";
    }else{
        $sql = "SELECT count(u.id) as total";
    }
    $sql.=" FROM {user} AS u  
            JOIN {local_costcenter} cc ON cc.id = u.open_departmentid
            JOIN {course} as c ON c.open_departmentid = u.open_departmentid AND c.id=$course_id
            JOIN {role} as r ON r.id = u.open_role 
            WHERE u.id > 2 AND u.suspended = :suspended AND u.deleted = :deleted 
            AND r.shortname = 'student'";
    if($lastitem!=0){
       $sql.=" AND u.id > $lastitem";
    }
    if (!is_siteadmin()) {
        $user_detail = $DB->get_record('user', array('id'=>$USER->id));
        $sql .= " AND u.open_costcenterid = :costcenter";
        $params['costcenter'] = $course ->open_costcenterid;
        if (has_capability('local/costcenter:manage_owndepartments',$context) AND !has_capability('local/costcenter:manage_ownorganization',$context)) {
            $sql .=" AND u.open_departmentid = :department";
            $params['department'] = $user_detail->open_departmentid;
        }
    }
    $sql .=" AND u.id <> $USER->id";
    if (!empty($params['email'])) {
         $sql.=" AND u.id IN ({$params['email']})";
    }
    if (!empty($params['uname'])) {
         $sql .=" AND u.id IN ({$params['uname']})";
    }
    if (!empty($params['department'])) {
         $sql .=" AND u.open_departmentid IN ({$params['department']})";
    }
    if (!empty($params['organization'])) {
         $sql .=" AND u.open_costcenterid IN ({$params['organization']})";
    }
    if (!empty($params['idnumber'])) {
         $sql .=" AND u.id IN ({$params['idnumber']})";
    }
    if (!empty($params['groups'])) {
         $group_list = $DB->get_records_sql_menu("select cm.id, cm.userid from {cohort_members} cm, {user} u where u.id = cm.userid AND u.deleted = 0 AND u.suspended = 0 AND cm.cohortid IN ({$params['groups']})");
         
         $groups_members = implode(',', $group_list);
         if (!empty($groups_members))
         $sql .=" AND u.id IN ({$groups_members})";
         else
         $sql .=" AND u.id =0";
    }
    if ($type=='add') {
        $sql .= " AND u.id NOT IN (SELECT ue.userid
                             FROM {user_enrolments} AS ue 
                             JOIN {enrol} e ON (e.id = ue.enrolid and e.courseid=$course_id and (e.enrol='manual' OR e.enrol='self')))";
    }elseif ($type=='remove') {
        $sql .= " AND u.id IN (SELECT ue.userid
                             FROM {user_enrolments} AS ue 
                             JOIN {enrol} e ON (e.id = ue.enrolid and e.courseid=$course_id and (e.enrol='manual' OR e.enrol='self')))";
    }

    $order = ' ORDER BY u.id ASC ';
    if($perpage!=-1){
        $order.="LIMIT $perpage";
    }
    if($total==0){
        $availableusers = $DB->get_records_sql_menu($sql .$order,$params);
    }else{
        $availableusers = $DB->count_records_sql($sql,$params);
    }
    return $availableusers;
}

/**
 * Enrol candidates.
 */
class custom_enrol_manual_potential_participant extends user_selector_base {
    protected $costcenter;
    protected $enrolid;
    protected $department;
    protected $email;
    protected $idnumber;
    protected $name;
    protected $uname;
    protected $course_id;
    protected $id;
    public function __construct($name, $options, $costcenter, $department,$email,$idnumber,$uname,$id, $course_id) {
        $this->costcenter = $costcenter;
        $this->email = $email;
        $this->idnumber = $idnumber;
        $this->uname = $uname;
        $this->id=$id;

        $this->department=$department;
        $this->enrolid  = $options['enrolid'];
        $this->course_id = $course_id;
        parent::__construct($name, $options);
    }

    /**
     * Candidate users
     * @param string $search
     * @return array
     */
    public function find_users($search) {
        global $DB,$USER;

        $systemcontext = context_system::instance();
        if(!is_siteadmin()  && !has_capability('local/assign_multiple_departments:manage', $systemcontext)){

            /*if we get any issues please revoke the previous instance code and change by changing code*/
            /** End of code **/
            $userdata_sql="SELECT u.* FROM {user} u WHERE u.id >2 AND u.deleted=0 AND u.suspended=0 AND u.open_costcenterid=$this->costcenter";
            $email=$this->email;
            if(!empty($email) ){
                $userdata_sql.=" AND u.id IN({$email})";
            }
            $name=$this->uname;
           if(!empty($nam1) ){
                $userdata_sql .=" AND u.id in ('$nam1')";
            }

            if(!empty($this->department)){
                $userdata_sql .=" AND u.open_departmentid in ($this->department)";
            }
            if(!empty($this->idnumber)){
                $userdata_sql .=" AND u.id in($this->idnumber)";
            }
            $users_list = $DB->get_fieldset_sql($userdata_sql);
            $enrolled_sql = "SELECT u.id FROM {user} u
                             JOIN {user_enrolments} ue ON (ue.userid = u.id)
                             JOIN {enrol} e ON (e.id = ue.enrolid and e.courseid=$this->course_id)
                            ";

            $enrolled_users_list = $DB->get_fieldset_sql($enrolled_sql);
            $users_list = array_diff($users_list, $enrolled_users_list);
             $users_list = implode(',',$users_list);
        }else{
            $userdata_sql="SELECT u.* FROM {user} u WHERE u.id >1 AND u.deleted=0 AND u.suspended=0 ";
            if(!empty($this->costcenter)){
                $userdata_sql .= " AND u.open_costcenterid IN ($this->costcenter)";
            }
            if(!empty($this->department)){
                $userdata_sql .=" AND u.open_departmentid in($this->department)";
            }
            $email=$this->email;
           if(!empty($email)){
               $userdata_sql.=" AND u.id IN({$email})";
            }
            if(!empty($this->uname)){
                $userdata_sql .=" AND u.id in('$this->uname')";
            }

            if(!empty($this->idnumber)){
                $userdata_sql .=" AND u.id IN ($this->idnumber)";
            }
            $users_list = $DB->get_fieldset_sql($userdata_sql);

            if(isset($batch_members))
                $users_list = array_intersect($users_list,$batch_members);
            $enrolled_sql = "SELECT u.id FROM {user} u
                             JOIN {user_enrolments} ue ON (ue.userid = u.id)
                             JOIN {enrol} e ON (e.id = ue.enrolid and e.courseid=$this->course_id)
                            ";
            $enrolled_users_list = $DB->get_fieldset_sql($enrolled_sql);
            $users_list = array_diff($users_list, $enrolled_users_list);
            $users_list = implode(',',$users_list);
        }
        // By default wherecondition retrieves all users except the deleted, not confirmed and guest.
        list($wherecondition, $params) = $this->search_sql($search, 'u');
        $params['enrolid'] = $this->enrolid;

        $fields      = 'SELECT ' . $this->required_fields_sql('u');
        $countfields = 'SELECT COUNT(1)';

        $sql = " FROM {user} u
                 LEFT JOIN {user_enrolments} ue ON (ue.userid = u.id AND ue.enrolid= :enrolid)
                WHERE $wherecondition
                      AND ue.id IS NULL ";

        if(!empty($users_list))
            $sql .= ' AND u.id in('.$users_list.')';
        list($sort, $sortparams) = users_order_by_sql('u', $search, $this->accesscontext);
        $order = ' ORDER BY ' . $sort;

        if (!$this->is_validating() && (!empty($users_list))) {
            $potentialmemberscount = $DB->count_records_sql($countfields . $sql, $params);
            if ($potentialmemberscount > 500) {
                return $this->too_many_results($search, $potentialmemberscount);
            }
        }

        if(empty($users_list))
            $availableusers = array();
        else
            $availableusers = $DB->get_records_sql($fields . $sql . $order, array_merge($params, $sortparams));

        if (empty($availableusers)) {
            return array();
        }
        if ($search) {
            $groupname = get_string('enrolcandidatesmatching', 'enrol', $search);
        } else {
            $groupname = get_string('enrolcandidates', 'enrol');
        }
        return array($groupname => $availableusers);
    }
    protected function get_options() {
        $options = parent::get_options();
        $options['enrolid'] = $this->enrolid;
        $options['file']    = 'enrol/manual/locallib.php';
        return $options;
    }
}
class custom_enrol_manual_current_participant extends user_selector_base {
    protected $costcenter;
    protected $enrolid;
    protected $department;
    protected $email;
    protected $idnumber;
    protected $name;
    protected $uname;
    protected $course_id;
    protected $id;

    public function __construct($name,$options,$costcenter,$department,$email,$idnumber,$uname,$course_id,$id) {
        $this->costcenter = $costcenter;
        $this->email = $email;
        $this->idnumber = $idnumber;
        $this->uname = $uname;
        $this->id = $id;
        $this->department=$department;
        $this->course_id = $course_id;
        $this->enrolid  = $options['enrolid'];
        parent::__construct($name, $options);
    }

    /**
     * Candidate users
     * @param string $search
     * @return array
     */
    public function find_users($search) {
        global $DB,$USER;
        $systemcontext = context_system::instance();
        if(!is_siteadmin()  && !has_capability('local/assign_multiple_departments:manage', $systemcontext)){
            $this->id = $this->id;
            /*if we get any issues please revoke the previous instance code and change by changing code*/
            /** End of code **/
            $userdata_sql="SELECT u.* FROM {user} u
                            WHERE u.id >2 AND u.deleted=0 AND u.suspended=0 AND u.open_costcenterid=$this->costcenter";
            if(!empty($this->email)){
                $userdata_sql.=" AND u.id IN({$this->email})";
            }
            if(!empty($this->uname)){
                $userdata_sql .=" AND u.id in ('$this->uname')";
            }
            if(!empty($this->department)){
                $userdata_sql .=" AND u.open_departmentid in($this->department)";
            }
            if(!empty($this->idnumber)){
                $userdata_sql .=" AND u.id in ($this->idnumber)";
            }
            $users_list = $DB->get_fieldset_sql($userdata_sql);
            $users_list = implode(',',$users_list);
        }else{

            $userdata_sql="SELECT u.* FROM {user} u WHERE u.id >1 AND u.deleted=0 AND u.suspended=0 ";
            if(!empty($this->costcenter) && $this->costcenter!=='null'&& $this->costcenter!== "'-1'"){
                $userdata_sql .= " AND u.open_costcenterid IN($this->costcenter)";
            }
            if(!empty($this->department) && $this->department!=='null'&& $this->department!== "'-1'"){
                $userdata_sql .=" AND u.open_departmentid in($this->department)";
            }
            $email=$this->email;
            if(!empty($email)){
                $userdata_sql.=" AND u.id IN({$email})";
            }
            if(!empty($this->uname)){
                $userdata_sql .=" AND u.id in('$nam1')";
            }
            if(!empty($this->idnumber)){
                $userdata_sql .=" AND u.id in($this->idnumber)";
            }
            $users_list = $DB->get_fieldset_sql($userdata_sql);
            $users_list = implode(',',$users_list);
        }

     /** The Below code is for listing the enrolled user in text box from enrol,userenrolments table **/
        list($wherecondition, $params) = $this->search_sql($search, 'u');
        $params['enrolid'] = $this->enrolid;

        $fields      = 'SELECT ' . $this->required_fields_sql('u');
        $countfields = 'SELECT COUNT(1)';
        $sql = " FROM {user} u
                 JOIN {user_enrolments} ue ON (ue.userid = u.id)
                 JOIN {enrol} e ON (e.id = ue.enrolid and e.courseid=$this->course_id)
                WHERE  $wherecondition ";
        if(!empty($users_list))
            $sql .=" AND u.id in($users_list)";
        list($sort, $sortparams) = users_order_by_sql('u', $search, $this->accesscontext);
        $order = ' ORDER BY ' . $sort;
        if (!$this->is_validating()) {
            $potentialmemberscount = $DB->count_records_sql($countfields . $sql, $params);
            if ($potentialmemberscount > 500) {
                return $this->too_many_results($search, $potentialmemberscount);
            }
        }

        if(empty($users_list) && !is_siteadmin())
            $availableusers = array();
        else
            $availableusers = $DB->get_records_sql($fields . $sql . $order, array_merge($params, $sortparams));

        if (empty($availableusers)) {
            return array();
        }
        if ($search) {
            $groupname = get_string('enrolledusersmatching', 'enrol', $search);
        } else {
            $groupname = get_string('enrolledusers', 'enrol');
        }
        return array($groupname => $availableusers);
    }

    protected function get_options() {
        $options = parent::get_options();
        $options['enrolid'] = $this->enrolid;
        $options['file']    = 'enrol/manual/locallib.php';
        return $options;
    }
}
/*
* Author Rizwana
* Displays a node in left side menu
* @return  [type] string  link for the leftmenu
*/
function local_courses_leftmenunode(){
    $systemcontext = context_system::instance();
    $coursecatnodes = '';
    /*if(has_capability('moodle/category:manage', $systemcontext) || is_siteadmin()) {
        $coursecatnodes .= html_writer::start_tag('li', array('id'=> 'id_leftmenu_categories', 'class'=>'pull-left user_nav_div categories usernavdep row-fluid '));
        $categories_url = new moodle_url('/local/courses/index.php');
        $categories = html_writer::link($categories_url, '<i class="fa fa-book" aria-hidden="true" aria-label=""></i><i class="fa fa-book secbook" aria-hidden="true" aria-label=""></i><span class="user_navigation_link_text">'.get_string('leftmenu_browsecategories','local_courses').'</span>',array('class'=>'user_navigation_link'));
        $coursecatnodes .= $categories;
        $coursecatnodes .= html_writer::end_tag('li');
    }*/

    if(has_capability('local/costcenter:manage_multiorganizations',$systemcontext) || is_siteadmin() || has_capability('local/costcenter:manage_ownorganization', $systemcontext)){
        $coursecatnodes .= html_writer::start_tag('li', array('id'=> 'id_leftmenu_browsecourses', 'class'=>'pull-left user_nav_div browsecourses dropdown-item'));
            $courses_url = new moodle_url('/local/courses/courses.php');
            $courses = html_writer::link($courses_url, '<i class="fa fa-book"></i><span class="user_navigation_link_text">'.get_string('manage_courses','local_courses').'</span>',array('class'=>'user_navigation_link'));
            $coursecatnodes .= $courses;
        $coursecatnodes .= html_writer::end_tag('li');
    }
     if(has_capability('local/costcenter:manage_multiorganizations',$systemcontext) || is_siteadmin() || has_capability('local/costcenter:manage_ownorganization', $systemcontext)){
        $coursecatnodes .= html_writer::start_tag('li', array('id'=> 'id_leftmenu_browsereports', 'class'=>'pull-left user_nav_div browsecourses dropdown-item'));
            $courses_url = new moodle_url('/blocks/configurable_reports/managereport.php');
            $courses = html_writer::link($courses_url, '<i class="fa fa-signal"></i><span class="user_navigation_link_text">Manage Reports</span>',array('class'=>'user_navigation_link'));
            $coursecatnodes .= $courses;
        $coursecatnodes .= html_writer::end_tag('li');
    }
    
    return array('8' => $coursecatnodes);
}
function local_courses_quicklink_node(){
    global $CFG, $DB, $USER;
    $systemcontext = context_system::instance();
    $countsql  = "SELECT count(c.id) FROM {course} AS c"; 
    if(is_siteadmin()){
    $formsql = " JOIN {local_costcenter} AS co ON co.id = c.open_costcenterid
                 JOIN {course_categories} AS cc ON cc.id = c.category";
    }else if((!is_siteadmin() && !has_capability('local/costcenter:manage_multiorganizations', $systemcontext)) && has_capability('local/costcenter:manage_ownorganization',$systemcontext) && has_capability('block/learnerscript:managelsreports',$systemcontext)){
        $formsql = " JOIN {local_costcenter} AS co ON co.id = c.open_costcenterid
                   JOIN {course_categories} AS cc ON cc.id = c.category
                   WHERE c.open_costcenterid = $USER->open_costcenterid";
    }else if(!has_capability('local/costcenter:manage_ownorganization',$systemcontext) && has_capability('local/costcenter:manage_owndepartments',$systemcontext) && has_capability('block/learnerscript:managelsreports',$systemcontext)){
        $formsql = " JOIN {local_costcenter} AS co ON co.id = c.open_costcenterid
                   JOIN {course_categories} AS cc ON cc.id = c.category
                   WHERE c.open_costcenterid = $USER->open_costcenterid"; 
        if($USER->open_departmentid){
            $formsql .= " AND c.open_departmentid = $USER->open_departmentid";
        }
    }
    else if((!is_siteadmin() && !has_capability('local/costcenter:manage_multiorganizations', $systemcontext)) && !has_capability('local/costcenter:manage_ownorganization',$systemcontext) && has_capability('block/learnerscript:managelsreports',$systemcontext) && has_capability('block/learnerscript:manageownreports',$systemcontext)){

        $formsql = " JOIN {local_costcenter} AS co ON co.id = c.open_costcenterid
                   JOIN {course_categories} AS cc ON cc.id = c.category
                   WHERE c.open_costcenterid = $USER->open_costcenterid 
                   ";
    }
    else if(!has_capability('local/costcenter:manage_ownorganization',$systemcontext) && !has_capability('local/costcenter:manage_owndepartments',$systemcontext) && !has_capability('block/learnerscript:managelsreports',$systemcontext) && has_capability('block/learnerscript:manageownreports',$systemcontext)) {
        $formsql = " JOIN {local_costcenter} AS co ON co.id = c.open_costcenterid
                   JOIN {course_categories} AS cc ON cc.id = c.category
                   WHERE c.open_costcenterid = $USER->open_costcenterid 
                   AND c.open_departmentid = $USER->open_departmentid";
    }
    $formsql .= " AND c.id>1 AND c.open_parentcourseid=0 ";
    $totalcourses = $DB->count_records_sql($countsql.$formsql);
    if(is_siteadmin() || has_capability('local/costcenter:manage_ownorganization',$systemcontext)){
        $courses_content .= '<div class="w-full pull-left list_wrapper pink_block">
                                <div class="w-full pull-left top_content">
                                    <span class="pull-left quick_links_icon"><i class="fa fa-book" aria-hidden="true" aria-label=""></i></span>
                                    <span class="pull-right quick_links_count"><a href="'.$CFG->wwwroot.'/local/courses/courses.php">'.$totalcourses.'</a></span>
                                </div>
                                <div class="w-full pull-left pl-15px pr-15px"><a class="quick_link" href="'.$CFG->wwwroot.'/local/courses/courses.php">'.get_string('manage_courses', 'local_courses').'</a></div>
                            </div>';
    }
    return array('2' => $courses_content);
}

/*
* Author Sarath
* return count of courses under selected costcenter
* @return  [type] int count of courses
*/
function costcenterwise_courses_count($costcenter,$department=false,$category = false){
    global $USER, $DB;
        $params = array();
        $params['costcenter'] = $costcenter;
        $countcoursesql = "SELECT count(id) FROM {course} WHERE open_costcenterid = :costcenter AND open_parentcourseid = 0 AND sold_status IS NULL";
        if($category){
             $countcoursesql .= " AND category = :category ";
            $params['category'] = $category;
        }
        $activesql = " AND visible = 1 ";
        $inactivesql = " AND visible = 0 ";

        $countcourses = $DB->count_records_sql($countcoursesql, $params);
        $activecourses = $DB->count_records_sql($countcoursesql.$activesql, $params);
        $inactivecourses = $DB->count_records_sql($countcoursesql.$inactivesql, $params);

    return array('coursecount' => $countcourses,'activecoursecount' => $activecourses,'inactivecoursecount' => $inactivecourses);
}
function get_listof_courses($stable, $filterdata) {
   // print_r($filterdata);
   //  exit;
    global $CFG,$DB,$OUTPUT,$USER,$PAGE;
    $core_component = new core_component();
    require_once($CFG->libdir. '/coursecatlib.php');
    require_once($CFG->dirroot.'/course/renderer.php');
    require_once($CFG->dirroot . '/enrol/locallib.php');
  
    $autoenroll_plugin_exist = $core_component::get_plugin_directory('enrol','auto');
    if(!empty($autoenroll_plugin_exist)){
      require_once($CFG->dirroot . '/enrol/auto/lib.php');
    }
    $systemcontext = context_system::instance();
    $PAGE->requires->js_call_amd('local_courses/courseAjaxform', 'load');
    $departmentsparams = array();
    $subdepartmentsparams = array();
    $organizationsparams = array();
    $userorg = array();
    $userdep = array();
    $filtercategoriesparams= array();
    $filtercoursesparams = array();
    $chelper = new coursecat_helper();
    $selectsql = "SELECT c.id, c.fullname, c.shortname, c.category,c.open_points,c.open_costcenterid,c.open_departmentid, c.open_identifiedas, c.visible, c.open_skill, c.open_univdept_status FROM {course} AS c"; 
    $countsql  = "SELECT count(c.id) FROM {course} AS c ";
    $filterdata->departments = str_replace('_qf__force_multiselect_submission', '', $filterdata->departments);
    $filterdata->courses = str_replace('_qf__force_multiselect_submission', '', $filterdata->courses);
    $filterdata->organizations = str_replace('_qf__force_multiselect_submission', '', $filterdata->organizations);
    if(is_siteadmin()){
    $formsql = " JOIN {local_costcenter} AS co ON co.id = c.open_costcenterid
                 JOIN {course_categories} AS cc ON cc.id = c.category";
    }else if(has_capability('local/costcenter:manage_ownorganization',$systemcontext)){
    $formsql = " JOIN {local_costcenter} AS co ON co.id = c.open_costcenterid
               JOIN {course_categories} AS cc ON cc.id = c.category
               WHERE c.open_costcenterid = $USER->open_costcenterid";
    }else if(has_capability('local/costcenter:manage_owndepartments',$systemcontext)){
    $formsql = " JOIN {local_costcenter} AS co ON co.id = c.open_costcenterid
               JOIN {course_categories} AS cc ON cc.id = c.category
               WHERE c.open_costcenterid = $USER->open_costcenterid 
               AND c.open_departmentid = $USER->open_departmentid";
    }else{
    $formsql = " JOIN {local_costcenter} AS co ON co.id = c.open_costcenterid
               JOIN {course_categories} AS cc ON cc.id = c.category
               WHERE c.open_costcenterid = $USER->open_costcenterid 
               AND c.open_departmentid = $USER->open_departmentid";
    }
    $formsql .= " AND c.id>1 AND c.open_parentcourseid = 0 and c.forpurchaseindividually IS NULL";

    if(isset($filterdata->search_query) && trim($filterdata->search_query) != ''){
        $formsql .= " AND c.fullname LIKE :search";
        $searchparams = array('search' => '%'.trim($filterdata->search_query).'%');
    }else{
        $searchparams = array();
    }
    if(!empty($filterdata->courses)){
        $filtercourses = explode(',', $filterdata->courses);
        list($filtercoursessql, $filtercoursesparams) = $DB->get_in_or_equal($filtercourses, SQL_PARAMS_NAMED, 'param', true, false);
        $formsql .= " AND c.id $filtercoursessql";
    }
    
    if(!empty($filterdata->departments)){
        $departments = explode(',', $filterdata->departments);
        if(in_array(-1 , $departments)){
            $departments[array_search(-1, $departments)] = 0;
        }
        list($departmentssql, $departmentsparams) = $DB->get_in_or_equal($departments, SQL_PARAMS_NAMED, 'param', true, false);
        $formsql .= " AND c.open_departmentid $departmentssql";
    
    }
    
    if(!empty($filterdata->organizations)){
        $organizations = explode(',', $filterdata->organizations);
        list($organizationssql, $organizationsparams) = $DB->get_in_or_equal($organizations, SQL_PARAMS_NAMED, 'param', true, false);
        $formsql .= " AND c.open_costcenterid $organizationssql";
    }

    if (!is_siteadmin()) {
        $userorg = array('usercostcenter'=>$USER->open_costcenterid);
        $userdep = array('userdepartment'=>$USER->open_departmentid);
    }

    $params = array_merge($searchparams, $userorg, $userdep, $filtercategoriesparams, $filtercoursesparams, $departmentsparams, $subdepartmentsparams, $organizationsparams);
    $totalcourses = $DB->count_records_sql($countsql.$formsql, $params);
    $formsql .=" ORDER BY c.id DESC";
    $courses = $DB->get_records_sql($selectsql.$formsql, $params, $stable->start,$stable->length);

    $courseslist = array();
    if(!empty($courses)){
        $count = 0;
        foreach ($courses as $key => $course) {
            $course_in_list = new course_in_list($course);
            $context = context_course::instance($course->id);
            $category = $DB->get_record('course_categories',array('id'=>$course->category));

            $params = array('courseid'=>$course->id);
            
     
            $coursename = $course->fullname;
           
            if (strlen($coursename)>23){
                $coursenameCut = substr($coursename, 0, 23)."...";
                $courseslist[$count]["coursenameCut"] = $coursenameCut;
            }
    $soldstatus = $DB->get_field('course','sold_status',array('id'=>$course->id));
 
    $action1 = '';
 
      if($soldstatus == 1){
              $tmpcourse = $DB->get_records_sql("SELECT id,fullname,open_parentcourseid FROM {course} WHERE open_parentcourseid =".$course->id);

         if(!empty($tmpcourse)){

            foreach($tmpcourse as $tempcourse){
                $enrolledusersquery = "SELECT e.id FROM {user_enrolments} ue JOIN {enrol} e ON ue.enrolid = e.id WHERE e.courseid = ".$tempcourse->id;
                 $enroledusers = $DB->get_records_sql_menu($enrolledusersquery);
             }
           
             if(!empty($enroledusers)){
               
                 $visibleurl = html_writer::link('javascript:void(0)','<i class="icon fa fa-toggle-on" aria-hidden="true"></i>', array('title' => get_string('enroledusers','local_courses'), 'id' => $course->id));
                $action1 .= $visibleurl;
              

              }
              else{
               $visibleurl = html_writer::link('javascript:void(0)','<i class="icon fa fa-toggle-on" aria-hidden="true"></i>', array('id' => $course->id, 'onclick'=>'(function(e){ require(\'local_courses/courseAjaxform\').courseavailability({id: '.$course->id.', context:1,action: "availabityyes",fullname:"'.$course->fullname.'" }) })(event)'));
                $action1 .= $visibleurl;
              }                     
       } 
       else{
           
            $visibleurl = html_writer::link('javascript:void(0)','<i class="icon fa fa-toggle-on" aria-hidden="true"></i>', array('id' => $course->id, 'onclick'=>'(function(e){ require(\'local_courses/courseAjaxform\').courseavailability({id: '.$course->id.', context:1,action: "availabityyes",fullname:"'.$course->fullname.'" }) })(event)'));
            $action1 = $visibleurl;
          }
        }else{
          
            $hideurl = html_writer::link('javascript:void(0)', '<i class="icon fa fa-toggle-off" aria-hidden="true"></i>', array('id' => $course->id, 'onclick'=>'(function(e){ require(\'local_courses/courseAjaxform\').courseavailability({id: '.$course->id.', context:1,action: "availabityno", fullname:"'.$course->fullname.'" }) })(event)'));
            $action1 = $hideurl;
        }
            $curriculumcount = $DB->count_records_sql("SELECT COUNT(id) FROM {local_cc_semester_courses} WHERE open_parentcourseid =:courseid",array('courseid' => $course->id));

            $catname = $category->name;
            $catnamestring = $catname;
            $displayed_names = implode(',' ,$displayed_names);
            $courseslist[$count]["coursename"] = $coursename;
            $courseslist[$count]["catname"] = $catname;
            $courseslist[$count]["catnamestring"] = $catnamestring;
            $courseslist[$count]["enrolled_count"] = $enrolled_count;
            $courseslist[$count]["courseid"] = $course->id;
            $courseslist[$count]["action1"] = $action1;
            $courseslist[$count]["coursecode"] = $course->shortname;
            $courseslist[$count]["curriculumcount"] = $curriculumcount ? $curriculumcount : 0;
            // changes for https://eabyas.atlassian.net/browse/REX-81
            $courseslist[$count]["masterclass"] = $masterclass->fullname?$masterclass->fullname: get_string('all');
            $courseslist[$count]["syllabus"] = ($depvalue = $DB->get_field('local_costcenter', 'fullname',['id'=>$course->open_departmentid]))? $depvalue:get_string('all');
            $courseslist[$count]["school"] = ($costvalue = $DB->get_field('local_costcenter', 'fullname',['id'=>$course->open_costcenterid]))? $costvalue:get_string('all');

// <mallikarjun> - ODL-782 labels display -- starts
if($course->open_univdept_status == 1){
$courseslist[$count]["open_univdept_status"] = $course->open_univdept_status;
}
// <mallikarjun> - ODL-782 labels display -- end
            $coursesummary = strip_tags($chelper->get_course_formatted_summary($course_in_list,
                    array('overflowdiv' => false, 'noclean' => false, 'para' => false)));
            $summarystring = strlen($coursesummary) > 100 ? substr($coursesummary, 0, 100)."..." : $coursesummary;
            $courseslist[$count]["coursesummary"] = $summarystring;
    
            //course image
            if(file_exists($CFG->dirroot.'/local/includes.php')){
                require_once($CFG->dirroot.'/local/includes.php');
                $includes = new user_course_details();
                $courseimage = $includes->course_summary_files($course);                
                if(is_object($courseimage)){
                    $courseslist[$count]["courseimage"] = $courseimage->out();                    
                }else{
                    $courseslist[$count]["courseimage"] = $courseimage;
                }                
            }            

            $courseslist[$count]["courseurl"] = $CFG->wwwroot."/course/view.php?id=".$course->id;
            $enrolid = $DB->get_field('enrol','id',array('enrol'=>'manual','courseid'=>$course->id));
            
            $categorycontext = context_coursecat::instance($course->category);
            
            if((has_capability('local/costcenter_course:update',context_system::instance()) || is_siteadmin()) &&has_capability('local/costcenter_course:manage', $systemcontext)) {
                $courseedit = html_writer::link('javascript:void(0)', html_writer::tag('i', '', array('class' => 'fa fa-pencil icon')) , array('title' => get_string('edit'), 'alt' => get_string('edit'),'data-action' => 'createcoursemodal', 'class'=>'createcoursemodal', 'data-value'=>$course->id, 'onclick' =>'(function(e){ require("local_courses/courseAjaxform").init({contextid:'.$categorycontext->id.', component:"local_courses", callback:"custom_course_form", form_status:0, plugintype: "local", pluginname: "courses", courseid: ' . $course->id . ' }) })(event)'));
                $courseslist[$count]["editcourse"] = $courseedit;

                if(!empty($autoenroll_plugin_exist)){
                    $autoplugin = enrol_get_plugin('auto');
                    $instance = $autoplugin->get_instance_for_course($course->id);
                    if($instance){
                        if ($instance->status == ENROL_INSTANCE_DISABLED) {
                            
                        $courseslist[$count]["auto_enrol"] = $CFG->wwwroot."/enrol/auto/edit.php?courseid=".$course->id."&id=".$instance->id;
                        }
                    }
                }
            }
            if($course->id){
                $duplicatedcount = $DB->count_records_sql("SELECT count(id) FROM {course}
                                                    WHERE open_parentcourseid = $course->id");   
            }
            if(has_capability('local/costcenter_course:delete',$systemcontext)&&has_capability('local/costcenter_course:manage', $systemcontext)){
                 $deleteurl = html_writer::link('javascript:void(0)', $OUTPUT->pix_icon('t/delete', get_string('delete'), 'moodle', array('')), array('title' => get_string('delete'), 'id' => "courses_delete_confirm_".$course->id,'onclick'=>'(function(e){ require(\'local_courses/courseAjaxform\').deleteConfirm({action:\'deletecourse\' , id: ' . $course->id . ', count:'.$duplicatedcount.',name:"'.$course->fullname.'" }) })(event)'));
                 $courseslist[$count]["deleteaction"]= $deleteurl;
            }
            
            if((has_capability('local/costcenter_course:grade_view',context_system::instance()) || is_siteadmin())&&has_capability('local/costcenter_course:manage', $systemcontext)){
                $courseslist[$count]["grader"] = $CFG->wwwroot."/grade/report/grader/index.php?id=".$course->id."&enrolid=".$enrolid;
            }
            if((has_capability('local/costcenter_course:report_view',context_system::instance()) || is_siteadmin())&&has_capability('local/costcenter_course:manage', $systemcontext)){
                $courseslist[$count]["activity"] = $CFG->wwwroot."/report/outline/index.php?id=".$course->id;
            }
            if(has_capability('local/costcenter_course:delete',$systemcontext)&&has_capability('local/costcenter_course:manage', $systemcontext)){
                $courseslist[$count]["enrolusers"] = $CFG->wwwroot."/local/courses/courseenrol.php?id=".$course->id."&enrolid=".$enrolid;
            }
            $count++;
        }
        $nocourse = false;
        $pagination = false;
    }else{
        $nocourse = true;
        $pagination = false;
    }
    // check the course instance is not used in any plugin
    $candelete = true;
    $core_component = new core_component();
    
    $program_plugin_exist = $core_component::get_plugin_directory('local', 'program');
    if ($program_plugin_exist) {
        $exist_sql = "Select id from {local_cc_semester_courses} where courseid = ?";
        if ($DB->record_exists_sql($exist_sql, array($course->id)))
        $candelete = false;
    }
    $coursesContext = array(
        "hascourses" => $courseslist,
        "nocourses" => $nocourse,
        "totalcourses" => $totalcourses,
        "length" => count($courseslist),
        "actions"=>(((has_capability('local/costcenter_course:enrol',
        context_system::instance())|| has_capability('local/costcenter_course:update',
        context_system::instance())||has_capability('local/costcenter_course:delete',
        context_system::instance()) || has_capability('local/costcenter_course:grade_view',
        context_system::instance())|| has_capability('local/costcenter_course:report_view',
        context_system::instance())) || is_siteadmin())&&has_capability('local/costcenter_course:manage', $systemcontext)) ? true : 0,
        "enrol"=>((has_capability('local/costcenter_course:enrol',
        context_system::instance())  || is_siteadmin())&&has_capability('local/costcenter_course:manage', $systemcontext)) ? true : 0,
        "update"=>((has_capability('local/costcenter_course:update',
        context_system::instance()) || is_siteadmin())&&has_capability('local/costcenter_course:manage', $systemcontext)) ? true : 0,
        "delete"=>((has_capability('local/costcenter_course:delete',
        context_system::instance()) || is_siteadmin())&&has_capability('local/costcenter_course:manage', $systemcontext)) ? true : 0,
        "grade_view"=>((has_capability('local/costcenter_course:grade_view',
        context_system::instance()) || is_siteadmin())&&has_capability('local/costcenter_course:manage', $systemcontext)) ? true : 0,
        "report_view"=>((has_capability('local/costcenter_course:report_view',
        context_system::instance()) || is_siteadmin())&&has_capability('local/costcenter_course:manage', $systemcontext)) ? true : 0,
    );
    return $coursesContext;

}

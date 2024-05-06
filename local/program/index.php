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
 * Take attendance in curriculum.
 *
 * @package    local_curriculum
 * @copyright  2017 M Arun Kumar <arun@eabyas.in>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
define('NO_OUTPUT_BUFFERING', true);
global $CFG, $DB, $PAGE, $USER, $OUTPUT;
require_once($CFG->dirroot.'/local/program/lib.php');
require_once($CFG->dirroot. '/course/lib.php');
require_login();
$core_component = new core_component();
$courses_plugin_exists = $core_component::get_plugin_directory('local', 'courses');
if (!empty($courses_plugin_exists)) {
    require_once($CFG->dirroot . '/local/courses/filters_form.php');
}
use local_program\program;
$context = context_system::instance();
$formdataids = optional_param_array('id',array(), PARAM_RAW);
$type = optional_param('type',1, PARAM_RAW);
$deleteprogramid = optional_param('deleteprogramid',0, PARAM_RAW);
$delete = optional_param('delete', '', PARAM_ALPHANUM); // Confirmation hash.
$confirmid = optional_param('confirmid', '', PARAM_ALPHANUM); // Confirmation hash.
$PAGE->set_context($context);
$url = new moodle_url($CFG->wwwroot . '/local/program/index.php', array('type'=>$type));
$PAGE->requires->js_call_amd('local_program/ajaxforms', 'load');
if ((is_siteadmin() || (has_capability('local/program:manage_multiorganizations', context_system::instance()) ||has_capability('local/program:manage_ownorganization', context_system::instance())))) {

    $copytype=1;
    if($type==2){
      $copytype=2;
    }
}else{
    $copytype=2;
}
if(is_siteadmin() || has_capability('local/program:manageprogram', context_system::instance())){
    $labelname=get_string('manage_programs','local_program');
}else{
    $labelname=get_string('view_programs','local_program');
}

$filterlist = get_programfilterslist($type );

if (!empty($courses_plugin_exists)) {
    $mform = new filters_form($url, array('filterlist' => $filterlist,'action' => 'user_enrolment'));
    if ($mform->is_cancelled()) {
        redirect($url);
    } else {
        $filterdata = $mform->get_data();
        if ($filterdata) {
            $collapse = false;
        } else {
            $collapse = true;
        }
    }
}
$options = json_encode($_REQUEST);

$PAGE->requires->js_call_amd('local_program/program', 'ProgramsDatatable',array(array('type'=>$copytype,'options'=>$options)));
$PAGE->requires->css('/local/program/css/jquery.dataTables.min.css');
$PAGE->requires->css('/local/program/css/dataTables.checkboxes.css');


$renderer = $PAGE->get_renderer('local_program');
$PAGE->set_url($url);
$PAGE->set_pagelayout('admin');
/*$PAGE->navbar->add(get_string("pluginname", 'local_program'));
*/$PAGE->navbar->add($labelname);
$PAGE->set_title($labelname);
$PAGE->set_heading($labelname);


echo $OUTPUT->header();
if(empty($deleteprogramid)){
    echo "<div class='col-xs-12 page-desc textovr' id='textovr'><b class='page-hrdesc'>Description:</b><br>
    The page lists all the programs created under university/universities. Filters can be seen upon expanding the toggle provided below. Apply filters to filter programs by the university.<br>
    <b>University Programs:</b> This tab displays the 'Master version of Programs' offered by a University. University admin/eLearning admin can define the program structure (create semesters under years, add courses under semesters, set completion criteria to courses, etc,) and until the program is 'ready' with all required settings it cannot be 'published'. After programs are 'published', 'affiliated colleges' can be approved to offer the programs through an approval screen provided.<br>
    <b>College Programs:</b> This tab displays the programs offered under various affiliated colleges/study centers after getting approvals from University.
    These programs are synced to CMS to be purchased by students.
    <a href='javascript:void(0);' id='txtoverflow' onclick='show_txt()' class='showmore'>show more</a>
    <a href='javascript:void(0);' id='txtunderrflow' onclick='hide_txt()' class='showless' style='display:none;'>show less</a>
    </div>";
}
if($deleteprogramid){
    $programtimecreated = $DB->get_field('local_program','timecreated',array('id' => $deleteprogramid));
    if($delete === md5($programtimecreated)){
        $reurn=(new program)->uncopy_program_instance($deleteprogramid,$showfeedback = true,$progressbar=true);
        echo $reurn;
        $button = new single_button($PAGE->url, get_string('click_continue','local_program'), 'get', true);
        $button->class = 'continuebutton';
        echo $OUTPUT->render($button);
    }else{
        $program = $DB->get_record('local_program', array('id' => $deleteprogramid), 'id,fullname,shortname,shortcode,timecreated', MUST_EXIST);

        $programshortcode = format_string($program->shortcode, true);
        $programshortname = format_string($program->shortname, true);
        $programfullname = format_string($program->fullname, true);

        $strdeleteprogramcheck = get_string("deleteprogramcheck",'local_program');

        $categoryurl = new moodle_url('index.php');

        $message = "{$strdeleteprogramcheck}<br /><br /><b>{$programfullname}</b>";

        $continueurl = new moodle_url('index.php', array('deleteprogramid' => $program->id, 'delete' => md5($program->timecreated)));
        $continuebutton = new single_button($continueurl, get_string('delete'), 'post');
        echo $OUTPUT->confirm($message, $continuebutton, $categoryurl);
    }
}
elseif((($formdataids = data_submitted()) || $confirmid)&&$_REQUEST['_qf__filters_form']==0){
    $formdataids = $formdataids->programid;
    if($formdataids){
        $copiedlist=(new program)->move_universityprogram_instance($formdataids,$url);

        $mform = new program_collegeapproval_form($url, array('copiedlist' =>$copiedlist),'post','',array('id'=>'collegeaffliated'));
        $mform->display();

    }else{

        $data=data_submitted();

         if (isset($data->cancel)) {
             redirect($url);
         } else if (isset($data->saveanddisplay)&&$data->confirmid==md5($USER->username)) {
            $out=$renderer->college_affliated_program($data,$url);
            echo $out;
         }
    }

}
else{
    if(!has_capability('local/program:manageprogram', $context) && !has_capability('local/program:viewprogram', $context)){
        print_error('You donot have permission this page.');
    }else{
        if (!empty($courses_plugin_exists)) {
            print_collapsible_region_start('program_filters', 'filters_form', ' ' . ' ' . get_string('filters'), false, $collapse);
                $mform->display();
            print_collapsible_region_end();
        }

        $stable = new stdClass();
        $stable->thead = true;
        $stable->start = 0;
        $stable->length = -1;
        $stable->search = '';
        echo $renderer->viewcurriculumprograms($stable,$type,$options);

    }
}

echo '<script>
    var click_show = document.getElementById("textovr");
    var overflow = document.getElementById("overflow");
    var underrflow = document.getElementById("txtunderrflow");
    function show_txt(){
        click_show.classList.toggle("vis");
        document.getElementById("txtoverflow").style.display="none";
        document.getElementById("txtunderrflow").style.display="block";
    }
    function hide_txt(){
        click_show.classList.toggle("vis");
        document.getElementById("txtoverflow").style.display="block";
        document.getElementById("txtunderrflow").style.display="none";
    }
</script>';
echo $OUTPUT->footer();

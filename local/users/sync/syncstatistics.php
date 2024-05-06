<?php

require_once(dirname(__FILE__) . '/../../../config.php');

global $DB,$PAGE,$CFG,$OUTPUT;
$PAGE->requires->jquery();
$PAGE->requires->jquery_plugin('ui');
$PAGE->requires->jquery_plugin('ui-css');
$PAGE->requires->css('/local/users/sync/datatables.min.css');

$PAGE->requires->js('/local/users/js/cofilter.min.js', true);
$PAGE->requires->js('/local/users/js/syncstatdel.js', true);
$PAGE->requires->js_call_amd('local_users/datatablesamd', 'syncStatsDatatable', array('url' => $errorprocessingurl ));
$systemcontext = context_system::instance();
$PAGE->set_context($systemcontext);
$PAGE->set_url('/local/users/sync/syncstatistics.php');
$PAGE->set_pagelayout('admin');
$strheading = get_string('sync_stats', 'local_users');
$PAGE->set_title($strheading);
require_login();
$PAGE->navbar->add(get_string('pluginname', 'local_users'), new moodle_url('/local/users/index.php'));
$PAGE->navbar->add($strheading);
$PAGE->set_heading(get_string('syncstatistics','local_users'));
echo $OUTPUT->header();
if(!(has_capability('local/users:create',$systemcontext) || is_siteadmin())){
    echo print_error('no permission');
}
//echo "<h2 class='tmhead2'><div class='iconimage'></div>Sync Statistics</h3>";
echo html_writer::link(new moodle_url('/local/users/'),'Back',array('id'=>'sync_data'));
if((has_capability('local/costcenter:manage_multiorganizations',$systemcontext) || is_siteadmin())){
    $sql="SELECT * FROM {local_userssyncdata}";
} else {
    $sql="SELECT * FROM {local_userssyncdata} where usercreated = $USER->id";
}
$hrmssynclist = $DB->get_records_sql($sql);
$table = new html_table();
$table->id = 'syncdata';
$table->head = array('New Users Count','Updated Users Count','Errors Count','Warnings Count', 'Supervisor Warnings Count', 'Uploaded By', 'Uploaded on', 'Time Modified','Delete');
$table->align = array('center','center','center','center', 'center', 'center', 'center', 'center','center');

     $data=array();
     foreach($hrmssynclist as $hrmssyncinfo){
        $list=array();
        $check='<input type="checkbox" name="checkbox_id[]" class="delete_user" value="'. $hrmssyncinfo->id.'"/>';  
        $list[]= $hrmssyncinfo->newuserscount;
        $list[]= $hrmssyncinfo->updateduserscount;
        $list[]= $hrmssyncinfo->errorscount;
        $list[]= $hrmssyncinfo->warningscount;
        $list[]= $hrmssyncinfo->supervisorwarningscount;
        $usercreated = $DB->get_record('user', array('id'=>$hrmssyncinfo->usercreated));
        $list[]= $usercreated->firstname. ' '. $usercreated->lastname;
        $list[]= date("d/m/Y",$hrmssyncinfo->timecreated);
        $list[]= date("d/m/Y",$hrmssyncinfo->timemodified);
        $list[]= $check;
        $data[]=$list;
     }
$table->data=$data;
echo '<div class="filterarea" style="text-align:center"></div>';
echo html_writer::table($table);                                                                       
echo html_writer::tag('button', get_string('delete'), array('id'=>'btn_delete', 'style'=>'float: right;margin: 3px 10px 0px 0px;'));


                        
echo $OUTPUT->footer();
?>

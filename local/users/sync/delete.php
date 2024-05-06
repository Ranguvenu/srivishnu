<?php
require_once(dirname(__FILE__) . '/../../../config.php');
global $DB, $PAGE,$CFG,$OUTPUT;
//----------------Setting the page url---------------
$PAGE->set_url('/local/users/delete.php');
$PAGE->set_context(context_system::instance());
//$id = optional_param('rid',0,PARAM_INT);
$PAGE->set_pagelayout('admin');
if(isset($_POST["id"]))
{
 foreach($_POST["id"] as $id)
 {
  $DB->delete_records('local_userssyncdata',array('id'=>$id));
 }
}

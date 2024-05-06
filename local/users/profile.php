<?php
require_once(dirname(__FILE__) . '/../../config.php');

global $CFG,$USER, $DB, $PAGE;
$PAGE->requires->jquery();

$id = optional_param('id', $USER->id, PARAM_INT);

$PAGE->set_url('/local/users/profile.php', array('id' => $id));
$systemcontext = context_system::instance();
$PAGE->set_context($systemcontext);
$PAGE->requires->js_call_amd('local_users/newuser', 'load', array());
$PAGE->set_pagelayout('context_image');
require_login();

$strheading = get_string('viewprofile', 'local_users');
$PAGE->set_title(get_string('viewprofile', 'local_users'));
if (($id != $USER->id) AND (!(is_siteadmin() OR has_capability('local/costcenter:manage_multiorganizations',$systemcontext)))) {
    if(has_capability('local/users:create',$systemcontext)){
        $usercostcenter = $DB->get_field('user', 'open_costcenterid', array('id'=>$id));
        $managercostcenter = $DB->get_field('user', 'open_costcenterid', array('id'=>$USER->id));
        if ($usercostcenter != $managercostcenter) {
            echo print_error('no permission');
        }
    } else {
        echo print_error('no permission');
    }
}
$userid = $id ? $id : $USER->id; // Owner of the page
echo $OUTPUT->header();
echo '<style>
    header#page-header{margin-bottom: 20px;}
    header#page-header .card{display: none;}
</style>';
$renderer   = $PAGE->get_renderer('local_users');

echo $renderer->employees_profile_view($id);

echo $OUTPUT->footer();
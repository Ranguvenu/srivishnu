<?php
/*
 * File displays the edwiser bridge settings.
 */

require('../../config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once('mod_form.php');

// Restrict normal user to access this page
admin_externalpage_setup('edwiserbridge');

// Require Login
require_login();
$context = context_system::instance();
$baseurl = $CFG->wwwroot.'/local/edwiserbridge/edwiserbridge.php?tab=connection';

$mform_connection      = new edwiserbridge_connection_form($CFG->wwwroot.'/local/edwiserbridge/edwiserbridge.php?tab=connection', null, 'post', '', array("id" => "eb_conne_form"), true, null);
$mform_synchronization = new edwiserbridge_synchronization_form($CFG->wwwroot.'/local/edwiserbridge/edwiserbridge.php?tab=synchronization', null, 'post', '', array("id" => "eb_synch_form"), true, null);
$mform_navigation      = new edwiserbridge_navigation_form();

/*
 * Necessary page requirements.
 */
$PAGE->requires->jquery();
$PAGE->requires->jquery_plugin('ui');
$PAGE->requires->jquery_plugin('ui-css');
$PAGE->set_pagelayout('admin');
$PAGE->set_context($context);
$PAGE->set_url('/local/edwiserbridge/edwiserbridge.php?tab=connection');
$PAGE->set_title(get_string('eb-setting-page-title', 'local_edwiserbridge'));
$PAGE->requires->css('/local/edwiserbridge/styles/style.css');
$PAGE->requires->js_call_amd("local_edwiserbridge/edwiser_bridge", "init");


echo $OUTPUT->header();
echo $OUTPUT->container_start();


/*
 * Navigation form
 */
$mform_navigation->display();



//Connection form processing and displaying.
if ($mform_connection->is_cancelled()) {
    //Handle form cancel operation, if cancel button is present on form
} elseif ($form_data = $mform_connection->get_data()) {
    //In this case you process validated data. $mform->get_data() returns data posted in form.
    save_connection_form_settings($form_data);

    // print_object($form_data);
    $mform_connection->display();
} else {
    //Display connection form  for the first time.
    if (isset($_GET["tab"]) && $_GET["tab"] == "connection") {
        $mform_connection->display();
    }
}


//synchronization form processing and displaying.
if ($mform_synchronization->is_cancelled()) {
    //Handle form cancel operation, if cancel button is present on form
} elseif ($form_data = $mform_synchronization->get_data()) {
    //In this case you process validated data. $mform->get_data() returns data posted in form.
    save_synchronization_form_settings($form_data);
    //print_object($form_data);
    $mform_synchronization->display();
} else {
    // Display synchronization form for the first time.
    if (isset($_GET["tab"]) && $_GET["tab"] == "synchronization") {
        $mform_synchronization->display();
    }
}


echo $OUTPUT->container_end();
echo $OUTPUT->footer();

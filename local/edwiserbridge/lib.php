<?php

require_once(dirname(__FILE__)."/classes/class-api-handler.php");
require_once("{$CFG->libdir}/completionlib.php");


function local_edwiserbridge_extend_settings_navigation($settingsnav, $context)
{

}

/*function local_edwiserbridge_output_fragment_test($args) {
    global $CFG;
    // require_once($CFG->dirroot . '/local/edwiserbridge/mod_form.php');
    // $mform_connection = new edwiserbridge_connection_form($CFG->wwwroot.'/local/edwiserbridge/edwiserbridge.php?tab=connection', null, 'post', '', array("id" => "eb_conne_form"), true, null);
    // return $mform_connection->render();
    ob_start();
    print_r($args);
    return ob_get_clean();
}*/

/**
 * [save_connection_form_settings description]
 * @param  [type] $form_data [description]
 * @return [type]            [description]
 */
function save_connection_form_settings($form_data)
{
    if (count($form_data->wp_url) != count($form_data->wp_token)) {
        return;
    }

    $connection_settings = array();
    for ($i=0; $i<count($form_data->wp_url); $i++) {
        if (!empty($form_data->wp_url[$i]) && !empty($form_data->wp_token[$i]) && !empty($form_data->wp_name[$i])) {
            $connection_settings[$form_data->wp_name[$i]] = array(
                "wp_url" => $form_data->wp_url[$i],
                "wp_token" => $form_data->wp_token[$i],
                "wp_name" => $form_data->wp_name[$i]
            );
        }
    }
    set_config("eb_connection_settings", serialize($connection_settings));
}

/**
 * save the synch settings for the individual site
 */
function save_synchronization_form_settings($form_data)
{
    global $CFG;
    $synch_settings = array();
    $connection_settings = unserialize($CFG->eb_connection_settings);
    $connection_settings_keys = array_keys($connection_settings);


    if (in_array($form_data->wp_site_list, $connection_settings_keys)) {
        $existing_synch_settings = unserialize($CFG->eb_synch_settings);
        $synch_settings = $existing_synch_settings;
        $synch_settings[$form_data->wp_site_list] = array(
        // "course_enrollment"    => $form_data->wp_site_list,
        "course_enrollment"    => $form_data->course_enrollment,
        "course_un_enrollment" => $form_data->course_un_enrollment,
        "user_creation"        => $form_data->user_creation,
        "user_deletion"        => $form_data->user_deletion
        );
    } else {
        $synch_settings[$form_data->wp_site_list] = array(
            // "course_enrollment"    => $form_data->wp_site_list,
            "course_enrollment"    => $form_data->course_enrollment,
            "course_un_enrollment" => $form_data->course_un_enrollment,
            "user_creation"        => $form_data->user_creation,
            "user_deletion"        => $form_data->user_deletion
        );
    }
    set_config("eb_synch_settings", serialize($synch_settings));
}


/**
 * returns connection settings saved in the settings form.
 */
function get_connection_settings()
{
    global $CFG;
    $reponse["eb_connection_settings"] = isset($CFG->eb_connection_settings) ? unserialize($CFG->eb_connection_settings) : false;
    return $reponse;
}


/**
 * returns individual sites data.
 * @param  [type] $index [description]
 * @return [type]        [description]
 */
function get_synch_settings($index)
{
    global $CFG;
    $reponse = isset($CFG->eb_synch_settings) ? unserialize($CFG->eb_synch_settings) : false;

    $data = array(
        "course_enrollment"    => 0,
        "course_enrollment"    => 0,
        "course_un_enrollment" => 0,
        "user_creation"        => 0,
        "user_deletion"        => 0
    );

    if (isset($reponse[$index]) && !empty($reponse[$index])) {
        return $reponse[$index];
    }
    return $data;
}


/**
 * returns all the sites created in the edwiser settings.
 * @return [type] [description]
 */
function get_site_list()
{
    global $CFG;
    $reponse = isset($CFG->eb_connection_settings) ? unserialize($CFG->eb_connection_settings) : false;

    $sites = array();
    if ($reponse && count($reponse)) {
        foreach ($reponse as $key => $value) {
            $sites[$key] = $value["wp_name"];
        }
    } else {
        return array("" => "--- No Sites Available ---");
    }
    return $sites;
}



/**
 * Returns the main instance of EDW to prevent the need to use globals.
 *
 * @since  1.0.0
 *
 * @return EDW
 */
function api_handler_instance()
{
    return api_handler::instance();
}


/**
 * returns the list of courses in which user is enrolled
 * @return [type]          [description]
 */
function get_array_of_enrolled_courses($user_id)
{
    $enrolled_courses = enrol_get_users_courses($user_id);
    $courses = array();

    foreach ($enrolled_courses as $value) {
        array_push($courses, $value->id);
    }
    return $courses;
}

/**
 * removes processed coureses from the course whose progress is already provided.
 * @return [type]            [description]
 */
function remove_processed_coures($course_id, $courses)
{
    if (($key = array_search($course_id, $courses)) !== false) {
        unset($courses[$key]);
    }
    return $courses;
}

/**
 * functionality to check if the request is from wordpress and the stop processing the enrollment and unenrollment.
 * @return
 */
function check_if_request_is_from_wp()
{
    if (isset($_POST) && isset($_POST["enrolments"])) {
        return 1;
    }
    return 0;
}

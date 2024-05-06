<?php

/**
 * Language File
 *
 * @package   local_edwiser
 * @author    Wisdmlabs
 */


/**************  by default strings used by the moodle  ****************/

$string["local_edwiserbridgedescription"] = "";
$string['modulename'] = "Edwiser Bridge";
$string['modulenameplural'] = "Edwiser Bridge";
$string['pluginname'] = 'Edwiser Bridge';
$string['pluginadministration'] = "Edwiser Bridge administrator";
$string['modulename_help'] = '';

/**************  end of the strings used by default by the moodle  ****************/


/******* navigation menu and settings page   ********/

$string["wp_site_settings_title"] = "Site Settings :";

$string["nav_name"] = "Edwiser Bridge Settings";
$string["edwiserbridge"] = "Edwiser Bridge";
$string["eb-setting-page-title"] = "Edwiser Bridge Two Way Synchronization Settings";
$string["eb-setting-page-title_help"] = "Edwiser Bridge Two Way Synchronization Settings";

$string[""] = "Wordpress Synchronization Settings";
$string["enrollment_checkbox"] = "Enable user enrollment.";
$string["enrollment_checkbox_desc"] = "Enroll user from Moodle to Wordpress for linked users";
$string["unenrollment_checkbox"] = "Enable user un-enrollment.";
$string["unenrollment_checkbox_desc"] = "Unenroll user from Moodle to Wordpress for linked users";
$string["user_creation"] = "Enable user creation";
$string["user_creation_desc"] = "Create user In linked Wordpress site when created in Moodle Site";
$string["user_deletion"] = "Enable user deletion";
$string["user_deletion_desc"] = "Delete user In linked Wordpress site when deleted in Moodle Site";

$string["wp_settings_section"] = "Wordpress Connection Settings";
$string["wordpress_url"] = "Wordpress url";
$string["wp_token"] = "Access Token";
$string["wp_test_conn_btn"] = "Test Connection";
$string["wp_test_remove_site"] = "Remove Site";
$string["add_more_sites"] = "Add New Site";
$string["wordpress_site_name"] = "Site Name";
$string["site-list"] = "Site List";

$string["token_help"] = "Please enter access token used in Wordpress in connection setting";
$string["wordpress_site_name_help"] = "Please enter unique site name.";
$string["wordpress_url_help"] = "Please enter Wordpress site URL.";


$string["token"] = "Access Token";

/*********/


/******  Form validation.  ******/
$string['required'] = "- You must supply a value here.";
$string['sitename-duplicate-value'] = " - Site Name already exists, Please provide a different value.";
$string['url-duplicate-value'] = " - Wordpress Url already exists, Please provide a different value.";
/*******/


/*****  web service  *******/
$string["web_service_wp_url"] = "Wordpress site URL.";
$string["web_service_wp_token"] = "Web service token.";

$string["web_service_test_conn_status"] = '1 if successful connection and 0 on failure.';
$string["web_service_test_conn_msg"] = 'Success or error message.';

$string["web_service_site_index"] = "Site index is the nth no. of site saved in Edwiser Bridge settings.";


$string["web_service_course_enrollment"] = "Checks if the course enrollment is performed for the saved site";
$string["web_service_course_un_enrollment"] = "Checks if the course un-enrollment is performed for the saved site";
$string["web_service_user_creation"] = "Checks if the user creation is performed for the saved site";
$string["web_service_user_deletion"] = "Checks if the user deletion is performed for the saved site";


$string["web_service_offset"] = "This is the offset for the select query.";
$string["web_service_limit"] = "This limits the number of users returned.";
$string["web_service_search_string"] = "This string will be searched in the select query.";
$string["web_service_total_users"] = "Total number of users present in Moodle.";


$string["web_service_id"] = "User Id.";
$string["web_service_username"] = "Username of the user.";
$string["web_service_firstname"] = "Firstname of the user.";
$string["web_service_lastname"] = "Lastname of the user.";
$string["web_service_email"] = "Email of the user.";
/******/


/****  error handling  ***/
$string["default_error"] = "Please check the URL or wordpress site permalink: to know more about this error <a href='https://knowledgebase.edwiser.org/'  target='_blank'> click here </a>";

/**/

<?php

defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot.'/local/edwiserbridge/lib.php');
require_once($CFG->dirroot.'/user/lib.php');


class local_edwiserbridge_observer {

    /**
     * functionality to handle user enrollment event
     * @return [type]
     */
    public static function user_enrolment_created(core\event\user_enrolment_created $event)
    {
        global $CFG;
        $user_data = user_get_users_by_id(array($event->relateduserid));

        $request_data = array(
            "action" => "course_enrollment",
            "data" => serialize(
                array(
                    "user_id"     => $event->relateduserid,
                    "course_id"   => $event->courseid,
                    "user_name"   => $user_data[$event->relateduserid]->username,
                    "first_name"  => $user_data[$event->relateduserid]->firstname,
                    "last_name"   => $user_data[$event->relateduserid]->lastname,
                    "email"       => $user_data[$event->relateduserid]->email
                )
            )
        );


        if (check_if_request_is_from_wp()) {
            return;
        }

        $api_handler = api_handler_instance();
        // $api_handler = new api_handler();
        if (isset($CFG->eb_connection_settings)) {
            $sites = unserialize($CFG->eb_connection_settings);
            $synch_conditions = unserialize($CFG->eb_synch_settings);
            foreach ($sites as $key => $value) {
                if ($synch_conditions[$value["wp_name"]]["course_enrollment"]) {
                    $api_handler->connect_to_wp_with_args($value["wp_url"], $request_data);
                }
            }
        }
    }


    /**
     * functionality to handle user un enrollment event
     * @return [type]
     */
    public static function user_enrolment_deleted(core\event\user_enrolment_deleted $event)
    {
        global $CFG;
        $user_data = user_get_users_by_id(array($event->relateduserid));
        $request_data = array(
            "action" => "course_un_enrollment",
            "data" => serialize(
                array(
                    "user_id"     => $event->relateduserid,
                    "course_id"   => $event->courseid,
                    "user_name"   => $user_data[$event->relateduserid]->username,
                    "first_name"  => $user_data[$event->relateduserid]->firstname,
                    "last_name"   => $user_data[$event->relateduserid]->lastname,
                    "email"       => $user_data[$event->relateduserid]->email
                )
            )
        );

        //checks if the request is from the wordpress site or from te Moodle site itself
        if (check_if_request_is_from_wp()) {
            return;
        }

        $api_handler = api_handler_instance();
        if (isset($CFG->eb_connection_settings)) {
            $sites = unserialize($CFG->eb_connection_settings);
            $synch_conditions = unserialize($CFG->eb_synch_settings);

            foreach ($sites as $key => $value) {

                if ($synch_conditions[$value["wp_name"]]["course_un_enrollment"]) {
                    $api_handler->connect_to_wp_with_args($value["wp_url"], $request_data);
                }
            }
        }
    }


    /**
     * functionality to handle user creation event
     * @return [type]
     */
    public static function user_created(core\event\user_created $event)
    {
        global $CFG;
        $user_data = user_get_users_by_id(array($event->relateduserid));
        $request_data = array(
            "action" => "user_creation",
            "data" => serialize(
                array(
                    "user_id"     => $event->relateduserid,
                    "user_name"   => $user_data[$event->relateduserid]->username,
                    "first_name"  => $user_data[$event->relateduserid]->firstname,
                    "last_name"   => $user_data[$event->relateduserid]->lastname,
                    "email"       => $user_data[$event->relateduserid]->email
                )
            )
        );

        $api_handler = api_handler_instance();
        if (isset($CFG->eb_connection_settings)) {
            $sites = unserialize($CFG->eb_connection_settings);
            $synch_conditions = unserialize($CFG->eb_synch_settings);

            foreach ($sites as $key => $value) {
                if ($synch_conditions[$value["wp_name"]]["user_creation"]) {
                    $api_handler->connect_to_wp_with_args($value["wp_url"], $request_data);
                }
            }
        }

    }


    /**
     * functionality to handle user deletion event
     * @return [type]
     */
    public static function user_deleted(core\event\user_deleted $event)
    {
        global $CFG;
        $request_data = array(
            "action" => "user_deletion",
            "data" => serialize(
                array(
                    "user_id"     => $event->relateduserid
                )
            )
        );

        $api_handler = api_handler_instance();
        if (isset($CFG->eb_connection_settings)) {
            $sites = unserialize($CFG->eb_connection_settings);
            $synch_conditions = unserialize($CFG->eb_synch_settings);

            foreach ($sites as $key => $value) {
                if ($synch_conditions[$value["wp_name"]]["user_deletion"]) {
                    $api_handler->connect_to_wp_with_args($value["wp_url"], $request_data);
                }
            }
        }
    }
}

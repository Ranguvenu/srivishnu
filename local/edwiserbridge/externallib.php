<?php

/**
*
*/

require_once($CFG->libdir . "/externallib.php");
require_once(dirname(__FILE__).'/lib.php');

class local_edwiserbridge_external extends external_api
{
    public function __construct()
    {
    }


    /**
     * request to test connection
     * @param  [type] $wp_url   [description]
     * @param  [type] $wp_token [description]
     * @return [type]           [description]
     */
    public static function eb_test_connection($wp_url, $wp_token)
    {
        $params = self::validate_parameters(
            self::eb_test_connection_parameters(),
            array('wp_url' => $wp_url, "wp_token" => $wp_token)
        );

        $request_data = array(
            "action" => "test_connection",
            "data" => serialize(
                array(
                    "token" => $params["wp_token"]
                )
            )
        );


        $api_handler = api_handler_instance();
        $response = $api_handler->connect_to_wp_with_args($params["wp_url"], $request_data);
        if (!$response["error"]) {
            $status = $response["data"]->status;
            $msg = $response["data"]->msg;
        } else {
            $status = 0;
            $msg = $response["msg"];
        }

        return array("status"=> $status, "msg" => $msg);
    }

    public static function eb_test_connection_parameters()
    {
        return new external_function_parameters(
            array(
                'wp_url' => new external_value(PARAM_TEXT, get_string('web_service_wp_url', 'local_edwiserbridge')),
                'wp_token' => new external_value(PARAM_TEXT, get_string('web_service_wp_token', 'local_edwiserbridge'))
            )
        );
    }

    public static function eb_test_connection_returns()
    {
        return new external_single_structure(
            array(
                'status'  => new external_value(PARAM_TEXT, get_string('web_service_test_conn_status', 'local_edwiserbridge')),
                'msg'  => new external_value(PARAM_RAW, get_string('web_service_test_conn_msg', 'local_edwiserbridge'))
            )
        );
    }


    /**
     * functionality to get all site related data.
     * @param  [type] $site_index [description]
     * @return [type]             [description]
     */
    public static function eb_get_site_data($site_index)
    {
        $params = self::validate_parameters(
            self::eb_get_site_data_parameters(),
            array('site_index' => $site_index)
        );
        return get_synch_settings($params['site_index']);
    }

    public static function eb_get_site_data_parameters()
    {
        return new external_function_parameters(
            array(
                'site_index' => new external_value(PARAM_TEXT, get_string('web_service_site_index', 'local_edwiserbridge'))
            )
        );
    }

    public static function eb_get_site_data_returns()
    {
        return new external_single_structure(
            array(
                'course_enrollment'  => new external_value(PARAM_INT, get_string('web_service_course_enrollment', 'local_edwiserbridge')),
                'course_un_enrollment'  => new external_value(PARAM_INT,  get_string('web_service_course_un_enrollment', 'local_edwiserbridge')),
                'user_creation'  => new external_value(PARAM_INT,  get_string('web_service_user_creation', 'local_edwiserbridge')),
                'user_deletion'  => new external_value(PARAM_INT, get_string('web_service_user_deletion', 'local_edwiserbridge'))
            )
        );
    }


    /**
     * functionality to get course progress
     * @param  [type] $user_id
     * @return [type]          [description]
     */
    public static function eb_get_course_progress($user_id)
    {
        global $DB, $CFG;

        $params = self::validate_parameters(
            self::eb_get_course_progress_parameters(),
            array('user_id' => $user_id)
        );

        $result = $DB->get_records_sql("SELECT ctx.instanceid course, (count(cmc.completionstate) / count(cm.id) * 100) completed
        FROM {user} u
        LEFT JOIN {role_assignments} ra ON u.id = ra.userid and u.id = ?
        JOIN {context} ctx ON ra.contextid = ctx.id
        JOIN {course_modules} cm ON ctx.instanceid = cm.course AND cm.completion > 0
        LEFT JOIN {course_modules_completion} cmc ON cm.id = cmc.coursemoduleid AND u.id = cmc.userid AND cmc.completionstate > 0
        GROUP BY ctx.instanceid, u.id
        HAVING completed = 30 OR completed > 30
        ORDER BY u.id", array($params['user_id']));


        $enrolled_courses = get_array_of_enrolled_courses($params['user_id'], 1);
        $processed_courses = $enrolled_courses;


        $response = array();

        if ($result && !empty($result)) {
            foreach ($result as $key => $value) {
                // if ($params['user_id'] == $value->user) {
                $course = get_course($value->course);
                $cinfo = new completion_info($course);
                // $iscomplete = $cinfo->is_course_complete($USER->id);
                $iscomplete = $cinfo->is_course_complete($value->user);
                if ($iscomplete) {
                    array_push($response, array("course_id" => $value->course, "completion" => "100"));
                    $processed_courses = remove_processed_coures($value->course, $processed_courses);
                } else {
                    array_push($response, array("course_id" => $value->course, "completion" => $value->completed));
                    $processed_courses = remove_processed_coures($value->course, $processed_courses);
                }
                // }
            }
        } else {
            foreach ($enrolled_courses as $value) {
                $course = get_course($value);
                $cinfo = new completion_info($course);
                $iscomplete = $cinfo->is_course_complete($params['user_id']);

                if ($iscomplete) {
                    array_push($response, array("course_id" => $value, "completion" => "100"));
                    $processed_courses = remove_processed_coures($value, $processed_courses);
                } else {
                    array_push($response, array("course_id" => $value, "completion" => "0"));
                    $processed_courses = remove_processed_coures($value, $processed_courses);
                }
            }
        }


        if (!empty($processed_courses)) {
            foreach ($processed_courses as $value) {
                $course = get_course($value);
                $cinfo = new completion_info($course);
                $iscomplete = $cinfo->is_course_complete($params['user_id']);

                if ($iscomplete) {
                    array_push($response, array("course_id" => $value, "completion" => "100"));
                    $processed_courses = remove_processed_coures($value, $processed_courses);
                } else {
                    array_push($response, array("course_id" => $value, "completion" => "0"));
                    $processed_courses = remove_processed_coures($value, $processed_courses);
                }
            }
        }
        return $response;
    }

    public static function eb_get_course_progress_parameters()
    {
        return new external_function_parameters(
            array(
                'user_id' => new external_value(PARAM_TEXT, '')
            )
        );
    }

    public static function eb_get_course_progress_returns()
    {
        return new external_multiple_structure(
            new external_single_structure(
                array(
                    'course_id'   => new external_value(PARAM_TEXT, ''),
                    'completion'  => new external_value(PARAM_TEXT, '')
                )
            )
        );
    }




    /**
     * functionality to get users in chunk.
     * @param  [type] $user_id
     * @return [type]          [description]
     */
    public static function eb_get_users($offset, $limit, $search_string, $total_users)
    {
        global $DB;

        $params = self::validate_parameters(
            self::eb_get_users_parameters(),
            array('offset' => $offset, "limit" => $limit, "search_string" => $search_string, "total_users" => $total_users)
        );

        $query = "SELECT id, username, firstname, lastname, email FROM {user} WHERE  deleted = 0 AND confirmed = 1 ";

        if (!empty($params['search_string'])) {
            $search_string = "%" . $params['search_string'] . "%";
            $query .= " AND (firstname LIKE '$search_string' OR lastname LIKE '$search_string' OR username LIKE '$search_string')";
        }

        if (!empty($params['limit'])) {
            $query .= " LIMIT $limit";
        }

        if (!empty($params['offset'])) {
            $query .= " OFFSET $offset";
        }


        $users = $DB->get_records_sql($query);
        $users = json_decode(json_encode($users), true);


        $user_count = 0;
        if (!empty($params['total_users'])) {
            $user_count = $DB->get_record_sql("SELECT count(*) total_count FROM {user}");
            $user_count = $user_count->total_count;
        }

        return array("total_users" => $user_count, "users" => $users);
    }

    public static function eb_get_users_parameters()
    {
        return new external_function_parameters(
            array(
                'offset' => new external_value(PARAM_INT, get_string('web_service_offset', 'local_edwiserbridge')),
                'limit' => new external_value(PARAM_INT, get_string('web_service_limit', 'local_edwiserbridge')),
                'search_string' => new external_value(PARAM_TEXT, get_string('web_service_search_string', 'local_edwiserbridge')),
                'total_users' => new external_value(PARAM_INT, get_string('web_service_total_users', 'local_edwiserbridge')),
            )
        );
    }

    public static function eb_get_users_returns()
    {
        return new external_function_parameters(
            array(
                'total_users' => new external_value(PARAM_INT, ''),
                'users' => new external_multiple_structure(
                    new external_single_structure(
                        array(
                            'id'        => new external_value(PARAM_INT, get_string('web_service_id', 'local_edwiserbridge')),
                            'username'  => new external_value(PARAM_TEXT, get_string('web_service_username', 'local_edwiserbridge')),
                            'firstname' => new external_value(PARAM_TEXT, get_string('web_service_firstname', 'local_edwiserbridge')),
                            'lastname'  => new external_value(PARAM_TEXT, get_string('web_service_lastname', 'local_edwiserbridge')),
                            'email'     => new external_value(PARAM_TEXT, get_string('web_service_email', 'local_edwiserbridge'))
                        )
                    )
                )
            )
        );
    }
}

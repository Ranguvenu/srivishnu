<?php
defined('MOODLE_INTERNAL') || die;
require_once("$CFG->libdir/externallib.php");
require_once("$CFG->dirroot/user/lib.php");
use \core_calendar\local\event\container as event_container;
use \core_calendar\external\event_exporter;
use \core_calendar\external\events_related_objects_cache;
class local_users_external extends external_api {

		/**
     * Describes the parameters for submit_create_group_form webservice.
     * @return external_function_parameters
     */
    public static function submit_create_user_form_parameters() {
        return new external_function_parameters(
            array(
                'id' => new external_value(PARAM_INT, 'userid', 0),
                'contextid' => new external_value(PARAM_INT, 'The context id for the evaluation', false),
                'jsonformdata' => new external_value(PARAM_RAW, 'The data from the create group form, encoded as a json array', false),
                'form_status' => new external_value(PARAM_INT, 'Form position', 0),
                'employee' => new external_value(PARAM_INT, 'Form position', 0)

            )
        );
    }

    /**
     * [submit_create_user_form description]
     * @param  [integer] $contextid
     * @param  [string] $jsonformdata
     * @return void
     */
	public function submit_create_user_form($id, $contextid, $jsonformdata, $form_status,$employee){
		global $PAGE, $CFG;
		require_once($CFG->dirroot . '/local/users/lib.php');
        // We always must pass webservice params through validate_parameters.

		$context = context::instance_by_id($contextid, MUST_EXIST);
        // We always must call validate_context in a webservice.
		self::validate_context($context);
		$serialiseddata = json_decode($jsonformdata);

		$data = array();
        parse_str($serialiseddata, $data);
        $warnings = array();
		$mform = new local_users\forms\create_user(null, array('form_status' => $form_status,'employee'=>$employee,'id' => $data['id'],'org'=>$data['open_costcenterid'],'dept'=>$data['department']), 'post', '', null, true, $data);
		$validateddata = $mform->get_data();
        $userlib = new local_users\events\users();
        if($validateddata){
            if($validateddata->id > 0){

                $uid = $userlib->update_existinguser($validateddata);
            } else{
				$uid = $userlib->insert_newuser($validateddata);
			}
            $formheaders = array_keys($mform->formstatus);
            $next = $form_status + 1;
            $nextform = array_key_exists($next, $formheaders);
            if ($nextform !== false/*&& end($formheaders) !== $form_status*/) {
                $form_status = $next;
                $error = false;
            } else {
                $form_status = -1;
                $error = true;
            }
		} else {
			// Generate a warning.
            throw new moodle_exception('Error in creation');
		}
        $return = array(
            'id' => $uid,
            'form_status' => $form_status);
        return $return;
	}


	/**
     * Returns description of method result value.
     *
     * @return external_description
     * @since Moodle 3.0
     */
    public static function submit_create_user_form_returns() {
        return new external_single_structure(array(
            'id' => new external_value(PARAM_INT, 'Userid'),
            'form_status' => new external_value(PARAM_INT, 'form_status'),
        ));
    }

        /**
     * Describes the parameters for submit_profile_info_form webservice.
     * @return external_function_parameters
     */
    public static function submit_profile_info_form_parameters() {
        return new external_function_parameters(
            array(
                'id' => new external_value(PARAM_INT, 'userid', 0),
                'contextid' => new external_value(PARAM_INT, 'The context id for the evaluation', false),
                'jsonformdata' => new external_value(PARAM_RAW, 'The data from the create group form, encoded as a json array', false)
            )
        );
    }

    /**
     * [submit_profile_info_form description]
     * @param  [integer] $contextid
     * @param  [string] $jsonformdata
     * @return void
     */
    public function submit_profile_info_form($id, $contextid, $jsonformdata){
        global $PAGE, $CFG;
        require_once($CFG->dirroot . '/local/users/lib.php');
        // We always must pass webservice params through validate_parameters.

        $context = context::instance_by_id($contextid, MUST_EXIST);
        // We always must call validate_context in a webservice.
        self::validate_context($context);
        $serialiseddata = json_decode($jsonformdata);

        $data = array();
        parse_str($serialiseddata, $data);
        $warnings = array();
        $mform = new local_users\forms\profile_info(null, array('id' => $data['id']), 'post', '', null, true, $data);
        $validateddata = $mform->get_data();
        $userlib = new local_users\events\users();
        if($validateddata){
            if($validateddata->id > 0){
                $uid = $userlib->update_existinguserprofile($validateddata);
            }
            $error = false;
        } else {
            // Generate a warning.
            throw new moodle_exception('Error in creation');
        }
        $return = array(
            'id' => $uid);
        return $return;
    }


    /**
     * Returns description of method result value.
     *
     * @return external_description
     * @since Moodle 3.0
     */
    public static function submit_profile_info_form_returns() {
        return new external_single_structure(array(
            'id' => new external_value(PARAM_INT, 'Userid')
        ));
    }

    public function delete_user_parameters(){
        return new external_function_parameters(
            array(
                'id' => new external_value(PARAM_INT, 'userid', 0),
                'contextid' => new external_value(PARAM_INT, 'The context id for the evaluation', false)
            )
        );
    }
    public function delete_user($id,$contextid){
        global $DB;

        $user = $DB->get_record('user', array('id' => $id));
        if($user){
            $new_empid = $user->open_employeeid.'_'.time();
            $DB->execute("UPDATE {user} SET `open_employeeid`=:employeeid WHERE id=:id AND username= :username AND email = :email", array('employeeid' => $new_empid, 'id'=>$user->id, 'username' => $user->username, 'email' => $user->email));
            user_delete_user($user);
            $return = TRUE;
        }else {
            throw new moodle_exception('Error in deletion');
            $return = FALSE;
        }
        return $return;
    }
    public function delete_user_returns(){
        return new external_value(PARAM_BOOL, 'return');
    }
    public function suspend_local_user_parameters(){
        return new external_function_parameters(
            array(
                'id' => new external_value(PARAM_INT, 'userid', 0),
                'contextid' => new external_value(PARAM_INT, 'The context id for the evaluation', false)
            )
        );
    }
    public function suspend_local_user($id,$contextid){
        global $DB;

        $user = $DB->get_record('user', array('id' => $id));
        if($user){
            if($user->suspended){
                $status = 0;
            }else{
                $status = 1;
            }
            $DB->execute('UPDATE {user} SET `suspended` = :status WHERE id = :id', array('id' => $user->id, 'status' => $status));
            $return = TRUE;
        }else {
            throw new moodle_exception('Error in inactivating');
            $return = FALSE;
        }
        return $return;
    }
    public function suspend_local_user_returns(){
        return new external_value(PARAM_BOOL, 'return');
    }


    /* get college admin users - start */
     /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function get_coladmins_parameters() {
        return new external_function_parameters(
                array()
        );
    }

    /**
     * Returns college admin users
     */
    public static function get_coladmins() {
        global $USER, $DB, $CFG;

        //Parameter validation
        $params = self::validate_parameters(self::get_coladmins_parameters(), array());

        $roleid = $DB->get_field('role', 'id', array('shortname' => 'college_head'));
        $sql = "SELECT * from {user} WHERE open_role = {$roleid}";
        $coladmin_users = $DB->get_records_sql($sql);

        return $coladmin_users;
    }

    /**
     * Create user return value description.
     *
     * @param array $additionalfields some additional field
     * @return single_structure_description
     */
    public static function coladmins_description($additionalfields = array()) {
        $userfields = array(
            'id'    => new external_value(core_user::get_property_type('id'), 'ID of the user'),
            'username'    => new external_value(core_user::get_property_type('username'), 'The username'),
            'firstname'   => new external_value(core_user::get_property_type('firstname'), 'The first name(s) of the user'),
            'lastname'    => new external_value(core_user::get_property_type('lastname'), 'The family name of the user'),
            'email'       => new external_value(core_user::get_property_type('email'), 'An email address - allow email as root@localhost'),
            'open_departmentid' => new external_value(PARAM_INT, 'University of Program')
            );
        if (!empty($additionalfields)) {
            $userfields = array_merge($userfields, $additionalfields);
        }
        return new external_single_structure($userfields);
    }

    /**
     * Returns description of method result value
     *
     * @return external_multiple_structure
     * @since Moodle 2.4
     */
    public static function get_coladmins_returns() {
        return new external_multiple_structure(self::coladmins_description());
    }

    /* get college admin users - start */
     /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function get_univadmins_parameters() {
        return new external_function_parameters(
                array()
        );
    }

    /**
     * Returns college admin users
     */
    public static function get_univadmins() {
        global $USER, $DB, $CFG;

        //Parameter validation
        $params = self::validate_parameters(self::get_univadmins_parameters(), array());

        $roleid = $DB->get_field('role', 'id', array('shortname' => 'university_head'));
        $sql = "SELECT * from {user} WHERE open_role = {$roleid}";
        $coladmin_users = $DB->get_records_sql($sql);

        return $coladmin_users;
    }

    /**
     * Create user return value description.
     *
     * @param array $additionalfields some additional field
     * @return single_structure_description
     */
    public static function univadmins_description($additionalfields = array()) {
        $userfields = array(
            'id'    => new external_value(core_user::get_property_type('id'), 'ID of the user'),
            'username'    => new external_value(core_user::get_property_type('username'), 'The username'),
            'firstname'   => new external_value(core_user::get_property_type('firstname'), 'The first name(s) of the user'),
            'lastname'    => new external_value(core_user::get_property_type('lastname'), 'The family name of the user'),
            'email'       => new external_value(core_user::get_property_type('email'), 'An email address - allow email as root@localhost'),
            'open_costcenterid' => new external_value(PARAM_INT, 'University of Program')
            );
        if (!empty($additionalfields)) {
            $userfields = array_merge($userfields, $additionalfields);
        }
        return new external_single_structure($userfields);
    }

    /**
     * Returns description of method result value
     *
     * @return external_multiple_structure
     * @since Moodle 2.4
     */
    public static function get_univadmins_returns() {
        return new external_multiple_structure(self::univadmins_description());
    }

     //added by sarath
    /**
     * Returns description of method parameters.
     *
     * @return external_function_parameters.
     */
    public static function profilemoduledata_parameters() {
        return new external_function_parameters([
                'options' => new external_value(PARAM_RAW, 'options'),
                'dataoptions' => new external_value(PARAM_RAW, 'The data for the service'),
                'offset' => new external_value(PARAM_INT, 'Number of items to skip from the begging of the result set',
                    VALUE_DEFAULT, 0),
                'limit' => new external_value(PARAM_INT, 'Maximum number of results to return',
                    VALUE_DEFAULT, 0),
                'contextid' => new external_value(PARAM_INT, 'contextid'),
                'filterdata' => new external_value(PARAM_RAW, 'The data for the service'),
            ]);
    }

    /**
     * Gets the list of users based on the login user
     *
     * @param int $options need to give options targetid,viewtype,perpage,cardclass
     * @param int $dataoptions need to give data which you need to get records
     * @param int $limit Maximum number of results to return
     * @param int $offset Number of items to skip from the beginning of the result set.
     * @param int $filterdata need to pass filterdata.
     * @return array The list of users and total users count.
     */
    public static function profilemoduledata(
        $options,
        $dataoptions,
        $offset = 0,
        $limit = 0,
        $contextid,
        $filterdata
    ) {
        global $OUTPUT, $CFG, $DB,$USER,$PAGE;
        require_once($CFG->dirroot . '/local/users/lib.php');
        require_login();
        $PAGE->set_url('/local/users/profile.php', array());
        $PAGE->set_context($contextid);
        // Parameter validation.
        $params = self::validate_parameters(
            self::profilemoduledata_parameters(),
            [
                'options' => $options,
                'dataoptions' => $dataoptions,
                'offset' => $offset,
                'limit' => $limit,
                'contextid' => $contextid,
                'filterdata' => $filterdata
            ]
        );
        $offset = $params['offset'];
        $limit = $params['limit'];
        $decodedata = json_decode($params['dataoptions']);

        $userclass = '\local_'.$decodedata->moduletype.'\local\user';
        if(class_exists($userclass)){
            $pluginclass = new $userclass;
            if(method_exists($userclass, 'user_profile_content')){
                $data = $pluginclass->user_profile_content($decodedata->userid,false,$offset,$limit);
            }
        }

        return [
            'totalcount' => $data->count,
            'records' =>$data->navdata,
            'options' => $options,
            'dataoptions' => $dataoptions,
            'filterdata' => $filterdata,
        ];

    }

    /**
     * Returns description of method result value.
     */
    public static function  profilemoduledata_returns() {

        return new external_single_structure([
            'options' => new external_value(PARAM_RAW, 'The paging data for the service'),
            'dataoptions' => new external_value(PARAM_RAW, 'The data for the service'),
            'totalcount' => new external_value(PARAM_INT, 'total number of count in result set'),
            'filterdata' => new external_value(PARAM_RAW, 'The data for the service'),
            'records' => new external_multiple_structure(
                            new external_single_structure(
                                array(
                                    'id' => new external_value(PARAM_INT, 'id of module'),
                                    'name' => new external_value(PARAM_RAW, 'name of module'),

                                    'description' => new external_value(PARAM_RAW, 'description'),
                                    'url' => new external_value(PARAM_RAW, 'url'),
                                    'status' => new external_value(PARAM_RAW, 'status'),
                                    'percentage' => new external_value(PARAM_RAW, 'percentage'),
                                    'startdate' => new external_value(PARAM_RAW, 'startdate'),
                                    'enddate' => new external_value(PARAM_RAW, 'enddate'),
                                    'validtill' => new external_value(PARAM_RAW, 'validtill')
                                )
                            )
                        )
        ]);
    }
    public static function dashboard_stats_parameters() {
        return new external_function_parameters(
             array(
                'userid' => new external_value(PARAM_INT, 'UserID'),
                'module' => new external_value(PARAM_RAW, 'Module')
                )
        );
    }
    public static function dashboard_stats($userid,$module) {
        global $USER, $DB;
        $stats = array();
        $data = array();
        switch ($module) {
        case 'courses':
            $completed = 0;
            $inprogress = 0;
            $enrolled = 0;
            break;
        // case 'classrooms':
        //     $completed = userdashboard_content::completed_classrooms_count();
        //     $inprogress = userdashboard_content::inprogress_classrooms_count();
        //     $enrolled = userdashboard_content::gettotal_classrooms();
        //     break;
        // case 'programs':
        //     $completed = block_userdashboard\lib\programs::completed_programs('');
        //     $inprogress = block_userdashboard\lib\programs::inprogress_programs('');
        //     $enrolled = count($inprogress)+count($completed);
        //     $stats['completed']       = count($completed);
        //     $stats['inprogress']      = count($inprogress);
        //     $stats['enrolled']        = $enrolled;
        //     break;
        default: break;
    }
        $stats['completed']       = $completed;
        $stats['inprogress']      = $inprogress;
        $stats['enrolled']        = $enrolled;
        return array('stats' => $stats);
    }
     public static function dashboard_stats_returns() {
        return new external_single_structure(
            array(
                'stats' => new external_single_structure(
                    array(
                        'completed'=> new external_value(PARAM_INT, 'Count of completed courses'),
                        'inprogress'=> new external_value(PARAM_RAW, 'Count of inprogress courses'),
                        'enrolled' => new external_value(PARAM_RAW, 'Count of enrolled courses'),
                    )
                )
            )
        );
    }

    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     * @since Moodle 2.5
     */
    public static function pending_activities_parameters() {
        return new external_function_parameters(
            array('events' => new external_single_structure(
                array(
                    'eventids' => new external_multiple_structure(
                        new external_value(PARAM_INT, 'event ids') , 'List of event ids', VALUE_DEFAULT, array()),
                    ), 'Event details', VALUE_DEFAULT, array()),
                'options' => new external_single_structure(
                    array(
                        'userevents' => new external_value(PARAM_BOOL, "Set to true to return current user's user events", VALUE_DEFAULT, true, NULL_ALLOWED),
                        'timestart' => new external_value(PARAM_INT, "Time from which events should be returned", VALUE_DEFAULT, 0, NULL_ALLOWED),
                        'timeend' => new external_value(PARAM_INT, "Time to which the events should be returned. We treat 0 and null as no end", VALUE_DEFAULT, 0, NULL_ALLOWED),
                        'ignorehidden' => new external_value(PARAM_BOOL, "Ignore hidden events or not", VALUE_DEFAULT, true, NULL_ALLOWED),
                    ), 'Options', VALUE_DEFAULT, array()
                )
            )
        );
    }

    /**
     * Get Calendar events
     *
     * @param array $events A list of events
     * @param array $options various options
     * @return array Array of event details
     * @since Moodle 2.5
     */
    public static function pending_activities($events = array(), $options = array()) {
        global $SITE, $DB, $USER, $CFG, $PAGE;
        require_once($CFG->dirroot."/calendar/lib.php");

        // Parameter validation.
        $params = self::validate_parameters(self::pending_activities_parameters(), array('events' => $events, 'options' => $options));
        $funcparam = array('courses' => array());
        $hassystemcap = has_capability('moodle/calendar:manageentries', context_system::instance());
        $warnings = array();

        // Let us find out courses and their categories that we can return events from.
        // $courses = $params['events']['courseids'];
        // $funcparam['courses'] = $courses;
        $mycourses = \local_courses\local\user::enrol_get_users_courses($USER->id, false, false);
        $mycourseids = array_keys($mycourses['data']);
        $funcparam['courses'] = $courses = $mycourseids;

        // Do we need user events?
        if (!empty($params['options']['userevents'])) {
            $funcparam['users'] = array($USER->id);
        } else {
            $funcparam['users'] = false;
        }

        // We treat 0 and null as no end.
        if (empty($params['options']['timeend'])) {
            $params['options']['timeend'] = PHP_INT_MAX;
        }
        // $params['options']['timestart'] = time();
        // $params['options']['timeend'] = strtotime('+1 day');
        // Event list does not check visibility and permissions, we'll check that later.
        $eventlist = calendar_get_legacy_events($params['options']['timestart'], $params['options']['timeend'],
                $USER->id, array(), $funcparam['courses'], true,
                true, array());

        // WS expects arrays.
        $events = array();

        // We need to get events asked for eventids.
        if ($eventsbyid = calendar_get_events_by_id($params['events']['eventids'])) {
            $eventlist += $eventsbyid;
        }

        foreach ($eventlist as $eventid => $eventobj) {
            $event = (array) $eventobj;
            // Description formatting.
            $calendareventobj = new calendar_event($event);
            list($event['description'], $event['format']) = $calendareventobj->format_external_text();
            $legacyevent = calendar_event::load($eventid);
            // Must check we can see this event.
            if (!calendar_view_event_allowed($legacyevent)) {
                // We can't return a warning in this case because the event is not optional.
                // We don't know the context for the event and it's not worth loading it.
                $syscontext = context_system::instance();
                throw new \required_capability_exception($syscontext, 'moodle/course:view', 'nopermission', '');
            }

            $legacyevent->count_repeats();

            $eventmapper = event_container::get_event_mapper();
            $event1 = $eventmapper->from_legacy_event_to_event($legacyevent);

            $cache = new events_related_objects_cache([$event1]);
            $relatedobjects = [
                'context' => $cache->get_context($event1),
                'course' => $cache->get_course($event1),
            ];

            $exporter = new event_exporter($event1, $relatedobjects);

            $renderer = $PAGE->get_renderer('core_calendar');

            $eventdata = $exporter->export($renderer);
            // User can see everything, no further check is needed.
            $events[$eventid] = $event;
            $events[$eventid]['eventdata'] = $eventdata;
            $events[$eventid]['activity'] = $DB->get_field($event['modulename'], 'name', array('id' => $event['instance']));

        }
        return array('pendingactivities' => $events, 'warnings' => $warnings);
    }

    /**
     * Returns description of method result value
     *
     * @return external_description
     * @since Moodle 2.5
     */
    public static function  pending_activities_returns() {
        return new external_single_structure(array(
                'pendingactivities' => new external_multiple_structure( new external_single_structure(
                        array(
                            'id' => new external_value(PARAM_INT, 'event id'),
                            'name' => new external_value(PARAM_TEXT, 'event name'),
                            'activity' => new external_value(PARAM_TEXT, 'event name'),
                            'description' => new external_value(PARAM_RAW, 'Description', VALUE_OPTIONAL, null, NULL_ALLOWED),
                            'format' => new external_format_value('description'),
                            'courseid' => new external_value(PARAM_INT, 'course id'),
                            'repeatid' => new external_value(PARAM_INT, 'repeat id'),
                            'modulename' => new external_value(PARAM_TEXT, 'module name', VALUE_OPTIONAL, null, NULL_ALLOWED),
                            'instance' => new external_value(PARAM_INT, 'instance id'),
                            'eventtype' => new external_value(PARAM_TEXT, 'Event type'),
                            'timestart' => new external_value(PARAM_INT, 'timestart'),
                            'timeduration' => new external_value(PARAM_INT, 'time duration'),
                            'visible' => new external_value(PARAM_INT, 'visible'),
                            'uuid' => new external_value(PARAM_TEXT, 'unique id of ical events', VALUE_OPTIONAL, null, NULL_NOT_ALLOWED),
                            'sequence' => new external_value(PARAM_INT, 'sequence'),
                            'timemodified' => new external_value(PARAM_INT, 'time modified'),
                            'eventdata' => event_exporter::get_read_structure()
                        ), 'event')
                 ),
                 'warnings' => new external_warnings()
                )
        );
    }
}

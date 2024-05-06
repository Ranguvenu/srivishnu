<?php
defined('MOODLE_INTERNAL') || die;
require_once("$CFG->libdir/externallib.php");
require_once($CFG->dirroot.'/local/faculties/lib.php');
class local_faculties_external extends external_api {

        /**
     * Describes the parameters for submit_create_group_form webservice.
     * @return external_function_parameters
     */
    public static function submit_facultyform_data_parameters() {
        return new external_function_parameters(
            array(
                'contextid' => new external_value(PARAM_INT, 'The context id for the evaluation'),
                'jsonformdata' => new external_value(PARAM_RAW, 'The data from the create group form, encoded as a json array'),
            
            )
        );
    }

    /**
     * form submission of school name and returns instance of this object
     *
     * @param int $contextid 
     * @param [string] $jsonformdata 
     * @return school form submits
     */
    public function submit_facultyform_data($contextid, $jsonformdata){
        global $PAGE, $CFG;

        require_once($CFG->dirroot . '/local/faculties/lib.php');
        // We always must pass webservice params through validate_parameters.
        $params = self::validate_parameters(self::submit_facultyform_data_parameters(),
                                    ['contextid' => $contextid, 'jsonformdata' => $jsonformdata]);
        $context = context_system::instance();
        // We always must call validate_context in a webservice.
        self::validate_context($context);
        $serialiseddata = json_decode($params['jsonformdata']);

        $data = array();
        parse_str($serialiseddata, $data);
        $warnings = array();
        $mform = new local_faculties\form\createfaculty_form(null, array(), 'post', '', null, true, $data);
        
        $valdata = $mform->get_data();
        // print_object($valdata);
        if($valdata){
            if($valdata->id>0){
                $updateclass = new local_faculties\events\update();
                $success = $updateclass->faculty_update_instance($valdata);
            } else{
                $insertclass = new local_faculties\events\insert();
                $success = $insertclass->faculty_insert_instance($valdata);
            }
        } else {
            // Generate a warning.
            throw new moodle_exception('Error in creation');
        }
        return $success;
    }

    public static function submit_facultyform_data_returns() {
        return new external_value(PARAM_INT, 'school id');
    }

    public static function faculty_status_confirm_parameters() {
        return new external_function_parameters(
            array(
                'action' => new external_value(PARAM_ACTION, 'Action of the event', false),
                'id' => new external_value(PARAM_INT, 'ID of the record', 0),
                'confirm' => new external_value(PARAM_INT, 'confirm',true),
                'actionstatus' => new external_value(PARAM_RAW, 'actionstatus', false),
                'actionstatusmsg' => new external_value(PARAM_RAW, 'actionstatusmsg', false),
            )
        );
    }
    /**
     * [school_status_confirm description]
     * @param  [type] $action  [description]
     * @param  [int] $id      [id of the school]
     * @param  [int] $confirm [confirmation key]
     * @return [boolean]          [true if success]
     */
    public static function faculty_status_confirm($action, $id, $confirm) {
        global $DB;
    
            if ($id) {
                $visible=$DB->get_field('local_faculties','visible',array('id'=>$id));
                if($visible==1){
                    $visible=0;
                }else{
                    $visible=1;
                }
                $sql = "UPDATE {local_faculties}
                   SET visible =$visible
                 WHERE id=$id";
                
                $DB->execute($sql);
                $return = true;
            } else {
                $return = false;
            }
        
        return $return;
    }
    /**
     * [school_status_confirm_returns description]
     * @return [external value] [boolean]
     */
    public static function faculty_status_confirm_returns() {
        return new external_value(PARAM_BOOL, 'return');
    }
    /**
     * [school_delete_school_parameters description]
     * @return [external value] [params for deleting school]
     */
    public static function delete_faculty_parameters(){
        return new external_function_parameters(
            array(
                'id' => new external_value(PARAM_INT, 'facultyid', 0)
                )
        );
    }
    /**
     * [school_delete_school description]
     * @param  [int] $id id of school to be deleted 
     * @return [boolean]     [true for success]
     */
    public static function delete_faculty($id){
        global $DB;
        if($id){
            $facultydelete = $DB->delete_records('local_faculties', array('id' => $id));
            return true;
        }else {
            throw new moodle_exception('Error in deleting');
            return false;
        }
    }
    /**
     * [school_delete_school_returns description]
     * @return [external value] [boolean]
     */
    public static function delete_faculty_returns() {
        return new external_value(PARAM_BOOL, 'return');
    }

    public static function global_filters_form_option_selector_parameters() {
        $query = new external_value(
            PARAM_RAW,
            'Query string'
        );
        $action = new external_value(
            PARAM_RAW,
            'Action for the classroom form selector'
        );
        $options = new external_value(
            PARAM_RAW,
            'Action for the classroom form selector'
        );
        $searchanywhere = new external_value(
            PARAM_BOOL,
            'find a match anywhere, or only at the beginning'
        );
        $page = new external_value(
            PARAM_INT,
            'Page number'
        );
        $perpage = new external_value(
            PARAM_INT,
            'Number per page'
        );
        return new external_function_parameters(array(
            'query' => $query,
            'action' => $action,
            'options' => $options,
            'searchanywhere' => $searchanywhere,
            'page' => $page,
            'perpage' => $perpage,

        ));
    }

    /**
     * Creates filter elements
     *
     * @param string $query
     * @param int $action
     * @param array $options
     * @param string $searchanywhere
     * @param int $page 
     * @param int $perpage
     * @param string $jsonformdata The data from the form, encoded as a json array.
     * @return string filter form element
     */
    public static function global_filters_form_option_selector($query, $action, $options, $searchanywhere, $page, $perpage) {
        global $CFG, $DB, $USER;
        $params = self::validate_parameters(self::global_filters_form_option_selector_parameters(), array(
            'query' => $query,
            'action' => $action,
            'options' => $options,
            'searchanywhere' => $searchanywhere,
            'page' => $page,
            'perpage' => $perpage
        ));
        $query = $params['query'];
        $action = $params['action'];
        $options = $params['options'];
        $searchanywhere=$params['searchanywhere'];
        $page=$params['page'];
        $perpage=$params['perpage'];

        if (!empty($options)) {
            $formoptions = json_decode($options);
        }
        if ($action) {
            $return = array();
            if($action === 'departments'){
                $filter = 'school';
            } 
            // else if($action === 'email' || $action === 'employeeid' || $action === 'username' || $action === 'users'){
            //     $filter = 'users';
            // } else if($action === 'organizations' || $action === 'departments'){
            //     $filter = 'costcenter';
            // } else{
            //     $filter = $action;
            // }
            $core_component = new core_component();
            $courses_plugin_exist = $core_component::get_plugin_directory('local', $filter);
            if ($courses_plugin_exist) {
                require_once($CFG->dirroot . '/local/' . $filter . '/lib.php');
                // $functionname = $action.'_filter';
                $functionname = 'departments_filter';
                $return = $functionname('',$query,$searchanywhere, $page, $perpage);
            }
            return json_encode($return);
        }
    }

    /**
     * Returns description of method result value
     *
     * @return external_description
     */
    public static function global_filters_form_option_selector_returns() {
        return new external_value(PARAM_RAW, 'data');
    }
    
    
    /* get faculties - start */   
     /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function get_faculties_parameters() {
        return new external_function_parameters(
                array(
//if I had any parameters, they would be described here. But I don't have any, so this array is empty.
                )
        );
    }

    /**
     * Returns Competency programid, fullname, shortname, description, associated Moodle course category and visibility
     * @return INT universityid
     * @return TEXT fullname
     * @return TEXT shortname
     * @return TEXT description
     * @return INT visible
     */
    public static function get_faculties() {
        global $USER, $DB, $CFG;

        //Parameter validation
        $params = self::validate_parameters(self::get_faculties_parameters(), array());

        $sql = 'SELECT * FROM {local_faculties}';
        $faculties = $DB->get_records_sql($sql);

        $facultiesinfo = array();
        foreach ($faculties as $faculty) {

                $facultyinfo = array();
                $facultyinfo['facultyid'] = $faculty->id;
                $facultyinfo['facultyname'] = $faculty->facultyname;
                $facultyinfo['facultycode'] = $faculty->facultycode;
                $facultyinfo['universityid'] = $faculty->university;
                $facultyinfo['description'] = $faculty->description;
                
                $facultiesinfo[] = $facultyinfo;
            }
        
       return $facultiesinfo;
    }

    /**
     * Returns description of method result value
     * @return external_description
     */
    public static function get_faculties_returns() {
        return new external_multiple_structure(
                new external_single_structure(
                array(
            'facultyid' => new external_value(PARAM_INT, 'University ID'),
            'facultyname' => new external_value(PARAM_TEXT, 'Fullname of University'),
            'facultycode' => new external_value(PARAM_TEXT, 'Shortname of University'),
            'universityid' => new external_value(PARAM_INT, 'University ID'),
            'description' => new external_value(PARAM_RAW, 'Description about University')
                )
             )
        );
    }

/* get faculties - ends */
}
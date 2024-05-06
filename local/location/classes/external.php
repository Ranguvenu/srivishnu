
<?php
defined('MOODLE_INTERNAL') || die;
require_once("$CFG->libdir/externallib.php");
class local_location_external extends external_api {

        /**
     * Describes the parameters for submit_create_group_form webservice.
     * @return external_function_parameters
     */
    public static function submit_instituteform_form_parameters() {
        return new external_function_parameters(
            array(
                'contextid' => new external_value(PARAM_INT, 'The context id for the evaluation'),
                'jsonformdata' => new external_value(PARAM_RAW, 'The data from the create group form, encoded as a json array'),

            )
        );
    }

    /**
     * form submission of institute name and returns instance of this object
     *
     * @param int $contextid
     * @param [string] $jsonformdata
     * @return institute form submits
     */
    public function submit_instituteform_form($contextid, $jsonformdata){
        global $PAGE, $CFG;

        require_once($CFG->dirroot . '/local/location/lib.php');
        // We always must pass webservice params through validate_parameters.
        $params = self::validate_parameters(self::submit_instituteform_form_parameters(),
                                    ['contextid' => $contextid, 'jsonformdata' => $jsonformdata]);
        // $context = $params['contextid'];
        $context = context_system::instance();
        // We always must call validate_context in a webservice.
        self::validate_context($context);
        $serialiseddata = json_decode($params['jsonformdata']);
        // throw new moodle_exception('Error in creation');
        // die;
        $data = array();

        parse_str($serialiseddata, $data);
        $warnings = array();
         $mform = new local_location\form\instituteform(null, array(), 'post', '', null, true, $data);
        $institutes  = new local_location\event\location();
        $valdata = $mform->get_data();
        // print_object($valdata);exit;
        if($valdata){
            if($valdata->id>0){

                $institutes->institute_update_instance($valdata);
            } else{

                $institutes->institute_insert_instance($valdata);
            }
        } else {
            // Generate a warning.
            throw new moodle_exception('Error in creation');
        }
    }


    /**
     * Returns description of method result value.
     *
     * @return external_description
     * @since Moodle 3.0
     */
    public static function submit_instituteform_form_returns() {
        return new external_value(PARAM_INT, 'institute id');
    }

public static function submit_roomform_form_parameters() {
        return new external_function_parameters(
            array(
                'contextid' => new external_value(PARAM_INT, 'The context id for the evaluation'),
                'jsonformdata' => new external_value(PARAM_RAW, 'The data from the create group form, encoded as a json array'),

            )
        );
    }

    /**
     * form submission of institute name and returns instance of this object
     *
     * @param int $contextid
     * @param [string] $jsonformdata
     * @return institute form submits
     */
    public function submit_roomform_form($contextid, $jsonformdata){
        global $PAGE, $CFG;

        require_once($CFG->dirroot . '/local/location/lib.php');
        // We always must pass webservice params through validate_parameters.
        $params = self::validate_parameters(self::submit_roomform_form_parameters(),
                                    ['contextid' => $contextid, 'jsonformdata' => $jsonformdata]);
        // $context = $params['contextid'];
        $context = context_system::instance();
        // We always must call validate_context in a webservice.
        self::validate_context($context);
        $serialiseddata = json_decode($params['jsonformdata']);
        // throw new moodle_exception('Error in creation');
        // die;
        $data = array();

        parse_str($serialiseddata, $data);
        $warnings = array();
         $mform = new local_location\form\roomform(null, array(), 'post', '', null, true, $data);
        $rooms  = new local_location\event\location();
        $valdata = $mform->get_data();

        if($valdata){
            if($valdata->id>0){
                $rooms->room_update_instance($valdata);
            } else{
                $rooms->room_insert_instance($valdata);
            }
        } else {
            // Generate a warning.
            throw new moodle_exception('Error in creation');
        }
    }


    /**
     * Returns description of method result value.
     *
     * @return external_description
     * @since Moodle 3.0
     */
    public static function submit_roomform_form_returns() {
        return new external_value(PARAM_INT, 'room id');
    }

    /**
     * [school_delete_school_parameters description]
     * @return [external value] [params for deleting school]
     */
    public static function delete_location_parameters(){
        return new external_function_parameters(
            array(
                'id' => new external_value(PARAM_INT, 'boardid', 0)
                )
        );
    }
    /**
     * [school_delete_school description]
     * @param  [int] $id id of school to be deleted 
     * @return [boolean]     [true for success]
     */
    public static function delete_location($id){
        global $DB;
        if($id){
            $locationdelete = $DB->delete_records('local_location_institutes', array('id' => $id));
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
    public static function delete_location_returns() {
        return new external_value(PARAM_BOOL, 'return');
    }

    /**
     * [school_delete_school_parameters description]
     * @return [external value] [params for deleting school]
     */
    public static function delete_room_parameters(){
        return new external_function_parameters(
            array(
                'id' => new external_value(PARAM_INT, 'boardid', 0)
                )
        );
    }
    /**
     * [school_delete_school description]
     * @param  [int] $id id of school to be deleted 
     * @return [boolean]     [true for success]
     */
    public static function delete_room($id){
        global $DB;
        if($id){
            $roomdelete = $DB->delete_records('local_location_room', array('id' => $id));
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
    public static function delete_room_returns() {
        return new external_value(PARAM_BOOL, 'return');
    }
}

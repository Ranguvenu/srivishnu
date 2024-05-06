
<?php
defined('MOODLE_INTERNAL') || die;
require_once("$CFG->libdir/externallib.php");
class local_costcenter_external extends external_api {

		/**
     * Describes the parameters for submit_create_group_form webservice.
     * @return external_function_parameters
     */
    public static function submit_costcenterform_form_parameters() {
        return new external_function_parameters(
            array(
                'contextid' => new external_value(PARAM_INT, 'The context id for the evaluation'),
                'jsonformdata' => new external_value(PARAM_RAW, 'The data from the create group form, encoded as a json array'),
            )
        );
    }

    /**
     * form submission of costcenter name and returns instance of this object
     *
     * @param int $contextid 
     * @param [string] $jsonformdata 
     * @return costcenter form submits
     */
	public function submit_costcenterform_form($contextid, $jsonformdata){
		global $PAGE, $CFG, $DB;

		require_once($CFG->dirroot . '/local/costcenter/lib.php');
        // We always must pass webservice params through validate_parameters.
		$params = self::validate_parameters(self::submit_costcenterform_form_parameters(),
                                    ['contextid' => $contextid, 'jsonformdata' => $jsonformdata]);
		$context = context_system::instance();
        // We always must call validate_context in a webservice.
		self::validate_context($context);
		$serialiseddata = json_decode($params['jsonformdata']);

		$data = array();
       
        parse_str($serialiseddata, $data);
        $warnings = array();
		 $mform = new local_costcenter\form\costcenterform(null, array(), 'post', '', null, true, $data);
		
        $valdata = $mform->get_data();
        if($valdata){
            if($valdata->id>0){
                $dept_status = $DB->get_field('local_costcenter','univ_dept_status',array('id' => $valdata->id));
             
                if($dept_status == 1 && $valdata->deptstatus == 1)
                    $valdata->univ_dept_status = 0;
                else if($dept_status == 0 && $valdata->deptstatus == 1)
                    $valdata->univ_dept_status = 1;

                $costcenterupdate = costcenter_edit_instance($valdata->id, $valdata);


                $catid = $DB->get_field('local_costcenter','category',array('id' => $valdata->id));

                if($costcenterupdate){  
                       $univ_dept_status = $DB->get_field('local_costcenter','univ_dept_status',array('id' => $univ_dept_status)); 
                       

                    }   
            } else{
				$costcenterinsert = costcenter_insert_instance($valdata);
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
    public static function submit_costcenterform_form_returns() {
        return new external_value(PARAM_INT, 'costcenter id');
    }
    /**
     * [costcenter_status_confirm_parameters description]
     * @return [external function param] [parameters for the costcenter status update]
     */
	public static function costcenter_status_confirm_parameters() {
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
	 * [costcenter_status_confirm description]
	 * @param  [type] $action  [description]
	 * @param  [int] $id      [id of the costcenter]
	 * @param  [int] $confirm [confirmation key]
	 * @return [boolean]          [true if success]
	 */
	public static function costcenter_status_confirm($action, $id, $confirm) {
		global $DB;
	
			if ($id) {
				$visible=$DB->get_field('local_costcenter','visible',array('id'=>$id));
				if($visible==1){
					$visible=0;
				}else{
					$visible=1;
				}
				$sql = "UPDATE {local_costcenter}
                   SET visible =$visible
                 WHERE id=$id";
				
				 $updatedid = $DB->execute($sql);
                 //Added by Yamini for updating status of visible when the university department visible updated.
				$return = true;
			} else {
				$return = false;
			}
		
		return $return;
	}
	/**
	 * [costcenter_status_confirm_returns description]
	 * @return [external value] [boolean]
	 */
	public static function costcenter_status_confirm_returns() {
		return new external_value(PARAM_BOOL, 'return');
	}
	/**
	 * [costcenter_delete_costcenter_parameters description]
	 * @return [external value] [params for deleting costcenter]
	 */
	public static function costcenter_delete_costcenter_parameters(){
		return new external_function_parameters(
            array(
                'id' => new external_value(PARAM_INT, 'userid', 0)
           		)
        );
	}
	/**
	 * [costcenter_delete_costcenter description]
	 * @param  [int] $id id of costcenter to be deleted 
	 * @return [boolean]     [true for success]
	 */
	public static function costcenter_delete_costcenter($id){
		global $DB;
		if($id){
            global $DB;
            $sql = 'SELECT category FROM {local_costcenter} WHERE id = '.$id;
            $categoryid = $DB->get_record_sql($sql);
			$costcenterdelete = $DB->delete_records('local_costcenter', array('id' => $id));
        	$costcenterdelete .= $DB->delete_records('local_costcenter_permissions', array('costcenterid' => $id));
            if($costcenterdelete){
                global $DB; 
                $categorydelete = $DB->execute('DELETE FROM {course_categories} WHERE id = '.$categoryid->category);
            }
			return true;
		}else {
			throw new moodle_exception('Error in deleting');
			return false;
		}
	}
	/**
	 * [costcenter_delete_costcenter_returns description]
	 * @return [external value] [boolean]
	 */
	public static function costcenter_delete_costcenter_returns() {
		return new external_value(PARAM_BOOL, 'return');
	}

 /* get universities - start */   
     /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function costcenter_get_universities_parameters() {
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
    public static function costcenter_get_universities() {
        global $USER, $DB, $CFG;

        //Parameter validation
        $params = self::validate_parameters(self::costcenter_get_universities_parameters(), array());

        $sql = 'SELECT * FROM {local_costcenter}';
        $universities = $DB->get_records_sql($sql);

        $universitiesinfo = array();
        foreach ($universities as $university) {

                $universityinfo = array();
                $universityinfo['universityid'] = $university->id;
                $universityinfo['fullname'] = $university->fullname;
                $universityinfo['shortname'] = $university->shortname;
                $universityinfo['description'] = $university->description;
				$universityinfo['parentid'] = $university->parentid;
                $universityinfo['visible'] = $university->visible;
                
                $universitiesinfo[] = $universityinfo;
            }
        
       return $universitiesinfo;
    }

    /**
     * Returns description of method result value
     * @return external_description
     */
    public static function costcenter_get_universities_returns() {
        return new external_multiple_structure(
                new external_single_structure(
                array(
            'universityid' => new external_value(PARAM_INT, 'University ID'),
            'fullname' => new external_value(PARAM_TEXT, 'Fullname of University'),
            'shortname' => new external_value(PARAM_TEXT, 'Shortname of University'),
            'description' => new external_value(PARAM_RAW, 'Description about University'),
            'parentid' => new external_value(PARAM_INT, 'Parent costcenter/university'),
            'visible' => new external_value(PARAM_INT, 'Active or Inactive')
                )
             )
        );
    }

/* get universities - ends */   
    
}


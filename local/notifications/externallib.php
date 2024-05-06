<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or localify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Feedback external API
 *
 * @package    local_onlinetests
 * @category   external
 * @copyright  2017 Juan Leyva <juan@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      Moodle 3.3
 */

defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir.'/externallib.php');

/**
 * Feedback external functions
 *
 * @package    local_onlinetests
 * @category   external
 * @copyright  2017 Juan Leyva <juan@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      Moodle 3.3
 */
class local_notifications_external extends external_api {
   
    
    
    
    /**
     * Describes the parameters for submit_create_group_form webservice.
     * @return external_function_parameters
     */
    public static function submit_create_notification_form_parameters() {
        return new external_function_parameters(
            array(
                'id' => new external_value(PARAM_INT, 'id', 0),
                'contextid' => new external_value(PARAM_INT, 'The context id for the onlinetests'),
                'jsonformdata' => new external_value(PARAM_RAW, 'The data from the create group form, encoded as a json array'),
                'form_status' => new external_value(PARAM_INT, 'Form position', 0)
            )
        );
    }
    
    /**
     * Submit the create group form.
     *
     * @param int $contextid The context id for the course.
     * @param string $jsonformdata The data from the form, encoded as a json array.
     * @return int new group id.
    */
    public static function submit_create_notification_form($id, $contextid, $jsonformdata, $form_status) {
        global $DB, $CFG, $USER;
        require_once($CFG->dirroot . '/local/notifications/lib.php');
 
        // We always must pass webservice params through validate_parameters.
        $params = self::validate_parameters(self::submit_create_notification_form_parameters(),
                                            ['id' => $id, 'contextid' => $contextid, 'jsonformdata' => $jsonformdata, 'form_status' => $form_status]);
 
        $context = context::instance_by_id($params['contextid'], MUST_EXIST);
 
        // We always must call validate_context in a webservice.
        self::validate_context($context);
        $serialiseddata = json_decode($params['jsonformdata']);
 
        $data = array();
        parse_str($serialiseddata, $data);
 
        $warnings = array();
        // The last param is the ajax submitted data.
        $mform = new local_notifications\forms\notification_form(null, array('form_status' => $form_status,'id' => $data['id'],'org'=>$data['costcenterid'],'moduleid'=>$data['moduleid']), 'post', '', null, true, $data);
        
        $validateddata = $mform->get_data();
        
        $lib = new \notifications();
        if ($validateddata) {
            if ($validateddata->id > 0) {
                $validateddata->usermodified = $USER->id;
                $validateddata->timemodified = time();
                if($form_status == 0){
                    $validateddata->moduleid=$data['moduleid'];
                    $validateddata->body = $validateddata->body['text'];
                    $insert = $lib->insert_update_record('local_notification_info', 'update', $validateddata);
                    if ($validateddata->moduleid){
                        $validateddata->moduleid = implode(',',$validateddata->moduleid);
                        $notif_type = $DB->get_field('local_notification_type', 'shortname', array('id'=>$validateddata->notificationid));
                        $notif_type_find=explode('_',$notif_type);
                        $validateddata->moduletype = $notif_type_find[0];
                    }else{
                        $validateddata->moduleid = NULL;
                        $notif_type = $DB->get_field('local_notification_type', 'shortname', array('id'=>$validateddata->notificationid));
                        $notif_type_find=explode('_',$notif_type);
                        $validateddata->moduletype = $notif_type_find[0];
                    }
                }else{
                    $validateddata->adminbody = $validateddata->adminbody['text'];
                }
              
                $insert = $lib->insert_update_record('local_notification_info', 'update', $validateddata);
            } else if ($validateddata->id <= 0) {
                $validateddata->moduleid=$data['moduleid'];
                $validateddata->body = $validateddata->body['text'];
                if ($validateddata->moduleid){
                    $validateddata->moduleid = implode(',',$validateddata->moduleid);
                    $notif_type = $DB->get_field('local_notification_type', 'shortname', array('id'=>$validateddata->notificationid));
                        $notif_type_find=explode('_',$notif_type);
                        $validateddata->moduletype = $notif_type_find[0];
                }else{
                    $validateddata->moduleid = NULL;
                    $notif_type = $DB->get_field('local_notification_type', 'shortname', array('id'=>$validateddata->notificationid));
                    $notif_type_find=explode('_',$notif_type);
                    $validateddata->moduletype = $notif_type_find[0];
                }
                $validateddata->usermodified = $USER->id;
                $validateddata->timemodified = time();
                $insert = $lib->insert_update_record('local_notification_info', 'insert', $validateddata);
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
            throw new moodle_exception('Error in submission');
        }

        $return = array(
            // 'error' => $error,
            'id' => $insert,
            'form_status' => $form_status);
 
        return $return;
    }
 
    /**
     * Returns description of method result value.
     *
     * @return external_description
     * @since Moodle 3.0
     */
    public static function submit_create_notification_form_returns() {
        return new external_single_structure(array(
            'id' => new external_value(PARAM_INT, 'notificationid'),
            'form_status' => new external_value(PARAM_INT, 'form_status'),
        ));
    }
}

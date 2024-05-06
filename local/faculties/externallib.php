<?php

// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
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
 * External Faculties API
 *
 * @package    lmsapi
 * @category   external
 * @copyright  2019 Pramod Kumar K <pramod@eabyas.in>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();
global $PAGE, $OUTPUT;
require_once("$CFG->libdir/externallib.php");

require_once($CFG->dirroot . '/user/lib.php');

/* Create master Faculties from API */
class local_faculties_faculties_from_api_external extends external_api {

    public static function create_faculties_from_api_parameters() {
        return new external_function_parameters(
                array(
            'params' => new external_value(PARAM_RAW, 'Faculty details', VALUE_DEFAULT, "")
                )
        );
    }

    public static function create_faculties_from_api($data) {
       global $DB, $CGF, $USER;
  
        $faculties = array();
        $det = json_decode($data, true);
        foreach ($det['Data'] as $faculty) {
            // Make sure that the SmbId, FcCode and FcDesc are not blank.
            foreach (array('SmbId', 'FcCode','FcDesc') as $fieldname) {
                if (trim($faculty[$fieldname]) === '') {
                    throw new invalid_parameter_exception('The field '.$fieldname.' cannot be blank');
                }
            }
            // Make sure mode is valid.
            if (empty($faculty['Mode'])) {
                throw new invalid_parameter_exception('Invalid authentication type: '.$program['Mode']);
            }

            $projectid = 15;
            $universityid = $DB->get_field('local_costcenter', 'id', array('projectid'=> $projectid));
            $boardid = $DB->get_field('local_boards','id', array('university'=> $universityid));

            if($faculty['Mode'] == "I"){

                 /*faculty creation*/
                $facultydata = new stdClass();
                $facultydata->facultyname = $faculty['FcDesc'];
                $facultydata->facultycode = $faculty['FcCode'];
                $facultydata->university = $universityid;
                $facultydata->board = $boardid;
                $facultydata->description = $faculty['FcDesc'];
                $facultydata->timecreated = time(); 
                $facultydata->timemodified = 0; 
                $facultydata->usercreated = $USER->id; 
                $facultydata->usermodified = 0;
                $facultydata->smbid = $faculty['SmbId'];
                
                $facultyid = $DB->insert_record('local_faculties',$facultydata);

            $faculties[] = array('id' => $facultyid, 'SmbId' => $faculty['SmbId']);

            }elseif($faculty['Mode'] == "U"){

                $facultyexist = $DB->get_record('local_faculties', array('smbid' => $faculty['SmbId']), 'id'); 
                
                $facultydataup = new stdClass();
                $facultydataup->facultyname = $faculty['FcDesc'];
                $facultydataup->timemodified = time(); 
                $siscourseup->usermodified = $USER->id; 
                $facultydataup->id = $facultyexist->id;
                $facultyid = $DB->update_record('local_faculties', $facultydataup);

                $faculties[] = array('id' => $facultyid, 'SmbId' => $faculty['SmbId']);
            }
        }
        
        return $faculties;
    }

    public static function create_faculties_from_api_returns() {
         return new external_multiple_structure(
            new external_single_structure(
                array(
                    'id'       => new external_value(PARAM_INT, 'Faculty id'),
                    'SmbId' => new external_value(PARAM_INT, 'Source System Unique id'),
                )
            )
        );
    }
}
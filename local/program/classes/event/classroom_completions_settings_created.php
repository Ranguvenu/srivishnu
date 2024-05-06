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
 * The local_classroom post created event.
 *
 * @package    local_classroom
 * @copyright  2014 Dan Poltawski <dan@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_program\event;

defined('MOODLE_INTERNAL') || die();

/**
 * The local_program post created event class.
 *
 * @property-read array $other {
 *      Extra information about the event.
 *
 *      - int discussionid: The discussion id the post is part of.
 *      - int classroomid: The classroom id the post is part of.
 *      - string classroomtype: The type of classroom the post is part of.
 * }
 *
 * @package    local_classroom
 * @since      Moodle 2.7
 * @copyright  2014 Dan Poltawski <dan@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class classroom_completions_settings_created extends \core\event\base {
    /**
     * Init method.
     *
     * @return void
     */
    protected function init() {
        $this->data['crud'] = 'c';
        $this->data['edulevel'] = self::LEVEL_PARTICIPATING;
        $this->data['objecttable'] = 'local_classroom';
    }

    /**
     * Returns description of what happened.
     *
     * @return string
     */
    public function get_description() {
        global $DB;
        $firstname=$DB->get_field_sql("SELECT concat(firstname,' ',lastname) FROM {user} where id=$this->userid");
        $classroom_id=$DB->get_field_sql("SELECT classroomid FROM {local_classroom_completion} where id=$this->objectid");
        if($classroom_id){
             $classroom_name=$DB->get_field_sql("SELECT name FROM {local_cc_semester_classrooms} where id=$classroom_id");
             return "The user with id '$firstname ($this->userid)' has created the classroom completions setting with id '$classroom_name ($classroom_id) '.";
        }else{
             return "The user with id '$firstname ($this->userid)' has created the classroom completions setting with id '$this->objectid'.";
        }
       
    }

    /**
     * Return localised event name.
     *
     * @return string
     */
    public static function get_name() {
        return get_string('eventclassroomcompletions_settings_created', 'local_program');
    }

    /**
     * Get URL related to the action
     *
     * @return \moodle_url
     */
    public function get_url() {
        global $DB;
        $classroom_id=$DB->get_field_sql("SELECT classroomid FROM {local_classroom_completion} where id=$this->objectid");
        if($classroom_id){
          $url = new \moodle_url('/local/program/view.php',array('cid'=>$classroom_id));
        }else{
          $url = new \moodle_url('/local/program/index.php');
        }
        $url->set_anchor('p'.$this->objectid);
        return $url;
    }

    /**
     * Return the legacy event log data.
     *
     * @return array|null
     */
    protected function get_legacy_logdata() {
        // The legacy log table expects a relative path to /local/forum/.
        $logurl = substr($this->get_url()->out_as_local_url(), strlen('/local/program/'));

        return array($this->objectid, 'classroom', 'add post', $logurl, $this->objectid, $this->contextinstanceid);
    }

    

    public static function get_objectid_mapping() {
        return array('db' => 'local_classroom', 'restore' => 'local_classroom');
    }

    public static function get_other_mapping() {
        $othermapped = array();
        $othermapped['classroomid'] = array('db' => 'local_classroom', 'restore' => 'local_classroom');
        return $othermapped;
    }
}

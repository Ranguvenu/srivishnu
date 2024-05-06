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
 * The local_curriculum post updated event.
 *
 * @package    local_curriculum
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_curriculum\event;

defined('MOODLE_INTERNAL') || die();

/**
 * The local_curriculum post updated event class.
 *
 * @package    local_curriculum
 * @since      Moodle 3.4
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class curriculum_updated extends \core\event\base {
    /**
     * Init method.
     *
     * @return void
     */
    protected function init() {
        $this->data['crud'] = 'u';
        $this->data['edulevel'] = self::LEVEL_OTHER;
        $this->data['objecttable'] = 'local_curriculum';
    }

    /**
     * Returns description of what happened.
     *
     * @return string
     */
    public function get_description() {
        global $DB, $USER;
        $username = fullname($USER);
        return "The user with id '$username ($USER->id)' has updated the curriculum with id '
        $this->objectid '.";
    }

    /**
     * Return localised event name.
     *
     * @return string
     */
    public static function get_name() {
        return get_string('eventcurriculumupdated', 'local_curriculum');
    }

    /**
     * Get URL related to the action
     *
     * @return \moodle_url
     */
  /*  public function get_url() {
        global $DB;
        $url = new \moodle_url('/local/program/programs.php', array('ccid' => $this->objectid));
        $url->set_anchor('p'.$this->objectid);
        return $url;
    }*/
}

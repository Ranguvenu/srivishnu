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
 * The local_curriculum post attendance_created_updated event.
 *
 * @package    local_curriculum
 * @copyright  2018 Arun Kumar M <arun@eabyas.in>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_program\event;

defined('MOODLE_INTERNAL') || die();

/**
 * The local_curriculum post attendance_created_updated event class.
 *
 * @property-read array $other {
 *      Extra information about the event.
 *
 *      - int discussionid: The discussion id the post is part of.
 *      - int curriculumid: The curriculum id the post is part of.
 *      - string curriculumtype: The type of curriculum the post is part of.
 * }
 *
 * @package    local_curriculum
 * @since      Moodle 3.4
 * @copyright  2018 Arun Kumar M <arun@eabyas.in>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class program_attendance_created_updated extends \core\event\base {
    /**
     * Init method.
     *
     * @return void
     */
    protected function init() {
        $this->data['crud'] = 'c';
        $this->data['edulevel'] = self::LEVEL_PARTICIPATING;
        $this->data['objecttable'] = 'local_program';
    }

    /**
     * Returns description of what happened.
     *
     * @return string
     */
    public function get_description() {
        global $DB;
    }

    /**
     * Return localised event name.
     *
     * @return string
     */
    public static function get_name() {
        return get_string('eventcurriculumattendance_created_updated', 'local_program');
    }
}

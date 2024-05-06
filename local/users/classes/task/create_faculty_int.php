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
 * A scheduled task for faculty creation.
 *
 * @todo MDL-44734 This job will be split up properly.
 *
 * @package    local_users
 * @copyright  2019 anusha@eabyas.in
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace local_users\task;
// require_once($CFG->dirroot . '/local/users/fetch_faculty.php');
class create_faculty_int extends \core\task\scheduled_task {

    /**
     * Get a descriptive name for this task (shown to admins).
     *
     * @return string
     */
    public function get_name() {
        return get_string('create_faculty_int', 'local_users');
    }

    /**
     * Run forum cron.
     */
    public function execute() {
        global $CFG;
        require_once($CFG->dirroot . '/local/users/lib.php');
        create_faculty();
    }
}

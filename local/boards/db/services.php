<?php

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
 * Web service local plugin template external functions and service definitions.
 *
 * @package    local school
 * @copyright  srilakshmi 2017
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// We defined the web service functions to install.

defined('MOODLE_INTERNAL') || die;
$functions = array(
        'local_boards_submit_boardform_data' => array(
                'classname'   => 'local_boards_external',
                'methodname'  => 'submit_boardform_data',
                'classpath'   => 'local/boards/classes/external.php',
                'description' => 'Submit form',
                'type'        => 'write',
                'ajax' => true,
        ),
        'local_boards_delete_board' => array(
            'classname'   => 'local_boards_external',
                'methodname'  => 'delete_board',
                'description' => 'deleting board',
                'type'        => 'write',
                'ajax' => true,
        ),
        'local_boards_suspend_board' => array(
                'classname'   => 'local_boards_external',
                'methodname'  => 'board_status_confirm',
                'description' => 'changing status of board',
                'type'        => 'write',
                'ajax' => true,
        ),
        'local_boards_form_option_selector' => array(
                'classname'   => 'local_boards_external',
                'methodname' => 'global_filters_form_option_selector',
                'classpath' => 'local/boards/classes/external.php',
                'description' => 'All global filters forms event handling',
                'ajax' => true,
                'type' => 'read',
        )
);


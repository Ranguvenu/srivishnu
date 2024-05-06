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
        'local_groups_submit_groupsform_data' => array(
                'classname'   => 'local_groups_external',
                'methodname'  => 'submit_groupsform_data',
                'classpath'   => 'local/groups/classes/external.php',
                'description' => 'Submit form',
                'type'        => 'write',
                'ajax' => true,
        ),
        'local_groups_delete_groups' => array(
            'classname'   => 'local_groups_external',
                'methodname'  => 'delete_groups',
                'description' => 'deleting groups',
                'type'        => 'write',
                'ajax' => true,
        ),
        'local_groups_suspend_groups' => array(
                'classname'   => 'local_groups_external',
                'methodname'  => 'groups_status_confirm',
                'description' => 'changing status of groups',
                'type'        => 'write',
                'ajax' => true,
        ),
        'local_groups_form_option_selector' => array(
                'classname'   => 'local_groups_external',
                'methodname' => 'global_filters_form_option_selector',
                'classpath' => 'local/groups/classes/external.php',
                'description' => 'All global filters forms event handling',
                'ajax' => true,
                'type' => 'read',
        )
);


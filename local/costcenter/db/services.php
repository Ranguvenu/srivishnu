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
 * @package    local costcenter
 * @copyright  srilakshmi 2017
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// We defined the web service functions to install.

defined('MOODLE_INTERNAL') || die;
$functions = array(
        'local_costcenter_submit_costcenterform_form' => array(
                'classname'   => 'local_costcenter_external',
                'methodname'  => 'submit_costcenterform_form',
                'classpath'   => 'local/costcenter/classes/external.php',
                'description' => 'Submit form',
                'type'        => 'write',
                'ajax' => true,
        ),
         'local_costcenter_status_confirm' => array(
                'classname'   => 'local_costcenter_external',
                'methodname'  => 'costcenter_status_confirm',
                'classpath'   => 'local/costcenter/classes/external.php',
                'description' => 'change the status',
                'type'        => 'write',
                'ajax' => true,
        ),
        'local_costcenter_delete_costcenter' => array(
                'classname'   => 'local_costcenter_external',
                'methodname'  => 'costcenter_delete_costcenter',
                'classpath'   => 'local/costcenter/classes/external.php',
                'description' => 'delete the costcenter',
                'type'        => 'write',
                'ajax' => true,
        ),
        'local_costcenter_get_universities' => array(
                'classname'   => 'local_costcenter_external',
                'methodname'  => 'costcenter_get_universities',
                'classpath'   => 'local/costcenter/classes/external.php',
                'description' => 'get universities ',
                'type'        => 'read',
                'ajax' => true,
        )
);

$services = array(
        'getuniversities' => array(
                'functions' => array ('local_costcenter_get_universities'),
                'restrictedusers' => 0,
                'enabled'=>1,
        )
);

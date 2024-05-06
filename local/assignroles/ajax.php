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
 * Handle ajax requests in curriculum
 *
 * @package    local_curriculums
 * @copyright  2018 Arun Kumar M {arun@eabyas.in}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define('AJAX_SCRIPT', true);
require_once(dirname(__FILE__) . '/../../config.php');
require_once("lib.php");
global $DB, $CFG, $USER, $PAGE;

$action = required_param('action', PARAM_ACTION);


require_login();
$PAGE->set_context($context);
$renderer = $PAGE->get_renderer('local_assignroles');
$costcenter = optional_param('costcenter', 0, PARAM_INT);
$roleid = optional_param('roleid',0,PARAM_INT);


switch ($action) {
    case 'userview':
            $userslist = find_users($costcenter,$roleid);
            echo json_encode(['data' => $userslist]);
    exit;
    break;
  
}

echo json_encode($return);


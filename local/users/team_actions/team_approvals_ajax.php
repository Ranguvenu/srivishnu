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
 * Course Allocation AJAX Response
 *
 * @package    block_courseallocation
 * @copyright  2017 Arun Kumar Mukka
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define('AJAX_SCRIPT', true);
require_once(dirname(dirname(dirname(dirname(__FILE__)))).'/config.php');

global $CFG, $USER, $PAGE;
use local_users\output\team_approvals;
use local_users\output\team_approvals_lib;
use context_system;

$systemcontext = context_system::instance();
require_login();
$PAGE->set_context($systemcontext);

$team_approvals = new team_approvals;
$team_approvals_lib = new team_approvals_lib();

$action = required_param('action', PARAM_TEXT);
$learningtype = optional_param('learningtype', '', PARAM_TEXT);
$search = optional_param('search', '', PARAM_RAW);
$requeststoapprove = optional_param('requeststoapprove', '', PARAM_RAW);

switch($action) {
    case 'searchdata':
        $return = $team_approvals->team_approval_records_list($learningtype, $search);
        // if($learningtype == 'elearning'){
        //     $return = $team_approvals->team_approval_records_list($learningtype, $search);
        // }elseif($learningtype == 'classroom'){
        //     $return = $team_approvals->team_approval_records_list($learningtype, $search);
        // }elseif($learningtype == 'program'){
        //     $return = $team_approvals->team_approval_records_list($learningtype, $search);
        // }else{
        //     $return = $team_approvals->team_approval_records_list($learningtype, $search);
        // }
    break;

    case 'change_learningtype':
        $return = $team_approvals->team_approval_records_list($learningtype, '');
    break;
    case 'requestapproved':
        $return = $team_approvals->team_requests_approved($learningtype, $requeststoapprove);
    break;
}
echo json_encode($return);
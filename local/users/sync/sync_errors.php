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
 * Bulk user registration script from a comma separated file
 * @package    local
 * @subpackage user
 * @copyright  2015 onwards Sreenivas <sreenivasula@eabyas.in>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once(dirname(__FILE__) . '/../../../config.php');

global $DB, $PAGE,$CFG,$OUTPUT;

$PAGE->requires->css('/local/users/css/jquery.dataTables.css');
$errorprocessingurl = new moodle_url('/local/users/sync/error_processing.php');
$PAGE->requires->js_call_amd('local_users/datatablesamd', 'syncErrorDatatable', array('url' => $errorprocessingurl, ));
$systemcontext = context_system::instance();
$PAGE->set_context($systemcontext);
$PAGE->set_url('/local/users/sync/sync_errors.php');
$PAGE->set_pagelayout('admin');
$strheading = 'Sync Errors';
$PAGE->set_title($strheading);
require_login();
$PAGE->navbar->add(get_string('pluginname', 'local_users'), new moodle_url('/local/users/index.php'));
$PAGE->navbar->add('Sync errors');
$PAGE->set_heading('Sync errors');
echo $OUTPUT->header();
if(!(has_capability('local/users:create',$systemcontext) || is_siteadmin())){
    echo print_error('no permission');
}
//echo "<h2 class='tmhead2'><div class='iconimage'></div>Sync Errors</h3>";
echo html_writer::link(new moodle_url('/local/users/'),'Back',array('id'=>'download_users'));

$table = new html_table();
$table->id = 'errors';
$table->head = array('Employee Id', 'Email', 'Mandatory Fields', 'Error', 'Sync Excuted By','Sync Excuted Date');
$table->align = array('center', 'left', 'center', 'left', 'center','center');
echo html_writer::table($table);

echo $OUTPUT->footer();
 
?>
<style>
	table#errors tr td{text-align: center;}
	table#errors tr td:nth-child(2){text-align: left;}
	table#errors tr td:nth-child(4){text-align: left;}
	.dataTables_length {
		width: 70% !important;
		float: left;
	}
</style>
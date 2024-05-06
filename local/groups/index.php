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
 * @package    local_groups
 * @copyright  2018 sreenivas
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once("../../config.php");
require_once("lib.php");
require_once($CFG->libdir.'/adminlib.php');

$contextid = optional_param('contextid', 0, PARAM_INT);
$page = optional_param('page', 0, PARAM_INT);
$searchquery  = optional_param('search', '', PARAM_RAW);
$showall = optional_param('showall', false, PARAM_BOOL);

require_login();

if ($contextid) {
    $context = context::instance_by_id($contextid, MUST_EXIST);
} else {
    $context = context_system::instance();
}

if ($context->contextlevel != CONTEXT_COURSECAT and $context->contextlevel != CONTEXT_SYSTEM) {
    print_error('invalidcontext');
}

$category = null;
if ($context->contextlevel == CONTEXT_COURSECAT) {
    $category = $DB->get_record('course_categories', array('id'=>$context->instanceid), '*', MUST_EXIST);
}

$manager = has_capability('moodle/cohort:manage', $context);
$canassign = has_capability('moodle/cohort:assign', $context);
if (!$manager) {
    require_capability('moodle/cohort:view', $context);
}

$strcohorts = get_string('cohorts', 'local_groups');

$PAGE->set_title($strcohorts);
$PAGE->set_heading(get_string('cohorts', 'local_groups'));
$PAGE->navbar->add(get_string('cohorts', 'local_groups'), new moodle_url('/local/groups/index.php', array('contextid' => $context->id)));
$PAGE->requires->js_call_amd('local_groups/renderselections', 'init');
$PAGE->requires->js_call_amd('local_groups/newgroups', 'load', array());
$PAGE->requires->css('/local/mooccourses/css/jquery.dataTables.css');
//$PAGE->requires->js_call_amd('local_groups/groupsview','groupsDatatable', array());
echo $OUTPUT->header();
$systemcontext = context_system::instance();

if ($showall) {
    $cohorts = local_groups_get_all_groups($page, 25, $searchquery);
} else {
    $cohorts = local_groups_get_groups($context->id, $page, 25, $searchquery);
}

$count = '';
if ($cohorts['allgroups'] > 0) {
    if ($searchquery === '') {
        $count = ' ('.$cohorts['allgroups'].')';
    } else {
        $count = ' ('.$cohorts['totalgroups'].'/'.$cohorts['allgroups'].')';
    }
}

//echo $OUTPUT->heading(get_string('cohorts', 'local_groups').$count);

$params = array('page' => $page);
if ($contextid) {
    $params['contextid'] = $contextid;
}
if ($searchquery) {
    $params['search'] = $searchquery;
}
if ($showall) {
    $params['showall'] = true;
}
$baseurl = new moodle_url('/local/groups/index.php', $params);

if ($editcontrols = local_groups_edit_controls($context, $baseurl)) {
  //  echo $OUTPUT->render($editcontrols);
}

// Add search form.
$search  = html_writer::start_tag('form', array('id'=>'searchcohortquery', 'method'=>'get', 'class' => 'form-inline search-cohort'));
$search .= html_writer::start_div('m-b-1');
$search .= html_writer::label(get_string('searchcohort', 'cohort'), 'cohort_search_q', true,
        array('class' => 'm-r-1')); // No : in form labels!
$search .= html_writer::empty_tag('input', array('id' => 'cohort_search_q', 'type' => 'text', 'name' => 'search',
        'value' => $searchquery, 'class' => 'form-control m-r-1'));
$search .= html_writer::empty_tag('input', array('type' => 'submit', 'value' => get_string('search', 'cohort'),
        'class' => 'btn btn-secondary'));
$search .= html_writer::empty_tag('input', array('type'=>'hidden', 'name'=>'contextid', 'value'=>$contextid));
$search .= html_writer::empty_tag('input', array('type'=>'hidden', 'name'=>'showall', 'value'=>$showall));
$search .= html_writer::end_div();
$search .= html_writer::end_tag('form');
//echo $search;

// Output pagination bar.
echo "<ul class='course_extended_menu_list'>
        <li>
            <div class='coursebackup course_extended_menu_itemcontainer'>
                <a class='course_extended_menu_itemlink' data-action='creategroupsmodal' data-value='0' title = 'Create Group' onclick ='(function(e){ require(\"local_groups/newgroups\").init({selector:\"creategroupmodal\", contextid:$systemcontext->id, id:0}) })(event)'><span class='createicon'><i class='icon fa fa-users' aria-hidden='true'></i></span></a>
            </div>
        </li>
    </ul>";
echo '<div class="col-12 page-desc"><b class="page-hrdesc">Description:</b><br>This page displays list of Groups under university/universities.';
echo $OUTPUT->paging_bar($cohorts['totalgroups'], $page, 25, $baseurl);

$local_groups = new local_groups($page, 25, $searchquery, $showall);
$output = $PAGE->get_renderer('local_groups');
echo $output->groups_view('','','');

echo $OUTPUT->footer();

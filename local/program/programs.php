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
 * Browse curriculums
 *
 * @package    local_curriculum
 * @copyright  2018 Arun Kumar M <arun@eabyas.in>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once(dirname(__FILE__) . '/../../config.php');
$systemcontext = context_system::instance();
require_login();
$id = optional_param('id', 0, PARAM_INT); // curriculum id
$hide = optional_param('hide', 0, PARAM_INT);
$show = optional_param('show', 0, PARAM_INT);

$PAGE->set_url($CFG->wwwroot . '/local/program/programs.php');
$PAGE->set_context($systemcontext);
$PAGE->set_title(get_string('manage_curriculum', 'local_program'));
$PAGE->set_heading(get_string('manage_curriculum', 'local_program'));
$PAGE->requires->jquery_plugin('ui-css');
$PAGE->requires->css('/local/program/css/jquery.dataTables.min.css', true);
$PAGE->requires->js_call_amd('local_program/ajaxforms', 'load');
$PAGE->requires->js_call_amd('local_program/program', 'curriculumDatatable',
                    array(array('curriculumstatus' => -1)));
$corecomponent = new core_component();
$epsilonpluginexist = $corecomponent::get_plugin_directory('theme', 'epsilon');
if (!empty($epsilonpluginexist)) {
    $PAGE->requires->js_call_amd('theme_epsilon/quickactions', 'quickactionsCall');
}
$renderer = $PAGE->get_renderer('local_program');
$PAGE->navbar->add(get_string("pluginname", 'local_program'), new moodle_url('index.php'));

echo $OUTPUT->header();

if(!has_capability('local/program:managecurriculum', $systemcontext) && !has_capability('local/program:viewcurriculum', $systemcontext)){
    print_error('You donot have permission this page.');
}else{
    // hide the curriculum.
    if ($hide AND $id) {
        $curriculum = $DB->get_record('local_curriculum', array('id'=>$id));
        $DB->set_field('local_curriculum', 'visible', 0, array('id'=>$id));
        redirect('index.php');
    }
    //show the curriculum
    if ($show AND $id) {
        $curriculum = $DB->get_record('local_curriculum', array('id'=>$id));
        $DB->set_field('local_curriculum', 'visible', 1, array('id'=>$id));
        redirect('index.php');
    }

    echo $renderer->get_curriculum_tabs();
}
//show the curriculum
if ($show AND $id) {
    $curriculum = $DB->get_record('local_curriculum', array('id'=>$id));
    $DB->set_field('local_curriculum', 'visible', 1, array('id'=>$id));
    redirect('index.php');
}

echo $renderer->get_curriculum_tabs();
echo $OUTPUT->footer();
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
 * Manage curriculum Form.
 *
 * @package    local_curriculum
 * @copyright  2018 Arun Kumar M <arun@eabyas.in>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_program\form;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');
require_once($CFG->libdir . '/completionlib.php');
use context_system;
use local_program\local\querylib;
use moodleform;
use core_component;

class program_form extends moodleform {
    public $formstatus;
    public function __construct($action = null, $customdata = null, $method = 'post',
        $target = '', $attributes = null, $editable = true, $formdata = null) {
        parent::__construct($action, $customdata, $method, $target, $attributes, $editable,
            $formdata);
    }

    public function definition() {
        global $CFG, $USER, $PAGE, $DB;
        $querieslib = new querylib();
        $mform = &$this->_form;
        $renderer = $PAGE->get_renderer('local_program');
        $context = context_system::instance();
        $formstatus = $this->_customdata['form_status'];
        $id = $this->_customdata['id'] > 0 ? $this->_customdata['id'] : 0;
        $programid= $this->_customdata['program'] > 0 ? $this->_customdata['program'] : 0;
        $formheaders = array_keys($this->formstatus);
        $formheader = $formheaders[$formstatus];

        $mform->addElement('hidden', 'id', $id);
        $mform->setType('id', PARAM_INT);

        $querieslib = new querylib();

        $program = $querieslib->get_curriculum_programlist($programid);

        $costcentername=$DB->get_field('local_costcenter','fullname',array('id'=>$program->costcenter));

        $mform->addElement('static', 'costcentername', get_string('costcenter', 'local_program'));
        $mform->setDefault('costcentername',$costcentername);


        $mform->addElement('static', 'programname', get_string('program', 'local_program'));
        $mform->setDefault('programname',$program->fullname);


        $mform->addElement('hidden', 'costcenter',
        get_string('costcenter', 'local_program'));
        $mform->setType('costcenter', PARAM_INT);
        $mform->setDefault('costcenter', $program->costcenter);

        $mform->addElement('hidden', 'program',
        get_string('program', 'local_program'));
        $mform->setType('program', PARAM_INT);
        $mform->setDefault('program', $programid);

        $mform->addElement('text', 'name', get_string('curriculum_name', 'local_program'), array());
        if (!empty($CFG->formatstringstriptags)) {
            $mform->setType('name', PARAM_TEXT);
        } else {
            $mform->setType('name', PARAM_CLEANHTML);
        }
       $mform->addRule('name', null, 'required', null, 'client');

        //$mform->addElement('filepicker', 'curriculumlogo',
        //        get_string('curriculumlogo', 'local_program'), null,
        //        array('maxbytes' => 2048000, 'accepted_types' => '.jpg'));

        $editoroptions = array(
            'noclean' => false,
            'autosave' => false
        );
        $mform->addElement('editor', 'cr_description',
                get_string('description', 'local_program'), null, $editoroptions);
        $mform->setType('cr_description', PARAM_RAW);
        $mform->addHelpButton('cr_description', 'description', 'local_program');

        $mform->disable_form_change_checker();
    }
    public function validation($data, $files) {
        global $CFG, $DB, $USER;

        $errors = parent::validation($data, $files);

        if (isset($data['name']) && empty(trim($data['name']))) {
            $errors['name'] = get_string('valnamerequired', 'local_program');
        }

        if (!isset($data['program']) || $data['program'] < 1) {
            $errors['program'] = 'You must supply a value here.';
        }
        return $errors;
    }

    public function set_data($components) {
        global $DB;
        $context = context_system::instance();
        $data = $DB->get_record('local_curriculum', array('id' => $components->id));
        $data->cr_description = array();
        $data->cr_description['text'] = $data->description;
        $draftitemid = file_get_submitted_draft_itemid('curriculumlogo');
        file_prepare_draft_area($draftitemid, $context->id, 'local_program', 'curriculumlogo',
            $data->curriculumlogo, null);
        $data->curriculumlogo = $draftitemid;
        parent::set_data($data);
    }
}
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
 * Bulk course upload forms
 *
 * @package    local
 * @subpackage sisprograms
 * @copyright  2019 S Sarath Kumar
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

require_once $CFG->libdir . '/formslib.php';

/**
 * Upload a file CSV file with course information.
 *
 * @copyright  2007 Petr Skoda  {@link http://skodak.org}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class admin_course_form1 extends moodleform {

    function definition() {
        $mform = $this->_form;

        $mform->addElement('header', 'settingsheader', get_string('upload'));

        $mform->addElement('filepicker', 'userfile', get_string('file'));
        $mform->addRule('userfile', null, 'required');

        $choices = csv_import_reader::get_delimiter_list();
        $mform->addElement('select', 'delimiter_name', get_string('csvdelimiter', 'local_sisprograms'), $choices);
        if (array_key_exists('cfg', $choices)) {
            $mform->setDefault('delimiter_name', 'cfg');
        } else if (get_string('listsep', 'langconfig') == ';') {
            $mform->setDefault('delimiter_name', 'semicolon');
        } else {
            $mform->setDefault('delimiter_name', 'comma');
        }

        $choices =  core_text::get_encodings();
        $mform->addElement('select', 'encoding', get_string('encoding', 'local_sisprograms'), $choices);
        $mform->setDefault('encoding', 'UTF-8');

        $choices = array('10' => 10, '20' => 20, '100' => 100, '1000' => 1000, '100000' => 100000);
        $mform->addElement('select', 'previewrows', get_string('rowpreviewnum', 'local_sisprograms'), $choices);
        $mform->setType('previewrows', PARAM_INT);

        $this->add_action_buttons(true, get_string('upload'));
    }

}


/**
 * Specify user upload details
 *
 * @copyright  2007 Petr Skoda  {@link http://skodak.org}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class admin_course_form2 extends moodleform {

    function definition() {
        global $CFG;

        $mform = $this->_form;
        $columns = $this->_customdata['columns'];
        $data = $this->_customdata['data'];

        // upload settings and file
        $mform->addElement('header', 'settingsheader', get_string('settings'));

        $choices = array('UU_USER_ADDNEW' => get_string('uuoptype_addnew', 'local_users'),
            'UU_USER_ADD_UPDATE' => get_string('uuoptype_addupdate', 'local_users'),
            'UU_USER_UPDATE' => get_string('uuoptype_update', 'local_users'));
        $mform->addElement('select', 'uutype', get_string('uuoptype', 'local_users'), $choices);


        $choices = array(UU_UPDATE_NOCHANGES => get_string('nochanges', 'local_users'),
            UU_UPDATE_FILEOVERRIDE => get_string('uuupdatefromfile', 'local_users'),
            UU_UPDATE_ALLOVERRIDE => get_string('uuupdateall', 'local_users'),
            UU_UPDATE_MISSING => get_string('uuupdatemissing', 'local_users'));
        $mform->addElement('select', 'uuupdatetype', get_string('uuupdatetype', 'local_users'), $choices);
        $mform->setDefault('uuupdatetype', UU_UPDATE_NOCHANGES);
        $mform->disabledIf('uuupdatetype', 'uutype', 'eq', 'UU_USER_ADDNEW');

        // hidden fields
        $mform->addElement('hidden', 'iid');
        $mform->setType('iid', PARAM_INT);

        $mform->addElement('hidden', 'previewrows');
        $mform->setType('previewrows', PARAM_INT);

        $this->add_action_buttons(true, get_string('upload'));

        $this->set_data($data);
    }

    /**
     * Form tweaks that depend on current data.
     */
    function definition_after_data() {
        $mform = $this->_form;
        $columns = $this->_customdata['columns'];

        foreach ($columns as $column) {
            if ($mform->elementExists($column)) {
                $mform->removeElement($column);
            }
        }
    }

    /**
     * Used to reformat the data from the editor component
     *
     * @return stdClass
     */
    function get_data() {
        $data = parent::get_data();

        if ($data !== null and isset($data->description)) {
            $data->descriptionformat = $data->description['format'];
            $data->description = $data->description['text'];
        }

        return $data;
    }

}
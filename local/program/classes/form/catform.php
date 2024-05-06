<?php
/*
 * This curriculum is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This curriculum is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this curriculum.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @package Local
 * @subpackage curriculum
 */
namespace local_program\form;
use core;
use moodleform;
use context_system;
if (!defined('MOODLE_INTERNAL')) {
	die('Direct access to this script is forbidden.'); // It must be included from a Moodle page.
}

require_once "{$CFG->dirroot}/lib/formslib.php";

class catform extends moodleform {

	/**
	 * Definition of the room form
	 */
	function definition() {
		global $DB;

		$mform = &$this->_form;
		$categoryid = $this->_customdata['id'];

		$selected_ins_name = $DB->get_records('local_costcenter');

		$mform->addElement('hidden', 'id', $instituteid);
		$mform->setType('id', PARAM_INT);

		$mform->addElement('text', 'fullname', get_string('category_name', 'local_program'));
		$mform->setType('fullname', PARAM_TEXT);
		$mform->addRule('fullname', null, 'required', null, 'client');

		$this->add_action_buttons();
	}
}
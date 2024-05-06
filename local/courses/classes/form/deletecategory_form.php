<?php

namespace local_courses\form;
use core;
use moodleform;
use context_system;
use context_coursecat;
require_once($CFG->dirroot . '/lib/formslib.php');
require_once($CFG->libdir . '/questionlib.php');
require_once($CFG->libdir.'/coursecatlib.php');
class deletecategory_form extends moodleform {
/**
	 * The coursecat object for that category being deleted.
	 * @var coursecat
	 */
	protected $coursecat;

	/**
	 * Defines the form.
	 */
	public function definition() {
		$mform = $this->_form;
		$this->coursecat = $this->_customdata;
		$categorycontext = context_coursecat::instance($this->coursecat->id);
		$categoryname = $this->coursecat->get_formatted_name();
		$candeletecontent = $this->coursecat->can_delete_full();
		// Get the list of categories we might be able to move to.
		$displaylist = $this->coursecat->move_content_targets_list();
		// Now build the options.
		$options = array();
		if ($displaylist) {
			$options[0] = get_string('movecontentstoanothercategory');
		}
		if ($candeletecontent) {
			$options[1] = get_string('deleteallcannotundo');
		}
		if (empty($options)) {
			print_error('youcannotdeletecategory', 'error', 'index.php', $categoryname);
		}
		// Now build the form.
		$mform->addElement('header', 'general', $categoryname);

		// Describe the contents of this category.
		$contents = '';
		if ($this->coursecat->has_children()) {
			$contents .= '<span>' . get_string('subcategories') . '</span>';
		}
		if ($this->coursecat->has_courses()) {
			$contents .= '<span>' . get_string('courses') . '</span>';
		}
		if (question_context_has_any_questions($categorycontext)) {
			$contents .= '<span>' . get_string('questionsinthequestionbank') . '</span>';
		}
		if (!empty($contents)) {
			// $mform->addElement('static', 'emptymessage', get_string('thiscategorycontains'), \html_writer::tag('ul', $contents));

			$mform->addElement('static', '', '', get_string('thiscategorycontains').\html_writer::tag('span',$contents, array('class'=>'pl-5px font-weight-bold')), '');

			$mform->addElement('static', '', '', 'You can not remove this category untill courses under it are removed ');

		} else {
			$mform->addElement('static', 'emptymessage', '', get_string('deletecategoryempty'));
			// $mform->addElement('static', 'emptymessage', '', get_string('deletecategoryempty'));

		}

		// Give the options for what to do.
		if (count($options) == 1) {
			$mform->addElement('select', 'fulldelete', get_string('whattodo'), $options);

			$optionkeys = array_keys($options);
			$option = reset($optionkeys);
			$mform->hardFreeze('fulldelete');
			$mform->setConstant('fulldelete', $option);
		}


		// if ($displaylist) {
		// 	$mform->addElement('select', 'newparent', get_string('movecategorycontentto'), $displaylist);
		// 	if (in_array($this->coursecat->parent, $displaylist)) {
		// 		$mform->setDefault('newparent', $this->coursecat->parent);
		// 	}
		// 	$mform->disabledIf('newparent', 'fulldelete', 'eq', '1');
		// }

		$mform->addElement('hidden', 'categoryid', $this->coursecat->id);
		$mform->setType('categoryid', PARAM_ALPHANUM);
		$mform->addElement('hidden', 'action', 'deletecategory');
		$mform->setType('action', PARAM_ALPHANUM);
		$mform->addElement('hidden', 'sure');
		// This gets set by default to ensure that if the user changes it manually we can detect it.
		$mform->setDefault('sure', md5(serialize($this->coursecat)));
		$mform->setType('sure', PARAM_ALPHANUM);

		//$this->add_action_buttons(true, get_string('delete'));
		 $mform->disable_form_change_checker();
	}

	/**
	 * Perform some extra moodle validation.
	 *
	 * @param array $data
	 * @param array $files
	 * @return array An array of errors.
	 */
	public function validation($data, $files) {
		$errors = parent::validation($data, $files);
		if ($data['sure'] !== md5(serialize($this->coursecat))) {
			$errors['categorylabel'] = get_string('categorymodifiedcancel');
		}

		return $errors;
	}
}
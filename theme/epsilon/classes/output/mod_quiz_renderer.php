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


namespace theme_epsilon\output;
defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/quiz/renderer.php');

use moodle_url;

/**
 * Overriden block settings renderer.
 *
 * @package    theme_epsilon
 * @copyright  2016 Frédéric Massart - FMCorz.net
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_quiz_renderer extends \mod_quiz_renderer {

    public function view_page($course, $quiz, $cm, $context, $viewobj) {
        $output = '';
        $output .= $this->view_information($quiz, $cm, $context, $viewobj->infomessages);
        $output .= $this->view_table($quiz, $context, $viewobj);
        $output .= $this->view_result_info($quiz, $context, $cm, $viewobj);
        $output .= $this->box($this->view_page_buttons($viewobj, $course), 'quizattempt');
        return $output;
    }
    
    public function view_page_buttons($viewobj, $course) {
        global $CFG;
        $output = '';
        if (!$viewobj->quizhasquestions) {
            $output .= $this->no_questions_message($viewobj->canedit, $viewobj->editurl);
        }
    
        $output .= $this->access_messages($viewobj->preventmessages);
    
        if ($viewobj->buttontext) {
            $output .= $this->start_attempt_button($viewobj->buttontext,
                    $viewobj->startattempturl, $viewobj->preflightcheckform,
                    $viewobj->popuprequired, $viewobj->popupoptions);
            
            if ($course->id == 1)
            $output .= $this->single_button(new moodle_url('/local/onlinetests'),
                    get_string('back'), 'get',
                    array('class' => 'continuebutton'));
        }
    
        if ($viewobj->showbacktocourse) {
            if ($course->id == 1)
            $output .= $this->single_button(new moodle_url('/local/onlinetests'),
                    get_string('back'), 'get',
                    array('class' => 'continuebutton'));
            else
            $output .= $this->single_button($viewobj->backtocourseurl,
                    get_string('backtocourse', 'quiz'), 'get',
                    array('class' => 'continuebutton'));
        }
    
        return $output;
    }

}

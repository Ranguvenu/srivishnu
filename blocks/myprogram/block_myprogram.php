<?php
/**
 * This file is part of eAbyas
 *
 * Copyright eAbyas Info Solutons Pvt Ltd, India
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author eabyas  <info@eabyas.in>
 */
defined('MOODLE_INTERNAL') || die();
class block_myprogram extends block_base {
	/**
     * block initializations
     */
    public function init() {
        $this->title   = get_string('pluginname', 'block_myprogram');
    }
    /**
     * block contents
     *
     * @return object
     */
    public function get_content() {
    	global $USER;
        // $this->page->requires->js_call_amd('block_myprogram/program', 'load');
    	$this->content = new stdClass;
    	$myprogram = new \block_myprogram\programlib($USER);
    	$this->content->text = $myprogram->block_content();
        return $this->content;
    }
    /**
     * block js requirements
     */
    public function get_required_javascript() {
		// $this->page->requires->jquery();
		// $this->page->requires->jquery_plugin('ui');
		// $this->page->requires->jquery_plugin('ui-css');
  //       $this->page->requires->js('/blocks/myprogram/js/program.js',true);
        // $this->page->requires->js_call_amd('block_myprogram/program', 'load');
    }
}
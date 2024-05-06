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
 * LearnerScript
 * A Moodle block for creating customizable reports
 * @package blocks
 * @author: eAbyas info solutions
 * @date: 2019
 */
namespace block_learnerscript\lsreports;
use block_learnerscript\local\pluginbase;

class plugin_college extends pluginbase {

    public function init() {
        $this->form = false;
        $this->unique = true;
        $this->singleselection = true;
        $this->placeholder = true;
        $this->maxlength = 0;
        $this->fullname = get_string('filtercollege', 'block_learnerscript');
        $this->reporttypes = array();
    }

    public function summary($data) {
        return get_string('filtercollege_summary', 'block_learnerscript');
    }

    public function execute($finalelements, $data, $filters) {
        $fcollege = isset($filters['filter_college']) ? $filters['filter_college'] : null;
        $filtercollege = optional_param('filter_college', $fcollege, PARAM_INT);
        if (!$filtercollege) {
            return $finalelements;
        }

        if ($this->report->type != 'sql' && $this->report->type != 'statistics') {
            return array($filtercollege);
        } else {
            if (preg_match("/%%FILTER_COMPANY:([^%]+)%%/i", $finalelements, $output)) {
                $replace = ' AND ' . $output[1] . ' = ' . $filtercollege;
                return str_replace('%%FILTER_COMPANY:' . $output[1] . '%%', $replace, $finalelements);
            }
        }
        return $finalelements;
    }
    public function filter_data($selectoption = true, $request = array()){
        $fcollege = isset($request['filter_college']) ? $request['filter_college'] : 0;
        $filtercollege = optional_param('filter_college', $fcollege, PARAM_RAW);
        $collegeoptions = (new \block_learnerscript\local\querylib)->filter_get_colleges($this, $selectoption, $filtercollege);
        return $collegeoptions;
    }
    public function selected_filter($selected, $request = array()) {
        $filterdata = $this->filter_data(false, $request);
        return $filterdata[$selected];
    }
    public function print_filter(&$mform) {
        global $DB, $CFG, $USER;
        $request = array_merge($_POST, $_GET);
        $collegeoptions = $this->filter_data(true, $request);
        if(!$this->placeholder){
            unset($collegeoptions[0]);
        }

        $select = $mform->addElement('select', 'filter_college', null, $collegeoptions,array('data-select2'=>true, 'data-maximum-selection-length' => $this->maxlength));
        $select->setHiddenLabel(true);
        if(!$this->singleselection){
            $select->setMultiple(true);
        }
        if($this->required){
            if (!empty(array_keys($collegeoptions)[1])) {
                $select->setSelected(array_keys($collegeoptions)[1]);
            }
        }
        $mform->setType('filter_college', PARAM_INT);
    }

}

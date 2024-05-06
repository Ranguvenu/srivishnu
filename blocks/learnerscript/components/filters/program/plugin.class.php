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
 * @author: Jahnavi
 * @date: 2023
 */
namespace block_learnerscript\lsreports;
use block_learnerscript\local\pluginbase;
use block_learnerscript\local\querylib;

class plugin_program extends pluginbase {

    public function init() {
        $this->form = false;
        $this->unique = true;
        $this->singleselection = true;
        $this->placeholder = true;
        $this->maxlength = 0;
        $this->fullname = get_string('filterprogram', 'block_learnerscript');
        $this->reporttypes = array();
        $this->filtertype = 'custom';
        if (!empty($this->reportclass->basicparams)) {
            foreach ($this->reportclass->basicparams as $basicparam) {
                if ($basicparam['name'] == 'program') {
                    $this->filtertype = 'basic';
                }
            }
        }
    }

    public function summary($data) {
        return get_string('filterprogram_summary', 'block_learnerscript');
    }

    public function execute($finalelements, $data, $filters) {
        $fprogram = isset($filters['filter_program']) ? $filters['filter_program'] : null;
        $filterprogram = optional_param('filter_program', $fprogram, PARAM_INT);
        if (!$filterprogram) {
            return $finalelements;
        }

        if ($this->report->type != 'sql') {
            return array($filterprogram);
        } else {
            if (preg_match("/%%FILTER_DEPARTMENT:([^%]+)%%/i", $finalelements, $output)) {
                $replace = ' AND ' . $output[1] . ' = ' . $filterprogram;
                return str_replace('%%FILTER_DEPARTMENT:' . $output[1] . '%%', $replace, $finalelements);
            }
        }
        return $finalelements;
    }
    public function filter_data($selectoption = true, $request = array()){
        global $DB;
        if ($this->reportclass->basicparams) {
            $basicparams = array_column($this->reportclass->basicparams, 'name');
            if (in_array('college', $basicparams)) {
                $collegeoptions = (new \block_learnerscript\local\querylib)->filter_get_colleges($this, false, false, false, false, false, false);
                $collegeids = array_keys($collegeoptions);
                if (empty($request['filter_college'])) {
                    $programcollegeid = array_shift($collegeids);
                } else {
                    $programcollegeid = $request['filter_college'];
                }
            } else {
                $programcollegeid = 0;
            }
        } else {
            $programcollegeid = 0;
        }
        $programdeptid = $DB->get_field_sql("SELECT id FROM {local_costcenter} WHERE parentid = $programcollegeid AND depth != 1 AND univ_dept_status = 0 ORDER BY id ASC LIMIT 0, 1");
        if (empty($programdeptid)) {
            $programdeptid = 0;
        }
        $programoptions = (new \block_learnerscript\local\querylib)->filter_get_programs($this, $selectoption, $programcollegeid, $programdeptid);
        return $programoptions;
    }
    public function selected_filter($selected, $request = array()) {
        $filterdata = $this->filter_data(true, $request);
        return $filterdata[$selected];
    }
    public function print_filter(&$mform) {
        global $DB, $CFG, $USER;
        $request = array_merge($_POST, $_GET);
        $programoptions = $this->filter_data(true, $request);
        if(!$this->placeholder || $this->filtertype == 'basic'){
            unset($programoptions[0]);
        }
        $select = $mform->addElement('select', 'filter_program', null, $programoptions,array('data-select2'=>true, 'data-maximum-selection-length' => $this->maxlength));
        $select->setHiddenLabel(true);
        if(!$this->singleselection){
            $select->setMultiple(true);
        }
        if($this->required){
            if (!empty(array_keys($programoptions)[1])) {
                $select->setSelected(array_keys($programoptions)[1]);
            }
        }
        $mform->setType('filter_program', PARAM_INT); 
        $mform->addElement('hidden', 'filter_program_type', $this->filtertype);
        $mform->setType('filter_program_type', PARAM_RAW);
    }

}

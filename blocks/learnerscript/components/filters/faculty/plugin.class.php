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
 * LearnerScript Reports
 * A Moodle block for creating customizable reports
 * @package blocks
 * @author: sudharani.sadula@moodle.com
 * @date: 2023
 */
namespace block_learnerscript\lsreports;
use block_learnerscript\local\pluginbase;
use stdClass;
use context_course;
class plugin_faculty extends pluginbase {

    public function init() {
        $this->form = false;
        $this->unique = true;
        $this->fullname = get_string('filterfaculty', 'block_learnerscript');
        $this->reporttypes = array('sql');
    }

    public function summary($data) {
        return get_string('filterfaculty_summary', 'block_learnerscript');
    }

    public function execute($finalelements, $data) {
        $filteruser = optional_param('filter_faculty', 0, PARAM_INT);
        if (!$filteruser) {
            return $finalelements;
        }

        if ($this->report->type != 'sql') {
            return array($filteruser);
        } else {
            if (preg_match("/%%FILTER_FACULTY:([^%]+)%%/i", $finalelements, $output)) {
                $replace = ' AND ' . $output[1] . ' = ' . $filteruser;
                return str_replace('%%FILTER_FACULTY:' . $output[1] . '%%', $replace, $finalelements);
            }
        }
        return $finalelements;
    }
     public function filter_data($selectoption = true, $request = array()){
         global $DB, $COURSE;

        $reportclassname = 'block_learnerscript\lsreports\report_' . $this->report->type;
        /*$reportclass = new $reportclassname($this->report);

        if ($this->report->type != 'sql') {
            $components = (new block_learnerscript\local\ls)->cr_unserialize($this->report->components);
            $conditions = $components['conditions'];
            $facultylist = $reportclass->elements_by_conditions($conditions);
        } else {
            $coursecontext = context_course::instance($COURSE->id);
            $facultylist = array_keys(get_faculties_by_capability($coursecontext, 'moodle/user:viewdetails'));
        }*/

        $facultyoptions = array();
        if($selectoption){
        $facultyoptions[0] = get_string('filter_faculty', 'block_learnerscript');
        }

        if (empty($facultylist)) {
            list($usql, $params) = $DB->get_in_or_equal($facultylist);
            $faculties = $DB->get_records_sql("SELECT u.id, concat(u.firstname,' ',u.lastname) AS fullname FROM {user} u JOIN {role_assignments} ra ON ra.userid=u.id JOIN {role} r ON r.id = ra.roleid WHERE r.shortname = 'faculty'");

            foreach ($faculties as $c) {
                $facultyoptions[$c->id] = $c->fullname;
            }
        }
        return $facultyoptions;
    }
    public function selected_filter($selected, $request) {
        $filterdata = $this->filter_data(true, $request);
        return $filterdata[$selected];
    }
    public function print_filter(&$mform) {
        $facultyoptions = $this->filter_data();
        $select = $mform->addElement('select', 'filter_faculty', get_string('faculty'), $facultyoptions);
        $select->setHiddenLabel(true);
        $mform->setType('filter_faculty', PARAM_INT);
    }

}

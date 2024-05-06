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

/** CobaltLMS Reports
  * A Moodle block for creating customizable reports
  * @package blocks
  * @author: Hemalatha arun <hemalatha@eabyas.in>
  * @date: 2013
  */

require_once($CFG->dirroot.'/blocks/configurable_reports/plugin.class.php');

class plugin_program extends plugin_base{
	
	function init(){
		$this->form = true;
		$this->unique = true;
		$this->fullname = get_string('filterprogram','block_configurable_reports');
		$this->reporttypes = array('courseactivities');
	}
	
	function summary($data){
		return $data->field;
	}
	
	function execute($finalelements, $data){		
		global $DB, $CFG;
		$filter_program =  optional_param('filter_program',0,PARAM_INT);

        if ($this->report->type != 'sql') {
            return array($filter_program);
        } else {
            if (preg_match("/%%FILTER_PROGRAM:([^%]+)%%/i", $finalelements, $output)) {
                $replace = ' AND ' . $output[1] . ' LIKE \'%' . $filter_semester . '%\'';
                return str_replace('%%FILTER_PROGRAM:' . $output[1] . '%%', $replace, $finalelements);
            }
        }
        return $finalelements;	

	}
	
	function print_filter(&$mform, $data){
		global $DB, $CFG,$PAGE,$USER;		
		
		$filter_program = optional_param('filter_program',0,PARAM_INT);
		$reportclassname = 'report_'.$this->report->type;	
		$reportclass = new $reportclassname($this->report);		

		$filteroptions = array();
		$filteroptions[0] = get_string('all');
		
		
		//if(!empty($programlist)){
		
			//list($usql, $params) = $DB->get_in_or_equal($programlist);
			if(empty($data->field))
			 $data->field="shortname";		
		    if(is_siteadmin()){
		        $sql = "SELECT * FROM {local_program}";
		    }else{
		        $sql = "SELECT * FROM {local_program} WHERE costcenter = ".$USER->open_costcenterid;
		    }
			
			if($rs = $DB->get_recordset_sql($sql)){
					foreach($rs as $r){				
						$filteroptions[$r->id] = format_string($r->fullname);
					}
					$rs->close();
				}
		//}
		$mform->addElement('select', 'filter_program', 'Program', $filteroptions);
		$mform->setType('filter_program', PARAM_INT);
	}
}

?>
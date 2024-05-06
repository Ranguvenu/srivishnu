<?php
namespace local_faculties\functions;

class facultylibfunctions{
	public function facultyform_boardslist($universityid){
	    global $DB;
	    if($universityid) {
		    $sql = "select id,fullname from {local_boards} where university = $universityid";
		    $boards = $DB->get_records_sql($sql);
	      	return $boards;
	  	}else {
	  		return $universityid;
	  	}
	}
}
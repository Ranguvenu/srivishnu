<?php
// namespace local_costcenter\taskclasses;
require_once(dirname(__FILE__) . '/../../config.php');


     function inttovancode($int = 0) {
        $num = base_convert((int) $int, 10, 36);
        $length = strlen($num);
        return chr($length + ord('0') - 1) . $num;
    }

    /**
     * Convert a vancode to an integer
     * @param string $char Vancode to convert. Must be <= '9zzzzzzzzzz'
     * @return integer The integer representation of the specified vancode
     */
    function vancodetoint($char = '00') {
        return base_convert(substr($char, 1), 36, 10);
    }

    /**
     * Increment a vancode by N (or decrement if negative)
     *
     */
    function increment_vancode($char, $inc = 1) {
        return inttovancode(vancodetoint($char) + (int) $inc);
    }

     function increment_sortorder($sortorder, $inc = 1) {
        if (!$lastdot = strrpos($sortorder, '.')) {
            // root level, just increment the whole thing
            return increment_vancode($sortorder, $inc);
        }
        $start = substr($sortorder, 0, $lastdot + 1);
        $last = substr($sortorder, $lastdot + 1);
        // increment the last vancode in the sequence
        return $start . increment_vancode($last, $inc);
    }

function get_next_child_sortthread($parentid, $table) {
        global $DB, $CFG;
        $maxthread_sql = "";
        $maxthread_sql .= "SELECT MAX(sortorder) AS sortorder FROM {$CFG->prefix}{$table} WHERE 1=1 ";

        if($table == 'local_costcenter'){
          $maxthread_sql .= " AND parentid = ?";  
        }else{
          $maxthread_sql .= " AND parent = ?";
        }
        $maxthread = $DB->get_record_sql($maxthread_sql, array($parentid));
        
        //  echo "the parentid".$parentid;
        if (!$maxthread || strlen($maxthread->sortorder) == 0) {
            if ($parentid == 0) {
                // first top level item
                return inttovancode(1);
            } else {
                // parent has no children yet
                return $DB->get_field('local_school', 'sortorder', array('id' => $parentid)) . '.' . inttovancode(1);
            }
        }
        return increment_sortorder($maxthread->sortorder);
    }

  function fetch_institutes_fromapi() {
   global $DB, $USER, $CFG;
 
    $curl = curl_init();

    curl_setopt_array($curl, array(
      CURLOPT_URL => "https://amsiapi13-live-130002.campusnexus.cloud/API/LMS/InstituteDetails",
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_TIMEOUT => 30,
      CURLOPT_CUSTOMREQUEST => "GET",
      CURLOPT_SSL_VERIFYHOST => 0,
      CURLOPT_SSL_VERIFYPEER => 0,
      CURLOPT_HTTPHEADER => array(
        "Authorization: Basic ". base64_encode('IUMSAPI:CQVPS0814C'),
        "Content-Type: application/json"
      ),
    ));

    $response = curl_exec($curl);
    $err = curl_error($curl);

    curl_close($curl);

    if ($err) {
      echo "cURL Error #:" . $err;
    } else {
        $institutes = json_decode($response);
    }

    foreach($institutes as $inst){
   
        $inst->project_id = 1; //temporary value for now get the college university id later from project_id incoming param

        //$hierarchy = new hierarchy();
        if (!$sortorder = get_next_child_sortthread($inst->project_id, 'local_costcenter')) {
              return false;
        }
        
        $path = $DB->get_records('local_costcenter', array('parentid' => $inst->project_id), 'path DESC', 'id, path ', 0,1 );
       
        $projectid = 15;
        $universityid = $DB->get_field('local_costcenter', 'id', array('projectid'=> $projectid));
        
        foreach($path as $key => $value)
        {     
              $path = '1/'. ($key+1);
              $instituteinfo = new stdClass();
              $instituteinfo->fullname = $inst->institute_name;
              $instituteinfo->shortname = $inst->institute_code;
              $instituteinfo->parentid = $universityid;
              $instituteinfo->timecreated = time();
              $instituteinfo->timemodified = 0;
              $instituteinfo->usermodified = $USER->id;
              $instituteinfo->path = $path;
              $instituteinfo->depth = 2;
              $instituteinfo->sortorder =$sortorder;
              $instituteinfo->category =1;

              $existcollege = $DB->get_record('local_costcenter', array('shortname' => $inst->institute_code), 'id');
              if(!$existcollege){
                 $result = $DB->insert_record('local_costcenter', $instituteinfo);
              }else{
                $instituteinfoup = new stdClass();
                $instituteinfoup->id = $existcollege->id;
                $instituteinfoup->fullname = $inst->institute_name;
                $instituteinfo->timemodified = time();
                $instituteinfo->usermodified = $USER->id;
                $userid = $DB->update_record('local_costcenter', $instituteinfoup);
              }
    }
}
}

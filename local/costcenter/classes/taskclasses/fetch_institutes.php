<?php
namespace local_costcenter\taskclasses;
require_once($CFG->dirroot.'/config.php');

// 
class fetch_institutes {

   public function inttovancode($int = 0) {
        $num = base_convert((int) $int, 10, 36);
        $length = strlen($num);
        return chr($length + ord('0') - 1) . $num;
    }

    /**
     * Convert a vancode to an integer
     * @param string $char Vancode to convert. Must be <= '9zzzzzzzzzz'
     * @return integer The integer representation of the specified vancode
     */
    public function vancodetoint($char = '00') {
        return base_convert(substr($char, 1), 36, 10);
    }

    /**
     * Increment a vancode by N (or decrement if negative)
     *
     */
    public function increment_vancode($char, $inc = 1) {
        return self::inttovancode(self::vancodetoint($char) + (int) $inc);
    }

    public function increment_sortorder($sortorder, $inc = 1) {
        if (!$lastdot = strrpos($sortorder, '.')) {
            // root level, just increment the whole thing
            return self::increment_vancode($sortorder, $inc);
        }
        $start = substr($sortorder, 0, $lastdot + 1);
        $last = substr($sortorder, $lastdot + 1);
        // increment the last vancode in the sequence
        return $start . self::increment_vancode($last, $inc);
    }

    public function get_next_child_sortthread($parentid, $table) {
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
                return self::inttovancode(1);
            } else {
                // parent has no children yet
                return $DB->get_field('local_school', 'sortorder', array('id' => $parentid)) . '.' . self::inttovancode(1);
            }
        }
        return self::increment_sortorder($maxthread->sortorder);
    }

  public function fetch_institutes_fromapi() {
   
    global $DB, $USER, $CFG;
    require_once($CFG->dirroot.'/local/lib.php');

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
      //print_r($inst);

    $inst->project_id = 1; //temporary value for now get the college university id later from project_id incoming param

    //$hierarchy = new hierarchy();
      if (!$sortorder = self::get_next_child_sortthread($inst->project_id, 'local_costcenter')) {
            return false;
      }
      
      $path = $DB->get_records('local_costcenter', array('parentid' => $inst->project_id), 'path DESC', 'id, path ', 0,1 );
   
    foreach($path as $key => $value)
    {     
          $path = '1/'. ($key+1);
          $instituteinfo = new stdClass();
          $instituteinfo->fullname = $inst->institute_name;
          $instituteinfo->shortname = $inst->institute_code;
          $instituteinfo->parentid = $inst->project_id;
          $instituteinfo->timecreated = time();
          $instituteinfo->timemodified = time();
          $instituteinfo->usermodified = 2;
          $instituteinfo->path = $path;
          $instituteinfo->depth = 2;
          $instituteinfo->sortorder =$sortorder;
          $instituteinfo->category =1;
    }
    print_object($instituteinfo);
    $result = $DB->insert_record('local_costcenter', $instituteinfo);
  }
}
}

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
 * Bulk user registration functions
 *
 * @package    local
 * @subpackage sisprograms
 * @copyright  2019 onwards Sarath Kumar
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();


/**
 * Tracking of processed users.
 *
 * This class prints user information into a html table.
 *
 * @package    core
 * @subpackage admin
 * @copyright  2007 Petr Skoda  {@link http://skodak.org}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class uu_progress_tracker {

    private $_row;
    public $columns = array('subjectcode', 'subjectname','programcode','programname', 'duration','runningfromyear','universitycode','universityname');

    /**
     * Flush previous line and start a new one.
     * @return void
     */
    public function flush() {
        if (empty($this->_row) or empty($this->_row['line']['normal'])) {
            // Nothing to print - each line has to have at least number
            $this->_row = array();
            foreach ($this->columns as $col) {
                $this->_row[$col] = array('normal' => '', 'info' => '', 'warning' => '', 'error' => '');
            }
            return;
        }
        $ci = 0;
        $ri = 1;
        echo '<tr class="r' . $ri . '">';
        foreach ($this->_row as $key => $field) {
            foreach ($field as $type => $content) {
                if ($field[$type] !== '') {
                    $field[$type] = '<span class="uu' . $type . '">' . $field[$type] . '</span>';
                } else {
                    unset($field[$type]);
                }
            }
            echo '<td class="cell c' . $ci++ . '">';
            if (!empty($field)) {
                echo implode('<br />', $field);
            } else {
                echo '&nbsp;';
            }
            echo '</td>';
        }
        echo '</tr>';
        foreach ($this->columns as $col) {
            $this->_row[$col] = array('normal' => '', 'info' => '', 'warning' => '', 'error' => '');
        }
    }

    /**
     * Add tracking info
     * @param string $col name of column
     * @param string $msg message
     * @param string $level 'normal', 'warning' or 'error'
     * @param bool $merge true means add as new line, false means override all previous text of the same type
     * @return void
     */
    public function track($col, $msg, $level = 'normal', $merge = true) {

        if (empty($this->_row)) {
            $this->flush(); //init arrays
        }
        if (!in_array($col, $this->columns)) {
            debugging('Incorrect column:' . $col);
            return;
        }
        if ($merge) {
            if ($this->_row[$col][$level] != '') {
                $this->_row[$col][$level] .='<br />';
            }
            $this->_row[$col][$level] .= $msg;
        } else {
            $this->_row[$col][$level] = $msg;
        }
    }

    /**
     * Print the table end
     * @return void
     */
    public function close() {
        
    }

}

/**
 * Validation callback function - verified the column line of csv file.
 * Converts standard column names to lowercase.
 * @param csv_import_reader $cir
 * @param array $stdfields standard user fields
 * @param array $profilefields custom profile fields
 * @param moodle_url $returnurl return url in case of any error
 * @return array list of fields
 */
function uu_validate_course_upload_columns(csv_import_reader $cir, $stdfields, $profilefields, moodle_url $returnurl) {
    $columns = $cir->get_columns();

    if (empty($columns)) {
        $cir->close();
        $cir->cleanup();
        print_error('cannotreadtmpfile', 'error', $returnurl);
    }
    if (count($columns) < 2) {
        $cir->close();
        $cir->cleanup();
        print_error('csvfewcolumns', 'error', $returnurl);
    }

    // test columns
    $processed = array();

    foreach ($columns as $key => $unused) {
        $field = $columns[$key];
        $lcfield = core_text::strtolower($field);
        if (in_array($field, $stdfields) or in_array($lcfield, $stdfields)) {
            // standard fields are only lowercase
            $newfield = $lcfield;
        } else if (in_array($field, $profilefields)) {
            // exact profile field name match - these are case sensitive
            $newfield = $field;
        } else if (in_array($lcfield, $profilefields)) {
            // hack: somebody wrote uppercase in csv file, but the system knows only lowercase profile field
            $newfield = $lcfield;
        } else if (preg_match('/^(cohort|user|group|type|role|enrolperiod)\d+$/', $lcfield)) {
            // special fields for enrolments
            $newfield = $lcfield;
        } else {
            $cir->close();
            $cir->cleanup();
            print_error('invalidfieldname', 'error', $returnurl, $field);
        }
        if (in_array($newfield, $processed)) {
            $cir->close();
            $cir->cleanup();
            print_error('duplicatefieldname', 'error', $returnurl, $newfield);
        }
        $processed[$key] = $newfield;
        
    }
    return $processed;
}

function insert_newuser($data) {
    global $DB, $USER, $CFG;
    $data->confirmed = 1;
    $data->mnethostid = 1;
    if (!$user = $DB->get_record('user', array('email' => $data->email))) {
        $data->id = $DB->insert_record('user', $data);
        $data->userid = $data->id;
    } else {
        $data->userid = $user->id;
    }
    if (!$localuser = $DB->get_record('local_users', array('userid' => $data->userid))) {
        $data->localid = $DB->insert_record('local_users', $data);
    }


    $ctx = new stdClass();
    $ctx->id = -1;
    $ctx->contextlevel = CONTEXT_USER;
    $ctx->instanceid = $data->userid;
    $ctx->depth = 2;
    if (!$context = $DB->get_record('context', array('contextlevel' => $ctx->contextlevel, 'instanceid' => $ctx->instanceid))) {
        $ctx->id = $DB->insert_record('context', $ctx);
        $ctx->path = '/1/' . $ctx->id;
        $DB->update_record('context', $ctx);
    } else {
        $ctx->id = $context->id;
    }
    $role = new stdClass();
    $role->id = -1;
    $role->roleid = $data->roleid;
    $role->contextid = $DB->get_field('context', 'id', array('contextlevel' => CONTEXT_SYSTEM));
    $role->userid = $data->userid;
    $role->timemodified = time();
    $role->modifierid = $USER->id;
    if (!$roleid = $DB->get_record('role_assignments', array('roleid' => $role->roleid, 'contextid' => $role->contextid, 'userid' => $role->userid))) {
        $role->id = $DB->insert_record('role_assignments', $role);
    } else {
        $role->id = $roleid->id;
    }
    $scl = new stdClass();
    $scl->id = -1;
    $scl->userid = $data->userid;
    $scl->costcenterid = $data->costcenterid;
    $scl->roleid = $data->roleid;
    $scl->timecreated = time();
    $scl->timemodified = time();
    $scl->usermodified = $USER->id;
    if (!$school = $DB->get_record('local_costcenter_permissions', array('userid' => $scl->userid, 'costcenterid' => $scl->costcenterid, 'roleid' => $scl->roleid))) {
        $scl->id = $DB->insert_record('local_costcenter_permissions', $scl);
    } else {
        $scl->id = $school->id;
    }
    return $data->userid;
}
